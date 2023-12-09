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

class SSLHistoryDom extends SSLDom
{
    /**
     * @var SSLTrackFactory
     */
    protected $track_factory;
    
    public function __construct($array=array())
    {
        $this->track_factory = Inject::the(new SSLTrackFactory());
        parent::__construct($array);
    }
    
    /**
     * @return array of SSLTrack
     */
    public function getTracks()
    {
        $data = $this->getData();
        $tracks = array();
        foreach($data as $datum)
        {
            if ($datum instanceof SSLTrack) 
                $tracks[] = $datum;
        }
        return $tracks;
    }

    /**
     * @return array of SSLTrack
     */
    public function getDedupedTracks()
    {
        $tracks = $this->getTracks();

        // raw session files are append-only when in use, and often contain multiple entries
        // for the same track written at different points in the lifecycle (e.g. deck load / 
        // deck eject). Newer versions of Serato clean these up when you end the session,
        // but it can still happen if you crash etc.
        
        $seen_ids = array();
        $positions_to_filter = array();

        $ptr = count($tracks);
        while($ptr > 0)
        {
            // work backward through the array removing younger dupes

            $ptr--;
            $row_id = $tracks[$ptr]->getRow();
            if(isset($seen_ids[$row_id]))
            {
                $tracks[$ptr] = null;
            }
            else
            {
                $seen_ids[$row_id] = true;
            }
        }

        return array_filter($tracks);
    }

    /**
     * @return array of SSLAdatChunk
     */
    public function getData()
    {        
        $data = array();
        foreach($this as $chunk)
        {
            if($chunk instanceof SSLOentChunk)
            {
                $data[] = $chunk->getDataInto($this->track_factory->newTrack());
            }
            
            elseif($chunk instanceof SSLOrenChunk)
            {
                $data[] = $chunk->getDataInto(new SSLTrackDelete());
            }

            elseif($chunk instanceof SSLVrsnChunk)
            {
                $data[] = $chunk->getDataInto(new SSLVersion());
            }
        }
        
        $data = $this->mergeRows($data); // this will re-key everything by row number
        
        return $data;
    }
    
    /** 
     * Find all changes since we last polled.
     * 
     * @return SSLHistoryDiffDom
     */
    public function getNewOrUpdatedTracksSince(SSLHistoryDom $tree)
    {
        $a_tracks = $this->getTracks(); // newer tree
        $b_tracks = $tree->getTracks(); // older tree
        
        $a_track_rows = array_keys($a_tracks);
        $b_track_rows = array_keys($b_tracks);
        
        $track_rows_added = array_diff($a_track_rows, $b_track_rows);
        $tracks_added = array();
        foreach($track_rows_added as $row)
        {
            $tracks_added[$row] = $a_tracks[$row];
        }

        foreach($a_tracks as $row => $track)
        {
            /* @var $track SSLTrack */
            if( !isset($b_tracks[$row]) || $track->getUpdatedAt() > $b_tracks[$row]->getUpdatedAt() ) 
            {
                $tracks_added[$row] = $track;
            }
        }
        
        return new SSLHistoryDiffDom($tracks_added);
    }
    
    /**
     * SSL often writes updates to rows to the end of the binary file, which
     * means that the file can contain several blocks referring to the same item
     * in the tracklisting. The last one always takes precedence.
     * 
     * SSL will usually clean the file up at exit time, replacing all identically 
     * numbered rows with the most recent.
     * 
     * @param array $tracks
     */
    private function mergeRows(array $tracks)
    {
        $merged = array();
        foreach($tracks as $track)
        {
            // updates to tracks will always come later in the file, 
            // so no need to check for updatedAt
            if($track instanceof SSLTrack)
            {
            	/* @var $track SSLTrack */
                $merged[$track->getRow()] = $track;
            }
            elseif($track instanceof SSLTrackDelete)
            {
            	/* @var $track SSLTrackDelete */
                $merged[$track->getRow()] = null;
            }
        }
        return $merged;
    }
}