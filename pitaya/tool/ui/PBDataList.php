<?php
	class PBDataList extends PBObject
	{
		const ALLOWED_COLUMN_TYPES = 'checkbox,radio';

		private $_columns = array();
		private $_data = array();
		private $_attr = array();

		private $_identifier	= '';
		private $_emptyNotifier = '';
		private $_renderHeader	= TRUE;

		private $_resultCache	= NULL;

		public static function Column( $title, $colType = '', $dataType = '', $align = '', $ctntAlign = '', $width = '', $style = '', $attr = '' )
		{
			$data = array(
				'title'			=> '',
				'column-type'	=> '',
				'data-type'		=> '',
				'align'			=> '',
				'content-align'	=> '',
				'width'			=> '',
				'style'			=> '',
				'attr'			=> ''
			);

			if ( func_num_args() == 1 && is_array($title) )
			{
				foreach ( $title as $field => $val ) $data[ $field ] = $val;
				return $data;
			}
			else
			{
				return array(
					'title'			=> $title,
					'column-type'	=> $colType,
					'data-type'		=> $dataType,
					'align'			=> $align,
					'content-align'	=> $ctntAlign,
					'width'			=> $width,
					'style'			=> $style,
					'attr'			=> $attr
				);
			}

		}

		public function __construct()
		{
			static $instCounter = 0;
			$this->_identifier = substr(md5(uniqid() . ++$instCounter), 0, 16);
			$this->_emptyNotifier = 'Empty';
		}



		public function __set_identifier($value) { $this->_identifier = $value; }
		public function __get_identifier() { return $this->_identifier; }



		public function __set_header($value) { $this->_columns = $value; }
		public function __get_header() { return $this->_columns; }
		public function addColumn($column) { $this->_columns[] = $column; }



		public function __set_data($value) { $this->_data = $value; }
		public function __get_data() { return $this->_data; }
		public function addData($data) { $this->_data[] = $data; }



		public function __set_attr($value) { $this->_attr = $value; }
		public function __get_attr($value) { return $this->_attr; }
		public function addAttr($attrStr) { $this->_attr[] = $attrStr; }



		public function __set_renderHeader($value) { $this->_renderHeader = $value; }
		public function __get_renderHeader() { return $this->_renderHeader; }



		public function __set_emptyStr($value) { $this->_emptyNotifier = $value; }
		public function __get_emptyStr() { return $this->_emptyNotifier; }



		public function __get_html() { return $this->render(); }
		public function __toString() { return $this->render(); }
		public function render( $updateCache = FALSE )
		{
			if ( $this->_resultCache !== NULL && !$updateCache ) return $this->_resultCache;

			$columns = array();

			$header = '';
			foreach ($this->_columns as $column)
			{
				$colProp = array();
				@$colProp['column-type'] = (in_array(strtolower(@$column['column-type']), explode(',', self::ALLOWED_COLUMN_TYPES))) ?
										  strtolower(@$column['column-type']) : '';

				@$colProp['data-type']		= (empty($column['data-type'])) ? 'raw' : $column['data-type'];
				@$colProp['width']			= (empty($column['width'])) ? '' : "width=\"{$column['width']}\"";
				@$colProp['align']			= (empty($column['align'])) ? '' : "style='text-align:{$column['align']}'";
				@$colProp['content-align']	= (empty($column['content-align'])) ? '' : "style='text-align:{$column['content-align']}'";

				@$colProp['style']			= (empty($column['style'])) ? '' : $column['style'];
				@$colProp['group']			= (empty($column['group'])) ? '' : $column['group'];
				@$colProp['attr']			= (empty($column['attr'])) ? '' : $column['attr'];

				$columns[] = $colProp;


				if ($this->_renderHeader) @$header .= "<th {$colProp['width']} {$colProp['align']} {$colProp['attr']}>{$column['title']}</th>";
			}
			if ($this->_renderHeader) $header = empty($header) ? '' : "<thead><tr>{$header}</tr></thead>";


			$body = '';
			if (count(($this->_data)) > 0)
			{
				foreach ($this->_data as $rowData)
				{
					$rowHTML = '';
					foreach ($columns as $idx => $def)
					{
						$type			= $def['data-type'];
						$width			= $def['width'];
						$align			= empty($def['content-align']) ? $align : $def['content-align'];
						$style			= $def['style'];
						$disabled		= '';
						$group			= $def['group'];
						$attr			= '';

						$checked = '';

						if ( !is_array(@$rowData[$idx]) )
							$value = CAST( @$rowData[$idx], $type );
						else
						{
							$value		= (isset($rowData[$idx]['value'])) ? CAST( @$rowData[$idx]['value'], $type ) : '';
							$checked	= (CAST( @$rowData[$idx]['checked'], 'bool' )) ? 'checked' : '';
							$disabled	= (CAST( @$rowData[$idx]['disabled'], 'bool' )) ? 'disabled' : '';
							$align		= (isset($rowData[$idx]['align'])) ? $rowData[$idx]['align'] : $align;
							$style		= (isset($rowData[$idx]['style'])) ? $rowData[$idx]['style'] : $style;
							$group		= (isset($rowData[$idx]['group'])) ? $rowData[$idx]['group'] : $group;
							$attr		= (isset($rowData[$idx]['attr'])) ? $rowData[$idx]['attr'] : '';
						}



						if (!empty($style)) $style = "style='{$style}'";
						if (empty($group))  $group = $this->_identifier;

						$group = "name='{$group}'";

						switch ($def['column-type'])
						{
							case 'checkbox':
								$rowHTML .= "<td {$width} {$align} {$attr}><input type='checkbox' {$group} value='{$value}' {$disabled} {$checked} rel='{$this->_identifier}' /></td>";
								break;
							case 'radio':
								$rowHTML .= "<td {$width} {$align} {$attr}><input type='radio' {$group} value='{$value}' {$disabled} {$checked} rel='{$this->_identifier}' /></td>";
								break;
							default:
								$rowHTML .= "<td {$width} {$align} {$attr}><div {$style}>{$value}</div></td>";
								break;
						}
					}
					$body .= "<tr>{$rowHTML}</tr>";
				}
			}
			else
			{
				$numCols = count($columns) + 1;
				$body .= "<tr><td colspan='{$numCols}'><div style='text-align:center'>{$this->_emptyNotifier}</div></td></tr>";
			}


			$attr = implode(' ', $this->_attr);

			return $this->_resultCache = <<<HTML
				<table {$attr} rel='{$this->_identifier}'>
					{$header}
					<tbody>{$body}</tbody>
				</table>
HTML;
		}
	}
