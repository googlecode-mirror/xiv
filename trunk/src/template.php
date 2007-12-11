<?php

require_once "engines/template_engine.php";

class template extends template_engine {

	public $uses = array('param', 'auth');

	public $enable_cache = true;

	public function file_icon($file) { 
		$archive =& $this->using('archive');
		return $this->icon('mimetypes/'.str_replace('/', '-', $archive->mimetype($archive->extension($file))));
	}

	public function full_date($datetime) {
		$format =& $this->using('format');
		$buff = '
		<span class="date">
			<span class="date-weekday">'.$format->date($datetime, '%A').',</span>
			<span class="date-day">'.$format->date($datetime, '%e').'</span>
			<span class="date-month">'.$format->date($datetime, '%B').'</span>
			<span class="date-hour">'.$format->date($datetime, '%H:%M').'</span>
			<span class="date-year">'.$format->date($datetime, '%Y').'</span>
		</span>
		';
		return $buff;
	} 
	
	public function date($datetime) {
		$format =& $this->using('format');
		return '<span class="date">'.$format->date($datetime).'</span>';
	} 

	public function about($user_id) {
		extract(
			$this->using(
				'format',
				'db'
			)	
		);
		$user = $db->find_one(
			array(
				'user' => array(
					'fields' => 'about',
					'table' => 'users',
					'where' => $db->bind('id = ?', $user_id)
				)
			)
		);
		if ($user) {
			$tpl = '
			<div class="about">
				'.$this->avatar($user_id).'
				<h5>'.$this->user_link($user_id).'</h5>
				'.$format->display($user['user']['about'], $user['id']).'
			</div>
			';
			return $tpl;
		}
	}

	public function widget($title, $name, $vars = null, $class = 'blocks/block') {
		$widget =& $this->using('widget');

		$tpl = new template();
		$tpl->load($class);
		
		$block = array(
			'id'						=> 0,
			'custom_style'	=> '',
			'class'					=> ''
		);
		$block['title'] = $title;
		$block['content'] = $widget->load($name, $vars ? $vars : array());
		
		$tpl->set($block, null, false);

		return $tpl->output();
	}

	public function avatar($user_id, $size = 50) {
		if (preg_match('/^[0-9]+$/', $user_id)) {
			$auth =& $this->using('auth');
			$data = $auth->get_user($user_id);
			return $this->avatar($data ? $data['user']['avatar'] : '', $size);
		} else {
			$size = $size.'x'.$size;
			if (is_file(TM_DATA_DIR.$user_id) || is_file(TM_SOURCE_DIR.$user_id)) {
				return '<span class="avatar-wrapper">'.$this->thumbnail($user_id, $size).'</span>';
			} else {
				return '<span class="avatar-wrapper">'.$this->thumbnail('/media/unknown.png', $size).'</span>';
			}
		}
	}

	public function rpc_link($href, $text, $icon = null, $options = array()) {
		$options = array_merge(array('onclick' => 'return link(this, true);'), $options);
		if ($icon) {
			$text = $this->mini_icon($icon).' '.$text;
		}
		return $this->link($href, $text, $options);
	}

	public function exec_action($url, $text, $icon = null) {
		$param =& $this->using('param');
		return $this->link(null, ($icon ? $this->mini_icon($icon).' ' : null).$text, array('onclick' => 'return exec_action(this)', 'href' => $param->create($url)));	
	}

	public function action($url, $title, $icon = 'action') {
		$param =& $this->using('param');
		return $this->link(null, $this->mini_icon($icon).' '.$title, array('href' => $param->create($url)));	
	}

	public function show_messages() {
		extract(
			$this->using(
				'param',
				'session'
			)
		);
		$errors = array(
			$session->get('error'),
			$param->get('error')
		);
		$buff = array();
		foreach ($errors as $err) {
			if ($err) {
				$buff[] = $this->tag('div', array('class' => 'error'), $err);
			}
		}
		return implode('', $buff);
	}

