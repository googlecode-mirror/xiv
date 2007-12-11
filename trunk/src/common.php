<?php

/**
 * Common code from the core
 * ---
 * Written by Jose Carlos Nieto <xiam@astrata.com.mx>
 * Copyright (c) 2007 Jose Carlos Nieto
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @author          Jose Carlos Nieto <xiam@astrata.com.mx>
 * @copyright       Copyright (c) 2007, Jose Carlos Nieto
 * @link            http://opensource.astrata.com.mx Astrata Open Source Projects
 * @version         $Revision$
 * @modifiedby      $Last_changed_by: $
 * @lastmodified    $Date$
 * @license         http://www.opensource.org/licenses/mit-license.php The MIT License
 *
 */

require_once 'constants.php';

// set this to GMT
date_default_timezone_set('America/Los_Angeles');

set_error_handler('tm_error_handler');

function tm_error_handler($errno, $errstr, $errfile, $errline, $errcontext) {
	if (error_reporting()) {
		switch($errno) {
			case 2048:
				// not prepared yet
			break;
			default:
				//debug($errcontext);
				debug('ERROR '.$errno.': '.preg_replace('/<[^>]+>/', '', $errstr).' '.$errfile.':'.$errline.'');
			break;
		}
		if ($errno == E_USER_ERROR) {
			exit(0);
		}
	}
}

function debug($mixed, $level = 0) {
	if ($level < 1) {
		if (defined('TM_CLI')) {
			print_r($mixed);	
		} else {
			$fh = fopen(TM_TEMP_DIR.'debug.log', 'a');
			ob_start();

			print_r($mixed);
			echo "\n";

			$buff = ob_get_clean();

			$buff = preg_replace_callback("/./i", create_function('$a', '$b = ord($a[0]); return ($b >= 32 && $b < 128) ? $a[0] : sprintf(\'\\x%02x\', $b);'), $buff);

			fwrite($fh, trim($buff)."\n");
			fclose($fh);
		}
	}
}

function debug_old($string) {
	if (!defined('TM_CLI')) {
		header("Content-Type: text/plain; charset=utf-8");
		print_r($string);
	} else {
		ob_start();
		print_r($string);
		$buff = ob_get_clean();
		echo preg_replace_callback("/^(\s+)/m", create_function('$a', 'return str_repeat(" ", strlen($a[0])/4*2);'), $buff);
	}
	die;
}

function env($name) {
	// this portion is based on cakephp's env().

	if ($name == 'SCRIPT_NAME') {
	/*
		if (env('CGI_MODE')) {
			$name = 'SCRIPT_URL';
		}
	*/
	}

	switch ($name) {
		case 'WEBROOT':
			if (preg_match('/.*\.php$/', env('SCRIPT_NAME'))) {
				return rtrim(dirname(env('SCRIPT_NAME')), '/').'/';
			} else {
				return env('SCRIPT_NAME');
			}
		break;
		case 'HTTPS':
			return (strpos(env('SCRIPT_URI'), 'https://') === 0);
		break;
		case 'CGI_MODE':
			return (substr(php_sapi_name(), 0, 3) == 'cgi');
		break;
		default:
			return isset($_SERVER[$name]) ? $_SERVER[$name] : null;
		break;
	}
}

function def($a, $b) {
	return $a ? $a : $b;
}

if (!is_writable(TM_TEMP_DIR)) {
	trigger_error("Please make sure \"".TM_TEMP_DIR."\" is writable (chmod 777).", E_USER_ERROR);
}
if (!is_writable(TM_DATA_DIR)) {
	trigger_error("Please make sure \"".TM_DATA_DIR."\" is writable (chmod 777).", E_USER_ERROR);
}

class tm_core extends tm_object {

	protected $loaded_controls;
	protected $loaded_models;
	protected $loaded_actions;
	
	public function __construct(&$params = null) {
		parent::__construct($params);
		$this->param =& $this->using('param');
	}

	public function force_exit() {
		$this->disable_debug();
		exit(0);
	}

	public function disable_debug() {
		define('TM_NO_DEBUG', true);
	}

	public function is_editor() {
		$auth =& $this->using('auth');
		return $auth->is_editor($this->module_name);
	}

	public function load_model($module) {
		$file = TM_MODULES_DIR.$module.'/model.php';
		if (file_exists($file)) {
			require_once $file;
			$model_class = $module.'_model';
			if (class_exists($model_class)) {
				if (!isset($this->loaded_models[$model_class])) {
					$this->loaded_models[$model_class] = new $model_class();
				}
				return $this->loaded_models[$model_class];
			}
		}
		return null;
	}

