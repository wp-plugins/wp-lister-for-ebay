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

		// remove ebay specific meta data from duplicated products
		add_action( 'woocommerce_duplicate_product', array( &$this, 'woocommerce_duplicate_product' ), 0, 2 );
	}

	function add_meta_box() {

		$title = __('eBay Options', 'wplister');
		add_meta_box( 'wplister-ebay-details', $title, array( &$this, 'meta_box_basic' ), 'product', 'normal', 'default');

		$title = __('Advanced eBay Options', 'wplister');
		add_meta_box( 'wplister-ebay-advanced', $title, array( &$this, 'meta_box_advanced' ), 'product', 'normal', 'default');

		$title = __('eBay Part Compatibility', 'wplister');
		add_meta_box( 'wplister-ebay-compat', $title, array( &$this, 'meta_box_compat' ), 'product', 'normal', 'default');

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
            	width: 33%;
            	line-height: 2em;
            }
            #wplister-ebay-details input { 
            	width: 62%; 
            }
            #wplister-ebay-details .description { 
            	clear: both;
            	margin-left: 33%;
            #wplister-ebay-details .de.input_specs,
            #wplister-ebay-details .de.select_specs { 
            	clear:100%h;
            	margin-left: 33%;
            }

			.branch-3-8 div.update-nag {
				border-left: 4px solid #ffba00;
			}
        </style>
        <?php

		woocommerce_wp_text_input( array(
			'id' 			=> 'wpl_ebay_title',
			'label' 		=> __('Listing title', 'wplister'),
			'placeholder' 	=> __('Custom listing title', 'wplister'),
			'description' 	=> __('Leave empty to generate title from product name. Maximum length: 80 characters','wplister'),
			'custom_attributes' => array( 'maxlength' => 80 ),
			'value'			=> get_post_meta( $post->ID, '_ebay_title', true )
		) );

		woocommerce_wp_text_input( array(
			'id' 			=> 'wpl_ebay_subtitle',
			'label' 		=> __('Listing subtitle', 'wplister'),
			'placeholder' 	=> __('Custom listing subtitle', 'wplister'),
			'description' 	=> __('Leave empty to use the product excerpt. Maximum length: 55 characters','wplister'),
			'custom_attributes' => array( 'maxlength' => 55 ),
			'value'			=> get_post_meta( $post->ID, '_ebay_subtitle', true )
		) );

		woocommerce_wp_text_input( array(
			'id' 			=> 'wpl_ebay_start_price',
			'label' 		=> __('Price / Start Price', 'wplister'),
			'placeholder' 	=> __('Start Price', 'wplister'),
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
		if ( isset( $item_conditions[ $primary_category_id ] ) && is_array( $item_conditions[ $primary_category_id ] ) ) {
			// get available conditions and add default value "use profile setting" to the beginning
		    $available_conditions = array('' => __('-- use profile setting --','wplister')) + $item_conditions[ $primary_category_id ]; 
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
			'placeholder' 	=> __('Condition description', 'wplister'),
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
            	width: 33%;
            	line-height: 2em;
            }
            #wplister-ebay-advanced input, 
            #wplister-ebay-advanced select.select { 
            	width: 62%; 
            }
            #wplister-ebay-advanced input.checkbox { 
            	width:auto; 
            }
            #wplister-ebay-advanced #ItemSpecifics_container input,
            #wplister-ebay-advanced input.input_specs,
            #wplister-ebay-advanced input.select_specs { 
            	width:100%; 
            }
            #wplister-ebay-advanced #ItemSpecifics_container th { 
            	text-align: center;
            }
            #wplister-ebay-advanced #EbayItemSpecificsBox .inside { 
            	margin:0;
            	padding:0;
            }
            #wplister-ebay-advanced .description { 
            	clear: both;
            	margin-left: 33%;
            }
            #wplister-ebay-advanced .wpl_ebay_hide_from_unlisted_field .description,
            #wplister-ebay-advanced .wpl_ebay_bestoffer_enabled_field .description { 
            	margin-left: 0.3em;
				height: 1.4em;
				display: inline-block;
            	vertical-align: bottom;
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
			'placeholder' 	=> __('Buy Now Price', 'wplister'),
			'description' 	=> __('The optional Buy Now Price is only used for auction style listings. It has no effect on fixed price listings.','wplister'),
			'desc_tip'		=>  true,
			'value'			=> get_post_meta( $post->ID, '_ebay_buynow_price', true )
		) );

		woocommerce_wp_text_input( array(
			'id' 			=> 'wpl_ebay_reserve_price',
			'label' 		=> __('Reserve Price', 'wplister'),
			'placeholder' 	=> __('Reserve Price', 'wplister'),
			'description' 	=> __('The lowest price at which you are willing to sell the item. Not all categories support a reserve price.','wplister'),
			'desc_tip'		=>  true,
			'value'			=> get_post_meta( $post->ID, '_ebay_reserve_price', true )
		) );

		woocommerce_wp_text_input( array(
			'id' 			=> 'wpl_ebay_upc',
			'label' 		=> __('UPC', 'wplister'),
			'placeholder' 	=> __('Enter a Universal Product Code (UPC) to use product details from the eBay catalog.', 'wplister'),
			'value'			=> get_post_meta( $post->ID, '_ebay_upc', true )
		) );

		woocommerce_wp_text_input( array(
			'id' 			=> 'wpl_ebay_gallery_image_url',
			'label' 		=> __('Gallery Image URL', 'wplister'),
			'placeholder' 	=> __('Enter an URL if you want to use a custom gallery image on eBay.', 'wplister'),
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

		woocommerce_wp_checkbox( array( 
			'id'    		=> 'wpl_ebay_bestoffer_enabled', 
			'label' 		=> __('Best Offer', 'wplister'),
			'description' 	=> __('Enable Best Offer to allow a buyer to make a lower-priced binding offer.','wplister'),
			'value' 		=> get_post_meta( $post->ID, '_ebay_bestoffer_enabled', true )
		) );

		woocommerce_wp_text_input( array(
			'id' 			=> 'wpl_ebay_bo_autoaccept_price',
			'label' 		=> __('Auto accept price', 'wplister'),
			'placeholder' 	=> __('The price at which Best Offers are automatically accepted.', 'wplister'),
			'value'			=> get_post_meta( $post->ID, '_ebay_bo_autoaccept_price', true )
		) );

		woocommerce_wp_text_input( array(
			'id' 			=> 'wpl_ebay_bo_minimum_price',
			'label' 		=> __('Minimum price', 'wplister'),
			'placeholder' 	=> __('Specifies the minimum acceptable Best Offer price.', 'wplister'),
			'value'			=> get_post_meta( $post->ID, '_ebay_bo_minimum_price', true )
		) );




		$wpl_seller_profiles_enabled	= get_option('wplister_ebay_seller_profiles_enabled');
		if ( $wpl_seller_profiles_enabled ) {

			// $wpl_seller_shipping_profiles	= get_option('wplister_ebay_seller_shipping_profiles');
			$wpl_seller_payment_profiles	= get_option('wplister_ebay_seller_payment_profiles');
			$wpl_seller_return_profiles		= get_option('wplister_ebay_seller_return_profiles');
			// echo "<pre>";print_r($wpl_seller_payment_profiles);echo"</pre>";#die();

			if ( is_array( $wpl_seller_payment_profiles ) ) {

				$seller_payment_profiles = array( '' => __('-- use profile setting --','wplister') );
				foreach ( $wpl_seller_payment_profiles as $seller_profile ) {
					$seller_payment_profiles[ $seller_profile->ProfileID ] = $seller_profile->ProfileName . ' - ' . $seller_profile->ShortSummary;
				}

				woocommerce_wp_select( array(
					'id' 			=> 'wpl_ebay_seller_payment_profile_id',
					'label' 		=> __('Payment profile', 'wplister'),
					'options' 		=> $seller_payment_profiles,
					// 'description' 	=> __('Available conditions may vary for different categories.','wplister'),
					'value'			=> get_post_meta( $post->ID, '_ebay_seller_payment_profile_id', true )
				) );

			}

			if ( is_array( $wpl_seller_return_profiles ) ) {

				$seller_return_profiles = array( '' => __('-- use profile setting --','wplister') );
				foreach ( $wpl_seller_return_profiles as $seller_profile ) {
					$seller_return_profiles[ $seller_profile->ProfileID ] = $seller_profile->ProfileName . ' - ' . $seller_profile->ShortSummary;
				}

				woocommerce_wp_select( array(
					'id' 			=> 'wpl_ebay_seller_return_profile_id',
					'label' 		=> __('Return profile', 'wplister'),
					'options' 		=> $seller_return_profiles,
					// 'description' 	=> __('Available conditions may vary for different categories.','wplister'),
					'value'			=> get_post_meta( $post->ID, '_ebay_seller_return_profile_id', true )
				) );

			}

		}


		woocommerce_wp_textarea_input( array( 
			'id'    => 'wpl_ebay_payment_instructions', 
			'label' => __('Payment Instructions', 'wplister'),
			'value' => get_post_meta( $post->ID, '_ebay_payment_instructions', true )
		) );

		$this->showCategoryOptions();
		$this->showItemSpecifics();
		// $this->showCompatibilityTable();
		// WPL_WooFrontEndIntegration::showCompatibilityList();

		if ( get_option( 'wplister_external_products_inventory' ) == 1 ) {
			$this->enabledInventoryOnExternalProducts();
		}

		// woocommerce_wp_checkbox( array( 'id' => 'wpl_update_ebay_on_save', 'wrapper_class' => 'update_ebay', 'label' => __('Update on save?', 'wplister') ) );
	
	}

	function meta_box_compat() {
		$this->showCompatibilityTable();
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

	} // showCategoryOptions()

	// show editable parts compatibility table
	function showCompatibilityTable() {
		global $post;
		$has_compat_table = true;

		// get compatibility list and names
		$compatibility_list   = get_post_meta( $post->ID, '_ebay_item_compatibility_list', true );
		$compatibility_names  = get_post_meta( $post->ID, '_ebay_item_compatibility_names', true );
		// echo "<pre>cols: ";print_r($compatibility_names);echo"</pre>";#die();
		// echo "<pre>rows: ";print_r($compatibility_list);echo"</pre>";#die();

		// return if there is no compatibility list
		// if ( ( ! is_array($compatibility_list) ) || ( sizeof($compatibility_list) == 0 ) ) return;

		// empty default table
		if ( ( ! is_array($compatibility_list) ) || ( sizeof($compatibility_list) == 0 ) ) {
			// if ( ! get_option( 'wplister_enable_compatibility_table' ) ) return;

			// $compatibility_names = array('Make','Model','Year');
			// $compatibility_list  = array();
			$has_compat_table = false;
		}

		// echo '<h2>'.  __('Item Compatibility List','wplister') . '</h2>';

		?>
			<div class="ebay_item_compatibility_table_wrapper" style="<?php echo $has_compat_table ? '' : 'display:none' ?>">

				<?php if ( $has_compat_table ) : ?>
				<table class="ebay_item_compatibility_table">

					<tr>
						<?php foreach ($compatibility_names as $name) : ?>
							<th><?php echo $name ?></th>
						<?php endforeach; ?>
						<th><?php echo 'Notes' ?></th>
					</tr>

					<?php foreach ($compatibility_list as $comp) : ?>

						<tr>
							<?php foreach ($compatibility_names as $name) : ?>

								<td><?php echo $comp->applications[ $name ]->value ?></td>

							<?php endforeach; ?>

							<td><?php echo $comp->notes ?></td>

						</tr>
						
					<?php endforeach; ?>
				</table>
				<?php endif; ?>

				<div style="float:right; margin-top:1em;">
					<a href="#" id="wpl_btn_remove_compatibility_table" class="button"><?php echo __('Clear all','wplister') ?></a>
					<a href="#" id="wpl_btn_add_compatibility_row" class="button"><?php echo __('Add row','wplister') ?></a>
				</div>
				<p>
					<?php echo __('To remove a row empty the first column and update.','wplister') ?>
				</p>

			</div>

			<a href="#" id="wpl_btn_add_compatibility_table" class="button" style="<?php echo $has_compat_table ? 'display:none' : '' ?>">
				<?php echo __('Add compatibility table','wplister') ?>
			</a>

			<input type="hidden" name="wpl_e2e_compatibility_list"   id="wpl_e2e_compatibility_list"   value='<?php #echo json_encode($compatibility_list)  ?>' />
			<input type="hidden" name="wpl_e2e_compatibility_names"  id="wpl_e2e_compatibility_names"  value='<?php #echo json_encode($compatibility_names) ?>' />
			<input type="hidden" name="wpl_e2e_compatibility_remove" id="wpl_e2e_compatibility_remove" value='' />

			<style type="text/css">

				.ebay_item_compatibility_table {
					width: 100%;
				}
				.ebay_item_compatibility_table tr th {
					text-align: left;
					border-bottom: 3px double #bbb;
				}
				.ebay_item_compatibility_table tr td {
					border-bottom: 1px solid #ccc;
				}
				#wpl_btn_add_compatibility_row {
					/*float: right;*/
				}
				
			</style>

			<script type="text/javascript">

				jQuery( document ).ready( function () {

					// make table editable
					wpl_initCompatTable();

					// handle add row button
					jQuery('#wpl_btn_add_compatibility_row').on('click', function(evt) {

						// clone the last row and append to table
						jQuery('table.ebay_item_compatibility_table tr:last').last().clone().insertAfter('table.ebay_item_compatibility_table tr:last');

						// update listener
						jQuery('table.ebay_item_compatibility_table td').on('change', function(evt, newValue) {
							wpl_updateTableData();
						});

						return false; // reject change
					});

					// handle remove table button
					jQuery('#wpl_btn_remove_compatibility_table').on('click', function(evt) {
						var confirmed = confirm("<?php echo __('Are you sure you want to remove the entire table?','wplister') ?>");
						if ( confirmed ) {

							// remove table
							jQuery('table.ebay_item_compatibility_table').remove();

							// hide table wrapper
							jQuery('.ebay_item_compatibility_table_wrapper').slideUp();

							// show add table button
							jQuery('#wpl_btn_add_compatibility_table').show();

							// clear data
				            jQuery('#wpl_e2e_compatibility_list'  ).attr('value', '' );
				            jQuery('#wpl_e2e_compatibility_names' ).attr('value', '' );
				            jQuery('#wpl_e2e_compatibility_remove').attr('value', 'yes' );

						}
						return false;
					});

					// handle add table button
					jQuery('#wpl_btn_add_compatibility_table').on('click', function(evt) {

						// var default_headers = ['Make','Model','Year'];
						var default_headers = prompt('Please enter the table columns separated by comma:','Make,Model,Year').split(',');

						// create table
						jQuery('div.ebay_item_compatibility_table_wrapper').prepend('<table class="ebay_item_compatibility_table"></table>');
						jQuery('table.ebay_item_compatibility_table').append('<tr></tr>');
						jQuery('table.ebay_item_compatibility_table').append('<tr></tr>');
						for (var i = default_headers.length - 1; i >= 0; i--) {
							var col_name = default_headers[i];
							jQuery('table.ebay_item_compatibility_table tr:first').prepend('<th>'+jQuery.trim(col_name)+'</th>');
							jQuery('table.ebay_item_compatibility_table tr:last' ).prepend('<td>Enter '+col_name+'...</td>');
						};
						jQuery('table.ebay_item_compatibility_table tr:first').append('<th>'+'Notes'+'</th>');
						jQuery('table.ebay_item_compatibility_table tr:last' ).append('<td></td>');

						// show table
						jQuery('.ebay_item_compatibility_table_wrapper').slideToggle();

						// hide button
						jQuery('#wpl_btn_add_compatibility_table').hide();

						// make table editable
						wpl_initCompatTable();

						return false; // reject change
					});

				});	


		        function wpl_initCompatTable() {

					// make table editable
					jQuery('table.ebay_item_compatibility_table').editableTableWidget();

					// listen to submit
					// jQuery('form#post').on('submit', function(evt, value) {
					// 	console.log(evt);
					// 	console.log(value);
					// 	alert( evt + value );
					// 	return false;
					// });

					// listen to changes
					jQuery('table.ebay_item_compatibility_table td').on('change', function(evt, newValue) {
						// update hidden data fields
						wpl_updateTableData();
						// return false; // reject change
					});

				};	


		        function wpl_updateTableData() {
		            var row = 0, data = [], cols = [];

		            jQuery('table.ebay_item_compatibility_table').find('tbody tr').each(function () {

		                row += 1;
		                data[row] = [];

		                jQuery(this).find('td').each(function () {
		                    data[row].push(jQuery(this).html());
		                });

		                jQuery(this).find('th').each(function () {
		                    cols.push(jQuery(this).html());
		                });
		            });

		            // Remove undefined
		            data.splice(0, 2);

		            console.log('data',data);
		            // console.log('string', JSON.stringify(data) );
		            // alert(data);

		            // update hidden field
		            jQuery('#wpl_e2e_compatibility_list').attr('value', JSON.stringify(data) );
		            jQuery('#wpl_e2e_compatibility_names').attr('value', JSON.stringify(cols) );
		            jQuery('#wpl_e2e_compatibility_remove').attr('value', '' );

		            // return data;
		        }

			
			</script>

		<?php

		wp_enqueue_script( 'jquery-editable-table' );

	} // showCompatibilityTable()

	function showItemSpecifics() {
	} // showItemSpecifics()

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
            	width: 33%;
            	line-height: 2em;
            }
            #wplister-ebay-shipping label img.help_tip { 
				vertical-align: bottom;
            	float: right;
				margin: 0;
				margin-top: 0.5em;
				margin-right: 0.5em;
            }
            #wplister-ebay-shipping input { 
            	/*width: 64%; */
            }
            #wplister-ebay-shipping .description { 
            	/*clear: both;*/
            	/*margin-left: 33%;*/
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

		// mustache template engine
		wp_register_script( 'jquery-editable-table', WPLISTER_URL.'/js/editable-table/mindmup-editabletable.js', array( 'jquery' ) );
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

			update_post_meta( $post_id, '_ebay_seller_payment_profile_id', 	esc_attr( @$_POST['wpl_ebay_seller_payment_profile_id'] ) );
			update_post_meta( $post_id, '_ebay_seller_return_profile_id', 	esc_attr( @$_POST['wpl_ebay_seller_return_profile_id'] ) );
			update_post_meta( $post_id, '_ebay_bestoffer_enabled', 			esc_attr( @$_POST['wpl_ebay_bestoffer_enabled'] ) );
			update_post_meta( $post_id, '_ebay_bo_autoaccept_price', 		esc_attr( @$_POST['wpl_ebay_bo_autoaccept_price'] ) );
			update_post_meta( $post_id, '_ebay_bo_minimum_price', 			esc_attr( @$_POST['wpl_ebay_bo_minimum_price'] ) );

			// shipping options
			$ebay_shipping_service_type = esc_attr( @$_POST['wpl_e2e_shipping_service_type'] );

			if ( $ebay_shipping_service_type && $ebay_shipping_service_type != 'disabled' ) {
	
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

				delete_post_meta( $post_id, '_ebay_seller_shipping_profile_id' );
				delete_post_meta( $post_id, '_ebay_shipping_loc_enable_free_shipping' );
				delete_post_meta( $post_id, '_ebay_shipping_ShipToLocations' );
				delete_post_meta( $post_id, '_ebay_shipping_ExcludeShipToLocations' );

			}


		}

	} // save_meta_box()

	function woocommerce_duplicate_product( $new_id, $post ) {

		// remove ebay specific meta data from duplicated products
		delete_post_meta( $new_id, '_ebay_title' 			);
		delete_post_meta( $new_id, '_ebay_start_price' 		);
		delete_post_meta( $new_id, '_ebay_upc' 				);
		delete_post_meta( $new_id, '_ebay_gallery_image_url');
		delete_post_meta( $new_id, '_ebay_item_id'			); // created by importer add-on
		delete_post_meta( $new_id, '_ebay_item_source'		); // created by importer add-on

	} // woocommerce_duplicate_product()

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
