<?php
	final class PBUploadedFile {
		const ERROR_OK					= UPLOAD_ERR_OK;
		const ERROR_INI_SIZE			= UPLOAD_ERR_INI_SIZE;
		const ERROR_FORM_SIZE			= UPLOAD_ERR_FORM_SIZE;
		const ERROR_PARTIAL				= UPLOAD_ERR_PARTIAL;
		const ERROR_NO_FILE				= UPLOAD_ERR_NO_FILE;
		const ERROR_NO_TMP_DIR			= UPLOAD_ERR_NO_TMP_DIR;
		const ERROR_CANT_WRITE			= UPLOAD_ERR_CANT_WRITE;
		const ERROR_EXTENSION			= UPLOAD_ERR_EXTENSION;
		const ERROR_GENERATED			= 1000;
		const ERROR_CANT_PROC_MOVE		= 1001;
		const ERROR_INVALID_TARGET_PATH	= 1002;
	}
	
	final class PBFileUploadHandler extends PBModule {
		const UPLOAD_PROC_NOOP				=  0;
		const UPLOAD_PROC_MD5_CHECKSUM		=  1;
		const UPLOAD_PROC_SHA1_CHECKSUM		=  2;
		const UPLOAD_PROC_SHA256_CHECKSUM	=  4;
		const UPLOAD_PROC_SHA512_CHECKSUM	=  8;
		const UPLOAD_PROC_CRC_CHECKSUM		= 16;



		private $_storagePath = '';
		public function __get_storagePath(){
			return $this->_storagePath;
		}
		public function __set_storagePath( $value ){
			$this->_storagePath = "{$value}";
		}

		private $_purgeError = FALSE;
		public function __get_purgeError(){
			return $this->_purgeError;
		}
		public function __set_purgeError( $value ){
			$this->_purgeError = ( $value === TRUE );
		}

		private $_procFlag = PBFileUploadHandler::UPLOAD_PROC_NOOP;
		public function __get_procFlag(){
			return $this->_procFlag;
		}
		public function __set_procFlag( $value ){
			$this->_procFlag = CAST( $value, 'int strict' );
		}

		private $_filePreProc = NULL;
		public function __get_filePreProc(){
			return $this->_filePreProc;
		}
		public function __set_filePreProc( $value ){
			$this->_filePreProc = is_callable($value) ? $value : NULL;
		}

		private $_fileProc = NULL;
		public function __get_fileProc(){
			return $this->_fileProc;
		}
		public function __set_fileProc( $value ){
			$this->_fileProc = is_callable($value) ? $value : NULL;
		}
		
		private $_autoDirectroy = FALSE;
		public function __get_autoDir(){
			return $this->_autoDirectroy;
		}
		public function __set_autoDir( $value ){
			$this->_autoDirectroy = !!$value;
		}
		


		private $_fields = [];
		public function execute( $param ) {
			
			if ( PBRequest::Request()->method !== "POST" ) return FALSE;
			$this->_fields = is_array($this->data->initData) ? $this->data->initData : [];
				


			$param = CAST( $param, 'array' );
			$uploadedFiles	= PBRequest::Request()->files;
			$purgeError		= $this->_purgeError;
			$storagePath	= $this->_storagePath;
			$procFlag		= $this->_procFlag;
			$autoDir		= $this->_autoDirectroy;
			$preprocFunc	= is_callable($this->_filePreProc) ? $this->_filePreProc : function( $fileInfo ){ return $fileInfo; };
			$procFunc		= is_callable($this->_fileProc) ? $this->_fileProc : function( $fileInfo ){ return $fileInfo; };

			$targetFields	= $this->_fields;
			if ( empty($targetFields) )
				$targetFields	= ( empty( $param ) ) ? array_keys( $uploadedFiles ) : $param;



			$processed = ary_filter( $targetFields, function( $item, &$fieldName ) use ( &$autoDir, &$uploadedFiles, &$purgeError, &$storagePath, &$procFlag, &$procFunc, &$preprocFunc )
			{
				if ( empty($item) || !@is_array($uploadedFiles[$item]) ) return NULL;

				$fieldName = $item;

				return ary_filter( $uploadedFiles[$item], function( $info, $idx ) use ( &$autoDir, &$purgeError, &$storagePath, &$procFlag, &$procFunc, &$preprocFunc )
				{
					$fileInfo = [
						'name'		=> $info['name'],
						'tmpPath'	=> "{$info[ 'tmp_name' ]}"
					];
					$token = sha1( uniqid() . "{$info['name']}" );

					// region [ Skipping condition of PHP file error ]
					if ( !empty( $info[ 'error' ] ) )
					{
						if ( $purgeError ) return NULL;

						$fileInfo[ 'error' ] = $info[ 'error' ];
						return $fileInfo;
					}
					// endregion

					// region [ Exract information from original input file ]
					if ( $procFlag & PBFileUploadHandler::UPLOAD_PROC_MD5_CHECKSUM )
						$fileInfo[ 'md5' ] = hash_file( 'md5', $info['tmp_name'] );

					if ( $procFlag & PBFileUploadHandler::UPLOAD_PROC_SHA1_CHECKSUM )
						$fileInfo[ 'sha1' ] = hash_file( 'sha1', $info['tmp_name'] );

					if ( $procFlag & PBFileUploadHandler::UPLOAD_PROC_SHA256_CHECKSUM )
						$fileInfo[ 'sha256' ] = hash_file( 'sha256', $info['tmp_name'] );

					if ( $procFlag & PBFileUploadHandler::UPLOAD_PROC_SHA512_CHECKSUM )
						$fileInfo[ 'sha256' ] = hash_file( 'sha512', $info['tmp_name'] );

					if ( $procFlag & PBFileUploadHandler::UPLOAD_PROC_CRC_CHECKSUM )
						$fileInfo[ 'crc32' ] = hash_file( 'crc32', $info['tmp_name'] );
					// endregion

					
					// region [ Collect remaining input file infomation ]
					$mime = $info['type'];
					list( $mimeMajor, $mimeMinor ) = explode( '/', $mime );

					$fileInfo['token']	= $token;
					$fileInfo['name']	= $info['name'];
					$fileInfo['mime']	= array(
						'general'	=> $mime,
						'major'		=> $mimeMajor,
						'minor'		=> $mimeMinor,
					);
					$fileInfo['size']	= $info['size'];
					// endregion
					
					
					$tempInfo	= $fileInfo; // Prevent $fileInfo from being modified
					$overwrites = $preprocFunc( $tempInfo );
					if ( !is_array( $overwrites ) ) $overwrites = [];
					

					
					// NOTE: If purge error is on...
					if ( !empty($fileInfo[ 'error' ]) && $purgeError ) return NULL;

					
					// region [ Move file to file stroage ]
					if ( !empty($storagePath) )
					{
						if ( $autoDir ) 
							@mkdir( $storagePath, 0777, TRUE );
							
					
					
						if ( !is_dir( $storagePath ) )
							$fileInfo[ 'error' ] = PBUploadedFile::ERROR_INVALID_TARGET_PATH;

						if ( !is_uploaded_file( $info['tmp_name'] ) )
							$fileInfo[ 'error' ] = PBUploadedFile::ERROR_GENERATED;

						$baseName	= empty($overwrites[ 'storageName' ]) ? $token : $overwrites[ 'storageName' ]; 
						$dstPath	= "{$storagePath}/{$baseName}";
						$result		= @move_uploaded_file( $info['tmp_name'], $dstPath );

						if ( empty($result) )
							$fileInfo[ 'error' ] = PBUploadedFile::ERROR_CANT_PROC_MOVE;
						else
						{
							unset( $fileInfo['tmpPath'] );
							$fileInfo[ 'storageName' ] = $overwrites[ 'storageName' ];
						}
					}
					// endregion

					
					
					// INFO: Overwrites details
					foreach( $fileInfo as $field => $value )
					{
						if ( !array_key_exists( $field, $overwrites ) ) continue;
						$fileInfo[ $field ] = $overwrites[ $field ];
					}

					return $procFunc( $fileInfo );
				}, NULL);
			}, NULL);

			return $processed;
		}
	}
