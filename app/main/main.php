<?php
	final class main extends PBModule {
		const PRIOR_MODULES = [ 'ext.modules.main_before' ];
	
		public function execute($chainData) {
			_R( 'req' )->res = PBRequest()->resource;
			
			$this->chain[] = "main_b";
			$this->chain[] = "main_b#main_b_b";
		}
	}