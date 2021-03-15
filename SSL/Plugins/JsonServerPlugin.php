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

class JsonServerPlugin implements SSLPlugin, NowPlayingObserver, TickObserver, ParallelTask
{
    protected $socket;
    protected $port;
    
    protected $most_recent_track;
    protected $most_recent_accepted_connection;
    
    public function __construct(array $config, $port)
    {
        $this->port = $port;
    }

    public function onSetup() 
    {
    }
    
    public function onStart() 
    {
        $this->socket = socket_create_listen($this->port, SOMAXCONN);
        if($this->socket == false)
        {
            throw new RuntimeException("Listening on port {$this->port} failed: " . socket_last_error());
        }
        
        socket_set_nonblock($this->socket);
        
        L::level(L::DEBUG) && 
            L::log(L::DEBUG, __CLASS__, "Installed JSON / HTML listener...", 
                array());

        if(L::level(L::INFO))
        {
            L::log(L::INFO, __CLASS__, "now playing info will be available at:", 
                array());

            L::log(L::INFO, __CLASS__, "- http://localhost:%d/nowplaying.json", 
                array($this->port));

            L::log(L::INFO, __CLASS__, "- http://localhost:%d/nowplaying.html (for OBS on this computer)", 
                array($this->port));

            L::log(L::INFO, __CLASS__, "- http://%s:%d/nowplaying.json", 
                array($this->getLocalIP(), $this->port));

            L::log(L::INFO, __CLASS__, "- http://%s:%d/nowplaying.html (for OBS on this computer)", 
                array($this->getLocalIP(), $this->port));
        }
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
        do
        {
            // handle all pending requests in parallel
            $conn = @socket_accept($this->socket);
            if($conn !== false)
            {
                $this->most_recent_accepted_connection = $conn;
                $runner = new ParallelRunner();
                $runner->spinOff($this, 'JSON / HTML Request');
                unset($this->most_recent_accepted_connection);
            }
        }
        while($conn !== false);
    }
    
    public function run()
    {
        $this->handleRequest($this->most_recent_accepted_connection);
    }
    
    protected function generateJSON()
    {
        if(isset($this->most_recent_track))
        {
            $body = $this->most_recent_track->toJson();
        }
        else
        {
            $body = json_encode(null);
        }
        
        $len = strlen($body);
        return array(
            'HTTP/1.0 200 OK',
            'Date: ' . date('r'),
            'Content-Type: application/json',
            'Content-Length: ' . $len,
            'Server: ScratchLive! Scrobbler',
            'Connection: close', 
            '',
            $body
        );
    }

    protected function generateHTML()
    {
        $body = "<!doctype html>\n<html><head><title>Now Playing in Serato</title>\n";
        $body .= "<meta http-equiv=\"refresh\" content=\"5\">\n";
        $body .= "</head><body>\n";

        if(isset($this->most_recent_track))
        {
            $data = $this->most_recent_track->toArray();
            foreach($data as $k => $v)
            {
                $body .= sprintf("<div id=\"%s\">%s</div>\n", $k, htmlspecialchars($v));
            }
        }
        
        $body .= "</body></html>";

        $len = strlen($body);
        return array(
            'HTTP/1.0 200 OK',
            'Date: ' . date('r'),
            'Content-Type: text/html',
            'Content-Length: ' . $len,
            'Server: ScratchLive! Scrobbler',
            'Connection: close', 
            '',
            $body
        );
    }

    protected function generate404()
    {
        $body = '<html><head><title>404 Not Found</title></head><body>No Dice.</body></html>';
        $len = strlen($body);
        return array(
            'HTTP/1.0 404 Not Found',
            'Date: ' . date('r'),
            'Content-Type: text/html',
            'Content-Length: ' . $len,
            'Server: ScratchLive! Scrobbler',
            'Connection: close', 
            '',
            $body
        );
    }

    protected function handleRequest($conn)
    {
        socket_set_block($conn);
        $request = '';
        $bytes = socket_recv($conn, $request, 16384, 0);
        if($bytes === false)
        {
            L::level(L::DEBUG) && 
                L::log(L::DEBUG, __CLASS__, "Problem reading from socket: %s", 
                    array(socket_last_error($conn)));
                       
            return;
        }
        
        $request = explode("\n", $request);
        $get_line = explode(' ', $request[0]);
        if(preg_match('#^/nowplaying\.json(?:\?.*|$)#', $get_line[1]))
        {
            $route_name = 'nowplaying.json';
            $lines = $this->generateJSON();
        }
        if(preg_match('#^/nowplaying.html(?:\?.*|$)#', $get_line[1]))
        {
            $route_name = 'nowplaying.html';
            $lines = $this->generateHTML();
        }
        else
        {
            $route_name = 'unknown';
            $lines = $this->generate404();
        }

        socket_write($conn, implode("\n", $lines));
        socket_close($conn);
        L::level(L::DEBUG) && 
            L::log(L::DEBUG, __CLASS__, "Finished handling %s request.", 
                array($route_name));
    }

    /**
     * Returns which of your IPs is the one that gets to the internet by setting up a connection to Google DNS
     * (which doesn't send any traffic, as it's UDP).
     * 
     * N.B. Whilst this is most likely to be the IP of your computer on your local network, it could also give
     * a different IP (that's no use for OBS) if you happen to be on a VPN or something.
     */
    private function getLocalIP()
    {
        $sock = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
        socket_connect($sock, "8.8.8.8", 53);
        socket_getsockname($sock, $name); // $name passed by reference
        return $name;
    }
}