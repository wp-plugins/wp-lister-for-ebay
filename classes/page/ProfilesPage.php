<?php
/**
 * ProfilesPage class
 * 
 */

class ProfilesPage extends WPL_Page {

	const slug = 'profiles';

	public function onWpInit() {
		// parent::onWpInit();

		// Add custom screen options
		$load_action = "load-".$this->main_admin_menu_slug."_page_wplister-".self::slug;
		add_action( $load_action, array( &$this, 'addScreenOptions' ) );

	}

	public function onWpAdminMenu() {
		parent::onWpAdminMenu();

		add_submenu_page( self::ParentMenuId, $this->getSubmenuPageTitle( 'Profiles' ), __('Profiles','wplister'), 
						  self::ParentPermissions, $this->getSubmenuId( 'profiles' ), array( &$this, 'onDisplayProfilesPage' ) );
	}

	public function handleSubmit() {
        WPLE()->logger->debug("handleSubmit()");

		// handle duplicate profile
		if ( $this->requestAction() == 'duplicate_auction_profile' ) {
			$this->duplicateProfile();
		}
		// handle delete action
		if ( isset( $_REQUEST['profile'] ) && ( $this->requestAction() == 'delete_profile' ) ) {
			$this->initEC();
			$this->EC->deleteProfiles( $_REQUEST['profile'] );
			$this->EC->closeEbay();
			$this->showMessage( __('Selected items were removed.','wplister') );
		}
	}
	
	function addScreenOptions() {
		$option = 'per_page';
		$args = array(
	    	'label' => 'Profiles',
	        'default' => 20,
	        'option' => 'profiles_per_page'
	        );
		add_screen_option( $option, $args );
		$this->profilesTable = new ProfilesTable();

		// load styles and scripts for this page only
		add_action( 'admin_print_styles', array( &$this, 'onWpPrintStyles' ) );
		add_action( 'admin_enqueue_scripts', array( &$this, 'onWpEnqueueScripts' ) );		

	}
	
	// handle save profile action
	// this needs to be called after WooCommerce initialized its taxonomies, but before the first byte is sent
	public function onWpAdminInit() {

		// handle save profile
		if ( $this->requestAction() == 'save_profile' ) {
			$this->saveProfile();
			if ( @$_POST['return_to'] == 'listings' ) {
				$return_url = get_admin_url().'admin.php?page=wplister';
		        if ( isset($_REQUEST['listing_status']) )	$return_url = add_query_arg( 'listing_status', $_REQUEST['listing_status'], $return_url );
		        if ( isset($_REQUEST['s']) )				$return_url = add_query_arg( 's', $_REQUEST['s'], $return_url );
				wp_redirect( $return_url );
			}
		}

	}


	public function onDisplayProfilesPage() {
		$this->check_wplister_setup();
	
		// edit profile
		if ( ( $this->requestAction() == 'edit' ) || ( $this->requestAction() == 'add_new_profile' ) ) {
			return $this->displayEditPage();			
		} 

    	// Fetch, prepare, sort, and filter our table data...
	    $profilesTable = $this->profilesTable;
	    $profilesTable->prepare_items();

		// process errors 		
		// if ($this->IC->message) $this->showMessage( $this->IC->message,1 );
		
		$aData = array(
			'plugin_url'				=> self::$PLUGIN_URL,
			'message'					=> $this->message,

			'profilesTable'				=> $profilesTable,
		
			'form_action'				=> 'admin.php?page='.self::ParentMenuId.'-profiles'
		);
		$this->display( 'profiles_page', $aData );
		
	}

