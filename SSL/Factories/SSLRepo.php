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
 * Static Repository for constructors related to Serato ScratchLive! classes.
 */
class SSLRepo implements Factory
{
    /**
     * @return SSLChunkReader
     */
    public function newChunkReader($fp)
    {
        return new SSLChunkReader($fp);
    }
    
    /**
     * @return SSLHistoryDom
     */
    public function newHistoryDom()
    {
        return new SSLHistoryDom();
    }

    /**
     * @return SSLHistoryIndexDom
     */
    public function newHistoryIndexDom()
    {
        return new SSLHistoryIndexDom();
    }

    /**
     * @return SSLLibraryDom
     */
    public function newLibraryDom()
    {
        return new SSLLibraryDom();
    }
    
    /**
     * @return SSLDom
     */
    public function newDom()
    {
        return new SSLDom();
    }
    
    /**
     * @return SSLParser
     */
    public function newParser(?SSLDom $dom = null)
    {
        return new SSLParser($dom);
    }
    
    /**
     * @return SSLChunkParser
     */
    public function newChunkParser($data = null)
    {
        return new SSLChunkParser($data);
    }
    
    /**
     * @return SSLRealtimeModelDeck
     */
    public function newRealtimeModelDeck($deck_number)
    {
        return new SSLRealtimeModelDeck($deck_number);        
    }
    
    /**
     * @return TrackStoppedEvent
     */
    public function newTrackStoppedEvent(SSLTrack $track)
    {
        return new TrackStoppedEvent($track);
    }

    /**
     * @return TrackStartedEvent
     */
    public function newTrackStartedEvent(SSLTrack $track)
    {
        return new TrackStartedEvent($track);
    }

    /**
     * @return TrackUpdatedEvent
     */
    public function newTrackUpdatedEvent(SSLTrack $track)
    {
        return new TrackUpdatedEvent($track);
    }
    
    /**
     * @return TrackChangeEventList
     */
    public function newTrackChangeEventList($events)
    {
        return new TrackChangeEventList($events);
    }
    
    /**
     * @return ScrobblerTrackModel
     */
    public function newScrobblerTrackModel(SSLTrack $started_track)
    {
        return new ScrobblerTrackModel($started_track);
    }
    
    /**
     * @return RuntimeCachingSSLTrack
     */
    public function newRuntimeCachingTrack(SSLTrackCache $cache)
    {
        return new RuntimeCachingSSLTrack($cache);
    }
    /**
     * @return SSLTrackCache 
     */
    public function newTrackCache()
    {
        return new SSLTrackCache();
    }
}