<?php
/**
 * PHP Scrobbler
 *
 * This class lets you submit tracks to a lastfm account. Curl needed.
 * 
 * Modified by Ben Isaacs (Last.fm) to support: 
 * * taking clientId and clientVer as params
 * * optionally use Web Service Session auth
 * * support submission of Now Playing info as well as scrobbles
 *
 * Basic usage:
 *
 * <?php
 * require('md/Scrobbler.php');
 * $scrobbler = new md_Scrobbler('lastfmUser', 'password');
 * $scrobbler->add('Jerry Goldsmith', 'The space jockey', 'alien', 289);
 * $scrobbler->submit();
 * ?>
 *
 * @author Mickael Desfrenes <desfrenes@gmail.com>
 * @author Ben Isaacs <ben@last.fm>
 * @license http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link http://github.com/ben-xo/PHP-Scrobbler
 */
class md_Scrobbler_Exception extends Exception{}
class md_Scrobbler
{
	// change this according to your client id and version
	const CLIENT_ID  = 'tst';
	const CLIENT_VER = '1.0';
	
	protected $clientId;
	protected $clientVer;

	const SCROBBLER_URL    = 'http://post.audioscrobbler.com/?hs=true&p=1.2.1&c=<client-id>&v=<client-ver>&u=<user>&t=<timestamp>&a=<auth>';
	const SCROBBLER_WS_URL = 'http://post.audioscrobbler.com/?hs=true&p=1.2.1&c=<client-id>&v=<client-ver>&u=<user>&t=<timestamp>&a=<auth>&api_key=<api_key>&sk=<session_key>';
	
	// curl timeout
	const TIMEOUT       = 10;

	// lastfm user
	protected $user;
	// lastfm user password
	protected $password;
	// scrobbler session id
	protected $sessionId         = '';
	// last handshake failure timestamp (0 = no failure)
	protected $handShakeFailure  = 0;
	// number of hard failures
	protected $submitFailures    = 0;
	// store tracks here
	protected $queue             = array();
	protected $nowPlayingUrl;
	protected $submissionUrl;
	
	protected $api_key;
	protected $api_secret;
	protected $api_sk;

	/**
	 * New md_Scrobbler
	 *
	 * You must supply either a password, or an API Key / Secret / SK combination.
	 * The combination is preferred according to http://www.last.fm/api/submissions
	 * 
	 * If you do not supply a client ID and Version, they will be taken from the class
	 * constants above.
	 *
	 * @param string LastFM login
	 * @param string LastFM password
	 * @param string LastFM API Key
	 * @param string LastFM API Secret
	 * @param string LastFM API Session Key
	 * @param string Client ID for scrobbling
	 * @param string Client Version for scrobbling
	 */
	public function __construct($user, $password=null, $api_key=null, $api_secret=null, $api_sk=null, $clientId=null, $clientVer=null)
	{
		$this->user = $user;
		
		if(isset($api_key))
		{
		    if(!isset($api_secret) || !isset($api_sk))
		    {
		        throw new md_Scrobbler_Exception('You must supply an API Secret and API Session Key to go with the API Key.');
		    }
    		$this->api_key = $api_key;
    		$this->api_secret = $api_secret;
    		$this->api_sk  = $api_sk;
		} 
		elseif(isset($password))
		{
		    $this->password = $password;
		}
		else
		{
		    throw new md_Scrobbler_Exception('You must supply either a password or an API Key.');
		}
		
		if(isset($clientId))
		{
		    $this->clientId = $clientId;
		}
		else
		{
		    $this->clientId = self::CLIENT_ID;
		}
		
		if(isset($clientVer))
		{
		    $this->clientVer = $clientVer;
		}
		else
		{
		    $this->clientVer = self::CLIENT_VER;
		}
	}

	/**
	 * Add a track to the queue
	 *
	 * @param string artist name
	 * @param string track title
	 * @param string album title
	 * @param integer track length (seconds)
	 * @param integer track play timestamp
	 * @param integer track number
	 * @param string source type (see lastFM API docs)
	 * @param integer rating
	 * @param string music brain track ID
	 * @return boolean
	 */
	public function add($artist, $track, $album = '', $trackDuration, $scrobbleTime = '', $trackNumber = '', $source = 'P', $rating = '', $mbTrackId = '')
	{
		if(empty($scrobbleTime))
		{
			$scrobbleTime = time();
		}
		$this->queue[] = array('artist'        => $artist,
							   'track'         => $track,
							   'scrobbleTime'  => $scrobbleTime,
							   'trackDuration' => $trackDuration,
							   'album'         => $album,
							   'trackNumber'   => $trackNumber,
							   'source'        => $source,
							   'rating'        => $rating,
							   'mbTrackId'     => $mbTrackId
							   );
		return true;
	}

	/**
	 * Submission process
	 *
	 * @throws md_Scrobbler_Exception
	 * @return boolean
	 */
	public function submit()
	{
		if(empty($this->queue))
		{
			throw new md_Scrobbler_Exception('Nothing to submit.');
			return false;
		}
		
		$data = $this->generatePostData();
		if($this->sendSubmission( 'submission' , $data ))
		{
			$this->queue = array();		    
		}		
	}
	
