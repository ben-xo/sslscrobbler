<?php

/**
 *  @author      Ben XO (me@ben-xo.com)
 *  @copyright   Copyright (c) 2013 Ben XO
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

class CLIIrcCatPlugin implements CLIPlugin
{
    /**
     * @var array of IrcCatPlugin
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
        echo "IRCCat options:\n\n"
           . "    -I or --irccat <host:port#channel>:\n"
           . "            Post tracklists to IRCCat on <host:port> into channel <#channel>.\n\n"
           . "\n";
    }
    
    /**
     * It's possible to include more than one instance of TwitterPlugin
     *  
     * @see CLIPlugin::parseOption()
     */
    public function parseOption($arg, array &$argv) 
    {
        if($arg == '--irccat' || $arg == '-I')
        {
            $host_port_channel = array_shift($argv);
            if(preg_match('/^(.*?):(.*?)(#.*)$/', $host_port_channel, $matches))
            {
                $host = $matches[1];
                $port = $matches[2];
                $channel = $matches[3];
                $this->plugins[] = $this->newIrcCatPlugin($this->config, $host, $port, $channel);
            } 
            else 
            {
                throw new RuntimeException(sprintf("Couldn't understand %s %s (expected %s host:port#channel)", $arg, $host_port_channel, $arg));
            }
                
            return true;
        }
                
        return false;
    }

    public function addPrompts(array &$argv)
    {
        // not worth it
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

    protected function newIrcCatPlugin($config, $host, $port, $channel)
    {
        return new IrcCatPlugin($config, $host, $port, $channel);
    }
}
