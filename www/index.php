<?php

/**
 * Here we use SERVER_ADMIN setup (from Apache vhost) to determine environment.
 * You can also use anything else, like hostname, etc.
 */
if ($_SERVER['SERVER_ADMIN'] == 'webmaster@yourdomain.com') {
	define('ENV', 'prod');
} else {
	define('ENV', 'dev');
}

require_once(dirname(__FILE__) . '/Toro.php');
require_once(dirname(__FILE__) . '/../Config.php');
require_once(dirname(__FILE__) . '/IndexHandler.php');
require_once(dirname(__FILE__) . '/OauthHandler.php');

/**
 * Handle IE8 and 9 jquery ajax requests:
 * IE8 and 9 does not natively allow cross domain ajax xmlHttpRequests.
 * They require the use of XDomainRequest and we use a library by MoonScript to handle it.
 * The catch is POST data from those requests come as Content-Type: text/plain,
 * instead of application/x-www-form-urlencoded. As such, PHP does not handle placing it into $_POST.
 */
if (empty($_POST) && !empty($HTTP_RAW_POST_DATA)) {
	parse_str($HTTP_RAW_POST_DATA, $_POST);
}

/**
 * The actual url routing map for our app
 */

Toro::serve(array(
	'/' => 'IndexHandler',
	'/oauth' => 'OauthHandler'
));
