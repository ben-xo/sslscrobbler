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

require_once 'ScrobblerTrackModelTest.php';

class SSLRealtimeModelDeckTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var SSLRealtimeModelDeck
     */
    protected $srmd;
    
    public function setUp()
    {
        $this->srmd = new SSLRealtimeModelDeck(0);
        $this->srmd->setDebug(false);
    }

    public function trackMock($id, $state)
    {
        $t = $this->getMock('SSLTrack');
        
        // these shouldn't really be needed by SSLRealtimeModelDeck
        $t->expects($this->never()) ->method('getLengthInSeconds');
        $t->expects($this->never()) ->method('getPlayed');
        $t->expects($this->never()) ->method('getPlaytime');
        
        $t->expects($this->any()) ->method('getRow')   ->will($this->returnValue($id));
        $t->expects($this->any()) ->method('getStatus')->will($this->returnValue($state));
        return $t;
    }
    
    public function test_initial_state()
    {
        $this->assertFalse($this->srmd->trackStarted());
        $this->assertFalse($this->srmd->trackStopped());
        $this->assertFalse($this->srmd->trackUpdated());
        
        $this->assertNull($this->srmd->getCurrentTrack());
        $this->assertNull($this->srmd->getPreviousTrack());
    }
    
    // tests for NEW / PLAYING / PLAYED tracks.
    
    /**
     * @depends test_initial_state
     */
    public function test_start_one_track()
    {
        $track0 = $this->trackMock(123, 'NEW');
        $this->srmd->notify( new SSLHistoryDiffDom( array(
           $track0
        ) ) );
        
        $this->assertTrue($this->srmd->trackStarted());
        $this->assertFalse($this->srmd->trackStopped());
        $this->assertFalse($this->srmd->trackUpdated());
        
        $this->assertSame($this->srmd->getCurrentTrack(), $track0);
        $this->assertNull($this->srmd->getPreviousTrack());
    }
    
    /**
     * @depends test_start_one_track
     */
    public function test_start_two_new_tracks()
    {
        $track0 = $this->trackMock(123, 'NEW');
        $track1 = $this->trackMock(456, 'NEW');
        $this->srmd->notify( new SSLHistoryDiffDom( array(
           $track0, $track1
        ) ) );
        
        $this->assertTrue($this->srmd->trackStarted());
        $this->assertFalse($this->srmd->trackStopped());
        $this->assertFalse($this->srmd->trackUpdated());

        $this->assertSame($this->srmd->getCurrentTrack(), $track1);
        $this->assertNull($this->srmd->getPreviousTrack()); // NEW to NEW implies that the first one was SKIPPED
    }
    
    /**
     * Compound updates should sort into the right order, so the 
     * result should be the same as the previous test 
     * 
     * @depends test_start_two_new_tracks
     */
    public function test_start_two_new_tracks_in_wrong_order_compound()
    {
        $track0 = $this->trackMock(456, 'NEW');
        $track1 = $this->trackMock(123, 'NEW');
        $this->srmd->notify( new SSLHistoryDiffDom( array(
           $track0, $track1
        ) ) );
        
        $this->assertTrue($this->srmd->trackStarted());
        $this->assertFalse($this->srmd->trackStopped());
        $this->assertFalse($this->srmd->trackUpdated());

        $this->assertSame($this->srmd->getCurrentTrack(), $track0);
        $this->assertNull($this->srmd->getPreviousTrack()); // NEW to NEW implies that the first one was SKIPPED
    }

    /**
     * @depends test_start_one_track
     */
    public function test_start_two_played_tracks()
    {
        $track0 = $this->trackMock(123, 'PLAYED');
        $track1 = $this->trackMock(456, 'PLAYED');
        $this->srmd->notify( new SSLHistoryDiffDom( array(
           $track0, $track1
        ) ) );
        
        $this->assertFalse($this->srmd->trackStarted());
        $this->assertFalse($this->srmd->trackStopped());
        $this->assertFalse($this->srmd->trackUpdated());

        $this->assertNull($this->srmd->getCurrentTrack());
        $this->assertSame($this->srmd->getPreviousTrack(), $track1);
    }

    /**
     * @depends test_start_one_track
     */
    public function test_start_two_tracks__played_new()
    {
        $track0 = $this->trackMock(123, 'PLAYED');
        $track1 = $this->trackMock(456, 'NEW');
        $this->srmd->notify( new SSLHistoryDiffDom( array(
           $track0, $track1
        ) ) );
        
        $this->assertTrue($this->srmd->trackStarted());
        $this->assertFalse($this->srmd->trackStopped());
        $this->assertFalse($this->srmd->trackUpdated());

        $this->assertSame($this->srmd->getCurrentTrack(), $track1);
        $this->assertSame($this->srmd->getPreviousTrack(), $track0);
    }

    /**
     * @depends test_start_one_track
     */
    public function test_start_two_tracks__new_played()
    {
        $track0 = $this->trackMock(123, 'NEW');
        $track1 = $this->trackMock(456, 'PLAYED');
        $this->srmd->notify( new SSLHistoryDiffDom( array(
           $track0, $track1
        ) ) );
        
        $this->assertFalse($this->srmd->trackStarted());
        $this->assertFalse($this->srmd->trackStopped());
        $this->assertFalse($this->srmd->trackUpdated());

        $this->assertNull($this->srmd->getCurrentTrack());
        $this->assertSame($this->srmd->getPreviousTrack(), $track1);
    }
    
    /**
     * @depends test_start_two_new_tracks
     */
    public function test_start_three_new_tracks()
    {
        $track0 = $this->trackMock(123, 'NEW');
        $track1 = $this->trackMock(456, 'NEW');
        $track2 = $this->trackMock(789, 'NEW');
        $this->srmd->notify( new SSLHistoryDiffDom( array(
           $track0, $track1, $track2
        ) ) );
        
        $this->assertTrue($this->srmd->trackStarted());
        $this->assertFalse($this->srmd->trackStopped());
        $this->assertFalse($this->srmd->trackUpdated());

        $this->assertSame($this->srmd->getCurrentTrack(), $track2);
        $this->assertNull($this->srmd->getPreviousTrack());
    }
    
    /**
     * @depends test_start_one_track
     */
    public function test_start_one_track_stop_same_track_sequence()
    {
        $track0 = $this->trackMock(123, 'NEW');
        $this->srmd->notify( new SSLHistoryDiffDom( array(
           $track0
        ) ) );
        
        // this is the intended case: start a track (and notify)...
        $this->assertTrue($this->srmd->trackStarted());
        $this->assertFalse($this->srmd->trackStopped());
        $this->assertFalse($this->srmd->trackUpdated());
        
        $this->assertSame($this->srmd->getCurrentTrack(), $track0);
        $this->assertNull($this->srmd->getPreviousTrack());

        $track1 = $this->trackMock(123, 'PLAYED');
        $this->srmd->notify( new SSLHistoryDiffDom( array(
           $track1
        ) ) );
        
        // ...then stop it again (and notify)
        $this->assertFalse($this->srmd->trackStarted());
        $this->assertTrue($this->srmd->trackStopped());
        $this->assertFalse($this->srmd->trackUpdated());

        $this->assertNull($this->srmd->getCurrentTrack());
        $this->assertSame($this->srmd->getPreviousTrack(), $track1);
    } 

    /**
     * @depends test_start_one_track
     */
    public function test_start_one_track_then_update_it()
    {
        $track0 = $this->trackMock(123, 'NEW');
        $this->srmd->notify( new SSLHistoryDiffDom( array(
           $track0
        ) ) );
        
        // this is the intended case: start a track (and notify)...
        $this->assertTrue($this->srmd->trackStarted());
        $this->assertFalse($this->srmd->trackStopped());
        $this->assertFalse($this->srmd->trackUpdated());
        
        $this->assertSame($this->srmd->getCurrentTrack(), $track0);
        $this->assertNull($this->srmd->getPreviousTrack());

        $track1 = $this->trackMock(123, 'PLAYING');
        $this->srmd->notify( new SSLHistoryDiffDom( array(
           $track1
        ) ) );
        
        // ...then stop it again (and notify)
        $this->assertFalse($this->srmd->trackStarted());
        $this->assertFalse($this->srmd->trackStopped());
        $this->assertTrue($this->srmd->trackUpdated());

        $this->assertSame($this->srmd->getCurrentTrack(), $track1);
        $this->assertNull($this->srmd->getPreviousTrack());
    } 

    /**
     * Same as previous test, but NEW -> NEW (same info but new object)
     * 
     * @depends test_start_one_track
     */
    public function test_start_one_track_then_update_it_2()
    {
        $track0 = $this->trackMock(123, 'NEW');
        $this->srmd->notify( new SSLHistoryDiffDom( array(
           $track0
        ) ) );
        
        // this is the intended case: start a track (and notify)...
        $this->assertTrue($this->srmd->trackStarted());
        $this->assertFalse($this->srmd->trackStopped());
        $this->assertFalse($this->srmd->trackUpdated());
        
        $this->assertSame($this->srmd->getCurrentTrack(), $track0);
        $this->assertNull($this->srmd->getPreviousTrack());

        $track1 = $this->trackMock(123, 'NEW');
        $this->srmd->notify( new SSLHistoryDiffDom( array(
           $track1
        ) ) );
        
        // ...then stop it again (and notify)
        $this->assertFalse($this->srmd->trackStarted());
        $this->assertFalse($this->srmd->trackStopped());
        $this->assertTrue($this->srmd->trackUpdated());

        $this->assertSame($this->srmd->getCurrentTrack(), $track1);
        $this->assertNull($this->srmd->getPreviousTrack());
    }
    
    /**
     * @depends test_start_one_track_stop_same_track_sequence
     */
    public function test_start_one_track_stop_same_track_compound()
    {
        $track0 = $this->trackMock(123, 'NEW');
        $track1 = $this->trackMock(123, 'PLAYED');
        $this->srmd->notify( new SSLHistoryDiffDom( array(
           $track0, $track1
        ) ) );
        
        // if you receive a start and stop in a single update of the same track,
        // then there's really no point making a fuss about it.
        $this->assertFalse($this->srmd->trackStarted());
        $this->assertFalse($this->srmd->trackStopped());
        $this->assertFalse($this->srmd->trackUpdated());

        $this->assertNull($this->srmd->getCurrentTrack());
        $this->assertSame($this->srmd->getPreviousTrack(), $track1);
    }
    
    /**
     * @depends test_start_one_track_stop_same_track_compound
     */
    public function test_start_one_track_sequence_then_stop_that_track_and_start_another_compound()
    {
        $track0 = $this->trackMock(123, 'NEW');
        $this->srmd->notify( new SSLHistoryDiffDom( array(
           $track0
        ) ) );
        
        // this is the intended case: start a track (and notify)...
        $this->assertTrue($this->srmd->trackStarted());
        $this->assertFalse($this->srmd->trackStopped());
        $this->assertFalse($this->srmd->trackUpdated());
        
        $this->assertSame($this->srmd->getCurrentTrack(), $track0);
        $this->assertNull($this->srmd->getPreviousTrack());

        $track1 = $this->trackMock(123, 'PLAYED');
        $track2 = $this->trackMock(456, 'NEW');
        $this->srmd->notify( new SSLHistoryDiffDom( array(
           $track1, $track2
        ) ) );
        
        
        // ...then stop it again (and notify) and start another (and notify)
        $this->assertTrue($this->srmd->trackStarted());
        $this->assertTrue($this->srmd->trackStopped());
        $this->assertFalse($this->srmd->trackUpdated());

        $this->assertSame($this->srmd->getCurrentTrack(), $track2);
        $this->assertSame($this->srmd->getPreviousTrack(), $track1);
    }
    
    /**
     * Unlike out-of-order track in a single diff, the model should
     * ignore the 2nd update because the track is irrelevant.
     * 
     * @depends test_start_one_track_stop_same_track_compound
     */
    public function test_start_one_track_sequence_then_start_another_out_of_order()
    {
        $track0 = $this->trackMock(456, 'NEW');
        $this->srmd->notify( new SSLHistoryDiffDom( array(
           $track0
        ) ) );
        
        $this->assertTrue($this->srmd->trackStarted());
        $this->assertFalse($this->srmd->trackStopped());
        $this->assertFalse($this->srmd->trackUpdated());
        
        $this->assertSame($this->srmd->getCurrentTrack(), $track0);
        $this->assertNull($this->srmd->getPreviousTrack());

        $track1 = $this->trackMock(123, 'NEW');
        $this->srmd->notify( new SSLHistoryDiffDom( array(
           $track1
        ) ) );
        
        $this->assertFalse($this->srmd->trackStarted());
        $this->assertFalse($this->srmd->trackStopped());
        $this->assertFalse($this->srmd->trackUpdated());

        $this->assertSame($this->srmd->getCurrentTrack(), $track0);
        $this->assertNull($this->srmd->getPreviousTrack());
    }
    
    /**
     * Double-notification of the same actual track object should 
     * not have the effect twice.
     * 
     * @depends test_start_one_track_stop_same_track_compound
     */
    public function test_start_one_track_but_notify_twice()
    {
        $track0 = $this->trackMock(123, 'NEW');
        
        $this->srmd->notify( new SSLHistoryDiffDom( array(
           $track0
        ) ) );
        
        $this->assertTrue($this->srmd->trackStarted());
        $this->assertFalse($this->srmd->trackStopped());
        $this->assertFalse($this->srmd->trackUpdated());
        
        $this->assertSame($this->srmd->getCurrentTrack(), $track0);
        $this->assertNull($this->srmd->getPreviousTrack());

        $this->srmd->notify( new SSLHistoryDiffDom( array(
           $track0
        ) ) );
        
        $this->assertFalse($this->srmd->trackStarted());
        $this->assertFalse($this->srmd->trackStopped());
        $this->assertFalse($this->srmd->trackUpdated());

        $this->assertSame($this->srmd->getCurrentTrack(), $track0);
        $this->assertNull($this->srmd->getPreviousTrack());
    }    
    
    /**
     * @depends test_start_one_track_sequence_then_stop_that_track_and_start_another_compound
     */
    public function test_start_three_tracks_compound_then_stop_one_track_sequence()
    {
        $track0 = $this->trackMock(123, 'NEW');
        $track1 = $this->trackMock(456, 'NEW');
        $track2 = $this->trackMock(789, 'NEW');
        $this->srmd->notify( new SSLHistoryDiffDom( array(
           $track0, $track1, $track2
        ) ) );
        
        // start one, then start two, then start three
        $this->assertTrue($this->srmd->trackStarted());
        $this->assertFalse($this->srmd->trackStopped());
        $this->assertFalse($this->srmd->trackUpdated());
        
        $this->assertSame($this->srmd->getCurrentTrack(), $track2);
        $this->assertNull($this->srmd->getPreviousTrack());

        $track4 = $this->trackMock(789, 'PLAYED');
        $this->srmd->notify( new SSLHistoryDiffDom( array(
           $track4
        ) ) );
        
        // now stop one
        $this->assertFalse($this->srmd->trackStarted());
        $this->assertTrue($this->srmd->trackStopped());
        $this->assertFalse($this->srmd->trackUpdated());

        $this->assertNull($this->srmd->getCurrentTrack());
        $this->assertSame($this->srmd->getPreviousTrack(), $track4);
    }
    
    // tests for NEW / SKIPPED tracks
    
    public function test_start_one_track_skip_same_track_sequence() 
    {
        $track0 = $this->trackMock(123, 'NEW');
        $this->srmd->notify( new SSLHistoryDiffDom( array(
           $track0
        ) ) );
        
        $this->assertTrue($this->srmd->trackStarted());
        $this->assertFalse($this->srmd->trackStopped());
        $this->assertFalse($this->srmd->trackUpdated());
        
        $this->assertSame($this->srmd->getCurrentTrack(), $track0);
        $this->assertNull($this->srmd->getPreviousTrack());

        $track1 = $this->trackMock(123, 'SKIPPED');
        $this->srmd->notify( new SSLHistoryDiffDom( array(
           $track1
        ) ) );
        
        $this->assertFalse($this->srmd->trackStarted());
        $this->assertTrue($this->srmd->trackStopped());
        $this->assertFalse($this->srmd->trackUpdated());

        $this->assertNull($this->srmd->getCurrentTrack());
        $this->assertNull($this->srmd->getPreviousTrack());
    }
    
    public function test_start_one_track_skip_same_track_compound() 
    {
        $track0 = $this->trackMock(123, 'NEW');
        $track1 = $this->trackMock(123, 'SKIPPED');
        $this->srmd->notify( new SSLHistoryDiffDom( array(
           $track0, $track1
        ) ) );
        
        $this->assertFalse($this->srmd->trackStarted());
        $this->assertFalse($this->srmd->trackStopped());
        $this->assertFalse($this->srmd->trackUpdated());

        $this->assertNull($this->srmd->getCurrentTrack());
        $this->assertNull($this->srmd->getPreviousTrack());
    }
    
    public function test_start_one_track_sequence_then_skip_that_track_and_start_another_compound()
    {
        $track0 = $this->trackMock(123, 'NEW');
        $this->srmd->notify( new SSLHistoryDiffDom( array(
           $track0
        ) ) );
        
        // this is the intended case: start a track (and notify)...
        $this->assertTrue($this->srmd->trackStarted());
        $this->assertFalse($this->srmd->trackStopped());
        $this->assertFalse($this->srmd->trackUpdated());
        
        $this->assertSame($this->srmd->getCurrentTrack(), $track0);
        $this->assertNull($this->srmd->getPreviousTrack());

        $track1 = $this->trackMock(123, 'SKIPPED');
        $track2 = $this->trackMock(456, 'NEW');
        $this->srmd->notify( new SSLHistoryDiffDom( array(
           $track1, $track2
        ) ) );
        
        
        // ...then stop it again (and notify) and start another (and notify)
        $this->assertTrue($this->srmd->trackStarted());
        $this->assertTrue($this->srmd->trackStopped());
        $this->assertFalse($this->srmd->trackUpdated());

        $this->assertSame($this->srmd->getCurrentTrack(), $track2);
        $this->assertNull($this->srmd->getPreviousTrack());
    }
    
    // test transitions:
    public function test_invalid_transition() 
    {
        $track0 = $this->trackMock(123, 'WTF');
        try
        {
            $this->srmd->transitionTo($track0);
            $this->fail();
        }
        catch(InvalidArgumentException $e) { }
    }
    
    public function test_EMPTY_to_EMPTY() 
    {
        // a track can not naturally be "EMPTY", as that makes no sense
        $track0 = $this->trackMock(123, 'EMPTY');
        try
        {
            $this->srmd->transitionTo($track0);
            $this->fail();
        }
        catch(SSLInvalidTransitionException $e) { }
    }
    
