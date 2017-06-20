<?php
	abstract class PBIVarStorageAccess {
		abstract public function fetch( $name );
		abstract public function write( $name, $value );
		abstract public function delete( $name );
	}
	final class PBVarStorage {
		/** @var PBIVarStorageAccess */
		private $_dataInterface = NULL;
		
		public function __set($name, $value) {
			if ( ($name == 'delegate') && ( $value instanceof PBIVarStorageAccess ) ) {
				$this->_dataInterface = $value;
				return;
			}
			
			$this->_dataInterface->write($name, $value);
		}
		public function __get($name) {
			if ( $name == 'delegate' ) {
				return $this->_dataInterface;
			}
			
			return $this->_dataInterface->fetch($name);
		}
		public function __unset($name) {
			$this->_dataInterface->delete($name);
		}
	}
	function PBSysConf() {
		static $_singleton = NULL;
		if ( $_singleton === NULL ) {
			$_singleton = new PBVarStorage();
		}
		return $_singleton;
	}
	
	
	final class PBMongoVarStorage extends PBIVarStorageAccess {
		/** @var string */
		private $_ns = NULL;
		
		/** @var PBDataSource */
		private $_source = NULL;
		public function __construct($ns = NULL, PBDataSource $source = NULL) {
			$this->_ns = $ns ?: "db.sys-conf";
			$this->_source = $source ?: PBDataSource();
		}
		
		public function fetch($name) {
			$value = $this->_source->get( $this->_ns, [ 'name' => $name ], [ 'collect' => TRUE ] );
			return (count($value) == 0) ? NULL : current($value)->value;
		}
		
		public function write($name, $value) {
			$this->_source->update(
				$this->_ns, [ 'name' => $name ], [
					'name' => $name, 'value' => $value
				], [ 'upsert' => TRUE ]
			);
		}
		
		public function delete($name) {
			$this->_source->delete( $this->_ns, [ 'name' => $name ] );
		}
	}