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

class CLIJsonServerPlugin implements CLIPlugin
{
    /**
     * @var array[JsonServerPlugin]
     */
    protected $plugins = array();
    
    protected $used_ports = array();

    protected $config;

    public function __construct(array $config=array())
    {
        $this->setConfig($config);
    }
    
    public function setConfig(array $config)
    { 
        $this->config = $config;
    }
    
    public function usage($appname, array $argv)
    {
        echo "JSON / HTML Server options:\n";
        echo "    -J or --json <port>\n";
        echo " or -H or --html <port>   :    Enable local web server for the details of the current playing track.\n";
        echo "                               The info will be available as JSON at http://<your ip>:<port>/nowplaying.json and\n";
        echo "                               also as HTML for styling with OBS at http://<your ip>:<port>/nowplaying.html\n";
        echo "\n";
        echo "                               If OBS runs on the same computer you can use URLs such as http://localhost:<port>/nowplaying.html\n";
        echo "\n";
        echo "                               By default, all available fields are displayed, but you can narrow it down to just the fields you want\n";
        echo "                               using URLs such as http://<your ip>:<port>/nowplaying.html?artist&title which you can style with CSS.\n";
        echo "\n";
        echo "    --html-template <file>:     A optional file containing strings like {{artist}} or {{title}}.\n";
        echo "\n";
    }
    
    /**
     * It's possible to include more than one instance of JsonServerPlugin
     *  
     * @see CLIPlugin::parseOption()
     */
    public function parseOption($arg, array &$argv) 
    {
        if($arg == '--json' || $arg == '-J' || $arg == '--html' || $arg == '-H')
        {
            $port = array_shift($argv);

            if(in_array($port, $this->used_ports))
                throw new RuntimeException("Port $port is already claimed by another JSON / HTML Server. Did you mean to specify more than one?");

            $this->used_ports[] = $port;
            $this->plugins[] = $this->newJsonServerPlugin($this->config, $port);
            return true;
        }

        if($arg == '--html-template')
        {
            $template_filename = array_shift($argv);

            if(!file_exists($template_filename) || !is_file($template_filename))
                throw new RuntimeException("File '$template_filename' does not exist.");

            if(!is_readable($template_filename))
                    throw new RuntimeException("File '$template_filename' can not be read.");

            $template_body = file_get_contents($template_filename);
            if($template_body === false)
                throw new RuntimeException("Could not load file '$template_body'");

            $this->config['template'] = $template_body;

            $match_count = preg_match_all('/{{([a-zA-Z0-9]+?)}}/', $template_body, $matches);
            if(!$match_count)
                throw new RuntimeException("Template $template_filename does not contain any fields such as {{artist}} or {{title}}");

            $this->config['template_fields'] = $matches[1];

            L::level(L::INFO) &&
                L::log(L::INFO, __CLASS__, "loaded HTML template from %s with %d templated fields",
                    array($template_filename, $match_count));

            L::level(L::DEBUG) &&
                L::log(L::DEBUG, __CLASS__, "Fields in this template: %s",
                    array(implode(', ', $matches[1])));

            return true;
        }

        return false;
    }

    public function addPrompts(array &$argv)
    {
        $port = 8080;

        $argv[] = '-J';
        $argv[] = $port;
    }

    public function addPluginsTo(SSLPluggable $sslpluggable)
    {
        L::level(L::DEBUG) && 
            L::log(L::DEBUG, __CLASS__, "yielding %d plugins", 
                array(count($this->plugins)));

        foreach($this->plugins as $plugin)
        {
            $plugin->setConfig($this->config);
            $sslpluggable->addPlugin($plugin);
        }
    }

    protected function newJsonServerPlugin($config, $port)
    {
        return new JsonServerPlugin($config, $port);
    }
}