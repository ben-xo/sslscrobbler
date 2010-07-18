<?php
/**
 * File that stores api calls for group api calls
 * @package apicalls
 */
/**
 * Allows access to the api requests relating to groups
 * @package apicalls
 */
class lastfmApiGroup extends lastfmApi {
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
	 * Get a list of members for this group
	 * @param array $methodVars An array with the following required values: <i>group</i>
	 * @return array
	 */
	public function getMembers($methodVars) {
		// Check for required variables
		if ( !empty($methodVars['group']) ) {
			$vars = array(
				'method' => 'group.getmembers',
				'api_key' => $this->auth->apiKey
			);
			$vars = array_merge($vars, $methodVars);
			
			if ( $call = $this->apiGetCall($vars) ) {
				$i = 0;
				$members['for'] = (string) $call->members['for'];
				$members['page'] = (string) $call->members['page'];
				$members['perPage'] = (string) $call->members['perPage'];
				$members['totalPages'] = (string) $call->members['totalPages'];
				foreach ( $call->members->user as $user ) {
					$members['members'][$i]['name'] = (string) $user->name;
					$members['members'][$i]['realname'] = (string) $user->realname;
					$members['members'][$i]['url'] = (string) $user->url;
					$members['members'][$i]['image']['small'] = (string) $user->image[0];
					$members['members'][$i]['image']['medium'] = (string) $user->image[1];
					$members['members'][$i]['image']['large'] = (string) $user->image[2];
					$i++;
				}
				return $members;
			}
			else {
				return FALSE;
			}
		}
		else {
			// Give a 91 error if incorrect variables are used
			$this->handleError(91, 'You must include a group variable in the call for this method');
			return FALSE;
		}
	}
	
	/**
	 * Get an album chart for a group, for a given date range. If no date range is supplied, it will return the most recent album chart for this group
	 * @param array $methodVars An array with the following required values: <i>group</i> and optional values: <i>from</i>, <i>to</i>
	 * @return array
	 */
	public function getWeeklyAlbumChart($methodVars) {
		// Check for required variables
		if ( !empty($methodVars['group']) ) {
			$vars = array(
				'method' => 'group.getweeklyalbumchart',
				'api_key' => $this->auth->apiKey
			);
			$vars = array_merge($vars, $methodVars);
			
			if ( $call = $this->apiGetCall($vars) ) {
				$i = 0;
				foreach ( $call->weeklyalbumchart->album as $album ) {
					$albums[$i]['name'] = (string) $album->name;
					$albums[$i]['rank'] = (string) $album['rank'];
					$albums[$i]['artist']['name'] = (string) $album->artist;
					$albums[$i]['artist']['mbid'] = (string) $album->artist['mbid'];
					$albums[$i]['playcount'] = (string) $album->playcount;
					$albums[$i]['url'] = (string) $album->url;
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
			$this->handleError(91, 'You must include a group variable in the call for this method');
			return FALSE;
		}
	}
	
	/**
	 * Get an artist chart for a group, for a given date range. If no date range is supplied, it will return the most recent album chart for this group
	 * @param array $methodVars An array with the following required values: <i>group</i> and optional values: <i>from</i>, <i>to</i>
	 * @return array
	 */
	public function getWeeklyArtistChart($methodVars) {
		// Check for required variables
		if ( !empty($methodVars['group']) ) {
			$vars = array(
				'method' => 'group.getweeklyartistchart',
				'api_key' => $this->auth->apiKey
			);
			$vars = array_merge($vars, $methodVars);
			
			if ( $call = $this->apiGetCall($vars) ) {
				$i = 0;
				foreach ( $call->weeklyartistchart->artist as $artist ) {
					$artists[$i]['name'] = (string) $artist->name;
					$artists[$i]['rank'] = (string) $artist['rank'];
					$artists[$i]['mbid'] = (string) $artist->mbid;
					$artists[$i]['playcount'] = (string) $artist->playcount;
					$artists[$i]['url'] = (string) $artist->url;
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
			$this->handleError(91, 'You must include a group variable in the call for this method');
			return FALSE;
		}
	}
	
	/**
	 * Get a list of available charts for this group, expressed as date ranges which can be sent to the chart services
	 * @param array $methodVars An array with the following required values: <i>group</i>
	 * @return array
	 */
	public function getWeeklyChartList($methodVars) {
		// Check for required variables
		if ( !empty($methodVars['group']) ) {
			$vars = array(
				'method' => 'group.getweeklychartlist',
				'api_key' => $this->auth->apiKey
			);
			$vars = array_merge($vars, $methodVars);
			
			if ( $call = $this->apiGetCall($vars) ) {
				$i = 0;
				foreach ( $call->weeklychartlist->chart as $chart ) {
					$chartList[$i]['from'] = (string) $chart['from'];
					$chartList[$i]['to'] = (string) $chart['to'];
					$i++;
				}
				return $chartList;
			}
			else {
				return FALSE;
			}
		}
		else {
			// Give a 91 error if incorrect variables are used
			$this->handleError(91, 'You must include a group variable in the call for this method');
			return FALSE;
		}
	}
	
	/**
	 * Get a track chart for a group, for a given date range. If no date range is supplied, it will return the most recent album chart for this group
	 * @param array $methodVars An array with the following required values: <i>group</i> and optional values: <i>from</i>, <i>to</i>
	 * @return array
	 */
	public function getWeeklyTrackChart($methodVars) {
		// Check for required variables
		if ( !empty($methodVars['group']) ) {
			$vars = array(
				'method' => 'group.getweeklytrackchart',
				'api_key' => $this->auth->apiKey
			);
			$vars = array_merge($vars, $methodVars);
			
			if ( $call = $this->apiGetCall($vars) ) {
				$i = 0;
				foreach ( $call->weeklytrackchart->track as $track ) {
					$tracks[$i]['name'] = (string) $track->name;
					$tracks[$i]['rank'] = (string) $track['rank'];
					$tracks[$i]['artist']['name'] = (string) $track->artist;
					$tracks[$i]['artist']['mbid'] = (string) $track->artist['mbid'];
					$tracks[$i]['playcount'] = (string) $track->playcount;
					$tracks[$i]['url'] = (string) $track->url;
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
			$this->handleError(91, 'You must include a group variable in the call for this method');
			return FALSE;
		}
	}
}

?>