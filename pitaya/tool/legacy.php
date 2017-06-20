<?php
	function TO($value, $type, $options = NULL) {
		DEPRECATION_WARNING( "Legacy API [TO] is marked as deprecated! Please refer to CAST api!" );
	
	
		if (is_array($type))
		{
			$criteria	= @$type['criteria'];
			$default	= @$type['default'];

			// NOTE: The following line must put at the end of this scope for overwriting prevention!!!
			$type		= @$type['type'];
		}
		else
			$default = $criteria = NULL;

		if ( !is_array( $options ) ) $options = array();



		$type = is_string($type) ? strtolower($type) : 'raw';


		$illustrator = explode(' ', trim($type));
		$baseType = array_shift($illustrator);

		switch($baseType)
		{
			// INFO: int [strict]
			case 'int':
				$value = trim("$value");

				if (in_array('strict', $illustrator))
					$value = EXPR_INT($value) ? $value : 0;
				else
					$value = EXPR_NUMERIC($value) ? $value : 0;

				return (in_array( 'no-casting', $illustrator )) ? $value : @intval($value);

			// INFO: float [strict]
			case 'float':
				$value = trim("$value");

				if (in_array('strict', $illustrator))
					$value = EXPR_FLOAT($value) ? $value : 0.0;
				else
					$value = EXPR_NUMERIC($value) ? $value : 0.0;

				return (in_array( 'no-casting', $illustrator )) ? $value : @floatval($value);

			// INFO: string [decode-url] [encode-url] [purge-html]
			case 'string':
				$value = trim("$value");

				if (in_array('encode-url', $illustrator))
					$value = urlencode($value);

				if (in_array('decode-url', $illustrator))
					$value = urldecode($value);

				if (in_array('lower-case', $illustrator))
					$value = strtolower($value);
				else
				if (in_array('upper-case', $illustrator))
					$value = strtoupper($value);

				if (in_array('purge-html', $illustrator))
					$value = htmlspecialchars($value);


				
				if (in_array('json', $illustrator) || in_array('json-array', $illustrator))
					$value = json_decode($value, TRUE);

				if (in_array('json-object', $illustrator))
					$value = json_decode($value);

				return $value;

			// INFO: boolean
			case 'boolean':
			case 'bool':
				if (in_array('is-true', $illustrator))
					return ($value == TRUE);
				else
				if (in_array('is-false', $illustrator))
					return ($value == FALSE);
				else
					return !(empty($value));

			case 'array':
				if ( is_array( $value ) )
					return $value;

				$value = "{$value}";

				if ( empty($value) )
					return array();
				else
				if ( !empty( $options['regex'] ) )
					return preg_split("{$options['regex']}", $value);
				else
				if ( !empty($options['delimiter']) )
					return explode( $options['delimiter'], $value );

				return array();


			// INFO: time string
			case 'time':
				$value = trim("$value");

				$val = strtotime("{$value}");
				return ($val === FALSE || $val < 0) ? 0 : $val;

			// INFO: range
			case 'range':
				if (!is_array($criteria)) $criteria = array();
				return (in_array($value, $criteria)) ? $value : $default;

			case 'raw':
			default:
				return $value;
		}
	}