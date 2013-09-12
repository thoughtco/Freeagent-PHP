<?

/*
---

An example of how to use the PHP kit
---
*/

	ini_set('display_errors', 'on');

	// include class
	include('../Freeagent.php');
	
	// enter your app's config details here
	$client = new Freeagent('your-app-id', 'your-app-secret');
	
	// no OAuth code set
	if (!isset($_GET['code'])){
	
		// get the authorisation url we need to pass customer to, passing the url you want them returned to (This url)
		$authoriseURL = $client->getAuthoriseURL('http://this.url');
		
		header('Location: '.$authoriseURL);
		exit();
	
	
	// we have a code
	} else {
	
		// now exchange the code for an access token - you should save this for future usage
		$accessToken = $client->getAccessToken($_GET['code'], 'http://this.url');
	
		// set authentication token
		$client->setAccessToken($accessToken);
		
	    echo '
	    	<p>Lets test the API by pulling down contacts</p>	
	    	<pre>';
	    
	    print_r($client->get('contacts'));
	    
	    echo '
	    	</pre>
	    ';
    
	}
	    
?>