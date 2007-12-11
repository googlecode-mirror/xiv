<?php

/**
 * HTTP client
 * A simple HTTP client
 * ---
 * Written by Jose Carlos Nieto <xiam@astrata.com.mx>
 * Copyright (c) 2007 Jose Carlos Nieto <xiam@astrata.com.mx>
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @author          Jose Carlos Nieto <xiam@astrata.com.mx>
 * @copyright       Copyright (c) 2007, Jose Carlos Nieto <xiam@astrata.com.mx>
 * @link            http://opensource.astrata.com.mx Astrata Open Source Projects
 * @version         $Revision$
 * @modifiedby      $Last_changed_by: $
 * @lastmodified    $Date$
 * @license         http://www.opensource.org/licenses/mit-license.php The MIT License
 *
 */

class http_client extends socket {

	/**
	 * Host to connect
	 * @var string
	 */
	var $http_host;

	/**
	 * Port to connect
	 * @var integer
	 */
	var $http_port = 80;

	/**
	 * User to log in
	 * @var string
	 */
	var $http_user;

	/**
	 * Password to log in
	 * @var string
	 */
	var $http_pass;

	/**
	 * User agent
	 * @var string
	 */
	var $user_agent = null;
	
	/**
	 * Client headers
	 * @var array
	 */
	var $request_headers;

	/**
	 * Response headers
	 * @var array
	 */
	var $response_headers;

	/**
	 * Response document
	 * @var array
	 */
	var $response_document;


	/**
	 * Constructor.
	 */
	function __construct($host, $port = 80, $user = null, $pass = null) {
		parent::__construct();
		$this->http_host = $host;
		$this->http_port = $port;
		$this->http_user = $user;
		$this->http_pass = $pass;

		$this->set_header('host', "{$this->http_host}:{$this->http_port}");
		if ($this->http_user)
			$this->set_header('authorization', "basic ".base64_encode("{$this->http_user}:{$this->http_pass}"));
	}

	/**
	 * Opens a connection to the web server
	 * @returns boolean connection status
	 */
	function open() {
		$this->connect($this->http_host, $this->http_port);
		return $this->status();
	}

	/**
	 * Evaluates the served file
	 * @parse string &$buff reference to the contents of the downloaded file
	 */
	function parse_response(&$buff) {

		preg_match("/^(.*?)\r\n\r\n(.*?)$/s", $buff, $match);

		if (isset($match[2])) {
			$this->response_document = $match[2];

			$headers = explode("\n", $match[1]);

			$status = false;
			foreach ($headers as $h) {
				if (!$status) {
					$status = $h;
					// expecting HTTP/1.1 200 OK
					if (!strpos($status, "200")) {
						return false;
					}
				} else {
					preg_match("/^([^:]*?):\s*(.*?)$/i", $h, $m);
					if (isset($m[2]))
						$this->response_headers[strtolower($m[1])] = trim($m[2]);
				}
			}

			// inflating gzip'ed pages
			if ((isset($this->response_headers['content-encoding']) && ($this->response_headers['content-encoding'] == "gzip")) || (isset($this->response_headers['vary']) && strtolower($this->response_headers['vary']) == 'accept-encoding')) {
				// Read http://www.php.net/manual/en/function.gzinflate.php
				$this->response_document = gzinflate(substr($this->response_document, 10));
			}
		}
	}

	/**
	 * Sets a client header.
	 * @param string $name Name of the HTTP header.
	 * @param string $value Value of the header.
	 */
	function set_header($name, $value) {
		$this->request_headers[strtolower($name)] = $value;
	}

	/*
	 * Sends client headers.
	 * 
	 */
	function send_headers() {

		$this->set_header('user-agent', $this->user_agent ? $this->user_agent : 'Mozilla/5.0 Gekko/'.$this->version().'');

		$this->set_header('accept', '*/*');

		if (function_exists('gzinflate'))
			$this->set_header('accept-encoding', 'gzip,deflate');
		
		$this->set_header('keep-alive', '300');
		$this->set_header('connection', 'keep-alive');

		foreach ($this->request_headers as $name => $value) {
			$name = preg_replace_callback('/(^|-)(.)/', create_function('$a', 'return $a[1].strtoupper($a[2]);'), $name);
			$this->write("{$name}: $value\r\n");
		}

		$this->write("\r\n");
	}

	/**
	 * Prepares variables for being sent.
	 * @param array $vars Variables to send
	 * @returns string Variables formatted to be sent
	 */
	function join_variables($vars) {
		$buff = array();
		foreach ($vars as $f => $v)
			$buff[] = "$f=".urlencode($v);
		return implode("&", $buff);
	}

