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

class cli extends tm_object {
	
	public $options = array();

	public function argv($index = -1) {
		global $argv;
		return $index < 0 ? $argv : $argv[$index];
	}
	public function __construct() {
		$argv = $this->argv();
		$count = count($argv);
		for ($i = 1; $i < $count; $i++) {
			$arg = $argv[$i];
			if (preg_match('/--([a-z0-9\-]+)=?(.+)?/', $arg, $match)) {
				$this->options[$match[1]] = isset($match[2]) ? $match[2] : true;
			}
		}
	}
	public function option($index, $default = null) {
		return isset($this->options[$index]) ? $this->options[$index] : $default;
	}
	public function begin($s) {
		$this->out("* $s");
	}
	public function success($s) {
		$this->out("--> $s\n");
	}
	public function out($s) {
		echo "$s\n";
	}
}
?>
