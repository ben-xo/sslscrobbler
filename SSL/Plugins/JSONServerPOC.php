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

class JSONServerPOC implements SSLPlugin, NowPlayingObserver, TickObserver
{
    protected $socket;
    
    protected $most_recent_track;
    
    public function usage($appname, array $argv)
    {
    }
    
    public function parseOption($arg, array &$argv) 
    {
        return false;
    }
    
    public function onSetup() 
    {
    }
    
    public function onInstall() 
    {
    }
    
    public function onStart() 
    {
        $this->socket = socket_create_listen(10080, SOMAXCONN);
        if($this->socket == false)
        {
            throw new RuntimeException("Listening on port 10080 failed: " . socket_last_error());
        }
        
        socket_set_nonblock($this->socket);
    }
    
    public function onStop() 
    {
        socket_close($this->socket);
    }

    public function getObservers()
    {
        return array( $this );
    }
        
    public function notifyNowPlaying(SSLTrack $track=null)
    {
        $this->most_recent_track = $track;
    }
    
    public function notifyTick($seconds)
    {
        $conn = @socket_accept($this->socket);
        if($conn !== false)
        {
            $this->handleRequest($conn);
        }
    }
    
    
    protected function handleRequest($conn)
    {
        L::level(L::DEBUG) && 
            L::log(L::DEBUG, __CLASS__, "Forking to handle request...", 
                array());
                    
        $pid = pcntl_fork();
        if($pid)
        {
            // parent
            if($pid == -1)
            {            
                throw new RuntimeException("Fork failed!");
            }
        }
        else
        {
            // child
            
            socket_set_block($conn);
            $request = '';
            $bytes = socket_recv($conn, $request, 16384, 0);
            if($bytes === false)
            {
                L::level(L::DEBUG) && 
                    L::log(L::DEBUG, __CLASS__, "Problem reading from socket: %s", 
                        array(socket_last_error($conn)));   
                exit;
            }
            
            $request = explode("\n", $request);
            $get_line = explode(' ', $request[0]);
            if(preg_match('#^/nowplaying\.json(?:\?.*|$)#', $get_line[1]))
            {
                $data = array();
                if(isset($this->most_recent_track))
                {
                    $track = $this->most_recent_track;
                    $data = array(
                        'artist' => $track->getArtist(),
                        'title' => $track->getTitle(),
                        'album' => $track->getAlbum(),
                        'length' => $track->getLengthInSeconds()
                    );
                }
                
                $body = json_encode($data);
                $len = strlen($body);
                $lines = array(
                    'HTTP/1.0 200 OK',
                    'Date: ' . date('r'),
                    'Content-Type: application/json',
                    'Content-Length: ' . $len,
                    'Server: ScratchLive! Scrobbler',
                    'Connection: close', 
                    '',
                    $body
                );
                socket_write($conn, implode("\n", $lines));
                socket_close($conn);
                L::level(L::DEBUG) && 
                    L::log(L::DEBUG, __CLASS__, "Finished handling request.", 
                        array());
            }
            else
            {
                $body = '<html><head><title>404 Not Found</title></head><body>No Dice.</body></html>';
                $len = strlen($body);
                $lines = array(
                    'HTTP/1.0 404 Not Found',
                    'Date: ' . date('r'),
                    'Content-Type: text/html',
                    'Content-Length: ' . $len,
                    'Server: ScratchLive! Scrobbler',
                    'Connection: close', 
                    '',
                    $body
                );
                socket_write($conn, implode("\n", $lines));
                socket_close($conn);
                L::level(L::DEBUG) && 
                    L::log(L::DEBUG, __CLASS__, "Handled unknown request.", 
                        array());
            }
            
            exit;
        }
    }
    
}