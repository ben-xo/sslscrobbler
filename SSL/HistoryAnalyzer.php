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

class HistoryAnalyzer extends HistoryReader
{
    protected $db = 'analyze.db';

    /**
     * @var SQLite3
     */
    protected $dbo;

    /**
     * The main entry point to the application. Start here!
     * When this returns, the program is done.
     * 
     * @param $argc (from GLOBAL)
     * @param $argv (from GLOBAL)
     */
    public function main($argc, array $argv)
    {
        date_default_timezone_set('UTC');
        mb_internal_encoding('UTF-8');
                
        try
        {
            $this->parseOptions($argv);
            $this->setupLogging();
                        
            // guess history file (always go for the most recently modified)
            $this->historydir = $this->getDefaultHistoryDir();
            
            if($this->help)
            {
                $this->usage($this->appname, $argv);
                return;
            }
            
            $this->dbo = new SQLite3($this->db);
            $this->initializeDb();
            
            $this->analyzeDir($this->historydir);
                            
        }
        catch(Exception $e)
        {   
            echo $e->getMessage() . "\n";
            if($this->verbosity > L::INFO)
            {
                echo $e->getTraceAsString() . "\n";
            }  
            echo "Try {$this->appname} --help\n";
        }
    }
    
    public function usage($appname, array $arg, $debug_help = false, $plugin_help = false)
    {
        echo "Usage: {$appname} [OPTIONS] [session file]\n";
        echo "Session file is optional. If omitted, the most recent history file from {$this->historydir} will be used automatically\n";
        echo "    -h or --help:              This message.\n";
        echo "\n";
        foreach($this->cli_plugins as $plugin)
        {
            /* @var $plugin CLIPlugin */
            $plugin->usage($appname, $argv);
        }
        echo "Debugging options:\n";
        echo "    -v or --verbosity <0-9>:   How much logging to output. (default: 0 (none))\n";
    }
    
    protected function parseOptions(array $argv)
    {
        $this->appname = array_shift($argv);
        
        while($arg = array_shift($argv))
        {
            if($arg == '--help' || $arg == '-h')
            {
                $this->help = true;
                continue;
            }
                        
            if($arg == '--verbosity' || $arg == '-v')
            {
                $this->verbosity = (int) array_shift($argv);
                continue;
            }
            
            $this->filename = $arg;
        }
    }
    
    protected function analyzeDir($from_dir)
    {
        // Use the caching version via Dependency Injection. This means that all 
        // new SSLTracks created using a SSLTrackFactory will get a RuntimeCachingSSLTrack
        // that knows how to ask the cache about expensive lookups (such as getID3 stuff). 
        Inject::map('SSLTrackFactory', new SSLTrackCache());

        $newest_mtime = 0;
        $fps = array();
        
        $di = new DirectoryIterator($from_dir);
        $dom = new SSLHistoryDom();
        
        foreach($di as $f)
        {
            if(!$f->isFile() || !substr($f->getFilename(), -8) == '.session')
                continue;
    
            $fps[] = $f->getPathname();
        }
        
        natsort($fps);

        $track_total = 0;
        foreach($fps as $fp)
        {
            $fn = basename($fp);
            try {
                $parser = new SSLParser($dom);
                $parsed_dom = $parser->parse($fp);
            } catch(Exception $e) {
                echo "-- ignoring {$fn}.\n";
                $parser->close();
                continue;
            }
            $parser->close();

            $tracks = $parsed_dom->getDedupedTracks();
            $count = count($tracks);

            echo "++ session $fn: " . count($parsed_dom) . " chunks yielded $count tracks to add to the DB\n";

            foreach($tracks as $track)
            {
                /* @var $track SSLTrack */
                $query = sprintf("INSERT INTO history (row, filename, title, artist, deck, starttime, endtime, played, updatedAt, playtime, length, album, fullpath)
                            VALUES (%d, '%s', '%s', '%s', %d, %d, %d, %d, %d, %d, '%s', '%s', '%s')",
                    $track->getRow(),
                    SQLite3::escapeString($track->getFilename()),
                    SQLite3::escapeString($track->getTitle()),
                    SQLite3::escapeString($track->getArtist()),
                    $track->getDeck(),
                    $track->getStartTime(),
                    $track->getEndTime(),
                    $track->getPlayed(),
                    $track->getUpdatedAt(),
                    $track->getPlayTime(),
                    $track->getLengthInSeconds(SSLTrack::TRY_HARD),
                    SQLite3::escapeString($track->getAlbum()),
                    SQLite3::escapeString($track->getFullpath())
                );
                
                if(!$this->dbo->exec($query))
                {
                    echo "WARNING: '{$this->dbo->lastErrorMsg()} (code {$this->dbo->lastErrorCode()})' importing '{$track->getRow()}' - {(string)$track}\n";
                }

                $track_total++;
                if($track_total % 100 == 0) {
                    echo "Track {$track_total}…\n";
                }
            }
        }
        echo "++ Saw $track_total tracks overall.\n";
        echo "done\n";
        
    }
    
    protected function initializeDb()
    {
        $this->dbo->exec("DROP TABLE IF EXISTS history;");
        $this->dbo->exec("CREATE TABLE history (
            row INTEGER PRIMARY KEY,
            filename VARCHAR,
            title VARCHAR,
            artist VARCHAR,
            deck INTEGER,
            starttime INTEGER,
            endtime INTEGER,
            played INTEGER,
            updatedAt INTEGER,
            playtime INTEGER,
            length INTEGER,
            album VARCHAR,
            fullpath TEXT
        );");
    }
}
