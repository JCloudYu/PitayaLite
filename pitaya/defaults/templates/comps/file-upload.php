<div class="file-upload-wrapper">
	<div class="upload-label"><?=@$label?></div>
	<input id="<?=@$tmplId?>" type="file" accept='<?=@$accept?>' <?=(!!@$multiple ? 'multiple' : '')?>/>
</div>
<script type="application/javascript">
	(function() {
		var file = $( '#<?=$tmplId?>' );
		file.change(function(){
			if ( file.val() == "" ) {
				file.trigger( 'upload-cancelled' );
				return;
			}
			
			file.prop( 'disabled', true );
			file.trigger( 'upload-start' );

			var formData = new FormData();
			for( var i=0; i<file[0].files.length; i++ ) {
				formData.append( "<?=@$uploadName ?: 'uploaded'?>[" + i + "]", file[0].files[i] );
			}



			$.ajax({
				type: '<?=@$method ?: 'POST'?>',
				url: '<?=@$url?>',
				data: formData,
				contentType: false,
				processData: false,
				dataType: 'json'
			})
			.done(function(data){
				file.trigger( data.status ? 'upload-fail': 'upload-success', data );
			})
			.fail(function(){
				file.trigger( 'upload-fail', {
					internal:true,
					data:Array.prototype.slice.call(arguments, 0)
				});
			})
			.always(function(){
				file.trigger( 'upload-end' );
				file.val( '' );
				file.prop( 'disabled', false );
			});
		});
	})();
</script>