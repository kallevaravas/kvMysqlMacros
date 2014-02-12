class DB {
    protected $default_config = array(
        'server' => 'localhost',
        'database' => '',
        'user' => 'root',
        'password' => '',
        'table_prefix' => 'kv_',
        'mysql_config_array' => 'config_mysql'
    );
	var $conn = NULL;
	var $queryid = NULL;
	var $row = array();
	var $errdesc = "";
	var $errno = NULL;
	var $query_count = NULL;
	var $query_count_exp = array();
    function __construct ($config_mysql) {
        $this->config = array_merge($this->default_config, $config_mysql);
		if ($this->conn == 0) {
			if ($this->config['password'] == "") {
				$this->conn = mysql_connect($this->config['server'], $this->config['user']);
			} else {
				$this->conn = mysql_connect($this->config['server'], $this->config['user'], $this->config['password']);
			}
			if (!$this->conn) {
				$this->error("Connection failure");
			}
			if ($this->config['database'] != "") {
				if (!mysql_select_db($this->config['database'], $this->conn)) {
					$this->error("Cannot use database: " . $this->config['database']);
				}
			}
			mysql_query("SET NAMES utf8");
		}
        unset($this->config['password']);
        unset($GLOBALS[$this->config['mysql_config_array']]);
    }
    function __destruct () {
        mysql_close($this->conn);
        if (@mysql_ping($this->conn)) {
            CriticalError('Closing mysql connection was unsuccessful!');
        }
    }
	protected function select_db ($database = "") {
		if($database != "") {
			$this->config['database'] = $database;
		}
		if(!mysql_select_db($this->config['database'], $this->conn)) {
			$this->error("Cannot use database: " . $this->config['database']);
		}
	}
	protected function _format_query_callback ($match, $init = FALSE) {
		static $args = NULL;
		if ($init) {
			$args = $match;
			return;
		}
		switch ($match[1]) {
			case '%d':
				return (int) array_shift($args);
			case '%s':
				return array_shift($args);
			case '%%':
				return '%';
			case '%f':
				return (float) array_shift($args);
			case '%b':
				return db_encode_blob(array_shift($args));
		}
	}
	protected function _format_query ($sql, $args) {
		if (isset($args[0]) and is_array($args[0])) {
			$args = $args[0];
		}
		$this->_format_query_callback($args, TRUE);
		$sql = preg_replace_callback('/(%d|%s|%%|%f|%b)/', array($this, '_format_query_callback'), $sql);
		return $sql;
	}
	function query ($query_string) {
		$args = func_get_args();
		array_shift($args);
		$query_string = str_replace("{TABLE_PREFIX}", $this->config['table_prefix'], $query_string);
		if(count($args) > 0) {
			$query_string = $this->_format_query($query_string, $args);
		}
		$this->queryid = mysql_query($query_string, $this->conn);
		if (!$this->queryid) {
			$this->error("Invalid SQL: " . $query_string);
		}
		$this->query_count++;
		$this->query_count_exp[] = $query_string;
		return $this->queryid;
	}
    function query_array ($query_string, $array = false) {
        if ($array and is_array($array)) {
            $query_string = str_replace("{ARRAY}", implode(', ', $array), $query_string);
        } else {
            $query_string = str_replace("{ARRAY}", 0, $query_string);
        }
        return $this->query($query_string);
    }
	function query_first ($query_string) {
		$args = func_get_args();
		array_shift($args);
		$queryid = $this->query($query_string, $args);
		$returnarray = $this->fetch_array($queryid, $query_string);
		$this->free_result($queryid);
		return $returnarray;
	}
	function fetch_array ($queryid = -1, $query_string = "") {
		if($queryid != -1) {
			$this->queryid = $queryid;
		}
		if(isset($this->queryid)) {
			if(($this->row = mysql_fetch_array($this->queryid)) === FALSE) {
				return null;
			}
		} else {
			if(!empty($query_string)) {
				$this->error("Invalid query id (" . $this->queryid . ") on query string: $query_string");
			} else {
				$this->error("Invalid query id: " . $this->queryid);
			}
		}
		return $this->row;
	}
	function affected_rows () {
		if($this->conn) {
			$result = @mysql_affected_rows($this->conn);
			return $result;
		} else {
			return false;
		}
	}
	protected function free_result ($queryid = -1) {
		if($queryid != -1) {
			$this->queryid = $queryid;
		}
		return @mysql_free_result($this->queryid);
	}
	function get_num_rows () {
		return mysql_num_rows($this->queryid);
	}
	function get_num_fields () {
		return mysql_num_fields($this->queryid);
	}
	function mysql_fetch_assoc ($query) {
		return mysql_fetch_assoc($query);
	}
	function insert_id () {
		return mysql_insert_id($this->conn);
	}
	protected function geterrdesc () {
		$this->error = mysql_error();
		return $this->error;
	}
	protected function geterrno () {
		$this->errno = mysql_errno();
		return $this->errno;
	}
	protected function error ($msg) {
		$this->errdesc = mysql_error();
		$this->errno = mysql_errno();
		$message = 'Error: ' . $this->errdesc . '<br>';
		$message .= 'Date: ' . gmdate("d/m/Y h:I") . '<br>';
		$message .= 'Page: ' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'] . '<br>';
		$message .= '<br>' . $msg . '<br>';
		print '<div style="color:#000;width:600px;margin:10px 0;font: 13px Trebuchet MS1,Trebuchet MS,sans-serif;padding:5px;-webkit-box-shadow: 0 1px 1px 0 #ABABAB;box-shadow: 0 1px 1px 0 #ABABAB;background:#EBEBEB;cursor:default;line-height: 23px;" ondblclick="this.style.display=\'none\';"><b>DATABASE PROBLEM' . ($this->errno ? ' #' . $this->errno : NULL) . '</b><br />' . $message . '</div>';
	}
}
