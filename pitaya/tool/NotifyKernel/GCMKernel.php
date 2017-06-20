<?php
	using( 'tool.NotifyKernel.INotifyKernel' );
	
	class GCMKernel extends PBObject implements INotifyKernel
	{
		const GCM_HTTP_SERVER_ADDRESS	 = "https://gcm-http.googleapis.com/gcm/send";
		const GCM_ANDROID_SERVER_ADDRESS = "https://android.googleapis.com/gcm/send";

		private $_serverAddr = "";
		private $_serverKey	 = "";
		private $_conTimeout = 100;


		public function __construct( $serverAddr, $serverKey, $timeout = 100 ) {
			$this->_serverAddr	= $serverAddr;
			$this->_serverKey	= $serverKey;
			$this->_conTimeout	= $timeout;
		}

		public function send( $msgContent )
		{
			$microTime	= microtime( TRUE );
			$currTime	= $microTime | 0;
			$microTime	= (microtime( TRUE ) * 1000) | 0;
		
			$token		= $msgContent[ 'token' ];
			$background	= CAST( $msgContent[ 'background' ], 'int strict', 0 );
			$identifier	= $microTime % MONTH_SEC;
			
			
			
			$payload = [
				'to' => $token,
				'content_available' => !!$background
			];
			
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



			$hCurl = curl_init();
			curl_setopt( $hCurl, CURLOPT_URL, $this->_serverAddr );
			curl_setopt( $hCurl, CURLOPT_POST, TRUE );
			curl_setopt( $hCurl, CURLOPT_FRESH_CONNECT, TRUE );
			curl_setopt( $hCurl, CURLOPT_HTTPHEADER, array (
				'Authorization: key=' . $this->_serverKey,
				'Content-Type: application/json'
			));
			curl_setopt( $hCurl, CURLOPT_RETURNTRANSFER, TRUE );
			curl_setopt( $hCurl, CURLOPT_SSL_VERIFYPEER, FALSE );
			curl_setopt( $hCurl, CURLOPT_POSTFIELDS, json_encode( $payload ) );
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



			return [
				'status'	=> ($code == 200) && (is_array($response)) && ($response[ 'success' ] > 0),
				'code'		=> $code,
				'error'		=> $error,
				'response'	=> $response
			];
		}
	}
