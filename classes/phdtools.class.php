<?php
abstract class PHDTools
{
	protected $user_dir, $nsql_dir, $auth = false, $owner = false, $session = array();
	protected $types = array('string','int','bool', 'timestamp','realtime');
	
	###### Private Functions ######
	
	protected function authenticate( $user, $pass ) {
		$auth = false;
		if(!file_exists("{$this->nsql_dir}/.owner")) {
			return false;
		}
		$owner = file_get_contents("{$this->nsql_dir}/.owner");
		$owner = explode(':', $owner);
		if($user === $owner[0] && hash("sha256", $pass.$owner[2]) === $owner[1]) {
			$this->owner = true;
			$this->session['user'] = $user;
			return true;
		} elseif($user != $owner[0] && is_dir("{$this->nsql_dir}/{$user}")) {
			$hash = explode(':', file_get_contents("{$this->nsql_dir}/{$user}/.password"));
			if(hash("sha256", $pass.$hash[1]) === $hash[0]) {
				$this->user_dir = "{$this->nsql_dir}/{$user}";
				return true;
			}
		}
		return false;
	}
	
	protected function isAuth() {
		if(!$this->auth) {
			throw new Exception(__METHOD__.'(): You don\'t seem to be authenticated...');
		}
		return true;
	}
	
	protected function prepInput( $input ) {
		$parts = explode('/', trim($input, '/'));
		return end($parts); 
	}
	
	protected function rrmdir( $dir ) {
		$files = array_diff(scandir($dir), array('.','..'));
		foreach ($files as $file) {
			(is_dir("$dir/$file")) ? $this->rrmdir("$dir/$file") : unlink("$dir/$file");
		}
		return rmdir($dir);
	}
	
	protected function filterType($data, $type) {
		if($type === 'int') {
			return "{$data}i";
		} elseif($type === 'bool') { 
			return ($data)?'true':'false';
		} elseif($type === 'timestamp'){
			return $_SERVER['REQUEST_TIME'];
		} elseif($type === 'realtime') {
			return time($data);
		} else {
			return (string) $data;
		}
	}
	
	protected function storeData( $file, $data ) {
		if (is_file($file) && !is_writable($file)) {
            if (!chmod($filename, 0666)) {
                 throw new Exception(__METHOD__.'(): You dont have permissions to write to this...');
            }
        }
		$fp = fopen($file, 'w');
		if (!flock($fp, LOCK_EX)) {
			fclose($fp);
			return false;
		}
		ftruncate($fp, 0);
		fwrite($fp, $data);
		fflush($fp);
		flock($fp, LOCK_UN);
		fclose($fp);
		return true;
	}
	
	# # # # # # # # # # # # # #
	#                         #
	# PHD Language Functions  #
	#                         #
	# # # # # # # # # # # # # #
	
	###### Initial setup ######
	
	/* Desc: create a new database root
	 * Function: setup_db_root($db_root, $owner_username = 'root', $owner_password = '4321b')
	 * $db_root: the root directory for the new PHD database system
	 * $owner_username: the username for the new PHD database owner account
	 * $owner_password: the password for the new PHD database owner account
	 */
	public function setupDatabaseRoot( $db_root, $owner_username = 'root', $owner_password = '4321b' ) {
		if(!$this->create_new_db_root) {
			throw new Exception('setup_db_root(): This function has been disabled in the class settings.');
		} elseif(is_dir($db_root)) {
			throw new Exception("setup_db_root(): Database root '{$db_root}' seems to already exist or folder exists already for another purpose.");
		} elseif(!mkdir($db_root)) {
			throw new Exception("setup_db_root(): Was unable to create a new database root '{$db_root}'. Check file permissions.");
		}
		$this->nsql_dir = $db_root;
		$salt = rand(1,9999);
		$this->storeData("{$db_root}/.owner", $owner_username.':'.hash("sha256", $owner_password.$salt).':'.$salt);
		$this->storeData("{$db_root}/.htaccess","deny from all\nOptions -Indexes");
		mkdir("{$db_root}/{$owner_username}");
		$this->storeData("{$db_root}/{$owner_username}/.htaccess",'deny from all');
		$this->auth = true;
		$this->owner = true;
		return true;
	}
	
	###### database functions ######
	
	/* Desc: Switch to another database *Admin only
	 * Function: switchDatabase( $database )
	 * $database: name of a database to switch to
	 */
	protected function switch_database( $database ) {
		$this->isAuth();
		$database = $this->prepInput($database);
		if(!($this->owner && is_dir("{$this->nsql_dir}/$database"))) {
			throw new Exception("switch_database(): Was unable to authenticate into ({$database}) as user ({$this->session['user']})");
		}
		$this->database = $database;
		return true;
	}
	
