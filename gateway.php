<?php
	define( 'IS_CLI_ENV', php_sapi_name() === "cli" );
	define( 'IS_HTTP_ENV', !IS_CLI_ENV );
	
	define( 'IS_WIN_ENV', (strtoupper(substr( PHP_OS, 0, 3 )) === 'WIN') );
	define( 'SPACE_ROOT', realpath(dirname("{$_SERVER['SCRIPT_FILENAME']}")) );
	require_once SPACE_ROOT . '/lib.php';
	chdir( SPACE_ROOT ); // INFO: Change working directory to space root
	
	
	
   @include_once SPACE_ROOT . "/pitaya.config.php";
	s_define( 'SYS_TIMEZONE', 'UTC' );
	s_define( 'PITAYA_PATH', SPACE_ROOT . '/pitaya' );
	
	date_default_timezone_set( SYS_TIMEZONE );
	$pitayaRootPath = PITAYA_PATH;
	if ( IS_WIN_ENV && !is_dir( $pitayaRootPath ) && is_file( "{$pitayaRootPath}.lnk" ) ) {
		$pitayaRootPath = resolve_lnk( "{$pitayaRootPath}.lnk" );
	}
	$pitayaRootPath = realpath($pitayaRootPath);
	define( 'PITAYA_ROOT', $pitayaRootPath );
	unset($pitayaRootPath);
	
	
	
	require_once PITAYA_ROOT . "/base.php";
	using( 'kernel.sys' );
	PBKernel::boot( @$_SERVER['argv'] );