	public function load_control($module) {
		$file = TM_MODULES_DIR.$module.'/control.php';
		if (file_exists($file)) {
			require_once $file;
			$control_class = $module.'_control';
			if (class_exists($control_class)) {
				if (!isset($this->loaded_controls[$control_class])) {
					$this->loaded_controls[$control_class] = new $control_class();
				}
				return $this->loaded_controls[$control_class];
			}
		}
		return null;
	}

	public function load_action($module) {
		$file = TM_MODULES_DIR.$module.'/action.php';
		if (file_exists($file)) {
			require_once $file;
			$control_class = $module.'_action';
			if (class_exists($control_class)) {
				if (!isset($this->loaded_controls[$control_class])) {
					$this->loaded_controls[$control_class] = new $control_class();
				}
				return $this->loaded_controls[$control_class];
			}
		}
		return null;
	}

	protected function is_callable($action) {
		// this works with php 5.2
		if (substr($action, 0, 1) != '_') {
			$class = $orig_class = get_class($this);
			$methods = array();
			while ($class) {
				$t = get_class_methods($class);
				$m =& $methods[$class];
				foreach ($t as $a) {
					$m[$a] = true; 
				}
				$class = get_parent_class($class);
			}
			$remove = false;
			reset($methods);
			while (list($i) = each($methods)) {
				if ($remove) {
					reset($methods[$i]);
					while(list($j) = each($methods[$i])) {
						unset($methods[$orig_class][$j]);
					}
					break;
				}
				if ($i == 'tm_control') {
					$remove = true;
				}
			}
			return isset($methods[$orig_class][$action]);
		}
	}

	protected function dispatch_action(&$action) {

		extract(
			$this->using(
				'auth',
				'env',
				'param'
			)
		);

		if (!$action) { 
			$action = 'index';
		}

		$new_action = null;
		
		if (defined('TM_ADMIN_MODE') && TM_ADMIN_MODE) {
			// Administrator functions
			if (!$new_action && $this->is_callable("admin_{$action}")) {
				if ($auth->is_operator()) {
					$new_action = "admin_{$action}";
				}
			}
		} else {
			// User functions
			if (!$new_action && $this->is_callable("user_{$action}")) {
				if ($auth->is_user()) {
					$new_action = "user_{$action}";
				}
			}
		}

		// Default functions
		if (!$new_action) {
			if ($this->is_callable($action)) {
				$new_action = $action;
			} else {
				if (method_exists($this, 'catch_all')) {
					$new_action = 'catch_all';
				} else {
					if ($this->is_callable("user_{$action}") || $this->is_callable("operator_{$action}") || $this->is_callable("admin_{$action}")) {
						$env->login(__('Access denied.'));
					} else {
						$env->fatal_error(__("Couldn't access function '{$action}()' in module '{$this->module_name}'."));
					}
				}
			}
		}

		$action = $new_action;

		// Permissions check
		if (defined('TM_ADMIN_MODE') && TM_ADMIN_MODE) {
			if (!empty($this->model->permissions)) {
				foreach($this->model->permissions as $name => $methods) {
					foreach ($methods as $method) {
						if ($method == $action) {
							if ($auth->allow($this->module_name, $name)) {
								return $action;
							}
							$env->access_denied();
						}
					}
				}
			} 
		} else {
			// No admin
			return $action;
		}

		return $action;

		$env->access_denied();
	}

	protected function component($component, $args = null) {
		$class_name = "{$component}_component";
		require_once TM_COMPONENT_DIR."{$component}/{$component}.php";
		$class_object = new $class_name($args);	
		$class_object->parent =& $this;
		return $class_object;
	}

}

class tm_control_parent extends tm_core {
	
	public $custom_param = false;
	public $layout = 'index';
	public $render_stopped = false;
	private $feeds = array();
	
	public function stop_render() {
		$this->render_stopped = true;
	}

	public function __construct(&$params = null) {
		parent::__construct($params);
		$this->module_name = preg_replace('/^(.+)_control$/', '\1', get_class($this));

		if (!defined('C_TEMPLATE')) {
			define('C_TEMPLATE', 'default');
		}

		$this->template_root = TM_TEMPLATES_DIR.C_TEMPLATE.'/layout/views/'.$this->module_name.'/';

		$this->model =& $this->load_model($this->module_name);
		$this->render_stopped = false;
	}

	public function set_selected_modules($modules) {
		$param =& $this->using('param');
		$ret = array();
		foreach($modules as $module) {
			$ret[] = array(
				'modules' => array(
					'module_name' => $module
				)
			);
		}
		$param->set('modules', $ret);
	}

