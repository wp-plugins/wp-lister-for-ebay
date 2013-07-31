
							<div id="freight-shipping-info" class="" style="display:none">
								<p><?php echo __('Freight shipping may be used when flat or calculated shipping cannot be used due to the greater weight of the item.','wplister'); ?></p>							
								<p><?php echo __('Currently, FreightFlat is available only for the US, UK, AU, CA and CAFR sites, and only for domestic shipping. On the US site, FreightFlat applies to shipping with carriers that are not affiliated with eBay.','wplister'); ?></p>							
								<p><?php echo __('Due to limitations in the eBay API, you still need to select at least one valid domestic shipping service. This will have no effect on the listing on eBay.','wplister'); ?></p>
							</div>


							<!-- flat shipping services table -->
							<table id="loc_shipping_options_table_flat" class="service_table_flat service_table" style="">
								
								<tr>
									<th><?php echo __('Shipping service','wplister'); ?> *</th>
									<th><?php echo __('First item cost','wplister'); ?> *</th>
									<th><?php echo __('Additional items cost','wplister'); ?></th>
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
										<input type="button" value="<?php echo __('remove','wplister'); ?>" class="button-secondary" 
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
									<th><?php echo __('Shipping service','wplister'); ?> *</th>
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
										<input type="button" value="<?php echo __('remove','wplister'); ?>" class="button-secondary" 
											onclick="jQuery(this).parent().parent().remove();" />
									</td>
								</tr>
								<?php endforeach; ?>

							</table>

							<input type="button" value="<?php echo __('Add domestic shipping option','wplister'); ?>" 
								id="btn_add_loc_shipping_option" 
								name="btn_add_loc_shipping_option" 
								onclick="handleAddShippingServiceRow('local');"
								class="button-secondary button-add-shipping-option">

							<div class="service_table_calc loc_service_table_calc" style="border-top:1px solid #ccc; margin-top:10px; padding-top:10px;">

								<label class="text_label"><?php echo __('Package type','wplister'); ?>:</label>
								<select name="wpl_e2e_shipping_package" id="wpl-shipping_package" 
										title="Type" class="required-entry select select_shipping_package" style="width:auto">
									<?php foreach ($wpl_available_shipping_packages as $shipping_package) : ?>
										<option value="<?php echo $shipping_package->ShippingPackage ?>" <?php if ( @$item_details['shipping_package'] == $shipping_package->ShippingPackage ): ?>selected="selected"<?php endif; ?>><?php echo $shipping_package->Description ?></option>
									<?php endforeach; ?>
								</select>
								<br class="clear" />


								<label class="text_label"><?php echo __('Packaging and handling costs','wplister'); ?>:</label>
								<input type="text" name="wpl_e2e_PackagingHandlingCosts" 
									value="<?php echo @$item_details['PackagingHandlingCosts']; ?>" class="" />
								
							</div>

