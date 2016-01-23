<?php

/**
 * Basic skeleton client that just handles starting and connecting to the Slack WebSocket server
 * as a Bot User, and no higher level functionality than that.
 *
 * Namely, it doesn't try to interpret event types (for message events received).
 * If you need to detect, for example, or message events, you should do so in app code such as the start.php example.
 */

class SlackBotUser {
	/**
	 * API endpoint for rtm.start
	 */
	const API_RTM_START_URI = 'https://slack.com/api/rtm.start';

	/**
	 * Settings for log level
	 */
	const LOG_LEVEL_NORMAL = 1;
	const LOG_LEVEL_VERBOSE = 2;

	/**
	 * See doc: https://api.slack.com/rtm
	 */
	const CHARACTER_LIMIT = 4000;

	/**
	 * Bot User token
	 */
	private $token;

	/**
	 * Store the web socket URI string
	 */
	private $webSocketUri;

	/**
	 * The WebSocket Client object
	 */
	private $webSocketClient;

	/**
	 * We need to keep a unique ID per connection
	 */
	private $sendId;

	/**
	 * List of users in this team once connected
	 * Format is an assoc array of:
	 * {userId : {'channel' : directImChannelId, 'name' : userName}}
	 */
	public $users;

	/**
	 * Contains team info like 'id', 'name', 'domain'.
	 */
	public $team;

	/**
	 * For inter-process communication
	 */
	private $readerSocket;
	private $writerSocket;

	/**
	 * Log level setting
	 */
	private $logLevel;

	/**
	 * Constructor
	 * @param	string	Bot access token
	 */
	public function __construct($token = '') {
		$this->webSocketUri = '';
		$this->sendId = 0;
		$this->logLevel = self::LOG_LEVEL_NORMAL;

		if (empty($token)) {
			// no token given, check command line arguments
			global $argv;
			if (empty($argv[1])) {
				$this->log(self::LOG_LEVEL_NORMAL, 'Error: No Bot User token given! You must provide a token as the sole command line argument of this script.');
				$this->token = '';
			} else {
				$this->log(self::LOG_LEVEL_NORMAL, 'Using Bot User token "'.$argv[1].'".');
				$this->token = $argv[1];
			}
		} else {
			$this->token = $token;
		}
	}

	/**
	 * Set the log level.
	 * @param	int		The log level
	 */
	public function setLogLevel($level) {
		if ($level == self::LOG_LEVEL_NORMAL || $level == self::LOG_LEVEL_VERBOSE) {
			$this->logLevel = $level;
		}
	}

	/**
	 * First make request to rtm.start endpoint to retrieve the WebSocket URI.
	 * Then establish socket connection to WebSocket URI.
	 */
	public function connect() {
		$this->createParentChildComm();

		$this->rtmStart();
		$this->log(self::LOG_LEVEL_NORMAL, 'Opening WebSocket connection...');
		$this->webSocketClient = new \WebSocket\Client( $this->webSocketUri );

		$response = $this->receive();
		if (!empty($response) && !empty($response['type']) && $response['type'] == 'hello') {
			$this->log(self::LOG_LEVEL_NORMAL, 'Hello received from Slack.');
		} else {
			throw new Exception('First event from Slack WebSocket URI is not a Hello!');
		}

		$this->sendId = 1;

		// once connected, we need to fork a child which is repsonsible for pinging every 10 sec and
		// sending any messages through the WebSockets connection.
		// (because parent and child proc can't both send stuff to it)
		// (because parent needs to be on a blocking receive, PHP 5.3 limitation/bug, so we need
		// a child process for pinging and sending messages)
		$pid = pcntl_fork();
		if ($pid == -1) {
			die('Fork failed');
		} elseif ($pid) {
			socket_close($this->readerSocket);
			$this->log(self::LOG_LEVEL_NORMAL, 'Testing interprocess communication (IPC)...');
			$line = sprintf("Message sent from parent pid %d successful.\n", getmypid());
			if (!socket_write($this->writerSocket, $line, strlen($line))) {
				throw new Exception(socket_strerror(socket_last_error()));
			}
		} else {
			socket_close($this->writerSocket);
			$line = socket_read($this->readerSocket, 1024, PHP_NORMAL_READ);
			$this->log(self::LOG_LEVEL_NORMAL, sprintf("Message received from child pid %d. %s. IPC ready.", getmypid(), rtrim($line)));
			$this->pingLoop();
		}
	}

	/**
	 * Attempts to receive a message from WebSocket. Blocking call, but with timeouts.
	 * Put it in a while(true) loop to keep monitoring for messages.
	 */
	public function receive() {
		if (empty($this->webSocketClient)) {
			throw new Exception('No WebSocket connection established, cannot receive message!');
		}

		// This loop isn't here to do multiple receives or keep receiving,
		// but purely to handle timeouts. When a timeout occurs with no message,
		// we get back an empty string. In such a case, we need to receive again
		// until we get a message back. (or script terminated by Ctrl-C)
		do {
			$msgString = $this->webSocketClient->receive();
		} while (empty($msgString));

		$msgAssocArray = json_decode($msgString, true);
		if (!empty($msgAssocArray) && !empty($msgAssocArray['type']) && $msgAssocArray['type'] == 'pong') {
			$this->log(self::LOG_LEVEL_VERBOSE, 'Received event: '.$msgString);
		} else {
			$this->log(self::LOG_LEVEL_NORMAL, 'Received event: '.$msgString);
		}
		return $msgAssocArray;
	}

