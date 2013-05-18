<?php
/**
 * EbayShippingModel class
 *
 * responsible for managing shipping methods and talking to ebay
 * 
 */

// list of used EbatNs classes:

// require_once 'EbatNs_ServiceProxy.php';

// require_once 'GeteBayDetailsRequestType.php';
// require_once 'ShippingServiceDetailsType.php';	
// require_once 'ShippingLocationDetailsType.php';	
// require_once 'CountryDetailsType.php';	

// require_once 'EbatNs_DatabaseProvider.php';	
// require_once 'EbatNs_Logger.php';

class EbayShippingModel extends WPL_Model {
	const table = 'ebay_shipping';

	var $_session;
	var $_cs;

	function EbayShippingModel()
	{
		global $wpl_logger;
		$this->logger = &$wpl_logger;

		global $wpdb;
		$this->tablename = $wpdb->prefix . self::table;
	}
	

	function downloadShippingDetails($session)
	{
		// eBay motors (100) uses shipping services from ebay.com (1)
		if ( $session->getSiteId() == 100 ) {
	        $session->setSiteId( 1 );
		}

		$this->initServiceProxy($session);
		
		$this->_cs->setHandler('ShippingServiceDetailsType', array(& $this, 'storeShippingDetail'));
		
		// truncate the db
		global $wpdb;
		$wpdb->query('truncate '.$this->tablename);
		
		// download the shipping data 
		$req = new GeteBayDetailsRequestType();
        $req->setDetailName( 'ShippingServiceDetails' );
		
		$res = $this->_cs->GeteBayDetails($req);
				
	}

	function storeShippingDetail($type, & $Detail)
	{
		global $wpdb;

		//#type $Detail ShippingServiceDetailsType
		$data['service_id'] = $Detail->ShippingServiceID;

		#$data['carrier'] = $Detail->ShippingCarrier[0];
		if ( is_array( $Detail->ShippingCarrier ) )
			$data['carrier'] = $Detail->ShippingCarrier[0];
		else
			$data['carrier'] = '';
		
		$data['service_name']        = $Detail->ShippingService;
		$data['service_description'] = $Detail->Description;
		$data['international']       = $Detail->InternationalService;
		$data['version']             = $Detail->DetailVersion;	

		$data['ShippingCategory']    = $Detail->ShippingCategory;
		$data['DimensionsRequired']  = $Detail->DimensionsRequired ? 1 : 0;
		$data['WeightRequired']      = $Detail->WeightRequired ? 1 : 0;

		// ShippingServices can have multiple ServiceTypes
		foreach ($Detail->ServiceType as $ServiceType) {
			if ( $ServiceType == 'Flat') 		$data['isFlat'] = 1;
			if ( $ServiceType == 'Calculated') 	$data['isCalculated'] = 1;
		}
		
		// only save valid shipping services to db
		if ( $Detail->ValidForSellingFlow == 1) {
			$wpdb->insert($this->tablename, $data);
			$this->logger->info('inserted shipping service '.$Detail->ShippingService);
		}
					
		return true;
	}

	function downloadShippingLocations($session, $siteid = 77)
	{
		$this->initServiceProxy($session);
		
		// download the shipping locations 
		$req = new GeteBayDetailsRequestType();
        $req->setDetailName( 'ShippingLocationDetails' );
		
		$res = $this->_cs->GeteBayDetails($req);

		// save $locations as serialized array
		foreach ($res->ShippingLocationDetails as $Location) {
			$locations[$Location->ShippingLocation] = $Location->Description;
		}
		update_option( 'wplister_ShippingLocationDetails', serialize($locations) );
		
	}

	function downloadCountryDetails($session, $siteid = 77)
	{
		$this->initServiceProxy($session);
		
		// download the shipping locations 
		$req = new GeteBayDetailsRequestType();
        $req->setDetailName( 'CountryDetails' );
		
		$res = $this->_cs->GeteBayDetails($req);

		// save $countries as serialized array
		foreach ($res->CountryDetails as $Country) {
			$countries[$Country->Country] = $Country->Description;
		}
		update_option( 'wplister_CountryDetails', serialize($countries) );
		
	}





	function downloadDispatchTimes($session)
	{
		$this->logger->info( "downloadDispatchTimes()" );
		$this->initServiceProxy($session);
		
		// download ebay details 
		$req = new GeteBayDetailsRequestType();
        $req->setDetailName( 'DispatchTimeMaxDetails' );
		
		$res = $this->_cs->GeteBayDetails($req);

		// handle response and check if successful
		if ( $this->handleResponse($res) ) {

			// save array of allowed dispatch times
			$dispatch_times = array();
			foreach ($res->DispatchTimeMaxDetails as $Detail) {
				$dispatch_times[ $Detail->DispatchTimeMax ] = $Detail->Description;
			}
			
			// update_option('wplister_dispatch_times_available', $dispatch_times);
			update_option('wplister_DispatchTimeMaxDetails', $dispatch_times);

		} // call successful
				
	}
	
