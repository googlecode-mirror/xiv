<?php

/**
 * Cache
 * Caching features
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


class cache extends tm_object {

	private $cache_dir = '/tmp/tm/cache';
	private $max_cache_life = 259200;

	private $lifetime = 3600;

	public function remove_dead($dirname) {
		$dh = opendir($dirname);
		while (($f = readdir($dh)) !== false) {
			if ($f != '.' && $f != '..') {
				$path = $dirname.'/'.$f;
				if (is_dir($path)) {
					$this->remove_dead_files($path);
				} else {
					if ((time() - filemtime($path)) > $this->max_cache_life) {
						unlink($path);
					}
				}
			}
		}
		closedir($dh);
	}

	private function init_directory() {
		$path = explode('/', $this->cache_dir);
		$dir = null;
		foreach ($path as $p) {
			@mkdir($dir .= "/{$p}", 0777);
		}
	}

	public function __construct($name = null, $lifetime = 0, $type = 'app', $unique = false) {
		$this->cache_dir = TM_TEMP_DIR.'cache/'.$type.'/';
		if ($name) {
			if ($lifetime > 0) {
				$this->lifetime = $lifetime;
			}
			$this->cache_file = $this->cache_dir.'/'.md5($name).'.'.strlen($name).($unique ? '.'.md5(TM_UNIQUE_STR) : null);
			$this->init_directory();
		} else {
			$this->init_directory();
			$this->remove_dead($this->cache_dir);
		}
	}

	public function is_valid() {
		return (file_exists($this->cache_file) && (time()-filemtime($this->cache_file) < $this->lifetime));
	}

	public function get() {
		$fh = fopen($this->cache_file, 'r');
		$data = fread($fh, filesize($this->cache_file));
		fclose($fh);
		return unserialize($data);
	}

	public function save($data) {
		$fh = fopen($this->cache_file, 'w');
		fwrite($fh, serialize($data));
		fclose($fh);
	}

	public function clear() {
		@unlink($this->cache_file);
	}
	
	public function clear_all($dirname = null) {
		if (!$dirname) {
			$dirname = $this->cache_dir;	
		}
		$dh = opendir($dirname);
		while (($f = readdir($dh)) !== false) {
			if ($f != '.' && $f != '..') {
				$path = $dirname.'/'.$f;
				if (is_dir($path)) {
					$this->clear_all($path);
				} else {
					unlink($path);
				}
			}
		}
		closedir($dh);
		rmdir($dirname);
	}
}

?>
