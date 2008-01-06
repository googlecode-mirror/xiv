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

class sanitization extends tm_object {
	/**
	* Constructor.
	**/
	function __construct(&$parent = null) {
		parent::__construct($parent);
	}
	/**
	* Get rid of those annoying magic quotes
	*/
	function disable_magic_quotes(&$var = null) {
		if ($var === null) {
			self::disable_magic_quotes($_GET);
			self::disable_magic_quotes($_POST);
			self::disable_magic_quotes($_COOKIE);
		} else {
			if (get_magic_quotes_gpc()) {
				if (is_array($var)) {
					reset($var);
					while (list($i) = each($var))
						$this->disable_magic_quotes($var[$i]);
				} else {
					$var = stripslashes($var);
				}
			}
		}
	}
}
?>
