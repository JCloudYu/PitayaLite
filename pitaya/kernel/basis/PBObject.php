<?php
	/**
	 * Class PBObject
	 * @property-read string $class
	 */
	class PBObject {
		private static $_getPrefix = "__get_";
		private static $_setPrefix = "__set_";

		public function &__get($name) {

			$getTarget = self::$_getPrefix.$name;
			$setTarget = self::$_setPrefix.$name;
			if ( method_exists($this, $getTarget) )
			{
				@$result = &$this->{$getTarget}();
				return $result;
			}
			else
			if ( method_exists($this, $setTarget) )
				throw(new Exception("Getting value from an set-only property '{$name}'."));
			else
				throw(new Exception("Getting value from an undefined property '{$name}'."));
		}
		public function __set($name, $value) {

			$getTarget = self::$_getPrefix.$name;
			$setTarget = self::$_setPrefix.$name;
			if(method_exists($this, $setTarget))
				return $this->{$setTarget}($value);
			else
			if(method_exists($this, $getTarget))
				throw(new Exception("Setting value to an get-only property '{$name}'."));
			else
				throw(new Exception("Setting value to an undefined property '{$name}'."));
		}
		public function __get_class() {
			return get_class($this);
		}
	}
