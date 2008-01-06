<?php

/**
 * textMotion
 * ---
 * Written by Jose Carlos Nieto <xiam@menteslibres.org>
 * Copyright (c) 2007-2008, Jose Carlos Nieto <xiam@menteslibres.org>
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @author          Jose Carlos Nieto <xiam@menteslibres.org>
 * @package         textMotion
 * @copyright       Copyright (c) 2007-2008, J. Carlos Nieto <xiam@menteslibres.org>
 * @link            http://www.textmotion.org
 * @version         $Revision$
 * @modifiedby      $LastChangedBy$
 * @lastmodified    $Date$
 * @license         http://www.opensource.org/licenses/mit-license.php The MIT License
 *
 */

class stream {
	private $fp;
	private $file;
	public function __construct($file = null) {
		if ($file) {
			$this->open($file);
		}
	}
	public function open($file) {
		$this->file = $file;
		if (is_readable($file)) {
			$this->fp = fopen($this->file, 'rb');
			return true;
		} else {
			return false;
		}
	}
	public function read($bytes) {
		$buff = '';
		if ($bytes > 0) {
			$buff = fread($this->fp, $bytes);
		}
		return $buff;
	}
	public function seekto($pos) {
		fseek($this->fp, $pos);
		return $this->currentpos();
	}
	public function length() {
		return filesize($this->file);
	}
	public function currentpos() {
		return ftell($this->fp);
	}
	public function close() {
		return fclose($this->fp);
	}
}
?>
