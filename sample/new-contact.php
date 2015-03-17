<?php

/**
 * AllClients Account ID and API Key.
 */
$account_id   = '[ SET ACCOUNT ID ]';
$api_key      = '[ SET API KEY ]';

/**
 * The API endpoint and timezone.
 */
$api_endpoint = 'http://www.allclients.com/api/2/';
$api_timezone = new DateTimeZone('America/Los_Angeles');

/**
 * Require and instantiate the API wrapper.
 */
require 'class-allclients-api.php';
$api = new AllClientsAPI($api_endpoint, $account_id, $api_key);

/**
 * Get contact flags.
 */
$flag_options = array();
$flags_xml = $api->method('GetFlags');
if ($flags_xml === false) {
	$flag_options[] = $api->getLastError();
} elseif (isset($flags_xml->error)) {
	$flag_options[] = (string) $flags_xml->error;
} else {
	foreach ($flags_xml->flags->flag as $flag) {
		$flag_options[(int) $flag->flagid] = (string) $flag->name;
	}
}

/**
 * Get todo plans.
 */
$todo_plan_options = array();
$todo_plans_xml = $api->method('GetToDoPlans');
if ($todo_plans_xml === false) {
	$todo_plan_options[] = $api->getLastError();
} elseif (isset($todo_plans_xml->error)) {
	$todo_plan_options[] = (string) $todo_plans_xml->error;
} else {
	foreach ($todo_plans_xml->todoplans->todoplan as $plan) {
		$todo_plan_options[(int) $plan->id] = (string) $plan->name;
	}
}

/**
 * Messages and errors for output.
 */
$messages = array();
$errors   = array();

/**
 * Handle form post.
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$first_name    = $_POST['first-name'];
	$last_name     = $_POST['last-name'];
	$email         = $_POST['email'];
	$contact_flags = isset($_POST['contact-flags']) ? $_POST['contact-flags'] : array();
	$todo_plan     = $_POST['todo-plan'];

	try {

		if (empty($first_name) || empty($last_name)) {
			throw new Exception('First and last name are required.');
		}

		/**
		 * Contact data.
		 */
		$contact_data = array(
			'firstname' => $first_name,
			'lastname'  => $last_name,
			'email'     => $email,
		);

		/**
		 * Create new contact with AddContact method.
		 *
		 * @var SimpleXMLElement $contact_xml
		 */
		if (false === ($contact_xml = $api->method('AddContact', $contact_data))) {
			throw new Exception($api->getLastError());
		}

		/**
		 * Handle API exceptions or get contact ID.
		 */
		if (isset($contact_xml->error)) {
			throw new Exception('API error: ' . $contact_xml->error);
		}

		/**
		 * Get the new contact ID
		 */
		$contact_id = (int) $contact_xml->contactid;
		$messages[] = sprintf('Added contact ID %d, %s %s', $contact_id, $first_name, $last_name);

		/**
		 * Iterate and add contact flags.
		 */
		foreach ($contact_flags as $flag_id) {
			/**
			 * The API requires the tag name, retrieve from options array.
			 */
			$flag_name = $flag_options[$flag_id];

			/**
			 * Create new contact flag with ContactFlags method.
			 *
			 * @var SimpleXMLElement $add_flag_xml
			 */
			$add_flag_xml = $api->method('ContactFlags', array(
				'mode'           => 1, // Add flag
				'identifymethod' => 1, // Identify by contact ID
				'identifyvalue'  => $contact_id,
				'flag'           => $flag_name,
			));

			// Handle output of ContactFlags response.
			if ($add_flag_xml === false) {
				$errors[] = sprintf("Error adding flag '%s': %s", $flag_name, $api->getLastError());
			} elseif (isset($add_flag_xml->error)) {
				$errors[] = sprintf("API exception adding flag '%s': %s", $flag_name, $add_flag_xml->error);
			} else {
				$messages[] = sprintf("Added contact flag `%s`", $flag_name);
			}
		}

		/**
		 * Assign todo plan.
		 */
		if (!empty($todo_plan)) {
			/**
			 * Assign with AssignToDoPlan method.
			 *
			 * @var SimpleXMLElement $assign_todo_plan_xml
			 */
			$assign_todo_plan_xml = $api->method('AssignToDoPlan', array(
				'identifymethod' => 1, // Identify by contact ID
				'identifyvalue'  => $contact_id,
				'todoplanid'     => $todo_plan,
			));

			// Handle output of AssignToDoPlan response.
			$todo_plan_name = $todo_plan_options[$todo_plan];
			if ($assign_todo_plan_xml === false) {
				$errors[] = sprintf("Error adding todo plan '%s', ID %d: %s", $todo_plan_name, $todo_plan, $api->getLastError());
			} elseif (isset($assign_todo_plan_xml->error)) {
				$errors[] = sprintf("API exception adding todo plan '%s', ID %d: %s", $todo_plan_name, $todo_plan, $assign_todo_plan_xml->error);
			} else {
				$messages[] = sprintf("Assigned todo plan `%s`, ID %d", $todo_plan_name, $todo_plan);
			}
		}
	} catch (Exception $e) {
		$errors[] = $e->getMessage();
	}
}

?>
<!DOCTYPE html>
<html>
<head lang="en">
	<meta charset="UTF-8">
	<title>AllClients New Contact Sample</title>
	<link rel="stylesheet" href="//maxcdn.bootstrapcdn.com/bootstrap/3.3.2/css/bootstrap.min.css">
</head>
<body>

<div class="container">
	<?php if ($messages): ?>
		<div class="alert alert-success" role="alert">
			<?php echo implode("<br>", $messages); ?>
		</div>
	<?php endif; ?>
	<?php if ($errors): ?>
		<div class="alert alert-danger" role="alert">
			<?php echo implode("<br>", $errors); ?>
		</div>
	<?php endif; ?>
	<h1>New AllClients Contact</h1>
	<form action="new-contact.php" method="post">
		<div class="form-group">
			<label for="first-name">First Name:</label>
			<input type="text" name="first-name" id="first-name" class="form-control">
		</div>
		<div class="form-group">
			<label for="last-name">Last Name:</label>
			<input type="text" name="last-name" id="last-name" class="form-control">
		</div>
		<div class="form-group">
			<label for="email">Email Address:</label>
			<input type="email" name="email" id="email" class="form-control">
		</div>
		<div class="form-group">
			<label for="contact-flags">Contact Flag(s):</label>
			<select name="contact-flags[]" id="contact-flags" multiple class="form-control" size="6">
				<?php foreach ($flag_options as $value => $name): ?>
					<option value="<?php echo $value; ?>"><?php echo htmlentities($name); ?></option>
				<?php endforeach; ?>
			</select>
		</div>
		<div class="form-group">
			<label for="todo-plan">Assign Todo Plan:</label>
			<select name="todo-plan" id="todo-plan" class="form-control">
				<option value="">None</option>
				<?php foreach ($todo_plan_options as $value => $name): ?>
					<option value="<?php echo $value; ?>"><?php echo htmlentities($name); ?></option>
				<?php endforeach; ?>
			</select>
		</div>
		<button type="submit" class="btn btn-default">Submit</button>
	</form>
</div>

</body>
</html>
