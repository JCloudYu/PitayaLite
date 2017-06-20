<?php
	final class PBStream extends PBObject {
	
		private $_pipes = array();
		public function __construct( $resource = NULL ) { $this->tee( $resource ); }
		
		
		
		public function tee( $resource )
		{
			if ( $resource === NULL ) return NULL;

			// Other PBStream object is feed
			if ( is_a( $resource, "PBStream" ) )
			{
				$this->_attachStream( $resource );
			}
			else
			// A stream resource is feed
			if ( is_resource( $resource ) )
			{
				$this->_attachResource( $resource );
			}
			else
			// A path is given
			if ( is_string( $resource ) )
			{
				$this->_attachPath( $resource );
			}

			// Purge pipes that are not valid anymore
			$this->_purgePipes();

			return $this;
		}
		public function pop( $close = FALSE )
		{
			$stream = @array_pop($this->_pipes);

			if ( $close )
			{
				if ( is_resource( $stream ) && get_resource_type($stream) == "stream" )
					fclose( $stream );

				$stream = NULL;
			}

			return $stream;
		}
		public function shift( $close = FALSE )
		{
			$stream = @array_shift($this->_pipes);

			if ( $close )
			{
				if ( is_resource( $stream ) && get_resource_type($stream) == "stream" )
					fclose( $stream );

				$stream = NULL;
			}

			return $stream;
		}
		public function write( $string, $length = NULL )
		{
			foreach ( $this->_pipes as $handle )
			{
				if ( !is_resource( $handle ) ) continue;

				if ( empty( $length ) )
					fwrite( $handle, $string );
				else
					fwrite( $handle, $string, $length );
			}

			return $this;
		}
		public function flush()
		{
			foreach ( $this->_pipes as $handle )
			{
				if ( !is_resource( $handle ) ) continue;
				fflush( $handle );
			}

			return $this;
		}
		
		
		public function __get_numBranches()
		{
			return count($this->_pipes);
		}
		private function _attachStream( PBStream $resource )
		{
			foreach ( $resource->_pipes as $handle )
			{
				if ( is_resource( $handle ) )
					$this->_pipes[] = $handle;
			}

		}
		private function _attachPath( $path )
		{
			$handle = @fopen($path, $mode);
			if ( $handle ) $this->_pipes[] = $handle;
		}
		private function _attachResource( $resource )
		{
			if ( get_resource_type( $resource ) != "stream" ) return;
			$this->_pipes[] = $resource;
		}
		private function _purgePipes()
		{
			$newPipes = array();
			foreach ( $this->_pipes as $idx => $handle )
			{
				if ( is_resource( $handle ) )
					$newPipes[] = $handle;
			}

			$old = $this->_pipes;
			$this->_pipes = $newPipes;
			unset( $old );
		}






		/** @var PBStream */
		private static $_OUT_STREAM = NULL;
		public static function STDOUT()
		{
			if ( self::$_OUT_STREAM ) return self::$_OUT_STREAM;
			return ( self::$_OUT_STREAM = new PBStream( STDOUT ) );
		}
		
		/** @var PBStream */
		private static $_IN_STREAM = NULL;
		public static function STDIN()
		{
			if ( self::$_IN_STREAM ) return self::$_IN_STREAM;
			return ( self::$_IN_STREAM = new PBStream( STDIN ) );
		}

		/** @var PBStream */
		private static $_ERR_STREAM = NULL;
		public static function STDERR()
		{
			if ( self::$_ERR_STREAM ) return self::$_ERR_STREAM;
			return ( self::$_ERR_STREAM = new PBStream( STDERR ) );
		}

		public static function Open( $path, $mode = "a+b" ) {
			$handle = @fopen($path, $mode);
			if ( $handle === FALSE ) return NULL;

			return new PBStream( $handle );
		}
		public static function Rotatable( $path )
		{
			if ( is_dir( $path ) ) return NULL;


			$dir = dirname( $path );
			if ( !is_dir($dir) && (@mkdir( $dir ) === FALSE) ) return NULL;


			if ( is_file( $path ) )
			{
				$today	  = strtotime( date('Y-m-d') );
				$fileTime = filemtime( $path );

				if ( $fileTime <= $today )
				{
					$fileTime = date('Ymd', $fileTime);
					if ( rename( $path, "{$path}-{$fileTime}" ) === FALSE )
						return NULL;
				}
			}

			$handle = @fopen( $path, "a+b" );
			if ( $handle === FALSE ) return NULL;

			return new PBStream( $handle );
		}
	}