	public function user_link($id, $only_url = false) {
		extract(
			$this->using(
				'db',
				'param'
			)
		);
		$user = $db->find_one(
			array(
				'user' => array(
					'model' => 'users',
					'fields' => array('username', 'nickname'),
					'where' => $db->bind('id = ?', $id)
				)
			)
		);
		if ($user) {
			if ($only_url) {
				return $param->create('/module=users/action=view/name='.$this->escape($user['user']['username']));
			}
			return $this->link('/module=users/action=view/name='.$this->escape($user['user']['username']), $this->escape($user['user']['nickname']));
		} else {
			return "Anonymous";	
		}
	}
	
	public function safe_link($href, $text) {
		if (preg_match('/^http:\/\/.*$/', $href)) {
			null;
		} else if (preg_match('/^javascript:.*/', $href)) {
			$href = '';
		} else {
			$href = "http://{$href}";
		}
		return $href ? $this->link(null, $this->escape($text), array('href' => $this->escape($href))) : $this->escape($text);
	}

	public function __construct($template = null) {
		parent::__construct();

		if (TM_DEBUG_LEVEL > 1) {
			$this->enable_cache = false;
		}

		$this->cache_dir = TM_TEMP_DIR.'cache/templates/';

		$ds = explode('/', $this->cache_dir);
		$cp = null;
		foreach ($ds as $d) {
			@mkdir($cp .= $d.'/', 0777);
		}

		$this->clean_cache();

		if ($template) {
			$this->load($template);
		}
	}
	
	/**
	 * Executes the template
	 * 
	 */
	public function execute() {
		extract($GLOBALS['_TEMPLATE_GLOBALS'], EXTR_SKIP);
		$this->vars['_auth'] = TM_ACTION_AUTH;
		extract($this->vars, EXTR_SKIP);

		extract($this->using($this->uses));

		ob_start();
		include $this->cache_file;
		$this->result = ob_get_clean();
	}

	public function load($name) {

		if ($name{0} == '/') {
			$path = $name.'.tpl';
		} else if (file_exists(TM_TEMPLATES_DIR.C_TEMPLATE.'/layout/'.$name.'.tpl')) {
			$path = TM_TEMPLATES_DIR.C_TEMPLATE.'/layout/'.$name.'.tpl';	
		} else {
			$path = TM_TEMPLATES_DIR.C_TEMPLATE.'/layout/views/'.$name.'.tpl';
		}

		if (file_exists($path)) {
			$this->load_template($path);
		} else {
			trigger_error('Template \''.$path.'\' is missing.', E_USER_ERROR);
		}
	}

	public function javascript($src) {
		return $this->tag('script', array('src' => TM_WEBROOT.$src, 'type' => 'text/javascript'));
	}

	public function is_single_tag($tag) {
		return preg_match("/input|img|br|hr/i", $tag);
	}

	public function mini_icon($name) {
		return $this->icon($name, 16);
	}

	// TODO
	public function icon($name, $size = 48) {
		$conf =& $this->using('conf');

		$extensions = array('png', 'gif');

		$theme = $conf->get('core/icon_theme', 'default', 's');

		$final = '';

		foreach ($extensions as $ext) {
			$path = "icons/{$theme}/{$size}/{$name}.{$ext}";
			if (file_exists(TM_MEDIA_DIR.$path)) {
				$final = $path;
				break;
			}
		}

		if (!$final) {
			$path = "icons/{$theme}/{$size}/default.png";
			if (file_exists(TM_MEDIA_DIR.$path)) 
				$final = $path;
		}

		if (!$final) {	
			$path = "icons/default/{$size}/default.png";
			if (file_exists(TM_MEDIA_DIR.$path)) 
				$final = $path;
		}
		if ($final) {
			return $this->img(TM_MEDIA_WEBROOT.$path, array('width' => $size, 'height' => $size, 'alt' => $name));
		}
		return '[.]';
	}

	public function ajax_updater($id, $url, $params = null) {
		if (!$params)
			$params = array();
		return "new Ajax.Updater('{$id}', '".app_url($url)."')";
	}

