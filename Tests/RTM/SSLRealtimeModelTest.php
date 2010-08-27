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

class SSLRealtimeModelTest_SSLRepo extends SSLRepo
{
    public $decks = array();
    public $call_count = 0;
    public $decks_called = array();
    
    public function newRealtimeModelDeck($deck_number)
    {
        $this->call_count++;
        $this->decks_called[] = $deck_number;
        return $this->decks[$deck_number];
    }
}

class SSLRealtimeModelTest extends PHPUnit_Framework_TestCase implements TrackChangeObserver
{
    protected $rtm;
    protected $repo;

    /**
     * @var TrackChangeEventList
     */
    protected $tcel;
    protected $decks = array();
    
    public function setUp()
    {
        $this->repo = new SSLRealtimeModelTest_SSLRepo();
        Inject::map('SSLRepo', $this->repo);
        
        $this->tcel = null;
        $this->rtm = new SSLRealtimeModel();
        $this->rtm->addTrackChangeObserver($this);
        foreach( array(0, 1, 2) as $i)
        {
            $this->decks[$i] = $this->getMock(
            	'SSLRealtimeModelDeck', 
                array(
                	'notify', 
                	'trackStarted', 'trackStopped', 'trackUpdated', 
                	'getCurrentTrack', 'getPreviousTrack'
                ), 
                array($i)
            );
        }
        $this->repo->decks = $this->decks;
    }
    
    public function tearDown()
    {
        Inject::reset();
    }
    
    public function notifyTrackChange(TrackChangeEventList $events)
    {
        $this->tcel = $events;
    }
    
    public function trackMock($id, $deck)
    {
        $t = $this->getMock('SSLTrack');
        
        // these shouldn't really be needed by SSLRealtimeModelDeck
        $t->expects($this->never()) ->method('getLengthInSeconds');
        $t->expects($this->never()) ->method('getPlayed');
        $t->expects($this->never()) ->method('getPlaytime');
        
        $t->expects($this->any()) ->method('getRow') ->will($this->returnValue($id));
        $t->expects($this->any()) ->method('getDeck')->will($this->returnValue($deck));
        return $t;
    }    
    
    public function test_initial_state()
    {
        $this->assertEquals($this->rtm->getDeckIDs(), array());
        $this->assertNull($this->tcel);
    }
    
    /**
     * @depends test_initial_state
     */
    public function test_empty_update()
    {
        $this->rtm->notifyDiff( new SSLHistoryDiffDom( array(
        )));
        
        $this->assertEquals($this->rtm->getDeckIDs(), array());
        $this->assertNull($this->tcel);
    }
    
    /**
     * @depends test_empty_update
     */
    public function test_single_deck_update()
    {
        $track0 = $this->trackMock(123, 0);
        
        $this->decks[0]->expects($this->once())
                       ->method('notify')
                       ->will($this->returnValue(null));
                  
        $this->decks[0]->expects($this->once())
                       ->method('trackStarted')
                       ->will($this->returnValue($track0));
                       
        $this->rtm->notifyDiff( new SSLHistoryDiffDom( array(
            $track0
        )));
        
        $this->assertEquals($this->rtm->getDeckIDs(), array( 0 ));
        $this->assertNotNull($this->tcel);
        $this->assertEquals($this->tcel[0], new TrackStartedEvent($track0));
        $this->assertEquals($this->repo->decks_called, array(0));
        $this->assertEquals($this->repo->call_count, 1);
    }

    public function test_single_deck_update_2()
    {
        $track0 = $this->trackMock(123, 1);
        
        $this->decks[1]->expects($this->once())
                       ->method('notify')
                       ->will($this->returnValue(null));
                  
        $this->decks[1]->expects($this->once())
                       ->method('trackStarted')
                       ->will($this->returnValue($track0));
                       
        $this->rtm->notifyDiff( new SSLHistoryDiffDom( array(
            $track0
        )));
        
        $this->assertEquals($this->rtm->getDeckIDs(), array( 1 ));
        $this->assertNotNull($this->tcel);
        $this->assertEquals($this->tcel[0], new TrackStartedEvent($track0));
        $this->assertEquals($this->repo->decks_called, array(1));
        $this->assertEquals($this->repo->call_count, 1);
    }
    
    /**
     * @depends test_single_deck_update
     * @depends test_single_deck_update_2
     */
    public function test_tripple_deck_update()
    {
        $tracks = array();
        foreach(array(0, 1, 2) as $i)
        {
            $track = $this->trackMock(100+$i, $i);
                                  
            $this->decks[$i]->expects($this->once())
                           ->method('notify')
                           ->will($this->returnValue(null));
                      
            $this->decks[$i]->expects($this->once())
                           ->method('trackStarted')
                           ->will($this->returnValue($track));

            array_unshift($tracks, $track); // populate it backwards on purpose
        }
                       
        $this->rtm->notifyDiff( new SSLHistoryDiffDom( $tracks ));    
        $this->assertEquals($this->rtm->getDeckIDs(), array( 0, 1, 2 ));
        $this->assertNotNull($this->tcel);
        $this->assertEquals($this->repo->decks_called, array(2,1,0));
        $this->assertEquals($this->repo->call_count, 3);
        
        foreach(array(0, 1, 2) as $i)
        {
            $this->assertEquals($this->tcel[$i], new TrackStartedEvent($tracks[$i]));
        }
    }
}