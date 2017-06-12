<?php
	class PBFileSystem {
		public static function CopyDir( $sourceDir, $targetDir ) {
			$jobQueue = [];
			$jobQueue[] = (object)[
				'src' => basename($sourceDir),
				'dst' => basename($targetDir)
			];
			$sourceDir = dirname($sourceDir);
			$targetDir = dirname($targetDir);
			
			while ( count($jobQueue) > 0 ) {
				$job = array_shift($jobQueue);
				$dirSrc = $job->src;
				$dirDst = empty($job->dst) ? $dirSrc : $job->dst;
				$dirList = self::__COPY_DIR( "{$sourceDir}/{$dirSrc}", "{$targetDir}/{$dirDst}" );
				foreach ( $dirList as $dir ) {
					$jobQueue[] = (object)[ 'src' => "{$job->src}/{$dir}" ];
				}
			}
		}
		public static function __COPY_DIR( $srcDir, $destDir ) {
			static $SKIPPED_FILES = [ '.', '..' ];
		
			@mkdir( $destDir );
			if ( !is_dir($destDir) || !is_writable($destDir) ) {
				throw new PBException([ 
					'msg'	 => "Destination directory error!",
					'code'	 => -1,
					'reason' => "Cannot create directory on '{$destDir}'"
				]);
			}
		
			$hDir = @opendir( $srcDir );
			if ( empty($hDir) ) {
				throw new PBException([ 
					'msg'	 => "Source directory error!",
					'code'	 => -1,
					'reason' => "Cannot read contents of directory '{$srcDir}'"
				]);
			}
			
			
			
			$dirList = [];
			while( ($file = @readdir($hDir)) !== FALSE ) {
				if ( in_array($file, $SKIPPED_FILES) ) continue;
				$srcFile = "{$srcDir}/{$file}";
				if ( is_dir($srcFile) ) {
					$dirList[] = $file;
					continue;
				}
				
				$destFile = "{$destDir}/{$file}";
				$result = @copy( $srcFile, $destFile );
				if ( $result === FALSE ) {
					throw new PBException([ 
						'msg'	 => "Copy file error!",
						'code'	 => -2,
						'reason' => "File '{$srcFile}' is not able to be copied into '{$destFile}'!"
					]);
				}
			}
			
			@closedir($hDir);
			return $dirList;
		}
	}
