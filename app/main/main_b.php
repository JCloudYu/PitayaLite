<?php
	final class main_b extends PBModule {
		public function execute($chainData) {
			DEBUG::VarDump( $this->class );
		}
	}
	
	final class main_b_b extends PBModule {
		public function execute($chainData) {
			DEBUG::VarDump( $this->class );
		}
	}