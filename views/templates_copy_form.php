<style type="text/css">

#copy_tpl label.text_label {
	display: block;
	float: left;
	width: 33%;
	margin: 1px;
	padding: 3px;
	clear: both;
}
#copy_tpl input.text_input, 
#copy_tpl textarea, 
#copy_tpl select.select {
	width: 64%;
	margin-bottom: 5px;
	/*padding: 3px 8px;*/
}
#copy_tpl input.price_input {
	width: 12%;
	margin-bottom: 5px;
	/*padding: 3px 8px;*/
}

#copy_tpl input.checkbox_input {
	margin-left: 15px;
	/*padding: 3px 8px;*/
}

</style>


<form id="copy_tpl">

	<h2><?php echo __('Duplicate Template','wplister') ?></h2>


	<label class="text_label" for="template_name"><?php echo __('Name','wplister'); ?></label>
	<input type="text" id="template_name" name="wpl_e2e_template_name" value="<?php echo $wpl_item['template_name']; ?> (copy)" class="text_input"/>

	<label class="text_label" for="template_description"><?php echo __('Description','wplister'); ?></label>
	<input type="text" id="template_description" name="wpl_e2e_template_description" value="<?php echo $wpl_item['template_description']; ?>" class="text_input"/>


	<div class="submit" style="padding-top: 0; float: right;">
		<input type="hidden" id="template_id" name="wpl_e2e_template_id" value="<?php echo $wpl_item['template_id']; ?>"/>
		<input type="submit" id="btn_save_as_new" value="<?php echo __('Duplicate Template','wplister'); ?>" name="duplicate" class="button-primary">
	</div>


</form>


<script type="text/javascript">

	jQuery('#btn_save_as_new').click(function(e) {
		// console.log('btn_save_as_new',e);
		return wpl_submit_form( true );
	});

	function wpl_submit_form( save_as_new ) {

	    var data = jQuery('#copy_tpl').serialize();
	    if ( save_as_new ) data += '&save_as_new=true';

	    jQuery.ajax({
	        type: 'post',
	        url: ajaxurl,
	        // dataType: 'json',
	        data: data + '&action=wpl_duplicate_template',
	        // cache: false,
	        success: function(response){

			    if ( response == 'success' ) {
			    	tb_remove();
			    	location.href = 'admin.php?page=wplister-templates';
			    } else {
		            var msg = '<div class="updated">'+response+'</div>';
				    jQuery('#wpl_duplicate_tpl_container').append( msg );
		            console.log(response);
			    }

	        }
	    }); 
	    return false;
	}

</script>