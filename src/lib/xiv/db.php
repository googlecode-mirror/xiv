<?php
/**
 * db
 * ---
 * Written by Jose Carlos Nieto <xiam@menteslibres.org>
 * Copyright (c) 2007 textMotion Developers
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @author          Jose Carlos Nieto <xiam@menteslibres.org>
 * @copyright       Copyright (c) 2007-2008, textMotion Developers
 * @link            http://www.textmotion.org
 * @version         $Revision$
 * @modifiedby      $LastChangedBy$
 * @lastmodified    $Date$
 * @license         http://www.opensource.org/licenses/mit-license.php The MIT License
 *
 */

require "lib/engines/database_engine.php";
require "conf/database.php";

class db extends database {

	var $db = null;

	function __construct($use = 'default') {
		if (!defined('TM_STATIC')) {
			require_once "databases/{$this->config[$use]['driver']}.php";
			$class_name = "{$this->config[$use]['driver']}_driver";
			$this->db =& new $class_name;
			while (list($i) = each($this->config[$use]))
				$this->db->$i = $this->config[$use][$i];
			if ($this->db->open() == false) {
				debug($this->error($this->db->error()));
			}
		}
	}

	function __call($func, $args) {
		return call_user_func_array(array(&$this->db, $func), $args);
	}
}
?>
