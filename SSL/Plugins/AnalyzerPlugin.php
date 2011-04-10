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

class AnalyzerPlugin implements SSLPlugin, NowPlayingObserver
{
    protected $config;
    
    /**
     * @var SQLite3
     */
    protected $dbo;
    
    public function __construct(array $config)
    {
        $this->setConfig($config);
    }
    
    public function setConfig(array $config)
    {
        $this->config = $config;
    }
        
    public function onSetup() 
    {
    }
    
    public function onStart() 
    {
        $this->dbo = new SQLite3($this->config['db']);
    }
    
    public function onStop() {}
    
    public function getObservers()
    {
        return array( $this );
    }
    
    public function notifyNowPlaying(SSLTrack $track=null)
    {
        if($track)
        {
            echo "============================\n";
            echo "Analysis report:\n";
            echo "Last played this song " . $this->lastPlayed($track) . "\n";
            echo "Played this song " . $this->allTimePlays($track) . " times (all time)\n";
            echo "Played this song " . $this->threeMonthPlays($track) . " times (last 3 months)\n";
            echo "Next tracks:\n";
            echo $this->nextPlays($track) . "\n";
            echo "============================\n";
        }
    }
    
    public function lastPlayed(SSLTrack $track)
    {
        $statement = $this->dbo->prepare("SELECT starttime FROM history WHERE title=:title AND artist=:artist AND played=1 ORDER BY row DESC LIMIT 1");
        $statement->bindValue('title', $track->getTitle()); 
        $statement->bindValue('artist', $track->getArtist()); 
        $results = $statement->execute()->fetchArray();
        
        if($results === false) return 'never';
        
        $now = time();
        $played = $results['starttime'];
        $ago = $now - $played;
        return round($ago / 60 / 60 / 24) . ' days ago';
    }
    
    public function threeMonthPlays(SSLTrack $track)
    {
        $three_months_ago = time() - 60*60*24*30;
        $statement = $this->dbo->prepare("SELECT COUNT(*) FROM history WHERE title=:title AND artist=:artist AND played=1 AND starttime > :threemonthsago");
        $statement->bindValue('title', $track->getTitle()); 
        $statement->bindValue('artist', $track->getArtist()); 
        $statement->bindValue('threemonthsago', $three_months_ago); 
        $results = $statement->execute()->fetchArray();
        
        if($results === false) return 0;
        return $results[0];
    }
    
    public function nextPlays(SSLTrack $track)
    {
        $statement = $this->dbo->prepare("SELECT row FROM history WHERE title=:title AND artist=:artist AND played=1");
        $statement->bindValue('title', $track->getTitle()); 
        $statement->bindValue('artist', $track->getArtist()); 
        $results = $statement->execute();
        if($results === false) return '';
        
        $tracks = array();
        while(($result = $results->fetchArray(SQLITE3_ASSOC)) !== false)
        {
            $row = $result['row'];
            $statement = $this->dbo->prepare("SELECT artist, title FROM history WHERE played=1 and row > :row ORDER BY row ASC LIMIT 1");
            $statement->bindValue('row', $row); 
            $next_results = $statement->execute()->fetchArray();
            @$tracks[$next_results[0] . ' - ' . $next_results[1]]++;
        }
        
        $output = array();
        foreach($tracks as $title => $count)
        {
            $output[] = '* ' . $title . ' ' . $count . ' times';
        }
        return implode("\n", $output);
    }
    
    public function allTimePlays(SSLTrack $track)
    {
        $statement = $this->dbo->prepare("SELECT COUNT(*) FROM history WHERE title=:title AND artist=:artist AND played=1");
        $statement->bindValue('title', $track->getTitle()); 
        $statement->bindValue('artist', $track->getArtist()); 
        $results = $statement->execute()->fetchArray();
        
        if($results === false) return 0;
        return $results[0];
    }

}