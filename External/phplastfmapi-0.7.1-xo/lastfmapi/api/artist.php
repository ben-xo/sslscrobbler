<?php
/**
 * File that stores api calls for artist api calls
 * @package apicalls
 */
/**
 * Allows access to the api requests relating to artists
 * @package apicalls
 */
class lastfmApiArtist extends lastfmApi {
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
	 * Tag an artist using a list of user supplied tags. (Requires full auth)
	 * @param array $methodVars An array with the following required values: <i>artist</i>, <i>tags</i>
	 * @return boolean
	 */
	public function addTags($methodVars) {
		// Only allow full authed calls
		if ( $this->fullAuth == TRUE ) {
			// Check for required variables
			if ( !empty($methodVars['artist']) && !empty($methodVars['tags']) ) {
				// If the tags variables is an array build a CS list
				if ( is_array($methodVars['tags']) ) {
					$tags = '';
					foreach ( $methodVars['tags'] as $tag ) {
						$tags .= $tag.',';
					}
					$tags = substr($tags, 0, -1);
				}
				else {
					$tags = $methodVars['tags'];
				}
				$methodVars['tags'] = $tags;
				
				// Set the call variables
				$vars = array(
					'method' => 'artist.addtags',
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
				$this->handleError(91, 'You must include artist and tags varialbes in the call for this method');
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
	 * Get a list of upcoming events for this artist.
	 * @param array $methodVars An array with the following required values: <i>artist</i>
	 * @return array
	 */
	public function getEvents($methodVars) {
		// Check for required variables
		if ( !empty($methodVars['artist']) ) {
			$vars = array(
				'method' => 'artist.getevents',
				'api_key' => $this->auth->apiKey
			);
			$vars = array_merge($vars, $methodVars);
			
			if ( $call = $this->apiGetCall($vars) ) {
				if ( $call->events['total'] != 0 ) {
					$i = 0;
					foreach ( $call->events->event as $event ) {
						$events[$i]['id'] = (string) $event->id;
						$events[$i]['title'] = (string) $event->title;
						$ii = 0;
						foreach ( $event->artists->artist as $artist ) {
							$events[$i]['artists'][$ii] = (string) $artist;
							$ii++;
						}
						$events[$i]['headliner'] = (string) $event->artists->headliner;
						$events[$i]['venue']['name'] = (string) $event->venue->name;
						$events[$i]['venue']['location']['city'] = (string) $event->venue->location->city;
						$events[$i]['venue']['location']['country'] = (string) $event->venue->location->country;
						$events[$i]['venue']['location']['street'] = (string) $event->venue->location->street;
						$events[$i]['venue']['location']['postcode'] = (string) $event->venue->location->postalcode;
						$geopoint =  $event->venue->location->children('http://www.w3.org/2003/01/geo/wgs84_pos#');
						$events[$i]['venue']['location']['geopoint']['lat'] = (string) $geopoint->point->lat;
						$events[$i]['venue']['location']['geopoint']['long'] = (string) $geopoint->point->long;
						$events[$i]['venue']['location']['timezone'] = (string) $event->venue->location->timezone;
						$events[$i]['venue']['url'] = (string) $call->venue->url;
						$events[$i]['startdate'] = strtotime(trim((string) $event->startDate));
						$events[$i]['description'] = (string) $event->description;
						$events[$i]['image']['small'] = (string) $event->image[0];
						$events[$i]['image']['mendium'] = (string) $event->image[1];
						$events[$i]['image']['large'] = (string) $event->image[2];
						$events[$i]['attendance'] = (string) $event->attendance;
						$events[$i]['reviews'] = (string) $event->reviews;
						$events[$i]['tag'] = (string) $event->tag;
						$events[$i]['url'] = (string) $event->url;
						
						$i++;
					}
					
					return $events;
				}
				else {
					// No events are found
					$this->handleError(90, 'Artist has no events');
					return FALSE;
				}
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
	 * Get Images for this artist in a variety of sizes.
	 * @param array $methodVars An array with the following required values: <i>artist</i> and optional values: <i>page</i>, <i>limit</i>, <i>order</i>
	 * @return array
	 */
	public function getImages($methodVars) {
		// Check for required variables
		if ( !empty($methodVars['artist']) ) {
			$vars = array(
				'method' => 'artist.getimages',
				'api_key' => $this->auth->apiKey
			);
			$vars = array_merge($vars, $methodVars);
			
			if ( $call = $this->apiGetCall($vars) ) {
				$images = array();
				$i = 0;
				$images['artist'] = (string)$call->images['artist'];
				$images['page'] = (string)$call->images['page'];
				$images['totalpages'] = (string)$call->images['totalpages'];
				$images['total'] = (string)$call->images['total'];
				
				foreach ( $call->images->image as $image ) {
					$images['images'][$i]['title'] = (string) $image->title;
					$images['images'][$i]['url'] = (string) $image->url;
					$images['images'][$i]['dateadded'] = (string) $image->dateadded;
					$images['images'][$i]['format'] = (string) $image->format;
					$images['images'][$i]['sizes'] = array();
					$official = isset($image['official']) ? (string) $image['official'] : false;
					$images['images'][$i]['official'] = $official == 'yes';
					foreach( $image->sizes->size as $size ) {
						$images['images'][$i]['sizes'][(string)$size['name']] = array(
							'width' => (string) $size['width'],
							'height' => (string) $size['height'],
							'url' => (string) $size,
						);
					}
					$images['images'][$i]['votes'] = array(
						'thumbsup' => (string) $image->votes->thumbsup,
						'thumbsdown' => (string) $image->votes->thumbsdown,
					);
					$i++;
				}
				return $images;
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
	 * Get the metadata for an artist on Last.fm. Includes biography.
	 * @param array $methodVars An array with the following values: <i>artist</i> or <i>mbid</i>
	 * @return array
	 */
	public function getInfo($methodVars) {
		$vars = array(
			'method' => 'artist.getinfo',
			'api_key' => $this->auth->apiKey
		);
		$vars = array_merge($vars, $methodVars);
		
		if ( $call = $this->apiGetCall($vars) ) {
			$info['name'] = (string) $call->artist->name;
			$info['mbid'] = (string) $call->artist->mbid;
			$info['url'] = (string) $call->artist->url;
			$info['image']['small'] = (string) $call->artist->image;
			$info['image']['medium'] = (string) $call->artist->image[1];
			$info['image']['large'] = (string) $call->artist->image[2];
			$info['streamable'] = (string) $call->artist->streamable;
			$info['stats']['listeners'] = (string) $call->artist->stats->listeners;
			$info['stats']['playcount'] = (string) $call->artist->stats->plays;
			$i = 0;
			foreach ( $call->artist->similar->artist as $artist ) {
				$info['similar'][$i]['name'] = (string) $artist->name;
				$info['similar'][$i]['url'] = (string) $artist->url;
				$info['similar'][$i]['image']['small'] = (string) $artist->image;
				$info['similar'][$i]['image']['medium'] = (string) $artist->image[1];
				$info['similar'][$i]['image']['large'] = (string) $artist->image[2];
				$i++;
			}
			if ( count($call->artist->tags->tag) > 0 ) {
				$i = 0;
				foreach ( $call->artist->tags->tag as $tag ) {
					$info['tags'][$i]['name'] = (string) $tag->name;
					$info['tags'][$i]['url'] = (string) $tag->url;
					$i++;
				}
			}
			else {
				$info['tags'] = FALSE;
			}
			$info['bio']['published'] = (string) $call->artist->bio->published;
			$info['bio']['summary'] = (string) $call->artist->bio->summary;
			$info['bio']['content'] = (string) $call->artist->bio->content;
			
			return $info;
		}
		else {
			return FALSE;
		}
	}
	
	/**
	 * Get shouts for this artist.
	 * @param array $methodVars An array with the following required values: <i>artist</i>
	 * @return array
	 */
	public function getShouts($methodVars) {
		if ( !empty($methodVars['artist']) ) {
			$vars = array(
				'method' => 'artist.getshouts',
				'api_key' => $this->auth->apiKey
			);
			$vars = array_merge($vars, $methodVars);
			
			if ( $call = $this->apiGetCall($vars) ) {
				$shouts['artist'] = (string)$call->shouts['artist'];
				$shouts['total'] = (string)$call->shouts['total'];
				$i = 0;
				foreach ( $call->shouts->shout as $shout ) {
					$shouts['shouts'][$i]['body'] = (string)$shout->body;
					$shouts['shouts'][$i]['author'] = (string)$shout->author;
					$shouts['shouts'][$i]['date'] = strtotime((string)$shout->date);
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
			$this->handleError(91, 'You must include artist variable in the call for this method');
			return FALSE;
		}
	}
	
	/**
	 * Get all the artists similar to this artist
	 * @param array $methodVars An array with the following required value: <i>artist</i> and optional value: <i>limit</i>, <i></i>
	 * @return array
	 */
	public function getSimilar($methodVars) {
		// Check for required variables
		if ( !empty($methodVars['artist']) ) {
			$vars = array(
				'method' => 'artist.getsimilar',
				'api_key' => $this->auth->apiKey
			);
			$vars = array_merge($vars, $methodVars);
			
			if ( $call = $this->apiGetCall($vars) ) {
				$similar = '';
				$i = 0;
				foreach ( $call->similarartists->artist as $artist ) {
					$similar[$i]['name'] = (string) $artist->name;
					$similar[$i]['mbid'] = (string) $artist->mbid;
					$similar[$i]['match'] = (string) $artist->match;
					$similar[$i]['url'] = (string) $artist->url;
					$similar[$i]['image'] = (string) $artist->image;
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
			$this->handleError(91, 'You must include artist variable in the call for this method');
			return FALSE;
		}
	}
	
	/**
	 * Get the tags applied by an individual user to an artist on Last.fm. (Requires full auth)
	 * @param array $methodVars An array with the following required values: <i>artist</i>
	 * @return array
	 */
	public function getTags($methodVars) {
		// Only allow full authed calls
		if ( $this->fullAuth == TRUE ) {
			// Check for required variables
			if ( !empty($methodVars['artist']) ) {
				$vars = array(
					'method' => 'artist.gettags',
					'api_key' => $this->auth->apiKey,
					'sk' => $this->auth->sessionKey
				);
				$vars = array_merge($vars, $methodVars);
				$sig = $this->apiSig($this->auth->secret, $vars);
				$vars['api_sig'] = $sig;
				
				if ( $call = $this->apiGetCall($vars) ) {
					if ( count($call->tags->tag) > 0 ) {
						$i = 0;
						foreach ( $call->tags[0]->tag as $tag ) {
							$tags[$i]['name'] = (string) $tag->name;
							$tags[$i]['url'] = (string) $tag->url;
							$i++;
						}
						
						return $tags;
					}
					else {
						// No tagsare found
						$this->handleError(90, 'Artist has no tags from this user');
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
		else {
			// Give a 92 error if not fully authed
			$this->handleError(92, 'Method requires full auth. Call auth.getSession using lastfmApiAuth class');
			return FALSE;
		}
	}
	
	/**
	 * Get the top albums for an artist on Last.fm, ordered by popularity
	 * @param array $methodVars An array with the following required values: <i>artist</i>
	 * @return array
	 */
	public function getTopAlbums($methodVars) {
		// Check for required variables
		if ( !empty($methodVars['artist']) ) {
			$vars = array(
				'method' => 'artist.gettopalbums',
				'api_key' => $this->auth->apiKey
			);
			$vars = array_merge($vars, $methodVars);
			
			if ( $call = $this->apiGetCall($vars) ) {
				if ( count($call->topalbums->album) > 0 ) {
					$i = 0;
					foreach ( $call->topalbums->album as $album ) {
						$topAlbums[$i]['rank'] = (string) $album['rank'];
						$topAlbums[$i]['name'] = (string) $album->name;
						$topAlbums[$i]['mbid'] = (string) $album->mbid;
						$topAlbums[$i]['playcount'] = (string) $album->playcount;
						$topAlbums[$i]['url'] = (string) $album->url;
						$topAlbums[$i]['image']['small'] = (string) $album->image[0];
						$topAlbums[$i]['image']['medium'] = (string) $album->image[1];
						$topAlbums[$i]['image']['large'] = (string) $album->image[2];
						$i++;
					}
					
					return $topAlbums;
				}
				else {
					// No tagsare found
					$this->handleError(90, 'Artist has no top albums');
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
	
	/**
	 * Get the top fans for an artist on Last.fm, based on listening data
	 * @param array $methodVars An array with the following required values: <i>artist</i>
	 * @return array
	 */
	public function getTopFans($methodVars) {
		// Check for required variables
		if ( !empty($methodVars['artist']) ) {
			$vars = array(
				'method' => 'artist.gettopfans',
				'api_key' => $this->auth->apiKey
			);
			$vars = array_merge($vars, $methodVars);
			
			if ( $call = $this->apiGetCall($vars) ) {
				if ( count($call->topfans->user) > 0 ) {
					$i = 0;
					foreach ( $call->topfans->user as $user ) {
						$topFans[$i]['name'] = (string) $user->name;
						$topFans[$i]['url'] = (string) $user->url;
						$topFans[$i]['image']['small'] = (string) $user->image[0];
						$topFans[$i]['image']['medium'] = (string) $user->image[1];
						$topFans[$i]['image']['large'] = (string) $user->image[2];
						$topFans[$i]['weight'] = (string) $user->weight;
						$i++;
					}
					
					return $topFans;
				}
				else {
					// No tagsare found
					$this->handleError(90, 'Artist has no top users');
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
	
	/**
	 * Get the top tags for an artist on Last.fm, ordered by popularity
	 * @param array $methodVars An array with the following required values: <i>artist</i>
	 * @return array
	 */
	public function getTopTags($methodVars) {
		// Check for required variables
		if ( !empty($methodVars['artist']) ) {
			$vars = array(
				'method' => 'artist.gettoptags',
				'api_key' => $this->auth->apiKey
			);
			$vars = array_merge($vars, $methodVars);
			
			if ( $call = $this->apiGetCall($vars) ) {
				if ( count($call->toptags->tag) > 0 ) {
					$i = 0;
					foreach ( $call->toptags->tag as $tag ) {
						$topTags[$i]['name'] = (string) $tag->name;
						$topTags[$i]['url'] = (string) $tag->url;
						$i++;
					}
					
					return $topTags;
				}
				else {
					// No tagsare found
					$this->handleError(90, 'Artist has no top tags');
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
	
	/**
	 * Get the top tracks by an artist on Last.fm, ordered by popularity
	 * @param array $methodVars An array with the following required values: <i>artist</i>
	 * @return array
	 */
	public function getTopTracks($methodVars) {
		// Check for required variables
		if ( !empty($methodVars['artist']) ) {
			$vars = array(
				'method' => 'artist.gettoptracks',
				'api_key' => $this->auth->apiKey
			);
			$vars = array_merge($vars, $methodVars);
			
			if ( $call = $this->apiGetCall($vars) ) {
				if ( count($call->toptracks->track) > 0 ) {
					$i = 0;
					foreach ( $call->toptracks->track as $tracks ) {
						$topTracks[$i]['rank'] = (string) $tracks['rank'];
						$topTracks[$i]['name'] = (string) $tracks->name;
						$topTracks[$i]['playcount'] = (string) $tracks->playcount;
						$topTracks[$i]['streamable'] = (string) $tracks->streamable;
						$topTracks[$i]['url'] = (string) $tracks->url;
						$topTracks[$i]['image']['small'] = (string) $tracks->image[0];
						$topTracks[$i]['image']['medium'] = (string) $tracks->image[1];
						$topTracks[$i]['image']['large'] = (string) $tracks->image[2];
						$i++;
					}
					
					return $topTracks;
				}
				else {
					// No tagsare found
					$this->handleError(90, 'Artist has no top tracks');
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
	
	/**
	 * Remove a user's tag from an artist. (Requires full auth)
	 * @param array $methodVars An array with the following required values: <i>artist</i>, <i>tag</i>
	 * @return boolean
	 */
	public function removeTag($methodVars) {
		// Only allow full authed calls
		if ( $this->fullAuth == TRUE ) {
			// Check for required variables
			if ( !empty($methodVars['artist']) && !empty($methodVars['tag']) ) {
				$vars = array(
					'method' => 'artist.removetag',
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
				$this->handleError(91, 'You must include artist and tag varialbes in the call for this method');
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
	 * Search for an artist by name. Returns artist matches sorted by relevance
	 * @param array $methodVars An array with the following required value: <i>artist</i> and optional values: <i>limite</i>, <i>page</i>
	 * @return array
	 */
	public function search($methodVars) {
		// Check for required variables
		if ( !empty($methodVars['artist']) ) {
			$vars = array(
				'method' => 'artist.search',
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
					foreach ( $call->results->artistmatches->artist as $artist ) {
						$searchResults['results'][$i]['name'] = (string) $artist->name;
						$searchResults['results'][$i]['mbid'] = (string) $artist->mbid;
						$searchResults['results'][$i]['url'] = (string) $artist->url;
						$searchResults['results'][$i]['streamable'] = (string) $artist->streamable;
						$searchResults['results'][$i]['image']['small'] = (string) $artist->image_small;
						$searchResults['results'][$i]['image']['large'] = (string) $artist->image;
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
	
	/**
	 * Share an artist with Last.fm users or other friends. (Requires full auth)
	 * @param array $methodVars An array with the following required values: <i>artist</i>, <i>recipient</i> and optional values: <i>message</i>
	 * @return boolean
	 */
	public function share($methodVars) {
		// Only allow full authed calls
		if ( $this->fullAuth == TRUE ) {
			// Check for required variables
			if ( !empty($methodVars['artist']) && !empty($methodVars['recipient']) ) {
				$vars = array(
					'method' => 'artist.share',
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
				$this->handleError(91, 'You must include artist and recipient varialbes in the call for this method');
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
	 * Shout in this artist's shoutbox
	 * @param array $methodVars An array with the following required values: <i>artist</i>, <i>message</i>
	 * @return boolean
	 */
	public function shout($methodVars) {
		// Only allow full authed calls
		if ( $this->fullAuth == TRUE ) {
			// Check for required variables
			if ( !empty($methodVars['artist']) && !empty($methodVars['message']) ) {
				$vars = array(
					'method' => 'artist.shout',
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
				$this->handleError(91, 'You must include artist and message variables in the call for this method');
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