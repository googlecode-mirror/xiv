<?php
/**
 * Session manager
 * Encripted session manager.
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

require_once "lib/third_party/crypt/blowfish/blowfish.php";

class session_handler extends tm_object {

	public $compress = false;
	public $encryption = true;

	public function __construct(&$params = null) {

		parent::__construct($params);

		extract(
			$this->using(
				'cookie',
				'misc'
			)
		);

		session_set_cookie_params($cookie->lifetime, $cookie->path, $cookie->domain);

		session_start();

		if ($this->encryption) {
			$sess_key = preg_replace('/[^a-zA-Z0-9]/', '', $cookie->get('sess_key'));
		
			if (strlen($sess_key) != 12) {
				$cookie->set('sess_key', $sess = $misc->random_string(12));
			}

			if (strlen($sess_key) == 12 && isset($_SESSION['data']) && isset($_SESSION['pass']) && $_SESSION['pass'] == md5(TM_UNIQUE_STR)) {
				$bf = new Crypt_Blowfish($sess_key);	
				$data = @unserialize((function_exists('gzuncompress') && $this->compress)? @gzuncompress($bf->decrypt($_SESSION['data'])) : $bf->decrypt($_SESSION['data']));
				$_SESSION = $data ? $data : array();
			} else {
				$_SESSION = array();
			}
		}
	}
	public function clear($name) {
		unset($_SESSION[$name]);
	}
	public function reset() {
		$_SESSION = array();
	}
	public function __destruct() {
		if (!defined('TM_SESSION_SAVED')) {

			$cookie =& $this->using('cookie');

			if ($this->encryption) {
				$sess_key = preg_replace('/[^a-zA-Z0-9]/', '', $cookie->get('sess_key'));

				if (strlen($sess_key) == 12) {
					$bf = new Crypt_Blowfish($sess_key);
					$data = (function_exists('gzcompress') && $this->compress) ? gzcompress(serialize($_SESSION)) : serialize($_SESSION); 
					$_SESSION = array();
					$_SESSION['data'] = $bf->encrypt($data);
					$_SESSION['pass'] = md5(TM_UNIQUE_STR);
				} else {
					$_SESSION = array();
				}
			}

			session_write_close();
			$_SESSION = array();

			define('TM_SESSION_SAVED', true);
		}
	}
	private function get_value($name, &$from) {

		$a =& $from;
		$b = null;
		$name = explode('.', $name);
		while ($name) {
			$n = array_shift($name);
			$b =& $a[$n];
			$a =& $b;
		}
		return isset($a) ? $a : null;
	}
	public function set($name, $value) {
		$_SESSION[$name] = $value;
	}
	public function get($name) {
		$a = $this->get_value($name, $_SESSION);
		if ("$a")
			return $a;
		return null;
	}
	public function run_test() {
		new session();
		if (!isset($_SESSION['i']))
			$_SESSION['i'] = 0;
		$_SESSION['i']++;
		print_r($_SESSION);
	}
}
// session::run_test();
?>
