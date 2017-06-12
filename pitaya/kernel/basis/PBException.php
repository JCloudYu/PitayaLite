<?php
	

	class PBException extends Exception
	{
		private $_errDescriptor = NULL;

		public function __get( $varName )
		{
			$target = "__get_{$varName}";
			switch ( $varName )
			{
				case "descriptor":
					return $this->_errDescriptor;
				default:
					break;
			}

			throw(new Exception("Getting value from an undefined property '{$name}'."));
			return NULL;
		}

		public function __get_descriptor() {
			return $this->_errDescriptor;
		}

		public function __construct( $descriptor, $code = 0, $prevExcept = NULL )
		{
			$message = $descriptor;

			if ( is_array( $descriptor ) )
			{
				$this->_errDescriptor = $descriptor;
				$code		= CAST( @$descriptor['code'], 'int strict' );
				$message	= $descriptor['msg'];
			}

			parent::__construct( $message, $code, $prevExcept );
		}
	}
