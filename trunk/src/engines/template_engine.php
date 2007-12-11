<?php

/**
 * Template Engine
 * Translates a set of specially formatted strings into PHP code.
 * ---
 * Written by Jose Carlos Nieto <xiam@astrata.com.mx>
 * Copyright (c) 2007 Astrata Software S.A. de C.V.
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @author          Jose Carlos Nieto <xiam@astrata.com.mx>
 * @copyright       Copyright (c) 2007, Astrata Software S.A. de C.V.
 * @link            http://opensource.astrata.com.mx Astrata Open Source Projects
 * @version         $Revision$
 * @modifiedby      $Last_changed_by: $
 * @lastmodified    $Date$
 * @license         http://www.opensource.org/licenses/mit-license.php The MIT License
 *
 */

require_once "simple_parser_engine.php";

$GLOBALS['_TEMPLATE_GLOBALS'] = array();

class template_engine extends simple_parser_engine {

	/**
	 * Key based array of variables that will be extracted into the template's current symbol table
	 * @var array
	 */
	var $vars = array();

	/**
	 * Directory where to store cached templates.
	 * @var string
	 */
	var $cache_dir = '/tmp';

	/**
	 * Set to true if you want this class to save a cached version of the template.
	 * @var boolean
	 */
	var $enable_cache = true;

	/**
	 * We want to evaluate only the first level of enclosed text
	 * @var boolean
	 */
	var $use_stack = true;

	/**
	 * Using '\' as a general scape character
	 * @var string
	 */
	var $escape_char = '\\';

	/**
	 * Template delimiters
	 * @var array
	 */
	var $delimiters = array(
		array('<!--{', '}-->'),
		array('{', '}', '[^\n\r]')
	);

	/**
	 * Escapes HTML tags.
	 * @param mixed $data A reference to the variable that will be escaped
	 * @returns escaped variable
	 */
	function escape(&$data) {
		if (is_array($data)) {
			while (list($k) = each($data))
				$this->escape($data[$k]);
		} else {
			$data = htmlspecialchars($data);
		}
		return $data;
	}
	
	/**
	 * Inserts variables into the current template symbol table.
	 * @param mixed $name A string with the name of the variable or an array that contains a key based array of variables.
	 * @param string $value Value of the variable.
	 * @param boolean $escape True if you want HTML entities to be escaped.
	 */
	function set($name, $value = null, $escape = true) {
		if (is_array($name)) {
			foreach ($name as $n => $v) 
				$this->set($n, $v, $escape);
		} else {
			$this->vars[$name] = $escape ? $this->escape($value) : $value;
		}
	}
	
	/**
	 * Inserts variables into the global template symbol table.
	 * @param mixed $name A string with the name of the variable or an array that contains a key based array of variables.
	 * @param string $value Value of the variable.
	 * @param boolean $escape True if you want HTML entities to be escaped.
	 */
	function set_global($name, $value = null, $escape = true) {
		if (is_array($name)) {
			foreach ($name as $n => $v) 
				template::set_global($n, $v, $escape);
		} else {
			$a =& $GLOBALS['_TEMPLATE_GLOBALS'];
			$b = null;
			$name = explode('.', $name);
			while ($name) {
				$n = array_shift($name);
				$b =& $a[$n];
				if (!isset($b)) {
					$b = array();
				}
				$a =& $b;
			}
			$a = $escape ? template::escape($value) : $value;
		}
	}

	function get_global($name) {
		$var =& $GLOBALS['_TEMPLATE_GLOBALS'][$name];
		return isset($var) ? $var : null;
	}

	/**
	 * Reads an entire file from disk.
	 * @param string $path Full path of the file to read.
	 * @param string &$buff Reference where to store the file contents.
	 */
	function load_file($path, &$buff) {
		if (file_exists($path)) {
			$fh = fopen($path, 'r');
			if (filesize($path)) {
				$buff = fread($fh, filesize($path));
			} else {
				$buff = null;
			}
			fclose($fh);
		} else {
			trigger_error("Not readable $path.", E_USER_ERROR);
		}
	}

	/**
	 * Executes the template
	 * 
	 */
	function execute() {
		extract($GLOBALS['_TEMPLATE_GLOBALS'], EXTR_SKIP);
		extract($this->vars, EXTR_SKIP);
		ob_start();
		include $this->cache_file;
		$this->result = ob_get_clean();
	}

