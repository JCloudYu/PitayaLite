<?php
	/**
	 ** 1028.CSMS-BDF - PBDBCollector.php
	 ** Created by JCloudYu on 2015/10/14 18:01
	 **/
	class PBDBCollector
	{
		public static function CollectByField( PDOStatement $stmt, $field = 'id' )
		{
			$result	= array();

			if ( !empty($field) )
				while ( ($row = $stmt->fetch()) !== FALSE ) $result[$row["{$field}"]] = $row;
			else
				while ( ($row = $stmt->fetch()) !== FALSE ) $result[] = $row;

			return $result;
		}

		public static function CollectByFilter( PDOStatement $stmt, $filterFunc = NULL, $skipValue = FALSE )
		{
			$result	= array();
			$func = (is_callable($filterFunc)) ? $filterFunc : function($item){ return $item; };

			while ( ($row = $stmt->fetch()) !== FALSE )
			{
				$index = NULL;
				$filterResult = $func($row, $index);

				if ( (func_num_args() > 2) && ($filterResult === $skipValue) ) continue;

				if ( $index !== NULL )
					$result[ $index ] = $filterResult;
				else
					$result[] = $filterResult;
			}

			return $result;
		}
	}
