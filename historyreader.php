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

require_once 'External/getID3/getid3.php';
require_once 'SSL/Autoloader.php';

//define('SCROBBLER_LOG', '/tmp/scrobbler.log');
//define('SINGLE_THREADED', true);

function __autoload($class)
{
    $a = new Autoloader();
    return $a->load($class);
}

$growlConfig = array(
    'address' => 'localhost',
    'password' => '',
    'app_name' => 'SSLHistoryReader'
);

$lastfmConfig = array(
    'api_key' => '9dc2c6ce26602ff23787a7ebd4066ad8',
    'api_secret' => '9cc1995235704e14d9d9dcdb3a2ba693'
);

$twitterConfig = array(
    'consumer_key' => 'muDxig9YR8URoKrv3GamA',
    'consumer_secret' => 'UyOd1a9Gjicoc1Yt4dvZT3Ext8Z2paH40YSRYambc',
    'message' => 'now playing: %s → :beatport:"',
    'filters' => array(
        // filters from SSL/Plugins/Twitter/MessageFilters
        new BeatportTrackMessageFilter( new VgdURLShortener() )
    )
);

$nowplayingloggerConfig = array(
    'filename' => dirname(__FILE__) . '/SSL/Plugins/NowPlaying/nowplaying.txt'
);

// set max log levels for various internal components. (The default is unlimited.)
$log_levels = array(
//    'TickSource' => L::SILENT,
//    'SSLHistoryFileMonitor' => L::DEBUG,
//    'SSLRealtimeModel' => L::DEBUG,
//    'NowPlayingModel' => L::DEBUG,
);

$h = new HistoryReader();
$h->setVerbosityOverride($log_levels);
$h->addPlugin(new GrowlPlugin($growlConfig));
$h->addPlugin(new LastfmPlugin($lastfmConfig));
$h->addPlugin(new TwitterPlugin($twitterConfig));
$h->addPlugin(new NowPlayingLoggerPlugin($nowplayingloggerConfig));

/* Disabled plugins */
//$h->addPlugin(new JSONServerPOC());
//$h->addPlugin(new AnalyzerPlugin(array('db' => dirname(__FILE__) . '/analyze.db')));

$h->main($argc, $argv);
