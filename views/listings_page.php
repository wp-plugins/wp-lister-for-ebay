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
	td.column-auction_title a.missing_product_title_link {
		color: #D54E21;
	}

	.tablenav .actions a.wpl_job_button {
		display: inline-block;
		margin: 0;
		margin-top: 1px;
		margin-right: 5px;
	}

	#TB_window table.variations_table {
		width: 99%
	}
	#TB_window table.variations_table th {
		border-bottom: 1px solid #aaa;
		padding: 4px 9px;
	}
	#TB_window table.variations_table td {
		border-bottom: 1px solid #ccc;
		padding: 4px 9px;
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

		<a id="btn_verify_all_prepared_items" class="btn_verify_all_prepared_items button wpl_job_button"
		   title="<?php echo __('Verify all prepared items with eBay and get listing fees.','wplister') ?>"
			><?php echo __('Verify all prepared items','wplister'); ?></a>

		<?php if ( current_user_can( 'publish_ebay_listings' ) ) : ?>

		<a id="btn_publish_all_verified_items" class="btn_publish_all_verified_items button wpl_job_button"
		   title="<?php echo __('Publish all verified items on eBay.','wplister') ?>"
			><?php echo __('Publish all verified items','wplister'); ?></a>

		<a id="btn_revise_all_changed_items" class="btn_revise_all_changed_items button wpl_job_button"
		   title="<?php echo __('Revise all changed items on eBay.','wplister') ?>"
			><?php echo __('Revise all changed items','wplister'); ?></a>

		<a id="btn_update_all_published_items" class="btn_update_all_published_items button wpl_job_button"
		   title="<?php echo __('Update all published items from eBay.','wplister') .' '. 'Note: This will only update the listing items in WP-Lister. Products in WooCommerce will not be affected.' ?>"
			><?php echo __('Update all published items','wplister'); ?></a>

		<?php endif; ?>

	</div>

<!--
	<br>

	<form method="post" action="<?php echo $wpl_form_action; ?>">
		<div class="submit" style="padding-top: 0; float: left;">
			<?php #wp_nonce_field( 'e2e_tools_page' ); ?>
			<input type="hidden" name="action" value="verify_all_prepared_items" />
			<input type="submit" value="<?php echo __('Verify all prepared items','wplister') ?>" name="submit" class="button"
				   title="<?php echo __('Verify all prepared items with eBay and get listing fees.','wplister') ?>">
		</div>
	</form>

	<form method="post" action="<?php echo $wpl_form_action; ?>">
		<div class="submit" style="padding-top: 0; float: left; padding-left:15px;">
			<?php #wp_nonce_field( 'e2e_tools_page' ); ?>
			<input type="hidden" name="action" value="publish_all_verified_items" />
			<input type="submit" value="<?php echo __('Publish all verified items','wplister') ?>" name="submit" class="button" 
				   title="<?php echo __('Publish all verified items on eBay.','wplister') ?>">
		</div>
	</form>

	<form method="post" action="<?php echo $wpl_form_action; ?>">
		<div class="submit" style="padding-top: 0; float: left; padding-left:15px;">
			<?php #wp_nonce_field( 'e2e_tools_page' ); ?>
			<input type="hidden" name="action" value="revise_all_changed_items" />
			<input type="submit" value="<?php echo __('Revise all changed items','wplister') ?>" name="submit" class="button" 
				   title="<?php echo __('Revise all changed items on eBay.','wplister') ?>">
		</div>
	</form>

	<form method="post" action="<?php echo $wpl_form_action; ?>">
		<div class="submit" style="padding-top: 0; float: left; padding-left:15px;">
			<?php #wp_nonce_field( 'e2e_tools_page' ); ?>
			<input type="hidden" name="action" value="update_all_published_items" />
			<input type="submit" value="<?php echo __('Update all published items','wplister') ?>" name="submit" class="button" 
				   title="<?php echo __('Update all published items from eBay.','wplister') ?>">
		</div>
	</form>
-->

	<script type="text/javascript">
		jQuery( document ).ready(
			function () {
		
				// ask again before ending items
				jQuery('.row-actions .end_item a').on('click', function() {
					return confirm("<?php echo __('Are you sure you want to end this listing?','wplister') ?>");
				})
	
				// ask again before relisting items
				jQuery('.row-actions .relist a').on('click', function() {
					return confirm("<?php echo __('Are you sure you want to relist this ended listing?','wplister') ?>");
				})
	
				// ask again before deleting items
				jQuery('.row-actions .delete a').on('click', function() {
					return confirm("<?php echo __('Are you sure you want to remove this listing from WP-Lister?','wplister') ?>");
				})
				jQuery('#wpl_dupe_details a.delete').on('click', function() {
					return confirm("<?php echo __('Are you sure you want to remove this listing from WP-Lister?','wplister') ?>");
				})

				// handle bulk actions click
				jQuery(".bulkactions input[type='submit']").on('click', function() {
					
					if ( 'doaction'  == this.id ) var selected_action = jQuery("select[name='action']").first().val();
					if ( 'doaction2' == this.id ) var selected_action = jQuery("select[name='action2']").first().val();

					// console.log( this.id );
					// console.log('action',selected_action);

					// create array of selected listing IDs
					var item_ids = [];
					var checked_items = jQuery(".check-column input:checked[name='auction[]']");
					checked_items.each( function(index, checkbox) {
						 item_ids.push( checkbox.value );
						 // console.log( 'checked listing ID', checkbox.value );
					});
					// console.log( item_ids );

					// check if any items were selected
					if ( item_ids.length > 0 ) {
						var params = {
							'listing_ids': item_ids
						}

						if ( 'verify' == selected_action ) {
							WpLister.JobRunner.runJob( 'verifyItems', 'Verifying selected items...', params );
							return false;
						}
						if ( 'publish2e' == selected_action ) {
							WpLister.JobRunner.runJob( 'publishItems', 'Publishing selected items...', params );
							return false;
						}
						if ( 'revise' == selected_action ) {
							WpLister.JobRunner.runJob( 'reviseItems', 'Revising selected items...', params );
							return false;
						}
						if ( 'update' == selected_action ) {
							WpLister.JobRunner.runJob( 'updateItems', 'Updating selected items...', params );
							return false;
						}

						if ( 'end_item' == selected_action ) {
							WpLister.JobRunner.runJob( 'endItems', 'Ending selected items...', params );
							return false;
						}
						if ( 'relist' == selected_action ) {
							WpLister.JobRunner.runJob( 'relistItems', 'Relisting selected items...', params );
							return false;
						}

					}

					return true;

				})

	
			}
		);
	
	</script>


	<?php if ( isset( $_GET['action'] ) && ( $_GET['action'] == 'verifyPreparedItemsNow' ) ) : ?>
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