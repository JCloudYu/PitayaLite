<?php
	final class PBPathResolver {
		private static $_path_cache = [];
		private static $_kernel_cache = [];
		public static function Initialize() {
			static $_initialized = FALSE;
			if ( $_initialized ) return;
			
			

			// INFO: Attach pitaya root packages
			$list = scandir(PITAYA_ROOT);
			foreach ($list as $dir) {
				$absPath = PITAYA_ROOT . "/{$dir}";
				if ( !is_dir($absPath) ) continue;
				
				self::$_kernel_cache[strtolower($dir)] = $absPath;
			}

			// INFO: Attach other keywords
			self::$_kernel_cache[ 'root' ]		= SPACE_ROOT;
			self::$_kernel_cache[ 'ext' ]		= SPACE_ROOT . '/ext';
			self::$_kernel_cache[ 'broot' ]		= defined( "BASIS_PATH" ) ? BASIS_PATH : SPACE_ROOT . '/app';
			self::$_kernel_cache[ 'basis' ]		= self::$_kernel_cache[ 'broot' ];

			// Resolve to real path if targeted directory is a lnk file
			if ( IS_WIN_ENV ) {
				foreach( self::$_kernel_cache as $key => $path ) {
					$linkPath = "{$path}.lnk";
					if ( is_dir( $path ) || !is_file( $linkPath ) ) continue;
					self::$_kernel_cache[ $key ] = resolve_lnk( $linkPath );
				}
			}


			s_define( 'BASIS_ROOT', self::$_kernel_cache[ 'broot' ], TRUE );
			
			self::$_path_cache = self::$_kernel_cache;
			$_initialized = TRUE;
		}
		public static function Purge() {
			static $_purged = FALSE;
			if ( $_purged ) return;
		
			if ( defined( 'WORKING_ROOT' ) ) {
				self::$_path_cache[ 'basis' ] = WORKING_ROOT;
				$_purged = TRUE;
			}
		}
		public static function Register( $map = [] ) {
			if ( !is_array($map) ) return TRUE;
			
			foreach( $map as $key => $path ) {
				if ( IS_WIN_ENV ) {
					$linkPath = "{$path}.lnk";
					if ( !is_dir( $path ) && is_file( $linkPath ) ) {
						$path = resolve_lnk( $linkPath );
					}
				}
				
				self::$_path_cache[ $key ] = $path;
			}
			
			
			
			foreach( self::$_kernel_cache as $key => $path ) {
				self::$_path_cache[ $key ] = $path;
			}
			
			return TRUE;
		}
		public static function Resolve( $package ) {
			return empty(self::$_path_cache[$package]) ? '' : self::$_path_cache[$package];
		}
	}
	PBPathResolver::Initialize();

	
	function path($referencingContext = '', $appendItem = '') {
		$tokens = explode('.', $referencingContext);
		$completePath = PBPathResolver::Resolve(array_shift($tokens));

		foreach( $tokens as $token)
			$completePath .= "/{$token}";

		$appendItem = trim($appendItem);
		return $completePath . (empty($appendItem) ? '' : "/{$appendItem}");
	}
	function using($referencingContext = '', $important = TRUE) {
		static $registeredInclusions = array();
		if ( func_num_args() == 1 && $referencingContext === TRUE ) return $registeredInclusions;

		$tokens = explode('.', $referencingContext);
		$tokens = array_reverse($tokens);

		if ( isset($registeredInclusions[($referencingContext)]) )
			return $registeredInclusions[($referencingContext)];

		if($tokens[0] == '*')
		{
			array_shift($tokens);
			$tokens = array_reverse($tokens);
			$completePath = PBPathResolver::Resolve(array_shift($tokens));


			foreach( $tokens as $token)
				$completePath .= "/{$token}";
			$completePath .= '/';

			$dirHandle = file_exists($completePath) ? opendir($completePath) : NULL;

			if($dirHandle === NULL && $important)
				throw(new Exception("Cannot locate package: {$completePath}"));

			if($dirHandle !== NULL)
			while(($entry = readdir($dirHandle)) !== FALSE)
			{
				if($entry == '.' || $entry == '..') continue;
				if(preg_match('/.*php$/', $entry) === 1)
				{
					$givenContainer = substr($referencingContext, 0, -2);
					$validEntry = substr($entry, 0, -4);

					if(isset($registeredInclusions[("$givenContainer.$validEntry")])) continue;

					$targetPath = "$completePath/$entry";

					$registeredInclusions[("$givenContainer.$validEntry")] = TRUE;

					if($important) require($targetPath);
					else include($targetPath);
				}
			}

			$registeredInclusions[($referencingContext)] = $dirHandle !== NULL;
		}
		else
		{
			$tokens = array_reverse($tokens);
			$completePath = PBPathResolver::Resolve(array_shift($tokens));

			foreach( $tokens as $token)
				$completePath .= "/{$token}";

			$completePath .= '.php';

			if(file_exists($completePath)) $registeredInclusions[($referencingContext)] = TRUE;
			else $registeredInclusions[($referencingContext)] = FALSE;

			if($important) require($completePath);
			else @include($completePath);
		}

		return $registeredInclusions[($referencingContext)];
	}
	