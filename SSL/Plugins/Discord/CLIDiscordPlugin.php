<?php

/**
 *  @author      Ben XO (me@ben-xo.com)
 *  @copyright   Copyright (c) 2021 Ben XO
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
class CLIDiscordPlugin implements CLIPlugin
{

    /**
     *
     * @var DiscordPlugin[]
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
        echo "Discord options:\n";
        echo "    --discord <session>:       Post tracklists to Discord. <session> is a 'save name' for the session. You will be guided through creating a bot if this is the first time.\n";
        echo "\n";
    }

    /**
     * It's possible to include more than one instance of DiscordPlugin
     *
     * @see CLIPlugin::parseOption()
     */
    public function parseOption($arg, array &$argv)
    {
        if ($arg == '--discord') {
            $sessionname = array_shift($argv);
            $this->plugins[$sessionname] = $this->newDiscordPlugin($this->config, $sessionname);
            return true;
        }

        return false;
    }

    public function addPrompts(array &$argv)
    {
        $ui = new UI();
        $discord_session_file = glob('discord-*.txt');
        if ($discord_session_file) {
            $discord_session_file = $discord_session_file[0];
            preg_match('/discord-([^.]+)\.txt/', $discord_session_file, $matches);
            if (isset($matches[1])) {
                $discord_name = $matches[1];
                while(true) {
                    $answer = strtolower($ui->readline("Discord: do you want to post tracklists to $discord_name? [Y/n] "));
                    if ($answer == 'y' || $answer == '') {
                        $argv[] = '--discord';
                        $argv[] = $discord_name;
                        return;
                    } elseif($answer == 'n') {
                        $answer = strtolower($ui->readline("Discord: do you want to log out from @$discord_name? [y/N] "));
                        if ($answer == 'y') {
                            unlink($discord_session_file);
                        }
                        break;
                    }
                }
            }
        }

        $discord_name = $ui->readline("Discord: type your Discord session name (empty to skip): ");
        if ($discord_name) {
            $argv[] = '--discord';
            $argv[] = $discord_name;
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
    
    protected function newDiscordPlugin($config, $sessionname)
    {
        return new DiscordPlugin($config, $sessionname);
    }
}