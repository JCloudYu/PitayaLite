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
	final class PBRSA extends PBObject {
	
		const DEFAULT_KEY_LENGTH = 2048;
		const DEFAULT_TAILING_SIZE = 11;
	
		public static function RSA( $idenity, $keyPath = NULL, $keyLen = PBRSA::DEFAULT_KEY_LENGTH ) {
		
			static $keyCache = [];
			if ( $keyCache[ $idenity ] ) return $keyCache[ 'identity' ];
			
			
			$keyInfo = PBRSA::ParseKey( $keyPath, $keyLen );
			return ( $keyInfo === NULL ) ? NULL : ( $keyCache[ $idenity ] = new PBRSA( $keyInfo ) );
		}
		private static function ParseKey( $keyPath = NULL, $keyLen = PBRSA::DEFAULT_KEY_LENGTH ) {
		
			$keyType = [];
			if ( $keyPath === NULL )
			{
				$keyInst = openssl_pkey_new([
					'private_key_bits' => $keyLen,
					'private_key_type' => OPENSSL_KEYTYPE_RSA
				]);
				
				$keyType = 'private';
			}
			else
			{
				if ( is_file($keyPath) )
				{
					if ( !is_readable($keyPath) ) return NULL;
					$keyContent = file_get_contents( $keyPath );
				}

			

				$keyInst = openssl_pkey_get_private( $keyContent );
				$keyType = 'private';
				if ( !$keyInst )
				{
					$keyInst = openssl_pkey_get_public( $keyContent );
					if ( !$keyContent ) return NULL;
					
					$keyType = 'public';
				}
			}
			
			return [ 'handle' => $keyInst, 'type' => $keyType ];
		}



		private $_hKey		= NULL;
		private $_keyLen	= self::DEFAULT_KEY_LENGTH;
		private $_keyType	= NULL;
		private $_chunkSize	= 0;
		private $_keyData	= NULL;

		private function __construct( $RSAInfo ) {
		
			$keyInfo = openssl_pkey_get_details( $RSAInfo[ 'handle' ] );
			
			$this->_hKey		= $RSAInfo[ 'handle' ];
			$this->_keyLen		= $keyInfo[ 'bits' ];
			$this->_keyType		= $RSAInfo[ 'type' ];
			$this->_chunkSize	= ($keyInfo[ 'bits' ] / 8.0) | 0;

		}
		public function __get_publicKey() {
			static $keyCache = NULL;
			if ( $keyCache === NULL )
				$keyCache = @openssl_pkey_get_details( $this->_hKey );
			
			return $keyCache['key'];
		}
		public function __get_privateKey() {
			static $keyCache = NULL;
			
			if ( $this->_keyType != "private" ) return NULL;
			
			
			if ( $keyCache === NULL )
				@openssl_pkey_export( $this->_hKey, $keyCache );
			
			return empty($keyCache) ? NULL : $keyCache;
		}
		public function __get_keyInfo() {
			static $keyCache = NULL;
			if ( $keyCache === NULL )
			{
				$keyCache = @openssl_pkey_get_details( $this->_hKey );
				$keyCache[ 'key' ] = [ 'public' => $keyCache[ 'key' ] ];
				$keyCache[ 'key' ][ 'private' ] = $this->__get_privateKey();
			}
		
			return $keyCache;
		}
		public function __get_is_private() { return $this->_keyType === "private"; }
		public function __get_bits() { return $this->_keyLen; }
		public function __get_type() {
			static $typeCache = NULL;
			
			if ( $typeCache === NULL )
			{
				$info = $this->keyInfo;
				switch( $info['type'] )
				{
					case OPENSSL_KEYTYPE_RSA:
						$typeCache = 'rsa'; break;
					case OPENSSL_KEYTYPE_DSA:
						$typeCache = 'dsa'; break;
					case OPENSSL_KEYTYPE_DH:
						$typeCache = 'dh'; break;
					case OPENSSL_KEYTYPE_EC:
						$typeCache = 'ec'; break;
					default:
						$typeCache = 'unknown'; break;
				}
			}
			
			return $typeCache;
		}
		
		
		
		
		/*
		public function encrypt($data)
		{
			$chunks = str_split($data, $this->_chunkSize - self::DEFAULT_TAILING_SIZE);

			$result = '';
			foreach ($chunks as $chunk)
			{
				if (openssl_public_encrypt($chunk, $encrypted, $this->_hKeyAlt))
					$result .= base64_encode($encrypted);
				else
					return '';
			}

			return $result;
		}

		public function decrypt($data)
		{
			if ($this->_keyType == 'public') return '';

			$result = '';
			$chunks = str_split($data, intval(ceil(floatval($this->_chunkSize) / 3.0) * 4));

			foreach ($chunks as $chunk)
			{
				if (openssl_private_decrypt(base64_decode($chunk), $decrypted, $this->_hKey))
					$result .= $decrypted;
				else
					return '';
			}

			return $result;
		}
		*/
	}