	/* Desc: Make a new database *Admin only
	 * Function: make_database( $database, $pass )
	 * $database: name of database to create_function
	 * $pass: Password to set for the database
	 */
	protected function make_database( $database, $pass ) {
		$this->isAuth();
		$database = $this->prepInput($database);
		if(!$this->owner) {
			throw new Exception("make_database(): Was unable to create database ({$database}) as user ({$this->session['user']})");
		} elseif(is_dir("{$this->nsql_dir}/{$database}")) {
			throw new Exception("make_database(): Database ({$database}) already seems to exist...");
		}
		mkdir("{$this->nsql_dir}/{$database}");
		$salt = rand(1,9999);
		$this->storeData("{$this->nsql_dir}/{$database}/.password", hash("sha256", $owner_password.$salt).':'.$salt);
		$this->storeData("{$this->nsql_dir}/{$database}/.htaccess", "deny from all\nOptions -Indexes");
		return true;
	}
	
	/* Desc: Remove/delete a database *Admin only
	 * Function: remove_database( $database )
	 * $database: name of a database to remove
	 */
	protected function remove_database( $database ) {
		$this->isAuth();
		$database = $this->prepInput($database);
		if(!$this->owner) {
			throw new Exception("remove_database(): Was unable to create database ({$database}) as user ({$this->session['user']})");
		} elseif(!is_dir("{$this->nsql_dir}/{$database}")) {
			throw new Exception("remove_database(): Database ({$database}) doesn't seen to exist...");
		}
		$this->rrmdir("{$this->nsql_dir}/{$database}");
		return true;
	}
	
	###### table and row functions ######
	
	/* Desc: Make a table
	 * Function: make_table( $table, $rows )
	 * $table: name of table to create
	 * $cols: takes an array array('ColumnName'=>'DataType') if DataType is unset it defaults to 'string'
	 */
	protected function make_table( $table, $cols ) {
		$this->isAuth();
		$table = $this->prepInput($table);
		if($this->table_exists($table)) {
			throw new Exception("make_table(): Table ({$table}@{$this->database}) already exists.");
		} elseif(!is_array($cols)) {
			throw new Exception('make_table(): Expects second paramater to be array()...');
		}
		mkdir("{$this->nsql_dir}/{$this->database}/{$table}");
		$array = array();
		foreach($cols as $col => $type) {
			if(is_int($col)) {
				$col = $type;
				$type = 'string';
			}
			if(!in_array($type, $this->types)) {
				$type = $this->default_type;
			}
			$array[$col]['rows'] = array();
			$array[$col]['type'] = $type;
			$array[$col]['name'] = $col;
		}
		$this->storeData("{$this->nsql_dir}/{$this->database}/{$table}/table.tb", json_encode($array));
		$this->storeData("{$this->nsql_dir}/{$this->database}/{$table}/.htaccess", "deny from all\nOptions -Indexes");
		return true;
	}
	
	/* Desc: Remove a table
	 * Function: remove_table( $table )
	 * $table: Name of the table to remove
	 */
	protected function remove_table( $table ) {
		$this->isAuth();
		$table = $this->prepInput($table);
		if(!$this->table_exists($table)) {
			throw new Exception("remove_table(): Table ({$table}@{$this->database}) does not exist.");
		}
		$this->rrmdir("{$this->nsql_dir}/{$this->database}/{$table}");
		return true;
	}
	
	/* Desc: clear a tables contents compleatly
	 * Function: clear_table( $table )
	 * $table: table to clear
	 */
	protected function clear_table( $table ) {
		$this->isAuth();
		if(!$this->table_exists($table)) {
			throw new Exception("clear_table(): Table ({$table}@{$this->database}) does not exist.");
		}
		$cleared = array();
		$tb = json_decode(file_get_contents("{$this->nsql_dir}/{$this->database}/{$table}/table.tb"), true);
		foreach($tb as $column) {
			$cleared[$column['name']]['rows'] = array();
			$cleared[$column['name']]['type'] = $column['type'];
			$cleared[$column['name']]['name'] = $column['name'];
		}
		$this->storeData("{$this->nsql_dir}/{$this->database}/{$table}/table.tb", json_encode($cleared));
		return true;
	}
	
	/* Desc: Count number of rows (entries) in table
	 * Function: count_table_rows( $table )
	 * $table: the table to count the rows of
	 */
	protected function count_table_rows( $table ) {
		$this->isAuth();
		$table = $this->prepInput($table);
		if(!$this->table_exists($table)) {
			throw new Exception("count_table_rows(): Table ({$table}@{$this->database}) does not exist.");
		}
		return count($this->get_table($table));
	}
	
