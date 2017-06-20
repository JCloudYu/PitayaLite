<?php

	class ExtPDO extends PDO
	{
		const VARIABLE_TABLE = '__ext_pdo_sys_wide_variables';

		public static function DSN($host, $db, $port = 3306, $driver = 'mysql')
		{
			return "$driver:host=$host;port=$port;dbname=$db;";
		}

		private $__use_Variable = FALSE;
		public function __construct($dsn, $username, $userpass, $option)
		{
			@$forceVar = (($key = array_search('CREATE_VAR', $option)) !== FALSE ) ? TRUE : FALSE;
			unset($option[$key]);

			$option[PDO::MYSQL_ATTR_INIT_COMMAND] = isset($option['charset']) ? "SET NAMES {$option['charset']}" : "SET NAMES utf8";
			unset($option['charset']);

			if (count($option) > 0)
				parent::__construct($dsn, $username, $userpass, $option);
			else
				parent::__construct($dsn, $username, $userpass);

			$this->__use_Variable = $this->__checkVariableCap($forceVar);
		}

		private function __checkVariableCap($forceVariable)
		{
			$tableName = self::VARIABLE_TABLE;

			if ($this->checkTable($tableName)) return TRUE;

			if ($forceVariable)
			{
				return $this->query(<<<SQL
					CREATE TABLE IF NOT EXISTS `{$tableName}` (
						`id` int(11) NOT NULL AUTO_INCREMENT,
						`name` varchar(255) NOT NULL,
						`value` longtext,
						PRIMARY KEY (`id`),
						UNIQUE KEY `name_UNIQUE` (`name`)
					) DEFAULT CHARSET=utf8;
SQL
				);
			}
		}

		public function checkTable($tableName, $updateCache = FALSE)
		{
			$result = $this->fetch("SHOW TABLES LIKE '{$tableName}';");
			return !empty($result);
		}

		public function getTables($updateCache = FALSE) {

			static $tableCache = NULL;

			if ($tableCache !== NULL && $updateCache == FALSE) return $tableCache;

			$tableCache = array();
			$stmt = $this->select("SHOW TABLES;");
			while (($row = $stmt->fetch(PDO::FETCH_NUM)) !== FALSE) $tableCache[] = $row[0];

			return $tableCache;
		}

		public function select($sqlStmt, $stmtVars = NULL) {

			if ($stmtVars)
			{
				$pdoStmt = parent::prepare($sqlStmt);
				if ($pdoStmt === FALSE) return FALSE;

				$pdoStmt->execute($stmtVars);

				return $pdoStmt;
			}

			return parent::query($sqlStmt);
		}

		public function query($sqlStmt, $stmtVars = NULL) {

			if ($stmtVars)
			{
				$pdoStmt = parent::prepare($sqlStmt);
				if ($pdoStmt === FALSE) return FALSE;

				$pdoStmt->execute($stmtVars);
			}
			else
			{
				$pdoStmt = parent::query($sqlStmt);
				if ($pdoStmt === FALSE) return FALSE;
			}

			return $pdoStmt->rowCount();
		}

		public function fetch($sqlStmt, $stmtVars = NULL) {

			if ($stmtVars)
			{
				$pdoStmt = parent::prepare($sqlStmt);
				if (!$pdoStmt) return NULL;

				$pdoStmt->execute($stmtVars);
				$fetchData = $pdoStmt->fetch();
			}
			else
			{
				$pdoStmt = parent::query($sqlStmt);
				if (!$pdoStmt) return NULL;

				$fetchData = $pdoStmt->fetch();
			}

			return $fetchData ? $fetchData : NULL;
		}

		public function fetchAll($sqlStmt, $stmtVars = NULL) {

			if ($stmtVars)
			{
				$pdoStmt = parent::prepare($sqlStmt);
				$pdoStmt->execute($stmtVars);

				$fetchData = $pdoStmt->fetchAll();
			}
			else
			{
				$fetchData =  parent::query($sqlStmt)->fetchAll();
			}

			return $fetchData;
		}

		public function __unset($name) {

			static $table = ExtPDO::VARIABLE_TABLE;
			$this->query("DELETE FROM `$table` WHERE `name` = '$name';");
		}

		public function __get($name) {

			static $table = ExtPDO::VARIABLE_TABLE;
			$row = $this->fetch("SELECT * FROM `$table` WHERE `name` = '$name'");


			return ($row) ? json_decode($row['value'], TRUE) : NULL;
		}

		public function __set($name, $value) {

			static $table = ExtPDO::VARIABLE_TABLE;

			$value = json_encode($value);
			return $this->query("INSERT INTO `$table`(`name`, `value`) VALUES(:name, :value) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`);",
								 array(':name' => $name,
									   ':value' => $value));
		}

		public function __isset( $name ) {
			static $table = ExtPDO::VARIABLE_TABLE;
			$row = $this->fetch("SELECT * FROM `{$table}` WHERE `name` = '{$name}';");

			return !empty($row);
		}

		public function setAttribute($name, $value) {

			return parent::setAttribute($name, $value);
		}


		public function queryUpdate($table, $WHERE = '', $data = array())
		{
			if ( empty($WHERE) ) return FALSE;

			$SET = PBDBCtrl::SET( $data, $param, TRUE );

			if ( is_array($WHERE) )
			{
				foreach ( $WHERE['param'] as $idx => $val ) $param[$idx] = $val;
				$WHERE = $WHERE['where'];
			}

			$BASE_SQL = "UPDATE `{$table}` SET {$SET} WHERE {$WHERE}";
			return $this->query( $BASE_SQL, $param );
		}

		public function queryPickingUpdate( $table, $identity, $field = 'id', $data = array() )
		{
			if ( is_array($identity) ) $identity = implode(',', $identity);

			$WHERE = array(
				'where' => "FIND_IN_SET(`{$field}`, :id)",
				'param' => array( ':id' => $identity )
			);

			return $this->queryUpdate( $table, $WHERE, $data );
		}

		public function queryRemove( $table, $WHERE = '' )
		{
			if ( empty($WHERE) ) return FALSE;

			$param = array();
			if ( is_array($WHERE) )
			{
				foreach ( $WHERE['param'] as $idx => $val ) $param[$idx] = $val;
				$WHERE = $WHERE['where'];
			}

			$BASE_SQL = "DELETE FROM `{$table}` WHERE {$WHERE}";
			return $this->query( $BASE_SQL, $param );
		}

		public function queryPickingRemove( $table, $identity, $field = 'id' )
		{
			if ( is_array($identity) ) $identity = implode(',', $identity);

			$WHERE = array(
				'where' => "FIND_IN_SET(`{$field}`, :id)",
				'param' => array( ':id' => $identity )
			);

			return $this->queryRemove( $table, $WHERE );
		}

		public function queryInsert($table, $data, $duplicated = '')
		{
			if ( !is_array($data) ) return FALSE;
			if ( !is_array( reset($data) ) ) $data = array( $data );

			$indices = array();
			$DUPLICATED = empty( $duplicated ) ? "" : "ON DUPLICATE KEY {$duplicated}";

			foreach ( $data as $value )
			{
				if ( empty($value) )
				{
					$indices[] = FALSE;
					continue;
				}

				$param	= array();
				$SET	= PBDBCtrl::SET( $value, $param );
				$this->query( "INSERT INTO `{$table}` SET {$SET} {$DUPLICATED}", $param );
				$indices[] = $this->lastInsertId();
			}

			return ( count($indices) > 1 ) ? $indices : @array_shift( $indices );
		}

		public function queryAll( $table, $options = array(), &$pageInfo = NULL )
		{
			$BASE_SQL = "SELECT :fields FROM `{$table}` WHERE 1";
			return $this->querySelect( $BASE_SQL, NULL, $options, $pageInfo);
		}

		public function queryPicking( $table, $val, $field = 'id', $options = array(), &$pageInfo = NULL )
		{
			if ( is_array( $val ) ) $val = implode(',', $val);

			$BASE_SQL = "SELECT :fields FROM `{$table}` WHERE FIND_IN_SET(`{$field}`, :id)";
			return $this->querySelect( $BASE_SQL, array(':id' => $val), $options, $pageInfo);
		}

		public function querySelect( $baseSql, $param = NULL, $options = array(), &$pageInfo = NULL )
		{
			$SQL = strtr( $baseSql, array(':fields' => '*') );
			if ( $param ) $SQL = array('sql' => $SQL, 'param' => $param);
			$LIMIT = PBDBCtrl::LIMIT( $SQL, @$pageInfo['page'], @$pageInfo['pageSize'], $pageInfo, $this );

			$ARGS = self::CollectArgument( $options );
			$SQL  = strtr( $baseSql, array(':fields' => $ARGS['FIELDS']) );
			return $this->select( "{$SQL} ORDER BY {$ARGS['ORDER']} LIMIT {$LIMIT};", $param );
		}

		private static function CollectArgument( $options = array() )
		{
			if ( !is_array(@$options['order']) )  $options['order'] = array('id' => 'DESC');
			if ( !is_array(@$options['fields']) ) $options['fields'] = array();

			return array(
				'ORDER'	 => PBDBCtrl::ORDER( $options['order'] ),
				'FIELDS' => emptY( $options['fields'] ) ? '*' : implode(',', $options['fields'])
			);
		}
	}
