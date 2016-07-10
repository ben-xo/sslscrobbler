<?php

date_default_timezone_set('UTC');

$slave = popen ('php slave.php', 'r');
$conn = false;
while($conn === false)
$conn = @stream_socket_client('tcp://127.0.0.1:5380', $err, $errStr, 1.0);

echo "Writing to slave...";
sleep(2);
fwrite($conn, "H");
echo fread($conn, 128);

sleep(2);
fwrite($conn, "B");
echo fread($conn, 128);

fwrite($conn, "B");
echo fread($conn, 128);

fwrite($conn, "B");
echo fread($conn, 128);

sleep(3);
fwrite($conn, "H");
echo fread($conn, 128);

sleep(3);
fwrite($conn, "A");
echo fread($conn, 128);

sleep(1);
fwrite($conn, "X");

