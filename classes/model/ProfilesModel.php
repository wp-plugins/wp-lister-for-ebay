<?php

class ProfilesModel extends WPL_Model {

	function ProfilesModel()
	{
		global $wpl_logger;
		$this->logger = &$wpl_logger;

		global $wpdb;
		$this->tablename = $wpdb->prefix . 'ebay_profiles';
	}
	

	function getAll() {
		global $wpdb;	
		$profiles = $wpdb->get_results("
			SELECT * 
			FROM $this->tablename
		", ARRAY_A);		

		foreach( $profiles as &$profile ) {
			$profile['details'] = $this->decodeObject( $profile['details'] );
		}

		return $profiles;		
	}


	function getItem( $id ) {
		global $wpdb;	
		$item = $wpdb->get_row("
			SELECT * 
			FROM $this->tablename
			WHERE profile_id = '$id'
		", ARRAY_A);		

		$item['details'] = $this->decodeObject( $item['details'], true );
		$item['conditions'] = unserialize( $item['conditions'] );
		
		// get category names
		$item['details']['ebay_category_1_name'] = EbayCategoriesModel::getCategoryName( $item['details']['ebay_category_1_id'] );
		$item['details']['ebay_category_2_name'] = EbayCategoriesModel::getCategoryName( $item['details']['ebay_category_2_id'] );

		// make sure that at least one payment and shipping option exist
		$item['details']['loc_shipping_options'] = $this->fixShippingArray( $item['details']['loc_shipping_options'] );
		$item['details']['int_shipping_options'] = $this->fixShippingArray( $item['details']['int_shipping_options'] );
		$item['details']['payment_options'] = $this->fixShippingArray( $item['details']['payment_options'] );

		return $item;		
	}

	function newItem() {
		$item = array(
			"profile_id" => false,
			"profile_name" => "New profile",
			"profile_description" => "",
			"listing_duration" => "Days_7",
		);

		$item['details'] = array(
		
			"auction_type" => "FixedPriceItem",
			"condition_id" => "1000",
			"counter_style" => "BasicStyle",
			"country" => "US",
			"currency" => "USD",
			"dispatch_time" => "2",
			"ebay_category_1_id" => "",
			"ebay_category_1_name" => null,
			"ebay_category_2_id" => "",
			"ebay_category_2_name" => null,
			"fixed_price" => "",
			"int_shipping_options" => array(),
			"listing_duration" => "Days_7",
			"loc_shipping_options" => array(),
			"location" => "",
			"payment_options" => array(),
			"profile_description" => "",
			// "profile_id" => "0",
			"profile_name" => "New profile",
			"quantity" => "1",
			"returns_accepted" => "1",
			"returns_description" => "",
			"returns_within" => "Days_14",
			"start_price" => "",
			"store_category_1_id" => "",
			"store_category_2_id" => "",
			"tax_mode" => "none",
			"template" => "",
			"title_prefix" => "",
			"title_suffix" => "",
			"vat_percent" => "",
			"with_gallery_image" => "1",
			"with_image" => "1"

		);


		$item['conditions'] = array();
		
		// make sure that at least one payment and shipping option exist
		$item['details']['loc_shipping_options'] = $this->fixShippingArray();
		$item['details']['int_shipping_options'] = $this->fixShippingArray();
		$item['details']['payment_options'] 	 = $this->fixShippingArray();

		return $item;		
	}

	// make sure, $options array contains at least one item
	function fixShippingArray( $options = false ) {
		if ( !is_array( $options )  ) $options = array( '' );
		if ( count( $options ) == 0 ) $options = array( '' );
		return $options;
	}

	function deleteItem( $id ) {
		global $wpdb;
		$wpdb->query("
			DELETE
			FROM $this->tablename
			WHERE profile_id = '$id'
		");
	}


	function insertProfile($id, $details)
	{
		global $wpdb;

		$data['profile_id'] = $id;
		$data['profile_name'] = $data['profile_name'];
		$data['details'] = $this->encodeObject($details);

		$wpdb->insert($this->tablename, $data);
					
		return true;
	}

	function updateProfile($id, $data) {
		global $wpdb;	
		$result = $wpdb->update( $this->tablename, $data, array( 'profile_id' => $id ) );

		return $result;		

	}

	function duplicateProfile($id) {
		global $wpdb;	

		// get raw db content
		$data = $wpdb->get_row("
			SELECT * 
			FROM $this->tablename
			WHERE profile_id = '$id'
		", ARRAY_A);
				
		// adjust duplicate
		$data['profile_name'] = $data['profile_name'] .' ('. __('duplicated','wplister').')';
		unset( $data['profile_id'] );				

		// insert record				
		$wpdb->insert( $this->tablename, $data );

		return $wpdb->insert_id;		

	}

	function getAllNames() {
		global $wpdb;	
		$results = $wpdb->get_results("
			SELECT profile_id, profile_name 
			FROM $this->tablename
		");		

		$profiles = array();
		foreach( $results as $result ) {
			$profiles[ $result->profile_id ] = $result->profile_name;
		}

		return $profiles;		
	}


	function getPageItems( $current_page, $per_page ) {
		global $wpdb;

        $orderby = (!empty($_REQUEST['orderby'])) ? $_REQUEST['orderby'] : 'profile_id'; //If no sort, default to title
        $order = (!empty($_REQUEST['order'])) ? $_REQUEST['order'] : 'desc'; //If no order, default to asc
        $offset = ( $current_page - 1 ) * $per_page;

        // get items
		$items = $wpdb->get_results("
			SELECT *
			FROM $this->tablename
			ORDER BY $orderby $order
            LIMIT $offset, $per_page
		", ARRAY_A);

		// get total items count - if needed
		if ( ( $current_page == 1 ) && ( count( $items ) < $per_page ) ) {
			$this->total_items = count( $items );
		} else {
			$this->total_items = $wpdb->get_var("
				SELECT COUNT(*)
				FROM $this->tablename
				ORDER BY $orderby $order
			");			
		}

		foreach( $items as &$profile ) {
			$profile['details'] = $this->decodeObject( $profile['details'] );
		}

		return $items;
	}



}
