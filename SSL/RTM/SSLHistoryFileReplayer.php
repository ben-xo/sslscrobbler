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
 * History file replayer yields one row on every tick event. Designed for use
 * with the CrankHandle (which ticks on input, rather than on a timer) for
 * testing transitions in a problem history file one at a time.
 * 
 * See the --post-process option in HistoryReader for usage.
 * 
 * Note that this is both TickObserver and TickObservable. It's designed to observe
 * either a manual or instant tick, and to emit a tick that replays what was logged
 * in the history file.
 */
class SSLHistoryFileReplayer implements SSLDiffObservable, TickObserver, ExitObservable, TickObservable
{
    protected $diff_observers = array();
    protected $exit_observers = array();
    protected $tick_observers = array();
    
    protected $filename;
    
    /**
     * @var SSLHistoryDiffDom
     */
    protected $tree;
    
    /**
     * @var array of SSLHistoryDiffDom
     */
    protected $payloads = array();
    
    protected $initialized = false;
    
    protected $pointer = 0;

    public function __construct($filename)
    {
        $this->filename = $filename;
        $this->tree = new SSLHistoryDom(); // start on empty
    }
    
    public function addDiffObserver(SSLDiffObserver $observer)
    {
        $this->diff_observers[] = $observer;
    }
    
    protected function notifyDiffObservers(SSLHistoryDiffDom $changes)
    {
        foreach($this->diff_observers as $observer)
        {
            /* @var $observer SSLDiffObserver */
            $observer->notifyDiff($changes);
        }
    }
    
    public function addExitObserver(ExitObserver $observer)
    {
        $this->exit_observers[] = $observer;
    }
    
    protected function notifyExitObservers()
    {
        foreach($this->exit_observers as $observer)
        {
            /* @var $observer ExitObserver */
            $observer->notifyExit();
        }
    }
    
    public function addTickObserver(TickObserver $observer)
    {
        $this->tick_observers[] = $observer;
    }
    
    protected function notifyTickObservers($seconds)
    {
        L::level(L::DEBUG) &&
            L::log(L::DEBUG, __CLASS__, 'Pseudo tick %d seconds', 
                array($seconds));
                
        foreach($this->tick_observers as $observer)
        {
            /* @var $observer TickObserver */
            $observer->notifyTick($seconds);
        }
    }    
    
    public function startClock($interval, SignalHandler $sh = null, InputHandler $ih = null)
    {
        // doesn't do anything on the file replayer
        $this->notifyTickObservers(0);        
    }
    
    public function notifyTick($seconds)
    {
        if(!$this->initialized) $this->initialize();
        
        $last_timestamp = 0;
        $last_payload = count($this->payloads) - 1;
        $pointer = 0;
        
        foreach($this->payloads as $payload)
        {
            $payload_timestamp = $payload->getFirstTimestamp();
            L::level(L::DEBUG) &&
                L::log(L::DEBUG, __CLASS__, 'Yielding payload for timestamp %s (%s)', 
                    array( 
                        $payload_timestamp, 
                        date("Y-m-d H:i:s", $payload_timestamp) 
                        ));
            
            $this->notifyTickObservers($payload_timestamp - $last_timestamp);
            $this->notifyDiffObservers($payload);
            $last_timestamp = $payload_timestamp;
            $pointer++;
        }
        
        $this->notifyTickObservers(300);
        
        if($pointer >= $last_payload)
        {
            // exit app on EOF.
            $this->notifyExitObservers();
        }
    }

    /**
     * @return SSLHistoryDom
     */
    protected function read($filename)
    {
        $parser = new SSLParser(new SSLHistoryDom());
        $tree = $parser->parse($filename);
        $parser->close();
        return $tree;
    }
    
    /**
     * Split the contents of the file into SSLTrack rows grouped by timestamp.
     */
    protected function initialize()
    {
        $tree = $this->read($this->filename);
        $tracks = $tree->getTracks();
        $this->groupByTimestamp($tracks);
        $this->initialized = true;
    }
    
    protected function groupByTimestamp(array $tracks)
    {
        $last_updated_at = 0;
        $group = array();
        
        L::level(L::DEBUG) &&
            L::log(L::DEBUG, __CLASS__, 'Found %d tracks in file', 
                array(count($tracks)));

        //usort($tracks, array($this, 'timestampSort'));
                
        foreach($tracks as $track)
        {
            /* @var $track SSLTrack */
            if($track->getUpdatedAt() != $last_updated_at)
            {
                if(!empty($group)) 
                {
                    L::level(L::DEBUG) &&
                        L::log(L::DEBUG, __CLASS__, 'Entries found at %s', 
                            array(date('Y-m-d H:i:s', $last_updated_at)));
                        
                    $this->payloads[] = new SSLHistoryDiffDom($group);
                }
                $last_updated_at = $track->getUpdatedAt();
                $group = array();   
            }
                            
            $group[] = $track;
        }
                
        if(!empty($group)) 
        {
            L::level(L::DEBUG) &&
                L::log(L::DEBUG, __CLASS__, 'Entries found at %s', 
                    array(date('Y-m-d H:i:s', $last_updated_at)));

            $this->payloads[$last_updated_at] = new SSLHistoryDiffDom($group);
        }
        
        L::level(L::DEBUG) &&
            L::log(L::DEBUG, __CLASS__, 'Divided tracks in %d groups', 
                array(count($this->payloads)));        
    }
    
    private function timestampSort(SSLTrack $a, SSLTrack $b)
    {
        $a_ts = $a->getUpdatedAt();
        $b_ts = $b->getUpdatedAt();
        
        if($a_ts > $b_ts) return 1;
        if($a_ts == $b_ts) return 0;
        return -1;
    }
}