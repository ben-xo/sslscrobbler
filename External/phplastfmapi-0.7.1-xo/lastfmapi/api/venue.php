<?php
/**
 * File that stores api calls for venue api calls
 * @package apicalls
 */
/**
 * Allows access to the api requests relating to venues
 * @package apicalls
 */
class lastfmApiVenue extends lastfmApi {
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
	 * Get a list of upcoming events at this venue
	 * @param array $methodVars An array with the following required value: <i>venue</i>
	 * @return array
	 */
	public function getEvents($methodVars) {
		if ( !empty($methodVars['venue']) ) {
			$vars = array(
				'method' => 'venue.getEvents',
				'api_key' => $this->auth->apiKey
			);
			$vars = array_merge($vars, $methodVars);
			
			if ( $call = $this->apiGetCall($vars) ) {
				if ( count($call->events->event) > 0 ) {
					$events = array();
					$events['venue_name'] = (string) $call->events['venue'];
					$i = 0;
					foreach ( $call->events->event as $event ) {
						$events['event'][$i]['id'] = (string) $event->id;
						$events['event'][$i]['status'] = (string) $event['status'];
						$events['event'][$i]['title'] = (string) $event->title;
						if ( count($event->artists->artist) > 0 ) {
							foreach ( $event->artists->artist as $artist ) {
								$events['event'][$i]['artists'][] = (string) $artist;
							}
						}
						$events['event'][$i]['headliner'] = (string) $event->artists->headliner;
						$events['event'][$i]['venue']['id'] = (string) $event->venue->id;
						$events['event'][$i]['venue']['name'] = (string) $event->venue->name;
						$events['event'][$i]['venue']['location']['city'] = (string) $event->venue->location->city;
						$events['event'][$i]['venue']['location']['country'] = (string) $event->venue->location->country;
						$events['event'][$i]['venue']['location']['street'] = (string) $event->venue->location->street;
						$events['event'][$i]['venue']['location']['postalcode'] = (string) $event->venue->location->postalcode;
						$geoPoints = $event->venue->location->children('http://www.w3.org/2003/01/geo/wgs84_pos#');
						$events['event'][$i]['venue']['location']['geopoint']['lat'] = (string) $geoPoints->point->lat;
						$events['event'][$i]['venue']['location']['geopoint']['long'] = (string) $geoPoints->point->long;
						$events['event'][$i]['venue']['url'] = (string) $event->venue->url;
						$events['event'][$i]['startDate'] = (string) strtotime($event->startDate);
						$events['event'][$i]['description'] = (string) $event->description;
						$events['event'][$i]['image']['small'] = (string) $event->image[0];
						$events['event'][$i]['image']['medium'] = (string) $event->image[1];
						$events['event'][$i]['image']['large'] = (string) $event->image[2];
						$events['event'][$i]['attendance'] = (string) $event->attendance;
						$events['event'][$i]['reviews'] = (string) $event->reviews;
						$events['event'][$i]['tag'] = (string) $event->tag;
						$events['event'][$i]['url'] = (string) $event->url;
						$i++;
					}
					
					return $events;
				}
				else {
					$this->handleError(90, 'There is no events for this venue');
					return FALSE;
				}
			}
			else {
				return FALSE;
			}
		}
		else {
			// Give a 91 error if incorrect variables are used
			$this->handleError(91, 'You must include the venue variable in the call for this method');
			return FALSE;
		}
	}
	
