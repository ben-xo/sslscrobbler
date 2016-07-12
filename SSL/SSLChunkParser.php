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
    /**
     * @var SSLChunkFactory
     */
    protected $chunk_factory;
    
    protected $ptr = 0;
    protected $eos = false;
    protected $data;
    protected $data_len;
    
    public function __construct($data=null)
    {
        $this->chunk_factory = Inject::the(new SSLChunkFactory());
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
        
        do
        {
            $header_bin = substr($string, $this->ptr, 8);
            $length_read = strlen($header_bin);
        }
        while($length_read > 0 && str_pad($header_bin, 8, "\0") == "\0\0\0\0\0\0\0\0");
        
        if($length_read < 8)
            throw new RuntimeException("Could not parse string ($length_read bytes is too short to contain a header)"); 

        list($chunk_type, $chunk_size) = $this->parseHeader($header_bin);
                
        $body_bin = substr($string, $this->ptr + 8, $chunk_size);
        
        $chunk = $this->chunk_factory->newChunk($chunk_type, $body_bin);
        
        $this->ptr += 8 + $chunk_size;
        if($this->ptr >= $this->data_len) 
        {
            $this->eos = true;
        }
        
        return $chunk;
    }
    
    protected function resync($fp)
    {
        $done = false;
        do
        {
            $next_four = fread($fp, 4);
            if($next_four == false)
            {
                //eof
                return false;
            }

            $next_four_length = strlen($next_four);
            if($next_four_length < 4)
            {
                // eof;
                return false;
            }

            if($next_four == 'oent')
            {
                // rewind by the length of 'oent'
                fseek($fp, -4, SEEK_CUR);
                return true;
            }
            fseek($fp, -3, SEEK_CUR); // seek to next chunk, byte by byte.
        }
        while(!feof($fp));

        // eof
        return false;
    }

    public function parseFromFile($fp)
    {
        // It looks like newer Serato sometimes allocates large chunks of free space in the file at the end.
        // This doesn't count as data, so we skip it, using str_pad() to make sure we also skip any run of 
        // nulls that is shorter than 8 bytes.
        do
        {
            $header_bin = fread($fp, 8);
            $length_read = strlen($header_bin);

            $just_blank_bytes = (str_pad($header_bin, 8, "\0") == "\0\0\0\0\0\0\0\0");
            if($just_blank_bytes)
            {
                L::level(L::WARNING) &&
                    L::log(L::WARNING, __CLASS__, "Hit unallocated blank space in file.",
                        array());

                // argh. Attempt to resync the stream to the next oent.
                if(!$this->resync($fp))
                {
                    // eof.
                    return false;
                }
            }
        }
        while($length_read > 0 && $just_blank_bytes);

        if($length_read == 0)
        {
            L::level(L::DEBUG) &&
                L::log(L::DEBUG, __CLASS__, "Reached EOF; no more to read", 
                    array());
                
            return false; // if we read an exact chunk, it's not an 'eof'.
        }
            
        if($length_read < 8)
            throw new OutOfBoundsException("No more data (read {$length_read} bytes)");

        if($header_bin === false)
            throw new RuntimeException("Read error; failed to read 8 bytes of chunk header");
            
        list($chunk_type, $chunk_size) = $this->parseHeader($header_bin);
        
        if($chunk_size > 1048576)
        {
            // a chunk larger than 1Mb!?
            $chunk_size = number_format($chunk_size / 1024 / 1024, 2);
            $dumper = new Hexdumper();
            throw new RuntimeException(
                sprintf("Found chunk claiming to be enormous ({$chunk_size} MiB); are you reading the right file?\n%s", $dumper->hexdump($header_bin))
            );
        }

        if($chunk_size > 0)
        {
            $body_bin = fread($fp, $chunk_size);
            $chunk = $this->chunk_factory->newChunk($chunk_type, $body_bin);

            L::level(L::DEBUG) &&
                L::log(L::DEBUG, __CLASS__, "Read %s chunk from file",
                    array($chunk_type));
        }
        else
        {
            $chunk = '';

            L::level(L::WARNING) &&
                L::log(L::WARNING, __CLASS__, "Read 0-byte %s chunk from file. This is unusual.",
                    array($chunk_type));
        }

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
}