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
 * The PluginWrapper acts as a switch box that sits in between the HistoryReader
 * and various real plugins. It implements both sides of all of the plugin 
 * interfaces.
 * 
 * It is directly controlled by the PluginManager.
 * 
 * The main reason that PluginWrapper and PluginManager are separate classes
 * is because PluginManager acts as a TickObserver in order to control
 * plugins, but some of those plugins themselves may be TickObservers and
 * may be wrapped by the PluginWrapper.
 *
 * PluginWrapper instances are not intended to be created by anything other
 * than PluginManager.
 *
 * @author ben
 */
class PluginWrapper implements TickObservable, TickObserver,
                               SSLDiffObservable, SSLDiffObserver,
                               TrackChangeObservable, TrackChangeObserver,
                               NowPlayingObservable, NowPlayingObserver,
                               ScrobbleObservable, ScrobbleObserver
{
    /** @var SSLPlugin[] */
    protected $plugins = array();
    
    /* 
     * stuff from SSLPlugin. We do not implement SSLPlugin here, 
     * as that's what PluginManager does, but it delegates everything
     * to our plugins.
     */
    
    public function setOptions(array $options)
    {
        foreach($this->plugins as $plugin)
        {
            if($plugin instanceof SSLOptionablePlugin) {
                $plugin->setOptions($options);
            }
        }
    }
    
    public function addPlugin($id, SSLPlugin $plugin)
    {
        $this->plugins[$id] = $plugin;        
    }
    
    public function onSetup()
    {
        foreach($this->plugins as $plugin)
        {
            $plugin->onSetup();
        }
    }
    
    public function onStart()
    {
        $this->addAllObserversFromPlugins();
        
        foreach($this->plugins as $plugin)
        {
            $plugin->onStart();
        }
    }
    
    public function onStop()
    {
        foreach($this->plugins as $plugin)
        {
            $plugin->onStop();
        }
    }
    
    protected $to = array(); // TickObservers
    protected $do = array(); // DiffObservers
    protected $tco = array(); // TrackChangeObservers
    protected $npo = array(); // NowPlayingObservers
    protected $so = array(); // ScrobbleObservers

    /* The observable part */
    
    /*
     * In order to conform to the interfaces, any of the standard observers
     * can be added. However, typically observers are only added here from
     * $this->addPlugin(), which will wrap all Observers in their WithId
     * equivalent so that we can later extract them again.
     * 
     * Adding observers directly from the outside is not recommended.
     * Better to add most observers directly to the respective original 
     * event source.
     * 
     */
    
    public function addTickObserver(TickObserver $o)
    {
        if(!$o instanceof PluginWithId)
            throw new RuntimeException("PluginManager observers must all be instances of PluginWithId");
            
        $this->to[] = $o;
    }
    
    public function addDiffObserver(SSLDiffObserver $o)
    {
        if(!$o instanceof PluginWithId)
            throw new RuntimeException("PluginManager observers must all be instances of PluginWithId");
            
        $this->do[] = $o;
    }
    
    public function addTrackChangeObserver(TrackChangeObserver $o)
    {
        if(!$o instanceof PluginWithId)
            throw new RuntimeException("PluginManager observers must all be instances of PluginWithId");
            
        $this->tco[] = $o;
    }
    
    public function addNowPlayingObserver(NowPlayingObserver $o)
    {
        if(!$o instanceof PluginWithId)
            throw new RuntimeException("PluginManager observers must all be instances of PluginWithId");
            
        $this->npo[] = $o;
    }
    
    public function addScrobbleObserver(ScrobbleObserver $o)
    {
        if(!$o instanceof PluginWithId)
            throw new RuntimeException("PluginManager observers must all be instances of PluginWithId");
            
        $this->so[] = $o;
    }
    
    /* The observer part */
    public function notifyTick($seconds)
    {
        foreach($this->to as $t) // target
        {
            $t->notifyTick($seconds);
        }
    }

    public function notifyDiff(SSLHistoryDiffDom $changes)
    {
        foreach($this->do as $t) // target
        {
            $t->notifyDiff($changes);
        }
    }
    
    public function notifyTrackChange(TrackChangeEventList $events)
    {
        foreach($this->tco as $t) // target
        {
            $t->notifyTrackChange($events);
        }
    }
    
    public function notifyNowPlaying(?SSLTrack $track=null)
    {
        foreach($this->npo as $t) // target
        {
            $t->notifyNowPlaying($track);
        }
    }
    
    public function notifyScrobble(SSLTrack $track)
    {
        foreach($this->so as $t) // target
        {
            $t->notifyScrobble($track);
        }
    }

    public function startClock($interval, ?SignalHandler $sh = null, ?InputHandler $ih = null)
    {
        foreach($this->to as $t) // target
        {
            $t->startClock($interval, $sh, $ih);
        }
    }
    
    protected function addAllObserversFromPlugins()
    {
        foreach($this->plugins as $id => $plugin)
        {
            $observers = $plugin->getObservers();
            $oc = 0; // observer count
            foreach($observers as $o)
            {
                // we wrap every observer in a 'WithId' version
                // of the same observer which we can later use
                // to selectively pull matching observers back
                // out of the list.
                
                if($o instanceof TickObserver)        
                {
                    $this->addTickObserver(
                        new TickObserverWithId($o, $id)
                    );
                    $oc++;
                }
                
                if($o instanceof SSLDiffObserver)     
                {
                    $this->addDiffObserver(
                        new SSLDiffObserverWithId($o, $id)
                    );
                    $oc++;
                }
                
                if($o instanceof TrackChangeObserver) 
                {
                    $this->addTrackChangeObserver(
                        new TrackChangeObserverWithId($o, $id)
                    );
                    $oc++;
                }
                
                if($o instanceof NowPlayingObserver)  
                {
                    $this->addNowPlayingObserver(
                        new NowPlayingObserverWithId($o, $id)
                    );
                    $oc++;
                }
                
                if($o instanceof ScrobbleObserver)
                {
                    $this->addScrobbleObserver(
                        new ScrobbleObserverWithId($o, $id)
                    );
                    $oc++;
                }
            }
            
            L::level(L::INFO, __CLASS__) && 
                L::log(L::INFO, __CLASS__, "%d: %s installed", 
                    array($id, get_class($plugin)));
                    
            L::level(L::DEBUG, __CLASS__) && 
                L::log(L::DEBUG, __CLASS__, "%s brought %d observers to the table", 
                    array(get_class($plugin), $oc));
        }         
    }
}
