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

abstract class SSLFileReader
{
    /**
     * @var SSLRepo
     */
    protected $factory;

    protected $filename;
    
    public function __construct()
    {
        $this->factory = Inject::the(new SSLRepo());
    }
    
    public function setFilename($filename)
    {
        $this->filename = $filename;
    }
    
    /**
     * @return SSLDom
     */
    protected function read($filename)
    {
        $parser = $this->factory->newParser( $this->newDom() );
        $tree = $parser->parse($filename);
        $parser->close();
        return $tree;
    }
    
    public function dump()
    {
        L::level(L::INFO) && 
            L::log(L::INFO, __CLASS__, 'reading structure of %s...', 
                array($this->filename));
                
        $tree = $this->read($this->filename);
                
        L::level(L::INFO) && 
            L::log(L::INFO, __CLASS__, 'asking structure DOM to parse %s...', 
                array($this->filename));
        
        // Without this line you'll just get hexdumps, which is not very exciting.
        $tree->getData();
         
        L::level(L::INFO) && 
            L::log(L::INFO, __CLASS__, 'printing structure of %s...', 
                array($this->filename));
        
        // After the parsing has occurred, we get much more exciting debug output.
        $tree->display();
        
        echo "Memory usage: " . number_format(memory_get_peak_usage()) . " bytes\n";
    }    
    
    /**
     * @return SSLDom
     */
    abstract protected function newDom();
}