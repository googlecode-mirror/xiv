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

class auth extends tm_object {

	private $cache = array();

	public function default_user() {
		$return = array(
			'user' => array(
				'id' => 0
			),
			'groups' => array(
				'guest' => TM_GROUP_GUEST,
				'all'		=> TM_GROUP_ALL
			)
		);
		return $return;
	}

	public function logout($user_id = null) {
		extract(
			$this->using(
				'cookie',
				'session',
				'db'
			)
		);
		if (!$user_id) {
			// logging out the current user
			$cookie->clear('auth_key');
			$session->clear('auth_salt');
		}
		// database logout
		if (!is_integer($user_id)) {
			$user_id = $this->user['user']['id'];
		}
		if ($user_id) {
			$db->update(
				'users',
				array(
					'auth_key' => ''
				),
				$db->bind('id = ?', $user_id)
			);
		}
	}
	
	public function __construct(&$params = null) {

		parent::__construct($params);

		$session =& $this->using('session');

		$session->clear('user');

		if (!defined('TM_STATIC')) {

			extract(
				$this->using(
					'db',
					'cookie',
					'env'
				)
			);

			// getting cookie params
			$key = explode(':', preg_replace('/[^a-zA-Z0-9:]/', null, $cookie->get('auth_key')));

			if (isset($key[1])) {

				$user = $this->get_user($key[0]);

				if ($user['user']['id']) {

					if ($user['user']['auth_key'] == md5($key[1].TM_UNIQUE_STR)) {
						// prevents cookie stealing
						if ($key[2] != $session->get('auth_salt')) {
							$this->logout();
							$env->redirect('/');
							exit(0);
						}
						define('TM_AUTH', true);
					}
				}
			}
		}

		if (!defined('TM_AUTH')) {
			define('TM_AUTH', false);
		}

		if (!TM_AUTH) {
			$user = $this->default_user();
		}

		$session->set('user', $user);

		$this->user =& $user;
	}

	public function get_user($user_id) {

		if (!isset($this->cache['get_user'])) {
			$this->cache['get_user'] = array();
		}

		$cache =& $this->cache['get_user'][$user_id];

		if (!isset($user)) {

			$db =& $this->using('db');
			// finding user
			$r = $db->find_one(
				array(
					'user' => array(
						'model' => 'users',
						'has_many' => array(
							'groups' => array(
								'table' => 'groups_users',
								'belongs_to' => array(
									'group' => array(
										'model' => 'groups',
										'fields' => array('id', 'name'),
										'foreign_key' => 'group_id'
									)	
								)
							)
						),
						'where' => $db->bind(
							'user.id = ?',
							$user_id
						)
					)
				)
			);

			if ($r) {

				// fetching additional user's groups
				$fetched = $queue = array();
				if (!empty($r['groups'])) {
					foreach ($r['groups'] as $g) {
						$queue[] = $g['group']['id'];
						$fetched[$g['group']['name']] = $g['group']['id'];
					}
				}

				while ($queue) {
					$groups = $db->find_all(
						array(
							'group' => array(
								'model' => 'groups',
								'fields' => array('id'),
								'assoc_key' => 'parent_id',
								'many_to_many' => array(
									'subgroup' => array(
										'table' => 'groups',
										'fields' => array('id', 'name'),
										'join_table' => 'groups_groups',
										'assoc_key' => 'related_id'
									)
								),
								'where' => "
								(groups_groups.parent_id = '".implode("' OR groups_groups.parent_id = '", $queue)."')
								",
								'trailing' => "GROUP BY (subgroup.id)"
							)
						)
					);
					$queue = array();
					if ($groups) {
						foreach ($groups as $g) {
							if (!isset($fetched[$g['subgroup']['name']])) {
								$fetched[$g['subgroup']['name']] = $g['subgroup']['id'];
								$queue[] = $g['subgroup']['id'];
							}
						}
					}
				}
				// automatic groups
				$fetched['user'] = TM_GROUP_USER;
				$fetched['all'] = TM_GROUP_ALL;
				/*
				foreach ($fetched as $name => $gid) {
					if ($gid == 1 || ($gid >= 6)) {
						$fetched['operator'] = TM_GROUP_OPERATOR;
						break;
					}
				}
				*/
				$r['groups'] = $fetched;
				// associated privileges
				$r['privileges'] = array();
				$ps = $db->find_all(
					array(
						'p' => array(
							'table' => 'groups_privileges',
							'where' => $db->bind('group_id in (\''.implode('\', \'', $r['groups']).'\')')
						)
					)
				);
				if ($ps) {
					foreach($ps as $p) {
						if (!isset($r['privileges'][$p['p']['module']])) {
							$r['privileges'][$p['p']['module']] = array(
								'allow_write' => 0,
								'allow_edit' => 0,
								'allow_delete' => 0
							);
						}
						$r['privileges'][$p['p']['module']]['allow_write'] += $p['p']['allow_write'];
						$r['privileges'][$p['p']['module']]['allow_edit'] += $p['p']['allow_edit'];
						$r['privileges'][$p['p']['module']]['allow_delete'] += $p['p']['allow_delete'];
						if ($p['p']['allow_write'] || $p['p']['allow_edit'] || $p['p']['allow_delete']) {
							$r['groups']['operator'] = TM_GROUP_OPERATOR;
						}
					}
				}
				$cache = $r;
			} else {
				$cache = $this->default_user();
			}
		}
		return $cache;
	}

