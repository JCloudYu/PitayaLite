<?php
	Pitaya([
		'space-root' => __ROOT,
		'packages' => [
			'broot'		=> @constant( 'BASIS_PATH' ) ?: __ROOT.'/basis',
			'share' 	=> @constant( 'SHARE_PATH' ) ?: __ROOT.'/share',
			'data'		=> @constant( 'DATA_PATH' ) ?: __ROOT.'/data',
			'lib'		=> @constant( 'LIB_PATH' )  ?: __ROOT.'/lib'
		],
		'module-packages' => [
			'data.modules', 'share.modules'
		],
		'boot-scripts' => [
			'broot.boot', 'share.boot', 'root.boot'
		],
		'default-basis'	=> (php_sapi_name() == 'cli') ? 'cli' : 'index',
		'debug-mode' => @constant( 'DEBUG_MODE' ),
		'debug-dialog-width' => @constant( 'DEBUG_DIALOG_WIDTH' ),
		'system-timezone' => @constant( 'SYS_TIMEZONE' ),
		'throw-exceptions' => @constant( 'THROW_EXCEPTIONS' ),
		'log-exceptions' => @constant( 'LOG_EXCEPTIONS' ),
		'attach-depth' => @constant( 'ENV_ATTACH_DEPTH' ) ?: @constant( 'PITAYA_ENVIRONMENTAL_ATTACH_LEVEL' ) ?: 0,
		
		
		'leading-modules' => [],
		'tailing-modules' => [],
		'log-dir' => @constant( 'SYS_LOG_DIR' ) ?: __ROOT . '/log'
	]);
