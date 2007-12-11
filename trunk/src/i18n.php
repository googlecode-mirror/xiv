<?php
class i18n extends tm_object {
	public function __construct(&$params = null) {
		parent::__construct(&$params);
		$conf =& $this->using('conf');
		$lang = $conf->get('core/locale', 'en', 's');
		locale($lang);
	}
}
?>
