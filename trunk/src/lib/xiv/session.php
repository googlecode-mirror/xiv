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

require_once "handlers/session_handler.php";
class session extends session_handler {
	public function __construct(&$params = null) {
		parent::__construct($params);
		if (!$this->get('auth_key')) {
			$this->set('auth_key', md5(IP_ADDR.microtime().rand(0, 999)));
		}
		define('TM_ACTION_AUTH', $this->get('auth_key'));
	}
	public function set_message($type, $text) {
		if (!isset($_SESSION['messages'][$type])) 
			$_SESSION['messages'][$type] = array();
		$_SESSION['messages'][$type][] = $text;
	}
}
?>