	public function actions($actions) {
		$buff = array();
		foreach ($actions as $icon => $link) {
			
			preg_match('/([a-z0-9A-Z]+):([^$]+)$/', $link, $m);
			if (isset($m[2])) {
				switch ($m[1]) {
					case 'confirm':
						$buff[] = $this->confirm_link($m[2], $this->mini_icon($icon));
					break;
					case 'javascript':
						$buff[] = $this->link(null, $this->mini_icon($icon), array('href' => 'javascript:void(0)', 'onclick' => $m[2]));
					break;
				}
			} else {
				$buff[] = $this->link($link, $this->mini_icon($icon));
			}
		}
		return $this->tag('div', array('class' => 'action-list-inline'), implode(' | ', $buff));
	}

	public function tag($name, $attr_arr = null, $inner_html = null) {
		$attr = array();
		if (!$attr_arr) 
			$attr_arr = array();
		foreach ($attr_arr as $k => $v) {
			if (is_numeric($k))
				$k = $v;
			$attr[] = "{$k}=\"{$v}\"";
		}
		$buff = "<$name";
		$buff .= ($attr ? " ".implode(" ", $attr) : null);
		$buff .= $this->is_single_tag($name) ? " />" : ">{$inner_html}</$name>";
		return $buff;
	}

	public function get_name($name) {
		$part = explode('.', $name);	
		$name = array_shift($part);
		return "{$name}".($part ? '['.implode('][', $part).']' : null);
	}

	public function get_value($name, $from = null, $escape = true) {

		if (!$from)
			$from =& $this->vars;

		$a =& $from;
		$b = null;
		$name = explode('.', $name);
		while ($name) {
			$n = array_shift($name);
			$b =& $a[$n];
			$a =& $b;
		}
		$a = isset($a) ? $a : null;
		return $escape ? $this->escape($a) : $a;
	}

	public function select($name = null, $options = null, $attr = null) {

		unset($value);

		if (!$options)
			$options = array();

		if ($attr && !is_array($attr)) {
			$value = $attr;
			$attr = null;
		}

		if (!$attr)
			$attr = array();

		if ($name) 
			$attr['name'] = $this->get_name($name); 
		
		$buff = array();
		$buff[] = $this->tag('option', null, '');
		if (!isset($value)) {
			$value = $this->get_value($name);
		}
		foreach ($options as $v => $n) {
			$buff[] = $this->tag('option', (strcmp($value, $v) == 0) ? array('selected' => 'selected', 'value' => $v) : array('value' => $v), $n);
		}

		return $this->tag('select', $attr, implode(" ", $buff));
	}
	
	public function link($href, $text = null, $attr = null) {
		
		$param =& $this->using('param');

		if (!$text)
			$text = $href;

		if (!isset($attr['href']))
			$attr['href'] = $param->create($href);

		return $this->tag('a', $attr, $text);
	}
	
	public function confirm_link($href, $text = null, $attr = null) {
		$param =& $this->using('param');

		if (!$text)
			$text = $href;

		$attr['href'] = 'javascript:void(0);';
		$attr['onclick'] = 'javascript:confirm_action(\''.$param->create($href, false).'\');';

		return $this->tag('a', $attr, $text);
	}

	public function thumbnail($file, $maxsize = 100, $attr = null) {

		if (!$attr) {
			$attr = array();
		}
		
		$this->using('image');

		if (!$file) {
			$file = '/media/noimage.png';
		}

		$thumbdir = TM_DATA_DIR.'thumbs/';
		$thumbfile = $thumbdir.$maxsize.'_'.basename($file);

		if (!file_exists($thumbfile)) {
			@mkdir($thumbdir);
			$path = TM_DATA_DIR.$file;
			if (!file_exists($path)) {
				$path = TM_SOURCE_DIR.$file;
			}
			if (is_writable($thumbdir) && file_exists($path) && !is_dir($path)) {
				$img = new image();
				$img->load($path);
				$img->resize($maxsize);
				$img->save($thumbfile);
			}
		} else {
			list($attr['width'], $attr['height']) = getimagesize($thumbfile);
		}

		$thumburl = TM_BASE_URL.'data/'.substr($thumbfile, strlen(TM_DATA_DIR));

		return $this->img($thumburl, $attr);
	}

