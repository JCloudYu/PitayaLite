<?php
	/**
	 ** 1024.QueueCounter - PBPusher.php
	 ** Created by JCloudYu on 2016/08/05 21:02
	 **/

	class PBPusher
	{
		const SUPPORTED_PORTAL_IDS = [ 'apn', 'gcm' ];
		
		private $_portals = [];
	
		public function __construct( $connectInfos = [] )
		{
			foreach ( $connectInfos as $portalId => $connectInfo )
			{
				switch( $portalId )
				{
					case "apn":
						$this->_portals[ "apn" ] = new PBAPNSPortal();
						$this->_portals[ "apn" ]->connect( $connectInfo );
						break;
					
					case "gcm":
						$this->_portals[ "gcm" ] = new PBGCMPortal();
						$this->_portals[ "gcm" ]->connect( $connectInfo );
						break;
					
					default:
						break;
				}
			}
		}
		public function push( $payload = [] ) {
			$portal = @$this->_portals[ @"{$payload[ 'portal' ]}" ];
			if ( empty($portal) ) {
				return PBIPushPortal::__RESPOND_SEND_STATUS( FALSE, -1, [ "error" => "Invalid Portal!" ] );
			}
			return $portal->push( $payload );
		}
		
		public static function BasicPayload( $portal, $target, $title, $body, $sound, $badge, $data = NULL, $options = [] ) {
			$payload = [
				'portal'		=> $portal,
				'token'			=> $target,
				'background'	=> !empty($options[ 'background' ]),
				'expire'		=> $options[ 'expire' ],
				'notification'	=> [
					'title' => $title,
					'body'	=> $body,
					'sound'	=> $sound,
					'badge'	=> $badge
				]
			];
			
			
			
			if ( is_array($data) ) $payload[ 'data' ] = $data;
			return $payload;
		}
	};
	
	abstract class PBIPushPortal extends PBObject {
	
		public abstract function connect( $connectInfo = [] );
		public abstract function push( $payload = [] );
		
		public function disconnect() { return TRUE; }
		public function reconnect() { return TRUE; }
		
		
		
		public static function __IDENTIFIER( $range = MONTH_SEC ) {
			return ( ( microtime( TRUE ) * 1000 ) | 0 ) % $range;
		}
		public static function __RESPOND_SEND_STATUS( $status, $identifier, $additionalInfo = NULL ) {
			return [ 'status' => $status, 'identifier' => $identifier, 'info' => $additionalInfo ];
		}
	}
	class PBAPNSPortal extends PBIPushPortal {
	
		const DEFAULT_CONNECTION_TIMEOUT	= 100;
		const DEVELOPMENT_PUSH_SERVER		= "gateway.sandbox.push.apple.com";
		const PRODUCTION_PUSH_SERVER		= "gateway.push.apple.com";
		const APNS_ERROR_MESSAGE_MAP		= [
			0	=> "No errors encountered",
			1	=> "Processing error",
			2	=> "Missing device token",
			3	=> "Missing topic",
			4	=> "Missing payload",
			5	=> "Invalid token size",
			6	=> "Invalid topic size",
			7	=> "Invalid payload size",
			8	=> "Invalid token",
			10	=> "Shutdown",
			128	=> "Protocol error (APNs could not parse the notification)",
			255	=> "None (unknown)"
		];


		private $_serverAddr = "";
		private $_certPath	 = "";
		private $_certPass	 = "";
		private $_conTimeout = self::DEFAULT_CONNECTION_TIMEOUT;
		private $_connection = NULL;


		public function __destruct() {
			$this->disconnect();
		}
		public function __get_isConnected() {
			return !empty( $this->_connection );
		}

		public function connect( $connectInfo = [] ) {
			// INFO: Force reconnection
			if ( $this->isConnected ) $this->disconnect();
			
			
			
			// INFO: Store connection information
			$this->_serverAddr	= @"{$connectInfo[ 'address' ]}";
			$this->_certPath	= @"{$connectInfo[ 'certificate' ]}";
			$this->_certPass	= @"{$connectInfo[ 'password' ]}";
			$this->_conTimeout	= empty($connectInfo[ 'timeout' ]) ? self::DEFAULT_CONNECTION_TIMEOUT : $connectInfo[ 'timeout' ];
			
			
			
			// INFO: Create stream
			$context = stream_context_create();
			stream_context_set_option( $context, 'ssl', 'local_cert', $this->_certPath );
			if ( !empty( $this->_certPass ) )
				stream_context_set_option( $context, 'ssl', 'passphrase', $this->_certPass );

			$this->_connection = stream_socket_client(
				"ssl://{$this->_serverAddr}:2195", $error, $errorStr,
				$this->_conTimeout,
				STREAM_CLIENT_CONNECT,
				$context
			);

			if ( $this->_connection === FALSE ) {
				$this->_connection = NULL;

				return array(
					'error' => $error,
					'msg'   => $errorStr
				);
			}


			stream_set_blocking( $this->_connection, 0 );
			return TRUE;
		}
		public function reconnect( $connectInfo = [] ) {
			$this->disconnect();
			
			$info = empty( $connectInfo ) ? [
				'address'		=> $this->_serverAddr,
				'certificate'	=> $this->_certPath,
				'password'		=> $this->_certPass,
				'timeout'		=> $this->_conTimeout
			] : $connectInfo;
			
			return $this->connect( $info );
		}
		public function disconnect() {
		
			if ( !empty( $this->_connection ) )
				fclose( $this->_connection );

			$this->_connection = NULL;
			
			return TRUE;
		}
		public function push( $msgContent = [] ) {
		
			$identifier = parent::__IDENTIFIER();
		
			if ( !$this->isConnected ) {
				return parent::__RESPOND_SEND_STATUS( FALSE, $identifier, [ "error" => "Not Connected!" ]);
			}
		
			
			$currTime		= time();
			$token			= $msgContent[ 'token' ];
			$background		= CAST( $msgContent[ 'background' ], 'int strict', 0 );
			$expire			= TO( $msgContent[ 'expire' ], 'int strict' );
			$priority		= TO( $msgContent[ 'priority' ], 'int strict' );
			


			$payload = $msgContent[ 'data' ];
			
			$payload[ 'aps' ] = [
				'alert' => [
					'title' 	=> $msgContent[ 'notification' ][ 'title' ],
					'body'		=> $msgContent[ 'notification' ][ 'body' ],
				],
				'sound'		=> $msgContent[ 'notification' ][ 'sound' ],
				'badge'		=> $msgContent[ 'notification' ][ 'badge' ]
			];
			
			if ( $background > 0 ) $payload[ 'aps' ][ 'content-available' ] = 1;
				
			
			
			$response = self::__SEND( $this->_connection, $token, $payload, [
				'identifier' => $identifier,
				'expire'	 => ( $expire < 0 || (4294967295.0 - floatval($expire)) < 0 ) ? ($currTime + MONTH_SEC) : $expire,
				'priority'	 => in_array( $priority, [ 5, 10 ] ) ? $priority : 10
			]);


			$status = ($response === TRUE);
			if ( !$status ) $this->reconnect();
			
			return parent::__RESPOND_SEND_STATUS( $status, $identifier, $status ? [] : $response );
		}
		
		
		
		private static function __SEND( $stream, $token, $payload, $options = [], $blockTime = 0.5 ) {
			$frames = "";
		
			// Token Frame
			$data = pack( "H*", $token );
			$frames .= pack( "Cn", 1, strlen($data) ) . $data;
			
			// Payload
			$data = json_encode( $payload );
			$frames .= pack( "Cn", 2, strlen($data) ) . $data;
			
			
			
			// Notification Identifier
			if ( array_key_exists( 'identifier', $options ) )
			{
				$data = pack( "N", $options[ 'identifier' ] );
				$frames .= pack( "Cn", 3, strlen($data) ) . $data;
			}
			
			// Notification Expiration Date ( The end of the day )
			if ( array_key_exists( 'expiration', $options ) )
			{
				$data = pack( "N", $options[ 'expiration' ] );
				$frames .= pack( "Cn", 4, strlen($data) ) . $data;
			}
			
			
			// Message Priority
			if ( array_key_exists( 'priority', $options ) )
			{
				$data = pack( "C", $options[ 'priority' ] );
				$frames .= pack( "Cn", 5, strlen($data) ) . $data;
			}
			
			
			
			// Pack everything together
			$package = pack( "CN", 2, strlen($frames) ) . $frames;
			fwrite( $stream, $package );
			
			
			
			if ( !empty($blockTime) ) usleep( $blockTime * 1000000 );
			return self::__EatResponse( $stream );
		}
		private static function __EatResponse( $stream ) {
			$response = fread( $stream, 6 );
			
			if ( $response )
			{
				$response = unpack( "Ccommand/Ccode/Nidentifier", $response );
				$response[ 'reason' ] = self::APNS_ERROR_MESSAGE_MAP[ $response['code'] ];
				return $response;
			}
			
			return TRUE;
		}
	}
	class PBGCMPortal extends PBIPushPortal {
	
		const DEFAULT_CONNECTION_TIMEOUT	= 100;
		const GCM_HTTP_SERVER_ADDRESS		= "https://gcm-http.googleapis.com/gcm/send";
		const GCM_ANDROID_SERVER_ADDRESS	= "https://android.googleapis.com/gcm/send";


		private $_serverAddr = "";
		private $_serverKey	 = "";
		private $_conTimeout = self::DEFAULT_CONNECTION_TIMEOUT;

		
		
		public function connect( $connectInfo = [] ) {
		
			$this->_serverAddr	= @"{$connectInfo[ 'address' ]}";
			$this->_serverKey	= @"{$connectInfo[ 'key' ]}";
			$this->_conTimeout	= empty($connectInfo[ 'timeout' ]) ? self::DEFAULT_CONNECTION_TIMEOUT : $connectInfo[ 'timeout' ];

			return TRUE;
		}
		public function reconnect( $connectInfo = [] ) {
			$info = empty( $connectInfo ) ? [
				'address'		=> $this->_serverAddr,
				'key'			=> $this->_serverKey,
				'timeout'		=> $this->_conTimeout
			] : $connectInfo;
			
			return $this->connect( $info );
		}
		public function push( $msgContent = [] ) {
		
			$currTime	= time();
			$token		= $msgContent[ 'token' ];
			$background	= CAST( $msgContent[ 'background' ], 'int strict', 0 );
			$identifier	= parent::__IDENTIFIER();
			
			
			
			$payload[ is_array($token) ? 'registration_ids' : 'to' ] = $token;
			$payload[ 'content-available' ] = !!$background;
			
			if ( !empty($msgContent['data']) )
				$payload[ 'data' ] = $msgContent[ 'data' ];
			
			
			if ( !empty($msgContent[ 'notification' ]) )
			{
				$payload[ 'notification' ] = [
					'title' => "{$msgContent['notification'][ 'title' ]}",
					'tag'	=> "{$identifier}"
				];
				
				if ( array_key_exists( 'body', $msgContent['notification'] ) )
					$payload[ 'notification' ][ 'body' ] = "{$msgContent['notification']['body']}";
				
				if ( array_key_exists( 'icon', $msgContent['notification'] ) )
					$payload[ 'notification' ][ 'icon' ] = empty($msgContent['notification']['icon']) ? "myicon" : "{$msgContent['notification']['icon']}";
					
				if ( array_key_exists( 'sound', $msgContent['notification'] ) )
					$payload[ 'notification' ][ 'sound' ] = empty($msgContent['notification']['sound']) ? "default" : "{$msgContent['notification']['sound']}";
					
				if ( array_key_exists( 'color', $msgContent['notification'] ) )
					$payload[ 'notification' ][ 'color' ] = empty($msgContent['notification']['sound']) ? "#FFFFFF" : "{$msgContent['notification']['color']}";
			}


			$payloadStr = json_encode($payload);
			$payloadLen	 = strlen($payloadStr);

			$hCurl = curl_init();
			curl_setopt( $hCurl, CURLOPT_URL, $this->_serverAddr );
			curl_setopt( $hCurl, CURLOPT_FRESH_CONNECT, TRUE );
			curl_setopt( $hCurl, CURLOPT_CUSTOMREQUEST, 'POST' );
			curl_setopt( $hCurl, CURLOPT_HTTPHEADER, array (
				'Authorization: key=' . trim($this->_serverKey),
				'Content-Type: application/json',
				"Content-Length: {$payloadLen}"
			));
			curl_setopt( $hCurl, CURLOPT_POSTFIELDS, $payloadStr );
			curl_setopt( $hCurl, CURLOPT_RETURNTRANSFER, TRUE );
			curl_setopt( $hCurl, CURLOPT_SSL_VERIFYPEER, FALSE );
			$response = curl_exec( $hCurl );
			
			if ( $response === FALSE )
			{
				$code		= -1;
				$error		= curl_error( $hCurl );
				$response	= NULL;
			}
			else
			{
				$result = @json_decode( $response, TRUE );
			
				$code		= curl_getinfo( $hCurl, CURLINFO_HTTP_CODE );
				$error		= empty($result) ? $response : NULL;
				$response	= empty($result) ? $response : $result;
			}
			curl_close( $hCurl );
			
			
			
			return parent::__RESPOND_SEND_STATUS(
				($code == 200) && (is_array($response)) && ($response[ 'success' ] > 0),
				$identifier,
				[
					'code'		=> $code,
					'error'		=> $error,
					'response'	=> $response
				]
			);
		}
	}
	
