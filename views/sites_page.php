<?php include_once( dirname(__FILE__).'/common_header.php' ); ?>

<style type="text/css">

	th.column-blog_title {
		width: 25%;
	}

</style>

<div class="wrap">
	<div class="icon32" style="background: url(<?php echo $wpl_plugin_url; ?>img/hammer-32x32.png) no-repeat;" id="wpl-icon"><br /></div>
	<h2><?php echo __('WP-Lister Network Sites','wplister') ?></h2>
	<?php echo $wpl_message ?>


	<!-- show profiles table -->
    <!-- Forms are NOT created automatically, so you need to wrap the table in one to use features like bulk actions -->
    <form id="profiles-filter" method="post" action="<?php echo $wpl_form_action; ?>" >
        <!-- For plugins, we also need to ensure that the form posts back to our current page -->
        <input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>" />
        <!-- Now we can render the completed list table -->
        <?php $wpl_sitesTable->display() ?>
    </form>

	<br style="clear:both;"/>

	<?php
	// echo "<pre>";print_r($wpl_sitesTable->_column_headers);echo"</pre>";#die();
	?>

	<!--
	<form method="post" action="<?php echo $wpl_form_action; ?>">
		<div class="submit" style="padding-top: 0; float: left;">
			<?php #wp_nonce_field( 'e2e_tools_page' ); ?>
			<input type="hidden" name="action" value="update_sites" />
			<input type="submit" value="<?php echo __('Update sites','wplister') ?>" name="submit" class="button"
				   title="<?php echo __('Update recent sites from eBay.','wplister') ?>">
		</div>
	</form>
	-->


</div>