<?php
/**
 * Stores the socket methods
 * @package base
 */
/**
 * Allows access to the socket methods using a standard class
 * @package base
 */
class lastfmApiSocket {
	/**
	 * Stores the socket handler
	 * @var class
	 */
	private $handle;
	/**
	 * Stores the host name
	 * @var string
	 */
	private $host;
	/**
	 * Stores the port number
	 * @var integer
	 */
	private $port;
	
	/**
	 * Stores the error string
	 * @var string
	 */
	public $error_string;
	/**
	 * Stores the error number
	 * @var integer
	 */
	public $error_number;
	
	/**
	 * Run when the class is created. Sets the variables
	 * @param string $host The host name
	 * @param integer $port The port number
	 * @return boolean
	 */
	function lastfmApiSocket ($host, $port) {
		// Set class variables
		$this->host = $host;
		$this->port = $port;
		
		// Open a connection in the class variable
		$this->handle = fsockopen($this->host, $this->port, $this->error_number, $this->error_string);
		if ( $this->handle ) {
			return TRUE;
		}
		else {
			// If failed return false
			return FALSE;
		}
	}
	
	/**
	 * Send data through the socket and listen for a return
	 * @param string $msg Data to send
	 * @param string $type The type of data to return (array or string)
	 * @return string|array
	 */
	function send ($msg, $type = '') {
		// Send message over connection
		fwrite($this->handle, $msg);
		
		// Check what type is required
		if ( $type == 'array' ) {
			// If array loop and create array
			$response = array();
			$line_num = 0;
			while ( !feof($this->handle) ) {
	       			$response[$line_num] = fgets($this->handle, 4096);
				$line_num++;
	   		}
			// Return response as array
			return $response;
		}
		elseif ( $type == 'string' ) {
			// If string, loop and create string
			$response = '';
	   		while ( !feof($this->handle) ) {
	       			$response .= fgets($this->handle, 4096);
	   		}
			// Return response as string
			return $response;
		}
		else {
			// If anything else, return nothing but a TRUE
			return TRUE;
		}
		
	}
	
	/**
	 * Closes the connection
	 * @return boolean
	 */
	function close () {
		// Close connection
		fclose($this->handle);
		return TRUE;
	}
}

?>