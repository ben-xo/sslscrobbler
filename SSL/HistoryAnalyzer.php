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

class HistoryAnalyzer
{
    // command line switches
    protected $verbosity = L::INFO;
    
    protected $plugins = array();
    protected $override_verbosity = array();
    
    protected $appname;
    protected $filename;
    protected $historydir;
    protected $log_file;
    protected $help;
    
    protected $db = 'analyze.db';
    
    /**
     * @var SQLite3
     */
    protected $dbo;
        
    /**
     * @var Logger
     */
    protected $logger;
    
    /**
     * Takes an array of class names => log levels. Mainly
     * you can use this to shut certain classes up that are too noisy
     * at a particular log level, e.g. TickSource (which normally 
     * sends a L::DEBUG message every 2 seconds).
     * 
     * @param array $override
     */
    public function setVerbosityOverride(array $override)
    {
        $this->override_verbosity = $override;
    }

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
    
    public function usage($appname, array $argv)
    {
        echo "Usage: {$appname} [OPTIONS] [session file]\n";
        echo "Session file is optional. If omitted, the most recent history file from {$this->historydir} will be used automatically\n";
        echo "    -h or --help:              This message.\n";
        echo "\n";
        foreach($this->plugins as $plugin)
        {
            $plugin->usage($appname, $argv);
        }
        echo "Debugging options:\n";
        echo "    -v or --verbosity <0-9>:   How much logging to output. (default: 0 (none))\n";
    }
    
    protected function getDefaultHistoryDir()
    {
        // OSX
        $dir = getenv('HOME') . '/Music/ScratchLIVE/History/Sessions';
        if(is_dir($dir)) return $dir;

        $dir = getenv('HOME') . '/Music/_Serato_/History/Sessions';
        if(is_dir($dir)) return $dir;
        
        // Windows Vista / Windows 7 ?
        $dir = getenv('USERPROFILE') . '\Music\ScratchLIVE\History\Sessions';
        if(is_dir($dir)) return $dir;

        $dir = getenv('USERPROFILE') . '\Music\_Serato_\History\Sessions';
        if(is_dir($dir)) return $dir;
        
        // Windows XP
        $dir = getenv('USERPROFILE') . '\My Documents\My Music\ScratchLIVE\History\Sessions';
        if(is_dir($dir)) return $dir;

        $dir = getenv('USERPROFILE') . '\My Documents\My Music\_Serato_\History\Sessions';
        if(is_dir($dir)) return $dir;
        
        throw new RuntimeException("Could not find your ScratchLive History folder; it wasn't where I was expecting.");
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
    
    protected function setupLogging()
    {
        if($this->verbosity == 0)
        {
            L::setLogger(new NullLogger());
            return;
        }
        
        if($this->log_file)
        {
            $logger = new FileLogger();
            $logger->setLogFile($this->log_file);
        }
        else
        {
            $logger = new ConsoleLogger();
        }
        
        L::setLogger($logger);
        L::setLevel($this->verbosity);
        L::setOverrides($this->override_verbosity);
    }
    
    protected function analyzeDir($from_dir)
    {
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

        foreach($fps as $fp)
        {
            $fn = basename($fp);
            echo "** Parsing {$fn}...\n";
            try {
                $parser = new SSLParser($dom);
                $dom = $parser->parse($fp);
            } catch(Exception $e) {
                echo "-- ignoring {$fn}.\n";
            }
            $parser->close();
        }
        echo "++ Saw " . count($dom) . " chunks\n";
        echo "** Extracting tracks...\n";
        $tracks = $dom->getTracks();
        echo "++ Saw " . count($tracks) . " tracks\n";

        echo "** Importing to db";
        foreach($tracks as $track)
        {
            /* @var $track SSLTrack */
            echo ".";
            $query = sprintf("INSERT INTO history (row, filename, title, artist, deck, starttime, endtime, played, updatedAt, playtime, length, album, fullpath)
                         VALUES (%d, '%s', '%s', '%s', %d, %d, %d, %d, %d, %d, '%s', '%s', '%s')",
                $track->getRow(),
                sqlite_escape_string($track->getFilename()),
                sqlite_escape_string($track->getTitle()),
                sqlite_escape_string($track->getArtist()),
                $track->getDeck(),
                $track->getStartTime(),
                $track->getEndTime(),
                $track->getPlayed(),
                $track->getUpdatedAt(),
                $track->getPlayTime(),
                $track->getLengthInSeconds(SSLTrack::TRY_HARD),
                sqlite_escape_string($track->getAlbum()),
                sqlite_escape_string($track->getFullpath())
            );
            
            if(!$this->dbo->exec($query))
            {
                throw new Exception($error);
            }
        }
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
