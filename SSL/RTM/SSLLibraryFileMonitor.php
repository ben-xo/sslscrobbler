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

class SSLLibraryFileMonitor implements /* SSLDiffObservable, */ SSLDiffDelegate
{
    public function __construct($filename, DiffMonitor $monitor)
    {
        $factory = Inject::the(new SSLRepo());
        $monitor->setFilename($filename);
        $monitor->setPrototype($factory->newLibraryDom());
        $monitor->setDiffDelegate($this);
    }
    
    /* // TODO: a version of this for index file diffs
    protected $diff_observers = array();
    
    public function addDiffObserver(SSLDiffObserver $observer)
    {
        $this->diff_observers[] = $observer;
    }
    */
    
    public function onDiff(SSLDom $changes)
    {
        if(!$changes instanceof SSLLibraryDom)
            throw new RuntimeException(__CLASS__ . ' can only notify about SSLLibraryDom. Got a ' . get_class($changes));
              
        /*
        foreach($this->diff_observers as $observer)
        {
            $observer->notifyDiff($changes);
        }
        */
    }
}