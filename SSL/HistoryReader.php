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

class HistoryReader implements SSLPluggable, SSLFilenameSource
{
    // command line switches
    protected $dump_and_exit = false;
    protected $dump_type = 'sessionfile';
    protected $wait_for_file = true;
    protected $help = false;
    protected $replay = false;
    protected $csv = false;
    protected $log_file = '';
    protected $verbosity = L::INFO;
    
    /**
     * Plugins that can return Observers.
     * 
     * @var array of SSLPlugin
     */
    protected $plugins = array();
    
    /**
     * Plugins that can return SSLPlugins, configured from the command line
     * 
     * @var array of SSLPlugin
     */
    protected $cli_plugins = array();
    
    protected $override_verbosity = array();
    
    protected $sleep = 2;
    
    protected $appname;
    protected $filename;
    protected $historydir;
    
    protected $max_plugin_id = 0;
    
    /**
     * This is set to true just as we enter the main event loop, and then 
     * unset after we leave the loop. 
     */
    protected $clock_is_ticking = false;
        
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
     * Enable a plugin.
     * 
     * @param SSLPlugin $plugin
     */
    public function addPlugin(SSLPlugin $plugin)
    {
        if($this->clock_is_ticking) 
            $plugin->onStart();
        
        $this->plugins[$this->max_plugin_id] = $plugin;
        
        L::level(L::DEBUG) && 
            L::log(L::DEBUG, __CLASS__, "added %s plugin with id %d", 
                array(get_class($plugin), $this->max_plugin_id));
        
        $this->max_plugin_id++;
    }
    
    /**
     * Disable a plugin.
     * 
     * @param int $id
     */
    public function removePlugin($id)
    {
        if($this->clock_is_ticking) 
            $this->plugins[$id]->onStop();
            
        unset($this->plugins[$id]);
    }
    
    /**
     * Enable a CLI plugin.
     * 
     * @param CLIPlugin $plugin
     */
    public function addCLIPlugin(CLIPlugin $plugin)
    {
        if($this->clock_is_ticking) 
            throw new RuntimeException("There's no point adding a CLI Plugin while the app is running.");
        
        $this->cli_plugins[] = $plugin;
    }
    
