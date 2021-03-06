<?php
require 'config.php';
require 'common.php';

if(isset($_REQUEST['user']) && isset($_REQUEST['password'])) {
	header('Content-Type: text/plain');

	$mysql = new mysqli($CONFIG['host'], $CONFIG['user'], $CONFIG['pass'], $CONFIG['database']);

	$result = $mysql->query('SELECT * FROM ' . $CONFIG['table'] . ' WHERE username="' . $mysql->real_escape_string($_REQUEST['user']) . '" AND password="' . $mysql->real_escape_string(hash('sha256', $_REQUEST['password'])) . '"');
	if($result->num_rows === 1) {
		$array = $result->fetch_array(MYSQLI_ASSOC);

		$session = gen_uniq($mysql, $CONFIG['table'], 'session');

		$mysql->query('UPDATE ' . $CONFIG['table'] . ' SET session="' . $session . '" WHERE id="' . $array['id'] . '"');

		$version = 0;
		if(empty($CONFIG['version'])) {
			$rss = @file_get_contents('http://mcupdate.tumblr.com/rss');
			preg_match('/<item>.*?<title>Minecraft [0-9]+\\.[0-9]+.*?<\\/title>.*?<pubDate>(.*?)<\\/pubDate>.*?<\\/item>/s', $rss, $matches);
			$version = @strtotime($matches[1]) * 1000;
		}
		else {
			$version = strtotime($CONFIG['version']) * 1000;
		}

		echo $version . ':' . 'deprecated' . ':' . $array['username'] . ':' . $session . ':';
	}
	else if($CONFIG['onlineauth'] && isset($_REQUEST['version'])) {
		echo file_get_contents('http://login.minecraft.net/?user=' . urlencode($_REQUEST['user']) . '&password=' . urlencode($_REQUEST['password']) . '&version=' . urlencode($_REQUEST['version']));
	}
	else {
		echo 'Bad login';
	}
	$result->free();

	$mysql->close();
}
else {
	echo $CONFIG['message'];
}
?>
