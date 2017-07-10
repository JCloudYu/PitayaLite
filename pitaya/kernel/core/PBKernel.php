<?php
	final class PBKernel extends PBObject {
		// region [ Boot Related ]
		/** @var $_SYS_INSTANCE PBKernel */
		private static $_SYS_INSTANCE = NULL;
		public static function Kernel() {
			return self::$_SYS_INSTANCE;
		}
		public static function boot() {

			// INFO: Avoid repeated initialization
			if ( PBKernel::$_SYS_INSTANCE ) return;
			$G_CONF = PBStaticConf( 'pitaya-env' );



			try {
				// INFO: Keep booting
				PBKernel::$_SYS_INSTANCE = new PBKernel();
				PBKernel::$_SYS_INSTANCE->__initialize();
				PBKernel::$_SYS_INSTANCE->_process->run();

				Termination::NORMALLY();
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

				if ( $G_CONF[ 'log-exceptions' ] === TRUE )
				{
					PBLog( 'exception' )->log(print_r($e, TRUE));
					$extMsg = "See exception log for more information!";
				}

				PBLog( 'error' )->log( $errMsg );
				if (!empty($extMsg)) {
					PBLog( 'error' )->log( $extMsg );
				}



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
						$errProcObj = PBModule(ERROR_MODULE);
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
				if ( $G_CONF[ 'throw-exceptions' ] === TRUE )
				{
					throw( $e );
				}
				else
				if ( IS_HTTP_ENV && $G_CONF[ 'debug-mode' ] )
				{
					if ( !headers_sent() )
					{
						header( "HTTP/1.1 500 Internal Server Error" );
						header( "Status: 500 Internal Server Error" );
						header( "Content-Type: text/html; charset=utf8" );
					}

					echo $errMsg;
				}



				Termination::WITH_STATUS(Termination::STATUS_ERROR);
			}
		}
		
		private static $_bootResolver = NULL;
		public static function SetBasisResolver($callable){
			if ( !is_callable($callable) ) return;
			self::$_bootResolver = $callable;
		}
		// endregion
		
		
		
		/** @var PBProc */
		private $_process = NULL;

		private function __construct() {}
		private function __initialize() {
			$G_CONF = PBStaticConf( 'pitaya-env' );
			foreach( $G_CONF[ 'boot-scripts' ] as $script ) {
				$path = path($script) . '.php';
				if ( is_file($path) && is_readable($path) ) {
					require_once $path;
				}
			}
			
			// INFO: Allow developers to assign custom boot scripts dynamically
			foreach( $this->_customBootScripts as $script ) {
				$path = path($script) . '.php';
				if ( is_file($path) && is_readable($path) ) {
					require_once $path;
				}
			}



			// INFO: Perform service decision and data initialization
			$module = $this->__judgeMainService();
			PBPathResolver::Purge();
			
			
			
			PBRequest()->__initialize()->parseQuery(function_exists( 'default_query_parser' ) ? 'default_query_parser' : NULL);
			



			// INFO: Bring up the main process
			$this->__forkProcess($module);
		}
		private function __judgeMainService() {
			$G_CONF = PBStaticConf( 'pitaya-env' );
		
		
		
			$service = $attributes = $fragment = '';
			$moduleRequest = [];
			
			if ( IS_HTTP_ENV ) {
			
				$reqURI		= @"{$_SERVER['REQUEST_URI']}";
				$request	= empty($reqURI) ? array() : explode('?', $reqURI);
				$resource	= preg_replace('/\/+/', '/', preg_replace('/^\/*|\/*$/', '', preg_replace('/\\\\/', '/', CAST( @array_shift( $request ), 'string no-trim' ) )));
				$attributes	= implode( '?', $request );



				$resource	 = ary_filter( empty($resource) ? array() : explode( '/', $resource ), function( $item ) {
					return urldecode( $item );
				});
				$attachPoint = @array_splice( $resource, 0, $G_CONF[ 'attach-depth' ] );
				$GLOBALS[ 'attachPoint' ] = $attachPoint;
				$GLOBALS[ 'rawRequest' ] = implode('/', $resource) . (empty($attributes) ? '' : "?{$attributes}");



				$service = @array_shift( $resource );
				$moduleRequest = $resource;
			}
			else {
				$service = CAST( @array_shift($_SERVER['argv']), 'string' );
				$moduleRequest = $_SERVER['argv'];
			}



			$processReq = function( $moduleRequest, $attributes ) {
				if ( IS_CLI_ENV ) return $moduleRequest;

				$moduleRequest	= implode('/', $moduleRequest);
				$attributes		= empty($attributes) ? '' : "?{$attributes}";
				return "{$moduleRequest}{$attributes}";
			};


	
			if ( !empty($G_CONF[ 'entry-module' ]) ) {
				$entryModule = PBModule($G_CONF[ 'entry-module' ], TRUE, TRUE);
				if (!empty($entryModule)) {
					PBPathResolver::Register([ 'basis' => getcwd() ]);
					$GLOBALS['request'] = $processReq( $moduleRequest, $attributes );
					return $entryModule;
				}
			}






			// INFO: Customized service decision logic
			if ( is_callable(self::$_bootResolver) ) {
				$resolver = self::$_bootResolver;
				$result = call_user_func($resolver, $service, $moduleRequest, $attributes, $fragment);
				if ( !empty($result) ) {
					$result = object($result);
				
					$service		= @$result->basis ?: $service;
					$moduleRequest	= @$result->resource ?: $moduleRequest;
				}
			}



			// INFO: Detect Main Service
			$pos = strpos($service, '.');
			$moduleName = ( $pos === FALSE ) ? $service : substr($service, 0, $pos);
			$state = file_exists(path("broot.{$moduleName}", "{$moduleName}.php" ));
			if ($state) {
				$entryModule = PBModule( "broot.{$moduleName}.{$moduleName}" );
				PBPathResolver::Register([
					'basis' => ($basisDir=path("broot.{$moduleName}"))
				]);
				chdir($basisDir);
				$GLOBALS['request'] = $processReq( $moduleRequest, $attributes );
				return $entryModule;
			}






			$reqService = "{$service}";
			if ( !empty($service) ) {
				array_unshift($moduleRequest, $service);
			}
			$moduleName = $G_CONF[ 'default-basis' ];
			$state = $state || file_exists( path( "broot.{$moduleName}.{$moduleName}" ) . ".php" );
			if ($state) {
				$entryModule = PBModule( "broot.{$moduleName}.{$moduleName}" );
				PBPathResolver::Register([
					'basis' => ($basisDir=path("broot.{$moduleName}"))
				]);
				chdir( $basisDir );
				$GLOBALS['request'] = $processReq( $moduleRequest, $attributes );
				return $entryModule;
			}
			// endregion

			throw(new Exception("Cannot locate default basis ({$reqService})!"));
		}
		private function __forkProcess($module) {
			if ( $this->_process ) return;
			
			$this->_process = PBProc($this);
			$this->_process->prepareQueue($module);
		}
		
		private $_customBootScripts = [];
		public function addBootScripts($bootScripts=[]) {
			if ( !is_array($bootScripts) ) {
				$bootScripts = [$bootScripts];
			}
			
			foreach( $bootScripts as $script ) {
				$this->_customBootScripts[] = $script;
			}
		}
	}
	function PBKernel() {
		return PBKernel::Kernel();
	}