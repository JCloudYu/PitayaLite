<?php
	/**
	 * Class PBException
	 * @property-read mixed $descriptor
	 */
	class PBException extends Exception {
		private $_errDescriptor = NULL;

		public function __construct( $descriptor, $code = 0, $prevExcept = NULL ) {
			$message = $descriptor;

			if ( is_array( $descriptor ) ) {
				$this->_errDescriptor = $descriptor;
				$code		= CAST( @$descriptor['code'], 'int strict' );
				$message	= $descriptor['msg'];
			}

			parent::__construct( $message, $code, $prevExcept );
		}
		public function __get( $varName ) {
			switch ( $varName )
			{
				case "descriptor":
					return $this->_errDescriptor;
				default:
					break;
			}

			throw(new Exception("Getting value from an undefined property '{$varName}'."));
		}
	}
