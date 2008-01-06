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

class panel extends tm_object {

	private $icons;

	private $sections;

	public function add_icon($icon) {
		
		$param =& $this->using('param');

		$section = $icon['section'];
		if (!isset($this->sections[$section])) 
			$this->sections[$section] = array();
		$icon['url'] = $param->create($icon['url']);
		$this->sections[$section][] = $icon;
	}

	private function sort($a, $b) {
		return strcmp($a['title'], $b['title']);
	}

	public function compile() {
		$this->using('template');
		$tpl = new template('panel');
		ksort($this->sections);
		foreach ($this->sections as $i => $v) {
			usort($this->sections[$i], array(&$this, 'sort'));
		}
		$tpl->set('sections', $this->sections ? $this->sections : array());
		return $tpl->output();
	}

	public function create($type) {
		
		$auth =& $this->using('auth');

		$modules = $auth->get_modules();

		$panel = new panel();
		foreach ($modules as $module) {
			$panel_file = TM_MODULES_DIR.$module."/{$type}_panel.php";
			if (file_exists($panel_file)) {
				$icons = array();
				include $panel_file;
				foreach ($icons as $icon)
					$panel->add_icon($icon);
			}
		}
		return $panel->compile();
	}
}
?>