<?php
	function DEPRECATION_WARNING( $message, $forceOutput = FALSE ) {
		if ( !DEBUG_BACKTRACE_ENABLED ) return;
	
		$trace = debug_backtrace();
		$scopeInfo = $trace[1];
		$message = "{$message} @{$scopeInfo['file']}:{$scopeInfo['line']}";
		
		PBLog::ERRLog( $message, 'deprecated.pblog' );
		if ( $forceOutput ) echo $message . EOL;
	}
	function DEBUG_WARNING( $message, $forceOutput = FALSE ) {
		if ( !DEBUG_BACKTRACE_ENABLED ) return;
	
		$trace = debug_backtrace();
		$scopeInfo = $trace[1];
		$message = "{$message} @{$scopeInfo['file']}:{$scopeInfo['line']}";
		
		PBLog::ERRLog( $message, 'debug-warning.pblog' );
		if ( $forceOutput ) echo $message . EOL;
	}



	PBKernel::__imprint_constants();
	PBRequest::__imprint_constants();
