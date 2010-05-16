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

require_once 'SSLInvalidTransitionException.php';

/**
 * Models one (of several, usually 2) decks in Serato SSL.
 * 
 * @see SSLRealtimeModel for more info on the statuses, transitions and their meanings.
 * @author ben
 *
 */
class SSLRealtimeModelDeck
{
    protected $deck_number;
    private $debug = true;
    
    /**
     * @var SSLTrack
     */
    protected $track;
    
    /**
     * @var SSLTrack
     */
    protected $previous_track;
    
    protected $status, $start_time, $end_time;
    
    public function __construct($deck_number)
    {
        $this->deck_number = $deck_number;
    }

    public function setDebug($debug)
    {
        $this->debug = $debug;
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
    
    public function isStopped()
    {
        switch($this->status) {
            case 'NEW':
            case 'PLAYING':
                return false;
                
            default:
                return true;
        }
    }
    
    // Mutators
    
    /**
     * Notify the deck of a group of changed written to the History File.
     * 
     * SSL batches writes of track info, but doesn't output them in the 
     * natural transition order - that is, sometimes information about the following
     * song appears in the log before closing info on the previous song, as the
     * History File is track oriented, not deck oriented.
     * 
     * During notify(), we reorder this information into an order that's
     * transition compatible.
     * 
     * As diffs usually only come during track load, change or eject, it would be
     * abnormal to see information about more than 2 tracks on a single deck here,
     * and abnormal for neither of them to be the one currently on the deck.
     * Therefore, we check the ordering here and bump the current track to first place.
     * 
     * @param SSLHistoryDiffDom $diff
     */
    public function notify(SSLHistoryDiffDom $diff)
    {
        $my_tracks = array();
        foreach($diff->getTracks() as $track)
        {
            if($track->getDeck() == $this->deck_number)
            {
                // track notification for this deck!
                $my_tracks[$track->getRow()] = $track;
                $this->debug && print "DEBUG: SSLRealtimeModelDeck::notify(): Saw " . $track->getTitle() . " in diff (row " . $track->getRow(). ")\n";
            }
        }
        
        ksort($my_tracks); // sort in natural 'history' order
        
        foreach($my_tracks as $track)
        {
            try
            {
                $this->transition($this->getStatus(), $track->getStatus(), $track);
            } 
            catch(SSLInvalidTransitionException $e)
            {
                $this->debug && print "SSLRealtimeModelDeck::notify(): " . $e->getMessage() . "\n";
            }
        }
    }    
        
    public function start(SSLTrack $track)
    {
        $this->transition($this->getStatus(), 'NEW', $track);    
    }
    
    public function stop()
    {
        if($this->status == 'NEW')
        {
            $this->transitionFromNewToSkipped();
        }
        elseif($this->status == 'PLAYING')
        {
            $this->transitionFromPlayingToPlayed();
        }
        
        // else: already stopped 
    }
    
    public function transitionFromEmptyToNew(SSLTrack $track)
    {
        $this->track = $track;
        $this->status = 'NEW';
        $this->start_time = time();
        $this->end_time = null;
    }
    
    public function transitionFromSkippedToNew(SSLTrack $track)
    {
        $this->transitionFromEmptyToNew($track); 
    }
    
    public function transitionFromPlayedToNew(SSLTrack $track)
    {
        $this->previous_track = $this->track; 
        $this->transitionFromEmptyToNew($track);
    }
    
    public function transitionFromNewToSkipped()
    {
        $this->status = 'SKIPPED';
        $this->end_time = time();
    }

    public function transitionFromNewToPlaying()
    {
        $this->status = 'PLAYING';
    }

    public function transitionFromPlayingToPlayed()
    {
        $this->status = 'PLAYED';
        $this->end_time = time();
    }
    
    /**
     * Transitions combinations
     * @param $from
     * @param $to
     */
    public function transition($from, $to, SSLTrack $track)
    {
        $this->debug && print "DEBUG: SSLRealtimeModelDeck::transition() deck {$this->deck_number} $from to $to with track " . $track->getTitle() . "\n";
        
        switch($from)
        {
            case 'EMPTY':
                $this->transitionFromEmptyTo($to, $track);
                break;
                
            case 'SKIPPED':
                $this->transitionFromSkippedTo($to, $track);
                break;
                
            case 'PLAYED':
                $this->transitionFromPlayedTo($to, $track);
                break;
                
            case 'NEW':
                $this->transitionFromNewTo($to, $track);
                break;
                
            case 'PLAYING':
                $this->transitionFromPlayingTo($to, $track);
                break;
                
            default:
                throw new InvalidArgumentException('Unknown FROM state "'. $from . '"');
        }
    }
    
    public function transitionFromEmptyTo($to, SSLTrack $track)
    {
        switch($to)
        {
            case 'NEW':
                $this->transitionFromEmptyToNew($track);
                break;
                
            case 'PLAYING':
                $this->transitionFromEmptyToNew($track);
                $this->transitionFromNewToPlaying();
                break;
                
            case 'SKIPPED':
            case 'PLAYED':
                // these transitions can happen if you start reading a history file
                // part way through, when the deck's not really empty.
            case 'EMPTY':
                throw new SSLInvalidTransitionException('Invalid transition from EMPTY to '. $to);
                
            default:
                throw new InvalidArgumentException('Unknown TO state "'. $to . '"');
        }
    }

    public function transitionFromSkippedTo($to, SSLTrack $track)
    {
        switch($to)
        {
            case 'NEW':
                $this->transitionFromSkippedToNew($track);
                break;
                
            case 'PLAYING':
                // a transition from SKIPPED straight to PLAYING can happen in preview-player mode
                $this->transitionFromSkippedToNew($track);
                $this->transitionFromNewToPlaying();
                break;
                
            case 'EMPTY':
            case 'SKIPPED':
            case 'PLAYED':
                throw new SSLInvalidTransitionException('Invalid transition from SKIPPED to '. $to);
                
            default:                
                throw new InvalidArgumentException('Unknown TO state "'. $to . '"');
        }
    }

    public function transitionFromPlayedTo($to, SSLTrack $track)
    {
        switch($to)
        {
            case 'NEW':
                $this->transitionFromPlayedToNew($track);
                break;
                
            case 'PLAYING':
                // a transition from PLAYED straight to PLAYING can happen in preview-player mode
                $this->transitionFromPlayedToNew($track);
                $this->transitionFromNewToPlaying();
                break;
                
            case 'PLAYED':
            case 'EMPTY':
            case 'SKIPPED':
                throw new SSLInvalidTransitionException('Invalid transition from PLAYED to '. $to);
                
            default:                
                throw new InvalidArgumentException('Unknown TO state "'. $to . '"');
        }
    }
    
    public function transitionFromNewTo($to, SSLTrack $track)
    {
        switch($to)
        {
            case 'SKIPPED':
                $this->transitionFromNewToSkipped();
                break;
                
            case 'PLAYING':
                $this->transitionFromNewToPlaying();
                break;

            case 'NEW':
                // a transition from NEW straight to NEW happens when an the non-playing
                // deck has its track changed
                $this->transitionFromNewToSkipped();
                $this->transitionFromSkippedToNew($track);
                break;
                
            case 'EMPTY':
            case 'PLAYED':
                throw new SSLInvalidTransitionException('Invalid transition from NEW to '. $to);
                
            default:                
                throw new InvalidArgumentException('Unknown TO state "'. $to . '"');        
        }
    }

    public function transitionFromPlayingTo($to, SSLTrack $track)
    {
        switch($to)
        {
            case 'PLAYED':
                $this->transitionFromPlayingToPlayed();
                break;

            case 'NEW':
                // a transition from PLAYING to NEW happens when the playing
                // deck has its track changed
                $this->transitionFromPlayingToPlayed();
                $this->transitionFromPlayedToNew($track);
                break;
                
            case 'SKIPPED':
                // this can happen if something that's marked PLAYING
                // is then marked as unplayed in the History List before
                // being changed
                $this->transitionFromNewToSkipped($track);
                break;
                
            case 'EMPTY':
            case 'PLAYING':
                throw new SSLInvalidTransitionException('Invalid transition from PLAYING to '. $to);
                
            default:
                throw new InvalidArgumentException('Unknown TO state "'. $to . '"');        
        }
    }    
}