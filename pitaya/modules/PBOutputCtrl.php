<?php
	class PBHttpOut extends PBModule {
		protected static $_statusCode = NULL;
		public static function StatusCode( $code ) {
			self::$_statusCode = CAST( $code, 'int strict', NULL );
		}
		
		protected static $_contentType = NULL;
		public static function ContentType( $type ) {
			self::$_contentType = CAST( $type, 'string', NULL );
		}
		
		protected static $_headers = [];
		public static function Header( $headerList = [] ){
			self::$_headers = array_merge( self::$_headers, $headerList );
		}
		
		protected static $_outputData = NULL;
		public static function DataOut( ...$args ) {
			if ( count($args) > 1 ) {
				self::$_statusCode = CAST( $args[0], 'int strict', 200 );
				self::$_outputData = $args[1];
				return;
			}
			
			self::$_outputData = $args[0];
		}
	
	
	
		public function execute( $param ) {
			if ( IS_CLI_ENV ) return;
			
			
		
			if ( self::$_statusCode !== NULL )
				PBHTTP::ResponseStatus( self::$_statusCode );
			
			if ( self::$_contentType !== NULL )
				header( "Content-Type: " . self::$_contentType );
			
			foreach( self::$_headers as $field => $value ) {
				if ( $value === NULL ) continue;
				header( "{$field}: {$value}" );
			}
			
			
			
			$content = ( $param === NULL ) ? self::$_outputData : $param;
			if ( is_resource($content) ) {
				$output = fopen( "php://output", "a+b" );
				stream_copy_to_stream( $content, $output );
				fclose($output);
				return;
			}
			
			
			echo $content;
		}
	}