<?php
	abstract class PBModule extends PBObject {
		const PRIOR_MODULES = [];
		public function precondition() {
			$modules = [];
			foreach( static::PRIOR_MODULES as $moduleName ) {
				$modules[] = $module = PBModule( $moduleName );
				data_fuse( $module->data, $this->data );
			}
			return $modules;
		}
		public function execute( $chainData ) {
			return $chainData;
		}
		public function __invoke() {
			$args = func_get_args();
			return call_user_func( [ $this, 'execute' ], @array_shift($args), ...$args );
		}
		public function __toString() {
			return "{$this( NULL )}";
		}
		


		private $_data = NULL;
		public function &__get_data() {
			if ( $this->_data === NULL ) 
				$this->_data = (object)[];
			
			return $this->_data;
		}	

		private $_instId = NULL;
		public function __set_id( $value ) {
			if ( $this->_instId !== NULL ) return;
			$this->_instId = "{$value}";
		}
		public function __get_id() {
			return $this->_instId;
		}
		public function __get_id_short() {
			return substr( $this->_instId, 0, 8  );
		}
		public function __get_id_medium() {
			return substr( $this->_instId, 0, 16 );
		}
		public function __get_id_long() {
			return substr( $this->_instId, 0, 32 );
		}
	
		public function __get_class() {
			return get_class($this);
		}
		public function __get_class_lower() {
			return strtolower(get_class($this));
		}
		public function __get_class_uppper() {
			return strtoupper(get_class($this));
		}
		
		protected $chain = [];
		public function __get_bootChain() {
			return $this->chain;
		}
		
		
		protected $error = NULL;
		public function __get_error() {
			return $this->error;
		}
	}
	abstract class PBTplModule extends PBModule {
		private $_tplObj = NULL;
	
		public function __get_vars() {
			return empty($this->_tplObj) ? NULL : $this->_tplObj->vars;
		}
		public function __invoke( $output = TRUE ) {
			if ( empty($this->data->tmpl) ) {
				return "";
			}
		
			$this->_tplObj = $tplObj = PBTmplRenderer( $this->data->tmpl, @$this->data->tmplPath ?: NULL );
			data_fuse( $tplObj, $this->data, FALSE );
			
			return $tplObj($output);
		}
		public function __toString() {
			return $this( FALSE );
		}
	}
	
	/**
	 * @param $moduleName
	 * @param bool $reusable
	 * @param bool $noThrow
	 * @return PBModule|null
	 * @throws Exception
	 * @throws PBException
	 */
	function PBModule( $moduleName, $reusable = TRUE, $noThrow = FALSE ) {
		static $_attachedModule = [];
		
		
		if ( is_a($moduleName, PBModule::class) ) {
			if ( $_attachedModule[$moduleName->id] )
				return $moduleName;
			else
			if ( !$noThrow ) {
				throw new PBException([
					'code' => -1,
					'msg' => "Given module is not a center-controlled module!"
				]);
			}
			else {
				return NULL;
			}
		}
		
		
		
		if ( !empty($_attachedModule[$moduleName]) ) {
			$module = $_attachedModule[ $moduleName ];

			if ( ($moduleName != $module->id) && !$reusable ) {
				$module = NULL;
			}
		}


		try {
			if ( empty($module) ) {
				$module	  = PBKernel::SYS()->acquireModule( $moduleName );
				$moduleId = $module->id;
				$_attachedModule[ $moduleId ] = $module;
	
				if ( $reusable ) $_attachedModule[ $moduleName ] = $module;
			}
	
			return $module;
		}
		catch( Exception $e ) {
			if ($noThrow) return NULL;
			throw $e;
		}
	}
