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
 * Either parses from data it was constructed with, and an internal string pointer,
 * or from a file pointer that's passed to parseFromFile().
 */
class SSLChunkParser
{
    protected $ptr = 0;
    protected $eos = false;
    protected $data;
    protected $data_len;
    
    public function __construct($data=null)
    {
        $this->data = $data;
        $this->data_len = strlen($data);
    }
    
    public function hasMore()
    {
        return !$this->eos;
    }
    
    public function parse()
    {
        $string = $this->data;
        
        $header_bin = substr($string, $this->ptr, 8);
        
        $length_read = strlen($header_bin);
        if($length_read < 8)
            throw new RuntimeException("Could not parse string ($length_read bytes is too short to contain a header)"); 

        list($chunk_type, $chunk_size) = $this->parseHeader($header_bin);
                
        $body_bin = substr($string, $this->ptr + 8, $chunk_size);
        
        $chunk = $this->newChunk($chunk_type, $body_bin);
        
        $this->ptr += 8 + $chunk_size;
        if($this->ptr >= $this->data_len) 
        {
            $this->eos = true;
        }
        
        return $chunk;
    }
    
    public function parseFromFile($fp)
    {
        $header_bin = fread($fp, 8);
                
        $length_read = strlen($header_bin);
        if($length_read == 0)
            return false; // if we read an exact chunk, it's not an 'eof'.
            
        if($length_read < 8)
            throw new OutOfBoundsException("No more data (read $length_read bytes)");

        if($header_bin === false)
            throw new RuntimeException("Read error; failed to read 6 bytes of chunk header");
            
        list($chunk_type, $chunk_size) = $this->parseHeader($header_bin);
        
        $body_bin = fread($fp, $chunk_size);
        
        $chunk = $this->newChunk($chunk_type, $body_bin);
        return $chunk;
    }
    
    /**
     * @param array(type, length)
     */
    public function parseHeader($bin)
    {
        // header has 4 ascii chars for the type, then 4 bytes of chunk size
        $header = unpack('c4chars/Nlength', $bin);
        $block_type = chr($header['chars1']) . chr($header['chars2']) . chr($header['chars3']) . chr($header['chars4']);
        return array($block_type, $header['length']);
    }

    protected function newChunk($type, $bin)
    {
        $cf = new SSLChunkFactory();
        return $cf->newChunk($type, $bin);   
    }
}