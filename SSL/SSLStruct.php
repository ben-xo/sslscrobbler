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
 * SSL Structs, e.g. Track, are concrete data structures that model actual data
 * from SSL. This representation is decoupled from chunk types that the data is
 * stored in, for reasons of polymorphism.
 * 
 * Usually a DOM that's context specific will pass expected SSLStructs into 
 * encountered SSLChunks to extract data from them, when parsing the file. 
 * That way, SSLChunks don't have to know much about their own data, and SSLStructs 
 * don't have to know anything about the binary representation of the Chunks. 
 */
abstract class SSLStruct
{
    /**
     * Returns an XOUP parsing program for the Unpacker.
     * 
     * @return string XOUP
     */
    abstract public function getParser();

    /**
     * Take data (often the output of running the parser)
     * and fill up the object.
     */
    abstract public function populateFrom(array $fields);
}