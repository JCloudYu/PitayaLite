<?php
	final class main extends PBModule {
		const PRIOR_MODULES = [ 'prior' ];
	
		public function execute($chainData) {
			DEBUG::VarDump(__CLASS__);
			$this->chain[] = 'main#main_next';
		}
	}
	
	final class main_next extends PBModule {
		public function execute($chainData) {
			DEBUG::VarDump(__CLASS__);
		}
	}