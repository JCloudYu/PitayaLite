<?php
	final class PBScope {
		private $_scope_levels;
		public function __construct( $stack = '', $seperator = ' ' ) {
			if ( !is_array($stack) )
				$stack = empty($stack) ? array() : explode( "{$seperator}", "{$stack}" );
			$this->_scope_levels = $stack;
		}
		public function set( $breadcrumb = [], $glue = '#' ) {
			if ( is_array($breadcrumb) ) {
				$this->_scope_levels = array_values($breadcrumb);
			}
			else
			if ( $breadcrumb == '' ) {
				$this->_scope_levels = [];
			}
			else {
				$this->_scope_levels = explode( "{$glue}", "{$breadcrumb}" );
			}
			
			
			
			return $this;
		}
		public function append( $breadcrumb = [], $glue = '#' ) {
			if ( is_array($breadcrumb) ) {
				$breadcrumb = array_values($breadcrumb);
			}
			else
			if ( $breadcrumb == '' ) {
				$breadcrumb = [];
			}
			else {
				$breadcrumb = explode( "{$glue}", "{$breadcrumb}" );
			}
			
			array_push( $this->_scope_levels, ...$breadcrumb );
			
			
			
			return $this;
		}
		
		
		
		public function push( $item ) {
			$arguments = func_get_args();
			if ( count($arguments) == 0 )
				return FALSE;

			foreach( $arguments as $item )
				array_push( $this->_scope_levels, $item );
			return TRUE;
		}
		public function pop() {
			return @array_pop( $this->_scope_levels );
		}
		public function breadcrumb( $glue = '#' ) {
			return implode( $glue,
				ary_filter( $this->_scope_levels, function($item) use( $glue ) {
					return str_replace( $glue, "_", "{$item}" );
				})
			);
		}
		public function accept( $acceptList = [], $glue = '#' ) {
			$currentScope = $this->breadcrumb( $glue );
			return in_array($currentScope, $acceptList);
		}
		public function __toString() { return $this->breadcrumb(); }
	}
	
	
	
	function PBScope() {
		static $_singleton = NULL;
		if ( $_singleton === NULL ) {
			$_singleton = new PBScope();
		}
		
		return $_singleton;
	}
