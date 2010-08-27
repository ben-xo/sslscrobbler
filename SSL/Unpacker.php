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
 * Base class for binary unpacking. 
 * 
 * An Unpacker takes a binary string, and returns an array of key/value pairs, using
 * the unpack() method.
 * 
 * XoupInterpreter extends this, as do all compiled XOUP programs.
 * 
 * SSLStructs (such as SSLTrack) have a getUnpacker() method that returns an 
 * appropriate unpacker for the sort of data that the struct is expecting to be 
 * populated from.
 * 
 * Unpacker provides a methods in the base class to do with binary string and integer 
 * handling as found in SSL's binary files.
 * 
 * unpackstr():
 * SSL binary files appear to contain UTF-16 text, so string unpacking assumes
 * that you want to convert from UTF-16 to UTF-8.
 * 
 * unpackint():
 * This is a utility function for unpacking big endian unsigned binary integers
 * into PHP integers, which is how SSL appears to store non-string values.
 * 
 * @author ben
 */
abstract class Unpacker
{
    /**
     * Unpack the binary data in $bin, and return it.
     * 
     * @param binary string $bin
     * @return Array of key value pairs.
     */
    abstract public function unpack($bin);
    
    /**
     * Converts an SSL binary string into a PHP string
     */
    protected function unpackstr($datum)
    {
        return mb_convert_encoding($datum, 'UTF-8', 'UTF-16');
    }
    
    /**
     * Converts an SSL binary string into a PHP integer
     */
    protected function unpackint($datum)
    {
        $width = strlen($datum);
        switch($width)
        {
            case 8:
                // Seems that ScratchLive 2.0 uses 64-bit timestamps. oh shi--
                $vals = unpack('Nupper/Nlower', $datum);
                if(PHP_INT_MAX == 0x7FFFFFFF)
                {
                    if($vals['upper'])
                    {
                        L::level(L::WARNING) &&
                            L::log(L::WARNING, __CLASS__, "Encountered 64-bit integer > PHP_INT_MAX on a 32-bit PHP. Throwing away upper bytes. Original value was 0x%08X%08X",
                                array(dechex($vals['upper']), dechex($vals['lower'])));
                    }
                    return $vals['lower'];
                }
                else
                {
                    return ($vals['upper'] << 32) + ($vals['lower']);
                }
                break;
                
            case 4:
                $vals = unpack('Nval', $datum); // unsigned long (always 32 bit, big endian byte order)
                break;
                
            case 2:
                $vals = unpack('nval', $datum); // unsigned short (always 16 bit, big endian byte order)
                break;
                
            case 1:
                $vals = unpack('cval', $datum); // char
                break;
                
            default:
                throw new InvalidArgumentException('Cannot unpack an odd-sized int of width ' . $width);
        }
        
        return $vals['val'];
    }

    protected function unpackfloat($datum)
    {
        $width = strlen($datum);
        switch($width)
        {
            case 8:
                $vals = unpack('dval', $datum);
                break;
                
            case 4:
                $vals = unpack('fval', $datum);
                break;
                
            default:
                throw new InvalidArgumentException('Cannot unpack an odd-sized float of width ' . $width);
        }
        return $vals['val'];
    }
}