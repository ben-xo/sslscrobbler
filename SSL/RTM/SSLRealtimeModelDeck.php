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
 * Models one (of several, usually 2) decks in Serato SSL.
 * 
 * Generally you would notify the deck of a bunch of SSLTracks (usually from the Serato 
 * History file) using notify(), and it would cherry pick relevant entries based on the 
 * deck number. After processing the Tracks, various status methods are pollable for 
 * the deck's current state and information about what changed. 
 * 
 * You may also ask the deck information about how long the current track has been 
 * playing for, etc.
 * 
 * @see SSLRealtimeModel for more info on the statuses, transitions and their meanings.
 * @author ben
 *
 */
class SSLRealtimeModelDeck
{
    protected $deck_number;
    protected $max_row = 0;
    
    /*
     * Status flags that are updated by notify()
     */
    protected $track_stopped = false;
    protected $track_started = false;
    protected $track_updated = false;
    
    /**
     * Stores the track on the deck at the beginning
     * of the update pass.
     * 
     * @var SSLTrack
     */
    protected $pre_update_track = null;
    
    /**
     * Stores the track currently on the deck.
     * 
     * @var SSLTrack
     */
    protected $track;
    
    /**
     * Stores the last played track on the deck.
     * 
     * @var SSLTrack
     */
    protected $previous_track;
    
    protected $status, $start_time, $end_time;
    
    public function __construct($deck_number)
    {
        $this->deck_number = $deck_number;
    }

    
    // Getters
    
    /**
     * Returns the previously played track
     * or null if the deck was previously empty
     * 
     * @param integer $deck
     * @return SSLTrack
     */
    public function getPreviousTrack()
    {
        return isset($this->previous_track) ? $this->previous_track : null;
    }
    
    /**
     * Returns the currently playing track
     * or null if the deck is empty
     * 
     * @param integer $deck
     * @return SSLTrack
     */
    public function getCurrentTrack()
    {
        return isset($this->track) ? $this->track : null;
    }
    
    public function getStatus()
    {
        return isset($this->status) ? $this->status : 'EMPTY';
    }
    
    public function getStartTime()
    {
        return isset($this->start_time) ? $this->start_time : null;
    }
    
    public function getEndTime()
    {
        return isset($this->end_time) ? $this->end_time : null;
    }   
    
    /**
     * Returns the play time of the track in seconds.
     * 
     * @param integer $deck
     * @return integer
     */
    public function getPlaytime()
    {
        $startTime = $this->getStartTime();
        $status = $this->getStatus();
        
        if(!isset($startTime)) 
        {
            return 0;   
        }
        
        if($status == 'PLAYED' || $status == 'SKIPPED')
        {
            $endTime = $this->getEndTime();            
        }
        else
        {
            $endTime = time();
        }
        
        return $endTime - $startTime;        
    }
    
    /**
     * Returns true if a track started since the last notify.
     */
    public function trackStarted()
    {
        return $this->track_started;
    }
    
    /**
     * Returns true if a track stopped since the last notify.
     */
    public function trackStopped()
    {
        return $this->track_stopped;
    }
    
    /**
     * Returns true if a track was updated since the last notify.
     */
    public function trackUpdated()
    {
        return $this->track_updated;
    }
    

   
    protected function resetFlags()
    {
        $this->track_started = false;
        $this->track_stopped = false;
        $this->track_updated = false;
    }
    

    // Mutators
    
