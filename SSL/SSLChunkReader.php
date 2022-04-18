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

class SSLChunkReader
{
    /**
     * @var SSLRepo
     */
    protected $factory;
    
    protected $fp;
    
    public function __construct($fp)
    { 
        $this->factory = Inject::the(new SSLRepo());
        $this->fp = $fp;
    }
    
    public function getChunks()
    {
        $chunks = array();
        
        do
        {
            $chunk = $this->readChunk();
            if($chunk !== false)
            {
                $chunks[] = $chunk;
            }
        }
        while($chunk !== false);
        
        L::level(L::DEBUG, __CLASS__) &&
            L::log(L::DEBUG, __CLASS__, "Read %d chunks", 
                array( count($chunks)));
                
        return $chunks;
    }
    
    /**
     * Reads an SSL data file chunk from the filepointer. Chunks have an 8 byte header
     * consisting of 4 byte type string and 4 byte length.
     * 
     * @return SSLChunk
     */
    protected function readChunk()
    {
        $cp = $this->factory->newChunkParser();
        return $cp->parseFromFile($this->fp);
    }
}
