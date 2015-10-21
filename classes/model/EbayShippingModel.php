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
// require_once 'EbatNs_Logger.php';

class EbayShippingModel extends WPL_Model {
	const table = 'ebay_shipping';

	var $_session;
	var $_cs;
	var $site_id;

	function EbayShippingModel()
	{
		// global $wpl_logger;
		// $this->logger = &$wpl_logger;

		global $wpdb;
		$this->tablename = $wpdb->prefix . self::table;
	}
	

	function downloadShippingDetails( $session, $site_id )
	{
		// eBay motors (100) uses shipping services from ebay.com (1)
		if ( $session->getSiteId() == 100 ) {
	        $session->setSiteId( 1 );
		}

		$this->initServiceProxy($session);
		$this->site_id = $site_id;
		
		$this->_cs->setHandler('ShippingServiceDetailsType', array(& $this, 'storeShippingDetail'));
		
		// truncate the db
		global $wpdb;
		$wpdb->query( $wpdb->prepare("DELETE FROM {$this->tablename} WHERE site_id = %s ", $site_id ) );
		
		// download the shipping data 
		$req = new GeteBayDetailsRequestType();
        $req->setDetailName( 'ShippingServiceDetails' );
		
		$res = $this->_cs->GeteBayDetails($req);
				
	}

	function storeShippingDetail( $type, $Detail )
	{
		global $wpdb;

		//#type $Detail ShippingServiceDetailsType
		$data['service_id'] = $Detail->ShippingServiceID;
		$data['site_id']    = $this->site_id;

		#$data['carrier'] = $Detail->ShippingCarrier[0];
		if ( is_array( $Detail->ShippingCarrier ) )
			$data['carrier'] = $Detail->ShippingCarrier[0];
		else
			$data['carrier'] = '';
		
		$data['service_name']        = $Detail->ShippingService;
		$data['service_description'] = $Detail->Description;
		$data['international']       = $Detail->InternationalService ? 1 : 0;
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
			WPLE()->logger->info('inserted shipping service '.$Detail->ShippingService);
		}
					
