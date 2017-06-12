<?php
	/*
		check-list = {
			"tmplId":@uuid(ver:4),
			"options":@array(ref:@select-option),
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

	$type	  = @$type ?: 'raw';
	$emptyStr = @$emptyStr ?: '';
	$group	  = @$group ?: @$tmplId ?: UUIDv4();
	$options  = is_array(@$options) ? $options : [];
	
		
?>
<div id="<?=@$tmplId?>" class='data-list clearfix'><?php
	if( count( $options ) <= 0 ) {
		echo "<div style='text-align:center'><span>{$emptyStr}</span></div>";
	}
	else {
		foreach( @$options as $option ) {
			if ( is_array($option) ) $option = stdClass($option);
			if ( !is_a( $option, stdClass::class ) ) continue;


			$value		= CAST( @$option->value, $type );
			$disabled	= @!!$option->disabled ? 'disabled': '';
			$checked	= @!!$option->checked ? 'checked' : '';
			
			echo @"<label data-rel='{$tmplId}'><input type='checkbox' name='{$group}' value='{$value}' {$disabled} {$checked} />{$option->label}</label>";
		}
	}
?></div>