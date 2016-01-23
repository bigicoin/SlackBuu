<?php

define('ENV', 'prod');

require_once(dirname(__FILE__).'/WebSocket/require.php');
require_once(dirname(__FILE__).'/Config.php');
require_once(dirname(__FILE__).'/SlackBotUser.php');

$bot = new SlackBotUser();
$bot->setLogLevel(SlackBotUser::LOG_LEVEL_NORMAL);
$bot->connect();

// During $bot->connect() is when the child process gets forked out,
// where it continues in a ping loop and acting as a simple message sender.
// Therefore, app logic should be included past this point,
// so we can keep memory usage on the child process small.

while (true) {
	$response = $bot->receive();
	if (!empty($response['type'])) {
		if ($response['type'] == 'message' && empty($response['reply_to']) && !empty($response['user'])) {
			$bot->send($response['channel'], 'Ok, message received!');
		}
	}
}
