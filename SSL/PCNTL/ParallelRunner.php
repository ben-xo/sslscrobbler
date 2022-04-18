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

class ParallelRunner
{
    public function spinOff(ParallelTask $t, $task='task')
    {
        if(function_exists('pcntl_fork') && !defined('SINGLE_THREADED'))
        { 
            L::level(L::DEBUG, __CLASS__) && 
                L::log(L::DEBUG, __CLASS__, "Forking %s...", 
                    array($task));
                    
            $pid = pcntl_fork();
            if($pid)
            {
                // parent
                if($pid == -1)
                {            
                    L::level(L::WARNING, __CLASS__) && 
                        L::log(L::WARNING, __CLASS__, "Fork failed! Running %s single-threaded...", 
                            array($task));
                
                    $t->run();
                    return false;
                }
                
                return $pid;
            }
            else
            {
                // child
                $t->run();
                exit;
            }
            
        }
        else
        {
            L::level(L::DEBUG, __CLASS__) && 
                L::log(L::DEBUG, __CLASS__, "PCNTL not supported. Running %s single-threaded. If %s is slow, it will block the app until it is finished.", 
                    array($task, $task));
                    
            $t->run();
        }
        
        return false;
    }    
}
