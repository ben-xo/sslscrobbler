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
 * A version of SSLTrack which is registered in a global cache. This enables
 * expensive derived properties, such as track length calculated using GetID3,
 * to be reported back to the cache which can carry these values forward
 * into new instances of SSLTrack that represent the same row but in different
 * states, so that it's unneccessary to calculate these values more than once.
 */
class RuntimeCachingSSLTrack extends SSLTrack
{
    /**
     * @var SSLTrackCache
     */
    protected $track_cache;

    public function __construct(SSLTrackCache $cache)
    {
        $this->track_cache = $cache;
        parent::__construct();
    }
    
    /**
     * This version of setLengthIfUnknown() looks in the cache that the track was
     * constructed with for a track with the same row ID. If it finds one, it 
     * inquired if this (possibly expensive) question has already been answered.
     * If it has, we use the answer from the cache.
     * 
     * If no answer is found in the cache, we fall back to scanning the file with
     * getID3 in the usual way. If results are found we are sure to poke them 
     * back in to the cache.
     * 
     * This is necessary because ScratchLive! can create several objects for the
     * same row (as it will write multiple, updated, versions of the row to the
     * history file over time). Several of these may be missing expensive info,
     * so we want to cache it from object to object.
     * 
     */
    public function setLengthIfUnknown()
    {
        $length = $this->getLength();
        if(!isset($length))
        {
            // have a go at pulling it from the cache, if it's in the cache!
            $cached_track = $this->track_cache->getByRow($this->getRow());
            
            if($cached_track)
            {
                $cached_length = $cached_track->getLength();
                if(isset($cached_length)) 
                {
                    $this->setLength($cached_length);
                }
            }
            
            parent::setLengthIfUnknown();
            
            $length = $this->getLength();

            // save in the cache, for next time.
            if(isset($length))
            {
                if(isset($cached_track))
                {
                    $cached_track->length = $length;
                }
                else
                {
                    $this->track_cache->register($this);
                }
            }
        }
    }
}