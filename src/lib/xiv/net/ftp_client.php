<?php

/**
 * FTP client
 * A simple FTP client
 * ---
 * Written by Jose Carlos Nieto <xiam@menteslibres.org>
 * Copyright (c) 2007 Jose Carlos Nieto <xiam@menteslibres.org>
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @author          Jose Carlos Nieto <xiam@menteslibres.org>
 * @copyright       Copyright (c) 2007-2008, Jose Carlos Nieto <xiam@menteslibres.org>
 * @link            http://opensource.astrata.com.mx Astrata Open Source Projects
 * @version         $Revision$
 * @modifiedby      $LastChangedBy$
 * @lastmodified    $Date$
 * @license         http://www.opensource.org/licenses/mit-license.php The MIT License
 *
 */

require_once TM_LIB_DIR.'socket.php';

class ftp_client extends socket {

	/**
	 * FTP host to connect
	 * @var string
	 */
	var $ftp_host;

	/**
	 * FTP Port to connect
	 * @var int
	 */
	var $ftp_port = 21;


	/**
	 * Server username
	 * @var string
	 */
	var $ftp_user;

	/*
	 * Server password
	 * @var string
	 */
	var $ftp_pass;

	
	var $_queue = array();

	/**
	 * Constructor
	 */
	function __construct($host = null, $port = 21, $user = 'anonymous', $pass = 'anonymous@example.com') {
		parent::__construct();
		if ($host) {
			$this->ftp_host = $host;
			$this->ftp_port = $port;
			$this->ftp_user = $user;
			$this->ftp_pass = $pass;
		}
	}

	/**
	 * Opens the connection
	 * @returns boolean connection status
	 */
	function open() {
		$this->connect($this->ftp_host, $this->ftp_port);
		return $this->status();
	}

	/**
	 * Expects responses to reply specific answers
	 * @param string $expects FTP command expected
	 * @param string $answers Command to reply
	 */
	function chat($expecting, $answer = null) {
		
		$this->queue[] = array($expecting, $answer);

		list($expecting, $answer) = array_shift($this->queue);

		$data = $this->read();

		if (substr(trim($data), 0, 3) == $expecting) {
			if ($answer) {
				$this->write($answer);
			}
			return true;
		} else {
			debug('E: '.$expecting);
			debug('R: '.$data);
			return false;
		}
	}

	/**
	 * Server authentication
	 * @returns boolean true if the user authenticaded successfully
	 */
	function login() {
		$this->chat("220", "USER {$this->ftp_user}\r\n");
		$this->chat("331", "PASS {$this->ftp_pass}\r\n");
		$this->chat("230") or $this->disconnect();
		return $this->status();
	}

	/**
	 * Creates a directory
	 * @param string $name Directory name
	 * @param string $mode UNIX mode
	 */
	function mkdir($name, $mode = "0775") {
		$this->write("MKD $name\r\n");
		return ($this->chat("257") && $this->chmod($name, $mode));
	}

	/**
	 * Change file mode
	 * @param string $name File name
	 * @param string $mode UNIX mode
	 */
	function chmod($name, $mode) {
		$this->write("SITE CHMOD $mode $name\r\n");
		return $this->chat("200");
	}

	/**
	 * Changes to directory
	 * @param string $name Where to change
	 */
	function cd($name) {
		$this->write("CWD ".rtrim($name, "/")."/\r\n");
		return $this->chat("250");
	}

	/**
	 * Deletes a file
	 * @param string $name File name
	 */
	function delete($name) {
		$this->write("DELE $name\r\n");
		return $this->chat("250");
	}

	/**
	 * Removes an empty directory
	 * @param string $name Directory name
	 */
	function rmdir($name) {
		$this->write("RMD $name/\r\n");
		return $this->chat("250");
	}

	/**
	 * Modification time of a file
	 * @returns int Last time when a file was modified.
	 */
	function mtime($file) {
		$this->write("MDTM $file\r\n");
		if ($this->chat('213')) {
			return substr($this->last(), 4);
		}
		return false;
	}

