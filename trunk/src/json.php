<?php
/**
 * json parser
 * ---
 * Written by Jorge Medrano <me@h1pp1e.net>
 * Copyright (c) 2007 Jorge Medrano.
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @author          Jorge Medrano <me@h1pp1e.net>
 * @copyright       Copyright (c) 2007, Jorge Medrano
 * @link            http://source.h1pp1e.net Source Projects
 * @version         0.0.1.26082007
 * @license         http://www.opensource.org/licenses/mit-license.php The MIT License
 */

/*
require_once "object.php";
require_once "common.php";
*/

class json extends tm_object {

	public function response($a) {
		if (!defined('TM_AJAX'))
			define('TM_AJAX', true);
		if (!defined('TM_NO_DEBUG'))
			define('TM_NO_DEBUG', true);
		header('Content-Type: text/plain; charset=utf-8');
		print $this->encode($a);
		exit(0);
	}

	/**
	* Converts a PHP variable into a valid JSON string.
	* @access public
	* @param string $var PHP variable
	* @return string JSON string.
	*/
	public function encode($var){
		$parsed = "";
		switch (gettype($var)){
			case "boolean": 
				$parsed = ($var ? "true" : "false");
			break;
			case "double":
			case "float": 
			case "integer": 
				$parsed = $var;
			break;
			case "string": 
				$replacement = json::__get_replacement();
				$parsed = '"';
				$var = str_replace($replacement["find"], $replacement["replace"], $var);
				$parsed .= $var;
				$parsed .= '"';
			break;
			case "NULL": 
				$parsed = "null";
			break;
			case "array": 
				if (!count($var) || array_keys($var) === range(0, count($var)-1)){
					$array = array();
					foreach ($var as $valor) {
						array_push($array, json::encode($valor));
					}
					return "[" . implode(",", $array) . "]";
				} else {
					$array = array();
					foreach ($var as $key => $value) {
						array_push($array, json::encode(strval($key)) . ":" . json::encode($value)); 
					}
					return "{" . implode(",", $array) . "}";
				}
			break;
			case "object":
				null;
			break;
			default:
				$parsed = '';
			break;

		}
		return $parsed;
	}

	/**
	* Replace the special characters for escape character
	* @access private
	* @return array 
	*/
	private static function __get_replacement(){
		static $replacement = array("find" => array(), "replace" => array());
		if ($replacement["find"] == array()) {
			foreach(array_merge(range(0, 7), array(11), range(14, 31)) as $v) {
				$replacement["find"][] = chr($v);
				$replacement["replace"][] = "\\u00".sprintf("%02x", $v);
			}
			$replacement["find"] = array_merge(array(chr(0x5c), chr(0x2F), chr(0x22), chr(0x0d), chr(0x0c), chr(0x0a), chr(0x09), chr(0x08)), $replacement["find"]);
			$replacement["replace"] = array_merge(array('\\\\', '\\/', '\\"', '\r', '\f', '\n', '\t', '\b'), $replacement["replace"]);
		}	
		return $replacement;
	}

	public static function run_test() {
		$array = array(
			'foo' => array(
				'bar',
				'baz' => array(1, 2, 3, 4, 5, 6, 7),
				array(
					'a', 'b', 'c', 'd'
				)
			),
			'bar' => array(5, 4, 3, 2, 1),
			'oop' => 'object oriented programming',
			'baz' => array(
				1,2,3,4,5, array('a', array(1, 2, 3), 'b', 'x' => 'c')
			)
		);
		debug(json::encode($array));
	}
}

//json::run_test();

?>