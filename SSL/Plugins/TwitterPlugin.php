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

require_once 'External/twitteroauth-0.2.0-beta3.0/twitteroauth/twitteroauth.php';
require_once 'External/twitter-php/src/twitter.class.php';

/**
 * Sends your current Now Playing track to a Twitter account.
 * 
 * For command line setup @see CLITwitterPlugin
 */
class TwitterPlugin implements SSLPlugin
{
    protected $config;
    protected $sessionname;

    public function __construct(array $config, $sessionname)
    {
        $this->setConfig($config);
        $this->sessionname = $sessionname;
    }
    
    public function onSetup() 
    {
        $this->loadOrAuthTwitterConfig();
    }
    
    public function onStart() {}
    public function onStop() {}
    
    public function getObservers()
    {
        return array(
            new SSLTwitterAdaptor( $this->getTwitter(), $this->config['message'], $this->config['filters'] )
        );
    }

    public function setConfig(array $config)
    { 
        $this->config = $config;
    }

    protected function loadOrAuthTwitterConfig()
    {
        $sk_file = 'twitter-' . $this->sessionname . '.txt';
        while(!file_exists($sk_file))
        {
            echo "Twitter: Authorizing for {$this->sessionname}...\n";
            $this->authTwitter($this->sessionname);
        }
        
        list(
            $this->config['oauth_token'], 
            $this->config['oauth_token_secret']
        ) = explode("\n", trim(file_get_contents($sk_file)));
    }
        
    protected function authTwitter($save_name)
    {   
        $config = $this->config;
        $conn = new TwitterOAuth($config['consumer_key'], $config['consumer_secret']);
        $request_token = $conn->getRequestToken();
        if($request_token === false || $conn->lastStatusCode() != 200)
        {
            throw new RuntimeException("Error fetching Twitter auth token: Status code " .  $conn->lastStatusCode() . '; ' . $conn->http_error);
        }
        
        $url = $conn->getAuthorizeURL($request_token);
        
        // Automatically send the user to the auth page.
        
        $ui = new UI();
        $ui->openBrowser($url);
        $pin = $ui->readline("Please visit {$url} then type the pin number: ");
                
        $conn = new TwitterOAuth($config['consumer_key'], $config['consumer_secret'], $request_token['oauth_token'], $request_token['oauth_token_secret']);
        $access_token = $conn->getAccessToken($pin);        
        if($access_token === false || $conn->lastStatusCode() != 200)
        {
            throw new RuntimeException("Error fetching Twitter access token: Status code " .  $conn->lastStatusCode() . '; ' . $conn->http_error);
        }
        
        $this->config['oauth_token'] = $access_token['oauth_token'];
        $this->config['oauth_token_secret'] = $access_token['oauth_token_secret'];

        echo "Your Twitter token is " . $access_token['oauth_token'] . "\n";
        echo "Your Twitter token secret is " . $access_token['oauth_token_secret'] . "\n";
        echo "(Written to twitter-{$save_name}.txt)\n";
        
        if(file_put_contents("twitter-{$save_name}.txt", $access_token['oauth_token'] . "\n" . $access_token['oauth_token_secret']))
        {
            return;
        }
        
        throw new RuntimeException("Failed to save oauth token to twitter-{$save_name}.txt");
    }  
     
    /**
     * @return Twitter
     */
    protected function getTwitter()
    {
        $config = $this->config;
        $twitter = new Twitter(
            $config['consumer_key'], 
            $config['consumer_secret'], 
            $config['oauth_token'], 
            $config['oauth_token_secret']
        );
        return $twitter;
    }
}
