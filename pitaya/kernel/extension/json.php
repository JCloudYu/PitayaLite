<?php
	abstract class PBJSONContainer implements ArrayAccess, Iterator {
		protected $_container = array();
		protected $_end		  = TRUE;

		abstract function safe_cast();
		public function __construct( $data ) {
			if ( func_num_args() > 0 && is_array($data) ) {
				$this->_container = $data;
			}
		}
		
		public function offsetSet( $offset, $value ) {
			if ( $offset === NULL )
				$this->_container[] = $value;
			else
				$this->_container[ $offset ] = $value;
		}
		public function& offsetGet( $offset ) {
			return $this->_container[ $offset ];
		}
		public function offsetExists( $offset ) {
			return array_key_exists( $offset, $this->_container );
		}
		public function offsetUnset( $offset ) {
			unset( $this->_container[ $offset ] );
		}

		public function& __get( $name ) {
			return $this[$name];
		}
		public function __set( $name, $value ) {
			$this[ $name ] = $value;
		}
		public function __isset( $name ) {
			return isset( $this[$name] );
		}
		public function __unset( $name ) {
			unset( $this[$name] );
		}

		public function& current () {
			return current($this->_container);
		}
		public function key() {
			return key($this->_container);
		}
		public function next() {
			$this->_end = !next($this->_container);
		}
		public function rewind() {
			reset($this->_container);
		}
		public function valid() {
			return ( count($this->_container) <= 0 ) ? FALSE : $this->_end;
		}
		public static function Flatten( $content ) {

			if ( is_a( $content, 'stdClass' ) )
			{
				$result = new stdClass();
				foreach ( $content as $key => $value )
					$result->{$key} = self::Flatten($value);
				return $result;
			}
			else
			if ( is_array( $content ) )
			{
				foreach ( $content as $key => $value )
					$content[$key] = self::Flatten($value);
				return $content;
			}
			else
			if ( is_a( $content, 'PBJSONContainer' ) )
				return $content->safe_cast();


			return $content;
		}
	}
	
	class PBJSONObject extends PBJSONContainer {
		public function safe_cast() {
			foreach ( $this->_container as $key => $value )
				$this->_container[$key] = PBJSONContainer::Flatten( $value );

			return (object)$this->_container;
		}
	}
	function PBJSONObject( $data = NULL ) {
		return new PBJSONObject( $data );
	}
	
	class PBJSONArray extends PBJSONContainer {
		public function safe_cast() {
			foreach ( $this->_container as $key => $value )
				$this->_container[$key] = PBJSONContainer::Flatten( $value );

			return array_values($this->_container);
		}
	}
	function PBJSONArray( $data = NULL ) {
		return new PBJSONArray( $data );
	}
	
	
	class PBJSONCast {
		public $data = NULL;
		public function __construct( &$carriedData = NULL ) {
			$this->data = $carriedData;
		}
		public function __invoke( $output = FALSE ) {
			$outData = $this->data;
			if (is_a($outData, 'PBJSONContainer')) {
				$outData = $outData->safe_cast();
			}
			if ($output) {
				echo json_encode($outData);
				return;
			}
			
			return json_encode($outData);
		}
		public function __toString() { 
			return $this(FALSE);
		}
	}
	function PBJSONCast( $jsonData=NULL ) {
		return new PBJSONCast($jsonData);
	}
