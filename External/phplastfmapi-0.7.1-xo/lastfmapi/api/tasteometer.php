<?php
/**
 * File that stores api calls for tasteometer api calls
 * @package apicalls
 */
/**
 * Allows access to the api requests relating to the tasteometer
 * @package apicalls
 */
class lastfmApiTasteometer extends lastfmApi {
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
	 * Get a Tasteometer score from two inputs, along with a list of shared artists. If the input is a User or a Myspace URL, some additional information is returned
	 * @param array $methodVars An array with two sub arrays <i>(1 and 2) both containing the required variables: <i>type ('user' | 'artists' | 'myspace')</i> and <i>value ([Last.fm username] | [Comma-separated artist names] | [MySpace profile URL])</i>
	 * @return array
	 */
	public function compare($methodVars) {
		// Check for required variables
		if ( !empty($methodVars[1]['type']) && !empty($methodVars[1]['value']) &&  !empty($methodVars[2]['type']) && !empty($methodVars[2]['value']) ) {	
			if ( $methodVars[1]['type'] == 'artists' && is_array($methodVars[1]['value']) ) {
				$value1 = '';
				foreach ( $methodVars[1]['value'] as $artist ) {
					$value1 .= $artist.',';
				}
				$value1 = substr($value1, 0, -1);
			}
			else {
				$value1 = $methodVars[1]['value'];
			}
			
			if ( $methodVars[2]['type'] == 'artists' && is_array($methodVars[2]['value']) ) {
				$value2 = '';
				foreach ( $methodVars[2]['value'] as $artist ) {
					$value2 .= $artist.',';
				}
				$value2 = substr($value2, 0, -1);
			}
			else {
				$value2 = $methodVars[2]['value'];
			}
			
			$vars = array(
				'method' => 'tasteometer.compare',
				'api_key' => $this->auth->apiKey,
				'type1' => $methodVars[1]['type'],
				'value1' => $value1,
				'type2' => $methodVars[2]['type'],
				'value2' => $value2
			);
			
			if ( $call = $this->apiGetCall($vars) ) {
				$result = '';
				
				$result['score'] = (string) $call->comparison->result->score;
				$result['matches'] = (string) $call->comparison->result->artists['matches'];
				$i = 0;
				foreach ( $call->comparison->result->artists->artist as $artist ) {
					$result['artists'][$i]['name'] = (string) $artist->name;
					$result['artists'][$i]['url'] = (string) $artist->url;
					$result['artists'][$i]['image']['small'] = (string) $artist->image[2];
					$result['artists'][$i]['image']['medium'] = (string) $artist->image[1];
					$result['artists'][$i]['image']['large'] = (string) $artist->image[0];
					$i++;
				}
				
				$countUser = 0;
				$countMyspace = 0;
				switch ( $methodVars[1]['type'] ) {
					case 'user':
						$result['inputOne']['type'] = 'user';
						$result['inputOne']['name'] = (string) $call->comparison->input->user[$countUser]->name;
						$result['inputOne']['url'] = (string) $call->comparison->input->user[$countUser]->url;
						$result['inputOne']['image']['small'] = (string) $call->comparison->input->user[$countUser]->image[2];
						$result['inputOne']['image']['medium'] = (string) $call->comparison->input->user[$countUser]->image[1];
						$result['inputOne']['image']['large'] = (string) $call->comparison->input->user[$countUser]->image[0];
						$countUser++;
					break;
					case 'artists':
						$result['inputOne']['type'] = 'artists';
					break;
					case 'myspace':
						$result['inputOne']['type'] = 'myspace';
						$result['inputOne']['url'] = (string) $call->comparison->input->myspace[$countMyspace]->url;
						$result['inputOne']['image'] = (string) $call->comparison->input->myspace[$countMyspace]->image;
						$countMyspace++;
					break;
				}
				
				switch ( $methodVars[2]['type'] ) {
					case 'user':
						$result['inputTwo']['type'] = 'user';
						$result['inputTwo']['name'] = (string) $call->comparison->input->user[$countUser]->name;
						$result['inputTwo']['url'] = (string) $call->comparison->input->user[$countUser]->url;
						$result['inputTwo']['image']['small'] = (string) $call->comparison->input->user[$countUser]->image[2];
						$result['inputTwo']['image']['medium'] = (string) $call->comparison->input->user[$countUser]->image[1];
						$result['inputTwo']['image']['large'] = (string) $call->comparison->input->user[$countUser]->image[0];
						$countUser++;
					break;
					case 'artists':
						$result['inputTwo']['type'] = 'artists';
					break;
					case 'myspace':
						$result['inputTwo']['type'] = 'myspace';
						$result['inputTwo']['url'] = (string) $call->comparison->input->myspace[$countMyspace]->url;
						$result['inputTwo']['image'] = (string) $call->comparison->input->myspace[$countMyspace]->image;
						$countMyspace++;
					break;
				}
				
				return $result;
			}
			else {
				return FALSE;
			}
		}
		else {
			// Give a 91 error if incorrect variables are used
			$this->handleError(91, 'You must include 2 sets of type and value varialbes in the call for this method');
			return FALSE;
		}
	}
}

?>