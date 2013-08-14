
<style type="text/css">

	input.price_input {
		width: 100%;
	}
	
	.service_table th {
		text-align: left;
	}
	
	/* shipping service type */
	select.select_shipping_type {
		width: auto;
		position: absolute;
		right: 4px;
		top: 4px;
		line-height: 20px;
		height: 21px;
		font-size: 12px;
	}

</style>


					<div class="postbox" id="ShippingOptionsBox">
						<h3><span><?php echo __('Shipping Options','wplister'); ?></span>
							<!-- service type selector -->
							<select name="wpl_e2e_shipping_service_type" id="wpl-text-loc_shipping_service_type" 
									class="required-entry select select_shipping_type" style="width:auto;"
									onchange="handleShippingTypeSelectionChange(this)">
								<option value="flat" <?php if ( @$item_details['shipping_service_type'] == 'flat' ): ?>selected="selected"<?php endif; ?>><?php echo __('Use Flat Shipping','wplister'); ?></option>
								<option value="calc" <?php if ( @$item_details['shipping_service_type'] == 'calc' ): ?>selected="selected"<?php endif; ?>><?php echo __('Use Calculated Shipping','wplister'); ?></option>
								<option value="FlatDomesticCalculatedInternational" <?php if ( @$item_details['shipping_service_type'] == 'FlatDomesticCalculatedInternational' ): ?>selected="selected"<?php endif; ?>><?php echo __('Use Flat Domestic and Calculated International Shipping','wplister'); ?></option>
								<option value="CalculatedDomesticFlatInternational" <?php if ( @$item_details['shipping_service_type'] == 'CalculatedDomesticFlatInternational' ): ?>selected="selected"<?php endif; ?>><?php echo __('Use Calculated Domestic and Flat International Shipping','wplister'); ?></option>
								<option value="FreightFlat" <?php if ( @$item_details['shipping_service_type'] == 'FreightFlat' ): ?>selected="selected"<?php endif; ?>><?php echo __('Use Freight Shipping','wplister'); ?></option>
							</select>
						</h3>
						<div class="inside">

							<?php include('edit_shipping_loc.php') ?>

						</div>
					</div>


					<div class="postbox" id="IntShippingOptionsBox">
						<h3><span><?php echo __('International shipping','wplister'); ?></span></h3>
						<div class="inside">

							<?php include('edit_shipping_int.php') ?>

						</div>
					</div>



<script type="text/javascript">

	<?php include('edit_shipping.js') ?>

</script>
