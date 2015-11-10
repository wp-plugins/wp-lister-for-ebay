<style type="text/css">

	/* item specifics */
 	.select_specs,
 	.select_specs_attrib,
 	.input_specs {
 		width: 100%;
 	}
	#ItemSpecifics_container input.disabled {
		background-color: #fff;
		color: #000;
	}
	#ItemSpecifics_container .input_specs {
		box-sizing: border-box;
		-webkit-border-radius: 3px;
		border-radius: 3px;
		border-width: 1px;
		border-style: solid;
		border-color: #DFDFDF;
		background-color: white;
		color: #333;
		line-height: 16px;
	}
	.ui-autocomplete li a {
		font-size: 12px;
		line-height: 16px;
	}
	.ui-widget-content .ui-state-hover {
		border: none;
	}

</style>


					<script id="tpl_ItemSpecifics" type="text/html">
						<tr>
							<td>
								{{#isRequired}}	
									<input type="hidden" name="itmSpecs_name[{{id}}]" value="{{Name}}" />
									<input type="text" name="disabled_itmSpecs_name[{{id}}]" 
									disabled class="disabled" value="{{Name}}"  /> *
								{{/isRequired}} 
								{{^isRequired}}	
									<input type="hidden" name="itmSpecs_name[{{id}}]" value="{{Name}}" />
									<input type="text" name="disabled_itmSpecs_name[{{id}}]" 
									disabled class="disabled" value="{{Name}}"  />
								{{/isRequired}} 
							</td>
							<td>
								{{#isSelectionOnly}}
									<select name="itmSpecs_value[{{id}}]" id="itmSpecs_value_{{id}}" class="select_specs">
										<option value="">---</option>
										{{#recommendedValues}}
										<option value="{{.}}">{{.}}</option>
										{{/recommendedValues}}
									</select>
								{{/isSelectionOnly}}
								{{^isSelectionOnly}}
									<input name="itmSpecs_value[{{id}}]" id="itmSpecs_value_{{id}}"  class="input_specs" 
										value="{{setValue}}" />
								{{/isSelectionOnly}}
							</td>
							<!--<td>&nbsp;</td>-->
							<td>
								<select name="itmSpecs_attrib[{{id}}]" id="itmSpecs_attrib_{{id}}" class="select_specs_attrib">
									<optgroup label="<?php echo __('Product Attributes','wplister') ?>">
										<option value="">---</option>
										{{#AvailableAttributes}}
										<option value="{{name}}">{{label}}</option>
										{{/AvailableAttributes}}
									</optgroup>
									<optgroup label="<?php echo __('Custom Attributes','wplister') ?>">
										{{#CustomAttributes}}
										<option value="{{name}}">{{label}}</option>
										{{/CustomAttributes}}
									</optgroup>
								</select>
							</td>
						</tr>
					</script>
					<script id="tpl_ItemSpecifics_tableHeader" type="text/html">
						<tr>
							<th>Item Specifics Name</th>
							<th>use Custom Value</th>
							<!--<th width="5%">&nbsp;</th>-->
							<th>pull Value from Attribute</th>
						</tr>
					</script>

					<?php
						// get item specifics as json

						// $specifics contains all available item specifics for the selected category
						if ( ! isset( $specifics ) || empty( $specifics ) ) {
							// $specifics = isset($wpl_item['category_specifics']) ? maybe_unserialize( $wpl_item['category_specifics'] ) : false;
							$specifics = isset($wpl_specifics) ? maybe_unserialize( $wpl_specifics ) : false;
						}

						// $item_specifics contains values set for this particular product / profile
						if ( ! isset( $item_specifics ) || empty( $item_specifics ) )
							$item_specifics = isset( $item_details['item_specifics'] ) ? $item_details['item_specifics'] : '';

						// convert empty variable to empty array
						if ( ! is_array( $specifics      ) ) $specifics      = array();
						if ( ! is_array( $item_specifics ) ) $item_specifics = array();
					?>
					<script type="text/javascript">

						var CategorySpecificsData    = <?php echo json_encode( $specifics ) ?>;
						var AvailableAttributes      = <?php echo json_encode( $wpl_available_attributes ) ?>;
						var CustomAttributes         = <?php echo json_encode( $wpl_custom_attributes ) ?>;
						var CurrentItemSpecifics     = <?php echo json_encode( $item_specifics ) ?>;
						var default_ebay_category_id = <?php echo @$wpl_default_ebay_category_id ? $wpl_default_ebay_category_id : 0 ?>;
						var is_profile_page          = <?php echo ( isset($_GET['profile']) || ( isset($_GET['action']) && $_GET['action'] == 'add_new_profile' ) ) ? 1 : 0; ?>;

						var wpl_site_id    = '<?php echo $wpl_site_id ?>';
						var wpl_account_id = '<?php echo $wpl_account_id ?>';

						// handle new primary category
						// update item specifics
						function updateItemSpecifics() {
							var primary_category_id = jQuery('#ebay_category_id_1')[0].value;

							jQuery('#EbayItemSpecificsBox .inside').slideUp(500);
							jQuery('#EbayItemSpecificsBox .loadingMsg').slideDown(500);

					        // fetch category specifics
					        var params = {
								action: 	'wpl_getCategorySpecifics',
								id: 		primary_category_id,
								site_id: 	wpl_site_id,
								account_id: wpl_account_id,
								nonce: 		'TODO'
					        };
					        var jqxhr = jQuery.getJSON( ajaxurl, params )
					        .success( function( response ) { 

					            // console.log( 'response: ', response ); 
					            CategorySpecificsData = response;

					            buildItemSpecifics();
								jQuery('#EbayItemSpecificsBox .inside').slideDown(500);
								jQuery('#EbayItemSpecificsBox .loadingMsg').slideUp(500);

					        })
					        .error( function(e,xhr,error) { 
					            console.log( "error", xhr, error ); 
					            console.log( e.responseText ); 
					        });			
						}

						// built item specifics table
						function buildItemSpecifics() {

							var tpl                 = jQuery('#tpl_ItemSpecifics').html();
							var tpl_head            = jQuery('#tpl_ItemSpecifics_tableHeader').html();
							var primary_category_id = jQuery('#ebay_category_id_1')[0].value;
							var container           = jQuery('#ItemSpecifics_container');
							// var specs               = CategorySpecificsData[ primary_category_id ];
							var specs               = CategorySpecificsData;

							// // possibly use default category
							// if ( ( ! specs ) && ( default_ebay_category_id ) ) {
							// 	specs = CategorySpecificsData[ default_ebay_category_id ];
							// }

							// console.log('specs: ',specs);
							// console.log('CategorySpecificsData: ',CategorySpecificsData);
							// console.log('default_ebay_category_id: ',default_ebay_category_id);
							// console.log('primary_category_id: ',primary_category_id);

							if ( ( ! specs ) || ( specs == 'none' ) ) {
								container.html( '<?php echo __('There are no recommended item specifics for the primary category.','wplister') ?>' );
								return;
							}

							// clear container
							container.html( tpl_head );

							if ( (specs) && (specs.length > 0) )
							for (var i = 0; i < specs.length; i++) {

								// ignore invalid data - Name is required
								if ( specs[i].Name == null ) continue;
								
								// create template view
								var spec = specs[i];
								spec.id                  = spec.Name.replace(/[^A-Za-z0-9]/g,'');
								spec.isSelectionOnly     = spec.SelectionMode == 'SelectionOnly' ? true : false;
								spec.isRequired          = spec.MinValues > 0 ? true : false;
								spec.AvailableAttributes = AvailableAttributes;
								spec.CustomAttributes    = CustomAttributes;

								// apply current settings
								if ( (CurrentItemSpecifics) && (CurrentItemSpecifics.length > 0) )
								for (var k = CurrentItemSpecifics.length - 1; k >= 0; k--) {
									if ( spec.Name == CurrentItemSpecifics[k].name ) {
										// console.log('match for: ', spec.Name);
										spec.setValue     = stripslashes( CurrentItemSpecifics[k].value );
										spec.setAttribute = CurrentItemSpecifics[k].attribute;
									}
								};
								
								// render template and append to table
								newHtml = Mustache.render( tpl, spec );
								container.append( newHtml );
								
								// create combobox or autocompleter
								if ( spec.isSelectionOnly ) {
									// jQuery('#itmSpecs_value_'+spec.id).combobox();
								} else {
									jQuery('#itmSpecs_value_'+spec.id).autocomplete({ source: spec.recommendedValues });																				
								}

								// fix JS Syntax error for values that contain single quotes (like "Kim's Brand")
								if ( 'string' == typeof spec.setValue ) {
									spec.setValue = spec.setValue.replace(/'/g, "\\'");
								}

								// set Brand / MPN by default
								if ( 'MPN'   == spec.id && is_profile_page && spec.setAttribute == undefined ) {
									spec.setAttribute = '_ebay_mpn';
								}
								if ( 'Brand' == spec.id && is_profile_page && spec.setAttribute == undefined ) {
									spec.setAttribute = '_ebay_brand';
								}

								// restore selection
								jQuery('select#itmSpecs_value_'+spec.id+" option[value='"+spec.setValue+"']").attr('selected',true);
								jQuery('select#itmSpecs_attrib_'+spec.id+" option[value='"+spec.setAttribute+"']").attr('selected',true);

							};

						}

						// init item specifics when page is loaded
						jQuery( document ).ready( function () {
							buildItemSpecifics();
						});	


						function stripslashes (str) {
						    return (str + '').replace(/\\(.?)/g, function (s, n1) {
							    switch (n1) {
								    case '\\':
								        return '\\';
								    case '0':
								        return '\u0000';
								    case '':
								        return '';
								    default:
								        return n1;
							    }
						    });
						}

					</script>				

					<?php if ( isset($_GET['profile']) || ( isset($_GET['action']) && $_GET['action'] == 'add_new_profile' ) ) : ?>
					<div class="postbox" id="EbayItemSpecificsBox">
						<h3><span><?php echo __('Item Specifics','wplister'); ?></span></h3>
					<?php else: ?>
					<div id="EbayItemSpecificsBox">
					<?php endif; ?>

						<div class="inside">
							<table id="ItemSpecifics_container" style="width:100%"></table>
							<!-- <pre><?php #print_r($specifics) ?></pre> -->
							<!-- <pre><?php #print_r($item_details['item_specifics']) ?></pre> -->
						</div>
						<div class="loadingMsg" style="display:none;">
							<div style="text-align:center;padding:50px;font-style:italics;">
								<img src="<?php echo WPLISTER_URL ?>/img/ajax-loader-f9.gif" /><br><br>
								<i>loading item specifics...</i>
							</div>
						</div>

					</div>


	
<style>
	.ui-combobox {
		position: relative;
		display: inline-block;
	}
	.ui-combobox-toggle {
		position: absolute;
		top: 0;
		bottom: 0;
		margin-left: -1px;
		padding: 0;
		/* adjust styles for IE 6/7 */
		*height: 1.7em;
		*top: 0.1em;
	}
	.ui-combobox-input {
		margin: 0;
		padding: 0.3em;
	}
</style>

<script>
	(function( jQuery ) {
		jQuery.widget( "ui.combobox", {
			_create: function() {
				var input,
					self = this,
					select = this.element.hide(),
					selected = select.children( ":selected" ),
					value = selected.val() ? selected.text() : "",
					wrapper = this.wrapper = jQuery( "<span>" )
						.addClass( "ui-combobox" )
						.insertAfter( select );

				input = jQuery( "<input>" )
					.appendTo( wrapper )
					.val( value )
					.addClass( "ui-state-default ui-combobox-input" )
					.autocomplete({
						delay: 0,
						minLength: 0,
						source: function( request, response ) {
							var matcher = new RegExp( jQuery.ui.autocomplete.escapeRegex(request.term), "i" );
							response( select.children( "option" ).map(function() {
								var text = jQuery( this ).text();
								if ( this.value && ( !request.term || matcher.test(text) ) )
									return {
										label: text.replace(
											new RegExp(
												"(?![^&;]+;)(?!<[^<>]*)(" +
												jQuery.ui.autocomplete.escapeRegex(request.term) +
												")(?![^<>]*>)(?![^&;]+;)", "gi"
											), "<strong>$1</strong>" ),
										value: text,
										option: this
									};
							}) );
						},
						select: function( event, ui ) {
							ui.item.option.selected = true;
							self._trigger( "selected", event, {
								item: ui.item.option
							});
						},
						change: function( event, ui ) {
							if ( !ui.item ) {
								var matcher = new RegExp( "^" + jQuery.ui.autocomplete.escapeRegex( jQuery(this).val() ) + "$", "i" ),
									valid = false;
								select.children( "option" ).each(function() {
									if ( jQuery( this ).text().match( matcher ) ) {
										this.selected = valid = true;
										return false;
									}
								});
								if ( !valid ) {
									// remove invalid value, as it didn't match anything
									jQuery( this ).val( "" );
									select.val( "" );
									input.data( "autocomplete" ).term = "";
									return false;
								}
							}
						}
					})
					.addClass( "ui-widget ui-widget-content ui-corner-left" );

				input.data( "autocomplete" )._renderItem = function( ul, item ) {
					return jQuery( "<li></li>" )
						.data( "item.autocomplete", item )
						.append( "<a>" + item.label + "</a>" )
						.appendTo( ul );
				};

				jQuery( "<a>" )
					.attr( "tabIndex", -1 )
					.attr( "title", "Show All Items" )
					.appendTo( wrapper )
					.button({
						icons: {
							primary: "ui-icon-triangle-1-s"
						},
						text: false
					})
					.removeClass( "ui-corner-all" )
					.addClass( "ui-corner-right ui-combobox-toggle" )
					.click(function() {
						// close if already visible
						if ( input.autocomplete( "widget" ).is( ":visible" ) ) {
							input.autocomplete( "close" );
							return;
						}

						// work around a bug (likely same cause as #5265)
						jQuery( this ).blur();

						// pass empty string as value to search for, displaying all results
						input.autocomplete( "search", "" );
						input.focus();
					});
			},

			destroy: function() {
				this.wrapper.remove();
				this.element.show();
				jQuery.Widget.prototype.destroy.call( this );
			}
		});
	})( jQuery );

	// jQuery(function() {
	// 	jQuery( "#combobox" ).combobox();
	// 	jQuery( "#toggle" ).click(function() {
	// 		jQuery( "#combobox" ).toggle();
	// 	});
	// });

</script>


