DiscordSDK Class:  'support/sdk_discord.php'
============================================

An ultra-lightweight PHP SDK for accessing the Discord API and Discord webhook endpoints.

Examples can be found here:

https://github.com/cubiclesoft/php-discord-sdk/

DiscordSDK::SendWebhookMessage($url, $params, $fileinfo = false)
----------------------------------------------------------------

Access:  public static

Parameters:

* $url - A string containing a valid Discord webhook URL.
* $params - An array of parameters to pass to the URL as JSON data.
* $fileinfo - A boolean of false or an array of file information to pass in for `multipart/form-data` (Default is false).

Returns:  A standard array of information.

This static function executes the webhook specified by the URL.  [See the Discord documentation](https://discord.com/developers/docs/resources/webhook#execute-webhook) for a complete list of parameters.

Uploading a file requires $fileinfo to be an array that is compatible with the Ultimate Web Scraper Toolkit.

DiscordSDK::SetAccessInfo($authtype, $authtoken, $apibase = "https://discord.com/api")
--------------------------------------------------------------------------------------

Access:  public

Parameters:

* $authtype - A string containing "Bot" or "Bearer".
* $token - A string containing a Discord Bot/Bearer Token.
* $apibase - A string containing the base URL of a Discord-compatible API.

Returns:  Nothing.

This function sets the baseline access information for later calls.

DiscordSDK::RunAPI($method, $apipath, $postvars = array(), $options = array(), $expected = 200, $decodebody = true)
-------------------------------------------------------------------------------------------------------------------

Access:  public

Parameters:

* $method - A string containing the API HTTP method (e.g. "GET", "POST", "DELETE").
* $apipath - A string containing the API to use (e.g. "guilds", "channels").
* $postvars - An array containing key-value pairs to use for a POST request (Default is array()).
* $options - An array containing additional options to pass to the underlying WebBrowser class (Default is array()).
* $expected - An integer containing the expected HTTP response code (Default is 200).
* $decodebody - A boolean indicating whether or not to decode the response body as JSON (Default is true).

Returns:  A standard array of information.

This function makes a single API call to the configured Discord-compatible API.

DiscordSDK::Discord_Translate($format, ...)
-------------------------------------------

Access:  _internal_ static

Parameters:

* $format - A string containing valid sprintf() format specifiers.

Returns:  A string containing a translation.

This internal static function takes input strings and translates them from English to some other language if CS_TRANSLATE_FUNC is defined to be a valid PHP function name.