//    public function test_EMPTY_to_NEW() {}
//    public function test_EMPTY_to_PLAYING() {}
//    public function test_EMPTY_to_PLAYED() {}
//    public function test_EMPTY_to_SKIPPED() {}
//
//    public function test_NEW_to_EMPTY() {}
//    public function test_NEW_to_NEW() {}
//    public function test_NEW_to_PLAYING() {}
//    public function test_NEW_to_PLAYED() {}
//    public function test_NEW_to_SKIPPED() {}
//
//    public function test_PLAYING_to_EMPTY() {}
//    public function test_PLAYING_to_NEW() {}
//    public function test_PLAYING_to_PLAYING() {}
//    public function test_PLAYING_to_PLAYED() {}
//    public function test_PLAYING_to_SKIPPED() {}
//
//    public function test_PLAYED_to_EMPTY() {}
//    public function test_PLAYED_to_NEW() {}
//    public function test_PLAYED_to_PLAYING() {}
//    public function test_PLAYED_to_PLAYED() {}
//    public function test_PLAYED_to_SKIPPED() {}
//
//    public function test_SKIPPED_to_EMPTY() {}
//    public function test_SKIPPED_to_NEW() {}
//    public function test_SKIPPED_to_PLAYING() {}
//    public function test_SKIPPED_to_PLAYED() {}
//    public function test_SKIPPED_to_SKIPPED() {}
    
}