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

class CLITwitterPlugin implements CLIPlugin
{
    /**
     * @var TwitterPlugin[]
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
        echo "Twitter options:\n";
        echo "    -T or --twitter <session>: Post tracklists to Twitter. <session> is a 'save name' for the session. (Will ask you to authorize if you have not already)\n";
        echo "          --twitter-thread <session>: Post as replies, so they appear in a single thread.\n";
        echo "\n";
    }
    
    /**
     * It's possible to include more than one instance of TwitterPlugin
     *  
     * @see CLIPlugin::parseOption()
     */
    public function parseOption($arg, array &$argv) 
    {
        if($arg == '--twitter' || $arg == '-T')
        {
            $sessionname = array_shift($argv);
            $this->plugins[$sessionname] = $this->newTwitterPlugin($this->config, $sessionname);
            return true;
        }

        if($arg == '--twitter-thread')
        {
            $sessionname = array_shift($argv);
            $this->plugins[$sessionname] = $this->newTwitterPlugin($this->config, $sessionname);
            $this->plugins[$sessionname]->setThreading(true);
            return true;
        }
        
        return false;
    }

    public function addPrompts(array &$argv)
    {
        $prompt = Inject::the(new PromptFactory())->newPrompt();
        $twitter_session_files = glob('twitter-*.txt');
        if($twitter_session_files)
        {
            $twitter_session_file = $twitter_session_files[0];
            preg_match('/twitter-([^.]+)\.txt/', $twitter_session_file, $matches);
            if(isset($matches[1])) {
                $twitter_name = $matches[1];
                while(true) {
                    $answer = $prompt->readline("Twitter: do you want to tweet to @$twitter_name? [Y/n] ");
                    if ($answer == 'y' || $answer == '') {
                        $argv[] = '-T';
                        $argv[] = $twitter_name;
                        return;
                    } elseif($answer == 'n') {
                        $answer = $prompt->readline("Twitter: do you want to log out from @$twitter_name? [y/N] ");
                        if ($answer == 'y') {
                            unlink($twitter_session_file);
                        }
                        break;
                    }
                }
            }
        }

        $twitter_name = $prompt->readline("Twitter: type your Twitter name (empty to skip): @");
        if ($twitter_name) {
            $argv[] = '-T';
            $argv[] = $twitter_name;
            return;
        }
    }    
    
    public function addPluginsTo(SSLPluggable $sslpluggable)
    {
        L::level(L::DEBUG) && 
            L::log(L::DEBUG, __CLASS__, "yielding %d plugins", 
                array(count($this->plugins)));
        
        foreach($this->plugins as $plugin)
        {
            $sslpluggable->addPlugin($plugin);
        }
    }

    protected function newTwitterPlugin($config, $sessionname)
    {
        return new TwitterPlugin($config, $sessionname);
    }
}