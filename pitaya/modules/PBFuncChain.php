<?php
	class PBFuncChain extends PBModule implements ArrayAccess {
		private $_procStack = [];
		public function execute( $param ) {
			foreach ( $this->_procStack as $call ) {
				if ( !is_callable( $call ) ) continue;
				$param = $call( $param, $this->data );
			}

			return $param;
		}
		
		public function offsetExists($offset) {
			return array_key_exists($offset, $this->_procStack);
		}
		public function offsetGet($offset) {
			return @$this->_procStack[$offset];
		}
		public function offsetSet($offset, $value) {
			if ( $offset === NULL )
				$this->_procStack[] = $value;
			else
				$this->_procStack[$offset] = $value;
		}
		public function offsetUnset($offset) {
			unset($this->_procStack[$offset]);
		}
		
		
		
		// region [ DEPRECATED ]
		public function __set_item( $call ) {
			DEPRECATION_WARNING("PBFuncChain::item property is marked as deprecated! Please refer to PBFuncChain[] operator!");
			$this[] = $call;
		}
		public function add( $callable ) {
			DEPRECATION_WARNING("PBFuncChain::add api is marked as deprecated! Please refer to PBFuncChain[] operator!");
			$this[] = $callable;
		}
		// endregion
	}
