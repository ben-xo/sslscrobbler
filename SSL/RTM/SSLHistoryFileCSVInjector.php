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
 * History file replayer that reads rows from a CSV. Useful for testing transitions in the
 * Realtime Model end-to-end without having to record a ScratchLive session. 
 * 
 * See the --csv option in HistoryReader for usage.
 */
class SSLHistoryFileCSVInjector extends SSLHistoryFileReplayer
{
    /**
     * @return SSLHistoryDom
     */
    protected function readCSV($filename)
    {
        $fp = fopen($filename, 'r');
        if($fp === false) throw new RuntimeException("Could not open CSV file {$filename}");

        L::level(L::DEBUG, __CLASS__) &&
            L::log(L::DEBUG, __CLASS__, 'Opened %s for reading',
                array($filename));

        $tracks = array();
        $field_order = array('row', 'deck', 'artist', 'title', 'starttime', 'endtime', 'played', 'added', 'updatedAt', 'playtime', 'length');
        while(false !== ($fs = fgetcsv($fp, null, ',', '"', '\\')))
        {
            if($fs)
            {
                foreach($field_order as $i => $f_name)
                {
                    isset($fs[$i]) && $fields[$f_name] = $fs[$i];
                }
                
                $track = new SSLTrack();
                $track->populateFrom($fields);
                $tracks[] = $track;
            }
        }
        
        if(empty($tracks)) throw new InvalidArgumentException("File {$filename} contained no records");
        
        fclose($fp);
        
        return $tracks;
    }
    
    /**
     * Generate SSLTracks from a CSV, grouped by timestamp.
     */
    /**
     * Split the contents of the file into SSLTrack rows grouped by timestamp.
     */
    protected function initialize()
    {
        $tracks = $this->readCSV($this->filename);
        $this->groupByTimestamp($tracks);
        $this->initialized = true;
    }
    
}