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
    
    protected
        $filename,
        $row,
        $deck,
        $artist, 
        $title,
        $album, 
        $played, 
        $length,
        $start_time, 
        $end_time,
        $updated_at = 0,
        $added,
        $playtime,
        $fullpath,
        $fields = array()
    ;
    
    public function getUnpacker()
    {
        return $this->getUnpackerForFile(dirname(__FILE__) . '/SSLTrackAdat.xoup');
    }
    
    public function populateFrom(array $fields)
    {
        $this->fields = $fields;
        isset($fields['filename']) && $this->filename = trim($fields['filename']);
        isset($fields['row']) && $this->row = $fields['row'];
        isset($fields['title']) && $this->title = trim($fields['title']);
        isset($fields['artist']) && $this->artist = trim($fields['artist']);
        isset($fields['deck']) && $this->deck = $fields['deck'];
        isset($fields['starttime']) && $this->start_time = $fields['starttime'];
        isset($fields['endtime']) && $this->end_time = $fields['endtime'];
        isset($fields['played']) && $this->played = (bool) $fields['played'];
        isset($fields['added']) && $this->added = $fields['added'];
        isset($fields['updatedAt']) && $this->updated_at = $fields['updatedAt'];
        isset($fields['playtime']) && $this->playtime = $fields['playtime'];
        isset($fields['length']) && $this->length = trim($fields['length']);
        isset($fields['album']) && $this->album = trim($fields['album']);
        isset($fields['fullpath']) && $this->fullpath = trim($fields['fullpath']);
    }
    
    public function toArray()
    {
        return array(
            'filename' => $this->filename,
            'row' => $this->row,
            'title' => $this->title,
            'artist' => $this->artist,
            'deck' => $this->deck,
            'starttime' => $this->start_time,
            'endtime' => $this->end_time,
            'played' => $this->played,
            'added' => $this->added,
            'updatedAt' => $this->updated_at,
            'playtime' => $this->playtime,
            'length' => $this->length,
            'album' => $this->album,
            'fullpath' => $this->fullpath
        );
    }
    
    public function getFilename()
    {
        return $this->filename;
    }
    
    public function getFullpath()
    {
        return $this->fullpath;
    }
    
    public function getRow()
    {
        return $this->row;
    }
    
    public function getDeck()
    {
        return $this->deck;
    }
    
    public function getArtist()
    {
        return $this->artist;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function getAlbum()
    {
        return $this->album;
    }

    public function getPlayed()
    {
        return $this->played;
    }

    public function getPlayTime()
    {
        return $this->playtime;
    }

    public function getStartTime()
    {
        return $this->start_time;
    }

    public function getEndTime()
    {
        return $this->end_time;
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
        return $this->length;
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
        
        if(preg_match('/^(\d+):(\d+)/', $this->getLength(), $matches))
        {
            return $matches[1] * 60 + $matches[2];
        }
        return 0;
    }
    
    public function getUpdatedAt()
    {
        return $this->updated_at;
    }
    
    public function isPlayed()
    {
        return (bool) $this->played;
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
    
    /**
     * This will attempt to set the length via guess work, if it's not already set.
     */
    protected function setLengthIfUnknown()
    {
        if(!isset($this->length))
        {
            $this->length = $this->guessLengthFromFile();
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

        if(strlen($fullpath) == 0)
        {
            L::level(L::WARNING) &&
                L::log(L::WARNING, __CLASS__, 'Guessing MP3 length from file failed: full path was empty. Perhaps this entry was manually added?',
                    array( ));

            return "0:00";
        }

        if(!$this->file_exists($fullpath))
        {
            L::level(L::WARNING) &&
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
        $getid3->option_extra_info = true;
        $getid3->encoding = 'UTF-8';
            
        try
        {
            $info = $getid3->Analyze($fullpath);
            $playtime = $info['playtime_seconds'];
            if($playtime)
            {
                L::level(L::WARNING) &&
                    L::log(L::WARNING, __CLASS__, 'Guessed MP3 length %d seconds from file.',
                        array( $playtime ));

                $minutes = floor($playtime / 60);
                $seconds = $playtime % 60;
                return sprintf("%d:%02d", $minutes, $seconds);
            }

            L::level(L::WARNING) &&
                L::log(L::WARNING, __CLASS__, 'Guessing MP3 length from file failed for an unknown reason. Hmmph.',
                    array( ));
            
        }
        catch(getid3_exception $e)
        {
            // MP3 couldn't be analyzed.
            L::level(L::WARNING) &&
                L::log(L::WARNING, __CLASS__, 'Guessing MP3 length from file failed: %s',
                    array( $e->getMessage() ));
        }
        return "0:00";
    }
    
    
    public function __toString()
    {
        return sprintf("PLAYED:%s - ADDED:%s - DECK:%s - %s - %s - %s", 
            $this->played ? '1' : '0', isset($this->added) ? ($this->added ? '1' : '0') : 'X', 
            $this->deck,
            $this->artist, $this->title,  floor($this->playtime / 60) . ':' . ($this->playtime % 60)
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
        return file_exists($filename);
    }
}