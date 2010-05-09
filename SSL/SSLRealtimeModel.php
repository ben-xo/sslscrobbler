<?php

/**
 * The real time model responds to timer ticks via tick(), and changes to the tracks
 * on the decks via notify(). (Calling notify() does not imply a tick()).
 * 
 * This model is intended to simulate the decks in ScratchLIVE, but it does so by inferring
 * the status of the decks from information abotu TRACKS that are written to the history file.
 * Therefore it will not always match everything that happens in software exactly. However, 
 * it can infer quite a lot!
 * 
 * We model the decks to be in one of five states:
 * 
 * * EMPTY
 * 		No track has yet been loaded on to the deck. This is the starting state for the deck.
 * 		(This state is never returned to by any other transition).
 * 
 * * NEW		
 * 		A track was added to the deck, and may (or may not) be playing. At this point it's 
 * 		unknown	if it will count as a play or not for the purposes of the history (ScratchLive
 *      itself enters it into the history as 'unplayed' by default). However, we model it as 
 *      "Now Playing" for the purposes of scrobble-info after waiting NOW_PLAYING_POINT seconds,
 *      so as to keep it visually interesting...
 * 
 * * PLAYING 
 * 		The track on the deck is still playing, but will definitely count as "played" 
 * 		in the history. In ScratchLive, tracks transition from NEW to PLAYING when a new track 
 * 		is loaded on to any OTHER decks. At this point, ScratchLive marks the track as played.
 * 		We then trigger timers for the various scrobble rules, based on MIN_SCROBBLE_TIME and
 * 		SCROBBLE_POINT_DIVIDER.
 * 
 * * PLAYED	
 * 		Similar to EMPTY, but indicates that the previous track on the deck has finished playing.
 * 		This state is usually only reached if the track runs out, or the deck was unloaded while 
 * 		the state was PLAYING. If the track was simply replaced by a different track, you won't 
 * 		see this state.
 * 
 * * SKIPPED
 * 		Similar to EMPTY, but the previous track on the deck was not played for the purposes of 
 * 		the history. This state	is usually only reached if the deck was unloaded while the state 
 * 		was NEW (implying that you are just trying a track out in the mix, rather than playing it). 
 * 		If the track was simply replaced by a different track, you won't see this state.
 * 
 * @author ben
 */
class SSLRealtimeModel
{
    const MIN_SCROBBLE_TIME = 5;
    const NOW_PLAYING_POINT = 10;
    const SCROBBLE_POINT_DIVIDER = 16;
    
    /* @var $left_track  SSLTrack */
    /* @var $right_track SSLTrack */
    
    private $track   = array();
    private $previous = array();
    private $status = array( 'EMPTY', 'EMPTY', 'EMPTY');
    private $start = array();
    private $end = array();
    private $played = array();
    private $nowplaying = '--';
    private $nowplaying_deck = -1;
    private $scrobbled = '--';
    private $scrobbled_deck = -1;
    private $scrobbled_after = -1;
    private $next_timer = array(0, 0, 0);
    
    public function tick()
    {
        // timer based updates
        foreach(array_keys($this->track) as $deck)
        {
            if($this->next_timer[$deck] && $this->next_timer[$deck] <= time())
            {
                // timer for this track has elapsed!
                $this->next_timer[$deck] = 0;
                
                // switch on the status when the timer goes off
                // so we don't notify on scrobbler, now played etc multiple times.
                switch($this->status[$deck])
                {
                    case 'PLAYED':
                        if($this->track[$deck]->getLengthInSeconds() >= self::MIN_SCROBBLE_TIME)
                        {
                            $this->scrobbled = $this->getTrackTitle($deck);
                            $this->scrobbled_deck = $deck;
                            $this->next_timer[$deck] = 0;
                        } 
                        break;
                        
                    case 'SKIPPED':
                        if($deck == $this->nowplaying_deck)
                        {
                            $this->nowplaying = '--';
                            $this->nowplaying_deck = -1;
                            $this->next_timer[$deck] = 0;
                        }
                        break;
                        
                    case 'NEW':
                    case 'PLAYING':
                        
                        if($this->getPlaytimeInSeconds($deck) >= self::NOW_PLAYING_POINT)
                        {
                            $this->next_timer[$deck] = $this->start[$deck] + self::MIN_SCROBBLE_TIME;
                            if($deck == $this->nowplaying_deck)
                            {
                                $may_scrobble = $this->track[$deck]->getLengthInSeconds() >= self::MIN_SCROBBLE_TIME;
                                if($may_scrobble)
                                {
                                    $scrobble_point = floor($this->track[$deck]->getLengthInSeconds() / self::SCROBBLE_POINT_DIVIDER);
                                    $this->next_timer[$deck] = $this->start[$deck] + $scrobble_point;
                                    $playtime = $this->getPlaytimeInSeconds($deck);
                                    if( $playtime >= $scrobble_point )
                                    {
                                        $this->scrobbled = $this->getTrackTitle($deck);
                                        $this->scrobbled_deck = $deck;
                                        $this->scrobbled_after = $playtime;
                                        $this->next_timer[$deck] = 0;
                                    }                            
                                }
                            }
                            
                            $newest_deck = $deck; 
                            foreach(array_keys($this->track) as $d)
                            {
                                if($this->start[$d] > $this->start[$newest_deck])
                                {
                                    $newest_deck = $d;
                                }   
                            }
                            
                            if($deck == $newest_deck)
                            {
                                $this->nowplaying = $this->getTrackTitle($deck);
                                $this->nowplaying_deck = $deck;
                            } 
                        }
                        break;                    
                }
            }
        }
    }
    
