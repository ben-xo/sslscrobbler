<?php

/**
 *  @author      Ben XO (me@ben-xo.com)
 *  @copyright   Copyright (c) 2010 Ben XO
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

class SSLDiscordAdaptor implements ParallelTask, NowPlayingObserver, ScrobbleObserver
{
    /**
     * @var DiscordSDK
     */
    protected $discord;
    protected $msg_format;
    protected $channel_id;
    
    /**
     * @var Array of ITrackMessageFilter
     */
    protected $message_filters;
    
    protected $sessionname;
    
    /**
     * @var SSLTrack
     */
    protected $track_to_notify;
    
    protected $synchronous = false;
    
    public function __construct(DiscordSDK $discord, $msg_format, array $message_filters, $sessionname, $webhook_url)
    {
        $this->discord = $discord;
        $this->msg_format = $msg_format;
        $this->message_filters = $message_filters;
        $this->sessionname = $sessionname;
        $this->webhook_url = $webhook_url;
    }
    
    public function notifyNowPlaying(SSLTrack $track=null)
    {
        if($track)
        {
            $this->track_to_notify = $track;
            
            if($this->synchronous) {
                $this->run();
            } else {
                // Send tweet in a new process, so that it doesn't block other plugins.
                $runner = new ParallelRunner();
                $runner->spinOff($this, 'Discord update');
            }
            unset($this->track_to_notify);            
        }
    }
    
    protected function sendNowPlaying()
    {
        $track = $this->track_to_notify;
        
        $message = $this->msg_format;
        foreach($this->message_filters as $mf)
        {
            /* @var $mf ITrackMessageFilter */
            $message = $mf->apply($track, $message);
        }
              
        $title = $track->getFullTitle();

        $message = sprintf($message, $title);

        try
        {
            L::level(L::DEBUG, __CLASS__) &&
                L::log(L::DEBUG, __CLASS__, 'Sending Now Playing to Discord: %s',
                    array( $message ));

            $options = array(
                'content' => $message
            );
            $response = $this->discord->SendWebhookMessage($this->webhook_url, $options);
            if(isset($response['error']))
            {
                throw new RuntimeException($response['error']);
            }
        }
        catch(Exception $e)
        {
            L::level(L::WARNING, __CLASS__) &&
                L::log(L::WARNING, __CLASS__, 'Could not send Now Playing to Discord: %s',
                    array( $e->getMessage() ));
        }
    }

    public function notifyScrobble(SSLTrack $track)
    {

    }
    
    public function run()
    {
        $this->sendNowPlaying();
    }
    
    public function setSynchronous($synchronous) {
        $this->synchronous = $synchronous;
    }
}
