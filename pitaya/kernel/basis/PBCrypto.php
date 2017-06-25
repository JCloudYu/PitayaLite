<?php
 	using( 'kernel.basis.PBObject' );
 
	final class PBCrypto {
		const CANDIDATES_LOWER_NO_SYM = "0123456789abcdefghijklmnopqrstuvwxyz";
		const CANDIDATES_MIXED_NO_SYM = "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
		const CANDIDATES_SYMBOLS	  = " !\"$%^&*()-_=+[{]};:'@#~|,<.>/?\\/`";

		public static function GenPass( $length, $caseSensitive = TRUE, $withSymbol = TRUE )
		{
			$candidate = ($caseSensitive) ? self::CANDIDATES_MIXED_NO_SYM : self::CANDIDATES_LOWER_NO_SYM;
			$candidate = str_split( ($withSymbol) ? $candidate . self::CANDIDATES_SYMBOLS : $candidate );
			shuffle($candidate);

			do
			{
				$pass = '';
				while ( strlen($pass) != $length )
				{
					$pass .= $candidate[array_rand($candidate)];
					$pass = trim($pass);
				}
			}
			while( !self::ValidatePass($pass, $caseSensitive, $withSymbol) );

			return $pass;
		}
		public static function GenTOTP( $raw_secret, $refTime = NULL, $length = 6, $alg = 'sha1', $timeQuantum = 30 ) {
			$refTime = ( $refTime === NULL ) ? time() : $refTime;
			return self::GenHOTP( $raw_secret, (($refTime * 1000) / ($timeQuantum * 1000)) | 0, $length, $alg );
		}
		public static function GenHOTP( $raw_secret, $counter, $length = 6, $alg = 'sha1' ) {
			if ( PHP_VERSION_ID >= 50603 )
				$counter = pack( 'J', $counter );
			else
				$counter = str_pad( pack( 'N', $counter ), 8, chr(0), STR_PAD_LEFT );

			$hash = hash_hmac( $alg, $counter, $raw_secret, TRUE );
			$hasLen = strlen( $hash );

			$offset = ord($hash[ $hasLen - 1 ]) & 0xf;
			$otp = (
				((ord($hash[ $offset + 0 ]) & 0x7f) << 24 ) |
				((ord($hash[ $offset + 1 ]) & 0xff) << 16 ) |
				((ord($hash[ $offset + 2 ]) & 0xff) <<  8 ) |
				 (ord($hash[ $offset + 3 ]) & 0xff)
			) % pow( 10, $length );

			return str_pad( $otp, $length, "0", STR_PAD_LEFT );
		}
		
		public static function ValidatePass($target, $caseSensitive = TRUE, $checkSymbol = TRUE)
		{
			$target	  = str_split($target);
			$checkRes = array('upper' => FALSE, 'lower' => FALSE, 'digit' => FALSE, 'symbol' => FALSE);

			foreach ( $target as $data )
			{
				$val = ord($data);

				if ( $val >= 65 && $val <= 90 ) // A-Z
					$checkRes['upper'] = $checkRes['upper'] || TRUE;
				else
				if ( $val >= 97 && $val <= 122 ) // a-z
					$checkRes['lower'] = $checkRes['lower'] || TRUE;
				else
				if ( $val >= 48 && $val <= 57 ) // 0-9
					$checkRes['digit'] = $checkRes['digit'] || TRUE;
				else
					$checkRes['symbol'] = $checkRes['symbol'] || TRUE;
			}

			$result = $checkRes['lower'];
			$result = $result && $checkRes['digit'];

			if ( $caseSensitive )	$result = $result && $checkRes['upper'];
			if ( $checkSymbol ) $result = $result && $checkRes['symbol'];

			return $result;
		}
	}
	final class PBJWT {
		const SUPPORTED_ALG = [
			'NONE',
			'HS256', 'HS384', 'HS512',
			'RS256', 'RS384', 'RS512',
			'RAW_RS256', 'RAW_RS384', 'RAW_RS512'
		];
	
		const ALG_NONE	= 'NONE';
		const ALG_HS256 = 'HS256';
		const ALG_HS384 = 'HS384';
		const ALG_HS512 = 'HS512';
		const ALG_RS256 = 'RS256';
		const ALG_RS384 = 'RS384';
		const ALG_RS512 = 'RS512';
		
		// INFO: Non-standard signing algorithm
		const ALG_RAW_RS256 = 'RAW_RS256';
		const ALG_RAW_RS384 = 'RAW_RS384';
		const ALG_RAW_RS512 = 'RAW_RS512';
		
		/**
		 * @param stdClass $payload The payload of the message
		 * @param string $alg Encryption algorithm
		 * @param string|PBRSA $secret The secret used to generate the signature
		 * @return string|bool False on error
		 */
		public static function Encode( stdClass $payload, $alg = 'NONE', $secret = '' ) {
			$payload = PBBase64::URLEncode(json_encode($payload));
			
			switch(strtoupper($alg)) {
				case "HS256":
					$header	 = PBBase64::URLEncode(json_encode([ 'alg' => 'HS256', 'typ' => 'JWT' ]));
					$leading = "{$header}.{$payload}";
					$sig = PBBase64::URLEncode(hash_hmac('sha256', $leading, $secret, TRUE));
					break;
					
				case "HS384":
					$header	 = PBBase64::URLEncode(json_encode([ 'alg' => 'HS384', 'typ' => 'JWT' ]));
					$leading = "{$header}.{$payload}";
					$sig = PBBase64::URLEncode(hash_hmac('sha384', $leading, $secret, TRUE));
					break;
				
				case "HS512":
					$header	 = PBBase64::URLEncode(json_encode([ 'alg' => 'HS512', 'typ' => 'JWT' ]));
					$leading = "{$header}.{$payload}";
					$sig = PBBase64::URLEncode(hash_hmac('sha512', $leading, $secret, TRUE));
					break;
					
				case "RS256":
					$header	 = PBBase64::URLEncode(json_encode([ 'alg' => 'RS256', 'typ' => 'JWT' ]));
					$leading = "{$header}.{$payload}";
					if ( !is_a($secret, PBRSA::class) && !$secret->isPrivate ) {
						return FALSE;
					}
					
					$sig = PBBase64::URLEncode($secret->sign($leading, OPENSSL_ALGO_SHA256));
					break;
					
				case "RS384":
					$header	 = PBBase64::URLEncode(json_encode([ 'alg' => 'RS384', 'typ' => 'JWT' ]));
					$leading = "{$header}.{$payload}";
					if ( !is_a($secret, PBRSA::class) && !$secret->isPrivate ) {
						return FALSE;
					}
					
					$sig = PBBase64::URLEncode($secret->sign($leading, OPENSSL_ALGO_SHA384));
					break;
					
				case "RS512":
					$header	 = PBBase64::URLEncode(json_encode([ 'alg' => 'RS512', 'typ' => 'JWT' ]));
					$leading = "{$header}.{$payload}";
					if ( !is_a($secret, PBRSA::class) && !$secret->isPrivate ) {
						return FALSE;
					}
					
					$sig = PBBase64::URLEncode($secret->sign($leading, OPENSSL_ALGO_SHA512));
					break;
					
				case "RAW_RS256":
					$header	 = PBBase64::URLEncode(json_encode([ 'alg' => 'RAW_RS256', 'typ' => 'JWT' ]));
					$leading = "{$header}.{$payload}";
					if ( !is_a($secret, PBRSA::class) ) {
						return FALSE;
					}
					
					$rawSig = sha256($leading, TRUE);
					$sig = PBBase64::URLEncode($secret->encrypt($rawSig));
					break;
					
				case "RAW_RS384":
					$header	 = PBBase64::URLEncode(json_encode([ 'alg' => 'RAW_RS384', 'typ' => 'JWT' ]));
					$leading = "{$header}.{$payload}";
					if ( !is_a($secret, PBRSA::class) ) {
						return FALSE;
					}
					
					$rawSig = sha384($leading, TRUE);
					$sig = PBBase64::URLEncode($secret->encrypt($rawSig));
					break;
					
				case "RAW_RS512":
					$header	 = PBBase64::URLEncode(json_encode([ 'alg' => 'RAW_RS512', 'typ' => 'JWT' ]));
					$leading = "{$header}.{$payload}";
					if ( !is_a($secret, PBRSA::class) ) {
						return FALSE;
					}
					
					$rawSig = sha512($leading, TRUE);
					$sig = PBBase64::URLEncode($secret->encrypt($rawSig));
					break;
				
				case "NONE":
					$header	 = PBBase64::URLEncode(json_encode([ 'alg' => 'none' ]));
					$leading = "{$header}.{$payload}";
					$sig = "";
					break;
				
				default:
					return FALSE;
			}
			
			return "{$leading}.{$sig}";
		}
		
		/**
		 * @param string $jwtToken Encoded JWT token
		 * @param string|PBRSA $secret The secret to verify the token
		 * @return null|object|stdClass
		 */
		public static function Decode( $jwtToken, $secret = '' ) {
			$jwtToken = explode( '.', "{$jwtToken}" );
			if ( count($jwtToken) < 2 ) return NULL;
			
			@list( $encHeader, $encPayload, $sig ) = $jwtToken;
			$header = @json_decode(PBBase64::URLDecode($encHeader));
			$payload = @json_decode(PBBase64::URLDecode($encPayload));

			if ( empty($header) || empty($payload) ) {
				return NULL;
			}



			$argCount = func_num_args();
			$msgBody  = "{$encHeader}.{$encPayload}";
			if ( $argCount < 2 ) {
				$verified = FALSE;
			}
			else {
				$verified = self::Verify(strtoupper(@$header->alg), $msgBody, $sig, $secret);
			}
			
			return stdClass([
				'header'	=> $header,
				'payload'	=> $payload,
				'body'		=> $msgBody,
				'signature'	=> $sig,
				'verified'	=> $verified,
			]);
		}
		
		/**
		 * @param string $alg The algorithm to verify the body
		 * @param string $body The msg body of the signature
		 * @param string $sig The msg body's signature
		 * @param string|PBRSA $secret The secret used to verify the signature
		 * @return bool
		 */
		public static function Verify($alg, $body, $sig, $secret) {
			switch(strtoupper($alg)) {
				case "NONE":
					return TRUE;
					
				case "HS256":
					$verify = PBBase64::URLEncode(hash_hmac('sha256', $body, $secret, TRUE));
					return $verify == $sig;
					
				case "HS384":
					$verify = PBBase64::URLEncode(hash_hmac('sha384', $body, $secret, TRUE));
					return ($verify == $sig);
				
				case "HS512":
					$verify = PBBase64::URLEncode(hash_hmac('sha512', $body, $secret, TRUE));
					return ($verify == $sig);
				
				case "RS256":
					if ( !is_a($secret, PBRSA::class) ) {
						return FALSE;
					}
					else {
						$verify = PBBase64::URLDecode($sig);
						return !!$secret->validate($body, $verify, OPENSSL_ALGO_SHA256);
					}
				
				case "RS384":
					if ( !is_a($secret, PBRSA::class) ) {
						return FALSE;
					}
					else {
						$verify = PBBase64::URLDecode($sig);
						return !!$secret->validate($body, $verify, OPENSSL_ALGO_SHA384);
					}
				
				case "RS512":
					if ( !is_a($secret, PBRSA::class) ) {
						return FALSE;
					}
					else {
						$verify = PBBase64::URLDecode($sig);
						return !!$secret->validate($body, $verify, OPENSSL_ALGO_SHA512);
					}
				
				case "RAW_RS256":
					if ( !is_a($secret, PBRSA::class) ) {
						return FALSE;
					}
					else {
						$digest = sha256( $body, TRUE );
						$verify = $secret->decrypt(PBBase64::URLDecode($sig));
						return ($digest == $verify);
					}
				
				case "RAW_RS384":
					if ( !is_a($secret, PBRSA::class) ) {
						return FALSE;
					}
					else {
						$digest = sha384( $body, TRUE );
						$verify = $secret->decrypt(PBBase64::URLDecode($sig));
						return ($digest == $verify);
					}
				
				case "RAW_RS512":
					if ( !is_a($secret, PBRSA::class) ) {
						return FALSE;
					}
					else {
						$digest = sha512( $body, TRUE );
						$verify = $secret->decrypt(PBBase64::URLDecode($sig));
						return ($digest == $verify);
					}
			}
			
			return FALSE;
		}
	}
	final class PBBase64 {
		public static function URLEncode( $data ){
			return strtr(rtrim(base64_encode( $data ), '='), '+/', '-_');
		}
		public static function URLDecode( $data ){
			$length = strlen( $data );
			$repeat = 4 - ($length % 4);
			return base64_decode( strtr( $data . str_repeat( "=", $repeat ), '-_', '+/'), TRUE );
		}

		public static function Encode( $data ){
			return base64_encode($data);
		}
		public static function Decode( $data ) {
			return base64_decode( $data, TRUE );
		}
	}
	
	/**
	 * @property-read PBRSA $publicRSAKey
	 * @property-read bool $isPrivate
	 * @property-read string|bool $privateKey
	 * @property-read string $publicKey
	 * @property-read int $size
	 * @property-read int $type
	 */
	final class PBRSA {
		/**
		 * @param string $keySrc The key content or the key path
		 * @param string $passPhrase The password that encrypts the key
		 * @return PBRSA|null
		 */
		public static function LoadRSA($keySrc, $passPhrase='') {
			$args = func_get_args();
			return self::LoadRSAFile(...$args) ?: self::LoadRSAString(...$args);
		}
		
		/**
		 * @param string $path Path to the key file
		 * @param string $passPhrase Password that is used to encrypt the key file
		 * @return PBRSA|null
		 */
		public static function LoadRSAFile($path, $passPhrase='') {
			if ( !is_file($path) || !is_readable($path) ) {
				return NULL;
			}
			
			$args = func_get_args();
			$args[0] = file_get_contents($path);
			return self::LoadRSAString(...$args);
		}
		
		/**
		 * @param mixed $pack The packed content of the key
		 * @param string $passPhrase The pass phrase that encodes the packed content
		 * @return PBRSA|null
		 */
		public static function LoadRSAString($pack, $passPhrase='') {
			$args = [$pack];
			if ( !empty($passPhrase) ) {
				$args[] = $passPhrase;
			}
			
			$hKey = @openssl_pkey_get_private(...$args);
			if ( $hKey !== FALSE ) {
				return new PBRSA($hKey, TRUE);
			}
			
			$hKey = @openssl_pkey_get_public(...$args);
			if ($hKey !== FALSE) {
				return new PBRSA($hKey, FALSE);
			}
			
			return NULL;
		}






		// region [ Main PBRSA Content ]
		private $_hKey		= NULL;
		private $_isPriv	= FALSE;
		private $_keyDetail	= NULL;
	
		private $_pubCache	= NULL;



		private function __construct($hKey, $isPrivate=TRUE) {
			$this->_hKey		= $hKey;
			$this->_isPriv		= $isPrivate;
			$this->_keyDetail	= openssl_pkey_get_details($hKey);
		}
		public function __get($name) {
			switch($name) {
				case "isPrivate":
					return $this->_isPriv;
				
				case "publicRSAKey":
					if ( !$this->_isPriv ) {
						return $this;
					}
					
					if ( $this->_pubCache ) {
						return $this->_pubCache;
					}
					
					$hKey = openssl_pkey_get_public($this->_keyDetail['key']);
					return ($this->_pubCache = new PBRSA($hKey, FALSE));
				
				case "publicKey":
					return $this->_keyDetail['key'];
					
				case "privateKey":
					return $this->exportPrivateKey();
				
				case "size":
					return $this->_keyDetail['bits'];
				
				case "type":
					return $this->_keyDetail['type'];
				
				default:
					throw new Exception("Property `{$name}` is not defined");
			}
		}
		public function exportPrivateKey($passPhrase='', $cipher=OPENSSL_CIPHER_AES_256_CBC) {
			if ( !$this->_isPriv ) {
				return FALSE;
			}
			

			$status = openssl_pkey_export(
				$this->_hKey,
				$output,
				empty($passPhrase) ? NULL : $passPhrase,
				[ 'encrypt_key_cipher' => $cipher ]
			);
			if ( !$status ) {
				return FALSE;
			}
			
			return $output;
		}
		public function encrypt($data, $padding=OPENSSL_PKCS1_PADDING) {
			$output = NULL;
			if ( $this->_isPriv ) {
				$status = openssl_private_encrypt($data, $output, $this->_hKey, $padding);
			}
			else {
				$status = openssl_public_encrypt($data, $output, $this->_hKey, $padding);
			}
			
			return $status ? $output : FALSE;
		}
		public function decrypt($data, $padding=OPENSSL_PKCS1_PADDING) {
			$output = NULL;
			if ( $this->_isPriv ) {
				$status = openssl_private_decrypt($data, $output, $this->_hKey, $padding);
			}
			else {
				$status = openssl_public_decrypt($data, $output, $this->_hKey, $padding);
			}
			
			return $status ? $output : FALSE;
		}
		public function sign($data, $alg=OPENSSL_ALGO_SHA256) {
			if ( !$this->_isPriv ) return FALSE;
			
			$status = openssl_sign($data, $output, $this->_hKey, $alg);
			return $status ? $output : FALSE;
		}
		public function validate($data, $signature, $alg=OPENSSL_ALGO_SHA256) {
			if ( $this->_isPriv ) {
				$args = func_get_args();
				return $this->publicRSAKey->validate(...$args);
			}
			
			return openssl_verify($data, $signature, $this->_hKey, $alg);
		}
		// endregion
	}

