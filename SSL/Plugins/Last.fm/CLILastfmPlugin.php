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

class CLILastfmPlugin implements CLIPlugin
{
    /**
     * @var array of LastfmPlugin
     */
    protected $plugins = array();

    protected $config;

    public function __construct(array $config)
    {
        $this->setConfig($config);
    }
    
    public function setConfig(array $config)
    { 
        $this->config = $config;
    }
    
    public function usage($appname, array $argv)
    {
        echo "Last.fm options:\n\n"
           . "    -L or --lastfm <username>:"
           . "            Scrobble / send 'Now Playing' to Last.fm for user <username>.\n"
           . "            (Will ask you to authorize if you have not already)\n\n"
           . "\n";
    }
    
    /**
     * It's possible to include more than one instance of LastfmPlugin
     *  
     * @see CLIPlugin::parseOption()
     */
    public function parseOption($arg, array &$argv) 
    {
        if($arg == '--lastfm' || $arg == '-L')
        {
            $username = array_shift($argv);
            $this->plugins[] = $this->newLastfmPlugin($this->config, $username);
            return true;
        }
                
        return false;
    }

    public function addPrompts(array &$argv)
    {
        $ui = new UI();
        $lastfm_session_files = glob('lastfm-*.txt');
        if($lastfm_session_files)
        {
            $lastfm_session_file = $lastfm_session_files[0];
            preg_match('/lastfm-([^.]+)\.txt/', $lastfm_session_file, $matches);
            if(isset($matches[1])) {
                $lastfm_name = $matches[1];
                while(true) {
                    $answer = strtolower($ui->readline("Last.fm: do you want to scrobble to www.last.fm/user/$lastfm_name? [Y/n] "));
                    if ($answer == 'y' || $answer == '') {
                        $argv[] = '-L';
                        $argv[] = $lastfm_name;
                        return;
                    } elseif($answer == 'n') {
                        $answer = strtolower($ui->readline("Last.fm: do you want to log out from www.last.fm/user/$lastfm_name? [y/N] "));
                        if ($answer == 'y') {
                            unlink($lastfm_session_file);
                        }
                        break;
                    }
                }
            }
        }

        $lastfm_name = $ui->readline("Last.fm: type your Last.fm user name (empty to skip): ");
        if ($lastfm_name) {
            $argv[] = '-L';
            $argv[] = $lastfm_name;
            return;
        }
    }   

    public function addPluginsTo(SSLPluggable $sslpluggable)
    {
        L::level(L::DEBUG, __CLASS__) && 
            L::log(L::DEBUG, __CLASS__, "yielding %d plugins", 
                array(count($this->plugins)));

        foreach($this->plugins as $plugin)
        {
            $sslpluggable->addPlugin($plugin);
        }
    }

    protected function newLastfmPlugin($config, $username)
    {
        return new LastfmPlugin($config, $username);
    }
}
