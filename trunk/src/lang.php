<?php
	define('TM_LANGUAGE_ID', 'en_US');
	class lang {
		function __construct() {
			bindtextdomain('messages', TM_LOCALE_DIR);
			setlocale(LC_ALL, TM_LANGUAGE_ID);
			textdomain('messages');
		}
	}
	new lang();
?>
