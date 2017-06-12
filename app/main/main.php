<?php
	final class main extends PBModule {
		public function execute($chainData) {
			_R( 'req' )->res = PBRequest()->resource;
			DEBUG::VarDump(_R( 'req' )->res);
		}
	}