<?php include_once( dirname(__FILE__).'/common_header.php' ); ?>

<style type="text/css">
	
	th.column-template_name {
		width: 65%;
	}

</style>

<div class="wrap">
	<div class="icon32" style="background: url(<?php echo $wpl_plugin_url; ?>img/hammer-32x32.png) no-repeat;" id="wpl-icon"><br /></div>
	<h2><?php echo __('Templates','wplister') ?> <a href="<?php echo $wpl_form_action; ?>&action=add_new_template" class="add-new-h2"><?php echo __('Add New','wplister') ?></a> </h2>
	<?php echo $wpl_message ?>


	<!-- show templates table -->
    <!-- Forms are NOT created automatically, so you need to wrap the table in one to use features like bulk actions -->
    <form id="templates-filter" method="get" action="<?php echo $wpl_form_action; ?>" >
        <!-- For plugins, we also need to ensure that the form posts back to our current page -->
        <input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>" />
        <!-- Now we can render the completed list table -->
        <?php $wpl_templatesTable->display() ?>
    </form>
	<br style="clear:both;"/>

    <form id="templates-add" method="get" action="<?php echo $wpl_form_action; ?>" >
        <!-- For plugins, we also need to ensure that the form posts back to our current page -->
        <input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>" />
        <input type="hidden" name="action" value="add_new_template" />

		<input type="submit" value="<?php echo __('Create new template','wplister') ?>" name="submit" class="button-secondary">
		&nbsp; <a href="#" onclick="jQuery('#templates-upload').show();return false;" class="button-secondary"><?php echo __('Upload existing template','wplister') ?></a>
    </form>
    <br style="clear:both;"/>


	<form id="templates-upload" enctype="multipart/form-data" method="post" action="<?php echo $wpl_form_action; ?>" style="display:none;">
        <input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>" />
        <input type="hidden" name="action" value="wpl_upload_template" />
		<?php wp_nonce_field( 'wpl_upload_template' ); ?>

	    <input type="file" name="fupload" />
		<input type="submit" value="<?php echo __('Upload','wplister') ?>" name="submit" class="button-secondary">
		<p>
			You can only upload a zipped folder containing the template files.
		</p>
	</form>

	<!--
	<p>
		debug info below:
	</p>
	-->

	<?php if ( get_option('wplister_log_level') > 5 ): ?>
	<pre><?php #print_r($wpl_templates); ?></pre>
	<?php endif; ?>

	<script type="text/javascript">
		jQuery( document ).ready(
			function () {
		
				// ask again before deleting
				jQuery('.row-actions .delete_listing_template a').on('click', function() {
					return confirm("<?php echo __('Are you sure you want to delete this item?.','wplister') ?>");
				})
	
			}
		);
	
	</script>

</div>