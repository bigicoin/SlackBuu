<?php

/**
 * If by the time this file is included, ENV is not defined, we assume dev.
 * May not change environment after this.
 */

if (!defined('ENV')) {
	define('ENV', 'dev');
}

/**
 * Global consts, same across environments
 */

// None so far

/**
 * Environment-dependent consts
 */

if (ENV == 'prod') {

	define('SLACK_APP_CLIENT_ID', '123.456');
	define('SLACK_APP_CLIENT_SECRET', 'abcdef123456');

	define('HOST_PROTOCOL', 'http');
	define('HOST_NAME', 'yourdomain.com');

} else {

	define('SLACK_APP_CLIENT_ID', '19040857907.19242118417');
	define('SLACK_APP_CLIENT_SECRET', '0508ea1106a9a3bc49c35192c1c9c1ee');

	define('HOST_PROTOCOL', 'http');
	define('HOST_NAME', 'slacktest.agum.com');

}
