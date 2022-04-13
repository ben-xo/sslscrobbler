<?php

/**
 *  @author      Ben XO (me@ben-xo.com) & Nick Masi
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

class FileLogger implements Logger
{

    protected $log_file = '';
    protected $only_name;

    public function log($timestamp, $level, $source, $message) {
        // logs into the appropriate file rather than console
        if ($this->only_name) {
            // log only the track name in write mode, this has the affect of the file only and always
            // containing the name of the currently playing track (the point of this feature is that it
            // allows you to point BUTT [Broadcast Using This Tool] to the log file and thereby always
            // display the name of the song currently playing in Serato)
            if (strcmp($source, "NowPlayingModel") == 0 && strcmp(substr($message, 0, 14), "enqueued track") == 0) {
                $file = fopen($this->log_file, 'w');
                fwrite($file, substr($message, 18) . "\n");
                fclose($file);
            }
        } else {
            // log everything sent into the file in append mode
            $file = fopen($this->log_file, "a");
            $level = L::getNameFor($level);
            fwrite($file, date("Y-m-d H:i:s", $timestamp) . " {$level}: {$source} - {$message}\n");  
            fclose($file);
        }

    }

    public function setLogFile($log_file_input, $only_name_option) {
        $this->log_file = $log_file_input;
        $this->only_name = $only_name_option;
    }
}