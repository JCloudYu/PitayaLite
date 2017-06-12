<?php
	using( 'modules.PBOutputCtrl' );
	
	class PBTemplateOut extends PBHttpOut {
		public function execute( $chainData ) {
			$template = PBTmplRenderer::Tpl( @$this->data->tmplName, @$this->data->tmplPath );
			unset( $this->data->initData );
			unset( $this->data->tmplName );
			unset( $this->data->tmplPath );
			
			
			$tplData = data_merge( $this->data, $chainData ?: [], self::$_outputData ?: [] );
			foreach( $tplData as $field => $value ) $template->{$field} = $value;
			
			parent::execute(NULL); $template(TRUE);
		}
	}
	class_alias( 'PBTemplateOut', 'tmpl' );

