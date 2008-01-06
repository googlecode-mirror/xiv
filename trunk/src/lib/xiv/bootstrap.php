<?php
/**
 * textMotion bootstrap
 * ---
 * Written by Jose Carlos Nieto <xiam@menteslibres.org>
 * Copyright (c) 2007 Jose Carlos Nieto
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @author          Jose Carlos Nieto <xiam@menteslibres.org>
 * @copyright       (c) 2007-2008, Jose Carlos Nieto
 * @link            http://www.textmotion.org
 * @version         $Revision$
 * @modifiedby      $LastChangedBy$
 * @lastmodified    $Date$
 * @license         http://www.opensource.org/licenses/mit-license.php The MIT License
 *
 */

chdir(dirname(__FILE__).'/../');
require 'object.php';
require 'common.php';

$gettext = null;

function locale($locale) {
	global $gettext;
	if (!defined('TM_LOCALE')) {

		putenv('LC_ALL='.$locale);
		setlocale(LC_ALL, $locale);

		require_once TM_LIB_DIR.'third_party'.DS.'gettext'.DS.'gettext.php';
		require_once TM_LIB_DIR.'stream.php';

		$mofile = TM_LOCALE_DIR.$locale.DS.'LC_MESSAGES'.DS.'messages.mo';

		$stream = new stream();
		if ($stream->open($mofile)) {
			$gettext = new gettext_reader($stream);
			define('TM_LOCALE', $locale);
		} else {
			return false;
		}
	}
	return TM_LOCALE;
}

function __($e) {
	global $gettext;
	$args = func_get_args();
	if ($gettext) {
		$args[0] = $gettext->translate($args[0]);
	}
	return isset($args[1]) ? call_user_func_array('sprintf', $args) : $args[0];
}

// TODO: This should be dynamically guessed
if (!defined('TM_WEBROOT')) {
	define('TM_WEBROOT', env('WEBROOT'));
}

if (!defined('TM_CLI')) {
	define('TM_BASE_URL', (env('HTTPS') ? 'https': 'http').'://'.TM_HOST.TM_WEBROOT);
	if (substr(env('REQUEST_URI'), 0, strlen(env('SCRIPT_NAME'))) == env('SCRIPT_NAME')) {
		header('Location: '.TM_BASE_URL.(env('QUERY_STRING') ? '?'.env('QUERY_STRING') : '').'');
	}
}

if (!defined('TM_MEDIA_WEBROOT')) {
	define('TM_MEDIA_WEBROOT', TM_WEBROOT.'media/');
}
?>
