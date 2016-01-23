SlackBuu
========

What is SlackBuu?
-----------------

SlackBuu is Slack **B**ot **U**ser **U**nderlayer.

It is a PHP library for building Slack chat bot apps on top of.

It does not try to make use of every type of custom integration in Slack. It is specifically made to build
Slack chat bot services, one that connects to Slack's WebSockets API and send & receive messages.

Additionally, this also includes the OAuth logic for users to add your app onto their Slack team. (Optional)

The motivation to develop this comes from the fact that while many good libraries for Slack integration
are available in other langauges, the selection for PHP is more limited. There are a few out there that have
very heavy dependencies, like React PHP, or PHP 5.4+ / 5.5+. (At this moment, PHP 5.3 is still the default
version that comes with many AWS and Digital Ocean builds, which makes having a PHP 5.4+ requirement a
laborous effort to deploy something quickly to play around with.)

Requirements
------------

*	PHP 5.3 or above
*	PHP [cURL library](http://php.net/manual/en/book.curl.php)
*	Apache web server (Optional, only if you want the OAuth integration)
*	No other dependencies, so composer not required; since everything needed is included in the repo.

Credits
-------

Because I'm not using composer dependencies, I included in this repo two PHP libraries I made use of:

*	[Websocket-PHP](https://github.com/Textalk/websocket-php) to allow a PHP script to act as a WebSocket client (not server)
*	[ToroPHP](https://github.com/anandkunal/ToroPHP) to make clean URLs easily in a very light-weight way

Slack APIs used
---------------

This library makes use of the following [Slack APIs](https://api.slack.com/):

*	[Bot Users](https://api.slack.com/bot-users)
*	[Real Time Messaging (RTM) API](https://api.slack.com/rtm) (WebSocket API)
*	[rtm.start](https://api.slack.com/methods/rtm.start)
*	[Slack OAuth](https://api.slack.com/docs/oauth)

Getting started
---------------

You can use only the SlackBotUser module on its own, as a PHP chat bot server just for your Slack team
as custom integration; or you can use the OAuth layer in addition, which will be a full base code for
building your own distributable Slack app.

To start a bot user on your Slack team only
-------------------------------------------

1.	Visit https://[yourteam].slack.com/apps/manage/A0F7YS25R-bots
1.	Click "Add integration", choose a name, and copy the API token of the bot.
1.	Cd to the root of this repo, and use the API token in the following command.
1.	Run `php start-dev.php xoxb-123-abc` and your chat bot will start running!

To build a distributable Slack chat bot app
-------------------------------------------

1.	Run `chmod 777 logs` to make the logs directory writable and executable by Apache.
1.	Set up a vhost on your domain and dev domain.
1.	Look at [www/index.php](www/index.php) to see how prod and dev environment is determined. Set up your vhost accordingly.
1.	[Create a new Slack app from this page](https://api.slack.com/applications).
1.	Enter something for App Name, Team, Description, Icon, etc.
1.	In the "Redirect URI(s)" field, enter your domain. For example: `http://yourdomain.com/`
1.	Copy the Client ID and Client Secret from the result page, paste into [Config.php](Config.php) accordingly, and update the host name in there.
1.	Scroll down on this page, to "Bot User", click "Add a bot to this app".
1.	Pick a name and click the "Add bot user" button.
1.	Visit `http://yourdomain.com` and install your own app and try it out!

License
-------

SlackBuu was created by [Bigi Lui](https://www.linkedin.com/in/bigilui) and released under the MIT License.
