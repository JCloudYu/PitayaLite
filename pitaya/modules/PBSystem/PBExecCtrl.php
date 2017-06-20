<?php
	class PBBasisChain extends PBModule {
		public function execute( $chainData ) {
			$chainInfo = $this->data->initData ?: [];
			foreach ( $chainInfo as $chainModule ) {
				$this->chain[] = $module = PBModule($chainModule);
			}
				
			return $chainData;
		}
	}

	class PBVectorChain extends PBModule {
		public function execute( $chainData ) {
			$module			= PBModule( "working." . __STANDALONE_MODULE__ );
			$this->chain[]	= PBModule($module);
			data_fuse($module->data, $this->data);
			
			return $chainData;
		}
	}