	public function displayEditPage() {
	
		// init model
		$profilesModel = new ProfilesModel();

		// get item
		if ( $this->requestAction() == 'add_new_profile' ) {
			$item = $profilesModel->newItem();
		} else {
			$item = $profilesModel->getItem( $_REQUEST['profile'] );
		}

		// set account id
		$account_id = $item['account_id'];
		$site_id    = isset( $item['site_id'] ) ? $item['site_id'] : false;
		if ( ! $account_id ) $account_id = get_option( 'wplister_default_account_id' );
		if ( ! $site_id    ) $site_id    = WPLE()->accounts[ $account_id ]->site_id;
	
		// get ebay data
		$payment_options           = EbayPaymentModel::getAll( $site_id );
		$loc_flat_shipping_options = EbayShippingModel::getAllLocal( $site_id, 'flat' );
		$int_flat_shipping_options = EbayShippingModel::getAllInternational( $site_id, 'flat' );
		$shipping_locations        = EbayShippingModel::getShippingLocations( $site_id );
		$exclude_locations         = EbayShippingModel::getExcludeShippingLocations( $site_id );
		$countries                 = EbayShippingModel::getEbayCountries( $site_id );
		$template_files            = $this->getTemplatesList();
		$store_categories          = $this->getStoreCategories( $account_id );

		$loc_calc_shipping_options = EbayShippingModel::getAllLocal( $site_id, 'calculated' );
		$int_calc_shipping_options = EbayShippingModel::getAllInternational( $site_id, 'calculated' );
		$available_attributes      = ProductWrapper::getAttributeTaxonomies();

		// add attribute for SKU
		// $attrib = new stdClass();
		// $attrib->name = '_sku';
		// $attrib->label = 'SKU';
		// $available_attributes[] = $attrib;

		// process custom attributes
		$wpl_custom_attributes = array();
		$custom_attributes = apply_filters( 'wplister_custom_attributes', array() );
		if ( is_array( $custom_attributes ) )
		foreach ( $custom_attributes as $attrib ) {

			$new_attribute = new stdClass();
			$new_attribute->name  = $attrib['id'];
			$new_attribute->label = $attrib['label'];
			$wpl_custom_attributes[] = $new_attribute;

		}

		// $available_dispatch_times     = self::getOption('DispatchTimeMaxDetails');
		// $available_shipping_packages  = self::getOption('ShippingPackageDetails');
		// $ReturnsWithinOptions         = get_option('wplister_ReturnsWithinOptions')

		$available_dispatch_times        = WPLE_eBaySite::getSiteObj( $site_id )->getDispatchTimeMaxDetails();
		$available_shipping_packages     = WPLE_eBaySite::getSiteObj( $site_id )->getShippingPackageDetails();
		$ReturnsWithinOptions            = WPLE_eBaySite::getSiteObj( $site_id )->getReturnsWithinOptions();
		$ShippingCostPaidByOptions       = WPLE_eBaySite::getSiteObj( $site_id )->getShippingCostPaidByOptions();
		

		$prepared_listings  = WPLE_ListingQueryHelper::countItemsUsingProfile( $item['profile_id'], 'prepared' );
		$verified_listings  = WPLE_ListingQueryHelper::countItemsUsingProfile( $item['profile_id'], 'verified' );
		$published_listings = WPLE_ListingQueryHelper::countItemsUsingProfile( $item['profile_id'], 'published' );
		$ended_listings     = WPLE_ListingQueryHelper::countItemsUsingProfile( $item['profile_id'], 'ended' );
		$locked_listings    = WPLE_ListingQueryHelper::countItemsUsingProfile( $item['profile_id'], 'locked' );

		// this turned out to be to ressource intensive with 10k listings:
		// $prepared_listings  = WPLE_ListingQueryHelper::getAllPreparedWithProfile( $item['profile_id'] );
		// $verified_listings  = WPLE_ListingQueryHelper::getAllVerifiedWithProfile( $item['profile_id'] );
		// $published_listings = WPLE_ListingQueryHelper::getAllPublishedWithProfile( $item['profile_id'] );
		// $ended_listings     = WPLE_ListingQueryHelper::getAllEndedWithProfile( $item['profile_id'] );
		// $locked_listings    = WPLE_ListingQueryHelper::getAllLockedWithProfile( $item['profile_id'] );


		// do we have a primary category?
		$details = $item['details'];
		if ( intval( $details['ebay_category_1_id'] ) != 0 ) {
			$primary_category_id = $details['ebay_category_1_id'];
		} else {
			// if not use default category
		    $primary_category_id = self::getOption('default_ebay_category_id');
		}

		// fetch updated item specifics for category
		$specifics = EbayCategoriesModel::getItemSpecificsForCategory( $primary_category_id, $site_id, $account_id );

		// fetch updated available conditions array
		// $item['conditions'] = $this->fetchItemConditions( $primary_category_id, $item['profile_id'], $item['account_id'] );
		$available_conditions = EbayCategoriesModel::getConditionsForCategory( $primary_category_id, false, $account_id );

		// // build available conditions array
		// $available_conditions = false;
		// if ( isset( $item['conditions'][ $primary_category_id ] ) ) {
		// 	$available_conditions = $item['conditions'][ $primary_category_id ];
		// }
		// // echo "<pre>";print_r($available_conditions);echo"</pre>";

		// check if COD is available on the selected site
		$cod_available = false;
		foreach ( $payment_options as $po ) {
			if ( 'COD' == $po['payment_name'] ) $cod_available = true;
		}

		// fetch available shipping discount profiles
		$shipping_flat_profiles = array();
		$shipping_calc_profiles = array();
	    $ShippingDiscountProfiles = self::getOption('ShippingDiscountProfiles', array() );
		if ( isset( $ShippingDiscountProfiles['FlatShippingDiscount'] ) ) {
			$shipping_flat_profiles = $ShippingDiscountProfiles['FlatShippingDiscount'];
		}
		if ( isset( $ShippingDiscountProfiles['CalculatedShippingDiscount'] ) ) {
			$shipping_calc_profiles = $ShippingDiscountProfiles['CalculatedShippingDiscount'];
		}
		// echo "<pre>";print_r($shipping_flat_profiles);echo"</pre>";


		// get available seller profiles
		$seller_profiles_enabled  = get_option('wplister_ebay_seller_profiles_enabled');
		$seller_shipping_profiles = get_option('wplister_ebay_seller_shipping_profiles');
		$seller_payment_profiles  = get_option('wplister_ebay_seller_payment_profiles');
		$seller_return_profiles   = get_option('wplister_ebay_seller_return_profiles');

		if ( isset( WPLE()->accounts[ $account_id ] ) ) {
			$account = WPLE()->accounts[ $account_id ];
			$seller_profiles_enabled  = $account->seller_profiles;
			$seller_shipping_profiles = maybe_unserialize( $account->shipping_profiles );
			$seller_payment_profiles  = maybe_unserialize( $account->payment_profiles );
			$seller_return_profiles   = maybe_unserialize( $account->return_profiles );
		}


		$aData = array(
			'plugin_url'				=> self::$PLUGIN_URL,
			'message'					=> $this->message,

			'item'                      => $item,
			'site_id'                   => $site_id,
			'account_id'                => $account_id,
			'payment_options'           => $payment_options,
			'loc_flat_shipping_options' => $loc_flat_shipping_options,
			'int_flat_shipping_options' => $int_flat_shipping_options,
			'loc_calc_shipping_options' => $loc_calc_shipping_options,
			'int_calc_shipping_options' => $int_calc_shipping_options,
			'available_attributes'      => $available_attributes,
			'custom_attributes'      	=> $wpl_custom_attributes,
			'calc_shipping_enabled'	 	=> in_array( self::getOption('ebay_site_id'), array(0,2,15,100) ),
			'default_ebay_category_id'	=> self::getOption('default_ebay_category_id'),
			'shipping_locations'        => $shipping_locations,
			'exclude_locations'         => $exclude_locations,
			'countries'                 => $countries,
			'template_files'            => $template_files,
			'store_categories'          => $store_categories,
			'prepared_listings_count'   => $prepared_listings,
			'verified_listings_count'   => $verified_listings,
			'published_listings_count'  => $published_listings,
			'ended_listings_count'      => $ended_listings,
			'locked_listings_count'     => $locked_listings,
			'total_listings_count'      => $prepared_listings + $verified_listings + $published_listings,
			'available_dispatch_times'  => $available_dispatch_times,
			'specifics'  				=> $specifics,
			'available_conditions'  	=> $available_conditions,
			'available_shipping_packages' => $available_shipping_packages,
			'shipping_flat_profiles'  	=> $shipping_flat_profiles,
			'shipping_calc_profiles'  	=> $shipping_calc_profiles,
			'cod_available'  			=> $cod_available,
			'ReturnsWithinOptions'  	=> $ReturnsWithinOptions,
			'ShippingCostPaidByOptions' => $ShippingCostPaidByOptions,
			'seller_profiles_enabled'	=> $seller_profiles_enabled,
			'seller_shipping_profiles'	=> $seller_shipping_profiles,
			'seller_payment_profiles'	=> $seller_payment_profiles,
			'seller_return_profiles'	=> $seller_return_profiles,
			
			'form_action'				=> 'admin.php?page='.self::ParentMenuId.'-profiles'
		);
		$this->display( 'profiles_edit_page', array_merge( $aData, $item ) );
		
	}

