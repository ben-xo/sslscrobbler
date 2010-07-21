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

class TickSource implements TickObservable
{
    protected $tick_observers = array();
    
    public function addTickObserver(TickObserver $o)
    {
        $this->tick_observers[] = $o;
    }
    
    protected function notifyTickObservers($seconds)
    {
        foreach($this->tick_observers as $observer)
        {
            $observer->notifyTick($seconds);
        }
    }
    
    public function startClock($interval, SignalHandler $sh = null, InputHandler $ih = null)
    {
        L::level(L::DEBUG) && 
            L::log(L::DEBUG, __CLASS__, "Clock Started, interval %s", 
                array($interval));
        
        $elapsed = 0.0;
        $start_time = microtime(true);
        
        $continue = true;
        while($continue)
        {
            L::level(L::DEBUG) && 
                L::log(L::DEBUG, __CLASS__, "Tick %s seconds", 
                    array($elapsed));
                    
            $this->notifyTickObservers($elapsed);
            
            $processing_time = microtime(true) - $start_time;
            
            if($processing_time > $interval)
            {
                L::level(L::WARNING) && 
                    L::log(L::WARNING, __CLASS__, "Notification took %s, which is longer than interval %s",
                        array($processing_time, $interval));
            }
            else
            {
                $this->sleep($interval - $processing_time);
            }
            
            $end_time = microtime(true);
            $elapsed = $end_time - $start_time;
            $start_time = $end_time;
            
            // test for exit condition via CTRL-C / SIGTERM
            if($sh)
            {
                $sh->test();
                $continue = ($continue && !$sh->shouldExit());
            }
            
            // test for exit condition via User Input
            if($ih)
            {
                $ih->process();
                $continue = ($continue && !$ih->shouldExit());  
            } 
        }
    }
    
    protected function sleep($seconds)
    {
        return usleep($seconds * 1000000);   
    }
}