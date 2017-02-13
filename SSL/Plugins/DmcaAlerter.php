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
 * Looks at the history of played tracks to see if the next track will 
 * cause the mix to become unplayable in the USA when uploaded to mixcloud.
 *
 * See http://support.mixcloud.com/customer/en/portal/articles/1590263-why-is-my-upload-not-available-
 *     http://support.mixcloud.com/customer/en/portal/articles/1595566-why-can-t-i-listen-licensing-rules-by-country
 * 
 * US Rules (as of 2015):
 * - Maximum 4 tracks by an artist (and max 3 consecutively)
​ * - Maximum 3 tracks from an album (and max 2 consecutively) 
 *
 * @author ben
 */
class DmcaAlerter implements SSLPlugin, TrackChangeObserver, ScrobbleObserver
{
    protected $notifier;

    public function __construct(PopupNotifier $notifier = null)
    {
        $this->notifier = $notifier;
    }
    
    public function onSetup() {}
    public function onStart() {}
    public function onStop() {}

    public function getObservers()
    {
        return array( new self );
    }

    public function setConfig(array $config)
    {
    }

    public function notifyTrackChange(TrackChangeEventList $events)
    {
        foreach($events as $event)
        {
            if($event instanceof TrackStartedEvent)
            {
                $track = $event->getTrack();
                if($track)
                {
                    L::level(L::INFO) &&
                        L::log(L::INFO, __CLASS__, "Checking if track would break any Mixcloud DMCA limits",
                            array( ));

                    $this->checkLimits($track);
                }
            }
        }
    }

    public function notifyScrobble(SSLTrack $track=null)
    {
        if($track)
        {
            L::level(L::INFO) &&
                L::log(L::INFO, __CLASS__, "Recording track as played for Mixcloud DMCA accounting",
                    array( ));

            $this->recordPlayedTrack($track);
        }
    }

    protected $artist_playcounts = array();
    protected $album_playcounts = array();
    protected $last_three_artists = array();
    protected $last_two_albums = array();

    /**
     * Record which artist / albums are played.
     *
     * Triggered when a track is considered "played"
     */
    protected function recordPlayedTrack(SSLTrack $track)
    {
        $artist = $track->getArtist();
        $album = $track->getAlbum();

        if(!isset($this->artist_playcounts[$artist]))
            $this->artist_playcounts[$artist] = 0;

        if(!isset($this->album_playcounts[$album]))
            $this->album_playcounts[$album] = 0;

        $this->artist_playcounts[$artist]++;
        $this->album_playcounts[$album]++;

        $this->last_three_artists[] = $artist;
        $this->last_two_albums[] = $album;

        if(count($this->last_three_artists) > 3)
            array_shift($this->last_three_artists);

        if(count($this->last_two_albums) > 2)
            array_shift($this->last_two_albums);

        L::level(L::DEBUG) &&
            L::log(L::DEBUG, __CLASS__, "Last 3 artists: '%s'; Last 2 albums: '%s'",
                array( implode("', '", $this->last_three_artists),
                       implode("', '", $this->last_two_albums) ));
    }

    /**
     * Check if the given track would break any of the licensing limits if played next.
     *
     * Triggered when a track is loaded onto a deck.
     */
    protected function checkLimits(SSLTrack $track)
    {
        $artist = $track->getArtist();
        $album = $track->getAlbum();

        if(isset($this->artist_playcounts[$artist]) && $this->artist_playcounts[$artist] > 4)
        {
            $this->alert("/!\ You have already played artist '%s' 4 times this show. /!\\", $artist);
        }

        elseif(isset($this->album_playcounts[$album]) && $this->album_playcounts[$album] > 3)
        {
            $this->alert("/!\ You have already played album '%s' 3 times this show. /!\\", $album);
        }

        elseif($this->isTooManyConsecutiveArtists($artist))
        {
            $this->alert("/!\ You have played artist '%s' too many times in a row. /!\\", $artist);
        }

        elseif($this->isTooManyConsecutiveAlbums($album))
        {
            $this->alert("/!\ You have played album '%s' too many times in a row. /!\\", $album);
        }

        else
        {
            L::level(L::INFO) &&
                L::log(L::INFO, __CLASS__, "Nah, we're fine!",
                    array( ));
        }
    }

    protected function isTooManyConsecutiveArtists($artist)
    {
        $count = 1; // if we were to play this artist…
        foreach($this->last_three_artists as $past_artist)
        {
            if($past_artist == $artist) $count++;
        }
        return $count > 3; // …would it be too many in a row?
    }

    protected function isTooManyConsecutiveAlbums($album)
    {
        if($album == '') return false;

        // it is possible for albums by different artists to have the same name, but
        // we don't compensate for that relatively unlikely case here.

        $count = 1; // if we were to play this album…
        foreach($this->last_two_albums as $past_album)
        {
            if($past_album == '') continue;
            if($past_album == $album) $count++;
        }
        return $count > 2; // …would it be too many in a row?
    }

    protected function alert($message, $offending_item)
    {
        if($this->notifier)
        {
            $this->notifier->notify('alert', "Don't play it!", sprintf($message, $offending_item));
        }

        L::level(L::WARNING) &&
            L::log(L::WARNING, __CLASS__, $message,
                array( $offending_item ));
    }
}
