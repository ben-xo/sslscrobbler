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


class PluginCapsule implements SSLPlugin
{
    protected $plugins = array();
    
    /**
     * Enable a plugin.
     * 
     * @param SSLPlugin $plugin
     */
    public function addPlugin(SSLPlugin $plugin)
    {
        $this->plugins[$this->max_plugin_id] = $plugin;
        $this->max_plugin_id++;
    }
    
    /**
     * Disable a plugin.
     * 
     * @param int $id
     */
    public function removePlugin($id)
    {
        unset($this->plugins[$id]);
    }

    public function usage($appname, array $argv)
    {
        foreach($this->plugins as $plugin)
        {
            /* @var $plugin SSLPlugin */
            $plugin->usage($appname, $argv);
        }
    }
    
    public function parseOption($arg, array &$argv)
    {
        foreach($this->plugins as $plugin)
        {
            /* @var $plugin SSLPlugin */
            if($plugin->parseOption($arg, $argv))
            {
                return true;
            }
        }
        
        return false;
    }
    
    public function onSetup()
    {
        foreach($this->plugins as $plugin)
        {
            /* @var $plugin SSLPlugin */
            $plugin->onSetup();
        }
    }
    
    public function onStart()
    {
        foreach($this->plugins as $plugin)
        {
            /* @var $plugin SSLPlugin */
            $plugin->onStart();
        }
    }
    
    public function onStop()
    {
        foreach($this->plugins as $plugin)
        {
            /* @var $plugin SSLPlugin */
            $plugin->onStop();
        }
    }
    
    public function getObservers()
    {
        $observers = array();
        foreach($this->plugins as $plugin)
        {
            /* @var $plugin SSLPlugin */
            $plugin_observers = $plugin->getObservers();
            foreach($plugin_observers as $observer)
            {
                $observers[] = $observer;
            }
        }
        return $observers;
    }
}