<?php
/**
 * Output handler
 * Output handler with compression features.
 * ---
 * Written by Jose Carlos Nieto <xiam@menteslibres.org>
 * Copyright (c) 2007 Astrata Software S.A. de C.V.
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @author          Jose Carlos Nieto <xiam@menteslibres.org>
 * @copyright       Copyright (c) 2007-2008, Astrata Software S.A. de C.V.
 * @link            http://opensource.astrata.com.mx Astrata Open Source Projects
 * @version         $Revision$
 * @modifiedby      $LastChangedBy$
 * @lastmodified    $Date$
 * @license         http://www.opensource.org/licenses/mit-license.php The MIT License
 *
 */

class output_handler extends tm_object {
	var $enable_compression = false;
	var $_gz_output = true;
	function __construct(&$params = null) {
		parent::__construct(&$params);
		if ($this->enable_compression && extension_loaded('zlib') && isset($_SERVER['HTTP_ACCEPT_ENCODING']) && ereg('(gzip)', $_SERVER['HTTP_ACCEPT_ENCODING'])) {
			$this->_gz_output = true;
			ob_start('ob_gzhandler');
		} else {
			ob_start();
		}
	}
	function __destruct() {
		if ($this->_gz_output) {

			$buff = ob_get_clean();
			$size = strlen($buff);
			header('Content-Encoding: gzip');
			header('Vary: Accept-Encoding');
			
			$crc = crc32($buff);
			$buff = "\x1f\x8b\x08\x00\x00\x00\x00\x00".substr(gzcompress($buff, 9), 0, -4);
			$buff .= pack('V', $crc);
			$buff .= pack('V', $size);

			header('Content-Length: '.strlen($buff).'');

			print $buff;
		} else {
			print ob_get_clean();
		}
	}
	function run_test() {
		$output = new output_handler();
		echo str_repeat("A", 500);
	}
}
//output_handler::run_test();
?>
