<?php
require 'config.php';

if(isset($_REQUEST['user']) && isset($_REQUEST['password'])) {
	$mysql = new mysqli($CONFIG['host'], $CONFIG['user'], $CONFIG['pass'], $CONFIG['database']);
	$result = $mysql->query('SELECT * FROM ' . $CONFIG['table'] . ' WHERE username="' . $mysql->real_escape_string($_REQUEST['user']) . '" AND password="' . $mysql->real_escape_string(hash('sha256', $_REQUEST['password'])) . '"');
	if($result->num_rows == 1) {
		$array = $result->fetch_array(MYSQLI_ASSOC);
		do {
			$result->close();
			$id = dechex(rand(268435456, 4294967295));
			$result = $mysql->query('SELECT * FROM ' . $CONFIG['table'] . ' WHERE session="' . $id . '"');
		} while($result->num_rows != 0);
		$mysql->query('UPDATE ' . $CONFIG['table'] . ' SET session="' . $id . '" WHERE id=' . $array['id']);

		$rss = @file_get_contents('http://mcupdate.tumblr.com/rss');
		if(!empty($rss)) {
			preg_match('/<pubDate>(.*?)<\\/pubDate>/', $rss, $match);
			$version = strtotime($match[1]) * 1000;
		}
		else {
			$version = strtotime($CONFIG['version']) * 1000;
		}

		echo $version . ':' . 'deprecated' . ':' . $array['username'] . ':' . $id . ':';
	}
	else if($CONFIG['onlineauth'] && isset($_REQUEST['version'])) {
		echo file_get_contents('http://login.minecraft.net/?user=' . $_REQUEST['user'] . '&password=' . $_REQUEST['password'] . '&version=' . $_REQUEST['version']);
	}
	else {
		echo 'Bad login';
	}

	$result->close();
	$mysql->close();
}
else {
	echo $CONFIG['message'];
}
?>