    /**
     * Notify the deck of a group of changes written to the History File.
     * 
     * SSL batches writes of track info, but doesn't output them in the 
     * natural transition order - that is, sometimes information about the following
     * song appears in the log before closing info on the previous song, as the
     * History File is track oriented, not deck oriented.
     * 
     * During notify(), we reorder this information into an order that's
     * transition compatible (that is, row ID ascending order).
     * 
     * The implication of sending a bunch of track notifications together (rather
     * than one by one) is that they happen simultaneously, and the end result
     * is what's important rather than every step to get there.
     * 
     * As diffs usually only come during track load, change or eject, it would be
     * abnormal to see information about more than 2 tracks on a single deck here,
     * and abnormal for neither of them to be the one currently on the deck.
     * 
     * However, it can happen - for example, loading a historical file or starting
     * the monitor half way through a session.
     * 
     * @param SSLHistoryDiffDom $diff
     */
    public function notify(SSLHistoryDiffDom $diff)
    {
        $this->resetFlags();
        
        $starting_track = $this->track;
        if(isset($starting_track))
        {
            $starting_track_row = $starting_track->getRow();
        } 
        
        // filter out tracks that are not for this deck, or that are too old
        $my_tracks = array();
        foreach($diff->getTracks() as $track)
        {
            /* @var $track SSLTrack */
            if( $track->getDeck() == $this->deck_number && 
                $track->getRow()  >= $this->max_row )
            {
                // track notification for this deck!
                $my_tracks[$track->getRow()] = $track;
                L::level(L::DEBUG) && 
                    L::log(L::DEBUG, __CLASS__, "Saw %s in diff (row %s)", 
                        array( $track->getTitle(), $track->getRow()));
            }
        }
        
        // sort into natural 'history' order (that is, by row ID ascending)
        ksort($my_tracks); 
        
        foreach($my_tracks as $track)
        {
            try
            {
                $this->transitionTo($track);
            } 
            catch(SSLInvalidTransitionException $e)
            {
                L::level(L::WARNING) && 
                    L::log(L::WARNING, __CLASS__, "Invalid Transition: %s", 
                        array($e->getMessage()) );
            }
        }

        // set status flags
        if( $this->track && 
            (  
               !$starting_track || 
               $this->track->getRow() != $starting_track_row
            ) )
        {
            // There is now a track where there was none.
            $this->track_started = true;
        }
        
        if( $starting_track && 
            (
                !$this->track || 
                $this->track->getRow() != $starting_track_row
            ) )
        {
            // There is now no track where there was one.
            $this->track_stopped = true;
        }
        
        if(  $this->track && 
             $starting_track && 
            ($this->track->getRow() == $starting_track_row) &&
            ($this->track !== $starting_track) )
        {
            // The track on the deck is the same as before, but the object has been replaced.
            // (We signal this as an update on the principle that there's absolutely no reason
            // that SSL would log the same row twice unless there was new information in the new
            // row).
            
            $this->track_updated = true;
        }
    }    

    // Transition combinations
    
    public function transitionTo(SSLTrack $track)
    {
        $from = $this->getStatus();
        $to = $track->getStatus();
        
        L::level(L::INFO) && 
            L::log(L::INFO, __CLASS__, "deck %d transitioned from %s to %s with track %s", 
                array( $this->deck_number, $from, $to, $track->getTitle()) );
        
        switch($from)
        {
            case 'EMPTY':
                $this->transitionFromEmptyTo($track);
                break;
                
            case 'SKIPPED':
                $this->transitionFromSkippedTo($track);
                break;
                
            case 'PLAYED':
                $this->transitionFromPlayedTo($track);
                break;
                
            case 'NEW':
                $this->transitionFromNewTo($track);
                break;
                
            case 'PLAYING':
                $this->transitionFromPlayingTo($track);
                break;
                
            default:
                throw new InvalidArgumentException('Unknown FROM state "'. $from . '"');
        }
    }
    
    protected function transitionFromEmptyTo(SSLTrack $track)
    {
        $to = $track->getStatus();
        switch($to)
        {
            case 'NEW':
                $this->transitionFromEmptyToNew($track);
                break;
                
            case 'PLAYING':
                $this->transitionFromEmptyToNew($track);
                $this->transitionFromNewToPlaying($track);
                break;
                
            // The following transitions can happen if you start reading a history file
            // part way through, when the deck's not really empty; or you load a normalised history file
            
            case 'SKIPPED':
                $this->transitionFromEmptyToNew($track);
                $this->transitionFromNewToSkipped($track);
                break;
                
            case 'PLAYED':
                $this->transitionFromEmptyToNew($track);
                $this->transitionFromNewToPlaying($track);
                $this->transitionFromPlayingToPlayed($track);
                break;
                
            case 'EMPTY':
                throw new SSLInvalidTransitionException('Invalid transition from EMPTY to '. $to);
                
            default:
                throw new InvalidArgumentException('Unknown TO state "'. $to . '"');
        }
    }

    protected function transitionFromSkippedTo(SSLTrack $track)
    {
        $to = $track->getStatus();
        switch($to)
        {
            case 'NEW':
                $this->transitionFromSkippedToNew($track);
                break;
                
            case 'PLAYING':
                // a transition from SKIPPED straight to PLAYING can happen in preview-player mode
                $this->transitionFromSkippedToNew($track);
                $this->transitionFromNewToPlaying($track);
                break;

            // The following transitions can happen if you start reading a history file
            // part way through, when the deck's not really empty; or you load a normalised history file
                
            case 'SKIPPED':
                $this->transitionFromSkippedToNew($track);
                $this->transitionFromNewToSkipped($track);
                break;
                
            case 'PLAYED':
                $this->transitionFromSkippedToNew($track);
                $this->transitionFromNewToPlaying($track);
                $this->transitionFromPlayingToPlayed($track);
                break;
                
            case 'EMPTY':
                throw new SSLInvalidTransitionException('Invalid transition from SKIPPED to '. $to);
                
            default:                
                throw new InvalidArgumentException('Unknown TO state "'. $to . '"');
        }
    }

