

							<!-- flat international shipping services table -->
							<table id="int_shipping_options_table_flat" class="service_table_flat service_table" style="">
								
								<tr>
									<th>
										<?php echo __('Destination','wplister'); ?>
		                                <?php wplister_tooltip('The international location or region to where the item seller will ship the item.') ?>
									</th>
									<th>
										<?php echo __('Shipping service','wplister'); ?>
		                                <?php wplister_tooltip('The international shipping service being offered by the seller to ship an item to a buyer.<br>A seller can offer up to four domestic shipping services and up to five international shipping services.') ?>
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

								<?php foreach ($item_details['int_shipping_options'] as $service) : ?>
								<tr class="row">
									<td>
										<?php if ( ! isset( $service['location'] ) ) $service['location'] = ''; // prevent undefined index warning on old profiles ?>
										<?php $service['location'] = is_array( $service['location'] ) ? $service['location'] : array( $service['location'] ); ?>
										<select multiple="multiple" class="chosen_select select_location" id="" name="wpl_e2e_int_shipping_options_flat[][location][]" data-placeholder="Select locations" style="">
											<?php
												foreach ( $wpl_shipping_locations as $location => $desc )
													echo '<option value="' . $location . '" ' . selected( ! empty( $service['location'] ) && in_array( $location, $service['location'] ), true ) . '>' . $desc . '</option>';
											?>
										</select>
										<!--
										<select name="wpl_e2e_int_shipping_options_flat[][location]" 
												title="Location" class="required-entry select select_location" style="width:100%;">
											<option value="">-- <?php echo __('Please select','wplister'); ?> --</option>
											<?php foreach ($wpl_shipping_locations as $loc => $desc) : ?>
												<option value="<?php echo $loc ?>" 
													<?php if ( @$service['location'] == $loc ) : ?>
														selected="selected"
													<?php endif; ?>
													><?php echo $desc ?></option>
											<?php endforeach; ?>
										</select>
										-->
									</td><td>
										<!-- flat shipping services -->
										<select name="wpl_e2e_int_shipping_options_flat[][service_name]" 
												title="Service" class="required-entry select select_service_name" style="width:100%;">
										<?php ProfilesPage::wpl_generate_shipping_option_tags( $wpl_int_flat_shipping_options, $service ) ?>											
										</select>
									</td><td>
										<input type="text" name="wpl_e2e_int_shipping_options_flat[][price]" 
											value="<?php echo @$service['price']; ?>" class="price_input field_price" />
									</td><td>
										<input type="text" name="wpl_e2e_int_shipping_options_flat[][add_price]" 
											value="<?php echo @$service['add_price']; ?>" class="price_input field_add_price" />
									</td><td>
										<input type="button" value="<?php echo __('remove','wplister'); ?>" class="button" 
											onclick="jQuery(this).parent().parent().remove();" />
									</td>
								</tr>
								<?php endforeach; ?>

							</table>

							<!-- calculated international shipping services table -->
							<table id="int_shipping_options_table_calc" class="service_table_calc service_table" style="">
								
								<tr>
									<th>
										<?php echo __('Shipping service','wplister'); ?>
		                                <?php wplister_tooltip('The international shipping service being offered by the seller to ship an item to a buyer.<br>A seller can offer up to four domestic shipping services and up to five international shipping services.') ?>
									</th>
									<th>
										<?php echo __('Destination','wplister'); ?>
		                                <?php wplister_tooltip('The international location or region to where the item seller will ship the item.') ?>
									</th>
									<!-- <th><?php echo __('Package','wplister'); ?></th> -->
									<!-- <th><?php echo __('Handling fee','wplister'); ?></th> -->
									<th>&nbsp;</th>
								</tr>

								<?php foreach ($item_details['int_shipping_options'] as $service) : ?>
								<tr class="row">
									<td>
										<!-- calculated shipping services -->
										<select name="wpl_e2e_int_shipping_options_calc[][service_name]"
												title="Service" class="required-entry select select_service_name" style="width:100%;">
										<?php ProfilesPage::wpl_generate_shipping_option_tags( $wpl_int_calc_shipping_options, $service ) ?>											
										</select>
									</td><td>
										<?php // make sure $service is structured correctly ?>
										<?php $service = is_array( $service ) ? $service : array(); ?>
										<?php $service = isset( $service['location'] ) ? $service : array( 'location' => array() ); ?>
										<?php $service['location'] = is_array( $service['location'] ) ? $service['location'] : array( $service['location'] ); ?>
										<select multiple="multiple" class="chosen_select select_location" id="" name="wpl_e2e_int_shipping_options_calc[][location][]" data-placeholder="Select locations" style="">
											<?php
												foreach ( $wpl_shipping_locations as $location => $desc )
													echo '<option value="' . $location . '" ' . selected( ! empty( $service['location'] ) && in_array( $location, $service['location'] ), true ) . '>' . $desc . '</option>';
											?>
										</select>
										<!--
										<select name="wpl_e2e_int_shipping_options_calc[][location]" 
												title="Location" class="required-entry select select_location" style="width:100%;">
											<option value="">-- <?php echo __('Please select','wplister'); ?> --</option>
											<?php foreach ($wpl_shipping_locations as $loc => $desc) : ?>
												<option value="<?php echo $loc ?>" 
													<?php if ( @$service['location'] == $loc ) : ?>
														selected="selected"
													<?php endif; ?>
													><?php echo $desc ?></option>
											<?php endforeach; ?>
										</select>
										-->
									</td><td>
										<input type="button" value="<?php echo __('remove','wplister'); ?>" class="button" 
											onclick="jQuery(this).parent().parent().remove();" />
									</td>
								</tr>
								<?php endforeach; ?>

							</table>

							<input type="button" value="<?php echo __('Add international shipping option','wplister'); ?>" 
								id="btn_add_int_shipping_option" 
								name="btn_add_int_shipping_option" 
								onclick="handleAddShippingServiceRow('international');"
								class="button button-add-shipping-option">

							<div class="service_table_calc int_service_table_calc" style="border-top:1px solid #ccc; margin-top:10px; padding-top:10px;">

								<label class="text_label">
									<?php echo __('Shipping discount profile','wplister'); ?>
		                            <?php wplister_tooltip('<b>Shipping Discount Profile</b><br>If you have created shipping discount profiles in your eBay account you can select one of them here to allow more control over shipping fees for combined orders.') ?>
								</label>
								<select name="wpl_e2e_shipping_int_calc_profile" id="wpl-shipping_int_calc_profile" 
										title="Type" class="required-entry select select_shipping_int_calc_profile" style="width:auto">
									<option value="">-- <?php echo __('no discount profile','wplister') ?> --</option>
									<?php foreach ($wpl_shipping_calc_profiles as $shipping_profile) : ?>
										<option value="<?php echo $shipping_profile->DiscountProfileID ?>" <?php if ( @$item_details['shipping_int_calc_profile'] == $shipping_profile->DiscountProfileID ): ?>selected="selected"<?php endif; ?>><?php echo $shipping_profile->DiscountProfileName ?></option>
									<?php endforeach; ?>
								</select>
								<br class="clear" />

								<label class="text_label">
									<?php echo __('Packaging and handling costs','wplister'); ?>:
		                            <?php wplister_tooltip('Fees a seller might assess for the shipping of the item (in addition to whatever the shipping service might charge).') ?>
								</label>
								<input type="text" name="wpl_e2e_InternationalPackagingHandlingCosts" 
									value="<?php echo @$item_details['InternationalPackagingHandlingCosts']; ?>" class="" />								

							</div>


							<div class="service_table_flat int_service_table_flat" style="border-top:1px solid #ccc; margin-top:10px; padding-top:10px;">

								<label class="text_label">
									<?php echo __('Shipping discount profile','wplister'); ?>
		                            <?php wplister_tooltip('<b>Shipping Discount Profile</b><br>If you have created shipping discount profiles in your eBay account you can select one of them here to allow more control over shipping fees for combined orders.') ?>
								</label>
								<select name="wpl_e2e_shipping_int_flat_profile" id="wpl-shipping_int_flat_profile" 
										title="Type" class="required-entry select select_shipping_int_flat_profile" style="width:auto">
									<option value="">-- <?php echo __('no discount profile','wplister') ?> --</option>
									<?php foreach ($wpl_shipping_flat_profiles as $shipping_profile) : ?>
										<option value="<?php echo $shipping_profile->DiscountProfileID ?>" <?php if ( @$item_details['shipping_int_flat_profile'] == $shipping_profile->DiscountProfileID ): ?>selected="selected"<?php endif; ?>><?php echo $shipping_profile->DiscountProfileName ?></option>
									<?php endforeach; ?>
								</select>
								
							</div>

							<div class="service_table_flat service_table_calc" >

								<label class="text_label">
									<?php echo __('Promotional shipping discount','wplister'); ?>
		                            <?php wplister_tooltip('This specifies whether to offer the promotional shipping discount for international shipping services (only applicable if the seller has a promotional shipping discount in effect at the moment.') ?>
								</label>
								<select name="wpl_e2e_InternationalPromotionalShippingDiscount" id="wpl-InternationalPromotionalShippingDiscount" 
										title="Type" class="required-entry select select_InternationalPromotionalShippingDiscount" style="width:auto">
									<option value="0" <?php if ( @$item_details['InternationalPromotionalShippingDiscount'] != 1 ): ?>selected="selected"<?php endif; ?> ><?php echo __('No','wplister') ?></option>
									<option value="1" <?php if ( @$item_details['InternationalPromotionalShippingDiscount'] == 1 ): ?>selected="selected"<?php endif; ?> ><?php echo __('Yes','wplister') ?></option>
								</select>
								
							</div>

							<?php #if ( isset( $item_details['ShipToLocations'] ) && is_array( $item_details['ShipToLocations'] ) ) : ?>
							<div class="" style="border-top:1px solid #ccc; margin-top:10px; padding-top:10px;">

								<label class="text_label">
									<?php echo __('Ship to locations','wplister'); ?>
		                            <?php wplister_tooltip('Select the locations you want to ship to. (optional)') ?>
								</label>
								<select multiple="multiple" class="chosen_select" id="wpl_e2e_ShipToLocations" name="wpl_e2e_ShipToLocations[]" data-placeholder="Select locations" style="width: 65%;">
									<?php
										foreach ( $wpl_shipping_locations as $location => $desc )
											echo '<option value="' . $location . '" ' . selected( ! empty( $item_details['ShipToLocations'] ) && in_array( $location, $item_details['ShipToLocations'] ), true ) . '>' . $desc . '</option>';
									?>
								</select>
								<br class="clear" />
								
								<label class="text_label">
									<?php echo __('Exclude locations','wplister'); ?>
		                            <?php wplister_tooltip('Select the locations you do not want to ship to. (optional)') ?>
								</label>
								<select multiple="multiple" class="chosen_select" id="wpl_e2e_ExcludeShipToLocations" name="wpl_e2e_ExcludeShipToLocations[]" data-placeholder="Select locations" style="width: 65%;">
									<?php
										foreach ( $wpl_exclude_locations as $location => $desc )
											echo '<option value="' . $location . '" ' . selected( ! empty( $item_details['ExcludeShipToLocations'] ) && in_array( $location, $item_details['ExcludeShipToLocations'] ), true ) . '>' . $desc . '</option>';
									?>
								</select>
								<br class="clear" />



							</div>
							<?php #endif; ?>
