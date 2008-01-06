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


error_reporting(E_ALL);

class tm_object {
	public $debug_level = 0;
	public $use = array();

	public function plugin($plugin) {
		$file = TM_PLUGIN_DIR."/{$plugin}.php";
		if (file_exists($file)) {
			$class = "{$plugin}_plugin";
			if (!isset($this->loaded_plugins[$class])) {
				require_once $file;
				$this->loaded_plugins[$class] = new $class();
			}
			return $this->loaded_plugins[$class];
		}
		return null;
	}

	function using() {
		$args = func_get_args();
		$force = false;
		if (is_array($args[0])) {
			$args = $args[0];
			$force = true;
		}
		$return = array();
		foreach ($args as $use) {
			$path = $use;
			$use = basename($use);
			$this->$use =& $GLOBALS['_CORELIBS'][$use];
			if (!isset($this->$use)) {
				require_once TM_LIB_DIR."{$path}.php";
				$this->$use = new $use();
			}
			$return[$use] =& $this->$use;
		}
		return (count($return) > 1 || $force) ? $return : $return[$use];
	}
	
	function __construct(&$params = null) {
		$this->user =& $_SESSION['user'];
		if ($this->use) {
			$this->using($this->use);
		}
	}

	function debug($data, $error_type = null, $file = null, $line = null) {
		ob_start();
		print_r($data);
		$data = ob_get_clean();
		if ($this->debug_level > 0) {
			echo "<pre class=\"debug\">".time().": ".htmlspecialchars($data)."<pre>";
		}
	}

	function version() {
		return '0.0';
	}

	function error($error, $type = E_USER_ERROR) {
		trigger_error($error, E_USER_ERROR);
	}

	function load($p) {
		$p .= '.php';
		if (file_exists(TM_LIB_DIR.$p)) {
			require_once TM_LIB_DIR.$p;
			return;
		} else {
			$x = explode('/', $p);
			$f = TM_MODULES_DIR."{$x[0]}/lib/{$x[1]}";
			if (file_exists($f)) {
				require_once $f;
				return;
			}
		}
		trigger_error("Unknown library '$p'.", E_USER_ERROR);
	}
}
?>
