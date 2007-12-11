<?php
	
/**
 * Database engine
 * ---
 * Written by Jose Carlos Nieto <xiam@astrata.com.mx>
 * Copyright (c) 2007 Astrata Software S.A. de C.V.
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @author          Jose Carlos Nieto <xiam@astrata.com.mx>
 * @copyright       Copyright (c) 2007, Astrata Software S.A. de C.V.
 * @link            http://opensource.astrata.com.mx Astrata Open Source Projects
 * @version         $Revision$
 * @modifiedby      $Last_changed_by: $
 * @lastmodified    $Date$
 * @license         http://www.opensource.org/licenses/mit-license.php The MIT License
 *
 */

define('DB_SAVE_REPLACE', 1);
define('DB_SAVE_APPEND', 2);

class database_engine extends tm_object {

	/**
	 * Query log
	 * @var array
	 */
	public $query_log = array();

	public $query_count = 0;

	private $cache = array();

	/**
	 * Database username
	 * @var string
	 */
	public $username;

	/**
	 * Database password
	 * @var string
	 */
	public $password;

	/**
	 * Hostname to connect
	 * @var string
	 */
	public $hostname;

	/**
	 * Database name
	 * @var string
	 */
	public $database;

	/**
	 * Table prefix
	 * @var string
	 */
	public $prefix;

	/**
	 * Driver name to use
	 * @var string
	 */
	public $driver;

	private $_link;
	public $_last_query;
	public $_counter;

	private function set_query_fields(&$dest, $alias, $fields) {
		foreach ($fields as $i => $f) {
			if (is_int($i)) {
				$dest[] = "`{$alias}`.`{$f}` AS `{$alias}.{$f}`";
			} else {
				$dest[] = "`{$alias}`.`{$i}` AS `{$alias}.{$f}`";
			}
		}
	}

	public function get_fields($table) {
		$f = array();
		$table = explode('/', $table);
		$table = $table[0];
		$q = $this->desc($table);
		foreach ($q as $p)
			$f[] = $p['Field'];
		return $f;
	}

	public function get_tables($like = null) {
		$f = array();
		$q = $this->query('SHOW TABLES'.($like ? " LIKE '{$like}'" : null));
		while ($row = $this->fetch_row($q)) {
			$f[] = $row[0];
		}
		return $f;
	}

	private function set_properties(&$data, $alias) {

		$model = null;
		// including model only when required
		if (isset($data['model'])) {
			$model_file = TM_MODULES_DIR."{$data['model']}/model.php";
			if (file_exists($model_file)) {
				require_once $model_file;
				$model_class = "{$data['model']}_model";
				$model = new $model_class;
			}
		}

		$default = array(
			'primary_key' => 'id',
			'model' => null,
			'table' => $alias,
			'fields' => null,
			'assoc_key' => "{$alias}_id",

			'limit' => -1,
			'offset' => 0,
			'where' => null,
			'trailing' => null,
			'order_by' => null,
			'distinct' => false,

			'dependent' => false,
			'save_type' => DB_SAVE_REPLACE,
			'join_type' => 'LEFT JOIN',
			'join_condition' => null,
			'join_statement' => null,
			'finder_query' => null,
			'join_table' => null,
			'foreign_key' => null,
			'belongs_to' => array(),
			'has_many' => array(),
			'many_to_many' => array(),
			'has_one' => array(),

			'inner_join' => array()
		);

		if (empty($data['model']))
			$data['model'] = $data['table'];

		foreach ($default as $k => $v) {
			if (!isset($data[$k]))
				$data[$k] = isset($model->$k) ? $model->$k : $default[$k];
		}

		if ($data['dependent'])
			$data['join_type'] = 'INNER JOIN';
		
		if (!$data['fields'])
			$data['fields'] = $this->get_fields($data['table']);

		if (!is_array($data['fields']))
			$data['fields'] = array($data['fields']);

		// foreign relations
		$foreign = array('many_to_many', 'has_many');
		foreach ($foreign as $f) {
			foreach ($data[$f] as $alias => $prop) {
				if (empty($prop['foreign_key'])) {
					$data[$f][$alias]['foreign_key'] = $data['assoc_key'];
				}
			}
		}

		// assoc relations
		$assoc = array('has_one', 'belongs_to');
		foreach ($assoc as $a) {
			foreach ($data[$a] as $alias => $prop) {
				if (empty($prop['assoc_key']))
					$data[$a][$alias]['assoc_key'] = $data['assoc_key'];
			}
		}

	}

