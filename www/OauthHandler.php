<?php

define('APP_ROOT', dirname(__FILE__) . '/..');

class OauthHandler {
	/**
	 * API endpoint for oauth.access
	 */
	const API_OAUTH_ACCESS = 'https://slack.com/api/oauth.access';

	/**
	 * Sample response from oauth.access request:
	 *	{
	 *	    "access_token": "xoxp-XXXXXXXX-XXXXXXXX-XXXXX",
	 *	    "scope": "incoming-webhook,commands,bot",
	 *	    "team_name": "Team Installing Your Hook",
	 *	    "team_id": "XXXXXXXXXX",
	 *	    "incoming_webhook": {
	 *	        "url": "https://hooks.slack.com/TXXXXX/BXXXXX/XXXXXXXXXX",
	 *	        "channel": "#channel-it-will-post-to",
	 *	        "configuration_url": "https://teamname.slack.com/services/BXXXXX"
	 *	    },
	 *	    "bot":{
	 *	        "bot_user_id":"UTTTTTTTTTTR",
	 *	        "bot_access_token":"xoxb-XXXXXXXXXXXX-TTTTTTTTTTTTTT"
	 *	    }
	 *	}
	 */

	/**
	 * Oauth get handler.
	 * In all instances of rendering output, we simply echo the message now.
	 * You should build a nice HTML page to display.
	 */
	public function get() {
		if (!empty($_GET['error'])) {
			// user denied request on slack prompt
			$error = $_GET['error'];
			echo $error;
		} else if (!empty($_GET['code'])) {
			// user accepted on slack prompt
			$result = $this->oauthAccess($_GET['code']); // see above for sample response
			if (empty($result)) {
				// no result
				$error = 'Error while making OAuth request to Slack!';
				echo $error;
			} else if (empty($result['access_token'])) {
				// no access token
				$error = 'No access token retrieved from Slack!';
				echo $error;
			} else if (empty($result['bot']) || empty($result['bot']['bot_access_token'])) {
				// no bot
				$error = 'No bot token retrieved from Slack!';
				echo $error;
			} else {
				// got bot!
				// save team info and access tokens into data store
				$this->saveTeam($result['team_id'], $result['team_name'], $result['access_token'], $result['bot']['bot_user_id'], $result['bot']['bot_access_token']);
				// run the bot
				$this->runBot($result['bot']['bot_access_token'], $result['team_id']);
				// display success page
				$team = $result['team_name'];
				echo "Slack App successfully installed on $team!";
			}
		} else {
			// unknown
			$error = 'No error message from Slack.';
			echo $error;
		}
	}

	/**
	 * Function code to save team info once an admin authorized your app.
	 * @param	string	Team id
	 * @param	string	Team name
	 * @param	string	App access token
	 * @param	string	Bot user id
	 * @param	string	Bot access token
	 */
	private function saveTeam($teamId, $teamName, $accessToken, $botUserId, $botAccessToken) {
		// You should save this info in a data store.
		error_log($teamId);
		error_log($teamName);
		error_log($accessToken);
		error_log($botUserId);
		error_log($botAccessToken);
	}

	/**
	 * Function code to start running the bot. Technically we only need the access token,
	 * but the team ID gives us something we can use to name the log file, for example.
	 * @param	string	Bot access token
	 * @param	string	Team id
	 */
	private function runBot($botAccessToken, $teamId) {
		// run the bot
		exec('php '.APP_ROOT.'/start-'.ENV.'.php '.$botAccessToken.' >> '.APP_ROOT.'/logs/'.$teamId.'.log 2>&1 &');
	}

	/**
	 * Makes HTTP call to Slack oauth.access endpoint
	 */
	private function oauthAccess($code) {
		$params = http_build_query(array(
			'client_id' => SLACK_APP_CLIENT_ID,
			'client_secret' => SLACK_APP_CLIENT_SECRET,
			'code' => $code,
			'redirect_uri' => HOST_PROTOCOL.'://'.HOST_NAME.'/oauth'
		));
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, self::API_OAUTH_ACCESS);
		curl_setopt($ch, CURLOPT_POST, true); 
		curl_setopt($ch, CURLOPT_POSTFIELDS, $params); 
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
		// curl_setopt($ch, CURLOPT_CAINFO, self::getSslCertFile()); // not needed
		$output = curl_exec($ch);
		curl_close($ch);
		$result = json_decode($output, true);
		return $result;
	}

	/**
	 * Oauth post handler.
	 */
	public function post() {
		ToroHook::fire('405', "GET"); // method not allowed
	}
}
