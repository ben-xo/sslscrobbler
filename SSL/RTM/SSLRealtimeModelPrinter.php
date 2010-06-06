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

class SSLRealtimeModelPrinter implements TrackChangeObserver
{
    /**
     * @var SSLRealtimeModel
     */
    private $rtm;
    
    public function __construct(SSLRealtimeModel $rtm)
    {
        $this->rtm = $rtm;
    }
    
    protected function getPrevTrackTitle($deck)
    { 
        $track = $this->rtm->getPreviousTrack($deck);
        if(!isset($track)) return ' ';
        return $track->getArtist() . ' - ' . $track->getTitle();
    }
        
    protected function getTrackTitle($deck)
    { 
        $track = $this->rtm->getCurrentTrack($deck);
        if(!isset($track)) return ' ';
        return $track->getArtist() . ' - ' . $track->getTitle();
    }
    
    protected function getLength($deck)
    {
        $track = $this->rtm->getCurrentTrack($deck);
        if(!isset($track)) return '--:--.--';
        return $track->getLength();
    }
   
    protected function getFormattedPlaytime($deck)
    {
        $seconds = $this->rtm->getPlaytime($deck);
        if(!$seconds) return '--:--';
        return sprintf("%02d:%02d", floor($seconds / 60) , $seconds % 60);
    }
    
    protected function getNextEvent($deck)
    {
        $timer = $this->rtm->getEventTimer($deck);
        if(empty($timer)) return 0;
        return time() - $timer;
    }
        
    public function render()
    {
        $return = '';
        foreach($this->rtm->getDeckIDs() as $deck)
        {
            $status = $this->rtm->getStatus($deck);
            $title = $this->getTrackTitle($deck);
            $played = $this->getFormattedPlaytime($deck);
            $length = $this->getLength($deck);
            $prev_title = $this->getPrevTrackTitle($deck);
            $return .= sprintf(
                "\n%d: %-10.10s [%s] [%s] [prev: %s]",
                $deck, $status, $played, $title, $prev_title
            );
        }
        
        return $return;
    }    
    
    public function __toString()
    {
        return $this->render();
    }
    
    public function notifyTrackChange(TrackChangeEvent $event)
    {
        echo $this->render() . "\n";
        echo "Date: " . date('Y-m-d H:i:s') . " Memory Usage: " . number_format(memory_get_usage()) . " bytes\n";
    }
}