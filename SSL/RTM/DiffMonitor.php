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

class DiffMonitor extends SSLFileReader implements TickObserver
{

    /**
     * @var SSLDom
     */
    protected $dom_prototype;

    protected $last_ignored_filename = '';
    
    public function setPrototype(SSLDom $prototype)
    {
        $this->dom_prototype = $prototype;
    }
    
    /**
     * @var SSLDiffDelegate
     */
    protected $diff_delegate;
    
    public function setDiffDelegate(SSLDiffDelegate $delegate)
    {
        $this->diff_delegate = $delegate;
    }
    
    /**
     * The filename source yields new filenames from time to time.
     * 
     * @var SSLFilenameSource
     */
    protected $fns;
    
    public function setFilenameSource(SSLFilenameSource $fns)
    {
        $this->fns = $fns;
    }
    
    protected function checkForNewFilename()
    { 
        if(isset($this->fns))
        {
            $old_filename = $this->filename;
            $new_filename = $this->fns->getNewFilename();
            $got_new_file = $this->fileIsNewer($old_filename, $new_filename);
            if($got_new_file)
            {
                $this->filename = $new_filename;
                L::level(L::INFO) && 
                    L::log(L::INFO, __CLASS__, "Changed to new file %s", 
                        array($this->filename));
            }
            
            return $got_new_file;
        }
        return false;
    }

    protected function fileIsNewer($old_filename, $new_filename)
    {
        if($old_filename == $new_filename)
        {
            return false;
        }

        if(preg_match("/(\d+).session$/", $old_filename, $old) && 
           preg_match("/(\d+).session$/", $new_filename, $new))
        {
            // this prevents us from switching to an older history file by mistake.
            if($old[1] < $new[1])
            {
                return true;
            }

            // don't spam
            if($new_filename != $this->last_ignored_filename)
            {
                L::level(L::INFO) &&
                    L::log(L::INFO, __CLASS__, "Ignoring update to older session file %s",
                        array($new_filename));

                $this->last_ignored_filename = $new_filename;
            }

            return false;
        }

        // if they're different but we couldn't match them numerically, just assume it's newer.
        return true;
    }
    
    public function notifyTick($seconds)
    {
        $this->checkForNewFilename();
        
        $new_tree = $this->read($this->filename);
        $changed = $new_tree->getNewOrUpdatedTracksSince($this->tree);
        if(count($changed->getTracks()) > 0 )
        {
            $this->onDiff($changed);
            $this->tree = $new_tree;
        }
    }
    
    protected function newDom()
    {
        if(!isset($this->dom_prototype))
            throw new RuntimeException('no prototype set for diffing');
        
        return clone $this->dom_prototype;
    }
    
    protected function onDiff(SSLDom $changed)
    {
        if(!isset($this->diff_delegate))
            throw new RuntimeException('no delegate set for diffs');
        
        $this->diff_delegate->onDiff($changed);
    }
}