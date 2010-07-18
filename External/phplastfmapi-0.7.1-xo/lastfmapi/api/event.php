<?php
/**
 * File that stores api calls for event api calls
 * @package apicalls
 */
/**
 * Allows access to the api requests relating to events
 * @package apicalls
 */
class lastfmApiEvent extends lastfmApi {
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
	 * Set a user's attendance status for an event. (Requires full auth)
	 * @param array $methodVars An array with the following required values: <i>eventId</i>, <i>status (0=Attending, 1=Maybe attending, 2=Not attending)</i> 
	 * @return boolean
	 */
	public function attend($methodVars) {
		// Only allow full authed calls
		if ( $this->fullAuth == TRUE ) {
			// Check for required variables
			if ( !empty($methodVars['event']) && !empty($methodVars['status']) ) {
				$vars = array(
					'method' => 'event.attend',
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
	 * Get a list of attendees for an event
	 * @param array $methodVars An array with the following required values: <i>eventId</i>
	 * @return array
	 */
	public function getAttendees($methodVars) {
		// Check for required variables
		if ( !empty($methodVars['event']) ) {
			$vars = array(
				'method' => 'event.getattendees',
				'api_key' => $this->auth->apiKey
			);
			$vars = array_merge($vars, $methodVars);
			
			if ( $call = $this->apiGetCall($vars) ) {
				$attendees['id'] = (string) $call->attendees['event'];
				$attendees['total'] = (string) $call->attendees['total'];
				$i = 0;
				foreach ( $call->attendees->user as $user ) {
					$attendees['attendees'][$i]['name'] = (string) $user->name;
					$attendees['attendees'][$i]['realname'] = (string) $user->realname;
					$attendees['attendees'][$i]['images']['small'] = (string) $user->image[0];
					$attendees['attendees'][$i]['images']['medium'] = (string) $user->image[1];
					$attendees['attendees'][$i]['images']['large'] = (string) $user->image[2];
					$attendees['attendees'][$i]['url'] = (string) $user->url;
					$i++;
				}
				
				return $attendees;
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
	
	/**
	 * Get the metadata for an event on Last.fm. Includes attendance and lineup information
	 * @param array $methodVars An array with the following required values: <i>eventId</i>
	 * @return array
	 */
	public function getInfo($methodVars) {
		// Check for required variables
		if ( !empty($methodVars['event']) ) {
			$vars = array(
				'method' => 'event.getinfo',
				'api_key' => $this->auth->apiKey
			);
			$vars = array_merge($vars, $methodVars);
			
			if ( $call = $this->apiGetCall($vars) ) {
				$info['id'] = (string) $call->event->id;
				$info['title'] = (string) $call->event->title;
				$ii = 0;
				foreach ( $call->event->artists->artist as $artist ) {
					$info['artists'][$ii] = (string) $artist;
					$ii++;
				}
				$info['headliner'] = (string) $call->event->artists->headliner;
				$info['venue']['name'] = (string) $call->event->venue->name;
				$info['venue']['location']['city'] = (string) $call->event->venue->location->city;
				$info['venue']['location']['country'] = (string) $call->event->venue->location->country;
				$info['venue']['location']['street'] = (string) $call->event->venue->location->street;
				$info['venue']['location']['postcode'] = (string) $call->event->venue->location->postalcode;
				$geopoint =  $call->event->venue->location->children('http://www.w3.org/2003/01/geo/wgs84_pos#');
				$info['venue']['location']['geopoint']['lat'] = (string) $geopoint->point->lat;
				$info['venue']['location']['geopoint']['long'] = (string) $geopoint->point->long;
				$info['venue']['location']['timezone'] = (string) $call->event->venue->location->timezone;
				$info['venue']['url'] = (string) $call->venue->url;
				$info['startdate'] = strtotime(trim((string) $call->event->startDate));
				$info['description'] = (string) $call->event->description;
				$info['image']['small'] = (string) $call->event->image[0];
				$info['image']['medium'] = (string) $call->event->image[1];
				$info['image']['large'] = (string) $call->event->image[2];
				$info['attendance'] = (string) $call->event->attendance;
				$info['reviews'] = (string) $call->event->reviews;
				$info['tag'] = (string) $call->event->tag;
				$info['url'] = (string) $call->event->url;
				
				return $info;
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
	
	/**
	 * Get shouts for this event
	 * @param array $methodVars An array with the following required values: <i>eventId</i>
	 * @return array
	 */
	public function getShouts($methodVars) {
		// Check for required variables
		if ( !empty($methodVars['event']) ) {
			$vars = array(
				'method' => 'event.getshouts',
				'api_key' => $this->auth->apiKey
			);
			$vars = array_merge($vars, $methodVars);
			
			if ( $call = $this->apiGetCall($vars) ) {
				$shouts['id'] = (string) $call->shouts['event'];
				$shouts['total'] = (string) $call->shouts['total'];
				$i = 0;
				foreach ( $call->shouts->shout as $shout ) {
					$shouts['shouts'][$i]['body'] = (string) $shout->body;
					$shouts['shouts'][$i]['author'] = (string) $shout->author;
					$shouts['shouts'][$i]['date'] = strtotime((string) $shout->date);
					$i++;
				}
				
				return $shouts;
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
	
	/**
	 * Share an event with one or more Last.fm users or other friends. (Requires full auth)
	 * @param array $methodVars An array with the following required values: <i>eventId</i>, <i>recipient</i> and option value: <i>message</i>
	 * @return boolean
	 */
	public function share($methodVars) {
		// Only allow full authed calls
		if ( $this->fullAuth == TRUE ) {
			// Check for required variables
			if ( !empty($methodVars['event']) && !empty($methodVars['recipient']) ) {
				$vars = array(
					'method' => 'event.share',
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
				$this->handleError(91, 'You must include eventId and recipient variables in the call for this method');
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
	 * Shout in this event's shoutbox (Requires full auth)
	 * @param array $methodVars An array with the following required values: <i>eventId</i>, <i>message</i>
	 * @return boolean
	 */
	public function shout($methodVars) {
		// Only allow full authed calls
		if ( $this->fullAuth == TRUE ) {
			// Check for required variables
			if ( !empty($methodVars['event']) && !empty($methodVars['message']) ) {
				$vars = array(
					'method' => 'event.shout',
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
				$this->handleError(91, 'You must include eventId and message variables in the call for this method');
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