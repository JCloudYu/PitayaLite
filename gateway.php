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
	require_once __PITAYA_LIB_PATH . '/init-lite.php';
	
	Pitaya()->execute();
