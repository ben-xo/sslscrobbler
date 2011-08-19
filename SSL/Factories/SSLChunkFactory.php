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

class SSLChunkFactory implements Factory
{
    public function newChunk($type, $bin)
    {
        switch($type)
        {
            case 'vrsn':
                return new SSLVrsnChunk($bin); 
            case 'oent':
                return new SSLOentChunk($bin);
            case 'oren':
                return new SSLOrenChunk($bin);
            case 'adat':
                return new SSLAdatChunk($bin);
            case 'uent':
                return new SSLUentChunk($bin);
            case 'ocol':
                return new SSLOcolChunk($bin);
            case 'oses':
                return new SSLOsesChunk($bin);
            case 'ucok':
                return new SSLUcokChunk($bin);
            case 'ucow':
                return new SSLUcowChunk($bin);
            default:
                return new SSLUnknownChunk($type, $bin);
        }
    }
}