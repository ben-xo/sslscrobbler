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

/*
    Test plan for 'Now Playing' logic:
    
	Templates:
    START    = (length 300, !isPlayed, playtime 0)
    PLAYING  = (length 300,  isPlayed, playtime 125)
    PLAYING2 = (length 300,  isPlayed, playtime 150)
    PLAYED   = (length 300,  isPlayed, playtime 300)
    
    Actions:
    1. Add track 123 PLAYING
    1.1. Add track 456 PLAYING
    1.1.1. Stop track 456 PLAYING
    1.1.2. Stop track 123 PLAYING
    1.1.3. Stop tracks 123 PLAYING and 456 PLAYING
    1.2. Stop track 123 PLAYING
    1.3. Wait 175
    1.3.1. Add track 456 PLAYING
    1.3.1.1. Wait 175
    1.3.1.1.1. Add track 789 PLAYING
    1.3.1.1.1.1. Wait 175
    1.3.2. Add track 456 START
    1.3.2.1. Wait 45
    1.4. Wait 170
    1.4.1. Add track 456 PLAYING
    1.4.1.1. Wait 10
    2. Add track 123 START
    2.1. Add track 456 PLAYING
    
    Test Definitions:
    OQT = Only Queued Track
    OQTPNPP = Oldest Queued Track Past Now Playing Point
    QIE = Queue Is Empty
    SQMRNP = Still Queued Most Recently Now Playing
    
    Tests:
    1.a >> When the first track is started, "Now Playing first track" is sent immediately as 1st is OQT
    1.1.a >> When the second track is then started, no signal is sent immediately as 1st is OQTPNPP
    1.1.1.a >> When the second track is then stopped, no signal is sent immediately as 1st is OQTPNPP
    1.1.2.a >> When the first track is stopped, "Now Playing second track" is sent immediately as 2nd is OQT
    1.1.3.a >> When both tracks are stopped at the same time, "Now Playing Stopped" is sent immediately as QIE
    1.2.a >> When the only track is removed, "Now Playing Stopped" is sent immediately as QIE
    1.3.a >> When the first track has played through to the end according to the timer, but not stopped, no signal is sent immediately as 1st is OQT
    1.3.1.a >> When the second track is then started, "Now Playing second track" is sent immediately as 2nd is OQTPNPP
    1.3.1.1.a >> When the second track has played through to the end according to the timer, but not stopped, no signal is sent immediately as 2nd track is SQMRNP
    1.3.1.1.1.a >> When the third track is then started, "Now Playing third track" is sent immediately as 3rd is OQTPNPP
    1.3.1.1.1.1.a >> When the third track has played through to the end according to the timer, but not stopped, no signal is sent immediately as 3rd track is SQMRNP
    1.3.2.a >> When the second track is then started, no signal is sent immediately as 1st track is SQMRNP
    1.3.2.1.a >> When the second track has played past NP point according to timer, "Now Playing second track" is sent immediately as 2nd track is OQTPNPP
    1.4.a >> When the first track has played for some time, no signal is sent immediately as 1st track is OQT
    1.4.1.a >> When the second track is then started, no signal is immediately sent as 1st is OQTPNPP
    1.4.1.1.a >> When the first track has played through to the end according to the timer, but not stopped, "Now Playing second track" is sent immediately as 2nd track OQTPNPP
    2.a >> When the first track is started, "Now Playing first track" is sent immediately as 1st is OQT
    2.1.a >> When the second track is started, "Now Playing second track" is sent immediately, as 2nd is OQTPNPP
    

*/

/**
 * A version of ScrobblerTrackModelFactory that does a lot more probe-able logging.
 */
class NowPlayingModelTest_SSLRepo extends SSLRepo
{
    public $decks = array();
    public $call_count = 0;
    
    public function newScrobblerTrackModel(SSLTrack $track)
    {
        $this->call_count++;
        $track_row = $track->getRow();
        $this->decks[$track_row] = parent::newScrobblerTrackModel($track);
        return $this->decks[$track_row];
    }
}

class NowPlayingModelTest extends PHPUnit_Framework_TestCase implements NowPlayingObserver
{
    /**
     * @var NowPlayingModel
     */
    protected $srm;
    
    /**
     * @var NowPlayingModelTest_SSLRepo
     */
    protected $stm_factory;
    
    protected $track123_PLAYING;
    protected $track456_PLAYING;
    protected $track789_PLAYING;
    
    protected $track123_START;
    protected $track456_START;
    
    protected $track123_PLAYED_0;
    protected $track123_PLAYING_1;
    protected $track123_PLAYING_2;
    protected $track456_PLAYED_0;
    protected $track456_PLAYING_1;
    protected $track456_PLAYING_2;
    protected $track789_PLAYED_0;
    protected $track789_PLAYING_1;
    protected $track789_PLAYING_2;
    
    
    // self-shunt variables
    
