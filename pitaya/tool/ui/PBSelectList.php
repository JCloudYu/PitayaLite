<?php
	class PBSelectList extends PBObject
	{
		const ALLOWED_SELECT_TYPES = 'checkbox,radio';

		private $_dataType = array();
		private $_data = array();
		private $_attr = array();

		private $_identifier = '';

		private $_emptyNotifier = '';

		private $_renderHeader = TRUE;

		public function __construct()
		{
			static $instCounter = 0;
			$this->_identifier = substr(md5(uniqid() . ++$instCounter), 0, 16);
			$this->_emptyNotifier = 'Empty';
		}



		public function __set_identifier($value) { $this->_identifier = $value; }
		public function __get_identifier() { return $this->_identifier; }



		public function __set_dataType($value) { $this->_dataType = $value; }
		public function __get_dataType() { return $this->_dataType; }



		public function __set_data($value) { $this->_data = $value; }
		public function __get_data() { return $this->_data; }
		public function addData($data) { $this->_data[] = $data; }



		public function __set_attr($value) { $this->_attr = $value; }
		public function __get_attr($value) { return $this->_attr; }
		public function addAttr($attrStr) { $this->_attr[] = $attrStr; }


		public function __set_emptyStr($value) { $this->_emptyNotifier = $value; }
		public function __get_emptyStr() { return $this->_emptyNotifier; }


		public function __get_html( $force = FALSE ) {
			static $cache = NULL;

			if ( !empty($cache) && !$force ) return $cache;
			return $cache = $this->render();
		}

		public function render()
		{
			$dataProp = array();

			@$dataProp['type'] 		= (in_array(strtolower(@$this->_dataType['column-type']), explode(',', self::ALLOWED_SELECT_TYPES))) ?
				strtolower(@$this->_dataType['column-type']) : 'checkbox';

			@$dataProp['data-type']	= (empty($this->_dataType['data-type'])) ? 'raw' : $this->_dataType['data-type'];
			@$dataProp['width'] 	= (empty($this->_dataType['width'])) ? '' : $this->_dataType['width'];
			@$dataProp['align'] 	= (empty($this->_dataType['align'])) ? '' : $this->_dataType['align'];
			@$dataProp['style'] 	= (empty($this->_dataType['style'])) ? '' : $this->_dataType['style'];
			@$dataProp['group']		= (empty($this->_dataType['group'])) ? '' : $this->_dataType['group'];


			$body = '';
			if (count(($this->_data)) > 0)
			{
				foreach ($this->_data as $selOption)
				{
					$selHTML = '';
					$columnStyle = array();

					$type		= $dataProp['data-type'];
					$width		= $dataProp['width'];
					$align		= $dataProp['align'];
					$style		= $dataProp['style'];
					$disabled	= '';
					$checked	= '';
					$group		= $dataProp['group'];

					if (is_array(@$selOption))
					{
						$value  	= (isset($selOption['value'])) ? CAST(@$selOption['value'], $type) : '';
						$label		= (isset($selOption['label'])) ? $selOption['label'] : $value;
						$checked	= (CAST(@$selOption['checked'], 'boolean')) ? 'checked' : '';
						$disabled	= (CAST(@$selOption['disabled'], 'boolean')) ? 'disabled' : '';
						$align		= (isset($selOption['align'])) ? $selOption['align'] : $align;
						$style		= (isset($selOption['style'])) ? $selOption['style'] : $style;
						$group		= (isset($selOption['group'])) ? $selOption['group'] : $group;
					}
					else
					{
						$value = CAST(@$selOption, $type);
						$label = $value;
					}


					if (!empty($align)) $columnStyle[] = "text-align:{$align}";
					if (!empty($width)) $columnStyle[] = "width:" . ((is_numeric($width)) ? "{$width}px" : $width);
					if (!empty($style)) $columnStyle[] = $style;


					$columnStyle[] = "float:left";
					$columnStyle[] = "margin-bottom:5px";
					$columnStyle[] = "padding-right:10px";
					$columnStyle = implode('; ', $columnStyle);



					if (!empty($columnStyle)) $columnStyle = "style='{$columnStyle}'";
					if (empty($group)) $group = $this->_identifier;

					$group = "name='{$group}'";

					switch ($dataProp['type'])
					{
						case 'checkbox':
							$selHTML .= "<input type='checkbox' {$group} value='{$value}' {$disabled} {$checked} rel='{$this->_identifier}' /><span style='margin-left:5px;'>{$label}</span>";
							break;
						case 'radio':
							$selHTML .= "<input type='radio' {$group} value='{$value}' {$disabled} {$checked} rel='{$this->_identifier}' /><span style='margin-left:5px;'>{$label}</span>";
							break;
						default:
							$selHTML .= "<span>{$value}</span>";
							break;
					}


					$body .= "<div {$columnStyle}>{$selHTML}</div>";
				}
			}
			else
			{
				$body .= "<div style='text-align:center'><span>{$this->_emptyNotifier}</span></div>";
			}


			$attr = implode(' ', $this->_attr);
			return "<div {$attr} rel='{$this->_identifier}'><div class='clearfix'>{$body}</div></div>";

		}
	}
