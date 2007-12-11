<?php
/**
 * My_sQLi driver
 * A database wrapper.
 * ---
 * Written by Jorge de Jesus Medrano Rodriguez <h1pp1e@users.sourceforge.net>
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @author          Jorge de Jesus Medrano Rodriguez <h1pp1e@users.sourceforge.net>
 * @copyright       Copyright (c) 2007, Jorge de Jesus Medrano Rodriguez
 * @link            http://www.h1pp1e.net
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

	private $connect = 'mysqli_connect';

	/**
	* Escapes a string into a safe SQL value
	*
	* @param string $val
	* @returns Safe SQL value
	*/
	function escape($val) {
		return mysqli_real_escape_string($this->_link, $val);
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
		$this->_link = $connect($this->hostname, $this->username, $this->password, true) or $this->error();
		mysqli_select_db($this->_link, $this->database) or $this->error();
	}

	/**
	* @returns a SQL error
	*/
	function get_error() {
		return mysqli_error($this->_link);
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
		$this->last_query = mysqli_query($this->_link, $this->last_sql) or $this->error()." During query {$this->last_sql}";
		$this->debug($this->last_sql);
		$this->query_count++;
		return $this->last_query;
	}

	/**
	* @returns The latest inserted ID
	*/
	function getlast_insert_id() {
		return mysqli_insert_id($this->_link);
	}

	/**
	* @returns A count of all the elements in a table
	*/
	function count($table) {
		$q = $this->fetch_row($this->query("SELECT count(*) FROM ".$this->table_name($table).""));
		return isset($q[0]) ? $q[0] : 0;
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
		if (!is_array($fields))
			$fields = array($fields);
		$this->query("SELECT ".implode(", ", $fields)." FROM ".$this->table_name($table, true)." WHERE {$where} {$trailing}");
		return $this->last_query;
	}

	/**
	* Inserts a row
	* 
	* @param string $table Table you want to use
	* @param array $fields_and_values Data you want to insert
	* @param string $no_escape $fields_and_values' keys you don't want to be escaped
	* @returns resource query id
	*/
	function insert($table, $fields_and_values, $no_escape = null) {
		if (!is_array($no_escape)) 
			$no_escape = array();
		$fields = $values = array();
		foreach ($fields_and_values as $field => $value) {
			$fields[] = $field;
			$values[] = in_array($field, $no_escape) ? $value : "'".$this->escape($value)."'";
		}
		$this->query("INSERT INTO ".$this->table_name($table)." (".implode(",", $fields).") VALUES(".implode(", ", $values).")");
		return $this->last_query;
	}

	/**
	* Updates a row
	* 
	* @param string $table Table you want to use
	* @param array $fields_and_values Data you want to insert
	* @param string $where SQL conditions
	* @param string $trailing Additional SQL
	* @param string $no_escape $fields_and_values' keys you don't want to be escaped
	* @returns resource query id
	*/
	function update($table, $fields_and_values, $where = "1 = 1", $trailing = null, $no_escape = null) {
		if (!is_array($no_escape)) 
			$no_escape = array();
		
		$values = array();
		foreach ($fields_and_values as $field => $value)
			$values[] = "{$field} = ".(in_array($field, $no_escape) ? $value : "'".$this->escape($value)."'");

		$this->query("UPDATE ".$this->table_name($table)." SET ".implode(",", $values)." WHERE $where $trailing");

		return $this->last_query;
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
		return mysqli_fetch_assoc($q);
	}

	/**
	* Number of rows matched with a query
	*
	* @param resource $q My_sQL Query ID
	* @returns The number of rows affected by the latest SELECT statement
	*/
	function num_rows($q) {
		return mysqli_num_rows($q);
	}

	/**
	* Number of rows affected by a row modification
	*
	* @returns The number of rows affected by the latest change (UPDATE, DELETE, etc)
	*/
	function affected_rows() {
		return mysqli_affected_rows($this->_link);
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
		if ($this->num_rows($this->select($table, $where))) 
			return $this->update($table, $fields_and_values, $where);
		return $this->insert($table, $fields_and_values);
	}

	/**
	* Closes the connection
	*/
	function close() {
		mysqli_close($this->_link);
	}
}
?>