	public function img($file, $attr = null) {
		if (!$attr) $attr = array();

		if (!isset($attr['alt']))
			$attr['alt'] = basename($file);
		
		if (file_exists(TM_DATA_DIR.$file) && !preg_match('/^\.\./', $file)) {
			$attr['src'] = TM_WEBROOT.'data/'.ltrim($file, '/');
			$size = getimagesize(TM_DATA_DIR.$file);
			$attr['width'] = $size[0];
			$attr['height'] = $size[1];
		} else if (file_exists(TM_SOURCE_DIR.$file) && !preg_match('/^\.\./', $file)) {
			$attr['src'] = TM_BASE_URL.ltrim($file, '/');
			$size = getimagesize(TM_SOURCE_DIR.$file);
			$attr['width'] = $size[0];
			$attr['height'] = $size[1];
		} else {
			$attr['src'] = $file;
		}
		return $this->tag('img', $attr);
	}

	public function file($name = null, $attr = null) {
		if (!$attr) $attr = array();
		if ($name)
			$attr['name'] = $this->get_name($name);
		$attr['type'] = 'file';
		return $this->tag('input', $attr);
	}

	public function input($name = null, $attr = null) {
		if ($attr && !is_array($attr)) {
			$attr = array('value' => $attr);
		}
		if (!$attr) $attr = array();
		
		if ($name) 
			$attr['name'] = $this->get_name($name); 
		if (!isset($attr['type'])) 
			$attr['type'] = 'text';
		if (!isset($attr['value']))
			$attr['value'] = $this->get_value($name);
		return $this->tag('input', $attr);
	}

	public function password($name = null, $attr = null) {
		if (!$attr) $attr = array();
		$attr['type'] = 'password';
		return $this->input($name, $attr);
	}

	public function textarea($name = null, $attr = null, $inner_text = null) {

		if (!$inner_text)
			$inner_text = $this->get_value($name);

		if ($name)
			$attr['name'] = $this->get_name($name);

		/*
		if (!isset($attr['style'])) {
			$attr['style'] = 'width: 350px; height: 100px;';
		}
		*/

		return $this->tag('textarea', $attr, $inner_text);
	}

	public function status($name) {
		return $this->select($name, array('0' => __('Disabled'), '1' => __('Enabled')));
	}

	public function belongs_to($name, $data, $key, $title = null) {
		if (!$title)
			$title = $key;

		$options = array();
		foreach ($data as $d) 
			$options[$this->get_value($key, $d)] = $this->get_value($title, $d);

		return $this->select($name, $options);
	}

	public function ajax_action($href, $text, $icon = null) {
		$param =& $this->using('param');
		$href = $param->create($href);
		return $this->action_link("javascript:ajax_action('{$href}')", $text, $icon);
	}

	public function action_link($href, $text, $icon = null) {
		
		$param =& $this->using('param');

		$prop = array();

		preg_match('/^([^:]*):(.*)$/', $href, $m);
		if ($m) {
			switch($m[1]) {
				case 'confirm':
					$prop['href'] = 'javascript:void(0)';
					$prop['onclick'] = "javascript:confirm_action('".$param->create($m[2])."')";
				break;
				case 'javascript':
					$prop['href'] = 'javascript:void(0)';
					$prop['onclick'] = substr($href, 11);
				break;
			}
		} else {
			$prop['href'] = $param->create($href);
		}
		if (!isset($prop['href']))
			$prop['href'] = $href;

		return $this->link(null, ($icon ? $this->mini_icon($icon).' ' : '').$text, $prop);
	}

