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

class SSLParser
{
    /**
     * @var SSLRepo
     */
    protected $factory;
    
    /**
     * File pointer
     */
    protected $fp;
    
    /**
     * Prototype DOM which is cloned for new chunks to be read into.
     * 
     * @var SSLDom
     */
    protected $dom_prototype;

    /**
     * You should construct the parser with a $dom of the type that you wish to 
     * fill in from the parsed chunks (different DOM types have different parsing
     * semantics for the chunk types). For example, you probably want to use the
     * SSLHistoryDom for reading the history file, as this DOM understands that
     * ADAT chunks are SSLTracks. 
     * 
     * @param SSLDom $dom
     */
    public function __construct(SSLDom $dom = null)
    {
        $this->factory = Inject::the(new SSLRepo());
        
        if(isset($dom))
        {
            $this->dom_prototype = $dom;
        }
        else
        {
            $this->dom_prototype = $this->factory->newDom();  
        }
        
    }
    
    /**
     * Open a file and read its chunks.
     * 
     * @return SSLDom
     */
    public function parse($filename)
    {
        $this->open($filename);
        return $this->readChunks();
    }
        
    public function open($filename)
    {
        $this->fp = fopen($filename, 'r');
        if(!$this->fp)
        {
            throw new RuntimeException("Opening file {$filename} failed.");
        }
    }
    
    public function close()
    {
        fclose($this->fp);
    }

    public function readChunks()
    {
        if(!$this->fp)
        {
            throw new RuntimeException("readChunks() called with no file open.");
        }
        
        $reader = $this->factory->newChunkReader($this->fp);
        $dom = clone $this->dom_prototype;
        $dom->addChunks( $reader->getChunks() );
        return $dom;
    }
    
}