	public function set_selected_groups($groups) {

		$db =& $this->using('db');

		extract(
			$this->using(
				'db',
				'param'
			)
		);

		if (!is_array($groups))
			$groups = array($groups);

		$c = count($groups);
		for ($i = 0; $i < $c; $i++) {
			$cond[] = 'name = ?';
		}
		$cond = implode(' or ', $cond);

		$args[] = $cond;
		$args = array_merge($args, $groups);

		// default selection
		$groups = $db->find_all(
			array(
				'groups' => array(
					'table' => 'groups',
					'fields' => array(
						'id' => 'group_id'
					),
					'where' => call_user_func_array(array(&$db, 'bind'), $args)
				)
			)
		);

		if ($groups) {
			$param->set('groups', $groups);
		} else {
			$param->set('groups', array());
		}
	}
	
	public function set_system_groups() {
		extract(
			$this->using(
				'db',
				'param'
			)
		);

		$system_groups = $db->find_all(
			array(
				'group' => array(
					'model' => 'groups',
					'fields' => array('id', 'name'),
					'order_by' => 'id ASC'
				)
			)
		);

		$param->set('system_groups', $system_groups);
	}

	public function set_system_modules() {
		extract(
			$this->using(
				'env',
				'param'
			)
		);
		$modules = $env->get_modules();
		$system_modules = array(
			array(
				'module' => array('name' => 'all')
			)
		);
		foreach($modules as $m) {
			$system_modules[] = array(
				'module' => array('name' => $m)
			);
		}
		$param->set('system_modules', $system_modules);
	}

	public function add_feed($url) {
		$param =& $this->using('param');
		$this->using('template');
		$this->feeds[] = $param->create($url);
		template::set_global('feeds', $this->feeds, false);	
	}

	public function before_render() {
	
	}

	public function after_render() {
	
	}

	public function render_action($action) {
		$this->action = $action;
		$this->{$action}();
		return $this->render();
	}

	protected function render($action = null, $overwrite = false) {

		if ($this->render_stopped) {
			return ' ';
		}

		if (!$action) {
			$action = $this->action;
		}

		$param =& $this->using('param');

		if ($overwrite || !isset($this->rendered))
			$this->rendered = '';

		$tpl_file = $this->template_root.$action;

		if (file_exists("{$tpl_file}.tpl") && (!$this->rendered || $overwrite)) {

			// Before render
			if ($this->is_callable('before_render'))
				$this->before_render();

			$tpl = new template();

			$tpl->load($tpl_file);

			$tpl->set($param->get_params(), null, false);

			$this->rendered .= $tpl->output();
		
			// After render
			if ($this->is_callable('after_render')) {
				$this->after_render();
			}
		} else {
			if ($action == 'catch_all') {
				null;
			} else {
				if (!$this->rendered) {
					$env =& $this->using('env');
					$env->fatal_error(__("Template file %s was not found! If you want this to be bypassed and handled in a custom way set \$custom_route = true into your control.", "{$tpl_file}.tpl"));
				}
			}
		}
		return $this->rendered;
	}

	public function exec_action($action = 'index') {

		extract(
			$this->using(
				'param',
				'auth',
				'env'
			)
		);
		
		if (defined('TM_ADMIN_MODE') && TM_ADMIN_MODE == 1) {
			if ($auth->is_user() == false) {
				$env->login(__('Please login first.'));
			}
		}

		$this->dispatch_action($action);

		$this->action = $action;

		// autoloading ajax layout
		if (preg_match('/(^|.*_)ajax_.*/', $action)) {
			header('Content-Type: text/plain; charset=utf-8');
			if (isset($_POST['__ajax']) || isset($_GET['__ajax']) || env('HTTP_X_REQUESTED_WITH')) {
				define('TM_AJAX', true);
			} else {
				if (TM_ENABLE_DEBUG > 0) {
					define('TM_AJAX', true);
				} else {
					$env->redirect('/');
				}
			}
			$this->layout = 'ajax';
		}

		if (defined('TM_ADMIN_MODE') && TM_ADMIN_MODE) {
			$param->base_map = "/module=alpha/base=alpha/action=alpha";
		} else {
			$param->base_map = "/module=alpha/action=alpha";
		}

		// Before call
		if ($this->is_callable('before_execute')) {
			$this->before_execute();
		}

		// Calling this function
		$this->rendered = $this->$action();
	
		// After call
		if ($this->is_callable('after_execute')) {
			$this->after_execute();
		}

		return $this->render($action);
	}

	public function add_actions(&$items, $actions = null) {

		if (!$actions)
			$actions = array();

		if (!isset($actions['edit']))
			$actions['edit'] = 'edit';

		if (!isset($actions['delete']))
			$actions['delete'] = 'delete';

		if (is_array($items)) {
			reset($items);
			while (list($i) = each($items)) {
				$items[$i]['_actions'] = array(
					'edit' => "/module=admin/base=".$this->module()."/action={$actions['edit']}/id={$items[$i]['id']}",
					'delete' => "confirm:/module=admin/base=".$this->module()."/action={$actions['delete']}/id={$items[$i]['id']}?auth=".TM_ACTION_AUTH
				);
			}
		}
	}
}

