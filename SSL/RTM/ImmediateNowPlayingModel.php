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

class ImmediateNowPlayingModel implements NowPlayingObservable, SSLDiffObserver
{
    /**
     * @var SSLRepo
     */
    protected $factory;
    
    protected $now_playing_observers = array();

    public function __construct()
    {
        $this->factory = Inject::the(new SSLRepo());
    }
    
    public function addNowPlayingObserver(NowPlayingObserver $o)
    {    
        $this->now_playing_observers[] = $o;    
    }
    
    protected function notifyNowPlayingObservers(SSLTrack $track)
    {
        foreach($this->now_playing_observers as $o)
        {
            /* @var $o ScrobbleObserver */
            $o->notifyNowPlaying($track);
        }
    }

    public function notifyDiff(SSLHistoryDiffDom $dom)
    {
        $tracks = $dom->getTracks();
        foreach($tracks as $track)
        {
            if($track->isPlayed())
            {
                $this->notifyNowPlayingObservers($track);
            }
        }
    }
    
}