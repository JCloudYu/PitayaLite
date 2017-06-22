<?php
	using( 'modules.PBOutputCtrl' );
	
	class PBJSONOut extends PBHttpOut {
		public function execute( $chainData ) {
			PBHttpOutput::ContentType( "application/json" );
			parent::execute(json_encode(
				self::$_outputData ?: $chainData
			));
		}
	}
	class_alias( 'PBJSONOut', 'json' );