	public function save($data) {

		$param =& $this->using('param');

		$sql = null;
		$save = array();

		list($main_alias, $main_data) = each($data);
		$this->set_properties($main_data, $main_alias);
		
		foreach ($main_data['fields'] as $field)
			$save[$field] = $param->get($main_alias, $field);

		if (isset($main_data[$main_data['primary_key']])) {

			$id = (int)$main_data[$main_data['primary_key']];
			$this->update($main_data['table'], $save, "{$main_data['primary_key']} = '{$id}'");	

		} else {
			$q = $this->insert($main_data['table'], $save);
			if ($q) {
				$id = $this->last_insert_id();
			} else {
				// TODO: error handler
				die;
			}
		}
		
		foreach ($main_data['has_many'] as $assoc_alias => $assoc_data) {
			$this->set_properties($assoc_data, $assoc_alias);
			
			$read = $param->get($assoc_alias);
			if (isset($read) && is_array($read)) {
				$i = 0;
				foreach ($read as $r) {
					$save = array();
					$save[$assoc_data['foreign_key']] = $id;
					foreach ($assoc_data['fields'] as $field) 
						$save[$field] = isset($r[$field]) ? $r[$field] : null;
					switch ($assoc_data['save_type']) {
						case DB_SAVE_REPLACE:
							if ($i == 0)
								$this->delete($assoc_data['table'], "{$assoc_data['foreign_key']} = '{$id}'");	
						break;
					}
					$this->insert($assoc_data['table'], $save);
					$i++;
				}
			}
		}

		foreach ($main_data['many_to_many'] as $assoc_alias => $assoc_data) {
			$this->set_properties($assoc_data, $assoc_alias);

			if (!$assoc_data['join_table']) {
				$join_table = $this->jointable_name($assoc_data['table'], $main_data['table']);
				$assoc_data['join_table'] = array(
					$join_table => array(
						'table' => $join_table
					)	
				);
			}

			if (!is_array($assoc_data['join_table'])) {
				$assoc_data['join_table'] = array(
					$assoc_data['join_table'] => array(
						'table' => $assoc_data['join_table']
					)
				);
			}
			list($join_alias, $join_data) = each($assoc_data['join_table']);
			$this->set_properties($join_data, $join_alias);

			$read = $param->get($assoc_alias);
			$i = 0;
			foreach ($read as $r) {
				$save = null;
				$save[$assoc_data['foreign_key']] = $id; 
				$save[$assoc_data['assoc_key']] = $r;
				// saving
				switch ($assoc_data['save_type']) {
					case DB_SAVE_REPLACE:
						if ($i == 0)
							$this->delete($join_data['table'], "{$assoc_data['foreign_key']} = '{$id}'");	
						$this->insert($join_data['table'], $save);
					break;
					case DB_SAVE_APPEND:
						$q = $this->count($join_data['table'], "{$assoc_data['foreign_key']} = '{$id}' AND {$assoc_data['assoc_key']} = '{$r}'");
						if (!$q)
							$this->insert($join_data['table'], $save);
					break;
				}
				$i++;
			}
		}

		foreach ($main_data['has_one'] as $assoc_alias => $assoc_data) {
			$this->set_properties($assoc_data, $assoc_alias);
			$read = $param->get($assoc_alias);
			$save = array();
			$save[$assoc_data['assoc_key']] = $id;
			foreach ($assoc_data['fields'] as $field)
				$save[$field] = isset($read[$field]) ? $read[$field] : null;
			$q = $this->fetch_one($this->select($assoc_data['table'], $assoc_data['primary_key'], "".$assoc_data['assoc_key']." = '{$id}'"));
			if ($q) {
				$this->update($assoc_data['table'], $save, "{$assoc_data['primary_key']} = '{$q['id']}'");		
			} else {
				$this->insert($assoc_data['table'], $save);
			}
		}
	}

