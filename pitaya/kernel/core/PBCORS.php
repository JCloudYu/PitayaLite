<?php
	using('kernel.core.PBRequest');
	
	final class PBCORS extends PBObject {
		private static $_reqInstance = NULL;
		public static function CORS()
		{
			if (self::$_reqInstance) return self::$_reqInstance;

			self::$_reqInstance = new PBCORS();
			return self::$_reqInstance;
		}
		
		private $_CORS_Data = NULL;
		private function __construct(){
			$headers = PBRequest::Request()->headers;
			
			$this->_CORS_Data =  [
				'origin'	=> (@$headers[ 'Origin' ] === NULL) ? NULL : strtolower("{$headers['Origin']}"),
				'method'	=> (@$headers[ 'Access-Control-Request-Method' ] === NULL) ? NULL : strtoupper("{$headers['Access-Control-Request-Method']}"),
				'headers'	=> (@$headers[ 'Access-Control-Request-Headers' ] === NULL) ? NULL : ary_filter( explode( ',', @"{$headers[ 'Access-Control-Request-Headers' ]}"), function( $item ){ return ucwords(trim($item), '-'); }, '' )
			];
		}
		
		public function __get_origin(){  return $this->_CORS_Data[ 'origin' ]; }
		public function __get_method(){  return $this->_CORS_Data[ 'method' ]; }
		public function __get_headers(){ return $this->_CORS_Data[ 'headers' ]; }
		
		public function filterOrigin( $whiteList = [] ) {
			$origin = @$this->_CORS_Data[ 'origin' ];
			$acceptNull = FALSE; $wildcard = FALSE;
			
			$filterd = ary_filter( $whiteList, function( $candidate ) use ( $origin, &$acceptNull, &$wildcard ) {
				$acceptNull = $acceptNull || ( $candidate === NULL );
				$wildcard = $wildcard || ($candidate === '*');
				
				if ( $origin === $candidate ) return TRUE;
			}, FALSE );
			
			if ( $origin === NULL && $acceptNull ) return '*';
			
			
			
			$finalDomain = @array_shift( $filterd );
			if ( $finalDomain === NULL && $wildcard ) return $origin;
			
			return $finalDomain;
		}
		public function filterMethod( $whiteList = [] ) {
			if ( $this->_CORS_Data[ 'method' ] === NULL ) return FALSE;
			
			foreach( $whiteList as $method )
			{
				$method = strtoupper( $method );
				if ( $method == $this->_CORS_Data[ 'method' ] )
					return $method;
			}
			
			return NULL;
		}
		public function filterHeaders( $whiteList = [] ) {
			if ( $this->_CORS_Data[ 'headers' ] === NULL ) return FALSE;
			
			$validHeaders = [];
			foreach( $whiteList as $header )
			{
				$header = ucwords( $header, '-' );
				$key = array_search( $header, $this->_CORS_Data['headers'] );
				if ( $key === FALSE ) continue;
				
				$validHeaders[] = $key;
			}
			
			return $validHeaders;
		}
		
		public function acceptOrigin( $whiteList = [], &$status = TRUE ) {
		
			$acceptedOrigin = ( is_array( $whiteList ) ) ? $this->filterOrigin( $whiteList ) : "{$whiteList}";
			
			if ( $acceptedOrigin )
			{
				header( "Access-Control-Allow-Origin: {$acceptedOrigin}" );
				$status = TRUE;
			}
			else
			{
				PBHTTP::ResponseStatus( PBHTTP::STATUS_403_FORBIDDEN );
				$status = FALSE;
			}
			
			
			
			return $this;
		}
		public function acceptMethod( $whiteList = [], &$status = TRUE ) {
			$acceptedMethod = $this->filterMethod( $whiteList );
			if ( !$acceptedMethod )
				PBHTTP::ResponseStatus( PBHTTP::STATUS_403_FORBIDDEN );
			
			foreach( $whiteList as &$method ) $method = strtoupper($method);
			if ( array_search( 'OPTIONS', $whiteList ) === FALSE ) $whiteList[] = 'OPTIONS';
			
			header( "Access-Control-Allow-Method: " . implode( ', ', $whiteList ) );
			$status = !!$acceptedMethod;
			
			
			
			return $this;
		}
		public function acceptHeaders( $whiteList = [], &$status = TRUE ) {
			$validHeaders = $this->filterHeaders( $whiteList );
			$isValid = ( $validHeaders === FALSE || count($validHeaders) === count($this->_CORS_Data[ 'headers' ]) );
			
			if ( !$isValid )
				PBHTTP::ResponseStatus( PBHTTP::STATUS_403_FORBIDDEN );
			
			foreach( $whiteList as &$header ) $header = ucwords( $header, '-' );
			header( "Access-Control-Allow-Headers: " . implode( ', ', $whiteList ) );
			$status = $isValid;
			
			
			
			return $this;
		}
		public function acceptCredentials( $allowCredentials = TRUE, &$status = TRUE ) {
			$allowCredentials = !empty($allowCredentials) ? 'true' : 'false';
			header( "Access-Control-Allow-Credentials: {$allowCredentials}" );
			$status = TRUE;
			
			
			
			return $this;
		}
		public function acceptDuration( $duration = 0, &$status = TRUE ){
			$duration = CAST( $duration, 'int strict' );
			$status = ( $duration >= 0 );
			if ( $status ) header( "Access-Control-Max-Age: {$duration}" );
			
			
			
			return $this;
		}
		public function exposeHeaders( $headers = [], &$status = TRUE ) {
			foreach( $headers as &$header ) $header = ucwords("{$header}");
			$status = !empty($headers);
			
			if ( $status )
			{
				$headers = implode( ', ', $headers );
				header( "Access-Control-Expose-Headers: {$headers}" );
			}
			
			
			
			return $this;
		}
	}
	
	
