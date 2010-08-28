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

interface SSLPlugin
{
    /**
     * Help output.
     * 
     * @return string of additional help text
     */
    public function usage($appname, array $argv);
    
    /**
     * Attempt to parse an option.
     * 
     * @return true if the option was parsed by the plugin, false otherwise.
     */
    public function parseOption($arg, array &$argv);
    
    /**
     * Called before the main app is initialised. This is the right
     * place to configuration thins with the user, e.g. OAuth on 
     * Twitter, Last.fm app authorisation, etc.
     */
    public function onSetup();
    
    /**
     * Called right before any of the modelling is set up. This is the
     * right place to do complex setup before the plugin is hooked
     * into the listeners.
     */
    public function onInstall();
    
    /**
     * Called right before the clock starts ticking.
     */
    public function onStart();
    
    /**
     * Called right before shutdown.
     */
    public function onStop();
    
    /**
     * @return array of Observer
     */
    public function getObservers();
}