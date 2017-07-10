<?php
	require_once __DIR__ . '/kernel/_env/env.independent.php';
	final class PitayaRuntime {
		private static $_singleton = NULL;
		public static function Pitaya() {
			if ( self::$_singleton === NULL ) {
				self::$_singleton = new PitayaRuntime();
			}
			
			return self::$_singleton;
		}
		
		
		
		private $_executed = FALSE;
		private function __construct() {}
		
		private $_initArgs = [];
		public function prepare($data = []) {
			foreach( $data as $key => $value ) {
				$this->_initArgs[$key] = $value;
			}
			
			return $this;
		}
		
		public function execute() {
			$this->_executed = TRUE;			
			PBStaticConf( 'pitaya-env', [
				'space-root' => @$this->_initArgs[ 'space-root' ],
				'default-basis' => @$this->_initArgs[ 'default-basis' ] ?: 'main',
				'entry-module' => @$this->_initArgs[ 'entry-module' ] ?: NULL,
				'attach-depth' => @$this->_initArgs[ 'attach-depth' ] ?: 0,
				'module-packages' => is_array(@$this->_initArgs['module-packages']) ? $this->_initArgs['module-packages'] : [],
				'debug-mode' => !!@$this->_initArgs[ 'debug-mode' ],
				'debug-dialog-width' => @$this->_initArgs[ 'debug-dialog-width' ] ?: 350,
				'throw-exceptions' => !!@$this->_initArgs[ 'throw-exceptions' ],
				'log-exceptions' => (@$this->_initArgs[ 'log-exceptions' ] === NULL) ? TRUE : !!@$this->_initArgs[ 'log-exceptions' ],
				'system-timezone' => @$this->_initArgs[ 'system-timezone' ] ?: 'UTC',
				'boot-scripts' => is_array(@$this->_initArgs['boot-scripts']) ? $this->_initArgs['boot-scripts'] : [],
				'packages' => is_array(@$this->_initArgs['packages']) ? $this->_initArgs['packages'] : [],
				'leading-modules' => is_array(@$this->_initArgs['leading-modules']) ? $this->_initArgs['leading-modules'] : [],
				'tailing-modules' => is_array(@$this->_initArgs['tailing-modules']) ? $this->_initArgs['tailing-modules'] : [],
				'log-dir' => @$this->_initArgs[ 'log-dir' ] ?: FALSE
			]);
			
			require_once __DIR__ . '/boot.php';
			PBKernel::boot();
		}
	}
	function Pitaya($initArgs=[]) {
		return PitayaRuntime::Pitaya()->prepare($initArgs);
	}
