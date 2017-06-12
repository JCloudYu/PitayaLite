<?php
	/*
		select-list = {
			"tmplId":@uuid(ver:4),
			"multiple":@bool,
			"options":@array(ref:@select-option)
		}
	
		select-option = {
			"selected":@bool,
			"value":@string,
			"title":@string,
			"label":@string
		}
	*/
	$options = !is_array($options) ? [] : $options;
	$multiple = empty($multiple) ? '' : 'multiple';
?>
<select id="<?=@$tmplId?>" <?=$multiple?>>
	<?php
		foreach( @$options as $option ) {
			if ( is_array($option) ) $option = stdClass($option);
			if ( !is_a( $option, stdClass::class ) ) continue;
			
			
			
			$selected = !empty($option->selected) ? 'selected' : '';
			echo @"<option value='{$option->value}' title='{$option->title}' {$selected}>{$option->label}</option>";
		}
	?>
</select>
