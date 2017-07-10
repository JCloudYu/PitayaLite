<?php
	/**
	 * Class PBModule
	 * @property-read string $id
	 * @property-read string $id_short
	 * @property-read string $id_medium
	 * @property-read string $class
	 * @property-read string $class_lower
	 * @property-read string $class_upper
	 * @property-read mixed[] $bootChain
	 * @property-read mixed $error
	 */
	abstract class PBModule extends PBObject {
		const PRIOR_MODULES = [];
		public function precondition() {
			$modules = [];
			foreach( static::PRIOR_MODULES as $moduleName ) {
				$modules[] = $module = PBModule( $moduleName );
				data_fuse( $module->data, $this->data );
			}
			return $modules;
		}
		public function execute( $chainData ) {
			return $chainData;
		}
		public function __invoke() {
			$args = func_get_args();
			return call_user_func( [ $this, 'execute' ], @array_shift($args), ...$args );
		}
		public function __toString() {
			return "{$this( NULL )}";
		}
		


		private $_data = NULL;
		public function &__get_data() {
			if ( $this->_data === NULL ) 
				$this->_data = (object)[];
			
			return $this->_data;
		}	

		private $_instId = NULL;
		public function __set_id( $value ) {
			if ( $this->_instId !== NULL ) return;
			$this->_instId = "{$value}";
		}
		public function __get_id() {
			return $this->_instId;
		}
		public function __get_id_short() {
			return substr( $this->_instId, 0, 8  );
		}
		public function __get_id_medium() {
			return substr( $this->_instId, 0, 16 );
		}
	
		public function __get_class() {
			return get_class($this);
		}
		public function __get_class_lower() {
			return strtolower(get_class($this));
		}
		public function __get_class_upper() {
			return strtoupper(get_class($this));
		}
		
		protected $chain = [];
		public function __get_bootChain() {
			return $this->chain;
		}
		
		protected $error = NULL;
		public function __get_error() {
			return $this->error;
		}
		
		
		


		
		private static $_MODULE_SEARCH_PATHS = [];
		private static function InstantiateModule($identifier) {
			static $_counter = 0;
			
			$moduleDesc = self::ParseModuleIdentifier( $identifier );
			if ( $moduleDesc === FALSE ) {
				throw( new Exception( "Given target module identifier has syntax error!" ) );
			}



			$package  = implode( '.', $moduleDesc[ 'package' ] );
			$module	  = $moduleDesc[ 'module' ];
			$class	  = empty($moduleDesc[ 'class' ]) ? $module : $moduleDesc[ 'class' ];
			$moduleId = md5( "{$package}.{$module}#{$class}&" . (++$_counter) . '?' . microtime() );



			// region [ Search path construction ]
			$G_CONF = PBStaticConf( 'pitaya-env' );

			$moduleSearchPaths = [];
			$moduleSearchPaths[] = "modules.";
			$moduleSearchPaths[] = "basis.";
			
			
			foreach ( $G_CONF[ 'module-packages' ] as $path ) {
				$moduleSearchPaths[] = "{$path}.";
			}
			foreach ( self::$_MODULE_SEARCH_PATHS as $path ) {
				$moduleSearchPaths[] = "{$path}.";
			}
			$moduleSearchPaths[] = ""; // Use global identifier
			$moduleSearchPaths = array_unique($moduleSearchPaths);
			// endregion



			$hit = FALSE;
			foreach ( $moduleSearchPaths as $searchPath ) {
				$searchPkg = "{$searchPath}{$package}";
				$candidate = path($searchPkg, "{$module}.php");
				if (file_exists($candidate)) {
					using($hit="{$searchPkg}.{$module}");
					break;
				}
				
				$candidate = path("{$searchPkg}.{$module}", "{$module}.php");
				if (file_exists($candidate)) {
					using($hit="{$searchPkg}.{$module}.{$module}");
					break;
				}
			}


			
			if ( empty($hit) || !class_exists($class) ) {
				throw(new Exception("Module {$class} doesn't exist!"));
			}



			$invokeModule = "{$class}";
			$moduleObj = new $invokeModule();
			if ( !is_a($moduleObj, PBModule::class) ) {
				throw new Exception( "`{$class}` is not a valid PBModule!" );
			}

			$moduleObj->id = $moduleId;
			return $moduleObj;
		}
		private static function ParseModuleIdentifier( $moduleIdentifier ) {
			$moduleIdentifier = trim( "{$moduleIdentifier}" );
			if ( empty($moduleIdentifier) ) return FALSE;



			$packages	= explode( '.',  "{$moduleIdentifier}" );
			$packages	= ary_filter( $packages, NULL, FALSE );
			$module		= array_pop( $packages );



			$module = explode( '#', $module);
			if ( count( $module ) > 2 ) return FALSE;

			$class	= trim(@"{$module[1]}");
			$module	= trim("{$module[0]}");
			if ( empty( $module ) ) return FALSE;


			return array(
				'package'	=> $packages,
				'module'	=> $module,
				'class'		=> $class
			);
		}
		
		
		
		private static $_ATTACHED_MODULES = [];
		public static function Module($moduleName, $reusable = TRUE, $noThrow = FALSE) {
			if ( is_a($moduleName, PBModule::class) ) {
				if (self::$_ATTACHED_MODULES[$moduleName->id]) {
					return $moduleName;
				}
				elseif ( !$noThrow ) {
					throw new PBException([
						'code' => -1,
						'msg' => "Given module is not a center-controlled module!"
					]);
				}
				else {
					return NULL;
				}
			}
			
			
			
			$module = NULL;
			if( @self::$_ATTACHED_MODULES[$moduleName] ) {
				// Module exists
				$module = self::$_ATTACHED_MODULES[$moduleName];
				
				// Directly selected by id
				if ( $moduleName == $module->id ) {
					return $module;
				}
				
				// Selected by module name and requesting reusable module
				if ( $reusable ) {
					return $module;
				}
			}
	
	
			try {
				$module = self::InstantiateModule($moduleName);
				$moduleId = $module->id;
				self::$_ATTACHED_MODULES[$moduleId] = $module;
				if ( $reusable ) {
					self::$_ATTACHED_MODULES[$moduleName] = $module;
				}
				return $module;
			}
			catch( Exception $e ) {
				if ($noThrow) return NULL;
				throw $e;
			}
		}
		public static function AddSearchPackage($package) {
			if ( empty($package) ) {
				return FALSE;
			}

			$hash = md5($package=trim($package));
			if (@self::$_MODULE_SEARCH_PATHS[$hash] !== NULL) {
				return TRUE;
			}



			if ( !is_dir(path($package)) ) {
				return FALSE;
			}
			
			self::$_MODULE_SEARCH_PATHS[$hash] = $package;
			return TRUE;
		}
		public static function RemoveSearchPackage($package) {
			if ( empty($package) ) {
				return FALSE;
			}

			$hash = md5($package=trim($package));
			unset(self::$_MODULE_SEARCH_PATHS[$hash]);
			return TRUE;
		}
	}
	
	/**
	 * Class PBTplModule
	 * @property-read mixed[] $vars
	 */
	abstract class PBTplModule extends PBModule {
		private $_tplObj = NULL;
	
		public function __get_vars() {
			return empty($this->_tplObj) ? NULL : $this->_tplObj->vars;
		}
		public function __invoke( $output = TRUE ) {
			if ( empty($this->data->tmpl) ) {
				return "";
			}
		
			$this->_tplObj = $tplObj = PBTmplRenderer( $this->data->tmpl, @$this->data->tmplPath ?: NULL );
			data_fuse( $tplObj, $this->data, FALSE );
			
			return $tplObj($output);
		}
		public function __toString() {
			return $this( FALSE );
		}
	}
	function PBModule( $moduleName, $reusable = TRUE, $noThrow = FALSE ) {
		$args = func_get_args();
		return PBModule::Module(...$args);
	}
