<?php
	final class PBHTTP {
		public static function ResponseContentType( $type ) {
			header( "Content-Type: {$type}" );
		}
		public static function ResponseStatus( $status ) {
			$statusMsg = PBHTTP::STATUS_STRING[ $status ];
			if ( empty($statusMsg) ) throw new Exception("Unsupported HTTP Status Code");

			header("HTTP/1.1 {$status} {$statusMsg}");
			header("Status: {$status} {$statusMsg}");
		}
		public static function ResponseJSON( $obj, $status = NULL ) {
			self::ResponseContent( json_encode($obj), "application/json", $status );
		}
		public static function ResponseContent( $content, $contentType = "text/plain", $status = NULL ) {
			if ( $status !== NULL ) self::ResponseStatus( $status );
			header("Content-Type: {$contentType}");



			if ( !is_resource($content) )
			{
				echo "{$content}";
				return;
			}

			$output = fopen( "php://output", "a+b" );
			stream_copy_to_stream( $content, $output );
			fclose($output);
		}
		
		const DEFAULT_COOKIE_INFO = [
			'name'		=> '',
			'value'		=> '',
			'expire'	=> 0,
			'duration'	=> 0,
			'domain'	=> '',
			'path'		=> '/',
			'ssl'		=> TRUE,
			'http'		=> TRUE,
			'same-site'	=> 'strict' // @string(def:'strict'|'lax')
		];
		public static function SetCookie( $options ) {
			if ( headers_sent() ) return FALSE;
			if ( is_a( $options, stdClass::class ) ) {
				$options = (array)$options;
			}
			
			if ( !is_array($options) ) return NULL;
			
			$firstElm = current($options);
			if ( !is_array($firstElm) && !is_object($firstElm) )
				$options = [ $options ];
	
	
			
			data_filter($options, function( $cookie ){
				if ( is_a( $cookie, stdClass::class ) )
					$cookie = (array)$cookie;
			
				if ( !is_array($cookie) || empty($cookie['name']) ) 
					return NULL;
			
			
			
				$cookieAttr   = [];
				$cookieAttr[] = @"{$cookie[ 'name' ]}={$cookie[ 'value' ]}";
				
				if ( array_key_exists( 'expire', $cookie ) ) {
					$expireTime  = CAST( $cookie[ 'expire' ], 'int strict', PITAYA_BOOT_TIME );
					$expireStamp = date( "D, d M Y H:m:s", $expireTime - PITAYA_ZONE_DIFF );
					$cookieAttr[] = "Expire={$expireStamp} GMT";
				}
				
				if ( array_key_exists( 'duration', $cookie ) ) {
					$duration = CAST( $cookie[ 'duration' ], 'int strict', 0 );
					$cookieAttr[] = "Max-Age={$duration}";
				}
				
				if ( !empty($cookie[ 'domain' ]) ) {
					$cookieAttr[] = "Domain={$cookie[ 'domain' ]}";
				}
				
				if ( !empty($cookie[ 'path' ]) ) {
					$cookieAttr[] = "Path={$cookie[ 'path' ]}";
				}
					
				if ( !empty($cookie[ 'ssl' ]) ) {
					$cookieAttr[] = "Secure";
				}
				
				if ( !empty($cookie[ 'http' ]) ) {
					$cookieAttr[] = "HttpOnly";
				}
				
				if ( !empty($cookie[ 'same-site' ]) ) {
					$cookieAttr[] = "SameSite=" . ucfirst( @"{$cookie[ 'same-site' ]}" );
				}
				
				$cookieAttribute = implode( '; ', $cookieAttr );
				header( "Set-Cookie: {$cookieAttribute};" );
			}, NULL);
			
			return TRUE;
		}
		
		// region [ HTTP Status Code ]
		//INFO: Information
		const STATUS_100_CONTINUE								= 100;
		const STATUS_101_SWITCHING_PROTOCOLS					= 101;
		const STATUS_102_PROCESSING								= 102;

		//INFO: Success
		const STATUS_200_OK										= 200;
		const STATUS_201_CREATED								= 201;
		const STATUS_202_ACCEPTED								= 202;
		const STATUS_203_NON_AUTHORITATIVE_INFORMATION			= 203;
		const STATUS_204_NO_CONTENT								= 204;
		const STATUS_205_RESET_CONTENT							= 205;
		const STATUS_206_PARTIAL_CONTENT						= 206;
		const STATUS_207_MULTI_STATUS							= 207;
		const STATUS_208_ALREADY_REPORTED						= 208;
		const STATUS_226_IM_USED								= 226;

		//INFO: Redirect
		const STATUS_300_MULTIPLE_CHOICES						= 300;
		const STATUS_301_MOVED_PERMANENTLY						= 301;
		const STATUS_302_FOUND									= 302;
		const STATUS_303_SEE_OTHER								= 303;
		const STATUS_304_NOT_MODIFIED							= 304;
		const STATUS_305_USE_PROXY								= 305;
		const STATUS_306_SWITCH_PROXY							= 306;
		const STATUS_307_TEMPORARY_REDIRECT						= 307;
		const STATUS_308_PERMANENT_REDIRECT						= 308;

		//INFO: Client Error
		const STATUS_400_BAD_REQUEST							= 400;
		const STATUS_401_UNAUTHORIZED							= 401;
		const STATUS_402_PAYMENT_REQUIRED						= 402;
		const STATUS_403_FORBIDDEN								= 403;
		const STATUS_404_NOT_FOUND								= 404;
		const STATUS_405_METHOD_NOT_ALLOWED						= 405;
		const STATUS_406_NOT_ACCEPTABLE							= 406;
		const STATUS_407_PROXY_AUTHENTICATION_REQUIRED			= 407;
		const STATUS_408_REQUEST_TIMEOUT						= 408;
		const STATUS_409_CONFLICT								= 409;
		const STATUS_410_GONE									= 410;
		const STATUS_411_LENGTH_REQUIRED						= 411;
		const STATUS_412_PRECONDITION_FAILED					= 412;
		const STATUS_413_REQUEST_ENTITY_TOO_LARGE				= 413;
		const STATUS_414_REQUEST_URI_TOO_LONG					= 414;
		const STATUS_415_UNSUPPORTED_MEDIA_TYPE					= 415;
		const STATUS_416_REQUEST_RANGE_NOT_SATISFIABLE			= 416;
		const STATUS_417_EXPECTATION_FAILED						= 417;
		const STATUS_418_IM_A_TEAPOT							= 418;
		const STATUS_420_ENHANCE_YOUR_CALM						= 420;
		const STATUS_422_UNPROCESSABLE_ENTITY					= 422;
		const STATUS_423_LOCKED									= 423;
		const STATUS_424_FAILED_DEPENDENCY						= 424;
		const STATUS_424_METHOD_FAILURE							= 424;
		const STATUS_425_UNORDERED_COLLECTION					= 425;
		const STATUS_426_UPGRADE_REQUIRED						= 426;
		const STATUS_428_PRECONDITION_REQUIRED 					= 428;
		const STATUS_429_TOO_MANY_REQUESTS						= 429;
		const STATUS_431_REQUEST_HEADER_FIELDS_TOO_MANY			= 431;
		const STATUS_444_NO_RESPONSE							= 444;
		const STATUS_449_RETRY_WITH								= 449;
		const STATUS_450_BLOCKED_BY_WINDOWS_PARENTAL_CONTROLS	= 450;
		const STATUS_451_UNAVAILABLE_FOR_LEGAL_REASONS			= 451;
		const STATUS_451_REDIRECT								= 451;
		const STATUS_494_REQUEST_HEADER_TOO_LARGE				= 494;
		const STATUS_495_CERT_ERROR								= 495;
		const STATUS_496_NO_CERT								= 496;
		const STATUS_497_HTTP_TO_HTTPS							= 497;
		const STATUS_499_CLIENT_CLOSED_REQUEST					= 499;

		//INFO: Server Error
		const STATUS_500_INTERNAL_SERVER_ERROR					= 500;
		const STATUS_501_NOT_IMPLEMENTED						= 501;
		const STATUS_502_BAD_GATEWAY							= 502;
		const STATUS_503_SERVICE_UNAVAILABLE					= 503;
		const STATUS_504_GATEWAY_TIMEOUT						= 504;
		const STATUS_505_HTTP_VERSION_NOT_SUPPORTED				= 505;
		const STATUS_506_VARIANT_ALSO_NEGOTIATES				= 506;
		const STATUS_507_INSUFFICIENT_STORAGE					= 507;
		const STATUS_508_LOOP_DETECTED							= 508;
		const STATUS_509_BANDWIDTH_LIMIT_EXCEEDED				= 509;
		const STATUS_510_NOT_EXTENDED							= 510;
		const STATUS_511_NETWORK_AUTHENTICATION_REQUIRED		= 511;
		const STATUS_598_NETWORK_READ_TIMEOUT					= 598;
		const STATUS_599_NETWORK_CONNECT_TIMEOUT_ERROR			= 599;
		
		//INFO: Status String
		const STATUS_STRING = [
			self::STATUS_100_CONTINUE								=> 'Continue',
			self::STATUS_101_SWITCHING_PROTOCOLS					=> 'Switching Protocols',
			self::STATUS_102_PROCESSING								=> 'Processing',

			self::STATUS_200_OK										=> 'OK',
			self::STATUS_201_CREATED								=> 'Created',
			self::STATUS_202_ACCEPTED								=> 'Accepted',
			self::STATUS_203_NON_AUTHORITATIVE_INFORMATION			=> 'Non-Authoritative Information',
			self::STATUS_204_NO_CONTENT								=> 'No Content',
			self::STATUS_205_RESET_CONTENT							=> 'Reset Content',
			self::STATUS_206_PARTIAL_CONTENT						=> 'Partial Content',
			self::STATUS_207_MULTI_STATUS							=> 'Multi-Status',
			self::STATUS_208_ALREADY_REPORTED						=> 'Already Reported',
			self::STATUS_226_IM_USED								=> 'IM Used',

			self::STATUS_300_MULTIPLE_CHOICES						=> 'Multiple Choices',
			self::STATUS_301_MOVED_PERMANENTLY						=> 'Moved Permanently',
			self::STATUS_302_FOUND									=> 'Found',
			self::STATUS_303_SEE_OTHER								=> 'See Other',
			self::STATUS_304_NOT_MODIFIED							=> 'Not Modified',
			self::STATUS_305_USE_PROXY								=> 'Use Proxy',
			self::STATUS_306_SWITCH_PROXY							=> 'Switch Proxy',
			self::STATUS_307_TEMPORARY_REDIRECT						=> 'Temporary Redirect',
			self::STATUS_308_PERMANENT_REDIRECT						=> 'Permanent Redirect',

			self::STATUS_400_BAD_REQUEST							=> 'Bad Request',
			self::STATUS_401_UNAUTHORIZED							=> 'Unauthorized',
			self::STATUS_402_PAYMENT_REQUIRED						=> 'Payment Required',
			self::STATUS_403_FORBIDDEN								=> 'Forbidden',
			self::STATUS_404_NOT_FOUND								=> 'Not Found',
			self::STATUS_405_METHOD_NOT_ALLOWED						=> 'Method Not Allowed',
			self::STATUS_406_NOT_ACCEPTABLE							=> 'Not Acceptable',
			self::STATUS_407_PROXY_AUTHENTICATION_REQUIRED			=> 'Proxy Authentication Required',
			self::STATUS_408_REQUEST_TIMEOUT						=> 'Request Timeout',
			self::STATUS_409_CONFLICT								=> 'Conflict',
			self::STATUS_410_GONE									=> 'Gone',
			self::STATUS_411_LENGTH_REQUIRED						=> 'Length Required',
			self::STATUS_412_PRECONDITION_FAILED					=> 'Precondition Failed',
			self::STATUS_413_REQUEST_ENTITY_TOO_LARGE				=> 'Request Entity Too Large',
			self::STATUS_414_REQUEST_URI_TOO_LONG					=> 'Request-URI Too Long',
			self::STATUS_415_UNSUPPORTED_MEDIA_TYPE					=> 'Unsupported Media Type',
			self::STATUS_416_REQUEST_RANGE_NOT_SATISFIABLE			=> 'Request Range Not Satisfiable',
			self::STATUS_417_EXPECTATION_FAILED						=> 'Expectation Failed',
			self::STATUS_418_IM_A_TEAPOT							=> 'I\'m a teapot',
			self::STATUS_420_ENHANCE_YOUR_CALM						=> 'Enhance Your Calm',
			self::STATUS_422_UNPROCESSABLE_ENTITY					=> 'Unprocessable Entity',
			self::STATUS_423_LOCKED									=> 'Locked',
			self::STATUS_424_FAILED_DEPENDENCY						=> 'Failed Dependency',
//			self::STATUS_424_FAILED_DEPENDENCY						=> 'Method Failure',
			self::STATUS_425_UNORDERED_COLLECTION					=> 'Unordered Collection',
			self::STATUS_426_UPGRADE_REQUIRED						=> 'Upgrade Required',
			self::STATUS_428_PRECONDITION_REQUIRED					=> 'Precondition Required',
			self::STATUS_429_TOO_MANY_REQUESTS						=> 'Too Many Requests',
			self::STATUS_431_REQUEST_HEADER_FIELDS_TOO_MANY			=> 'Request Header Fields Too Large',
			self::STATUS_444_NO_RESPONSE							=> 'No Response',
			self::STATUS_449_RETRY_WITH								=> 'Retry With',
			self::STATUS_450_BLOCKED_BY_WINDOWS_PARENTAL_CONTROLS	=> 'Blocked by Windows Parental Controls',
//			self::STATUS_451_REDIRECT								=> 'Unavailable For Legal Reasons',
			self::STATUS_451_REDIRECT								=> 'Redirect',
			self::STATUS_494_REQUEST_HEADER_TOO_LARGE				=> 'Request Header Too Large',
			self::STATUS_495_CERT_ERROR								=> 'Cert Error',
			self::STATUS_496_NO_CERT								=> 'No Cert',
			self::STATUS_497_HTTP_TO_HTTPS							=> 'HTTP to HTTPS',
			self::STATUS_499_CLIENT_CLOSED_REQUEST					=> 'Client Closed Request',

			self::STATUS_500_INTERNAL_SERVER_ERROR					=> 'Internal Server Error',
			self::STATUS_501_NOT_IMPLEMENTED						=> 'Not Implemented',
			self::STATUS_502_BAD_GATEWAY							=> 'Bad Gateway',
			self::STATUS_503_SERVICE_UNAVAILABLE					=> 'Service Unavailable',
			self::STATUS_504_GATEWAY_TIMEOUT						=> 'Gateway Timeout',
			self::STATUS_505_HTTP_VERSION_NOT_SUPPORTED				=> 'HTTP Version Not Supported',
			self::STATUS_506_VARIANT_ALSO_NEGOTIATES				=> 'Variant Also Negotiates',
			self::STATUS_507_INSUFFICIENT_STORAGE					=> 'Insufficient Storage',
			self::STATUS_508_LOOP_DETECTED							=> 'Loop Detected',
			self::STATUS_509_BANDWIDTH_LIMIT_EXCEEDED				=> 'Bandwidth Limit Exceeded',
			self::STATUS_510_NOT_EXTENDED							=> 'Not Extended',
			self::STATUS_511_NETWORK_AUTHENTICATION_REQUIRED		=> 'Network Authentication Required',
			self::STATUS_598_NETWORK_READ_TIMEOUT					=> 'Network read timeout error',
			self::STATUS_599_NETWORK_CONNECT_TIMEOUT_ERROR			=> 'Network connect timeout error'
		];
		// endregion
	}
