<?php
/**
 * 1031.faceAppServer - PBShell.php
 * Created by JCloudYu on 2015/06/22 21:32
 */
	final Class PBShell
	{
		public static function RunCommand( $cmd, $msg = '' )
		{
			PBStdIO::STDOUT( ( empty($msg) ) ? $cmd : $msg );
			exec( $cmd, $out = NULL, $status);
			if ( $status )
			{
				PBStdIO::STDERR( implode("\n", $out) );
				PBStdIO::STDERR( "Error occurred! Terminating..." );
				Termination::WITH_STATUS( Termination::STATUS_ERROR );
			}
		}

		public static function ReadLine( $msg = '', $type = 'raw', $validateFunc = NULL, $useStar = FALSE ) {
			return self::ReadInput( $msg, $type, $validateFunc );
		}
		public static function ReadPass( $msg = '', $type = 'raw', $validateFunc = NULL, $useStar = FALSE ) {
			return self::ReadInput( $msg, $type, $validateFunc, TRUE, $useStar );
		}
		public static function ReadInput( $msg = '', $type = 'raw', $validateFunc = NULL, $isPass = FALSE, $useStar = FALSE )
		{
			// INFO: Normalize validating function
			if ( empty( $validateFunc ) ) $validateFunc = function( $val ){ return TRUE; };
			if ( !is_callable( $validateFunc ) ) $validateFunc = function( $val ){ return !empty($val); };



			do
			{
				$value = TO( PBStdIO::READ( $msg, $isPass, $useStar), $type );
				if ( $validateFunc($value) ) break;
			}
			while( 1 );

			return $value;
		}
	}
