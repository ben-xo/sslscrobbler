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
 * Provides global static functions for logging.
 * 
 * @author ben
 */
class L
{
    const SILENT = 0;
    const ERROR = 1;
    const WARNING = 2;
    const INFO = 3; 
    const DEBUG = 9;
    
    static private $logger;
    static private $threshold;
    static private $overrides = array();
    
    private function __construct() {}
    private function __clone() {}
    
    public static function getNameFor($level)
    {
        switch($level)
        {
            case 0:  return 'SILENT';
            case 1:  return 'ERROR';
            case 2:  return 'WARNING';
            case 3:  return 'INFO';
            case 9:  return 'DEBUG';
            default: return 'UNKNOWN';
        }
    }
    
    public static function setLogger(Logger $logger)
    {
        self::$logger = $logger;
    }
        
    public static function setLevel($threshold)
    {
        self::$threshold = $threshold;
    }
    
    public static function setOverrides(array $overrides)
    {
        self::$overrides = $overrides;
    }
    
    public static function level($log_at_level)
    {
        return $log_at_level <= self::$threshold;
    }
    
    public static function log($log_at_level, $source, $message, array $args=array())
    {
        if(isset(self::$overrides[$source]))
        {
            $threshold = self::$overrides[$source];
        }
        else
        {
            $threshold = self::$threshold;
        }
        
        if($log_at_level <= $threshold)
        {
            self::$logger->log(time(), $log_at_level, $source, vsprintf($message, $args));
        }
    }
}