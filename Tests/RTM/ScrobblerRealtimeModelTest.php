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

class ScrobblerRealtimeModelTest extends PHPUnit_Framework_TestCase implements NowPlayingObserver
{
    protected $srm;
    
    protected $track0;
    protected $track1;
    protected $track2;
    
    protected $deck0;
    protected $deck1;
    protected $deck2;
    
    protected $now_playing_called;
    protected $now_playing_called_with;
    
    public function setUp()
    {
        $this->srm = $this->getMock('ScrobblerRealtimeModel', 
            array( 'newScrobblerTrackModel' )
        );

        $this->srm->addNowPlayingObserver($this);
        
        // tracks
        $stm_test = new ScrobblerTrackModelTest();
        $this->track0 = $stm_test->trackMock(123, 300, true, 125);
        $this->track1 = $stm_test->trackMock(456, 300, true, 125);
        $this->track2 = $stm_test->trackMock(789, 300, true, 125);
        
        // deck models
        $this->deck0 = new ScrobblerTrackModel($this->track0);
        $this->deck1 = new ScrobblerTrackModel($this->track1);
        $this->deck2 = new ScrobblerTrackModel($this->track2);
        
        $this->now_playing_called = false;
        $this->now_playing_called_with = null;
    }
    
    // support methods
    
    public function srmExpectsNewDeckExactly($exactly, $override0=null, $override1=null, $override2=null)
    {
        $this->srm->expects($this->exactly($exactly))
             ->method('newScrobblerTrackModel')
             ->will($this->onConsecutiveCalls( 
                 isset($override0) ? $override0 : $this->deck0,
                 isset($override1) ? $override1 : $this->deck1,
                 isset($override2) ? $override2 : $this->deck2
             ))
        ;
    }
    
    public function sendStart(SSLTrack $track)
    {
        $this->now_playing_called = false;
        $this->now_playing_called_with = null;
        
        // events
        $events = new TrackChangeEventList( 
            array(new TrackStartedEvent($track)) 
        );
            
        $this->srm->notifyTrackChange($events);
    }
    
    public function sendStop(SSLTrack $track)
    {
        $this->now_playing_called = false;
        $this->now_playing_called_with = null;
        
        // events
        $events = new TrackChangeEventList( 
            array(new TrackStoppedEvent($track)) 
        );
            
        $this->srm->notifyTrackChange($events);
    }
    
    public function sendTick($seconds)
    {
        $this->now_playing_called = false;
        $this->now_playing_called_with = null;
        $this->srm->notifyTick($seconds);
    }
    
    // self-shunt methods
    
    public function notifyNowPlaying(SSLTrack $track = null)
    {
        $this->now_playing_called = true;
        $this->now_playing_called_with = $track;
    }
    
    // here be the tests!
    
    public function test_empty_deck_ticking_does_nothing_interesting()
    {
        $this->sendTick(1000);
        $this->assertFalse($this->now_playing_called);
    }
    
    // Tests for tracks that are marked "now playing" by their model when they're added.
    // This stuff should exercise the queueing / de-queueing logic.

    public function test_queue_size()
    {
        $srm = $this->srm;
        
        // setup
        
        $this->srmExpectsNewDeckExactly(3);

        $events1 = new TrackChangeEventList( 
            array(new TrackStartedEvent($this->track0)) 
        );
        
        $events2 = new TrackChangeEventList( 
            array(new TrackStartedEvent($this->track1), 
                  new TrackStartedEvent($this->track2)) 
        );
        
        // initial state
        $this->assertEquals(0, $srm->getQueueSize());
        
        // track start
        $srm->notifyTrackChange($events1);
        $this->assertEquals(1, $srm->getQueueSize());
        
        // this tests that the same track is not double-added 
        $srm->notifyTrackChange($events1);
        $this->assertEquals(1, $srm->getQueueSize());
        
        // second pair of track starts
        $srm->notifyTrackChange($events2);
        $this->assertEquals(3, $srm->getQueueSize());
    }
    
    /**
     * @depends test_queue_size
     */
    public function test_start_a_track_sets_now_playing()
    {
        $this->srmExpectsNewDeckExactly(1);
        $this->sendStart($this->track0);
        
        $this->assertTrue($this->now_playing_called);
        $this->assertSame($this->now_playing_called_with, $this->track0);
    }
    
    /**
     * @depends test_start_a_track_sets_now_playing
     */
    public function test_stop_track_removes_now_playing()
    {
        // expectations
        $this->srmExpectsNewDeckExactly(1);
        $this->sendStart($this->track0);
        $this->assertTrue($this->now_playing_called);
        $this->assertSame($this->now_playing_called_with, $this->track0);

        $this->sendStop($this->track0);        
        $this->assertEquals(0, $this->srm->getQueueSize());
        $this->assertTrue($this->now_playing_called);
        $this->assertNull($this->now_playing_called_with);
    }
    
