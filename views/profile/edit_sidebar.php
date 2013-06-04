<style type="text/css">

	#side-sortables .postbox input.text_input,
	#side-sortables .postbox select.select {
	    width: 50%;
	}
	#side-sortables .postbox label.text_label {
	    width: 45%;
	}

	#side-sortables .postbox .inside p.desc {
		margin-left: 2%;
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




					<!-- first sidebox -->
					<div class="postbox" id="submitdiv">
						<!--<div title="Click to toggle" class="handlediv"><br></div>-->
						<h3><span><?php echo __('Update','wplister'); ?></span></h3>
						<div class="inside">

							<div id="submitpost" class="submitbox">

								<div id="misc-publishing-actions">
									<div class="misc-pub-section">
									<!-- optional save and apply to all prepared listings already using this profile -->
									<?php if ( count($wpl_prepared_listings) > -1 ): ?>
										<p><?php printf( __('There are %s prepared, %s verified and %s published items using this profile.','wplister'), count($wpl_prepared_listings), count($wpl_verified_listings), count($wpl_published_listings) ) ?></p>

										<input type="checkbox" name="wpl_e2e_apply_changes_to_all_prepared" value="yes" id="apply_changes_to_all_prepared" <?php if ($wpl_prepared_listings) echo 'checked' ?>/>
										<label for="apply_changes_to_all_prepared"><?php printf( __('update %s prepared items','wplister'), count($wpl_prepared_listings) ) ?></label>
										<br class="clear" />

										<input type="checkbox" name="wpl_e2e_apply_changes_to_all_verified" value="yes" id="apply_changes_to_all_verified" <?php if ($wpl_verified_listings) echo 'checked' ?>/>
										<label for="apply_changes_to_all_verified"><?php printf( __('update %s verified items','wplister'), count($wpl_verified_listings) ) ?></label>
										<br class="clear" />

										<input type="checkbox" name="wpl_e2e_apply_changes_to_all_published" value="yes" id="apply_changes_to_all_published" <?php if ($wpl_published_listings) echo 'checked' ?>/>
										<label for="apply_changes_to_all_published"><?php printf( __('update %s published items','wplister'), count($wpl_published_listings) ) ?></label>

									<?php else: ?>
										<p>There are no prepared items using this profile.</p>
									<?php endif; ?>
									</div>
								</div>

								<div id="major-publishing-actions">
									<div id="publishing-action">
										<input type="hidden" name="action" value="save_profile" />
										<input type="hidden" name="wpl_e2e_profile_id" value="<?php echo $wpl_item['profile_id']; ?>" />
										<input type="hidden" name="return_to" value="<?php echo @$_GET['return_to']; ?>" />
										<input type="submit" value="<?php echo __('Save profile','wplister'); ?>" id="publish" class="button-primary" name="save">
									</div>
									<div class="clear"></div>
								</div>

							</div>

						</div>
					</div>


					<div class="postbox" id="LocationSettingsBox">
						<h3><span><?php echo __('Location and Taxes','wplister'); ?></span></h3>
						<div class="inside">

							<label for="wpl-text-location" class="text_label"><?php echo __('Location','wplister'); ?>: *</label>
							<input type="text" name="wpl_e2e_location" id="wpl-text-location" value="<?php echo $item_details['location']; ?>" class="text_input" />
							<br class="clear" />

							<label for="wpl-text-postcode" class="text_label"><?php echo __('Postal code','wplister'); ?>: *</label>
							<input type="text" name="wpl_e2e_postcode" id="wpl-text-postcode" value="<?php echo @$item_details['postcode']; ?>" class="text_input" />
							<br class="clear" />

							<label for="wpl-text-country" class="text_label"><?php echo __('Country','wplister'); ?>: *</label>
							<select id="wpl-text-country" name="wpl_e2e_country" title="Country" class=" required-entry select">
								<option value="">-- <?php echo __('Please select','wplister'); ?> --</option>
								<?php foreach ($wpl_countries as $country => $desc) : ?>
									<option value="<?php echo $country ?>" 
										<?php if ( $item_details['country'] == $country ) : ?>
											selected="selected"
										<?php endif; ?>
										><?php echo $desc ?></option>
								<?php endforeach; ?>
							</select>
							<br class="clear" />


							<label for="wpl-text-currency" class="text_label"><?php echo __('Currency','wplister'); ?>: *</label>
							<select id="wpl-text-currency" name="wpl_e2e_currency" title="Currency" class=" required-entry select">
								<option value="USD" <?php if ( $item_details['currency'] == 'USD' ): ?>selected="selected"<?php endif; ?>>USD</option>
								<option value="CAD" <?php if ( $item_details['currency'] == 'CAD' ): ?>selected="selected"<?php endif; ?>>CAD</option>
								<option value="EUR" <?php if ( $item_details['currency'] == 'EUR' ): ?>selected="selected"<?php endif; ?>>EUR</option>
								<option value="GBP" <?php if ( $item_details['currency'] == 'GBP' ): ?>selected="selected"<?php endif; ?>>GBP</option>
								<option value="SEK" <?php if ( $item_details['currency'] == 'SEK' ): ?>selected="selected"<?php endif; ?>>SEK</option>
								<option value="CHF" <?php if ( $item_details['currency'] == 'CHF' ): ?>selected="selected"<?php endif; ?>>CHF</option>
								<option value="AUD" <?php if ( $item_details['currency'] == 'AUD' ): ?>selected="selected"<?php endif; ?>>AUD</option>
								<option value="HKD" <?php if ( $item_details['currency'] == 'HKD' ): ?>selected="selected"<?php endif; ?>>HKD</option>
								<option value="INR" <?php if ( $item_details['currency'] == 'INR' ): ?>selected="selected"<?php endif; ?>>INR</option>
								<option value="MYR" <?php if ( $item_details['currency'] == 'MYR' ): ?>selected="selected"<?php endif; ?>>MYR</option>
								<option value="PHP" <?php if ( $item_details['currency'] == 'PHP' ): ?>selected="selected"<?php endif; ?>>PHP</option>
								<option value="PLN" <?php if ( $item_details['currency'] == 'PLN' ): ?>selected="selected"<?php endif; ?>>PLN</option>
								<option value="SGD" <?php if ( $item_details['currency'] == 'SGD' ): ?>selected="selected"<?php endif; ?>>SGD</option>
							</select>
							<br class="clear" />

							<label for="wpl-text-tax_mode" class="text_label"><?php echo __('Taxes','wplister'); ?>: *</label>
							<select id="wpl-text-tax_mode" name="wpl_e2e_tax_mode" title="Taxes" class=" required-entry select">
								<option value="">-- <?php echo __('Please select','wplister'); ?> --</option>
								<option value="none" <?php if ( $item_details['tax_mode'] == 'none' ): ?>selected="selected"<?php endif; ?>><?php echo __('no taxes','wplister'); ?></option>
								<option value="fix" <?php if ( $item_details['tax_mode'] == 'fix' ): ?>selected="selected"<?php endif; ?>><?php echo __('fixed tax rate','wplister'); ?></option>
								<option value="ebay_table" <?php if ( $item_details['tax_mode'] == 'ebay_table' ): ?>selected="selected"<?php endif; ?>><?php echo __('use Sales Tax Table','wplister'); ?></option>
								<!--<option value="product" <?php if ( $item_details['tax_mode'] == 'product' ): ?>selected="selected"<?php endif; ?>><?php echo __('apply product tax','wplister'); ?> (beta!)</option>-->
							</select>
							<br class="clear" />

							<label for="wpl-text-vat_percent" class="text_label"><?php echo __('Tax rate (percent)','wplister'); ?>:</label>
							<input type="text" name="wpl_e2e_vat_percent" id="wpl-text-vat_percent" value="<?php echo $item_details['vat_percent']; ?>" class="text_input" />
							<br class="clear" />

						</div>
					</div>


					<div class="postbox" id="TemplatesBox">
						<h3><span><?php echo __('Template','wplister'); ?></span></h3>
						<div class="inside">
							<?php foreach ($wpl_template_files as $tpl) : ?>
								<?php
									$tpl_name = $tpl['template_name'];
									$tpl_path = $tpl['template_path'];
									$checked  = ( $item_details['template'] == $tpl_path ) ? 'checked="checked"' : '';
								?>

								<input type="radio" value="<?php echo $tpl_path ?>" id="template-<?php echo basename($tpl_path) ?>" name="wpl_e2e_template" class="post-format" <?php echo $checked ?> > 
								<label for="template-<?php echo basename($tpl_path) ?>"><?php echo $tpl_name ?></label><br>

							<?php endforeach; ?>							
						</div>
					</div>


					<div class="postbox" id="TitleSettingsBox">
						<h3><span><?php echo __('Title and Subtitle','wplister'); ?></span></h3>
						<div class="inside">

							<label for="wpl-text-title_prefix" class="text_label"><?php echo __('Title prefix','wplister'); ?>:</label>
							<input type="text" name="wpl_e2e_title_prefix" id="wpl-text-title_prefix" value="<?php echo $item_details['title_prefix']; ?>" class="text_input" />
							<br class="clear" />

							<label for="wpl-text-title_suffix" class="text_label"><?php echo __('Title suffix','wplister'); ?>:</label>
							<input type="text" name="wpl_e2e_title_suffix" id="wpl-text-title_suffix" value="<?php echo $item_details['title_suffix']; ?>" class="text_input" />
							<br class="clear" />

							<p class="desc" style="display: block;">
								<?php echo __('Use these to automatically prepend and append keywords to your listing title.','wplister'); ?>
								<a href="#" onclick="jQuery('#title_help_msg').slideToggle('fast');return false;">
									<?php echo __('more info','wplister'); ?>
								</a>
							</p>
							<p id="title_help_msg" class="desc_toggle" style="display: none;">
								<?php echo __('You can use a subset of the available listing shortcodes in title prefix and suffix.','wplister'); ?>
								<br><br>
								<?php echo __('Example','wplister'); ?>: 
								If you have a product attribute "Size", use the following shortcode to include the products size in the listing title:
								<br><br>
								<code>[[attribute_Size]]</code><br>
								<hr>
							</p>


							<label for="wpl-text-subtitle_enabled" class="text_label"><?php echo __('List subtitle','wplister'); ?>:</label>
							<select id="wpl-text-subtitle_enabled" name="wpl_e2e_subtitle_enabled" title="Use additional product description as subtitle" class=" required-entry select">
								<option value="1" <?php if ( @$item_details['subtitle_enabled'] == '1' ): ?>selected="selected"<?php endif; ?>><?php echo __('Yes','wplister'); ?></option>
								<option value="0" <?php if ( @$item_details['subtitle_enabled'] != '1' ): ?>selected="selected"<?php endif; ?>><?php echo __('No','wplister'); ?></option>
							</select>
							<br class="clear" />

							<label for="wpl-text-custom_subtitle" class="text_label"><?php echo __('Custom subtitle','wplister'); ?>:</label>
							<input type="text" name="wpl_e2e_custom_subtitle" id="wpl-text-custom_subtitle" value="<?php echo @$item_details['custom_subtitle']; ?>" maxlength="55" class="text_input" />
							<br class="clear" />

							<p class="desc" style="display: block;">
								<?php echo __('Leave this empty to use the short description as subtitle.','wplister'); ?>
								<?php echo __('Will be truncated after 55 characters.','wplister'); ?>
							</p>


						</div>
					</div>


					<div class="postbox" id="VariationsSettingsBox">
						<h3><span><?php echo __('Variations','wplister'); ?></span></h3>
						<div class="inside">

							<label for="wpl-text-variations_mode" class="text_label"><?php echo __('Mode','wplister'); ?>:</label>
							<select id="wpl-text-variations_mode" name="wpl_e2e_variations_mode" title="Variation Mode" class=" required-entry select">
								<option value="default" <?php if ( @$item_details['variations_mode'] == 'default' ): ?>selected="selected"<?php endif; ?>><?php echo __('list as variations','wplister'); ?></option>
								<option value="flat"    <?php if ( @$item_details['variations_mode'] == 'flat' ): ?>selected="selected"<?php endif; ?>><?php echo __('flatten variations','wplister'); ?></option>
							</select>
							<br class="clear" />

							<label for="wpl-text-add_variations_table" class="text_label"><?php echo __('Include variations list','wplister'); ?>:</label>
							<select id="wpl-text-add_variations_table" name="wpl_e2e_add_variations_table" title="Add variations list as HTML table to item description" class=" required-entry select">
								<option value="1" <?php if ( @$item_details['add_variations_table'] == '1' ): ?>selected="selected"<?php endif; ?>><?php echo __('Yes','wplister'); ?></option>
								<option value="0" <?php if ( @$item_details['add_variations_table'] != '1' ): ?>selected="selected"<?php endif; ?>><?php echo __('No','wplister'); ?></option>
							</select>
							<br class="clear" />

							<label for="wpl-text-with_variation_images" class="text_label"><?php echo __('Variation images','wplister'); ?>:</label>
							<select id="wpl-text-with_variation_images" name="wpl_e2e_with_variation_images" title="Variation images" class=" required-entry select">
								<option value="1" <?php if ( @$item_details['with_variation_images'] == '1' ): ?>selected="selected"<?php endif; ?>><?php echo __('Yes','wplister'); ?></option>
								<option value="0" <?php if ( @$item_details['with_variation_images'] != '1' ): ?>selected="selected"<?php endif; ?>><?php echo __('No','wplister'); ?></option>
							</select>
							<br class="clear" />

						</div>
					</div>


					<div class="postbox" id="LayoutSettingsBox">
						<h3><span><?php echo __('Images','wplister'); ?></span></h3>
						<div class="inside">

							<label for="wpl-text-with_image" class="text_label"><?php echo __('Image','wplister'); ?>:</label>
							<select id="wpl-text-with_image" name="wpl_e2e_with_image" title="Image" class=" required-entry select">
								<option value="">-- <?php echo __('Please select','wplister'); ?> --</option>
								<option value="1" <?php if ( $item_details['with_image'] == '1' ): ?>selected="selected"<?php endif; ?>><?php echo __('Yes','wplister'); ?></option>
								<option value="0" <?php if ( $item_details['with_image'] == '0' ): ?>selected="selected"<?php endif; ?>><?php echo __('No','wplister'); ?></option>
							</select>
							<br class="clear" />

							<label for="wpl-text-with_gallery_image" class="text_label"><?php echo __('Gallery image','wplister'); ?>:</label>
							<select id="wpl-text-with_gallery_image" name="wpl_e2e_with_gallery_image" title="Gallery image" class=" required-entry select">
								<option value="">-- <?php echo __('Please select','wplister'); ?> --</option>
								<option value="1" <?php if ( $item_details['with_gallery_image'] == '1' ): ?>selected="selected"<?php endif; ?>><?php echo __('Yes','wplister'); ?></option>
								<option value="0" <?php if ( $item_details['with_gallery_image'] == '0' ): ?>selected="selected"<?php endif; ?>><?php echo __('No','wplister'); ?></option>
							</select>
							<br class="clear" />


						</div>
					</div>




					<div class="postbox" id="OtherSettingsBox">
						<h3><span><?php echo __('Other options','wplister'); ?></span></h3>
						<div class="inside">


							<label for="wpl-text-global_shipping" class="text_label"><?php echo __('Global Shipping','wplister'); ?>:</label>
							<select id="wpl-text-global_shipping" name="wpl_e2e_global_shipping" title="Enable Global Shipping" class=" required-entry select">
								<option value="1" <?php if ( @$item_details['global_shipping'] == '1' ): ?>selected="selected"<?php endif; ?>><?php echo __('Yes','wplister'); ?></option>
								<option value="0" <?php if ( @$item_details['global_shipping'] != '1' ): ?>selected="selected"<?php endif; ?>><?php echo __('No','wplister'); ?></option>
							</select>
							<br class="clear" />

							<label for="wpl-text-private_listing" class="text_label"><?php echo __('Private listing','wplister'); ?>:</label>
							<select id="wpl-text-private_listing" name="wpl_e2e_private_listing" title="List as private listing" class=" required-entry select">
								<option value="1" <?php if ( @$item_details['private_listing'] == '1' ): ?>selected="selected"<?php endif; ?>><?php echo __('Yes','wplister'); ?></option>
								<option value="0" <?php if ( @$item_details['private_listing'] != '1' ): ?>selected="selected"<?php endif; ?>><?php echo __('No','wplister'); ?></option>
							</select>
							<br class="clear" />

							<label for="wpl-text-use_sku_as_upc" class="text_label"><?php echo __('Use SKU as UPC','wplister'); ?>:</label>
							<select id="wpl-text-use_sku_as_upc" name="wpl_e2e_use_sku_as_upc" title="Fetch the UPC value from the products SKU" class=" required-entry select">
								<option value="1" <?php if ( @$item_details['use_sku_as_upc'] == '1' ): ?>selected="selected"<?php endif; ?>><?php echo __('Yes','wplister'); ?></option>
								<option value="0" <?php if ( @$item_details['use_sku_as_upc'] != '1' ): ?>selected="selected"<?php endif; ?>><?php echo __('No','wplister'); ?></option>
							</select>
							<br class="clear" />

							<label for="wpl-text-strikethrough_pricing" class="text_label"><?php echo __('Strikethrough price','wplister'); ?>:</label>
							<select id="wpl-text-strikethrough_pricing" name="wpl_e2e_strikethrough_pricing" title="Fetch the UPC value from the products SKU" class=" required-entry select">
								<option value="1" <?php if ( @$item_details['strikethrough_pricing'] == '1' ): ?>selected="selected"<?php endif; ?>><?php echo __('Yes','wplister'); ?></option>
								<option value="0" <?php if ( @$item_details['strikethrough_pricing'] != '1' ): ?>selected="selected"<?php endif; ?>><?php echo __('No','wplister'); ?></option>
							</select>
							<br class="clear" />

							<label for="wpl-text-counter_style" class="text_label"><?php echo __('Counter','wplister'); ?>:</label>
							<select id="wpl-text-counter_style" name="wpl_e2e_counter_style" title="Counter" class=" required-entry select">
								<option value="">-- <?php echo __('Please select','wplister'); ?> --</option>
								<option value="BasicStyle" <?php if ( $item_details['counter_style'] == 'BasicStyle' ): ?>selected="selected"<?php endif; ?>>BasicStyle</option>
								<option value="GreenLED" <?php if ( $item_details['counter_style'] == 'GreenLED' ): ?>selected="selected"<?php endif; ?>>GreenLED</option>
								<option value="HiddenStyle" <?php if ( $item_details['counter_style'] == 'HiddenStyle' ): ?>selected="selected"<?php endif; ?>>HiddenStyle</option>
								<option value="HonestyStyle" <?php if ( $item_details['counter_style'] == 'HonestyStyle' ): ?>selected="selected"<?php endif; ?>>HonestyStyle</option>
								<option value="NoHitCounter" <?php if ( $item_details['counter_style'] == 'NoHitCounter' ): ?>selected="selected"<?php endif; ?>>NoHitCounter</option>
								<option value="RetroStyle" <?php if ( $item_details['counter_style'] == 'RetroStyle' ): ?>selected="selected"<?php endif; ?>>RetroStyle</option>
							</select>
							<br class="clear" />

						</div>
					</div>


					<div class="postbox" id="HelpBox">
						<h3><span><?php echo __('Help','wplister'); ?></span></h3>
						<div class="inside">
							<p>
								Profiles can be complicated. But you only set it up once - and apply it to as many products as you wish.
							</p>
							<p>
								<b>Tip of the Day:</b><br>
								You can enter weight mapping as shipping costs
								like this: <br>
								<code>[weight|0:6.75|5:12.5|20:19.95]</code><br>
							</p>
							<p>
								This would set the shipping cost to <br>
								-  6.75 for weight below 5 kg<br>
								- 12.50 for weight above 5 kg<br>
								- 19.95 for weight above 20 kg<br>
							</p>
							<p>
								For more information visit the 
								<a href="http://www.wplab.com/plugins/wp-lister/faq/" target="_blank">FAQ</a>.
							</p>
						</div>
					</div>



