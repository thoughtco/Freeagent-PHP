Freeagent-PHP
=============

A PHP toolkit for accessing the FreeAgent API.

--------------

### Installation

To install simply include the Freeagent.php file in your PHP script, and instantiate the class. 

```php
include('/path/to/Freeagent.php');
$client = new Freeagent('your-app-id', 'your-app-secret');
```

### Authentication
Authentication is through the OAuth Protocol.
An example flow is included in the examples folder.


### Usage
Once you have authenticated you can begin using the toolkit to call the API.

#### GET requests
GET requests are made to get existing resources, and work as follows:

```php
$client->get('resource_name', array('optional_param' => 'value'));
```

#### POST requests
POST requests are made to create new resources, and work as follows:

```php
$client->post('resource_name', array('required_param' => 'value'));
```

#### PUT requests
PUT requests are made to update existing resources, and work as follows:

```php
$client->put('resource_name', array('param_to_update' => 'value'));
```

#### DELETE requests
DELETE requests are made to delete existing resources, and work as follows:

```php
$client->delete('resource_name');
```


### Resources
The list of resources consumable by the API can be found at https://dev.freeagent.com/docs

### Support
If you require help or further explanation, feel free to message us on twitter (@thoughtco @ryanhmitchell)