	/**
	* Returns only one row
	* @param string $data Database relation
	*/
	public function find_one($data) {
		if (is_array($data)) {
			list($main_alias, $main_data) = each($data);
			$data[$main_alias]['limit'] = 1;
			$q = $this->find_all($data);
			if ($q)
				return $q[0];
			else
				return null;
		} else {
			$args = func_get_args();
			
			if ($args) {

				$table		= $args[0];
				$fields		= empty($args[1]) ? null : $args[1];
				$where		= empty($args[2]) ? null : $args[2];
				$trailing	= empty($args[3]) ? null : $args[3];

				if (is_numeric($where))
					$where = "id = '{$where}'";

				$f = $this->fetch_one($this->select($table, $fields, $where, $trailing));

				if (!is_array($fields))
					$fields = array($fields);

				if (count($fields) === 1 && isset($f[$fields[0]])) 
					return $f[$fields[0]];

				return $f;
			} else {
				// TODO: error handler
			}
		}
	}

	/**
	 * Returns all matching rows
	 * @param string $data Database relation
	 */
	public function find_all($data, $use_cache = true) {
	
		$cache_id = md5(serialize($data)).sizeof($data);

		if (!isset($this->cache['find_all']))
			$this->cache['find_all'] = array();

		$cache =& $this->cache['find_all'][$cache_id];

		if (!$use_cache || !isset($cache)) {

			if (is_array($data)) {
				$sql = null;

				$query_fields = array();
				$query_join = array();
				$query_where = array();
				$result = array();

				list($main_alias, $main_data) = each($data);
				$this->set_properties($main_data, $main_alias);
				$this->set_query_fields($query_fields, $main_alias, $main_data['fields']);

				if ($main_data['has_one']) {
					
					$result['has_one'] = array();

					foreach ($main_data['has_one'] as $assoc_alias => $assoc_data) {
					
						$this->set_properties($assoc_data, $assoc_alias);

						if ($assoc_data['finder_query']) {

							$q = $this->fetch_one($this->query($assoc_data['finder_query']));

							if ($q)
								$result['has_one'][$assoc_alias] = $q;

						} else {
							
							$this->set_query_fields($query_fields, $assoc_alias, $assoc_data['fields']);
							
							if (!$assoc_data['join_statement']) {
								$assoc_data['join_statement'] =
								"{$assoc_data['join_type']} ".$this->table_name($assoc_data['table'])." `{$assoc_alias}` "
								."ON (`{$assoc_alias}`.`{$assoc_data['assoc_key']}` = `{$main_alias}`.`{$assoc_data['primary_key']}`)";
							}
							
							$query_join[] = $assoc_data['join_statement'];

						}
					}
				}

				if ($main_data['belongs_to']) {

					$result['belongs_to'] = array();

					foreach ($main_data['belongs_to'] as $assoc_alias => $assoc_data) {

						$this->set_properties($assoc_data, $assoc_alias);

						if ($assoc_data['finder_query']) {

							$q = $this->fetch_one($this->query($assoc_data['finder_query']));

							if ($q)
								$result['belongs_to'][$assoc_alias] = $q;

						} else {

							$this->set_query_fields($query_fields, $assoc_alias, $assoc_data['fields']);
							if (!$assoc_data['join_statement']) {
								$assoc_data['join_statement'] = ""
								."{$assoc_data['join_type']} ".$this->table_name($assoc_data['table'])." `{$assoc_alias}` "
								."ON (".($assoc_data['join_condition'] ? $assoc_data['join_condition'] : "`{$main_alias}`.`{$assoc_data['foreign_key']}` = `{$assoc_alias}`.`{$assoc_data['primary_key']}`").")";
							}
							$query_join[] = $assoc_data['join_statement'];
						}
					}
				}

				if ($main_data['many_to_many']) {

					$result['many_to_many'] = array();

					foreach ($main_data['many_to_many'] as $assoc_alias => $assoc_data) {
						$this->set_properties($assoc_data, $assoc_alias);

						if ($assoc_data['finder_query']) {
							$q = $this->fetch_all($this->query($assoc_data['finder_query']));
							$result['many_to_many'][$assoc_alias] = $q[0];
						} else {
							$this->set_query_fields($query_fields, $assoc_alias, $assoc_data['fields']);

							if (!$assoc_data['join_table']) {
								$join_table = $this->jointable_name($assoc_data['table'], $main_data['table']);
								$assoc_data['join_table'] = array(
									$join_table => array(
										'table' => $join_table
									)	
								);
							}

							if (!is_array($assoc_data['join_table'])) {
								$assoc_data['join_table'] = array(
									$assoc_data['join_table'] => array(
										'table' => $assoc_data['join_table']
									)
								);
							}

							list($join_alias, $join_data) = each($assoc_data['join_table']);
							$this->set_properties($join_data, $join_alias);
							$this->set_query_fields($query_fields, $join_alias, $join_data['fields']);

							if (!$assoc_data['join_statement']) {
								$assoc_data['join_statement'] = ""
								."{$assoc_data['join_type']} ".$this->table_name($join_data['table'])." `{$join_alias}` "
								."ON (`{$join_alias}`.`{$assoc_data['foreign_key']}` = `{$main_alias}`.`{$main_data['primary_key']}`) "
								.($assoc_data['where'] ? "AND ({$join_data['where']}) " : null)
								."{$assoc_data['join_type']} ".$this->table_name($assoc_data['table'])." `{$assoc_alias}` "
								."ON (`{$join_alias}`.`{$assoc_data['assoc_key']}` = `{$assoc_alias}`.`{$assoc_data['primary_key']}`) "
								.($assoc_data['where'] ? "AND ({$assoc_data['where']}) " : null);
							}

							$query_join[] = $assoc_data['join_statement'];
						}
					}
				}

				$query = "SELECT ".($main_data['distinct'] ? ' DISTINCT ' : null)." ".implode(', ', $query_fields)." "
				."FROM ".$this->table_name($main_data['table'])." `{$main_alias}` "
				.($query_join ? implode(' ', $query_join) : null)." "
				.($main_data['where'] ? "WHERE {$main_data['where']} " : null)
				.($main_data['order_by'] ? "ORDER BY {$main_data['order_by']} " : null)
				.(($main_data['limit'] > 0) ? "LIMIT {$main_data['offset']}, {$main_data['limit']} " : null)
				.($main_data['trailing'] ? $main_data['trailing']." " : null)
				;

				$values = $this->fetch_all($this->query($query));

				if ($main_data['has_many']) {
					
					$result['has_many'] = array();

					if (is_array($values)) {
						foreach ($values as $i => $value) {
							foreach ($main_data['has_many'] as $assoc_alias => $assoc_data) {
								if (!empty($assoc_data['finder_query'])) {
									$q = $this->fetch_all($this->query($assoc_data['finder_query']));	
									if ($q) {
										foreach ($q as $p) {
											$this->results_array($p);
											$values[$i][$assoc_alias][] = array(
												$assoc_alias => $p
											);
										}
									}
								} else {
									$this->set_properties($assoc_data, $assoc_alias);
									$assoc_data['where'] = ($assoc_data['where'] ? "({$assoc_data['where']}) AND ": null)."(`{$assoc_data['foreign_key']}` = '".$value["{$main_alias}.{$main_data['primary_key']}"]."')";
									$values[$i][$assoc_alias] = $this->find_all(
										array(
											$assoc_alias => $assoc_data
										)
									);
								}
							}
						}
					}
				}

				$this->results_array($values);

				if ($values) {
					if (!empty($result['belongs_to'])) {
						foreach ($values as $i => $v) {
							foreach ($result['belongs_to'] as $assoc_alias => $query_data) {
								$values[$i][$assoc_alias] = $query_data;
							}
						}
					}
					
					if (!empty($result['has_one'])) {
						foreach ($values as $i => $v) {
							foreach ($result['has_one'] as $assoc_alias => $query_data) {
								$values[$i][$assoc_alias] = $query_data;
							}
						}
					}

					if (!empty($result['many_to_many'])) {
						foreach ($values as $i => $v) {
							foreach ($result['many_to_many'] as $assoc_alias => $query_data) {
								$values[$i][$assoc_alias] = $query_data;
							}
						}
					}
				}
				$cache = $values ? $values : array();
			}
		}
		return $cache;
	}