    /**
     * @depends test_stop_track_removes_now_playing
     */
    public function test_start_second_track_leaves_first_now_playing()
    {
        // expectations
        $this->srmExpectsNewDeckExactly(2);
        $this->sendStart($this->track0);        
        $this->assertTrue($this->now_playing_called);
        $this->assertSame($this->now_playing_called_with, $this->track0);

        $this->sendStart($this->track1);

        // even though there are 2 in the queue...
        $this->assertEquals(2, $this->srm->getQueueSize());
        
        // ...nobody was notified that the 2nd one is now playing
        $this->assertFalse($this->now_playing_called);
    }
    
    /**
     * @depends test_start_second_track_leaves_first_now_playing
     */
    public function test_stop_second_track_leaves_first_now_playing()
    {
        // expectations
        $this->srmExpectsNewDeckExactly(2);
        $this->sendStart($this->track0);
        
        $this->sendStart($this->track1);
        
        // even though a 2nd track was added (then removed)...
        $this->assertEquals(2, $this->srm->getQueueSize());
        
        // ...nobody was notified that the 2nd one is now playing
        $this->assertFalse($this->now_playing_called);
        
        $this->sendStop($this->track1);
        
        // even though a 2nd track was added (then removed)...
        $this->assertEquals(1, $this->srm->getQueueSize());
        
        // ...nobody was notified that the 2nd one is now playing
        $this->assertFalse($this->now_playing_called);
    }
    
    public function test_stop_first_track_sets_second_now_playing()
    {
        // expectations
        $this->srmExpectsNewDeckExactly(2);
        $this->sendStart($this->track0);
        $this->sendStart($this->track1);
        $this->sendStop($this->track0);
        
        $this->assertEquals(1, $this->srm->getQueueSize());
        
        $this->assertTrue($this->now_playing_called);
        $this->assertSame($this->now_playing_called_with, $this->track1);
    }
    
    public function test_second_track_becomes_now_playing_before_first_and_notifies_observers()
    {
        // This tests that a track with isNowPlaying() == false, that is nevertheless the first
        // added track, cedes control to the 2nd track if the second track becomes isNowPlaying().
        
        $stm_test = new ScrobblerTrackModelTest();
        $track0 = $stm_test->trackMock(123, 300, false, 0); // definitely not "now playing"
        $deck0 = new ScrobblerTrackModel($track0);
        
        // expectations
        $this->srmExpectsNewDeckExactly(2, $deck0);
        $this->sendStart($track0);

        $this->assertTrue($this->now_playing_called);
        $this->assertSame($this->now_playing_called_with, $track0);        

        // now the actual test!
        $this->sendStart($this->track1);
        
        $this->assertEquals(2, $this->srm->getQueueSize());
        
        $this->assertTrue($this->now_playing_called);
        $this->assertSame($this->now_playing_called_with, $this->track1);
    }
    
    /**
     * @depends test_start_a_track_sets_now_playing
     */
    public function test_notifying_two_stops()
    {
        // get the model into a state with a track playing, and a track queued...
        
        $srm = $this->srm;
        
        $stm_test = new ScrobblerTrackModelTest();
        
        // expectations
        $this->srmExpectsNewDeckExactly(2);
        
        // send multiple starts        
        $srm->notifyTrackChange(new TrackChangeEventList( 
            array(new TrackStartedEvent($this->track0), 
                  new TrackStartedEvent($this->track1)) 
        ));

        $this->assertTrue($this->now_playing_called);
        $this->assertSame($this->now_playing_called_with, $this->track0);        
        
        
        // Now for the important bit of the test!
        $stm_test = new ScrobblerTrackModelTest();
        $track2 = $stm_test->trackMock(123, 300, true, 150); // track0, but "played"
        $track3 = $stm_test->trackMock(456, 300, true, 150); // track1, but "played"
        
        // reset, and send multiple stops
        $this->now_playing_called = false;
        $this->now_playing_called_with = false;
        $srm->notifyTrackChange(new TrackChangeEventList( 
            array(new TrackStoppedEvent($track2), 
                  new TrackStoppedEvent($track3)) 
        ));
        
        $this->assertEquals(0, $srm->getQueueSize());
        
        $this->assertTrue($this->now_playing_called);
        $this->assertNull($this->now_playing_called_with);
    }
    
    /*
     * "Tick" based tests 
     */
    
