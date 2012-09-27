<?php
/**
 * ListingsModel class
 *
 * responsible for managing listings and talking to ebay
 * 
 */

// list of used EbatNs classes:

// EbatNs_ServiceProxy.php
// EbatNs_DatabaseProvider.php
// EbatNs_Logger.php

// GetMyeBaySellingRequestType.php
// ItemType.php
// VariationType.php
// VariationsType.php
// NameValueListArrayType.php
// NameValueListType.php
// PicturesType.php
// VariationSpecificPictureSetType.php

// VerifyAddItemRequestType.php
// VerifyAddItemResponseType.php
// AddItemRequestType.php
// AddItemResponseType.php
// EndItemRequestType.php
// EndItemResponseType.php
// ReviseItemRequestType.php
// ReviseItemResponseType.php

// VerifyAddFixedPriceItemRequestType.php
// VerifyAddFixedPriceItemResponseType.php
// AddFixedPriceItemRequestType.php
// AddFixedPriceItemResponseType.php

// GetItemRequestType.php
// GetItemResponseType.php

// GetSellerTransactionsRequestType.php
// GetSellerTransactionsResponseType.php


class ListingsModel extends WPL_Model {

	var $_session;
	var $_cs;

	var $variationAttributes = array();

	function ListingsModel()
	{
		global $wpl_logger;
		$this->logger = &$wpl_logger;

		global $wpdb;
		$this->tablename = $wpdb->prefix . 'ebay_auctions';
	}


	function downloadListingDetails($session, $siteid = 77)
	{
		$this->initServiceProxy($session);

		$this->_cs->setHandler('ItemType', array(& $this, 'storeItemDetail'));

		// download the data
		$req = new GetMyeBaySellingRequestType();
		$req->setActiveList( true );

		$res = $this->_cs->GetMyeBaySelling($req);
	}

	function storeItemDetail($type, & $Detail)
	{
		global $wpdb;
		//#type $Detail ItemType
		
		// map ItemType to DB columns
		$data = $this->mapItemDetailToDB( $Detail );

		$wpdb->insert( $this->tablename, $data );
		$data['status'] = 'imported';

		return true;
	}



	function getPageItems( $current_page, $per_page ) {
		global $wpdb;

        $orderby = (!empty($_REQUEST['orderby'])) ? $_REQUEST['orderby'] : 'id'; //If no sort, default to title
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

		return $items;
	}



	/* the following methods could go into another class, since they use wpdb instead of EbatNs_DatabaseProvider */