	public function nowPlaying($artist, $track, $album = '', $trackDuration, $trackNumber = '', $mbTrackId = '')
	{
	    $data = $this->generateNowPlayingPostData($artist, $track, $album, $trackDuration, $trackNumber, $mbTrackId);
	    $this->sendSubmission( 'nowPlaying', $data );
	}

	protected function sendSubmission($url_type, $post_data)
	{
		if(empty($this->sessionId) or $this->submitFailures > 2)
		{
			$this->handShake();
		}
		
		// add session id
		$post_data = 's=' . $this->sessionId . '&' . $post_data;
		
		if($url_type == 'nowPlaying') 
		{
		    $url = $this->nowPlayingUrl;
		}
		elseif($url_type == 'submission')
		{
		    $url = $this->submissionUrl;
		}
		
		
		$data = $this->doCurl($url, $post_data);
		$data = explode("\n", $data);	
		if($data[0] != 'OK')
		{
			$this->submitFailures++;
			if($data[0] == 'BADSESSION')
			{
				throw new md_Scrobbler_Exception('Bad session id.');
			}
			else
			{
				throw new md_Scrobbler_Exception('Submission failed : ' . $data[0]);
			}
			return false;
		}
		else
		{
			return true;
		}
	}
	
	protected function doCurl($url, $post_data)
	{
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_TIMEOUT, self::TIMEOUT);
		curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, self::TIMEOUT);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $post_data);
		$data = curl_exec($curl);
		curl_close ($curl);
		return $data;
	}
	
	protected function handShake()
	{
	    $url = $this->generateScrobblerUrl();
		$curl = curl_init($url);
		curl_setopt($curl, CURLOPT_TIMEOUT, self::TIMEOUT);
		curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, self::TIMEOUT);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);

		$data = curl_exec($curl);

		curl_close($curl);
		$data = explode("\n", $data);
		if($data[0] != 'OK')
		{
			$this->handShakeFailure = time();
			switch($data[0])
			{
				case 'BANNED':
					throw new md_Scrobbler_Exception('Client banned.');
					break;
				case 'BADTIME':
					throw new md_Scrobbler_Exception('Wrong system clock.');
					break;
				case 'BADAUTH':
					throw new md_Scrobbler_Exception('Wrong credentials.');
					break;
				default:
					throw new md_Scrobbler_Exception('Unexpected handshake error: ' . $data[0]);
					break;
			}
			return false;
		}
		else
		{
			$this->sessionId        = trim($data[1]);
			$this->nowPlayingUrl    = trim($data[2]);
			$this->submissionUrl    = trim($data[3]);
			$this->handShakeFailure = 0;
			$this->submitFailures   = 0;
			return true;
		}

		return false;
	}

	protected function generateScrobblerUrl()
	{
		$stamp = time();
		
		if(isset($this->password))
		{
	    	$url = str_replace(
						   array('<client-id>',
								 '<client-ver>',
								 '<user>',
								 '<timestamp>',
								 '<auth>'),
						   array($this->clientId,
								 $this->clientVer,
								 $this->user,
								 $stamp,
								 md5(md5($this->password) . $stamp)),
						   self::SCROBBLER_URL
						   );
		}
		else
		{
    		$url = str_replace(
						   array('<client-id>',
								 '<client-ver>',
								 '<user>',
								 '<timestamp>',
								 '<auth>',
						         '<api_key>',
						         '<session_key>'),
						   array($this->clientId,
								 $this->clientVer,
								 $this->user,
								 $stamp,
								 md5($this->api_secret . $stamp),
								 $this->api_key,
								 $this->api_sk),
						   self::SCROBBLER_WS_URL
						   );
		}
						   
        return $url;
	}

	protected function generatePostData()
	{
	    $body = '';
		$i = 0;
		foreach($this->queue as $item)
		{
			$body .= 'a[' . $i . ']=' . rawurlencode($item['artist']) . '&'
			. 't[' . $i . ']=' . rawurlencode($item['track']) . '&'
			. 'i[' . $i . ']=' . $item['scrobbleTime'] . '&'
			. 'o[' . $i . ']=' . $item['source'] . '&'
			. 'r[' . $i . ']=' . $item['rating'] . '&'
			. 'l[' . $i . ']=' . $item['trackDuration'] . '&'
			. 'b[' . $i . ']=' . rawurlencode($item['album']) . '&'
			. 'n[' . $i . ']=' . $item['trackNumber'] . '&'
			. 'm[' . $i . ']=' . $item['mbTrackId'] . '&';
			$i++;
		}

		return $body;
	}
	
	protected function generateNowPlayingPostData($artist, $track, $album = '', $trackDuration, $trackNumber = '', $mbTrackId = '')
	{
		$body = 'a=' . rawurlencode($artist) . '&'
		. 't=' . rawurlencode($track) . '&'
		. 'l=' . $trackDuration . '&'
		. 'b=' . rawurlencode($album) . '&'
		. 'n=' . $trackNumber . '&'
		. 'm=' . $mbTrackId;
		return $body;
	}
}
