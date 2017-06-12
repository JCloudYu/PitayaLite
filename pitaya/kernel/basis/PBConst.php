<?php
	final class PBConst implements ArrayAccess {
		public static function Constant(){ return PBConst(); }
		private function get($name, ...$args) {
			array_unshift($args, $this->{$name});
			return call_user_func_array('CAST', $args);
		}



		// INFO: Magic methods
		public function __set($name, $value) {}
		public function __unset($name) {}
		public function __get($name) {
			return constant($name);
		}
		public function __isset($name) {
			return defined($name);
		}
		
		
		
		// INFO: Array access
		public function offsetSet($offset, $val) {}
		public function offsetUnset($offset) {}
		public function offsetExists($offset) { return isset($this->{$offset}); }
		public function offsetGet($offset) { return $this->{$offset}; }
	}
	
	class_alias( PBConst::class, 'PBConstant' );
	
	
	
	function PBConst() {
		static $_singleton = NULL;
		if ( $_singleton === NULL ) {
			$_singleton = new PBConst();
		}
		
		return $_singleton;
	}