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

// require_once 'EbatNs_DatabaseProvider.php';	
// require_once 'EbatNs_Logger.php';

class EbayPaymentModel extends WPL_Model {
	const table = 'ebay_payment';

	var $_session;
	var $_cs;

	function EbayPaymentModel()
	{
		global $wpl_logger;
		$this->logger = &$wpl_logger;
	
		global $wpdb;
		$this->tablename = $wpdb->prefix . self::table;
	}
	
	function downloadPaymentDetails($session, $siteid = 77)
	{
		$this->initServiceProxy($session);
		
		$this->_cs->setHandler('PaymentOptionDetailsType', array(& $this, 'storePaymentDetail'));
		
		// truncate the db
		global $wpdb;
		$wpdb->query('truncate '.$this->tablename);
		
		// download the shipping data 
		$req = new GeteBayDetailsRequestType();
        $req->setDetailName( 'PaymentOptionDetails' );
		
		$res = $this->_cs->GeteBayDetails($req);
				
	}

	function storePaymentDetail($type, & $Detail)
	{
		global $wpdb;

		//#type $Detail PaymentOptionDetailsType
		$data['payment_name'] = $Detail->PaymentOption;
		$data['payment_description'] = $Detail->Description;
		$data['version'] = $Detail->DetailVersion;

		$wpdb->insert($this->tablename, $data);
		$this->logger->info('inserted payment option '.$Detail->PaymentOption);
					
		return true;
	}
	
	
	function downloadMinimumStartPrices($session)
	{
		$this->logger->info( "downloadMinimumStartPrices()" );
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
			
			update_option('wplister_MinListingStartPrices', $price_details);

		} // call successful
				
	}
	
	
	
	/* the following methods could go into another class, since they use wpdb instead of EbatNs_DatabaseProvider */
	
	function getAll() {
		global $wpdb;	
		$this->tablename = $wpdb->prefix . self::table;
		$profiles = $wpdb->get_results("
			SELECT * 
			FROM $this->tablename
			ORDER BY payment_description
		", ARRAY_A);

		return $profiles;		
	}

	function getItem( $id ) {
		global $wpdb;	
		$this->tablename = $wpdb->prefix . self::table;
		$item = $wpdb->get_row("
			SELECT * 
			FROM $this->tablename
			WHERE payment_name = '$id'
		", ARRAY_A);		

		return $item;		
	}


	function getTitleByServiceName( $payment_name ) {
		global $wpdb;	
		$this->tablename = $wpdb->prefix . self::table;

		$payment_description = $wpdb->get_var("
			SELECT payment_description 
			FROM $this->tablename
			WHERE payment_name = '$payment_name'
		");		

		if ( ! $payment_description ) return $payment_name;
		return $payment_description;		
	}

	
	
}
