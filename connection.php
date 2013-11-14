<?php
/**
 *Class to instantiate different api connections
 * 
 * @author Adam Bilsing <adambilsing@gmail.com
 */
class connection
{
	/**
	 *public and private variables 
	 *
	 * @var string stores data for the class
	 */
	static public $_path;
	static private $_user;
	static private $_token;
	static private $_headers;

	/**
	 * Sets $_path, $_user, $_token, $_headers upon class instantiation
	 * 
	 * @param $user, $path, $token required for the class
	 * @return void
	 */
	public function __construct($user, $path, $token) {
		$path = explode('/api/v2/', $path);
		$this->_path = $path[0];
		$this->_user = $user;
		$this->_token = $token;

		$encodedToken = base64_encode($this->_user.":".$this->_token);

		$authHeaderString = 'Authorization: Basic ' . $encodedToken;
		$this->_headers = array($authHeaderString, 'Accept: application/json','Content-Type: application/json');

	}	


	public static function http_parse_headers( $header )
    {
        $retVal = array();
        $fields = explode("\r\n", preg_replace('/\x0D\x0A[\x09\x20]+/', ' ', $header));
        foreach( $fields as $field ) {
            if( preg_match('/([^:]+): (.+)/m', $field, $match) ) {
                $match[1] = preg_replace('/(?<=^|[\x09\x20\x2D])./e', 'strtoupper("\0")', strtolower(trim($match[1])));
                if( isset($retVal[$match[1]]) ) {
                    $retVal[$match[1]] = array($retVal[$match[1]], $match[2]);
                } else {
                    $retVal[$match[1]] = trim($match[2]);
                }
            }
        }
        if ($retVal['X-Bc-Apilimit-Remaining'] <= 100) {
        	sleep(300);
        }
    }

    public function error($body, $url, $json, $type) {
    	global $error;
    	if (isset($json)) {
	    	$results = json_decode($body, true);
			$results = $results[0];
			$results['type'] = $type;
			$results['url'] = $url;
			$results['payload'] = $json;
			$error = $results;
		} else {
			$results = json_decode($body, true);
			$results = $results[0];
			$results['type'] = $type;
			$results['url'] = $url;
			$error = $results;
		}
    }

	/**
	 * Performs a get request to the instantiated class
	 * 
	 * Accepts the resource to perform the request on
	 * 
	 * @param $resource string $resource a string to perform get on
	 * @return results or var_dump error
	 */
	public function get($resource) {

		$url = $this->_path . '/api/v2' . $resource;

		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_HTTPHEADER, $this->_headers);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl, CURLOPT_VERBOSE, 1);
		curl_setopt($curl, CURLOPT_HEADER, 1);
		curl_setopt($curl, CURLOPT_HTTPGET, 1);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);            
		$response = curl_exec($curl);
		$http_status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		$header_size = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
		$headers = substr($response, 0, $header_size);
		$body = substr($response, $header_size);
		self::http_parse_headers($headers);
		curl_close ($curl);
		if ($http_status == 200) {
			$results = json_decode($body, true);
			return $results;
		} else {
			$this->error($body, $url, null, 'GET');
		} 

	
	}

	/**
	 * Performs a put request to the instantiated class
	 * 
	 * Accepts the resource to perform the request on, and fields to be sent
	 * 
	 * @param string $resource a string to perform get on
	 * @param array $fields an array to be sent in the request
	 * @return results or var_dump error
	 */
	public function put($resource, $fields) {

		$url = $this->_path . '/api/v2' . $resource;
		$json = json_encode($fields);
		
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_HTTPHEADER, $this->_headers);
	    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	    curl_setopt($curl, CURLOPT_VERBOSE, 1);
	    curl_setopt($curl, CURLOPT_HEADER, 1);
	    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "PUT");
	    curl_setopt($curl, CURLOPT_POSTFIELDS, $json); 
	    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
	    $response = curl_exec($curl);
	    $http_status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
	    $header_size = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
	    $headers = substr($response, 0, $header_size);
	    $body = substr($response, $header_size);
	    self::http_parse_headers($headers);
	    curl_close($curl);
		if ($http_status == 200) {
			$results = json_decode($body, true);
			return $results;
		} else {
			$this->error($body, $url, $json, 'PUT');
		}

	}

	/**
	 * Performs a post request to the instantiated class
	 * 
	 * Accepts the resource to perform the request on, and fields to be sent
	 * 
	 * @param string $resource a string to perform get on
	 * @param array $fields an array to be sent in the request
	 * @return results or var_dump error
	 */
	public function post($resource, $fields) {
		global $error;
		$url = $this->_path . '/api/v2' . $resource;
		$json = json_encode($fields);

		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_HTTPHEADER, $this->_headers);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_VERBOSE, 1);
		curl_setopt($curl, CURLOPT_HEADER, 1);
		curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
		curl_setopt($curl, CURLOPT_POSTFIELDS, $json);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		$response = curl_exec ($curl);
		$http_status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		$header_size = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
		$headers = substr($response, 0, $header_size);
		$body = substr($response, $header_size);
		self::http_parse_headers($headers);
		curl_close ($curl);
		if ($http_status == 201) {
			$results = json_decode($body, true);
			return $results;
		} else {
			$this->error($body, $url, $json, 'POST');
		}
	}

	/**
	 * Performs a delete request to the instantiated class
	 * 
	 * Accepts the resource to perform the request on
	 * 
	 * @param string $resource a string to perform get on
	 * @return proper response or var_dump error
	 */
	public function delete($resource) {
			
		$url = $this->_path . '/api/v2' . $resource;
		
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_HTTPHEADER, $this->_headers);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_VERBOSE, 1);
		curl_setopt($curl, CURLOPT_HEADER, 1);
		curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "DELETE");
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		$response = curl_exec($curl);
		$http_status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		$header_size = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
		$headers = substr($response, 0, $header_size);
		$body = substr($response, $header_size);
		self::http_parse_headers($headers);	        
		curl_close ($curl);
		if ($http_status == 204) {
	     	return $http_status . ' DELETED';
		 } else {
		 	$this->error($body, $url, null, 'DELETE');
		 }
	}
}

?>