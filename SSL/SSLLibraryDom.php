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

class SSLLibraryDom extends SSLDom
{
    /**
     * @return array of SSLLibraryTrack
     */
    public function getLibraryTracks()
    {
        $data = $this->getData();
        $tracks = array();
        foreach($data as $datum)
        {
            if ($datum instanceof SSLLibraryTrack) 
                $tracks[] = $datum;
        }
        return $tracks;
    }
    
    /**
     * @return array of SSLOtrkChunk
     */
    public function getData()
    {        
        $data = array();
        $chunk_count = 0;
        foreach($this as $chunk)
        {
            
            if($chunk instanceof SSLOtrkChunk)
            {
                $data[] = $chunk->getDataInto(new SSLLibraryTrack());
            }

            elseif($chunk instanceof SSLVrsnChunk)
            {
                $data[] = $chunk->getDataInto(new SSLVersion());
            }
            
            $chunk_count++;
            
            L::level(L::DEBUG, __CLASS__) && !($chunk_count % 1000) &&
                L::log(L::DEBUG, __CLASS__, "Parsed %d chunks...", 
                    array( $chunk_count ));        
        }
        
        return $data;
    }
}
