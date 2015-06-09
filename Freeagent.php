<?php

/*
---

Freeagent-PHP:
A PHP toolkit for accessing Freeagent

version: 0.1.0

copyrights:
  - [Ryan Mitchell](@ryanhmitchell)

license:
  - [MIT License]

---
*/

class Freeagent {
        
    // credentials
    private $clientId;
    private $clientSecret;
    
    // oauth urls
    private $oauthAuthoriseURL = 'https://api.freeagent.com/v2/approve_app';
    private $oauthAccessTokenURL = 'https://api.freeagent.com/v2/token_endpoint';
    private $accessToken;
    
    // base url for api calls
    private $apiUrl = 'https://api.freeagent.com/v2/';
    
    // debug mode
    private $debug = false;
        
    // default constructor
    function __construct($clientId, $clientSecret, $sandbox=false){
    
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;

        if ($sandbox==true){
			$this->oauthAuthoriseURL = preg_replace('/api/', 'api.sandbox', $this->oauthAuthoriseURL);
			$this->oauthAccessTokenURL = preg_replace('/api/', 'api.sandbox', $this->oauthAccessTokenURL);
			$this->apiUrl = preg_replace('/api/', 'api.sandbox', $this->apiUrl);
        }

    }
    
    // pass off to oauth authorise url
    public function getAuthoriseURL($callback){
            
        $authoriseURL  = $this->oauthAuthoriseURL;
        $authoriseURL .= '?response_type=code';
        $authoriseURL .= '&client_id='.$this->clientId;
        $authoriseURL .= '&redirect_uri='.urlencode($callback);
        
        return $authoriseURL;
        
    }
    
    // convert an oauth code to an access token
    public function getAccessToken($code, $callback){
        
        // params to send to oauth receiver
        $params = array(
            'code' => $code,
            'grant_type' => 'authorization_code',
            'redirect_uri' => $callback
        );
        
        // call oauth
        $result = $this->call('', 'oauth', $params);
        
        // Save accessToken
        $this->accessToken = $result->access_token;
    
        // Return the response as an array
        return $result;
    }
    
    // refresh token - they expire after 7 days
    public function refreshToken($refreshToken){
        
        // params to send to oauth receiver
        $params = array(
            'refresh_token' => $refreshToken,
            'grant_type' => 'refresh_token',
        );
        
        // call oauth
        $result = $this->call('', 'oauth', $params);

        // Save accessToken
        $this->accessToken = $result->access_token;
    
        // Return the response as an array
        return $result;
    }
    
    public function setAccessToken($accessToken){
        $this->accessToken = $accessToken;
    }
    
    public function get($endpoint, $data=array()){
        return $this->call($endpoint, 'get', $data);
    }
    
    public function post($endpoint, $data){
        return $this->call($endpoint, 'post', $data);
    }
    
    public function put($endpoint, $data){
        return $this->call($endpoint, 'put', $data);
    }
    
    public function delete($endpoint, $data){
        return $this->call($endpoint, 'delete', $data);
    }   
    
    /**************************************************************************
    * Private functions
    **************************************************************************/
    
    
	private function parseHeaders($header){
		
		$retVal = array();
		$fields = explode("\r\n", preg_replace('/\x0D\x0A[\x09\x20]+/', ' ', $header));
		
		foreach ($fields as $field){
			if (preg_match('/([^:]+): (.+)/m', $field, $match)){
				$match[1] = preg_replace_callback('/(?<=^|[\x09\x20\x2D])./',
                    create_function ('$matches', 'return strtoupper("\0");'), strtolower(trim($match[1])));
				if (isset($retVal[$match[1]])){
					$retVal[$match[1]] = array($retVal[$match[1]], $match[2]);
				} else {
					$retVal[$match[1]] = trim($match[2]);
				}
			}
		}
		
		return $retVal;
	            
	}
    
    private function call($endpoint, $type, $data=array()){

        $ch = curl_init();
        
        // Setup curl options
        $curl_options = array(
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 60,
            CURLOPT_USERAGENT      => 'Depot-PHP'
        );
        
        $splitBody = false;
        
        // Set curl url to call
        if ($type == 'oauth'){
            $curlURL = $this->oauthAccessTokenURL;
        } else {
            $curlURL = $this->apiUrl.$endpoint;
            $curl_options += array(
            	CURLOPT_HTTPHEADER => array(
            		'Authorization: Bearer '.$this->accessToken,
            		'Accept: application/json',
            		'Content-Type: application/json',
            	),
            	CURLOPT_HEADER => 1
            );  
            
            $splitBody = true;        	
        }
                                                        
        // type of request determines our headers
        switch($type){
        
            case 'post':
                $curl_options = $curl_options + array(
					CURLOPT_POST        => 1,
					CURLOPT_POSTFIELDS  => json_encode($data)
                );
            break;
                
            case 'put':
                $curl_options = $curl_options + array(
					CURLOPT_CUSTOMREQUEST => 'PUT',
					CURLOPT_POSTFIELDS  => json_encode($data)
                );
            break;
                         
            case 'delete':
                $curl_options = $curl_options + array(
                	CURLOPT_CUSTOMREQUEST => 'DELETE',
                    CURLOPT_POSTFIELDS  => $data
                );
            break;
                                                
            case 'get':
            	$curlURL .= '?&'.http_build_query($data);
                $curl_options = $curl_options + array(
                );
            break;
                
            case 'oauth':
                $curl_options = $curl_options + array(
                	CURLOPT_USERPWD	   => $this->clientId.':'.$this->clientSecret,
                    CURLOPT_POST       => 1,
                    CURLOPT_POSTFIELDS => $data
                );
            break;
            
        }
                
        // add url
        $curl_options = $curl_options + array(
			CURLOPT_URL => $curlURL
        );
                                                                
        // Set curl options
        curl_setopt_array($ch, $curl_options);
        
        // Send the request
        $this->result = curl_exec($ch);
                
        // freeagent returns location: for some responses
        if ($splitBody){
	        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
			$header = substr($this->result, 0, $header_size);
			$this->result = substr($this->result, $header_size);
			$this->headers = $this->parseHeaders($header);
        }
        
        // curl info
        $this->info = curl_getinfo($ch);
        
        if ($this->debug){
            var_dump($this->result);
            var_dump($this->info);
        }
        
        // Close the connection
        curl_close($ch);
        
        return json_decode($this->result);
    }
    
}
	
?>
