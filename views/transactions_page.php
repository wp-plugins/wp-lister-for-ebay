<?php include_once( dirname(__FILE__).'/common_header.php' ); ?>

<style type="text/css">

	th.column-item_title {
		width: 25%;
	}

</style>

<div class="wrap">
	<div class="icon32" style="background: url(<?php echo $wpl_plugin_url; ?>img/hammer-32x32.png) no-repeat;" id="wpl-icon"><br /></div>
	<h2><?php echo __('Transactions','wplister') ?></h2>
	<?php echo $wpl_message ?>


	<!-- show profiles table -->
	<?php $wpl_transactionsTable->views(); ?>
    <!-- Forms are NOT created automatically, so you need to wrap the table in one to use features like bulk actions -->
    <form id="profiles-filter" method="post" action="<?php echo $wpl_form_action; ?>" >
        <!-- For plugins, we also need to ensure that the form posts back to our current page -->
        <input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>" />
        <!-- Now we can render the completed list table -->
		<?php $wpl_transactionsTable->search_box( __('Search','wplister'), 'transaction-search-input' ); ?>
        <?php $wpl_transactionsTable->display() ?>
    </form>

	<br style="clear:both;"/>


	<?php if ( 'transaction' == get_option( 'wplister_ebay_update_mode', 'order' ) ) : ?>
	
		<?php if ( wp_next_scheduled( 'wplister_update_auctions' ) ) : ?>
		<p>
			<?php echo __('Next scheduled update','wplister'); ?>: 
			<?php echo human_time_diff( wp_next_scheduled( 'wplister_update_auctions' ), current_time('timestamp',1) ) ?>
		</p>
		<?php endif; ?>

		<form method="post" action="<?php echo $wpl_form_action; ?>">
			<p>
				<?php #wp_nonce_field( 'e2e_tools_page' ); ?>
				<input type="hidden" name="action" value="update_transactions" />
				<input type="submit" value="<?php echo __('Update transactions','wplister') ?>" name="submit" class="button"
					   title="<?php echo __('Update recent transactions from eBay.','wplister') ?>">
			</p>
		</form>

	<?php endif; ?>


</div>