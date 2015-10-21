<?php

class ProfilesModel extends WPL_Model {

	function ProfilesModel()
	{
		// global $wpl_logger;
		// $this->logger = &$wpl_logger;

		global $wpdb;
		$this->tablename = $wpdb->prefix . 'ebay_profiles';
	}
	

	function getAll() {
		global $wpdb;	
		$profiles = $wpdb->get_results("
			SELECT * 
			FROM $this->tablename
			ORDER BY sort_order ASC, profile_name ASC
		", ARRAY_A);		

		foreach( $profiles as &$profile ) {
			$profile['details'] = self::decodeObject( $profile['details'] );
		}

		return $profiles;		
	}


	function getItem( $id ) {
		global $wpdb;	
		$item = $wpdb->get_row( $wpdb->prepare("
			SELECT * 
			FROM $this->tablename
			WHERE profile_id = %s
		", $id 
		), ARRAY_A);		

		$item['details'] = self::decodeObject( $item['details'], true );
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
			"profile_id"          => false,
			"profile_name"        => "New profile",
			"profile_description" => "",
			"listing_duration"    => "Days_7",
			"account_id"    	  => get_option( 'wplister_default_account_id' ),
		);

		$item['details'] = array(	
			"auction_type"            => "FixedPriceItem",
			"condition_id"            => "1000",
			"counter_style"           => "BasicStyle",
			"country"                 => "US",
			"currency"                => "USD",
			"dispatch_time"           => "2",
			"ebay_category_1_id"      => "",
			"ebay_category_1_name"    => null,
			"ebay_category_2_id"      => "",
			"ebay_category_2_name"    => null,
			"fixed_price"             => "",
			"int_shipping_options"    => array(),
			"listing_duration"        => "Days_7",
			"loc_shipping_options"    => array(),
			"location"                => "",
			"payment_options"         => array(),
			"profile_description"     => "",
			"profile_name"            => "New profile",
			"custom_quantity_enabled" => "",
			"max_quantity"            => "",
			"quantity"                => "",
			"returns_accepted"        => "1",
			"returns_description"     => "",
			"returns_within"          => "Days_14",
			"start_price"             => "",
			"store_category_1_id"     => "",
			"store_category_2_id"     => "",
			"tax_mode"                => "none",
			"template"                => "",
			"title_prefix"            => "",
			"title_suffix"            => "",
			"vat_percent"             => "",
			"with_gallery_image"      => "1"
		);

		$item['conditions'] = array();
		
		// make sure that at least one payment and shipping option exist
		$item['details']['loc_shipping_options'] = $this->fixShippingArray();
		$item['details']['int_shipping_options'] = $this->fixShippingArray();
		$item['details']['payment_options'] 	 = $this->fixShippingArray();

		return $item;		
	}

	// make sure, $options array contains at least one item
	static function fixShippingArray( $options = false ) {
		if ( !is_array( $options )  ) $options = array( '' );
		if ( count( $options ) == 0 ) $options = array( '' );
		return $options;
	}

	function deleteItem( $id ) {
		global $wpdb;

		// check if there are listings using this profile
		$listings = WPLE_ListingQueryHelper::getAllWithProfile( $id );
		if ( ! empty($listings) ) {
			$this->showMessage('This profile is applied to '.count($listings).' listings and can not be deleted.',1,1);
			return false;
		}

		$wpdb->query( $wpdb->prepare("
			DELETE
			FROM $this->tablename
			WHERE profile_id = %s
		", $id ) );
	}


	function insertProfile($id, $details)
	{
		global $wpdb;

		$data['profile_id'] = $id;
		$data['profile_name'] = $data['profile_name'];
		$data['details'] = self::encodeObject($details);

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
		$data = $wpdb->get_row( $wpdb->prepare("
			SELECT * 
			FROM $this->tablename
			WHERE profile_id = %s
		", $id 
		), ARRAY_A);
				
		// adjust duplicate
		$data['profile_name'] = $data['profile_name'] .' ('. __('duplicated','wplister').')';
		unset( $data['profile_id'] );				

		// insert record				
		$wpdb->insert( $this->tablename, $data );

		return $wpdb->insert_id;		

	}

	function getAllNames() {
		global $wpdb;	

		// return if DB has not been initialized yet
		if ( get_option('wplister_db_version') < 37 ) return array();

		$results = $wpdb->get_results("
			SELECT profile_id, profile_name 
			FROM $this->tablename
			ORDER BY sort_order ASC, profile_name ASC
		");		

		$profiles = array();
		foreach( $results as $result ) {
			$profiles[ $result->profile_id ] = $result->profile_name;
		}

		return $profiles;		
	}


	function getPageItems( $current_page, $per_page ) {
		global $wpdb;

        $orderby  = (!empty($_REQUEST['orderby'])) ? esc_sql( $_REQUEST['orderby'] ) : 'profile_name';
        $order    = (!empty($_REQUEST['order']))   ? esc_sql( $_REQUEST['order']   ) : 'asc';
        $offset   = ( $current_page - 1 ) * $per_page;
        $per_page = esc_sql( $per_page );

        // regard sort order if sorted by profile name
        if ( $orderby == 'profile_name' ) $orderby = 'sort_order '.$order.', profile_name';

        $join_sql  = '';
        $where_sql = 'WHERE 1 = 1 ';

        // filter search_query
		$search_query = isset($_REQUEST['s']) ? esc_sql( $_REQUEST['s'] ) : false;
		if ( $search_query ) {
			$where_sql .= "
				AND  ( profile_name        LIKE '%".$search_query."%'
					OR profile_description LIKE '%".$search_query."%' )
			";
		} 

        // get items
		$items = $wpdb->get_results("
			SELECT *
			FROM $this->tablename
            $join_sql 
	        $where_sql
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
	            $join_sql 
    	        $where_sql
				ORDER BY $orderby $order
			");			
		}

		foreach( $items as &$profile ) {
			$profile['details'] = self::decodeObject( $profile['details'] );
		}

		return $items;
	}


} // class ProfilesModel
