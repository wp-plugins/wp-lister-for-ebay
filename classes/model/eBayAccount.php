<?php
/**
 * WPLE_eBayAccount class
 *
 */

// class WPLE_eBayAccount extends WPLE_NewModel {
class WPLE_eBayAccount extends WPL_Core {

	const TABLENAME = 'ebay_accounts';

	var $id;
	var $title;
	var $site_id;
	var $site_code;

	function __construct( $id = null ) {
		
		$this->init();

		if ( $id ) {
			$this->id = $id;
			
			$account = $this->getAccount( $id );
			if ( ! $account ) return false; // this doesn't actually return an empty object - why?

			// load data into object		
			foreach( $account AS $key => $value ){
			    $this->$key = $value;
			}

			return $this;
		}

	}

	function init()	{
		// global $wpl_logger;
		// $this->logger = &$wpl_logger;

		$this->fieldnames = array(
			'title',
			'site_id',
			'site_code',
			'active',
			'sandbox_mode',
			'token',
			'user_name',
			'user_details',
			'valid_until',
			'ebay_motors',
			'oosc_mode',
			'seller_profiles',
			'shipping_profiles',
			'payment_profiles',
			'return_profiles',
			'shipping_discount_profiles',
			'categories_map_ebay',
			'categories_map_store',
			'default_ebay_category_id',
			'paypal_email',
			'sync_orders',
			'sync_products',
			'last_orders_sync',
		);

	}

	// get single account
	static function getAccount( $id )	{
		global $wpdb;
		$table = $wpdb->prefix . self::TABLENAME;
		
		$item = $wpdb->get_row("
			SELECT *
			FROM $table
			WHERE id = '$id'
		", OBJECT);

		// $item->allowed_sites = maybe_unserialize( $item->allowed_sites );
		return $item;
	}

	// get all accounts
	static function getAll( $include_inactive = false ) {
		global $wpdb;
		$table = $wpdb->prefix . self::TABLENAME;

		// return if DB has not been initialized yet
		if ( get_option('wplister_db_version') < 37 ) return array();

		$where_sql = $include_inactive ? '' : 'WHERE active = 1';
		$items = $wpdb->get_results("
			SELECT *
			FROM $table
			$where_sql
			ORDER BY title ASC
		", OBJECT_K);

		return $items;
	}

	// get account title
	static function getAccountTitle( $id )	{
		global $wpdb;
		$table = $wpdb->prefix . self::TABLENAME;
		
		$account_title = $wpdb->get_var("
			SELECT title
			FROM $table
			WHERE id = '$id'
		");
		return $account_title;
	}

	// get this account's site
	function getSite()	{

		// return WPLA_AmazonSite::getSite( $this->site_id );

	}

	// save account
	function add() {
		global $wpdb;
		$table = $wpdb->prefix . self::TABLENAME;

		$data = array();
		foreach ( $this->fieldnames as $key ) {
			if ( isset( $this->$key ) ) {
				$data[ $key ] = $this->$key;
			} 
		}

		if ( sizeof( $data ) > 0 ) {
			$result = $wpdb->insert( $table, $data );
			echo $wpdb->last_error;

			$this->id = $wpdb->insert_id;
			return $wpdb->insert_id;		
		}

	}

	// update feed
	function update() {
		global $wpdb;
		$table = $wpdb->prefix . self::TABLENAME;
		if ( ! $this->id ) return;

		$data = array();
		foreach ( $this->fieldnames as $key ) {
			if ( isset( $this->$key ) ) {
				$data[ $key ] = $this->$key;
			} 
		}

		if ( sizeof( $data ) > 0 ) {
			$result = $wpdb->update( $table, $data, array( 'id' => $this->id ) );
			echo $wpdb->last_error;
			// echo "<pre>";print_r($wpdb->last_query);echo"</pre>";#die();
			// return $wpdb->insert_id;		
		}

	}

	function updateUserDetails() {
		if ( ! $this->id ) return;

		// update token expiration date
		$this->initEC( $this->id );
		$expdate = $this->EC->GetTokenStatus( true );
		$this->EC->closeEbay();
		if ( $expdate ) {
			$this->valid_until = $expdate;
			$this->update();
			update_option( 'wplister_ebay_token_is_invalid', false );
		}

		// update user details
		$this->initEC( $this->id );
		$user_details = $this->EC->GetUser( true );
		$this->EC->closeEbay();
		if ( $user_details ) {
			$this->user_name 	= $user_details->UserID;
			$this->user_details = maybe_serialize( $user_details );
			$this->update();
		}

		// update seller profiles
		$this->initEC( $this->id );
		$result = $this->EC->GetUserPreferences( true );
		$this->EC->closeEbay();
		if ( $result ) {
			$this->seller_profiles   = $result->SellerProfileOptedIn ? 1 : 0;
			$this->shipping_profiles = maybe_serialize( $result->seller_shipping_profiles );
			$this->payment_profiles  = maybe_serialize( $result->seller_payment_profiles );
			$this->return_profiles   = maybe_serialize( $result->seller_return_profiles );
			$this->update();
		}

	} // updateUserDetails()


	// delete account
	function delete() {
		global $wpdb;
		$table = $wpdb->prefix . self::TABLENAME;
		if ( ! $this->id ) return;

		$result = $wpdb->delete( $table, array( 'id' => $this->id ) );
		echo $wpdb->last_error;
	}

	function getPageItems( $current_page, $per_page ) {
		global $wpdb;
		$table = $wpdb->prefix . self::TABLENAME;

        $orderby = (!empty($_REQUEST['orderby'])) ? $_REQUEST['orderby'] : 'title'; //If no sort, default to title
        $order = (!empty($_REQUEST['order'])) ? $_REQUEST['order'] : 'asc'; //If no order, default to asc
        $offset = ( $current_page - 1 ) * $per_page;

        // get items
		$items = $wpdb->get_results("
			SELECT *
			FROM $table
			ORDER BY $orderby $order
            LIMIT $offset, $per_page
		", ARRAY_A);

		// get total items count - if needed
		if ( ( $current_page == 1 ) && ( count( $items ) < $per_page ) ) {
			$this->total_items = count( $items );
		} else {
			$this->total_items = $wpdb->get_var("
				SELECT COUNT(*)
				FROM $table
				ORDER BY $orderby $order
			");			
		}

		foreach( $items as &$account ) {
			// $account['ReportTypeName'] = $this->getRecordTypeName( $account['ReportType'] );
		}

		return $items;
	}


} // WPLE_eBayAccount()