	/**
	 * Executes the template and returns the result
	 * @returns The result of the executed template
	 */
	function output() {
		$this->execute();
		return $this->result;
	}

	/**
	 * Executes the template and echoes the result
	 */
	function write_output() {
		echo $this->output();
	}

	/**
	 * Writes the template buffer into its cache file
	 * @param string $buff the data that will be written
	 */
	function cache_write($buff) {
		$fh = fopen($this->cache_file, 'w') or trigger_error("Couldn't write file {$this->cache_file}!", E_USER_ERROR);
		fwrite($fh, $buff);
		fclose($fh);
	}

	/**
	 * Translates specially formatted strings into PHP code
	 * @param string $code String to be evaluated
	 * @param array $e Enclosing tags
	 */
	function eval_enclosed($code, $e) {

		$enclosed = substr($code, strlen($e[0]), -1*strlen($e[1]));

		if (preg_match('/^[^\n]+$/', $code)) {
			
			if (preg_match('/^[A-Z0-9_]+$/', $enclosed)) {
				return "<?php echo $enclosed ?>";
			} else {

				switch ($e[0]) {
					case '<!--{':
						switch($enclosed) {
							case 'else':
								return "<?php } else { ?>";
							break;
							case '/':
								return "<?php } ?>";
							break;
							default:
								if (preg_match('/else.*/', $enclosed)) {
									return "<?php } $enclosed { ?>";
								} else {
									return "<?php $enclosed { ?>";
								}
							break;
						}
					break;
					case '{':
						if (preg_match('/^[A-Z0-9\'"\s]+:.+$/i', $enclosed)) {
							// javascript tuple
							return $code;
						} else {
							switch ($enclosed{0}) {
								case '%':
									// TODO: use regular expressions
									if (substr($enclosed, 1, 5) == 'using') {
										return "<?php extract(\$this->using(array".substr($enclosed, 6)."), EXTR_OVERWRITE) ?>";
									} else {
										return "<?php \$this->".substr($enclosed, 1)."; ?>";
									}
								break;
								case '=':
									// as is
									return "<?php echo ".substr($enclosed, 1)." ?>";
								break;
								case ':':
									// language
									if ($enclosed{1} == '(') {
										return "<?php echo __".substr($enclosed, 1)." ?>";
									} else {
										return "<?php echo __(\"".substr($enclosed, 1)."\") ?>";
									}
								break;
								default:
									// escaped
									return "<?php echo htmlspecialchars($enclosed) ?>";
								break;
							}
						}
					break;
				}
			}
		} else {
			return $code;
		}
	}

	public function clean_cache() {
		$dh = opendir($this->cache_dir);
		while (($f = readdir($dh)) !== false) {
			if ($f{0} != '.') {
				$f = $this->cache_dir.'/'.$f;
				if (time()-filemtime($f) > 3600*24) {
					unlink($f);
				}
			}
		}
		closedir($dh);
	}

	public function __construct(&$param = null) {
	}

	/**
	 * Reads a template file from disk and converts it into PHP
	 * @param string $file Full path to the template file
	 */
	function load_template($file) {

		$this->vars = array();

		if (!file_exists($file))
			trigger_error("Template file \"$file\" does not exists.", E_USER_ERROR);

		$this->cache_name = substr(md5($file), 0, 16).".".filemtime($file).'.'.filesize($file);
		$this->cache_file = $this->cache_dir.$this->cache_name;

		if (!file_exists($this->cache_file) || !$this->enable_cache) {
			// reading from disk
			$this->load_file($file, $buff);
			
			// included files
			while (preg_match('/{((include|require)\(\'([^\']+)\'\))}/', $buff, $match)) {

				$file = dirname($file).'/'.$match[3];
				$fh = fopen($file, 'r');
				$contents = fread($fh, filesize($file));
				fclose($fh);

				$buff = str_replace($match[0], $contents, $buff);

				/*

				$buff = preg_replace('/{((include|require)[^}]+)}/', '<?php \1 ?>', $buff);
				$this->cache_write($buff);
				ob_start();
				chdir(dirname($file));
				include $this->cache_file;
				$buff = ob_get_clean();
				*/
			}
			$this->parse($buff);

			$this->cache_write($buff);
		}
	}
}
?>
