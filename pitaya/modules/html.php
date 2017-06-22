<?php
	using( 'modules.PBOutputCtrl' );
	
	class PBHtmlOut extends PBHttpOut {
		
		public function execute( $chainData ) {
		
			$outputCtnt = self::$_outputData ?: $chainData;
		
		
			$js = [
				'prepend'		=> '',
				'append'		=> '',
				'last'			=> '',
				'file prepend'	=> '',
				'file append'	=> ''
			];
			$css = [
				'inline'	=> '',
				'file'		=> ''
			];
			$header = '';


			// region [ Inline JS ]
			$js['prepend']	= empty($this->_js[ 'prepend' ]) ? '' : "<script type='application/javascript'>" . implode('', $this->_js['prepend']) . "</script>";
			$js['append']	= empty($this->_js[ 'append' ]) ? '' : "<script type='application/javascript'>" . implode('', $this->_js['append']) . "</script>";
			$js['last']		= empty($this->_js[ 'last' ]) ? '' : "<script type='application/javascript'>" . implode('', $this->_js['last']) . "</script>";
			// endregion
			// region [ JS Files ]
			$pathList = [];
			foreach ($this->_jsFiles[ 'prepend' ] as $fileDesc )
			{
				if ( in_array( $fileDesc[ 'path' ], $pathList ) ) continue;
				if ( !is_array( $fileDesc['attr'] ) )
					$attributes = $fileDesc['attr'];
				else
					$attributes = self::GenAttribute($fileDesc['attr']);
				
				$js['file prepend'] .= "<script type='application/javascript' src='{$fileDesc[ 'path' ]}' {$attributes}></script>";
			}



			foreach ($this->_jsFiles[ 'append' ] as $fileDesc )
			{
				if ( in_array( $fileDesc[ 'path' ], $pathList ) ) continue;
				if ( !is_array( $fileDesc['attr'] ) )
					$attributes = $fileDesc['attr'];
				else
					$attributes = self::GenAttribute($fileDesc['attr']);
				
				$js['file append'] .= "<script type='application/javascript' src='{$fileDesc[ 'path' ]}' {$attributes}></script>";
			}
			// endregion
			
			// region [ Inline CSS ]
			$css['inline']	= empty($this->_css) ? '' : "<style type='text/css'>" . implode('', $this->_css) . "</style>";
			// endregion
			// region [ CSS Files ]
			foreach ($this->_cssFiles as $fileDesc )
			{
				if ( in_array( $fileDesc[ 'path' ], $pathList ) ) continue;
				if ( !is_array( $fileDesc['attr'] ) )
					$attributes = $fileDesc['attr'];
				else
					$attributes = self::GenAttribute($fileDesc['attr']);
				
				$css['file'] .= "<link href='{$fileDesc[ 'path' ]}' type='text/css' rel='stylesheet' {$attributes} />";
			}
			// endregion

			// region [ Other Header Contents ]
			$header	 = implode('', $this->_header);
			$metaTag = implode('', ary_filter( $this->_meta, function( $meta, &$idx ) {
				$attributes = self::GenAttribute($meta);
				return "<meta {$attributes}/>";
			}));
			// endregion



			// region [ Generate Body and Html Attributes ]
			$bodyAttr = self::GenAttribute( @$this->_elm[ 'body' ] );
			$htmlAttr = self::GenAttribute( @$this->_elm[ 'html' ] );
			// endregion
			// region [ Generate Body Content ]
			$contentWrapper = call_user_func(function( $baseBody, $elm ) {
				if ( empty($elm[ 'page' ]) || !is_array( $elm[ 'page' ] ) )
					return $baseBody;

				$attributes = self::GenAttribute( $elm[ 'page' ] );
				return empty( $attributes ) ? $baseBody : "<div {$attributes}>{$baseBody}</div>";
			}, "{$outputCtnt}", $this->_elm);
			// endregion



			PBHttpOutput::ContentType( "text/html" );
			parent::execute( "<!DOCTYPE html><html {$htmlAttr}><head>{$metaTag}{$header}{$js['file prepend']}{$js['prepend']}{$css['file']}{$css['inline']}</head><body {$bodyAttr}>{$contentWrapper}{$js['append']}{$js['file append']}{$js['last']}</body></html>" );
		}
		
		// region [ Private Properties ]
		private $localResourcePath = '';

		private $_js = array('prepend' => [], 'append' => [], 'last' => []);
		private $_css = [];

		private $_jsFiles = array( 'prepend' => [], 'append' => [] );
		private $_cssFiles = [];

		private $_header = [];

		private $_prop	= [];
		private $_elm	= [];
		private $_meta	= [];
		// endregion
		// region [ Public  Properties ]
		public function __get_js() { return $this->_js; }
		public function __get_css() { return $this->_css; }
		public function __set_jsBegin($value) { $this->addJS($value, FALSE); }
		public function __set_js($value) { $this->addJS($value, TRUE); }
		public function __set_jsLast($value) { $this->addJS($value, "LAST"); }
		public function __set_css($value) { $this->addCSS($value); }
		
		
		public function __get_jsFiles() { return $this->_jsFiles; }
		public function __get_cssFiles() { return $this->_cssFiles; }
		public function __set_file( $value ) {
			if ( $value instanceof stdClass ) $value = (array)$value;
			if ( !is_array($value) ) return;
			
			$this->addFile( @$value[ 'path' ], @$value[ 'type' ], @$value[ 'attr' ] );
		}
		public function __set_files( $value ) {
			if ( !is_array($value) ) return;
			foreach( $value as $fileDes ) $this->__set_file( $fileDes );
		}
		public function __get_defaultResourcePath() { return $this->localResourcePath; }
		public function __set_defaultResourcePath($value) { $this->localResourcePath = "{$value}"; }
		
		
		public function __set_header($value) { $this->_header[] = $value; }
		public function &__get_html() {
			if ( empty($this->_elm[ 'html' ]) )
				$this->_elm[ 'html' ] = [];

			return $this->_elm[ 'html' ];
		}
		public function &__get_body() {
			if ( empty($this->_elm[ 'body' ]) )
				$this->_elm[ 'body' ] = [];

			return $this->_elm[ 'body' ];
		}
		public function &__get_page() {
			if ( empty($this->_elm[ 'page' ]) )
				$this->_elm[ 'page' ] = [];

			return $this->_elm[ 'page' ];
		}
		public function &__get_meta() {
			return $this->_meta;
		}
		// endregion
		
		// region [ Public Methods ]
		public function addJS($script, $append = TRUE)
		{
			if ( is_string($append) && CAST( $append, 'string upper-case' ) == "LAST" )
				$pos = "last";
			else
				$pos = ($append) ? 'append' : 'prepend';

			$this->_js[$pos][] = $script;
		}
		public function addCSS($css) { $this->_css[] = $css; }
		
		public function addFile($name, $type, $attr = []) {
		
			$type  = explode( ' ', strtolower($type) );
			$path  = in_array( 'external', $type ) ? "{$name}" : "{$this->localResourcePath}{$name}";
			$order = in_array( 'append', $type   ) ? 'append' : 'prepend';

			switch (strtolower($type[0]))
			{
				case 'js':
					$this->_jsFiles[ $order ][] = [ 'path' => $path, 'attr' => $attr ];
					break;

				case 'css':
					$this->_cssFiles[] = [ 'path' => $path, 'attr' => $attr ];
					break;

				default:
					break;
			}
		}
		public function removeFile($name, $type) {
		
			$type = explode(' ', strtolower($type));
			$path = in_array('external', $type) ? "{$name}" : "{$this->localResourcePath}{$name}";

			switch (strtolower($type[0]))
			{
				case 'js':
					foreach ( array( 'prepend', 'append' ) as $order )
					foreach ( $this->_jsFiles[ $order ] as $idx => $fileDesc )
					{
						if ( $fileDesc[ 'path' ] == $path )
							unset( $this->_jsFiles[ $order ][ $idx ] );
					}
					break;

				case 'css':
					foreach ( $this->_cssFiles as $idx => $fileDesc )
					{
						if ( $fileDesc[ 'path' ] == $path )
							unset( $this->_cssFiles[ $idx ] );
					}
					break;

				default:
					break;
			}
		}
		public function replaceFile($name, $replacement, $type)
		{
			$type = explode( ' ', strtolower($type) );
			$path = in_array( 'external', $type ) ? "{$name}" : "{$this->localResourcePath}{$name}";
			$rep  = in_array( 'external', $type ) ? "{$replacement}" : "{$this->localResourcePath}{$replacement}";

			switch (strtolower($type[0]))
			{
				case 'js':
					foreach ( array( 'prepend', 'append' ) as $order )
					foreach ( $this->_jsFiles[ $order ] as $idx => $fileDesc )
						if ( $fileDesc[ 'path' ] == $path )
							$this->_jsFiles[ $order ][ $idx ][ 'path' ] = $rep;
					break;

				case 'css':
					foreach ( $this->_cssFiles as $idx => $fileDesc )
						if ( $fileDesc[ 'path' ] == $path )
							$this->_cssFiles[ $idx ][ 'path' ] = $rep;
					break;

				default:
					break;
			}
		}
		
		public static function GenAttribute( $attrList ){
			if ( is_a( $attrList, stdClass::class ) )
				$attrList = (array)$attrList;
				
			if ( !is_array($attrList) ) return "";
		
		
		
			$buff = [];
			foreach( $attrList as $attr => $value ) {
				if ( !is_string( $attr ) ) {
					$attr  = $value;
					$value = FALSE;
				}
				
				$attr = str_replace( [ "\"", "'", "<", ">", "/", "=" ], "", $attr );
				if ( $attr == "" ) continue;
				
				if ( $value === FALSE )
					$buff[] = "{$attr}";
				else
				{
					$value = htmlspecialchars( $value );
					$buff[] = "{$attr}=\"{$value}\"";
				}
			}
			
			return implode( " ", $buff );
		}
		// endregion
	}
	class_alias( 'PBHtmlOut', 'html' );
