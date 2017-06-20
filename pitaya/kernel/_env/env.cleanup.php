<?php
	function DEPRECATION_WARNING( $message, $forceOutput = FALSE ) {
		if ( !DEBUG_BACKTRACE_ENABLED ) return;
	
		$trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
		$scopeInfo = $trace[1];
		$message = "{$message} @{$scopeInfo['file']}:{$scopeInfo['line']}";
		
		PBLog( 'error' )->log( $message );
		if ( $forceOutput ) echo $message . EOL;
	}
	function DEBUG_WARNING( $message, $forceOutput = FALSE ) {
		if ( !DEBUG_BACKTRACE_ENABLED ) return;
	
		$trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
		$scopeInfo = $trace[1];
		$message = "{$message} @{$scopeInfo['file']}:{$scopeInfo['line']}";
		
		PBLog( 'error' )->log( $message );
		if ( $forceOutput ) echo $message . EOL;
	}



	$_SERVER['argv'] = @$_SERVER['argv'] ?: [];
	$_SERVER['argc'] = count($_SERVER['argv']);


	PBRequest::__imprint_constants();

	// INFO: Clean up everything
	unset($GLOBALS[ 'extPath'] );
	unset($GLOBALS[ 'STANDALONE_EXEC'] );