	private function duplicateProfile() {
				
		// init model
		$profilesModel = new ProfilesModel();
		$new_profile_id = $profilesModel->duplicateProfile( $_REQUEST['profile'] );
		
		// redirect to edit new profile
		wp_redirect( get_admin_url().'admin.php?page=wplister-profiles&action=edit&profile='.$new_profile_id );

	}

	static public function convertToDecimal( $price ) {
		$price = str_replace(',', '.', $price );
		$price = str_replace('$', '', $price );
		// $price = preg_replace( '/[^\d\.]/', '', $price );  
		return $price;
	}

	static public function fixProfilePrices( $details ) {
	
		if ( isset( $details['start_price'] ) ) $details['start_price'] = self::convertToDecimal( $details['start_price'] );
		if ( isset( $details['fixed_price'] ) ) $details['fixed_price'] = self::convertToDecimal( $details['fixed_price'] );
		if ( isset( $details['bo_minimum_price'] ) ) $details['bo_minimum_price'] = self::convertToDecimal( $details['bo_minimum_price'] );
		if ( isset( $details['bo_autoaccept_price'] ) ) $details['bo_autoaccept_price'] = self::convertToDecimal( $details['bo_autoaccept_price'] );

		if ( is_array( $details['loc_shipping_options'] ) )
		foreach ($details['loc_shipping_options'] as $key => &$option) {
			if ( isset( $option['price'] )) $option['price'] = self::convertToDecimal( $option['price'] );
			if ( isset( $option['add_price'] )) $option['add_price'] = self::convertToDecimal( $option['add_price'] );
		}

		if ( is_array( $details['int_shipping_options'] ) )
		foreach ($details['int_shipping_options'] as $key => &$option) {
			if ( isset( $option['price'] )) $option['price'] = self::convertToDecimal( $option['price'] );
			if ( isset( $option['add_price'] )) $option['add_price'] = self::convertToDecimal( $option['add_price'] );
		}

		return $details;
	}

