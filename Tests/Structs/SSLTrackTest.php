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

class SSLTrackTest_ExternalRepo extends ExternalRepo
{
    public $getID3;
    public $calls = 0;
    
    public function newGetID3()
    {
        $this->calls++;
        return $this->getID3;
    }
}

class SSLTrackTest extends PHPUnit\Framework\TestCase
{
    var $external_repo;
    var $mock_getid3;
    
    function newSSLTrack()
    {
        return new SSLTrack();
    }
    
    function mockSSLTrack($methods=array())
    {
        return $this->getMockBuilder('SSLTrack')
                    ->disableOriginalConstructor()
                    ->onlyMethods($methods)
                    ->getMock();
        // return $this->createMock('SSLTrack', $methods);
    }
    
    function setUp(): void
    {
        $this->external_repo = new SSLTrackTest_ExternalRepo();
        $this->mock_getid3 = $this->createMock('getid3', array('Analyze'));
        $this->external_repo->getID3 = $this->mock_getid3;
        Inject::map('ExternalRepo', $this->external_repo);
    }
    
    function tearDown(): void
    {
        Inject::reset();
    }
    
    function test_guess_length_unneccessary()
    {
        $this->mock_getid3->expects($this->never())->method('Analyze');
        
        $t = $this->newSSLTrack();
        $t->populateFrom(array('row' => 1, 'length' => '1:23'));
        $this->assertSame('1:23', $t->getLength());
        $this->assertSame(83, $t->getLengthInSeconds());
        $this->assertSame('1:23', $t->getLength(SSLTrack::TRY_HARD));
        $this->assertSame(83, $t->getLengthInSeconds(SSLTrack::TRY_HARD));

        $t = $this->newSSLTrack();
        $t->populateFrom(array('row' => 2, 'length' => '0:00'));
        $this->mock_getid3->expects($this->never())->method('Analyze');
        $this->assertSame('0:00', $t->getLength());
        $this->assertSame(0, $t->getLengthInSeconds());
        $this->assertSame('0:00', $t->getLength(SSLTrack::TRY_HARD));
        $this->assertSame(0, $t->getLengthInSeconds(SSLTrack::TRY_HARD));

        $t = $this->newSSLTrack();
        $t->populateFrom(array('row' => 3, 'length' => '1:00.1'));
        $this->mock_getid3->expects($this->never())->method('Analyze');
        $this->assertSame('1:00.1', $t->getLength());
        $this->assertSame(60, $t->getLengthInSeconds());
        $this->assertSame('1:00.1', $t->getLength(SSLTrack::TRY_HARD));
        $this->assertSame(60, $t->getLengthInSeconds(SSLTrack::TRY_HARD));
    }

    function test_guess_length_necessary_without_try_hard()
    {
        $t = $this->newSSLTrack();
        $t->populateFrom(array('row' => 4));
        $this->mock_getid3->expects($this->never())->method('Analyze');
        $this->assertSame(null, $t->getLength());
        $this->assertSame(0, $t->getLengthInSeconds());
    }

    function test_guess_length_necessary_with_try_hard()
    {
        $this->mock_getid3
             ->expects($this->once())
             ->method('Analyze')
             ->willReturn(  array('playtime_seconds' => 83.123) )
        ;
        
        $t = $this->mockSSLTrack(array('file_exists'));
        $t->expects($this->once())
          ->method('file_exists')
          ->willReturn(true)
        ;
        
        $t->populateFrom(array('row' => 5, 'fullpath' => '/file.mp3'));
        
        $this->assertSame('1:23', $t->getLength(SSLTrack::TRY_HARD));
        $this->assertSame(83, $t->getLengthInSeconds(SSLTrack::TRY_HARD));
    }
    
    function test_guess_length_necessary_no_path()
    {
        $this->mock_getid3->expects($this->never())->method('Analyze');
        
        // don't add fullpath
        $t = $this->newSSLTrack();
        $t->populateFrom(array('row' => 6));
        $this->assertSame('0:00', $t->getLength(SSLTrack::TRY_HARD));
        $this->assertSame(0, $t->getLengthInSeconds(SSLTrack::TRY_HARD));
    }

    function test_guess_length_necessary_path_not_found()
    {
        $this->mock_getid3->expects($this->never())->method('Analyze');
        
        $t = $this->mockSSLTrack(array('file_exists'));
        $t->expects($this->once())
          ->method('file_exists')
          ->willReturn(false)
        ;
        
        $t->populateFrom(array('row' => 7, 'fullpath' => '/file.mp3'));
        $this->assertSame('0:00', $t->getLength(SSLTrack::TRY_HARD));
        $this->assertSame(0, $t->getLengthInSeconds(SSLTrack::TRY_HARD));
    }

    function test_guess_length_necessary_with_try_hard_saves_results()
    {
        $this->mock_getid3
             ->expects($this->once())
             ->method('Analyze')
             ->willReturn( array('playtime_seconds' => 83.123) )
        ;
        
        $t = $this->mockSSLTrack(array('file_exists'));
        $t->expects($this->once())
          ->method('file_exists')
          ->willReturn(true)
        ;
        
        $t->populateFrom(array('row' => 8, 'fullpath' => '/file.mp3'));
        
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
    }
}