<?php
	class PBKernelVersion extends PBModule {
	
		public function execute( $chainData = NULL, $initData = NULL ) {
			$request = IS_HTTP_ENV ? PBRequest::Request()->parseQuery()->query['resource'] : $initData; 
			$reqVer = CAST( @array_shift( $request ), 'string upper-case' );
			$verMap = array(
				"MAJOR"		=> PITAYA_VERSION_MAJOR,
				"MINOR"		=> PITAYA_VERSION_MINOR,
				"BUILD"		=> PITAYA_VERSION_BUILD,
				"PATCH"		=> PITAYA_VERSION_PATCH,
				"SHORT"		=> PITAYA_VERSION_SHORT,
				"COMPLETE"	=> PITAYA_VERSION,
				"DETAIL"	=> PITAYA_VERSION_DETAIL
			);
			$version = empty($verMap[$reqVer]) ? $verMap['DETAIL'] : $verMap[$reqVer];
			return $version;
		}
	}
