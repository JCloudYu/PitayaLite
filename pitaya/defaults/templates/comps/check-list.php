<?php
	/*
		check-list = {
			"tmplId":@uuid(ver:4),
			"options":@array(ref:@select-option),
			"columnStyle":[@string],
			"group":@string,
			"emptyStr":@string,
			"dataType":@string,
			"attr":[@string]
		}
		select-option = {
			"checked":@bool,
			"disabled"":@bool,
			"value":@string,
			"title":@string,
			"label":@string
		}
	*/

	$columnStyle = $columnStyle?: [];
	data_fuse( $columnStyle, [
		'float: left;',
		'margin-bottom: 5px;',
		'padding-right: 10px;'
	] );
	$columnStyle 	= implode( ' ', $columnStyle );
	$dataType 		= empty( $dataType )? 'raw': $dataType;
	$group 			= empty( $group )? $tmplId: $group;
	$group 			= "name='{$group}'";
	$attr 			= implode( ' ', $attr );
?>
<div id="<?=@$tmplId?>" <?=$attr?>><div class='clearfix'>
<?php
	if( count( $options ) <= 0 )
		echo "<div style='text-align:center'><span>{$emptyStr}</span></div>";
	else
	{
		foreach( @$options as $option ) {
			if ( is_array($option) ) $option = stdClass($option);
			if ( !is_a( $option, stdClass::class ) ) continue;


			$value 		= CAST( @$option->value, $dataType );
			$disabled 	= CAST( @$option->disabled, 'boolean' ) ? 'disabled': '';
			$checked 	= CAST( @$option->checked, 'boolean' ) ? 'checked' : '';


			echo "<div style='{$columnStyle}'><input type='checkbox' {$group} value='{$value}' {$disabled} {$checked} rel='{$tmplId}' /><span style='margin-left:5px;'>{$option->label}</span></div>";
		}
	}
?>
</div></div>