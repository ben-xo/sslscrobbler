<?php

require_once 'Scrobbler.php';

$api_key = '9dc2c6ce26602ff23787a7ebd4066ad8';
$api_secret = '9cc1995235704e14d9d9dcdb3a2ba693';
$api_sk = file_get_contents(dirname(__FILE__) . '/../../lastfm-ben-xo.txt');
if(empty($api_sk)) die('Need an SK.');

$scrobbler = new md_Scrobbler('ben-xo', null, $api_key, $api_secret, $api_sk, 'tst', '1.0');
$scrobbler->nowPlaying('Test', 'Test', 'Test', '73');

?>