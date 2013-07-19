<?php include_once( dirname(__FILE__).'/common_header.php' ); ?>

<style type="text/css">

	td.column-price, 
	td.column-fees {
		text-align: right;
	}
	th.column-auction_title {
		width: 25%;
	}
	
	td.column-auction_title a.product_title_link {
		color: #555;
	}
	td.column-auction_title a.product_title_link:hover {
		/*color: #21759B;*/
		color: #D54E21;
	}

	.tablenav .actions a.wpl_job_button {
		display: inline-block;
		margin: 0;
		margin-top: 1px;
		margin-right: 5px;
	}

</style>

<div class="wrap">
	<div class="icon32" style="background: url(<?php echo $wpl_plugin_url; ?>img/hammer-32x32.png) no-repeat;" id="wpl-icon"><br /></div>
	<h2><?php echo __('Listings','wplister') ?></h2>
	<?php echo $wpl_message ?>

	<!-- show listings table -->
	<?php $wpl_listingsTable->views(); ?>
    <!-- Forms are NOT created automatically, so you need to wrap the table in one to use features like bulk actions -->
    <form id="listings-filter" method="post" action="<?php echo $wpl_form_action; ?>" >
        <!-- For plugins, we also need to ensure that the form posts back to our current page -->
        <input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>" />
        <!-- Now we can render the completed list table -->
		<?php $wpl_listingsTable->search_box( __('Search','wplister'), 'listing-search-input' ); ?>
        <?php $wpl_listingsTable->display() ?>
    </form>

	<br style="clear:both;"/>


	<div class="submit" style="">

		<a id="btn_verify_all_prepared_items" class="btn_verify_all_prepared_items button-secondary wpl_job_button"
		   title="<?php echo __('Verify all prepared items with eBay and get listing fees.','wplister') ?>"
			><?php echo __('Verify all prepared items','wplister'); ?></a>

		<a id="btn_publish_all_verified_items" class="btn_publish_all_verified_items button-secondary wpl_job_button"
		   title="<?php echo __('Publish all verified items on eBay.','wplister') ?>"
			><?php echo __('Publish all verified items','wplister'); ?></a>

		<a id="btn_revise_all_changed_items" class="btn_revise_all_changed_items button-secondary wpl_job_button"
		   title="<?php echo __('Revise all changed items on eBay.','wplister') ?>"
			><?php echo __('Revise all changed items','wplister'); ?></a>

		<a id="btn_update_all_published_items" class="btn_update_all_published_items button-secondary wpl_job_button"
		   title="<?php echo __('Update all published items from eBay.','wplister') ?>"
			><?php echo __('Update all published items','wplister'); ?></a>

	</div>

<!--
	<br>

	<form method="post" action="<?php echo $wpl_form_action; ?>">
		<div class="submit" style="padding-top: 0; float: left;">
			<?php #wp_nonce_field( 'e2e_tools_page' ); ?>
			<input type="hidden" name="action" value="verify_all_prepared_items" />
			<input type="submit" value="<?php echo __('Verify all prepared items','wplister') ?>" name="submit" class="button-secondary"
				   title="<?php echo __('Verify all prepared items with eBay and get listing fees.','wplister') ?>">
		</div>
	</form>

	<form method="post" action="<?php echo $wpl_form_action; ?>">
		<div class="submit" style="padding-top: 0; float: left; padding-left:15px;">
			<?php #wp_nonce_field( 'e2e_tools_page' ); ?>
			<input type="hidden" name="action" value="publish_all_verified_items" />
			<input type="submit" value="<?php echo __('Publish all verified items','wplister') ?>" name="submit" class="button-secondary" 
				   title="<?php echo __('Publish all verified items on eBay.','wplister') ?>">
		</div>
	</form>

	<form method="post" action="<?php echo $wpl_form_action; ?>">
		<div class="submit" style="padding-top: 0; float: left; padding-left:15px;">
			<?php #wp_nonce_field( 'e2e_tools_page' ); ?>
			<input type="hidden" name="action" value="revise_all_changed_items" />
			<input type="submit" value="<?php echo __('Revise all changed items','wplister') ?>" name="submit" class="button-secondary" 
				   title="<?php echo __('Revise all changed items on eBay.','wplister') ?>">
		</div>
	</form>

	<form method="post" action="<?php echo $wpl_form_action; ?>">
		<div class="submit" style="padding-top: 0; float: left; padding-left:15px;">
			<?php #wp_nonce_field( 'e2e_tools_page' ); ?>
			<input type="hidden" name="action" value="update_all_published_items" />
			<input type="submit" value="<?php echo __('Update all published items','wplister') ?>" name="submit" class="button-secondary" 
				   title="<?php echo __('Update all published items from eBay.','wplister') ?>">
		</div>
	</form>
-->

	<script type="text/javascript">
		jQuery( document ).ready(
			function () {
		
				// ask again before ending items
				jQuery('.row-actions .end_item a').on('click', function() {
					return confirm("<?php echo __('Are you sure you want to end this listing?.','wplister') ?>");
				})
	
				// ask again before relisting items
				jQuery('.row-actions .relist a').on('click', function() {
					return confirm("<?php echo __('Are you sure you want to relist this ended listing?.','wplister') ?>");
				})
	
				// ask again before deleting items
				jQuery('.row-actions .delete a').on('click', function() {
					return confirm("<?php echo __('Are you sure you want to remove this listing from WP-Lister?.','wplister') ?>");
				})
				jQuery('#wpl_dupe_details a.delete').on('click', function() {
					return confirm("<?php echo __('Are you sure you want to remove this listing from WP-Lister?.','wplister') ?>");
				})
	
			}
		);
	
	</script>


	<?php if ( @$_GET['action'] == 'verifyPreparedItemsNow' ) : ?>
		<script type="text/javascript">
			jQuery( document ).ready( function () {	
				// auto start verify job
				setTimeout(function() {
					jQuery('#btn_verify_all_prepared_items').click();
				}, 1000); // delays 1 sec
			});
		</script>
	<?php endif; ?>


</div>