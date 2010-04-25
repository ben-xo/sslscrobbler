<?php

class SSLRealtimeModel
{
    const MIN_SCROBBLE_TIME = 10;
    const NOW_PLAYING_POINT = 15;
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
                $this->next_timer[$deck] = 0;
                
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
                        $this->end[$deck] = $track->getEndTime();
                        break;
                        
                    case 'NEW':
                        $this->track[$deck] = $track;
                        $this->start[$deck] = time();
                        if(!$this->next_timer[$deck])
                        {
                            $this->next_timer[$deck] = time() + self::NOW_PLAYING_POINT;
                        }
                        break;
                        
                    case 'PLAYED':
                        $this->previous[$deck] = $track;
                        $this->end[$deck] = $track->getEndTime();
                        break;
                        
                    case 'PLAYING':
                        $this->track[$deck] = $track;
                        if(!$this->next_timer[$deck])
                        {
                            $this->next_timer[$deck] = time() + self::NOW_PLAYING_POINT;
                        }
                        break;
                        
                    default:
                }
                if(!isset($this->track[$deck]) || $this->track[$deck]->getRow() == $track->getRow()) 
                {
                    $this->status[$deck] = $status;
                }
            }
        }        
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