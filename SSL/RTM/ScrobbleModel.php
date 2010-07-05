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

class ScrobbleModel implements ScrobbleObservable, TrackChangeObserver
{
    protected $scrobble_observers = array();
    
    public function addScrobbleObserver(ScrobbleObserver $o)
    {    
        $this->scrobble_observers[] = $o;    
    }
    
    protected function notifyScrobbleObservers(SSLTrack $track)
    {
        foreach($this->scrobble_observers as $o)
        {
            /* @var $o ScrobbleObserver */
            $o->notifyScrobble($track);
        }
    }

    public function notifyTrackChange(TrackChangeEventList $events)
    {
        foreach($events as $event)
        {
            if($event instanceof TrackStoppedEvent)
            {
                $this->trackStopped($event->getTrack());
            }
        }
    }
    
    protected function trackStopped(SSLTrack $track)
    {
        $stm = $this->newScrobblerTrackModel($track);
        if($stm->isScrobblable())
        {
            L::level(L::INFO) &&
                L::log(L::INFO, __CLASS__, 'I reckon it\'s time to submit a scrobble for %s!', 
                    array($track->getFullTitle()));
                    
            $this->notifyScrobbleObservers($track);
        }
    }

    /**
     * Override me in tests.
     * 
     * @param SSLTrack $track
     */
    protected function newScrobblerTrackModel(SSLTrack $track)
    {
        return new ScrobblerTrackModel($track);
    }
}