	public function get_current_user() {
		$session =& $this->using('session');
		$user = $session->get('user');
		if ($user) {
			$user_id = $user['user']['id'];
		} else {
			$user_id = 0;
		}
		return $this->get_user($user_id);
	}

	public function has_group($name, $user_id = null) {
		$session =& $this->using('session');
		if (strlen($user_id) == 0) {
			$user = $session->get('user');
			if ($user) {
				$user_id = $user['user']['id'];
			} else {
				$user_id = 0;
			}
		}
		$user = $this->get_user($user_id);
		if ($user) {
			return isset($user['groups'][$name]);
		}
		return false;
	}

	public function is_editor($module, $user_id = null) {
		if ($this->has_group('admin', $user_id)) {
			return true;
		} else {
			return $this->has_group($module.'_editor', $user_id);
		}
	}

	public function allow_read($module) {
		if ($this->is_admin()) {
			return true;
		}
		return ($this->allow($module, 'write') || $this->allow($module, 'delete') || $this->allow($module, 'edit'));
	}

	public function allow_write($module) {
		return $this->allow($module, 'write');
	}
	public function allow_create($module) {
		return $this->allow($module, 'write');
	}
	public function allow_edit($module) {
		return $this->allow($module, 'edit');
	}
	public function allow_delete($module) {
		return $this->allow($module, 'delete');
	}

	public function allow($module, $action) {
		if ($this->is_admin()) {
			return true;
		} else {
			if ($action == 'read') {
				return $this->allow_read($module);
			} else {
				$user = $this->get_current_user();
				return !empty($user['privileges'][$module]['allow_'.$action]);
			}
		}
	}

	public function is_admin($user_id = null) {
		return $this->has_group('admin', $user_id);
	}

	public function is_user($user_id = null) {
		return $this->has_group('user', $user_id);
	}

	public function is_guest($user_id = null) {
		return $this->has_group('guest', $user_id);
	}

	public function is_operator($user_id = null) {
		if ($this->is_admin()) {
			return true;
		} else {
			return $this->has_group('operator', $user_id);
		}
	}

	public function get_modules() {
		$modules = array();
		$dh = opendir(TM_MODULES_DIR);
		while (($f = readdir($dh)) !== false) {
			if ($f{0} != '.' && is_dir(TM_MODULES_DIR.$f))	
				$modules[] = $f;
		}
		closedir($dh);
		return $modules;
	}

	public function sql_lock($item, $user_id = null) {

		extract(
			$this->using(
				'db',
				'session'
			)
		);

		if (!$user_id) {
			$user = $session->get('user');
			if ($user['user']['id']) {
				$user_id = $user['user']['id'];
			} else {
				$user_id = null;
			}
		}

		$user = $this->get_user($user_id);

		$item = explode('/', $item);
		
		$jt = $db->jointable_name($item[0], 'groups');

		$group_cond = "(jt.group_id = '".implode("' OR jt.group_id = '", $user['groups'])."')";

		// admin has access to everything but guest
		if (isset($user['groups']['admin'])) {
			$group_cond = 'jt.group_id != '.TM_GROUP_GUEST;
		}

		$q = $db->fetch_all(
			$db->select (
				array(
					"{$item[0]}/t",
					"{$jt}/jt",
				),
				't.id',
				"
					t.id = jt.{$item[1]}
					AND ({$group_cond})
					GROUP BY t.id
				"
			)
		);

		$r = array();
		if ($q) {
			foreach ($q as $p)
				$r[] = $p['id'];
		}

		return ($db->lock(isset($item[2]) ? $item[2] : 'id', $r));
	}
}
?>
