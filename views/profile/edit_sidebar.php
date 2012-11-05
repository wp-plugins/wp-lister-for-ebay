<style type="text/css">

	#side-sortables .postbox input.text_input,
	#side-sortables .postbox select.select {
	    width: 50%;
	}
	#side-sortables .postbox label.text_label {
	    width: 45%;
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
									<?php if ( count($wpl_prepared_auctions) > 0 ): ?>
										<p><?php printf( __('There are %s prepared items using this profile.','wplister'), count($wpl_prepared_auctions) ) ?></p>
										<input type="checkbox" name="wpl_e2e_apply_changes_to_all_prepared" value="yes" id="apply_changes_to_all_prepared" />
										<label for="apply_changes_to_all_prepared"><?php printf( __('update prepared items','wplister'), count($wpl_prepared_auctions) ) ?></label>
									<?php else: ?>
										<p>There are no prepared items using this profile.</p>
									<?php endif; ?>
									</div>
								</div>

								<div id="major-publishing-actions">
									<div id="publishing-action">
										<input type="hidden" name="action" value="save_profile" />
										<input type="hidden" name="wpl_e2e_profile_id" value="<?php echo $wpl_item['profile_id']; ?>" />
										<input type="submit" value="<?php echo __('Save profile','wplister'); ?>" id="publish" class="button-primary" name="save">
									</div>
									<div class="clear"></div>
								</div>

							</div>

						</div>
					</div>


					<div class="postbox" id="LocationSettingsBox">
						<h3><span><?php echo __('Location settings','wplister'); ?></span></h3>
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
								<option value="product" <?php if ( $item_details['tax_mode'] == 'product' ): ?>selected="selected"<?php endif; ?>><?php echo __('apply product tax','wplister'); ?> (beta!)</option>
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


					<div class="postbox" id="LayoutSettingsBox">
						<h3><span><?php echo __('Layout','wplister'); ?></span></h3>
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


							<label for="wpl-text-with_variation_images" class="text_label"><?php echo __('Variation images','wplister'); ?>:</label>
							<select id="wpl-text-with_variation_images" name="wpl_e2e_with_variation_images" title="Variation images" class=" required-entry select">
								<option value="1" <?php if ( @$item_details['with_variation_images'] == '1' ): ?>selected="selected"<?php endif; ?>><?php echo __('Yes','wplister'); ?></option>
								<option value="0" <?php if ( @$item_details['with_variation_images'] != '1' ): ?>selected="selected"<?php endif; ?>><?php echo __('No','wplister'); ?></option>
							</select>
							<br class="clear" />

							<label for="wpl-text-subtitle_enabled" class="text_label"><?php echo __('List subtitle','wplister'); ?>:</label>
							<select id="wpl-text-subtitle_enabled" name="wpl_e2e_subtitle_enabled" title="Use additional product description as subtitle" class=" required-entry select">
								<option value="1" <?php if ( @$item_details['subtitle_enabled'] == '1' ): ?>selected="selected"<?php endif; ?>><?php echo __('Yes','wplister'); ?></option>
								<option value="0" <?php if ( @$item_details['subtitle_enabled'] != '1' ): ?>selected="selected"<?php endif; ?>><?php echo __('No','wplister'); ?></option>
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



