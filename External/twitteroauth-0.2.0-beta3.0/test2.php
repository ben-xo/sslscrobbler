<?php

require_once 'twitteroauth/twitteroauth.php';

$conn = new TwitterOAuth('muDxig9YR8URoKrv3GamA', 'UyOd1a9Gjicoc1Yt4dvZT3Ext8Z2paH40YSRYambc');
$request_token = $conn->getRequestToken();
$redirect_url = $conn->getAuthorizeURL($request_token);

system("open '{$redirect_url}'");
$pin = readline("Please visit $redirect_url and enter pin: ");


$conn = new TwitterOAuth('muDxig9YR8URoKrv3GamA', 'UyOd1a9Gjicoc1Yt4dvZT3Ext8Z2paH40YSRYambc', $request_token['oauth_token'], $request_token['oauth_token_secret']);
$access_token = $conn->getAccessToken($pin);

echo "Your token is " . $access_token['oauth_token'] . "\n";
echo "Your token secret is " . $access_token['oauth_token_secret'] . "\n";
