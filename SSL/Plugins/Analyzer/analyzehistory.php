#!/usr/bin/env php
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

error_reporting(E_ALL | E_STRICT);

chdir(dirname(dirname(dirname(__DIR__))));
require_once 'External/getID3/getid3.php';
require_once 'SSL/Autoloader.php';
chdir(__DIR__);

//define('SCROBBLER_LOG', '/tmp/scrobbler.log');
//define('SINGLE_THREADED', true);

function ___autoload($class)
{
    $a = new Autoloader();
    return $a->load($class);
}

spl_autoload_register("___autoload");


// set max log levels for various internal components. (The default is unlimited.)
$log_levels = array(
//    'TickSource' => L::SILENT,
//    'SSLHistoryFileMonitor' => L::DEBUG,
//    'SSLRealtimeModel' => L::DEBUG,
//    'NowPlayingModel' => L::DEBUG,
);

$h = new HistoryAnalyzer();
$h->setVerbosityOverride($log_levels, L::INFO);
$h->main($argc, $argv);
