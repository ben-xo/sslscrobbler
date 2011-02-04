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

require_once 'External/BeatPHPort/BeatportAPIClient.php';

class BeatportTrackMessageFilter implements ITrackMessageFilter
{
    /**
     * @var IURLShortener
     */
    protected $url_shortener;
    
    public function __construct(IURLShortener $url_shortener=null)
    {
        $this->url_shortener = $url_shortener;
    }
    
    public function apply(SSLTrack $track, $message)
    {
        $url = $this->getBeatportURL($track);
        if($url && $this->url_shortener)
        {
            $url = $this->url_shortener->shorten($url);
        }
        
        return preg_replace('/:beatport:/', $url, $message);
    }
    
    protected function getBeatportURL(SSLTrack $track)
    {
        $url = '';
        
        $bp = $this->newBeatportAPIClient();
        
        $artistName = $track->getArtist();
        $trackName = $track->getTitle();
        
        $bp_track = $bp->getTrackByArtist($artistName, $trackName);
        
        if($bp_track)
        {
            $bp_artist_names = $bp_track->getArtistNames();
            $bp_track_title = $bp_track->getName();
            $bp_url = $bp_track->getURL();
            
            $bp_track_string = implode(', ', $bp_artist_names) . ' - ' . $bp_track_title;
            $ssl_track_string = $track->getFullTitle();
            
            L::level(L::INFO) && 
                L::log(L::INFO, __CLASS__, "Found %s at %s", 
                    array($bp_track_string, $bp_url));

            if($this->checkMatch($ssl_track_string, $bp_track_string))
            {
                $url = $bp_track->getURL();
            }
            else
            {
                L::level(L::INFO) && 
                    L::log(L::INFO, __CLASS__, "Rejected %s as a match for %s", 
                        array($bp_track_string, $ssl_track_string));
            }
        }
        else
        {
            L::level(L::INFO) && 
                L::log(L::INFO, __CLASS__, "No track found.", 
                    array());
        }
        
        return $url;
    }
    
    /**
     * Compare similarity of two strings by seeing how many characters they have in common.
     * 
     * This method should be agnostic to things like reverse artist-name ordering, ignores 
     * punctuation, transliterates accents, and should not be very sensitive to things like 
     * '&' versus 'and'. 
     */
    protected function checkMatch($a, $b)
    {
        $a = $this->characterize($a);
        $b = $this->characterize($b);
        similar_text($a, $b, $p);
        return ($p >= 0.8);
    } 
    
    protected function characterize($s)
    {
        $s = iconv('UTF-8', 'ASCII//TRANSLIT', $s);
        $s = strtolower($s);
        $s = preg_replace('/[^a-z]/', '', $s);
        $ss = preg_split('//', $s);
        sort($ss);
        return trim(implode('', $ss));
    }
    
    /**
     * @return BeatportAPIClient
     */
    protected function newBeatportAPIClient()
    {
        return new BeatportAPIClient();
    }
}