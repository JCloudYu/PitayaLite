<?php
	using( 'tool.NotifyKernel.INotifyKernel' );

	class APNKernel extends PBObject implements INotifyKernel
	{
		const DEVELOPMENT_PUSH_SERVER	= "gateway.sandbox.push.apple.com";
		const PRODUCTION_PUSH_SERVER	= "gateway.push.apple.com";

		private $_serverAddr = "";
		private $_certPath	 = "";
		private $_certPass	 = "";
		private $_conTimeout = 100;
		private $_connection = NULL;



		public function __construct() { }
		public function __destruct() {
			$this->disconnect();
		}
		public function __get_isConnected() {
			return !empty( $this->_connection );
		}



		public function connect( $serverAddr, $certPath, $certPass, $timeout = 100 )
		{
			if ( $this->isConnected )
				$this->disconnect();


			$this->_serverAddr = $serverAddr;
			$this->_certPath   = $certPath;
			$this->_certPass   = $certPass;
			$this->_conTimeout = $timeout;


			$context = stream_context_create();
			stream_context_set_option( $context, 'ssl', 'local_cert', $this->_certPath );
			if ( !empty( $certPass ) )
				stream_context_set_option( $context, 'ssl', 'passphrase', $this->_certPass );

			$fCon = stream_socket_client(
				"ssl://{$this->_serverAddr}:2195",
				$error, $errorStr,
				$this->_conTimeout,
				STREAM_CLIENT_CONNECT,
				$context
			);

			if ( $fCon === FALSE ) {
				$this->_connection = NULL;

				return array(
					'error' => $error,
					'msg'   => $errorStr
				);
			}

			$this->_connection = $fCon;
			stream_set_blocking( $this->_connection, 0 );

			return $this;
		}
		public function disconnect()
		{
			if ( !empty( $this->_connection ) )
				fclose( $this->_connection );

			$this->_connection = NULL;
		}
		public function reconnect()
		{
			if ( !$this->isConnected )
				return;

			$this->disconnect();
			$this->connect( $this->_serverAddr, $this->_certPath, $this->_certPass, $this->_conTimeout );
		}

		public function send( $msgContent ) {
			if ( !$this->isConnected )
				return NULL;


			$microTime	= microtime( TRUE );
			$currTime	= $microTime | 0;
			$microTime	= (microtime( TRUE ) * 1000) | 0;
			
			
			$token			= $msgContent[ 'token' ];
			$background		= CAST( $msgContent[ 'background' ], 'int strict', 0 );
			$expire			= TO( $msgContent[ 'expire' ], 'int strict' );
			$priority		= TO( $msgContent[ 'priority' ], 'int strict' );
			$identifier		= $microTime % MONTH_SEC;
			


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


			if ( $response === TRUE )
			{
				return [
					'status'		=> TRUE,
					'identifier'	=> $identifier
				];
			}
			else
			{
				$this->reconnect();
			
				$response[ 'status' ] = FALSE;
				return $response;
			}
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
			return ( $response ) ? unpack( "Ccommand/Ccode/Nidentifier", $response ) : TRUE;
		}
	}