	private function results_array(&$arr) {
		if (is_array($arr)) {

			$copy = array();

			foreach ($arr as $i => $v) {
				$this->results_array($v);
				if (!is_numeric($i)) {
					$i = explode('.', $i);
					if (isset($i[1])) {
						if (!isset($copy[$i[0]]))
							$copy[$i[0]] = array();
						$copy[$i[0]][$i[1]] = $v;
					} else {
						$copy[$i[0]] = $v;
					}
				} else {
					$copy[$i] = $v;
				}
			}

			foreach ($copy as $i => $v) {
				if (is_array($v)) {
					$e = true;
					foreach ($v as $j) {
						if ($j != '') {
							$e = false;
							break;
						}
					}
					if ($e)
						$copy[$i] = null;
				}
			}
			
			$arr = $copy;
		}
	}

	/**
	 * Creates an SQL condition that will lock to certain results
	 * @param string $field Field name
	 * @param array $possible_values Values to lock results
	 */
	public function lock($field, $possible_values = array()) {
		if ($possible_values)
			return "({$field} = '".implode("' OR {$field} = '", $possible_values)."')";
		return "(0 = 1)";
	}

	/**
	 * Determines join table names
	 * @param string $t1 Table name
	 * @param string $t2 Table name
	 * @returns string Name of the join table
	 */
	public function jointable_name($t1, $t2) {
		return $t1 > $t2 ? "{$t2}_{$t1}" : "{$t1}_{$t2}";
	}