	static public function getPreprocessedPostDetails() {

		// item details
		$details = array();
		foreach ( $_POST as $key => $val ) {
			if ( substr($key, 0, 8 ) == 'wpl_e2e_' ) {
				$field = substr( $key, 8);
				$details[$field] = $val;	
			}
		}
		// print_r($details);die();

		// fix condition_description
		$details['condition_description'] = isset( $details['condition_description'] ) ? stripslashes( $details['condition_description'] ) : '';

		// handle flat and calculated shipping
		$service_type = isset( $details['shipping_service_type'] ) ? $details['shipping_service_type'] : 'flat';

		// process domestic and international shipping options arrays
		switch ( $service_type ) {
			case 'calc':
				$details['loc_shipping_options'] = $details['loc_shipping_options_calc'];
				$details['int_shipping_options'] = $details['int_shipping_options_calc'];
				break;
			
			case 'FlatDomesticCalculatedInternational':
				$details['loc_shipping_options'] = $details['loc_shipping_options_flat'];
				$details['int_shipping_options'] = $details['int_shipping_options_calc'];
				break;
			
			case 'CalculatedDomesticFlatInternational':
				$details['loc_shipping_options'] = $details['loc_shipping_options_calc'];
				$details['int_shipping_options'] = $details['int_shipping_options_flat'];
				break;
			
			default:
				$details['loc_shipping_options'] = $details['loc_shipping_options_flat'];
				$details['int_shipping_options'] = $details['int_shipping_options_flat'];
				break;
		}

		// handle free shipping option
		$loc_free_shipping = strstr( 'calc', strtolower($service_type) ) ? $details['shipping_loc_calc_free_shipping'] : $details['shipping_loc_flat_free_shipping'];
		$details['shipping_loc_enable_free_shipping'] = $loc_free_shipping;

		// fix entered prices
		$details = self::fixProfilePrices( $details );

		// clean details array
		unset( $details['loc_shipping_options_flat'] );
		unset( $details['loc_shipping_options_calc'] );
		unset( $details['int_shipping_options_flat'] );
		unset( $details['int_shipping_options_calc'] );
		unset( $details['shipping_loc_calc_free_shipping'] );
		unset( $details['shipping_loc_flat_free_shipping'] );

		return $details;
	}

