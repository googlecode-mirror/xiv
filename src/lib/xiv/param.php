<?php
/**
 * Param
 * textMotion request variables wrapper.
 * ---
 * Written by Jose Carlos Nieto <xiam@menteslibres.org>
 * Copyright (c) 2007 textMotion Development Crew
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @author          Jose Carlos Nieto <xiam@menteslibres.org>
 * @copyright       Copyright (c) 2007-2008, textMotion Development Crew
 * @link            http://www.textmotion.org
 * @id              $Id$
 * @version         $Revision$
 * @modifiedby      $LastChangedBy$
 * @lastmodified    $Date$
 * @license         http://www.opensource.org/licenses/mit-license.php The MIT License
 *
 */

require_once "lib/handlers/url_handler.php";

class param extends url_handler {

	/**
	* Private escape callback function
	*
	* @var string
	*/
	private $escape_function = 'htmlspecialchars';

	/**
	 * Private params holder
	 *
	 * @var array
	 */
	private $params = array();

	/**
	 * The URL's base path.
	 *
	 * @var string
	 */
	public $base_map = null;


	/**
	 * Constructor
	 */
	public function __construct(&$params = null) {
		parent::__construct($params);
		$this->pretty_urls = TM_PRETTY_URLS; 
		$this->base_path = TM_WEBROOT;
		$this->base_script = TM_WEBROOT;
	}

	public function from_editor($name) {
		$format =& $this->using('format');
		$this->set($name, $format->editor_content_fix($this->get($name)));
	}

	private function move_uploaded_file($files, $destdir) {

		$return = array();

		$element = $files['name'];

		if (is_array($element)) {

			reset($element);

			while(list($i) = each($element)) {

				$file = array(
					'name' => $files['name'][$i],
					'type' => $files['type'][$i],
					'tmp_name' => $files['tmp_name'][$i],
					'error' => $files['error'][$i],
					'size' => $files['size'][$i]
				);

				if ($file['error'] == 0) {

					$name = $file['name'];
					for ($i = 1; file_exists($destdir.$name); $i++) {
						$a = $i;
						switch ($i) {
							case 1: $a .= 'st'; break;
							case 2: $a .= 'nd'; break;
							case 3: $a .= 'rd'; break;
							default: $a .= 'th'; break;
						}
						$name = $a.'_'.$file['name'];
					}
					$file['orig_name'] = $file['name'];
					$file['path'] = $destdir.$name;
					$file['name'] = $name;

					move_uploaded_file($file['tmp_name'], $file['path']);

					$return[] = $file;
				}
			}
		} else {
			foreach ($files as $key => $value) {
				$files[$key] = array($value);
			}
			return $this->move_uploaded_file($files, $destdir);
		}
		return $return;
	}

	public function remote($url) {
		extract(
			$this->using(
				'json',
				'archive'
			)
		);
		return $json->decode($archive->download($url));
	}

	public function upload($varname, $destdir) {
		
		$files =& $_FILES;

		$varname = explode('.', $varname);
		
		$main = array_shift($varname);

		$result = array();

		if (!empty($files[$main])) {
			foreach($files[$main] as $key => $arr) {
				foreach ($varname as $path) {
					if (isset($arr[$path])) {
						$arr = $arr[$path];
					} else {
						return;
					}
				}
				foreach($arr as $i => $v) {
					$result[$i][$key] = $v;
				}
			}
		}

		$destdir = rtrim($destdir, '/').'/';

		if (!empty($result)) {
			$data = array();
			foreach ($result as $i => $v) {
				$data[$i] = $this->move_uploaded_file($v, $destdir);
				$data[$i] = $data[$i][0];
			}
			return $data;
		} 

		return false;
	}

	public function files() {
		$files = array();

		if (!empty($_FILES)) {
			reset($_FILES);
			while (list($var) = each($_FILES)) { 
				$files[$var] = array();
				if (is_array($_FILES[$var]['name'])) {
					reset($_FILES[$var]['name']);
					while (list($i) = each($_FILES[$var]['name'])) {
						if (!$_FILES[$var]['error'][$i]) {
							$files[$var][$i] = array(
								'name' => $_FILES[$var]['name'][$i],
								'type' => $_FILES[$var]['type'][$i],
								'tmp_name' => $_FILES[$var]['tmp_name'][$i],
								'error' => $_FILES[$var]['error'][$i],
								'size' => $_FILES[$var]['size'][$i]
							);
						}
					}
				} else {
					if (!$_FILES[$var]['error']) {
						$files[$var][] = array(
							'name' => $_FILES[$var]['name'],
							'type' => $_FILES[$var]['type'],
							'tmp_name' => $_FILES[$var]['tmp_name'],
							'error' => $_FILES[$var]['error'],
							'size' => $_FILES[$var]['size']
						);
					}
				}
			}
		}
		return $files;
	}