	/**
	 * Current work directory
	 * @returns string The directory where the client is
	 */
	function pwd() {
		$this->write("PWD\r\n");
		if ($this->chat("257")) 
			return substr($this->last(), 4);
		return false;
	}

	/**
	 * Renames a file
	 * @param string $old Old name
	 * @param string $new New name
	 */
	function rename($old, $new) {
		$this->write("RNFR $old\r\n");
		if ($this->chat("350")) {
			$this->write("RNTO $new\r\n");
			return $this->chat("250");
		}
		return true;
	}

	/**
	 * Enters passive mode and opens a data connection
	 * @param resource $sock Reference to the data connection stream
	 * @returns boolean True on success
	 */
	function pasv(&$sock) {
		$this->write("PASV\r\n");
		$success = $this->chat("227");

		if ($success) {
			// where to connect?
			preg_match("/\(([\d,]*)\)/", $this->last(), $match);
			$match = explode(",", $match[1]);

			if (isset($match[5])) {
				$host = "{$match[0]}.{$match[1]}.{$match[2]}.{$match[3]}";
				$port = $match[4]*256 + $match[5];

				$sock = new socket();
				$sock->connect($host, $port);

				if ($sock->status()) {
					return true;
				} else {
					$this->error(__("Couldn't open data connection to %s:%s.", $host, $port), E_USER_ERROR);
					return false;
				}
			} else {
				$this->debug($match, __FILE__, __LINE__);
			}
		}
	}

	/**
	 * Connection type
	 * @param string $type Type of connection
	 */
	function type($type) {
		$this->write("TYPE $type\r\n");
		return $this->chat("200");
	}

	/**
	 * Sends an archive to the server
	 * @param string $orig Full local path of the file to upload
	 * @param string $dest Remote destination
	 */
	function put($orig, $dest = null) {

		if (!$dest)
			$dest = basename($orig);

		if (file_exists($orig)) {

			$success = $this->pasv($sock);

			if ($success) {
			
				$this->type("I");

				$this->write("STOR $dest\r\n");
				$this->chat("150");

				$fh = fopen($orig, "r");

				do {
					$sock->write(fread($fh, 1024*8));
				} while (!feof($fh));

				$sock->disconnect();

				$this->chat("226");

				return true;
			} else {
				$this->error(__("Couldn't upload file '%s'.", $orig), E_USER_ERROR);
				return false;
			}
		} else {
			$this->error(__("File '%s' does not exists.", $orig), E_USER_ERROR);
		}
	}

	/**
	 * Downloads a file from the server
	 * @param string $file File to download
	 * @param string Contents of the file
	 */
	function get($file) {

		// binary transfers
		$this->type("I");

		// passive mode
		$success = $this->pasv($sock);

		if ($success) {

			$this->write("RETR $file\r\n");
			$this->chat("150");

			$data = $sock->read();
			$sock->disconnect();
			
			$this->chat("226");

			return $data;
		} else {
			$this->error(__("Couldn't download file '%s'.", $file), E_USER_ERROR);
		}
		return false;
	}

	/*
	 * Closes the connection
	 */
	function close() {
		$this->write("QUIT\r\n");
		$this->disconnect();
	}

	/**
	 * Runs a functional test
	 */
	function run_test() {
		$ftp = new ftp_client("localhost", 21, "user", "pass");
		$ftp->debug_level = 1;
		$ftp->open();
		if ($ftp->login()) {
			
			$t = 'test.txt';

			$ftp->cd("public_html");

			$ftp->put(__FILE__, $t);
			
			$ftp->chmod($t, 777);
			
			$buff = $ftp->get($t);

			if ($buff) {
				echo "<pre>".htmlspecialchars($buff)."</pre>";
				$ftp->delete($t);
			}
			
			$ftp->close();
		} else {
			$ftp->error('Login incorrect', E_USER_ERROR);
		}
	}
}
//ftp_client::run_test();
?>
