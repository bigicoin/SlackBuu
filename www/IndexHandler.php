<?php

class IndexHandler {
	/**
	 * Homepage handler (for accessing the root domain).
	 */
	public function get() {
		?>
		<!DOCTYPE html>
		<html lang="en">
		<head>
		<meta charset="utf-8"/>
		<title>My Slack Chat Bot</title>
		</head>
		<body>
			<div>
				<a href="https://slack.com/oauth/authorize?scope=bot&client_id=<?php echo SLACK_APP_CLIENT_ID; ?>&redirect_uri=<?php echo HOST_PROTOCOL; ?>%3A%2F%2F<?php echo HOST_NAME; ?>%2Foauth"><img alt="Add to Slack" height="40" width="139" src="https://platform.slack-edge.com/img/add_to_slack.png" srcset="https://platform.slack-edge.com/img/add_to_slack.png 1x, https://platform.slack-edge.com/img/add_to_slack@2x.png 2x"></a>
			</div>
		</body>
		</html>
		<?php
	}

	public function post() {
		ToroHook::fire('405', "GET"); // method not allowed
	}
}
