<?php
/**
 * URL handler
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

class url_handler extends tm_object {
	
	var $pretty_urls = true;

	var $base_script = '/index.php';

	public function __construct(&$params = null) {
		parent::__construct($params);
	}

	public function set_arg($args, $base = null) {
		if (!$base)
			$base = $_SERVER["REQUEST_URI"];

		$base_script = null;
		$orig_args = array();

		if (preg_match("/(.*)\?(.*)/", $base, $m) && isset($m[2])) {
			$base_script = $m[1];
			$tmp = explode('&', $m[2]);
			foreach ($tmp as $t) {
				list($var, $val) = explode('=', $t);
				$orig_args[$var] = $val;
			}
		}

		if (!$base_script)	
			$base_script = $base;
		
		$tmp = explode('&', $args);
		foreach ($tmp as $t) {
			list($var, $val) = explode('=', $t);
			$orig_args[$var] = $val;
		}

		$a = array();
		foreach ($orig_args as $var => $val) 
			$a[] = "{$var}={$val}";

		return $base_script.($a ? "?".implode('&', $a): null);
	}

	public function map($map) {

		$param = array();

		$map = trim($map, '/');

		// separing route from arguments
		preg_match('/^([^?]+)?\??(.+)?$/', $map, $match);

		// getting variables inside map
		$m = array();
		preg_match_all('/([^=]+)=([^\/]+)\/?/', isset($match[1]) ? $match[1] : null, $m[]);
		preg_match_all('/([^=]+)=([^&]+)&?/', isset($match[2]) ? $match[2] : null, $m[]);
	
		$path = explode('/', trim(isset($_GET['route']) ? $_GET['route'] : null, '/')); 

		foreach ($m as $j => $n) {
			foreach ($n[1] as $i => $v) {
				$name =& $n[1][$i];
				$type =& $n[2][$i];
				if ($type == 'trailing') {
					$tmp = array();
					for ($k = $i; isset($path[$k]); $k++)
						$tmp[] = $path[$k];
					$param[$name] = implode('/', $tmp);
					break(2);
				} else {
					$value = null;
					if (isset($_GET[$name])) {
						$value =& $_GET[$name];
					}
					if ($j == 0) {
						if (!empty($path[$i])) {
							$value = $path[$i];
						}
					}
					$this->type_cast($value, $type);
				}
				$param[$name] = $value;
			}
		}
		return $param;
	}
	static function type_cast(&$v, $type = 'string') {
		switch ($type) {
			case 'alpha':
				$v = preg_replace('/[^a-z0-9A-Z_]/', '', $v);
			break;
			case 'int':
				$v = intval(preg_replace('/[^0-9]/', '', $v));
			break;
			case 'boolean':
				$v = ($v);
			break;
			default:
				$v = "{$v}";
			break;
		}	
	}
	function create($prototype, $urlencode = true) {

		if (preg_match('/^[a-z]+:\/\/.*/', $prototype)) {
			return $prototype;	
		} else {
		
			$map = trim($prototype, '/');

			preg_match('/^([^?]+)?\??(.+)?$/', $map, $match);

			$m = array();
			preg_match_all('/([^=]+)(=?)([^\/]+)\/?/', isset($match[1]) ? $match[1] : null, $path);
			preg_match_all('/([^=]+)=([^&]+)&?/', isset($match[2]) ? $match[2] : null, $param);

			$final_path = $final_param = array();

			foreach ($path[1] as $i => $name) {
				if ($path[2][$i] == '=') {
					$value = $urlencode ? urlencode($path[3][$i]) : $path[3][$i];
					if ($this->pretty_urls) {
						$final_path[] = $value;
					} else {
						$final_param[] = "{$name}={$value}";
					}
				} else {
					$final_path[] = $path[0][$i];
				}
			}

			foreach ($param[1] as $i => $name) {
				$value = $urlencode ? urlencode($param[2][$i]) : $param[2][$i];
				$final_param[] = "{$name}={$value}";
			}

			if ($this->pretty_urls) {
				$link = $this->base_path.implode('/', $final_path).($final_param ? '?'.implode('&', $final_param) : null);
			} else {
				$link = $this->base_script.($final_param ? '?'.implode('&', $final_param): null);
			}
			$link = str_replace('%23', '#', $link);
			return $link;
		}
	}

	function run_test() {
		$_GET['route'] = "something/lost/and/neverseen";

		$tests = array(
			'/module=alpha/base=alpha?explicit=int&var=int',
			'/module=alpha/base=alpha',
			'?explicit=int&var=int'
		);

		foreach ($tests as $test) {
			$url = new url_handler();
			$params = $url->map($test);
			print_r($params);
		}

		echo $url->create('/module=something/base=lost?explicit=7&var=9').'<br />';
		echo $url->create('/module=something/base=lost').'<br />';
		echo $url->create('?explicit=7&var=9').'<br />';
	}
}
//url_handler::run_test();
?>
