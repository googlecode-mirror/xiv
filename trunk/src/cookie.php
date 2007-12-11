<?php
require_once "handlers/cookie_handler.php";
class cookie extends cookie_handler {
	function __construct(&$params = null) {
		parent::__construct($params);
		$this->path = TM_WEBROOT;
	}
}
?>
