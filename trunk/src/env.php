<?php
class env extends tm_object {

	function get_modules() {
		$modules = array();
		$dh = opendir(TM_MODULES_DIR);
		while (($f = readdir($dh)) !== false) {
			if ($f{0} != '.' && is_dir(TM_MODULES_DIR.$f))	
				$modules[] = $f;
		}
		closedir($dh);
		return $modules;
	}

	function __construct(&$params = null) {
		parent::__construct($params);
	}
	function exec_time($curr = null) {
		$mtime = preg_replace_callback('/([0-9.]+)\s([0-9.]+)/', create_function('$a', 'return $a[1]+$a[2];'), START_MICROTIME);
		$etime = preg_replace_callback('/([0-9.]+)\s([0-9.]+)/', create_function('$a', 'return $a[1]+$a[2];'), $curr ? $curr : microtime());
		return substr($etime - $mtime, 0, 5);
	}
	function redirect($url, $eval = true) {

		$param =& $this->using('param');
		$url = ($eval ? $param->create($url) : $url);

		if (defined('TM_MAIN_REQUEST') && TM_MAIN_REQUEST == 'action') {
			$json =& $this->using('json');
			$json->response(
				array(
					'redirectTo' => $url
				)
			);
		} else {
			if (env('HTTP_X_REQUESTED_WITH')) {
				die('<script type="text/javascript">location.href = "'.htmlspecialchars($url).'"</script>');
			} else {
				header("Location: {$url}");
				die('<script type="text/javascript">location.href = "'.htmlspecialchars($url).'"</script>');
			}
		}

	}
	function access_denied() {
		$this->login(__('Access denied.'));
		$this->fatal_error(__('Access denied.'));
	}
	// TODO
	function set_message($message) {
		
	}
	function login($error = null) {
		extract(
			$this->using(
				'param',
				'cookie',
				'session'
			)
		);
		$cookie->clear('auth_key');
		$session->clear('auth_salt');
		$this->redirect('/module=users?return='.$param->server('REQUEST_URI').($error ? '&error='.$error : null));
		exit(0);
	}
	function fatal_error($s) {
		$this->error($s, 1);
	}
	function json($data) {
		$json =& $this->using('json');
		$json->response($data);
		die;
	}
	function http_code($num) {
		debug('HTTP code '.$num.' sent.');
		$errmsg = 'Error '.$num;
		switch($num) {
			case '403':
				$errmsg = 'Forbidden';
			break;
			case '404':
				$errmsg = 'Not found';
			break;
		}
		echo '<h1>'.$errmsg.'</h1>';
		die;
	}
	function error($s = null, $level = 0) {
		if (defined('TM_MAIN_REQUEST') && TM_MAIN_REQUEST == 'action') {
			$json =& $this->using('json');
			$json->response(
				array(
					'errorMessage' => $s
				)
			);
		} else {
			debug($s."\n");
			die;
		}
	}
}
?>
