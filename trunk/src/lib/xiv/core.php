<?php
/**
 * Textmotion
 * ---
 * Written by Jose Carlos Nieto <xiam@menteslibres.org>
 * Copyright (c) 2007 Jose Carlos Nieto <xiam@menteslibres.org>
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @author          Jose Carlos Nieto <xiam@menteslibres.org>
 * @copyright       Copyright (c) 2007-2008, Jose Carlos Nieto <xiam@menteslibres.org>
 * @link            http://www.textmotion.org
 * @version         $Revision$
 * @modifiedby      $LastChangedBy$
 * @lastmodified    $Date$
 * @license         http://www.opensource.org/licenses/mit-license.php The MIT License
 *
 */

chdir(dirname(__FILE__).'/../');

require_once "lib/bootstrap.php";

if (defined('TM_CLI')) {
	echo "\nThe textmotion engine. http://www.textmotion.org\n";
	echo "License: MIT license\n";
	echo "Copyright (c) 2007-2008, J. Carlos Nieto <xiam@menteslibres.org>\n\n";
}

?>
