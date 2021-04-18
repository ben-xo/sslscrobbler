Discord SDK for PHP
===================

An ultra-lightweight PHP SDK for accessing the Discord API and Discord webhook endpoints.  This SDK does not currently support the Discord Gateway (i.e. the WebSocket interface).

[![Donate](https://cubiclesoft.com/res/donate-shield.png)](https://cubiclesoft.com/donate/) [![Discord](https://img.shields.io/discord/777282089980526602?label=chat&logo=discord)](https://cubiclesoft.com/product-support/github/)

Features
--------

* Does what it says on the tin in a mere 4KB of code.
* Works with [Discord](https://www.discord.com/) and compatible services.
* Has a liberal open source license.  MIT or LGPL, your choice.
* Designed for relatively painless integration into your project.
* Sits on GitHub for all of that pull request and issue tracker goodness to easily submit changes and ideas respectively.

Getting Started (API)
---------------------

Set up Discord API access:

* Visit `https://discord.com/developers/applications`.
* Create a New Application.  Name it something sensible such as "Invite Bot" or whatever the associated bot will do in the server.
* Go to the Bot tab and enable the bot user.
* Turn off the "Public Bot" and "Requires OAuth2 Code Grant" options under the Authorization Flow section.
* Scroll to the bottom of the Bot section and calculate the permissions integer for the APIs you plan on accessing.  The example code below requires the "Create Instant Invite" permission.
* Go to the General Information tab and copy the "Client ID" to the clipboard.
* In a new browser tab, visit `https://discord.com/oauth2/authorize?client_id=YOUR_APP_CLIENT_ID&scope=bot&permissions=YOUR_PERMISSIONS_INTEGER`
* If all goes well, Discord should ask which server the bot should join and then perform a verification step.
* Back in the Application, go to the Bot tab and copy the "Token" to the clipboard.

Create a 30 minute, single use invite for temporary members:

```php
<?php
	require_once "support/sdk_discord.php";

	// Replace the string with the bot token above.
	$bottoken = "YOUR_BOT_TOKEN_HERE";

	// Replace this with a valid channel ID.
	// The channel ID can be found at the end of the URL in Discord.
	$channelid = "YOUR_CHANNEL_ID_HERE";

	// Create a temporary invite.
	$discord = new DiscordSDK();
	$discord->SetAccessInfo("Bot", $bottoken);

	// For a complete list of options:
	// https://discord.com/developers/docs/resources/channel#create-channel-invite
	$options = array(
		"max_age" => 1800,
		"max_uses" => 1,
		"unique" => true,
		"temporary" => true
	);

	$result = $discord->RunAPI("POST", "channels/" . $channelid . "/invites", $options);
	if (!$result["success"])
	{
		var_dump($result);

		exit();
	}

	$url = "https://discord.gg/" . $result["data"]["code"];

	echo $url . "\n";
?>
```

Getting Started (Webhook)
-------------------------

Set up a webhook in Discord:

* Go to Server Settings in a Discord server (must be the owner).
* Integrations -> Webhooks -> New Webhook.
* Name the webhook and assign it to a Discord channel.
* Click the 'Copy Webhook URL' button.

Send a webhook notification that posts a message in a channel:

```php
<?php
	require_once "support/sdk_discord.php";

	// Replace the string with the Webhook URL above.
	$url = "URL_OF_WEBHOOK";

	// For a complete list of options:
	// https://discord.com/developers/docs/resources/webhook#execute-webhook
	$options = array(
		"content" => "It works!"
	);

	$result = DiscordSDK::SendWebhookMessage($url, $options);
	if (!$result["success"])
	{
		var_dump($result);

		exit();
	}
?>
```

Send a webhook notification that posts a message with a file attachment in a channel:

```php
<?php
	require_once "support/sdk_discord.php";

	// Replace the string with the Webhook URL above.
	$url = "URL_OF_WEBHOOK";

	// For a complete list of options:
	// https://discord.com/developers/docs/resources/webhook#execute-webhook
	$options = array(
		"content" => "It works!"
	);

	// Attaching a file.
	$fileinfo = array(
		"name" => "file",
		"filename" => "mycat.jpg",
		"type" => "image/jpeg",
		"data" => file_get_contents("/path/to/mycat.jpg")
	);

	// OR attach multiple files.
//	$fileinfo = array(
//		array(
//			"name" => "file",
//			"filename" => "mycat.jpg",
//			"type" => "image/jpeg",
//			"data" => file_get_contents("/path/to/mycat.jpg")
//		),
//		array(
//			"name" => "file2",
//			"filename" => "othercat.jpg",
//			"type" => "image/jpeg",
//			"data" => file_get_contents("/path/to/othercat.jpg")
//		),
//	);

	$result = DiscordSDK::SendWebhookMessage($url, $options, $fileinfo);
	if (!$result["success"])
	{
		var_dump($result);

		exit();
	}
?>
```

More Information
----------------

* [SDK documentation](https://github.com/cubiclesoft/php-discord-sdk/blob/master/docs/sdk_discord.md) - The DiscordSDK class documentation.
