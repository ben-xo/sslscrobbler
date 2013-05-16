<?php

/**
 *  @author      Jason Salaz (jason@zenenet.com)
 *  @copyright   Copyright (c) 2013 Jason Salaz
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

class NicecastLoggerPlugin implements SSLPlugin, NowPlayingObserver
{
	protected $config;

	public function __construct(array $config)
	{
		$this->setConfig($config);
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

	public function getFilename()
	{
		return $this->config['filename'];
	}

	public function onStop()
	{
		unlink($this->getFilename());
	}

	public function getObservers()
	{
		return array( $this );
	}

	public function notifyNowPlaying(SSLTrack $track=null)
	{
		$nicecastOutput = <<<EOF
Title: {$track->getTitle()}
Artist: {$track->getArtist()}
Album: {$track->getAlbum()}
Time: {$track->getLength()}
EOF;
		file_put_contents($this->getFilename(), $nicecastOutput);
	}
}
