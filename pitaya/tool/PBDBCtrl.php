<?php
	using('tool.ExtPDO');

	final class PBDBCtrl {
		public static function DB( $identifier = NULL, $conInfo = NULL, $options = array( 'CREATE_VAR' ) )
		{
			static $_dbSources = array();

			if ( is_array($identifier) )
			{
				$key			= 0;
				$connectInfo	= $identifier;
				$createOpt		= is_array($conInfo) ? $conInfo : array( 'CREATE_VAR' );
			}
			else
			{
				$key			= empty($identifier) ? 0 : "_IDENTITY_{$identifier}";
				$connectInfo	= is_array($conInfo) ? $conInfo : array();
				$createOpt		= is_array($options) ? $options : array( 'CREATE_VAR' );
			}


			if ( !empty($_dbSources[ $key ]) && !in_array('FORCE_CREATE', $createOpt) )
				return $_dbSources[ $key ];

			if ( $connectInfo )
			{
				if ( !empty($_dbSources[ $key ]) )
					unset($_dbSources[ $key ]);

				return ( $_dbSources[ $key ] = self::CONNECT( $connectInfo, $createOpt ) );
			}

			return NULL;
		}
		public static function GEN_CONNECT_INFO( $db, $account, $password, $host = "localhost", $port = 3306 )
		{
			return array(
				'host'		=> $host,
				'port'		=> $port,
				'db'		=> $db,
				'account'	=> $account,
				'password'	=> $password
			);
		}
		public static function CONNECT($param = NULL, $option = array('CREATE_VAR'))
		{
			$dsn = ExtPDO::DSN($param['host'], $param['db'], $param['port']);
			$connection = new ExtPDO($dsn, $param['account'], $param['password'], $option);
			$connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
			$connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			$connection->setAttribute(PDO::ATTR_EMULATE_PREPARES, FALSE);
			return $connection;
		}
		public static function LIMIT($SQL, $page = NULL, $pageSize = NULL, &$pageInfo = NULL, &$DB = NULL)
		{
			if (is_array($SQL))
			{
				$sql	= $SQL['sql'];
				$param	= $SQL['param'];
			}
			else
			{
				$sql	= $SQL;
				$param	= array();
			}

			$sql = trim($sql);
			$sql = preg_replace('/(select)(.|[\n])*(from([^;]|[\n])*);*/i', "$1 count(*) as count $3", $sql, -1);
			$DB_CONNECTION = empty($DB) ? PBDBCtrl::DB() : $DB;
			$countResult = $DB_CONNECTION->fetch($sql, $param);


			$totalCount = $countResult['count'];
			$page		= CAST($page,		'int');
			$pageSize	= CAST($pageSize,	'int');

			if (!empty($pageSize))
			{
				$totalPages = ceil((float)$totalCount/(float)$pageSize);
				$page = min(max($page, 1), max($totalPages, 1));

				$from = ($page - 1) * $pageSize;
				$limitClause = "{$from},{$pageSize}";
			}
			else
			{
				$totalPages = $page = 1;
				$pageSize = $totalCount;

				$LIMIT_MAX = ($totalCount < 1) ? 1 : $totalCount;
				$limitClause = "0,{$LIMIT_MAX}";
			}



			$pageInfo = array(
				'pageSize'		=> $pageSize,
				'page'			=> $page,
				'totalPages'	=> $totalPages,
				'totalRecords'	=> $totalCount
			);

			return $limitClause;
		}
		public static function SET($data, &$param = NULL, $varIndex = FALSE)
		{
			$param = $sql = array();
			foreach ($data as $col => $val)
			{
				if ($varIndex)
				{
					$stmt = "`{$col}` = :{$col}";
					$param[":{$col}"] = $val;
				}
				else
				{
					$stmt = "`{$col}` = ?";
					$param[] = $val;
				}

				$sql[] = $stmt;
			}
			return implode(', ', $sql);
		}
		public static function ORDER($orderOpt = array())
		{
			if (!is_array($orderOpt)) return '';

			$orderStmt = array();
			$hasRandom = FALSE;
			foreach ($orderOpt as $colName => $sequence)
			{
				if ( strtoupper($colName) == "RANDOM" && strtoupper($sequence) == "RANDOM" )
				{
					$hasRandom = TRUE;
					break;
				}
				else
				{
					$seq = (in_array(strtoupper("{$sequence}"), array('ASC', 'DESC'))) ? " $sequence" : "";
					$orderStmt[] = "`{$colName}`{$seq}";
				}
			}
			$orderStmt = ($hasRandom) ? "RAND()" : implode(', ', $orderStmt);

			if (empty($orderStmt)) return '';

			return $orderStmt;
		}
	}
