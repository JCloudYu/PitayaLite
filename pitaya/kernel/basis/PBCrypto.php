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
		const ALG_NONE	= 'NONE';
		const ALG_HS256 = 'HS256';
	
		public static function Encode( stdClass $payload, $alg = 'NONE', $secret = '' ) {
			$payload = PBBase64::URLEncode(json_encode($payload));
			
			if ( !in_array( $alg = strtoupper($alg), [ 'NONE', 'HS256' ] ) ) {
				$alg = 'NONE';
			}
			
			if ( $alg == "NONE" ) {
				$header	 = PBBase64::URLEncode(json_encode([ 'alg' => 'none' ]));
				$leading = "{$header}.{$payload}";
				$sig = "";
			}
			else
			if ( $alg == "HS256" ) {
				$header	 = PBBase64::URLEncode(json_encode([ 'alg' => 'HS256', 'typ' => 'JWT' ]));
				$leading = "{$header}.{$payload}";
				$sig = PBBase64::URLEncode(hash_hmac('sha256', $leading, $secret, TRUE));
			}
			
			return "{$leading}.{$sig}";
		}
		public static function Decode( $jwtToken, $secret = '' ) {
			$jwtToken = explode( '.', "{$jwtToken}" );
			if ( count($jwtToken) != 3 ) return NULL;
			
			list( $encHeader, $encPayload, $sig ) = $jwtToken;
			$header = @json_decode(PBBase64::URLDecode($encHeader));
			$payload = @json_decode(PBBase64::URLDecode($encPayload));

			if ( empty($header) || empty($payload) ) return NULL;
			if ( @$header->alg === "none" ) {
				return stdClass([
					'header'	=> $header,
					'payload'	=> $payload,
					'verified'	=> TRUE
				]);
			}
			else
			if ( @$header->alg === "HS256" ) {
				$verified = ( func_num_args() < 2 ) ? FALSE : ($sig === PBBase64::URLEncode(hash_hmac('sha256', "{$encHeader}.{$encPayload}", $secret, TRUE)));
				return stdClass([
					'header'	=> $header,
					'payload'	=> $payload,
					'verified'	=> $verified
				]);
			}
			
			return NULL;
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
		 * @param mixed $pack The packed content of the key
		 * @param string $passPhrase The pass phrase that encodes the packed content
		 * @return PBRSA|null
		 */
		public static function LoadRSA($pack, $passPhrase='') {
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
					$hKey = openssl_pkey_get_public($this->_keyDetail['key']);
					return new PBRSA($hKey, FALSE);
				
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
		// endregion
	}