	/**
	 * Send a message to a channel via the WebSocket connection.
	 * @param	string	The channel id
	 * @param	string	The text to send
	 */
	public function send($channel, $text) {
		$text = substr($text, 0, self::CHARACTER_LIMIT);
		$message = array(
			// add id later in child process, which keeps track of sendId.
			'type' => 'message',
			'channel' => $channel,
			'text' => $text
		);
		$payload = json_encode($message)."\n";
		// throw it over to the child process, which is responsible for sending messages to websocket
		$ret = socket_write($this->writerSocket, $payload, strlen($payload));
		if (!$ret) {
			$this->log(self::LOG_LEVEL_NORMAL, 'Error: '.socket_strerror(socket_last_error()));
		}
	}

	/**
	 * Parent child communcation sockets creation
	 */
	private function createParentChildComm() {
		$this->log(self::LOG_LEVEL_NORMAL, 'Preparing inter-process communication sockets...');
		$sockets = array();
		if (!socket_create_pair(AF_UNIX, SOCK_STREAM, 0, $sockets)) {
			throw new Exception(socket_strerror(socket_last_error()));
		}
		list($this->readerSocket, $this->writerSocket) = $sockets;

		socket_set_nonblock($this->readerSocket);
		socket_set_nonblock($this->writerSocket);
	}

	/**
	 * Make rtm.start API call (HTTPS POST call) to get WebSocket URI.
	 */
	private function rtmStart() {
		$this->log(self::LOG_LEVEL_NORMAL, 'Making rtm.start call...');
		$params = http_build_query(array(
			'token' => $this->token,
			'simple_latest' => 1,
			'no_unreads' => 1
		));
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, self::API_RTM_START_URI);
		curl_setopt($ch, CURLOPT_POST, true); 
		curl_setopt($ch, CURLOPT_POSTFIELDS, $params); 
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
		// curl_setopt($ch, CURLOPT_CAINFO, self::getSslCertFile()); // not needed
		$output = curl_exec($ch);
		curl_close($ch);
		$result = json_decode($output, true);

		// print_r($result); // debug
		if (empty($result) || empty($result['url'])) {
			throw new Exception('Failed to get WebSocket URI: '.$result['error']);
		}

		$this->webSocketUri = $result['url'];
		$this->users = $this->buildUsersInfo($result['ims'], $result['users']);
		$this->team = $result['team'];
		$this->log(self::LOG_LEVEL_NORMAL, 'WebSocket URI received.');
	}

	/**
	 * Ping loop for child
	 */
	private function pingLoop() {
		while (true) {
			$count = 0;
			while ($count < 10) {
				sleep(1);
				// check to see if any to-send message came through
				do {
					$message = $this->readIPCSocket();
					$message = rtrim($message);
					if (!empty($message)) {
						// decode message, add id, then re-encode it.
						$obj = json_decode($message, true);
						$obj['id'] = $this->sendId++;
						$message = json_encode($obj);
						$this->log(self::LOG_LEVEL_NORMAL, 'Sending event: '.$message);
						$this->webSocketClient->send( $message );
					}
				} while (!empty($message));
				// continue
				$count++;
			}
			// 10 seconds up, send ping
			$this->webSocketClient->send( json_encode(array('id' => $this->sendId++, 'type' => 'ping')) );
		}
		exit; // shouldn't get here but if it does, exit. This also kills the parent process's websocket connection if it happens.
	}

	/**
	 * Helper method to kinda sorta imitate socket_read(), which is blocking, and make a non-blocking version.
	 */
	private function readIPCSocket() {
		// cannot use socket_read (for example like this)
		// $message = socket_read($this->readerSocket, self::CHARACTER_LIMIT + 1000, PHP_NORMAL_READ);
		// because it's also a blocking call (PHP limitation)
		// need to use socket_rev(), char by char.
		$buffer = '';
		$char = '';
		do {
			// need the @ to silence a warning when no data is available on the socket and null is returned.
			// (which is exactly the non-blocking behavior we rely on)
			@socket_recv($this->readerSocket, $char, 1, MSG_DONTWAIT);
			if ($char !== null) {
				$buffer .= $char;
			}
		} while ($char !== null && $char !== "\n"); // if line break encountered, it's a separate message coming up.
		return $buffer;
	}

	/**
	 * Build users info like this:
	 * {'U123456': {'channel': 'D123456', 'name': 'John'}}
	 * @param	array	The "ims" data from rtm.start
	 * @param	array	The "users" data from rtm.start
	 */
	private function buildUsersInfo($ims, $users) {
		$result = array();
		if (!empty($ims)) {
			foreach ($ims as $im) {
				if (!empty($im['user']) && !empty($im['id'])) {
					$result[$im['user']] = array('channel' => $im['id']);
				}
			}
		}
		if (!empty($users)) {
			foreach ($users as $user) {
				if (!empty($user['id']) && !empty($user['name'])) {
					if (empty($result[$user['id']])) {
						$result[$user['id']] = array();
					}
					$result[$user['id']]['name'] = $user['name'];
				}
			}
		}
		return $result;
	}

	/**
	 * Retrieves the bot token being used in this session
	 */
	public function getToken() {
		return $this->token;
	}

	/**
	 * Prints a log message only if the current log level setting is higher than message being logged.
	 * @param	int		The log level to use
	 * @param	string	The text to log
	 */
	private function log($level, $message) {
		if ($level == self::LOG_LEVEL_NORMAL || ($level == self::LOG_LEVEL_VERBOSE && $this->logLevel == self::LOG_LEVEL_VERBOSE)) {
			error_log(date('[Y-m-d H:i:s] ').$message);
		}
	}
}