    protected function transitionFromPlayedTo(SSLTrack $track)
    {
        $to = $track->getStatus();
        switch($to)
        {
            case 'NEW':
                $this->transitionFromPlayedToNew($track);
                break;
                
            case 'PLAYING':
                // a transition from PLAYED straight to PLAYING can happen in preview-player mode
                $this->transitionFromPlayedToNew($track);
                $this->transitionFromNewToPlaying($track);
                break;
                
            // The following transitions can happen if you start reading a history file
            // part way through, when the deck's not really empty; or you load a normalised history file
            
            case 'PLAYED':
                $this->transitionFromPlayedToNew($track);
                $this->transitionFromNewToPlaying($track);
                $this->transitionFromPlayingToPlayed($track);
                break;
                
            case 'SKIPPED':
                $this->transitionFromPlayedToNew($track);
                $this->transitionFromNewToSkipped($track);
                break;
                
            case 'EMPTY':
                throw new SSLInvalidTransitionException('Invalid transition from PLAYED to '. $to);
                
            default:                
                throw new InvalidArgumentException('Unknown TO state "'. $to . '"');
        }
    }
    
    protected function transitionFromNewTo(SSLTrack $track)
    {
        $to = $track->getStatus();
        switch($to)
        {
            case 'SKIPPED':
                $this->transitionFromNewToSkipped($track);
                break;
                
            case 'PLAYING':
                $this->transitionFromNewToPlaying($track);
                break;

            case 'NEW':
                // a transition from NEW straight to NEW happens when an the non-playing
                // deck has its track changed
                $this->transitionFromNewToSkipped($track);
                $this->transitionFromSkippedToNew($track);
                break;
                
            case 'PLAYED':
                $this->transitionFromNewToPlaying($track);
                $this->transitionFromPlayingToPlayed($track);
                break;
                
            case 'EMPTY':
                throw new SSLInvalidTransitionException('Invalid transition from NEW to '. $to);
                
            default:                
                throw new InvalidArgumentException('Unknown TO state "'. $to . '"');        
        }
    }

    protected function transitionFromPlayingTo(SSLTrack $track)
    {
        $to = $track->getStatus();
        switch($to)
        {
            case 'PLAYED':
                $this->transitionFromPlayingToPlayed($track);
                break;

            case 'NEW':
                // a transition from PLAYING to NEW happens when the playing
                // deck has its track changed
                $this->transitionFromPlayingToPlayed($track);
                $this->transitionFromPlayedToNew($track);
                break;
                
            case 'SKIPPED':
                // this can happen if something that's marked PLAYING
                // is then marked as unplayed in the History List before
                // being changed
                $this->transitionFromNewToSkipped($track);
                break;
                
            case 'PLAYING':
                $this->transitionFromPlayingToPlayed($track);
                $this->transitionFromPlayedToNew($track);
                $this->transitionFromNewToPlaying($track);
                break;
                
            case 'EMPTY':
                throw new SSLInvalidTransitionException('Invalid transition from PLAYING to '. $to);
                
            default:
                throw new InvalidArgumentException('Unknown TO state "'. $to . '"');        
        }
    }

    // Base transitions
    
    protected function transitionFromSkippedToNew(SSLTrack $track)
    {
        $this->transitionFromEmptyToNew($track);
    }
    
    protected function transitionFromPlayedToNew(SSLTrack $track)
    {
        $this->transitionFromEmptyToNew($track);
    }

    protected function transitionFromEmptyToNew(SSLTrack $track)
    {
        $this->max_row = max($track->getRow(), $this->max_row);
        $this->track = $track;
        $this->status = $track->getStatus();
        $this->start_time = time();
        $this->end_time = null;
        // don't touch previous track 
    }
    
    protected function transitionFromNewToPlaying(SSLTrack $track)
    {
        $this->max_row = max($track->getRow(), $this->max_row);
        $this->track = $track;
        $this->status = $track->getStatus();
        // don't touch start time
        // don't touch end time
        // don't touch previous track
    }

    protected function transitionFromPlayingToPlayed(SSLTrack $track)
    {
        $this->max_row = max($track->getRow(), $this->max_row);
        $this->track = null;
        $this->status = $track->getStatus();
        // don't touch start time
        $this->end_time = time();
        $this->previous_track = $track; 
    }
    
    protected function transitionFromNewToSkipped(SSLTrack $track)
    {
        $this->max_row = max($track->getRow(), $this->max_row);
        $this->track = null;
        $this->status = $track->getStatus();
        // don't touch start time
        $this->end_time = time();
        // don't touch previous track
    }
    
}