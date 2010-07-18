<?php
/**
 * Stores the caching methods
 * @package base
 */
/**
 * Allows access to the caching methods to cache data when api calls are made
 * @package base
 */
class lastfmApiCache {
	/**
	 * Stores the batabase class
	 * @var class
	 */
	private $db;
	/**
	 * Stores the batabase type
	 * @var string
	 */
	private $type;
	/**
	 * Stores the error details
	 * @var array
	 */
	public $error;
	/**
	 * Stores the path to the sqlite database
	 * @var string
	 */
	private $path;
	/**
	 * Stores the amount of time to cahce for
	 * @var integer
	 */
	private $cache_length;
	/**
	 * Stores the config array
	 * @var array
	 */
	private $config;
	/**
	 * States if caching is enabled or not
	 * @var boolean
	 */
	private $enabled;
	
	/**
	 * Run when the class is created
	 * @param array $config The config array
	 * @uses lastfmApiDatabase
	 */
	function __construct($config) {
		$this->config = $config;
		
		if ( isset($this->config['cache_type']) ) {
			$this->type = $this->config['cache_type'];
		}
		else {
			$this->type = 'sqlite';
		}
		
		$this->check_if_enabled();
		
		if ( $this->enabled == true ) {
			if ( $this->type == 'sqlite' ) {
				require_once 'sqlite.php';				
				$this->db = new lastfmApiDatabase($this->config['path'].'phplastfmapi');
			}
			else {
				if ( isset($this->config['database']['host']) && isset($this->config['database']['username']) && isset($this->config['database']['password']) && isset($this->config['database']['name']) ) {
					require_once 'mysql.php';				
					$this->db = new lastfmApiDatabase($this->config['database']['host'], $this->config['database']['username'], $this->config['database']['password'], $this->config['database']['name']);
				}
				else {
					$this->error = 'Not all mysql database variables were supplied';
					return false;
				}
			}
			if ( !empty($this->db->error) ) {
				$this->error = $this->db->error;
				return false;
			}
			else {
				$this->check_table_exists();
			}
			//$this->show_all();
		}
	}
	
	/**
	 * Internal method to chack if caching is enabled
	 * @access private
	 * @return void
	 */
	private function check_if_enabled() {
		if ( $this->config['enabled'] == true && function_exists('sqlite_open') ) {
			$this->enabled = true;
		}
		else {
			$this->enabled = false;
		}
	}
	
	/**
	 * Internal method to check if the table exists
	 * @access private
	 * @return void
	 */
	private function check_table_exists() {
		if ( $this->type == 'sqlite' ) {
			$query = "SELECT count(*) FROM sqlite_master WHERE name='cache'";
			$result = $this->db->query($query);
			$numbers = $result->fetchAll();
			if ( $numbers[0]['count(*)'] > 0 ) {
				// Ok
			}
			else {
				$this->create_table();
			}
		}
		else {
			$query = "show tables like 'cache'";
			$result = $this->db->query($query);
			if ( $result->size() > 0 ) {
				// Ok
			}
			else {
				$this->create_table();
			}
		}
	}
	
	/**
	 * Internal method to create the table if it doesn't exist
	 * @access private
	 * @return void
	 */
	private function create_table() {
		if ( $this->type == 'sqlite' ) {
			$auto_increase = '';
		}
		else {
			$auto_increase = ' AUTO_INCREMENT';
		}
		$query = "CREATE TABLE cache (cache_id INTEGER PRIMARY KEY".$auto_increase.", unique_vars TEXT, expires INT, body TEXT)";
		if ( $this->db->query($query) ) {
			// Ok
		}
		else {
			// TODO: Handle error
			echo $this->db->error;
		}
	}
	
	/**
	 * Searches the database for the cahce date. Returns an array if it exists or false if it doesn't 
	 * @access public
	 * @param array $unique_vars Array of variables that are unique to this piece of cached data
	 * @return string
	 */
	public function get($unique_vars) {
		if ( $this->enabled == true ) {
			$query = "SELECT expires, body FROM cache WHERE unique_vars='".htmlentities(serialize($unique_vars))."' LIMIT 1";
			if ( $result = $this->db->query($query) ) {
				if ( $result->size() > 0 ) {
					$row = $result->fetch();
					if ( $row['expires'] < time() ) {
						$this->del($unique_vars);
						return false;
					}
					else {
						//print_r(unserialize(html_entity_decode($row['body'])));
						return unserialize(html_entity_decode($row['body']));
					}
				}
				else {
					return false;
				}
			}
			else {
				// TODO: Handle error
				return false;
			}
		}
		else {
			return false;
		}
	}
	
	/**
	 * Adds new cache data to the database
	 * @access public
	 * @param array $unique_vars Array of variables that are unique to this piece of cached data
	 * @param string $body The contents of the cache to put into the database
	 * @return boolean
	 */
	public function set($unique_vars,  $body) {
		if ( $this->enabled == true ) {
			$expire = time() + $this->config['cache_length'];
			$query = "INSERT INTO cache (unique_vars, expires, body) VALUES ('".htmlentities(serialize($unique_vars))."', '".$expire."', \"".htmlentities(serialize($body))."\")";
			if ( $this->db->query($query) ) {
				return true;
			}
			else {
				// TODO: Handle error
				return false;
			}
		}
		else {
			return false;
		}
	}
	
	/**
	 * Internal method to delete unneeded cache data
	 * @access private
	 * @param array $unique_vars Array of variables that are unique to this piece of cached data
	 * @return boolean
	 */
	private function del($unique_vars) {
		$query = "DELETE FROM cache WHERE unique_vars='".htmlentities(serialize($unique_vars))."'";
		if ( $this->db->query($query) ) {
			return true;
		}
		else {
			// TODO: Handle error
			return false;
		}
	}
	
	/**
	 * Internal method to show all cached data (used for debugging)
	 * @access private
	 * @return boolean
	 */
	private function show_all() {
		$query = "SELECT expires, body FROM cache";
		if ( $result = $this->db->query($query) ) {
			if ( $result->size() > 0 ) {
				$results = $result->fetchAll();
				echo '<pre>';
				print_r($results);
				echo '</pre>';
			}
			else {
				return false;
			}
		}
		else {
			// TODO: Handle error
			return false;
		}
	}
}

?>