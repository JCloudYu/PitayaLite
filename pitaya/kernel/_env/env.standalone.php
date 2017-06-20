<?php
	if ( IS_CLI_ENV ) {
		array_shift( $_SERVER['argv'] ); // INFO: Remove script file path
		
		if ( @"{$_SERVER['argv'][0]}" == "-entry" ) {
			array_shift($_SERVER['argv']);	// -entry
			array_shift($_SERVER['argv']);	// script name
			
			s_define( 'PITAYA_STANDALONE_EXECUTION_MODE', TRUE );
			s_define( 'PITAYA_STANDALINE_EXECUTION_SCRIPT', "{$_SERVER['argv'][0]}" );
			s_define( 'PITAYA_STANDALINE_EXECUTION_DIR', getcwd() );
		}
	}
	
	s_define( 'PITAYA_STANDALONE_EXECUTION_MODE', FALSE );