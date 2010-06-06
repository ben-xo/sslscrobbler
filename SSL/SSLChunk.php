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

abstract class SSLChunk
{
    protected $type;
    protected $data;
    
    public function __construct($type, $data)
    {
        $this->type = $type;
        $this->data = $data;
    }
    
    abstract public function getData();    
    
    public function __toString()
    {
        return $this->toString();
    }

    public function toString($indent=0)
    {
        $string = $this->chunkDebugHeader($indent);
        $string .= $this->chunkDebugBody($indent+1);
        return $string;        
    }
    
    protected function chunkDebugBody($indent=0)
    {
        return $this->hexdump($indent);
    }
    
    protected function chunkDebugHeader($indent=0)
    {
        return str_repeat("\t", $indent) . "CHUNK<{$this->type}>: \n";
    }
    
    protected function hexdump($indent=0)
    {
        $width = 32;
        $out = '';
        $rows = ceil(strlen($this->data) / $width);
        $hd = new Hexdumper();
        for($i = 0; $i < $rows; $i++)
        {
            $row = substr($this->data, ($i*$width), $width);
            $out .= $hd->hexdump($row, $indent, $width);
        }
        return $out;
    }    
}