	/**
	 * Prefixes given table name(s) and formats them according SQL
	 * @var mixed $table String or array of strings containing the name of the table(s) (table/alias)
	 * @var boolean $alias If true will return table name in a 'table as table_alias' fashion, useful for SELECT statements
	 * @return string SQL formatted table name
	 */
	public function table_name($table, $alias = false) {
		if (is_array($table)) {
			$ts = array();
			foreach ($table as $t)
				$ts[] = $this->table_name($t, $alias);
			return implode(", ", $ts);
		} else {
			if ($alias) {
				$table = explode('/', $table);
				$alias = isset($table[1]) ? $table[1] : $table[0];
				return "`".$this->prefix.$table[0].(isset($this->suffix) ? $this->suffix : null)."` as `$alias`";
			} else {
				return "`".$this->prefix.$table.(isset($this->suffix) ? $this->suffix : null)."`";
			}
		}
	}

	public function error() {
		parent::error($this->get_error()." @ ".$this->last_sql);
	}

	// deprecated
	public function common_extract_fields($values) {

		$param =& $this->using('param');

		$fields = array();
		if (is_array($values)) {
			foreach ($values as $k => $name) {
				if (is_numeric($k)) {
					if ($param->exists($name)) 
						$fields[$name] = $param->get($name);
				} else
					$fields[$k] = $name;
			}
		} else if ($values) {
			if ($param->exists($values)) 
				$fields[$values] = $param->get($values);
		} else {
			$fields = $param->get_all();
		}
		return $fields;
	}

