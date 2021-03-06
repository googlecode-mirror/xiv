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

class misc extends tm_object {
	function random_string($len, $charset = "abcdefghijklmnopqrstuvwxyz0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ") {
		$buff = null;
		for ($i = 0; $i < $len; $i++)
			$buff .= $charset[rand(0, strlen($charset)-1)];
		return $buff;
	}
}
?>
