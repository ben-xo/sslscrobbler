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
 * See the --replay option in HistoryReader for usage.
 */
class SSLHistoryFileReplayer implements SSLDiffObservable, TickObserver
{
    protected $diff_observers = array();
    
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
            $observer->notifyDiff($changes);
        }
    }
    
    public function notifyTick($seconds)
    {
        if(!$this->initialized) $this->initialize();
        
        if(isset($this->payloads[$this->pointer]))
        {
            L::level(L::DEBUG) &&
                L::log(L::DEBUG, __CLASS__, 'Yielding payload for timestamp %s (%s)', 
                    array( 
                        $this->payloads[$this->pointer]->getFirstTimestamp(), 
                        date("Y-m-d H:i:s", $this->payloads[$this->pointer]->getFirstTimestamp()) 
                        ));
                    
            $this->notifyDiffObservers($this->payloads[$this->pointer]);
            $this->pointer++;
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
                
        foreach($tracks as $track)
        {
            /* @var $track SSLTrack */
            if($track->getUpdatedAt() != $last_updated_at)
            {
                if(!empty($group)) 
                {
                    $this->payloads[] = new SSLHistoryDiffDom($group);
                }
                $last_updated_at = $track->getUpdatedAt();
                $group = array();   
            }
            
            $group[] = $track;
        }
        
        if(!empty($group)) 
        {
            $this->payloads[] = new SSLHistoryDiffDom($group);
        }
        
        L::level(L::DEBUG) &&
            L::log(L::DEBUG, __CLASS__, 'Divided tracks in %d groups', 
                array(count($this->payloads)));        
    }
}