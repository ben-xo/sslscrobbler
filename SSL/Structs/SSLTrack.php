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
 * Represents a Track found in an SSL history file. (There may be other 
 * representations of Tracks found in other SSL files).
 * 
 * SSL puts a lot of useful information into the history file, including
 * the full path to the MP3, bpm, key, etc., as well as history-oriented data
 * such as start and end time, which deck the track was played on, played or 
 * skipped, manually added, etc. There is also an incrementing integer primary 
 * key (which I've called 'row').
 * 
 * History file Tracks also have a concept of their own 'status', in the sense of
 * 'NEW', 'PLAYING', 'PLAYED' or 'SKIPPED'. (@see SSLRealtimeModel for more detail).
 * These states are derived from a combination of the 'played' field, which is
 * either 0 or 1 corresponding to whether or not the row is 'green' in the SSL
 * interface, and whether or not an 'endtime' is present.
 * 
 * @author ben
 */
class SSLTrack extends SSLStruct
{
    /**
     * Flag used by getLength to indicate that the client would
     * like the length by any means necessary, even if that means
     * an expensive full-file-scan.
     */
    const TRY_HARD = 1;
       
    public function getUnpacker()
    {
        return $this->getUnpackerForFile(dirname(__FILE__) . '/SSLTrackAdat.xoup');
    }
    
    /**
     * Get the length of the file, as a string (e.g. "1:23.45" 
     * for 1 minute 23.45 seconds). This is how Serato
     * returns it from the file.
     * 
     * Pass SSLTrack::TRY_HARD if you would like the file 
     * length to be guessed from the file itself, if possible,
     * and don't mind that this is possibly an expensive operation.
     * 
     * @param $flags
     */
    public function getLength($flags=0)
    {
        if($flags & self::TRY_HARD)
        {
            $this->setLengthIfUnknown();
        }
        return parent::getLength();
    }
    
    /**
     * Get the length of the file, as an integer number of seconds.
     * 
     * Pass SSLTrack::TRY_HARD if you would like the file 
     * length to be guessed from the file itself, if possible,
     * and don't mind that this is possibly an expensive operation.
     * 
     * @param $flags
     */
    public function getLengthInSeconds($flags=0)
    {
        if($flags & self::TRY_HARD)
        {
            $this->setLengthIfUnknown();
        }
        
        $length = $this->getLength();

        if(isset($length) && preg_match('/^(\d+):(\d+)/', $length, $matches))
        {
            return $matches[1] * 60 + $matches[2];
        }
        return 0;
    }
       
    public function isPlayed()
    {
        return (bool) $this->getPlayed();
    }
    
    public function getStatus()
    {
        if($this->isPlayed())
        {
            if($this->getPlaytime())
            {
                // 1 N
                return 'PLAYED';
            }
            else
            {
                // 1 0
                return 'PLAYING';
            }
        }
        else
        {
            if($this->getPlaytime())
            {
                // 0 N
                return 'SKIPPED';
            }
            else
            {
                // 0 0
                return 'NEW';
            }
            
        }
    }
    
    public function getFullTitle()
    {
        return $this->getArtist() . ' - ' . $this->getTitle();
    }

    public function getFullStartTime()
    {
        return $this->renderTime($this->getStartTime());
    }

    public function getFullEndTime()
    {
        return $this->renderTime($this->getEndTime());
    }

    protected function renderTime($timestamp)
    {
        return date('Y-m-d H:i:s', $timestamp);
    }

    /**
     * This will attempt to set the length via guess work, if it's not already set.
     */
    protected function setLengthIfUnknown()
    {
        $length = parent::getLength();
        if(!isset($length))
        {
            $this->setLength($this->guessLengthFromFile());
        }
    }

    /**
     * Sometimes ScratchLive doesn't supply the length, even when it knows the file.
     * Not sure why; perhaps files that have never been analysed.
     * 
     * So, let's attempt to guess it by analysing the full file.
     */
    protected function guessLengthFromFile()
    {
        $fullpath = $this->getFullpath();

        if(isset($fullpath) && strlen($fullpath) == 0)
        {
            L::level(L::WARNING, __CLASS__) &&
                L::log(L::WARNING, __CLASS__, 'Guessing MP3 length from file failed: full path was empty. Perhaps this entry was manually added?',
                    array( ));

            return "0:00";
        }

        if(!$this->file_exists($fullpath))
        {
            L::level(L::WARNING, __CLASS__) &&
                L::log(L::WARNING, __CLASS__, 'Guessing MP3 length from file failed: file not found (%s)',
                    array( $fullpath ));

            return "0:00";
        }

        $external_factory = Inject::the(new ExternalRepo());
        /* @var $external_factory ExternalFactory */
        $getid3 = $external_factory->newGetID3();
        /* @var $getid3 getid3 */
        $getid3->option_tag_lyrics3 = false;
        $getid3->option_tag_apetag = false;
        $getid3->option_tags_html = false;
        $getid3->option_extra_info = true;
        $getid3->encoding = 'UTF-8';
            
        try
        {
            $info = $getid3->Analyze($fullpath);
            $playtime = $info['playtime_seconds'];
            if($playtime)
            {
                L::level(L::WARNING, __CLASS__) &&
                    L::log(L::WARNING, __CLASS__, 'Guessed MP3 length %d seconds from file.',
                        array( $playtime ));

                $minutes = floor($playtime / 60);
                $seconds = (int)$playtime % 60;
                return sprintf("%d:%02d", $minutes, $seconds);
            }

            L::level(L::WARNING, __CLASS__) &&
                L::log(L::WARNING, __CLASS__, 'Guessing MP3 length from file failed for an unknown reason. Hmmph.',
                    array( ));
            
        }
        catch(getid3_exception $e)
        {
            // MP3 couldn't be analyzed.
            L::level(L::WARNING, __CLASS__) &&
                L::log(L::WARNING, __CLASS__, 'Guessing MP3 length from file failed: %s',
                    array( $e->getMessage() ));
        }
        return "0:00";
    }
    
    
    public function __toString()
    {
        $played = $this->getPlayed();
        $added = $this->getAdded();
        $playtime = $this->getPlaytime();
        $deck = $this->getDeck();
        $artist = $this->getArtist();
        $title = $this->getTitle();

        return sprintf("PLAYED:%s - ADDED:%s - DECK:%s - %s - %s - %s", 
            $played ? '1' : '0', isset($added) ? ($added ? '1' : '0') : 'X', 
            $deck,
            $artist, $title,  floor($playtime / 60) . ':' . ($playtime % 60)
        );
        
        // debugging
        $s = '';
        foreach($this->fields as $k => $v)
        {
            $s .= "$k => $v\n";
        }
        return $s;
    }
    
    protected function file_exists($filename)
    {
        return isset($filename) && file_exists($filename);
    }

    // exists to make phpunit happy

    public function getRow()
    {
        return parent::getRow();
    }

    public function getPlayed()
    {
        return parent::getPlayed();
    }

    public function getPlaytime()
    {
        return parent::getPlaytime();
    }

    public function getDeck()
    {
        return parent::getDeck();
    }
}
