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


require_once TM_MODULES_DIR.'blocks/lib/block_info_parser.php';

class widget extends tm_object {

	public function __construct(&$params = null) {
		parent::__construct($params);
	}
	public function load($__name, $__vars = null) {
		
		$__script = explode('.', $__name);

		$__file = TM_MODULES_DIR."{$__script[0]}/widgets/{$__script[1]}.php";

		$__class_name = "{$__script[0]}_{$__script[1]}_block";

		if (!is_array($__vars)) {
			$__vars = array();
		}

		$__xml = new block_info_parser();
		
		$__xml = $__xml->parse_file($__file);

		if (!empty($__xml['variable'])) {
			foreach ($__xml['variable'] as $__k => $__v) {
				if (!isset($__vars[$__k])) {
					$__vars[$__k] = $__v['default_value'];
				}
			}
		}

		if ($__vars) {
			extract($__vars, EXTR_SKIP);
		}

		$__block = array('block' => array());
		foreach($this->parent as $k => $v) {
			$__block['block'][$k] = $v;
		}
		extract($__block, EXTR_SKIP);

		ob_start();
		if (!class_exists($__class_name)) {
			if (file_exists($__file)) 
				include $__file;
			else
				trigger_error(__('Block script does not exists.'), E_USER_ERROR);
		}

		if (class_exists($__class_name)) {
			$__class_object = new $__class_name;
			$__class_object->template_file = TM_TEMPLATES_DIR.C_TEMPLATE.'/layout/views/'.$__script[0].'/block_'.$__script[1].'';
			$__class_object->params = $__vars;

			if ($__class_object->cacheable) {

				$this->using('cache');

				$cache_id = 'block/'.$__class_name.'/'.serialize($__vars);

				$cache = new cache($cache_id, $__class_object->cache_life); 

				$saved = array(
					'properties' => array(),
					'result' => null
				);

				if ($cache->is_valid()) {
					$saved = $cache->get();
				} else {
					$__class_object->param->set($__vars);
					ob_start();
					$__class_object->display();
					$saved['result'] = ob_get_clean();
					if (property_exists($__class_object, 'title')) {
						$saved['properties']['title']  = $__class_object->title;
					}
					$cache->save($saved);
				}
				foreach($saved['properties'] as $prop => $val)  {
					$this->block[$prop] = $val;
				}
				print $saved['result'];
			} else {
				$__class_object->param->set($__vars);
				$__class_object->param->set($__block);
				$__class_object->display();
				$properties = array('title');
				foreach($properties as $prop) {
					if (property_exists($__class_object, $prop)) {
						$this->block[$prop] = $__class_object->{$prop};
					}
				}
			}
		}

		return ob_get_clean();
	}
}
?>
