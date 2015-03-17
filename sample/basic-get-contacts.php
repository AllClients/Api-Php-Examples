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
 * Specify URL and form fields for GetContacts API function.
 */
$url = $api_endpoint . 'GetContacts.aspx';
$data = array(
	'accountid' => $account_id,
	'apikey'    => $api_key,
);

/**
 * Fetch XML contact list from AllClients as a string:
 *
 *   <?xml version="1.0"?>
 *   <results>
 *     <contacts>
 *       <contact>
 *         <id>64704</id>
 *         <firstname>Rasmus</firstname>
 *         <lastname>Lerdorf</lastname>
 *         <adddate>12/31/2014 12:46:50 PM</adddate>
 *         <editdate>1/3/2015 7:03:10 AM</editdate>
 *       </contact>
 *     </contacts>
 *   </results>
 *
 * @var string $contacts_xml_string
 */
$contacts_xml_string = post_api_url($url, $data);

/**
 * SimpleXML will create an object representation of the XML API response. If
 * the XML is invalid, simplexml_load_string will return false.
 *
 * @var SimpleXMLElement $results_xml
 */
$results_xml = simplexml_load_string($contacts_xml_string);
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
 * If no error was returned, the GetContacts results object will contain a
 * 'contacts' child SimpleXMLElement.
 *
 * @var SimpleXMLElement $contacts_xml
 */
$contacts_xml = $results_xml->contacts;

/**
 * The contacts XML object represents the <contacts> node in the XML. The
 * child elements of this object are the contacts themselves.
 */
$count = $contacts_xml->children()->count();

if ($count === 0) {
	printf("No contacts returned!{$nl}");
	exit;
} else {

	printf("Contacts returned: %d{$nl}", $count);

	/**
	 * Iterate contact SimpleXMLElements and output.
	 */
	foreach($contacts_xml->children() as $contact) {

		/**
		 * Each of the properties of the contact are SimpleXMLElements
		 * themselves. You can cast each of the values you want to extract.
		 */
		$id         = (int) $contact->id;
		$first_name = (string) $contact->firstname;
		$last_name  = (string) $contact->lastname;

		/**
		 * If an optional field such as a contact's company is not set, it will
		 * return an empty SimpleXMLElement when accessed. If cast to a string,
		 * it will return an empty string.
		 */
		$company = (string) $contact->company;

		/**
		 * ...or you can check that a field is set first
		 */
		if (isset($contact->email)) {
			$email = (string) $contact->email;
		} else {
			$email = null;
		}

		/**
		 * Datetime fields come from the API in a custom format, for example:
		 *
		 *   <adddate>2/15/2014 1:15:05 PM</adddate>
		 *
		 * To parse to a DateTime object, you can use the date_create_from_format
		 * function with the format 'n/j/Y g:i:s a'. The timezone of the created
		 * DateTime object will be the default PHP timezone if not specified, so
		 * it is important to specify which timezone the API uses.
		 *
		 * @var DateTime $add_date
		 * @var DateTime $edit_date
		 */
		$add_date  = date_create_from_format('n/j/Y g:i:s a', $contact->adddate, $api_timezone);
		$edit_date = date_create_from_format('n/j/Y g:i:s a', $contact->editdate, $api_timezone);

		/**
		 * Output the contact: Last, First (id), updated date
		 */
		printf("%s, %s (%d), updated %s{$nl}", $last_name, $first_name, $id, $edit_date->format('m/d/Y'));

		/**
		 * Note: If you would prefer to work with the contact data as an array
		 * instead of as SimpleXML elements, cast the contact object to one:
		 */
		$contact_array = (array) $contact;

		/**
		 * All contact fields will be strings, so if the types are important
		 * you must still cast them, as with the ID:
		 */
		$id         = (int) $contact_array['id'];
		$first_name = $contact_array['firstname'];
		$last_name  = $contact_array['lastname'];
		$add_date   = date_create_from_format('n/j/Y g:i:s a', $contact_array['adddate'], $api_timezone);

	}

}
