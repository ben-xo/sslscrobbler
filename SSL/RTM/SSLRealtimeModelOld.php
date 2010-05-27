<?php

class SSLRealtimeModelOLD
{
    const MIN_SCROBBLE_TIME = 5;
    const NOW_PLAYING_POINT = 10;
    const SCROBBLE_POINT_DIVIDER = 16;
    
    private $track   = array();
    private $previous = array();
    private $status = array();
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
                switch($this->getStatus($deck))
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
    
    protected function getStatus($deck)
    {
        if(!isset($this->status[$deck]))
            return 'EMPTY';
            
        return $this->status[$deck];
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
    
    
}