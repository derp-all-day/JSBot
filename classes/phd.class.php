<?php
include_once 'phdtools.class.php';
require_once 'phdtools.class.php';
class PHD extends PHDTools
{
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *\
	 * Developer(s): Kai (Tux.)                                                                                *
	 * Description: An alternative purely php flat-file database system.                                       *
	 *                                                                                                         *
	 * INSTRUCTIONS:                                                                                           *
	 * 1. run: (new PHD)->setupDatabaseRoot( $db_root, $owner_username = 'root', $owner_password = '4321b' );  *
	 * 2. Disable '$create_new_db_root' in settings for obvious security reasons.                              *
	 * 3. create an instance and go from there. :)                                                             *
	 *                                                                                                         *
	\* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

	# <settings>

	/* Root account can change class settings 'settings()' */
	protected $root_can_change_settings = true;

	/* Users can view settings 'settings()' */
	protected $users_can_view_settings = true;

	/* can the function 'setup_db_root()' to create a new NoSQL db root be called */
	protected $create_new_db_root = true;

	/* The default type when an unsupported or blank type is called */
	protected $default_type = 'text';

	/* Can remote connections to this node be made */
	protected $remote_enabled = true;

	/* Remote connections to this node can write to database */
	protected $remote_write = true;

	/* Remote connections to this node can read from database */
	protected $remote_read = true;

	/* Disabled functions for remote connections to this node */
	protected $disallowed = array('delete','setup_db_root', '`', 'changePassword');

	/* an array of tables you wish to block from accessing via remote connections to this node */
	protected $tables_blacklist = array();

	# </settings>

	# array of settings names
	protected $settings = array(
		'root_can_change_settings',
		'users_can_view_settings',
		'create_new_db_root',
		'default_type',
		'remote_enabled',
		'remote_write',
		'remote_read',
		'disallowed',
		'tables_blacklist'
	);

	# query variables
	protected $ram = array(), $table = '', $query = array(0 => false), $regex = array(0 => false), $create = false, $rows = '';
	protected $order = array(0 => false), $byID = false, $db = '', $rand = false, $list_by = false;
	protected $read  = array('get','nodeInit','exists','ls'), $write = array('changePassword','put','clear','delete','change','create','bump');
	protected $version = 'PHD: Hypertext Database v8.3';
	# Magic Methods And Functions
	public function __construct( $user = '', $pass = '', $nsql_dir = '' ) {
		if(is_dir($nsql_dir) && is_file("{$nsql_dir}/.owner")) {
			$this->nsql_dir = $nsql_dir;
			$this->auth = $this->authenticate($user,$pass);
			if($this->auth) {
				$this->database = $user;
			} else {
				$this->nsql_dir = '';
			}
		}
	}

	# Remote Connection Handling Functions

	protected function filter_function( $line, $functions ) {
		$line = str_replace(' ', '', $line);
		$jar = explode('->', $line);
		foreach($functions as $function) {
			foreach($jar as $func) {
				if(stristr($func, "{$function}(")) {
					return true;
				}
			}
		}
		return false;
	}
	protected function parse_function( $string ) {
		$jar = explode('(', $string);
		$function = $jar[0];$args = trim(str_replace($function, '', $string), '()').', NULL';
		$arg_list = array();$in_quotes = array(false, '');$buff = '';
		foreach(str_split($args) as $char) {
			if($in_quotes[0]) {
				if($char !== $in_quotes[1]) {
					$buff .= $char;
				} else {
					$arg_list[] = $buff;
					$buff = '';
					$in_quotes = array(false, '');
				}
			} elseif($char === '\'' || $char === '"') {
				$in_quotes[0] = true;
				$in_quotes[1] = $char;
			} elseif($char !== ',') {
				if($char !== ' ') {
					$buff .= $char;
				} else {
					if($buff !== '' && str_replace(' ', '', $buff) !== '') {
						if($buff == 'true') {
							$arg_list[] = true;
						} elseif($buff == 'false') {
							$arg_list[] = false;
						} elseif($buff == 'null') {
							$arg_list[] = null;
						} else {
							$arg_list[] = (int) $buff;
						}
						$buff = '';
					}
				}
			}
		}
		if($buff !== '') {
			$arg_list[] = $buff;
		}
		return array($function, $arg_list);
	}

	public function nullFunction() { return false; }

	protected function parse_script( $string ) {
		$in_quotes = array(false, '');
		$buff = '';
		$queries = array();
		foreach(str_split($string) as $char) {
			if($in_quotes[0]) {
				$buff .= $char;
				if($char === $in_quotes[1]) {
					$in_quotes = array(false, '');
				}
			} elseif($char === '\'' || $char === '"') {
				$buff .= $char;
				$in_quotes[0] = true;
				$in_quotes[1] = $char;
			} elseif($char === ';') {
				$queries[] = $buff;
				$buff = '';
			} else {
				$buff .= $char;
			}
		}
		return $queries;
	}

	protected function text_to_array( $data ) {
		# array('lol' => 'string', 'kek' => 'string')
		#preg_match("~(array\(.*?\))(,|\s|\)|;)~i", $data, $match);
		#eval("\$data = {$match[1]}");
		return $data;
	}

	protected function interpret_functions( $query ) {
		if($this->filter_function($query, $this->disallowed)) {
			return NULL;
		}
		$methods = explode('->', $query);
		$ret = array();
		foreach($methods as $method) {
			$func_array = $this->parse_function($method);
			if(!$this->remote_write) {
				if(in_array($func_array[0], $this->write)) {
					$func_array[0] = 'nullFunction';
					$func_array[1] = array();
				}
			}
			if(!$this->remote_read) {
				if(in_array($func_array[0], $this->read)) {
					$func_array[0] = 'nullFunction';
					$func_array[1] = array();
				}
			}
			if($func_array[0] === 'setupDatabaseRoot') {
				$func_array[0] = 'nullFunction';
				$func_array[1] = array();
			}
			if($func_array[0] === 'table') {
				if(in_array($func_array[1][0], $this->tables_blacklist)) {
					$func_array[0] = 'nullFunction';
					$func_array[1] = array();
				}
				foreach($func_array[1] as $key => $val) {
					if(preg_match('~^array~i', $val)) {
						$func_array[1][$key] = $this->text_to_array($val);
					}
				}
			}
			$reflection = new ReflectionMethod($this, $func_array[0]);
			if ($reflection->isPublic()) {
				$ret = call_user_func_array(array($this, $func_array[0]), $func_array[1]);
			}
		}
		return json_encode($ret);
	}

	# Internal Functions

	protected function check_ram( $table ) {
		return !empty($this->ram[$table]);
	}

	protected function get_by_id_from_ram($table, $id) {
		if(!$this->check_ram($table)) {
			$this->ram[$this->table] = $this->get_table($this->table);
		}
		foreach($this->ram[$table] as $key => $row) {
			if($key === $id) {
				return array($key => $row);
			}
		}
		return false;
	}

	protected function get_row_id_from_ram($table, $col, $val) {
		if(!$this->check_ram($table)) {
			$this->ram[$this->table] = $this->get_table($this->table);
		}
		foreach($this->ram[$table] as $key => $row) {
			if(!empty($row[$col]) && $row[$col] === $val) {
				return $key;
			}
		}
		return false;
	}

	protected function get_where_from_ram($table, $col, $val, $limit) {
		if(!$this->check_ram($table)) {
			$this->ram[$this->table] = $this->get_table($this->table);
		}
		$ret = array();
		foreach($this->ram[$table] as $key => $row) {
			if(!empty($row[$col]) && $row[$col] === $val) {
				$ret[$key] = $row;
				$limit--;
			}
			if($limit === 0) {
				return (empty($ret))?false:$ret;
			}
		}
		if(!empty($ret)) {
			return $ret;
		}
		return false;
	}

	protected function get_table_from_ram( $table ) {
		if(!$this->check_ram($table)) {
			$this->ram[$this->table] = $this->get_table($this->table);
		}
		return $this->ram[$table];
	}

	# Other Internal PHD Functions

	protected function array_orderby() {
		$args = func_get_args();
		$data = array_shift($args);
		foreach ($args as $n => $field) {
			if (is_string($field)) {
				$tmp = array();
				foreach ($data as $key => $row) {
					$tmp[$key] = $row[$field];
				}
				$args[$n] = $tmp;
			}
		}
		$args[] = &$data;
		call_user_func_array('array_multisort', $args);
		return array_pop($args);
	}

	protected function order_output($out) {
		if($this->rand) {
			$num = count($out) - 1;
			$num = ($num > 0)?$num:1;
			$num = rand(0, $num);
			foreach($out as $key => $row) {
				$num--;
				if($num === 0) {
					return array($key => $row);
				}
			}
			return array($key => $row);
		} elseif(!$this->order[0]) {
			return $out;
		} elseif(!$this->column_exists($this->table, $this->order[1])) {
			throw new Exception("get(): column '{$this->order[1]}'@'{$this->table}' does not exist.");
		}
		$column = $this->order[1];
		$order = (in_array($this->order[2], array('asc', 'dec'), true))?$this->order[2]:'asc';
		$tmp = $this->order;
		$this->order = array(0 => false);
		if($order === 'dec') {
			return $this->array_orderby($out, $tmp[1], SORT_DESC);
		}
		return $this->array_orderby($out, $tmp[1], SORT_ASC);
	}

	# PHD User Functions

	/* DESC: Check if current object is successfully authenticated to an existing local PHD database
	 * FUNCTION: isAuthenenticated()
	 */
	public function isAuthenticated() {
		return ($this->auth)?true:false;
	}

	/* DESC: Select a database
	 * FUNCTION: database( $database )
	 * $database: the name of the database to select
	 */
	public function database( $database ) {
		$this->isAuth();
		if(!$this->owner) {
			throw new Exception("database(): Was unable to select database ({$database}) as user ({$this->session['user']})");
		}
		$database = $this->prepInput($database);
		if(is_dir("{$this->nsql_dir}/{$database}")) {
			$this->db = $database;
		} else {
			throw new Exception("database(): Was unable to select database ({$database}) as user ({$this->session['user']})");
		}
		return $this;
	}

	/* DESC: Select a table or define a new table to be created
	 * FUNCTION: table( $table, $rows = '' )
	 * $table: the name of the table to select or to be created
	 * $rows: (optional) if creating a table takes an asociative array of the new table schema
	 * 		^array('column' => 'type', 'column' => 'type', ...)
	 */
	public function table( $table = '', $rows = '' ) {
		$this->isAuth();
		$table = $this->prepInput($table);
		$this->rows  = $rows;
		$this->table = $table;
		return $this;
	}

	/* DESC: Fetch a row or table
	 * FUNCTION: get( $flag = false )
	 * $flag: (optional) a flag to alter the behaviour of the function
	 *		^ 'all', 'live', 'next', 'id'
	 */
	public function get( $flag = false ) {
		$this->isAuth();
		if($flag === 'all') {
			$this->query[3] = -1;
		}
		if($flag === 'next') {
			return $this->table($this->table)->getNextID();
		}
		if($this->byID) {
			$id = $this->byID;
			$this->byID = false;
			if($flag === 'live') {
				return $this->get_row_by_id($this->table, $id);
			}
			return $this->get_by_id_from_ram($this->table, $id);
		} elseif($this->query[0]) {
			$query = $this->query;
			$this->query = array(0 => false);
			if($flag === 'live') {
				$this->ram[$this->table] = $this->get_table($this->table);
			}
			if($flag === 'id') {
				return $this->get_row_id_from_ram($this->table, $query[1], $query[2]);
			}
			return $this->order_output($this->get_where_from_ram($this->table, $query[1], $query[2], $query[3]));
		} elseif($this->regex[0]) {
			if($flag === 'live') {
				$this->ram[$this->table] = $this->get_table($this->table);
			}
			$table = $this->get_table_from_ram($this->table);
			$ret = array();
			if(!$this->column_exists($this->table, $this->regex[1])) {
				throw new Exception("get(): cloumn '{$this->regex[1]}'@'{$this->table}' does not exist.");
			}
			foreach($table as $key => $row) {
				if(preg_match($this->regex[2], $row[$this->regex[1]])) {
					if($flag === 'id') {
						return $key;
					}
					$ret[$key] = $row;
					$this->regex[3]--;
				}
				if($this->regex[3] === 0) {
					return (empty($ret))?false:$this->order_output($ret);
				}
			}
			if(!empty($ret)) {
				return $this->order_output($ret);
			}
			return false;
		} else {
			if($flag === 'live') {
				$this->ram[$this->table] = $this->get_table($this->table);
			}
			return $this->order_output($this->get_table_from_ram($this->table));
		}
	}
	/* DESC: Create a database or table
	 * FUNCTION: create( $optional = '' )
	 * $optional: (optional) When creating a new database you must set this to the desired password for that database
	 */
	public function create( $optional = '' ) {
		$this->isAuth();
		if($this->db != '') {
			if($optional == '') {
				throw new Exception('create(): Requires one parameter as the password when making a new database for that database.');
			}
			$this->make_database($this->db, $optional);
			$this->db = '';
			return true;
		}
		if(!is_array($this->rows)) {
			$data = $this->rows;
			preg_match("~^(array\(.*?\))$~i", $data, $match);
			eval("\$data = {$match[0]};");
			$this->rows = $data;
		}
		if($this->table != '' && $this->make_table($this->table, $this->rows)) {
			$this->rows  = '';
			return $this;
		}
		$this->rows  = '';
		return false;
	}

	/* DESC: Enter data (new row) into a table. Normaly returns new row id.
	 * FUNCTION: put( $data = '', $chain = false )
	 * $data: an associative array of the new row to enter into the table
	 * 		^ array('column' => 'data', 'column' => 'data', ...)
	 *      ^ or takes json array
	 * $chain: (optional) return $this instead of the new rows ID to allow chaining multiple table insertions
	 */
	public function put( $data, $chain = false ) {
		$this->isAuth();
		if(!is_array($data)) {
			$data = $data;
			preg_match('~^(array\(.*?\))$~i', $data, $match);
			eval("\$data = {$match[0]};");
		}
		foreach($data as $key => $val) {
			$data[$key] = htmlentities($val, ENT_QUOTES | ENT_IGNORE, "UTF-8");
		}
		$id = $this->insert($this->table, $data);
		if($chain) {
			return $this;
		}
		return $id;
	}

	/* DESC: Clear a table or row from a table (Same as 'delete()')
	 * FUNCTION: clear()
	 */
	public function clear() {
		$this->isAuth();
		if($this->byID) {
			$id = $this->byID;
			$this->byID = false;
			return $this->remove_row_by_id($this->table, $id);
		} elseif($this->query[0]) {
			$query = $this->query;
			$this->query = array(0 => false);
			return $this->remove_row($this->table, $query[1], $query[2]);
		} else {
			if($this->rrmdir("{$this->nsql_dir}/{$this->database}/{$this->table}")) {
				unset($this->ram[$this->table]);
				return $this;
			}
			return false;
		}
	}

	/* DESC: Delete a row, table, or database
	 * FUNCTION: delete()
	 */
	public function delete() {
		$this->isAuth();
		if($this->db != '') {
			$this->remove_database($this->db);
			$this->db = '';
			return $this;
		} elseif($this->byID) {
			$id = $this->byID;
			$this->byID = false;
			return $this->remove_row_by_id($this->table, $id);
		} elseif($this->query[0]) {
			$query = $this->query;
			$this->query = array(0 => false);
			return $this->remove_row($this->table, $query[1], $query[2]);
		} else {
			if($this->rrmdir("{$this->nsql_dir}/{$this->database}/{$this->table}")) {
				unset($this->ram[$this->table]);
				return $this;
			}
			return false;
		}
	}

	/* DESC: Search a table for a row(s) were a column equals a given value
	 * FUNCTION: find( $val1 = '', $val2 = '', $limit = 1 )
	 * $val1: Name of the column to look in
	 * $val2: Value to search for
	 * $limit: (optional) Limit of rows to return
	 */
	public function find( $val1 = '', $val2 = '', $limit = 1 ) {
		if(empty($val1) || !isset($val2)) {
			throw new Exception('find(): Missing arguments...');
		}
		if($this->regex[0]) {
			$this->regex = array(0 => false);
		}
		$this->query = array(true, $val1, $val2, $limit);
		$this->byID = false;
		return $this;
	}

	/* DESC: Search a table for a row(s) were a column equals a given regular expression
	 * FUNCTION: regex( $column, $expression, $limit = 1 )
	 * $column: The column to search
	 * $expression: The expression to match
	 * $limit:  (optional) Limit of rows to return
	 */
	public function regex( $column, $expression, $limit = 1 ) {
		if($this->query[0]) {
			$this->query = array(0 => false);
		}
		$this->byID = false;
		$this->regex = array(true, $column, $expression, $limit);
		return $this;
	}

	/* DESC: Update a value in column of a specified row
	 * FUNCTION: change( $col = '', $val = '' )
	 * $col: Name of the column to change the value of
	 * $val: the value to change it to
	 */
	public function change( $col = '', $val = '' ) {
		if($this->byID) {
			$id = $this->byID;
			$this->byID = false;
			return $this->update_by_id($this->table, $id, $col, $val);
		} elseif($this->query[0]) {
			$query = $this->query;
			$this->query = array(0 => false);
			return $this->update($this->table, $query[1], $query[2], $col, $val);
		}
	}

	/* DESC: Select a row by it's row ID
	 * FUNCTION: id( $id )
	 * $id: The row ID of the row to select
	 */
	public function id( $id ){
		if($this->query[0]) {
			$this->query = array(0 => false);
		}
		if($this->regex[0]) {
			$this->regex = array(0 => false);
		}
		$this->byID = $id;
		return $this;
	}

	/* DESC: select a random row from a  table
	 * FUNTION: rand()
	 */
	public function rand( $count = 1 ) {
		$this->rand = true;
		return $this;
	}

	/* DESC: Check if a table or a database exists
	 * FUNCTION: exists()
	 */
	public function exists() {
		if(empty($this->table)) {
			throw new Exception('getNextID(): Table must be set.');
		}
		if(!empty($this->db)) {
			if(!$this->owner) {
				throw new Exception('exists(): Only the database Admin can check is a database exists.');
			}
			$db = $this->db;
			$this->db = '';
			return false; // function database() checks this itself
		}
		return $this->table_exists($this->table);
	}

	/* DESC: Create a connection to a remote PHD database (node) and execute a query(s)
	 * FUNCTION: remote( $query, $user, $pass, $db, $node )
	 * $query: the phd code to execute without the '$obj->' at the beginning of query string each separated by a semicolon ';'
	 * $user: Username for the remote database to connect to
	 * $pass: Password for the remote database to connect to
	 * $db: The name of the remote database to connect to
	 * $node: the url of the  remote node (node.php) to connect to
	 */
	public function remote( $query, $user, $pass, $db, $node ) {
		if(!empty($query) && !empty($user) && !empty($pass) && !empty($db) && !empty($node)) {
			if(!preg_match('!^(((f|ht)tp(s)?://)[-a-zA-Zа-яА-Я()0-9@:%_+.~#?&;//=]+)$!i', $node)) {
				throw new Exception('remote(): Node must be a valid url');
			}
			$http = curl_init($node);
			curl_setopt($http, CURLOPT_RETURNTRANSFER, 1);
			$result = curl_exec($http);
			$http_status = curl_getinfo($http, CURLINFO_HTTP_CODE);
			curl_close($http);
			if($http_status != 200) {
				throw new Exception('remote(): Given node appears to be off line.');
			}
			$pass = urlencode($pass);
			$user = urlencode($user);
			$db = urlencode($db);
			$query = urlencode($query);
			return file_get_contents("{$node}?user={$user}&pass={$pass}&db={$db}&query={$query}");
		} else {
			throw new Exception('remote(): User did not set all required parameters');
		}
	}

	/* DESC: Used for handling remote connections to this database threw 'node.php'. Returns a json array. Basically 'eval()' for PHD.
	 * FUNCTION: nodeInit( $query )
	 * $query: The PHD code to execute without the '$obj->' at the beginning of each line.
	 */
	public function nodeInit( $query ) {
		$this->isAuth();
		if(!$this->remote_enabled) {
			die('Remote connections to this node are turned off.');
		}
		$return = array();
		$queries = $this->parse_script($_GET['query']);
		foreach($queries as $query) {
			$query = trim($query, "\n");
			$return[] = json_decode($this->interpret_functions($query), true);
		}
		return json_encode($return);
	}

	/* DESC: Returns an array of set remote privs ('read'=>HasReadPrivs, 'write', HasWritePrivs)
	 * FUNCTION: checkRemotePrivs()
	 */
	public function checkRemotePrivs() {
		$this->isAuth();
		return array('read' => $this->remote_read, 'write' => $this->remote_write);
	}

	/* DESC: Check if remote connections are enabled to be made to the local databases
	 * FUNCTION: remoteConnectionEnabled()
	 */
	public function remoteConnectionEnabled() {
		$this->isAuth();
		return $this->remote_enabled;
	}

	/* DESC: Return array of current PHD class settings
	 * FUNCTION: settings()
	 */
	public function settings() {
		$this->isAuth();
		if(!$this->users_can_view_settings) {
			throw new Exception('settings(): This function has been disabled in the class settings.');
		}
		if(!$this->owner || !in_array($name, $this->settings) || !is_bool($value)) {
			foreach($this->settings as $setting) {
				$v = ($this->$setting)?'true':'false';
				$array[] = array('name'=>$setting, 'value' => $v);
			}
			return $array;
		}
	}

	/* DESC: Return the next (usable) unused row ID of a given table
	 * FUNCTION: getNextID()
	 */
	public function getNextID() {
		$this->isAuth();
		if(empty($this->table)) {
			throw new Exception('getNextID(): Table must be set.');
		}
		$stamp = -1;
		$tb = json_decode(file_get_contents("{$this->nsql_dir}/{$this->database}/{$this->table}/table.tb"), true);
		foreach($tb as $key => $val) {
			foreach($val['rows'] as $key => $val) {
				$id = (int) str_replace('s', '', $key);
				$stamp = ($stamp > $id)?$stamp:$id;
			}
			++$stamp;
			break;
		}
		return $stamp.'s';
	}

	/* DESC: Placed before you call 'get()' in the chain. Orders fetched data either ascending or descending by a specific column.
	 * FUNCTION: order( $column, $order = 'asc' )
	 * $column: Name of the column to order the fetched rows by.
	 * $order: How to order the fetched rows ('asc', 'dec'), 'asc' is default
	 * 		^asc = ascending, dec = descending
	 */
	public function order( $column, $order = 'asc' ) {
		$this->isAuth();
		if(empty($this->table)) {
			throw new Exception('order(): Table must be set.');
		}
		$this->order = array(true, $column, $order);
		return $this;
	}

	/* DESC: Move a specified row to the top of the table
	 * FUNCTION: bump( $flag = false )
	 * $flag: Alter the functionality of the function
	 * 		^'keep_key'
	 */
	public function bump( $flag = false ) {
		$this->isAuth();
		if(empty($this->table)) {
			throw new Exception('bumb(): Table must be set.');
		}
		if(!in_array($flag, array('keep_key'), true)) {
			if($this->byID) {
				$tb = json_decode(file_get_contents("{$this->nsql_dir}/{$this->database}/{$this->table}/table.tb"), true);
				$ret = array();
				$id = $this->byID;
				$this->byID = '';
				foreach($tb as $key => $col) {
					$tmp = array();
					$tmp2 = array();
					$tmp = $col['rows'][$id];
					unset($col['rows'][$id]);
					$tmp2['rows']['0s'] = $tmp;
					$i = 1;
					foreach($col['rows'] as $row) {
						$tmp2['rows']["{$i}s"] = $row;
						$i++;
					}
					$tmp2['type'] = $col['type'];
					$tmp2['name'] = $col['name'];
					$ret[$key] = $tmp2;
				}
				return $this->storeData("{$this->nsql_dir}/{$this->database}/{$this->table}/table.tb", json_encode($ret));
			} elseif($this->query[0]) {
				$id = $this->table($this->table)->find($query[1], $query[2])->get('id');
				return $this->table($this->table)->id($id)->bump();
			} else {
				$tb = json_decode(file_get_contents("{$this->nsql_dir}/{$this->database}/{$this->table}/table.tb"), true);
				$ret = array();
				$id = $this->byID;
				$this->byID = '';
				foreach($tb as $key => $col) {
					$tmp = array();
					$i = 0;
					foreach($col['rows'] as $row) {
						$tmp['rows']["{$i}s"] = $row;
						$i++;
					}
					$tmp['type'] = $col['type'];
					$tmp['name'] = $col['name'];
					$ret[$key] = $tmp;
				}
			}
		} elseif($flag === 'keep_key') {
			if($this->byID) {
				$tb = json_decode(file_get_contents("{$this->nsql_dir}/{$this->database}/{$this->table}/table.tb"), true);
				$ret = array();
				$id = $this->byID;
				$this->byID = '';
				foreach($tb as $key => $col) {
					$tmp = array();
					$tmp2 = array();
					$tmp = $col['rows'][$id];
					unset($col['rows'][$id]);
					$tmp2['rows']['0s'] = $tmp;
					$i = 1;
					foreach($col['rows'] as $row) {
						$tmp2['rows']["{$i}s"] = $row;
						$i++;
					}
					$tmp2['type'] = $col['type'];
					$tmp2['name'] = $col['name'];
					$ret[$key] = $tmp2;
				}
				return $this->storeData("{$this->nsql_dir}/{$this->database}/{$this->table}/table.tb", json_encode($ret));
			} elseif($this->query[0]) {
				$id = $this->table($this->table)->find($query[1], $query[2])->get('id');
				return $this->table($this->table)->id($id)->bump();
			}
		} else {
			return false;
		}
		return $this->storeData("{$this->nsql_dir}/{$this->database}/{$this->table}/table.tb", json_encode($ret));
	}

	/* DESC: Dump a tables contents into a runable PHD script
	 * FUNCCTION: dump( $flag = false )
	 * $flag: Alter the functionality of the function
	 * 		^'live'
	 */
	public function dump( $flag = false ) {
		$this->isAuth();
		if(empty($this->table)) {
			throw new Exception('dump(): Table must be set.');
		} elseif(!$this->table_exists($this->table)) {
			throw new Exception("dump(): Table '{$this->table}'does not exist.");
		}
		$date = @date("F j, Y, g:i a");
		$output_h = "/* PHD DUMP\n * [TABLE DUMP FOR TABLE '{$this->table}'@'{$this->database}']\n * SERVER: {$_SERVER['HTTP_HOST']} \n * DATE: {$date}\n * ROW COUNT: INT_ROW_COUNT\n */\n\n";
		$output = "// Create connection to the database\n\$db = new PHD('username', 'password', 'DatabaseRoot');\n\n";
		$output .= "// Delete the table if it already exists\nif(\$db->table('{$this->table}')->exists()) {\n\t\$db->table('{$this->table}')->delete();\n}\n\n";
		// Construct table creation
		$output .= "//Create table '{$this->table}'\n\$db->table('{$this->table}', array(";
		$tb = json_decode(file_get_contents("{$this->nsql_dir}/{$this->database}/{$this->table}/table.tb"), true);
		foreach($tb as $key => $val) {
			$output .= "\n\t'{$val['name']}' => '{$val['type']}',";
		}
		$output = rtrim($output, ",")."\n))->create();\n\n";
		// Construct row creations
		if($flag === 'live') {
			$table = $this->get_table_from_ram($this->table);
		} else {
			$table = $this->get_table($this->table);
		}
		$entry = 0;
		foreach($table as $key => $row) {
			$output .= "//Create row entry '{$entry}s' for table '{$this->table}'\n";
			$output .= "\$db->table('{$this->table}')->put(array(";
			foreach($row as $k => $v) {
				if(is_int($v)) {
					$output .= "\n\t'{$k}' => {$v},";
				} elseif(is_bool($v)) {
					$v = ($v)?'true':'false';
					$output .= "\n\t'{$k}' => {$v},";
				} else {
					$output .= "\n\t'{$k}' => '{$v}',";
				}
			}
			$output = rtrim($output, ",")."\n));\n\n";
			$entry++;
		}
		return str_replace('INT_ROW_COUNT', $entry, $output_h).$output;
	}

	/* DESC: List the tables in the current database or the rows and row types of a table, returns an array
	 * FUNCTION: function schema()
	 *
	 */
	public function schema() {
		$this->isAuth();
		$return = array();
		if($this->table_exists($this->table)) {
			$table = json_decode(file_get_contents("{$this->nsql_dir}/{$this->database}/{$this->table}/table.tb"), true);
			foreach($table as $key => $row) {
				$return[$key] = $row['type'];
			}
			return $return;
		} else {
			foreach (glob("{$this->nsql_dir}/{$this->database}/*", GLOB_ONLYDIR) as $table) {
				$return[] = array_pop(explode('/', $table));
			}
			$this->list_by = '';
			return $return;
		}
		return false;
	}

	/* DESC: Print out the current version.
	 * FUNCTION: version()
	 */
	public function version() {
		return $this->version;
	}

	/* Desc: change a database users password
	 * Function: change_password($new_password)
	 * $new_password: new password to set
	 */
	public function changePassword( $new_password ) {
		$this->isAuth();
		$salt = rand(1,9999);
		$new = hash("sha256", $new_password.$salt);
		if($this->owner) {
			$jar = explode(':', "{$this->nsql_dir}/.owner");
			$this->storeData("{$this->nsql_dir}/.owner", $jar[0].':'.$new.':'.$salt);
		} else {
			$this->storeData("{$this->nsql_dir}/{$this->database}/.password", $new.':'.$salt);
		}
		return true;
	}
	public function isAdmin() {
		if($this->owner) {
			return true;
		} else {
			return false;
		}
	}
	public function count() {
		$this->isAuth();
		if(empty($this->table) || !$this->table_exists($this->table)) {
			throw new Exception('count(): Table does not exist.');
		}
		$table = $this->get_table_from_ram($this->table);
		$count = 0;
		foreach($table as $row) {
			$count++;
		}
		return $count;
	}
	public function remoteDisallowedFunctions() {
		return $this->disallowed;
	}
	public function remoteDisallowedTables() {
		return $this->tables_blacklist;
	}
}
?>
