
							<div id="freight-shipping-info" class="" style="display:none">
								<p><?php echo __('Freight shipping may be used when flat or calculated shipping cannot be used due to the greater weight of the item.','wplister'); ?></p>							
								<p><?php echo __('Currently, FreightFlat is available only for the US, UK, AU, CA and CAFR sites, and only for domestic shipping. On the US site, FreightFlat applies to shipping with carriers that are not affiliated with eBay.','wplister'); ?></p>							
								<p><?php echo __('Due to limitations in the eBay API, you still need to select at least one valid domestic shipping service. This will have no effect on the listing on eBay.','wplister'); ?></p>
							</div>


							<!-- flat shipping services table -->
							<table id="loc_shipping_options_table_flat" class="service_table_flat service_table" style="">
								
								<tr>
									<th>
										<?php echo __('Shipping service','wplister'); ?>
		                                <?php wplister_tooltip('The domestic shipping service being offered by the seller to ship an item to a buyer.<br>A seller can offer up to four domestic shipping services and up to five international shipping services.') ?>
									</th>
									<th>
										<?php echo __('First item cost','wplister'); ?>
		                                <?php wplister_tooltip('The cost to ship a single item. Enter zero to enable free shipping. This field is required.') ?>
									</th>
									<th>
										<?php echo __('Additional items cost','wplister'); ?>
		                                <?php wplister_tooltip('The cost of shipping each additional item beyond the first item.<br>This is required if the listing is for multiple items. For single-item listings, it should be zero (or is defaulted to zero if left blank).') ?>
									</th>
									<th>&nbsp;</th>
								</tr>

								<?php foreach ($item_details['loc_shipping_options'] as $service) : ?>
								<tr class="row">
									<td>
										<!-- flat shipping services -->
										<select name="wpl_e2e_loc_shipping_options_flat[][service_name]" 
												title="Service" class="required-entry select select_service_name" style="width:100%;">
										<?php ProfilesPage::wpl_generate_shipping_option_tags( $wpl_loc_flat_shipping_options, $service ) ?>											
										</select>
									</td><td>
										<input type="text" name="wpl_e2e_loc_shipping_options_flat[][price]" 
											value="<?php echo @$service['price']; ?>" class="price_input field_price" />
									</td><td>
										<input type="text" name="wpl_e2e_loc_shipping_options_flat[][add_price]" 
											value="<?php echo @$service['add_price']; ?>" class="price_input field_add_price" />
									</td><td>
										<input type="button" value="<?php echo __('remove','wplister'); ?>" class="button" 
											onclick="jQuery(this).parent().parent().remove();" />
									</td>
								</tr>
								<?php endforeach; ?>

							</table>

							<!-- calculated shipping services table -->
							<?php if ( ! $wpl_calc_shipping_enabled ) : ?>
							<div class="inline_error service_table_calc" style="background-color: #ffebe8; border: 1px solid #c00; padding: 5px 15px;">
								<?php echo __('Warning: Calculated shipping is currently only available on eBay US, Canada and Australia.','wplister'); ?>
							</div>
							<?php endif; ?>
							<table id="loc_shipping_options_table_calc" class="service_table_calc service_table" style="">
								
								<tr>
									<th>
										<?php echo __('Shipping service','wplister'); ?>
		                                <?php wplister_tooltip('The shipping service being offered by the seller to ship an item to a buyer.<br>A seller can offer up to four domestic shipping services and up to five international shipping services.') ?>
									</th>
									<th>&nbsp;</th>
								</tr>

								<?php foreach ($item_details['loc_shipping_options'] as $service) : ?>
								<tr class="row">
									<td>
										<!-- calculated shipping services -->
										<select name="wpl_e2e_loc_shipping_options_calc[][service_name]"
												title="Service" class="required-entry select select_service_name" style="width:100%;">
										<?php ProfilesPage::wpl_generate_shipping_option_tags( $wpl_loc_calc_shipping_options, $service ) ?>											
										</select>
 									</td><td>
										<input type="button" value="<?php echo __('remove','wplister'); ?>" class="button" 
											onclick="jQuery(this).parent().parent().remove();" />
									</td>
								</tr>
								<?php endforeach; ?>

							</table>

							<input type="button" value="<?php echo __('Add domestic shipping option','wplister'); ?>" 
								id="btn_add_loc_shipping_option" 
								name="btn_add_loc_shipping_option" 
								onclick="handleAddShippingServiceRow('local');"
								class="button button-add-shipping-option">

							<div class="service_table_calc loc_service_table_calc" style="border-top:1px solid #ccc; margin-top:10px; padding-top:10px;">

								<label class="text_label">
									<?php echo __('Shipping discount profile','wplister'); ?>
		                            <?php wplister_tooltip('<b>Shipping Discount Profile</b><br>If you have created shipping discount profiles in your eBay account you can select one of them here to allow more control over shipping fees for combined orders.') ?>
								</label>
								<select name="wpl_e2e_shipping_loc_calc_profile" id="wpl-shipping_loc_calc_profile" 
										title="Type" class="required-entry select select_shipping_loc_calc_profile" style="width:auto">
									<option value="">-- <?php echo __('no discount profile','wplister') ?> --</option>
									<?php foreach ($wpl_shipping_calc_profiles as $shipping_profile) : ?>
										<option value="<?php echo $shipping_profile->DiscountProfileID ?>" <?php if ( @$item_details['shipping_loc_calc_profile'] == $shipping_profile->DiscountProfileID ): ?>selected="selected"<?php endif; ?>><?php echo $shipping_profile->DiscountProfileName ?></option>
									<?php endforeach; ?>
								</select>
								<br class="clear" />

								<label class="text_label">
									<?php echo __('Enable free shipping','wplister'); ?>
		                            <?php wplister_tooltip('Free shipping can only be enabled for the first shipping service in the list.') ?>
								</label>
								<select name="wpl_e2e_shipping_loc_calc_free_shipping" id="wpl-shipping_loc_calc_free_shipping" 
										title="Type" class="required-entry select select_shipping_loc_calc_free_shipping" style="width:auto">
									<option value="0" <?php if ( @$item_details['shipping_loc_enable_free_shipping'] != 1 ): ?>selected="selected"<?php endif; ?> ><?php echo __('No','wplister') ?></option>
									<option value="1" <?php if ( @$item_details['shipping_loc_enable_free_shipping'] == 1 ): ?>selected="selected"<?php endif; ?> ><?php echo __('Yes','wplister') ?></option>
								</select>
								<br class="clear" />


								<label class="text_label">
									<?php echo __('Packaging and handling costs','wplister'); ?>
		                            <?php wplister_tooltip('Fees a seller might assess for the shipping of the item (in addition to whatever the shipping service might charge).') ?>
								</label>
								<input type="text" name="wpl_e2e_PackagingHandlingCosts" 
									value="<?php echo @$item_details['PackagingHandlingCosts']; ?>" class="" />
								
							</div>

							<div class="service_table_flat loc_service_table_flat" style="border-top:1px solid #ccc; margin-top:10px; padding-top:10px;">

								<label class="text_label">
									<?php echo __('Shipping discount profile','wplister'); ?>
		                            <?php wplister_tooltip('<b>Shipping Discount Profile</b><br>If you have created shipping discount profiles in your eBay account you can select one of them here to allow more control over shipping fees for combined orders.') ?>
								</label>
								<select name="wpl_e2e_shipping_loc_flat_profile" id="wpl-shipping_loc_flat_profile" 
										title="Type" class="required-entry select select_shipping_loc_flat_profile" style="width:auto">
									<option value="">-- <?php echo __('no discount profile','wplister') ?> --</option>
									<?php foreach ($wpl_shipping_flat_profiles as $shipping_profile) : ?>
										<option value="<?php echo $shipping_profile->DiscountProfileID ?>" <?php if ( @$item_details['shipping_loc_flat_profile'] == $shipping_profile->DiscountProfileID ): ?>selected="selected"<?php endif; ?>><?php echo $shipping_profile->DiscountProfileName ?></option>
									<?php endforeach; ?>
								</select>
								<br class="clear" />

								<label class="text_label">
									<?php echo __('Enable free shipping','wplister'); ?>
		                            <?php wplister_tooltip('Free shipping can only be enabled for the first shipping service in the list.') ?>
								</label>
								<select name="wpl_e2e_shipping_loc_flat_free_shipping" id="wpl-shipping_loc_flat_free_shipping" 
										title="Type" class="required-entry select select_shipping_loc_flat_free_shipping" style="width:auto">
									<option value="0" <?php if ( @$item_details['shipping_loc_enable_free_shipping'] != 1 ): ?>selected="selected"<?php endif; ?> ><?php echo __('No','wplister') ?></option>
									<option value="1" <?php if ( @$item_details['shipping_loc_enable_free_shipping'] == 1 ): ?>selected="selected"<?php endif; ?> ><?php echo __('Yes','wplister') ?></option>
								</select>
								<br class="clear" />
								
							</div>

							<!-- package type is required if either local or international are set to calc -->
							<div class="service_table_calc loc_service_table_calc int_service_table_calc">

								<label class="text_label">
									<?php echo __('Package type','wplister'); ?>
		                            <?php wplister_tooltip('<b>Shipping Package</b><br>The nature of the package used to ship the item(s). This is required to calculate the shipping costs.') ?>
								</label>
								<select name="wpl_e2e_shipping_package" id="wpl-shipping_package" 
										title="Type" class="required-entry select select_shipping_package" style="width:auto">
									<?php foreach ($wpl_available_shipping_packages as $shipping_package) : ?>
										<option value="<?php echo $shipping_package->ShippingPackage ?>" <?php if ( @$item_details['shipping_package'] == $shipping_package->ShippingPackage ): ?>selected="selected"<?php endif; ?>><?php echo $shipping_package->Description ?></option>
									<?php endforeach; ?>
								</select>
								<br class="clear" />

							</div>

							<div class="service_table_flat service_table_calc" >

								<label class="text_label">
									<?php echo __('Promotional shipping discount','wplister'); ?>
		                            <?php wplister_tooltip('This specifies whether to offer the promotional shipping discount for domestic shipping services (only applicable if the seller has a promotional shipping discount in effect at the moment.') ?>
								</label>
								<select name="wpl_e2e_PromotionalShippingDiscount" id="wpl-PromotionalShippingDiscount" 
										title="Type" class="required-entry select select_PromotionalShippingDiscount" style="width:auto">
									<option value="0" <?php if ( @$item_details['PromotionalShippingDiscount'] != 1 ): ?>selected="selected"<?php endif; ?> ><?php echo __('No','wplister') ?></option>
									<option value="1" <?php if ( @$item_details['PromotionalShippingDiscount'] == 1 ): ?>selected="selected"<?php endif; ?> ><?php echo __('Yes','wplister') ?></option>
								</select>
								
							</div>

							<?php if ( isset( $wpl_seller_shipping_profiles ) && is_array( $wpl_seller_shipping_profiles ) ): ?>
							<label for="wpl-text-seller_shipping_profile_id" class="text_label">
								<?php echo __('Shipping profile','wplister'); ?> (beta)
                                <?php wplister_tooltip('Instead of setting your shipping details in WP-Lister you can select a predefined shipping profile from your eBay account.') ?>
							</label>
							<select id="wpl-text-seller_shipping_profile_id" name="wpl_e2e_seller_shipping_profile_id" class=" required-entry select">
								<option value="">-- <?php echo __('no profile','wplister'); ?> --</option>
								<?php foreach ($wpl_seller_shipping_profiles as $seller_profile ) : ?>
									<option value="<?php echo $seller_profile->ProfileID ?>" 
										<?php if ( @$item_details['seller_shipping_profile_id'] == $seller_profile->ProfileID ) : ?>
											selected="selected"
										<?php endif; ?>
										><?php echo $seller_profile->ProfileName . ' - ' . $seller_profile->ShortSummary ?></option>
								<?php endforeach; ?>
							</select>
							<br class="clear" />
							<?php endif; ?>

