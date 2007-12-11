<?php
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
