<?php
/**
 * AllClients Account ID and API Key.
 */
$account_id   = '[ SET ACCOUNT ID ]';
$api_key      = '[ SET API KEY ]';

/**
 * The API endpoint and time zone.
 */
$api_endpoint = 'http://www.allclients.com/api/2/';
$api_timezone = new DateTimeZone('America/Los_Angeles');

/**
 * The contact information to insert.
 *
 * Information will be added to your AllClients contacts!
 */
$contact = array(
	'firstname' => '',
	'lastname'  => '',
);

/**
 * Newline character, to support browser or CLI output.
 */
$nl = php_sapi_name() === 'cli' ? "\n" : "<br>";

/**
 * Post data to URL with cURL and return result XML string.
 *
 * Outputs cURL error and exits on failure.
 *
 * @param string $url
 * @param array  $data
 *
 * @return string
 */
function post_api_url($url, array $data = array()) {
	global $nl;

	// Initialize a new cURL resource and set the URL.
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);

	// Form data must be transformed from an array into a query string.
	$data_query = http_build_query($data);

	// Set request type to POST and set the data to post.
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $data_query);

	// Set cURL to error on failed response codes from AllClients server,
	// such as 404 and 500.
	curl_setopt($ch, CURLOPT_FAILONERROR, true);

	// Set cURL option to return the response from the AllClients server,
	// otherwise $output below will just return true/false.
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

	// Post data to API.
	$output = curl_exec($ch);

	// Exit on cURL error.
	if ($output === false) {
		// It is important to close the cURL session after curl_error()
		printf("cURL returned an error: %s{$nl}", curl_error($ch));
		curl_close($ch);
		exit;
	}

	// Close the cURL session
	curl_close($ch);

	// Return response
	return $output;
}

/**
 * Specify URL and form fields for AddContact API function.
 */
$url = $api_endpoint . 'AddContact.aspx';
$data = array(
	'accountid' => $account_id,
	'apikey'    => $api_key,
	'firstname' => $contact['firstname'],
	'lastname'  => $contact['lastname'],
);

/**
 * Exit if contact information is not specified.
 */
if (empty($data['firstname']) || empty($data['lastname'])) {
	print("First and last name must be specified at top of file to run.{$nl}");
	exit;
}

/**
 * Insert the contact and get the response as XML string:
 *
 *   <?xml version="1.0"?>
 *   <results>
 *     <message>Success</message>
 *     <contactid>15631</contactid>
 *   </results>
 *
 * @var string $contacts_xml_string
 */
$result_xml_string = post_api_url($url, $data);

/**
 * SimpleXML will create an object representation of the XML API response. If
 * the XML is invalid, simplexml_load_string will return false.
 *
 * @var SimpleXMLElement $results_xml
 */
$results_xml = simplexml_load_string($result_xml_string);
if ($results_xml === false) {
	print("Error parsing XML{$nl}");
	exit;
}

/**
 * If an API error has occurred, the results object will contain a child 'error'
 * SimpleXMLElement parsed from the error response:
 *
 *   <?xml version="1.0"?>
 *   <results>
 *     <error>Authentication failed</error>
 *   </results>
 */
if (isset($results_xml->error)) {
	printf("AllClients API returned an error: %s{$nl}", $results_xml->error);
	exit;
}

/**
 * If no error was returned, the AddContact results object will contain a
 * 'contactid' child SimpleXMLElement, which can be cast to an integer.
 */
$contactid = (int) $results_xml->contactid;

printf("Added contact with contactid %d{$nl}", $contactid);
