<?php
/**
 * Conf
 * Manages general configuration.
 * ---
 * Copyright (c) 2007 Jose Carlos Nieto <xiam@astrata.com.mx>
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @author          Jose Carlos Nieto <xiam@astrata.com.mx>
 * @copyright       Copyright (c) 2007, Jose Carlos Nieto
 * @link            http://www.textmotion.org
 * @version         $Revision$
 * @modifiedby      $Last_changed_by: $
 * @lastmodified    $Date$
 * @license         http://www.opensource.org/licenses/mit-license.php The MIT License
 *
 */
class conf extends tm_object {
	public $_cache;
	public $static = false;
	
	function __construct(&$params = null) {

		parent::__construct($params);

		$this->_cache =& $GLOBALS['_CONF'];
		$this->_cache = array();

		if (defined('TM_STATIC')) {
			$this->static = true;
		}
		if ($params == 1) {
			$this->static = false;
		}

		if (!$this->static) {

			$db =& $this->using('db');

			$cf = $db->common_select_all('conf', array('class', 'keyname', 'keyvalue', 'keytype'));

			if ($cf) {
				foreach ($cf as $c) {
					$this->_cache["{$c['class']}/{$c['keyname']}"] = $this->cast_type($c['keyvalue'], $c['keytype']);
					if ($c['class'] == 'core') {
						$define = 'C_'.strtoupper($c['keyname']);
						$value =  $this->_cache["{$c["class"]}/{$c["keyname"]}"];
						if (!defined($define))
							define($define, $value);
					}
				}
			}
		}
	}
	function cast_type($str, $type = 's') {
		switch ($type) {
			case 'i':
				return intval($str);
			break;
			case 'b':
				return $str ? true : false;
			break;
			default:
				return "{$str}";
			break;
		}
	}
	function get($name, $default_value = null, $default_type = null, $is_default = false) {
		if (isset($this->_cache[$name])) {
			return $this->_cache[$name];
		} else if ($default_value) {
			$this->set($name, $default_value, $default_type, $is_default);
			return $this->get($name);
		}
		return null;
	}
	function set($name, $value = null, $type = 's', $is_default = false) {
		$db =& $this->db;

		$value = $this->cast_type($value, $type);
		$name = explode('/', $name);
		$class = array_shift($name);
		$name = implode('/', $name);

		if (!$this->static) {
			if (isset($this->_cache[$class.'/'.$name])) {
				$db->update(
					'conf',
					array(
						'keyvalue' => $value,
						'is_default' => $is_default
					),
					$db->bind('class = ? and keyname = ?', $class, $name)
				);
			} else {
				$db->insert(
					'conf',
					array(
						'class' => $class,
						'keyname' => $name,
						'keyvalue' => $value,
						'keytype' => $type,
						'is_default' => $is_default
					)
				);
			}
		}
		$this->_cache["{$class}/{$name}"] = $value;
	}
}
?>
