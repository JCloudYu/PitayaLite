<?php
	using( 'modules.PBOutputCtrl' );
	
	class PBAPIOut extends PBHttpOut {
		public static function ErrorOut( $statusCode, $type, $code = 0, $message = "", $subcode = NULL, $detail = NULL ) {
			if ( is_object($type) || is_array($type) ) {
				$responseObj = $type;
			}
			else {
				$responseObj = stdClass([
					"type"		=> $type,
					"code"		=> $code,
					"subcode"	=> $subcode,
					"message"	=> $message,
					"detail"	=> $detail
				]);
				
				if ( $detail === NULL ) {
					unset($responseObj->detail);
				}
				
				if ( $subcode === NULL ) {
					unset($responseObj->subcode);
				}
			}
			
			
			
			PBHttpOut::DataOut( $statusCode, stdClass([ "error" => $responseObj ]) );
		}
	
		public function execute( $chainData ) {
			$result = self::__PROCESS_OUTPUT( self::$_outputData ?: $chainData );
			self::ContentType( 'application/json' );
			parent::execute( @json_encode($result) );
		}
		private static function __PROCESS_OUTPUT( $param ) {
			$result = stdClass();
			
			if ( $param === NULL ) {
				$param = stdClass();
			}
			else
			if ( is_array($param) ) {
				$param = stdClass($param);
			}
			else
			if ( !is_object($param) ) {
				$param = stdClass([
					'data' => $param
				]);
			}
			
			
			// Merging params
			$param = clone $param;
			$result->scope = PBScope()->breadcrumb();
			if ( property_exists($param, 'data') ) {
				$result->data = $param->data;
				unset($param->data);
			}
			if ( property_exists($param, 'error') ) {
				$result->error = $param->error;
				unset($param->error);
			}
			if ( property_exists($param, 'paging') ) {
				$result->paging = $param->paging;
				unset($param->paging);
			}
			$result = data_set( $result, $param );



			return $result;
		}
	}
	class_alias( 'PBAPIOut', 'pajax' );
