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
 * A Scenario is a "playlist" of tick and diff events, along with expectations on events triggered.
 * The playlist is usually loaded in a subclass (e.g. ScenarioFile).
 * 
 * Scenarios are used for validating the behaviour of the other parts of the system. The Scenario
 * acts as both a source and sink of events, generating Ticks and Diffs, and receiving
 * NowPlaying and Scrobble events.
 * 
 * Usually, the events are filtered through a realtime model (RTM) stack such as the one used in
 * the main SSLScrobbler application. It is ScenarioEngine's job to wire this up.
 * 
 * @author ben
 */
class Scenario implements TickObservable, SSLDiffObservable, NowPlayingObserver, ScrobbleObserver 
{   
    protected $scenario_playlist = array();
    
    protected $tick_observers = array();
    protected $diff_observers = array();
    
    public function next()
    {
        
    }
    
    public function addTickObserver(TickObserver $o)
    {
        $this->tick_observers[] = $o;
    }
    
    public function addDiffObserver(SSLDiffObserver $o)
    {
        $this->diff_observers[] = $o;
    }
    
    public function startClock($interval)
    {
        $this->sendTick(0);
    }
    
    public function notifyNowPlaying(SSLTrack $track=null)
    {
        
    }
    
    public function notifyScrobble(SSLTrack $track=null)
    {
    
    }
}