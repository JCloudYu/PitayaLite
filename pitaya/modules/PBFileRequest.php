<?php
	class PBFileRequest extends PBModule {
		private $_targetPath	= '';
		private static $_acceptableExt	= NULL;

		public function __construct() {
			if ( empty(self::$_acceptableExt) )
			{
				self::$_acceptableExt = ary_filter(
					json_decode(file_get_contents(path("defaults", "extension-map.json")), TRUE),
					function( $item, &$idx ){ $idx = strtolower($idx); return $item; }
				);
			}
		}

		private $_relPath = [];
		public function __set_relPath($value) {
			if ( !is_array( $value ) ) $value = [ "{$value}" ];
			$this->_relPath = $value;
		}
		public function& __get_relPath() { return $this->_relPath; }

		private $_multiByteRangeMode = FALSE;
		public function __set_multiBytes($value) { $this->_multiByteRangeMode = !empty($value); }
		public function __get_multiBytes() { return $this->_multiByteRangeMode; }

		private $_allowRagneRequest = FALSE;
		public function __set_allowRangeRequest($value) { $this->_allowRagneRequest = !empty($value); }
		public function __get_allowRangeRequest() { return $this->_allowRagneRequest; }

		private $_mime = "";
		public function __set_defaultMime( $value ) { $this->_mime = $value; }
		public function __get_defaultMime() { return $this->_mime; }

		private $_strict_mime = TRUE;
		public function __set_strictMime( $value ) { $this->_strict_mime = empty($value); }
		public function __get_strictMime() { return $this->_strict_mime; }

		private $_custExtensionMap = array();
		public function __set_extensionMap( $value ) { $this->_custExtensionMap = is_array( $value ) ? $value : array(); }
		public function __get_extensionMap( ) { return $this->_custExtensionMap; }

		private $_downloadName = "";
		public function __set_downloadName( $value ) { $this->_downloadName = @"{$value}"; }
		public function __get_downloadName() { return $this->_downloadName; }


		public function execute( $chainData ) {
			$initData = $this->data->initDate;
			$this->_targetPath = (is_array($initData)) ? implode('/', $initData) : "{$initData}";
		
		
		

			$extensionMap = self::$_acceptableExt;
			ary_filter( $this->_custExtensionMap, function( $item, $idx ) use( &$extensionMap ) {
				$idx = strtolower( "{$idx}" );
				$extensionMap[ $idx ] = $item;
			});



			// INFO: Check whether the mime is allowed
			$ext = @strtolower(pathinfo("{$this->_targetPath}", PATHINFO_EXTENSION));
			$this->_mime = ( empty($this->_mime) ) ? @$extensionMap[ $ext ] : $this->_mime;

			if ( empty($this->_mime) && empty($this->_strict_mime) )
				$this->_mime = "application/octet-stream";

			if ( empty($this->_mime) )
			{
				header('HTTP/1.1 403 Forbidden');
				exit(0);
			}



			// INFO: Path validation
			$searchPath = $this->_relPath;
			$workingRoot = path( 'basis' );
			array_unshift( $searchPath, $workingRoot );

			$filePath = NULL; $targetPath = $this->_targetPath;
			ary_filter( $searchPath, function( $pathDir ) use( $targetPath, &$filePath ) {
				if ( $filePath !== NULL ) return;

				$path = "{$pathDir}/{$targetPath}";
				if ( is_file($path) && is_readable($path) )
					$filePath = $path;


			}, NULL);

			if ( empty($filePath) )
			{
				header('HTTP/1.1 404 Not Found');
				exit(0);
			}



			// INFO: Basic Cache Control
			$fileETag = fileinode( $filePath );
			$fileTime = gmstrftime( "%a, %d %b %Y %T %Z", filemtime( $filePath ) );

			$headerETag   = @PBRequest::Request()->server['HTTP_IF_NONE_MATCH'];
			$headerFTime = @PBRequest::Request()->server['HTTP_IF_MODIFIED_SINCE'];

			if ( ( $headerETag == "\"{$fileETag}\"" ) && ($headerFTime == $fileTime) )
			{
				header( 'HTTP/1.1 304 Not Modified' );
				exit(0);
			}



			// INFO: Get file info and http range info
			$ranges	  = PBRequest::Request()->range;



			// INFO: Normal mode
			if (empty($ranges) || !$this->_allowRagneRequest)
			{
				$fileStream	= fopen($filePath, "rb");
				$outStream	= fopen("php://output", "wb");
				$fileSize	= filesize($filePath);


				if (empty($fileStream))
				{
					header("HTTP/1.1 429 Too Many Requests");
					exit(0);
				}

				header("HTTP/1.1 200 OK");
				header("Content-Type: {$this->_mime}");
				header("Content-Length: {$fileSize}");
				if ( !empty($this->_downloadName) )
					header( "Content-Disposition: attachement; filename=\"{$this->_downloadName}\"" );

				header("Last-Modified: {$fileTime}");
				header("ETag: \"{$fileETag}\"");

				self::ChunkStream($outStream, $fileStream, array('from' => 0, 'to' => $fileSize-1));

				fclose($fileStream);
				fclose($outStream);

				exit(0);
			}

			if ($this->_multiByteRangeMode)
				$this->multiRanges($filePath, $ranges);
			else
				$this->singleRange($filePath, $ranges);

			exit(0);
		}
		public function multiRanges($filePath, $ranges)
		{
			$fileSize = filesize($filePath);
			$boundaryToken	= '--pb-' . sha1(uniqid('', TRUE));
			$rangeSize = 0;

			foreach ($ranges as $idx => $range)
			{
				$from = $range['from']; $to = $range['to'];
				$notValid = FALSE;

				// Check nullness and convert null values
				if ($from === NULL && $to === NULL)
					$notValid = $notValid || TRUE;
				else
				{
					if ($from === NULL)
					{
						$from = $fileSize - $to;
						$to = $fileSize - 1;
					}

					if ($to === NULL)   $to   = $fileSize - 1;

					// Validate other conditions
					if ($from < 0 || $to < 0) $notValid = $notValid || TRUE;
					if ($to < $from) $notValid = $notValid || TRUE;
					if ($from >= $fileSize || $to >= $fileSize) $notValid = $notValid || TRUE;
				}

				if ($notValid)
				{
					header("HTTP/1.1 416 Request Range Not Satisfiable");
					exit(0);
				}

				$ranges[$idx] = array('from' => $from, 'to' => $to);
				$rangeSize += strlen(CRLF . "--{$boundaryToken}" . CRLF);
				$rangeSize += strlen("Content-Type: {$this->_mime}" . CRLF);
				$rangeSize += strlen("Content-Range: bytes {$range['from']}-{$range['to']}/{$fileSize}" . CRLF . CRLF);

				$rangeSize += ($to - $from) + 1;
			}
			$rangeSize += strlen(CRLF . "--{$boundaryToken}--" . CRLF);


			$fileStream		= fopen($filePath, "rb");
			$outStream		= fopen("php://output", "wb");

			if (empty($fileStream))
			{
				header("HTTP/1.1 429 Too Many Requests");
				exit(0);
			}


			header('HTTP/1.1 206 Partial Content');
			header("Accept-Ranges: bytes");
			header("Content-Type: multipart/byteranges; boundary={$boundaryToken}");
			header("Content-Length: {$rangeSize}");


			foreach ($ranges as $range)
			{
				echo CRLF . "--{$boundaryToken}" . CRLF;

				echo "Content-Type: {$this->_mime}" . CRLF;
				echo "Content-Range: bytes {$range['from']}-{$range['to']}/{$fileSize}" . CRLF . CRLF;

				self::ChunkStream($outStream, $fileStream, $range);

			}
			echo CRLF . "--{$boundaryToken}--" . CRLF;

			fclose($fileStream);
			fclose($outStream);
		}
		public function singleRange($filePath, $ranges)
		{
			$fileSize = filesize($filePath);
			$endByte  = $fileSize - 1;


			$range = array_shift($ranges);
			$from = $range['from']; $to = $range['to'];
			$notValid = FALSE;

			// Check nullness and convert null values
			if ($from === NULL && $to === NULL)
				$notValid = $notValid || TRUE;
			else
			{
				if ($from === NULL)
				{
					$from = $fileSize - $to;
					$to = $fileSize - 1;
				}

				if ($to === NULL)   $to   = $fileSize - 1;

				// Validate other conditions
				if ($from < 0 || $to < 0) $notValid = $notValid || TRUE;
				if ($to < $from) $notValid = $notValid || TRUE;
				if ($from >= $fileSize || $to >= $fileSize) $notValid = $notValid || TRUE;
			}

			if ($notValid)
			{
				header("HTTP/1.1 416 Request Range Not Satisfiable");
				exit(0);
			}

			$range 		= array('from' => $from, 'to' => $to);
			$rangeSize  = ($to - $from) + 1;



			$fileStream	= fopen($filePath, "rb");
			$outStream  = fopen("php://output", "wb");

			if (empty($fileStream))
			{
				header("HTTP/1.1 429 Too Many Requests");
				exit(0);
			}


			header('HTTP/1.1 206 Partial Content');
			header("Accept-Ranges: 0-{$endByte}");
			header("Content-Type: {$this->_mime}");
			header("Content-Length: {$rangeSize}");
			header("Content-Range: bytes {$range['from']}-{$range['to']}/{$fileSize}");


			self::ChunkStream($outStream, $fileStream, $range);

			fclose($fileStream);
			fclose($outStream);
		}
		
		public static function ChunkStream($oStream, $iStream, $range, $packageSize = 1024, $restrict = FALSE) {
			$from = $range['from']; $to = $range['to'];
			$length = ($to - $from) + 1;

			fseek($iStream, $from);
			set_time_limit(0);

			while (!feof($iStream) && ($length > 0))
			{
				$readSize = min($length, $packageSize);
				fwrite($oStream, fread($iStream, $readSize));
				fflush($oStream);
				$length -= $readSize;
			}
		}
	}