    public function test_now_playing_goes_to_newest_non_now_playing_track_as_default() 
    {
        // test that it doesn't revert to the previous track if they're both past the scrobble point, basically
        
        // get the model into a state with a track playing, and a track queued...
        
        // expectations
        $this->srmExpectsNewDeckExactly(2);
        $this->sendStart($this->track0);
        $this->assertTrue($this->now_playing_called);
        $this->assertSame($this->now_playing_called_with, $this->track0);
        
        $this->sendTick(50); // it's already at 125 seconds in, so bring it to 175 (past scrobble point)
        
        // even though it's past scrobble point, it's the first track, so it's still "now playing"
        $this->assertFalse($this->now_playing_called);
        
        // MILK.
        $this->sendStart($this->track1);
        $this->assertTrue($this->now_playing_called); // should switch to 2nd track now
        $this->assertSame($this->now_playing_called_with, $this->track1);
        
        $this->sendTick(1); // now they're both now playing (track0 at 176, track1 at 126)
        
        // get them both past the scrobble point
        $this->sendTick(40); // track0 -> 216, track1 -> 166
        
        $this->assertFalse($this->now_playing_called); // it should stay with the 2nd track.        
    }
    
    public function test_now_playing_goes_to_newest_non_now_playing_track_as_default_2() 
    {
        // test that it doesn't revert to the previous track if they're both past the scrobble point, basically
        
        // get the model into a state with a track playing, and a track queued...
        
        // expectations
        $this->srmExpectsNewDeckExactly(3);
        
        $this->sendStart($this->track0);
        $this->assertTrue($this->now_playing_called);
        $this->assertSame($this->now_playing_called_with, $this->track0);
        
        $this->sendTick(50); // it's already at 125 seconds in, so bring it to 175 (past scrobble point)
        
        // even though it's past scrobble point, it's the first track, so it's still "now playing"
        $this->assertFalse($this->now_playing_called);
        
        // MILK.
        $this->sendStart($this->track1);
        $this->assertTrue($this->now_playing_called); // should switch to 2nd track now
        $this->assertSame($this->now_playing_called_with, $this->track1);
        
        $this->sendTick(50); // now they're both now playing and past scrobble point (track0 at 225, track1 at 175)

        $this->assertFalse($this->now_playing_called); // it should stay with the 2nd track.        
        
        $this->sendStart($this->track2);
        $this->assertTrue($this->now_playing_called); // should switch to 3rd track now
        $this->assertSame($this->now_playing_called_with, $this->track2);
        
        // get them all past the scrobble point
        $this->sendTick(40); // track0 -> 265, track1 -> 205, track2 -> 165
        
        $this->assertFalse($this->now_playing_called); // it should stay with the 3rd track.        
    }
    
    public function test_second_track_becomes_now_playing_after_reaching_np_point() 
    {
        // Override track1 / deck1 to be new on the deck with 0 seconds played for this test 
        $stm_test = new ScrobblerTrackModelTest();
        $this->track1 = $stm_test->trackMock(456, 300, false, 0); // new track pon de floor
        $this->deck1 = new ScrobblerTrackModel($this->track1);
        
        // expectations
        $this->srmExpectsNewDeckExactly(2);
        $this->sendStart($this->track0);
        $this->assertTrue($this->now_playing_called);
        $this->assertSame($this->now_playing_called_with, $this->track0);
        
        $this->sendTick(50); // it's already at 125 seconds in, so bring it to 175 (past scrobble point)
        
        // even though it's past scrobble point, it's the first track, so it's still "now playing"
        $this->assertFalse($this->now_playing_called);
        $this->assertFalse($this->deck0->isNowPlaying());

        // Now for the important bit of the test!        
        $this->sendStart($this->track1);
        $this->assertFalse($this->now_playing_called); // should not switch to the track immediately
        $this->assertNull($this->now_playing_called_with);
        
        $this->sendTick(45); // get track 1 past the now playing point
                        
        $this->assertTrue($this->now_playing_called); // it should switch to 2nd track
        $this->assertSame($this->now_playing_called_with, $this->track1);        
    }
    
    public function test_second_track_becomes_now_playing_after_first_reaches_scrobble_point() 
    {
        // expectations
        $this->srmExpectsNewDeckExactly(2);
        $this->sendStart($this->track0);
        $this->assertTrue($this->now_playing_called);
        $this->assertSame($this->now_playing_called_with, $this->track0);
        
        $this->sendTick(20); // it's already at 125 seconds in, so bring it to 145 (just before scrobble point)
        $this->assertFalse($this->now_playing_called);

        // Now for the important bit of the test!        
        $this->sendStart($this->track1);
        $this->assertFalse($this->now_playing_called); // should not switch to the track immediately
        $this->assertNull($this->now_playing_called_with);
        
        $this->sendTick(10); // get first track past the scrobble point (145 -> 155, but track2 is still 'now playing'-able)(
                        
        $this->assertTrue($this->now_playing_called); // it should switch to 2nd track
        $this->assertSame($this->now_playing_called_with, $this->track1);        
    }
    
//    public function test_no_scrobbling_under_30s()
//    {
//        
//    }
//    
//    public function test_no_scrobbling_unplayed()
//    {
//        
//    }
//    
//    public function test_stop_all_tracks_on_shutdown()
//    {
//        
//    }
}