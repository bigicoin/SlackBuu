<?php

/**
 * Note: Every file in this directory are from the public repo: https://github.com/Textalk/websocket-php
 * The only reason we're not using composer to load it, but included all the hard files here, is because
 * their composer setup is currently broken (as of 12/30/2015).
 *
 * "textalk/websocket": "1.0.*" currently includes a broken version of their library, but the most updated
 * version in their github repo actually works fine. Therefore we include the files here.
 *
 * Namespaces are kept, we're just not using composer autoloader for this, so we have this require file,
 * which we will simply do a require/include on, in order to use this WebSocket namespaced client.
 */

require_once(dirname(__FILE__) . '/Exception.php');
require_once(dirname(__FILE__) . '/BadOpcodeException.php');
require_once(dirname(__FILE__) . '/BadUriException.php');
require_once(dirname(__FILE__) . '/ConnectionException.php');

require_once(dirname(__FILE__) . '/Base.php');
require_once(dirname(__FILE__) . '/Client.php');

// require_once(dirname(__FILE__) . '/Server.php');
