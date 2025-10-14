<?php

/**
 *  @author      Attila Györffy (attila.gyorffy@gmail.com)
 *  @copyright   Copyright (c) 2017 Attila Györffy
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

/**
 * Sends your current Now Playing track to a HTTP REST API via a curl request
 *
 * For command line setup @see CLIDBPlugin
 */
class curlPlugin implements SSLPlugin, NowPlayingObserver
{

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

  public function onStop()
  {
  }

  public function getObservers()
  {
      return array(
          $this
      );
  }

  public function notifyNowPlaying(?SSLTrack $track=null)
  {
      if($track)
      {
        L::level(L::INFO, __CLASS__) && L::log(L::INFO, __CLASS__, 'Sending %s to an API via cURL.', array($track->getFullTitle()));
        $this->sendRequest($track);
      }
      else
      {
        L::level(L::INFO, __CLASS__) && L::log(L::INFO, __CLASS__, 'No track data available. Not Sending via cURL.');
      }
  }

  protected function sendRequest($track)
  {
    $curl = curl_init();

    curl_setopt($curl, CURLOPT_PORT, $this->config['port']);
    curl_setopt($curl, CURLOPT_URL, $this->config['url']);

    if ($this->config['verb'] == 'POST') {
      curl_setopt($curl, CURLOPT_POST, true);
    }

    curl_setopt($curl, CURLOPT_USERAGENT, $this->config['user_agent']);

    $params = array(
      'full_title' => $track->getFullTitle(),
      'artist' => $track->getArtist(),
      'title' => $track->getTitle()
    );

    curl_setopt($curl, CURLOPT_POSTFIELDS, $params);

    curl_exec($curl);
    curl_close($curl);
  }
}
