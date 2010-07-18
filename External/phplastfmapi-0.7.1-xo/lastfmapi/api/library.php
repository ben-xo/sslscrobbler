<?php
/**
 * File that stores api calls for user library api calls
 * @package apicalls
 */
/**
 * Allows access to the api requests relating to user libraries
 * @package apicalls
 */
class lastfmApiLibrary extends lastfmApi {
	/**
	 * Stores the config values set in the call
	 * @access public
	 * @var array
	 */
	public $config;
	/**
	 * Stores the auth variables used in all api calls
	 * @access private
	 * @var array
	 */
	private $auth;
	/**
	 * States if the user has full authentication to use api requests that modify data
	 * @access private
	 * @var boolean
	 */
	private $fullAuth;
	
	/**
	 * @param array $auth Passes the authentication variables
	 * @param array $fullAuth A boolean value stating if the user has full authentication or not
	 * @param array $config An array of config variables related to caching and other features
	 */
	function __construct($auth, $fullAuth, $config) {
		$this->auth = $auth;
		$this->fullAuth = $fullAuth;
		$this->config = $config;
	}
	
	/**
	 * Add an album to a user's Last.fm library (Requires full auth)
	 * @param array $methodVars An array with the following required values: <i>artist</i>, <i>album</i>
	 * @return boolean
	 */
	public function addAlbum($methodVars) {
		// Only allow full authed calls
		if ( $this->fullAuth == TRUE ) {
			// Check for required variables
			if ( !empty($methodVars['artist']) && !empty($methodVars['album']) ) {
				$vars = array(
					'method' => 'library.addalbum',
					'api_key' => $this->auth->apiKey,
					'sk' => $this->auth->sessionKey
				);
				$vars = array_merge($vars, $methodVars);
				$sig = $this->apiSig($this->auth->secret, $vars);
				$vars['api_sig'] = $sig;
				
				if ( $call = $this->apiPostCall($vars) ) {
					return TRUE;
				}
				else {
					return FALSE;
				}
			}
			else {
				// Give a 91 error if incorrect variables are used
				$this->handleError(91, 'You must include artist and album varialbes in the call for this method');
				return FALSE;
			}
		}
		else {
			// Give a 92 error if not fully authed
			$this->handleError(92, 'Method requires full auth. Call auth.getSession using lastfmApiAuth class');
			return FALSE;
		}
	}
	
	/**
	 * Add an artist to a user's Last.fm library (Requires full auth)
	 * @param array $methodVars An array with the following required values: <i>artist</i>
	 * @return boolean
	 */
	public function addArtist($methodVars) {
		// Only allow full authed calls
		if ( $this->fullAuth == TRUE ) {
			// Check for required variables
			if ( !empty($methodVars['artist']) ) {
				$vars = array(
					'method' => 'library.addartist',
					'api_key' => $this->auth->apiKey,
					'sk' => $this->auth->sessionKey
				);
				$vars = array_merge($vars, $methodVars);
				$sig = $this->apiSig($this->auth->secret, $vars);
				$vars['api_sig'] = $sig;
				
				if ( $call = $this->apiPostCall($vars) ) {
					return TRUE;
				}
				else {
					return FALSE;
				}
			}
			else {
				// Give a 91 error if incorrect variables are used
				$this->handleError(91, 'You must include artist varialbe in the call for this method');
				return FALSE;
			}
		}
		else {
			// Give a 92 error if not fully authed
			$this->handleError(92, 'Method requires full auth. Call auth.getSession using lastfmApiAuth class');
			return FALSE;
		}
	}
	
	/**
	 * Add a track to a user's Last.fm library (Requires full auth)
	 * @param array $methodVars An array with the following required values: <i>artist</i>, <i>track</i>
	 * @return boolean
	 */
	public function addTrack($methodVars) {
		// Only allow full authed calls
		if ( $this->fullAuth == TRUE ) {
			// Check for required variables
			if ( !empty($methodVars['artist']) && !empty($methodVars['track']) ) {
				$vars = array(
					'method' => 'library.addtrack',
					'api_key' => $this->auth->apiKey,
					'sk' => $this->auth->sessionKey
				);
				$vars = array_merge($vars, $methodVars);
				$sig = $this->apiSig($this->auth->secret, $vars);
				$vars['api_sig'] = $sig;
				
				if ( $call = $this->apiPostCall($vars) ) {
					return TRUE;
				}
				else {
					return FALSE;
				}
			}
			else {
				// Give a 91 error if incorrect variables are used
				$this->handleError(91, 'You must include artist and track varialbes in the call for this method');
				return FALSE;
			}
		}
		else {
			// Give a 92 error if not fully authed
			$this->handleError(92, 'Method requires full auth. Call auth.getSession using lastfmApiAuth class');
			return FALSE;
		}
	}
	
