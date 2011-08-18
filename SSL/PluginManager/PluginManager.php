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

/**
 * During the main loop, the PluginManager receives tick events and fiddles with 
 * plugin settings (if they have been changed externally).
 * 
 * PluginManager is, itself, a plugin, as it is implemented by yielding a bunch of plugins
 * that are actually just wrappers under the tight control of the PluginManager that are
 * able to programmatically allow or inhibit events to various real plugins.
 * 
 */
class PluginManager implements SSLPluggable, SSLPlugin, TickObserver
{
    /**
     * This object implements all of the various SSLPlugin interfaces
     * and is yielded to each of the observers. It acts as a thin layer
     * that passes events directly through to the real plugins, with
     * the added capability that it also acts as a switchbox that can
     * enable and disable plugins.
     * 
     * The main reason that PluginWrapper and PluginManager are separate classes
     * is because PluginManager acts as a TickObserver in order to control
     * plugins, but some of those plugins themselves may be TickObservers and
     * may be wrapped by the PluginWrapper.
     *  
     * @var PluginWrapper
     */
    protected $plugin_wrapper;
    
    protected $max_plugin_id = 0;
    
    protected $clock_is_ticking = false;
    protected $setup_done = false;
    
    public function __construct()
    {
        $this->plugin_wrapper = new PluginWrapper();
    }
    
    /**
     * Enable a plugin.
     * 
     * HistoryReader calls this indirectly when it asks CLIPlugins to add their
     * SSLPlugins to the plugin chain.
     * 
     * @param SSLPlugin $plugin
     */
    public function addPlugin(SSLPlugin $plugin)
    {
        // onSetup for late added plugins
        if($this->setup_done)
            $plugin->onSetup();

        // onStart for late added plugins
        if($this->clock_is_ticking) 
            $plugin->onStart();

        $this->plugin_wrapper->addPlugin($this->max_plugin_id, $plugin);

        L::level(L::DEBUG) && 
            L::log(L::DEBUG, __CLASS__, "added %s plugin with id %d", 
                array(get_class($plugin), $this->max_plugin_id));

        $this->max_plugin_id++;
    }
            
    public function notifyTick($seconds)
    {
        // TODO: enable or disable plugins using the PluginManagerWrapper
        //       as a switchbox.
    }
    
    public function changeLogLevel($newlevel)
    {
        L::setLevel($newlevel);
    }
    
    public function onSetup()
    {
        $this->plugin_wrapper->onSetup();
        $this->setup_done = true;
    }
    
    public function onStart()
    {
        $this->plugin_wrapper->onStart();
        $this->clock_is_ticking = true;
    }
    
    public function onStop()
    {
        $this->clock_is_ticking = false;
        $this->plugin_wrapper->onStop();
    }
    
    public function getObservers()
    {
        // PluginWrapper is all of the observers.
        return array($this->plugin_wrapper);
    }
}