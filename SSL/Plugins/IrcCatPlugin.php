<?php

/**
 *  @author      Ben XO (me@ben-xo.com)
 *  @copyright   Copyright (c) 2013 Ben XO
 *  @license     MIT License (http://www.opensource.org/licenses/mit-license.html)
 *
 *  Permission is hereby granted, free of charge, to any person obtaining a copy
 *  of this software and associated documentation files (the "Software"), to deal
 *  in the Software without restriction, including without limitation the rights
 *  to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 *  copies of the Software, and to permit persons to whom the Software is
 *  furnished to do so, subject to the following conditions:
 *
 *  The above copyright notice and this permission notice shall be included in
 *  all copies or substantial portions of the Software.
 *
 *  THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 *  IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 *  FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 *  AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 *  LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 *  OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 *  THE SOFTWARE.
 */

class IrcCatPlugin implements SSLPlugin, NowPlayingObserver
{
	protected $config;
	protected $host;
	protected $port;
	protected $channel;

	public function __construct(array $config, $host, $port, $channel)
	{
		$this->setConfig($config);
		$this->host = $host;
		$this->port = $port;
		$this->channel = $channel;
	}

	public function setConfig(array $config)
	{
		$this->config = $config;
	}

	public function onSetup()
	{
	}

	public function onStart()
	{
	}

	public function onStop()
	{
	}

	public function getObservers()
	{
		return array( $this );
	}

	public function notifyNowPlaying(?SSLTrack $track=null)
	{
	    if(!$track) return;
	    
	    $sock = fsockopen($this->host, $this->port, $errno, $errstr, $timeout=1);
	    if($sock===false)
	    {
	        L::level(L::ERROR, __CLASS__) &&
	            L::log(L::ERROR, __CLASS__, "couldn't connect to IRCCat: (%d) %s",
	                array($errno, $errstr));
	        
	         return;
	    }
	    
	    $message = sprintf($this->config['message'], $track->getFullTitle());
	    
	    L::level(L::INFO, __CLASS__) &&
	        L::log(L::INFO, __CLASS__, "sending '%s' -> %s:%d#%s",
	            array($message, $this->host, $this->port, $this->channel));
	     
	    
	    fwrite($sock, sprintf("%s %s", $this->channel, $message));
	    fclose($sock);
	}
}
