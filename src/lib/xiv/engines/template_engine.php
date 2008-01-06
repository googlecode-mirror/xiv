<?php

/**
 * Template Engine
 * Translates a set of specially formatted strings into PHP code.
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

	public function date($datetime) {
		$format =& $this->using('format');
		return '<span class="date">'.$format->date($datetime).'</span>';
	} 

	public function rpc_link($href, $text, $icon = null, $options = array()) {
		$options = array_merge(array('onclick' => 'return link(this, true);'), $options);
		if ($icon) {
			$text = $this->mini_icon($icon).' '.$text;
		}
		return $this->link($href, $text, $options);
	}

	public function exec_action($url, $text, $icon = null) {
		$param =& $this->using('param');
		return $this->link(null, ($icon ? $this->mini_icon($icon).' ' : null).$text, array('onclick' => 'return exec_action(this)', 'href' => $param->create($url)));	
	}

	public function action($url, $title, $icon = 'action') {
		$param =& $this->using('param');
		return $this->link(null, $this->mini_icon($icon).' '.$title, array('href' => $param->create($url)));	
	}

	public function show_messages() {
		extract(
			$this->using(
				'param',
				'session'
			)
		);
		$errors = array(
			$session->get('error'),
			$param->get('error')
		);
		$buff = array();
		foreach ($errors as $err) {
			if ($err) {
				$buff[] = $this->tag('div', array('class' => 'error'), $err);
			}
		}
		return implode('', $buff);
	}

	public function safe_link($href, $text) {
		if (preg_match('/^http:\/\/.*$/', $href)) {
			null;
		} else if (preg_match('/^javascript:.*/', $href)) {
			$href = '';
		} else {
			$href = "http://{$href}";
		}
		return $href ? $this->link(null, $this->escape($text), array('href' => $this->escape($href))) : $this->escape($text);
	}

	public function javascript($src) {
		return $this->tag('script', array('src' => TM_WEBROOT.$src, 'type' => 'text/javascript'));
	}

	public function is_single_tag($tag) {
		return preg_match("/input|img|br|hr/i", $tag);
	}

	public function tag($name, $attr_arr = null, $inner_html = null) {
		$attr = array();
		if (!$attr_arr) 
			$attr_arr = array();
		foreach ($attr_arr as $k => $v) {
			if (is_numeric($k))
				$k = $v;
			$attr[] = "{$k}=\"{$v}\"";
		}
		$buff = "<$name";
		$buff .= ($attr ? " ".implode(" ", $attr) : null);
		$buff .= $this->is_single_tag($name) ? " />" : ">{$inner_html}</$name>";
		return $buff;
	}

	public function get_name($name) {
		$part = explode('.', $name);	
		$name = array_shift($part);
		return "{$name}".($part ? '['.implode('][', $part).']' : null);
	}

	public function get_value($name, $from = null, $escape = true) {

		if (!$from)
			$from =& $this->vars;

		$a =& $from;
		$b = null;
		$name = explode('.', $name);
		while ($name) {
			$n = array_shift($name);
			$b =& $a[$n];
			$a =& $b;
		}
		$a = isset($a) ? $a : null;
		return $escape ? $this->escape($a) : $a;
	}

	public function select($name = null, $options = null, $attr = null) {

		unset($value);

		if (!$options)
			$options = array();

		if ($attr && !is_array($attr)) {
			$value = $attr;
			$attr = null;
		}

		if (!$attr)
			$attr = array();

		if ($name) 
			$attr['name'] = $this->get_name($name); 
		
		$buff = array();
		$buff[] = $this->tag('option', null, '');
		if (!isset($value)) {
			$value = $this->get_value($name);
			if (!is_string($value)) {
				$value = '';
			}
		}
		foreach ($options as $v => $n) {
			$buff[] = $this->tag('option', (strcmp($value, $v) == 0) ? array('selected' => 'selected', 'value' => $v) : array('value' => $v), $n);
		}

		return $this->tag('select', $attr, implode(" ", $buff));
	}
	
	public function link($href, $text = null, $attr = null) {
		
		$param =& $this->using('param');

		if (!$text)
			$text = $href;

		if (!isset($attr['href']))
			$attr['href'] = $param->create($href);

		return $this->tag('a', $attr, $text);
	}
	
	public function confirm_link($href, $text = null, $attr = null) {
		$param =& $this->using('param');

		if (!$text)
			$text = $href;

		$attr['href'] = 'javascript:void(0);';
		$attr['onclick'] = 'javascript:confirm_action(\''.$param->create($href, false).'\');';

		return $this->tag('a', $attr, $text);
	}

	public function thumbnail($file, $maxsize = 100, $attr = null) {

		if (!$attr) {
			$attr = array();
		}
		
		$this->using('image');

		if (!$file) {
			$file = '/media/noimage.png';
		}

		$thumbdir = TM_DATA_DIR.'thumbs/';
		
		$thumbfile = $thumbdir.$maxsize.'_'.md5($file).'_'.basename($file);

		if (!file_exists($thumbfile)) {
			@mkdir($thumbdir);
			$path = TM_DATA_DIR.$file;
			if (!file_exists($path)) {
				$path = TM_SOURCE_DIR.$file;
			}
			if (is_writable($thumbdir) && file_exists($path) && !is_dir($path)) {
				$img = new image();
				$img->load($path);
				$img->resize($maxsize);
				$img->save($thumbfile);
			}
		} else {
			list($attr['width'], $attr['height']) = getimagesize($thumbfile);
		}

		$thumburl = TM_BASE_URL.'data/'.substr($thumbfile, strlen(TM_DATA_DIR));

		return $this->img($thumburl, $attr);
	}

	public function img($file, $attr = null) {
		if (!$attr) $attr = array();

		if (!isset($attr['alt']))
			$attr['alt'] = basename($file);
		
		if (file_exists(TM_DATA_DIR.$file) && !preg_match('/^\.\./', $file)) {
			$attr['src'] = TM_WEBROOT.'data/'.ltrim($file, '/');
			$size = getimagesize(TM_DATA_DIR.$file);
			$attr['width'] = $size[0];
			$attr['height'] = $size[1];
		} else if (file_exists(TM_SOURCE_DIR.$file) && !preg_match('/^\.\./', $file)) {
			$attr['src'] = TM_BASE_URL.ltrim($file, '/');
			$size = getimagesize(TM_SOURCE_DIR.$file);
			$attr['width'] = $size[0];
			$attr['height'] = $size[1];
		} else {
			$attr['src'] = $file;
		}
		return $this->tag('img', $attr);
	}

	public function file($name = null, $attr = null) {
		if (!$attr) $attr = array();
		if ($name)
			$attr['name'] = $this->get_name($name);
		$attr['type'] = 'file';
		return $this->tag('input', $attr);
	}

	public function input($name = null, $attr = null) {
		if ($attr && !is_array($attr)) {
			$attr = array('value' => $attr);
		}
		if (!$attr) $attr = array();
		
		if ($name) 
			$attr['name'] = $this->get_name($name); 
		if (!isset($attr['type'])) 
			$attr['type'] = 'text';
		if (!isset($attr['value']))
			$attr['value'] = $this->get_value($name);
		return $this->tag('input', $attr);
	}

	public function password($name = null, $attr = null) {
		if (!$attr) $attr = array();
		$attr['type'] = 'password';
		return $this->input($name, $attr);
	}

	public function textarea($name = null, $attr = null, $inner_text = null) {

		if (!$inner_text)
			$inner_text = $this->get_value($name);

		if ($name)
			$attr['name'] = $this->get_name($name);

		return $this->tag('textarea', $attr, $inner_text);
	}

	public function status($name) {
		return $this->select($name, array('0' => __('Disabled'), '1' => __('Enabled')));
	}

	public function belongs_to($name, $data, $key, $title = null) {
		if (!$title)
			$title = $key;

		$options = array();
		foreach ($data as $d) 
			$options[$this->get_value($key, $d)] = $this->get_value($title, $d);

		return $this->select($name, $options);
	}

	public function ajax_action($href, $text, $icon = null) {
		$param =& $this->using('param');
		$href = $param->create($href);
		return $this->action_link("javascript:ajax_action('{$href}')", $text, $icon);
	}

	public function action_link($href, $text, $icon = null) {
		
		$param =& $this->using('param');

		$prop = array();

		preg_match('/^([^:]*):(.*)$/', $href, $m);
		if ($m) {
			switch($m[1]) {
				case 'confirm':
					$prop['href'] = 'javascript:void(0)';
					$prop['onclick'] = "javascript:confirm_action('".$param->create($m[2])."')";
				break;
				case 'javascript':
					$prop['href'] = 'javascript:void(0)';
					$prop['onclick'] = substr($href, 11);
				break;
			}
		} else {
			$prop['href'] = $param->create($href);
		}
		if (!isset($prop['href']))
			$prop['href'] = $href;

		return $this->link(null, ($icon ? $this->mini_icon($icon).' ' : '').$text, $prop);
	}

	public function checkbox($name, $attr = null, $checked = false, $text = null) {
		if (!$attr) {
			$attr = array();
		}

		if (!isset($attr['name'])) 
			$attr['name'] = $this->get_name($name);

		if (!isset($attr['value']))
			$attr['value'] = 1;

		if ($checked || ($checked === null && $this->get_value($name)))
			$attr['checked'] = 'checked';
	
		if ($text) {
			return $this->tag('label', null, $this->tag('span', null, $this->checkbox($name, $attr).' '.$text));
		} else {
			$attr['type'] = 'checkbox';
			return $this->tag('input', array('type' => 'hidden', 'value' => '', 'name' => $attr['name'])).$this->tag('input', $attr);
		}
	}

	public function hidden($name = null, $attr = null) {
		if (!is_array($attr) && $attr) {
			$attr = array('value' => $attr);
		}
		if (!$attr) {
			$attr = array();
		}
		$attr['type'] = 'hidden';
		return $this->input($name, $attr);
	}

	public function submit($caption, $attr = null) {
		if (!$attr) $attr = array();
		$attr = array_merge(
			$attr,
			array(
				'type' => 'submit',
				'class' => 'submit'
			)
		);
		return $this->tag('button', $attr, $caption);
	}
	
	public function reset($caption, $attr = null) {
		if (!$attr) $attr = array();
		$attr = array_merge(
			$attr,
			array(
				'type' => 'reset',
				'class' => 'reset'
			)
		);
		return $this->tag('button', $attr, $caption);
	}

	public function button($name = null, $attr = null, $inner_html = '') {
		if (!$attr) $attr = array();

		if ($name) 
			$attr['name'] = $this->get_name($name); 

		if (!isset($attr['type'])) 
			$attr['type'] = 'button';
		return $this->tag("button", $attr, $inner_html);
	}

	public function buttons() {
		return $this->tag('div', array('class' => 'buttons'), $this->button(null, array('class' => 'submit', 'type' => 'submit'), $this->mini_icon('actions/submit').' '.__('Continue')));
	}

	public function output() {
		$this->execute();

		$this->result = preg_replace_callback(
		'/<([^>]+)(action|src|href)="([^\.\/#][^"]*)"([^>]*?)>/',
		create_function('$a', 'return "<".$a[1]." $a[2]=\"".((preg_match("/[a-z]+:/", $a[3])) ? $a[3] : TM_BASE_URL.ltrim($a[3], "/"))."\"".$a[4].">";'),
		$this->result);

		return $this->result;
	}
}
?>