	/**
	 * Performs a HTTP Post
	 * @param string $url URL where to post
	 * @param array $vars Variables to send
	 * @param boolean $only_headers Set to true if you want only the document headers to be returned.
	 * @returns string Document contents or headers
	 */
	function post($url, $vars, $only_headers = false) {
		$data = $this->join_variables($vars);

		$this->write("POST $url HTTP/1.1\r\n");
		$this->set_header('content-length', strlen($data));
		$this->set_header('content-type', 'application/x-www-form-urlencoded');
		$this->send_headers();
		$this->write($data);
		
		if ($only_headers)
			return $this->get_headers();

		$this->read_response();

		return $this->response_document;
	}

	/**
	 * Reads only HTTP headers
	 * @returns string HTTP headers
	 */
	private function get_headers() {
		$buff = "";
		stream_set_timeout($this->_socket, $this->read_timeout);
		do {
			$line = $this->read_line($this->_socket);
			$buff .= $line;
			if ($line == "\r\n")
				return $buff;
		} while (!feof($this->_socket));
	}

	private function read_response() {
		if ($this->download()) {
			if ((isset($this->response_headers['content-encoding']) && ($this->response_headers['content-encoding'] == "gzip")) || (isset($this->response_headers['vary']) && strtolower($this->response_headers['vary']) == 'accept-encoding')) {
				// Read http://www.php.net/manual/en/function.gzinflate.php
				$this->response_document = gzinflate(substr($this->response_document, 10));
			}
		}
	}

	private function download() {
		unset($headers);
		unset($document);
		$document = null;
		$data = '';
		$buff = '';
		$size = 0;
		$tmp = '';

		while ($this->status()) {
			$buff = $tmp.$this->read();
			$len = strlen($buff);
			$tmp = '';
			for ($i = 0; $i < $len; $i++) {
				$chr = $buff{$i};
				$tmp .= $chr;
				if (isset($document)) {
					if (isset($headers['transfer-encoding'])) {
						switch($headers['transfer-encoding'] == 'chunked') {
							case 'chunked':
								if ($size) {
									$tmp = '';
									for (; $size > 0 && $i < $len; $i++, $size--) {
										$tmp .= $buff{$i};
									}
									$document .= $tmp;
									$tmp = '';
								} else {
									if ($chr == "\n" && strlen(trim($tmp))) {
										$size = hexdec(trim($tmp));
										if ($size == 0) {
											$this->response_document = $document;
											return true;
										}
										$tmp = '';
									}
								}
							break;
							default:
								debug('Unsupported transfer encoding '.$headers['transfer-encoding']);
								return false;
							break;
						}
					} else if (isset($headers['content-length'])) {
						if (!$size) {
							$size = $headers['content-length'];
						}
						$tmp = '';
						for (; $size > 0 && $i < $len; $i++, $size--) {
							$tmp .= $buff{$i};
						}
						$document .= $tmp;
						$tmp = '';
						if ($size == 0) {
							$this->response_document = $document;
							return true;
						}
					} else {
						$document = $buff;
						while ($this->status()) {
							$document .= $this->read();
						}
						$this->response_document = $document;
						return true;
					}
				} else {
					if ($chr == "\n") {
						if (!isset($headers)) {
							// first response.
							if (strpos($tmp, '200 OK')) {
								$headers = array();
							} else {
								return false;
							}
						} else {
							if (trim($tmp)) {
								// storing headers
								preg_match('/^([^:]+):\s?(.+)$/', $tmp, $match);
								$headers[strtolower($match[1])] = trim($match[2]);
							} else {
								$document = '';
								$this->response_headers = $headers;
							}
						}
						$tmp = '';
					}
				}
			}
		}
	}

	/**
	 * Standard HTTP get
	 * @param string $url URL to get
	 * @param boolean $only_headers Set true if you want only the document headers
	 * @returns string Document content or headers
	 */
	function get($url, $only_headers = false) {

		$this->write("GET $url HTTP/1.1\r\n");
		$this->send_headers();

		if ($only_headers) {
			return $this->get_headers();
		}

		$this->read_response();

		return $this->response_document;
	}

	/**
	 * Runs a functional test
	 */
	function run_test() {
		header('content-type: text/plain');
		$http = new http_client('localhost', '80');
		if ($http->open()) {
			echo $http->get('/test/foo.txt');
			$http->close();
		}
	}
}

// http_client::run_test();
?>
