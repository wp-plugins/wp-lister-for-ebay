<?php include_once( dirname(__FILE__).'/common_header.php' ); ?>

<style type="text/css">

	th.column-details {
		width: 25%;
	}

</style>

<div class="wrap">
	<div class="icon32" style="background: url(<?php echo $wpl_plugin_url; ?>img/hammer-32x32.png) no-repeat;" id="wpl-icon"><br /></div>
	<h2><?php echo __('Orders','wplister') ?></h2>
	<?php echo $wpl_message ?>


	<!-- show profiles table -->
	<?php $wpl_ordersTable->views(); ?>
    <!-- Forms are NOT created automatically, so you need to wrap the table in one to use features like bulk actions -->
    <form id="profiles-filter" method="post" action="<?php echo $wpl_form_action; ?>" >
        <!-- For plugins, we also need to ensure that the form posts back to our current page -->
        <input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>" />
        <!-- Now we can render the completed list table -->
		<?php $wpl_ordersTable->search_box( __('Search','wplister'), 'order-search-input' ); ?>
        <?php $wpl_ordersTable->display() ?>
    </form>

	<br style="clear:both;"/>


	<?php if ( 'order' == get_option( 'wplister_ebay_update_mode', 'order' ) ) : ?>
	
		<p>
		<?php if ( wp_next_scheduled( 'wplister_update_auctions' ) ) : ?>
			<?php echo __('Next scheduled update','wplister'); ?>: 
			<?php echo human_time_diff( wp_next_scheduled( 'wplister_update_auctions' ), current_time('timestamp',1) ) ?>
			<?php echo wp_next_scheduled( 'wplister_update_auctions' ) < current_time('timestamp',1) ? 'ago' : '' ?>
		<?php else: ?>
			<?php echo __('Automatic background updates are currently disabled.','wplister'); ?>
		<?php endif; ?>
		</p>

		<div class="submit" style="padding-top: 0; float: right;">
			<a href="admin.php?page=wplister-transactions" class="button" title="View transactions from before switching to orders"><?php echo __('View transactions','wplister') ?></a>
		</div>

		<form method="post" action="<?php echo $wpl_form_action; ?>">
			<p>
				<?php #wp_nonce_field( 'e2e_tools_page' ); ?>
				<input type="hidden" name="action" value="update_orders" />
				<input type="submit" value="<?php echo __('Update orders','wplister') ?>" name="submit" class="button"
					   title="<?php echo __('Update recent orders from eBay.','wplister') ?>">
				&nbsp; <a href="#" onclick="jQuery('#wpl_advanced_order_options').toggle();return false;" class="button"><?php echo __('Options','wplister') ?></a>
			</p>

			<div id="wpl_advanced_order_options" class="submit" style="display:none; padding-top: 0; float:left; clear:both">
				<label for="wpl_number_of_days" class="text_label"><?php echo __('Update timespan','wplister'); ?></label>
				<select name="wpl_number_of_days" id="wpl_number_of_days" 
						class="required-entry select" style="width:auto;"
						>
					<option value=""   ><?php echo __('-- since last updated order --','wplister'); ?></option>
					<option value="1"  >1  <?php echo __('day','wplister'); ?></option>
					<option value="2"  >2  <?php echo __('days','wplister'); ?></option>
					<option value="3"  >3  <?php echo __('days','wplister'); ?></option>
					<option value="5"  >5  <?php echo __('days','wplister'); ?></option>
					<option value="7"  >7  <?php echo __('days','wplister'); ?></option>
					<option value="10" >10 <?php echo __('days','wplister'); ?></option>
					<option value="14" >14 <?php echo __('days','wplister'); ?></option>
					<option value="28" >28 <?php echo __('days','wplister'); ?></option>
				</select>
			</div>
		</form>

	<?php endif; ?>


</div>