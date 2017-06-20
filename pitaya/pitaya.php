<?php
	require_once __DIR__ . '/kernel/_env/env.independent.php';
	function Pitaya($initArgs=[]) {
		$config = [
			'space-root' => @$initArgs[ 'space-root' ],
			'default-basis' => @$initArgs[ 'default-basis' ] ?: 'main',
			'entry-module' => @$initArgs[ 'entry-module' ] ?: NULL,
			'attach-depth' => @$initArgs[ 'attach-depth' ] ?: 0,
			'module-packages' => is_array(@$initArgs['module-packages']) ? $initArgs['module-packages'] : [],
			'debug-mode' => !!@$initArgs[ 'debug-mode' ],
			'debug-console-width' => @$initArgs[ 'debug-console-width' ] ?: 350,
			'throw-exceptions' => !!@$initArgs[ 'throw-exceptions' ],
			'log-exceptions' => (@$initArgs[ 'log-exceptions' ] === NULL) ? TRUE : !!@$initArgs[ 'log-exceptions' ],
			'system-timezone' => @$initArgs[ 'system-timezone' ] ?: 'UTC',
			'boot-scripts' => is_array(@$initArgs['boot-scripts']) ? $initArgs['boot-scripts'] : [],
			'packages' => is_array(@$initArgs['packages']) ? $initArgs['packages'] : [],
			'leading-modules' => is_array(@$initArgs['leading-modules']) ? $initArgs['leading-modules'] : [],
			'tailing-modules' => is_array(@$initArgs['tailing-modules']) ? $initArgs['tailing-modules'] : [],
			'log-dir' => @$initArgs[ 'log-dir' ] ?: FALSE
		];
	
		PBStaticConf( 'pitaya-env', $config );
		require_once __DIR__ . '/boot.php';
		PBKernel::boot();
	}