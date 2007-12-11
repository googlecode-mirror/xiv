<?php
/**
 * My_sQL driver
 * A database wrapper.
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
class mysql_driver extends database_engine {

	/**
	* Function for using at database connection
	*
	* @var string
	*/

	private $connect = 'mysql_connect';

	/**
	* Escapes a string into a safe SQL value
	*
	* @param string $val
	* @returns Safe SQL value
	*/
	function escape($val) {
		return mysql_real_escape_string($val, $this->_link);
	}

	/**
	* Returns a safe SQL statement by giving a prototype and its values
	*
	* @param string $first The SQL statement (usually a where clause)
	* @param string $value1, $value2, ..., $value_n SQL Values
	* @returns string Safe SQL statement with binded arguments
	*/
	function bind() {
		$args = func_get_args();
		$cond =& $args[0];
		if (isset($cond)) {
			$argc = count($args);
			if ((substr_count($cond, '?')) == $argc-1) {
				for ($i = 1; $i < $argc; $i++)
					$args[$i] = "'".$this->escape($args[$i])."'";
				$cond = str_replace('?', '%s', $cond);
				return call_user_func_array('sprintf', $args);
			} else {
				trigger_error(__('Unmatched arguments.'), E_USER_ERROR);
			}
		} else {
			trigger_error(__('You must provide a prototype!.'), E_USER_ERROR);
		}
	}

	/**
	* Creates a table given its properties
	*/
	function create($table, $properties) {
	}

	/**
	* Opens a database connection
	*/
	function open() {
		$connect =& $this->connect;
		$this->_link = @$connect($this->hostname, $this->username, $this->password, true);
		if ($this->_link) {
			return @mysql_select_db($this->database, $this->_link);
		} else {
			return false;
		}
	}

	/**
	* @returns a SQL error
	*/
	function get_error() {
		return $this->_link ? mysql_error($this->_link) : mysql_error();
	}

	/**
	* Executes a query and returns the result ID
	* 
	* @param string $sql SQL to execute
	* @param string $file __FILE__
	* @param string $line __LINE__
	* @returns resource Query ID
	*/
	function query($sql, $file = null, $line = null) {
		$this->last_sql = $sql;
		$timea = microtime();
		$this->last_query = mysql_query($this->last_sql, $this->_link) or $this->error()." During query {$this->last_sql}";
		$timeb = microtime();

		$timea = explode(' ', $timea);
		$timea = $timea[0] + $timea[1];
		$timeb = explode(' ', $timeb);
		$timeb = $timeb[0] + $timeb[1];

		$time = round($timeb - $timea, 5);

		$this->debug($this->last_sql);
		$this->query_count++;
		$this->query_log[] = array(
			'sql' => $sql,
			'affected' => @(int)$this->affected_rows(),
			'matched' => @(int)$this->num_rows($this->last_query),
			'error' => $this->get_error(),
			'time' => $time
		);
		return $this->last_query;
	}

	/**
	* @returns The latest inserted ID
	*/
	function last_insert_id() {
		return mysql_insert_id($this->_link);
	}

	/**
	* @returns A count of all the elements in a table
	*/
	function count($table, $where = null, $trailing = null) {
		if (is_resource($table)) {
			return mysql_num_rows($table);	
		} else {
			if (!$where)
				$where = "1 = 1";
			$q = $this->fetch_row($this->query("SELECT count(*) FROM ".$this->table_name($table)." WHERE {$where} {$trailing}"));
			return isset($q[0]) ? $q[0] : 0;
		}
	}

	/**
	* Selects fields from table and returns the query ID
	* 
	* @param string $table Table you want to use
	* @param mixed $fields Array or SQL string of fields to select
	* @param string $where SQL condition
	* @param string $trailing Additional SQL
	* @returns resource query id
	*/
	function select($table, $fields = "*", $where = null, $trailing = null) {
		if (!$where)
			$where = "1 = 1";

		if (!preg_match('/[^0-9]/', $where))
			$where = "id = '{$where}'";

		if (!is_array($fields))
			$fields = array($fields);
		$this->query("SELECT ".implode(", ", $fields)." FROM ".$this->table_name($table, true)." WHERE {$where} {$trailing}");
		return $this->last_query;
	}

	private function fields_and_values($fields_and_values) {

		$return = array(
			'fields' => array(),
			'values' => array()
		);
	
		$fields =& $return['fields'];
		$values =& $return['values'];

		foreach ($fields_and_values as $field => $value) {
			if (is_numeric($field)) {
				if (is_array($value))	{
					list($field, $value) = each($value);
					$fields[] = $field;
					$values[] = $value;
				} else {
					// textmotion specific
					if (!isset($param))
						$param =& $this->using('param');
					$fields[] = $value;
					$values[] = "'".$this->escape($param->get($value))."'";
				}
			} else {
				if (is_array($value)) {
					// tree => leaf
					$copy = array();
					foreach ($value as $k => $v) {
						if (is_numeric($k)) {
							if (is_array($v)) {
								list($k, $v) = each($v);
								$copy[] = array("{$field}.{$k}" => $v);	
							} else {
								$copy[] = "{$field}.{$v}";
							}
						} else {
							$copy["{$field}.{$k}"] = $v;
						}
					}

					$fields = $values = null;

					return $this->fields_and_values($copy);

				} else {
					$fields[] = $field;
					$values[] = "'".$this->escape($value)."'";
				}
			}
		}

		if (isset($this->auto_fields)) {
			foreach ($this->auto_fields as $i => $v) {
				if (!in_array($v, $fields)) {
					$fields[] = $this->auto_fields[$i];
					$values[] = $this->auto_values[$i];
				}
			}
		}

		return $return;
	}

	function auto_fields($type, $table, &$values) {

		$this->auto_fields = $this->auto_values = array();

		$auth =& $this->using('auth');

		$auto = array();
		$fields = $this->get_fields($table);

		foreach ($fields as $f) {
			switch ($f) {
				case 'author_id':
				case 'modifier_id':
					$auto[$f] = "'".$this->escape($auth->user['user']['id'])."'";
				break;
				case 'date_created':
				case 'date_modified':
					$auto[$f] = 'now()';
				break;
				case 'ip_address':
					$auto[$f] = "'".$this->escape(IP_ADDR)."'";
				break;
			}
		}

		switch ($type) {
			case 'insert':
				unset($auto['modifier_id']);
				unset($auto['date_modified']);
			break;
			case 'update':
				unset($auto['author_id']);
				unset($auto['date_created']);
			break;
		}

		foreach ($auto as $auto_field => $auto_value) {
			$append = true;

			foreach ($values as $f => $v) {
				if (is_numeric($f)) {
					if (is_array($v)) {
						list($_f) = each($v);
						if ($_f == $auto_field)
							continue;
					} else {
						if ($v == $auto_field)
							continue;
					}
				} else {
					if (is_array($v)) {
						foreach ($v as $c => $b) {
							if (is_numeric($c)) {
								if (is_array($b)) {
									if ($b[0] == $auto_field)
										continue;
								} else {
									if ($b == $auto_field)
										continue;
								}
							} else {
								if ($c == $auto_field)
									continue;
							}
						}
					} else {
						if ($f == $auto_field)
							continue;
					}
				}
			}

			$this->auto_fields[] = $auto_field;
			$this->auto_values[] = $auto_value;
		}
	}

	/**
	* Inserts a row
	* 
	* @param string $table Table you want to use
	* @param array $fields_and_values Data you want to insert
	* @returns resource query id
	*/
	function insert($table, $fields_and_values) {

		$this->auto_fields('insert', $table, $fields_and_values);

		extract($this->fields_and_values($fields_and_values));

		$copy = array();
		foreach ($fields as $f) {
			$f = explode('.', $f);
			$copy[] = array_pop($f);
		}

		$q = $this->query(
			"
			INSERT INTO
				".$this->table_name($table)."
				(".implode(", ", $copy).")
				VALUES
				(".implode(", ", $values).")
			"
		);

		return $q;
	}

	/**
	* Updates a row
	* 
	* @param string $table Table you want to use
	* @param array $fields_and_values Data you want to insert
	* @param string $where SQL conditions
	* @param string $trailing Additional SQL
	* @param string $no_escape $fields_and_values' keys you don't want to be escaped (deprecated)
	* @returns resource query id
	*/
	function update($table, $fields_and_values, $where = "1 = 1", $trailing = null) {

		// TODO: use the model's primary key here
		if (is_numeric($where)) {
			$where = $this->bind('id = ?', $where);
		}

		if (count($fields_and_values) == 1) {
			list($k) = each($fields_and_values);
			if (is_array($fields_and_values[$k])) {
				$table = explode('/', $table);
				$table = "{$table[0]}/{$k}";
			}
		}

		$this->auto_fields('update', $table, $fields_and_values);

		extract($this->fields_and_values($fields_and_values));

		$update = array();
		foreach($fields as $i => $field) 
			$update[] = "{$field} = {$values[$i]}";

		$q = $this->query("
			UPDATE
				".$this->table_name($table, true)."
			SET ".implode(",", $update)."
			WHERE
				$where
				$trailing
		");

		return $q;
	}

	/**
	* Deletes a row
	* 
	* @param string $table Table you want to use
	* @param string $where SQL conditions
	* @param string $trailing Additional SQL
	* @returns resource query id
	*/
	function delete($table, $where, $trailing = null) {
		if (!preg_match('/[^0-9]/', $where))
			$where = $this->bind('id = ?', $where);
		return $this->query("DELETE FROM ".$this->table_name($table)." WHERE $where $trailing");
	}

	/**
	* A numeric array of values
	*
	* @param resource $q My_sQL Query ID
	* @returns Array of row's data (numerical index).
	*/
	function fetch_row($q) {
		return mysql_fetch_row($q);
	}

	/**
	* An associative array of values and fields
	*
	* @param resource $q My_sQL Query ID
	* @returns Array of row's data (association based index).
	*/
	function fetch_array($q) {
		return mysql_fetch_array($q, MYSQL_ASSOC);
	}

	/**
	* Number of rows matched with a query
	*
	* @param resource $q My_sQL Query ID
	* @returns The number of rows affected by the latest SELECT statement
	*/
	function num_rows($q) {
		return mysql_num_rows($q);
	}

	/**
	* Number of rows affected by a row modification
	*
	* @returns The number of rows affected by the latest change (UPDATE, DELETE, etc)
	*/
	function affected_rows() {
		return mysql_affected_rows($this->_link);
	}

	/**
	* Reads all data from the query and returns an associative array
	*
	* @param resource $q My_sQL query ID
	* @returns An associative array of results
	*/
	function fetch_all($q) {
		if ($q && $this->num_rows($q)) {
			while ($r = $this->fetch_array($q))
				$buff[] = $r;
			return $buff;
		}
		return null;
	}

	/**
	* Returns the first query's result
	*
	* @param resource $q My_sQL query ID
	* @returns An associative array of the first result (null if there was no result)
	*/
	function fetch_one($q) {
		if ($this->num_rows($q)) 
			return $this->fetch_array($q);
		return null;
	}

	/**
	* Updates a row if exists, creates it otherwise
	*
	* @param string $table Table you want to use
	* @param array $fields_and_values Associative array of your data
	* @param string $where condition where to insert or update
	*/
	function insert_or_update($table, $fields_and_values, $where) {
		if ($this->count($table, $where)) {
			return $this->update($table, $fields_and_values, $where);
		}
		return $this->insert($table, $fields_and_values);
	}

	function desc($table) {
		if (!isset($this->cache['desc']))
			$this->cache['desc'] = array();
		$cache =& $this->cache['desc'][$table];
		if (!isset($cache)) {
			$cache = $this->fetch_all($this->query("DESC ".$this->table_name($table).""));
		}
		return $cache;
	}

	/**
	* Closes the connection
	*/
	function close() {
		mysql_close($this->_link);
	}

	function __destruct() {
		if (TM_DEBUG_LEVEL > 1 && !defined('TM_NO_DEBUG')) {
			if (!defined('TM_CLI')) {
				if (defined('TM_AJAX')) {
					return false;
					echo "/* <!-- \n";
					foreach ($this->query_log as $log) {
						echo htmlspecialchars($log['sql'])."\n";
						if ($log['error'])
							echo "\terror: ".htmlspecialchars($log['error'])."\n";
						echo "\ttime: {$log['time']}\n";
						echo "\tmarched: {$log['matched']}\n";
						echo "\taffected: {$log['affected']}\n";
						echo "\n";
						echo "\n";
					}
					echo "\n --> */";
				} else {
					echo "<div class=\"debug\">";
					foreach ($this->query_log as $log) {
						echo "<div class=\"sql\">
							<div>".htmlspecialchars($log['sql'])."</div>
							".($log['error'] ? "<div class=\"error\">".htmlspecialchars($log['error'])."</div>" : null)."
							<ul>
								<li><b>time:</b> {$log['time']}</li>
								<li><b>matched:</b> {$log['matched']}</li>
								<li><b>affected:</b> {$log['affected']}</li>
							</ul>
						</div>
						";
					}
					echo "</div>";
				}
			}
		}
	}
}
?>
