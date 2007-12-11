<?php
/**
 * Cookie manager
 * (Soft) encripted cookie manager.
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

class cookie_handler extends tm_object {
	
	public $path = null;
	public $key = null;
	public $encryption = false;
	public $lifetime = null;
	public $domain = null;

	function __construct(&$params = null) {
		
		parent::__construct($params);

		$this->lifetime = 3600*24*30;

		if ($this->encryption) {
			if (!$this->key) {
				$this->key = md5($_SERVER['REMOTE_ADDR'].$_SERVER['HTTP_USER_AGENT']);
			}
			foreach ($_COOKIE as $i => $v) {
				if ($i != 'PHPSESSID') {
					$v = $this->decrypt(base64_decode($v));
				}
				$_COOKIE[$i] = $v;
			}
		}
	}

	function encrypt($val) {
		$val = "$val";
		$len = strlen($this->key);
		for ($i = 0; isset($val{$i}); $i++) {
			$val{$i} = $val{$i}^$this->key{$i%$len};
		}
		return $val;
	}
	function decrypt($val) {
		return $this->encrypt($val);
	}
	function __destruct() {
		foreach ($_COOKIE as $i => $v) {
			if ($this->encryption) {
				if ($i != 'PHPSESSID') {
					$v = base64_encode($this->encrypt($v));
				}
			}
		 	@setcookie($i, $v, time()+3600, $this->path);
		}
	}
	function clear($name) {
		setcookie($name, '', 0, $this->path);
		unset($_COOKIE[$name]);
	}
	function set($name, $value = null) {
		$_COOKIE[$name] = $value;
	}
	function get($name) {
		return isset($_COOKIE[$name]) ? $_COOKIE[$name] : null;
	}
	function run_test() {
		ob_start();
		new cookie();
		include_once "session.php";
		new session();
		if (!isset($_COOKIE['test']))
			$_COOKIE['test'] = -1;
		$_COOKIE['test'] += 1;

		if (!isset($_SESSION['test']))
			$_SESSION['test'] = -1;
		$_SESSION['test']++;
		
		print_r($_COOKIE);
		print_r($_SESSION);
	}
}
?>
