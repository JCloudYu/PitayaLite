<?php
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
		'debug-dialog-width' => @constant( 'DEBUG_DIALOG_WIDTH' ),
		'system-timezone' => @constant( 'SYS_TIMEZONE' ),
		'throw-exceptions' => @constant( 'THROW_EXCEPTIONS' ),
		'log-exceptions' => @constant( 'LOG_EXCEPTIONS' ),
		'attach-depth' => @constant( 'ENV_ATTACH_DEPTH' ) ?: @constant( 'ENV_ATTACH_LEVEL' ) ?: 0,
		
		
		'leading-modules' => [],
		'tailing-modules' => [],
		'log-dir' => @constant( 'SYS_LOG_DIR' ) ?: __ROOT . '/log'
	]);
