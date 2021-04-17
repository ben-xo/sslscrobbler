<?php
	// Discord SDK.
	// (C) 2020 CubicleSoft.  All Rights Reserved.

	class DiscordSDK
	{
		protected $authtype, $authtoken, $apibase;

		public function __construct()
		{
			$this->authtype = false;
			$this->authtoken = false;
			$this->apibase = false;
		}

		public function SetAccessInfo($authtype, $authtoken, $apibase = "https://discord.com/api")
		{
			$this->authtype = $authtype;
			$this->authtoken = $authtoken;
			$this->apibase = $apibase;
		}

		public static function SendWebhookMessage($url, $params, $fileinfo = false)
		{
			if (!class_exists("WebBrowser", false))  require_once str_replace("\\", "/", dirname(__FILE__)) . "/web_browser.php";

			$options = array(
				"method" => "POST",
				"headers" => array(
					"Content-Type" => "multipart/form-data"
				),
				"postvars" => array(
					"payload_json" => json_encode($params, JSON_UNESCAPED_SLASHES)
				)
			);

			if ($fileinfo !== false)  $options["files"] = (isset($fileinfo["name"]) ? array($fileinfo) : $fileinfo);

			$web = new WebBrowser();

			$result = $web->Process($url, $options);

			if (!$result["success"])  return $result;

			if ($result["response"]["code"] != 200 && $result["response"]["code"] != 204)  return array("success" => false, "error" => self::Discord_Translate("Expected a 200 or 204 response from the Discord API.  Received '%s'.", $result["response"]["line"]), "errorcode" => "unexpected_discord_api_response", "info" => $result);

			return $result;
		}

		public function RunAPI($method, $apipath, $postvars = array(), $options = array(), $expected = 200, $decodebody = true)
		{
			if ($this->authtype === false || $this->authtoken === false)  return array("success" => false, "error" => self::Discord_Translate("Authentication token or type not set."), "errorcode" => "missing_auth_type_or_token");
			if ($this->apibase === false)  return array("success" => false, "error" => self::Discord_Translate("API base not set."), "errorcode" => "missing_apibase");

			if (!class_exists("WebBrowser", false))  require_once str_replace("\\", "/", dirname(__FILE__)) . "/web_browser.php";

			$url = $this->apibase . "/" . ltrim($apipath, "/");

			$options2 = array(
				"method" => $method,
				"headers" => array(
					"Authorization" => $this->authtype . " " . $this->authtoken,
					"User-Agent" => "DiscordSDK (https://github.com/cubiclesoft/php-discord-sdk, 1.0)"
				)
			);

			if ($method === "POST" || $method === "PUT")
			{
				$options2["headers"]["Content-Type"] = "application/json";
				$options2["body"] = json_encode($postvars, JSON_UNESCAPED_SLASHES);

				foreach ($options as $key => $val)
				{
					if (isset($options2[$key]) && is_array($options2[$key]))  $options2[$key] = array_merge($options2[$key], $val);
					else  $options2[$key] = $val;
				}
			}
			else
			{
				$options2 = array_merge($options2, $options);
			}

			$web = new WebBrowser();

			$result = $web->Process($url, $options2);

			if (!$result["success"])  return $result;

			if ($result["response"]["code"] != $expected)  return array("success" => false, "error" => self::Discord_Translate("Expected a %d response from the Discord API.  Received '%s'.", $expected, $result["response"]["line"]), "errorcode" => "unexpected_discord_api_response", "info" => $result);

			if ($decodebody)
			{
				$data = json_decode($result["body"], true);
				if (!is_array($data))  return array("success" => false, "error" => self::Discord_Translate("Unable to decode the server response as JSON."), "errorcode" => "expected_json", "info" => $result);

				$result = array(
					"success" => true,
					"data" => $data
				);
			}

			return $result;
		}

		protected static function Discord_Translate()
		{
			$args = func_get_args();
			if (!count($args))  return "";

			return call_user_func_array((defined("CS_TRANSLATE_FUNC") && function_exists(CS_TRANSLATE_FUNC) ? CS_TRANSLATE_FUNC : "sprintf"), $args);
		}
	}
?>