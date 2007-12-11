<?php
/**
 * File handler
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
 * @modifiedby      $Last_changed_by: xiam.core $
 * @lastmodified    $Date$
 * @license         http://www.opensource.org/licenses/mit-license.php The MIT License
 *
 */

class archive extends tm_object {

	/**
	* Returns the file extension
	* @param string $file Self explained.
	*/
	public function extension($file) {
		return strtolower(substr($file = basename($file), (strpos(strrev($file), ".")*-1)));
	}

	public function mimetype($ext) {
		switch ($ext) {
			case 'mp3': case 'ogg': case 'flac': case 'midi':
				return 'audio/x-generic';
			break;
			case 'gz': case 'tar': case 'zip': case 'bz2':
				return 'package/x-generic';
			break;
			case 'exe': case 'sh':
				return 'application/x-executable';
			break;
			case 'gif': case 'jpg': case 'bmp': case 'png':
				return 'image/x-generic';
			break;
			case 'gif': case 'jpg': case 'bmp': case 'png':
				return 'text/x-generic';
			break;
			case 'php':
				return 'text/php';
			break;
			default:
				return 'text/x-generic';
			break;
		}
	}

	/**
	* Returns a human readable filesize
	* @param int $size Size of the file (bytes).
	*	@author Marioly Garza Lozano <marioly@hackerss.com>
	*/
	public function nice_size($size) {
		$prefixes = array('bytes', 'Kb', 'Mb', 'Gb', 'Tb');
		$i = 0;
		while ($size >= 1024) {
			$size = $size/1024;
			$i++;
		}
		$size = round($size, 2);
		return "{$size}  {$prefixes[$i]}";
	}

	function read_file($path) {
		$f = fopen($path, 'r');
		if ($f) {
			$c = fread($f, filesize($path));
			fclose($f);
			return $c;
		}
	}

	function scandir($path) {
		$files = array();
		$d = opendir($path);
		while (($f = readdir($d)) !== false) {
			if ($f != '.' && $f != '..') {
				$files[] = $f;
			}
		}
		closedir($d);
		return $files;
	}

	public function assign_name($root, $basename) {
		$root = rtrim($root, '/').'/';
		$name = $basename;
		for ($i = 1; file_exists($root.$name); $i++) {
			$a = $i;
			switch ($i) {
				case 1: $a .= 'st'; break;
				case 2: $a .= 'nd'; break;
				case 3: $a .= 'rd'; break;
				default: $a .= 'th'; break;
			}
			$name = $a.'_'.$basename;
		}
		return $root.$name;
	}

	function force_download($path) {
		$size = filesize($path);

		header('content-type: application/octet-stream');
		header('content-length: '.$size);
		header('content-disposition: attachment; filename="'.basename($path).'"');

		$fp = fopen($path, 'r');
		for ($sent = 0; $sent < $size; $sent += 1024*64) {
			echo fread($fp, 1024*64);
		}
		fclose($fp);
	}

	function download($url, $dest) {
		if (preg_match('/([A-Za-z0-9]+):\/\/([^\/]+)\/(.*)/', $url, $scan)) {
			list($scan, $proto, $server, $path) = $scan;

			$proto = strtolower($proto);

			if (is_dir($dest)) {
				$dest = $this->assign_name($dest, basename($path));
			}

			$fp = fopen($dest, 'w');

			switch ($proto) {
				case 'http':
					require_once "socket.php";
					require_once "net/http_client.php";
					$http = new http_client($server, 80);
					$http->open();
					fwrite($fp, $http->get('/'.$path));
					$http->close();
				break;
			}

			fclose($fp);
		}
		return $dest;
	}

	public function secure_path($root, $subdir) {
		if (preg_match('/\.\./', $subdir)) {
			return false;	
		} else {
			return (is_dir($root) && file_exists(dirname($root.$subdir)));
		}
	}
}

?>