	/* Desc: check if a table exists
	 * Function: table_exists($table)
	 * $table: table to check
	 */
	protected function table_exists( $table ) {
		$this->isAuth();
		$table = $this->prepInput($table);
		if(is_dir("{$this->nsql_dir}/{$this->database}/{$table}")) {
			return true;
		}
		return false;
	}
	
	/* Desc: Remove a column from a table. CAUTION: Also removes all data from column.
	 * Function: remove_column($table, $row)
	 * $table: the table containing the column to remove
	 * $col: the column to remove
	 */
	protected function remove_column( $table, $col ) {
		$this->isAuth();
		$table = $this->prepInput($table);
		$col = $this->prepInput($col);
		if(!$this->table_exists($table)) {
			throw new Exception("remove_column(): Table ({$table}@{$this->database}) does not exist.");
		} elseif(!$this->column_exists($table, $col)) {
			throw new Exception("remove_column(): Column '{$col}@{$table}' does not exist.");
		}
		$tb = json_decode(file_get_contents("{$this->nsql_dir}/{$this->database}/{$table}/table.tb"), true);
		$new = array();
		foreach( $tb as $c) {
			if($c['name'] != $col) {
				$new[$c['name']]['name'] = $c['name'];
				$new[$c['name']]['rows'] = $c['rows'];
				$new[$c['name']]['type'] = $c['type'];
			}
		}
		$this->storeData("{$this->nsql_dir}/{$this->database}/{$table}/table.tb", json_encode($new));
		return true;
	}
	
	/* Desc: check if a column in a specified table exists
	 * Function: column_exists( $table, $col)
	 * $table: Table to look for column in
	 * $col: column to check for
	 */
	protected function column_exists( $table, $col ) {
		$this->isAuth();
		$table = $this->prepInput($table);
		$col = $this->prepInput($col);
		if(!$this->table_exists($table)) {
			throw new Exception("column_exists(): Table ({$table}@{$this->database}) does not exist.");
		}
		$tb = json_decode(file_get_contents("{$this->nsql_dir}/{$this->database}/{$table}/table.tb"), true);
		if(!empty($tb[$col])) {
			return true;
		}
		return false;
	}
	
	###### data handling functions ######
	
	/* Desc: Insert a row of data into a table
	 * Function: insert($table, $data)
	 * $table: Name of table to insert data into
	 * $data: takes array as input. Array of data to insert as (row => data)
	 */
	protected function insert( $table, $data ) {
		$this->isAuth();
		$table = $this->prepInput($table);
		if(!$this->table_exists($table)) {
			throw new Exception("insert(): Table ({$table}@{$this->database}) does not exist.");
		} elseif(!is_array($data)) {
			throw new Exception('insert(): requires $data to be an array().');
		}
		$stamp = -1;
		$tb = json_decode(file_get_contents("{$this->nsql_dir}/{$this->database}/{$table}/table.tb"), true);
		foreach($tb as $key => $val) {
			foreach($val['rows'] as $key => $val) {
				$id = (int) str_replace('s', '', $key);
				$stamp = ($stamp > $id)?$stamp:$id;
			}
			++$stamp;
			break;
		}
		$stamp = $stamp.'s';
		foreach($tb as $key => $val) {
			@$tb[$key]['rows'][$stamp] = $this->filterType($data[$key], $val['type']);
		}
		$this->storeData("{$this->nsql_dir}/{$this->database}/{$table}/table.tb", json_encode($tb));
		return $stamp;
	}
	
	/* Desc: check if a row exists
	 * Function: row_exists($table, $col, $val)
	 * $table: the table to search in
	 * $col: the column to search in
	 * $val: the value to search for
	 */
	protected function row_exists($table, $col, $val) {
		$this->isAuth();
		if($this->get_where($table, $col, $val, 1)) {
			return true;
		}
		return false;
	}
	
	/* Desc: check if a row exists by it's id
	 * Function: row_exists_by_id($table, $id)
	 * $table: the table to search in
	 * $id: the ID to search for
	 */
	protected function row_exists_by_id($table, $id) {
		$this->isAuth();
		if($this->get_row_by_id($table, $id)) {
			return true;
		}
		return false;
	}
	
