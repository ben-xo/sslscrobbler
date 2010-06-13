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

class ScrobblerTrackModelTest extends PHPUnit_Framework_TestCase
{
    protected $stm;
    protected $track;
    
    protected $scrobble_time;
    protected $now_playing_time;
    
    public function setUp()
    {
        $this->track = $this->trackMock(123);
        $this->stm = new ScrobblerTrackModel($this->track);
        
        $this->now_playing_time = ScrobblerTrackModel::NOW_PLAYING_MIN;
        $this->scrobble_time = (int) (300 / ScrobblerTrackModel::SCROBBLE_DIVIDER);
    }
    
    private function trackMock($id, $length=300, $played=false, $playtime=null)
    {
        $t = $this->getMock('SSLTrack');
        $t->expects($this->any()) ->method('getRow')             ->will($this->returnValue($id));
        $t->expects($this->any()) ->method('getLengthInSeconds') ->will($this->returnValue($length));
        $t->expects($this->any()) ->method('getPlayed')          ->will($this->returnValue($played));
        $t->expects($this->any()) ->method('getPlaytime')        ->will($this->returnValue($playtime));
        return $t;
    }
    
    public function test_getTrack_returns_what_was_constructed_with()
    {
        $track = $this->stm->getTrack();
        $this->assertSame($this->track, $track);
    }
    
    public function test_update_replaces_track_with_new_track()
    {
        $track2 = $this->trackMock(123);
        
        $this->assertNotSame($this->stm->getTrack(), $track2);
        
        $this->stm->update($track2);
        
        $this->assertSame($this->stm->getTrack(), $track2);
        $this->assertNotSame($this->stm->getTrack(), $this->track);
    }
    
    public function test_update_does_not_replace_track_with_different_track()
    {
        $track2 = $this->trackMock(456);
                
        $this->assertNotSame($this->stm->getTrack(), $track2);

        $this->stm->update($track2);
        
        $this->assertNotSame($this->stm->getTrack(), $track2);
        $this->assertSame($this->stm->getTrack(), $this->track);
    }
    
    public function test_default_state()
    {
        $this->assertFalse($this->stm->isNowPlaying());
        $this->assertFalse($this->stm->isScrobblable());
        return $this->stm;
    }
    
    /**
     * @depends test_default_state
     */
    public function test_small_elapse_does_not_change_state(ScrobblerTrackModel $stm)
    {
        $stm->elapse( 1 );
        $this->assertFalse($stm->isNowPlaying());
        $this->assertFalse($stm->isScrobblable());
        return $stm;
    }
    
    /**
     * @depends test_small_elapse_does_not_change_state
     */
    public function test_elapse_past_now_playing_point_is_now_playing(ScrobblerTrackModel $stm)
    {
        $stm->elapse( $this->now_playing_time );
        $this->assertTrue($stm->isNowPlaying());
        $this->assertFalse($stm->isScrobblable());
        return $stm;
    }
    
    /**
     * @depends test_elapse_past_now_playing_point_is_now_playing
     */
    public function test_further_elapse_is_still_now_playing(ScrobblerTrackModel $stm)
    {
        $stm->elapse(1);
        $this->assertTrue($stm->isNowPlaying());
        $this->assertFalse($stm->isScrobblable());
        return $stm;
    }
    
    /**
     * @depends test_further_elapse_is_still_now_playing
     */
    public function test_elapse_past_scrobble_point_is_not_scrobblable_yet(ScrobblerTrackModel $stm)
    {
        $this->assertEquals(150, $this->scrobble_time);
        $this->assertTrue($stm->isNowPlaying());
        $this->assertFalse($stm->isScrobblable()); // not true until the track is marked 'played'
        return $stm;
    }
    
    public function test_is_played_insufficient_to_be_scrobblable()
    {
        $stm = $this->stm;
        $stm->elapse( $this->now_playing_time + 5 );
        $stm->update( $this->trackMock(123, 300, true) );
        $this->assertTrue($stm->isNowPlaying());
        $this->assertFalse($stm->isScrobblable()); // not true until the track is past the scrobble point
    }

    public function test_is_played_and_past_scrobble_point_is_scrobblable__pass_point_first()
    {
        $stm = $this->stm;
        $stm->elapse( $this->scrobble_time + 5 );
        $stm->update( $this->trackMock(123, 300, true) );
        $this->assertTrue($stm->isNowPlaying());
        $this->assertTrue($stm->isScrobblable());
    }
    
    public function test_is_played_and_past_scrobble_point_is_scrobblable__is_played_first()
    {
        $stm = $this->stm;
        $stm->update( $this->trackMock(123, 300, true) );
        $stm->elapse( $this->scrobble_time + 5 );
        $this->assertTrue($stm->isNowPlaying());
        $this->assertTrue($stm->isScrobblable());
    }
    
    public function test_play_time_update_is_obeyed_for_now_playing()
    {
        $stm = $this->stm;
        $stm->elapse( $this->now_playing_time + 5 );
        $this->assertTrue($stm->isNowPlaying());
        
        // Update the track to say that fewer seconds have been played than have ticked.
        // This simulates what might happen if a track was put on the deck but play wasn't pressed for a while. 
        $stm->update( $this->trackMock(123, 300, true, $this->now_playing_time - 5) );
        $this->assertFalse($stm->isNowPlaying());
        
        $stm->elapse( $this->now_playing_time + 5 );
        $this->assertTrue($stm->isNowPlaying());
    }

    public function test_play_time_update_is_obeyed_for_scrobblability()
    {
        $stm = $this->stm;
        $stm->update( $this->trackMock(123, 300, true, 0) );
        $stm->elapse( $this->scrobble_time + 5 );
        $this->assertTrue($stm->isScrobblable());
        
        // Update the track to say that fewer seconds have been played than have ticked.
        // This simulates what might happen if a track was put on the deck but play wasn't pressed for a while. 
        $stm->update( $this->trackMock(123, 300, true, $this->scrobble_time - 5) );
        $this->assertFalse($stm->isScrobblable());
        
        $stm->elapse( $this->scrobble_time + 5 );
        $this->assertTrue($stm->isScrobblable());
    }
    
}