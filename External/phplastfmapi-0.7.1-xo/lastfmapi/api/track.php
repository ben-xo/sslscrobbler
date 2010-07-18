<?php
/**
 * File that stores api calls for track api calls
 * @package apicalls
 */
/**
 * Allows access to the api requests relating to tracks
 * @package apicalls
 */
class lastfmApiTrack extends lastfmApi {
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
	 * Tag an album using a list of user supplied tags (Requires full auth)
	 * @param array $methodVars An array with the following required values: <i>artist</i>, <i>track</i>, <i>tags</i>
	 * @return boolean
	 */
	public function addTags($methodVars) {
		// Only allow full authed calls
		if ( $this->fullAuth == TRUE ) {
			// Check for required variables
			if ( !empty($methodVars['artist']) && !empty($methodVars['track']) && !empty($methodVars['tags']) ) {
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
				
				$vars = array(
					'method' => 'track.addtags',
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
				$this->handleError(91, 'You must include artist, track and tags varialbes in the call for this method');
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
	 * Ban a track for a given user profile. This needs to be supplemented with a scrobbling submission containing the 'ban' rating (see the audioscrobbler API) (Requires full auth)
	 * @param array $methodVars An array with the following required values: <i>artist</i>, <i>track</i>
	 * @return boolean
	 */
	public function ban($methodVars) {
		// Only allow full authed calls
		if ( $this->fullAuth == TRUE ) {
			// Check for required variables
			if ( !empty($methodVars['artist']) && !empty($methodVars['track']) ) {
				$vars = array(
					'method' => 'track.ban',
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
	 * Get the metadata for a track on Last.fm using the artist/track name or a musicbrainz id
	 * @param array $methodVars An array with the following required values: <i>artist or mbid</i>, <i>track</i>
	 * @return array
	 */
	public function getInfo($methodVars) {
		$vars = array(
			'method' => 'track.getinfo',
			'api_key' => $this->auth->apiKey
		);
		$vars = array_merge($vars, $methodVars);
		
		if ( $call = $this->apiGetCall($vars) ) {
			$info['id'] = (string) $call->track->id;
			$info['name'] = (string) $call->track->name;
			$info['mbid'] = (string) $call->track->mbid;
			$info['url'] = (string) $call->track->url;
			$info['duration'] = (string) $call->track->duration;
			$info['streamable'] = (string) $call->track->streamable;
			$info['fulltrack'] = (string) $call->track->streamable['fulltrack'];
			$info['listeners'] = (string) $call->track->listeners;
			$info['playcount'] = (string) $call->track->playcount;
			$info['artist']['name'] = (string) $call->track->artist->name;
			$info['artist']['mbid'] = (string) $call->track->artist->mbid;
			$info['artist']['url'] = (string) $call->track->artist->url;
			$info['album']['position'] = (string) $call->track->album['position'];
			$info['album']['artist'] = (string) $call->track->album->artist;
			$info['album']['title'] = (string) $call->track->album->title;
			$info['album']['mbid'] = (string) $call->track->album->mbid;
			$info['album']['url'] = (string) $call->track->album->url;
			$info['album']['image']['small'] = (string) $call->track->album->image[0];
			$info['album']['image']['medium'] = (string) $call->track->album->image[1];
			$info['album']['image']['large'] = (string) $call->track->album->image[2];
			$i = 0;
			foreach ( $call->track->toptags->tag as $tag ) {
				$info['toptags'][$i]['name'] = (string) $tag->name;
				$info['toptags'][$i]['url'] = (string) $tag->url;
				$i++;
			}
			$info['wiki']['published'] = (string) $call->track->wiki->published;
			$info['wiki']['summary'] = (string) $call->track->wiki->summary;
			$info['wiki']['content'] = (string) $call->track->wiki->content;
			
			return $info;
		}
		else {
			return FALSE;
		}
	}
	
	/**
	 * Get the similar tracks for this track on Last.fm, based on listening data
	 * @param array $methodVars An array with the following required values: <i>artist</i>, <i>track</i>
	 * @return array
	 */
	public function getSimilar($methodVars) {
		// Check for required variables
		if ( !empty($methodVars['artist']) && !empty($methodVars['track']) ) {
			$vars = array(
				'method' => 'track.getsimilar',
				'api_key' => $this->auth->apiKey
			);
			$vars = array_merge($vars, $methodVars);
			
			if ( $call = $this->apiGetCall($vars) ) {
				if ( count($call->similartracks->track) > 0 ) {
					$i = 0;
					foreach ( $call->similartracks->track as $track ) {
						$similar[$i]['name'] = (string) $track->name;
						$similar[$i]['match'] = (string) $track->match;
						$similar[$i]['mbid'] = (string) $track->mbid;
						$similar[$i]['url'] = (string) $track->url;
						$similar[$i]['streamable'] = (string) $track->streamable;
						$similar[$i]['fulltrack'] = (string) $track->streamable['fulltrack'];
						$similar[$i]['artist']['name'] = (string) $track->artist->name;
						$similar[$i]['artist']['mbid'] = (string) $track->artist->mbid;
						$similar[$i]['artist']['url'] = (string) $track->artist->url;
						$similar[$i]['images']['small'] = (string) $track->image[0];
						$similar[$i]['images']['medium'] = (string) $track->image[1];
						$similar[$i]['images']['large'] = (string) $track->image[2];
						$i++;
					}
					return $similar;
				}
				else {
					$this->handleError(90, 'This track has no similar tracks');
					return FALSE;
				}
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
	
	/**
	 * Get the tags applied by an individual user to a track on Last.fm (Requires full auth)
	 * @param array $methodVars An array with the following required values: <i>artist</i>, <i>track</i>
	 * @return array
	 */
	public function getTags($methodVars) {
		// Only allow full authed calls
		if ( $this->fullAuth == TRUE ) {
			// Check for required variables
			if ( !empty($methodVars['artist']) && !empty($methodVars['track']) ) {
				$vars = array(
					'method' => 'track.gettags',
					'api_key' => $this->auth->apiKey,
					'sk' => $this->auth->sessionKey
				);
				$vars = array_merge($vars, $methodVars);
				$sig = $this->apiSig($this->auth->secret, $vars);
				$vars['api_sig'] = $sig;
				
				if ( $call = $this->apiGetCall($vars) ) {
					if ( count($call->tags->tag) > 0 ) {
						$tags['artist'] = (string) $call->tags['artist'];
						$tags['track'] = (string) $call->tags['track'];
						$i = 0;
						foreach ( $call->tags->tag as $tag ) {
							$tags['tags'][$i]['name'] = (string) $tag->name;
							$tags['tags'][$i]['url'] = (string) $tag->url;
							$i++;
						}
						return $tags;
					}
					else {
						$this->handleError(90, 'The user has no tags on this track');
						return FALSE;
					}
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
	 * Get the top fans for this track on Last.fm, based on listening data
	 * @param array $methodVars An array with the following required values: <i>artist</i>, <i>track</i>
	 * @return array
	 */
	public function getTopFans($methodVars) {
		// Check for required variables
		if ( !empty($methodVars['artist']) && !empty($methodVars['track']) ) {
			$vars = array(
				'method' => 'track.gettopfans',
				'api_key' => $this->auth->apiKey
			);
			$vars = array_merge($vars, $methodVars);
			
			if ( $call = $this->apiGetCall($vars) ) {
				if ( count($call->topfans->user) > 0 ) {
					$topFans['artist'] = (string) $call->topfans['artist'];
					$topFans['track'] = (string) $call->topfans['track'];
					$i = 0;
					foreach ( $call->topfans->user as $user ) {
						$topFans['users'][$i]['name'] = (string) $user->name;
						$topFans['users'][$i]['url'] = (string) $user->url;
						$topFans['users'][$i]['image']['small'] = (string) $user->image[0];
						$topFans['users'][$i]['image']['medium'] = (string) $user->image[1];
						$topFans['users'][$i]['image']['large'] = (string) $user->image[2];
						$topFans['users'][$i]['weight'] = (string) $user->weight;
						$i++;
					}
					return $topFans;
				}
				else {
					$this->handleError(90, 'This track has no fans');
					return FALSE;
				}
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
	
	/**
	 * Get the top tags for this track on Last.fm, ordered by tag count
	 * @param array $methodVars An array with the following required values: <i>artist</i>, <i>track</i>
	 * @return array
	 */
	public function getTopTags($methodVars) {
		// Check for required variables
		if ( !empty($methodVars['artist']) && !empty($methodVars['track']) ) {
			$vars = array(
				'method' => 'track.gettoptags',
				'api_key' => $this->auth->apiKey
			);
			$vars = array_merge($vars, $methodVars);
			
			if ( $call = $this->apiGetCall($vars) ) {
				if ( count($call->toptags->tag) > 0 ) {
					$topTags['artist'] = (string) $call->toptags['artist'];
					$topTags['track'] = (string) $call->toptags['track'];
					$i = 0;
					foreach ( $call->toptags->tag as $tag ) {
						$topTags['tags'][$i]['name'] = (string) $tag->name;
						$topTags['tags'][$i]['count'] = (string) $tag->count;
						$topTags['tags'][$i]['url'] = (string) $tag->url;
						$i++;
					}
					return $topTags;
				}
				else {
					$this->handleError(90, 'This track has no tags');
					return FALSE;
				}
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
	
	/**
	 * Love a track for a user profile. This needs to be supplemented with a scrobbling submission containing the 'love' rating (see the audioscrobbler API) (Requires full auth)
	 * @param array $methodVars An array with the following required values: <i>artist</i>, <i>track</i>
	 * @return boolean
	 */
	public function love($methodVars) {
		// Only allow full authed calls
		if ( $this->fullAuth == TRUE ) {
			// Check for required variables
			if ( !empty($methodVars['artist']) && !empty($methodVars['track']) ) {
				$vars = array(
					'method' => 'track.love',
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
	 * Remove a user's tag from a track (Requires full auth)
	 * @param array $methodVars An array with the following required values: <i>artist</i>, <i>track</i>, <i>tag</i>
	 * @return boolean
	 */
	public function removeTag($methodVars) {
		// Only allow full authed calls
		if ( $this->fullAuth == TRUE ) {
			// Check for required variables
			if ( !empty($methodVars['artist']) && !empty($methodVars['track']) && !empty($methodVars['tag']) ) {
				$vars = array(
					'method' => 'track.removetag',
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
				$this->handleError(91, 'You must include tag, artist and track varialbes in the call for this method');
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
	 * Search for a track by track name. Returns track matches sorted by relevance
	 * @param array $methodVars An array with the following required values: <i>track</i>
	 * @return array
	 */
	public function search($methodVars) {
		// Check for required variables
		if ( !empty($methodVars['track']) ) {
			$vars = array(
				'method' => 'track.search',
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
					foreach ( $call->results->trackmatches->track as $track ) {
						$searchResults['results'][$i]['name'] = (string) $track->name;
						$searchResults['results'][$i]['artist'] = (string) $track->artist;
						$searchResults['results'][$i]['url'] = (string) $track->url;
						$searchResults['results'][$i]['streamable'] = (string) $track->streamable;
						$searchResults['results'][$i]['fulltrack'] = (string) $track->streamable['fulltrack'];
						$searchResults['results'][$i]['listeners'] = (string) $track->listeners;
						$searchResults['results'][$i]['image']['small'] = (string) $track->image[0];
						$searchResults['results'][$i]['image']['medium'] = (string) $track->image[1];
						$searchResults['results'][$i]['image']['large'] = (string) $track->image[2];
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
			$this->handleError(91, 'You must include track variable in the call for this method');
			return FALSE;
		}
	}
	
	/**
	 * Share a track twith one or more Last.fm users or other friends (Requires full auth)
	 * @param array $methodVars An array with the following required values: <i>artist</i>, <i>track</i>, <i>recipient</i> and optional value: <i>message</i>
	 * @return boolean
	 */
	public function share($methodVars) {
		// Only allow full authed calls
		if ( $this->fullAuth == TRUE ) {
			// Check for required variables
			if ( !empty($methodVars['artist']) && !empty($methodVars['track']) && !empty($methodVars['recipient']) ) {
				$vars = array(
					'method' => 'track.share',
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
				$this->handleError(91, 'You must include recipient, artist and track varialbes in the call for this method');
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