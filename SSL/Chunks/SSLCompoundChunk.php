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
 * A Compound Chunk is a chunk that contains sub-chunks. OENT and OREN are both
 * compound in this way. The last parsed inner chunk is treated as the Compound-
 * Chunk's canonical chunk for getData() etc by default, although this can be
 * changed with selectIndex().
 */
class SSLCompoundChunk extends SSLChunk implements ArrayAccess, Iterator
{
    /**
     * @var array of SSLStructChunk
     */
    protected $chunks = array();
    
    protected $selected_index = -1;
    
    public function __construct($type, $data)
    {
        parent::__construct($type, '');
        
        // normally we'd save the factory here but this
        // is the only place we're using it
        $cp = Inject::the(new SSLRepo())->newChunkParser($data);
        while($cp->hasMore())
        {
            $chunk = $cp->parse();
            $this->chunks[] = $chunk;
            $this->selected_index++;
        }
    }
    
    public function select($index)
    {
        $this->selected_index = $index;
    }
    
    
    public function count()
    {
        return count($this->chunks);
    }
    
    // ArrayAccess implementation
    #[\ReturnTypeWillChange]
    public function offsetExists ($offset) 
    {
        return isset($this->chunks[$offset]);
    }
    
    #[\ReturnTypeWillChange]
    public function offsetGet ($offset) 
    {
        return $this->chunks[$offset];
    }
    
    #[\ReturnTypeWillChange]
    public function offsetSet ($offset, $value) 
    {
        throw new RuntimeException('SSLCompoundChunks are immutable');
    }
    
    #[\ReturnTypeWillChange]
    public function offsetUnset ($offset) 
    {
        throw new RuntimeException('SSLCompoundChunks are immutable');
    }
    
    // Iterator implementation
    #[\ReturnTypeWillChange]
    public function current () 
    {
        return current($this->chunks);
    }
    
    #[\ReturnTypeWillChange]
    public function next () 
    {
        return next($this->chunks);
    }

    #[\ReturnTypeWillChange]
    public function key () 
    {
        return key($this->chunks);
    }

    #[\ReturnTypeWillChange]
    public function valid () 
    {
        return (bool) current($this->chunks);
    }

    #[\ReturnTypeWillChange]
    public function rewind () 
    {
        return reset($this->chunks);
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
        if(isset($this->chunks[$this->selected_index]))
        {
            return $this->chunks[$this->selected_index]->getData();
        }
        
        throw new OutOfBoundsException("This {$this->type} chunk contains no inner chunk with index {$this->selected_index}");
    }
    
    /**
     * @return SSLStruct $struct
     */
    public function getDataInto(SSLStruct $struct)
    {
        if(isset($this->chunks[$this->selected_index]))
        {
            return $this->chunks[$this->selected_index]->getDataInto($struct);
        }
        
        throw new OutOfBoundsException("This {$this->type} chunk contains no inner chunk with index {$this->selected_index}");
    }
}