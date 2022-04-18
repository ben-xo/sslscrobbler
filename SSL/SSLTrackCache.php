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

class SSLTrackCache extends SSLTrackFactory
{
    /**
     * @var SSLRepo
     */
    protected $factory;
    protected $tracks = array();
    protected $file_lengths = array();
    
    public function __construct()
    {
        $this->factory = Inject::the(new SSLRepo());
    }
    
    public function register(SSLTrack $track)
    {
        $this->tracks[$track->getRow()] = $track;
        $length = $track->getLength();
        if($length)
        {
            // know the length? cache it by path too
            $this->setLengthByFullpath($track->getFullpath(), $length);
        }
        else
        {
            // don't know the length? check if it's cached by path
            $length = $this->getLengthByFullpath($track->getFullpath());
            if($length)
            {
                $track->setLength($length);
            }
        }
    }
    
    public function getByRow($row)
    {
        if(isset($this->tracks[$row]))
        {
            return $this->tracks[$row];
        }
        return null;
    }
    
    public function newTrack()
    {
        return $this->factory->newRuntimeCachingTrack($this);
    }

    public function setLengthByFullpath($fullpath, $length)
    {
        $this->file_lengths[$fullpath] = $length;
    }

    public function getLengthByFullpath($fullpath)
    {
        if(isset($this->file_lengths[$fullpath]))
        {
            return $this->file_lengths[$fullpath];
        }
    }
}
