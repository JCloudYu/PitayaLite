<?php
	class PBProc extends PBObject {
		public static function Module( $moduleName, $reusable = TRUE, $noThrow = FALSE ) {
			return call_user_func( 'PBModule', $moduleName, $reusable, $noThrow );
		}
		public static function ServiceModule() {
			return self::$_singleton->_entryModule;
		}
	
	
	
	
	
		/** @var PBProcess */
		public static $_singleton = NULL;
		public static function Proc() { return self::$_singleton; }
		
		/** @var PBKernel */
		private $_system = NULL;
		/** @var null|PBLinkedList */
		private $_bootSequence	= NULL;
		public function __construct( $sysInst ) {
			self::$_singleton = $this;
			$this->_system = $sysInst;
			$this->_bootSequence = PBLList::GENERATE();
		}
	
	
		
	
	
	
		public function addSearchPath( $package ) { return $this->_system->addModuleSearchPath( $package ); }
		public function removeSearchPath( $package ) { return $this->_system->removeModuleSearchPath( $package ); }
		public function getNextModule() {
		
			if (!PBLinkedList::NEXT($this->_bootSequence)) return NULL;
			$moduleId = $this->_bootSequence->data[ 'id' ];
			PBLinkedList::PREV($this->_bootSequence);
	
			return PBModule( $moduleId );
		}
		public function transferRequest($moduleRequest) {
		
			PBLinkedList::NEXT($this->_bootSequence);
			$this->_bootSequence->data['request'] = $moduleRequest;
			PBLinkedList::PREV($this->_bootSequence);
		}
		public function cancelNextModule() {
	
			$status = PBLList::NEXT($this->_bootSequence);
			if(!$status) return $status;
	
			$status = $status && PBLList::REMOVE($this->_bootSequence);
			return $status;
		}
		public function cancelModules( $skips = NULL ) {
		
			if ( func_num_args() == 0 )
			{
				while( PBLinkedList::NEXT($this->_bootSequence) )
					PBLinkedList::REMOVE($this->_bootSequence);
			}
			else
			if ( is_numeric( $skips ) )
			{
				if ( $skips > 0 )
				{
					$skipCounter = $skips;
					while( PBLinkedList::NEXT($this->_bootSequence) )
					{
						if ( $skipCounter <= 0 )
							PBLinkedList::REMOVE($this->_bootSequence);
						else
							$skipCounter--;
					}
					
					while( $skips-- > 0 )
						PBLinkedList::PREV( $this->_bootSequence );
				}
				else
				if ( $skips < 0 )
				{
					$skips = -$skips; $length = 0;
					while( PBLinkedList::NEXT($this->_bootSequence) ) $length++;
					
					if ( $length <= $skips )
					{
						while( $length-- > 0 ) PBLinkedList::PREV( $this->_bootSequence );
						return;
					}
					
					
					
					$length -= $skips;
					while( $skips-- > 0 ) PBLinkedList::PREV($this->_bootSequence);
					while( $length-- > 0 ) PBLinkedList::REMOVE( $this->_bootSequence );
				}
			}
			else
			{
				if ( !is_array( $skips ) ) $skips = [ $skips ];
			
			
				$skipCounter = 0;
				while( PBLinkedList::NEXT($this->_bootSequence) )
				{
					$module = PBModule( $this->_bootSequence->data[ 'id' ] );
					
					$valid = FALSE;
					data_filter( $skips, function( $name ) use( &$valid, &$module ) {
						$name = "{$name}";
						$valid = $valid || ( $module instanceof $name );
					});
					
					if ( $valid )
						$skipCounter++;
					else
						PBLinkedList::REMOVE($this->_bootSequence);
				}
				
				
				while( $skipCounter-- > 0 )
					PBLinkedList::PREV( $this->_bootSequence );
			}
		}
		
		
		
		
		
		public function run() {
			$dataInput = PBRequest()->resource;
			PBLList::HEAD($this->_bootSequence);
			do
			{
				$processed = @$this->_bootSequence->data[ 'processed' ] ?: FALSE;
				$module = PBModule(@$this->_bootSequence->data[ 'id' ]);
				if ( !$processed ) {
					$this->_bootSequence->data[ 'processed' ] = TRUE;
					$prerequisites = $module->precondition();
					if ( is_array($prerequisites) && count($prerequisites) > 0 ) {
						$this->_prependBootSequence($prerequisites);
						$module = PBModule(@$this->_bootSequence->data[ 'id' ]);
					}
				}
				
				
				$request = @$this->_bootSequence->data[ 'request' ];
				if ( !property_exists($module->data, "initData") )
					$module->data->initData = $request;
				
				$dataInput = $module->execute( $dataInput, $request );
				$this->_appendBootSequence( $module->bootChain );
			}
			while( PBLList::NEXT($this->_bootSequence) );
		}
		
		private $_entryModule	= NULL;
		private $_mainModuleId = NULL;
		public function attachMainService( $entryModule, $initData = NULL ) {
	
			if ( defined('LEADING_MODULES') ) {
				$moduleNames = (is_array(LEADING_MODULES) ? LEADING_MODULES : [ LEADING_MODULES ]);
				foreach( $moduleNames as $moduleName ) {
					$module = PBModule( $moduleName, TRUE );
					$moduleId = $module->id;
					PBLList::PUSH( $this->_bootSequence, [
						'id' => $moduleId
					], $moduleId );
				}
			}
	
	
	
			// NOTE: Service Entry Module
			$this->_entryModule = PBModule( $entryModule, TRUE );
			$this->_mainModuleId = $this->_entryModule->id;
			$this->_entryModule->data->initData = $initData;
			PBLList::PUSH( $this->_bootSequence, [
				'id' => $this->_mainModuleId
			], $this->_mainModuleId);
	
	
	
			if ( defined('TAILING_MODULES') ) {
				$moduleNames = (is_array(TAILING_MODULES) ? TAILING_MODULES : [ TAILING_MODULES ]);
				foreach( $moduleNames as $moduleName ) {
					$module = PBModule( $moduleName, TRUE );
					$moduleId = $module->id;
					PBLList::PUSH( $this->_bootSequence, [
						'id' => $moduleId
					], $moduleId );
				}
			}
			
			
			
			// NOTE: Rewind back to the first instance
			PBLinkedList::HEAD($this->_bootSequence);
		}
		
		private function _prependBootSequence( $bootSequence ) {
			if ( !is_array( $bootSequence )) return;
	
	
			$bootSequence = array_reverse( $bootSequence );
			foreach( $bootSequence as $illustrator ) {
				if (is_a($illustrator, stdClass::class)) {
					$illustrator = (array)$illustrator;
				}
	
				if (!is_array($illustrator)) {
					$illustrator = [ 'module' => $illustrator ];
				}
				
				
					
				$moduleHandle = @$illustrator[ 'module' ];
				if ( empty($moduleHandle) ) continue; // Skipping empty values
	
				$reuse = array_key_exists( 'reuse', $illustrator ) ? !empty($illustrator['reuse'] ) : TRUE;
				$moduleId = PBModule( $moduleHandle, $reuse )->id;
				
	
				PBLList::BEFORE( $this->_bootSequence,  [
					'id' => $moduleId, 'request' => @$illustrator[ 'request' ]
				], $moduleId );
				PBLList::PREV($this->_bootSequence);
			}
		}
		private function _appendBootSequence( $bootSequence ) {
			if ( !is_array( $bootSequence )) return;
	
	
	
			$bootSequence = array_reverse( $bootSequence );
			foreach( $bootSequence as $illustrator ) {
				if (is_a($illustrator, stdClass::class)) {
					$illustrator = (array)$illustrator;
				}
	
				if (!is_array($illustrator)) {
					$illustrator = [ 'module' => $illustrator ];
				}
				
				
					
				$moduleHandle = @$illustrator[ 'module' ];
				if ( empty($moduleHandle) ) continue; // Skipping empty values
	
				$reuse = array_key_exists( 'reuse', $illustrator ) ? !empty($illustrator['reuse'] ) : TRUE;
				$moduleId = PBModule( $moduleHandle, $reuse )->id;
				
	
				PBLList::AFTER( $this->_bootSequence,  [
					'id' => $moduleId, 'request' => @$illustrator[ 'request' ]
				], $moduleId );
			}
		}
	}
	class_alias( 'PBProc', 'PBProcess' );
	
	function PBProcess(){
		return PBProc();
	}
	function PBProc() {
		static $_singleton = NULL;
		if ( $_singleton === NULL ) {
			$_singleton = PBProcess::Proc();
		}
		
		return $_singleton;
	}
