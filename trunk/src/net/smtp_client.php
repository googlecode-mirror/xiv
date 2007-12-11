<?php

/**
 * FTP client
 * A simple FTP client
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

require_once "../socket.php";

class smtp_client extends socket {
	
	/**
	 * From e-mail
	 * @var string
	 */
	var $message_from_email = 'anonymous@example.com';

	/*
	 * From name
	 * @var string
	 */
	var $message_from_name = 'Jane Hacker';

	/**
	 * Host to connect
	 * @var string
	 */
	var $smtp_host;

	/**
	 * Port to connect
	 * @var string
	 */
	var $smtp_port = 25;

	/**
	 * SMTP username
	 * @var string
	 */
	var $smtp_user;

	/**
	 * SMTP password
	 * @var string
	 */
	var $smtp_pass;

	/**
	 * Client headers
	 * @var array
	 */
	var $request_headers;

	/**
	 * Constructor
	 */
	function __construct($host, $port = 25, $user = null, $pass = null) {
		parent::__construct();

		$this->smtp_host = $host;
		$this->smtp_port = $port;
		$this->smtp_user = $user;
		$this->smtp_pass = $pass;

		$this->set_header('mime-version', '1.0');
		$this->set_header('date', date('r'));
		$this->set_header('x-mailer', 'Gekko');
	}

	/**
	 * Opens a connection
	 * @returns boolean Connection status
	 */
	function open() {
		$this->connect($this->smtp_host, $this->smtp_port);
		return $this->status();
	}
	
	/**
	 * Login into the STMP server
	 * @returns boolean True on successful login
	 */
	function login() {

		// Please read rfc0821 (or if you're too lazy just sniff a conversation between your e-mail client
		// and one random smtp server)
		$this->chat("220", "EHLO {$this->smtp_host}\r\n");
		$ehlo = $this->read();

		// getting server supported auth methods (read rfc2554)
		// http://www.technoids.org/saslmech.html
		if ($this->smtp_user && preg_match_all("/\d{3}-AUTH\s(.*)/", $ehlo, $match) && isset($match[1][0])) {
			$methods = explode(" ", $match[1][0]);

			if (in_array("LOGIN", $methods)) {
				$this->write("AUTH LOGIN\r\n");
				$this->chat("334", base64_encode($this->smtp_user)."\r\n");
				$this->chat("334", base64_encode($this->smtp_pass)."\r\n");
			} else {
				$this->debug(__("Unsupported AUTH scheme."));
				return false;
			}

			if (!$this->chat("235", "", true)) {
				$this->debug(__("Wrong username or password."));
				return false;
			}
		} else {
			$this->chat("220", "HELO {$this->smtp_host}\r\n");
			$this->chat("250");
		}
		return true;
	}

	/**
	 * Sets a client header.
	 * @param string $name Name of the SMTP header.
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

		foreach ($this->request_headers as $name => $value) {
			$name = preg_replace_callback('/(^|-)(.)/', create_function('$a', 'return $a[1].strtoupper($a[2]);'), $name);
			$this->write("{$name}: $value\r\n");
		}

		$this->write("\r\n");
	}

	/**
	 * Sends an e-mail
	 * @param string $to Recipient
	 * @param string $subject Message's subject
	 * @param string $message Message's body
	 */
	function send($to, $subject, $message) {

		$this->write("MAIL FROM: <{$this->message_from_email}>\r\n");

		// can handle multiple recipients sepparated by commas
		$all = explode(",", $to);
		foreach ($all as $addr)
			$this->chat("250", "RCPT TO: <".trim($addr).">\r\n");

		// telling server that the following data is a message
		$this->chat("250", "DATA\r\n");
		$this->chat("354");

		$this->set_header('subject', $subject);
		$this->set_header('from', "{$this->message_from_name} <{$this->message_from_email}>");
		$this->set_header('to', "<{$to}>");
		
		$this->send_headers();

		$this->write($message);
		$this->write("\r\n.\r\n");
		
		$this->chat("250");
	}

	/**
	 * Expects responses to reply specific answers
	 * @param string $expects SMTP command expected
	 * @param string $answers Command to reply
	 */
	function chat($expecting, $answer = null) {
		
		$this->queue[] = array($expecting, $answer);

		list($expecting, $answer) = array_shift($this->queue);

		$this->debug("\t_eXPECTS: '$expecting', ANSWER:'$answer'");
		$data = $this->read();
		if (substr(trim($data), 0, 3) == $expecting) {
			if ($answer)
				$this->write($answer);
			return true;
		} else {
			$this->debug(__("Got '%s' while expecting '%s'.", $data, $expecting));
			return false;
		}
	}
	
	/**
	 * Closes the connection
	 */
	function close() {
		$this->write("QUIT\r\n");
		$this->disconnect();
	}
	
	/**
	 * Runs functional tests
	 */
	function run_test() {
		$smtp = new smtp_client("smtp.mail.example.com", 25, "username", "password"); 
		$smtp->debug_level = 1;
		$smtp->message_from_email = 'anonymous@example.com';
		$smtp->open();
		if ($smtp->login())
			$smtp->send('you@example.com', 'my subject', 'hello there! this is just a test message.');
		$smtp->close();
	}
}
//smtp_client::run_test();
?>
