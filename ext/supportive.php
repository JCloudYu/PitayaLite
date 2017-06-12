<?php
	function _R( $route = NULL ) {
		static $_singleton = NULL;
		if ( $_singleton === NULL ) {
			$_singleton = new PBDataTree();
		}
		
		return (func_num_args() > 0) ? $_singleton->travel($route) : $_singleton;
	}
	
	function object($item = NULL, $force = FALSE) {
		return call_user_func_array( 'stdClass', func_get_args() );
	}