    /**
     * The main entry point to the application. Start here!
     * When this returns, the program is done.
     * 
     * Program flow:
     * * Parse options
     * * Set up logging, if requested
     * * Ask plugins to do any early setup - this is where Last.fm / Twitter do OAuth etc
     * * If no filename was supplied, either wait for a new one to be created in the default 
     *   ScratchLive history folder (polls every 2 seconds), or go for the most recent 
     *   (when --immediate is specified). 
     * * If --dump was specified, display the structure of the file and exit. (Very useful for
     *   probing ScratchLive files).
     * * Otherwise, start monitoring the file.
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
            
            // do this as early as possible, but not before parsing options which may affect it.
            $this->setupLogging();
                        
            if($this->help)
            {
                $this->usage($this->appname, $argv);
                return;
            }
            
            // yield CLI configured plugins.
            foreach($this->cli_plugins as $plugin)
            {
                /* @var $plugin CLIPlugin */
                $plugin->addPluginsTo($this);
            }
            $this->cli_plugins = array();
            
            foreach($this->plugins as $plugin)
            {
                /* @var $plugin SSLPlugin */
                $plugin->onSetup();
            }
            
            $filename = $this->filename;
            
            if(empty($filename))
            {
                // guess history file (always go for the most recently modified)
                $this->historydir = $this->getDefaultHistoryDir();
                
                if($this->wait_for_file)
                {
                    echo "Waiting for new session file...\n";
                    // find the most recent file, then wait for a new one to be created and use that.
                    $first_filename = $this->getMostRecentFile($this->historydir, 'session');
                    $second_filename = $first_filename;
                    while($second_filename == $first_filename)
                    {
                        sleep($this->sleep);
                        $second_filename = $this->getMostRecentFile($this->historydir, 'session');
                    }
                    $filename = $second_filename;
                }
                else
                {
                    $filename = $this->getMostRecentFile($this->historydir, 'session');                
                }
                
                echo "Using file $filename ...\n";
            }
                            
            if(!file_exists($filename))
                throw new InvalidArgumentException("No such file $filename.");
                
            if(!is_readable($filename))
                throw new InvalidArgumentException("File $filename not readable.");
                
                
            if($this->dump_and_exit)
            {
                switch($this->dump_type)
                {
                    case 'sessionfile':
                        $monitor = new SSLHistoryFileDiffMonitor($filename);
                        $monitor->dump();
                        return;

                    case 'sessionindex':
                        /* @var $factory SSLRepo */
                        $factory = Inject::the(new SSLRepo());
                        $parser = $factory->newParser( $factory->newHistoryIndexDom() );
                        $tree = $parser->parse($filename);
                        $parser->close();
                        $tree->getSessions();
                        echo $tree;
                        return;
                        
                    default:
                        throw new RuntimeException('Unknown dump type');
                }
            }

            // start monitoring.
            $this->monitor($filename);            
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
        echo "    -i or --immediate:         Do not wait for the next history file to be created before monitoring. (Use if you started {$appname} mid way through a session)\n";
        echo "\n";

        foreach($this->cli_plugins as $plugin)
        {
            /* @var $plugin CLIPlugin */
            $plugin->usage($appname, $argv);
        }

        echo "Debugging options:\n";
        echo "    -d or --dump:              Dump the file's complete structure and exit\n";
        echo "          --dump-type <x>:     Use a specific parser. Options are: sessionfile, sessionindex\n";
        echo "    -v or --verbosity <0-9>:   How much logging to output. (default: 0 (none))\n";
        echo "    -l or --log-file <file>:   Where to send logging output. (If this option is omitted, output goes to stdout)\n";
        echo "          --replay:            Replay the session file, one batch per tick. (Tick by pressing enter at the console)\n"; 
        echo "          --csv:               Parse the session file as a CSV, not a binary file, for testing purposes. Best used with --replay\n"; 
    }
    
    public function getNewFilename()
    {
        if(isset($this->filename)) return $this->filename;
        return $this->getMostRecentFile($this->historydir, 'session');
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
            
            if($arg == '--dump' || $arg == '-d')
            {
                $this->dump_and_exit = true;
                continue;
            }

            if($arg == '--dump-type')
            {
                $this->dump_type = array_shift($argv);
                continue;
            }
            
            if($arg == '--immediate' || $arg == '-i')
            {
                $this->wait_for_file = false;
                continue;
            }

            if($arg == '--log-file' || $arg == '-l')
            {
                $this->log_file = array_shift($argv);
                continue;
            }
            
            if($arg == '--verbosity' || $arg == '-v')
            {
                $this->verbosity = (int) array_shift($argv);
                continue;
            }
            
            if($arg == '--replay')
            {
                $this->replay = true;
                continue;
            }
            
            if($arg == '--csv')
            {
                $this->csv = true;
                continue;
            }

            foreach($this->cli_plugins as $plugin)
            {
                /* @var $plugin CLIPlugin */
                if($plugin->parseOption($arg, $argv))
                {
                    continue 2;
                }
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
    
    /**
     * Sets up and couples the event-driven history monitoring components, 
     * and then starts the clock.
     * 
     *  A signal handler is installed to catch Ctrl-C, although it's still
     *  safer to shutdown ScratchLive! first if you want everything scrobbled
     *  correctly.
     *  
     * --replay can be used to replay a file with ticks on user input (for debugging).
     * --replay --csv can be used to replay from a CSV fake-file.
     * 
     */
    protected function monitor($filename)
    {
        // Use the caching version via Dependency Injection. This means that all 
        // new SSLTracks created using a SSLTrackFactory will get a RuntimeCachingSSLTrack
        // that knows how to ask the cache about expensive lookups (such as getID3 stuff). 
        Inject::map('SSLTrackFactory', new SSLTrackCache());
        
        if($this->replay) 
        {
            // tick when the user presses enter
            $ts = new CrankHandle();
            if($this->csv)
            {
                $hfm = new SSLHistoryFileCSVInjector($filename);
            }
            else
            {
                $hfm = new SSLHistoryFileReplayer($filename);
            }
        }
        else
        {
            // tick based on the clock
            $ts  = new TickSource();
            $hfm = new SSLHistoryFileTailMonitor($filename);
            $hfm->setFilenameSource($this);
            //$hfm = new SSLHistoryFileDiffMonitor($filename);
        }

        $pm = new PluginManager();
        
        // add all pre-configured plugins to the PluginManager.
        foreach($this->plugins as $id => $plugin)
        {
            /** @var SSLPlugin $plugin */
            $pm->addPlugin($id, $plugin);
        }
        
        $sh = new SignalHandler();
        //$ih = new InputHandler();

        $rtm = new SSLRealtimeModel();
        $rtm_printer = new SSLRealtimeModelPrinter($rtm);
        $npm = new NowPlayingModel();
        $sm = new ScrobbleModel();

        // the ordering here is important. See the README.txt for a collaboration diagram.
        $ts->addTickObserver($pm);
        $ts->addTickObserver($hfm);
        $ts->addTickObserver($npm);
        $hfm->addDiffObserver($rtm);
        $rtm->addTrackChangeObserver($rtm_printer);
        $rtm->addTrackChangeObserver($npm);
        $rtm->addTrackChangeObserver($sm);

        // get the PluginWrapper that wraps all other plugins.
        $pw = $pm->getObservers();
        
        // add all of the PluginWrappers to the various places.
        $ts->addTickObserver($pw[0]);
        $hfm->addDiffObserver($pw[0]);
        $rtm->addTrackChangeObserver($pw[0]);
        $npm->addNowPlayingObserver($pw[0]);
        $sm->addScrobbleObserver($pw[0]);
        
        $sh->install();
        //$ih->install();

        $pm->onStart();

        // Tick tick tick. This only returns if a signal is caught
        $this->clock_is_ticking = true;
        $ts->startClock($this->sleep, $sh/*, $ih*/);
        $this->clock_is_ticking = false;
        
        $rtm->shutdown();
        
        $pm->onStop();
    }
    
    protected function getMostRecentFile($from_dir, $type)
    {
        $newest_mtime = 0;
        $fp = '';
        $dot_type = '.' . $type;
        $type_length = strlen($dot_type);

        $di = new DirectoryIterator($from_dir);
        foreach($di as $f)
        {
            if(!$f->isFile() || substr($f->getFilename(), -$type_length) != $dot_type)
                continue;
    
            $mtime = $f->getMTime();
            if($mtime > $newest_mtime)
            {
                $newest_mtime = $mtime;
                $fp = $f->getPathname();
            }
        }
        if($fp) return $fp;
        throw new RuntimeException("No $type file found in $from_dir");
    }
}
