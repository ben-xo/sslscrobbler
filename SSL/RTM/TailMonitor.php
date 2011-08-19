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
 * Version of the diff monitor which keeps the file open and 
 * keeps reading from the end, rather than diffing the entire file each time.
 * 
 * @author ben
 */
class TailMonitor extends DiffMonitor
{
    /**
     * @var SSLParser
     */
    protected $tail_parser;
        
    public function notifyTick($seconds)
    {
        $this->checkForNewFilename();
        
        if(!isset($this->tail_parser))
        {
            $this->tail_parser = $this->newTailParser($this->filename);
        }
        
        $dom = $this->tail_parser->readChunks();
        $changed = $dom->getNewOrUpdatedTracksSince( $this->newDom() );
        
        if(count($changed->getTracks()) > 0)
        {
            $this->onDiff($changed);
        }
    }

    protected function checkForNewFilename()
    { 
        $got_new_file = parent::checkForNewFilename();
        if($got_new_file)
        {
            unset($this->tail_parser);
        }
        return $got_new_file;
    }
        
    protected function newTailParser($filename)
    {
        $parser = $this->factory->newParser( $this->newDom() );
        $parser->open($filename);
        return $parser;
    }
    
    public function close()
    {
        if(isset($this->tail_parser))
        {
            $this->tail_parser->close();
        }
    }
    
    public function __destruct()
    {
        $this->close();
    }
}