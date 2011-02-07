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

require_once 'External/vgd.php';

class VgdURLShortener implements IURLShortener
{
    public function shorten($url)
    {
        L::level(L::INFO) && 
            L::log(L::INFO, __CLASS__, "Shortening %s", 
                array($url));
        
        $result = vgdShorten($url);
        
        if($result['shortURL'])
        {
            L::level(L::INFO) && 
                L::log(L::INFO, __CLASS__, "Shortened to %s", 
                    array($result['shortURL']));
                    
            return $result['shortURL'];
        }
        
        L::level(L::WARNING) && 
            L::log(L::WARNING, __CLASS__, "Shorten failed: %s", 
                array($result['errorMessage']));
        
        // On failure we return empty rather than the original URL
        // on the assumption that it was too long in the first place
        
        //return $url;
        return '';
    }
}