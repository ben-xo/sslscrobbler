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

class CLIDBPlugin implements CLIPlugin
{
    /**
     * @var array of DBPlugin
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
        echo "DB options:\n";
        echo "    -D or --db <key>:          run an SQL statement based on <key> and the track name (edit config.php for the SQL)\n";
        echo "                               Current configured statement is:\n";
        echo "                               ";
        printf($this->config['sql'], '<trackname>', '<key>');
        echo "\n";
    }
    
    /**
     * It's possible to include more than one instance of DBPlugin
     *  
     * @see CLIPlugin::parseOption()
     */
    public function parseOption($arg, array &$argv) 
    {
        if($arg == '--db' || $arg == '-D')
        {
            $key = array_shift($argv);
            $this->plugins[] = $this->newDBPlugin($this->config, $key);
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

    protected function newDBPlugin($config, $key)
    {
        return new DBPlugin($config, $key);
    }
}