    protected $now_playing_called;
    protected $now_playing_called_with;
    
    public function setUp()
    {
        // tracks
        $stm_test = new ScrobblerTrackModelTest();
        $this->track123_PLAYING = $stm_test->trackMock(123, 300, true, 125);
        $this->track456_PLAYING = $stm_test->trackMock(456, 300, true, 125);
        $this->track789_PLAYING = $stm_test->trackMock(789, 300, true, 125);
        $this->track123_START   = $stm_test->trackMock(123, 300, false, 0);
        $this->track456_START   = $stm_test->trackMock(456, 300, false, 0);
        
        $this->track123_PLAYED_0  = $stm_test->trackMock(123, 300, true, 125, 0);
        $this->track123_PLAYING_1 = $stm_test->trackMock(456, 300, true, 125, 1);
        $this->track123_PLAYING_2 = $stm_test->trackMock(789, 300, true, 125, 2);
       
        $this->track456_PLAYED_0  = $stm_test->trackMock(123, 300, true, 125, 0);
        $this->track456_PLAYING_1 = $stm_test->trackMock(456, 300, true, 125, 1);
        $this->track456_PLAYING_2 = $stm_test->trackMock(789, 300, true, 125, 2);
        
        $this->track789_PLAYED_0  = $stm_test->trackMock(123, 300, true, 125, 0);
        $this->track789_PLAYING_1 = $stm_test->trackMock(456, 300, true, 125, 1);
        $this->track789_PLAYING_2 = $stm_test->trackMock(789, 300, true, 125, 2);
        
        
        // deck models
        $this->stm_factory = new NowPlayingModelTest_SSLRepo();
        Inject::map('SSLRepo', $this->stm_factory);
        
        $this->now_playing_called = false;
        $this->now_playing_called_with = null;
        
        $this->srm = new NowPlayingModel();
        $this->srm->addNowPlayingObserver($this);
    }
    
    public function tearDown()
    {
        Inject::reset();
    }
    
    // support methods
        
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
    
    public function sendMultiStart(array $tracks)
    {
        $this->now_playing_called = false;
        $this->now_playing_called_with = null;
        
        $event_tracks = array();
        foreach($tracks as $track)
        {
            $event_tracks[] = new TrackStartedEvent($track);    
        }
        
        // events
        $events = new TrackChangeEventList($event_tracks);
            
        $this->srm->notifyTrackChange($events);
    }
    