	public function textilize($title, $full_encoding = false) {
		$title = strtolower($title);
		if ($full_encoding) {
			$title = rawurlencode(str_replace("/", "-", $title));
			$title = str_replace("%20", "+", $title);
		} else {
			$title = htmlentities(utf8_decode($title));
			$title = preg_replace('/&amp;[^;]*;/', ' ', $title);
			$title = preg_replace('/&(.)[^;]*;/', '\\1', $title);
			$title = trim(preg_replace('/[^0-9a-zA-Z]/', ' ', $title));
			$title = str_replace(' ', '-', $title);
			// avoiding multiple occurences of - in the same line
			$title = preg_replace('/-+/', '-', $title);
		}
		return $title;
	}
	

	/**
	 * Returns the value of the parameter.
	 *
	 * @param string $name Name of the parameter
	 * @returns mixed Value of the paramether or null if it doesn't exists.
	 */
	public function get($path) {

		$a = $this->get_value($path, $this->params);
		if ("$a")
			return $a;

		$a = $this->get_value($path, $_GET);
		if ("$a")
			return $a;
		
		$a = $this->get_value($path, $_POST);
		if ("$a")
			return $a;

		return null;
	}

	/**
	 * Returns the bare value of a variable.
	 *
	 * @param string $name Variable name.
	 * @returns mixed Raw variable from GET or POST
	 */
	public static function raw($name) {
		return isset($_GET[$name]) ? $_GET[$name] : (isset($_POST[$name]) ? $_POST[$name] : null);
	}

	/**
	 * Imports the given arguments as paramethers, takes
	 *
	 * all its arguments as variable names.
	 * @returns void
	 */
	public function accept() {
		$args = func_get_args();
		foreach ($args as $arg)
			$this->set($arg, $this->raw($arg));
	}

	/*
	* Applies a function to each param given its name
	* 
	* @param mixed $name array of string containing a set of parameters
	* @param string $func callback function
	*/
	public function callback($name, $func) {
		if (is_array($name)) {	
			for ($i = 0; isset($name[$i]); $i++)
				$this->callback($name[$i], $func);
		} else {
			$this->set($name, $func($this->get($name)));
		}
	}

	private function empty_values(&$search, $value) {
		if (!empty($search)) {
			if (is_array($value)) {
				foreach ($value as $v) {
					if ($this->empty_values($search[$v], $v)) 
						return false;
				}
			} else {
				return !empty($search[$value]);
			}
		}
		return false;
	}

	private function get_value($name, &$from) {

		$a =& $from;
		$b = null;
		$name = explode('.', $name);
		while ($name) {
			$n = array_shift($name);
			$b =& $a[$n];
			$a =& $b;
		}
		return isset($a) ? $a : null;
	}

	/**
	 * Check if the given arguments of variable names has
	 *
	 * values, if any of them is empty it throws an error.
	 */
	public function not_empty() {
	
		$env =& $this->using('env');

		$args = func_get_args();
		foreach ($args as $arg) {
			if (!$this->get_value($arg, $_POST) && !$this->get_value($arg, $_GET) && !$this->get_value($arg, $this->params))
				$env->error(__("Required argument %s.", $arg));
		}
	}

	/**
	 * Import variables from the environment with a given map
	 *
	 * of values and types.
	 * @param string $map URL Prototype
	 * @returns array of paramethers with their values
	 */
	public function map($map) {
		$this->params = parent::map(rtrim($this->base_map, '/').'/'.ltrim($map, '/'));
		return $this->params;
	}

	/**
	 * Sets a paramether
	 *
	 * @param string $name Name of the paramether
	 * @param mixed $value Value of the paramether
	 * @param boolean $escape True if you want data to be escaped (default)
	 */
	public function set($name, $value = null, $escape = false) {

		if (is_array($name)) {
			foreach ($name as $n => $v)
				$this->set($n, $v, $escape);
		} else {
			$a =& $this->params;
			$b = null;
			$name = explode('.', $name);
			while ($name) {
				$n = array_shift($name);
				$b =& $a[$n];
				$a =& $b;
			}

			if ($escape) {
				$this->escape($value);
			}
			$a = $value;
		}
	}

	/**
	* Applies the escape function to the given variable
	*
	* @param mixed $mixed Reference to the string or array
	*/
	public function escape(&$mixed) {
		if (is_array($mixed)) {
			reset($mixed);
			while (list($i) = each($mixed))
				$this->escape($mixed[$i]);
		} else {
			$escape = $this->escape_function;
			$mixed = $escape($mixed);
		}
	}

	/**
	 * Get paramethers
	 *
	 * @returns array All registered paramethers.
	 */
	public function get_params() {
		return $this->params;
	}

	/**
	 * Get Server value
	 *
	 * @returns mixed The value of a existing SERVER variable or null
	 */
	public function server($name) {
		return env($name);
	}

	/**
	 * Checks if a variable is set or not
	 *
	 * @param string $name Name of the variable
	 */
	public function exists($name) {
		return isset($this->params[$name]);
	}
}
?>
