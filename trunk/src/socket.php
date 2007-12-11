<?php

/**
 * Socket wrapper
 * A simple but useful socket wrapper
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

//require_once "cache.php";

require_once "object.php";

class socket extends tm_object {

	/**
	 * Seconds the socket can wait for a connection request to be accepted.
	 * @var integer
	 */
	public $open_timeout = 5;

	/**
	 * Seconds the socket will hold the opened connection waiting for a package.
	 * @var integer
	 */
	public $read_timeout = 10;
	
	/**
	 * Seconds the socket will remember if the last connection was wheter a success or a failure.
	 * @var integer
	 */
	public $test_cache_time = 300;

	public $_socket;
	public $_errno;
	public $_errstr;
	public $_last_response;
	public $_status = 0;

	/**
	 * Creates a connect socket
	 * @param string $host Remote domain name or IP to connect
	 * @param int $port Port number to connect
	 */
	function connect($host, $port) {

		$last_conn_status = true;

		debug('Connecting to '.$host.' '.$port.'.');

		// trying to connect if the last try was successful of if this is the first
		if ($last_conn_status)
			$this->_socket = @fsockopen($host, $port, $this->_errno, $this->_errstr, $this->open_timeout);
		
		$last_conn_status = $this->status();

		if ($last_conn_status) {
			return $this->status();
		} else {
			debug('The connection has been closed.');
			return false;
		}

	}
	
	/**
	 * @returns boolean true if any connection is alive.
	 */
	function status() {
		return (is_resource($this->_socket) && !feof($this->_socket));
	}

	/*
	 * Writes raw data to the socket
	 * @param string data Raw data ready for being sent
	 * @returns int -1 if there is not active connection, 0 on failure, 1 on success
	 */
	function write($data) {
		if (!$this->status())
			return -1;
		debug("C: $data", 3);
		return fwrite($this->_socket, $data);
	}

	/**
	 * Sets or returns the last server response.
	 * @param string $response last words of the server.
	 * @returns last stored server response
	 */
	function last($response = null) {
		if ($response) {
			$this->_last_response = $response;
		}
		return $this->_last_response;
	}

	/**
	 * Reads server responses.
	 * @param boolean $until_eof True if you want to read all the packets.
	 * @returns string Server response
	 */
	function read($until_eof = false) {

		if ($until_eof) {
			// main buffer
			$data = null;

			if ($this->status()) {

				stream_set_timeout($this->_socket, $this->read_timeout);

				do {

					$buff = fread($this->_socket, 1024);

					$data .= $buff;
					$meta = stream_get_meta_data($this->_socket);

					if (!$until_eof && !$meta['unread_bytes']) {
						return false;
						break;
					}

					if ($meta['timed_out']) {
						debug('Connection timed out.');
						return false;
						break;
					}
					
				} while (!$meta['eof']);

				debug("S: ".$this->last($data), 3);
			}

			return $data;
		} else {
			return fread($this->_socket, 1024*10);
		}
	}

	/**
	 * @returns A response line from the server
	 */
	function read_line() {
		$data = fgets($this->_socket);
		debug("S: ".$this->last($data), 3);
		return $data;
	}

	/**
	 * Closes the connection
	 */
	function close() {
		debug('Disconnected.');
		$this->disconnect();
	}
	
	/**
	 * Closes the socket
	 */
	function disconnect() {
		if ($this->status())
			fclose($this->_socket);
	}
}

?>
