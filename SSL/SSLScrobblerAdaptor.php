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

class SSLScrobblerAdaptor implements NowPlayingObserver, ScrobbleObserver
{
    /**
     * @var md_Scrobbler
     */
    protected $scrobbler;
    
    public function __construct(md_Scrobbler $scrobbler)
    {
        $this->scrobbler = $scrobbler;
    }
    
    public function notifyNowPlaying(SSLTrack $track=null)
    {
        if($track)
        {
            try
            {
                L::level(L::DEBUG) &&
                    L::log(L::DEBUG, __CLASS__, 'Sending Now Playing to Last.fm',
                        array( ));
                    
                $this->scrobbler->nowPlaying(
                    $track->getArtist(),
                    $track->getTitle(),
                    $track->getAlbum(),
                    $track->getLengthInSeconds()
                );
            }
            catch(Exception $e)
            {
                L::level(L::WARNING) &&
                    L::log(L::WARNING, __CLASS__, 'Could not send Now Playing to Last.fm: %s',
                        array( $e->getMessage() ));
            }
        }
    }
    
    public function notifyScrobble(SSLTrack $track)
    {
        $length = $track->getLengthInSeconds();
        
        if($length == 0)
        {
            // Sometimes ScratchLive doesn't supply the length, even when it knows the file. 
            // Not sure why; perhaps file that have never been analysed.
            
            L::level(L::WARNING) &&
                L::log(L::WARNING, __CLASS__, 'Track %s apparently has length 0. Attempting to guess length.',
                    array( $track->getFullTitle() ));
                    
            $length = $track->guessLengthFromFile();
        }
        
        if($length == 0)
        {
            // Perhaps this entry was added manually.
            
            L::level(L::WARNING) &&
                L::log(L::WARNING, __CLASS__, 'Could not guess length. Last.fm will silently ignore the scrobble.',
                    array( $track->getFullTitle() ));
        }
        
        try
        {
            $this->scrobbler->add(
                $track->getArtist(),
                $track->getTitle(),
                $track->getAlbum(),
                $length,
                $track->getStartTime()
            );
            
            L::level(L::DEBUG) &&
                L::log(L::DEBUG, __CLASS__, 'Sending %d scrobble(s) to Last.fm',
                    array( $this->scrobbler->getQueueSize() ));
                    
            $this->scrobbler->submit();
            
            // TODO: caching if scrobbling's down whilst playing.
        }
        catch(Exception $e)
        {
            L::level(L::WARNING) &&
                L::log(L::WARNING, __CLASS__, 'Could not send %d scrobble(s) to Last.fm: %s',
                    array( $this->scrobbler->getQueueSize(), $e->getMessage() ));
        }
    }
}