	/* Desc: get a specified rows id
	 * Function: get_row_id( $table, $row, $val )
	 * $table: the table to look in
	 * $col: the column to search for a match
	 * $val: the value to match
	 */
	protected function get_row_id( $table, $col, $val ) {
		$this->isAuth();
		$table = $this->prepInput($table);
		$col = $this->prepInput($col);
		if(!$this->table_exists($table)) {
			return false;
		} elseif(!$this->row_exists($table, $col, $val)) {
			return false;
		}
		$jar = $this->get_where($table, $col, $val, 1);
		foreach($jar as $key => $val) {
			return $key;
		}
	}
	
	/* Desc: remove a row by it's id
	 * Function: remove_row_by_id( $table, $id )
	 * $table: the table to look in
	 * $id: the id of the column to remove
	 * $fix_array_index: Fix the tables ID numbering index
	 */
	protected function remove_row_by_id( $table, $id, $fix_array_index = false ) {
		$this->isAuth();
		$table = $this->prepInput($table);
		if(!$this->table_exists($table)) {
			throw new Exception("remove_row_by_id(): Table ({$table}@{$this->database}) does not exist.");
		}
		$tb = json_decode(file_get_contents("{$this->nsql_dir}/{$this->database}/{$table}/table.tb"), true);
		$ret = false;
		foreach($tb as $key => $val) {
			if(!empty($tb[$key]['rows'][$id])) {
				unset($tb[$key]['rows'][$id]);
				$ret = true;
			}
		}
		$this->storeData("{$this->nsql_dir}/{$this->database}/{$table}/table.tb", json_encode($tb));
		return $ret;
	}
	
	/* Desc: remove a specified column
	 * Function: remove_row($table, $row, $val)
	 * $table: the table to find the column to remove in
	 * $col: the column to match the value in
	 * $val: the value to match
	 * $fix_array_index: Fix the tables ID numbering index
	 */
	protected function remove_row( $table, $col, $val, $fix_array_index = false ) {
		$this->isAuth();
		$table = $this->prepInput($table);
		$col = $this->prepInput($col);
		if(!$this->table_exists($table)) {
			throw new Exception("remove_row(): Table ({$table}@{$this->database}) does not exist.");
		} elseif(!$this->column_exists($table, $col)) {
			throw new Exception("remove_row(): Column '{$col}@{$table}' does not exist.");
		}
		$tb = json_decode(file_get_contents("{$this->nsql_dir}/{$this->database}/{$table}/table.tb"), true);
		foreach($tb as $key => $value) {
			if($key === $col) {
				foreach($value['rows'] as $k => $v) {
					if($v === $val) {
						$id = $k;
						break;
					}
				}
			}
		}
		$ret = false;
		foreach($tb as $key => $val) {
			if(!empty($tb[$key]['rows'][$id])) {
				unset($tb[$key]['rows'][$id]);
				$ret = true;
			}
		}
		$this->storeData("{$this->nsql_dir}/{$this->database}/{$table}/table.tb", json_encode($tb));
		return $ret;
	}
	
	/* Desc: fetch a column by it's ID
	 * Function: get_row_by_id($table, $id)
	 * $table: table to look for the row ID in
	 * $id: ID of row to return
	 */
	protected function get_row_by_id( $table, $id ) {
		$this->isAuth();
		if(!$this->table_exists($table)) {
			throw new Exception("get_row_by_id(): Table ({$table}@{$this->database}) does not exist.");
		}
		$return = array();
		$tb = json_decode(file_get_contents("{$this->nsql_dir}/{$this->database}/{$table}/table.tb"), true);
		foreach($tb as $key => $value) {
			if(!empty($tb[$key]['rows'][$id])) {
				$return[$id][$key] = $tb[$key]['rows'][$id];
			}
		}
		return $return;
	}
	
	/* Desc: Update a value in a certain column
	 * Function: update($table, $find_row, $find_value, $update_row, $update_value)
	 * $table: table to find column in
	 * $find_col: column to search for value to match desired column in
	 * $find_value: value to match
	 * $update_col: column of value to update
	 * $update_value: value to update column with
	 */
	protected function update( $table, $find_col, $find_value, $update_col, $update_value) {
		$this->isAuth();
		$table = $this->prepInput($table);
		$find_col = $this->prepInput($find_col);
		$update_col = $this->prepInput($update_col);
		if(!$this->table_exists($table)) {
			throw new Exception("update(): Table ({$table}@{$this->database}) does not exist.");
		} elseif(!$this->column_exists($table, $find_col)) {
			throw new Exception("update(): Column '{$find_col}@{$table}' does not exist.");
		} elseif(!$this->column_exists($table, $update_col)) {
			throw new Exception("update(): Column '{$update_col}@{$table}' does not exist.");
		}
		$tb = json_decode(file_get_contents("{$this->nsql_dir}/{$this->database}/{$table}/table.tb"), true);
		$id = '';
		foreach($tb as $key => $val) {
			if($find_col === $key) {
				foreach($val['rows'] as $k => $value) {
					if($value === $find_value) {
						$id = $k;
					}
				}
				break;
			}
		}
		$ret = false;
		if(!empty($tb[$update_col]['rows'][$id])) {
			$type = $tb[$update_col]['type'];
			$tb[$update_col]['rows'][$id] = $this->filterType($update_value, $type);
			$ret = $id;
		}
		$this->storeData("{$this->nsql_dir}/{$this->database}/{$table}/table.tb", json_encode($tb));
		return $ret;
	}
	