	/**
	 * Get a paginated list of all the events held at this venue in the past
	 * @param array $methodVars An array with the following required value: <i>venue</i> and optional values: <i>page</i>, <i>limit</i>
	 * @return array
	 */
	public function getPastEvents($methodVars) {
		if ( !empty($methodVars['venue']) ) {
			$vars = array(
				'method' => 'venue.getPastEvents',
				'api_key' => $this->auth->apiKey
			);
			$vars = array_merge($vars, $methodVars);
			
			if ( $call = $this->apiGetCall($vars) ) {
				if ( count($call->events->event) > 0 ) {
					$events = array();
					$events['venue_name'] = (string) $call->events['venue'];
					$events['page'] = (string) $call->events['page'];
					$events['perPage'] = (string) $call->events['perPage'];
					$events['total'] = (string) $call->events['total'];
					$events['totalPages'] = (string) $call->events['totalPages'];
					$i = 0;
					foreach ( $call->events->event as $event ) {
						$events['event'][$i]['id'] = (string) $event->id;
						$events['event'][$i]['status'] = (string) $event['status'];
						$events['event'][$i]['title'] = (string) $event->title;
						if ( count($event->artists->artist) > 0 ) {
							foreach ( $event->artists->artist as $artist ) {
								$events['event'][$i]['artists'][] = (string) $artist;
							}
						}
						$events['event'][$i]['headliner'] = (string) $event->artists->headliner;
						$events['event'][$i]['venue']['id'] = (string) $event->venue->id;
						$events['event'][$i]['venue']['name'] = (string) $event->venue->name;
						$events['event'][$i]['venue']['location']['city'] = (string) $event->venue->location->city;
						$events['event'][$i]['venue']['location']['country'] = (string) $event->venue->location->country;
						$events['event'][$i]['venue']['location']['street'] = (string) $event->venue->location->street;
						$events['event'][$i]['venue']['location']['postalcode'] = (string) $event->venue->location->postalcode;
						$geoPoints = $event->venue->location->children('http://www.w3.org/2003/01/geo/wgs84_pos#');
						$events['event'][$i]['venue']['location']['geopoint']['lat'] = (string) $geoPoints->point->lat;
						$events['event'][$i]['venue']['location']['geopoint']['long'] = (string) $geoPoints->point->long;
						$events['event'][$i]['venue']['url'] = (string) $event->venue->url;
						$events['event'][$i]['startDate'] = (string) strtotime($event->startDate);
						$events['event'][$i]['description'] = (string) $event->description;
						$events['event'][$i]['image']['small'] = (string) $event->image[0];
						$events['event'][$i]['image']['medium'] = (string) $event->image[1];
						$events['event'][$i]['image']['large'] = (string) $event->image[2];
						$events['event'][$i]['attendance'] = (string) $event->attendance;
						$events['event'][$i]['reviews'] = (string) $event->reviews;
						$events['event'][$i]['tag'] = (string) $event->tag;
						$events['event'][$i]['url'] = (string) $event->url;
						$i++;
					}
					
					return $events;
				}
				else {
					$this->handleError(90, 'There is no past events for this venue');
					return FALSE;
				}
			}
			else {
				return FALSE;
			}
		}
		else {
			// Give a 91 error if incorrect variables are used
			$this->handleError(91, 'You must include the venue variable in the call for this method');
			return FALSE;
		}
	}
	
	/**
	 * Search for a venue by venue name
	 * @param array $methodVars An array with the following required value: <i>venue</i> and optional values: <i>limit</i>, <i>page</i>, <i>county</i>
	 * @return array
	 */
	public function search($methodVars) {
		// Check for required variables
		if ( !empty($methodVars['venue']) ) {
			$vars = array(
				'method' => 'venue.search',
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
					foreach ( $call->results->venuematches->venue as $venue ) {
						$searchResults['results'][$i]['id'] = (string) $venue->id;
						$searchResults['results'][$i]['name'] = (string) $venue->name;
						$searchResults['results'][$i]['location']['city'] = (string) $venue->location->city;
						$searchResults['results'][$i]['location']['country'] = (string) $venue->location->country;
						$searchResults['results'][$i]['location']['street'] = (string) $venue->location->street;
						$searchResults['results'][$i]['location']['postalcode'] = (string) $venue->location->postalcode;
						$geoPoints = $venue->location->children('http://www.w3.org/2003/01/geo/wgs84_pos#');
						$searchResults['results'][$i]['location']['geopoint']['lat'] = (string) $geoPoints->point->lat;
						$searchResults['results'][$i]['location']['geopoint']['long'] = (string) $geoPoints->point->long;
						$searchResults['results'][$i]['url'] = (string) $venue->url;
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
			$this->handleError(91, 'You must include artist varialbe in the call for this method');
			return FALSE;
		}
	}
}

?>