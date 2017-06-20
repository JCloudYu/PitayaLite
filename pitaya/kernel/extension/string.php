<?php
	// INFO: Data Structure Conversion
	function xml2ary( $xmlString )
	{
		/** @var $ImprintFunc Callable */
		static $ImprintFunc = NULL;
		if ( $ImprintFunc === NULL ) {
			$ImprintFunc = function( SimpleXMLElement $imprint ) use ( &$ImprintFunc )
			{
				$attributes	 = array();
				$collectData = NULL;

				// INFO: Eat attributes
				foreach ( $imprint->attributes() as $name => $value ) $attributes[ $name ] = (string) $value;

				// INFO: Simple contents
				if ( $imprint->count() <= 0 )
					$returnVal = (string) $imprint;
				else
				{
					$returnVal = $split = array();
					foreach ( $imprint as $property => $content )
					{
						$result = $ImprintFunc( $content );

						if ( !isset($returnVal[ $property ]) )
							$returnVal[ $property ] = $result;
						else
						if ( in_array( $property, $split ) )
							$returnVal[ $property ][] = $result;
						else
						{
							$split[] = $property;
							$returnVal[ $property ] = array( $returnVal[ $property ], $result );
						}
					}
				}



				if ( !is_array( $returnVal ) )
					$returnVal = ( empty($attributes) ) ? $returnVal : array( $returnVal, '@type' => 'simple', '@attr' => $attributes );
				else
				if ( !empty($attributes) )
					$returnVal[ '@attr' ] = $attributes;


				return $returnVal;
			};
		}

		$newsContents = @simplexml_load_string( $xmlString );
		return ( empty($newsContents) ) ? NULL : $ImprintFunc( $newsContents );
	}

	// INFO: PHP Built-in Functions Extension
	function hex_encode($data){ return bin2hex($data); }
	function hex_decode($data){ return pack( "H*", $data ); }
	function ord_utf8($string, &$offset) {
		$code = ord(substr($string, $offset,1));
		if ($code >= 128) {        //otherwise 0xxxxxxx
			if ($code < 224) $bytesnumber = 2;                //110xxxxx
			else if ($code < 240) $bytesnumber = 3;        //1110xxxx
			else if ($code < 248) $bytesnumber = 4;    //11110xxx
			$codetemp = $code - 192 - ($bytesnumber > 2 ? 32 : 0) - ($bytesnumber > 3 ? 16 : 0);
			for ($i = 2; $i <= $bytesnumber; $i++) {
				$offset ++;
				$code2 = ord(substr($string, $offset, 1)) - 128;        //10xxxxxx
				$codetemp = $codetemp*64 + $code2;
			}
			$code = $codetemp;
		}
		$offset += 1;
		if ($offset >= strlen($string)) $offset = -1;
		return $code;
	}
	function chr_utf8($u) {
		return mb_convert_encoding('&#' . intval($u) . ';', 'UTF-8', 'HTML-ENTITIES');
	}
	function ext_strtr($pattern, $replacements, $glue = FALSE, $mapper = NULL)
	{
		// INFO: Fail safe
		if ( !is_array($replacements) ) return '';
		$mapper	 = ( !is_callable($mapper) ) ? function($item){ return $item; } : $mapper;
		$pattern = "{$pattern}";
		$glue	 = ( $glue === TRUE ) ? '' : $glue;


		$firstElm = reset($replacements);
		if ( !empty($firstElm) && !is_array($firstElm) )
			return strtr( $pattern, $mapper($replacements) );
		else
		{
			$sepMode	= ( $glue === FALSE || $glue === NULL );
			$collector	= array();

			foreach ( $replacements as $key => $replace )
			{
				$result = strtr( $pattern, $mapper($replace, $key) );
				$collector[$key] = $result;
			}

			return ($sepMode) ? $collector : implode("{$glue}", $collector);
		}
	}
	function ext_trim($instance)
	{
		if (!is_array($instance))
			return trim($instance);
		else
		{
			$result = array();
			foreach ($instance as $key => $str)
				$result[$key] = trim($str);

			return $result;
		}
	}

	function base32_encode( $input )
	{
		$BASE32_ALPHABET = 'abcdefghijklmnopqrstuvwxyz234567';
		$output          = '';
		$v               = 0;
		$vbits           = 0;

		for ( $i = 0, $j = strlen( $input ); $i < $j; $i++ ) {
			$v <<= 8;
			$v += ord( $input[ $i ] );
			$vbits += 8;

			while ( $vbits >= 5 ) {
				$vbits -= 5;
				$output .= $BASE32_ALPHABET[ $v >> $vbits ];
				$v &= ( ( 1 << $vbits ) - 1 );
			}
		}

		if ( $vbits > 0 ) {
			$v <<= ( 5 - $vbits );
			$output .= $BASE32_ALPHABET[ $v ];
		}

		return $output;
	}
	function base32_decode( $input )
	{
		$input = strtolower($input);

		$output = '';
		$v      = 0;
		$vbits  = 0;

		for ( $i = 0, $j = strlen( $input ); $i < $j; $i++ ) {
			$v <<= 5;
			if ( $input[ $i ] >= 'a' && $input[ $i ] <= 'z' ) {
				$v += ( ord( $input[ $i ] ) - 97 );
			}
			elseif ( $input[ $i ] >= '2' && $input[ $i ] <= '7' ) {
				$v += ( 24 + $input[ $i ] );
			}
			else {
				exit( 1 );
			}

			$vbits += 5;
			while ( $vbits >= 8 ) {
				$vbits -= 8;
				$output .= chr( $v >> $vbits );
				$v &= ( ( 1 << $vbits ) - 1 );
			}
		}

		return $output;
	}

	// INFO: Extensions for UTF8 data processing
	function utf8_filter( $string, $func = NULL )
	{
		$len = strlen( $string );
		if ( $len <= 0 ) return NULL;


		$func = ( is_callable($func) ) ? $func : function( $codeVal, $bytes ) {
			return ( $codeVal < 0 ) ? '' : $bytes;
		};



		$index = 0; $collected = '';
		while( $index < $len )
		{
			$code = ord( $buff = $data = $string[ $index++ ] );
			$codeValue = 0;
			$hasError = FALSE;

			if ( ($code & 128) == 0 )		// 0xxxxxxx
			{
				$codeValue = $code;
				$numBytes = 0;
			}
			else
			if ( ($code >> 1) == 126 )	// 1111110x
			{
				$codeValue = $code & 0x01;
				$numBytes = 5;
			}
			else
			if ( ($code >> 2) == 62 )	// 111110xx
			{
				$codeValue = $code & 0x03;
				$numBytes = 4;
			}
			else
			if ( ($code >> 3) == 30 )	// 11110xxx
			{
				$codeValue = $code & 0x07;
				$numBytes = 3;
			}
			else
			if ( ($code >> 4) == 14 )	// 1110xxxx
			{
				$codeValue = $code & 0x0F;
				$numBytes = 2;
			}
			else
			if ( ($code >> 5) == 6 )	// 110xxxxx
			{
				$codeValue = $code & 0x1F;
				$numBytes = 1;
			}
			else
			{
				$hasError = $hasError || TRUE;
				$numBytes = 0;
			}



			while ( $numBytes-- > 0 )
			{
				$code = ord( $data = $string[ $index++ ] );
				$buff .= $data;


				if ( $code >> 6 != 2 )
				{
					$hasError = $hasError || TRUE;
					continue;
				}

				$code = $code & 0x3F;
				$codeValue = ($codeValue << 6) | $code;
			}



			$collected .= $func( ($hasError ? -1 : $codeValue), $buff );
		}

		return $collected;
	}
	function big5_filter( $string, $func = NULL, $strict = FALSE )
	{
		$len = strlen( $string );
		if ( $len <= 0 ) return NULL;


		$func = ( is_callable($func) ) ? $func : function( $codeVal, $bytes ) {
			return ( $codeVal < 0 ) ? '' : $bytes;
		};



		$index = 0; $collected = '';
		while( $index < $len )
		{
			$code		= ord( $buff = $data = $string[ $index++ ] );
			$codeValue	= $code;
			$numBytes	= ( $code < 128 ) ? 0 : 1;



			$hasError = FALSE;
			while ( $numBytes-- > 0 )
			{
				$code = ord( $data = $string[ $index++ ] );
				$buff .= $data;
				$codeValue = ($codeValue << 8) | $code;



				if ( $codeValue < 128 )
					continue;
				else
				if ( $codeValue >= 0x8140 && $codeValue <= 0xA0FE && !$strict )
					continue;
				else
				if ( $codeValue >= 0xA140 && $codeValue <= 0xA3BF )
					continue;
				else
				if ( $codeValue >= 0xA440 && $codeValue <= 0xC67E )
					continue;
				else
				if ( $codeValue >= 0xC6A1 && $codeValue <= 0xC8FE && !$strict )
					continue;
				else
				if ( $codeValue >= 0xC940 && $codeValue <= 0xF9D5 )
					continue;
				else
				if ( $codeValue >= 0xF9D6 && $codeValue <= 0xFEFE && !$strict )
					continue;


				$hasError = $hasError || TRUE;
			}



			$collected .= $func( ($hasError ? -1 : $codeValue), $buff );
		}

		return $collected;
	}

	// INFO: Version Processing
	function ParseVersion($verStr, $keepEmpty = FALSE)
	{
		if(!preg_match('/^\d+[.-]\d+(([.-]\d+[.-]\d+){0,1}|([.-]\d+){0,1})$/', $verStr)) return NULL;

		$ver = preg_split('/[.-]/', $verStr);
		return array(
			'major'		=> TO($ver[0], 'int'),
			'minor'		=> TO($ver[1], 'int'),
			'build'		=> ($ver[2] === NULL && $keepEmpty) ? NULL : TO($ver[2], 'int'),
			'revision'	=> ($ver[3] === NULL && $keepEmpty) ? NULL : TO($ver[3], 'int')
		);
	}
	function NormalizeVersion($verStr)
	{
		$ver = ParseVersion($verStr);
		return ($ver === NULL) ? NULL : "{$ver['major']}.{$ver['minor']}.{$ver['build']}-{$ver['revision']}";
	}
	function CompareVersion($verA, $verB, $minimalMajored = TRUE)
	{
		$normalize = (func_num_args() > 2) ? TRUE : FALSE;

		$verA = ParseVersion($verA, !$normalize);
		$verB = ParseVersion($verB, !$normalize);

		if (empty($verA) || empty($verB)) return FALSE;

		// major
		if ($verA['major'] > $verB['major']) return  1;
		if ($verA['major'] < $verB['major']) return -1;

		if ($verA['minor'] > $verB['minor']) return  1;
		if ($verA['minor'] < $verB['minor']) return -1;

		if ($verA['build'] !== NULL || $verB['build'] !== NULL)
		{
			if ($verA['build'] === NULL) return ($minimalMajored) ? -1 : 1;
			if ($verB['build'] === NULL) return ($minimalMajored) ?  1 : -1;

			if ($verA['build'] > $verB['build']) return  1;
			if ($verA['build'] < $verB['build']) return -1;


			if ($verA['revision'] !== NULL || $verB['revision'] !== NULL)
			{
				if ($verA['revision'] === NULL) return ($minimalMajored) ? -1 : 1;
				if ($verB['revision'] === NULL) return ($minimalMajored) ?  1 : -1;

				if ($verA['revision'] > $verB['revision']) return  1;
				if ($verA['revision'] < $verB['revision']) return -1;
			}
		}

		return 0;
	}

	// INFO: Supportive Functions
	function TimeElapsedQuantum($now, $target) {

		$nowBuff = new DateTime();
		$nowBuff->setTimestamp($now);
		$targetBuff = new DateTime();
		$targetBuff->setTimestamp($target);

		$interval = $nowBuff->diff($targetBuff);

		if ($interval->y)
		{
			$unit = ($interval->y > 1) ? 'years' : 'year';
			return "{$interval->y} {$unit} before";
		}

		if ($interval->m)
		{
			$unit = ($interval->m > 1) ? 'months' : 'month';
			return "{$interval->m} {$unit} before";
		}

		if ($interval->d)
		{
			$unit = ($interval->d > 1) ? 'days' : 'day';
			return "{$interval->d} {$unit} before";
		}

		if ($interval->h)
		{
			$unit = ($interval->h > 1) ? 'hours' : 'hour';
			return "{$interval->h} {$unit} before";
		}

		if ($interval->i)
		{
			$unit = ($interval->i > 1) ? 'minutes' : 'minute';
			return "{$interval->i} {$unit} before";
		}

		$unit = ($interval->s > 1) ? 'seconds' : 'second';
		return "{$interval->s} {$unit} before";
	}
	function LogStr($logMsg, $dateStr = TRUE, $timeSecond = TRUE, $timeZoneStr = TRUE) {
		$fmt = array();
		if ( $dateStr ) $fmt[] = "Y/m/d";
		$fmt[] = ($timeSecond) ? "H:i:s" : "H:i";
		if ( $timeZoneStr ) $fmt[] = "O";

		$fmt = implode(' ', $fmt);
		return "[" . date($fmt) . "] {$logMsg}";
	}
	function CheckEmailSyntax($email) { return (filter_var($email, FILTER_VALIDATE_EMAIL) !== FALSE); }
