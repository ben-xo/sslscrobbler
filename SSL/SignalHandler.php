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

class SignalHandler
{
    private $should_exit = false;
    
    public function install()
    {
        if(function_exists('pcntl_signal'))
        {
            pcntl_signal(SIGINT, array($this, 'handle'));
        }
        else
        {
            L::level(L::WARNING, __CLASS__) &&
                L::log(L::WARNING, __CLASS__, 'PCNTL extension not installed! Cannot trap Ctrl-C / SIGKILL',
                    array());           
        }
    }
    
    /**
     * @return false if we caught an exit signal, true otherwise
     */
    public function shouldExit()
    {
        return $this->should_exit;
    }
    
    /**
     * Catch signals.
     */
    public function test()
    {
        if(version_compare(PHP_VERSION, '5.3', '<'))
        {
            // XXX: Not sure if this works, need to test.
            declare(ticks = 1) {
                $nop = true;
            }
        }
        else
        {
            if(function_exists('pcntl_signal_dispatch'))
            {
                pcntl_signal_dispatch();
            }
        }
    }
    
    protected function handle($signal)
    {     
        switch ($signal)
        {          
            case SIGINT:
                L::level(L::INFO, __CLASS__) &&
                    L::log(L::INFO, __CLASS__, 'Caught SIGINT',
                        array());
                        
                $this->should_exit = true;
                break;
        }
    }    
}
