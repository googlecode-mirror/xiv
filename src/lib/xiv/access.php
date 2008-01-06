<?php
/**
 * Access control
 * ---
 * Written by Jose Carlos Nieto <xiam@menteslibres.org>
 * Copyright (c) 2007 Jose Carlos Nieto <xiam@menteslibres.org>
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @author          Jose Carlos Nieto <xiam@menteslibres.org>
 * @copyright       Copyright (c) 2007-2008, Jose Carlos Nieto <xiam@menteslibres.org>
 * @link            http://www.textmotion.org
 * @version         $Revision$
 * @modifiedby      $LastChangedBy$
 * @lastmodified    $Date$
 * @license         http://www.opensource.org/licenses/mit-license.php The MIT License
 *
 */

class access extends tm_object {
	
	/**
	 * Constructor
	 */
	public function __construct(&$params = null) {
		parent::__construct($params);
	}

	/**
	 * Returns true if the current user is within the allowed
	 * time between requests.
	 *
	 * @param reference $obj tm_module object
	 * @param int $time Seconds
	 * @param int $requests Max. requests within the given $time seconds.
	 */
	public function verify(&$obj, $time, $requests) {

		extract(
			$this->using(
				'db',
				'env',
				'auth'
			)
		);

		if (is_object($obj)) {
			$request_id = $obj->module_name.'/'.$obj->action;
		} else {
			$request_id = $obj;
		}

		$db->delete(
			'request_control',
			$db->bind(
				'action = ? AND ip_addr = ? AND last_access + '.$time.' < '.time().'',
				$request_id,
				IP_ADDR
			)
		);

		$banned = $db->find_one(
			array(
				'access' => array(
					'table' => 'request_control',
					'where' => $db->bind(
						'action = ? AND ip_addr = ? AND request_count >= ?',
						$request_id,
						IP_ADDR,
						$requests
					)
				)
			)
		);

		return $banned ? false : true;
	}

	public function clear(&$obj) {

		$db =& $this->using('db');

		if (is_object($obj)) {
			$request_id = $obj->module_name.'/'.$obj->action;
		} else {
			$request_id = $obj;
		}
	
		$db->delete(
			'request_control',
			$db->bind(
				'action = ? AND ip_addr = ?',
				$request_id,
				IP_ADDR
			)
		);
	}


	public function grant(&$obj, $time, $request, $persistent = false) {
		$test = $this->verify($obj, $time, $request);
		$this->mark($obj, $persistent);
		return $test;
	}

	/**
	 * Increases the request counter
	 * 
	 * @param reference $obj tm_module object.
	 * @param boolean $persistent True if you want the action time to be updated too.
	 */
	public function mark(&$obj, $persistent = false) {
		extract(
			$this->using(
				'db',
				'env',
				'auth'
			)
		);

		if (is_object($obj)) {
			$request_id = $obj->module_name.'/'.$obj->action;
		} else {
			$request_id = $obj;
		}
		
		if ($persistent) {
			$q = $db->update(
				'request_control',
				array(
					'last_access'					=> time(),
					array('request_count'	=> 'request_count + 1'),
					'user_id'							=> $auth->user['user']['id']
				),
				$db->bind(
					'action = ? AND ip_addr = ?',
					$request_id,
					IP_ADDR
				)
			);
		} else {
			$q = $db->update(
				'request_control',
				array(
					array('request_count' => 'request_count + 1'),
					'user_id'							=> $auth->user['user']['id']
				),
				$db->bind(
					'action = ? AND ip_addr = ?',
					$request_id,
					IP_ADDR
				)
			);
		}

		if (!$db->affected_rows($q)) {
			$db->insert(
				'request_control',
				array(
					'last_access'		=> time(),
					'ip_addr'				=> IP_ADDR,
					'user_id' 			=> $auth->user['user']['id'],
					'request_count'	=> 1,
					'action'				=> $request_id
				)
			);
		}
	}

	/**
	 * Unit test
	 */
	public static function run_test() {
		$foo = new tm_object();
		$foo->action = 'test_action';
		$access = new access();
		// Accepting 5 request within 10 seconds
		if ($access->verify($foo, 10, 5)) {
			echo "You can handle more requests from your ip.";
		} else {
			echo "You cannot handle more requests from your ip.";
		}
		$access->mark($foo);
	}
}
?>
