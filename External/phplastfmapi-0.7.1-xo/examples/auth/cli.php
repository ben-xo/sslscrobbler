<?php

# command line example, using the Desktop Auth Flow

error_reporting(E_ALL | E_STRICT);


require '../../lastfmapi/lastfmapi.php';

$vars = array(
	'apiKey' => 'fa3af76b9396d0091c9c41ebe3c63716',
	'secret' => 'f7df7cd6acf957521012f7a5f257d116'
);

if ( !file_exists('../token.txt') ) {
	
	$token = new lastfmApiAuth('gettoken', $vars);
	$vars['token'] = $token->token;

	echo "Now visit http://www.last.fm/api/auth/?api_key={$vars['apiKey']}&token={$token->token}\n\n";

	$file = fopen('../token.txt', 'w');
	$contents = $token->token;
	fwrite($file, $contents, strlen($contents));
	fclose($file);
	
}
elseif( !file_exists('../auth.txt') ) {
	
    $vars['token'] = file_get_contents('../token.txt');
    
	$auth = new lastfmApiAuth('getsession', $vars);

	$file = fopen('../auth.txt', 'w');
	$contents = $auth->apiKey."\n".$auth->secret."\n".$auth->username."\n".$auth->sessionKey."\n".$auth->subscriber;
	fwrite($file, $contents, strlen($contents));
	fclose($file);
	
	echo 'New key has been generated and saved to auth.txt' . "\n" . "\n";
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
	
	echo 'API Key: '.$auth->apiKey."\n";
	echo 'Secret: '.$auth->secret."\n";
	echo 'Username: '.$auth->username."\n";
	echo 'Session Key: '.$auth->sessionKey."\n";
	echo 'Subscriber: '.$auth->subscriber."\n";
}

?>