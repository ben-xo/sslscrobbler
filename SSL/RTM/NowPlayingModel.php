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

class NowPlayingModel implements TickObserver, TrackChangeObserver, NowPlayingObservable
{   
    /**
     * @var SSLRepo
     */
    protected $factory;
    
    /**
     * @var Array of NowPlayingObserver
     */
    protected $now_playing_observers = array();
    
    /**
     * @var Array of ScrobblerTrackModel
     */
    protected $now_playing_queue = array();
    
    /**
     * @var ScrobblerTrackModel
     */
    protected $now_playing_in_queue;
    
    public function __construct()
    {
        $this->factory = Inject::the(new SSLRepo());
    }
    
    public function notifyTick($seconds)
    {
        $was_playing_before_notify = false;
        if(isset($this->now_playing_in_queue))
        {
            $was_playing_before_notify = $this->now_playing_in_queue->getRow();
        }
        
        $this->elapse($seconds, $was_playing_before_notify);
    }
    
    public function notifyTrackChange(TrackChangeEventList $events)
    {
        $was_playing_before_notify = false;
        if(isset($this->now_playing_in_queue))
        {
            $was_playing_before_notify = $this->now_playing_in_queue->getRow();
        }
                 
        foreach($events as $event)
        {
            if($event instanceof TrackStoppedEvent)
            {
                $this->stopTrack($event->getTrack());
            }
            
            elseif($event instanceof TrackStartedEvent)
            {
                $this->startTrack($event->getTrack());
            }
            
            elseif($event instanceof TrackUpdatedEvent)
            {
                $this->updateTrack($event->getTrack());
            }
        }
        
        $this->elapse(0, $was_playing_before_notify);
    }
    
    public function addNowPlayingObserver(NowPlayingObserver $o)
    {
        $this->now_playing_observers[] = $o;
    }

    public function getQueueSize()
    {
        return count($this->now_playing_queue);
    }
    
    protected function stopTrack(SSLTrack $stopped_track)
    {
        $stopped_row = $stopped_track->getRow();
        
        foreach($this->now_playing_queue as $i => $scrobble_model)
        {
            /* @var $scrobble_model ScrobblerTrackModel */
            if($scrobble_model->getRow() == $stopped_row)
            {
                // remove from the queue.
                unset($this->now_playing_queue[$i]);
                
                // remove from now_playing, if necessary.
                if($this->now_playing_in_queue && 
                   $this->now_playing_in_queue->getRow() == $stopped_row)
                {
                    $this->now_playing_in_queue = null;
                }
                break;
            }
        }
        
        // reindex the queue
        $this->now_playing_queue = array_merge($this->now_playing_queue);
        
        L::level(L::INFO, __CLASS__) && 
            L::log(L::INFO,  __CLASS__, 'dequeued track %s', 
                array($stopped_track->getFullTitle()) );
                
        L::level(L::DEBUG, __CLASS__) && 
            L::log(L::DEBUG, __CLASS__, 'queue length is now %d', 
                array(count($this->now_playing_queue)) );
    }

    protected function startTrack(SSLTrack $started_track)
    {
        $started_row = $started_track->getRow();
        
        foreach($this->now_playing_queue as $i => $scrobble_model)
        {
            /* @var $scrobble_model ScrobblerTrackModel */
            if($scrobble_model->getRow() == $started_row)
            {
                // do not double-add to the queue.
                return;
            }
        }
                                    
        // Put new tracks last in the queue for the purposes of determining what's now playing.
        // This means that tracks should transition to "Now Playing" when the previous track is stopped or taken off the deck.
        $scrobble_model = $this->factory->newScrobblerTrackModel($started_track);
        $scrobble_model_row = $scrobble_model->getRow();
        if($scrobble_model_row != $started_row)
        {
            throw new RuntimeException("Row mismatch! Asked for {$started_row}, got {$scrobble_model_row}");   
        }
        $this->now_playing_queue[] = $scrobble_model;
        
        L::level(L::INFO, __CLASS__) && 
            L::log(L::INFO,  __CLASS__, 'enqueued track %s', 
                array($started_track->getFullTitle()) );
                
        L::level(L::DEBUG, __CLASS__) && 
            L::log(L::DEBUG, __CLASS__, 'queue length is now %d', 
                array( count($this->now_playing_queue)) );
    }
    
