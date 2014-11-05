<?php
require 'config.php';

$input = file_get_contents('php://input');
$json = json_decode($input, true);

if($json === NULL) {
	http_response_code(400);
	header('Content-Type: application/json');
	echo json_encode(array(
		'error' => 'JsonParseException',
		'errorMessage' => 'Error parsing JSON.'
	));
}
else if(isset($json['username']) && isset($json['password'])) {
	$mysql = new mysqli($CONFIG['host'], $CONFIG['user'], $CONFIG['pass'], $CONFIG['database']);

	$result = $mysql->query('SELECT * FROM ' . $CONFIG['table'] . ' WHERE username="' . $mysql->real_escape_string($json['username']) . '" AND password="' . $mysql->real_escape_string(hash('sha256', $json['password'])) . '"');
	if($result !== FALSE) {
		$array = $result->fetch_array(MYSQLI_ASSOC);
		$result->close();

		while(TRUE) {
			$access_token = dechex(rand(268435456, 4294967295));
			$result = $mysql->query('SELECT * FROM ' . $CONFIG['table'] . ' WHERE access_token="' . $access_token . '"');

			if($result === FALSE)
				break;

			$result->close();
		}

		if(isset($json['clientToken'])) {
			$client_token = $json['clientToken'];
		}
		else {
			while(TRUE) {
				$client_token = dechex(rand(268435456, 4294967295));
				$result = $mysql->query('SELECT * FROM ' . $CONFIG['table'] . ' WHERE client_token="' . $client_token . '"');

				if($result === FALSE)
					break;

				$result->close();
			}
		}

		$mysql->query('UPDATE ' . $CONFIG['table'] . ' SET access_token="' . $access_token . '", client_token="' . $mysql->real_escape_string($client_token) . '" WHERE id=' . $array['id']);

		$response = array(
			'accessToken' => $access_token,
			'clientToken' => $client_token
		);

		if(isset($json['requestUser']) && $json['requestUser'] === TRUE) {
			$response['user'] = array(
				'id' => $array['id']
			);
		}

		header('Content-Type: application/json');
		echo json_encode($response);
	}
	else if($CONFIG['onlineauth']) {
		$mojang = file_get_contents('https://authserver.mojang.com/authenticate', false, stream_context_create(array(
			'http' => array(
				'ignore_errors' => TRUE,
				'method' => 'POST',
				'header' => 'Content-Type: application/json'    . "\r\n" .
				            'Content-Length: ' . strlen($input) . "\r\n",
				'content' => $input
			)
		)));

		http_response_code(intval(explode(' ', $http_response_header)[1]));
		header('Content-Type: application/json');
		echo $mojang;
	}
	else {
		http_response_code(403);
		header('Content-Type: application/json');
		echo json_encode(array(
			'error' => 'ForbiddenOperationException',
			'errorMessage' => 'Invalid credentials. Invalid username or password.'
		));
	}

	$mysql->close();
}
else {
	http_response_code(403);
	header('Content-Type: application/json');
	echo json_encode(array(
		'error' => 'ForbiddenOperationException',
		'errorMessage' => 'Invalid credentials. Invalid username or password.'
	));
}
?>
