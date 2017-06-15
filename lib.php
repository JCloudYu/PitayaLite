<?php
	define( 'IS_COM_SUPPORTED', class_exists( 'COM' ) );

	if ( IS_WIN_ENV && !IS_COM_SUPPORTED ) {
		error_log( "COM extension ( php_com_dotnet.dll ) is not enabled! Some features will not be active!" );
	}

	function resolve_lnk($lnkPath) {
		if ( !IS_COM_SUPPORTED ) {
			return $lnkPath;
		}
		
		$lnkPath  = realpath($lnkPath);
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
	function s_define($name, $value, $sensitive = TRUE, $throwException = FALSE) {
		if ( !defined($name) ) {
			define($name, $value, $sensitive === FALSE);
			return;
		}

		if ( $throwException ) {
			throw(new Exception("Constant {$name} has been defined!"));
		}
	}
	