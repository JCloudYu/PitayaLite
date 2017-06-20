<?php
	// region [ Core Path APIs ]
	final class PBPathResolver {
		private static $_path_cache = [];
		private static $_kernel_cache = [];
		public static function Initialize() {
			static $_initialized = FALSE;
			if ( $_initialized ) return;
			
			

			// INFO: Attach pitaya root packages
			$G_CONF = PBStaticConf( 'pitaya-env' );
			$list	= scandir(PITAYA_ROOT);
			foreach( $list as $dir ) {
				if ( $dir == "." || $dir == ".." ) continue;
				
				$absPath = PITAYA_ROOT . "/{$dir}";
				if ( !is_dir($absPath) ) continue;
				self::$_kernel_cache[strtolower($dir)] = $absPath;
			}



			// INFO: Attach other keywords
			foreach( $G_CONF[ 'packages' ] as $pkg => $path ) {
				if ( IS_WIN_ENV ) {
					$linkPath = "{$path}.lnk";
					if ( is_file( $linkPath ) ) {
						$path = __resolve_lnk($linkPath);
					}
				}
				
				self::$_kernel_cache[ $pkg ] = $path;
			}
			
			
			
			// Default packages
			self::$_kernel_cache['root'] = $G_CONF[ 'space-root' ] ?: getcwd();
			if ( @self::$_kernel_cache[ 'broot' ] === NULL ) {
				self::$_kernel_cache['broot'] = self::$_kernel_cache['root'];
			}
			
			
			
			
			
			
			self::$_path_cache = self::$_kernel_cache;
			$_initialized = TRUE;
		}
		public static function Purge() {
			static $_purged = FALSE;
			if ( $_purged ) return;
			
			self::$_kernel_cache['basis'] = self::$_path_cache['basis'] ?: getcwd();
		}
		public static function Register($map=[]) {
			if ( !is_array($map) ) return;
			
			
			
			foreach( $map as $key => $path ) {
				if ( IS_WIN_ENV ) {
					$linkPath = "{$path}.lnk";
					if ( !is_dir( $path ) && is_file( $linkPath ) ) {
						$path = __resolve_lnk( $linkPath );
					}
				}
				
				self::$_path_cache[ $key ] = $path;
			}
			
			
			
			foreach( self::$_kernel_cache as $key => $path ) {
				self::$_path_cache[ $key ] = $path;
			}
		}
		public static function Resolve($package) {
			return @self::$_path_cache[$package];
		}
	}
	PBPathResolver::Initialize();
	
	function path($referencingContext = '', $appendItem=NULL) {
		$tokens	 = explode('.', $referencingContext);
		$pkg	 = array_shift($tokens);
		$pkgRoot = PBPathResolver::Resolve($pkg);
		if ( $pkgRoot === NULL ) {
			return FALSE;
		}
		
		array_unshift($tokens, $pkgRoot);
		if ( !empty($appendItem) ) {
			array_push($tokens, $appendItem);
		}
		return implode( '/', $tokens );
	}
	function using($referencingContext = '', $important = TRUE) {
		static $_path_cache = [];
		
		
		
		$pkgId = sha1($referencingContext);
		if ( @$_path_cache[$pkgId] ) {
			return TRUE;
		}
		
		
		
		
		$tokens	 = explode('.', $referencingContext);
		$lastCmp = array_pop($tokens);
		$pkg	 = array_shift($tokens);
		
		// Check package root
		$pkgRoot = PBPathResolver::Resolve($pkg);
		if ( $pkgRoot === NULL ) {
			if ( $important ) {
				throw new Exception( "Target package `{$referencingContext}` doesn't exist!" );
			}
			
			return FALSE;
		}
		
		// Check package dir
		$pkgDir = $pkgRoot . '/' . implode( '/', $tokens );
		if ( !is_dir($pkgDir) ) {
			if ( $important ) {
				throw new Exception( "Target package `{$referencingContext}` doesn't exist!" );
			}
			
			return FALSE;
		}

		




		if ( $lastCmp != '*' ) {
			$scriptPath = "{$pkgDir}/{$lastCmp}.php";
			if ( !is_file($scriptPath) ) {
				return FALSE;
			}
			
			
			
			$_path_cache[$pkgId] = TRUE;
			if ( $important ) {
				require_once $scriptPath;
			}
			else {
				@include_once $scriptPath;
			}
		}
		else {
			$dirHandle = @opendir($pkgDir) ?: NULL;
			if ( empty($dirHandle) ) {
				if ( $important ) {
					throw new Exception("Package `{$referencingContext}` is not accessible!");
				}
				
				return FALSE;
			}


			
			$pkgPath = $tokens; array_unshift($pkgPath, $pkg);
			$pkgPath = implode('.', $tokens);
			while( ($entry = readdir($dirHandle)) !== FALSE ) {
				if ( substr($entry, -4) != '.php' ) {
					continue;
				}
				
				
				
				$scriptName	= substr($entry, 0, -4);
				$pkgId		= sha1("{$pkgPath}.{$scriptName}");
				if ( @$_path_cache[$pkgId] ) continue;
				
				

				$_path_cache[$pkgId] = TRUE;
				$targetPath = "{$pkgDir}/{$entry}";

				if ( $important ) {
					require_once $targetPath;
				}
				else {
					@include_once $targetPath;
				}
			}
		}

		return TRUE;
	}
	// endregion
	
	final class DEBUG {
		public static function VarDump(...$args) {
			$G_CONF = PBStaticConf( 'pitaya-env' );


			if ( !$G_CONF[ 'debug-mode' ] ) return '';


			$width = intval($G_CONF[ 'debug-console-width' ]);

			$out = '';
			if( IS_HTTP_ENV ) {
				$out .= "<div class='debugOpt' style='background-color:#fefe00; z-index:9999; border:solid red; margin-bottom:10px; padding:5px; word-break:break-all; width:{$width}px; color:#000; position:relative;'>";
			}

			if ( IS_CLI_ENV ) {
				$indentSpace = "\t";
				$newLine = "\n";
			}
			else {
				$indentSpace = "&nbsp;&nbsp;&nbsp;&nbsp;";
				$newLine = "<br />";
			}


			if ( DEBUG_BACKTRACE_ENABLED ) {
				$info = self::BackTrace(DEBUG_BACKTRACE_PROVIDE_OBJECT|DEBUG_BACKTRACE_IGNORE_ARGS, 3);
				if ( @$info[1]['class'] == 'PBObject' && @$info[1]['function'] == '__get' )
					$locator = 2;
				else
					$locator = 1;
	
				$info = @$info[$locator];
	
				if($locator >= count($info))
				{
					$info['file'] = 'PHP System Call';
					$info['line'] = 'Unavailable';
				}
	
				if ( IS_HTTP_ENV ) {
					$out .= '<div>';
				}
				$out .= "{$info['file']} : {$info['line']}";
				if ( IS_HTTP_ENV ) {
					 $out .= '</div>';
				}
				$out .= $newLine;
			}



			$indent = -1;
			foreach($args as $arg)
			{
				if($indent >= 0) $out .= $newLine;

				$indent = 0;
				foreach(explode("\n", var_export($arg, TRUE)) as $chunk)
				{
					$chunk = trim($chunk);

					if(preg_match('/.*\($/', $chunk))
					{
						$tmp = explode(' ', $chunk);

						foreach($tmp as $tmpItem)
						{
							for($i=0; $i<$indent; $i++) $out .= $indentSpace;

							$out .= $tmpItem.$newLine;
						}
						$indent++;
					}
					else
					{
						if(preg_match('/^\).*/', $chunk))
							$indent--;

						for($i=0; $i<$indent; $i++) $out .= $indentSpace;
						$out .= $chunk.$newLine;
					}
				}
			}

			if ( IS_HTTP_ENV ) {
				$out .= '</div>';
			}

			echo $out;
		}
		
		
		public static function BackTrace(...$args) {
			if ( !DEBUG_BACKTRACE_ENABLED ) return NULL;



			$info = debug_backtrace(...$args);
			$depth = count($info);

			$adjusted = array();
			for ( $i=1; $i<$depth; $i++ ) {
				$adjusted[$i-1] = array();

				$tmp = $info[$i];

				@$adjusted[$i-1]['file'] = @$info[$i-1]['file'];
				@$adjusted[$i-1]['line'] = @$info[$i-1]['line'];

				@$adjusted[$i-1]['function'] = @$tmp['function'];

				if(array_key_exists('class',  $tmp)) $adjusted[$i-1]['class']  = $tmp['class'];
				if(array_key_exists('object', $tmp)) $adjusted[$i-1]['object'] = $tmp['object'];
				if(array_key_exists('type',	  $tmp)) $adjusted[$i-1]['type']   = $tmp['type'];
				if(array_key_exists('args',	  $tmp)) $adjusted[$i-1]['args']   = $tmp['args'];
			}

			$item = array_pop($info);
			unset($item['class']);
			unset($item['object']);
			unset($item['type']);
			unset($item['args']);
			array_push($adjusted,$item);

			return $adjusted;
		}
	}
	final class Termination {
		const STATUS_SUCCESS			= 0;
		const STATUS_ERROR				= 1;
		const STATUS_INCORRECT_USAGE	= 2;
		const STATUS_NOT_AN_EXECUTABLE	= 126;
		const STATUS_COMMAND_NOT_FOUND	= 127;
		const STATUS_SIGNAL_ERROR		= 128;

		private function __construct(){}

		public static function NORMALLY() {
			exit(self::STATUS_SUCCESS);
		}
		public static function ERROR() {
			exit(self::STATUS_ERROR);
		}
		public static function WITH_STATUS( $errorCode )
		{
			$errorCode = abs($errorCode);

			if ( $errorCode >= self::STATUS_SIGNAL_ERROR )
				$errorCode = $errorCode % self::STATUS_SIGNAL_ERROR;

			exit( $errorCode );
		}
	}
	function pb_metric(){
		static $_prevTime = 0, $_prevMem = 0;
		
		$now = microtime(TRUE);
		$memoryUsage = memory_get_usage();
		$result = (object)[
			'memory' => (object)[
				'current' => $memoryUsage,
				'peak'	  => memory_get_peak_usage(),
				'diff'	  => $memoryUsage - $_prevMem
			],
			'time' => (object)[
				'now' => $now,
				'dur' => $now - PITAYA_METRIC_BOOT_TIME
			],
			'diff' => $now - $_prevTime
		];
		
		$_prevTime	= $now;
		$_prevMem	= $memoryUsage;
		return $result;
	}