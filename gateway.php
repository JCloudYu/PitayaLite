<?php
	@define('__ROOT', realpath(__DIR__));
	
	
	
   @include_once __ROOT . "/pitaya.config.php";
	call_user_func(function(){
		$isWinEnv = (strtoupper(substr( PHP_OS, 0, 3 )) === 'WIN');
	
		$pitayaRootPath = __ROOT.'/pitaya';
		if ( $isWinEnv ) {
			if ( is_file("{$pitayaRootPath}.lnk") ) {
				$lnkPath  = realpath("{$pitayaRootPath}.lnk");
				$shell = new COM('WScript.Shell');
				$shortcut = $shell->createshortcut($lnkPath);
				$pitayaRootPath = $shortcut->targetpath;
			}
		}
		$pitayaRootPath = realpath($pitayaRootPath);
		
		define( '__PITAYA_LIB_PATH', $pitayaRootPath );
	});
	
	
   


	
	require_once __PITAYA_LIB_PATH . '/pitaya.php';
	
	Pitaya([
		'space-root' => __ROOT,
		'packages' => [
			'broot'		=> @constant( 'BASIS_PATH' ) ?: __ROOT.'/app',
			'ext'		=> __ROOT.'/ext',
			'data'		=> __ROOT.'/data'
		],
		'module-packages' => [
			'ext.modules'
		],
		'boot-scripts' => [
			'broot.boot', 'ext.boot', 'root.boot'
		],
		'default-basis'	=> 'main',
		'debug-mode' => @constant( 'DEBUG_MODE' ),
		'debug-console-width' => @constant( 'DEBUG_VIEWPORT_WIDTH' ),
		'system-timezone' => @constant( 'SYS_TIMEZONE' ),
		'throw-exceptions' => @constant( 'THROW_EXCEPTIONS' ),
		'log-exceptions' => @constant( 'LOG_EXCEPTIONS' ),
		'attach-depth' => @constant( 'ENV_ATTACH_DEPTH' ) ?: @constant( 'ENV_ATTACH_LEVEL' ) ?: 0,
		
		
		'leading-modules' => [],
		'tailing-modules' => [],
		'log-dir' => @constant( 'SYS_LOG_DIR' ) ?: __ROOT . '/log'
	]);