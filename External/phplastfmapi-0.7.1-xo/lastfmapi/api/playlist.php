<?php
/**
 * File that stores api calls for user playlists api calls
 * @package apicalls
 */
/**
 * Allows access to the api requests relating to user playlists
 * @package apicalls
 */
class lastfmApiPlaylist extends lastfmApi {
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
	 * Add a track to a Last.fm user's playlist (Requires full auth)
	 * @param array $methodVars An array with the following required values: <i>playlistId</i>, <i>artist</i>, <i>track</i>
	 * @return boolean
	 */
	public function addTrack($methodVars) {
		// Only allow full authed calls
		if ( $this->fullAuth == TRUE ) {
			// Check for required variables
			if ( !empty($methodVars['playlistID']) && !empty($methodVars['artist']) && !empty($methodVars['track']) ) {
				$vars = array(
					'method' => 'playlist.addtrack',
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
				$this->handleError(91, 'You must include playlistId, artist and track varialbes in the call for this method');
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
	 * Create a Last.fm playlist on behalf of a user (Requires full auth)
	 * @param array $methodVars An array with the following optional values: <i>title</i>, <i>description</i>
	 * @return array
	 */
	public function create($methodVars) {
		// Only allow full authed calls
		if ( $this->fullAuth == TRUE ) {
			$vars = array(
				'method' => 'playlist.create',
				'api_key' => $this->auth->apiKey,
				'sk' => $this->auth->sessionKey
			);
			$vars = array_merge($vars, $methodVars);
			$sig = $this->apiSig($this->auth->secret, $vars);
			$vars['api_sig'] = $sig;
			
			if ( $call = $this->apiPostCall($vars, 'xml') ) {
				$playlist['user'] = (string) $call->playlists['user'];
				$playlist['id'] = (string) $call->playlists->playlist->id;
				$playlist['title'] = (string) $call->playlists->playlist->title;
				$playlist['description'] = (string) $call->playlists->playlist->description;
				$playlist['date'] = strtotime(trim((string) $call->playlists->playlist->date));;
				$playlist['size'] = (string) $call->playlists->playlist->size;
				$playlist['duration'] = (string) $call->playlists->playlist->duration;
				$playlist['creator'] = (string) $call->playlists->playlist->creator;
				$playlist['url'] = (string) $call->playlists->playlist->url;
				$playlist['image']['small'] = (string) $call->playlists->playlist->image[0];
				$playlist['image']['medium'] = (string) $call->playlists->playlist->image[1];
				$playlist['image']['large'] = (string) $call->playlists->playlist->image[2];
				
				return $playlist;
			}
			else {
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
	 * Fetch XSPF playlists using a lastfm playlist url
	 * @param array $methodVars An array with the following required values: <i>playlistUrl</i>
	 * @return array
	 */
	public function fetch($methodVars) {
		// Check for required variables
		if ( !empty($methodVars['playlistURL']) ) {
			$vars = array(
				'method' => 'playlist.fetch',
				'api_key' => $this->auth->apiKey
			);
			$vars = array_merge($vars, $methodVars);
			
			if ( $call = $this->apiGetCall($vars) ) {
				$playlist['title'] = (string) $call->playlist->title;
				$playlist['annotation'] = (string) $call->playlist->annotation;
				$playlist['creator'] = (string) $call->playlist->creator;
				$playlist['date'] = strtotime(trim((string) $call->playlist->date));
				$playlist['version'] = (string) $call->playlist['version'];
				$i = 0;
				foreach ( $call->playlist->trackList->track as $track ) {
					$playlist['tracklisting'][$i]['title'] = (string) $track->title;
					$playlist['tracklisting'][$i]['url'] = (string) $track->extension->trackpage;
					$playlist['tracklisting'][$i]['duration'] = (string) $track->duration;
					$playlist['tracklisting'][$i]['info'] = (string) $track->info;
					$playlist['tracklisting'][$i]['image'] = (string) $track->image;
					$playlist['tracklisting'][$i]['artist']['name'] = (string) $track->creator;
					$playlist['tracklisting'][$i]['artist']['url'] = (string) $track->extension->artistpage;
					$playlist['tracklisting'][$i]['album']['name'] = (string) $track->album;
					$playlist['tracklisting'][$i]['album']['url'] = (string) $track->extension->albumpage;
					$i++;
				}
				return $playlist;
			}
			else {
				return FALSE;
			}
		}
		else {
			// Give a 91 error if incorrect variables are used
			$this->handleError(91, 'You must include playlistUrl varialbe in the call for this method');
			return FALSE;
		}
	}
}

?>