	function getAll() {
		global $wpdb;
		$items = $wpdb->get_results("
			SELECT *
			FROM $this->tablename
			ORDER BY id DESC
		", ARRAY_A);

		return $items;
	}

	function getItem( $id ) {
		global $wpdb;
		$item = $wpdb->get_row("
			SELECT *
			FROM $this->tablename
			WHERE id = '$id'
		", ARRAY_A);

		$item['profile_data'] = $this->decodeObject( $item['profile_data'], true );
		// $item['details'] = $this->decodeObject( $item['details'] );

		return $item;
	}

	function deleteItem( $id ) {
		global $wpdb;
		$wpdb->query("
			DELETE
			FROM $this->tablename
			WHERE id = '$id'
		");
	}

	function getItemByEbayID( $id ) {
		global $wpdb;
		$item = $wpdb->get_row("
			SELECT *
			FROM $this->tablename
			WHERE ebay_id = '$id'
		");
		if (!$item) return false;
		
		$item->profile_data = $this->decodeObject( $item->profile_data, true );
		$item->details = $this->decodeObject( $item->details );

		return $item;
	}

	function getTitleFromItemID( $id ) {
		global $wpdb;
		$item = $wpdb->get_var("
			SELECT auction_title
			FROM $this->tablename
			WHERE ebay_id = '$id'
		");
		return $item;
	}

	function getEbayIDFromID( $id ) {
		global $wpdb;
		$item = $wpdb->get_var("
			SELECT ebay_id
			FROM $this->tablename
			WHERE id = '$id'
		");
		return $item;
	}
	function getEbayIDFromPostID( $post_id ) {
		global $wpdb;
		$item = $wpdb->get_var("
			SELECT ebay_id
			FROM $this->tablename
			WHERE post_id = '$post_id'
		");
		return $item;
	}
	function getStatus( $id ) {
		global $wpdb;
		$item = $wpdb->get_var("
			SELECT status
			FROM $this->tablename
			WHERE id = '$id'
		");
		return $item;
	}
	function getStatusFromPostID( $post_id ) {
		global $wpdb;
		$item = $wpdb->get_var("
			SELECT status
			FROM $this->tablename
			WHERE post_id = '$post_id'
		");
		return $item;
	}
	function getViewItemURLFromPostID( $post_id ) {
		global $wpdb;
		$item = $wpdb->get_var("
			SELECT ViewItemURL
			FROM $this->tablename
			WHERE post_id = '$post_id'
		");
		return $item;
	}


	function uploadPictureToEPS( $url, $session ) {

		// preparation - set up new ServiceProxy with given session
		$this->initServiceProxy($session);

		// preprocess url
		$url = str_replace(' ', '%20', $url );

		$req = new UploadSiteHostedPicturesRequestType();
        $req->setExternalPictureURL( $url );

		# http://www.intradesys.com/de/forum/1496       
		// $req = new UploadSiteHostedPicturesRequestType();
		// $req->setPictureSet( 'Standard' );
		// $req->setPictureName( 'MyPic' );
		// $req->setPictureData(file_get_contents($url));

		$this->logger->info( "calling UploadSiteHostedPictures - $url " );
		$this->logger->debug( "Request: ".print_r($req,1) );
		// $res = $this->_cs->UploadSiteHostedPictures($req); 
		$res = $this->callUploadSiteHostedPictures($req, $session ); 
		$this->logger->info( "UploadSiteHostedPictures Complete" );
		$this->logger->info( "Response: ".print_r($res,1) );

		// handle response and check if successful
		if ( $this->handleResponse($res) ) {

			// fetch final url
			$eps_url = $res->SiteHostedPictureDetails->FullURL;
			
			$this->logger->info( "image was uploaded to EPS successfully. " );

			return $eps_url;

		} // call successful

		return false;

	}


	function callUploadSiteHostedPictures( $request, $session, $parseMode = EBATNS_PARSEMODE_CALL )
	{

		$this->_session = $session;
		// $this->_session->ReadTokenFile();
		$userToken = $this->_session->getRequestToken();
		$version = $this->_cs->getVersion();
		$ExternalPictureURL = $request->getExternalPictureURL();

	    ///Build the request XML request which is first part of multi-part POST
	    $xmlReq = '<?xml version="1.0" encoding="utf-8"?>' . "\n";
	    $xmlReq .= '<UploadSiteHostedPicturesRequest xmlns="urn:ebay:apis:eBLBaseComponents">' . "\n";
	    $xmlReq .= "<Version>$version</Version>\n";
	    $xmlReq .= "<ExternalPictureURL>$ExternalPictureURL</ExternalPictureURL>\n";    
	    $xmlReq .= "<RequesterCredentials><eBayAuthToken>$userToken</eBayAuthToken></RequesterCredentials>\n";
	    $xmlReq .= '</UploadSiteHostedPicturesRequest>';

		// place all data into theirs header
		$reqHeaders[] = 'X-EBAY-API-COMPATIBILITY-LEVEL: ' . $version;
		$reqHeaders[] = 'X-EBAY-API-DEV-NAME: ' . $this->_session->getDevId();
		$reqHeaders[] = 'X-EBAY-API-APP-NAME: ' . $this->_session->getAppId();
		$reqHeaders[] = 'X-EBAY-API-CERT-NAME: ' . $this->_session->getCertId();
		$reqHeaders[] = 'X-EBAY-API-CALL-NAME: ' . 'UploadSiteHostedPictures';
		$reqHeaders[] = 'X-EBAY-API-SITEID: ' . $this->_session->getSiteId();		
		$multiPartData = null;

		// echo "<pre>";print_r($request);#die();		
		// $body = $this->encodeMessageXmlStyle( $method, $request );
		// echo "<pre>";echo htmlspecialchars($body);die();				

		// $message = '<?xml version="1.0" encoding="utf-8"?---*-->' . "\n";
		// $message .= $body;
		$message = $xmlReq;
		
		// we support only Sandbox and Production here !
		if ($this->_session->getAppMode() == 1)
			$this->_ep = "https://api.sandbox.ebay.com/ws/api.dll";
		else
			$this->_ep = 'https://api.ebay.com/ws/api.dll';
		$this->_ep .= '?callname=' . 'UploadSiteHostedPictures';
		$this->_ep .= '&version=' . $version;

		// echo "<pre>";echo htmlspecialchars($message);die();		
				
		// $responseMsg = $this->_cs->sendMessageXmlStyle( $message, $reqHeaders, $multiPartData );
		$responseMsg = $this->sendMessageXmlStyle( $message, $reqHeaders, $multiPartData );
		// echo "<pre>";print_r($responseMsg);#die();				

		if ( $responseMsg )	{

			// $this->_cs->_startTp('Decoding SOAP Message');
			$ret = & $this->_cs->decodeMessage( 'UploadSiteHostedPictures', $responseMsg, $parseMode );
			// $this->_cs->_stopTp('Decoding SOAP Message');

		} else {
			$ret = & $this->_currentResult;
		}
		
		return $ret;
	}
	

	// sendMessage in XmlStyle,
	// the only difference is the extra headers we use here
	function sendMessageXmlStyle( $message, $extraXmlHeaders, $multiPartImageData = null )
	{
		$this->_currentResult = null;
		$this->_cs->log( $this->_ep, 'RequestUrl' );
		$this->_cs->logXml( $message, 'Request' );
		
		// $timeout = $this->_cs->_transportOptions['HTTP_TIMEOUT'];
		// if (!$timeout || $timeout <= 0)
		// 	$timeout = 300;
		$timeout = 30;

		
		$ch = curl_init();
		
		if ($multiPartImageData !== null)
		{
			$boundary = "MIME_boundary";
			
			$CRLF = "\r\n";
			
			$mp_message .= "--" . $boundary . $CRLF;
			$mp_message .= 'Content-Disposition: form-data; name="XML Payload"' . $CRLF;
			$mp_message .= 'Content-Type: text/xml;charset=utf-8' . $CRLF . $CRLF;
			$mp_message .= $message;
			$mp_message .= $CRLF;
			
			$mp_message .= "--" . $boundary . $CRLF;
			$mp_message .= 'Content-Disposition: form-data; name="dumy"; filename="dummy"' . $CRLF;
			$mp_message .= "Content-Transfer-Encoding: binary" . $CRLF;
			$mp_message .= "Content-Type: application/octet-stream" . $CRLF . $CRLF;
			$mp_message .= $multiPartImageData;
			
			$mp_message .= $CRLF;
			$mp_message .= "--" . $boundary . "--" . $CRLF;
			
			$message = $mp_message;
			
			$reqHeaders[] = 'Content-Type: multipart/form-data; boundary=' . $boundary;
			$reqHeaders[] = 'Content-Length: ' . strlen($message);
		}
		else
		{
			$reqHeaders[] = 'Content-Type: text/xml;charset=utf-8';
		}
		
		
		// if ($this->_cs->_transportOptions['HTTP_COMPRESS'])
		// {
		// 	$reqHeaders[] = 'Accept-Encoding: gzip, deflate';
		// 	curl_setopt( $ch, CURLOPT_ENCODING, "gzip");
		// 	curl_setopt( $ch, CURLOPT_ENCODING, "deflate");
		// }
		
		if (is_array($extraXmlHeaders))
			$reqHeaders = array_merge((array)$reqHeaders, $extraXmlHeaders);
		
		curl_setopt( $ch, CURLOPT_URL, $this->_ep );
		
		curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, 0);
		
		curl_setopt( $ch, CURLOPT_HTTPHEADER, $reqHeaders );
		curl_setopt( $ch, CURLOPT_USERAGENT, 'ebatns;xmlstyle;1.0' );
		curl_setopt( $ch, CURLOPT_TIMEOUT, $timeout );
		
		curl_setopt( $ch, CURLOPT_POST, 1 );
		curl_setopt( $ch, CURLOPT_POSTFIELDS, $message );
		
		curl_setopt( $ch, CURLOPT_FAILONERROR, 0 );
		curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, 1 );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
		curl_setopt( $ch, CURLOPT_HEADER, 1 );
		curl_setopt( $ch, CURLOPT_HTTP_VERSION, 1 );
		
		// added support for multi-threaded clients
		// if (isset($this->_cs->_transportOptions['HTTP_CURL_MULTITHREADED']))
		// {
		// 	curl_setopt( $ch, CURLOPT_DNS_USE_GLOBAL_CACHE, 0 );
		// }


		$responseRaw = curl_exec( $ch );
		// echo"<pre>";print_r($responseRaw);#die();
		if ( !$responseRaw )
		{
			$this->_currentResult = new EbatNs_ResponseError();
			$this->_currentResult->raise( 'curl_error ' . curl_errno( $ch ) . ' ' . curl_error( $ch ), 80000 + 1, EBAT_SEVERITY_ERROR );
			curl_close( $ch );
			
			return null;
		} 
		else
		{
			curl_close( $ch );
			
			$responseRaw = str_replace
			(
				array
				(
					"HTTP/1.1 100 Continue\r\n\r\nHTTP/1.1 200 OK\r\n",
					"HTTP/1.1 100 Continue\n\nHTTP/1.1 200 OK\n"
				),
				array
				(
					"HTTP/1.1 200 OK\r\n",
					"HTTP/1.1 200 OK\n"
				),
				$responseRaw
			);

			$responseBody = null;
			if ( preg_match( "/^(.*?)\r?\n\r?\n(.*)/s", $responseRaw, $match ) )
			{
				$responseBody = $match[2];
				$headerLines = split( "\r?\n", $match[1] );
				foreach ( $headerLines as $line )
				{
					if ( strpos( $line, ':' ) === false )
					{
						$responseHeaders[0] = $line;
						continue;
					} 
					list( $key, $value ) = split( ':', $line );
					$responseHeaders[strtolower( $key )] = trim( $value );
				} 
			} 
			
			if ($responseBody)
				$this->_cs->logXml( $responseBody, 'Response' );
			else
				$this->_cs->logXml( $responseRaw, 'ResponseRaw' );
		} 
		
		return $responseBody;
	} 
	
	function buildItem( $id, $session, $isFixedPriceItem = false, $reviseItem = false )
	{

		// fetch record from db
		$p = $this->getItem( $id );
		$profile_details = $p['profile_data']['details'];
		$images = $this->getProductImagesURL( $p['post_id'] );
		$main_image = $this->getProductMainImageURL( $p['post_id'] );
		$product_sku = ProductWrapper::getSKU( $p['post_id'] );
		if ( trim($product_sku) == '' ) $product_sku = false;
		$hasVariations = ProductWrapper::hasVariations( $p['post_id'] );

		// price has been calculated when applying the profile
		$start_price  = $p['price'];


		// build item
		$item = new ItemType();

		// Set Listing Properties
		$item->ListingDuration = $p['listing_duration'];
		$item->Quantity = $p['quantity'];

		
		// omit ListingType when revising item
		if ( ! $reviseItem ) {
			$item->ListingType = $p['auction_type'];
		}

		// Set the Listing Starting Price and Buy It Now Price
		$item->StartPrice = new AmountType();
		$item->StartPrice->setTypeValue( $start_price );
		$item->StartPrice->setTypeAttribute('currencyID', $profile_details['currency'] );

		// optional BuyItNow price
		if ( intval($profile_details['fixed_price']) != 0) {
			$buynow_price = $this->applyProfilePrice( $p['price'], $profile_details['fixed_price'] );
			$item->BuyItNowPrice = new AmountType();
			$item->BuyItNowPrice->setTypeValue( $buynow_price );
			$item->BuyItNowPrice->setTypeAttribute('currencyID', $profile_details['currency'] );
		}

		// Set the Item Title
		$item->Title = $this->prepareTitle( $p['auction_title'] );

		// SKU - omit if empty
		if ($product_sku) $item->SKU = $product_sku;


		// handle product image
		$item->PictureDetails = new PictureDetailsType();
		$item->PictureDetails->addPictureURL( str_replace(' ', '%20', $main_image ) );
		if ( $profile_details['with_gallery_image'] ) $item->PictureDetails->GalleryType = 'Gallery';
        



		// handle VAT (percent)
		if ( $profile_details['tax_mode'] == 'fix' ) {
			$item->VATDetails = new VATDetailsType();
			$item->VATDetails->VATPercent = $profile_details['vat_percent'];
		}

		// Set Local Info
		$item->Currency = $profile_details['currency'];
		$item->Country = $profile_details['country'];
		$item->Location = $profile_details['location'];
		$item->DispatchTimeMax = $profile_details['dispatch_time'];
		$item->ConditionID = $profile_details['condition_id'];

		// set eBay site from global site iD
		// http://developer.ebay.com/DevZone/XML/docs/Reference/eBay/types/SiteCodeType.html
		$site_id = $session->getSiteId();
		$sites = EbayController::getEbaySites();
		$site_name = $sites[$site_id];
		$item->Site = $site_name; 


		#$item->setSubTitle('Brandneuer iPod Mini!');
		#$item->setListingEnhancement('Highlight');
		$item->setHitCounter( $profile_details['counter_style'] );


		// ReturnPolicy
		$item->ReturnPolicy = new ReturnPolicyType();
		if ( $profile_details['returns_accepted'] == 1 ) {
			$item->ReturnPolicy->ReturnsAcceptedOption = 'ReturnsAccepted';
			$item->ReturnPolicy->ReturnsWithinOption = $profile_details['returns_within'];
			$item->ReturnPolicy->Description = stripslashes( $profile_details['returns_description'] );
		} else {
			$item->ReturnPolicy->ReturnsAcceptedOption = 'ReturnsNotAccepted';
		}			


		// handle primary category
		if ( intval($profile_details['ebay_category_1_id']) > 0 ) {
			$item->PrimaryCategory = new CategoryType();
			$item->PrimaryCategory->CategoryID = $profile_details['ebay_category_1_id'];
		} else {
			// get categories map
			$categories_map_ebay = get_option( 'wplister_categories_map_ebay' );
            
			// fetch products local category
			$terms = wp_get_post_terms( $p['post_id'], ProductWrapper::getTaxonomy() );
  			foreach ( $terms as $term ) $cats_array[] = $term->term_id;
			// TODO: look and check other categories
			$product_category = $cats_array[0];

            // look up ebay category 
            $ebay_category_id = @$categories_map_ebay[ $product_category ];

            if ( intval( $ebay_category_id ) > 0 ) {
				$item->PrimaryCategory = new CategoryType();
				$item->PrimaryCategory->CategoryID = $ebay_category_id;
            }

		}

		// optional secondary category
		if ( intval($profile_details['ebay_category_2_id']) > 0 ) {
			$item->SecondaryCategory = new CategoryType();
			$item->SecondaryCategory->CategoryID = $profile_details['ebay_category_2_id'];
		}

		// handle optional store category
		if ( intval($profile_details['store_category_1_id']) > 0 ) {
			$item->Storefront = new StorefrontType();
			$item->Storefront->StoreCategoryID = $profile_details['store_category_1_id'];
		} else {
			// get categories map
			$categories_map_store = get_option( 'wplister_categories_map_store' );
            
			// fetch products local category
			$terms = wp_get_post_terms( $p['post_id'], ProductWrapper::getTaxonomy() );
  			foreach ( $terms as $term ) $cats_array[] = $term->term_id;
			// TODO: look and check other categories
			$product_category = $cats_array[0];

            // look up store category 
            $store_category_id = @$categories_map_store[ $product_category ];

            if ( intval( $store_category_id ) > 0 ) {
				$item->Storefront = new StorefrontType();
				$item->Storefront->StoreCategoryID = $store_category_id;
            }
            
		}

		// optional secondary store category
		if ( intval($profile_details['store_category_2_id']) > 0 ) {
			$item->Storefront->StoreCategory2ID = $profile_details['store_category_2_id'];
		}

		// Set Payment Methods
		// $item->PaymentMethods[] = 'PersonalCheck';
		// $item->PaymentMethods[] = 'PayPal';
		// $item->PayPalEmailAddress = 'youraccount@yourcompany.com';
		foreach ( $profile_details['payment_options'] as $payment_method ) {
			# BuyerPaymentMethodCodeType
			$item->addPaymentMethods( $payment_method['payment_name'] );
			if ( $payment_method['payment_name'] == 'PayPal' ) {
				$item->PayPalEmailAddress = get_option( 'wplister_paypal_email' );
			}
		}


		// add shipping services and options
		$item = $this->buildShipping( $id, $item, $p['post_id'], $profile_details );			

		// add variations
		if ( $hasVariations ) $item = $this->buildVariations( $id, $item, $profile_details );			
	
		// add item specifics (attributes) - after variations
		$item = $this->buildItemSpecifics( $id, $item );			

		// Set the Item Description
		$item->Description = $this->getFinalHTML( $id );

	
		return $item;

	} /* end of buildItem() */

	public function buildShipping( $id, $item, $post_id, $profile_details ) {


		$this->logger->info('shipping_service_type: '.$profile_details['shipping_service_type'] );
		$isFlat = $profile_details['shipping_service_type'] != 'calc' ? true : false;
		$isCalc = $profile_details['shipping_service_type'] == 'calc' ? true : false;

		$shippingDetails = new ShippingDetailsType();
		$shippingDetails->ShippingType = $isFlat ? 'Flat' : 'Calculated';
		$this->logger->info('shippingDetails->ShippingType: '.$shippingDetails->ShippingType );

		// local shipping options
		$localShippingOptions = $profile_details['loc_shipping_options'];
		$this->logger->info('localShippingOptions: '.print_r($localShippingOptions,1));

		$pr = 1;
		foreach ($localShippingOptions as $opt) {

			$price = $this->getDynamicShipping( $opt['price'], $post_id );
			$add_price = $this->getDynamicShipping( $opt['add_price'], $post_id );

			$ShippingServiceOptions = new ShippingServiceOptionsType();
			$ShippingServiceOptions->setShippingService( $opt['service_name'] );
			$ShippingServiceOptions->setShippingServicePriority($pr);
			
			// set shipping costs for flat services
			if ( $isFlat ) {
				$ShippingServiceOptions->setShippingServiceCost( $price );
				if ( trim( $add_price ) == '' ) {
					$ShippingServiceOptions->setShippingServiceAdditionalCost( $price );
				} else {
					$ShippingServiceOptions->setShippingServiceAdditionalCost( $add_price );
				}				
			}

			$localShippingServices[]=$ShippingServiceOptions;
			$pr++;
		}
		$shippingDetails->setShippingServiceOptions($localShippingServices, null);


		// $intlShipping = array(
		// 	'UK_RoyalMailAirmailInternational' => array (
		// 		'Europe' => 1,
		// 		'Worldwide' => 1.50
		// 	),
		// 	'UK_RoyalMailInternationalSignedFor' => array (
		// 		'Europe' => 5,
		// 	)
		// );
		$intlShipping = $profile_details['int_shipping_options'];
		$this->logger->debug('intlShipping: '.print_r($intlShipping,1));

		$pr = 1;
		foreach ($intlShipping as $opt) {
			// foreach ($opt as $loc=>$price) {
				$price = $this->getDynamicShipping( $opt['price'], $post_id );
				$add_price = $this->getDynamicShipping( $opt['add_price'], $post_id );

				$InternationalShippingServiceOptions = new InternationalShippingServiceOptionsType();
				$InternationalShippingServiceOptions->setShippingService( $opt['service_name'] );
				$InternationalShippingServiceOptions->setShippingServicePriority($pr);
				$InternationalShippingServiceOptions->setShipToLocation( $opt['location'] );

				// set shipping costs for flat services
				if ( $isFlat ) {
					$InternationalShippingServiceOptions->setShippingServiceCost( $price );
					if ( trim( $add_price ) == '' ) {
						$InternationalShippingServiceOptions->setShippingServiceAdditionalCost( $price );
					} else {
						$InternationalShippingServiceOptions->setShippingServiceAdditionalCost( $add_price );
					}				
				}
				$shippingInternational[]=$InternationalShippingServiceOptions;
				$pr++;
			// }
		}
		// only set international shipping if $intlShipping array contains one or more valid items
		if ( @$intlShipping[0]['service_name'] != '' )
			$shippingDetails->setInternationalShippingServiceOption($shippingInternational,null);

		// set CalculatedShippingRate
		if ( $isCalc ) {
			$calculatedShippingRate = new CalculatedShippingRateType();
			$calculatedShippingRate->setOriginatingPostalCode( $profile_details['postcode'] );
			$calculatedShippingRate->setShippingPackage( $localShippingOptions[0]['ShippingPackage'] );
			$calculatedShippingRate->setWeightMajor( floatval(ProductWrapper::getWeight( $post_id )) );

			$dimensions = ProductWrapper::getDimensions( $post_id );
			if ( trim( @$dimensions['width']  ) != '' ) $calculatedShippingRate->setPackageWidth( $dimensions['width'] );
			if ( trim( @$dimensions['length'] ) != '' ) $calculatedShippingRate->setPackageLength( $dimensions['length'] );
			if ( trim( @$dimensions['height'] ) != '' ) $calculatedShippingRate->setPackageDepth( $dimensions['height'] );

			// debug
			// $weight = ProductWrapper::getWeight( $post_id ) ;
			// $this->logger->info('weight: '.print_r($weight,1));
			// $this->logger->info('dimensions: '.print_r($dimensions,1));


			$shippingDetails->setCalculatedShippingRate( $calculatedShippingRate );
		}

		
		$item->setShippingDetails($shippingDetails,null);

		return $item;

	} /* end of buildShipping() */

	public function checkItem( $item ) {

		$success = true;

		// check StartPrice
		if ( is_object( $item->Variations ) ) {

			$VariationsHaveStock = false;
			$VariationsSkuArray = array();
			$VariationsSkuAreUnique = true;

			// check each variation
			foreach ($item->Variations->Variation as $var) {
				
				// StartPrice must be greater than 0
				if ( intval($var->StartPrice) == 0 ) {
					$itemTitle = trim($item->Title); 
					$longMessage = __('Some variations seem to have no price.','wplister');
					$success = false;
				}

				// Quantity must be greater than 0 - at least for one variation
				if ( intval($var->Quantity) > 0 ) $VariationsHaveStock = true;

				// SKUs must be unique - if present
				if ( ($var->SKU) != '' ) {
					if ( in_array( $var->SKU, $VariationsSkuArray )) {
						$VariationsSkuAreUnique = false;
					} else {
						$VariationsSkuArray[] = $var->SKU;
					}
				} 

			}

			if ( ! $VariationsSkuAreUnique ) {
				foreach ($item->Variations->Variation as &$var) {
					$var->SKU = '';
				}
				$longMessage = __('You are using the same SKU for more than one variations which is not allowed by eBay.','wplister');
				$longMessage .= '<br>';
				$longMessage .= __('To circumvent this issue, your item will be listed without SKU.','wplister');
				// $success = false;
			}

			if ( ! $VariationsHaveStock ) {
				$longMessage = __('None of these variations are in stock.','wplister');
				$success = false;
			}

		} else {
			// StartPrice must be greater than 0
			if ( intval($item->StartPrice) == 0 ) {
				$itemTitle = trim($item->Title); 
				$longMessage = __('Price can not be zero.','wplister');
				$success = false;
			}
		}

		if ( ! $success && ! $this->is_ajax() ) {
			$this->showMessage( $longMessage, 1, true );
		} elseif ( ( $longMessage != '' ) && ! $this->is_ajax() ) {
			$this->showMessage( $longMessage, 0, true );
		}

		$htmlMsg  = '<div id="message" class="error"><p>';
		$htmlMsg .= '<b>' . 'This item did not pass the validation check' . ':</b>';
		$htmlMsg .= '<br>' . $longMessage . '';
		$htmlMsg .= '</p></div>';

		// save error as array of objects
		$errorObj = new stdClass();
		$errorObj->SeverityCode = 'Validation';
		$errorObj->ErrorCode 	= '42';
		$errorObj->ShortMessage = $longMessage;
		$errorObj->LongMessage 	= $longMessage;
		$errorObj->HtmlMessage 	= $htmlMsg;
		$errors = array( $errorObj );

		// save results as local property
		$this->result = new stdClass();
		$this->result->success = $success;
		$this->result->errors  = $errors;

		return $success;

	} /* end of checkItem() */

	public function buildItemSpecifics( $id, $item ) {

    	// new ItemSpecifics
    	$ItemSpecifics = new NameValueListArrayType();

		// get listing data
		$listing = $this->getItem( $id );

		// get product attributes
        $attributes = ProductWrapper::getAttributes( $listing['post_id'] );
		$this->logger->info('product attributes: '.print_r($attributes,1));


		// apply item specifics from profile
		$specifics = $listing['profile_data']['details']['item_specifics'];
		$this->logger->info('item_specifics: '.print_r($specifics,1));
        foreach ($specifics as $spec) {
        	if ( $spec['value'] != '' ) {
        		$value = $spec['value'];
	            $NameValueList = new NameValueListType();
		    	$NameValueList->setName ( $spec['name']  );
	    		$NameValueList->setValue( $value );
	        	if ( ! in_array( $spec['name'], $this->variationAttributes ) ) {
		        	$ItemSpecifics->addNameValueList( $NameValueList );
	        	}
				$this->logger->info("specs: added custom value: {$spec['name']} - $value");
        	} elseif ( $spec['attribute'] != '' ) {
        		$value = $attributes[ $spec['attribute'] ];
	            $NameValueList = new NameValueListType();
		    	$NameValueList->setName ( $spec['name']  );
	    		$NameValueList->setValue( $value );
	        	if ( ! in_array( $spec['name'], $this->variationAttributes ) ) {
		        	$ItemSpecifics->addNameValueList( $NameValueList );
	        	}
				$this->logger->info("specs: added product attribute: {$spec['name']} - $value");
        	}
        }

        // skip if item has no attributes
        // if ( count($attributes) == 0 ) return $item;

    	// add ItemSpecifics from product attributes
    	/* disabled for now, since it causes duplicates and it's not actually required anymore
        foreach ($attributes as $name => $value) {
            $NameValueList = new NameValueListType();
	    	$NameValueList->setName ( $name  );
    		$NameValueList->setValue( $value );
        	
        	// only add attribute to ItemSpecifics if not already present in variations
        	if ( ! in_array( $name, $this->variationAttributes ) ) {
	        	$ItemSpecifics->addNameValueList( $NameValueList );
        	}
        } */

        if ( count($ItemSpecifics) > 0 ) {
    		$item->setItemSpecifics( $ItemSpecifics );        	
			$this->logger->info("item specifics were added.");
        }

		return $item;

	} /* end of buildItemSpecifics() */

	public function buildVariations( $id, $item, $profile_details ) {

		// build variations
		$item->Variations = new VariationsType();

		// get product variations
		$p = $this->getItem( $id );
        $variations = ProductWrapper::getVariations( $p['post_id'] );

        // loop each combination
        foreach ($variations as $var) {

        	$newvar = new VariationType();

        	// handle price
			$newvar->StartPrice = $this->applyProfilePrice( $var['price'], $profile_details['start_price'] );

        	// handle variation quantity - if no quantity set in profile
        	if ( intval( $item->Quantity ) == 0 ) {
        		$newvar->Quantity   = intval( $var['stock'] );
        	} else {
	        	$newvar->Quantity 	= $item->Quantity;
        	}

			// handle sku
        	if ( $var['sku'] != '' ) {
        		$newvar->SKU = $var['sku'];
        	}

        	// add VariationSpecifics (v2)
        	$VariationSpecifics = new NameValueListArrayType();
            foreach ($var['variation_attributes'] as $name => $value) {
	            $NameValueList = new NameValueListType();
    	    	$NameValueList->setName ( $name  );
        		$NameValueList->setValue( $value );
	        	$VariationSpecifics->addNameValueList( $NameValueList );
            }

        	$newvar->setVariationSpecifics( $VariationSpecifics );

			$item->Variations->addVariation( $newvar );
        }

        // build temporary array for VariationSpecificsSet
    	$tmpVariationSpecificsSet = array();
        foreach ($variations as $var) {

            foreach ($var['variation_attributes'] as $name => $value) {
    	    	if ( ! is_array($tmpVariationSpecificsSet[ $name ]) ) {
		        	$tmpVariationSpecificsSet[ $name ] = array();
    	    	}
	        	if ( ! in_array( $value, $tmpVariationSpecificsSet[ $name ] ) ) {
	        		$tmpVariationSpecificsSet[ $name ][] = $value;	        		
	        	}
            }

        }
        // build VariationSpecificsSet
    	$VariationSpecificsSet = new NameValueListArrayType();
        foreach ($tmpVariationSpecificsSet as $name => $values) {

			$NameValueList = new NameValueListType();
        	$NameValueList->setName ( $name );
            foreach ($values as $value) {
	        	$NameValueList->addValue( $value );
	        }
	    	$VariationSpecificsSet->addNameValueList( $NameValueList );

        }
    	$item->Variations->setVariationSpecificsSet( $VariationSpecificsSet );

        
        // build array of variation attributes, which will be needed in builtItemSpecifics()
        $this->variationAttributes = array();
        foreach ($tmpVariationSpecificsSet as $key => $value) {
        	$this->variationAttributes[] = $key;
        }
        $this->logger->info('variationAttributes'.print_r($this->variationAttributes,1));


        // select *one* VariationSpecificsSet for Pictures set
        // currently the first one is selected automatically, but there will be preferences for this later
        $VariationValuesForPictures =  reset($tmpVariationSpecificsSet);
        $VariationNameForPictures   =    key($tmpVariationSpecificsSet);

        // build Pictures
    	$Pictures = new PicturesType();
    	$Pictures->setVariationSpecificName ( $VariationNameForPictures );
        foreach ($variations as $var) {

        	$VariationValue = $var['variation_attributes'][$VariationNameForPictures];

        	if ( in_array( $VariationValue, $VariationValuesForPictures ) ) {
		    	$VariationSpecificPictureSet = new VariationSpecificPictureSetType();
    	    	$VariationSpecificPictureSet->setVariationSpecificValue( $VariationValue );
        		$VariationSpecificPictureSet->addPictureURL( str_replace(' ', '%20', $var['image'] ) );

		        // only list variation images if enabled
        		if ( @$profile_details['with_variation_images'] != '0' ) {
	    			$Pictures->addVariationSpecificPictureSet( $VariationSpecificPictureSet );
		        }
	    
	    		// remove value from VariationValuesForPictures to avoid duplicates
	    		unset( $VariationValuesForPictures[ array_search( $VariationValue, $VariationValuesForPictures ) ] );
        	}

        }
    	$item->Variations->setPictures( $Pictures );

    	// ebay doesn't allow different weight and dimensions for varations
    	// so for calculated shipping services we just fetch those from the first variation
    	// and overwrite 
		$isCalc = $profile_details['shipping_service_type'] == 'calc' ? true : false;
		if ( $isCalc ) {

			// get weight and dimensions from first variation
			$weight = $variations[0]['weight'];
			$dimensions = $variations[0]['dimensions'];

			$item->ShippingDetails->CalculatedShippingRate->setWeightMajor( floatval( $weight ) );
			if ( trim( @$dimensions['width']  ) != '' ) $item->ShippingDetails->CalculatedShippingRate->setPackageWidth( $dimensions['width'] );
			if ( trim( @$dimensions['length'] ) != '' ) $item->ShippingDetails->CalculatedShippingRate->setPackageLength( $dimensions['length'] );
			if ( trim( @$dimensions['height'] ) != '' ) $item->ShippingDetails->CalculatedShippingRate->setPackageDepth( $dimensions['height'] );

			// debug
			$this->logger->info('first variations weight: '.print_r($weight,1));
			$this->logger->info('first variations dimensions: '.print_r($dimensions,1));
		}


        // remove some settings from single item
		$item->SKU = null;
		$item->Quantity = null;
		$item->StartPrice = null;
		$item->BuyItNowPrice = null;

		return $item;
		
		/* this we should get:
		<Variations>
		    <Variation>
		        <SKU />
		        <StartPrice>15</StartPrice>
		        <Quantity>1</Quantity>
		        <VariationSpecifics>
		            <NameValueList>
		                <Name>Size</Name>
		                <Value>large</Value>
		            </NameValueList>
		        </VariationSpecifics>
		    </Variation>
		    <Variation>
		        <SKU />
		        <StartPrice>10</StartPrice>
		        <Quantity>1</Quantity>
		        <VariationSpecifics>
		            <NameValueList>
		                <Name>Size</Name>
		                <Value>small</Value>
		            </NameValueList>
		        </VariationSpecifics>
		    </Variation>
		    <Pictures>
		        <VariationSpecificName>Size</VariationSpecificName>
		        <VariationSpecificPictureSet>
		            <VariationSpecificValue>large</VariationSpecificValue>
		            <PictureURL>http://www.example.com/wp-content/uploads/2011/09/grateful-dead.jpg</PictureURL>
		        </VariationSpecificPictureSet>
		        <VariationSpecificPictureSet>
		            <VariationSpecificValue>small</VariationSpecificValue>
		            <PictureURL>www.example.com/wp-content/uploads/2011/09/grateful-dead.jpg</PictureURL>
		        </VariationSpecificPictureSet>
		    </Pictures>
		    <VariationSpecificsSet>
		        <NameValueList>
		            <Name>Size</Name>
		            <Value>large</Value>
		            <Value>small</Value>
		        </NameValueList>
		    </VariationSpecificsSet>
		</Variations>
		*/

	}

	function addItem( $id, $session )
	{
		// skip this item if item status not allowed
		$allowed_statuses = array( 'prepared', 'verified' );
		if ( ! $this->checkItemStatus( $id, $allowed_statuses ) ) return false;

		// build item
		$item = $this->buildItem( $id, $session );
		if ( ! $this->checkItem($item) ) return $this->result;

		// preparation - set up new ServiceProxy with given session
		$this->initServiceProxy($session);

		// switch to FixedPriceItem if product has variations
		$listing_item = $this->getItem( $id );
		$useFixedPriceItem = ( ProductWrapper::hasVariations( $listing_item['post_id'] ) ) ? true : false;

		$this->logger->info( "Adding #$id: ".$item->Title );
		if ( $useFixedPriceItem ) {

			$req = new AddFixedPriceItemRequestType(); 
			$req->setItem($item);
			
			$this->logger->debug( "Request: ".print_r($req,1) );
			$res = $this->_cs->AddFixedPriceItem($req); 

		} else {

			$req = new AddItemRequestType(); 
			$req->setItem($item);
			
			$this->logger->debug( "Request: ".print_r($req,1) );
			$res = $this->_cs->AddItem($req); 

		}

		// handle response and check if successful
		if ( $this->handleResponse($res) ) {

			// save ebay ID and fees to db
			$listingFee = $this->getListingFeeFromResponse( $res );
			$data['ebay_id'] = $res->ItemID;
			$data['fees'] = $listingFee;
			$data['status'] = 'published';
			$this->updateListing( $id, $data );
			
			// get details like ViewItemURL from ebay automatically
			$this->updateItemDetails( $id, $session );

			$this->logger->info( "Item #$id sent to ebay, ItemID is ".$res->ItemID );

		} // call successful

		return $this->result;

	} // addItem()

	function reviseItem( $id, $session )
	{
		// skip this item if item status not allowed
		$allowed_statuses = array( 'published', 'changed' );
		if ( ! $this->checkItemStatus( $id, $allowed_statuses ) ) return false;

		// switch to FixedPriceItem if product has variations
		$listing_item = $this->getItem( $id );
		$useFixedPriceItem = ( ProductWrapper::hasVariations( $listing_item['post_id'] ) ) ? true : false;

		// build item
		$item = $this->buildItem( $id, $session, false, true );
		if ( ! $this->checkItem($item) ) return $this->result;
		
		// preparation - set up new ServiceProxy with given session
		$this->initServiceProxy($session);

		// set ItemID to revise
		$item->setItemID( $this->getEbayIDFromID($id) );

		$this->logger->info( "Revising #$id: ".$p['auction_title'] );
		if ( $useFixedPriceItem ) {

			$req = new ReviseFixedPriceItemRequestType(); 
			$req->setItem($item);
			
			$this->logger->debug( "Request: ".print_r($req,1) );
			$res = $this->_cs->ReviseFixedPriceItem($req); 

		} else {

			$req = new ReviseItemRequestType(); 
			$req->setItem($item);
			
			$this->logger->debug( "Request: ".print_r($req,1) );
			$res = $this->_cs->ReviseItem($req); 

		}

		// handle response and check if successful
		if ( $this->handleResponse($res) ) {

			// save ebay ID and fees to db
			#$listingFee = $this->getListingFeeFromResponse( $res );
			#$data['ebay_id'] = $res->ItemID;
			#$data['fees'] = $listingFee;
			$data['status'] = 'published';
			$this->updateListing( $id, $data );
			
			// get details like ViewItemURL from ebay automatically
			#$this->updateItemDetails( $id, $session );

			$this->logger->info( "Item #$id was revised, ItemID is ".$res->ItemID );

		} // call successful

		return $this->result;

	} // reviseItem()


	function verifyAddItem( $id, $session )
	{
		// skip this item if item status not allowed
		$allowed_statuses = array( 'prepared', 'verified' );
		if ( ! $this->checkItemStatus( $id, $allowed_statuses ) ) return false;

		// switch to FixedPriceItem if product has variations
		$listing_item = $this->getItem( $id );
		if ( ProductWrapper::hasVariations( $listing_item['post_id'] ) ) return $this->verifyAddFixedPriceItem( $id, $session );

		// build item
		$item = $this->buildItem( $id, $session );
		if ( ! $this->checkItem($item) ) return $this->result;

		// preparation - set up new ServiceProxy with given session
		$this->initServiceProxy($session);

		$req = new VerifyAddItemRequestType();
		$req->setItem($item);

		$this->logger->info( "Verifying #$id: ".$item->Title );
		$res = $this->_cs->VerifyAddItem($req);

		// handle response and check if successful
		if ( $this->handleResponse($res) ) {

			// save listing fees to db
			$listingFee = $this->getListingFeeFromResponse( $res );
			// $data['ebay_id'] = $res->ItemID;
			$data['fees'] = $listingFee;
			$data['status'] = 'verified';
			$this->updateListing( $id, $data );

			$this->logger->info( "Item #$id verified with ebay, getAck(): ".$res->getAck() );

		} // call successful
		
		return $this->result;

	} // verifyAddItem()

	function verifyAddFixedPriceItem( $id, $session )
	{
		// skip this item if item status not allowed
		$allowed_statuses = array( 'prepared', 'verified' );
		if ( ! $this->checkItemStatus( $id, $allowed_statuses ) ) return false;

		// build item
		$item = $this->buildItem( $id, $session, true );
		if ( ! $this->checkItem($item) ) return $this->result;

		// preparation - set up new ServiceProxy with given session
		$this->initServiceProxy($session);

		$req = new VerifyAddFixedPriceItemRequestType();
		$req->setItem($item);

		$this->logger->debug( "Verifying FixedPriceItem #$id: ".$p['auction_title'] );
		$res = $this->_cs->VerifyAddFixedPriceItem($req);

		// handle response and check if successful
		if ( $this->handleResponse($res) ) {

			// save fees to db
			$listingFee = $this->getListingFeeFromResponse( $res );
			// $data['ebay_id'] = $res->ItemID;
			$data['fees'] = $listingFee;
			$data['status'] = 'verified';
			$this->updateListing( $id, $data );

			$this->logger->info( "Item #$id verified with ebay, getAck(): ".$res->getAck() );

		} // call successful

		return $this->result;

	} // verifyAddItem()


	function endItem( $id, $session )
	{
		// skip this item if item status not allowed
		$allowed_statuses = array( 'published' );
		if ( ! $this->checkItemStatus( $id, $allowed_statuses ) ) return false;

		// preparation - set up new ServiceProxy with given session
		$this->initServiceProxy($session);

		// get eBay ID
		$item = $this->getItem( $id );
		$item_id = $item['ebay_id'];

		$req = new EndItemRequestType(); # ***
        $req->setItemID( $item_id );
        $req->setEndingReason('LostOrBroken');

		$this->logger->info( "calling EndItem($id) #$item_id " );
		$this->logger->debug( "Request: ".print_r($req,1) );
		$res = $this->_cs->EndItem($req); # ***
		$this->logger->info( "EndItem() Complete #$item_id" );
		$this->logger->debug( "Response: ".print_r($res,1) );

		// handle response and check if successful
		if ( $this->handleResponse($res) ) {

			// save ebay ID and fees to db
			$data['end_date'] = $res->EndTime;
			$data['status'] = 'ended';
			$this->updateListing( $id, $data );
			
			$this->logger->info( "Item #$id was ended manually. " );

		} // call successful

		return $this->result;

	} // endItem()


	function checkItemStatus( $id, $allowed_statuses )
	{
		$item = $this->getItem( $id );
		if ( in_array( $item['status'], $allowed_statuses ) ) {
			return true;
		} else {
			$this->logger->info("skipped item $id with status ".$item['status']);
			$this->logger->debug("allowed_statuses: ".print_r($allowed_statuses,1) );
			$this->showMessage( sprintf( 'Skipped %s item: %s', $item['status'], $item['auction_title'] ), false, true );
			return false;
		}

	} // endItem()


	function getListingFeeFromResponse( $res )
	{
		
		$fees = new FeesType();
		$fees = $res->GetFees();
		foreach ($fees->getFee() as $fee) {
			if ( $fee->GetName() == 'ListingFee' ) {
				$listingFee = $fee->GetFee()->getTypeValue();
			}
			$this->logger->debug( 'FeeName: '.$fee->GetName(). ' is '. $fee->GetFee()->getTypeValue().' '.$fee->GetFee()->getTypeAttribute('currencyID') );
		}
		return $listingFee;

	} // getListingFeeFromResponse()


	public function updateItemDetails( $id, $session ) {
		global $wpdb;

		// get item data
		$item = $this->getItem( $id );

		// preparation
		$this->initServiceProxy($session);

		$this->_cs->setHandler('ItemType', array(& $this, 'updateItemDetail'));

		// download the shipping data
		$req = new GetItemRequestType();
        $req->setItemID( $item['ebay_id'] );
		#$req->setDetailName( 'PaymentOptionDetails' );
		#$req->setActiveList( true );

		$res = $this->_cs->GetItem($req);		

		// handle response and check if successful
		if ( $this->handleResponse($res) ) {
			$this->logger->info( "Item #$id was updated from eBay, ItemID is ".$res->ItemID );
		} // call successful

		return $this->result;

	}


	function updateItemDetail($type, & $Detail)
	{
		global $wpdb;
		
		//#type $Detail ItemType
		
		// map ItemType to DB columns
		$data = $this->mapItemDetailToDB( $Detail );

		$this->logger->debug('Detail: '.print_r($Detail,1) );
		$this->logger->debug('data: '.print_r($data,1) );

		$wpdb->update( $this->tablename, $data, array( 'ebay_id' => $Detail->ItemID ) );

		#$this->logger->info('sql: '.$wpdb->last_query );
		#$this->logger->info( mysql_error() );

		return true;
	}

	function mapItemDetailToDB( $Detail )
	{
		//#type $Detail ItemType
		$data['ebay_id'] 			= $Detail->ItemID;
		$data['auction_title'] 		= $Detail->Title;
		$data['auction_type'] 		= $Detail->ListingType;
		$data['listing_duration'] 	= $Detail->ListingDuration;
		$data['date_published'] 	= $Detail->ListingDetails->StartTime;
		$data['end_date'] 			= $Detail->ListingDetails->EndTime;
		$data['price'] 				= $Detail->SellingStatus->CurrentPrice->value;
		$data['quantity_sold'] 		= $Detail->SellingStatus->QuantitySold;
		$data['quantity'] 			= $Detail->Quantity;
		$data['ViewItemURL'] 		= $Detail->ListingDetails->ViewItemURL;
		$data['GalleryURL'] 		= $Detail->PictureDetails->GalleryURL;

		// if this item has variations, we don't update quantity
		if ( count( @$Detail->Variations->Variation ) > 0 ) {
			unset( $data['quantity'] );
		}


		// set status to ended if end_date is in the past
		if ( time() > mysql2date('U', $data['end_date']) ) {
			$data['status'] 		= 'ended';
		} else {
			$data['status'] 		= 'published';			
		}

		$data['details'] = $this->encodeObject( $Detail );

		return $data;
	}



	public function updateListing( $id, $data ) {
		global $wpdb;

		// update
		$wpdb->update( $this->tablename, $data, array( 'id' => $id ) );

		#$this->logger->info('sql: '.$wpdb->last_query );
		#$this->logger->info( mysql_error() );
	}


	public function updateEndedListings( $sm = false ) {
		global $wpdb;

		$items = $this->getAllPastEndDate();

		foreach ($items as $item) {
			$wpdb->update( $this->tablename, array( 'status' => 'ended' ), array( 'id' => $item['id'] ) );
		}

		#$this->logger->info('sql: '.$wpdb->last_query );
		#$this->logger->info( mysql_error() );
	}

	public function getDynamicShipping( $price, $post_id ) {
		
		// return price if no mapping
		if ( ! substr( $price, 0, 1 ) == '[' ) return floatval($price);

		// split values list			
		$values = substr( $price, 1, -1 );
		$values = explode( '|', $values );

		// first item is mode
		$mode = array_shift($values);


		// weight mode
		if ( $mode == 'weight' ) {

			$product_weight = ProductWrapper::getWeight( $post_id );
			foreach ($values as $val) {
				list( $limit, $price ) = explode(':', $val);
				if ( $product_weight >= $limit) $shipping_cost = $price;
			}
			return floatval($shipping_cost);
		}
		
		// convert '0.00' to '0' - ebay api doesn't like '0.00'
		if ( $price == 0 ) $price = '0';

		return floatval($price);

	}


	public function prepareTitleAsHTML( $title ) {

		$this->logger->debug('prepareTitleAsHTML()  in: ' . $title );
		$title = htmlentities( $title, ENT_QUOTES, 'UTF-8', false );
		$this->logger->debug('prepareTitleAsHTML() out: ' . $title );

		return $title;
	}


	public function prepareTitle( $title ) {

		$this->logger->info('prepareTitle()  in: ' . $title );

		// replace some specials chars with harmless versions
		// $title = str_replace('&#8211;', '-', $title );
		// $title = str_replace('&ndash;', '-', $title );
		// $title = str_replace('“', '"', $title );
		// $title = str_replace('”', '"', $title );
		// $title = str_replace('’', '\'', $title );

		// $title = str_replace('&#8220;', '"', $title );
		// $title = str_replace('&#8221;', '"', $title );
		// $title = str_replace('&#8217;', '\'', $title );
		// $title = str_replace('&#8230;', '...', $title );

		// $this->logger->info('prepareTitle()  s1: ' . $title );

		$title = html_entity_decode( $title, ENT_QUOTES, 'UTF-8' );

		$this->logger->info('prepareTitle() out: ' . $title );
		return $title;
	}
	

	public function getFinalHTML( $id ) {
		
		// get item data
		$item = $this->getItem( $id );

		// load template
		$template = new TemplatesModel( $item['template'] );
		$html = $template->processItem( $item );

		// return html
		return $html;
	}

	public function getDynamicContent( $sFile, $inaData = array() ) {

		if ( !is_file( $sFile ) ) {
			$this->showMessage("File not found: ".$sFile,1,1);
			return false;
		}
		
		if ( count( $inaData ) > 0 ) {
			extract( $inaData, EXTR_PREFIX_ALL, 'wpl' );
		}
		
		ob_start();
			include( $sFile );
			$sContents = ob_get_contents();
		ob_end_clean();
		
		return $sContents;

	}
	

	public function getProductMainImageURL( $post_id, $checking_parent = false ) {

		// this seems to be neccessary for listing previews on some installations 
		if ( ! function_exists('get_post_thumbnail_id')) 
		require_once( ABSPATH . 'wp-includes/post-thumbnail-template.php');

		$large_image_url = ProductWrapper::getImageURL( $post_id );
		if ( $large_image_url ) {
			$image_url = $large_image_url;
		} else {
			$images = $this->getProductImagesURL( $post_id );
			$image_url = $images[0];
		}

		if ( ( $image_url == '' ) && ( ! $checking_parent ) ) {
			// $parents = get_post_ancestors( $post_id );
			$parent_id = get_post($post_id)->post_parent;
			if ( $parent_id ) {
				return $this->getProductMainImageURL( $parent_id, true);
			}
		}

		// ebay doesn't accept https - only http and ftp
		$image_url = str_replace( 'https://', 'http://', $image_url );
		
		return $image_url;

	}
	

	public function getProductImagesURL( $id ) {
		global $wpdb;

		$results = $wpdb->get_col( 
			"
			SELECT guid 
			FROM {$wpdb->prefix}posts
			WHERE post_type = 'attachment' 
			  AND post_parent = '$id' 
			ORDER BY menu_order
			"
		);
		$this->logger->debug( "getProductImagesURL( $id ) : " . print_r($results,1) );
		
		$filenames = array();
		foreach($results as $row) {
			#$filenames[] = basename( $row );
			$filenames[] = str_replace( 'https://', 'http://', $row );
		}
		
		return $filenames;
	}


	function getAllSelected() {
		global $wpdb;	
		$items = $wpdb->get_results("
			SELECT * 
			FROM $this->tablename
			WHERE status = 'selected'
			ORDER BY id DESC
		", ARRAY_A);		

		return $items;		
	}
	function getAllPrepared() {
		global $wpdb;	
		$items = $wpdb->get_results("
			SELECT * 
			FROM $this->tablename
			WHERE status = 'prepared'
			ORDER BY id DESC
		", ARRAY_A);		

		return $items;		
	}
	function getAllVerified() {
		global $wpdb;	
		$items = $wpdb->get_results("
			SELECT * 
			FROM $this->tablename
			WHERE status = 'verified'
			ORDER BY id DESC
		", ARRAY_A);		

		return $items;		
	}
	function getAllChanged() {
		global $wpdb;	
		$items = $wpdb->get_results("
			SELECT * 
			FROM $this->tablename
			WHERE status = 'changed'
			ORDER BY id DESC
		", ARRAY_A);		

		return $items;		
	}
	function getAllPublished() {
		global $wpdb;	
		$items = $wpdb->get_results("
			SELECT * 
			FROM $this->tablename
			WHERE status = 'published'
			   OR status = 'changed'
			ORDER BY id DESC
		", ARRAY_A);		

		return $items;		
	}
	function getAllPreparedWithProfile( $profile_id ) {
		global $wpdb;	
		$items = $wpdb->get_results("
			SELECT * 
			FROM $this->tablename
			WHERE status = 'prepared'
			  AND profile_id = '$profile_id'
			ORDER BY id DESC
		", ARRAY_A);		

		return $items;		
	}
	function getAllPastEndDate() {
		global $wpdb;	
		$items = $wpdb->get_results("
			SELECT id 
			FROM $this->tablename
			WHERE NOT status = 'ended'
			  AND end_date < NOW()
			ORDER BY id DESC
		", ARRAY_A);		

		return $items;		
	}

	function getRawPostExcerpt( $post_id ) {
		global $wpdb;	
		$excerpt = $wpdb->get_var("
			SELECT post_excerpt 
			FROM {$wpdb->prefix}posts
			WHERE ID = $post_id
		");

		return $excerpt;		
	}



	public function selectedProducts() {
		global $wpdb;	
		$items = $wpdb->get_results("
			SELECT * 
			FROM $this->tablename
			WHERE status = 'selected'
			ORDER BY id DESC
		", ARRAY_A);		

		return $items;		
	}

	
	public function reSelectListings( $ids ) {
		global $wpdb;
		foreach( $ids as $id ) {
			$wpdb->update( $this->tablename, array( 'status' => 'selected' ), array( 'id' => $id ) );
		}
	}


	public function prepareListings( $ids ) {
		foreach( $ids as $id ) {
			$this->prepareProductForListing( $id );
		}
	}

	public function prepareProductForListing( $post_id, $post_content = false, $post_title = false ) {
		global $wpdb;
		
		// get wp post record
		$post = get_post( $post_id );
		
		// gather product data
		$data['post_id'] = $post_id;
		$data['auction_title'] = $post_title ? $post_title : $post->post_title;
		$data['post_content'] = $post_content ? $post_content : $post->post_content;
		$data['price'] = ProductWrapper::getPrice( $post_id );
		$data['status'] = 'selected';
		
		$this->logger->info('insert new auction '.$post_id.' - title: '.$data['auction_title']);
		$this->logger->debug( print_r($post,1) );
		
		// insert in auctions table
		$wpdb->insert( $this->tablename, $data );

		$this->logger->debug('sql: '.$wpdb->last_query );
		$this->logger->debug( mysql_error() );
		
		return $wpdb->insert_id;
		
	}

	function applyProfilePrice( $product_price, $profile_price ) {
	
		$this->logger->debug('applyProfilePrice(): '.$product_price.' - '.$profile_price );

		// remove all spaces from profile setting
		$profile_price = str_replace( ' ','', trim($profile_price) );
		
		// return product price if profile is empty
		if ( $profile_price == '' ) return $product_price;
	
		// handle percent
		if ( preg_match('/\%/',$profile_price) ) {
			$this->logger->debug('percent mode');
		
			// parse percent syntax
			if ( preg_match('/([\+\-]?)([0-9\.]+)(\%)/',$profile_price, $matches) ) {
				$this->logger->debug('matches:' . print_r($matches,1) );

				$modifier = $matches[1];
				$value = $matches[2];
				
				if ($modifier == '+') {
					return $product_price + ( $product_price * $value/100 );							
				} elseif ($modifier == '-') {
					return $product_price - ( $product_price * $value/100 );				
				} else {
					return ( $product_price * $value/100 );
				}
			
			} else {
				// no valid syntax
				return $product_price;		
			}
						
		} else {

			$this->logger->debug('value mode');
		
			// parse value syntax
			if ( preg_match('/([\+\-]?)([0-9\.]+)/',$profile_price, $matches) ) {
				$this->logger->debug('matches:' . print_r($matches,1) );

				$modifier = $matches[1];
				$value = $matches[2];
				
				if ($modifier == '+') {
					return $product_price + $value;				
				} elseif ($modifier == '-') {
					return $product_price - $value;				
				} else {
					return $value;
				}
			
			} else {
				// no valid syntax
				return $product_price;		
			}
		
		}

	}

	public function applyProfileToItem( $profile, $item, $update_title = true ) {
		global $wpdb;

		// get item data
		$id 		= $item['id'];
		$post_id 	= $item['post_id'];
		$status 	= $this->getStatus( $id );
		$ebay_id 	= $this->getEbayIDFromID( $id );
		$post_title = get_the_title( $item['post_id'] );

		// skip ended auctions
		if ( $status == 'ended' ) return;

		// gather profile data
		$data = array();
		$data['profile_id'] 		= $profile['profile_id'];
		$data['auction_type'] 		= $profile['type'];
		$data['listing_duration'] 	= $profile['listing_duration'];
		$data['template'] 			= $profile['details']['template'];
		$data['quantity'] 			= $profile['details']['quantity'];
		$data['date_created'] 		= date( 'Y-m-d H:i:s' );
		$data['profile_data'] 		= $this->encodeObject( $profile );
		
		// add prefix and suffix to product title
		if ( $update_title ) {

			// append space to prefix, prepend space to suffix
			// TODO: make this an option
			$title_prefix = trim( $profile['details']['title_prefix'] ) . ' ';
			$title_suffix = ' ' . trim( $profile['details']['title_suffix'] );

			$data['auction_title'] = trim( $title_prefix . $post_title . $title_suffix );
		}

		// apply profile price
		$data['price'] = ProductWrapper::getPrice( $post_id );
		$data['price']  = $this->applyProfilePrice( $data['price'], $profile['details']['start_price'] );
		
		// fetch product stock if no quantity set in profile
		if ( intval( $data['quantity'] ) == 0 ) {
			$data['quantity'] = ProductWrapper::getStock( $post_id );
		}
		
		// default new status is 'prepared'
		$data['status'] = 'prepared';
		// except for already published items where it is 'changed'
		if ( intval($ebay_id) > 0 ) $data['status'] = 'changed';

		// update auctions table
		$wpdb->update( $this->tablename, $data, array( 'id' => $id ) );

	}

	public function applyProfileToItems( $profile, $items, $update_title = true ) {

		// apply profile to all items
		foreach( $items as $item ) {
			$this->applyProfileToItem( $profile, $item, $update_title );			
		}

		return $items;		
	}


	public function applyProfileToNewListings( $profile, $items = false, $update_title = true ) {

		// get selected items - if no items provided
		if (!$items) $items = $this->getAllSelected();

		$items = $this->applyProfileToItems( $profile, $items, $update_title );			

		return $items;		
	}

	public function reapplyProfileToItem( $id ) {
	
		// get item
		$item = $this->getItem( $id );

		// get profile
		$profilesModel = new ProfilesModel();
        $profile = $profilesModel->getItem( $item['profile_id'] );

        // re-apply profile
        $this->applyProfileToItem( $profile, $item );

	}

	public function reapplyProfileToItems( $ids ) {
		foreach( $ids as $id ) {
			$this->reapplyProfileToItem( $id );
		}
	}


}
