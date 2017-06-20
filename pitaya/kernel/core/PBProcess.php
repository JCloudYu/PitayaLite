<?php
	class PBProc extends PBObject {
		/** @var PBKernel */
		private $_system = NULL;
		
		private $_bootSequence	= [];
		private $_dupSequence	= [];
		private $_entryModule	= NULL;
		private $_mainModuleId	= NULL;
		private $_executing		= FALSE;
		
		
		
		public function __construct( $sysInst ) {
			$this->_system = $sysInst;
		}
		public function __get_entryModule() {
			return $this->_entryModule;
		}
	
	
		
	
	
	
		
		public function run() {
			if ( $this->_executing ) return;
			$this->_executing = TRUE;
		
			$dataInput = PBRequest()->resource;
			while( count($this->_bootSequence) > 0 ) {
				$item = array_shift($this->_bootSequence);
				$module = PBModule($item->id);
				if ( !$item->pre ) {
					$item->pre = TRUE;
					$prerequisites = $module->precondition();
					if ( is_array($prerequisites) && count($prerequisites) > 0 ) {
						array_unshift($this->_bootSequence, $item);
						$this->_prependBootSequence($prerequisites);
						continue;
					}
				}
				
				$dataInput = $module->execute( $dataInput );
				$this->_prependBootSequence( $module->bootChain );
			}
			
			$this->_executing = FALSE;
		}
		public function prepareQueue($entryModule) {
			$G_CONF = PBStaticConf( 'pitaya-env' );
			
			foreach( $G_CONF[ 'leading-modules' ] as $moduleName ) {
				$module = PBModule( $moduleName, TRUE );
				$this->_bootSequence[] = stdClass([
					'id'	=> $module->id,
					'pre'	=> FALSE
				]);
			}
			foreach( self::$_LEADING_MODULES as $moduleName ) {
				$module = PBModule( $moduleName, TRUE );
				$this->_bootSequence[] = stdClass([
					'id'	=> $module->id,
					'pre'	=> FALSE
				]);
			}
	
	
	
			// NOTE: Service Entry Module
			$this->_entryModule = PBModule($entryModule);
			$this->_mainModuleId = $this->_entryModule->id;
			$this->_bootSequence[] = stdClass([
				'id'	=> $this->_mainModuleId,
				'pre'	=> FALSE
			]);
			
	
	
			foreach( $G_CONF[ 'tailing-modules' ] as $moduleName ) {
				$module = PBModule( $moduleName, TRUE );
				$this->_bootSequence[] = stdClass([
					'id'	=> $module->id,
					'pre'	=> FALSE
				]);
			}
			foreach( self::$_TAILING_MODULES as $moduleName ) {
				$module = PBModule( $moduleName, TRUE );
				$this->_bootSequence[] = stdClass([
					'id'	=> $module->id,
					'pre'	=> FALSE
				]);
			}
		}
		public function getNextModule() {
			$desc = @$this->_bootSequence[0];
			if ( $desc === NULL ) {
				return NULL;
			}
			
			return PBModule($desc->id);
		}
		public function cancelNextModule() {
			$desc = @array_shift($this->_bootSequence);
			return $desc !== NULL;
		}
		public function cancelModules( $keeps = NULL ) {
			if ( func_num_args() == 0 ) {
				$this->_bootSequence = [];
			}
			elseif ( is_numeric( $keeps ) ) {
				if ( $keeps > 0 ) {
					@array_splice($this->_bootSequence, 0, $keeps);
				}
				else
				if ( $keeps < 0 ) {
					@array_splice( $this->_bootSequence, $keeps );
				}
			}
			else {
				if ( !is_array( $keeps ) ) $keeps = [ $keeps ];
			
				while ( count($this->_bootSequence) > 0 ) {
					$item = array_shift($this->_bootSequence);
					$module = PBModule($item->id);
					
					
					
					$valid = FALSE;
					foreach( $keeps as $name ) {
						if ( $module instanceof $name ) {
							$valid = TRUE;
							break;
						}
					}
					
					if ( $valid ) {
						array_unshift($this->_dupSequence, $item);
					}
				}
				
				
				
				$temp = &$this->_dupSequence;
				$this->_dupSequence = &$this->_bootSequence;
				$this->_bootSequence = &$temp;
			}
		}
		
		
		
		
		
		
		private static $_LEADING_MODULES = [];
		public static function LEADING_MODULES($modules=[]) {
			if ( func_num_args() == 0 ) {
				return self::$_LEADING_MODULES;
			}
			else {
				if ( empty($modules) ) {
					return NULL;
				}
				
				
				
				if ( is_string($modules) ) {
					$modules = [$modules];
				}
				
				if ( is_array($modules) ) {
					self::$_LEADING_MODULES = $modules;
				}
				
				return NULL;
			}
		}
		
		private static $_TAILING_MODULES = [];
		public static function TAILING_MODULES($modules=[]) {
			if ( func_num_args() == 0 ) {
				return self::$_TAILING_MODULES;
			}
			else {
				if ( empty($modules) ) {
					return NULL;
				}
				
				
				
				if ( is_string($modules) ) {
					$modules = [$modules];
				}
				
				if ( is_array($modules) ) {
					self::$_TAILING_MODULES = $modules;
				}
				
				return NULL;
			}
		}
		
		
		
		private function _prependBootSequence( $bootSequence ) {
			if ( !is_array( $bootSequence )) return;
	
	
			$bootSequence = array_reverse( $bootSequence );
			foreach( $bootSequence as $illustrator ) {
				if (is_a($illustrator, stdClass::class)) {
					$illustrator = (array)$illustrator;
				}
	
				if (!is_array($illustrator)) {
					$illustrator = [ 'module' => $illustrator ];
				}
				
				
					
				$moduleHandle = @$illustrator[ 'module' ];
				if ( empty($moduleHandle) ) continue; // Skipping empty values
	
				$reuse = array_key_exists( 'reuse', $illustrator ) ? !empty($illustrator['reuse'] ) : TRUE;
				array_unshift( $this->_bootSequence, stdClass([
					'id'	=> PBModule( $moduleHandle, $reuse )->id,
					'pre'	=> FALSE
				]));
			}
		}
	}
	
	
	
	function PBProc($sysInst = NULL) {
		static $_singleton = NULL;
		if ( $_singleton === NULL ) {
			$_singleton = new PBProc( $sysInst );
		}
		
		return $_singleton;
	}
