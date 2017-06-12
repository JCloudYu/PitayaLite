<?php
	define( 'PITAYA_METRIC_BOOT_TIME',		microtime( TRUE ) );
	s_define( 'DEBUG_MODE', FALSE, TRUE );
	if ( DEBUG_MODE ) {
		define( 'PITAYA_METRIC_BOOT_MEMORY',	memory_get_usage() );
	}


	
	require_once PITAYA_ROOT . '/kernel/env.version.php';

	// Detect minimum PHP Version
	if ( PHP_VERSION_ID < 50600 ) {
		die( "The system requires php 5.6.0 or higher!" );
	}
	
	
	
	require_once PITAYA_ROOT . '/kernel/env.core.php';
	require_once PITAYA_ROOT . '/kernel/env.runtime.php';
	
	

	// INFO: Load system core libraries and prepare system constants
	using( 'kernel.extension.*' );
	using( 'kernel.basis.PBObject' );
	using( 'kernel.basis.*' );
	using( 'kernel.core.*' );
	using( 'kernel.sys' );
	
	
	
	require_once PITAYA_ROOT . "/kernel/env.cleanup.php";
	if ( DEBUG_MODE ) {
		define( 'PITAYA_METRIC_KERNEL_TIME',	microtime( TRUE ) );
		define( 'PITAYA_METRIC_KERNEL_MEMORY',	memory_get_usage() );
	}
