<?php
	final class PBKernel extends PBObject {
		/** @var PBKernelAccessor */
		private static $_SYS_ACCESS_INTERFACE = NULL;
		public static function SYS() {
			return self::$_SYS_ACCESS_INTERFACE;
		}
	
		// region [ Boot Related ]
		private static $_cacheServicePath	= NULL;
		public static function __imprint_constants() {
			static $initialized = FALSE;
			if ( $initialized ) return;

			PBKernel::$_cacheServicePath  = BASIS_ROOT;
		}
		
		/** @var PBKernel */
		private static $_SYS_INSTANCE = NULL;
		public static function boot( $argv = NULL ) {

			// INFO: Avoid repeated initialization
			if ( PBKernel::$_SYS_INSTANCE ) return;



			try
			{
				s_define( 'ENV_ATTACH_LEVEL',	0,		TRUE );



				// INFO: Keep booting
				PBKernel::$_SYS_INSTANCE = new PBKernel();
				PBKernel::$_SYS_ACCESS_INTERFACE = new PBKernelAccessor( PBKernel::$_SYS_INSTANCE );
				
				PBKernel::$_SYS_INSTANCE->__initialize( $argv );
				PBKernel::$_SYS_INSTANCE->_process->run();

				exit(0);
			}
			catch( Exception $e )
			{
				$errMsg = "Uncaught exception: " . $e->getMessage();
				$extMsg = "";

				if ( is_a( $e, 'PBException' ) )
				{
					$descriptor = $e->descriptor;

					if ( !empty($descriptor) )
						$errMsg .= "\nData:\n" . print_r( $descriptor, TRUE );
				}

				if ( LOG_EXCEPTIONS )
				{
					PBLog::SYSLog( print_r($e, TRUE), "system.exception.pblog" );
					$extMsg = "See exception log for more information!";
				}

				PBLog::ERRLog( $errMsg );
				if (!empty($extMsg)) PBLog::ERRLog( $extMsg );



				if ( IS_CLI_ENV )
				{
					PBStdIO::STDERR( $errMsg );
					if (!empty($extMsg)) PBStdIO::STDERR( $extMsg );
				}
				else
				{
					error_log( preg_replace( '/(\n|\s)+/', ' ', $errMsg) );
					if (!empty($extMsg)) error_log( preg_replace( '/(\n|\s)+/', ' ', $errMsg) );
				}



				// INFO: Check vailidaty of default error processing module
				/** @var PBModule */
				$errProcObj = NULL;
				if ( defined( "ERROR_MODULE" ) )
				{
					try
					{
						$errProcObj = PBKernel::$_SYS_INSTANCE->acquireModule( ERROR_MODULE );
					}
					catch( Exception $e )
					{
						$errProcObj = NULL;
					}
				}


				if ( $errProcObj )
				{
					$errProcObj->execute( $e );
				}
				else
				if ( THROW_EXCEPTIONS === TRUE )
				{
					throw( $e );
				}
				else
				if ( IS_HTTP_ENV && DEBUG_MODE )
				{
					if ( !headers_sent() )
					{
						header( "HTTP/1.1 500 Internal Server Error" );
						header( "Status: 500 Internal Server Error" );
						header( "Content-Type: text/plain; charset=utf8" );
					}

					echo $errMsg;
				}



				exit(1);
			}
		}
		
		private static $_bootResolver = NULL;
		public static function SetResolver( $callable ){
			if ( !is_callable($callable) ) return;
			self::$_bootResolver = $callable;
		}
		// endregion

		// region [ Boot Control ]
		private function __construct() {}
		private function __initialize( $argv = NULL ) {

			// INFO: Preserve path of system container
			// DANGER: Make sure that this line will be executed before __judgeMainService ( "service" will be different )
			$preprocessEnvPaths = [
				path( 'root', 'boot.php' ),
				path( 'broot', 'boot.php' )
			];
			foreach ( $preprocessEnvPaths as $path ) {
				if ( is_file($path) && is_readable($path) ) {
					require_once $path;
				}
			}



			// INFO: Perform service decision and data initialization
			$this->__judgeMainService( $argv );
			
			
			// region [ PBPathResolver Customize Initialization ]
			$extPath = defined( 'PACKAGES' ) ? PACKAGES : [];
			PBPathResolver::Register( $extPath );
			PBPathResolver::Purge();
			// endregion
			
			
			PBRequest()->__initialize()->parseQuery(function_exists( 'default_query_parser' ) ? 'default_query_parser' : NULL);



			// INFO: Define runtime constants
			define( 'SESSION_BASIS', $this->_entryBasis );
			



			// INFO: Bring up the main process
			$this->__forkProcess($this->_entryBasis);
		}
		
		private $_entryBasis		= NULL;
		private function __judgeMainService( $argv = NULL ) {
			$service = $attributes = $fragment = '';
			$moduleRequest = [];
	
			if ( IS_HTTP_ENV ) {
			
				$reqURI		= @"{$_SERVER['REQUEST_URI']}";
				$request	= empty($reqURI) ? array() : explode('?', $reqURI);
				$resource	= preg_replace('/\/+/', '/', preg_replace('/^\/*|\/*$/', '', preg_replace('/\\\\/', '/', CAST( @array_shift( $request ), 'string no-trim' ) )));
				$attributes	= implode( '?', $request );



				$resource	 = data_filter( empty($resource) ? array() : explode( '/', $resource ), function( $item ) {
					return urldecode( $item );
				});
				$attachPoint = @array_splice( $resource, 0, ENV_ATTACH_LEVEL );
				$GLOBALS[ 'attachPoint' ] = $attachPoint;
				$GLOBALS[ 'rawRequest' ] = implode('/', $resource) . (empty($attributes) ? '' : "?{$attributes}");



				$service = @array_shift( $resource );
				$moduleRequest = $resource;
			}
			else {
				$service = CAST( @array_shift($argv), 'string' );
				$moduleRequest = $argv;
			}



			$processReq = function( $moduleRequest, $attributes ) {
				if ( IS_CLI_ENV ) return $moduleRequest;

				$moduleRequest	= implode('/', $moduleRequest);
				$attributes		= empty($attributes) ? '' : "?{$attributes}";
				return "{$moduleRequest}{$attributes}";
			};




			// region [ Find the default basis ]
			// INFO: Customized service decision logic
			if ( is_callable(self::$_bootResolver) ) {
				$resolver = self::$_bootResolver;
				$result = call_user_func($resolver, $service, $moduleRequest, $attributes, $fragment);
				if ( !empty($result) ) {
					$result = object($result);
				
					$service		= @$result->basis ?: @$result->service ?: $service;
					$moduleRequest	= @$result->resource ?: @$result->request ?: $moduleRequest;
					$workingDir		= @$result->root ?: @$result->workingRoot ?: '';
					
					
					
					// INFO: Detect Main Service
					$state = file_exists( path( "{$service}" ) . ".php" );
					if ($state) {
						$this->_entryBasis = $service;
		
						define( 'WORKING_ROOT', is_dir($workingDir) ? $workingDir : sys_get_temp_dir());
		
						$GLOBALS['service'] = $service;
						$GLOBALS['request'] = $processReq( $moduleRequest, $attributes );
						return;
					}
				}
			}

			




			// INFO: Detect Main Service
			$serviceParts = @explode( '.', "{$service}" );
			$serviceName = @array_pop( $serviceParts );
			$state = file_exists( path( "broot.{$serviceName}.{$serviceName}" ) . ".php" );
			if ($state) {
				$this->_entryBasis = $serviceName;

				define( 'WORKING_ROOT', PBKernel::$_cacheServicePath."/{$this->_entryBasis}" );

				$GLOBALS['service'] = $serviceName;
				$GLOBALS['request'] = $processReq( $moduleRequest, $attributes );
				return;
			}


			
			$reqService = "{$service}";
			if ( !empty($service) ) array_unshift($moduleRequest, $service);

			$state = $state || file_exists( path( "broot.main.main" ) . ".php" );
			if ($state) {
				$this->_entryBasis = 'main';

				define( 'WORKING_ROOT', PBKernel::$_cacheServicePath . "/main" );

				$GLOBALS['service'] = 'main';
				$GLOBALS['request'] = $processReq( $moduleRequest, $attributes );
				return;
			}
			// endregion

			throw(new Exception("Cannot locate default basis ({$reqService})!"));
		}
		// endregion

		// region [ Process Control ]
		/** @var PBProc */
		private $_process = NULL;
		private function __forkProcess($service) {
			if ( $this->_process ) return;
			
			
			
			$this->_process = PBProc( $this );

			chdir( WORKING_ROOT );
			$this->_process->prepareQueue($service);
		}
		// endregion
		
		// region [ Module Control ]
		private $_moduleSearchPaths	= [];
		public function addModuleSearchPath( $package = "" ) {
			if ( empty( $package ) ) return FALSE;

			$hash = md5( ($path = trim($package)) );
			if ( isset( $this->_moduleSearchPaths[$hash] ) ) return TRUE;


			if ( !is_dir( path( $path ) ) ) return FALSE;
			$this->_moduleSearchPaths[$hash] = $path;
			return TRUE;
		}
		public function removeModuleSearchPath( $package ) {
			if ( empty( $package ) ) return FALSE;

			$hash = md5( ($path = trim($package)) );
			if ( !isset( $this->_moduleSearchPaths[$hash] ) ) return TRUE;

			unset( $this->_moduleSearchPaths[$hash] );
			return TRUE;
		}
		public function acquireModule( $identifier ) {
			static $allocCounter = 0;

			$moduleDesc = self::ParseModuleIdentifier( $identifier );
			if ( $moduleDesc === FALSE ) throw( new Exception( "Given target module identifier has syntax error!" ) );



			$package  = implode( '.', $moduleDesc[ 'package' ] );
			$module	  = $moduleDesc[ 'module' ];
			$class	  = empty($moduleDesc[ 'class' ]) ? $module : $moduleDesc[ 'class' ];
			$moduleId = sha1( "{$package}.{$module}.{$class}#{$allocCounter}" . microtime() );






			// INFO: Search path construction
			$moduleSearchPaths   = [];
			$moduleSearchPaths[] = "basis.";
			$moduleSearchPaths[] = "modules.";
			$moduleSearchPaths[] = ""; // Use global identifier

			if ( defined("MODULE_PATH") )
				$moduleSearchPaths[] = MODULE_PATH . ".";

			foreach ( $this->_moduleSearchPaths as $path ) $moduleSearchPaths[] = "{$path}.";






			// INFO: Candidate paths
			$candidateComps = array();
			$candidateComps[] = $module;
			$candidateComps[] = "{$module}.{$module}";



			$hitPath = '';
			$subPkg	 = (!empty($package)) ? "{$package}." : "";
			foreach ( $moduleSearchPaths as $searchPath )
			{
				$searchPath = "{$searchPath}{$subPkg}";
				foreach ( $candidateComps as $component )
				{
					$path = "{$searchPath}{$component}";

					if (file_exists( path($path) . ".php" ))
					{
						using($path);
						$hitPath = $path;
					}
				}
			}



			if ( empty( $hitPath ) || !class_exists( $class ) )
				throw(new Exception("Module {$class} doesn't exist!"));



			$invokeModule = "{$class}";
			$moduleObj = new $invokeModule();
			if ( !is_a($moduleObj, PBModule::class) ) throw(new Exception("Requested class is not a valid module"));

			$moduleObj->id = $moduleId;
			return $moduleObj;
		}
		// endregion
		
		
		
		
		
		
		// region [ Supportive Functions ]
		private static function ParseModuleIdentifier( $moduleIdentifier )
		{
			$moduleIdentifier = trim( "{$moduleIdentifier}" );
			if ( empty($moduleIdentifier) ) return FALSE;



			$packages	= explode( '.',  "{$moduleIdentifier}" );
			$packages	= data_filter( $packages, NULL, FALSE );
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
		// endregion
	}
	class_alias( 'PBKernel', 'PBSysKernel' );

	final class PBKernelAccessor {
		/**@var PBKernel*/
		private $_relatedSys = NULL;
		public function __construct( PBKernel $sysInst ) {
			$this->_relatedSys = $sysInst;
		}
		
		public function acquireModule($moduleName, $reuse = FALSE) {
			return call_user_func_array([ $this->_relatedSys, "acquireModule" ], func_get_args());
		}
		
		public function addSearchPath( $package ) {
			return $this->_relatedSys->addModuleSearchPath( $package );
		}
		public function removeSearchPath( $package ) {
			return $this->_relatedSys->removeModuleSearchPath( $package );
		}
	}