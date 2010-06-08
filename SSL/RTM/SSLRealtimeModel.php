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

/**
 * The real time model responds to timer ticks via tick(), and changes to the tracks
 * on the decks via notify(). (Calling notify() does not imply a tick()).
 * 
 * This model is intended to simulate the decks in ScratchLIVE, but it does so by inferring
 * the status of the decks from information about TRACKS that are written to the history file.
 * Therefore it will not always match everything that happens in software exactly. However, 
 * it can infer quite a lot!
 * 
 * We model the decks to be in one of five states:
 * 
 * * EMPTY
 * 		No track has yet been loaded on to the deck. This is the starting state for the deck.
 * 		(This state is never returned to by any other transition, but PLAYED and SKIPPED are 
 * 		similar).
 * 
 * * NEW		
 * 		A track was added to the deck, and may (or may not) be playing. At this point it's 
 * 		unknown	if it will count as a play or not for the purposes of the history (ScratchLive
 *      itself enters it into the history as 'unplayed' by default). However, we model it as 
 *      "Now Playing" for the purposes of scrobble-info after waiting NOW_PLAYING_POINT seconds,
 *      so as to keep it visually interesting...
 * 
 * * PLAYING 
 * 		The track on the deck is still playing, and will definitely count as "played" in the
 * 		history. In ScratchLive, tracks transition from NEW to PLAYING when a new track is
 * 		loaded on to any OTHER deck. At this point, ScratchLive marks the track as played. We
 * 		then trigger timers for the various scrobble rules, based on MIN_SCROBBLE_TIME and
 * 		SCROBBLE_POINT_DIVIDER.
 * 
 * * PLAYED	
 * 		Similar to EMPTY, but indicates that the previous track on the deck has finished playing.
 * 		This state is usually only reached if the track runs out, or the deck was unloaded while 
 * 		the state was PLAYING. If the track was simply replaced by a different track, you won't 
 * 		see this state (see the Compound Transitions list below).
 * 
 * * SKIPPED
 * 		Similar to EMPTY, but the previous track on the deck was not played for the purposes of 
 * 		the history. This state	is usually only reached if the deck was unloaded while the state 
 * 		was NEW (implying that you are just trying a track out in the mix, rather than playing it). 
 * 		If the track was simply replaced by a different track, you won't see this state (see the
 * 		Compound Transitions list below).
 * 
 * ScratchLive writes several track blocks out to the history file at each track load. The 
 * statuses - NEW, PLAYING, PLAYED or SKIPPED, for a deck - can actually be inferred directly from 
 * fields on the track blocks. This creates the following possible transitions for an individual
 * deck:
 * 
 * Simple Transitions:
 * 
 *  EMPTY -> NEW        : First track is loaded to the deck
 *  SKIPPED -> NEW      : A track is loaded onto an empty deck
 *  PLAYED -> NEW       : A track is loaded onto an empty deck
 *  NEW -> SKIPPED      : NEW track is unloaded from the deck (e.g. via the eject button)
 *  NEW -> PLAYING      : A track is loaded to any other deck
 *  PLAYING -> PLAYED   : PLAYING track is unloaded from the deck (e.g. via the eject button)
 *  
 * Compound Transitions:
 * 
 *  NEW (-> SKIPPED) -> NEW     : A NEW track is replaced by another track
 *  PLAYING (-> PLAYED) -> NEW  : A PLAYING track is replaced by another track
 * 
 * @author ben
 */

class SSLRealtimeModel implements SSLDiffObserver, TrackChangeObservable
{
    protected $timers = array();
    protected $decks = array();
    
    protected $trackchange_observers = array();
       
    public function addTrackChangeObserver(TrackChangeObserver $o)
    {
        $this->trackchange_observers[] = $o;
    }
    
    protected function notifyTrackChangeObservers(TrackChangeEvent $event)
    {
        foreach($this->trackchange_observers as $o)
        {
            $o->notifyTrackChange($event);
        }
    }
    
    /**
     * @return SSLRealtimeModelDeck
     */
    protected function getDeck($deck)
    {
        if(!isset($this->decks[$deck]))
        {
            $this->decks[$deck] = new SSLRealtimeModelDeck($deck);
        }
        
        return $this->decks[$deck];
    }
    
    /**
     * Returns the previously played track on deck $deck
     * or null if the deck was previously empty
     * 
     * @param integer $deck
     * @return SSLTrack
     */
    public function getPreviousTrack($deck)
    {
        return $this->getDeck($deck)->getPreviousTrack();
    }
    
    /**
     * Returns the currently playing track on deck $deck
     * or null if the deck is empty
     * 
     * @param integer $deck
     * @return SSLTrack
     */
    public function getCurrentTrack($deck)
    {
        return $this->getDeck($deck)->getCurrentTrack();
    }
    
    public function getStatus($deck)
    {
        return $this->getDeck($deck)->getStatus();
    }
    
    public function getStartTime($deck)
    {
        return $this->getDeck($deck)->getStartTime();
    }
    
    public function getEndTime($deck)
    {
        return $this->getDeck($deck)->getEndTime();
    }
    
    public function getEventTimer($deck)
    {
        return isset($this->timers[$deck]) ? $this->timers[$deck] : 0;
    }    
    
    public function getDeckIDs()
    {
        return array_keys($this->decks);
    }
    
    /**
     * Returns the play time of the track on deck $deck, in seconds.
     * 
     * @param integer $deck
     * @return integer
     */
    public function getPlaytime($deck)
    {
        $this->getDeck($deck)->getPlaytime();      
    }
    
    /**
     * Update model information based on changes to the SSL History.
     * 
     * @param SSLHistoryDiffDom $diff
     */
    public function notifyDiff(SSLHistoryDiffDom $diff)
    {
        foreach($diff->getTracks() as $track)
        {
        	/** @var SSLTrack $track */
            // create track deck on demand
            $this->getDeck($track->getDeck());
        }
        
        foreach($this->decks as $deck_number => $deck)
        {
        	/** @var SSLRealtimeModelDeck $deck */
            $deck->notify($diff);
            
            //$events = array();
                        
            if($deck->trackStopped())
            {
                $prev_track = $deck->getPreviousTrack();
                if($prev_track)
                {
                    $event = new TrackStoppedEvent($prev_track);
                    $this->notifyTrackChangeObservers($event);
                }
            }
            
            if($deck->trackStarted())
            {
                $curr_track = $deck->getCurrentTrack();
                if($curr_track)
                {
                    $event = new TrackStartedEvent($curr_track);
                    $this->notifyTrackChangeObservers($event);
                }
            } 
            
            if($deck->trackUpdated())
            {
                $curr_track = $deck->getCurrentTrack();
                if($curr_track)
                {
                    $event = new TrackUpdatedEvent($curr_track);
                    $this->notifyTrackChangeObservers($event);
                }
            }
        }
        
        // TODO: make this send multiple events at once!
        //if(count($events))
        //{
        //    $this->notifyTrackChangeObservers($events);
        //}
    }
}
