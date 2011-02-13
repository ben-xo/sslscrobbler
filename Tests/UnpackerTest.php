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
    
    public function test_unpack_signed_int_64()
    {
        $zero = chr(0) . chr(0) . chr(0) . chr(0) . chr(0) . chr(0) . chr(0) . chr(0);
        $one = chr(0) . chr(0) . chr(0) . chr(0) . chr(0) . chr(0) . chr(0) . chr(1);
        
        // As an signed int, this should represent -1
        $minusone = chr(255) . chr(255) . chr(255) . chr(255) . chr(255) . chr(255) . chr(255) . chr(255);
        
        // on 64-bit systems this should be 0x7FFFFFFFFFFFFFFF
        // on 32-bit systems this will overflow horribly but should be clamped to 0x7FFFFFFF
        $max_int_64 = chr(127) . chr(255) . chr(255) . chr(255) . chr(255) . chr(255) . chr(255) . chr(255);
        
        // on 64-bit systems this should be 0x7FFFFFF
        // on 32-bit systems this should be 0x7FFFFFF
        $max_int_32 = chr(0) . chr(0) . chr(0) . chr(0) . chr(127) . chr(255) . chr(255) . chr(255);

        $min_int_64 = chr(128) . chr(0) . chr(0) . chr(0) . chr(0) . chr(0) . chr(0) . chr(0);

        // this is the 64-bit version of min_int_32. tricksy because you have to remember about sign extension!
        $min_int_32 = chr(255) . chr(255) . chr(255) . chr(255) . chr(128) . chr(0) . chr(0) . chr(0); 
        
        $large = chr(0) . chr(123) . chr(123) . chr(123) . chr(123) . chr(123) . chr(123) . chr(123);
        
        // 2s-complement of $large
        $minus_large = chr(255) . chr(132) . chr(132) . chr(132) . chr(132) . chr(132) . chr(132) . chr(133);
        
        $this->assertSame(0, $this->u->unpacksint($zero));
        $this->assertSame(1, $this->u->unpacksint($one));
        $this->assertSame(-1, $this->u->unpacksint($minusone));

        $this->assertSame(0x7FFFFFFF, $this->u->unpacksint($max_int_32));
        
        if(PHP_INT_SIZE == 8)
        {
            $this->assertSame(-0x80000000, $this->u->unpacksint($min_int_32));
            $this->assertSame(0x7FFFFFFFFFFFFFFF, $this->u->unpacksint($max_int_64));
            
            // PHP is retarded and won't let you represent min_int directly. -0x8000000000000000 parses as a float.
            $this->assertSame(-0x7FFFFFFFFFFFFFFF - 1, $this->u->unpacksint($min_int_64));
            
            $this->assertSame(0x7B7B7B7B7B7B7B, $this->u->unpacksint($large));
            $this->assertSame(-0x7B7B7B7B7B7B7B, $this->u->unpacksint($minus_large));
        }
        else
        {
            // PHP is retarded and won't let you represent min_int directly. -0x80000000 parses as a float.
            $this->assertSame(-0x7FFFFFFF - 1, $this->u->unpacksint($min_int_32));
            $this->assertSame(0x7FFFFFFF, $this->u->unpacksint($max_int_64));
            $this->assertSame(-0x7FFFFFFF - 1, $this->u->unpacksint($min_int_64));
            $this->assertSame(0x7FFFFFFF, $this->u->unpacksint($large));
            $this->assertSame(-0x7FFFFFFF - 1, $this->u->unpacksint($minus_large));
        }
    }
    
    public function test_unpack_unsigned_int_32()
    {
        $zero = chr(0) . chr(0) . chr(0) . chr(0);
        $one = chr(0) . chr(0) . chr(0) . chr(1);
        $max_int_32 = chr(127) . chr(255) . chr(255) . chr(255);
        
        $minusone = chr(255) . chr(255) . chr(255) . chr(255);
        $minustwo = chr(255) . chr(255) . chr(255) . chr(254);
        
        $this->assertSame(0, $this->u->unpackuint($zero));
        $this->assertSame(1, $this->u->unpackuint($one));
        $this->assertSame(0x7FFFFFFF, $this->u->unpackuint($max_int_32));
        
        if(PHP_INT_SIZE == 8)
        {
            $this->assertSame(0xFFFFFFFF, $this->u->unpackuint($minusone));
            $this->assertSame(0xFFFFFFFE, $this->u->unpackuint($minustwo));
        }
        else
        {
            // overflow
            $this->assertSame(0x7FFFFFFF, $this->u->unpackuint($minusone));
            $this->assertSame(0x7FFFFFFF, $this->u->unpackuint($minustwo));
        }
    }

    public function test_unpack_signed_int_32()
    {
        $zero = chr(0) . chr(0) . chr(0) . chr(0);
        $one = chr(0) . chr(0) . chr(0) . chr(1);
        $max_int_32 = chr(127) . chr(255) . chr(255) . chr(255);
        
        $minusone = chr(255) . chr(255) . chr(255) . chr(255);
        $minustwo = chr(255) . chr(255) . chr(255) . chr(254);
        
        $this->assertSame(0, $this->u->unpacksint($zero));
        $this->assertSame(1, $this->u->unpacksint($one));
        $this->assertSame(0x7FFFFFFF, $this->u->unpacksint($max_int_32));
        
        $this->assertSame(-1, $this->u->unpacksint($minusone));
        $this->assertSame(-2, $this->u->unpacksint($minustwo));
    }
    
    public function test_unpack_unsigned_int_16()
    {
        $zero = chr(0) . chr(0);
        $one = chr(0) . chr(1);
        $max_int_16 = chr(127) . chr(255);
        
        $minusone = chr(255) . chr(255);
        $minustwo = chr(255) . chr(254);
        
        $this->assertSame(0, $this->u->unpackuint($zero));
        $this->assertSame(1, $this->u->unpackuint($one));
        $this->assertSame(0x7FFF, $this->u->unpackuint($max_int_16));
        $this->assertSame(0xFFFF, $this->u->unpackuint($minusone));
        $this->assertSame(0xFFFE, $this->u->unpackuint($minustwo));
    }
    
    public function test_unpack_signed_int_16()
    {
        $zero = chr(0) . chr(0);
        $one = chr(0) . chr(1);
        $max_int_16 = chr(127) . chr(255);
        
        $minusone = chr(255) . chr(255);
        $minustwo = chr(255) . chr(254);
        
        $this->assertSame(0, $this->u->unpacksint($zero));
        $this->assertSame(1, $this->u->unpacksint($one));
        $this->assertSame(0x7FFF, $this->u->unpacksint($max_int_16));
        $this->assertSame(-1, $this->u->unpacksint($minusone));
        $this->assertSame(-2, $this->u->unpacksint($minustwo));
    }
    
    public function test_unpack_unsigned_int_8()
    {
        $zero = chr(0);
        $one = chr(1);
        $max_int_8 = chr(127);
        
        $minusone = chr(255);
        $minustwo = chr(254);
        
        $this->assertSame(0, $this->u->unpackuint($zero));
        $this->assertSame(1, $this->u->unpackuint($one));
        $this->assertSame(0x7F, $this->u->unpackuint($max_int_8));
        $this->assertSame(0xFF, $this->u->unpackuint($minusone));
        $this->assertSame(0xFE, $this->u->unpackuint($minustwo));
    }
    
    public function test_unpack_signed_int_8()
    {
        $zero = chr(0);
        $one = chr(1);
        $max_int_8 = chr(127);
        
        $minusone = chr(255);
        $minustwo = chr(254);
        
        $this->assertSame(0, $this->u->unpacksint($zero));
        $this->assertSame(1, $this->u->unpacksint($one));
        $this->assertSame(0x7F, $this->u->unpacksint($max_int_8));
        $this->assertSame(-1, $this->u->unpacksint($minusone));
        $this->assertSame(-2, $this->u->unpacksint($minustwo));
    }    
}

class UnpackerTestUnpacker extends Unpacker
{
    public function unpack($bin) { }
    public function unpacksint($datum, $intmax = PHP_INT_MAX) { return parent::unpacksint($datum, $intmax); }
    public function unpackuint($datum, $intmax = PHP_INT_MAX) { return parent::unpackuint($datum, $intmax); }
}