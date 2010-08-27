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

class SSLHistoryFileDiffMonitor implements SSLDiffObservable, TickObserver
{
    /**
     * @var SSLRepo
     */
    protected $factory;

    protected $diff_observers = array();
    
    protected $filename;
    
    /**
     * @var SSLHistoryDiffDom
     */
    protected $tree;

    public function __construct($filename)
    {
        $this->factory = Inject::the(new SSLRepo());
        $this->filename = $filename;
        $this->tree = $this->factory->newHistoryDom(); // start on empty
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
    
    public function dump()
    {
        $tree = $this->read($this->filename);
        
        // Sets up all the right parsing.
        //
        // There's no particular reason to assume that e.g. all Adat chunks 
        // encountered are going to be tracks, so the assumption-of-trackiness
        // is only made in the SSLHistoryDom and a Track Parser passed in to the
        // Adat chunk during the getTracks() call on the SSLHistoryDom.
        //
        // Basically, what I'm saying, is that without this line you'll just get
        // hexdumps, which is not very exciting.
        $tree->getTracks(); 
        
        // After the parsing has occurred, we get much more exciting debug output.
        echo $tree;
        
        echo "Memory usage: " . number_format(memory_get_peak_usage()) . " bytes\n";
    }
    
    public function notifyTick($seconds)
    {
        $new_tree = $this->read($this->filename);
        $changed = $new_tree->getNewOrUpdatedTracksSince($this->tree);
        if(count($changed->getTracks()) > 0 )
        {
            $this->notifyDiffObservers($changed);
            $this->tree = $new_tree;
        }
    }

    /**
     * @return SSLHistoryDom
     */
    protected function read($filename)
    {
        $parser = $this->factory->newParser( $this->factory->newHistoryDom() );
        $tree = $parser->parse($filename);
        $parser->close();
        return $tree;
    }
}