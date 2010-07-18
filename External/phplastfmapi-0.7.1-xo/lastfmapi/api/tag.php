<?php
/**
 * File that stores api calls for tag api calls
 * @package apicalls
 */
/**
 * Allows access to the api requests relating to tags
 * @package apicalls
 */
class lastfmApiTag extends lastfmApi {
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
	 * Search for tags similar to this one. Returns tags ranked by similarity, based on listening data
	 * @param array $methodVars An array with the following required values: <i>tag</i>
	 * @return array
	 */
	public function getSimilar($methodVars) {
		// Check for required variables
		if ( !empty($methodVars['tag']) ) {
			$vars = array(
				'method' => 'tag.getsimilar',
				'api_key' => $this->auth->apiKey
			);
			$vars = array_merge($vars, $methodVars);
			
			if ( $call = $this->apiGetCall($vars) ) {
				$i = 0;
				foreach ( $call->similartags->tag as $tag ) {
					$similar[$i]['name'] = (string) $tag->name;
					$similar[$i]['url'] = (string) $tag->url;
					$similar[$i]['streamable'] = (string) $tag->streamable;
					$i++;
				}
				return $similar;
			}
			else {
				return FALSE;
			}
		}
		else {
			// Give a 91 error if incorrect variables are used
			$this->handleError(91, 'You must include tag variable in the call for this method');
			return FALSE;
		}
	}
	
	/**
	 * Get the top albums tagged by this tag, ordered by tag count
	 * @param array $methodVars An array with the following required values: <i>tag</i>
	 * @return array
	 */
	public function getTopAlbums($methodVars) {
		// Check for required variables
		if ( !empty($methodVars['tag']) ) {
			$vars = array(
				'method' => 'tag.gettopalbums',
				'api_key' => $this->auth->apiKey
			);
			$vars = array_merge($vars, $methodVars);
			
			if ( $call = $this->apiGetCall($vars) ) {
				$i = 0;
				foreach ( $call->topalbums->album as $album ) {
					$topAlbums[$i]['name'] = (string) $album->name;
					$topAlbums[$i]['tagcount'] = (string) $album->tagcount;
					$topAlbums[$i]['url'] = (string) $album->url;
					$topAlbums[$i]['artist']['name'] = (string) $album->artist->name;
					$topAlbums[$i]['artist']['mbid'] = (string) $album->artist->mbid;
					$topAlbums[$i]['artist']['url'] = (string) $album->artist->url;
					$topAlbums[$i]['image']['small'] = (string) $album->image[0];
					$topAlbums[$i]['image']['medium'] = (string) $album->image[1];
					$topAlbums[$i]['image']['large'] = (string) $album->image[2];
					$i++;
				}
				return $topAlbums;
			}
			else {
				return FALSE;
			}
		}
		else {
			// Give a 91 error if incorrect variables are used
			$this->handleError(91, 'You must include tag variable in the call for this method');
			return FALSE;
		}
	}
	
	/**
	 * Get the top artists tagged by this tag, ordered by tag count
	 * @param array $methodVars An array with the following required values: <i>tag</i>
	 * @return array
	 */
	public function getTopArtists($methodVars) {
		// Check for required variables
		if ( !empty($methodVars['tag']) ) {
			$vars = array(
				'method' => 'tag.gettopartists',
				'api_key' => $this->auth->apiKey
			);
			$vars = array_merge($vars, $methodVars);
			
			if ( $call = $this->apiGetCall($vars) ) {
				$i = 0;
				foreach ( $call->topartists->artist as $artist ) {
					$topArtists[$i]['name'] = (string) $artist->name;
					$topArtists[$i]['tagcount'] = (string) $artist->tagcount;
					$topArtists[$i]['url'] = (string) $artist->url;
					$topArtists[$i]['mbid'] = (string) $artist->mbid;
					$topArtists[$i]['streamable'] = (string) $artist->streamable;
					$topArtists[$i]['image']['small'] = (string) $artist->image[0];
					$topArtists[$i]['image']['medium'] = (string) $artist->image[1];
					$topArtists[$i]['image']['large'] = (string) $artist->image[2];
					$i++;
				}
				return $topArtists;
			}
			else {
				return FALSE;
			}
		}
		else {
			// Give a 91 error if incorrect variables are used
			$this->handleError(91, 'You must include tag variable in the call for this method');
			return FALSE;
		}
	}
	
	/**
	 * Fetches the top global tags on Last.fm, sorted by popularity (number of times used)
	 * @return array
	 */
	public function getTopTags($methodVars = Array()) {
		$vars = array(
			'method' => 'tag.gettoptags',
			'api_key' => $this->auth->apiKey
		);
		$vars = array_merge($vars, $methodVars);
		
		if ( $call = $this->apiGetCall($vars) ) {
			$i = 0;
			foreach ( $call->toptags->tag as $tag ) {
				$topTags[$i]['name'] = (string) $tag->name;
				$topTags[$i]['count'] = (string) $tag->count;
				$topTags[$i]['url'] = (string) $tag->url;
				$i++;
			}
			return $topTags;
		}
		else {
			return FALSE;
		}
	}
	
