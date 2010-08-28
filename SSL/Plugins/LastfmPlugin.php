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

require_once 'External/PHP-Scrobbler/Scrobbler.php';
require_once 'External/phplastfmapi-0.7.1-xo/lastfmapi/lastfmapi.php';

/**
 * Scrobbles to Last.fm, and also updates the Now Playing status too. :)
 */
class LastfmPlugin implements SSLPlugin
{
    protected $config;
    protected $username;

    public function __construct(array $config)
    {
        $this->setConfig($config);
    }
    
    public function usage($appname, array $argv)
    {
        echo "Last.fm options:\n";
        echo "    -L or --lastfm <username>: Scrobble / send 'Now Playing' to Last.fm for user <username>. (Will ask you to authorize if you have not already)\n";
        echo "\n";
    }
    
    public function parseOption($arg, array &$argv) 
    {
        if($arg == '--lastfm' || $arg == '-L')
        {
            $this->username = array_shift($argv);
            return true;
        }
        
        return false;
    }
    
    public function onSetup() 
    {
        $this->loadOrAuthLastfmConfig();
    }
    
    public function onInstall() {}
    public function onStart() {}
    public function onStop() {}
    
    public function getObservers()
    {
        if($this->username)
        {
            return array(
                new SSLScrobblerAdaptor( $this->getScrobbler() )
            );
        }

        return array();
    }

    public function setConfig(array $config)
    { 
        $this->config = $config;
    }

    protected function loadOrAuthLastfmConfig()
    {
        if(isset($this->username))
        {
            $sk_file = 'lastfm-' . $this->username . '.txt';
            while(!file_exists($sk_file))
            {
                echo "Last.fm username supplied, but no Session Key saved. Authorizing...\n";
                $this->authLastfm();
            }
            
            $this->config['api_sk'] = trim(file_get_contents($sk_file));
        }
    }
        
    protected function authLastfm()
    {        
        $vars = array();
        $vars['apiKey'] = $this->config['api_key'];
        $vars['secret'] = $this->config['api_secret'];
        
        $token = new lastfmApiAuth('gettoken', $vars);
        if(!empty($token->error))
        {
            throw new RuntimeException("Error fetching Last.fm auth token: " . $token->error['desc']);
        }
        
        $vars['token'] = $token->token;

        $url = 'http://www.last.fm/api/auth?api_key=' . $vars['apiKey'] . '&token=' . $vars['token'];
        
        // Automatically send the user to the auth page.
        $ui = new UI();
        $ui->openBrowser($url);
        $ui->readline("Please visit {$url} then press Enter...");
        
        $auth = new lastfmApiAuth('getsession', $vars);
        if(!empty($auth->error))
        {
            throw new RuntimeException("Error fetching Last.fm session key: " . $auth->error['desc'] . ". (Did you authorize the app?)");            
        }
        
        echo "Your session key is {$auth->sessionKey} for user {$auth->username} (written to lastfm-{$auth->username}.txt)\n";
        
        if(file_put_contents("lastfm-{$auth->username}.txt", $auth->sessionKey))
        {
            return;
        }
        
        throw new RuntimeException("Failed to save session key to lastfm-{$auth->username}.txt");
    }   
     
    /**
     * @return md_Scrobbler
     */
    protected function getScrobbler()
    {
        return new md_Scrobbler(
            $this->username, null, 
            $this->config['api_key'], 
            $this->config['api_secret'], 
            $this->config['api_sk'], 
            'xsl', '0.1'
        );
    }
}