	private function saveProfile() {
		global $wpdb;	

		$details    = $this->getPreprocessedPostDetails();
		$profile_id = $this->getValueFromPost( 'profile_id' );
		$account_id = $this->getValueFromPost( 'account_id' );
		if ( ! $account_id ) $account_id = get_option( 'wplister_default_account_id' );

		// fix entered prices
		$details = self::fixProfilePrices( $details );

		// process item specifics
		$item_specifics  = array();
		$itmSpecs_name   = @$_POST['itmSpecs_name'];
		$itmSpecs_value  = @$_POST['itmSpecs_value'];
		$itmSpecs_attrib = @$_POST['itmSpecs_attrib'];

		if ( is_array( $itmSpecs_name ) )
		foreach ($itmSpecs_name as $key => $name) {
			
			#$name = str_replace('\\\\', '', $name );
			$name = stripslashes( $name );

			$value = trim( $itmSpecs_value[$key] );
			$attribute = trim( $itmSpecs_attrib[$key] );

			if ( ( $value != '') || ( $attribute != '' ) ) {
				$spec = new stdClass();
				$spec->name       = $name;
				$spec->value      = $value;
				$spec->attribute  = $attribute;
				$item_specifics[] = $spec;
			}

		}
		$details['item_specifics'] = $item_specifics;

	
		// add category names
		$details['ebay_category_1_name']  = EbayCategoriesModel::getCategoryName( $details['ebay_category_1_id'] );
		$details['ebay_category_2_name']  = EbayCategoriesModel::getCategoryName( $details['ebay_category_2_id'] );
		$details['store_category_1_name'] = EbayCategoriesModel::getStoreCategoryName( $details['store_category_1_id'] );
		$details['store_category_2_name'] = EbayCategoriesModel::getStoreCategoryName( $details['store_category_2_id'] );

		// fix prices - already done in fixProfilePrices()
		// $details['start_price'] = str_replace(',', '.', $details['start_price'] );
		// $details['fixed_price'] = str_replace(',', '.', $details['fixed_price'] );

		// if the user enters only fixed price but no start price, move fixed price to start price
		if ( ( $details['start_price'] == '' ) && ( $details['fixed_price'] != '' ) ) {
			$details['start_price'] = $details['fixed_price'];
			$details['fixed_price'] = '';
		}

		// fix quantities
		if ( ! $details['custom_quantity_enabled'] ) {
			$details['quantity']     = '';
			$details['max_quantity'] = '';
		}
		
		// do we have a primary category?
		if ( intval( $details['ebay_category_1_id'] ) != 0 ) {
			$primary_category_id = $details['ebay_category_1_id'];
		} else {
			// if not use default category
		    $primary_category_id = self::getOption('default_ebay_category_id');
		}

		// // do we have ConditionDetails for primary category?
		// // $conditions = $this->fetchItemConditions( $primary_category_id, $profile_id, $account_id );
		// $conditions = EbayCategoriesModel::getConditionsForCategory( $primary_category_id, false, $account_id );


		// // do we have item specifics for primary category?
		// if ( intval($profile_id) != 0 ) {
		// 	// $saved_specifics = $wpdb->get_var('SELECT category_specifics FROM '.$wpdb->prefix.'ebay_profiles WHERE profile_id = '.$profile_id);
		// 	$saved_specifics = $wpdb->get_var( $wpdb->prepare( "SELECT category_specifics FROM {$wpdb->prefix}ebay_profiles WHERE profile_id = %d", $profile_id ) );
		// 	$saved_specifics = unserialize($saved_specifics);
		// }

		// // fetch required item specifics for primary category
		// if ( ( isset( $saved_specifics[ $primary_category_id ] ) ) && ( $saved_specifics[ $primary_category_id ] != 'none' ) ) {
		// 	$specifics = $saved_specifics; 
		// } elseif ( (int)$primary_category_id != 0 ) {
		// 	$this->initEC( $account_id );
		// 	$specifics = $this->EC->getCategorySpecifics( $primary_category_id );
		// 	$this->EC->closeEbay();
		// } else {
		// 	$specifics = array();
		// }

		// // do we have item specifics for primary category?
		// (improved version of the above, using ebay_categories as cache)
		// $specifics = EbayCategoriesModel::getItemSpecificsForCategory( $primary_category_id, false, $account_id );
		// // $specifics = array( $primary_category_id => $specifics );

		if ( WPLISTER_LIGHT ) $specifics = array();
			
		// sql columns
		$item = array();
		$item['profile_id'] 				= $profile_id;
		$item['profile_name'] 				= $this->getValueFromPost( 'profile_name' );
		$item['profile_description'] 		= $this->getValueFromPost( 'profile_description' );
		$item['listing_duration'] 			= $this->getValueFromPost( 'listing_duration' );
		$item['type']						= $this->getValueFromPost( 'auction_type' );
		$item['sort_order'] 				= intval( $this->getValueFromPost( 'sort_order' ) );
		$item['details']			 		= json_encode( $details );		
		// $item['conditions']			 		= serialize( $conditions );	// deprecated	
		// $item['category_specifics']	 		= serialize( $specifics );	// deprecated	
		$item['conditions']			 		= '';
		$item['category_specifics']	 		= '';
		$item['account_id']					= $account_id;
		$item['site_id']					= WPLE()->accounts[ $item['account_id'] ]->site_id;
		
		// insert or update
		if ( $item['profile_id'] == 0 ) {
			// insert new profile
			unset( $item['profile_id'] );
			$result = $wpdb->insert( $wpdb->prefix.'ebay_profiles', $item );
		} else {
			// update profile
			$result = $wpdb->update( $wpdb->prefix.'ebay_profiles', $item, 
				array( 'profile_id' => $item['profile_id'] ) 
			);
		}

		// proper error handling
		if ($result===false) {
			$this->showMessage( "There was a problem saving your profile.<br>SQL:<pre>".$wpdb->last_query.'</pre>'.$wpdb->last_error, true );	
		} else {
			$this->showMessage( __('Profile saved.','wplister') );

			// if we were updating this template as part of setup, move to next step
			if ( '4' == self::getOption('setup_next_step') ) self::updateOption('setup_next_step', 5);

		}

		// if this is a new profile, skip further processing
		if ( ! $profile_id ) return;

		// handle delayed update option
		if ( isset($_POST['wple_delay_profile_application']) ) {
			update_option( 'wple_job_reapply_profile_id', $profile_id );
			return;
		}

		// prepare for updating items
		$listingsModel = new ListingsModel();
		$profilesModel = new ProfilesModel();
        $profile = $profilesModel->getItem( $this->getValueFromPost( 'profile_id' ) );

		// re-apply profile to all prepared
		if ( $this->getValueFromPost( 'apply_changes_to_all_prepared' ) == 'yes' ) {
			$items = WPLE_ListingQueryHelper::getAllPreparedWithProfile( $item['profile_id'] );
	        $listingsModel->applyProfileToItems( $profile, $items );
			$this->showMessage( sprintf( __('%s prepared items updated.','wplister'), count($items) ) );			
		}
		
		// re-apply profile to all verified
		if ( $this->getValueFromPost( 'apply_changes_to_all_verified' ) == 'yes' ) {
			$items = WPLE_ListingQueryHelper::getAllVerifiedWithProfile( $item['profile_id'] );
	        $listingsModel->applyProfileToItems( $profile, $items );
			$this->showMessage( sprintf( __('%s verified items updated.','wplister'), count($items) ) );			
		}
		
		// re-apply profile to all published
		if ( $this->getValueFromPost( 'apply_changes_to_all_published' ) == 'yes' ) {
			$items = WPLE_ListingQueryHelper::getAllPublishedWithProfile( $item['profile_id'] );
	        $listingsModel->applyProfileToItems( $profile, $items );
			$this->showMessage( sprintf( __('%s published items changed.','wplister'), count($items) ) );			
		}
		
		// re-apply profile to all ended
		if ( $this->getValueFromPost( 'apply_changes_to_all_ended' ) == 'yes' ) {
			$items = WPLE_ListingQueryHelper::getAllEndedWithProfile( $item['profile_id'] );
	        $listingsModel->applyProfileToItems( $profile, $items );
			$this->showMessage( sprintf( __('%s ended items updated.','wplister'), count($items) ) );			

			// update ended listings - required for autorelist to be applied
			// $listingsModel->updateEndedListings();
			$this->initEC( $account_id );
			$this->EC->updateListings();
			$this->EC->closeEbay();			
		}
		
	} // saveProfile()

	
	// // deprecated
	// public function fetchItemConditions( $ebay_category_id, $profile_id, $account_id ) {
	// 	global $wpdb;

