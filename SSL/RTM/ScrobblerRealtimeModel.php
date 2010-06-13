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

class ScrobblerRealtimeModel implements TickObserver, TrackChangeObserver
{    
    /**
     * @var Array of ScrobblerTrackModel
     */
    protected $scrobble_model_queue = array();
    
    /**
     * @var ScrobblerTrackModel
     */
    protected $now_playing_in_queue;
    
    protected $debug = true;
    
    public function notifyTick($seconds)
    {
        $this->elapse($seconds);
    }
    
    public function notifyTrackChange(TrackChangeEventList $events)
    {
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
        
        $this->elapse(0);
    }
    
    protected function stopTrack(SSLTrack $stopped_track)
    {
        $stopped_row = $stopped_track->getRow();
        
        foreach($this->scrobble_model_queue as $i => $scrobble_model)
        {
            if($scrobble_model->getRow() == $stopped_row)
            {
                unset($this->scrobble_model_queue[$i]);
            }
        }
        
        // reindex the queue
        $this->scrobble_model_queue = array_merge($this->scrobble_model_queue);
        
        $this->debug && print("DEBUG: ScrobbleRealtimeModel::stopTrack(): dequeued track " . $stopped_track->getFullTitle() 
                            . ". Queue length is now " . count($this->scrobble_model_queue) . "\n");
    }

    protected function startTrack(SSLTrack $started_track)
    {
                            
        // Put new tracks last in the queue for the purposes of determining what's now playing.
        // This means that tracks should transition to "Now Playing" when the previous track is stopped or taken off the deck.
        $this->scrobble_model_queue[] = new ScrobblerTrackModel($started_track);

        $this->debug && print("DEBUG: ScrobbleRealtimeModel::startTrack(): queued track " . $started_track->getFullTitle() 
                            . ". Queue length is now " . count($this->scrobble_model_queue) . "\n");
    }
    
    protected function updateTrack(SSLTrack $updated_track)
    {
        foreach($this->scrobble_model_queue as $scrobble_model)
        {
            // the ScrobblerTrackModel will ignore the track if it's not the one it's modelling
            $scrobble_model->update($updated_track);
        }
        
        $this->debug && print("DEBUG: ScrobbleRealtimeModel::updateTrack(): updated track " . $updated_track->getFullTitle() 
                            . ". Queue length is now " . count($this->scrobble_model_queue) . "\n");
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
     */
    protected function elapse($seconds)
    {
        foreach($this->scrobble_model_queue as $scrobble_model)
        {
            /* @var $scrobble_model ScrobblerTrackModel */
            if(!$scrobble_model) print "WTF\n";
            $scrobble_model->elapse($seconds);
        }
        
        $is_now_playing = false;
        $queue_length = count($this->scrobble_model_queue);
        foreach($this->scrobble_model_queue as &$scrobble_model)
        {
            // If this is the only track in the queue, show it now playing immediately,
            // otherwise only show it now playing after the "now playing" timer has elapsed.
            // The "immediate" bypass is appropriate for the first played track, and for 
            // the single-deck preview mode
            
            if($queue_length == 1 || $scrobble_model->isNowPlaying())
            {
                $is_now_playing = true;
                $candidate_now_playing_track = $scrobble_model->getTrack();

                // is this a "new" now playing track? (new id > old id) 
                if(empty($this->now_playing_in_queue) || $candidate_now_playing_track->getRow() > $this->getNowPlayingRow())
                {
                    // There is a new track playing!
                    
                    // keep a reference to the now playing model
                    $this->now_playing_in_queue =& $scrobble_model;
                    
                    // notify observers (Growl, etc)
                    // $this->notifyNowPlayingObservers($candidate_now_playing_track);
                    $this->lastfmNowPlaying($candidate_now_playing_track);
                }
                
                // break on first "Now Playing" track
                break;
            }
        }
        
        if(!$is_now_playing && !empty($this->now_playing_in_queue))
        {
            // Playback has stopped!
            
            $this->now_playing_in_queue = null;
        
            // notify observers (Growl, etc)
            // $this->notifyNowPlayingObservers(null);
            $this->lastfmNowPlaying(null);
        }
    }
    
    protected function lastfmNowPlaying(SSLTrack $track=null)
    {
        // TODO
        if($track)
        {
            echo "DEBUG: ScrobbleRealtimeModel::lastfmNowPlaying(): TODO: send Now Playing notice to Last.fm!\n";
        }
        else
        {
            echo "DEBUG: ScrobbleRealtimeModel::lastfmNowPlaying(): TODO: send Stopped Playing notice to Last.fm!\n";
        }
    }
}