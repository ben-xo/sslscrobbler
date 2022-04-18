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
 * Logs the track to a file every time there's a track change.
 *
 * Supports logging in natural string representation with config 'transform' => 'tostring' or
 * logging a serialized track object for you to play with in a hosted PHP page with config 'transform' => 'serialize'
 *
 * Remember that there's nothing stopping you configuring two (or more) instances of NowPlayingLoggerPlugin if you want both.
 */
class NowPlayingLoggerPlugin implements SSLPlugin, NowPlayingObserver
{
    protected $config;
    
    public function __construct(array $config)
    {
        $this->setConfig($config);
    }
    
    public function setConfig(array $config)
    {
        $this->config = $config;

        if(!isset($this->config['filename']))
        {
            L::level(L::WARNING, __CLASS__) &&
                L::log(L::WARNING, __CLASS__, '%s',
                    array( 'I am configured with no filename. Defaulting to nowplaying.txt in your current dir' ));

            $this->config['filename'] = 'nowplaying.txt';
        }

        if(!isset($this->config['transform']))
        {
            // warn people who used the old default config that they might want to make up their mind.

            L::level(L::WARNING, __CLASS__) &&
                L::log(L::WARNING, __CLASS__, '%s',
                    array( 'I am configured without a "transform" setting. Add "transform" => "tostring" or "transform" => "serialize" to silence this gripe.' ));

            L::level(L::WARNING, __CLASS__) &&
                L::log(L::WARNING, __CLASS__, '%s',
                    array( '("serialize" was the default before, but most people probably want "tostring", as that\'s the human readable one. Compare your config with config.php-default)' ));
        }
    }

    public function onSetup() 
    {
    }
    
    public function onStart() 
    {
    }
    
    public function getFilename()
    {
        return $this->config['filename'];
    }
    
    public function onStop() {}
    
    public function getObservers()
    {
        return array( $this );
    }

    public function transform(SSLTrack $track=null)
    {
        if(isset($this->config['transform']))
        {
            switch($this->config['transform'])
            {
                // This format goes well with the "SevenDigital" example bundled in the NowPlaying plugin folder.
                // If you want to customise the output on a PHP page, this format gives you everything.
                case 'serialize':
                    return serialize($track);

                // Most people probably just want a plain text file with the artist - title.
                case 'basic':
                    if($track)
                        return $track->getFullTitle();
                    return '';

                // log like in the logs
                case 'tostring':
                    return (string) $track;
            }
        }

        // if 'transform' is not supplied, then you probably already had a config.php before upgrading to this
        // version of SSLScrobbler and might be expecting the legacy behaviour. If this is the case you'll
        // get a warning log message too
        return serialize($track);
    }
    
    public function notifyNowPlaying(SSLTrack $track=null)
    {
        file_put_contents($this->getFilename(), $this->transform($track));
    }
}
