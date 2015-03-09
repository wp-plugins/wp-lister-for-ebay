
		// handle shipping service type selection
		function handleShippingTypeSelectionChange( typeselector ) {
			
			var serviceType = jQuery(typeselector).val()
			// var thisRow = jQuery(typeselector).parent().parent('.row');

			if ( serviceType == 'calc') {
				jQuery('.service_table_flat').hide();
				jQuery('.service_table_calc').show();
			} else if ( serviceType == 'FlatDomesticCalculatedInternational') {
				jQuery('#loc_shipping_options_table_flat').show();
				jQuery('#int_shipping_options_table_calc').show();
				jQuery('#loc_shipping_options_table_calc').hide();
				jQuery('#int_shipping_options_table_flat').hide();
				jQuery('.loc_service_table_calc').hide();
				jQuery('.int_service_table_flat').hide();
				jQuery('.loc_service_table_flat').show();
				jQuery('.int_service_table_calc').show();
			} else if ( serviceType == 'CalculatedDomesticFlatInternational') {
				jQuery('#loc_shipping_options_table_flat').hide();
				jQuery('#int_shipping_options_table_calc').hide();
				jQuery('#loc_shipping_options_table_calc').show();
				jQuery('#int_shipping_options_table_flat').show();
				jQuery('.loc_service_table_flat').hide();
				jQuery('.int_service_table_calc').hide();
				jQuery('.loc_service_table_calc').show();
				jQuery('.int_service_table_flat').show();
			} else if ( serviceType == 'disabled' ) {
				jQuery('#loc_shipping_options_table_flat').hide();
				jQuery('#int_shipping_options_table_calc').hide();
				jQuery('#loc_shipping_options_table_calc').hide();
				jQuery('#int_shipping_options_table_flat').hide();
				jQuery('.int_service_table_calc').hide();
				jQuery('.loc_service_table_calc').hide();
			} else if ( serviceType == 'FreightFlat' ) {
				jQuery('#loc_shipping_options_table_flat').show();
				jQuery('#int_shipping_options_table_calc').hide();
				jQuery('#loc_shipping_options_table_calc').hide();
				jQuery('#int_shipping_options_table_flat').hide();
				jQuery('.int_service_table_calc').hide();
				jQuery('.loc_service_table_calc').hide();
			} else {
				jQuery('.service_table_calc').hide();
				jQuery('.service_table_flat').show();
			}

			if ( serviceType == 'disabled') {
				jQuery('.ebay_shipping_options_wrapper').hide();
			} else {
				jQuery('.ebay_shipping_options_wrapper').show();				
			}

			if ( serviceType == 'FreightFlat') {
				jQuery('#freight-shipping-info').show();
				jQuery('#btn_add_int_shipping_option').hide();
			} else {
				jQuery('#freight-shipping-info').hide();
				jQuery('#btn_add_int_shipping_option').show();				
			}

		}

		// handle add shipping service table row
		function handleAddShippingServiceRow( mode ) {
			
			// disable chosen before cloning fields
			jQuery("select.wple_chosen_select").chosen('destroy');

			var shipping_type = jQuery('.select_shipping_type')[0] ? jQuery('.select_shipping_type')[0].value : 'flat';
			if ( shipping_type == 'flat' ) {
				var serviceTable_id = mode == 'local' ? '#loc_shipping_options_table_flat' : '#int_shipping_options_table_flat';
			} else if ( shipping_type == 'FlatDomesticCalculatedInternational' ) {
				var serviceTable_id = mode == 'local' ? '#loc_shipping_options_table_flat' : '#int_shipping_options_table_calc';
			} else if ( shipping_type == 'CalculatedDomesticFlatInternational' ) {
				var serviceTable_id = mode == 'local' ? '#loc_shipping_options_table_calc' : '#int_shipping_options_table_flat';
			} else { // calc
				var serviceTable_id = mode == 'local' ? '#loc_shipping_options_table_calc' : '#int_shipping_options_table_calc';
			}

			var serviceTable = jQuery(serviceTable_id);

			// clone the first row and append to table
			serviceTable.find('tr.row').first().clone().appendTo( serviceTable );

			// serviceTable.find('tr.row').last().find('.select_shipping_type').change();

			enumerateShippingTableFields();

			// enable chosen.js again
			jQuery("select.wple_chosen_select").chosen();

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
					jQuery(fields[i]).attr('name','wpl_e2e_'+thisDest+'_shipping_options_'+thisType+'['+i+'][location][]')
									 .attr('id','wpl_e2e_'+thisDest+'_shipping_options_'+thisType+'_location_choser_'+i+''); // select tags should have a unique id for chosen.js ()
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

				// update ui for selected shipping service type
				jQuery('.select_shipping_type').change();

				enumerateShippingTableFields();


			    // 
			    // Validation
			    // 

				// check required values on submit
				// jQuery('input#publish').on('click', function() {
				jQuery('form#post').on('submit', function() {
					
					// validate shipping options
					var shipping_type = jQuery('.select_shipping_type')[0] ? jQuery('.select_shipping_type')[0].value : 'disabled';
					var seller_profile = jQuery('#wpl-text-seller_shipping_profile_id')[0] ? jQuery('#wpl-text-seller_shipping_profile_id')[0].value : false;

					if ( ! seller_profile ) {

						// check domestic shipping options
						if ( shipping_type == 'flat' || shipping_type == 'FreightFlat' || shipping_type == 'FlatDomesticCalculatedInternational' ) {

							// local flat shipping option required
							if ( jQuery('#loc_shipping_options_table_flat .select_service_name')[0].value == '' ) {
								alert('Please select at least one domestic shipping service for eBay.'); return false;
							}
	
							// local flat shipping price required
							if ( jQuery('#loc_shipping_options_table_flat input.price_input')[0].value == '' ) {
								alert('Please enter a shipping fee for eBay.'); return false;
							}

							// max 5 shipping service options
							if ( jQuery('#loc_shipping_options_table_flat .select_service_name').length > 5 ) {
								alert('You have selected more than 5 local shipping services, which is not allowed by eBay.'); return false;
							}

						} else if ( shipping_type == 'calc' || shipping_type == 'CalculatedDomesticFlatInternational' ) {

							// local calc shipping option required
							if ( jQuery('#loc_shipping_options_table_calc .select_service_name')[0].value == '' ) {
								alert('Please select at least one domestic shipping service for eBay.'); return false;
							}						

							// max 5 shipping service options
							if ( jQuery('#loc_shipping_options_table_calc .select_service_name').length > 5 ) {
								alert('You have selected more than 5 local shipping services, which is not allowed by eBay.'); return false;
							}

						}

						// max 5 international shipping service options
						if ( shipping_type == 'flat' || shipping_type == 'FreightFlat' || shipping_type == 'CalculatedDomesticFlatInternational' ) {
							if ( jQuery('#int_shipping_options_table_flat .select_service_name').length > 5 ) {
								alert('You have selected more than 5 international shipping services, which is not allowed by eBay.'); return false;
							}
						} else if ( shipping_type == 'calc' || shipping_type == 'FlatDomesticCalculatedInternational' ) {
							if ( jQuery('#int_shipping_options_table_calc .select_service_name').length > 5 ) {
								alert('You have selected more than 5 international shipping services, which is not allowed by eBay.'); return false;
							}
						}

					}

					return true;
				})


			}
		);
	