	/**
	 * A paginated list of all the albums in a user's library, with play counts and tag counts
	 * @param array $methodVars An array with the following required values: <i>user</i> and optional values: <i>page</i>, <i>limit</i>
	 * @return array
	 */
	public function getAlbums($methodVars) {
		// Check for required variables
		if ( !empty($methodVars['user']) ) {
			$vars = array(
				'method' => 'library.getalbums',
				'api_key' => $this->auth->apiKey
			);
			$vars = array_merge($vars, $methodVars);
			
			if ( $call = $this->apiGetCall($vars) ) {
				$albums['page'] = (string) $call->albums['page'];
				$albums['perPage'] = (string) $call->albums['perPage'];
				$albums['totalPages'] = (string) $call->albums['totalPages'];
				$i = 0;
				foreach ( $call->albums->album as $album ) {
					$albums['results'][$i]['name'] = (string) $album->name;
					// THIS DOESN'T WORK AS DOCUMENTED  --- $albums['results'][$i]['rank'] = (string) $album['rank'];
					$albums['results'][$i]['playcount'] = (string) $album->playcount;
					$albums['results'][$i]['tagcount'] = (string) $album->tagcount;
					$albums['results'][$i]['mbid'] = (string) $album->mbid;
					$albums['results'][$i]['url'] = (string) $album->url;
					$albums['results'][$i]['artist']['name'] = (string) $album->artist->name;
					$albums['results'][$i]['artist']['mbid'] = (string) $album->artist->mbid;
					$albums['results'][$i]['artist']['url'] = (string) $album->artist->url;
					$albums['results'][$i]['image']['small'] = (string) $album->image[0];
					$albums['results'][$i]['image']['medium'] = (string) $album->image[1];
					$albums['results'][$i]['image']['large'] = (string) $album->image[2];
					$i++;
				}
				return $albums;
			}
			else {
				return FALSE;
			}
		}
		else {
			// Give a 91 error if incorrect variables are used
			$this->handleError(91, 'You must include a user variable in the call for this method');
			return FALSE;
		}
	}
	
	/**
	 * A paginated list of all the artists in a user's library, with play counts and tag counts
	 * @param array $methodVars An array with the following required values: <i>user</i> and optional values: <i>page</i>, <i>limit</i>
	 * @return array
	 */
	public function getArtists($methodVars) {
		// Check for required variables
		if ( !empty($methodVars['user']) ) {
			$vars = array(
				'method' => 'library.getartists',
				'api_key' => $this->auth->apiKey
			);
			$vars = array_merge($vars, $methodVars);
			
			if ( $call = $this->apiGetCall($vars) ) {
				$artists['page'] = (string) $call->artists['page'];
				$artists['perPage'] = (string) $call->artists['perPage'];
				$artists['totalPages'] = (string) $call->artists['totalPages'];
				$i = 0;
				foreach ( $call->artists->artist as $artist ) {
					$artists['results'][$i]['name'] = (string) $artist->name;
					// THIS DOESN'T WORK AS DOCUMENTED  --- $artists['results'][$i]['rank'] = (string) $artist['rank'];
					$artists['results'][$i]['playcount'] = (string) $artist->playcount;
					$artists['results'][$i]['tagcount'] = (string) $artist->tagcount;
					$artists['results'][$i]['mbid'] = (string) $artist->mbid;
					$artists['results'][$i]['url'] = (string) $artist->url;
					$artists['results'][$i]['streamable'] = (string) $artist->streamable;
					$artists['results'][$i]['image']['small'] = (string) $artist->image[0];
					$artists['results'][$i]['image']['medium'] = (string) $artist->image[1];
					$artists['results'][$i]['image']['large'] = (string) $artist->image[2];
					$i++;
				}
				return $artists;
			}
			else {
				return FALSE;
			}
		}
		else {
			// Give a 91 error if incorrect variables are used
			$this->handleError(91, 'You must include a user variable in the call for this method');
			return FALSE;
		}
	}
	
	/**
	 * A paginated list of all the tracks in a user's library, with play counts and tag counts
	 * @param array $methodVars An array with the following required values: <i>user</i> and optional values: <i>page</i>, <i>limit</i>
	 * @return array
	 */
	public function getTracks($methodVars) {
		// Check for required variables
		if ( !empty($methodVars['user']) ) {
			$vars = array(
				'method' => 'library.gettracks',
				'api_key' => $this->auth->apiKey
			);
			$vars = array_merge($vars, $methodVars);
			
			if ( $call = $this->apiGetCall($vars) ) {
				$tracks['page'] = (string) $call->tracks['page'];
				$tracks['perPage'] = (string) $call->tracks['perPage'];
				$tracks['totalPages'] = (string) $call->tracks['totalPages'];
				$i = 0;
				foreach ( $call->tracks->track as $track ) {
					$tracks['results'][$i]['name'] = (string) $track->name;
					// THIS DOESN'T WORK AS DOCUMENTED  --- $tracks['results'][$i]['rank'] = (string) $track['rank'];
					$tracks['results'][$i]['playcount'] = (string) $track->playcount;
					$tracks['results'][$i]['tagcount'] = (string) $track->tagcount;
					$tracks['results'][$i]['url'] = (string) $track->url;
					$tracks['results'][$i]['streamable'] = (string) $track->streamable;
					$tracks['results'][$i]['fulltrack'] = (string) $track->streamable['fulltrack'];
					$tracks['results'][$i]['artist']['name'] = (string) $track->artist->name;
					$tracks['results'][$i]['artist']['mbid'] = (string) $track->artist->mbid;
					$tracks['results'][$i]['artist']['url'] = (string) $track->artist->url;
					$tracks['results'][$i]['image']['small'] = (string) $track->image[0];
					$tracks['results'][$i]['image']['medium'] = (string) $track->image[1];
					$tracks['results'][$i]['image']['large'] = (string) $track->image[2];
					$i++;
				}
				return $tracks;
			}
			else {
				return FALSE;
			}
		}
		else {
			// Give a 91 error if incorrect variables are used
			$this->handleError(91, 'You must include a user variable in the call for this method');
			return FALSE;
		}
	}
}

?>