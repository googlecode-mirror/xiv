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

class sys extends tm_object {
	function create_dir($path, $perms = 0777) {
		$root = null;
		$path = explode('/', $path);
		foreach ($path as $dir) {
			$root .= $dir.'/';
			if (!file_exists($root))
				mkdir($root, $perms);
		}
	}
	function write($filename, $mode, $text = null) {
		$fh = fopen($filename, $mode);
		if ($fh) {
			fwrite($fh, $text);
			return fclose($fh);
		}
		return false;
	}
	function read($filename) {
		$fh = fopen($filename, 'r');
		if ($fh) {
			$c = fread($fh, filesize($filename));
			fclose($fh);
			return $c;
		}
		return null;
	}
	// TODO
	function log($message, $file = null, $line = null, $logname = 'messages') {
		$logfile = TM_TEMP_DIR."logs/{$logname}.log";
		$this->create_dir(dirname($logfile));
		// TODO
		$logline = $message;
		$this->write($logfile, 'a', $logline);
	}
}
?>