    public function sendMultiStop(array $tracks)
    {
        $this->now_playing_called = false;
        $this->now_playing_called_with = null;
        
        $event_tracks = array();
        foreach($tracks as $track)
        {
            $event_tracks[] = new TrackStoppedEvent($track);    
        }
        
        // events
        $events = new TrackChangeEventList($event_tracks);
            
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
    
    // common assertions
    
    public function assertNowPlayingSentForTrack(SSLTrack $track)
    {
        $this->assertTrue($this->now_playing_called);
        $this->assertSame($this->now_playing_called_with, $track);
    }
    
    public function assertNowPlayingSentStop()
    {
        $this->assertTrue($this->now_playing_called);
        $this->assertNull($this->now_playing_called_with);
    }
    
    public function assertNowPlayingNotSent()
    {
        $this->assertFalse($this->now_playing_called);
        $this->assertNull($this->now_playing_called_with);
    }
    
    public function assertQueueSize($size)
    {
        $this->assertEquals($size, $this->srm->getQueueSize());
    }
    
    public function assertDeckCount($count)
    {
        $this->assertEquals($count, $this->stm_factory->call_count);
    }
    
    // here be the tests!
    
    public function test_initial_state()
    {
        $this->assertNowPlayingNotSent();
        $this->assertQueueSize(0);
        $this->assertDeckCount(0);
    }
    
    public function test_empty_deck_ticking_does_nothing_interesting()
    {
        $this->sendTick(1000);
        $this->assertNowPlayingNotSent();
        $this->assertQueueSize(0);
        $this->assertDeckCount(0);
    }
    
    // Tests for tracks that are marked "now playing" by their model when they're added.
    // This stuff should exercise the queueing / de-queueing logic.

    public function test_queue_size()
    {
        $srm = $this->srm;
        
        // setup
        $events1 = new TrackChangeEventList( 
            array(new TrackStartedEvent($this->track123_PLAYING)) 
        );
        
        $events2 = new TrackChangeEventList( 
            array(new TrackStartedEvent($this->track456_PLAYING), 
                  new TrackStartedEvent($this->track789_PLAYING)) 
        );
        
        // track start
        $srm->notifyTrackChange($events1);
        $this->assertQueueSize(1);
        $this->assertDeckCount(1);
                
        // this tests that the same track is not double-added 
        $srm->notifyTrackChange($events1);
        $this->assertQueueSize(1);
        $this->assertDeckCount(1);
                
        // second pair of track starts
        $srm->notifyTrackChange($events2);
        $this->assertQueueSize(3);
        $this->assertDeckCount(3);
    }
    
    // Here begin the tests for the actual Now Playing logic
    
    /**
     * 1.a >> When the first track is started, "Now Playing first track" is sent immediately as 1st is OQT
     * 
     * @depends test_queue_size
     */
    public function test_start_a_track_sets_now_playing()
    {
        $this->sendStart($this->track123_PLAYING);
        $this->assertQueueSize(1);
        $this->assertDeckCount(1);
        $this->assertNowPlayingSentForTrack($this->track123_PLAYING);
    }
    
    /**
     * 1.1.a >> When the second track is then started, no signal is sent immediately as 1st is OQTPNPP
     * 
     * @depends test_start_a_track_sets_now_playing
     */
    public function test_start_second_track_leaves_first_now_playing()
    {
        // chain to 1.a
        $this->test_start_a_track_sets_now_playing();
        
        $this->sendStart($this->track456_PLAYING);

        // even though there are 2 in the queue...
        $this->assertQueueSize(2);
        $this->assertDeckCount(2);
                
        // ...nobody was notified that the 2nd one is now playing
        $this->assertNowPlayingNotSent();
    }

    /**
     * 1.1.1.a >> When the second track is then stopped, no signal is sent immediately as 1st is OQTPNPP
     * 
     * @depends test_start_second_track_leaves_first_now_playing
     */
    public function test_second_track_stopped_leaves_first_playing()
    {
        // chain to 1.1.a
        $this->test_start_second_track_leaves_first_now_playing();
        
        $this->sendStop($this->track456_PLAYING);

        $this->assertQueueSize(1);
        $this->assertDeckCount(2);
        $this->assertNowPlayingNotSent();
    }
    
    /**
     * 1.1.2.a >> When the first track is stopped, "Now Playing second track" is sent immediately as 2nd is OQT
     * 
     * @depends test_start_second_track_leaves_first_now_playing
     */
    public function test_stop_first_track_sets_second_now_playing()
    {
        // chain to 1.1.a
        $this->test_start_second_track_leaves_first_now_playing();
        
        $this->sendStop($this->track123_PLAYING);

        $this->assertQueueSize(1);
        $this->assertDeckCount(2);
        $this->assertNowPlayingSentForTrack($this->track456_PLAYING);
    }
    
    /**
     * 1.1.3.a >> When both tracks are stopped at the same time, "Now Playing Stopped" is sent immediately as QIE
     */
    public function test_stop_all_sends_stop_notification()
    {
        // chain to 1.1.a
        $this->test_start_second_track_leaves_first_now_playing();
        $this->sendMultiStop(array( $this->track123_PLAYING, $this->track456_PLAYING));
        $this->assertQueueSize(0);
        $this->assertDeckCount(2);
        $this->assertNowPlayingSentStop();
    }
    
    /**
     * 1.2.a >> When the only track is removed, "Now Playing Stopped" is sent immediately as QIE
     */
    public function test_stop_only_sends_stop_notification()
    {
        // chain to 1.a
        $this->test_start_a_track_sets_now_playing();
        $this->sendStop($this->track123_PLAYING);
        $this->assertQueueSize(0);
        $this->assertDeckCount(1);
        $this->assertNowPlayingSentStop();
    }
    
    /**
     * 1.3.a >> When the first track has played through to the end according to the timer, 
     * but not stopped, no signal is sent immediately as 1st is OQT
     */
    public function test_first_track_plays_to_end_without_notice()
    {
        // chain to 1.a
        $this->test_start_a_track_sets_now_playing();
        $this->sendTick(175);
        $this->assertQueueSize(1);
        $this->assertDeckCount(1);
        $this->assertNowPlayingNotSent();
    }
    
    /**
     * 1.3.1.a >> When the second track is then started, "Now Playing second track" is sent 
     * immediately as 2nd is OQTPNPP
     */
    public function test_second_track_after_first_ended_sends_now_playing()
    {
       // chain to 1.3.a
       $this->test_first_track_plays_to_end_without_notice();
       $this->sendStart($this->track456_PLAYING);
       $this->assertQueueSize(2);
       $this->assertDeckCount(2);
       $this->assertNowPlayingSentForTrack($this->track456_PLAYING);
    }
    
    /**
     * 1.3.1.1.a >> When the second track has played through to the end according to the 
     * timer, but not stopped, no signal is sent immediately as 2nd track is SQMRNP
     */
    public function test_second_track_plays_to_end_without_notice()
    {
        // chain to 1.3.1.a
        $this->test_second_track_after_first_ended_sends_now_playing();
        $this->sendTick(175);
        $this->assertQueueSize(2);
        $this->assertDeckCount(2);
        $this->assertNowPlayingNotSent();
    }
    
    /**
     * 1.3.1.1.1.a >> When the third track is then started, "Now Playing third track" 
     * is sent immediately as 3rd is OQTPNPP
     */
    public function test_third_track_after_second_ended_sends_now_playing()
    {
       // chain to 1.3.1.1.a
       $this->test_second_track_plays_to_end_without_notice();
       $this->sendStart($this->track789_PLAYING);
       $this->assertQueueSize(3);
       $this->assertDeckCount(3);
       $this->assertNowPlayingSentForTrack($this->track789_PLAYING);
    }
    
    /**
     * 1.3.1.1.1.1.a >> When the third track has played through to the end according 
     * to the timer, but not stopped, no signal is sent immediately as 3rd track is SQMRNP
     */
    public function test_third_track_plays_to_end_without_notice()
    {
        // chain to 1.3.1.1.1.a
        $this->test_third_track_after_second_ended_sends_now_playing();
        $this->sendTick(175);
        $this->assertQueueSize(3);
        $this->assertDeckCount(3);
        $this->assertNowPlayingNotSent();
    } 

    /**
     * 1.3.2.a >> When the second track is then started, no signal is sent immediately as 
     * 1st track is SQMRNP
     */
    public function test_second_track_doesnt_immediately_play_when_not_past_np_point_even_though_first_is_ended()
    {
       // chain to 1.3.a
       $this->test_first_track_plays_to_end_without_notice();
       $this->sendStart($this->track456_START);
       $this->assertQueueSize(2);
       $this->assertDeckCount(2);
       $this->assertNowPlayingNotSent();
    }
    
    /**
     * 1.3.2.1.a >> When the second track has played past NP point according to timer, 
     * "Now Playing second track" is sent immediately as 2nd track is OQTPNPP
     */
    public function test_second_track_becomes_now_playing_after_np_point()
    {
       // chain to 1.3.2.a
       $this->test_second_track_doesnt_immediately_play_when_not_past_np_point_even_though_first_is_ended();
       $this->sendTick(45);
       $this->assertQueueSize(2);
       $this->assertDeckCount(2);
       $this->assertNowPlayingSentForTrack($this->track456_START);
    }
    
    /**
     * 1.4.a >> When the first track has played for some time, no signal is sent immediately 
     * as 1st track is OQT
     */
    public function test_elapsing_timer_doesnt_affect_now_playing()
    {
        // chain to 1.a
        $this->test_start_a_track_sets_now_playing();
        $this->sendTick(170);
        $this->assertQueueSize(1);
        $this->assertDeckCount(1);
        $this->assertNowPlayingNotSent();
    }
    
    /**
     * 1.4.1.a >> When the second track is then started, no signal is immediately sent as 1st is OQTPNPP
     */
    public function test_second_track_doesnt_immediately_play_when_not_past_np_point()
    {
        // chain to 1.4.a
        $this->test_elapsing_timer_doesnt_affect_now_playing();
        $this->sendStart($this->track456_PLAYING);
        $this->assertQueueSize(2);
        $this->assertDeckCount(2);
        $this->assertNowPlayingNotSent();
    }
    
    /**
     * 1.4.1.1.a >> When the first track has played through to the end according to the timer, but not 
     * stopped, "Now Playing second track" is sent immediately as 2nd track OQTPNPP
     */
    public function test_second_track_becomes_now_playing_after_first_ends()
    {
        // chain to 1.4.1.a
        $this->test_second_track_doesnt_immediately_play_when_not_past_np_point();
        $this->sendTick(10);
        $this->assertQueueSize(2);
        $this->assertDeckCount(2);
        $this->assertNowPlayingSentForTrack($this->track456_PLAYING);        
    }
    
    /**
     * 2.a >> When the first track is started, "Now Playing first track" is sent immediately as 1st is OQT
     * 
     * (this differs from 1.a because in 1.a the track was past the NP point and in 2.a it is not)
     */
    public function test_start_track_sets_now_playing_2()
    {
        $this->sendStart($this->track123_START);
        $this->assertQueueSize(1);
        $this->assertDeckCount(1);
        $this->assertNowPlayingSentForTrack($this->track123_START);
    }
    
    /**
     * 2.1.a >> When the second track is started, "Now Playing second track" is sent immediately, 
     * as 2nd is OQTPNPP
     */
    public function test_second_track_becomes_now_playing_immediately()
    {
       // chain to 2.a
       $this->test_start_track_sets_now_playing_2();
       $this->sendStart($this->track456_PLAYING);
       $this->assertQueueSize(2);
       $this->assertDeckCount(2);
       $this->assertNowPlayingSentForTrack($this->track456_PLAYING);
    }    
}