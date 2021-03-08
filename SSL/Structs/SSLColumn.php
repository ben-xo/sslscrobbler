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

class SSLColumn extends SSLStruct
{
    protected $key, $width;

    protected $types = array();
    
    public function getColName()
    {
        if(isset($this->types[$this->type]))
        {
            return $this->types[$this->type];
        }
        
        return 'Unknown (' . $this->type . ')';
    }
    
    public function setKey(SSLColumnKey $k)
    {
        $this->key = $key;
    }
    
    public function setWidth(SSLColumnWidth $w)
    {
        $this->width = $w;
    }
    
    public function getKey()
    {
        return $this->key->getKey();
    }
    
    public function getWidth()
    {
        return $this->width->getWidth();
    }
    
    public function __toString()
    {
        return sprintf("%s (%dpx)", $this->getColName(), $this->width);
    }
    
    public function getUnpacker()
    {
        throw new RuntimeException('You can\'t unpack a column directly; width and key are separate');
    }
    
    public function populateFrom(array $fields)
    {
        isset($fields['key']) && $this->key = $fields['key'];
        isset($fields['width']) && $this->width = $fields['width'];

        unset($fields['key']);
        unset($fields['width']);

        parent::populateFrom($fields);
    }
}