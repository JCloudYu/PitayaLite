<?php
	using( 'modules.PBOutputCtrl' );
	
	class PBAJAXOut extends PBHttpOut {
	
		const STATUS_WARNING	=  1;
		const STATUS_NORMAL		=  0;
		const STATUS_ERROR		= -1;
		
		public function execute( $chainData ) {
			$result = self::__PROCESS_OUTPUT( self::$_outputData ?: $chainData );
			self::ContentType( 'application/json' );
			parent::execute( @json_encode($result) );
		}
		
		private static function __PROCESS_OUTPUT( $param ) {
		
			$ajaxReturn = (object)[];
			if ( is_array($param) )
				$param = (object)$param;
			else
			if ( !is_object($param) )
			{
				$param = (object)[
					'status' => self::STATUS_NORMAL,
					'msg'	 => $param
				];
			}
			
			
			// Merging params
			$param = clone $param;
			$ajaxReturn->status = CAST( @$param->status, 'int strict',	self::STATUS_NORMAL );
			$ajaxReturn->msg	= CAST( @$param->msg,	 'string',		'' );
			$ajaxReturn->scope	= PBScope()->breadcrumb( '#' );
			$ajaxReturn			= data_set( $ajaxReturn, $param );

			return $ajaxReturn;
		}
	}
	class_alias( 'PBAJAXOut', 'ajax' );