	// 	if ( ! $profile_id ) return array();

	// 	// get saved conditions for profile
	// 	// $saved_conditions = $wpdb->get_var('SELECT conditions FROM '.$wpdb->prefix.'ebay_profiles WHERE profile_id = '.$profile_id );
	// 	$saved_conditions = $wpdb->get_var( $wpdb->prepare( "SELECT conditions FROM {$wpdb->prefix}ebay_profiles WHERE profile_id = %d", $profile_id ) );
	// 	$saved_conditions = unserialize( $saved_conditions );

	// 	if ( ( isset( $saved_conditions[ $ebay_category_id ] ) ) && ( $saved_conditions[ $ebay_category_id ] != 'none' ) ) {

	// 		// conditions for primary category are already saved
	// 		$conditions = $saved_conditions; 

	// 	} elseif ( intval( $ebay_category_id ) != 0 ) {

	// 		// call GetCategoryFeatures for primary category
	// 		$this->initEC( $account_id );
	// 		$conditions = $this->EC->getCategoryConditions( $ebay_category_id );
	// 		$this->EC->closeEbay();

	// 	} else {
	// 		$conditions = array();
	// 	}

	// 	return $conditions;
	// } // fetchItemConditions()

	
	public function getTemplatesList() {

		$templatesModel = new TemplatesModel();
		$templates = $templatesModel->getAll();
		return $templates;
	}
	
