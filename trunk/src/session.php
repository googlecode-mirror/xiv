<?php
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
