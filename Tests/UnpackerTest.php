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

class UnpackerTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var UnpackerTestUnpacker
     */
    protected $u;
    
    public function setUp()
    {
        $this->u = new UnpackerTestUnpacker();
    }
    
    public function test_unpack_unsigned_int_64()
    {
        $zero = chr(0) . chr(0) . chr(0) . chr(0) . chr(0) . chr(0) . chr(0) . chr(0);
        $one = chr(0) . chr(0) . chr(0) . chr(0) . chr(0) . chr(0) . chr(0) . chr(1);
        
        // As an unsigned int, this should represent a very large +ve number
        // - but as all ints in PHP are signed, this should end up as a double due to PHP's overflow rules
        $minusone = chr(255) . chr(255) . chr(255) . chr(255) . chr(255) . chr(255) . chr(255) . chr(255);
        
        // on 64-bit systems this should be 0x7FFFFFFFFFFFFFFF
        // on 32-bit systems this will overflow horribly but should be clamped to 0x7FFFFFFF
        $max_int_64 = chr(127) . chr(255) . chr(255) . chr(255) . chr(255) . chr(255) . chr(255) . chr(255);
        
        // on 64-bit systems this should be 0x7FFFFFF
        // on 32-bit systems this should be 0x7FFFFFF
        $max_int_32 = chr(0) . chr(0) . chr(0) . chr(0) . chr(127) . chr(255) . chr(255) . chr(255);
        
        $large = chr(0) . chr(123) . chr(123) . chr(123) . chr(123) . chr(123) . chr(123) . chr(123);
        
        $this->assertSame(0, $this->u->unpackuint($zero));
        $this->assertSame(1, $this->u->unpackuint($one));
        $this->assertSame(0x7FFFFFFF, $this->u->unpackuint($max_int_32));
        
        if(PHP_INT_SIZE == 8)
        {
            $this->assertSame(0x7FFFFFFFFFFFFFFF, $this->u->unpackuint($minusone));
            $this->assertSame(0x7FFFFFFFFFFFFFFF, $this->u->unpackuint($max_int_64));
            $this->assertSame(0x7FFFFFFF, $this->u->unpackuint($max_int_32));
            $this->assertSame(0x7B7B7B7B7B7B7B, $this->u->unpackuint($large));
        }
        else
        {
            $this->assertSame(0x7FFFFFFF, $this->u->unpackuint($max_int_64));
            $this->assertSame(0x7FFFFFFF, $this->u->unpackuint($max_int_32));
            $this->assertSame(0x7FFFFFFF, $this->u->unpackuint($minusone));
            $this->assertSame(0x7FFFFFFF, $this->u->unpackuint($large));
        }
    }
    
//    public function test_unpack_unsigned_int_64()
//    {
//        $zero = chr(0) . chr(0) . chr(0) . chr(0) . chr(0) . chr(0) . chr(0) . chr(0);
//        $one = chr(0) . chr(0) . chr(0) . chr(0) . chr(0) . chr(0) . chr(0) . chr(1);
//        
//        // As an unsigned int, this should represent a very large +ve number
//        // - but as all ints in PHP are signed, this should end up as a double due to PHP's overflow rules
//        $minusone = chr(255) . chr(255) . chr(255) . chr(255) . chr(255) . chr(255) . chr(255) . chr(255);
//        
//        // on 64-bit systems this should be 0x7FFFFFFFFFFFFFFF
//        // on 32-bit systems this will overflow horribly but should be clamped to 0x7FFFFFFF
//        $max_int_64 = chr(127) . chr(255) . chr(255) . chr(255) . chr(255) . chr(255) . chr(255) . chr(255);
//        
//        // on 64-bit systems this should be 0x7FFFFFF
//        // on 32-bit systems this should be 0x7FFFFFF
//        $max_int_32 = chr(0) . chr(0) . chr(0) . chr(0) . chr(127) . chr(255) . chr(255) . chr(255);
//        
//        
//        $this->assertSame(0, $this->u->unpackuint($zero));
//        $this->assertSame(1, $this->u->unpackuint($one));
//        $this->assertSame(0x7FFFFFFF, $this->u->unpackuint($max_int_32));
//        
//        if(PHP_INT_SIZE == 8)
//        {
//            $this->assertSame(0x7FFFFFFFFFFFFFFF, $this->u->unpackuint($minusone));
//            $this->assertSame(0x7FFFFFFFFFFFFFFF, $this->u->unpackuint($max_int_64));
//            $this->assertSame(0x7FFFFFFF, $this->u->unpackuint($max_int_32));
//        }
//        else
//        {
//            $this->assertSame(0x7FFFFFFF, $this->u->unpackuint($max_int_64));
//            $this->assertSame(0x7FFFFFFF, $this->u->unpackuint($max_int_32));
//            $this->assertSame(0x7FFFFFFF, $this->u->unpackuint($minusone));
//        }
//    }
    
    
}

class UnpackerTestUnpacker extends Unpacker
{
    public function unpack($bin) { }
    public function unpacksint($datum, $intmax = PHP_INT_MAX) { return parent::unpacksint($datum, $intmax); }
    public function unpackuint($datum, $intmax = PHP_INT_MAX) { return parent::unpackuint($datum, $intmax); }
}