<?php
	@define( 'IS_WIN_ENV', strtoupper(substr( PHP_OS, 0, 3 )) === 'WIN' );
	@define( 'IS_COM_LIB_AVAILABLE', class_exists( 'COM' ) );
	
	if ( IS_WIN_ENV && !IS_COM_LIB_AVAILABLE ) {
		error_log( "COM extension ( php_com_dotnet.dll ) is not enabled! Some feature functions will not available!" );
	}
	
	
	
	function s_define($name, $value, $sensitive = TRUE, $throwException = FALSE) {
		if ( !defined($name) ) {
			define($name, $value, $sensitive === FALSE);
			return;
		}

		if ( $throwException ) {
			throw new Exception("Constant {$name} has been defined!");
		}
	}
	function __resolve_lnk( $lnkPath ) {
		$lnkPath = realpath($lnkPath);
		if ( !IS_COM_LIB_AVAILABLE ) {
			return $lnkPath;
		}
		
		
		
		$shell = new COM('WScript.Shell');
		$shortcut = $shell->createshortcut($lnkPath);
		$targetPath = $shortcut->targetpath;
		return $targetPath;
	
		/*
			// The following method could be failed on COM generated lnk files
			// Borrowed from http://www.witti.ws/blog/2011/02/21/extract-path-lnk-file-using-php
			$linkContent = file_get_contents( $lnkPath );
			return preg_replace( '@^.*\00([A-Z]:)(?:[\00\\\\]|\\\\.*?\\\\\\\\.*?\00)([^\00]+?)\00.*$@s', '$1\\\\$2', $linkContent );
		*/
	}
	