	/* Desc: Update a value in a certain column by it's ID
	 * Function: update_by_id( $table, $id, $col, $val )
	 * $table: table to find column in
	 * $id: the id of the column to update
	 * $col: column of value to update
	 * $val: value to update row with
	 */
	protected function update_by_id( $table, $id, $col, $val ) {
		$this->isAuth();
		$table = $this->prepInput($table);
		$col = $this->prepInput($col);
		if(!$this->table_exists($table)) {
			throw new Exception("update_by_id(): Table ({$table}@{$this->database}) does not exist.");
		} elseif(!$this->column_exists($table, $col)) {
			throw new Exception("update_by_id(): Column '{$col}@{$table}' does not exist.");
		}
		$tb = json_decode(file_get_contents("{$this->nsql_dir}/{$this->database}/{$table}/table.tb"), true);
		if(!empty($tb[$col]['rows'][$id])) {
			$type = $tb[$col]['type'];
			$tb[$col]['rows'][$id] = $this->filterType($val, $type);
			$this->storeData("{$this->nsql_dir}/{$this->database}/{$table}/table.tb", json_encode($tb));
			return true;
		}
		return false;
	}
	
	/* Desc: get data from table, returns an associative array.
	 * Function: get_table( $table )
	 * $table: Name of table to get data from
	 */
	protected function get_table( $table ) {
		$this->isAuth();
		$table = $this->prepInput($table);
		if(!$this->table_exists($table)) {
			throw new Exception("get_table(): Table ({$table}@{$this->database}) does not exist.");
		}
		$return = array();
		$tb = json_decode(file_get_contents("{$this->nsql_dir}/{$this->database}/{$table}/table.tb"), true);
		$return = array();
		foreach($tb as $key => $val) {
			$type = $val['type'];
			foreach($val['rows'] as $id => $v) {
				$v = html_entity_decode($v, ENT_QUOTES | ENT_IGNORE, "UTF-8");
				if($type === 'bool') {
					$v = ($v==='true')?true:false;
				} elseif($type === 'int') {
					$v = str_replace('i', '', $v);
					$v = (int) $v;
				}
				$return[$id][$key] = $v;
			}
		}
		return $return;
	}
	
	/* Desc: Get a row were a certain value exists.
	 * Function: get_where($table, $col, $value, $limit = 1)
	 * $table: the table to search in
	 * $col: the column to search in
	 * $value: The value to match
	 * $limit: limit on the number of rows to return. -1 for no limit
	 *
	 * returns: array(id=>array(data),'id'=>column_id)
	 *
	 */
	protected function get_where( $table, $col, $value, $limit = 1 ) {
		$this->isAuth();
		$table = $this->prepInput($table);
		$col = $this->prepInput($col);
		if(!$this->table_exists($table)) {
			throw new Exception("get_where(): Table ({$table}@{$this->database}) does not exist.");
		} elseif(!$this->column_exists($table, $col)) {
			throw new Exception("get_where(): Column '{$col}@{$table}' does not exist.");
		}
		$id = array();
		$return = array();
		$limit = (int)(is_int($limit))?$limit:1;
		$tb = json_decode(file_get_contents("{$this->nsql_dir}/{$this->database}/{$table}/table.tb"), true);
		foreach($tb as $key => $val) {
			if($key === $col) {
				foreach($val['rows'] as $k => $v) {
					if($v === $value) {
						$id[] = $k;
						$limit--;
					}
					if($limit===0) {
						break;
					}
				}
				break;
			}
		}
		if($id === '') {
			return false;
		}
		foreach($tb as $key => $val) {
			$type = $val['type'];
			foreach($val['rows'] as $k => $v) {
				if($k === $id) {
					if($type === 'bool') {
						$v = ($v==='true')?true:false;
					} elseif($type === 'int') {
						$v = (int) str_replace('i', '', $v);
					}
					$return[$id][$key] = $v;
				}
			}
		}
		return $return;
	}
}
?>
