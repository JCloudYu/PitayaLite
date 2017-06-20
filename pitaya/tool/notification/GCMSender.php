<?php
/**
 * 1024.QueueCounter - GCMSender.php
 * Created by JCloudYu on 2015/05/19 08:04
 */
	final class GCMSender extends PBObject
	{
		const GCM_SERVER_ADDRESS = "https://gcm-http.googleapis.com/gcm/send";

		// region [ Properties ]
		private $_apiKey = '';
		public function __set_apiKey( $value ) {
			$this->_apiKey = TO( $value, 'string' );
		}
		public function __get_apiKey() {
			return $this->_apiKey;
		}



		private $_receivers = array();
		public function __set_receivers( $value ) {
			if ( !is_array( $value ) ) return;
			$this->_receivers = $value;
		}
		public function __get_receivers( $value ) {
			return $this->_receivers;
		}



		private $_lastErrorCode = 0;
		private $_lastErrorMsg	= "";
		public function __get_lastErrorCode() { return $this->_lastErrorCode; }
		public function __get_lastErrorMsg()  { return $this->_lastErrorMsg;  }



		private $_testing = FALSE;
		public function __get_isTesting() { return $this->_testing; }
		public function __set_isTesting( $value ) { $this->_testing = ( $value === TRUE ); }
		// endregion



		public function send( $data )
		{
			if ( empty( $this->_apiKey ) )
			{
				$this->_raiseError( self::ERROR_NO_API_KEY );
				return self::ERROR_NO_API_KEY;
			}

			if ( !is_array( $data ) )
			{
				$this->_raiseError( self::ERROR_INVALID_DATA );
				return self::ERROR_INVALID_DATA;
			}


			$results		= array();
			$receiver	= $this->_receivers;
			while ( count( $receiver ) > 0 )
			{
				$partial = array_splice( $receiver, 0, 1000 );
				$results[] = array(
					'target' => $partial,
					'result' => GCMSender::DoSend( $this->_apiKey, $partial, $data, $this->_testing )
				);
			}

			return $results;
		}



		public static function DoSend( $apiKey, $receivers, $data = array(), $dryRun = FALSE )
		{
			// Verify api key
			if ( empty( $apiKey ) )
				throw new Exception( "Given api key is invalid!", self::ERROR_NO_API_KEY );


			// Verify receiver number
			if ( !is_array($receivers) )
				throw new Exception( "Parameter `receivers` must be an array", self::ERROR_INVALID_RECEIVER );

			$receiverNum = count( $receivers );
			if ( $receiverNum < 1 )
				throw new Exception( "There's no receiver!", self::ERROR_NO_RECEIVER );
			else
			if ( $receiverNum > 1000 )
				throw new Exception( "There're too many receivers!", self::ERROR_TOO_MANY_RECEIVERS );


			// Verify data format
			if ( !is_array( $data ) )
				throw new Exception( "Parameter `data` must be an array!", self::ERROR_INVALID_DATA );



			// INFO: Prepare payloads
			$payload = array();
			$payload[ 'registration_ids' ] = $receivers;
			$payload[ 'dry_run' ] = $dryRun;
			$payload[ 'data' ] = (object)(empty($data) ? array() : $data );

			$headers = array(
				'Authorization: key=' . "{$apiKey}",
				'Content-Type: application/json'
			);



			$hCurl = curl_init();
			curl_setopt( $hCurl, CURLOPT_RETURNTRANSFER,	TRUE );
			curl_setopt( $hCurl, CURLOPT_FRESH_CONNECT,		TRUE );
			curl_setopt( $hCurl, CURLOPT_POST,				TRUE );

			curl_setopt( $hCurl, CURLOPT_URL,				self::GCM_SERVER_ADDRESS );
			curl_setopt( $hCurl, CURLOPT_HTTPHEADER,		$headers );
			curl_setopt( $hCurl, CURLOPT_POSTFIELDS,		json_encode( $payload ) );
			$result = curl_exec( $hCurl );



			// Connection error
			if ( $result === FALSE )
			{
				$errNum = curl_error( $hCurl );
				curl_close( $hCurl );
				return $errNum;
			}



			// Generate results
			$httpStatus = curl_getinfo( $hCurl, CURLINFO_HTTP_CODE );
			$data = array(
				'status' => $httpStatus,
				'result' => ($httpStatus == 200) ? json_decode( $result, TRUE ) : $result
			);



			curl_close( $hCurl );
			return $data;
		}



		// region [ Error Handler ]
		const NO_ERROR					=  0;
		const ERROR_NO_API_KEY			= -1;
		const ERROR_INVALID_RECEIVER	= -2;
		const ERROR_NO_RECEIVER			= -3;
		const ERROR_TOO_MANY_RECEIVERS	= -4;
		const ERROR_INVALID_DATA		= -5;
		const ERROR_UNKNOWN				= -999;
		private function _raiseError( $errCode ){
			switch ( $errCode )
			{
				case self::ERROR_NO_API_KEY:
					$this->_lastErrorCode	= $errCode;
					$this->_lastErrorMsg	= "GCM sender key is not set!";
					break;

				case self::ERROR_NO_RECEIVER:
					$this->_lastErrorCode	= $errCode;
					$this->_lastErrorMsg	= "There's no receiver to send!";
					break;

				case self::ERROR_INVALID_DATA:
					$this->_lastErrorCode	= $errCode;
					$this->_lastErrorMsg	= "Given data has invalid format!";
					break;

				default:
					$this->_lastErrorCode	= self::ERROR_UNKNOWN;
					$this->_lastErrorMsg	= "Unknown error!";
					break;
			}
		}
		// endregion
	}
