<?php
	/**
	 * Class PBTmplRenderer
	 * @property-read string $tmplId
	 * @property-read mixed $vars
	 */
	class PBTmplRenderer {
		private static $_tplPath = NULL;
		public static function SetTplPath( $path ) {
			self::$_tplPath = "{$path}";
		}
		public static function Tpl( $tmplName, $basePath = NULL ) {
			return new PBTmplRenderer( $tmplName, $basePath );
		}
	
	
		private $_tplBasePath = "";
		private $_tplName = "";
		private $_identity = '';
		private function __construct( $tmplName, $basePath ) {
			$this->_tplBasePath = $basePath ?: self::$_tplPath ?: path( 'defaults.templates' );
			$this->_tplName	 = $tmplName;
			$this->_identity = UUID();
		}
		public function __toString() { return $this(); }
		public function __invoke( $output = FALSE ) {
			$path = str_replace( '.', '/', $this->_tplName );
			$scriptPath = "{$this->_tplBasePath}/{$path}.php";
			if (!$output) ob_start();
			$results = self::Render( $scriptPath, data_merge(
				$this->_variables,
				[ 'tmplId' => $this->_identity ]
			));
			data_fuse( $this->_variables, $results );
			return (!$output) ? ob_get_clean() : "";
		}
		
		private $_variables = [];
		public function __set( $name, $value ) {
			$this->_variables[ $name ] = $value;
		}
		public function &__get($name) {
			if ( $name == "tmplId" ) {
				$result = $this->_identity;
			}
			else
			if ( $name == "vars" ) {
				$result = $this->_variables;
			}
			else {
				$result = &$this->_variables[$name];
			}
			
			return $result;
		}
		
		private static function Render( $scriptPath, $variables = []) {
			extract( $variables, EXTR_OVERWRITE );
			$variables = [];
			require $scriptPath;
			return $variables;
		}
	}

	function PBTmplRenderer( $tmplName, $basePath = NULL ) {
		return PBTmplRenderer::Tpl( $tmplName, $basePath );
	}