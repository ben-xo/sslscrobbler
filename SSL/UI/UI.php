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
 * Helper methods for user interaction...
 */
class UI
{
    /**
     * Opens a browser on Windows and OS X.
     */
    public function openBrowser($url)
    {
        // Win
        if(preg_match("/^win/i", PHP_OS))
        {
            exec('start ' . str_replace('&', '^&', $url), $output, $retval);
        }
        
        // Mac
        elseif(preg_match("/^darwin/i", PHP_OS))
        {
            exec('open "' . $url . '"', $output, $retval);            
        }
    }

    /**
     * Reads a line from stdin.
     */
    public function readline($prompt) 
    {
        echo $prompt;
        
        // would be easier to do this with readline(), but some people don't have the extension installed.
        if(($fp = fopen("php://stdin", 'r')) !== false) 
        {
            $input = trim(fgets($fp));
            fclose($fp);
        }
        else
        {
            throw new RuntimeException('Failed to open stdin');
        }
        
        return $input;
    }    
}