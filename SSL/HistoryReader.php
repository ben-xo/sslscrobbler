<?php

/**
 *  @author      Ben XO (me@ben-xo.com) & Nick Masi
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
    protected $post_process = false;
    protected $dump_type = 'sessionfile';
    protected $wait_for_file = true;
    protected $dir_provided = false;
    protected $help = false;
    protected $debug_help = false;
    protected $manual_tick = false;
    protected $time_multiplier = 1.0;
    protected $csv = false;
    protected $log_file = '';
    protected $log_file_only_name = false;
    protected $verbosity = L::INFO;
        
    /**
     * Plugins that can return SSLPlugins, configured from the command line
     * 
     * @var array of SSLPlugin
     */
    protected $cli_plugins = array();
    
    /**
     * @var PluginManager
     */
    protected $plugin_manager;
    
    protected $override_verbosity = array();
    
    protected $sleep = 1;
    
    protected $appname;
    protected $filename;
    protected $historydir;
                
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
    public function setVerbosityOverride(array $override, $default_log_level)
    {
        $this->override_verbosity = $override;
        $this->verbosity = $default_log_level;
    }

    /**
     * Enable a CLI plugin.
     * 
     * @param CLIPlugin $plugin
     */
    public function addCLIPlugin(CLIPlugin $plugin)
    {
        $this->cli_plugins[] = $plugin;
    }

    public function addPlugin(SSLPlugin $plugin)
    {
        $this->plugin_manager->addPlugin($plugin);
    }
    
    public function __construct()
    {
        $this->plugin_manager = new PluginManager();
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
            // do this now so that we can still use defailt logging during parsing options
            $this->setupLogging();

            $this->parseOptions($argv);

            // do it again, as parsing options may have altered the logging setup
            $this->setupLogging();

            if (!$this->dir_provided) {
                // guess history file (always go for the most recently modified)
                $this->historydir = $this->getDefaultHistoryDir();
            }
                        
            if($this->help)
            {
                $this->usage($this->appname, $argv, $this->debug_help);
                return;
            }
            
            // yield CLI configured plugins.
            foreach($this->cli_plugins as $plugin)
            {
                /* @var $plugin CLIPlugin */
                $plugin->addPluginsTo($this->plugin_manager);
            }
            $this->cli_plugins = array();
            
            $this->plugin_manager->onSetup();
            
            $filename = $this->filename;
            
            if(empty($filename))
            {
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
                $monitor = new DiffMonitor();
                switch($this->dump_type)
                {
                    case 'sessionfile':
                        $hfm = new SSLHistoryFileMonitor($filename, $monitor);
                        break;

                    case 'sessionindex':
                        $hfm = new SSLHistoryIndexFileMonitor($filename, $monitor);
                        break;
                        
                    case 'library':
                        $hfm = new SSLLibraryFileMonitor($filename, $monitor);
                        break;
                        
                    default:
                        throw new RuntimeException('Unknown dump type. Try sessionfile, sessionindex, library');
                }
                
                $monitor->dump();
                return;
            }
            
            if($this->post_process)
            {
                $this->plugin_manager->setOptions(array('post_process' => true));
                $this->post_process($filename);
            }
            else
            {
                // start monitoring.
                $this->monitor($filename);
            }            
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
    
    public function usage($appname, array $argv, $debug_help=false)
    {
        echo "\n";
        echo "Usage: {$appname} [OPTIONS] [session file]\n";
        echo "Session file is optional. If omitted, the most recent history file from {$this->historydir} will be used automatically\n";
        echo "    -h or --help:              This message.\n";
        echo "    -i or --immediate:         Do not wait for the next history file - use the current one. You want this if Serato is already running.\n";
        echo "    -p or --post-process:      Loop through the file after the fact. Use for scrobbling a set you played with no internet.\n";
        echo "          --dir:               Use the most recent history file from this directory.\n";
        echo "\n";

        foreach($this->cli_plugins as $plugin)
        {
            /* @var $plugin CLIPlugin */
            $plugin->usage($appname, $argv);
        }

        if($debug_help)
        {
            echo "Debugging options:\n";
            echo "    -d or --dump:              Dump the file's complete structure and exit\n";
            echo "          --dump-type <x>:     Use a specific parser. Options are: sessionfile, sessionindex\n";
            echo "    -v or --verbosity <0-9>:   How much logging to output. (default: 0 (none))\n";
            echo "    -l or --log-file <file>:   Where to send logging output. (If this option is omitted, output goes to stdout)\n";
            echo "    -ln or --log-file-name-only <file>:  Same as -l but only logs the name of the track playing\n";
            echo "          --manual:            Replay the session file, one batch per tick. (Tick by pressing enter at the console)\n";
            echo "          --multiply-time <n>: Speed up time by a factor of n\n";
            echo "          --csv:               Parse the session file as a CSV, not a binary file, for testing purposes. Best used with --manual\n";
        }
        else
        {
            echo "    --debug-help:              Show help about debugging options.\n";
        }
        echo "\n";
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
        
        throw new RuntimeException("Could not find your Serato DJ / ScratchLive History folder; it wasn't where I was expecting. You'll have to tell me where to look with the --dir option.");
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

            if($arg == '--prompt')
            {
                $this->addPrompts($argv);
                continue;
            }

            if($arg == '--prompt-osascript')
            {
                Inject::map('PromptFactory', new OsascriptPromptFactory());
                $this->addPrompts($argv);
                continue;
            }

            if($arg == '--debug-help')
            {
                $this->help = true;
                $this->debug_help = true;
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

            if($arg == '--dir')
            {
                $this->dir_provided = true;
                $this->historydir = array_shift($argv);
                continue;
            }

            if($arg == '--post-process' || $arg == '-p')
            {
                $this->post_process = true;
                $this->wait_for_file = false;
                continue;
            }

            if($arg == '--log-file' || $arg == '-l')
            {
                $this->log_file = array_shift($argv);
                continue;
            }

            if($arg == '--log-file-name-only' || $arg == '-ln')
            {
                $this->log_file = array_shift($argv);
                $this->log_file_only_name = true;
                continue;
            }
            
            if($arg == '--verbosity' || $arg == '-v')
            {
                $this->verbosity = (int) array_shift($argv);
                continue;
            }
            
            if($arg == '--manual')
            {
                $this->manual_tick = true;
                continue;
            }

            if($arg == '--multiply-time')
            {
                $this->time_multiplier = (float) array_shift($argv);
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
    
    public function setupLogging()
    {
        if($this->verbosity == 0)
        {
            L::setLogger(new NullLogger());
            return;
        }
        
        if($this->log_file)
        {
            $logger = new FileLogger();
            $logger->setLogFile($this->log_file, $this->log_file_only_name);
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
     * --post-process can be used to replay a file. 
     * --manual to ticks on user input (for debugging. Overrides --post-process for ticks).
     * --csv can be used to replay from a CSV fake-file.
     * These options can be combined.
     * 
     */
    protected function monitor($filename)
    {
        // Use a dependency injection factory which returns TitleFilteredRuntimeCachingSSLTracks
        // instead of regular RuntimeCachingSSLTracks in order to get nicer titles which scrobble better
        // TODO: this doesn't feel like the right architecture as it's inheritance based, but title filtering is a behaviour.
        Inject::map('SSLRepo', new TitleFilteredSSLRepo());
        
        // Use the caching version via Dependency Injection. This means that all 
        // new SSLTracks created using a SSLTrackFactory will get a RuntimeCachingSSLTrack
        // that knows how to ask the cache about expensive lookups (such as getID3 stuff). 
        Inject::map('SSLTrackFactory', new SSLTrackCache());
        
        
        if($this->manual_tick) 
        {
            // tick when the user presses enter
            $pseudo_ts = $real_ts = new CrankHandle();
        }
        elseif($this->post_process)
        {
            $pseudo_ts = $real_ts = new InstantTickSource();
        }
        else
        {
            // tick based on the clock
            $pseudo_ts = $real_ts = new TickSource($this->time_multiplier);
        }
        
        if($this->post_process)
        {
            // $mon is TickObservable
            // $hfm is DiffObservable
            if($this->csv)
            {
                $mon = $hfm = new SSLHistoryFileCSVInjector($filename);
            }
            else
            {
                $mon = $hfm = new SSLHistoryFileReplayer($filename);
            }
            
            $pseudo_ts = $mon;
            $hfm->addExitObserver($real_ts);
        }
        else
        {
            $mon = new TailMonitor();
            $mon->setFilenameSource($this);
            $hfm = new SSLHistoryFileMonitor($filename, $mon);
        }

        $sh = new SignalHandler();
        //$ih = new InputHandler();

        $rtm = new SSLRealtimeModel();
        $rtm_printer = new SSLRealtimeModelPrinter($rtm);
        $npm = new NowPlayingModel();
        $sm = new ScrobbleModel();
        
        // the ordering here is important. See the README.txt for a collaboration diagram.
        $pseudo_ts->addTickObserver($this->plugin_manager);
        $real_ts->addTickObserver($mon);
        $pseudo_ts->addTickObserver($npm);
        $hfm->addDiffObserver($rtm);
        $rtm->addTrackChangeObserver($rtm_printer);
        $rtm->addTrackChangeObserver($npm);
        $rtm->addTrackChangeObserver($sm);

        // get the PluginWrapper that wraps all other plugins.
        $pw = $this->plugin_manager->getObservers();
        
        // add all of the PluginWrappers to the various places.
        $pseudo_ts->addTickObserver($pw[0]);
        $hfm->addDiffObserver($pw[0]);
        $rtm->addTrackChangeObserver($pw[0]);
        $npm->addNowPlayingObserver($pw[0]);
        $sm->addScrobbleObserver($pw[0]);
        
        $sh->install();
        //$ih->install();

        $this->plugin_manager->onStart();

        // Tick tick tick. This only returns if a signal is caught
        $real_ts->startClock($this->sleep, $sh/*, $ih*/);
        
        $rtm->shutdown();
        
        $this->plugin_manager->onStop();
    }
    
    protected function post_process($filename)
    {
        echo "post processing {$filename}...\n";
        
        // Use the caching version via Dependency Injection. This means that all 
        // new SSLTracks created using a SSLTrackFactory will get a RuntimeCachingSSLTrack
        // that knows how to ask the cache about expensive lookups (such as getID3 stuff). 
        Inject::map('SSLTrackFactory', new SSLTrackCache());
        
        $ts = new InstantTickSource();
        $hfm = new SSLHistoryFileReplayer($filename);
        $ism = new ImmediateScrobbleModel(); // deal with PLAYED tracks one by one
        $inp = new ImmediateNowPlayingModel(); // deal with PLAYED tracks one by one
        
        $ts->addTickObserver($hfm);
        $hfm->addExitObserver($ts);

        $hfm->addDiffObserver($ism);
        $hfm->addDiffObserver($inp);
        
        // get the PluginWrapper that wraps all other plugins.
        $pw = $this->plugin_manager->getObservers();
        
        // add all of the PluginWrappers to the various places.
        $ts->addTickObserver($pw[0]);
        $hfm->addDiffObserver($pw[0]);
        $ism->addScrobbleObserver($pw[0]);
        $inp->addNowPlayingObserver($pw[0]);
        
        $this->plugin_manager->onStart();

        // Tick tick tick. This only returns if a signal is caught
        $ts->startClock($this->sleep);
        
        $this->plugin_manager->onStop();
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

    protected function addPrompts(array &$argv)
    {
        foreach($this->cli_plugins as $plugin)
        {
            /* @var $plugin CLIPlugin */
            $plugin->addPrompts($argv);
        }
    }
}
