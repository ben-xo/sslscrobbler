<?php

require '../../lastfmapi/lastfmapi.php';

if ( !empty($_GET['token']) ) {
	$vars = array(
		'apiKey' => 'fa3af76b9396d0091c9c41ebe3c63716',
		'secret' => 'f7df7cd6acf957521012f7a5f257d116',
		'token' => $_GET['token']
	);
	
	$auth = new lastfmApiAuth('getsession', $vars);

	$file = fopen('../auth.txt', 'w');
	$contents = $auth->apiKey."\n".$auth->secret."\n".$auth->username."\n".$auth->sessionKey."\n".$auth->subscriber;
	fwrite($file, $contents, strlen($contents));
	fclose($file);
	
	echo 'New key has been generated and saved to auth.txt<br /><br />';
	echo '<a href="'.$_SERVER['PHP_SELF'].'">Reload</a>';
}
else {
	$file = fopen('../auth.txt', 'r');
	$vars = array(
		'apiKey' => trim(fgets($file)),
		'secret' => trim(fgets($file)),
		'username' => trim(fgets($file)),
		'sessionKey' => trim(fgets($file)),
		'subscriber' => trim(fgets($file))
	);
	$auth = new lastfmApiAuth('setsession', $vars);
	
	echo '<b>API Key:</b> '.$auth->apiKey.'<br />';
	echo '<b>Secret:</b> '.$auth->secret.'<br />';
	echo '<b>Username:</b> '.$auth->username.'<br />';
	echo '<b>Session Key:</b> '.$auth->sessionKey.'<br />';
	echo '<b>Subscriber:</b> '.$auth->subscriber.'<br /><br />';

	echo '<a href="http://www.last.fm/api/auth/?api_key='.$auth->apiKey.'">Get New Key</a>';
}

?>