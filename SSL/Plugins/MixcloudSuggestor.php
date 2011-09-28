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
 * Looks for tracks on mixcloud, as they are put on the deck; then looks up 
 * mixes on MixCloud containing that mix, and finds a selection of tracks
 * that other DJs have played next, for inspiration.
 * 
 * @author ben
 */
class MixcloudSuggestor implements SSLPlugin, TrackChangeObserver
{
    public function __construct()
    {
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
                $this->printSuggestions($event->getTrack());
            }
        }
    }
    
    protected function printSuggestions(SSLTrack $track)
    {
        try {
            $mixcloud_track_key = $this->getBestTrack($track);
            $cloudcasts = $this->getMixesForTrack($mixcloud_track_key);
            $nexttracks = array();
            foreach($cloudcasts as $cloudcast)
            {
                try {
                    $nexttracks[] = array(
                        'cloudcast' => $cloudcast,
                        'track' => $this->getNextTrackFor($mixcloud_track_key, $cloudcast['key'])
                    );
                } catch (Exception $e) {
                    L::level(L::WARNING) &&
                        L::log(L::WARNING, __CLASS__, '%s',
                            array( $e->getMessage() ));
                    // continue
                }
            }
            
            echo "Popular next tracks on mixcloud:\n";
            $longest_username = 0;
            foreach($nexttracks as $track) {
                $longest_username = max($longest_username, mb_strlen($track['cloudcast']['user']['name']));
            }
            
            foreach($nexttracks as $track) {
                echo sprintf("%{$longest_username}s played %s - %s\n",
                     $track['cloudcast']['user']['name'],
                     $track['track']['artist']['name'],
                     $track['track']['name']
                );
            }
            
        } catch(Exception $e) {
            L::level(L::WARNING) &&
                L::log(L::WARNING, __CLASS__, 'No suggestions: %s',
                    array( $e->getMessage() ));
            
        }
    }
    
    protected function getBestTrack(SSLTrack $track)
    {
        $search = $this->doMixcloudCall(
        	'best track', 
        	'http://api.mixcloud.com/search/?q=%s+%s&type=track', 
            array(
                urlencode($track->getArtist()),
                urlencode($track->getTitle())
            )
        );

        if(isset($search['error'])) throw new Exception('Error from Mixcloud: ' . $search['error']['message']);
        
        $potential_tracks = array();
        foreach($search['data'] as $result)
        {
            $potential_track = array(
                'artist' => $result['artist']['name'],
                'title' => $result['name'],
                'key' => $result['key']    
            );
            $score = levenshtein(
                $track->getArtist() . ' ' . $track->getTitle(), 
                $result['artist']['name'] . ' ' . $result['name']
            );
            $potential_tracks[$score] = $result['key'];
        }
        
        if($potential_tracks)
        {
            ksort($potential_tracks);
            $best_track = array_shift($potential_tracks);

            L::level(L::INFO) &&
                L::log(L::INFO, __CLASS__, 'Best track match: %s',
                    array( $best_track ));
                    
            return $best_track;
        }

        throw new Exception('no track match for ' . $track->getFullTitle());
    }
    
    protected function getMixesForTrack($key)
    {
        $search = $this->doMixcloudCall(
        	'mixes', 
        	'http://api.mixcloud.com%spopular/?limit=%d', 
            array(
                $key, 
                20 /* limit */
            )
        );

        if(isset($search['error'])) throw new Exception('Error from Mixcloud: ' . $search['error']['message']);
        
        $cloudcasts = array();
        foreach($search['data'] as $result)
        {
            $cloudcasts[] = $result;
        }
        
        L::level(L::INFO) &&
            L::log(L::INFO, __CLASS__, 'Found %d mixes for track %s',
                array( count($cloudcasts), $key ));
                
        return $cloudcasts;
    }
    
    protected function getNextTrackFor($track_key, $cloudcast_key)
    {
        $search = $this->doMixcloudCall(
        	'mix', 
       		'http://api.mixcloud.com%s', 
            array(
                $cloudcast_key
            )
        );
        
        if(isset($search['error'])) throw new Exception('Error from Mixcloud: ' . $search['error']['message']);
        if(!isset($search['sections'])) throw new Exception('Error from Mixcloud: no sections element in response');
        
        $next = false;
        foreach($search['sections'] as $track)
        {
            if($next) return $track['track'];
            if(isset($track['track']) && 
               isset($track['track']['key']) && 
               $track['track']['key'] == $track_key
            ) $next = true;
        }
        
        throw new Exception("Next track not found in mix $cloudcast_key!");
    }
    
    protected function doMixcloudCall($request_name, $uri, array $replacement_vars)
    {
        $full_api_uri = vsprintf($uri, $replacement_vars);
        $json_data = json_decode(file_get_contents($full_api_uri), true /* assoc */);
        if(!$json_data) throw new Exception('No data from ' . $request_name . ' request');
        return $json_data;
    }
}
