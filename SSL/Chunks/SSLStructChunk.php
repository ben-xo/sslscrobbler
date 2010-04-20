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

require_once dirname(__FILE__) . '/../Unpacker.php';
require_once dirname(__FILE__) . '/../SSLStruct.php';

abstract class SSLStructChunk extends SSLChunk
{
    protected $fields = array();
    
    public function getData()
    {
        return $this->fields;
    }
    
    public function getDataInto(SSLStruct $s)
    {        
        $s->populateFrom($this->fields);
        return $s;
    }
    
    public function parseWith($program)
    {
        if(empty($this->fields))
        {
            $up = new Unpacker($program);
            $this->fields = $up->unpack($this->data);
            unset($this->data);
        }
        return $this->fields;
    }
    
    protected function chunkDebugBody($indent=0)
    {
        if(empty($this->fields))
        {
            return parent::chunkDebugBody($indent);
        }
        
        $s = '';
        foreach($this->fields as $k => $v)
        {
            $s .= str_repeat("\t", $indent) . $k . ' => ' . $v . "\n";
        }
        return $s;
    }  
}