    protected function updateTrack(SSLTrack $updated_track)
    {
        foreach($this->now_playing_queue as $scrobble_model)
        {
            // the ScrobblerTrackModel will ignore the track if it's not the one it's modelling
            $scrobble_model->update($updated_track);
        }
        
        L::level(L::INFO, __CLASS__) && 
            L::log(L::INFO,  __CLASS__, 'updated track %s', 
                array($updated_track->getFullTitle()) );
                
        L::level(L::DEBUG, __CLASS__) && 
            L::log(L::DEBUG, __CLASS__, 'queue length is now %d', 
                array( count($this->now_playing_queue)) );
    }
    
    /**
     * @return SSLTrack
     */
    protected function getNowPlaying()
    {
        return $this->now_playing_in_queue->getTrack(); 
    }
    
    protected function getNowPlayingRow()
    {
        return $this->now_playing_in_queue->getRow();
    }
    
    /**
     * Update all queued tracks with elapsed seconds, then see if there
     * have been any transitions in what's "Now Playing".
     * 
     * @param integer $seconds
     * @param integer $was_playing_before id of the row playing, or false if none was playing
     */
    protected function elapse($seconds, $was_playing_before)
    {
        foreach($this->now_playing_queue as $scrobble_model)
        {
            /* @var $scrobble_model ScrobblerTrackModel */
            if(!$scrobble_model)
            {
                throw new RuntimeException("Invalid queue state: empty entry found!");  
            } 
            
            $scrobble_model->elapse($seconds);
        }
        
        $is_now_playing = false;
        $queue_length = count($this->now_playing_queue);
        
        // Try to find the earliest queued track which is naturally "now playing".
        foreach($this->now_playing_queue as $scrobble_model)
        {
            if($scrobble_model->isNowPlaying())
            {
                $is_now_playing = true;
                $this->setTrackNowPlaying($scrobble_model);
                
                // break on first "Now Playing" track
                return;
            }
        }

        if(!$is_now_playing)
        {
            // Perhaps no track is naturally "now playing".
            // Try to pick the oldest track which is no older than the previous "now playing" track.
            // That is, either "maintain the status quo" or "move forward".
            
            $preferred_now_playing_model = -1;
            foreach($this->now_playing_queue as $i => $scrobble_model)
            {
                $scrobble_model_row = $scrobble_model->getRow();
                if($scrobble_model_row < $was_playing_before)
                {
                    continue;
                }
                
                $is_now_playing = true;
                $preferred_now_playing_model = $i;
                break;
            }
            
            if($preferred_now_playing_model !== -1)
            {
                // set it "now playing" only once we've picked it.
                $this->setTrackNowPlaying($this->now_playing_queue[$preferred_now_playing_model]);
                return;
            }
        }
        
        if(!$is_now_playing && $queue_length >= 1)
        {
            // No track is naturally "now playing", and no track was found that is
            // equivalent or newer to the previous now playing track. Yet, there are tracks
            // in the queue - so let's just arbitrarily pick the first.
            
            // This is particularly appropriate for the first played track, and for 
            // the single-deck preview mode
                        
            $scrobble_model = $this->now_playing_queue[0];
            
            $is_now_playing = true;
            $this->setTrackNowPlaying($scrobble_model);
            return;
        }
        
        if(!$is_now_playing && $was_playing_before)
        {
            // There is definitely no track playing, but one was before. 
            // Send a "stop" notification.
            
            // Playback has stopped!
            $this->now_playing_in_queue = null;
        
            // notify observers (Growl, etc) that no track is playing.
            $this->notifyNowPlayingObservers(null);
        }
    }
    
    /**
     * Sets a track from a ScrobblerTrackModel as "now playing", and notifies observers
     * 
     * @param ScrobblerTrackModel $scrobble_model
     */
    protected function setTrackNowPlaying(ScrobblerTrackModel $scrobble_model)
    {
        $candidate_now_playing_track = $scrobble_model->getTrack();
        
        $new_track = false;
        
        // is this a "new" now playing track? 
        if(!empty($this->now_playing_in_queue))
        {
            $np_row = $this->now_playing_in_queue->getRow();
            $cd_row = $candidate_now_playing_track->getRow();
            if($np_row != $cd_row)
            {
                $new_track = true;
            }
        }
        else
        {
            $new_track = true;
        }
        
        if($new_track)
        {
            // There is a new track playing!
            
            // keep the now playing model
            $this->now_playing_in_queue = $scrobble_model;
            
            // notify observers (Growl, etc)
            $this->notifyNowPlayingObservers($candidate_now_playing_track);
        }
    }
    
    protected function notifyNowPlayingObservers(SSLTrack $track=null)
    {
        /* @var $observer NowPlayingObserver */
        foreach($this->now_playing_observers as $observer)
        {
            $observer->notifyNowPlaying($track);
        }
    }    
}
