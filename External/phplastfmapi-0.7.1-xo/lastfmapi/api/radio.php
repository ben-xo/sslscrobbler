<?php
/**
 * File that stores api calls for radio api calls
 * @package apicalls
 */
/**
 * Allows access to the api requests relating to the lastfm radio
 * @package apicalls
 */
class lastfmApiRadio extends lastfmApi {
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
	 * Tune into a radio station using a lastfm radio url. (Requires full auth)
	 * @param array $methodVars An array with the following required value: <i>station</i> and the following optional value: <i>lang</i> 
	 * @return array
	 */
	public function tune($methodVars) {
		// Only allow full authed calls
		if ( $this->fullAuth == TRUE ) {
			// Check for required variables
			if ( !empty($methodVars['station']) ) {
				$vars = array(
					'method' => 'radio.tune',
					'api_key' => $this->auth->apiKey,
					'sk' => $this->auth->sessionKey
				);
				$vars = array_merge($vars, $methodVars);
				
				$sig = $this->apiSig($this->auth->secret, $vars);
				$vars['api_sig'] = $sig;
				
				if ( $call = $this->apiPostCall($vars) ) {
					$tune['type'] = (string) $call->station->type;
					$tune['name'] = (string) $call->station->name;
					$tune['url'] = (string) $call->station->url;
					$tune['supportsdiscovery'] = (string) $call->station->supportsdiscovery;
					
					return $tune;
				}
				else {
					return FALSE;
				}
			}
			else {
				// Give a 91 error if incorrect variables are used
				$this->handleError(91, 'You must include eventId and status varialbes in the call for this method');
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
	 * Get a playlist of songs to play using the radio API. Must run radio.tune first. (Requires full auth)
	 * @param array $methodVars An array with the following optional values: <i>discovery</i> and <i>rtp</i>
	 * @return array
	 */
	public function getPlaylist($methodVars = Array()) {
		// Only allow full authed calls
		if ( $this->fullAuth == TRUE ) {
			$vars = array(
				'method' => 'radio.getPlaylist',
				'api_key' => $this->auth->apiKey,
				'sk' => $this->auth->sessionKey
			);
			$vars = array_merge($vars, $methodVars);
			
			$sig = $this->apiSig($this->auth->secret, $vars);
			$vars['api_sig'] = $sig;
			
			if ( $call = $this->apiPostCall($vars) ) {
				$playlist['version'] = (string) $call->playlist['version'];
				$playlist['title'] = (string) $call->playlist->title;
				$playlist['creator'] = (string) $call->playlist->creator;
				$playlist['date'] = strtotime((string) $call->playlist->date);
				$playlist['expire'] = (string) $call->playlist->link;
				if ( count($call->playlist->trackList) > 0 ) {
					$i = 0;
					foreach ( $call->playlist->trackList->track as $track ) {
						$playlist['tracklist'][$i]['location'] = (string) $track->location;
						$playlist['tracklist'][$i]['title'] = (string) $track->title;
						$playlist['tracklist'][$i]['identifier'] = (string) $track->identifier;
						$playlist['tracklist'][$i]['album'] = (string) $track->album;
						$playlist['tracklist'][$i]['creator'] = (string) $track->creator;
						$playlist['tracklist'][$i]['duration'] = (string) $track->duration;
						$playlist['tracklist'][$i]['image'] = (string) $track->image;
						$playlist['tracklist'][$i]['trackauth'] = (string) $track->extension->trackauth;
						$playlist['tracklist'][$i]['albumid'] = (string) $track->extension->albumid;
						$playlist['tracklist'][$i]['artistid'] = (string) $track->extension->artistid;
						$playlist['tracklist'][$i]['recording'] = (string) $track->extension->recording;
						$playlist['tracklist'][$i]['artistpage'] = (string) $track->extension->artistpage;
						$playlist['tracklist'][$i]['albumpage'] = (string) $track->extension->albumpage;
						$playlist['tracklist'][$i]['trackpage'] = (string) $track->extension->trackpage;
						$playlist['tracklist'][$i]['buyTrackURL'] = (string) $track->extension->buyTrackURL;
						$playlist['tracklist'][$i]['buyAlbumURL'] = (string) $track->extension->butAlbumURL;
						$playlist['tracklist'][$i]['freeTrackURL'] = (string) $track->extension->freeTrackURL;
						$i++;
					}
				}
				
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
}

?>