	public function action_index_item($base, $id, $postfix = null) {

		extract(
			$this->using(
				'auth',
				'param'
			)
		);

		if ($postfix)
			$postfix = "_{$postfix}";

		$buff = array();

		$module = explode('=', $base);
		$module = array_pop($module);

		if ($auth->allow_delete($module)) {
			$buff[] = $this->tag('li', null, $this->link(null, $this->mini_icon('actions/delete').' '.__('Delete'), array('onclick' => 'return link(this, true)', 'href' => $param->create("{$base}/action=ajax_delete{$postfix}?id={$id}"))));
		}
		if ($auth->allow_edit($module)) {
			$buff[] = $this->tag('li', null, $this->link(null, $this->mini_icon('actions/edit').' '.__('Edit'), array('onclick' => 'return link(this, true)', 'href' => $param->create("{$base}/action=ajax_edit{$postfix}?id={$id}"))));
		}

		return $this->tag('ul', array('class' => 'action-list-inline'), implode('', $buff));
	}

	public function many_to_many_select($name, $resource, $rel_key, $rel_caption = null, $selected = null, $sel_key = null) {

		if (!$rel_caption)
			$rel_caption = $rel_key;
		if (!$selected)
			$selected = array();

		$options = array();
		foreach ($resource as $r) {
			$option_value = $this->get_value($rel_key, $r);	
			$option_caption = $this->get_value($rel_caption, $r);	
			$is_selected = false;
			foreach ($selected as $s) {
				$option_selected = $this->get_value($sel_key, $s);
				if ($option_selected == $option_value) {
					$is_selected = true;
					break;
				}
			}
			$options[] = $this->checkbox("{$name}.", array('value' => $option_value), $is_selected, $option_caption);
		}

		return implode("\n", $options);
	}

	public function many_to_many($main_table, $related_table, $join_table = null, $name = null) {

		$db =& $this->using('db');

		list($main_table, $primary_key) = explode('/', $main_table);
		list($related_table, $foreign_key, $option_name) = explode('/', $related_table);

		if (!$join_table)
			$join_table = $main_table < $related_table ? "{$main_table}_{$related_table}": "{$related_table}_{$main_table}";

		if (!$name) 
			$name = $related_table;
	
		// getting all possible related rows
		$all = $db->fetch_all($db->select($related_table, array('id', $option_name)));
		
		$options = array();
		foreach ($all as $a) 
			$options[$a['id']] = $a[$option_name];

		// getting selected rows
		$selected = array();
		if (isset($this->vars[$related_table]) && is_array($this->vars[$related_table])) {
			foreach ($this->vars[$related_table] as $v) { 
				$selected[$v] = true;
			}
		}

		if (isset($this->vars['id'])) {
			$all = $db->fetch_all($db->select($join_table, $foreign_key, "{$primary_key} = '{$this->vars['id']}'"));
			if ($all) {
				foreach ($all as $a) 
					$selected[$a[$foreign_key]] = true;
			}
		}
		
		$buff = array();
		foreach ($options as $id => $value) 
			$buff[] = $this->checkbox("{$related_table}[]", array('value' => $id), isset($selected[$id]), $value);

		return implode("\n", $buff);
	}
	// TODO
	public function select_icon($name) {
		return $this->input($name);
	}
	public function editor($name, $attr = null, $type = null) {
	
		extract(
			$this->using(
				'auth',
				'param',
				'conf'
			)
		);

		$for_admin = $conf->get('core/admin_level_visual_editor', '1', 'b');
		$for_user = $conf->get('core/user_level_visual_editor', '1', 'b');
			
		if (!$attr) {
			$attr = array();
		}

		if (!isset($attr['style'])) {
			$attr['style'] = 'width: 90%; height: 200px;';
		}

		if (($auth->is_operator() && $for_admin) || (!$auth->is_operator() && $for_user)) {

			if (!$type) {
				if ($auth->is_admin()) {
					$type = 'complete';
				} else if ($auth->is_operator()) {
					$type = 'basic';
				} else if ($auth->is_user()) { 
					$type = 'mini';
				} else {
					$type = 'nano';
				}
			}

			$id = 'meteora_editor_'.time();
			$attr['id'] = $id;
			$buff = $this->textarea($name, $attr)."\n";
			$buff .= '<script type="text/javascript">new Editor($(\''.$id.'\'), {autoSave: \''.$param->create('/module=tools/action=auto_save').'\', blank: \''.$param->create('?module=tools&action=blank').'\', mode: \''.$type.'\'})</script>'."\n";

			return $buff;
		} else {
			return $this->textarea($name, $attr);	
		}
	}

