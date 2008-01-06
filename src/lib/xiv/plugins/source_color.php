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


require_once TM_LIB_DIR."third_party/geshi/geshi.php";

class source_color_plugin extends tm_plugin {
	public function __construct($lang = null) {
		$this->lang = $lang;
	}
	private function lang_alias($lang) {
		switch ($lang) {
			case 'html':
				return 'html4strict';
			break;
		}
		return $lang;
	}
	public function code(&$buff) {
		$geshi = new GeSHi($buff, $this->lang_alias($this->lang));
		$geshi->set_header_type(GESHI_HEADER_PRE);
		$geshi->enable_line_numbers(GESHI_FANCY_LINE_NUMBERS);
    $geshi->code_style = $geshi->line_style1 = '';
		$geshi->keyword_links = false;

		$error = $geshi->error();
		if ($error) {
			debug($error);
		}
		return '<span class="code">'.($error ? nl2br(htmlspecialchars($buff)) : $geshi->parse_code()).'</span>'; 
	}
}
?>