class tm_action_parent extends tm_core {
	
	public function __construct(&$params = null) {
		parent::__construct($params);
		$this->module_name = preg_replace('/^(.+)_action$/', '\1', get_class($this));
		$this->model =& $this->load_model($this->module_name);
	}

	public function crop_image($file, $size = '200') {
		$this->using('image');
		$img = new image();
		if ($img->load($file)) {
			$img->thumbnail($size);	
			$img->save();
		}
	}

	public function accept_upload($name, $dest, $extensions = '.*', $maxsize = -1) {

		extract(
			$this->using(
				'param',
				'env',
				'json'
			)
		);

		$dest = TM_DATA_DIR.$dest.'/';

		@mkdir($dest, 0777);

		if (is_writable($dest)) {
			$thumb = $param->upload($name, $dest);
			if (!$thumb) {
				return false;
			}
		} else {
			$env->fatal_error(__('Directory %s is not writable.', $dest));
		}

		if (!empty($thumb[0]['name'])) {
			if (preg_match('/\.('.$extensions.')$/i', $thumb[0]['name'])) {
				$filesize = ceil(filesize($thumb[0]['path'])/1024);
				if ($filesize < $maxsize || $maxsize < 0) {
					return $thumb[0];
				} else {
					$json->response(
						array(
							'errorMessage' => __('Your file is too large, please try again with a smaller one (less than %s).', ini_get('max_upload_filesize'))
						)
					);
				}
			} else {
				@unlink($thumb[0]['path']);
				$json->response(
					array(
						'errorMessage' => __('Couldn\'t upload that type of file.')
					)
				);
			}
		} else {
			$json->response(
				array(
					'errorMessage' => __('Your file is too large, please try again with a smaller one (less than %s).', ini_get('max_upload_filesize'))
				)
			);
		}
	}

	public function exec_action($action) {

		extract(
			$this->using(
				'param',
				'env'
			)
		);

		$this->dispatch_action($action);
		$this->action = $action;

		$action_class = "{$this->module_name}_action";

		if ($this->is_callable($action)) {

			if (defined('TM_ADMIN_MODE') && TM_ADMIN_MODE) {
				$param->base_map = "/module=alpha/base=alpha/action=alpha";
			} else {
				$param->base_map = "/module=alpha/action=alpha";
			}

			if ($this->is_callable('before_execute')) {
				$this->before_execute();
			}

			$this->$action();

			if ($this->is_callable('after_execute')) {
				$this->after_execute();
			}

			$param->map('?return=string');

			/*
			if ($param->get('return')) {
				$env->redirect($param->get('return'), false);
			} elseif ($param->server('HTTP_REFERER')) {
				$env->redirect($param->server('HTTP_REFERER'), false);
			} else {
				$env->redirect('/');
			}
			*/

		}
	}
}

class tm_block_parent extends tm_core {
	public $cacheable = false;
	public $cache_life = 3600;
	public $params = array();

	public function __construct(&$params = null) {
		parent::__construct($params);
	}

	public function param($param) {
		return (isset($this->params[$param]) ? $this->params[$param] : null);
	}

	protected function render($overwrite = false) {

		$param =& $this->using('param');

		if ($overwrite || !isset($this->rendered))
			$this->rendered = '';

		if (file_exists($this->template_file.'.tpl')) {
			$tpl = new template();

			$tpl->load($this->template_file);

			$tpl->set($param->get_params(), null, false);

			$this->rendered .= $tpl->output();
		} else {
			trigger_error('Block template \''.$this->template_file.'\' is missing.', E_USER_ERROR);
		}
		return $this->rendered;
	}
}

class tm_plugin_parent extends tm_core {

}

class tm_component_parent extends tm_object {

}

//

class tm_action extends tm_action_parent {
	public function before_execute() {
	
	}
	public function after_execute() {
	
	}
}

class tm_control extends tm_control_parent {
	public function before_execute() {
	
	}
	public function after_execute() {
	
	}
}

class tm_model_parent extends tm_core {

	public $permissions = array(
		'read' => array(
			'admin_ajax_index',
			'admin_index'
		),
		'write' => array(
			'admin_ajax_create'
		),
		'edit' => array(
			'admin_ajax_edit'
		),
		'delete' => array(
			'admin_ajax_delete'
		)
	);
	
	public function __construct(&$params = null) {
		parent::__construct($params);
		$this->module_name = preg_replace('/^(.+)_model$/', '\1', get_class($this));
	}

}

class tm_model extends tm_model_parent {

}

class tm_block extends tm_block_parent {
}

class tm_plugin extends tm_plugin_parent {

}
class tm_component extends tm_component_parent {

}

?>
