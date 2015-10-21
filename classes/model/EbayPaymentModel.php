<?php
/**
 * EbayPaymentModel class
 *
 * responsible for managing payment methods and talking to ebay
 * 
 */

// list of used EbatNs classes:

// require_once 'EbatNs_ServiceProxy.php';

// require_once 'GeteBayDetailsRequestType.php';
// require_once 'PaymentOptionDetailsType.php';	
// require_once 'EbatNs_Logger.php';

class EbayPaymentModel extends WPL_Model {
	const table = 'ebay_payment';

	var $_session;
	var $_cs;
	var $site_id;

	function EbayPaymentModel()
	{
		// global $wpl_logger;
		// $this->logger = &$wpl_logger;
	
		global $wpdb;
		$this->tablename = $wpdb->prefix . self::table;
	}
	
	function downloadPaymentDetails( $session, $site_id )
	{
		$this->initServiceProxy($session);
		$this->site_id = $site_id;
		
		$this->_cs->setHandler('PaymentOptionDetailsType', array(& $this, 'storePaymentDetail'));
		
		// truncate the db
		global $wpdb;
		$wpdb->query( $wpdb->prepare("DELETE FROM {$this->tablename} WHERE site_id = %s ", $site_id ) );
		
		// download the shipping data 
		$req = new GeteBayDetailsRequestType();
        $req->setDetailName( 'PaymentOptionDetails' );
		
		$res = $this->_cs->GeteBayDetails($req);
				
	}

	function storePaymentDetail( $type, $Detail )
	{
		global $wpdb;

		//#type $Detail PaymentOptionDetailsType
		$data['payment_name']        = $Detail->PaymentOption;
		$data['payment_description'] = $Detail->Description;
		$data['version']             = $Detail->DetailVersion;
		$data['site_id']             = $this->site_id;

		$wpdb->insert($this->tablename, $data);
		WPLE()->logger->info('inserted payment option '.$Detail->PaymentOption);
					
		return true;
	}
	
	
	function downloadMinimumStartPrices( $session, $site_id )
	{
		WPLE()->logger->info( "downloadMinimumStartPrices()" );
		$this->initServiceProxy($session);
		
		// download ebay details 
		$req = new GeteBayDetailsRequestType();
        $req->setDetailName( 'ListingStartPriceDetails' );
		
		$res = $this->_cs->GeteBayDetails($req);

		// handle response and check if successful
		if ( $this->handleResponse($res) ) {

			// save array of minimum start prices
			$price_details = array();
			foreach ($res->ListingStartPriceDetails as $Detail) {
				$price_details[ $Detail->ListingType ] = $Detail->StartPrice->value;
			}
			
			// update site property
			$Site = new WPLE_eBaySite( $site_id );
			$Site->MinListingStartPrices = serialize( $price_details );
			$Site->update();

			// update legacy option
			update_option('wplister_MinListingStartPrices', $price_details);

		} // call successful
				
	} // downloadMinimumStartPrices()
	
	function downloadReturnPolicyDetails( $session, $site_id )
	{
		WPLE()->logger->info( "downloadReturnPolicyDetails()" );
		$this->initServiceProxy($session);
		
		// download ebay details 
		$req = new GeteBayDetailsRequestType();
        $req->setDetailName( 'ReturnPolicyDetails' );
		
		$res = $this->_cs->GeteBayDetails($req);

		// handle response and check if successful
		if ( $this->handleResponse($res) ) {

			// save array of ReturnsWithin options
			$ReturnsWithinOptions = array();
			foreach ($res->ReturnPolicyDetails->ReturnsWithin as $Detail) {
				$ReturnsWithinOptions[ $Detail->ReturnsWithinOption ] = $Detail->Description;
			}
			
			// update legacy option
			update_option('wplister_ReturnsWithinOptions', $ReturnsWithinOptions);

			// save array of ShippingCostPaidBy options
			$ShippingCostPaidByOptions = array();
			foreach ($res->ReturnPolicyDetails->ShippingCostPaidBy as $Detail) {
				$ShippingCostPaidByOptions[ $Detail->ShippingCostPaidByOption ] = $Detail->Description;
			}
			
			// update legacy option
			update_option('wplister_ShippingCostPaidByOptions', $ShippingCostPaidByOptions);

			// update site properties
			$Site = new WPLE_eBaySite( $site_id );
			$Site->ReturnsWithinOptions      = serialize( $ReturnsWithinOptions );
			$Site->ShippingCostPaidByOptions = serialize( $ShippingCostPaidByOptions );
			$Site->update();

		} // call successful
				
	} // downloadReturnPolicyDetails()
	
	
	
	/* the following methods could go into another class, since they use wpdb instead of EbatNs_DatabaseProvider */
	
	static function getAll( $site_id ) {
		global $wpdb;	
		$table = $wpdb->prefix . self::table;
		$profiles = $wpdb->get_results( $wpdb->prepare("
			SELECT * 
			FROM $table
			WHERE site_id = %s
			ORDER BY payment_description
		", $site_id 
		), ARRAY_A);

		return $profiles;		
	}

	function getItem( $id ) {
		global $wpdb;	
		$this->tablename = $wpdb->prefix . self::table;
		$item = $wpdb->get_row( $wpdb->prepare("
			SELECT * 
			FROM $this->tablename
			WHERE payment_name = %s
		", $id 
		), ARRAY_A);		

		return $item;		
	}


	function getTitleByServiceName( $payment_name, $site_id = false ) {
		global $wpdb;	
		$this->tablename = $wpdb->prefix . self::table;

		$where_sql = $site_id ? "AND site_id = '".esc_sql($site_id)."'" : '';

		$payment_description = $wpdb->get_var( $wpdb->prepare("
			SELECT payment_description 
			FROM $this->tablename
			WHERE payment_name = %s
			$where_sql
		", $payment_name ) );		

		if ( ! $payment_description ) return $payment_name;
		return $payment_description;		
	}

	
} // class EbayPaymentModel
