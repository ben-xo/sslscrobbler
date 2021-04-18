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

require_once 'External/php-discord-sdk/support/sdk_discord.php';

/**
 * Sends your current Now Playing track to a Twitter account.
 * 
 * For command line setup @see CLITwitterPlugin
 */
class DiscordPlugin implements SSLPlugin, SSLOptionablePlugin
{
    protected $config;
    protected $sessionname;
    protected $synchronous = false;
    protected $threading = false;

    public function __construct(array $config, $sessionname)
    {
        $this->setConfig($config);
        $this->sessionname = $sessionname;
    }
    
    public function setOptions(array $options) {
    }
    
   
    public function onSetup() 
    {
        $this->loadOrAuthDiscordConfig();
    }
    
    public function onStart() {}
    public function onStop() {}
    
    public function getObservers()
    {
        return array( $this->getAdaptor() );
    }

    public function setConfig(array $config)
    { 
        $this->config = $config;
    }
    
    protected function getAdaptor() {
        $adaptor = new SSLDiscordAdaptor(
            $this->getDiscordSDK(),
            $this->config['message'],
            $this->config['filters'],
            $this->sessionname,
            $this->config['webhook_url']
        );
        return $adaptor;
    }

    protected function loadOrAuthDiscordConfig()
    {
        $auth_file = 'discord-' . $this->sessionname . '.txt';
        while(!file_exists($auth_file))
        {
            echo "Discord: Authorizing for {$this->sessionname}...\n";
            $this->authDiscord($this->sessionname);
        }
        
        $this->config['webhook_url'] = trim(file_get_contents($auth_file));
    }
        
    protected function authDiscord($save_name)
    {   
        $config = $this->config;
        
        $ui = new UI();
        
        // Connecting a desktop app to Discord requires you to set up an API account at Discord's side (because they seem strict
        // about not having API keys and details in your code). So, now we guide you through that flow.
        echo "You'll need to do a few steps at Discord first.\n";
        
        $webhook_url = $ui->readline("* You will need to ask the guild owner to create a webhook URL. Paste the URL here: ");
        
        $this->config['webhook_url'] = $webhook_url;

        echo "(Written to discord-{$save_name}.txt)\n";
        
        if(file_put_contents("discord-{$save_name}.txt", $webhook_url))
        {
            return;
        }
        
        throw new RuntimeException("Failed to save {$bot_or_bearer} token to discord-{$save_name}.txt");
    }  
     
    /**
     * @return DiscordSDK
     */
    protected function getDiscordSDK()
    {
        $discord = new DiscordSDK();
        return $discord;
    }
}
