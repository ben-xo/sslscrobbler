<?php

$dsn = 'mysql:host=127.0.0.1;dbname=test';
$user = '';
$pass = '';
$sql = 'SELECT track FROM example WHERE pk=\'ben\'';

//////////////////////////////////////

try {
    $dbh = new PDO($dsn, $user, $pass);
    $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $dbh->exec('SET CHARACTER SET utf8');
    $statement = $dbh->query($sql);
    $result = $statement->fetchAll();
    $output = array(
        'playing' => $result[0]['track']
    );
} catch (Exception $e) {
    $output = array( 'error' => $e->getMessage() );
}
header("Content-type: application/json");
echo json_encode($output);