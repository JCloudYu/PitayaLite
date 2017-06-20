<?php
	using( 'tool.PBTaskWorker.PBTaskKernel' );
	

	class PBMailKernel extends PBTaskKernel
	{
		const ERROR_INCORRECT_GIVEN_MSG	= -1;
		const ERROR_CONNECTION			= -2;
		const ERROR_FETCHING_RESPONSE	= -3;
		const ERROR_UNEXPECTED_RESPONSE	= -4;


		// region [ Properties ]
		protected $_account = "";
		public function __get_account() {
			return $this->_account;
		}
		public function __set_account( $value ) {
			$this->_account = "{$value}";
		}

		protected $_password = "";
		public function __get_password() {
			return $this->_password;
		}
		public function __set_password( $value ) {
			$this->_password = "{$value}";
		}

		protected $_fromName = "";
		public function __get_fromName() {
			return $this->_fromName;
		}
		public function __set_fromName( $value ) {
			$this->_fromName = "{$value}";
		}

		protected $_fromAddr = "";
		public function __get_fromAddr() {
			return $this->_fromAddr;
		}
		public function __set_fromAddr( $value ) {
			$this->_fromAddr = "{$value}";
		}

		protected $_relayAddr = "";
		public function __get_relayAddr() {
			return $this->_relayAddr;
		}
		public function __set_relayAddr( $value ) {
			$this->_relayAddr = "{$value}";
		}

		protected $_protocol = "";
		public function __get_protocol() {
			return $this->_protocol;
		}
		public function __set_protocol( $value ) {
			$this->_protocol = "{$value}";
		}

		protected $_relayPort = 25;
		public function __get_relayPort() {
			return $this->_relayPort;
		}
		public function __set_relayPort( $value ) {
			return $this->_relayPort = CAST( $value, 'int strict', 25 );
		}

		protected $_timeout = 5;
		public function __get_conTimeout() {
			return $this->_timeout;
		}
		public function __set_conTimeout( $value ) {
			return $this->_relayPort = CAST( $value, 'int strict', 5 );
		}

		protected $_debugOutput = FALSE;
		public function __get_debugOutput() {
			return $this->_debugOutput;
		}
		public function __set_debugOutput( $value ) {
			$this->_debugOutput = !empty($value);
		}
		// endregion






		protected $_conSocket = NULL;
		protected function _connect()
		{
			if ( !($this->_conSocket = @fsockopen("{$this->_protocol}{$this->_relayAddr}", $this->_relayPort, $errno, $errstr, $this->_timeout)) )
			{
				throw new PBException( array(
					'status' => PBMailKernel::ERROR_CONNECTION,
					'msg'	 => "Error connecting to \"{$this->_protocol}{$this->_relayAddr}\" ($errno) ($errstr)"
				));
			}
		}
		protected function _disconnect()
		{
			if ( $this->_conSocket )
				@fclose( $this->_conSocket );

			$this->_conSocket = NULL;
		}

		protected static function EatResponse( $socket, $expectedCode, $debugOutput = FALSE )
		{
			$lastResponse = '';
			while ( substr( $lastResponse, 3, 1 ) != ' ' )
			{
				if ( !( $lastResponse = fgets( $socket, 256 ) ) )
				{
					throw new PBException(array(
						'status'	=> PBMailKernel::ERROR_FETCHING_RESPONSE,
						'response'	=> $response
					));
				}
			}

			if ( $debugOutput ) echo trim($lastResponse) . LF;

			$statusCode = CAST( @substr( $lastResponse, 0, 3 ), 'int', 0 );
			if ( ($statusCode != $expectedCode) )
			{
				throw new PBException(array(
					'status'	=> PBMailKernel::ERROR_UNEXPECTED_RESPONSE,
					'code'		=> $statusCode,
					'expected'	=> $expectedCode,
					'response'	=> trim(@substr( $lastResponse, 3 ))
				));
			}
		}
		protected static function WriteContent( $socket, $content, $debugOutput = FALSE ) {
			if ( $debugOutput ) echo trim($content) . LF;

			fwrite( $socket, "{$content}\r\n" );
		}
	}

	class PBSMTPKernel extends PBMailKernel
	{
		public function link() {
			// region [ Start of SMTP Session ]
			try
			{
				$this->_connect();
				self::EatResponse( $this->_conSocket, 220, $this->_debugOutput );



				if ( empty($this->_account) && empty($this->_password) )
				{
					// INFO: Initiate Normal SMTP mode
					self::WriteContent( $this->_conSocket, "HELO {$this->_relayAddr}", $this->_debugOutput );
					self::EatResponse( $this->_conSocket, 250, $this->_debugOutput );
				}
				else
				{
					// INFO: Initiate ESMTP mode
					self::WriteContent( $this->_conSocket, "EHLO {$this->_relayAddr}", $this->_debugOutput );
					self::EatResponse( $this->_conSocket, 250, $this->_debugOutput );



					// INFO: AUTH Login Authentication protocol
					self::WriteContent( $this->_conSocket, "AUTH LOGIN", $this->_debugOutput );
					self::EatResponse( $this->_conSocket, 334, $this->_debugOutput );

					self::WriteContent( $this->_conSocket, base64_encode( $this->_account ), $this->_debugOutput );
					self::EatResponse( $this->_conSocket, 334, $this->_debugOutput );

					self::WriteContent( $this->_conSocket, base64_encode( $this->_password ), $this->_debugOutput );
					self::EatResponse( $this->_conSocket, 235, $this->_debugOutput );
				}

				return TRUE;
			}
			catch( PBException $e )
			{
				$descriptor = $e->descriptor;
				if ( $descriptor['status'] != PBMailKernel::ERROR_CONNECTION )
					$this->_disconnect();

				return $descriptor;
			}
			// endregion
		}
		public function unlink() {
			// region [ End of SMTP Session ]
			try
			{
				if ( $this->_conSocket )
				{
					self::WriteContent( $this->_conSocket, 'QUIT', $this->_debugOutput );
					$this->_disconnect();
				}

				return TRUE;
			}
			catch( PBException $e )
			{
				$descriptor = $e->descriptor;
				if ( $descriptor['status'] != PBMailKernel::ERROR_CONNECTION )
					$this->_disconnect();

				return $descriptor;
			}
			// endregion
		}

		public function process( $msg = NULL )
		{
			try
			{
				if ( empty($this->_conSocket) )
				{
					throw new PBException(array(
						'status' => PBMailKernel::ERROR_CONNECTION,
						'msg'	 => "Connection to remote server has not been established!"
					));
				}

				if ( !is_array( $msg ) )
				{
					throw new PBException(array(
						'status' => PBMailKernel::ERROR_INCORRECT_GIVEN_MSG,
						'msg'	 => "Given message is invalid"
					));
				}

				$senderInfo = array( "addr" => $this->_fromAddr, "name" => $this->_fromName );
				self::sendMail( $this->_conSocket, $senderInfo, $msg, $this->_debugOutput );

				return TRUE;
			}
			catch( PBException $e )
			{
				if ( $this->_conSocket )
				{
					self::WriteContent( $this->_conSocket, 'RSET', $this->_debugOutput );
					self::EatResponse( $this->_conSocket, 250, $debugOutput );
				}

				return $e->descriptor;
			}

		}

		public static function sendMail( $socket, $from, $msg, $debugOutput = FALSE )
		{
			// INFO: Parse and collect receipient information
			$recipients	= array();
			$parsed = ary_filter( array( 'to', 'cc', 'bcc' ), function( $field, &$idx ) use( &$msg, &$recipients ) {
				$fieldData = @$msg[ $idx = $field ];
				if ( empty($fieldData) ) return array();



				return ary_filter(
					is_array($fieldData) ? $fieldData : array($fieldData),
					function( $email ) use( &$recipients ) {
						$result = $addr = "<$email>";


						if ( is_array($email) )
						{
							$addr	= "<{$email['email']}>";
							$result = "\"{$email['name']}\" {$addr}";
						}

						$recipients[] = $addr;
						return $result;
					}
				);
			});



			$subject	= "{$msg['subject']}";
			$content	= "{$msg['content']}";
			$to			= $parsed[ 'to' ];
			$cc			= $parsed[ 'cc' ];



			if ( !is_array($from) )
			{
				$senderAddr = "{$from}";
				$senderName = "";
			}
			else
			{
				$senderAddr	= "{$from['addr']}";
				$senderName = trim("{$from['name']}");
			}

			$senderInfo = (empty( $senderName ) ? "" : "\"{$senderName}\" " ) . "<{$senderAddr}>";



			// INFO: Write envelope info
			self::WriteContent( $socket, "MAIL FROM: <{$senderAddr}>", $debugOutput );
			self::EatResponse( $socket, 250, $debugOutput );

			foreach ( $recipients as $email ) {
				self::WriteContent( $socket, "RCPT TO: {$email}", $debugOutput );
				self::EatResponse( $socket, 250, $debugOutput );
			}





			// region [ Start writing email ]
			self::WriteContent( $socket, "DATA", $debugOutput );
			self::EatResponse( $socket, 354, $debugOutput );



			// INFO: Headers
			self::WriteContent( $socket, "From: {$senderInfo}", $debugOutput );
			self::WriteContent( $socket, "To: " . implode( ', ', $to ), $debugOutput );
			if ( !empty($cc) ) self::WriteContent( $socket, "Cc: " . implode( ' ', $cc ), $debugOutput );
			self::WriteContent( $socket, "Date: " . date( "r" ), $debugOutput );
			self::WriteContent( $socket, "Subject: {$subject}", $debugOutput );



			// INFO: Body
			self::WriteContent( $socket, $content, $debugOutput );



			// INFO: Finishing
			self::WriteContent( $socket, '.', $debugOutput );
			self::EatResponse( $socket, 250, $debugOutput );
			// endregion
		}
	}

	class PBGMailSMTP extends PBSMTPKernel
	{
		const GMAIL_SMTP_RELAY_PROTOCOL	= "ssl://";
		const GMAIL_SMTP_RELAY_ADDR		= "smtp.gmail.com";
		const GMAIL_SMTP_RELAY_PORT		= 465;

		public function __construct( $account, $password )
		{
			$this->account		= $account;
			$this->password		= $password;
			$this->_relayAddr	= self::GMAIL_SMTP_RELAY_ADDR;
			$this->_relayPort	= self::GMAIL_SMTP_RELAY_PORT;
			$this->_protocol	= self::GMAIL_SMTP_RELAY_PROTOCOL;
		}
	}
