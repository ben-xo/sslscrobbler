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
 * Wrapper around a track which models how long the track has been playing for,
 * and various properties such as "Now Playing" or "Can Be Scrobbled".
 */
class ScrobblerTrackModel
{
    const SCROBBLE_DIVIDER = 2;
    const SCROBBLE_MIN = 30;
    const NOW_PLAYING_MIN = 30;
    
    /**
     * @var SSLTrack
     */
    protected $track;
    protected $scrobble_point;
    protected $end_point;
    
    protected $playtime = 0;
    protected $passed_now_playing_point = false;
    protected $passed_scrobble_point = false;
    protected $passed_end = false;
    
    public function __construct(SSLTrack $track)
    {
        $this->end_point = $track->getLengthInSeconds(SSLTrack::TRY_HARD);
        $this->scrobble_point = $this->end_point / self::SCROBBLE_DIVIDER;
        $this->setTrack($track);
    }
    
    public function update(SSLTrack $track)
    {
        if($track->getRow() == $this->track->getRow())
        {
            $this->setTrack($track);
        }
    }
    
    protected function setTrack(SSLTrack $track)
    {
        $this->track = $track;
        if($track->getPlayTime() !== null)
        {
            // don't update the playtime if the Track model itself doesn't know it
            $this->playtime = $track->getPlayTime();
        }
        $this->elapse(0);
    }
    
    public function elapse($seconds)
    {
        $this->playtime = intval($this->playtime) + intval($seconds);

        $was_passed_now_playing_point = $this->passed_now_playing_point;
        $was_passed_scrobble_point = $this->passed_scrobble_point;
        $was_ended = $this->passed_end;
        
        $this->passed_now_playing_point = ($this->playtime >= self::NOW_PLAYING_MIN);
        $this->passed_scrobble_point = ($this->playtime >= $this->scrobble_point);
        $this->passed_end = ($this->playtime >= $this->end_point);
        
        if($this->passed_now_playing_point && !$was_passed_now_playing_point)
        {
            L::level(L::INFO, __CLASS__) &&
                L::log(L::INFO, __CLASS__, '%s passed now playing point', 
                    array($this->track->getFullTitle()));
        }

        if($this->passed_scrobble_point && !$was_passed_scrobble_point)
        {
            L::level(L::INFO, __CLASS__) &&
                L::log(L::INFO, __CLASS__, '%s passed scrobble point', 
                    array($this->track->getFullTitle()));
        }

        if($this->passed_end && !$was_ended)
        {
            L::level(L::INFO, __CLASS__) &&
                L::log(L::INFO, __CLASS__, '%s passed end point', 
                    array($this->track->getFullTitle()));
        }
    }
    
    /**
     * We model a track as potentially "Now Playing" if it's been on the deck for 
     * "NOW_PLAYING_MIN" seconds, and has not yet been on the deck for long enough
     * that it could have ended. (SSL doesn't give us enough info to say if it's 
     * really playing or not.)
     * 
     * Note that edge cases, such as what's "Now Playing" when there's only 1 song
     * on the deck, are handled elsewhere (in NowPlayingModel)
     */
    public function isNowPlaying()
    {
        return $this->passed_now_playing_point && !$this->passed_end;
    }
    
    public function isScrobblable()
    {
        return $this->track->getPlayed() && $this->passed_scrobble_point;
    }
    
    public function isEnded()
    {
        return $this->passed_end;
    }
    
    public function getRow()
    {
        return $this->track->getRow();
    }
    
    /**
     * @return SSLTrack
     */
    public function getTrack()
    {
        return $this->track;
    }
}