	public function getStoreCategories( $account_id ) {
		global $wpdb;
		
		$results = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}ebay_store_categories WHERE account_id = %d", $account_id ) );
		return $results;
	}

	
	public function onWpPrintStyles() {

		// jqueryFileTree
		wp_register_style('jqueryFileTree_style', self::$PLUGIN_URL.'/js/jqueryFileTree/jqueryFileTree.css' );
		wp_enqueue_style('jqueryFileTree_style'); 

		// load styles for chosen.js
		global $woocommerce;
		if ( is_object($woocommerce) )
 			wp_enqueue_style( 'woocommerce_admin_styles', $woocommerce->plugin_url() . '/assets/css/admin.css' );

		// testing:
		// jQuery UI theme - for progressbar
		// wp_register_style('jQueryUITheme', 'http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.13/themes/cupertino/jquery-ui.css');
		// wp_enqueue_style('jQueryUITheme'); 

	}

	public function onWpEnqueueScripts() {

		// jqueryFileTree
		wp_register_script( 'jqueryFileTree', self::$PLUGIN_URL.'/js/jqueryFileTree/jqueryFileTree.js', array( 'jquery' ) );
		wp_enqueue_script( 'jqueryFileTree' );

		// nano template engine
		// wp_register_script( 'jquery_nano', self::$PLUGIN_URL.'/js/template/jquery.nano.js', array( 'jquery' ) );
		// wp_enqueue_script( 'jquery_nano' );

		// mustache template engine
		wp_register_script( 'mustache', self::$PLUGIN_URL.'/js/template/mustache.js', array( 'jquery' ) );
		wp_enqueue_script( 'mustache' );

		// enqueue chosen.js from WooCommerce
      	// wp_enqueue_script( 'ajax-chosen' );
	   	wp_enqueue_script( 'chosen' );

		// jQuery UI Autocomplete
		wp_enqueue_script( 'jquery-ui-button' );
		wp_enqueue_script( 'jquery-ui-autocomplete' );

		// testing:
		// jQuery UI progressbar
        // wp_enqueue_script('jquery-ui-core');
        // wp_enqueue_script('jquery-ui-progressbar');

	    // jQuery UI Dialog
    	// wp_enqueue_style( 'wp-jquery-ui-dialog' );
	    // wp_enqueue_script ( 'jquery-ui-dialog' ); 

	}


	static public function wpl_generate_shipping_option_tags( $services, $selected_service ) {
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
				<?php if ( isset($selected_service['service_name']) && $selected_service['service_name'] == $service['service_name'] ) : ?>
					selected="selected"
				<?php endif; ?>
				><?php echo $service['service_description'] ?></option>
		<?php endforeach; ?>
		</optgroup>

		<?php	
	}



}
