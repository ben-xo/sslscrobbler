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
    
    protected $sessionname;
    
    /**
     * @var SSLTrack
     */
    protected $track_to_notify;
    
    protected $synchronous = false;
    protected $threading = false;
    
    public function __construct(Twitter $twitter, $msg_format, array $message_filters, $sessionname)
    {
        $this->twitter = $twitter;
        $this->msg_format = $msg_format;
        $this->message_filters = $message_filters;
        $this->sessionname = $sessionname;
    }
    
    public function notifyNowPlaying(?SSLTrack $track=null)
    {
        if($track)
        {
            $this->track_to_notify = $track;
            
            if($this->synchronous) {
                $this->run();
            } else {
                // Send tweet in a new process, so that it doesn't block other plugins.
                $runner = new ParallelRunner();
                $runner->spinOff($this, 'Twitter update');
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

        $reply_id = $this->getReplyId();
        $options = array();
        if($this->threading && $reply_id) {
            $options['in_reply_to_status_id'] = $reply_id;
            $message = '@' . $this->sessionname . ' ' . $message;
        }
        
        // Twitter max message length, minus the pre-processed message,
        // and give back 2 chars for '%s'
        $max_title_length = 280 - (mb_strlen($message) - 2); 
        
        $title = $track->getFullTitle();
        $title_length = mb_strlen($title);

        if($title_length > $max_title_length)
        {
            $title = mb_substr($title, 0, $this->max_title_length - 1) . '…';
        }

        $status = sprintf($message, $title);

        try
        {
            L::level(L::DEBUG, __CLASS__) &&
                L::log(L::DEBUG, __CLASS__, 'Sending Now Playing to Twitter: %s',
                    array( $status ));

            $response = $this->twitter->send($status, null, $options);
            if($response->id) {
                $this->saveReplyId($response->id);
            }
        }
        catch(Exception $e)
        {
            L::level(L::WARNING, __CLASS__) &&
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
    
    public function setSynchronous($synchronous) {
        $this->synchronous = $synchronous;
    }

    public function setThreading($do_threading) {
        $this->threading = $do_threading;
    }
    
    protected function saveReplyId($id) {
        $reply_file = 'twitter-' . $this->sessionname . '-last-reply.txt';
        file_put_contents($reply_file, "$id");
    }

    protected function getReplyId() {
        $reply_file = 'twitter-' . $this->sessionname . '-last-reply.txt';
        if (! file_exists($reply_file)) {
            L::level(L::INFO, __CLASS__) &&
                L::log(L::INFO, __CLASS__, 'No reply file - starting new Twitter thread.',
                    array( ));
            return false;
        }
        $reply_file_mtime = filemtime($reply_file);
        $reply_file_age = time() - $reply_file_mtime;

        if($reply_file_age > 3600 /* 1 hour */) {
            unlink($reply_file);
            L::level(L::INFO, __CLASS__) &&
                 L::log(L::INFO, __CLASS__, 'Reply file too old - starting new Twitter thread.',
                    array( ));
            return false;
        }
        return file_get_contents($reply_file);
    }
}
