<?php

/**
 *  @author      Ben XO (me@ben-xo.com)
 *  @copyright   Copyright (c) 2022 Ben XO
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

class CLINowPlayingLoggerPlugin implements CLIPlugin
{
    /**
     * @var array of NowPlayingLoggerPlugin
     */
    protected $plugins = array();
   
    public function usage($appname, array $argv)
    {
        echo "Log 'Now Playing' Track options:\n";
        echo "    -ln or --log-track <file>: log the current playing track to a file (e.g. for streaming)\n";
        echo "    -ls or --log-serialized <file>: log the current playing track to a file in PHP serialized form (contains more info, but not human readable)\n";
        echo "    -lt or --log-tostring <file>: log the current playing track to a file in a fuller representation like in the logs\n";
        echo "\n";
    }

    /**
     * It's possible to include more than one instance of NowPlayingLoggerPlugin
     *  
     * @see CLIPlugin::parseOption()
     */
    public function parseOption($arg, array &$argv)
    {

        // --log-file-name-only is supported in recognition of the contribution by N-Masi
        if($arg == '--log-track' || $arg == '--log-file-name-only' || $arg == '-ln')
        {
            $config = array(
                'filename' => array_shift($argv),
                'transform' => 'basic'
            );
            $this->plugins[] = $this->newNowPlayingLoggerPlugin($config);
            return true;
        }

        if($arg == '--log-serialized' || $arg == '-ls')
        {
            $config = array(
                'filename' => array_shift($argv),
                'transform' => 'serialized'
            );
            $this->plugins[] = $this->newNowPlayingLoggerPlugin($config);
            return true;
        }


        if($arg == '--log-tostring' || $arg == '-lt')
        {
            $config = array(
                'filename' => array_shift($argv),
                'transform' => 'tostring'
            );
            $this->plugins[] = $this->newNowPlayingLoggerPlugin($config);
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
        L::level(L::DEBUG) &&
            L::log(L::DEBUG, __CLASS__, "yielding %d plugins",
                array(count($this->plugins)));

        foreach($this->plugins as $plugin)
        {
            $sslpluggable->addPlugin($plugin);
        }
    }

    protected function newNowPlayingLoggerPlugin($config)
    {
        return new NowPlayingLoggerPlugin($config);
    }
}