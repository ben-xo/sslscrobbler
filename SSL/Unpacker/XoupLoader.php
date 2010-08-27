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

class XoupLoader
{
    /**
     * @var XoupRepo
     */
    protected $factory;
    
    public function __construct()
    {
        $this->factory = Inject::the(new XoupRepo());
    }
    
    /**
     * @return Unpacker
     */
    public function load($filename, $load_compiled=true, $compile=true)
    {
        if($load_compiled)
        {
            $unpacker = $this->loadCompiled($filename);
            if($unpacker)
            {
                return $unpacker;
            }
        }
        
        $program = file_get_contents($filename);
        if($compile)
        {
            $compiler = $this->factory->newCompiler($program);
            $compiler->compile($filename);
            
            // load the compiled file, but don't recursively recompile if
            // something goes horribly wrong.
            return $this->load($filename, true, false);
        }
        
        if(empty($program)) 
        {
            throw new RuntimeException("Could not load {$filename}");
        }
        
        return $this->factory->newInterpreter($program);
    }
    
    /**
     * @return Unpacker|NULL
     */
    protected function loadCompiled($filename)
    {
        $basename = basename($filename, '.xoup');
        $class = 'XOUP' . $basename . 'Unpacker';
        
        if(!class_exists($class, false))
        {
        
            $dirname  = dirname($filename);
            $compiled_name = $dirname . '/' . $basename . '.php';
            if(file_exists($dirname . '/' . $basename . '.php'))
            {
                require_once $compiled_name;
                
                if(!class_exists($class))
                {
                    throw new RuntimeException("{$class} not present in {$compiled_name}");
                }
                
                L::level(L::DEBUG) && 
                    L::log(L::DEBUG, __CLASS__, 'loaded compiled XOUP class %s from file %s', 
                        array($class, $compiled_name));                
            }
            else
            {
                // no compiled file exists

                L::level(L::DEBUG) && 
                    L::log(L::DEBUG, __CLASS__, 'compiled XOUP file %s does not (yet) exist', 
                        array($compiled_name));                
                
                return null;
            }
        }
        return new $class;
    }
}
