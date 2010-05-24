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

require_once 'Unpacker/XoupLoader.php';
require_once 'Unpacker/XoupParser.php';
require_once 'Unpacker/XoupInterpreter.php';
require_once 'Unpacker/XoupCompiler.php';

/**
 * Base class for unpacking. XoupInterpreter extends this, as do
 * all compiled XOUP classes.	
 *  
 * @author ben
 */
abstract class Unpacker
{
    abstract public function unpack($bin);
    
    protected function unpackstr($datum)
    {
        return mb_convert_encoding($datum, 'UTF-8', 'UTF-16');
    }
    
    protected function unpackint($datum)
    {
        $width = strlen($datum);
        switch($width)
        {
                
            case 4:
                $w = 'N'; // unsigned long (always 32 bit, big endian byte order)
                break;
                
            case 2:
                $w = 'n'; // unsigned short (always 16 bit, big endian byte order)
                break;
                
            case 1:
                $w = 'c'; // char
                break;
                
            default:
                throw new InvalidArgumentException('Cannot unpack an odd-sized int of width ' . $width);
        }
        
        $vals = unpack($w . 'val', $datum);
        
        return $vals['val'];
    }    
}