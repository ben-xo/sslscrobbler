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

class SSLTwitterAdaptor implements ParallelTask, NowPlayingObserver, ScrobbleObserver
{
    /**
     * @var Twitter
     */
    protected $twitter;
    protected $msg_format;
    
    /**
     * @var Array of ITrackMessageFilter
     */
    protected $message_filters;
    
    /**
     * @var SSLTrack
     */
    protected $track_to_notify;
    
    public function __construct(Twitter $twitter, $msg_format, array $message_filters)
    {
        $this->twitter = $twitter;
        $this->msg_format = $msg_format;
        $this->message_filters = $message_filters;
    }
    
    public function notifyNowPlaying(SSLTrack $track=null)
    {
        if($track)
        {
            $this->track_to_notify = $track;
            
            // Send tweet in a new process, so that it doesn't block other plugins.
            $runner = new ParallelRunner();
            $runner->spinOff($this, 'Twitter update');
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
        
        // Twitter max message length, minus the pre-processed message,
        // and give back 2 chars for '%s'
        $max_title_length = 160 - (mb_strlen($message) - 2); 
        
        $title = $track->getFullTitle();
        $title_length = mb_strlen($title);

        if($title_length > $max_title_length)
        {
            $title = mb_substr($title, 0, $this->max_title_length - 1) . '…';
        }

        $status = sprintf($message, $title);

        try
        {
            L::level(L::DEBUG) &&
                L::log(L::DEBUG, __CLASS__, 'Sending Now Playing to Twitter',
                    array( ));

            $this->twitter->send($status);
        }
        catch(Exception $e)
        {
            L::level(L::WARNING) &&
                L::log(L::WARNING, __CLASS__, 'Could not send Now Playing to Twitter: %s',
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
}