	function downloadShippingPackages($session)
	{
		$this->logger->info( "downloadShippingPackages()" );
		$this->initServiceProxy($session);
		
		// download ebay details 
		$req = new GeteBayDetailsRequestType();
        $req->setDetailName( 'ShippingPackageDetails' );
		
		$res = $this->_cs->GeteBayDetails($req);

		// handle response and check if successful
		if ( $this->handleResponse($res) ) {

			// save array of allowed shipping packages
			$shipping_packages = array();
			foreach ($res->ShippingPackageDetails as $Detail) {
				$package = new stdClass();
				$package->ShippingPackage     = $Detail->ShippingPackage;
				$package->Description         = $Detail->Description;
				$package->PackageID           = $Detail->PackageID;
				$package->DefaultValue        = $Detail->DefaultValue;
				$package->DimensionsSupported = $Detail->DimensionsSupported;
				$shipping_packages[ $Detail->PackageID ] = $package;
			}
			
			update_option('wplister_ShippingPackageDetails', $shipping_packages);

		} // call successful
				
	}
	
	


	
	/* the following methods could go into another class, since they use wpdb instead of EbatNs_DatabaseProvider */
	
	function getAll() {
		global $wpdb;	
		$this->tablename = $wpdb->prefix . self::table;
		$services = $wpdb->get_results("
			SELECT * 
			FROM $this->tablename
			WHERE isFlat = 1
			ORDER BY ShippingCategory, service_description
		", ARRAY_A);		

		$services = self::fixShippingCategory( $services );
		return $services;		
	}
	function getAllLocal( $type = 'flat' ) {
		global $wpdb;	
		$this->tablename = $wpdb->prefix . self::table;

		// either find only flat or only calculated services
		$type_sql = $type == 'flat' ? 'isFlat = 1' : 'isCalculated = 1';

		$services = $wpdb->get_results("
			SELECT * 
			FROM $this->tablename
			WHERE international = 0
			  AND $type_sql
			ORDER BY ShippingCategory, service_description
		", ARRAY_A);		

		$services = self::fixShippingCategory( $services );
		return $services;		
	}
	function getAllInternational( $type = 'flat' ) {
		global $wpdb;	
		$this->tablename = $wpdb->prefix . self::table;

		// either find only flat or only calculated services
		$type_sql = $type == 'flat' ? 'isFlat = 1' : 'isCalculated = 1';

		$services = $wpdb->get_results("
			SELECT * 
			FROM $this->tablename
			WHERE international = 1
			  AND $type_sql
			ORDER BY ShippingCategory, service_description
		", ARRAY_A);		

		$services = self::fixShippingCategory( $services );
		return $services;		
	}
	function getShippingCategoryByServiceName( $service_name ) {
		global $wpdb;	
		$this->tablename = $wpdb->prefix . self::table;

		$ShippingCategory = $wpdb->get_var("
			SELECT ShippingCategory 
			FROM $this->tablename
			WHERE service_name = '$service_name'
		");		

		return $ShippingCategory;		
	}

	function getTitleByServiceName( $service_name ) {
		global $wpdb;	
		$this->tablename = $wpdb->prefix . self::table;

		$service_description = $wpdb->get_var("
			SELECT service_description 
			FROM $this->tablename
			WHERE service_name = '$service_name'
		");		

		if ( ! $service_description ) return $service_name;
		return $service_description;		
	}

	function getItem( $id ) {
		global $wpdb;	
		$this->tablename = $wpdb->prefix . self::table;
		$item = $wpdb->get_row("
			SELECT * 
			FROM $this->tablename
			WHERE service_id = '$id'
		", ARRAY_A);		

		return $item;		
	}

	function getShippingLocations() {
		$locations = unserialize( get_option( 'wplister_ShippingLocationDetails' ) );
		// $this->logger->info('wplister_ShippingLocationDetails'.print_r($locations,1));
		return $locations;
	}
	function getEbayCountries() {
		$countries = unserialize( get_option( 'wplister_CountryDetails' ) );
		// $this->logger->info('wplister_CountryDetails'.print_r($countries,1));
		asort($countries);
		return $countries;
	}

	function fixShippingCategory( $services ) {
		foreach ($services as &$service) {

			switch ( $service['ShippingCategory'] ) {
				case 'ECONOMY':
					$service['ShippingCategory'] = __('Economy services','wplister');
					break;
				
				case 'STANDARD':
					$service['ShippingCategory'] = __('Standard services','wplister');
					break;
				
				case 'EXPEDITED':
					$service['ShippingCategory'] = __('Expedited services','wplister');
					break;
				
				case 'ONE_DAY':
					$service['ShippingCategory'] = __('One-day services','wplister');
					break;
				
				case 'PICKUP':
					$service['ShippingCategory'] = __('Pickup services','wplister');
					break;
				
				case 'OTHER':
					$service['ShippingCategory'] = __('Other services','wplister');
					break;
				
				case 'NONE':
					$service['ShippingCategory'] = __('International services','wplister');
					break;
				
				default:
					# do nothing
					break;
			}

		}
		return $services;
	}

}
