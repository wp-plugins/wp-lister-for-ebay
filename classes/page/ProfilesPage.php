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
		add_action( "load-wp-lister_page_wplister-".self::slug, array( &$this, 'addScreenOptions' ) );

		// handle save profile
		if ( $this->requestAction() == 'save_profile' ) {
			$this->saveProfile();
			if ( @$_POST['return_to'] == 'listings' ) {
				wp_redirect( get_admin_url().'admin.php?page=wplister' );
			}
		}

	}

	public function onWpAdminMenu() {
		parent::onWpAdminMenu();

		add_submenu_page( self::ParentMenuId, $this->getSubmenuPageTitle( 'Profiles' ), __('Profiles','wplister'), 
						  self::ParentPermissions, $this->getSubmenuId( 'profiles' ), array( &$this, 'onDisplayProfilesPage' ) );
	}

	public function handleSubmit() {
        $this->logger->debug("handleSubmit()");

		// handle duplicate profile
		if ( $this->requestAction() == 'duplicate_auction_profile' ) {
			$this->duplicateProfile();
		}
		// handle delete action
		if ( $this->requestAction() == 'delete' ) {
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
	


	public function onDisplayProfilesPage() {
		WPL_Setup::checkSetup();
	
		// init model
		$profilesModel = new ProfilesModel();

		// edit profile
		if ( ( $this->requestAction() == 'edit' ) || ( $this->requestAction() == 'add_new_profile' ) ) {

			// get item
			if ( $this->requestAction() == 'add_new_profile' ) {
				$item = $profilesModel->newItem();
			} else {
				$item = $profilesModel->getItem( $_REQUEST['profile'] );
			}
			
			// get ebay data
			$payment_options           = EbayPaymentModel::getAll();
			$loc_flat_shipping_options = EbayShippingModel::getAllLocal('flat');
			$int_flat_shipping_options = EbayShippingModel::getAllInternational('flat');
			$shipping_locations        = EbayShippingModel::getShippingLocations();
			$countries                 = EbayShippingModel::getEbayCountries();
			$template_files            = $this->getTemplatesList();
			$store_categories          = $this->getStoreCategories();
			$available_dispatch_times     = self::getOption('DispatchTimeMaxDetails');
			
			$listingsModel = new ListingsModel();
			$prepared_listings  = $listingsModel->getAllPreparedWithProfile( $item['profile_id'] );
			$verified_listings  = $listingsModel->getAllVerifiedWithProfile( $item['profile_id'] );
			$published_listings = $listingsModel->getAllPublishedWithProfile( $item['profile_id'] );

			$aData = array(
				'plugin_url'				=> self::$PLUGIN_URL,
				'message'					=> $this->message,
	
				'item'                      => $item,
				'payment_options'           => $payment_options,
				'loc_flat_shipping_options' => $loc_flat_shipping_options,
				'int_flat_shipping_options' => $int_flat_shipping_options,
				'shipping_locations'        => $shipping_locations,
				'countries'                 => $countries,
				'template_files'            => $template_files,
				'store_categories'          => $store_categories,
				'prepared_listings'         => $prepared_listings,
				'verified_listings'         => $verified_listings,
				'published_listings'        => $published_listings,
				'available_dispatch_times'  => $available_dispatch_times,
				
				'form_action'				=> 'admin.php?page='.self::ParentMenuId.'-profiles'
			);
			$this->display( 'profiles_edit_page', array_merge( $aData, $item ) );
		
		// show list
		} else {

		    //Create an instance of our package class...
		    $profilesTable = $this->profilesTable;

	    	//Fetch, prepare, sort, and filter our data...
		    $profilesTable->prepare_items();
	
			// process errors 		
			#if ($this->IC->message) $this->showMessage( $this->IC->message,1 );
			
			$aData = array(
				'plugin_url'				=> self::$PLUGIN_URL,
				'message'					=> $this->message,
	
				'profilesTable'				=> $profilesTable,
			
				'form_action'				=> 'admin.php?page='.self::ParentMenuId.'-profiles'
			);
			$this->display( 'profiles_page', $aData );
		
		}

	}

	private function duplicateProfile() {
				
		// init model
		$profilesModel = new ProfilesModel();
		$new_profile_id = $profilesModel->duplicateProfile( $_REQUEST['profile'] );
		
		// redirect to edit new profile
		wp_redirect( get_admin_url().'admin.php?page=wplister-profiles&action=edit&profile='.$new_profile_id );

	}

	private function saveProfile() {
		global $wpdb;	

		// item details
		$details = array();
		foreach ( $_POST as $key => $val ) {
			if ( substr($key, 0, 8 ) == 'wpl_e2e_' ) {
				$field = substr( $key, 8);
				$details[$field] = $val;	
			}
		}
		// print_r($details);die();

		// process domestic and international shipping options arrays
		if ( 'calc' == @$details['shipping_service_type'] ) { 
			$details['loc_shipping_options'] = $details['loc_shipping_options_calc'];
			$details['int_shipping_options'] = $details['int_shipping_options_calc'];
		} else {
			$details['loc_shipping_options'] = $details['loc_shipping_options_flat'];
			$details['int_shipping_options'] = $details['int_shipping_options_flat'];
		}
		unset( $details['loc_shipping_options_flat'] );
		unset( $details['loc_shipping_options_calc'] );
		unset( $details['int_shipping_options_flat'] );
		unset( $details['int_shipping_options_calc'] );


		// process item specifics
		$item_specifics = array();
		$details['item_specifics'] = $item_specifics;

	
		// add category names
		$details['ebay_category_1_name']  = EbayCategoriesModel::getCategoryName( $details['ebay_category_1_id'] );
		$details['ebay_category_2_name']  = EbayCategoriesModel::getCategoryName( $details['ebay_category_2_id'] );
		$details['store_category_1_name'] = EbayCategoriesModel::getStoreCategoryName( $details['store_category_1_id'] );
		$details['store_category_2_name'] = EbayCategoriesModel::getStoreCategoryName( $details['store_category_2_id'] );

		// fix prices
		$details['start_price'] = str_replace(',', '.', $details['start_price'] );
		$details['fixed_price'] = str_replace(',', '.', $details['fixed_price'] );
		// if the user enters only fixed price but no start price, move fixed price to start price
		if ( ( $details['start_price'] == '' ) && ( $details['fixed_price'] != '' ) ) {
			$details['start_price'] = $details['fixed_price'];
			$details['fixed_price'] = '';
		}

		
		// do we have ConditionDetails for primary category?
		if ( intval($this->getValueFromPost( 'profile_id' )) != 0 ) {
			$saved_conditions = $wpdb->get_var('SELECT conditions FROM '.$wpdb->prefix.'ebay_profiles WHERE profile_id = '.$this->getValueFromPost( 'profile_id' ));
			$saved_conditions = unserialize($saved_conditions);
		}

		if ( isset( $saved_conditions[$details['ebay_category_1_id']] ) ) {
			$conditions = $saved_conditions; 
		} elseif ( (int)$details['ebay_category_1_id'] != 0 ) {
			// call GetCategoryFeatures for category #1
			$this->initEC();
			$conditions = $this->EC->getCategoryConditions( $details['ebay_category_1_id'] );
			$this->EC->closeEbay();
		} else {
			$conditions = array();
		}


		if ( WPLISTER_LIGHT ) $specifics = array();
			
		// sql columns
		$item = array();
		$item['profile_id'] 				= $this->getValueFromPost( 'profile_id' );
		$item['profile_name'] 				= $this->getValueFromPost( 'profile_name' );
		$item['profile_description'] 		= $this->getValueFromPost( 'profile_description' );
		$item['listing_duration'] 			= $this->getValueFromPost( 'listing_duration' );
		$item['type']						= $this->getValueFromPost( 'auction_type' );
		$item['details']			 		= json_encode( $details );		
		$item['conditions']			 		= serialize( $conditions );		
		$item['category_specifics']	 		= serialize( $specifics );		
		
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
			$this->showMessage( "There was a problem saving your profile.<br>SQL:<pre>".$wpdb->last_query.'</pre>'.mysql_error(), true );	
		} else {
			$this->showMessage( __('Profile saved.','wplister') );

			// if we were updating this template as part of setup, move to next step
			if ( '4' == self::getOption('setup_next_step') ) self::updateOption('setup_next_step', 5);

		}

		// prepare for updating items
		$listingsModel = new ListingsModel();
		$profilesModel = new ProfilesModel();
        $profile = $profilesModel->getItem( $this->getValueFromPost( 'profile_id' ) );

		// re-apply profile to all prepared
		if ( $this->getValueFromPost( 'apply_changes_to_all_prepared' ) == 'yes' ) {
			$items = $listingsModel->getAllPreparedWithProfile( $item['profile_id'] );
	        $listingsModel->applyProfileToNewListings( $profile, $items );
			$this->showMessage( sprintf( __('%s prepared items updated.','wplister'), count($items) ) );			
		}
		
		// re-apply profile to all verified
		if ( $this->getValueFromPost( 'apply_changes_to_all_verified' ) == 'yes' ) {
			$items = $listingsModel->getAllVerifiedWithProfile( $item['profile_id'] );
	        $listingsModel->applyProfileToNewListings( $profile, $items );
			$this->showMessage( sprintf( __('%s verified items updated.','wplister'), count($items) ) );			
		}
		
		// re-apply profile to all published
		if ( $this->getValueFromPost( 'apply_changes_to_all_published' ) == 'yes' ) {
			$items = $listingsModel->getAllPublishedWithProfile( $item['profile_id'] );
	        $listingsModel->applyProfileToNewListings( $profile, $items );
			$this->showMessage( sprintf( __('%s published items changed.','wplister'), count($items) ) );			
		}
		
	}

	
	public function getTemplatesList() {

		$templatesModel = new TemplatesModel();
		$templates = $templatesModel->getAll();
		return $templates;
	}
	
	public function getStoreCategories() {
		global $wpdb;
		
		$results = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}ebay_store_categories" );		
		return $results;
	}

	
	public function onWpPrintStyles() {

		// jqueryFileTree
		wp_register_style('jqueryFileTree_style', self::$PLUGIN_URL.'/js/jqueryFileTree/jqueryFileTree.css' );
		wp_enqueue_style('jqueryFileTree_style'); 

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


}
