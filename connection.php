<?php
/**
 *Class to instantiate different api connections
 * 
 * @author Adam Bilsing <adambilsing@gmail.com>
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
	static private $debug = true;
	private $cache = array();
	public $errors = array();

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

	private static function http_parse_headers($header) {
		$retVal = array();
		$fields = explode("\r\n", preg_replace('/\x0D\x0A[\x09\x20]+/', ' ', $header));

		foreach( $fields as $field ) {
			if( preg_match('/([^:]+): (.+)/m', $field, $match) ) {
				$match[1] = preg_replace_callback('/(?<=^|[\x09\x20\x2D])./', function() { return strtoupper("\0"); }, strtolower(trim($match[1])));
				if( isset($retVal[$match[1]]) ) {
					$retVal[$match[1]] = array($retVal[$match[1]], $match[2]);
				} else {
					$retVal[$match[1]] = trim($match[2]);
				}
			}
		}
		if (isset($retVal['X-Bc-Apilimit-Remaining']) && $retVal['X-Bc-Apilimit-Remaining'] <= 100) {
			sleep(300);
		}
	}

	private function error($body, $url, $json, $type) {
		if (isset($json)) {
			$results = json_decode($body, true);
			$results = $results[0];
			$results['type'] = $type;
			$results['url'] = $url;
			$results['payload'] = $json;
			$error = $results;
		} 
		else {
			$results = json_decode($body, true);
			$results = $results[0];
			$results['type'] = $type;
			$results['url'] = $url;
			$error = $results;
		}
		if ($this->debug) {
			error_log(json_encode($error, JSON_PRETTY_PRINT));
		}
		$this->errors[] = $error;
	}

	/**
	 * Performs a get request to the instantiated class
	 * 
	 * Accepts the resource to perform the request on
	 * 
	 * @param $resource string $resource a string to perform get on
	 * @return results or false
	 */
	public function get($resource) {
		$url = $this->_path . '/api/v2' . $resource;
		return $this->sendCurl($url, "GET");
	}

	/**
	 * Performs a put request to the instantiated class
	 * 
	 * Accepts the resource to perform the request on, and fields to be sent
	 * 
	 * @param string $resource a string to perform put on
	 * @param array $fields an array to be sent in the request
	 * @return results or false
	 */
	public function put($resource, $fields) {
		$url = $this->_path . '/api/v2' . $resource;
		$payload = json_encode($fields);
		return $this->sendCurl($url, "PUT", $payload);
	}

	/**
	 * Performs a post request to the instantiated class
	 * 
	 * Accepts the resource to perform the request on, and fields to be sent
	 * 
	 * @param string $resource a string to perform post on
	 * @param array $fields an array to be sent in the request
	 * @return results or false
	 */
	public function post($resource, $fields) {
		$url = $this->_path . '/api/v2' . $resource;
		$payload = json_encode($fields);
		return $this->sendCurl($url, "POST", $payload);
	}

	/**
	 * Performs a delete request to the instantiated class
	 * 
	 * Accepts the resource to perform the request on
	 * 
	 * @param string $resource a string to perform delete on
	 * @return response or false
	 */
	public function delete($resource) {
		$url = $this->_path . '/api/v2' . $resource;
		return $this->sendCurl($url, "DELETE");
	}

	/**
	 * Performs a cURL request to remote host
	 * 
	 * Accepts the url to send request to, http method of request and optional payload
	 * 
	 * @param string $url a string to perform $method on
	 * @param string $method the http method
	 * @param string $payload (optional) json encoded payload
	 * @return response or false
	 */
	private function sendCurl($url, $method, $payload = null) {
		if (
			(
				strtolower($method) === "get" 
				&& !isset($this->cache[$url])
			)
			|| strtolower($method) !== "get"
		) {
			$curl = curl_init();
			curl_setopt($curl, CURLOPT_URL, $url);
			curl_setopt($curl, CURLOPT_HTTPHEADER, $this->_headers);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($curl, CURLOPT_VERBOSE, $this->debug);
			curl_setopt($curl, CURLOPT_HEADER, 1);
			curl_setopt($curl, CURLOPT_CUSTOMREQUEST, strtoupper($method));
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

			if (strtolower($method) !== "get") {
				if (isset($this->cache[$url])) {
					unset($this->cache[$url]);
				}
				if ($payload) {
					curl_setopt($curl, CURLOPT_POSTFIELDS, $payload);
				}
			}

			$response 	 = curl_exec($curl);
			$http_status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
			$header_size = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
			$headers 	 = substr($response, 0, $header_size);
			$body 		 = substr($response, $header_size);

			self::http_parse_headers($headers);
			curl_close ($curl);

			if ($http_status == 200 && strtolower($method) === "get") {
				$results = json_decode($body, true);
				$this->cache[$url] = $results;
			}
			elseif ($http_status == 200 && strtolower($method) === "put") {
				$results = json_decode($body, true);
			}
			elseif ($http_status == 201 && strtolower($method) === "post") {
				$results = json_decode($body, true);
			}
			elseif ($http_status == 204 && strtolower($method) === "delete") {
				$results = $http_status . ' DELETED';
			}
			else {
				$this->error($body, $url, $payload, strtoupper($method));
				$results = false;
			}
		}
		elseif (isset($this->cache[$url])) {
			$results = $this->cache[$url];
		}
		else {
			$results = false;
		}

		return $results;
	}
}

?>