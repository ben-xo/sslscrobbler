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

require_once 'SSLTrackTest.php';

class RuntimeCachingSSLTrackTest extends SSLTrackTest
{
    var $cache;
    
    function setUp(): void
    {
        parent::setUp();
        $this->cache = new SSLTrackCache();
    }
    
    function newSSLTrack()
    {
        return new RuntimeCachingSSLTrack($this->cache);
    }
    
    function mockSSLTrack($methods=array())
    {
        $mock = $this->getMockBuilder('RuntimeCachingSSLTrack')
                    ->disableOriginalConstructor()
                    ->setMethods($methods)
                    ->getMock();
        $mock->__construct($this->cache);
        return $mock;
        // return $this->createMock('RuntimeCachingSSLTrack', $methods, array($this->cache));
    }

    function test_guess_length_necessary_with_try_hard_cached_results()
    {
        $this->mock_getid3
             ->expects($this->once())
             ->method('Analyze')
             ->will( $this->returnValue( array('playtime_seconds' => 83.123) ) )
        ;
        
        $t = $this->mockSSLTrack(array('file_exists'));
        $t->expects($this->once())
          ->method('file_exists')
          ->will($this->returnValue(true))
        ;
        
        $t->populateFrom(array('row' => 9, 'fullpath' => '/file.mp3'));
        
        $this->assertSame(null, $t->getLength());
        $this->assertSame(0, $t->getLengthInSeconds());
        
        $this->assertSame('1:23', $t->getLength(SSLTrack::TRY_HARD));
        $this->assertSame(83, $t->getLengthInSeconds(SSLTrack::TRY_HARD));

        // saved
        $this->assertSame('1:23', $t->getLength());
        $this->assertSame(83, $t->getLengthInSeconds());

        // shouldn't trigger second getID3 Analyse
        $this->assertSame('1:23', $t->getLength(SSLTrack::TRY_HARD));
        $this->assertSame(83, $t->getLengthInSeconds(SSLTrack::TRY_HARD));

        // AND AGAIN
        
        $t = $this->mockSSLTrack(array('file_exists'));
        $t->expects($this->never()) // never gets into the meaty bit of getID3 guessing
          ->method('file_exists')
          ->will($this->returnValue(true))
        ;
        
        // same row, so should pull from the cached copy
        $t->populateFrom(array('row' => 9, 'fullpath' => '/file.mp3'));

        // this won't give a result because it won't ask for a result from the cache
        $this->assertSame(null, $t->getLength());
        $this->assertSame(0, $t->getLengthInSeconds());
        
        // this will ask for a result from the cache, so shouldn't trigger a second analyze
        $this->assertSame('1:23', $t->getLength(SSLTrack::TRY_HARD));
        $this->assertSame(83, $t->getLengthInSeconds(SSLTrack::TRY_HARD));

        // saved result
        $this->assertSame('1:23', $t->getLength());
        $this->assertSame(83, $t->getLengthInSeconds());

        // saved result
        $this->assertSame('1:23', $t->getLength(SSLTrack::TRY_HARD));
        $this->assertSame(83, $t->getLengthInSeconds(SSLTrack::TRY_HARD));
                     
    }
}