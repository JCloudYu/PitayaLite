<?php
	final class global_init extends PBModule {
		public function execute($chainData) {
			DEBUG::VarDump( $this->class );
		}
	}