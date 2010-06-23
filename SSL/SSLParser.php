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
    protected $fp;
    
    /**
     * @var SSLDom
     */
    protected $dom;

    public function __construct(SSLDom $dom = null)
    {
        if(isset($dom))
        {
            $this->dom = $dom;
        }
        else
        {
            $this->newDom();        
        }
    }
    
    /**
     * @return SSLDom
     */
    public function parse($filename)
    {
        $this->fp = fopen($filename, 'r');
        if(!$this->fp)
            throw new RuntimeException("Opening file $filename failed.");

        $this->readChunks();
        
        return $this->dom;
    }
    
    public function close()
    {
        fclose($this->fp);
    }

    protected function readChunks()
    {
        $reader = new SSLChunkReader($this->fp);
        $this->dom->addChunks( $reader->getChunks() );
    }
    
    protected function newDom()
    {
        $this->dom = new SSLDom();
    }
}