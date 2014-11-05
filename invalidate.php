<?php
require 'config.php';

$input = file_get_contents('php://input');
$json = json_decode($input, true);

if(isset($json['accessToken']) && isset($json['clientToken'])) {
	$mysql = new mysqli($CONFIG['host'], $CONFIG['user'], $CONFIG['pass'], $CONFIG['database']);

	$result = $mysql->query('SELECT * FROM ' . $CONFIG['table'] . ' WHERE access_token="' . $mysql->real_escape_string($json['accessToken']) . '" AND client_token="' . $mysql->real_escape_string($json['clientToken']) . '"');
	if($result !== FALSE) {
		$array = $result->fetch_array(MYSQLI_ASSOC);
		$result->close();

		$mysql->query('UPDATE ' . $CONFIG['table'] . ' SET access_token="", client_token="" WHERE id=' . $array['id']);

		echo json_encode();
	}
	else if($CONFIG['onlineauth']) {
		$mojang = file_get_contents('https://authserver.mojang.com/invalidate', false, stream_context_create(array(
			'http' => array(
				'ignore_errors' => TRUE,
				'method' => 'POST',
				'header' => 'Content-Type: application/json'    . "\r\n" .
				            'Content-Length: ' . strlen($input) . "\r\n",
				'content' => $input
			)
		)));

		http_response_code(intval($http_response_header.split(' ')[1]));
		echo $mojang;
	}
	else {
		echo json_encode(array(
			'error' => 'ForbiddenOperationException',
			'errorMessage' => 'Invalid token.'
		));
	}

	$mysql->close();
}
else {
	echo $CONFIG['message'];
}
?>