	/**
	 * Get the top tracks tagged by this tag, ordered by tag count
	 * @param array $methodVars An array with the following required values: <i>tag</i>
	 * @return array
	 */
	public function getTopTracks($methodVars) {
		// Check for required variables
		if ( !empty($methodVars['tag']) ) {
			$vars = array(
				'method' => 'tag.gettoptracks',
				'api_key' => $this->auth->apiKey
			);
			$vars = array_merge($vars, $methodVars);
			
			if ( $call = $this->apiGetCall($vars) ) {
				$i = 0;
				foreach ( $call->toptracks->track as $track ) {
					$topTracks[$i]['name'] = (string) $track->name;
					$topTracks[$i]['tagcount'] = (string) $track->tagcount;
					$topTracks[$i]['url'] = (string) $track->url;
					$topTracks[$i]['streamable'] = (string) $track->streamable;
					$topTracks[$i]['fulltrack'] = (string) $track->streamable['fulltrack'];
					$topTracks[$i]['artist']['name'] = (string) $track->artist->name;
					$topTracks[$i]['artist']['mbid'] = (string) $track->artist->mbid;
					$topTracks[$i]['artist']['url'] = (string) $track->artist->url;
					$topTracks[$i]['image']['small'] = (string) $track->image[0];
					$topTracks[$i]['image']['medium'] = (string) $track->image[1];
					$topTracks[$i]['image']['large'] = (string) $track->image[2];
					$i++;
				}
				return $topTracks;
			}
			else {
				return FALSE;
			}
		}
		else {
			// Give a 91 error if incorrect variables are used
			$this->handleError(91, 'You must include tag variable in the call for this method');
			return FALSE;
		}
	}
	
	/**
	 * Get an artist chart for a tag, for a given date range. If no date range is supplied, it will return the most recent artist chart for this tag
	 * @param array $methodVars An array with the following required values: <i>tag</i> and optional values: <i>to</i>, <i>from</i>
	 * @return array
	 */
	public function getWeeklyArtistChart($methodVars) {
		// Check for required variables
		if ( !empty($methodVars['tag']) ) {
			$vars = array(
				'method' => 'tag.getweeklyartistchart',
				'api_key' => $this->auth->apiKey
			);
			$vars = array_merge($vars, $methodVars);
			
			if ( $call = $this->apiGetCall($vars) ) {
				$i = 0;
				foreach ( $call->weeklyartistchart->artist as $artist ) {
					$weeklyArtists[$i]['name'] = (string) $artist->name;
					$weeklyArtists[$i]['rank'] = (string) $artist['rank'];
					$weeklyArtists[$i]['mbid'] = (string) $artist->mbid;
					$weeklyArtists[$i]['weight'] = (string) $artist->weight;
					$weeklyArtists[$i]['url'] = (string) $artist->url;
					$i++;
				}
				
				return $weeklyArtists;
			}
			else {
				return FALSE;
			}
		}
		else {
			// Give a 91 error if incorrect variables are used
			$this->handleError(91, 'You must include artist variable in the call for this method');
			return FALSE;
		}
	}
	
	/**
	 * Get a list of available charts for this tag, expressed as date ranges which can be sent to the chart services
	 * @param array $methodVars An array with the following required values: <i>tag</i>
	 * @return array
	 */
	public function getWeeklyChartList($methodVars) {
		// Check for required variables
		if ( !empty($methodVars['tag']) ) {
			$vars = array(
				'method' => 'tag.getweeklychartlist',
				'api_key' => $this->auth->apiKey
			);
			$vars = array_merge($vars, $methodVars);
			
			if ( $call = $this->apiGetCall($vars) ) {
				$i = 0;
				foreach ( $call->weeklychartlist->chart as $chart ) {
					$weeklyChartList[$i]['from'] = (string) $chart['from'];
					$weeklyChartList[$i]['to'] = (string) $chart['to'];
					$i++;
				}
				
				return $weeklyChartList;
			}
			else {
				return FALSE;
			}
		}
		else {
			// Give a 91 error if incorrect variables are used
			$this->handleError(91, 'You must include artist variable in the call for this method');
			return FALSE;
		}
	}
	
	/**
	 * Search for a tag by name. Returns matches sorted by relevance
	 * @param array $methodVars An array with the following required values: <i>tag</i>
	 * @return array
	 */
	public function search($methodVars) {
		// Check for required variables
		if ( !empty($methodVars['tag']) ) {
			$vars = array(
				'method' => 'tag.search',
				'api_key' => $this->auth->apiKey
			);
			$vars = array_merge($vars, $methodVars);
			
			if ( $call = $this->apiGetCall($vars) ) {
				$opensearch = $call->results->children('http://a9.com/-/spec/opensearch/1.1/');
				if ( $opensearch->totalResults > 0 ) {
					$searchResults['totalResults'] = (string) $opensearch->totalResults;
					$searchResults['startIndex'] = (string) $opensearch->startIndex;
					$searchResults['itemsPerPage'] = (string) $opensearch->itemsPerPage;
					$i = 0;
					foreach ( $call->results->tagmatches->tag as $tag ) {
						$searchResults['results'][$i]['name'] = (string) $tag->name;
						$searchResults['results'][$i]['count'] = (string) $tag->count;
						$searchResults['results'][$i]['url'] = (string) $tag->url;
						$i++;
					}
					return $searchResults;
				}
				else {
					// No tagsare found
					$this->handleError(90, 'No results');
					return FALSE;
				}
			}
			else {
				return FALSE;
			}
		}
		else {
			// Give a 91 error if incorrect variables are used
			$this->handleError(91, 'You must include tag variable in the call for this method');
			return FALSE;
		}
	}
}

?>