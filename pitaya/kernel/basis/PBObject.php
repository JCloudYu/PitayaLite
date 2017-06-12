<?php
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
		

/*
		protected function __get_caller() {
			if ( !DEBUG_BACKTRACE_ENABLED ) return NULL;
			DEBUG_WARNING( "PBObject::caller is designed for debugging! It can be harmful to your system performance" );

			$tempBacktrace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT);
			$tempBacktrace = array_reverse($tempBacktrace);

			$backtrace = array();
			$item = array_shift($tempBacktrace);
			while( $item !== NULL) {
				$backtrace[] = $item;
				if("{$item['function']}" == '__get' || "{$item['function']}" == '__set')
				{
					$item = array_shift($tempBacktrace);
					if(preg_match('/^__set_|^__get_/', $item['function']) !== 1)
					{
						$backtrace[] = $item;
					}
				}

				$item = array_shift($tempBacktrace);
			}
			$backtrace = array_reverse($backtrace);

			array_shift($backtrace);

			if(count($backtrace) <= 1)
				return NULL;
			else
				return $backtrace[1];
		}
*/
	}
