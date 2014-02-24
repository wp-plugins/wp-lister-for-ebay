<?php
/**
 * add ebay options metaboxes to product edit page
 */

class WpLister_Product_MetaBox {

	function __construct() {

		add_action( 'add_meta_boxes', array( &$this, 'add_meta_box' ) );
		add_action( 'woocommerce_process_product_meta', array( &$this, 'save_meta_box' ), 0, 2 );

		if ( get_option( 'wplister_external_products_inventory' ) == 1 ) {
			add_action( 'woocommerce_process_product_meta_external', array( &$this, 'save_external_inventory' ) );
		}

	}

	function add_meta_box() {

		$title = __('eBay Options', 'wplister');
		add_meta_box( 'wplister-ebay-details', $title, array( &$this, 'meta_box_basic' ), 'product', 'normal', 'default');

		$title = __('Advanced eBay Options', 'wplister');
		add_meta_box( 'wplister-ebay-advanced', $title, array( &$this, 'meta_box_advanced' ), 'product', 'normal', 'default');

		$title = __('eBay Shipping Options', 'wplister');
		add_meta_box( 'wplister-ebay-shipping', $title, array( &$this, 'meta_box_shipping' ), 'product', 'normal', 'default');

		$this->enqueueFileTree();

	}

	function meta_box_basic() {
		global $woocommerce, $post;

        ?>
        <style type="text/css">
            #wplister-ebay-details label { 
            	float: left;
            	width:25%;
            	line-height: 2em;
            }
            #wplister-ebay-details input { 
            	width:74%; 
            }
            #wplister-ebay-details .description { 
            	clear: both;
            	margin-left: 25%;
            }

			.branch-3-8 div.update-nag {
				border-left: 4px solid #ffba00;
			}
        </style>
        <?php

		woocommerce_wp_text_input( array(
			'id' 			=> 'wpl_ebay_title',
			'label' 		=> __('Listing title', 'wplister'),
			'placeholder' 	=> 'Custom listing title',
			'description' 	=> __('Leave empty to generate title from product name. Maximum length: 80 characters','wplister'),
			'custom_attributes' => array( 'maxlength' => 80 ),
			'value'			=> get_post_meta( $post->ID, '_ebay_title', true )
		) );

		woocommerce_wp_text_input( array(
			'id' 			=> 'wpl_ebay_subtitle',
			'label' 		=> __('Listing subtitle', 'wplister'),
			'placeholder' 	=> 'Custom listing subtitle',
			'description' 	=> __('Leave empty to use the product excerpt. Maximum length: 55 characters','wplister'),
			'custom_attributes' => array( 'maxlength' => 55 ),
			'value'			=> get_post_meta( $post->ID, '_ebay_subtitle', true )
		) );

		woocommerce_wp_text_input( array(
			'id' 			=> 'wpl_ebay_start_price',
			'label' 		=> __('Price / Start Price', 'wplister'),
			'placeholder' 	=> 'Start Price',
			'value'			=> get_post_meta( $post->ID, '_ebay_start_price', true )
		) );

		woocommerce_wp_select( array(
			'id' 			=> 'wpl_ebay_auction_type',
			'label' 		=> __('Listing Type', 'wplister'),
			'options' 		=> array( 
					''               => __('-- use profile setting --', 'wplister'),
					'Chinese'        => __('Auction', 'wplister'),
					'FixedPriceItem' => __('Fixed Price', 'wplister')
				),
			'value'			=> get_post_meta( $post->ID, '_ebay_auction_type', true )
		) );

		woocommerce_wp_select( array(
			'id' 			=> 'wpl_ebay_listing_duration',
			'label' 		=> __('Listing Duration', 'wplister'),
			'options' 		=> array( 
					''               => __('-- use profile setting --', 'wplister'),
					'Days_1'         => '1 ' . __('Day', 'wplister'),
					'Days_3'         => '3 ' . __('Days', 'wplister'),
					'Days_5'         => '5 ' . __('Days', 'wplister'),
					'Days_7'         => '7 ' . __('Days', 'wplister'),
					'Days_10'        => '10 ' . __('Days', 'wplister'),
					'Days_30'        => '30 ' . __('Days', 'wplister'),
					'Days_60'        => '60 ' . __('Days', 'wplister'),
					'Days_90'        => '90 ' . __('Days', 'wplister'),
					'GTC'            =>  __('Good Till Canceled', 'wplister')
				),
			'value'			=> get_post_meta( $post->ID, '_ebay_listing_duration', true )
		) );

		$this->showItemConditionOptions();

	}

	function showItemConditionOptions() {
		global $woocommerce, $post;

		// default conditions - used when no primary category has been selected
		$default_conditions = array( 
			''     => __('-- use profile setting --', 'wplister'),
			'1000' => __('New', 'wplister'),
			'3000' => __('Used', 'wplister')
		);

		// do we have a primary category?
		if ( get_post_meta( $post->ID, '_ebay_category_1_id', true ) ) {
			$primary_category_id = get_post_meta( $post->ID, '_ebay_category_1_id', true );
		} else {
			// if not use default category
		    $primary_category_id = get_option('wplister_default_ebay_category_id');
		}

		// fetch updated available conditions array
		$item_conditions = $this->fetchItemConditions( $primary_category_id );

		// check if conditions are available for this category - or fall back to default
		if ( isset( $item_conditions[ $primary_category_id ] ) ) {
			$available_conditions = $item_conditions[ $primary_category_id ];
		} else {
			$available_conditions = $default_conditions;
		}

		woocommerce_wp_select( array(
			'id' 			=> 'wpl_ebay_condition_id',
			'label' 		=> __('Condition', 'wplister'),
			'options' 		=> $available_conditions,
			// 'description' 	=> __('Available conditions may vary for different categories.','wplister'),
			'value'			=> get_post_meta( $post->ID, '_ebay_condition_id', true )
		) );

		woocommerce_wp_text_input( array(
			'id' 			=> 'wpl_ebay_condition_description',
			'label' 		=> __('Condition description', 'wplister'),
			'placeholder' 	=> 'Condition description',
			'description' 	=> __('This field should only be used to further clarify the condition of used items.','wplister'),
			'value'			=> get_post_meta( $post->ID, '_ebay_condition_description', true )
		) );

	}

	public function fetchItemConditions( $ebay_category_id ) {
		global $wpdb;
		global $oWPL_WPLister;

		if ( ! $ebay_category_id ) return array();

		$transient_key = 'wplister_ebay_item_conditions_'.$ebay_category_id;

		$conditions = get_transient( $transient_key );
		if ( empty( $conditions ) ){
		   
			// fetch ebay conditions and update transient
			$oWPL_WPLister->initEC();
			$conditions = $oWPL_WPLister->EC->getCategoryConditions( $ebay_category_id );
			$oWPL_WPLister->EC->closeEbay();

			set_transient( $transient_key, $conditions, 24 * 60 * 60 );
		}

		return $conditions;
	}


	function meta_box_advanced() {
		global $woocommerce, $post;

        ?>
        <style type="text/css">
            #wplister-ebay-advanced label { 
            	float: left;
            	width:25%;
            	line-height: 2em;
            }
            #wplister-ebay-advanced input { 
            	width:74%; 
            }
            #wplister-ebay-advanced input.checkbox { 
            	width:auto; 
            }
            #wplister-ebay-advanced .description { 
            	clear: both;
            	margin-left: 25%;
            }
            #wplister-ebay-advanced .wpl_ebay_hide_from_unlisted_field .description { 
            	margin-left: 0;
            }
            #wplister-ebay-advanced h2 {
            	padding-top: 0.5em;
            	padding-bottom: 0.5em;
            	margin-top: 1em;
            	margin-bottom: 0;
            	border-top: 1px solid #555;
            	border-top: 2px dashed #ddd;
            }

        </style>
        <?php

		woocommerce_wp_text_input( array(
			'id' 			=> 'wpl_ebay_buynow_price',
			'label' 		=> __('Buy Now Price', 'wplister'),
			'placeholder' 	=> 'Buy Now Price',
			'description' 	=> __('The optional Buy Now Price is only used for auction style listings. It has no effect on fixed price listings.','wplister'),
			'value'			=> get_post_meta( $post->ID, '_ebay_buynow_price', true )
		) );

		woocommerce_wp_text_input( array(
			'id' 			=> 'wpl_ebay_reserve_price',
			'label' 		=> __('Reserve Price', 'wplister'),
			'placeholder' 	=> 'Reserve Price',
			'description' 	=> __('The lowest price at which you are willing to sell the item. Not all categories support a reserve price.','wplister'),
			'value'			=> get_post_meta( $post->ID, '_ebay_reserve_price', true )
		) );

		woocommerce_wp_text_input( array(
			'id' 			=> 'wpl_ebay_upc',
			'label' 		=> __('UPC', 'wplister'),
			'placeholder' 	=> 'Enter a Universal Product Code (UPC) to use product details from the eBay catalog.',
			'value'			=> get_post_meta( $post->ID, '_ebay_upc', true )
		) );

		woocommerce_wp_text_input( array(
			'id' 			=> 'wpl_ebay_gallery_image_url',
			'label' 		=> __('Gallery Image URL', 'wplister'),
			'placeholder' 	=> 'Enter an URL if you want to use a custom gallery image on eBay.',
			'value'			=> get_post_meta( $post->ID, '_ebay_gallery_image_url', true )
		) );

		woocommerce_wp_checkbox( array( 
			'id'    => 'wpl_ebay_global_shipping', 
			'label' => __('Global Shipping', 'wplister'),
			'value' => get_post_meta( $post->ID, '_ebay_global_shipping', true )
		) );

		woocommerce_wp_checkbox( array( 
			'id'    		=> 'wpl_ebay_hide_from_unlisted', 
			'label' 		=> __('Hide from eBay', 'wplister'),
			'description' 	=> __('Hide this product from the list of products currently not listed on eBay.','wplister'),
			'value' 		=> get_post_meta( $post->ID, '_ebay_hide_from_unlisted', true )
		) );

		woocommerce_wp_textarea_input( array( 
			'id'    => 'wpl_ebay_payment_instructions', 
			'label' => __('Payment Instructions', 'wplister'),
			'value' => get_post_meta( $post->ID, '_ebay_payment_instructions', true )
		) );

		$this->showCategoryOptions();
		$this->showItemSpecifics();
		WPL_WooFrontEndIntegration::showCompatibilityList();

		if ( get_option( 'wplister_external_products_inventory' ) == 1 ) {
			$this->enabledInventoryOnExternalProducts();
		}

		// woocommerce_wp_checkbox( array( 'id' => 'wpl_update_ebay_on_save', 'wrapper_class' => 'update_ebay', 'label' => __('Update on save?', 'wplister') ) );
	
	}

	function showCategoryOptions() {
		global $post;

		echo '<h2>'.  __('eBay categories','wplister') . '</h2>';

		// primary ebay category
		$ebay_category_1_id   = get_post_meta( $post->ID, '_ebay_category_1_id', true );
		$ebay_category_1_name = $ebay_category_1_id ? EbayCategoriesModel::getFullEbayCategoryName( $ebay_category_1_id ) : '-- default --';

		// secondary ebay category
		$ebay_category_2_id   = get_post_meta( $post->ID, '_ebay_category_2_id', true );
		$ebay_category_2_name = $ebay_category_2_id ? EbayCategoriesModel::getFullEbayCategoryName( $ebay_category_2_id ) : '-- default --';

		?>
		<div style="position:relative; margin: 0 5px;">
			<label for="wpl-text-ebay_category_1_name" class="text_label"><?php echo __('Primary eBay category','wplister'); ?></label>
			<input type="hidden" name="wpl_ebay_category_1_id" id="ebay_category_id_1" value="<?php echo $ebay_category_1_id ?>" class="" />
			<span  id="ebay_category_name_1" class="text_input" style="width:45%;float:left;line-height:2em;"><?php echo $ebay_category_1_name ?></span>
			<div class="category_row_actions">
				<input type="button" value="<?php echo __('select','wplister'); ?>" class="button btn_select_ebay_category" onclick="">
				<input type="button" value="<?php echo __('remove','wplister'); ?>" class="button btn_remove_ebay_category" onclick="">
			</div>
		</div>
		<br style="clear:both" />
		<div style="position:relative; margin: 0 5px;">
			<label for="wpl-text-ebay_category_2_name" class="text_label"><?php echo __('Secondary eBay category','wplister'); ?></label>
			<input type="hidden" name="wpl_ebay_category_2_id" id="ebay_category_id_2" value="<?php echo $ebay_category_2_id ?>" class="" />
			<span  id="ebay_category_name_2" class="text_input" style="width:45%;float:left;line-height:2em;"><?php echo $ebay_category_2_name ?></span>
			<div class="category_row_actions">
				<input type="button" value="<?php echo __('select','wplister'); ?>" class="button btn_select_ebay_category" onclick="">
				<input type="button" value="<?php echo __('remove','wplister'); ?>" class="button btn_remove_ebay_category" onclick="">
			</div>
		</div>
		<br style="clear:both" />

		<!-- hidden ajax categories tree -->
		<div id="ebay_categories_tree_wrapper">
			<div id="ebay_categories_tree_container"></div>
		</div>

		<style type="text/css">

			#ebay_categories_tree_wrapper,
			#store_categories_tree_wrapper {
				/*max-height: 320px;*/
				/*margin-left: 35%;*/
				overflow: auto;
				width: 65%;
				display: none;
			}

			#wplister-ebay-advanced .category_row_actions {
				position: absolute;
				top: 0;
				right: 0;
			}
            #wplister-ebay-advanced .category_row_actions input { 
            	width: auto; 
            }


			a.link_select_category {
				float: right;
				padding-top: 3px;
				text-decoration: none;
			}
			a.link_remove_category {
				padding-left: 3px;
				text-decoration: none;
			}
			
		</style>

		<script type="text/javascript">

			/* recusive function to gather the full category path names */
	        function wpl_getCategoryPathName( pathArray, depth ) {
				var pathname = '';
				if (typeof depth == 'undefined' ) depth = 0;

	        	// get name
		        if ( depth == 0 ) {
		        	var cat_name = jQuery('[rel=' + pathArray.join('\\\/') + ']').html();
		        } else {
			        var cat_name = jQuery('[rel=' + pathArray.join('\\\/') +'\\\/'+ ']').html();
		        }

		        // console.log('path...: ', pathArray.join('\\\/') );
		        // console.log('catname: ', cat_name);
		        // console.log('pathArray: ', pathArray);

		        // strip last (current) item
		        popped = pathArray.pop();
		        // console.log('popped: ',popped);

		        // call self with parent path
		        if ( pathArray.length > 2 ) {
			        pathname = wpl_getCategoryPathName( pathArray, depth + 1 ) + ' &raquo; ' + cat_name;
		        } else if ( pathArray.length > 1 ) {
			        pathname = cat_name;
		        }

		        return pathname;

	        }

			jQuery( document ).ready(
				function () {


					// select ebay category button
					jQuery('input.btn_select_ebay_category').click( function(event) {
						// var cat_id = jQuery(this).parent()[0].id.split('sel_ebay_cat_id_')[1];
						e2e_selecting_cat = ('ebay_category_name_1' == jQuery(this).parent().parent().first().find('.text_input')[0].id) ? 1 : 2;

						var tbHeight = tb_getPageSize()[1] - 120;
						var tbURL = "#TB_inline?height="+tbHeight+"&width=640&inlineId=ebay_categories_tree_wrapper"; 
	        			tb_show("Select a category", tbURL);  
						
					});
					// remove ebay category button
					jQuery('input.btn_remove_ebay_category').click( function(event) {
						var cat_id = ('ebay_category_name_1' == jQuery(this).parent().parent().first().find('.text_input')[0].id) ? 1 : 2;
						
						jQuery('#ebay_category_id_'+cat_id).attr('value','');
						jQuery('#ebay_category_name_'+cat_id).html('');
					});
			
					// select store category button
					jQuery('input.btn_select_store_category').click( function(event) {
						// var cat_id = jQuery(this).parent()[0].id.split('sel_store_cat_id_')[1];
						e2e_selecting_cat = ('store_category_name_1' == jQuery(this).parent().parent().first().find('.text_input')[0].id) ? 1 : 2;

						var tbHeight = tb_getPageSize()[1] - 120;
						var tbURL = "#TB_inline?height="+tbHeight+"&width=640&inlineId=store_categories_tree_wrapper"; 
	        			tb_show("Select a category", tbURL);  
						
					});
					// remove store category button
					jQuery('input.btn_remove_store_category').click( function(event) {
						var cat_id = ('store_category_name_1' == jQuery(this).parent().parent().first().find('.text_input')[0].id) ? 1 : 2;
						
						jQuery('#store_category_id_'+cat_id).attr('value','');
						jQuery('#store_category_name_'+cat_id).html('');
					});
			
			
					// jqueryFileTree 1 - ebay categories
				    jQuery('#ebay_categories_tree_container').fileTree({
				        root: '/0/',
				        script: ajaxurl+'?action=e2e_get_ebay_categories_tree',
				        expandSpeed: 400,
				        collapseSpeed: 400,
				        loadMessage: 'loading eBay categories...',
				        multiFolder: false
				    }, function(catpath) {

						// get cat id from full path
				        var cat_id = catpath.split('/').pop(); // get last item - like php basename()

				        // get name of selected category
				        var cat_name = '';

				        var pathname = wpl_getCategoryPathName( catpath.split('/') );
						// console.log('pathname: ',pathname);
				        
				        // update fields
				        jQuery('#ebay_category_id_'+e2e_selecting_cat).attr( 'value', cat_id );
				        jQuery('#ebay_category_name_'+e2e_selecting_cat).html( pathname );
				        
				        // close thickbox
				        tb_remove();

				        if ( e2e_selecting_cat == 1 ) {
				        	updateItemSpecifics();
				        // 	updateItemConditions();
				        }

				    });
		
					// jqueryFileTree 2 - store categories
				    jQuery('#store_categories_tree_container').fileTree({
				        root: '/0/',
				        script: ajaxurl+'?action=e2e_get_store_categories_tree',
				        expandSpeed: 400,
				        collapseSpeed: 400,
				        loadMessage: 'loading store categories...',
				        multiFolder: false
				    }, function(catpath) {

						// get cat id from full path
				        var cat_id = catpath.split('/').pop(); // get last item - like php basename()

				        // get name of selected category
				        var cat_name = '';

				        var pathname = wpl_getCategoryPathName( catpath.split('/') );
						// console.log('pathname: ',pathname);
				        
				        // update fields
				        jQuery('#store_category_id_'+e2e_selecting_cat).attr( 'value', cat_id );
				        jQuery('#store_category_name_'+e2e_selecting_cat).html( pathname );
				        
				        // close thickbox
				        tb_remove();

				    });
		


				}
			);
		
		
		</script>

		<?php

	}

	function showItemSpecifics() {
	}

	function enabledInventoryOnExternalProducts() {
		global $post;

		if ( ! function_exists('get_product') ) return;
		$product = get_product( $post->ID );

        ?>
		<script type="text/javascript">

			jQuery( document ).ready( function () {

				// add show_id_external class to inventory tab and fields
				jQuery('.product_data_tabs .inventory_tab').addClass('show_if_external');
				jQuery('#inventory_product_data .show_if_simple').addClass('show_if_external');				

				<?php if ( $product->is_type( 'external' ) ) : ?>

				// show inventory tab if this is an external product
				jQuery('.product_data_tabs .inventory_tab').show();
				jQuery('#inventory_product_data .show_if_simple').show();				

				<?php endif; ?>

			});	
		
		</script>
		<?php

	} // enabledInventoryOnExternalProducts()

	function meta_box_shipping() {
		global $woocommerce, $post;

        ?>
        <style type="text/css">
            #wplister-ebay-shipping label { 
            	float: left;
            	width:25%;
            	line-height: 2em;
            }
            #wplister-ebay-shipping input { 
            	/*width:74%; */
            }
            #wplister-ebay-shipping .description { 
            	/*clear: both;*/
            	/*margin-left: 25%;*/
            }
            #wplister-ebay-shipping .ebay_shipping_options_wrapper h2 {
            	padding-top: 0.5em;
            	padding-bottom: 0.5em;
            	margin-top: 1em;
            	margin-bottom: 0;
            	border-top: 1px solid #555;
            	border-top: 2px dashed #ddd;
            }

        </style>
        <?php

		$this->showShippingOptions();

	} // showCategoryOptions()

	function showShippingOptions() {
		global $woocommerce, $post;

		$wpl_loc_flat_shipping_options = EbayShippingModel::getAllLocal('flat');
		$wpl_int_flat_shipping_options = EbayShippingModel::getAllInternational('flat');
		$wpl_shipping_locations        = EbayShippingModel::getShippingLocations();
		$wpl_exclude_locations         = EbayShippingModel::getExcludeShippingLocations();
		$wpl_countries                 = EbayShippingModel::getEbayCountries();

		$wpl_loc_calc_shipping_options   = EbayShippingModel::getAllLocal('calculated');
		$wpl_int_calc_shipping_options   = EbayShippingModel::getAllInternational('calculated');
		$wpl_calc_shipping_enabled       = in_array( get_option('wplister_ebay_site_id'), array(0,2,15,100) );
		$wpl_available_shipping_packages = get_option('wplister_ShippingPackageDetails');

		$wpl_seller_profiles_enabled	= get_option('wplister_ebay_seller_profiles_enabled');
		$wpl_seller_shipping_profiles	= get_option('wplister_ebay_seller_shipping_profiles');
		$wpl_seller_payment_profiles	= get_option('wplister_ebay_seller_payment_profiles');
		$wpl_seller_return_profiles		= get_option('wplister_ebay_seller_return_profiles');

		// fetch available shipping discount profiles
		$wpl_shipping_flat_profiles = array();
		$wpl_shipping_calc_profiles = array();
	    $ShippingDiscountProfiles = get_option('wplister_ShippingDiscountProfiles', array() );
		if ( isset( $ShippingDiscountProfiles['FlatShippingDiscount'] ) ) {
			$wpl_shipping_flat_profiles = $ShippingDiscountProfiles['FlatShippingDiscount'];
		}
		if ( isset( $ShippingDiscountProfiles['CalculatedShippingDiscount'] ) ) {
			$wpl_shipping_calc_profiles = $ShippingDiscountProfiles['CalculatedShippingDiscount'];
		}

		// make sure that at least one payment and shipping option exist
		$item_details['loc_shipping_options'] = ProfilesModel::fixShippingArray( get_post_meta( $post->ID, '_ebay_loc_shipping_options', true ) );
		$item_details['int_shipping_options'] = ProfilesModel::fixShippingArray( get_post_meta( $post->ID, '_ebay_int_shipping_options', true ) );
		
		$item_details['shipping_loc_calc_profile']           = get_post_meta( $post->ID, '_ebay_shipping_loc_calc_profile', true );
		$item_details['shipping_loc_flat_profile']           = get_post_meta( $post->ID, '_ebay_shipping_loc_flat_profile', true );
		$item_details['shipping_int_calc_profile']           = get_post_meta( $post->ID, '_ebay_shipping_int_calc_profile', true );
		$item_details['shipping_int_flat_profile']           = get_post_meta( $post->ID, '_ebay_shipping_int_flat_profile', true );
		$item_details['seller_shipping_profile_id']          = get_post_meta( $post->ID, '_ebay_seller_shipping_profile_id', true );
		$item_details['PackagingHandlingCosts']              = get_post_meta( $post->ID, '_ebay_PackagingHandlingCosts', true );
		$item_details['InternationalPackagingHandlingCosts'] = get_post_meta( $post->ID, '_ebay_InternationalPackagingHandlingCosts', true );
		$item_details['shipping_service_type']               = get_post_meta( $post->ID, '_ebay_shipping_service_type', true );
		$item_details['shipping_package']   				 = get_post_meta( $post->ID, '_ebay_shipping_package', true );
		$item_details['shipping_loc_enable_free_shipping']   = get_post_meta( $post->ID, '_ebay_shipping_loc_enable_free_shipping', true );
		$item_details['ShipToLocations']   					 = get_post_meta( $post->ID, '_ebay_shipping_ShipToLocations', true );
		$item_details['ExcludeShipToLocations']   			 = get_post_meta( $post->ID, '_ebay_shipping_ExcludeShipToLocations', true );
		if ( ! $item_details['shipping_service_type'] ) $item_details['shipping_service_type'] = 'disabled';

		// echo '<h2>'.  __('Shipping Options','wplister') . '</h2>';
		?>
			<!-- service type selector -->
			<label for="wpl-text-loc_shipping_service_type" class="text_label"><?php echo __('Custom shipping options','wplister'); ?></label>
			<select name="wpl_e2e_shipping_service_type" id="wpl-text-loc_shipping_service_type" 
					class="required-entry select select_shipping_type" style="width:auto;"
					onchange="handleShippingTypeSelectionChange(this)">
				<option value="disabled" <?php if ( @$item_details['shipping_service_type'] == 'disabled' ): ?>selected="selected"<?php endif; ?>><?php echo __('-- use profile setting --','wplister'); ?></option>
				<option value="flat"     <?php if ( @$item_details['shipping_service_type'] == 'flat' ): ?>selected="selected"<?php endif; ?>><?php echo __('Use Flat Shipping','wplister'); ?></option>
				<option value="calc"     <?php if ( @$item_details['shipping_service_type'] == 'calc' ): ?>selected="selected"<?php endif; ?>><?php echo __('Use Calculated Shipping','wplister'); ?></option>
				<option value="FlatDomesticCalculatedInternational" <?php if ( @$item_details['shipping_service_type'] == 'FlatDomesticCalculatedInternational' ): ?>selected="selected"<?php endif; ?>><?php echo __('Use Flat Domestic and Calculated International Shipping','wplister'); ?></option>
				<option value="CalculatedDomesticFlatInternational" <?php if ( @$item_details['shipping_service_type'] == 'CalculatedDomesticFlatInternational' ): ?>selected="selected"<?php endif; ?>><?php echo __('Use Calculated Domestic and Flat International Shipping','wplister'); ?></option>
				<option value="FreightFlat" <?php if ( @$item_details['shipping_service_type'] == 'FreightFlat' ): ?>selected="selected"<?php endif; ?>><?php echo __('Use Freight Shipping','wplister'); ?></option>
			</select>
		<?php
		
		echo '<div class="ebay_shipping_options_wrapper">';
		echo '<h2>'.  __('Domestic shipping','wplister') . '</h2>';
		include( WPLISTER_PATH . '/views/profile/edit_shipping_loc.php' );

		echo '<h2>'.  __('International shipping','wplister') . '</h2>';
		include( WPLISTER_PATH . '/views/profile/edit_shipping_int.php' );
		echo '</div>';

		echo '<script>';
		include( WPLISTER_PATH . '/views/profile/edit_shipping.js' );		
		echo '</script>';
		
	} // showShippingOptions()

	function enqueueFileTree() {

		// jqueryFileTree
		wp_register_style('jqueryFileTree_style', WPLISTER_URL.'/js/jqueryFileTree/jqueryFileTree.css' );
		wp_enqueue_style('jqueryFileTree_style'); 

		// jqueryFileTree
		wp_register_script( 'jqueryFileTree', WPLISTER_URL.'/js/jqueryFileTree/jqueryFileTree.js', array( 'jquery' ) );
		wp_enqueue_script( 'jqueryFileTree' );

		// mustache template engine
		wp_register_script( 'mustache', WPLISTER_URL.'/js/template/mustache.js', array( 'jquery' ) );
		wp_enqueue_script( 'mustache' );

		// jQuery UI Autocomplete
		wp_enqueue_script( 'jquery-ui-button' );
		wp_enqueue_script( 'jquery-ui-autocomplete' );

	}

	function save_meta_box( $post_id, $post ) {

		if ( isset( $_POST['wpl_ebay_title'] ) ) {

			// get field values
			$wpl_ebay_title                 = esc_attr( @$_POST['wpl_ebay_title'] );
			$wpl_ebay_subtitle              = esc_attr( @$_POST['wpl_ebay_subtitle'] );
			$wpl_ebay_global_shipping       = esc_attr( @$_POST['wpl_ebay_global_shipping'] );
			$wpl_ebay_payment_instructions  = esc_attr( @$_POST['wpl_ebay_payment_instructions'] );
			$wpl_ebay_condition_description = esc_attr( @$_POST['wpl_ebay_condition_description'] );
			$wpl_ebay_condition_id 			= esc_attr( @$_POST['wpl_ebay_condition_id'] );
			$wpl_ebay_auction_type          = esc_attr( @$_POST['wpl_ebay_auction_type'] );
			$wpl_ebay_listing_duration      = esc_attr( @$_POST['wpl_ebay_listing_duration'] );
			$wpl_ebay_start_price           = esc_attr( @$_POST['wpl_ebay_start_price'] );
			$wpl_ebay_reserve_price         = esc_attr( @$_POST['wpl_ebay_reserve_price'] );
			$wpl_ebay_buynow_price          = esc_attr( @$_POST['wpl_ebay_buynow_price'] );
			$wpl_ebay_upc          			= esc_attr( @$_POST['wpl_ebay_upc'] );
			$wpl_ebay_hide_from_unlisted  	= esc_attr( @$_POST['wpl_ebay_hide_from_unlisted'] );
			$wpl_ebay_category_1_id      	= esc_attr( @$_POST['wpl_ebay_category_1_id'] );
			$wpl_ebay_category_2_id      	= esc_attr( @$_POST['wpl_ebay_category_2_id'] );
			$wpl_ebay_gallery_image_url   	= esc_attr( @$_POST['wpl_ebay_gallery_image_url'] );

			// Update order data
			update_post_meta( $post_id, '_ebay_title', $wpl_ebay_title );
			update_post_meta( $post_id, '_ebay_subtitle', $wpl_ebay_subtitle );
			update_post_meta( $post_id, '_ebay_global_shipping', $wpl_ebay_global_shipping );
			update_post_meta( $post_id, '_ebay_payment_instructions', $wpl_ebay_payment_instructions );
			update_post_meta( $post_id, '_ebay_condition_id', $wpl_ebay_condition_id );
			update_post_meta( $post_id, '_ebay_condition_description', $wpl_ebay_condition_description );
			update_post_meta( $post_id, '_ebay_listing_duration', $wpl_ebay_listing_duration );
			update_post_meta( $post_id, '_ebay_auction_type', $wpl_ebay_auction_type );
			update_post_meta( $post_id, '_ebay_start_price', $wpl_ebay_start_price );
			update_post_meta( $post_id, '_ebay_reserve_price', $wpl_ebay_reserve_price );
			update_post_meta( $post_id, '_ebay_buynow_price', $wpl_ebay_buynow_price );
			update_post_meta( $post_id, '_ebay_upc', $wpl_ebay_upc );
			update_post_meta( $post_id, '_ebay_hide_from_unlisted', $wpl_ebay_hide_from_unlisted );
			update_post_meta( $post_id, '_ebay_category_1_id', $wpl_ebay_category_1_id );
			update_post_meta( $post_id, '_ebay_category_2_id', $wpl_ebay_category_2_id );
			update_post_meta( $post_id, '_ebay_gallery_image_url', $wpl_ebay_gallery_image_url );

			// shipping options
			$ebay_shipping_service_type = esc_attr( @$_POST['wpl_e2e_shipping_service_type'] );

			if ( $ebay_shipping_service_type != 'disabled' ) {
	
				update_post_meta( $post_id, '_ebay_shipping_service_type', $ebay_shipping_service_type );

				$details = ProfilesPage::getPreprocessedPostDetails();
				update_post_meta( $post_id, '_ebay_loc_shipping_options', $details['loc_shipping_options'] );
				update_post_meta( $post_id, '_ebay_int_shipping_options', $details['int_shipping_options'] );

				update_post_meta( $post_id, '_ebay_shipping_package', esc_attr( @$_POST['wpl_e2e_shipping_package'] ) );
				update_post_meta( $post_id, '_ebay_PackagingHandlingCosts', esc_attr( @$_POST['wpl_e2e_PackagingHandlingCosts'] ) );
				update_post_meta( $post_id, '_ebay_InternationalPackagingHandlingCosts', esc_attr( @$_POST['wpl_e2e_InternationalPackagingHandlingCosts'] ) );

				update_post_meta( $post_id, '_ebay_shipping_loc_flat_profile', esc_attr( @$_POST['wpl_e2e_shipping_loc_flat_profile'] ) );
				update_post_meta( $post_id, '_ebay_shipping_int_flat_profile', esc_attr( @$_POST['wpl_e2e_shipping_int_flat_profile'] ) );
				update_post_meta( $post_id, '_ebay_shipping_loc_calc_profile', esc_attr( @$_POST['wpl_e2e_shipping_loc_calc_profile'] ) );
				update_post_meta( $post_id, '_ebay_shipping_int_calc_profile', esc_attr( @$_POST['wpl_e2e_shipping_int_calc_profile'] ) );
				update_post_meta( $post_id, '_ebay_seller_shipping_profile_id', esc_attr( @$_POST['wpl_e2e_seller_shipping_profile_id'] ) );
				
				$loc_free_shipping = strstr( 'calc', strtolower($ebay_shipping_service_type) ) ? @$_POST['wpl_e2e_shipping_loc_calc_free_shipping'] : @$_POST['wpl_e2e_shipping_loc_flat_free_shipping'];
				update_post_meta( $post_id, '_ebay_shipping_loc_enable_free_shipping', $loc_free_shipping );

				update_post_meta( $post_id, '_ebay_shipping_ShipToLocations', @$_POST['wpl_e2e_ShipToLocations'] );
				update_post_meta( $post_id, '_ebay_shipping_ExcludeShipToLocations', @$_POST['wpl_e2e_ExcludeShipToLocations'] );

			} else {

				delete_post_meta( $post_id, '_ebay_shipping_service_type' );
				delete_post_meta( $post_id, '_ebay_loc_shipping_options' );
				delete_post_meta( $post_id, '_ebay_int_shipping_options' );
				delete_post_meta( $post_id, '_ebay_shipping_package' );
				delete_post_meta( $post_id, '_ebay_PackagingHandlingCosts' );
				delete_post_meta( $post_id, '_ebay_InternationalPackagingHandlingCosts' );
				delete_post_meta( $post_id, '_ebay_shipping_loc_flat_profile' );
				delete_post_meta( $post_id, '_ebay_shipping_int_flat_profile' );
				delete_post_meta( $post_id, '_ebay_shipping_loc_calc_profile' );
				delete_post_meta( $post_id, '_ebay_shipping_int_calc_profile' );

			}


		}

	} // save_meta_box()

	function save_external_inventory( $post_id ) {

		if ( ! isset( $_POST['_stock'] ) ) return;

		// Update order data
		// see woocommerce/admin/post-types/writepanels/writepanel-product_data.php
        update_post_meta( $post_id, '_stock', (int) $_POST['_stock'] );
        update_post_meta( $post_id, '_stock_status', stripslashes( $_POST['_stock_status'] ) );
        update_post_meta( $post_id, '_backorders', stripslashes( $_POST['_backorders'] ) );
        update_post_meta( $post_id, '_manage_stock', 'yes' );

        // a quantity of zero means out of stock
        if ( (int) $_POST['_stock'] == 0 ) {
	        update_post_meta( $post_id, '_stock_status', 'outofstock' );
        } 

	}

} // class WpLister_Product_MetaBox
$WpLister_Product_MetaBox = new WpLister_Product_MetaBox();
