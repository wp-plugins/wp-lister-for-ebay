<?php include_once( dirname(__FILE__).'/common_header.php' ); ?>

<style type="text/css">

	td.column-price, 
	td.column-fees {
		text-align: right;
	}
	th.column-callname {
		width: 25%;
	}
	th.column-user {
		width: 10%;
	}
	th.column-success {
		width: 30%;
	}

	.widefat tbody th.check-column {
		padding-bottom: 0;
	}
</style>

<div class="wrap">
	<div class="icon32" style="background: url(<?php echo $wpl_plugin_url; ?>img/hammer-32x32.png) no-repeat;" id="wpl-icon"><br /></div>
	<h2><?php echo __('Logs','wplister') ?></h2>
	<?php echo $wpl_message ?>


	<!-- show log table -->
	<?php $wpl_logTable->views(); ?>
    <!-- Forms are NOT created automatically, so you need to wrap the table in one to use features like bulk actions -->
    <form id="profiles-filter" method="post" action="<?php echo $wpl_form_action; ?>" >
        <!-- For plugins, we also need to ensure that the form posts back to our current page -->
        <input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>" />
        <!-- Now we can render the completed list table -->
		<?php $wpl_logTable->search_box(__('Search','wplister'), 'log-search-input'); ?>
        <?php $wpl_logTable->display() ?>
    </form>

	<br style="clear:both;"/>



</div>