    public function notify(SSLHistoryDiffDom $diff)
    {
        // all timer resets and changes go here
        $stop = array(); // timers to stop
        $start = array(); // timers to start
        foreach($diff->getTracks() as $track)
        {
            /* @var $track SSLTrack */
            $deck = $track->getDeck();

            // we don't model inserts     
            if(isset($deck))
            {
                $status = $track->getStatus();
                switch($status)
                {
                    case 'SKIPPED':
                        $this->stopDeck($deck, $track);
                        break;
                        
                    case 'PLAYED':
                        $this->previous[$deck] = $track;
                        $this->stopDeck($deck, $track);
                        break;
                        
                    case 'NEW':
                        $this->startDeck($deck, $track);
                        $start[$deck] = time() + self::NOW_PLAYING_POINT;
                        break;
                                                
                    case 'PLAYING':
                        break;
                        
                    default:
                }
                if(!isset($this->track[$deck]) || $this->track[$deck]->getRow() == $track->getRow()) 
                {
                    $this->status[$deck] = $status;
                }
            }
        }
        
        foreach($stop as $deck => $stop)
        {
            $this->next_timer[$deck] = 1;            
        }
        
        foreach($start as $deck => $start)
        {
            $this->next_timer[$deck] = $start;
        }
    }
    
    protected function stopDeck($track, $deck)
    {
        $this->end[$deck] = $track->getEndTime();
    }
    
    protected function startDeck($track, $deck)
    {
        $this->track[$deck] = $track;
        $this->start[$deck] = time();
    }
    
    
    protected function getPrevTrackTitle($deck)
    { 
        if(!isset($this->previous[$deck])) return ' ';
        return $this->previous[$deck]->getArtist() . ' - ' . $this->previous[$deck]->getTitle();
    }
        
    protected function getTrackTitle($deck)
    { 
        if(!isset($this->track[$deck])) return ' ';
        return $this->track[$deck]->getArtist() . ' - ' . $this->track[$deck]->getTitle();
    }
    
    protected function getLength($deck)
    {
        if(!isset($this->track[$deck])) return '--:--.--';
        return $this->track[$deck]->getLength();
    }

    protected function getPlaytimeInSeconds($deck)
    {
        if(!isset($this->start[$deck])) return 0;
        if($this->status[$deck] == 'PLAYED' || $this->status[$deck] == 'SKIPPED')
        {
            $endtime = $this->end[$deck];
        }
        else
        {
            $endtime = time();
        }
        return $endtime - $this->start[$deck];
    }
    
    
    protected function getPlaytime($deck)
    {
        if(!isset($this->start[$deck])) return '--:--';
        if($this->status[$deck] == 'PLAYED' || $this->status[$deck] == 'SKIPPED')
        {
            $endtime = $this->end[$deck];
        }
        else
        {
            $endtime = time();
        }
        $seconds = $endtime - $this->start[$deck];
        $time = sprintf("%02d:%02d", floor($seconds / 60) , $seconds % 60);
        return $time;
    }
        
    public function __toString()
    {
        $left_status = $this->status[1];
        $left_title = $this->getTrackTitle(1);
        $left_played = $this->getPlaytime(1);
        $left_length = $this->getLength(1);
        
        $right_status = $this->status[2];
        $right_title = $this->getTrackTitle(2);
        $right_played = $this->getPlaytime(2);
        $right_length = $this->getLength(2);

        $prev_left_title = $this->getPrevTrackTitle(1);
        $prev_right_title = $this->getPrevTrackTitle(2);
        
        return sprintf(
        	"\nLPREV:%-10.10s ==> %s\nRPREV:%-10.10s ==> %s", 
             '', $prev_left_title, '', $prev_right_title
        ) .
        sprintf(
        	"\nLEFT :%-10.10s [%s] [%s] [next event: %03ds] ==> %s\nRIGHT:%-10.10s [%s] [%s] [next event: %03ds] ==> %s", 
            $left_status, $left_played, $left_length,    $this->next_timer[1] ? $this->next_timer[1] - time() : 0, $left_title,
            $right_status, $right_played, $right_length, $this->next_timer[2] ? $this->next_timer[2] - time() : 0, $right_title
        ) .
        sprintf(
        	"\nNOW PLAYING: %s\nLAST SCROBBLED: %s after %d seconds", 
            $this->nowplaying, $this->scrobbled, $this->scrobbled_after
        )
        ;
    }
}