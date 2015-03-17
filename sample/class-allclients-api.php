<?php

/**
 * AllClients API wrapper basic example.
 */
class AllClientsAPI
{
	/**
	 * AllClients API endpoint
	 *
	 * @var string
	 */
	private $endpoint;

	/**
	 * AllClients Account ID
	 *
	 * @var int $accountID
	 */
	private $accountID;

	/**
	 * AllClients API Key
	 *
	 * @var string $apiKey
	 */
	private $apiKey;

	/**
	 * Last cURL or simplexml_load_string error
	 *
	 * @var string|null $lastError
	 */
	private $lastError;

	/**
	 * @param string $endpoint    API endpoint
	 * @param int    $account_id  Account ID
	 * @param string $api_key     API Key
	 */
	public function __construct($endpoint, $account_id, $api_key)
	{
		$this->accountID = $account_id;
		$this->apiKey    = $api_key;
		$this->endpoint  = $endpoint;
	}

	/**
	 * Post data to API by method name and return response as a SimpleXMLElement
	 * object. Returns false on error, and the error message can be obtained with
	 * getLastError().
	 *
	 * @param string $method  API method name
	 * @param array  $data    Data array to post to API method
	 *
	 * @return SimpleXMLElement|false
	 */
	public function method($method, array $data = array())
	{
		// Form full URL for API method.
		$url = $this->endpoint . $method . ".aspx";

		// Add account ID and API key to request data.
		$data = array_merge(array(
			'accountid' => $this->accountID,
			'apikey'    => $this->apiKey,
		), $data);

		// Post data to API and get XML response, return false on failure.
		if (false === ($xml_string = $this->postUrl($url, $data))) {
			return false;
		}

		// Parse XML to SimpleXML object, set last error and return false on failure.
		if (false === ($xml = simplexml_load_string($xml_string))) {
			$this->lastError = sprintf("Cannot parse API response from %s as XML.", $url);
			return false;
		}

		return $xml;
	}

	/**
	 * Get last cURL or simplexml_load_string error message. Returns false if last API method was successful.
	 *
	 * @return string|false
	 */
	public function getLastError()
	{
		return !empty($this->lastError) ? $this->lastError : false;
	}

	/**
	 * Post data to API using cURL and return response body as string.
	 *
	 * @param string $url  Fully qualified API method URL
	 * @param array  $data Data array to post
	 *
	 * @return string|false
	 */
	protected function postUrl($url, array $data = array())
	{
		// URL encode post data.
		$data_query = http_build_query($data);

		// Initialize cURL and set options.
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data_query);
		curl_setopt($ch, CURLOPT_FAILONERROR, true);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

		// Execute cURL, on false get the cURL error, otherwise clear lastError.
		if (false === ($output = curl_exec($ch))) {
			$this->lastError = sprintf("cURL returned an error: %s", curl_error($ch));
		} else {
			$this->lastError = null;
		}

		// Close cURL session and return response - false on error.
		curl_close($ch);
		return $output;
	}

}