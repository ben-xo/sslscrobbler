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

class SSLCompoundChunk extends SSLChunk
{
    /**
     * @var array of SSLChunk
     */
    protected $chunks = array();
    
    /**
     * @var SSLChunk
     */
    protected $last_inner_chunk = null;
    
    public function __construct($type, $data)
    {
        parent::__construct($type, '');
        
        $cp = new SSLChunkParser($data);
        while($cp->hasMore())
        {
            $chunk = $cp->parse();
            $this->chunks[] =& $chunk;
            $this->last_inner_chunk =& $chunk;
        }
    }
    
    protected function chunkDebugBody($indent=0)
    {
        $string = '';
        foreach($this->chunks as $chunk)
        {
            $string .= $chunk->toString($indent+1);
        }
        return $string;
    }

    /**
     * @return array
     */
    public function getData()
    {
        if(isset($this->last_inner_chunk))
        {
            return $this->last_inner_chunk->getData();
        }
        
        throw new OutOfBoundException("This {$this->type} chunk contains no inner chunk");
    }
    
    /**
     * @return SSLStruct $struct
     */
    public function getDataInto(SSLStruct $struct)
    {
        if(isset($this->last_inner_chunk))
        {
            return $this->last_inner_chunk->getDataInto($struct);
        }
        
        throw new OutOfBoundException("This {$this->type} chunk contains no inner chunk");
    }
}