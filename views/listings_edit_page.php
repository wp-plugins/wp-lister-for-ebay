<?php include_once( dirname(__FILE__).'/common_header.php' ); ?>

<style type="text/css">

	/* sideboxes */
	#side-sortables .postbox input.text_input,
	#side-sortables .postbox select.select {
	    width: 50%;
	}
	#side-sortables .postbox label.text_label {
	    width: 45%;
	}

	.postbox h3 {
	    cursor: default;
	}
		
	/* backwards compatibility to WP 3.3 */
	#poststuff #post-body.columns-2 {
	    margin-right: 300px;
	}
	#poststuff #post-body {
	    padding: 0;
	}
	#post-body.columns-2 #postbox-container-1 {
	    float: right;
	    margin-right: -300px;
	    width: 280px;
	}
	#poststuff .postbox-container {
	    width: 100%;
	}
	#major-publishing-actions {
	    border-top: 1px solid #F5F5F5;
	    clear: both;
	    margin-top: -2px;
	    padding: 10px 10px 8px;
	}
	#post-body .misc-pub-section {
	    max-width: 100%;
	    border-right: none;
	}
</style>

<?php
	$item_details = $wpl_item['profile_data']['details'];
?>

<div class="wrap wplister-page">
	<div class="icon32" style="background: url(<?php echo $wpl_plugin_url; ?>img/hammer-32x32.png) no-repeat;" id="wpl-icon"><br /></div>
	<h2><?php echo __('Edit Listing','wplister') ?></h2>
	
	<?php echo $wpl_message ?>

	<form method="post" action="<?php echo $wpl_form_action; ?>">

	<div id="poststuff">
		<div id="post-body" class="metabox-holder columns-2">

			<div id="postbox-container-1" class="postbox-container">
				<div id="side-sortables" class="meta-box">


					<!-- first sidebox -->
					<div class="postbox" id="submitdiv">
						<!--<div title="Click to toggle" class="handlediv"><br></div>-->
						<h3><span><?php echo __('Update','wplister'); ?></span></h3>
						<div class="inside">

							<div id="submitpost" class="submitbox">

								<div id="misc-publishing-actions">

									<div class="misc-pub-section">
									<!-- optional revise item on save -->
									<?php if ( ( $wpl_item['status'] == 'published' ) || ( $wpl_item['status'] == 'changed' ) ): ?>
										<p><?php _e('Your changes to this item will only be updated on eBay when you revise this item.','wplister') ?></p>
										<input type="checkbox" name="wpl_e2e_revise_item_on_save" value="yes" id="revise_item_on_save" />
										<label for="revise_item_on_save"><?php _e('revise this item when saving','wplister') ?></label>
									<?php elseif ( ( $wpl_item['status'] == 'ended' ) || ( $wpl_item['status'] == 'ended' ) ): ?>
										<p><?php _e('This item has been ended.','wplister') ?></p>
									<?php else: ?>
										<p>This item has not been published yet.</p>
									<?php endif; ?>
									</div>

									<div class="misc-pub-section">
									<!-- optional revise item on save -->
									<?php $history = maybe_unserialize( $wpl_item['history'] ); ?> 

									<?php if ( isset($history['previous_ids']) ): ?>
										<p>
											<?php _e('Current item ID','wplister') ?>: <br>
											<?php echo $wpl_item['ebay_id'] ?>
										</p>
										<p>
											<?php _e('Previous item IDs','wplister') ?>:<br>
											<?php 
												if ( is_array($history['previous_ids']) )
												foreach ($history['previous_ids'] as $prev_id ) {
													echo "$prev_id <br>";
												}
											?>
										</p>
									<?php endif; ?>
									</div>

								</div>

								<div id="major-publishing-actions">
									<div id="publishing-action">
										<input type="hidden" name="action" value="save_listing" />
										<input type="hidden" name="wpl_e2e_listing_id" value="<?php echo $wpl_item['id']; ?>" />
										<input type="hidden" name="wpl_e2e_status" value="<?php echo $wpl_item['status']; ?>" > 
										<input type="submit" value="<?php echo __('Update','wplister'); ?>" id="publish" class="button-primary" name="save">
									</div>
									<div class="clear"></div>
								</div>

							</div>

						</div>
					</div>


					<div class="postbox" id="TemplatesBox">
						<h3><span><?php echo __('Template','wplister'); ?></span></h3>
						<div class="inside">
							<?php foreach ($wpl_template_files as $tpl) : ?>
								<?php
									$tpl_name = $tpl['template_name'];
									$tpl_path = $tpl['template_path'];
									$checked  = ( $wpl_item['template'] == $tpl_path ) ? 'checked="checked"' : '';
								?>

								<input type="radio" value="<?php echo $tpl_path ?>" id="template-<?php echo basename($tpl_path) ?>" name="wpl_e2e_template" class="post-format" <?php echo $checked ?> > 
								<label for="template-<?php echo basename($tpl_path) ?>"><?php echo $tpl_name ?></label><br>

							<?php endforeach; ?>							
						</div>
					</div>


					<div class="postbox" id="HelpBox">
						<h3><span><?php echo __('Information','wplister'); ?></span></h3>
						<div class="inside">
							<p>
								Editing a single listing might come in handy when you need to fix a single title or price. 
								But it is not recommended as part of your workflow. 
							</p>
							<p>
								If you find yourself editing single listings on a regular basis, 
								you should contact us and describe your requirements. We will then work out a solution which benefts all users.
							</p>
						</div>
					</div>


				</div>
			</div> <!-- #postbox-container-2 -->

			<div id="postbox-container-2" class="postbox-container">
				<div class="meta-box-sortables ui-sortable">
					

					<div class="postbox" id="GeneralSettingsBox">
						<h3><span><?php echo __('Item settings','wplister'); ?></span></h3>
						<div class="inside">

							<div id="titlediv" style="margin-bottom:5px;">
								<div id="titlewrap">
									<label for="wpl-text-auction_title" class="text_label"><?php echo __('Title','wplister'); ?>:</label>
									<input type="text" name="wpl_e2e_auction_title" size="30" value="<?php echo $wpl_item['auction_title']; ?>" id="title" autocomplete="off" style="width:65%;">
								</div>
							</div>

							<label for="wpl-text-price" class="text_label"><?php echo __('Price / Start price','wplister'); ?>:</label>
							<input type="text" name="wpl_e2e_price" id="wpl-text-price" value="<?php echo $wpl_item['price']; ?>" class="text_input" />
							<p class="desc" style="display: block;"><?php echo __('This will have no effect on product variations.','wplister'); ?></p>
							<!br class="clear" />

							<label for="wpl-text-quantity" class="text_label"><?php echo __('Quantity','wplister'); ?>:</label>
							<input type="text" name="wpl_e2e_quantity" id="wpl-text-quantity" value="<?php echo $wpl_item['quantity']; ?>" class="text_input" />
							<p class="desc" style="display: block;"><?php echo __('This will have no effect on product variations.','wplister'); ?></p>
							<!br class="clear" />


							<label for="wpl-text-listing_duration" class="text_label"><?php echo __('Duration','wplister'); ?>: *</label>
							<select id="wpl-text-listing_duration" name="wpl_e2e_listing_duration" title="Laufzeit" class=" required-entry select">
								<option value="">-- <?php echo __('Please select','wplister'); ?> --</option>
								<option value="Days_3" <?php if ( $wpl_item['listing_duration'] == 'Days_3' ): ?>selected="selected"<?php endif; ?>>3 <?php echo __('Days','wplister'); ?></option>
								<option value="Days_5" <?php if ( $wpl_item['listing_duration'] == 'Days_5' ): ?>selected="selected"<?php endif; ?>>5 <?php echo __('Days','wplister'); ?></option>
								<option value="Days_7" <?php if ( $wpl_item['listing_duration'] == 'Days_7' ): ?>selected="selected"<?php endif; ?>>7 <?php echo __('Days','wplister'); ?></option>
								<option value="Days_10" <?php if ( $wpl_item['listing_duration'] == 'Days_10' ): ?>selected="selected"<?php endif; ?>>10 <?php echo __('Days','wplister'); ?></option>
								<option value="Days_30" <?php if ( $wpl_item['listing_duration'] == 'Days_30' ): ?>selected="selected"<?php endif; ?>>30 <?php echo __('Days','wplister'); ?></option>
								<option value="Days_60" <?php if ( $wpl_item['listing_duration'] == 'Days_60' ): ?>selected="selected"<?php endif; ?>>60 <?php echo __('Days','wplister'); ?></option>
								<option value="Days_90" <?php if ( $wpl_item['listing_duration'] == 'Days_90' ): ?>selected="selected"<?php endif; ?>>90 <?php echo __('Days','wplister'); ?></option>
								<option value="GTC" <?php if ( $wpl_item['listing_duration'] == 'GTC' ): ?>selected="selected"<?php endif; ?>><?php echo __('Good Till Canceled','wplister'); ?> (GTC)</option>
							</select>
							<br class="clear" />


						</div>
					</div>



					<div class="postbox" id="DeveloperToolBox" style="display:none;">
						<h3><span><?php echo __('Developer options','wplister'); ?></span></h3>
						<div class="inside">
							<p>
								You should not normally need to modify the following settings. Use at your own risk!
							</p>

							<label for="wpl-text-quantity_sold" class="text_label"><?php echo __('Items sold','wplister'); ?>:</label>
							<input type="text" name="wpl_e2e_quantity_sold" size="30" value="<?php echo $wpl_item['quantity_sold']; ?>" class="text_input" />
							<br class="clear" />

							<label for="wpl-text-ebay_id" class="text_label"><?php echo __('eBay Item ID','wplister'); ?>:</label>
							<input type="text" name="wpl_e2e_ebay_id" size="30" value="<?php echo $wpl_item['ebay_id']; ?>" class="text_input" />
							<br class="clear" />

							<label for="wpl-text-listing_status" class="text_label"><?php echo __('Listing status','wplister'); ?>:</label>
							<select id="wpl-text-listing_status" name="wpl_e2e_listing_status" title="Laufzeit" class=" required-entry select">
								<option value="prepared" <?php if ( $wpl_item['status'] == 'prepared' ): ?>selected="selected"<?php endif; ?>><?php echo __('prepared','wplister'); ?></option>
								<option value="verified" <?php if ( $wpl_item['status'] == 'verified' ): ?>selected="selected"<?php endif; ?>><?php echo __('verified','wplister'); ?></option>
								<option value="published" <?php if ( $wpl_item['status'] == 'published' ): ?>selected="selected"<?php endif; ?>><?php echo __('published','wplister'); ?></option>
								<option value="sold" <?php if ( $wpl_item['status'] == 'sold' ): ?>selected="selected"<?php endif; ?>><?php echo __('sold','wplister'); ?></option>
								<option value="ended" <?php if ( $wpl_item['status'] == 'ended' ): ?>selected="selected"<?php endif; ?>><?php echo __('ended','wplister'); ?></option>
								<option value="changed" <?php if ( $wpl_item['status'] == 'changed' ): ?>selected="selected"<?php endif; ?>><?php echo __('changed','wplister'); ?></option>
							</select>
							<br class="clear" />

							<label for="wpl-text-auction_type" class="text_label"><?php echo __('Type','wplister'); ?>: *</label>
							<select id="wpl-text-auction_type" name="wpl_e2e_auction_type" title="Type" class=" required-entry select">
								<option value="">-- <?php echo __('Please select','wplister'); ?> --</option>
								<option value="Chinese" <?php if ( $wpl_item['auction_type'] == 'Chinese' ): ?>selected="selected"<?php endif; ?>><?php echo __('Auction','wplister'); ?></option>
								<option value="FixedPriceItem" <?php if ( $wpl_item['auction_type'] == 'FixedPriceItem' ): ?>selected="selected"<?php endif; ?>><?php echo __('Fixed Price','wplister'); ?></option>
							</select>
							<p class="desc" style="display: block;">
								<?php echo __('Note: eBay does not allow changing the listing type for already published items.','wplister'); ?>
							</p>

							<label for="wpl-text-post_id" class="text_label"><?php echo __('Product ID','wplister'); ?>:</label>
							<input type="text" name="wpl_e2e_post_id" size="30" value="<?php echo $wpl_item['post_id']; ?>" class="text_input" />
							<br class="clear" />

							<label for="wpl-enable_dev_mode" class="text_label"><?php echo __('Update advanced settings','wplister'); ?>:</label>
							<input type="checkbox" name="wpl_e2e_enable_dev_mode" id="wpl-enable_dev_mode" value="1" class="checkbox_input" />
							<span style="line-height: 24px">
								<?php echo __('Yes, I know what I am doing.','wplister'); ?>
							</span>
							<br class="clear" />


						</div>
					</div>




					<div class="submit" style="padding-top: 0; float: right; display:none;">
						<input type="submit" value="<?php echo __('Save listing','wplister'); ?>" name="submit" class="button-primary">
					</div>
						
				</div> <!-- .meta-box-sortables -->
			</div> <!-- #postbox-container-2 -->


		</div> <!-- #post-body -->
		<br class="clear">
	</div> <!-- #poststuff -->

	</form>


	<?php if ( isset($_GET['debug']) || ( get_option('wplister_log_level') > 6 ) ): ?>
		<pre><?php #print_r($wpl_int_shipping_options); ?></pre>
		<pre><?php print_r(maybe_unserialize( $wpl_item['details'] ) ); ?></pre>
		<pre><?php #print_r($wpl_item); ?></pre>
		<pre><?php 
			$details = maybe_unserialize( $wpl_item['details'] ); 
			if ( is_array( $details->Variations->Variation ) )
			foreach ($details->Variations->Variation as $var) {
				// echo "<pre>";print_r($var);echo"</pre>";#die(); 	
				echo $var->SKU . ' - ' . $var->Quantity . '<br>';
			} 
		?></pre>
	<?php endif; ?>


	<script type="text/javascript">
		jQuery( document ).ready(
			function () {
		

				// check required values on submit
				jQuery('.wplister-page form').on('submit', function() {
					
					// duration is required
					if ( jQuery('#wpl-text-listing_duration')[0].value == '' ) {
						alert('Please select a listing duration.'); return false;
					}

					// dispatch time is required
					if ( jQuery('#wpl-text-dispatch_time')[0].value == '' ) {
						alert('Please enter a handling time.'); return false;
					}

					// first category is required
					if ( jQuery('#wpl-text-ebay_category_1_id')[0].value == '' ) {
						alert('Please select a main category.'); return false;
					}

					return true;
				})


			}
		);
	
	</script>

</div>
