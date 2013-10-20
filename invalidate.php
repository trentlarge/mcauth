<?php
require 'config.php';

$input = file_get_contents('php://input');
$json = json_decode($input, true);

if(isset($json['accessToken']) && isset($json['clientToken'])) {
	$mysql = new mysqli($CONFIG['host'], $CONFIG['user'], $CONFIG['pass'], $CONFIG['database']);
	$result = $mysql->query('SELECT * FROM ' . $CONFIG['table'] . ' WHERE access_token="' . $mysql->real_escape_string($json['accessToken']) . '" AND client_token="' . $mysql->real_escape_string($json['clientToken']) . '"');
	if($result->num_rows === 1) {
		$array = $result->fetch_array(MYSQLI_ASSOC);
		$mysql->query('UPDATE ' . $CONFIG['table'] . ' SET access_token="" AND client_token="" WHERE id=' . $array['id']);

		echo json_encode();
	}
	else if($CONFIG['onlineauth']) {
		echo file_get_contents('https://authserver.mojang.com/invalidate', false, stream_context_create(array(
			'http' => array(
				'method' => 'POST',
				'header' => 'Content-Type: application/json',
				'content' => $input
			)
		)));
	}
	else {
		echo json_encode(array(
			'error' => 'ForbiddenOperationException',
			'errorMessage' => 'Invalid token.'
		));
	}

	$result->close();
	$mysql->close();
}
else {
	echo $CONFIG['message'];
}
?>
