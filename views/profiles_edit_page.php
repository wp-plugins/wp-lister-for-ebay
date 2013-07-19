<?php include_once( dirname(__FILE__).'/common_header.php' ); ?>

<style type="text/css">

	.postbox h3 {
	    cursor: default;
	}

</style>

<?php
	$item_details = $wpl_item['details'];
?>

<div class="wrap wplister-page">
	<div class="icon32" style="background: url(<?php echo $wpl_plugin_url; ?>img/hammer-32x32.png) no-repeat;" id="wpl-icon"><br /></div>
	<?php if ( $wpl_item['profile_id'] ): ?>
	<h2><?php echo __('Edit Profile','wplister') ?></h2>
	<?php else: ?>
	<h2><?php echo __('New Profile','wplister') ?></h2>
	<?php endif; ?>
	
	<?php echo $wpl_message ?>

	<form method="post" action="<?php echo $wpl_form_action; ?>">

	<!--
	<div id="titlediv" style="margin-top:10px; margin-bottom:5px; width:60%">
		<div id="titlewrap">
			<label class="hide-if-no-js" style="visibility: hidden; " id="title-prompt-text" for="title">Enter title here</label>
			<input type="text" name="wpl_e2e_profile_name" size="30" tabindex="1" value="<?php echo $wpl_item['profile_name']; ?>" id="title" autocomplete="off">
		</div>
	</div>
	-->

	<div id="poststuff">
		<div id="post-body" class="metabox-holder columns-2">

			<div id="postbox-container-1" class="postbox-container">
				<div id="side-sortables" class="meta-box">
					<?php include('profile/edit_sidebar.php') ?>
				</div>
			</div> <!-- #postbox-container-1 -->


			<!-- #postbox-container-2 -->
			<div id="postbox-container-2" class="postbox-container">
				<div class="meta-box-sortables ui-sortable">
					

					<div class="postbox" id="GeneralSettingsBox">
						<h3><span><?php echo __('General eBay settings','wplister'); ?></span></h3>
						<div class="inside">

							<div id="titlediv" style="margin-bottom:5px;">
								<div id="titlewrap">
									<label for="wpl-text-profile_description" class="text_label"><?php echo __('Profile name','wplister'); ?>: *</label>
									<input type="text" name="wpl_e2e_profile_name" size="30" value="<?php echo $wpl_item['profile_name']; ?>" id="title" autocomplete="off" style="width:65%;">
								</div>
							</div>

							<label for="wpl-text-profile_description" class="text_label"><?php echo __('Profile description','wplister'); ?>:</label>
							<input type="text" name="wpl_e2e_profile_description" id="wpl-text-profile_description" value="<?php echo str_replace('"','&quot;', $wpl_item['profile_description'] ); ?>" class="text_input" />
							<br class="clear" />

							<label for="wpl-text-auction_type" class="text_label"><?php echo __('Type','wplister'); ?>: *</label>
							<select id="wpl-text-auction_type" name="wpl_e2e_auction_type" title="Type" class=" required-entry select">
								<option value="">-- <?php echo __('Please select','wplister'); ?> --</option>
								<option value="Chinese" <?php if ( $item_details['auction_type'] == 'Chinese' ): ?>selected="selected"<?php endif; ?>><?php echo __('Auction','wplister'); ?></option>
								<option value="FixedPriceItem" <?php if ( $item_details['auction_type'] == 'FixedPriceItem' ): ?>selected="selected"<?php endif; ?>><?php echo __('Fixed Price','wplister'); ?></option>
							</select>
							<?php if ($wpl_published_listings) : ?>
							<p class="desc" style="display: block;">
								<?php echo __('Note: eBay does not allow changing the listing type for already published items.','wplister'); ?>
							</p>
							<?php endif; ?>

							<label for="wpl-text-start_price" class="text_label"><?php echo __('Price / Start price','wplister'); ?>:</label>
							<input type="text" name="wpl_e2e_start_price" id="wpl-text-start_price" value="<?php echo $item_details['start_price']; ?>" class="text_input" />
							<br class="clear" />

							<div id="wpl-text-fixed_price_container">
							<label for="wpl-text-fixed_price" class="text_label"><?php echo __('Buy Now Price','wplister'); ?>:</label>
							<input type="text" name="wpl_e2e_fixed_price" id="wpl-text-fixed_price" value="<?php echo $item_details['fixed_price']; ?>" class="text_input" />
							<br class="clear" />
							</div>

							<p class="desc" style="display: block;">
								<?php echo __('Fixed price (199), percent (+10% / -10%) or fixed change (+5 / -5)','wplister'); ?><br>
								<?php echo __('Leave this empty to use the product price as it is.','wplister'); ?>
							</p>

							<label for="wpl-text-quantity" class="text_label"><?php echo __('Quantity','wplister'); ?>:</label>
							<input type="text" name="wpl_e2e_quantity" id="wpl-text-quantity" value="<?php echo $item_details['quantity']; ?>" class="text_input" />
							<br class="clear" />
							<p class="desc" style="display: block;">
								<?php echo __('Leave this empty to list all available items.','wplister'); ?>
								<?php 
								?>
							</p>


							<label for="wpl-text-listing_duration" class="text_label"><?php echo __('Duration','wplister'); ?>: *</label>
							<select id="wpl-text-listing_duration" name="wpl_e2e_listing_duration" title="Duration" class=" required-entry select">
								<option value="">-- <?php echo __('Please select','wplister'); ?> --</option>
								<option value="Days_1" <?php if ( $wpl_item['listing_duration'] == 'Days_1' ): ?>selected="selected"<?php endif; ?>>1 <?php echo __('Day','wplister'); ?></option>
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
							<p class="desc" style="display: block;">
								<?php echo __('GTC listings will be charged every 30 days.','wplister'); ?>
							</p>


							<label for="wpl-text-condition_id" class="text_label"><?php echo __('Condition','wplister'); ?>: *</label>
							<select id="wpl-text-condition_id" name="wpl_e2e_condition_id" title="Condition" class=" required-entry select">
							<?php if ( isset( $wpl_available_conditions ) && is_array( $wpl_available_conditions ) ): ?>
								<?php foreach ($wpl_available_conditions as $condition_id => $desc) : ?>
									<option value="<?php echo $condition_id ?>" 
										<?php if ( $item_details['condition_id'] == $condition_id ) : ?>
											selected="selected"
										<?php endif; ?>
										><?php echo $desc ?></option>
								<?php endforeach; ?>
							<?php elseif ( $wpl_available_conditions == 'none' ) : ?>
								<option value="none" selected="selected"><?php echo __('none','wplister'); ?></option>
							<?php else: ?>
								<option value="1000" selected="selected"><?php echo __('New','wplister'); ?></option>
								<option value="3000" selected="selected"><?php echo __('Used','wplister'); ?></option>
							<?php endif; ?>
							</select>
							<br class="clear" />

							<p class="desc" style="display: block;">
								<?php echo __('Available conditions may vary for different categories.','wplister'); ?>
								<?php echo __('You should set the category first.','wplister'); ?>
							</p>

							<div id="wpl-text-condition_description_container">
							<label for="wpl-text-condition_description" class="text_label"><?php echo __('Condition description','wplister'); ?>:</label>
							<input type="text" name="wpl_e2e_condition_description" id="wpl-text-condition_description" value="<?php echo @$item_details['condition_description']; ?>" class="text_input" />
							<br class="clear" />
							<p class="desc" style="display: block;">
								<?php echo __('This field should only be used to further clarify the condition of used items.','wplister'); ?>
							</p>
							</div>


							<label for="wpl-text-dispatch_time" class="text_label"><?php echo __('Handling time','wplister'); ?>: *</label>
							<select id="wpl-text-dispatch_time" name="wpl_e2e_dispatch_time" title="Condition" class=" required-entry select">
							<?php if ( isset( $wpl_available_dispatch_times ) && is_array( $wpl_available_dispatch_times ) ): ?>
								<?php foreach ($wpl_available_dispatch_times as $dispatch_time => $desc) : ?>
									<option value="<?php echo $dispatch_time ?>" 
										<?php if ( $item_details['dispatch_time'] == $dispatch_time ) : ?>
											selected="selected"
										<?php endif; ?>
										><?php echo $desc ?></option>
								<?php endforeach; ?>
							<?php else: ?>
								<option value="1000" selected="selected"><?php echo __('New','wplister'); ?></option>
							<?php endif; ?>
							</select>


							<br class="clear" />
							<p class="desc" style="display: block;">
								<?php echo __('The maximum number of business days a seller commits to for shipping an item to domestic buyers after receiving a cleared payment.','wplister'); ?>
							</p>
	
						</div>
					</div>


					<?php include('profile/edit_categories.php') ?>
					<?php include('profile/edit_shipping.php') ?>


					<div class="postbox" id="PaymentOptionsBox">
						<h3><span><?php echo __('Payment methods','wplister'); ?></span></h3>
						<div class="inside">

							<label for="wpl-text-payment_options" class="text_label"><?php echo __('Payment methods','wplister'); ?>: *</label>
							<table id="payment_options_table" style="width:65%;">
								
								<?php foreach ($item_details['payment_options'] as $service) : ?>
								<tr class="row">
									<td>
										<select name="wpl_e2e_payment_options[][payment_name]" 
												class="required-entry select" style="width:100%;">
											<option value="">-- <?php echo __('Please select','wplister'); ?> --</option>
											<?php foreach ($wpl_payment_options as $opt) : ?>
												<option value="<?php echo $opt['payment_name'] ?>" 
													<?php if ( @$service['payment_name'] == $opt['payment_name'] ) : ?>
														selected="selected"
													<?php endif; ?>
													><?php echo $opt['payment_description'] ?></option>
											<?php endforeach; ?>
										</select>
									</td><td align="right">
										<input type="button" value="<?php echo __('remove','wplister'); ?>" class="button-secondary" 
											onclick="jQuery(this).parent().parent().remove();" />
									</td>
								</tr>
								<?php endforeach; ?>

							</table>

							<input type="button" value="<?php echo __('Add payment method','wplister'); ?>" name="btn_add_payment_option" 
								onclick="jQuery('#payment_options_table').find('tr.row').first().clone().appendTo('#payment_options_table');"
								class="button-secondary">

							<br class="clear" />


							<label for="wpl-text-payment_instructions" class="text_label"><?php echo __('Payment instructions','wplister'); ?>:</label>
							<textarea name="wpl_e2e_payment_instructions" id="wpl-text-payment_instructions" class="textarea"><?php echo stripslashes( @$item_details['payment_instructions'] ); ?></textarea>
							<br class="clear" />

						</div>
					</div>


					<div class="postbox" id="ReturnsSettingsBox">
						<h3><span><?php echo __('Returns settings','wplister'); ?></span></h3>
						<div class="inside">

							<label for="wpl-text-returns_accepted" class="text_label"><?php echo __('Returns settings','wplister'); ?>:</label>
							<select id="wpl-text-returns_accepted" name="wpl_e2e_returns_accepted" title="Returns" class=" required-entry select">
								<option value="">-- <?php echo __('Please select','wplister'); ?> --</option>
								<option value="1" <?php if ( $item_details['returns_accepted'] == '1' ): ?>selected="selected"<?php endif; ?>><?php echo __('Yes','wplister'); ?></option>
								<option value="0" <?php if ( $item_details['returns_accepted'] == '0' ): ?>selected="selected"<?php endif; ?>><?php echo __('No','wplister'); ?></option>
							</select>
							<br class="clear" />

							<label for="wpl-text-returns_within" class="text_label"><?php echo __('Returns within','wplister'); ?>:</label>
							<select id="wpl-text-returns_within" name="wpl_e2e_returns_within" class=" required-entry select">
								<option value="">-- <?php echo __('Please select','wplister'); ?> --</option>
								<option value="Days_10" <?php if ( $item_details['returns_within'] == 'Days_10' ): ?>selected="selected"<?php endif; ?>>10 <?php echo __('days','wplister'); ?></option>
								<option value="Days_14" <?php if ( $item_details['returns_within'] == 'Days_14' ): ?>selected="selected"<?php endif; ?>>14 <?php echo __('days','wplister'); ?></option>
								<option value="Days_30" <?php if ( $item_details['returns_within'] == 'Days_30' ): ?>selected="selected"<?php endif; ?>>30 <?php echo __('days','wplister'); ?></option>
								<option value="Days_60" <?php if ( $item_details['returns_within'] == 'Days_60' ): ?>selected="selected"<?php endif; ?>>60 <?php echo __('days','wplister'); ?></option>
							</select>
							<br class="clear" />

							<label for="wpl-text-RestockingFee" class="text_label"><?php echo __('Restocking fee','wplister'); ?>:</label>
							<select id="wpl-text-RestockingFee" name="wpl_e2e_RestockingFee" class=" required-entry select">
								<option value="">-- <?php echo __('Please select','wplister'); ?> --</option>
								<option value="NoRestockingFee" <?php if ( @$item_details['RestockingFee'] == 'NoRestockingFee' ): ?>selected="selected"<?php endif; ?>><?php echo __('No restocking fee','wplister'); ?></option>
								<option value="Percent_10" <?php if ( @$item_details['RestockingFee'] == 'Percent_10' ): ?>selected="selected"<?php endif; ?>>10 <?php echo __('percent','wplister'); ?></option>
								<option value="Percent_15" <?php if ( @$item_details['RestockingFee'] == 'Percent_15' ): ?>selected="selected"<?php endif; ?>>15 <?php echo __('percent','wplister'); ?></option>
								<option value="Percent_20" <?php if ( @$item_details['RestockingFee'] == 'Percent_20' ): ?>selected="selected"<?php endif; ?>>20 <?php echo __('percent','wplister'); ?></option>
							</select>
							<br class="clear" />

							<label for="wpl-text-returns_description" class="text_label"><?php echo __('Returns description','wplister'); ?>:</label>
							<textarea name="wpl_e2e_returns_description" id="wpl-text-returns_description" class="textarea"><?php echo stripslashes( $item_details['returns_description'] ); ?></textarea>
							<br class="clear" />

						</div>
					</div>




					<div class="submit" style="padding-top: 0; float: right; display:none;">
						<input type="submit" value="<?php echo __('Save profile','wplister'); ?>" name="submit" class="button-primary">
					</div>
						
				</div> <!-- .meta-box-sortables -->
			</div> <!-- #postbox-container-1 -->



		</div> <!-- #post-body -->
		<br class="clear">
	</div> <!-- #poststuff -->

	</form>


	<?php if ( get_option('wplister_log_level') > 6 ): ?>
	<pre><?php print_r($wpl_item); ?></pre>
	<?php endif; ?>


	<script type="text/javascript">

		jQuery( document ).ready(
			function () {

				// hide fixed price field for fixed price listings
				// (fixed price listings only use StartPrice)
				jQuery('#wpl-text-auction_type').change(function() {
  					if ( jQuery('#wpl-text-auction_type').val() == 'Chinese' ) {
  						jQuery('#wpl-text-fixed_price_container').show();
  					} else {
  						jQuery('#wpl-text-fixed_price_container').hide();
  					}
				});
				jQuery('#wpl-text-auction_type').change();

				// hide condition description field for "new" conditions (Condition IDs 1000-1499)
				jQuery('#wpl-text-condition_id').change(function() {
  					if ( 1000 <= jQuery('#wpl-text-condition_id').val() <= 1499 ) {
  						jQuery('#wpl-text-condition_description_container').show();
  					} else {
  						jQuery('#wpl-text-condition_description_container').hide();
  					}
				});
				jQuery('#wpl-text-condition_id').change();


			    // 
			    // Validation
			    // 
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

					// location required
					if ( jQuery('#wpl-text-location')[0].value == '' ) {
						alert('Please enter a location.'); return false;
					}

					// country required
					if ( jQuery('#wpl-text-country')[0].value == '' ) {
						alert('Please select a country.'); return false;
					}


					// flat: local shipping price required
					var shipping_type = jQuery('.select_shipping_type')[0] ? jQuery('.select_shipping_type')[0].value : 'flat';
					if ( ( shipping_type == 'flat' ) || ( shipping_type == 'FlatDomesticCalculatedInternational' ) ) {
						// if ( jQuery('#loc_shipping_options_table_flat input.price_input')[0].value == '' ) {
						// 	alert('Please enter a shipping fee.'); return false;
						// }
						// local shipping option required
						if ( jQuery('#loc_shipping_options_table_flat .select_service_name')[0].value == '' ) {
							alert('Please select at least one domestic shipping service.'); return false;
						}
					} else {
						// local shipping option required
						if ( jQuery('#loc_shipping_options_table_calc .select_service_name')[0].value == '' ) {
							alert('Please select at least one domestic shipping service.'); return false;
						}						
					}

					// payment method required
					if ( jQuery('#payment_options_table select')[0].value == '' ) {
						alert('Please select at least one payment method.'); return false;
					}

					// country required
					// if ( jQuery('#wpl-text-country')[0].value == '' ) {
					// 	alert('Please select a country.'); return false;
					// }


					// template is required
					var template_options = jQuery("input[name='wpl_e2e_template']");
					if( template_options.filter(':checked').length == 0){
						alert('Please select a listing template.'); return false;
					}

					return true;
				})


			}
		);






		// load item conditions on primary category change

		<?php
			// get item conditions as json
			$conditions = unserialize( @$wpl_item['category_conditions'] );
		?>
		var CategoryConditionsData = <?php echo json_encode( $conditions ) ?>;
		// var CurrentItemSpecifics = <?php echo json_encode( @$item_details['item_conditions'] ) ?>;
		// var default_ebay_category_id = <?php echo @$wpl_default_ebay_category_id ? $wpl_default_ebay_category_id : 0 ?>;

		// handle new primary category
		// update item conditions
		function updateItemConditions() {
			var primary_category_id = jQuery('#ebay_category_id_1')[0].value;

			// jQuery('#EbayItemSpecificsBox .inside').slideUp(500);
			// jQuery('#EbayItemSpecificsBox .loadingMsg').slideDown(500);

	        // fetch category conditions
	        var params = {
	            action: 'wpl_getCategoryConditions',
	            id: primary_category_id,
	            nonce: 'TODO'
	        };
	        var jqxhr = jQuery.getJSON( ajaxurl, params )
	        .success( function( response ) { 

	            // append to log
	            // console.log( 'response: ', response ); 
	            CategoryConditionsData = response;

	            buildItemConditions();
				// jQuery('#EbayItemConditionsBox .inside').slideDown(500);
				// jQuery('#EbayItemConditionsBox .loadingMsg').slideUp(500);

	        })
	        .error( function(e,xhr,error) { 
	            console.log( "error", xhr, error ); 
	            console.log( e.responseText ); 
	        });			
		}

		// built item conditions table
		function buildItemConditions() {

			var primary_category_id = jQuery('#ebay_category_id_1')[0].value;
			var conditions = CategoryConditionsData[ primary_category_id ];

			// console.log('buildItemConditions()');
			// console.log('primary_category_id',primary_category_id);
			// console.log('conditions step 1',conditions);

			// possibly use default category
			if ( ( ! conditions ) && ( default_ebay_category_id ) ) {
				conditions = CategoryConditionsData[ default_ebay_category_id ];
			}
			// console.log('conditions step 2',conditions);

			// console.log('conditions: ',conditions);
			// console.log('CategoryConditionsData: ',CategoryConditionsData);
			// console.log('default_ebay_category_id: ',default_ebay_category_id);
			// console.log('primary_category_id: ',primary_category_id);

			if ( ( ! conditions ) || ( conditions == 'none' ) ) {
				jQuery('#wpl-text-condition_id').children().remove();
	            jQuery('#wpl-text-condition_id').append( jQuery('<option/>').val( 'none' ).html( 'none' ) );
				return;
			}
			// console.log('conditions step 3',conditions);


			// save current selection
			var selected_condition_id = jQuery('#wpl-text-condition_id')[0].value;		
			// console.log('selected_condition_id',selected_condition_id);

			// clear options
			jQuery('#wpl-text-condition_id').children().remove();

			// add options
			for (var condition_id in conditions ) {
				// console.log('condition_id ',condition_id);
				// console.log('condition_name ',conditions[condition_id]);
				condition_name = conditions[condition_id];
	            jQuery('#wpl-text-condition_id').append( jQuery('<option/>').val( condition_id ).html( condition_name ) );

			}

			// restore current selection
			jQuery("#wpl-text-condition_id option[value='"+selected_condition_id+"']").attr('selected',true);


		}

		// init item conditions when page is loaded
		jQuery( document ).ready( function () {
			// buildItemConditions();
		});	

	
	</script>

</div>



	
