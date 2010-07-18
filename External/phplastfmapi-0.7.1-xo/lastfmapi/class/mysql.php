<?php
/**
 * Stores the mysql database methods
 * @package base
 */
/**
 * Allows access to the mysql database using a standard database class
 * @package base
 */
class lastfmApiDatabase {
	/**
	 * Stores the host name
	 * @var string
	 */
	private $host;
	/**
	 * Stores the username
	 * @var string
	 */
	private $dbUser;
	/**
	 * Stores the password
	 * @var string
	 */
	private $dbPass;
	/**
	 * Stores the database name
	 * @var string
	 */
	private $dbName;
	/**
	 * Stores the connection status
	 * @var boolean
	 */
	public $dbConn;
	/**
	 * Stores the error details
	 * @var array
	 */
	public $error;
	
	/**
	 * Run when the class is created. Sets up the variables
	 * @param string $host Database host address
	 * @param string $dbUser Database username
	 * @param string $dbPass Database password
	 * @param string $dbName Database name
	 * @return void
	 */
	function __construct($host,$dbUser,$dbPass,$dbName) {
		$this->host = $host;
		$this->dbUser = $dbUser;
		$this->dbPass = $dbPass;
		$this->dbName = $dbName;
		
		$this->connectToDb();
	}
	
	/**
	 * Internal command to connect to the database
	 * @return void
	 */
	private function connectToDb () {
		if (!$this->dbConn = @mysql_connect($this->host, $this->dbUser, $this->dbPass)) {
			$this->handleError();
			return false;
		}
		else if ( !@mysql_select_db($this->dbName, $this->dbConn) ) {
			$this->handleError();
			return false;
		}
	}
	
	/**
	 * Internal command to handle errors and populate the error variable
	 * @return void
	 */
	private function handleError () {
		$this->error = mysql_error($this->dbConn);
	}
	
	/**
	 * Command which runs queries. Returns a class on success and flase on error
	 * @param string $sql The SQL query to run
	 * @return class
	 * @uses lastfmApiDatabase_result
	 */
	public function query($sql) {
		if ( !$queryResource = mysql_query($sql, $this->dbConn) ) {
			echo mysql_error();
			$this->handleError();
			return false;
		}
		else {
			$return = new lastfmApiDatabase_result($this, $queryResource);
			return $return;
		}
	}
}

/**
 * A class which allows interaction with results when a query is run by lastfmApiDatabase
 * @package base
 */
class lastfmApiDatabase_result {
	/**
	 * Stores the mysql class
	 * @var class
	 */
	var $mysql;
	/**
	 * Stores the query
	 * @var class
	 */
	var $query;
	
	/**
	 * Run when the class is created. Sets up the variables
	 * @param class $mysql The mysql class
	 * @param class $query The query
	 * @return void
	 */
	function lastfmApiDatabase_result(&$mysql,$query) {
		$this->mysql = &$mysql;
		$this->query = $query;
	}
	
	/**
	 * Fetches the next result
	 * @return array
	 */
	public function fetch () {
		if ( $row = mysql_fetch_array($this->query) ) {
			return $row;
		}
		else if ( $this->size() > 0 ) {
			mysql_data_seek($this->query,0);
			return false;
		}
		else {
			return false;
		}
	}
	
	/**
	 * Fetches all the results
	 * @return array
	 */
	function fetchAll() {
		$result = array();
		while ( $row = mysql_fetch_array($this->query) ) {
			$result[] =$row;
		}
		return $result;
	}
	
	/**
	 * Shows the number of results
	 * @return integer
	 */
	function size () {
		return mysql_num_rows($this->query);
	}
}
?>