		return true;
	}

	function downloadShippingLocations($session, $site_id )
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

		// update site property
		$Site = new WPLE_eBaySite( $site_id );
		$Site->ShippingLocationDetails = serialize( $locations );
		$Site->update();

		// update legacy option
		update_option( 'wplister_ShippingLocationDetails', serialize($locations) );
		
	}

	function downloadExcludeShippingLocations($session, $site_id )
	{
		$this->initServiceProxy($session);
		
		// download the list of exludeable shipping locations 
		$req = new GeteBayDetailsRequestType();
        $req->setDetailName( 'ExcludeShippingLocationDetails' );
		
		$res = $this->_cs->GeteBayDetails($req);

		// save $locations as serialized array
		foreach ($res->ExcludeShippingLocationDetails as $Location) {
			$locations[$Location->Location] = $Location->Description;
		}

		// update site property
		$Site = new WPLE_eBaySite( $site_id );
		$Site->ExcludeShippingLocationDetails = serialize( $locations );
		$Site->update();

		// update legacy option
		update_option( 'wplister_ExcludeShippingLocationDetails', serialize($locations) );
		
	}

	function downloadCountryDetails($session, $site_id )
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

		// update site property
		$Site = new WPLE_eBaySite( $site_id );
		$Site->CountryDetails = serialize( $countries );
		$Site->update();

		// update legacy option
		update_option( 'wplister_CountryDetails', serialize($countries) );
		
	}





	function downloadDispatchTimes( $session, $site_id )
	{
		WPLE()->logger->info( "downloadDispatchTimes()" );
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
			
			// update site property
			$Site = new WPLE_eBaySite( $site_id );
			$Site->DispatchTimeMaxDetails = serialize( $dispatch_times );
			$Site->update();

			// update legacy option
			update_option('wplister_DispatchTimeMaxDetails', $dispatch_times );

		} // call successful
				
	}
	
	function downloadShippingPackages( $session, $site_id )
	{
		WPLE()->logger->info( "downloadShippingPackages()" );
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
			
			// update site property
			$Site = new WPLE_eBaySite( $site_id );
			$Site->ShippingPackageDetails = serialize( $shipping_packages );
			$Site->update();

			// update legacy option
			update_option('wplister_ShippingPackageDetails', $shipping_packages);

		} // call successful
				
	}
	
	
	
	function downloadShippingDiscountProfiles($session)
	{
		WPLE()->logger->info( "downloadShippingDiscountProfiles()" );
		$this->initServiceProxy($session);
		
		// download shipping discount profiles 
		$req = new GetShippingDiscountProfilesRequestType();
		
		$res = $this->_cs->GetShippingDiscountProfiles($req);

		// handle response and check if successful
		if ( $this->handleResponse($res) ) {

			// save array of discount profiles 
			$shipping_discount_profiles = array();
			// echo "<pre>";print_r($res);echo"</pre>";#die();

			// FlatShippingDiscount
			foreach ($res->FlatShippingDiscount->DiscountProfile as $Detail) {
				$profile = new stdClass();
				$profile->DiscountProfileID        = $Detail->DiscountProfileID;
				$profile->DiscountProfileName      = $Detail->DiscountProfileName != '' ? $Detail->DiscountProfileName : 'default';
				$profile->EachAdditionalAmount     = $Detail->EachAdditionalAmount->value;
				$profile->EachAdditionalAmountOff  = $Detail->EachAdditionalAmountOff->value;
				$profile->EachAdditionalPercentOff = $Detail->EachAdditionalPercentOff->value;
				$shipping_discount_profiles['FlatShippingDiscount'][ $Detail->DiscountProfileID ] = $profile;
			}

			// CalculatedShippingDiscount
			foreach ($res->CalculatedShippingDiscount->DiscountProfile as $Detail) {
				$profile = new stdClass();
				$profile->DiscountProfileID        = $Detail->DiscountProfileID;
				$profile->DiscountProfileName      = $Detail->DiscountProfileName != '' ? $Detail->DiscountProfileName : 'default';
				$profile->EachAdditionalAmount     = $Detail->EachAdditionalAmount->value;
				$profile->EachAdditionalAmountOff  = $Detail->EachAdditionalAmountOff->value;
				$profile->EachAdditionalPercentOff = $Detail->EachAdditionalPercentOff->value;
				$shipping_discount_profiles['CalculatedShippingDiscount'][ $Detail->DiscountProfileID ] = $profile;
			}			
			
			update_option('wplister_ShippingDiscountProfiles', $shipping_discount_profiles);

		} // call successful
				
	}
	
	// this should go into another class eventually
	function fetchDoesNotApplyText( $session, $site_id )
	{
		WPLE()->logger->info( "fetchDoesNotApplyText()" );
		$this->initServiceProxy($session);
		
		// download ebay details 
		$req = new GeteBayDetailsRequestType();
        $req->setDetailName( 'ProductDetails' );
		
		$res = $this->_cs->GeteBayDetails($req);

		// handle response and check if successful
		if ( $this->handleResponse($res) ) {

			// get text - default is 'Does not apply'
			$DoesNotApplyText = $res->ProductDetails->ProductIdentifierUnavailableText;
			
			// update site property
			$Site = new WPLE_eBaySite( $site_id );
			$Site->DoesNotApplyText = $DoesNotApplyText;
			$Site->update();

		} // call successful
				
	} // fetchDoesNotApplyText()
	
	


	
	/* the following methods could go into another class, since they use wpdb instead of EbatNs_DatabaseProvider */
	
	function getAll( $site_id ) {
		global $wpdb;	
		$this->tablename = $wpdb->prefix . self::table;
		$services = $wpdb->get_results( $wpdb->prepare("
			SELECT * 
			FROM $this->tablename
			WHERE isFlat  = 1
			  AND site_id = %s
			ORDER BY ShippingCategory, service_description
		", $site_id 
		), ARRAY_A );

		$services = self::fixShippingCategory( $services );
		return $services;		
	}
	static function getAllLocal( $site_id, $type = 'flat' ) {
		global $wpdb;	
		$table = $wpdb->prefix . self::table;

		// either find only flat or only calculated services
		$type_sql = $type == 'flat' ? 'isFlat = 1' : 'isCalculated = 1';

		$services = $wpdb->get_results( $wpdb->prepare("
			SELECT * 
			FROM $table
			WHERE international = 0
			  AND site_id       = %s
			  AND $type_sql
			ORDER BY ShippingCategory, service_description
		", $site_id 
		), ARRAY_A );

		$services = self::fixShippingCategory( $services );
		return $services;		
	}
	static function getAllInternational( $site_id, $type = 'flat' ) {
		global $wpdb;	
		$table = $wpdb->prefix . self::table;

		// either find only flat or only calculated services
		$type_sql = $type == 'flat' ? 'isFlat = 1' : 'isCalculated = 1';

		$services = $wpdb->get_results( $wpdb->prepare("
			SELECT * 
			FROM $table
			WHERE international = 1
			  AND site_id       = %s
			  AND $type_sql
			ORDER BY ShippingCategory, service_description
		", $site_id 
		), ARRAY_A );

		$services = self::fixShippingCategory( $services );
		return $services;		
	}
	function getShippingCategoryByServiceName( $service_name ) {
		global $wpdb;	
		$this->tablename = $wpdb->prefix . self::table;

		$ShippingCategory = $wpdb->get_var( $wpdb->prepare("
			SELECT ShippingCategory 
			FROM $this->tablename
			WHERE service_name = %s
		", $service_name ) );

		return $ShippingCategory;		
	}

	function getTitleByServiceName( $service_name ) {
		global $wpdb;	
		$this->tablename = $wpdb->prefix . self::table;

		$service_description = $wpdb->get_var( $wpdb->prepare("
			SELECT service_description 
			FROM $this->tablename
			WHERE service_name = %s
		", $service_name ) );		

		if ( ! $service_description ) return $service_name;
		return $service_description;		
	}

	function getItem( $id ) {
		global $wpdb;	
		$this->tablename = $wpdb->prefix . self::table;
		$item = $wpdb->get_row( $wpdb->prepare("
			SELECT * 
			FROM $this->tablename
			WHERE service_id = %s
		", $id 
		), ARRAY_A );

		return $item;		
	}

	static function getShippingLocations( $site_id ) {
		// $locations = maybe_unserialize( get_option( 'wplister_ShippingLocationDetails' ) );
		// WPLE()->logger->info('wplister_ShippingLocationDetails'.print_r($locations,1));

		$locations = maybe_unserialize( WPLE_eBaySite::getSiteObj( $site_id )->ShippingLocationDetails );

		if ( ! is_array($locations) ) return array();
		return $locations;
	}
	static function getExcludeShippingLocations( $site_id ) {
		// $locations = maybe_unserialize( get_option( 'wplister_ExcludeShippingLocationDetails' ) );
		// WPLE()->logger->info('wplister_ExcludeShippingLocationDetails'.print_r($locations,1));

		$locations = maybe_unserialize( WPLE_eBaySite::getSiteObj( $site_id )->ExcludeShippingLocationDetails );

		if ( ! is_array($locations) ) return array();

		// add NONE value to remove all previously sent locations
		$locations['NONE'] = 'NONE';
		
		return $locations;
	}
	static function getEbayCountries( $site_id ) {
		// $countries = maybe_unserialize( get_option( 'wplister_CountryDetails' ) );
		// WPLE()->logger->info('wplister_CountryDetails'.print_r($countries,1));

		$countries = maybe_unserialize( WPLE_eBaySite::getSiteObj( $site_id )->CountryDetails );

		if ( ! is_array($countries) ) return array();
		asort($countries);
		return $countries;
	}

	static function fixShippingCategory( $services ) {
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
				
				case 'EBAY_SHIPPING':
					$service['ShippingCategory'] = __('eBay shipping services','wplister');
					break;
				
				default:
					# do nothing
					break;
			}

		}
		return $services;
	} // fixShippingCategory()

	static function sortSellerProfiles( $profiles ) {
		usort( $profiles, array( 'EbayShippingModel', 'cmpSellerProfiles' ) );
		return $profiles;		
	}
	static function cmpSellerProfiles( $a, $b ) {
	    return strcmp( $a->ProfileName, $b->ProfileName );
	}

} // class EbayShippingModel
