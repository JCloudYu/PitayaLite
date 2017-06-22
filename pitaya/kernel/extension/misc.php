<?php
	// region [ Fusion two data ]
	function &data_fuse(&$data1, $data2, $overwrite = TRUE){
		if ( !is_array($data1) && !is_object($data1) ) return $data1;
		if ( !is_array($data2) && !is_object($data2) ) return $data1;
	
	
		$targetIsObj = is_object($data1);
		foreach( $data2 as $field => $value ) {
			if ($targetIsObj)
			{
				if ( !property_exists( $data1, $field ) || $overwrite )
					$data1->{$field} = $value;
			}
			else
			{
				if ( !array_key_exists( $field, $data1 ) || $overwrite )
					$data1[$field] = $value;
			}
		}
		
		return $data1;
	}
	function data_merge($data1, ...$sources) {
		$targetIsObj = is_object($data1);
		$destination = ($targetIsObj) ? clone $data1 : $data1;
		
		foreach( $sources as $source ) {
			data_fuse( $destination, $source );
		}
		
		return $destination;
	}
	function data_set($data1, ...$sources) {
		$targetIsObj = is_object($data1);
		$destination = ($targetIsObj) ? clone $data1 : $data1;
		
		foreach( $sources as $source ) {
			data_fuse( $destination, $source, FALSE );
		}
		
		return $destination;
	}
	// endregion
	
	// region [ Looping over data content ]
	function data_filter( $traversable, $filter = NULL, $skipVal = FALSE )
	{
		if ( !is_array($traversable) && !is_object($traversable) && !($traversable instanceof Traversable) ) return FALSE;

		$arguments	= func_get_args();
		$skipMode	= count($arguments) != 2;

		if ( !is_callable($filter) )
		{
			$filter = (!$skipMode) ?
				function( $item ) { return $item; } :
				function( $item ) { return (empty($item)) ? FALSE : $item; };
		}

		$collected = [];
		foreach ( $traversable as $idx => $item )
		{
			$result = $filter($item, $idx);
			if ( $skipMode && ($result === $skipVal) ) continue;

			if ( $idx === NULL )
				$collected[] = $result;
			else
				$collected[$idx] = $result;
		}

		return $collected;
	}
	function ary_filter() { return call_user_func_array( "data_filter", func_get_args() ); }
	function object_filter() { return call_user_func_array( "data_filter", func_get_args() ); }
	// endregion

	// region [ JSON Processing ]
	function pb_json_decode( $jsonString, ...$args ){
		// search and remove comments like /* */ and //
		$json = preg_replace('#(/\*([^*]|[\r\n]|(\*+([^*/]|[\r\n])))*\*+/)|([\s\t]//.*)|(^//.*)#', '', $jsonString);
		array_unshift($args, $json);
		return call_user_func_array( 'json_decode', $args );
	}

	function pb_json_encode( ...$args ){ 
		return call_user_func_array( 'json_encode', $args );
	}
	// endregion

	// region [ Hash Functions ]
	function sha256( $content, $rawOutput = FALSE ) {
		return hash( 'sha256', $content, $rawOutput );
	}
	
	function sha256_file( $fileName, $rawOutput = FALSE ) {
		return hash_file( 'sha256', $fileName, $rawOutput );
	}
	
	function sha384( $content, $rawOutput = FALSE ) {
		return hash( 'sha384', $content, $rawOutput );
	}
	
	function sha384_file( $fileName, $rawOutput = FALSE ) {
		return hash_file( 'sha384', $fileName, $rawOutput );
	}
	
	function sha512( $content, $rawOutput = FALSE ) {
		return hash( 'sha512', $content, $rawOutput );
	}
	
	function sha512_file( $fileName, $rawOutput = FALSE ) {
		return hash_file( 'sha512', $fileName, $rawOutput );
	}
	// endregion
	
	// region [ Array Function ]
	function is_assoc($array,  $allowEmpty = FALSE) {
		if ( !is_array($array) ) return FALSE;
		return (empty($array) && $allowEmpty) || (array_keys($array) !== range(0, count($array) - 1));
	}
	
	define( 'IN_ARY_MODE_AND', 			0x01 );
	define( 'IN_ARY_MODE_OR', 			0x00 );
	define( 'IN_ARY_MODE_STRICT', 		0x02 );
	define( 'IN_ARY_MODE_NONE_STRICT', 	0x00 );
	function in_ary($needle, $candidates, $mode = IN_ARY_MODE_OR) {
		if (!is_array($needle)) $needle = [ $needle ];


		if (!is_int($mode)) $mode = 0;
		$andMode 	= $mode & IN_ARY_MODE_AND;
		$strictMode = $mode & IN_ARY_MODE_STRICT;

		$state = (empty($andMode)) ? FALSE : TRUE;
		foreach ($needle as $content)
		{
			if ($andMode)
				$state = $state && in_array($content, $candidates, !empty($strictMode));
			else
				$state = $state || in_array($content, $candidates, !empty($strictMode));
		}

		return $state;
	}
	
	function ary_fill($size, $element, $startIndex = 0) {
		$rtAry = [];

		for($i = 0; $i <$size; $i++, $startIndex++)
			$rtAry[$startIndex] = $element;

		return $rtAry;
	}
	function ary_exclude($src, $ref) {
		if (!is_array($src)) return [];

		$args = func_get_args();
		array_shift($args);


		$left = $src;
		foreach ($args as $param)
		{
			if (!is_array($param)) continue;


			$stayed = [];
			foreach ($left as $src_content)
			{
				if (in_array($src_content, $param)) continue;
				$stayed[] = $src_content;
			}
			$left = $stayed;
		}

		return $left;
	}
	function ary_flag($ary, $flag, $matchCase = TRUE, $compareMode = IN_ARY_MODE_OR) {
		
	
		if (!is_array($ary)) $ary = [];
		if (!is_array($flag)) $flag = [ $flag ];

		if (!$matchCase)
			foreach ($flag as $id => $content) $flag[$id] = trim(strtolower("{$content}"));


		$candidates = [];
		foreach ($ary as $idx => $item)
		{
			if (!preg_match('/^\d+$/', "{$idx}")) continue;
			$candidates[] = trim(((!$matchCase) ? strtolower("{$item}") : "{$item}"));
		}

		return in_ary($flag, $candidates, $compareMode);
	}
	function ary_pick($ary, $indices) {
		if (empty($indices) || (!is_array($indices) && !is_string($indices)))
			return [];

		$indices = (is_string($indices)) ? explode(',', $indices) : $indices;
		$collected = [];
		foreach ($indices as $idx) { $collected[] = $ary[$idx]; }

		return $collected;
	}
	// endregion
	
	// region [ Numeric Detection ]
	function EXPR_NUMERIC($val) {
		return EXPR_INT($val) || EXPR_FLOAT_DOT($val) || EXPR_FLOAT_SCIENCE($val);
	}
	function EXPR_INT($val) {
		return (preg_match('/^[-+]{0,1}\d+$/', "{$val}") > 0);
	}
	function EXPR_UINT($val) {
		return (preg_match('/^\d+$/', "{$val}") > 0);
	}
	function EXPR_FLOAT($val) {
		return EXPR_FLOAT_DOT($val) || EXPR_FLOAT_SCIENCE($val);
	}
	function EXPR_FLOAT_DOT($val) {
		return (preg_match('/^[-+]{0,1}((\d+)|(\d*\.\d+))$/', "{$val}") > 0);
	}
	function EXPR_FLOAT_SCIENCE($val) {
		return (preg_match('/^[-+]{0,1}((\d*\.\d+)|(\d+\.\d*))[eE][-+]{0,1}\d+$/', "{$val}") > 0);

		/*
		// INFO: Alternative implementation of science expression syntax detection
		$parts = explode('e', strtolower("{$val}"));
		if (count($parts) != 2) return FALSE;

		return FLOAT_DOT_EXPR($parts[0]) && INT_EXPR($parts[1]);
		*/
	}
	// endregion
	
	// region [ Type Casting ]
	function CAST(...$args) {
	
		$value	 = @$args[0];
		$type	 = @$args[1];
		$filter  = @$args[2];
		$default = @$args[3];
	
	
	
		$opt	= explode( ' ', strtolower(trim("{$type}")) );
		$base	= @array_shift( $opt );
		$nArgs	= count($args);

		switch( $base )
		{
			// region int [strict] [no-casting]
			/*
			 *	CAST( $value, 'int strict no-casting', $default )
			 */
			case 'int':
				$default = $filter;

				$value = trim("$value");
				$defaultVal = ($nArgs > 2) ? $default : 0;
				$procFunc	= ( in_array('strict', $opt) ) ? "EXPR_INT" : "EXPR_NUMERIC";
				$status = $procFunc($value);
				
				if ( in_array( 'positive', $opt ) )
				{
					$str = "{$value}";
					$status = $status && !( $str[0] == "-" );
				}
				else
				if ( in_array( 'negative', $opt ) )
				{
					$str = "{$value}";
					$status = $status && ( $str[0] == "-" );
				}

				
				if ( !$status )
					return $defaultVal;
				else
					return ( in_array( 'no-casting', $opt ) ) ? $value : @intval($value);
			// endregion

			// region float [strict] [no-casting]
			/*
			 *	CAST( $value, 'float strict no-casting', $default )
			 */
			case 'float':
				$default = $filter;
				
				$value = trim("$value");
				$defaultVal = ($nArgs > 2) ? $default : 0.0;
				$procFunc	= ( in_array('strict', $opt) ) ? "EXPR_FLOAT" : "EXPR_NUMERIC";
				$status = $procFunc($value);
				
				if ( in_array( 'positive', $opt ) )
				{
					$str = "{$value}";
					$status = $status && !( $str[0] == "-" );
				}
				else
				if ( in_array( 'negative', $opt ) )
				{
					$str = "{$value}";
					$status = $status && ( $str[0] == "-" );
				}

				
				if ( !$status )
					return $defaultVal;
				else
					return ( in_array( 'no-casting', $opt ) ) ? $value : @floatval($value);
			// endregion

			// region string [force] [lower-case] [upper-case] [decode-url] [encode-url] [purge-html]
			/*
			 *	CAST( $value, 'string purge-html', $default )
			 */
			case 'string':
				$default = $filter;

				if ( !is_string( $value ) && ($nArgs > 2) && !in_array( 'force', $opt ) ) return $default;

				$value = in_array( 'no-trim', $opt ) ? "{$value}" : trim("{$value}");

				if (in_array('encode-url', $opt))
					$value = urlencode($value);

				if (in_array('decode-url', $opt))
					$value = urldecode($value);

				if (in_array('lower-case', $opt))
					$value = strtolower($value);
				else
				if (in_array('upper-case', $opt))
					$value = strtoupper($value);

				if (in_array('purge-html', $opt))
					$value = htmlspecialchars($value);

				return $value;
			// endregion

			// region range [op-and] [op-or] [strict]
			/*
			 *	CAST( $value, 'range op-and op-or strict', array(...), $default );
			 */
			case 'range':
				$defaultVal = ( $nArgs > 3 ) ? $default : NULL;

				if ( !is_array( $filter ) )
					return $defaultVal;


				$booleanOperator = ( in_array( 'op-and', $opt ) ) ? IN_ARY_MODE_AND : IN_ARY_MODE_OR;
				$strictTyping	 = ( in_array( 'strict', $opt ) ) ? IN_ARY_MODE_STRICT : IN_ARY_MODE_NONE_STRICT;
				return in_ary( $value, $filter, $booleanOperator | $strictTyping ) ? $value : $defaultVal;
			// endregion

			// region array [purge-empty] [regex] [delimiter] [json]
			/*
			 *	CAST( $value, 'array', $default ) // TYPING MODE
			 *	CAST( $value, 'array regex', $pattern, $default )	// SPLIT MODE
			 *	CAST( $value, 'array delimiter', $pattern, $default )	// SPLIT MODE
			 */
			case 'array':
				$typingOptions	= [ 'delimiter', 'regex' ];
				$typingMode		= (CAST( $typingOptions, 'range', $opt ) === NULL);

				if ( $typingMode === NULL )	// INFO: TYPING MODE
					$defaultVal = ($nArgs > 2) ? $filter : [];
				else						// INFO: SPLIT MODE
					$defaultVal = ($nArgs > 3) ? $filter : [];



				if ( is_array($value) )
					$converted = $value;
				else
				if ( trim( @"{$value}" ) === "" )
					return [];
				else
				if ( in_array( 'delimiter', $opt ) )
					$converted = @explode( "{$filter}", "{$value}" );
				else
				if ( in_array( 'regex', $opt ) )
					$converted = @preg_split( "{$filter}", "{$value}" );
				else
					$converted = $defaultVal;

				return ( in_array( 'purge-empty', $opt ) && is_array($converted) && (count($converted) == 0) ) ? $defaultVal : $converted;
			// endregion

			// region time [format]
			/*
			 *	CAST( $val, 'time', $default )					// Epoch Mode
			 *	CAST( $val, 'time format', $format, $default )	// Format Text Mode
			 *	CAST( $val, 'time parse', $format, $default )	// Get time from format
			 */
			case 'time':
				// Parse time according to format
				if ( in_array( 'parse', $opt ) )
				{
					$dateObj = date_create_from_format( "{$filter}", "{$value}" );
					if ( $dateObj === FALSE )
						return $nArgs > 3 ? $default : -1;
					
					return (in_array( 'get-object', $opt )) ? $dateObj : $dateObj->getTimestamp();
				}
			
			
			
				// INFO: Automatically parse time from string
				$val	= strtotime(trim("{$value}"));
				$fmtErr	= ($val === FALSE || $val < 0);

				// INFO: Format Text Mode
				if ( in_array( 'format', $opt ) )
				{
					if ( $fmtErr && ($nArgs > 3) )
						return $default;
					else
						return date( "{$filter}", ($fmtErr) ? 0 : $val );
				}

				// INFO: Epoch Mode
				$defaultVal = ($nArgs > 2) ? $filter : 0;
				return $fmtErr ? $defaultVal : $val;
			// endregion

			// region bool [is-true] [is-false]
			case 'boolean':
			case 'bool':
				if (in_array('is-true', $opt))
					return ($value === TRUE);
				else
				if (in_array('is-false', $opt))
					return !($value === FALSE);
				else
					return !(empty($value));
			// endregion

			case 'raw':
			default:
				return $value;
		}
	}
	// endregion