<?php

/**
 * Simple Parser Engine
 * Finds text between given delimiters and evaluates the enclosed buffer.
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

class simple_parser_engine extends tm_object {
	
	var $escape_char = null;
	var $delimiters = array();
	var $use_stack = false;
	var $recursive = true;
	
	function set_delimiters($arr) {
		$this->delimiters = $arr;
	}

	function eval_enclosed($buff, $delimiter) {
		return $buff;
	}

	function parse(&$buff) {

		$stack = array();
		$search_for_index = -1;
		
		for ($i = 0; isset($buff[$i]); $i++) {

			foreach ($this->delimiters as $index => $delimiter) {

				if ($search_for_index == -1 || $search_for_index == $index) {

					if ($search_for_index < 0 && $this->escape_char && $buff[$i] == $this->escape_char) {
						for ($k = 0; $k <= 1; $k++) {
							if (substr($buff, $i + 1, ($j = strlen($delimiter[$k]))) == $delimiter[$k]) {
								$buff = substr($buff, 0, $i).substr($buff, $i + 1);
								$i += $j;
							}
						}
					}

					for ($j = 0; isset($buff[$i+$j]) && isset($delimiter[0][$j]) && $delimiter[0][$j] == $buff[$i+$j]; $j++);
					if (($len = strlen($delimiter[0])) == $j) {
						if (!$stack)
							$search_for_index = $index;
						array_push($stack, $i);
						$i += $j - 1;
						break;
					}
					if ($search_for_index > -1) {

						for ($j = 0; isset($buff[$i+$j]) && isset($delimiter[1][$j]) && $delimiter[1][$j] == $buff[$i+$j]; $j++);

						if (($len = strlen($delimiter[1])) == $j) {

							// TODO
							if (!$stack) {
								trigger_error('Syntax error near ...', E_USER_ERROR);
							}

							$start = array_pop($stack);

							if (substr($buff, $start, strlen($delimiter[0])) == $delimiter[0]) {

								$i += $j - 1;

								if (!$stack) {
									
									$portion = substr($buff, $start, $i - $start + 1);

									if ($this->recursive) {
										$portion = substr($portion, strlen($delimiter[0]), -1*strlen($delimiter[1]));
										$this->parse($portion);
										$portion = $delimiter[0].$portion.$delimiter[1];
									}

									$enclosed = $this->eval_enclosed($portion, $delimiter);
									
									$buff = substr($buff, 0, $start).$enclosed.substr($buff, $i + 1);

									$i = $start + strlen($enclosed) - 1;

									$search_for_index = -1;
								}
								break;
							} else {
								array_push($stack, $start);
							}
						}
						
					}

				}
			}
		}

		if ($stack) { 
			return false;
			trigger_error("Syntax error near '".substr($buff, array_pop($stack), 60)."'", E_USER_ERROR);
		}

		return true;
	}
}
?>
