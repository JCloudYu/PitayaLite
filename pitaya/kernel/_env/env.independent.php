<?php
	final class PBStaticConf implements ArrayAccess {
		private $_storage = NULL;
		public function __construct($initData=[]) {
			$this->_storage = self::DeepCopy($initData);
		}
		public function offsetGet($offset) {
			return $this->_storage[$offset];
		}
		public function offsetSet($offset, $value) {
			throw new Exception( "Operation prohibited!" );
		}
		public function offsetUnset($offset) {
			throw new Exception( "Operation prohibited!" );
		}
		public function offsetExists($offset) {
			return @array_key_exists($offset, $this->_storage);
		}
		
		public static function DeepCopy($data, $toObject=FALSE) {
			$IS_ARY = is_array($data);
			$IS_OBJ = is_a($data, stdClass::class);
			if ( !$IS_ARY && !$IS_OBJ ) {
				return $data;
			}
		
		
		
			$dup = $toObject ? (object)[] : [];
			foreach( $data as $idx => $val ) {
				if ( $toObject ) {
					$dup->{$idx} = self::DeepCopy($val, $toObject);
				}
				else {
					$dup[ $idx ] = self::DeepCopy($val, $toObject);
				}
			}
			return $dup;
		}
	}
	function PBStaticConf($identifier=NULL, $conf=NULL) {
		static $_CONF_MAP = [];
		static $_DEFAULT_CONF = NULL;
		
		
		if (func_num_args() == 0) {
			return $_DEFAULT_CONF;
		}
		
		$identifier = "{$identifier}";
		$CONFIG = @$_CONF_MAP[$identifier];
		if ( func_num_args() == 1 ) {
			return $CONFIG;
		}
		
		if ( $CONFIG !== NULL || ( !is_array($conf) && !is_a($conf, stdClass::class)) ) {
			return FALSE;
		}
		
		$CFG = $_CONF_MAP[$identifier] = new PBStaticConf($conf);
		if ( $_DEFAULT_CONF === NULL ) {
			$_DEFAULT_CONF = $CFG;
		}
		
		
		return $CFG;
	}