<?php 

date_default_timezone_set('UTC');

$socket = stream_socket_server('tcp://127.0.0.1:5380', $err, $errStr);
if($socket === false) exit;
$conn = stream_socket_accept($socket);

$i = 0;
$buffer = array();
$want_to_error = array();
$command = '';
while(true)
{
	$i++; fwrite(STDERR, '+');
	$want_to_read = array($conn);
	$want_to_write = $want_to_error = array();
	if(stream_select($want_to_read, $want_to_write, $want_to_error, 1, 0))
	{
		fwrite(STDERR, '!');
		if($want_to_read)
		{
			fwrite(STDERR, '<');
			$command = substr(fread($conn, 4096), 0, 1);
		}
	}
	if($command) 
	{
		switch($command) {
			case 'H': $buffer[] = 'HELLO WORLD'; break;
			case 'A': $buffer[] = $i; break;
			case 'B': $buffer[] = $i*$i; break;
			case 'X': exit;
			default:
		}
		$command = '';
	}

	if($buffer)
	{	
		fwrite(STDERR, '>');
		fwrite($conn, implode('', $buffer));
		fflush($conn);
		$buffer = array();
	}
}
