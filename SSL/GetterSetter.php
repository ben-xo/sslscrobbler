<?php

/**
 *  @author      Ben XO (me@ben-xo.com)
 *  @copyright   Copyright (c) 2021 Ben XO
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

abstract class GetterSetter {
    
    protected $fields = array();
    
    /**
     * Missing Method Magic Accessor
     *
     * @param string $method Method to call (get* or set*)
     * @param array $params array of parameters for the method 
     * @return mixed the result of the method
     */
    public function __call($method, $params)
    {
        $var_name = substr($method, 3);
        $var_name[0] = strtolower($var_name[0]);
        switch(strtolower(substr($method, 0, 3)))
        {
            case 'get':
                if(isset($this->fields[$var_name]))
                    return $this->fields[$var_name];
                break;
                
            case 'set':
                $this->fields[$var_name] = $params[0];
                break;
                
            default:
                throw new Exception("Unknown method '" . $method . "' called on " . __CLASS__);
        }
    }

    public function toArray()
    {
        return $this->fields;
    }

    public function toJson()
    {
        return json_encode($this->fields);
    }
}
