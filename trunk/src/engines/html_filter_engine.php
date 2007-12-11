<?php

/**
 * HTML Filter Engine
 * Formats HTML and strips out possible harmful code based on a privileges
 * list.
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

class html_filter_engine extends simple_parser_engine {
	

	var $return_escaped_tag = true;

	/**
	 * We don't need a scape character.
	 * @var string
	 */
	var $escape_char = null;
	
	/**
	 * No need for recursion (since there is no recursion for HTML)
	 * @var boolean
	 */
	var $use_stack = false;

	/**
	 * Strings to delimiter tags to evaluate
	 * @var array
	 */
	var $delimiters = array(
		array('<!--', '-->'),
		array('<', '>')
	);

	/**
	 * Filter security level
	 * @var int
	 */
	var $filter_level = 0;

	/**
	 * Allowed tags for each security level (regexp)
	 * @var array
	 */
	var $allowed_tags_by_level = array(
		0 => 'a|b|u|i|s|br|code|pre|strong|strike|em|p|span|div|blockquote',
		1 => 'a|b|u|i|s|br|em|hr|sub|sup|small|strong|pre|code|p|strike|img|span|div|blockquote',
		2 => 'a|b|u|i|s|br|em|hr|div|span|ul|ol|li|pre|code|table|tr|td|sub|strong|strike|sup|small|strong|strike|img|embed|object|h[0-6]|p|param|blockquote',
		3 => '\w*'
	);
	
	// TODO
	function safe_url($url) {
		return $url;
	}


	/**
	 * Allowed attributes for each security level (regexp)
	 * @var array
	 */
	var $allowed_attr_by_level = array(
		0 => 'style|href',
		1 => 'style|href|src',
		2 => 'style|href|src|class',
		3 => '\w*'
	);

	/**
	 * Allowed style definitions for each security level (regexp)
	 * @var array
	 */
	var $allowed_style_by_level = array(
		0 => 'font-weight|text-decoration|font-style',
		1 => 'color|background|font-\w*|list-\w*|text-\w*',
		2 => 'color|background|font-\w*|list-\w*|text-\w*',
		3 => '[\w\-]*'
	);

	/**
	 * Banned style definitions for each security level (regexp)
	 * @var array
	 */
	var $banned_style_values_by_level = array(
		0 => "url.*\(.*|expression",
		1 => "url.*\(.*|expression",
		2 => "url.*\(.*|expression",
		3 => null
	);

	/**
	 * Separes HTML attributes into an array
	 * @param string $attr HTML tag attribute
	 * @returns array of well formatted attributes (an empty array if an error occurrs)
	 */
	function split_attributes($attr) {

		$attr = trim($attr, " /");

		$attr_arr = array();

		$key = $val = null;
		$step = 0;

		for ($i = 0; isset($attr[$i]); ) {

			while (isset($attr[$i]) && (preg_match("/[\s\n\r\t]/", $attr[$i])))
				$i++;

			switch ($step) {
				case 0:
					while (isset($attr[$i]) && $step == 0) {
						if (preg_match("/[a-zA-Z0-9]/", $attr[$i])) {
							$key .= $attr[$i];
						} else {
							if ($key && $attr[$i] == '=') {
								$step = 1;
							} elseif ($attr[$i] != ' ')
								return array(); // malformed
						}
						$i++;
					}
				break;
				case 1:

					$enclose = $attr[$i++];

					if ($enclose == '"' || $enclose == "'") {

						while (isset($attr[$i]) && $attr[$i] != $enclose) {

							$val .= $attr[$i];

							if (isset($attr[$i+1]) && $attr[++$i] == '\\') {
								if (isset($attr[++$i])) {
									if (isset($attr[$i+1]))
										$val .= $attr[$i++];
									elseif ($attr[$i] == $enclose)
										break;
								} else {
									return array(); // expecting character
								}
							}
						}

						if ($attr[$i] == $enclose) {
							$attr_arr[$key] = $val;
							$key = $val = null;
							$step = 0;
						}
						$i++;
					} else if (preg_match("/[a-zA-Z0-9]/", $enclose)) {
						$val .= $enclose; // string

						for (; isset($attr[$i]) && $step == 1 && preg_match("/[a-zA-Z0-9]/", $attr[$i]); $i++)
							$val .= $attr[$i];

						if ($val) {
							$attr_arr[$key] = $val;
							$key = $val = null;
							$step = 0;
						} else
							return array();

					} else
						return array(); // malformed string
				break;
			}
		}
		return $attr_arr;
	}
	

	/**
	 * Translates an array of attributes into a HTML string
	 * @param array attributes
	 * @returns string 
	 */
	function join_attributes($attr) {
		$buff = Array();
		if (is_array($attr))
			foreach ($attr as $a => $v)
				$buff[] = "$a=\"".str_replace("\"", "\\\"", $v)."\"";
		return implode(' ', $buff);
	}

	/**
	 * Checks all data inside a tag against the global rules.
	 * @param string $tag HTML tag definition
	 * @param array $e tag delimiters
	 * @returns false if an error occurrs
	 */
	function eval_enclosed($tag, $e) {
		if (!isset($this->stack))
			$this->stack = array();
		switch ($e[0]) {
			case '<':
				$tag = preg_replace('/[\n\r\t]/', ' ', $tag);

				preg_match('/<([^\s]+)\s*(.*?)>/s', $tag, $buff);		
			
				if (isset($buff[2]))
					$buff[2] = rtrim($buff[2], ' /');

				if (isset($buff[1])) {
					$node = strtolower($buff[1]);
					if (preg_match("/^\/?({$this->allowed_tags_by_level[$this->filter_level]})$/", $node)) {
						$attr = null;
						if ($node[0] == '/') {
							// closing tag
							// this is a closing tags, making sure it is closing something that is already open
							if ($this->stack) {
								$element = null;
								$nodename = substr($node, 1);
								while ($this->stack) {
									$pop = array_pop($this->stack);
									if ($pop == $nodename)
										break;
									else if (!$this->is_single_tag($pop)) {
										// attemping to close a tag that is not open, or is in another nest level
										array_push($this->stack, $pop);
										return htmlspecialchars($tag);
									}
								}
							} else {
								// there is nothing to close!
								return htmlspecialchars($tag);
							}
						} else {
							// opening tag

							array_push($this->stack, $node);

							$attr = $this->split_attributes($buff[2]);
							if ($attr) {
								while (list($name) = each($attr)) 
									if (!preg_match("/^({$this->allowed_attr_by_level[$this->filter_level]})$/", $name))
										unset($attr[$name]);	
							}
						
							if (isset($attr['style'])) {
								$valid = array();
								$es = explode(';', $attr['style']);
								foreach ($es as $e) {
									preg_match("/\s*([a-z\-]*)\s*:\s*(.*)\s*/", $e, $m);
									if (isset($m[2])) {
										list($m, $p, $v) = $m;
										$p = strtolower($p);
										if (preg_match("/{$this->allowed_style_by_level[$this->filter_level]}/", $p)) {
											$valid[] = "$p: $v";
										}
									}
								}
								$attr['style'] = implode('; ', $valid);
								if (!$attr['style'])
									unset($attr['style']);
							}

							if (isset($attr['src']))
								$attr['src'] = $this->safe_url($attr['src']);
							
							if (isset($attr['href']))
								$attr['href'] = $this->safe_url($attr['href']);

							$attr = $this->join_attributes($attr);
						}

						return "<{$node}".($attr ? " $attr" : null).($this->is_single_tag($node) ? ' /' : '').">";
					} else {
						return $this->return_escaped_tag ? htmlspecialchars($tag) : '';
					}
				}
				return $tag;
			break;
			case '<!--':
				return $this->filter_level > 1 ? $tag : null;
			break;
		}
	}

	/**
	 * Determines wheter an HTML tag is single or not
	 * @param string $node HTML tag name (lowercased)
	 * @returns true if $node is a single tag
	 */
	function is_single_tag($node) {
		return preg_match('/^(img|br|hr|input)$/', $node);
	}

	/**
	 * Runs the filter agains a text
	 * @param string &$text Reference to a text to evaluate
	 * @returns string contained a sanitized version of $text
	 */
	function filter(&$text) {
		$this->stack = array();
		$copy = $text;
		$success = $this->parse($text);
		if ($success) {
			while ($this->stack) {
				$unclosed = array_pop($this->stack);
				if (!$this->is_single_tag($unclosed))
					$text .= "</$unclosed>";
			}
		} else {
			$text = htmlspecialchars($copy);
		}
		return $text;
	}
}
?>
