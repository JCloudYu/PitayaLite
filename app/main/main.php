<?php
	final class main extends PBModule {
		const PRIOR_MODULES = [ 'ext.modules.main_before' ];
	
		public function execute($chainData) {
			$TRUNK = _R( 'req' );
			$TRUNK->res = PBRequest()->resource;
			
			DEBUG::VarDump( $TRUNK->res );
			$this->chain[] = "main_b";
			$this->chain[] = "main_b#main_b_b";
		}
	}