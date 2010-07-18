<?php
/**
 * Stores the sqlite database methods
 * @package base
 */
/**
 * Allows access to the sqlite database using a standard database class
 * @package base
 */
class lastfmApiDatabase {
	/**
	 * Stores the path to the database
	 * @var string
	 */
	var $path;
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
	 * @param string $path The path to the database
	 * @return void
	 */
	function __construct($path) {
		$this->path = $path;
		$this->connectToDb();
	}
	
	/**
	 * Internal command to connect to the database
	 * @return void
	 */
	function connectToDb () {
		if (!$this->dbConn = @sqlite_open($this->path, 0666, $this->error)) {
			return false;
		}
	}
	
	/**
	 * Method which runs queries. Returns a class on success and false on error
	 * @param string $sql The SQL query to run
	 * @return class
	 * @uses lastfmApiDatabase_result
	 */
	function & query($sql) {
		if ( !$queryResource = sqlite_query($this->dbConn, $sql, SQLITE_BOTH, $this->error) ) {
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
	 * Stores the sqlite class
	 * @var class
	 */
	var $sqlite;
	/**
	 * Stores the query
	 * @var class
	 */
	var $query;
	
	/**
	 * Run when the class is created. Sets up the variables
	 * @param class $sqlite The sqlite class
	 * @param class $query The query
	 * @return void
	 */
	function lastfmApiDatabase_result(&$sqlite, $query) {
		$this->sqlite = &$sqlite;
		$this->query = $query;
	}
	
	/**
	 * Fetches the next result
	 * @return array
	 */
	function fetch () {
		if ( $row = sqlite_fetch_array($this->query) ) {
			return $row;
		}
		else if ( $this->size() > 0 ) {
			sqlite_seek($this->query, 0);
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
		while ( $row = sqlite_fetch_array($this->query) ) {
			$result[] = $row;
		}
		return $result;
	}
	
	/**
	 * Shows the number of results
	 * @return integer
	 */
	function size () {
		return sqlite_num_rows($this->query);
	}
}
?>