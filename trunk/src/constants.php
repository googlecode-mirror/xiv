<?php
	error_reporting(E_ALL);
	
	define('DS', DIRECTORY_SEPARATOR);
	
	define('TM_SOURCE_DIR', dirname(dirname(__FILE__)).DS);

	// system groups
	define('TM_GROUP_ADMIN', 1);
	define('TM_GROUP_ALL', 2);
	define('TM_GROUP_GUEST', 3);
	define('TM_GROUP_USER', 4);
	define('TM_GROUP_OPERATOR', 5);

	define('TM_CONF_DIR', TM_SOURCE_DIR.'conf'.DS);
	define('TM_MEDIA_DIR', TM_SOURCE_DIR.'media'.DS);
	define('TM_LIB_DIR', TM_SOURCE_DIR.'lib'.DS);
	define('TM_PLUGIN_DIR', TM_SOURCE_DIR.'lib'.DS.'plugins'.DS);
	define('TM_COMPONENT_DIR', TM_LIB_DIR.'components'.DS);
	define('TM_MODULES_DIR', TM_SOURCE_DIR.'modules'.DS);
	define('TM_TEMPLATES_DIR', TM_SOURCE_DIR.'templates'.DS);
	define('TM_LOCALE_DIR', TM_SOURCE_DIR.'locale'.DS);

	if (!defined('TM_USER_DIR')) {
		define('TM_USER_DIR', TM_SOURCE_DIR);
	}

	define('TM_TEMP_DIR', TM_USER_DIR.'temp'.DS);
	define('TM_DATA_DIR', TM_USER_DIR.'data'.DS);

	define('TM_COOKIE_PREFIX', 'tm_');

	define('TM_DEBUG_LEVEL', 2);

	define('TM_VERSION', '0.9');

	define('TM_PRETTY_URLS', isset($_GET['pretty_url']));
	
	
	if (!isset($_SERVER['TERM'])) {
		define('TM_UNIQUE_STR', (isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT']: null).$_SERVER['REMOTE_ADDR']);
		define('IP_ADDR', $_SERVER['REMOTE_ADDR']);
		define('TM_HOST', $_SERVER['SERVER_NAME']);
	} else {
		define('TM_UNIQUE_STR', rand(10000, 99999));
		define('IP_ADDR', TM_UNIQUE_STR);
		define('TM_CLI', true);
	}

?>
