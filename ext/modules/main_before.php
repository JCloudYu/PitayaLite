<?php
	final class main_before extends PBModule {
		public function execute($chainData) {
			DEBUG::VarDump( $this->class );
		}
	}