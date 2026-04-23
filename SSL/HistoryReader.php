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
    protected $plugin_help = false;
    protected $manual_tick = false;
    protected $time_multiplier = 1.0;
    protected $csv = false;
    protected $log_file = '';
    protected $verbosity = L::INFO;
        
    /**
     * Plugins that can return SSLPlugins, configured from the command line
     * 
     * @var array of SSLPlugin
     */
    protected $cli_plugins = array();
    protected $has_now_playing_plugin = false; // this plugin deserves a special help message if disabled
    
    /**
     * @var PluginManager
     */
    protected $plugin_manager;
    
    protected $override_verbosity = array();
    
    protected $sleep = 1;
    
    protected $appname;
    protected $filename;
    protected $historydir;

    // Serato 4.x uses a SQLite database instead of binary .session files.
    // If $database_path is set (auto-detected or passed via --database), the
    // DB-mode event source is used. --legacy forces the file-tailing path.
    protected $database_path;
    protected $force_legacy = false;
                
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
        if($plugin instanceof CLINowPlayingLoggerPlugin)
            $this->has_now_playing_plugin = true;
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

            if($this->help)
            {
                $this->usage($this->appname, $argv, $this->debug_help, $this->plugin_help);
                return;
            }

            if (!$this->dir_provided) {
                // guess history file (always go for the most recently modified)
                $this->historydir = $this->getDefaultHistoryDir();
            }

            // Serato 4.x auto-detect: if a master.sqlite is present and the
            // user hasn't forced --legacy, switch to the DB event source. An
            // explicit --database <path> is honoured even with --legacy off.
            if (!$this->force_legacy && empty($this->database_path) && !$this->dump_and_exit) {
                $this->database_path = $this->getDefaultDatabasePath();
            }

            // yield CLI configured plugins.
            foreach($this->cli_plugins as $plugin)
            {
                /* @var $plugin CLIPlugin */
                $plugin->addPluginsTo($this->plugin_manager);
            }
            $this->cli_plugins = array();

            $this->plugin_manager->onSetup();

            if (!empty($this->database_path) && !$this->force_legacy) {
                if (!is_file($this->database_path)) {
                    throw new InvalidArgumentException("Serato database not found at {$this->database_path}");
                }
                if (!is_readable($this->database_path)) {
                    throw new InvalidArgumentException("Serato database not readable at {$this->database_path}");
                }
                echo "Using Serato 4.x database at {$this->database_path} ...\n";

                if ($this->post_process) {
                    $this->plugin_manager->setOptions(array('post_process' => true));
                    $this->post_process_database($this->database_path);
                } else {
                    $this->monitor_database($this->database_path);
                }
                return;
            }

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
    
    public function usage($appname, array $argv, $debug_help=false, $plugin_help=false)
    {
        echo "\n";
        echo "Usage: {$appname} [OPTIONS] [session file]\n\n";

        try
        {
            // if you supplied --dir as well as --help, this will fix the help message
            $this->historydir = $this->getDefaultHistoryDir();
            echo "Session file is optional. If omitted, the most recent history file from {$this->historydir} will be used\n\n";
        }
        catch(RuntimeException $e)
        {
            // don't error and quit - we're about to print help and quit.
            echo "Session file is optional, but I can't seem to find where your session files live. Is Serato installed on this computer?\n\n";
        }

        echo "    -h or --help:              This message. You probably want to read --plugin-help too.\n";
        echo "          --prompt:            Guided setup mode.\n";
        echo "    -l or --log-file <file>:   Where to send logging output instead of stdout.\n";
        if(!$this->has_now_playing_plugin) {
        echo "\n  **Note:** For options to log the current playing track title into a file for live streams etc., enable CLINowPlayingLoggerPlugin in config.php (see example on config.php-default).\n\n";
        }
        echo "    -p or --post-process:      Loop through the session file after your DJ set is finished. \n";
        echo "                               You can use this to e.g. scrobble a set you played whilst offline.\n\n";
        echo "    -i or --immediate:         Do not wait for a session file to be created - use the current one. \n";
        echo "                               You want this if Serato is already running.\n\n";
        echo "          --dir:               Use the most recent session file from this here instead.\n";
        echo "                               This is the option for you if we couldn't correctly guess where your Serato data lives.\n\n";
        echo "          --database <path>:   Path to Serato 4.x master.sqlite. Auto-detected if omitted.\n";
        echo "          --legacy:            Force the legacy .session-file tail-monitoring path, even if a\n";
        echo "                               Serato 4.x database is present. Useful for --post-process on old sets.\n\n";
        echo "          --plugin-help:       Show help for for all of the activated plugins\n";
        echo "                               e.g. Twitter, Last FM, Discord, etc - all the good stuff.\n\n";
        echo "          --debug-help:        Show help for options not usually used during a DJ set.\n";
        echo "\n";

        if($plugin_help)
        {
            foreach($this->cli_plugins as $plugin)
            {
                /* @var $plugin CLIPlugin */
                $plugin->usage($appname, $argv);
            }
            echo "\n";
        }

        if($debug_help)
        {
            echo "Debugging options:\n";
            echo "    Note that whenever the help mentions a session file, it will be the most recent found unless you specified a specific file.\n\n";
            echo "    -d or --dump:              Dump the file's complete structure, then exit.\n";
            echo "          --dump-type <x>:     Use a specific parser. Options are: sessionfile, sessionindex\n";
            echo "    -v or --verbosity <0-9>:   How much logging to output. default: " . (string)L::INFO . " (INFO)\n";
            echo "          --manual:            Replay the session file, one batch per tick. (Tick by pressing enter at the console)\n";
            echo "          --multiply-time <N>: Speed up time by a factor of N whilst in --manual mode.\n";
            echo "          --csv:               Parse the session file as a CSV, not a binary file, for testing purposes. Best used with --manual\n";
            echo "\n";
        }
    }
    
    public function getNewFilename()
    {
        if(isset($this->filename)) return $this->filename;
        return $this->getMostRecentFile($this->historydir, 'session');
    }
    
    /**
     * Serato DJ 4.x writes history into a SQLite database at this location
     * instead of the binary .session files older versions used. If the file
     * exists we default to DB-mode monitoring; --legacy forces the old path.
     *
     * @return string|null
     */
    protected function getDefaultDatabasePath()
    {
        // macOS
        $home = getenv('HOME');
        if ($home) {
            $path = $home . '/Library/Application Support/Serato/Library/master.sqlite';
            if (is_file($path)) return $path;
        }

        // Windows
        $user_profile = getenv('USERPROFILE');
        if ($user_profile) {
            $path = $user_profile . '\AppData\Roaming\Serato\Library\master.sqlite';
            if (is_file($path)) return $path;
            $path = $user_profile . '\AppData\Local\Serato\Library\master.sqlite';
            if (is_file($path)) return $path;
        }

        return null;
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
        // in case of --prompt we need the original
        $argv_copy = $argv;
        $prompted = false;

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
                // we only want to do this once (in case of double --prompt or two prompt types)
                if(!$prompted) {
                    $prompted = true;
                    $argv_before_length = sizeof($argv);

                    $this->addPrompts($argv);

                    // we want to show the equivalent output that --prompt generated.
                    $argv_copy = array_merge($argv_copy, array_slice($argv, $argv_before_length));

                    // remove --prompt
                    $key = array_search($arg, $argv_copy);
                    unset($argv_copy[$key]);
                }
                continue;
            }

            if($arg == '--prompt-osascript')
            {
                Inject::map('PromptFactory', new OsascriptPromptFactory());

                // we only want to do this once (in case of double --prompt or two prompt types)
                if(!$prompted) {
                    $prompted = true;
                    $argv_before_length = sizeof($argv);

                    $this->addPrompts($argv);

                    // we want to show the equivalent output that --prompt generated.
                    $argv_copy = array_merge($argv_copy, array_slice($argv, $argv_before_length));

                    // remove --prompt
                    $key = array_search($arg, $argv_copy);
                    unset($argv_copy[$key]);
                }
                continue;
            }

            if($arg == '--debug-help')
            {
                $this->help = true;
                $this->debug_help = true;
                continue;
            }

            if($arg == '--plugin-help')
            {
                $this->help = true;
                $this->plugin_help = true;
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

            if($arg == '--database')
            {
                $this->database_path = array_shift($argv);
                continue;
            }

            if($arg == '--legacy')
            {
                $this->force_legacy = true;
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

        if($prompted)
        {
            echo "\n";
            echo "The equivalent command line for your prompt is:\n";
            echo "\n";
            echo "\t" . implode(" ", $argv_copy) . "\n";
            echo "\n";
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
     * Common event-graph wiring for live monitoring. Both the legacy
     * file-tail path (monitor) and the Serato 4.x DB path (monitor_database)
     * reduce to: build an event source, plug it into the same downstream
     * graph (SSLRealtimeModel → NowPlayingModel / ScrobbleModel / printer /
     * plugins), then tick. Only the event source construction and the
     * mode-specific tick observer differ.
     *
     * A signal handler is installed to catch Ctrl-C, although it's still
     * safer to shutdown Serato first if you want everything scrobbled
     * correctly.
     *
     * @param SSLDiffObservable $hfm The event source.
     * @param callable $register_source_tick fn(TickSource $real_ts): void.
     *     Called so the caller can add whatever mode-specific tick observer
     *     drives its source — the legacy path registers its TailMonitor; the
     *     DB path registers the SSLHistoryDatabaseMonitor itself (which is
     *     its own TickObserver).
     *
     * --manual to tick on user input (for debugging).
     */
    protected function runLiveEventLoop(SSLDiffObservable $hfm, callable $register_source_tick)
    {
        // Use a dependency injection factory which returns TitleFilteredRuntimeCachingSSLTracks
        // instead of regular RuntimeCachingSSLTracks in order to get nicer titles which scrobble better.
        // TODO: this doesn't feel like the right architecture as it's inheritance based, but title filtering is a behaviour.
        Inject::map('SSLRepo', new TitleFilteredSSLRepo());

        // Use the caching version via Dependency Injection. This means that all
        // new SSLTracks created using a SSLTrackFactory will get a RuntimeCachingSSLTrack
        // that knows how to ask the cache about expensive lookups (such as getID3 stuff).
        Inject::map('SSLTrackFactory', new SSLTrackCache());

        if ($this->manual_tick) {
            // tick when the user presses enter
            $pseudo_ts = $real_ts = new CrankHandle();
        } else {
            // tick based on the clock
            $pseudo_ts = $real_ts = new TickSource($this->time_multiplier);
        }

        $sh = new SignalHandler();
        //$ih = new InputHandler();

        $rtm = new SSLRealtimeModel();
        $rtm_printer = new SSLRealtimeModelPrinter($rtm);
        $npm = new NowPlayingModel();
        $sm = new ScrobbleModel();

        // the ordering here is important. See the README.txt for a collaboration diagram.
        $pseudo_ts->addTickObserver($this->plugin_manager);
        $register_source_tick($real_ts);
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

    protected function monitor($filename)
    {
        $mon = new TailMonitor();
        $mon->setFilenameSource($this);
        $hfm = new SSLHistoryFileMonitor($filename, $mon);

        $this->runLiveEventLoop($hfm, function ($real_ts) use ($mon) {
            // TailMonitor is the tick observer — it's what actually re-reads
            // the file on each tick; SSLHistoryFileMonitor is just a diff
            // adapter between TailMonitor and the observers.
            $real_ts->addTickObserver($mon);
        });
    }

    /**
     * DB-mode equivalent of monitor(). Serato 4.x writes history to a SQLite
     * database instead of appending binary chunks to a .session file, so we
     * swap the file-tailing SSLHistoryFileMonitor + TailMonitor pair for a
     * single SSLHistoryDatabaseMonitor that polls the active session on each
     * tick. Everything downstream of the SSLDiffObservable seam (realtime
     * model, plugins, scrobble / now-playing models) is untouched.
     */
    protected function monitor_database($db_path)
    {
        $pdo = SSLHistoryDatabaseMonitor::openReadOnly($db_path);
        $hfm = new SSLHistoryDatabaseMonitor($pdo);

        $this->runLiveEventLoop($hfm, function ($real_ts) use ($hfm) {
            // The DB monitor *is* the tick observer (it polls on tick and
            // emits diffs), so we register it directly.
            $real_ts->addTickObserver($hfm);
        });
    }

    /**
     * Common setup for replay-mode post-processing. Legacy and DB paths
     * both pump their source through ImmediateScrobbleModel /
     * ImmediateNowPlayingModel and the plugin wrapper; they differ only
     * in (a) how they construct $hfm, and (b) how they actually drive
     * the replay (tick-based for legacy, tick-based OR one-shot for DB).
     *
     * @param SSLDiffObservable $hfm event source
     * @param callable $driver fn(array $plugin_wrappers): void. Called
     *     once all observers are wired. The driver is responsible for
     *     actually pumping events out of $hfm (startClock, runOnce, etc.)
     *     and for registering itself on any tick source it creates.
     */
    protected function runPostProcessLoop(SSLDiffObservable $hfm, callable $driver)
    {
        Inject::map('SSLRepo', new TitleFilteredSSLRepo());
        Inject::map('SSLTrackFactory', new SSLTrackCache());

        $ism = new ImmediateScrobbleModel(); // deal with PLAYED tracks one by one
        $inp = new ImmediateNowPlayingModel(); // deal with PLAYED tracks one by one

        $hfm->addDiffObserver($ism);
        $hfm->addDiffObserver($inp);

        // get the PluginWrapper that wraps all other plugins.
        $pw = $this->plugin_manager->getObservers();

        // add all of the PluginWrappers to the various places.
        $hfm->addDiffObserver($pw[0]);
        $ism->addScrobbleObserver($pw[0]);
        $inp->addNowPlayingObserver($pw[0]);

        $this->plugin_manager->onStart();

        $driver($pw);

        $this->plugin_manager->onStop();
    }

    /**
     * DB-mode equivalent of post_process(). Replays the active (or
     * most-recent) session's rows through the Immediate* models so a set
     * played offline or captured after-the-fact can be scrobbled without
     * needing Serato to re-emit events.
     *
     * Without --manual this is a one-shot: every row goes out in a single
     * diff and we're done. With --manual, we do a stepped replay — one row
     * per enter press, analogous to the legacy SSLHistoryFileReplayer.
     */
    protected function post_process_database($db_path)
    {
        echo "post processing {$db_path}...\n";

        $pdo = SSLHistoryDatabaseMonitor::openReadOnly($db_path);
        $hfm = new SSLHistoryDatabaseMonitor($pdo);

        if ($this->manual_tick) {
            $this->runPostProcessLoop($hfm, function ($pw) use ($hfm) {
                $ts = new CrankHandle();
                $count = $hfm->prepareSteppedReplay();
                echo "Stepped replay: {$count} entries queued. Press Enter to advance.\n";
                $ts->addTickObserver($hfm);
                $hfm->addExitObserver($ts);
                $ts->addTickObserver($pw[0]);
                $ts->startClock(0);
            });
        } else {
            $this->runPostProcessLoop($hfm, function ($pw) use ($hfm) {
                $count = $hfm->runOnce();
                echo "Processed {$count} entries from the most recent session.\n";
            });
        }
    }

    protected function post_process($filename)
    {
        echo "post processing {$filename}...\n";

        $ts = $this->manual_tick ? new CrankHandle() : new InstantTickSource();
        $hfm = $this->csv
            ? new SSLHistoryFileCSVInjector($filename)
            : new SSLHistoryFileReplayer($filename);

        $this->runPostProcessLoop($hfm, function ($pw) use ($ts, $hfm) {
            $ts->addTickObserver($hfm);
            $hfm->addExitObserver($ts);
            $ts->addTickObserver($pw[0]);
            // Tick tick tick. This only returns if a signal is caught
            // or the replayer notifies exit when it reaches EOF.
            $ts->startClock($this->sleep);
        });
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
