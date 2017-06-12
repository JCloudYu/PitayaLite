<?php
	using( 'sys.tool.PBScriptCtrl' );

	final class PBLocale extends PBObject implements ArrayAccess {
		public static function Locale() {
			/** @var PBLocale $__locale_singleton */
			static $__locale_singleton = NULL;
			if ($__locale_singleton !== NULL) return $__locale_singleton;

			$__locale_singleton = new PBLocale();
			return $__locale_singleton;
		}
		public static function Translate($token, $param = array()) {
			if ( !is_array($param) ) $param = [];

			$locale = self::Locale();
			return strtr($locale[$token], $param);
		}



		
		private $_curLocale 	= FALSE;
		private $_storedLocales = [];
		public function __construct() {
			$this->_basePackage = self::$_sharedBasePackage;
			$this->_localeLibPath = path($this->_basePackage);
		}



		private $_localeLibPath = '';
		public function __get_path() {
			return $this->_localeLibPath;
		}
		public function __set_path($path) {
			$this->_localeLibPath = $path;
		}
		
		public function __get_locale() { 
			return $this->_curLocale;
		}
		public function __set_locale($value) {
			$localeName = "{$value}";
			if ( empty($localeName) ) return;
			if ( !empty($this->_storedLocales[$localeName]) ) return;
			
			$localePath	= "{$this->_localeLibPath}/{$localeName}/locale.php";
			if ( !is_file($localePath) || !is_readable($localePath) ) return;
			
			
			$this->_curLocale = $localeName;
			$locale	= PBScriptCtrl::Imprint( $localePath );
			$locale	= array_key_exists( 'locale', $locale ) ? $locale['locale'] : [];
			$this->_storedLocales[$this->_curLocale] = $locale;
		}



		public function offsetExists($offset) { 
			return TRUE;
		}
		public function offsetUnset($offset) {
			if ( $this->_curLocale === FALSE ) return;
			unset($this->_storedLocales[$this->_curLocale][$offset]);
		}
		public function offsetSet($offset, $value) {
			if ( $this->_curLocale === FALSE ) return;
			$this->_storedLocales[$this->_curLocale][$offset] = $value;
		}
		public function offsetGet($offset) {
			if ( $this->_curLocale === FALSE ) return $offset;
			
			if ( is_array(@$this->_storedLocales[$this->_curLocale][ $offset ]) )
				return $this->_storedLocales[$this->_curLocale][ $offset ];
			
			return strtr($offset, @$this->_storedLocales[$this->_curLocale] ?: []);
		}
		
		
		
		// region [ Deprecated ]
		private static $_sharedBasePackage = NULL;
		public static function basePackage($packagePath = NULL) {
			DEPRECATION_WARNING( "PBLocale::basePackage api is marked as deprecated!" );
			self::$_sharedBasePackage = "{$packagePath}";
		}
		
		private $_basePackage = '';
		public function __get_basePackage() {
			DEPRECATION_WARNING( "PBLocale::basePackage property is marked as deprecated! Please refer to PBLocale::package property!" );
			return $this->_basePackage;
		}
		public function __set_basePackage($value) {
			DEPRECATION_WARNING( "PBLocale::basePackage property is marked as deprecated! Please refer to PBLocale::package property!" );
			$this->_basePackage = "{$value}";
			$this->_localeLibPath = path( $this->_basePackage );
		}
		// endregion
	}
	
	function PBLocale(){
		static $_singleton = NULL;
		if ( $_singleton === NULL ) {
			$_singleton = PBLocale::Locale();
		}
		
		return $_singleton;
	}
