PHP-cURL-lib-for-Bigcommerce-API
================================

Require the file in your script as follows:

require 'connection.php';

Instantiate connection class as such:

$store = new connection('Username', 'API path', 'API token');

call various methods to the connection

$store->get('RESOURCE'); <html><br/></html>

$store->delete('RESOURCE');

$store->post('RESOURCE', $fields);

$store->put('RESOURCE', $fields);

If the request fails the error details will be stored in the $error var.

If the requests per hour limit reaches 100 or less the library will automatically take 5 minutes between requests in order to provide enough
time for the requests to regenerate.  Once the request limit is above 100 the library will resume requests at a normal frequency.