	// deprecated
	public function now() {
		return date('Y-m-d h:i:s');
	}

	// deprecated
	public function common_update($table, $values = null, $id, $no_escape = null) {
		$fields = $this->common_extract_fields($values);
		return $this->update($table, $fields, "id = '".intval($id)."'", null, $no_escape);
	}
	
	// deprecated
	public function common_insert($table, $values = null, $plain = null) {
		$fields = $this->common_extract_fields($values);
		$this->insert($table, $fields, $plain);
		return $this->getlast_insert_id();
	}

	// deprecated
	public function common_delete($table, $id = null) {
		$this->delete($table, (is_int($id) ? "id = '{$id}'" : $id));
	}

	public function truncate($table) {
		return $this->query('TRUNCATE TABLE '.$this->table_name($table).'');
	}
	
	// deprecated
	public function common_select($table, $fields = null, $where = null, $trailing = null) {
		if (!preg_match('/[^0-9]/', $where))
			$where = "id = '{$where}'";
		$f = $this->fetch_one($this->select($table, $fields, $where, $trailing));
		if (!is_array($fields))
			$fields = array($fields);
		if (count($fields) === 1 && isset($f[$fields[0]])) {
			return $f[$fields[0]];
		}
		return $f;
	}
	
	// deprecated
	public function common_select_all($table, $fields = null, $where = null, $trailing = null) {
		return $this->fetch_all($this->select($table, $fields, $where, $trailing));
	}
	
	// deprecated
	public function common_select_one($table, $fields = '*', $where = null, $trailing = null) {
		/*
		if (!is_array($fields))
			$fields = array($fields);
		*/
		$f = $this->fetch_one($this->select($table, $fields, $where, $trailing));
		return ($fields = '*' || is_array($fields) || preg_match('/\sas\s/i', $fields)) ? $f : $f[$fields];
	}
	
	/**
	* Macro that saves a "has many" relation.
	*
	* @param string $table Table name
	* @param array $assoc_data An array containing assoc_key => value
	* @param array $foreign_data An array containing foreign_key => (array of values)
	*/
	function save_has_many($table, $assoc_data, $foreign_data) {
		list($a_key, $a_value) = each($assoc_data);
		list($f_key, $f_value) = each($foreign_data);
		$this->delete($table, $this->bind("{$a_key} = ?", $a_value));
		if (is_array($f_value)) {
			foreach ($f_value as $v) {
				if ($v)
					$this->insert($table, array($f_key => $v, $a_key => $a_value));
			}
		}
	}
	
	// deprecated
	public function common_save_many_to_many($main_table, $related_table, $join_table = null, $data = null) {

		$param =& $this->using('param');

		list($main_table, $primary_key) = explode('/', $main_table);
		list($related_table, $foreign_key) = explode('/', $related_table);

		if (!$join_table)
			$join_table = $main_table < $related_table ? "{$main_table}_{$related_table}": "{$related_table}_{$main_table}";

		if (!$data)
			$data = $param->get($related_table);

		if ($param->exists('id'))
			$id = (int)$param->get('id');
		else 
			$id = isset($this->id) ? $this->id : $this->getlast_insert_id();
		
		$this->query("DELETE FROM ".$this->table_name($join_table)." WHERE `{$primary_key}` = '{$id}'");

		if (is_array($data)) {
			foreach ($data as $related) {
				if ($related)
					$this->query("INSERT INTO ".$this->table_name($join_table)." (`$primary_key`, `$foreign_key`) VALUES('{$id}', '{$related}')");
			}
		}
	}

}
?>
