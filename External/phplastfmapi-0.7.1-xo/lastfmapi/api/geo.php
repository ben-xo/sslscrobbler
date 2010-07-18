<?php
/**
 * File that stores api calls for geographical api calls
 * @package apicalls
 */
/**
 * Allows access to the api requests relating to geographical date
 * @package apicalls
 */
class lastfmApiGeo extends lastfmApi {
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
	 * Get all events in a specific location by country or city name.
	 * @param array $methodVars An array with the following required values: <i>location</i> and optional values: <i>distance</i>, <i>page</i>
	 * @return array
	 */
	public function getEvents($methodVars) {
		// Check for required variables
		if ( !empty($methodVars['location']) ) {
			$vars = array(
				'method' => 'geo.getevents',
				'api_key' => $this->auth->apiKey
			);
			$vars = array_merge($vars, $methodVars);
			
			if ( $call = $this->apiGetCall($vars) ) {
				$events['location'] = (string) $call->events['location'];
				$events['currentPage'] = (string) $call->events['page'];
				$events['totalPages'] = (string) $call->events['totalpages'];
				$events['totalResults'] = (string) $call->events['total'];
				$i = 0;
				foreach ( $call->events->event as $event ) {
					$events['events'][$i]['id'] = (string) $event->id;
					$events['events'][$i]['title'] = (string) $event->title;
					$ii = 0;
					foreach ( $event->artists->artist as $artist ) {
						$events['events'][$i]['artists'][$ii] = (string) $artist;
						$ii++;
					}
					$events['events'][$i]['headliner'] = (string) $event->artists->headliner;
					$events['events'][$i]['venue']['name'] = (string) $event->venue->name;
					$events['events'][$i]['venue']['location']['city'] = (string) $event->venue->location->city;
					$events['events'][$i]['venue']['location']['country'] = (string) $event->venue->location->country;
					$events['events'][$i]['venue']['location']['street'] = (string) $event->venue->location->street;
					$events['events'][$i]['venue']['location']['postalcode'] = (string) $event->venue->location->postalcode;
					$geoTags = $event->venue->location->children('http://www.w3.org/2003/01/geo/wgs84_pos#');
					$events['events'][$i]['venue']['location']['point']['lat'] = (string) $geoTags->point->lat;
					$events['events'][$i]['venue']['location']['point']['long'] = (string) $geoTags->point->long;
					$events['events'][$i]['venue']['location']['timezone'] = (string) $event->venue->location->timezone;
					$events['events'][$i]['venue']['url'] = (string) $event->venue->url;
					$events['events'][$i]['startDate'] = strtotime(trim((string) $event->startDate));
					$events['events'][$i]['startTime'] = (string) $event->startTime;
					$events['events'][$i]['description'] = (string) $event->description;
					$events['events'][$i]['attendance'] = (string) $event->attendance;
					$events['events'][$i]['reviews'] = (string) $event->reviews;
					$events['events'][$i]['tag'] = (string) $event->tag;
					$events['events'][$i]['url'] = (string) $event->url;
					$i++;
				}
				return $events;
			}
			else {
				return FALSE;
			}
		}
		else {
			// Give a 91 error if incorrect variables are used
			$this->handleError(91, 'You must include a location varialbe in the call for this method');
			return FALSE;
		}
	}
	
	/**
	 * Get the most popular artists on Last.fm by country
	 * @param array $methodVars An array with the following required values: <i>country</i>
	 * @return array
	 */
	public function getTopArtists($methodVars) {
		// Check for required variables
		if ( !empty($methodVars['country']) ) {
			$vars = array(
				'method' => 'geo.gettopartists',
				'api_key' => $this->auth->apiKey
			);
			$vars = array_merge($vars, $methodVars);
			
			if ( $call = $this->apiGetCall($vars) ) {
				$i = 0;
				foreach ( $call->topartists->artist as $artist ) {
					$topArtists[$i]['name'] = (string) $artist->name;
					$topArtists[$i]['rank'] = (string) $artist['rank'];
					$topArtists[$i]['playcount'] = (string) $artist->playcount;
					$topArtists[$i]['mbid'] = (string) $artist->mbid;
					$topArtists[$i]['url'] = (string) $artist->url;
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
			$this->handleError(91, 'You must include a country varialbe in the call for this method');
			return FALSE;
		}
	}
	
	/**
	 * Get the most popular tracks on Last.fm last week by country
	 * @param array $methodVars An array with the following required values: <i>country</i>
	 * @return array
	 */
	public function getTopTracks($methodVars) {
		// Check for required variables
		if ( !empty($methodVars['country']) ) {
			$vars = array(
				'method' => 'geo.gettoptracks',
				'api_key' => $this->auth->apiKey
			);
			$vars = array_merge($vars, $methodVars);
			
			if ( $call = $this->apiGetCall($vars) ) {
				$i = 0;
				foreach ( $call->toptracks->track as $track ) {
					$topTracks[$i]['name'] = (string) $track->name;
					$topTracks[$i]['rank'] = (string) $track['rank'];
					$topTracks[$i]['playcount'] = (string) $track->playcount;
					$topTracks[$i]['mbid'] = (string) $track->mbid;
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
			$this->handleError(91, 'You must include a country varialbe in the call for this method');
			return FALSE;
		}
	}
}

?>