	public function checkbox($name, $attr = null, $checked = false, $text = null) {
		if (!$attr) {
			$attr = array();
		}

		if (!isset($attr['name'])) 
			$attr['name'] = $this->get_name($name);

		if (!isset($attr['value']))
			$attr['value'] = 1;

		if ($checked || ($checked === null && $this->get_value($name)))
			$attr['checked'] = 'checked';
	
		if ($text) {
			return $this->tag('label', null, $this->tag('span', null, $this->checkbox($name, $attr).' '.$text));
		} else {
			$attr['type'] = 'checkbox';
			return $this->tag('input', array('type' => 'hidden', 'value' => '', 'name' => $attr['name'])).$this->tag('input', $attr);
		}
	}

	public function rank($link, $rank, $total = 5) {
		$buff = array();
		for ($i = 0; $i < $total; $i++) {
			$buff[] = $this->mini_icon('apps/'.(($i < $rank) ? 'rank' : 'rank_off'));
		}
		return ''.implode('', $buff).'</a>';
	}

	public function hidden($name = null, $attr = null) {
		if (!is_array($attr) && $attr) {
			$attr = array('value' => $attr);
		}
		if (!$attr) {
			$attr = array();
		}
		$attr['type'] = 'hidden';
		return $this->input($name, $attr);
	}

	public function submit($caption, $attr = null) {
		if (!$attr) $attr = array();
		$attr = array_merge(
			$attr,
			array(
				'type' => 'submit',
				'class' => 'submit'
			)
		);
		return $this->tag('button', $attr, $caption);
	}
	
	public function reset($caption, $attr = null) {
		if (!$attr) $attr = array();
		$attr = array_merge(
			$attr,
			array(
				'type' => 'reset',
				'class' => 'reset'
			)
		);
		return $this->tag('button', $attr, $caption);
	}

	public function button($name = null, $attr = null, $inner_html = '') {
		if (!$attr) $attr = array();

		if ($name) 
			$attr['name'] = $this->get_name($name); 

		if (!isset($attr['type'])) 
			$attr['type'] = 'button';
		return $this->tag("button", $attr, $inner_html);
	}

	public function buttons() {
		return $this->tag('div', array('class' => 'buttons'), $this->button(null, array('class' => 'submit', 'type' => 'submit'), $this->mini_icon('actions/submit').' '.__('Continue')));
		//return $this->tag('div', array('class' => 'buttons'), $this->button(null, array('class' => 'reset', 'type' => 'reset'), $this->mini_icon('actions/reset').' '.__('Start again')).$this->button(null, array('class' => 'submit', 'type' => 'submit'), $this->mini_icon('actions/submit').' '.__('Continue')));
	}

	public function toggle($id, $text = 'Toggle') {
		return $this->tag('div', array('class' => 'buttons'), $this->button(null, array('type' => 'button', 'onclick' => '$(\''.$id.'\').toggle();'), $this->mini_icon('actions/toggle').' '.$text));
	}
	
	/**
	 * Executes the template and returns the result
	 * @returns The result of the executed template
	 */
	public function output() {
		$this->execute();

		$this->result = preg_replace_callback(
		'/<([^>]+)(action|src|href)="([^\.\/#][^"]*)"([^>]*?)>/',
		create_function('$a', 'return "<".$a[1]." $a[2]=\"".((preg_match("/[a-z]+:/", $a[3])) ? $a[3] : TM_BASE_URL.ltrim($a[3], "/"))."\"".$a[4].">";'),
		$this->result);

		return $this->result;
	}
}
?>
