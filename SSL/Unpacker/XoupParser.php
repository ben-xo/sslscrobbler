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

class XoupParser
{
    protected $data = array();
    
    public function parse($program)
    {
        $data = array();
        
        // strip comments
        $stripped_program = preg_replace("/#[^\n]*\n/", ' ', $program);
        
        // tokenize
        $ops = preg_split("/\s+/", $stripped_program);
        
        // trim
        $ops = array_map('trim', $ops);
        
        // remove empty tokens
        foreach($ops as $k => $v)
        {
            if(empty($v)) unset($ops[$k]);
        }
        
        $subs = array();
        $opdest = null;
        foreach($ops as $k => $v)
        {
            if(preg_match('/^([a-zA-Z0-9]+):$/', $v, $matches))
            {
                // it's a label
                $subs[$matches[1]] = array();
                $opdest = $matches[1];
            }
            elseif(preg_match('/^\.[a-zA-Z0-9]+$/', $v, $matches))
            {
                // we found our first piece of DATA. finish program parsing 
                // and move to DATA parsing
                $data = $this->parseData($program);
                break;
            }
            else
            {
                // it's an op
                if(is_null($opdest))
                {
                    throw new RuntimeException("Parse error: op found out of sub scope");
                }
                
                $subs[$opdest][] = $v;
            }
        }
        
        if(!in_array( 'main', array_keys($subs) ))
        {
            throw new RuntimeException("Parse error: no 'main' sub found.");
        }

        L::level(L::DEBUG) && 
            L::log(L::DEBUG, __CLASS__, 'parsed %d subs and %d literals', 
                array(count($subs), count($this->data)));
        
        return $subs;
    }
    
    public function getData()
    {
        return $this->data;
    }
    
    protected function parseData($data_text)
    {
        $data = array();
        preg_match_all('/\.([a-zA-Z0-9]+)\s+"(.*)"\s*$/m', $data_text, $matches, PREG_SET_ORDER);
        foreach($matches as $match)
        {
            $data[$match[1]] = stripslashes($match[2]);
        }
        $this->data = $data;
    }
}


