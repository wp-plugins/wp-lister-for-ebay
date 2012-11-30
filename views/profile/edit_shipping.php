<?php

/* functions */

function wpl_generate_shipping_option_tags( $services, $selected_service ) {
	?>

	<option value="">-- <?php echo __('Please select','wplister'); ?> --</option>
	
	<?php $lastShippingCategory = @$services[0]['ShippingCategory'] ?>
	<optgroup label="<?php echo @$services[0]['ShippingCategory'] ?>">
	
	<?php foreach ($services as $service) : ?>
		
		<?php if ( $lastShippingCategory != $service['ShippingCategory'] ) : ?>
		</optgroup>
		<optgroup label="<?php echo $service['ShippingCategory'] ?>">
		<?php $lastShippingCategory = $service['ShippingCategory'] ?>
		<?php endif; ?>

		<option value="<?php echo $service['service_name'] ?>" 
			<?php if ( @$selected_service['service_name'] == $service['service_name'] ) : ?>
				selected="selected"
			<?php endif; ?>
			><?php echo $service['service_description'] ?></option>
	<?php endforeach; ?>
	</optgroup>

	<?php	
}


?>


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
						</h3>
						<div class="inside">

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
										<?php wpl_generate_shipping_option_tags( $wpl_loc_flat_shipping_options, $service ) ?>											
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


							<input type="button" value="<?php echo __('Add domestic shipping option','wplister'); ?>" name="btn_add_loc_shipping_option" 
								onclick="handleAddShippingServiceRow('local');"
								class="button-secondary">



						</div>
					</div>


					<div class="postbox" id="IntShippingOptionsBox">
						<h3><span><?php echo __('International shipping','wplister'); ?></span></h3>
						<div class="inside">


							<!-- flat international shipping services table -->
							<table id="int_shipping_options_table_flat" class="service_table_flat service_table" style="">
								
								<tr>
									<th><?php echo __('Destination','wplister'); ?></th>
									<th><?php echo __('Shipping service','wplister'); ?></th>
									<th><?php echo __('First item cost','wplister'); ?></th>
									<th><?php echo __('Additional items cost','wplister'); ?></th>
									<th>&nbsp;</th>
								</tr>

								<?php foreach ($item_details['int_shipping_options'] as $service) : ?>
								<tr class="row">
									<td>
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
									</td><td>
										<!-- flat shipping services -->
										<select name="wpl_e2e_int_shipping_options_flat[][service_name]" 
												title="Service" class="required-entry select select_service_name" style="width:100%;">
										<?php wpl_generate_shipping_option_tags( $wpl_int_flat_shipping_options, $service ) ?>											
										</select>
									</td><td>
										<input type="text" name="wpl_e2e_int_shipping_options_flat[][price]" 
											value="<?php echo @$service['price']; ?>" class="price_input field_price" />
									</td><td>
										<input type="text" name="wpl_e2e_int_shipping_options_flat[][add_price]" 
											value="<?php echo @$service['add_price']; ?>" class="price_input field_add_price" />
									</td><td>
										<input type="button" value="<?php echo __('remove','wplister'); ?>" class="button-secondary" 
											onclick="jQuery(this).parent().parent().remove();" />
									</td>
								</tr>
								<?php endforeach; ?>

							</table>


							<input type="button" value="<?php echo __('Add international shipping option','wplister'); ?>" name="btn_add_loc_shipping_option" 
								onclick="handleAddShippingServiceRow('international');"
								class="button-secondary">


						</div>
					</div>




	<script type="text/javascript">


		// handle add shipping service table row
		function handleAddShippingServiceRow( mode ) {
			
			var shipping_type = jQuery('.select_shipping_type')[0] ? jQuery('.select_shipping_type')[0].value : 'flat';
			if ( shipping_type == 'flat' ) {
				var serviceTable_id = mode == 'local' ? '#loc_shipping_options_table_flat' : '#int_shipping_options_table_flat';
			} else {
				var serviceTable_id = mode == 'local' ? '#loc_shipping_options_table_calc' : '#int_shipping_options_table_calc';
			}

			var serviceTable = jQuery(serviceTable_id);

			// clone the first row and append to table
			serviceTable.find('tr.row').first().clone().appendTo( serviceTable );

			// serviceTable.find('tr.row').last().find('.select_shipping_type').change();

			enumerateShippingTableFields();

		}

		// enumerate shipping table fields
		function enumerateShippingTableFields() {
			
			jQuery('.service_table').each( function( index, item ){

				var thisDest = 'loc'  == item.id.substring( 0, 3 ) ? 'loc' : 'int';
				var thisType = 'flat' == item.id.substring( item.id.length - 4 ) ? 'flat' : 'calc';

				// service_name
				fields = jQuery(item).find('.select_service_name');
				for (var i = fields.length - 1; i >= 0; i--) {
					jQuery(fields[i]).attr('name','wpl_e2e_'+thisDest+'_shipping_options_'+thisType+'['+i+'][service_name]');
				};

				// shipping_package
				fields = jQuery(item).find('.select_shipping_package');
				for (var i = fields.length - 1; i >= 0; i--) {
					jQuery(fields[i]).attr('name','wpl_e2e_'+thisDest+'_shipping_options_'+thisType+'['+i+'][ShippingPackage]');
				};

				// location / destinaton
				fields = jQuery(item).find('.select_location');
				for (var i = fields.length - 1; i >= 0; i--) {
					jQuery(fields[i]).attr('name','wpl_e2e_'+thisDest+'_shipping_options_'+thisType+'['+i+'][location]');
				};

				// price field
				fields = jQuery(item).find('.field_price');
				for (var i = fields.length - 1; i >= 0; i--) {
					jQuery(fields[i]).attr('name','wpl_e2e_'+thisDest+'_shipping_options_'+thisType+'['+i+'][price]');
				};

				// additional price field
				fields = jQuery(item).find('.field_add_price');
				for (var i = fields.length - 1; i >= 0; i--) {
					jQuery(fields[i]).attr('name','wpl_e2e_'+thisDest+'_shipping_options_'+thisType+'['+i+'][add_price]');
				};

			});
		}


		jQuery( document ).ready(
			function () {


				enumerateShippingTableFields();

			}
		);
	
	
	</script>
