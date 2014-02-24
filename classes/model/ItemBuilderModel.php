<?php
/**
 * ItemBuilderModel class
 *
 * responsible for building listing items
 * 
 */

class ItemBuilderModel extends WPL_Model {

	// var $_session;
	// var $_cs;
	
	var $variationAttributes = array();
	var $result = false;

	function ItemBuilderModel()
	{
		global $wpl_logger;
		$this->logger = &$wpl_logger;

		// provide listings model
		$this->lm = new ListingsModel();
	}



	function buildItem( $id, $session, $reviseItem = false )
	{

		// fetch record from db
		$listing         = $this->lm->getItem( $id );
		$post_id 		 = $listing['post_id'];
		$profile_details = $listing['profile_data']['details'];
		$hasVariations   = ProductWrapper::hasVariations( $post_id );
		$isVariation     = ProductWrapper::isSingleVariation( $post_id );
			
		// adjust profile details from product level options
		$profile_details = $this->adjustProfileDetails( $id, $post_id, $profile_details );


		// create Item
		$item = new ItemType();

		// set quantity
		$item->Quantity = $listing['quantity'];

		// set listing title
		$item->Title = $this->prepareTitle( $listing['auction_title'] );

		// set listing description
		$item->Description = $this->getFinalHTML( $id, $item );

		// set listing duration
		$product_listing_duration = get_post_meta( $post_id, '_ebay_listing_duration', true );
		$item->ListingDuration = $product_listing_duration ? $product_listing_duration : $listing['listing_duration'];

		// omit ListingType when revising item
		if ( ! $reviseItem ) {
			$product_listing_type = get_post_meta( $post_id, '_ebay_auction_type', true );
			$item->ListingType = $product_listing_type ? $product_listing_type : $listing['auction_type'];
		}


		// set eBay Site
		$item = $this->setEbaySite( $item, $session );			

		// add prices
		$item = $this->buildPrices( $id, $item, $post_id, $profile_details, $listing );			

		// add images
		$item = $this->buildImages( $id, $item, $post_id, $profile_details, $session );			


		// if this is a split variation, use parent post_id for all further processing
		if ( $isVariation ) {
			$post_id = ProductWrapper::getVariationParent( $post_id );
		}


		// add various options from $profile_details
		$item = $this->buildProfileOptions( $item, $profile_details );			

		// add various options that depend on $profile_details and $post_id
		$item = $this->buildProductOptions( $id, $item, $post_id, $profile_details );			

		// add payment and return options
		$item = $this->buildPayment( $item, $profile_details );			

		// add shipping services and options
		$item = $this->buildShipping( $id, $item, $post_id, $profile_details );			

		// add seller profiles
		$item = $this->buildSellerProfiles( $id, $item, $post_id, $profile_details );			

		// add ebay categories and store categories
		$item = $this->buildCategories( $id, $item, $post_id, $profile_details );			

		// add variations
		if ( $hasVariations ) {
			if ( @$profile_details['variations_mode'] == 'flat' ) {
				// don't build variations - list as flat item
				$item = $this->flattenVariations( $id, $item, $post_id, $profile_details );	
			} else {
				// default: list as variations
				$item = $this->buildVariations( $id, $item, $profile_details, $listing, $session );	
			}
		}
	
		// add item specifics (attributes) - after variations
		$item = $this->buildItemSpecifics( $id, $item, $listing, $post_id );			

		// adjust item if this is a ReviseItem request
		if ( $reviseItem ) {
			$item = $this->adjustItemForRevision( $id, $item, $profile_details, $listing );			
		} else {
			$item = $this->buildSchedule( $item, $profile_details );						
		}
	
		// add UUID to prevent duplicate AddItem or RelistItem calls
		if ( ! $reviseItem ) {
			// build UUID from listing Title, product_id, previous ItemID and today's date and hour
			$uuid_src = $item->Title . $post_id . $listing['ebay_id'] . date('Y-m-d h');
			$item->setUUID( md5( $uuid_src ) );
			$this->logger->info('UUID src: '.$uuid_src);
		}

		return $item;

	} /* end of buildItem() */

	// adjust item for ReviseItem request
	public function adjustItemForRevision( $id, $item, $profile_details, $listing ) {

		// check if title should be omitted:
		// The title or subtitle cannot be changed if an auction-style listing has a bid or ends within 12 hours, 
		// or a fixed price listing has a sale or a pending Best Offer.
		if ( 'Chinese' == $listing['auction_type'] ) {

			// auction listing
			$hours_left = ( strtotime($listing['end_date']) - gmdate('U') ) / 3600;
			if ( $hours_left < 12 ) {
				$item->setTitle( null );
				$item->setSubTitle( null );
			}

		} else {

			// fixed price listing
			if ( $listing['quantity_sold'] > 0 ) {
				$item->setTitle( null );
				$item->setSubTitle( null );
			}

		}

		return $item;

	} /* end of adjustItemForRevision() */

	public function setEbaySite( $item, $session ) {

		// set eBay site from global site iD
		// http://developer.ebay.com/DevZone/XML/docs/Reference/eBay/types/SiteCodeType.html
		$site_id = $session->getSiteId();
		$sites = EbayController::getEbaySites();
		$site_name = $sites[$site_id];
		$item->Site = $site_name; 

		return $item;

	} /* end of setEbaySite() */

	public function buildCategories( $id, $item, $post_id, $profile_details ) {

		// handle primary category
		$ebay_category_1_id = get_post_meta( $post_id, '_ebay_category_1_id', true );
		if ( intval( $ebay_category_1_id ) > 0 ) {
			$item->PrimaryCategory = new CategoryType();
			$item->PrimaryCategory->CategoryID = $ebay_category_1_id;
		} elseif ( intval($profile_details['ebay_category_1_id']) > 0 ) {
			$item->PrimaryCategory = new CategoryType();
			$item->PrimaryCategory->CategoryID = $profile_details['ebay_category_1_id'];
		} else {
			// get ebay categories map
			$categories_map_ebay = get_option( 'wplister_categories_map_ebay' );
            
			// fetch products local category terms
			$terms = wp_get_post_terms( $post_id, ProductWrapper::getTaxonomy() );
			// $this->logger->info('terms: '.print_r($terms,1));

			$ebay_category_id = false;
			$primary_category_id = false;
			$secondary_category_id = false;
  			foreach ( $terms as $term ) {

	            // look up ebay category 
	            if ( isset( $categories_map_ebay[ $term->term_id ] ) ) {
    		        $ebay_category_id = @$categories_map_ebay[ $term->term_id ];
    		        $ebay_category_id = apply_filters( 'wplister_apply_ebay_category_map', $ebay_category_id, $post_id );
	            }
	            
	            // check ebay category 
	            if ( intval( $ebay_category_id ) > 0 ) {

	            	if ( ! $primary_category_id ) {
	    		        $primary_category_id = $ebay_category_id;
	            	} else {
	            		$secondary_category_id = $ebay_category_id;
	            	}
	            }

  			}

			$this->logger->info('mapped primary_category_id: '.$primary_category_id);
			$this->logger->info('mapped secondary_category_id: '.$secondary_category_id);

            if ( intval( $primary_category_id ) > 0 ) {
				$item->PrimaryCategory = new CategoryType();
				$item->PrimaryCategory->CategoryID = $primary_category_id;
            }

            if ( ( intval( $secondary_category_id ) > 0 ) && ( $secondary_category_id != $primary_category_id ) ) {
				$item->SecondaryCategory = new CategoryType();
				$item->SecondaryCategory->CategoryID = $secondary_category_id;
            }

		}

		// optional secondary category
		$ebay_category_2_id = get_post_meta( $post_id, '_ebay_category_2_id', true );
		if ( intval( $ebay_category_2_id ) > 0 ) {
			$item->SecondaryCategory = new CategoryType();
			$item->SecondaryCategory->CategoryID = $ebay_category_2_id;
		} elseif ( intval($profile_details['ebay_category_2_id']) > 0 ) {
			$item->SecondaryCategory = new CategoryType();
			$item->SecondaryCategory->CategoryID = $profile_details['ebay_category_2_id'];
		}



		// handle optional store category
		if ( intval($profile_details['store_category_1_id']) > 0 ) {
			$item->Storefront = new StorefrontType();
			$item->Storefront->StoreCategoryID = $profile_details['store_category_1_id'];
		} else {
			// get store categories map
			$categories_map_store = get_option( 'wplister_categories_map_store' );

			// fetch products local category terms
			$terms = wp_get_post_terms( $post_id, ProductWrapper::getTaxonomy() );
			// $this->logger->info('terms: '.print_r($terms,1));

			$store_category_id = false;
			$primary_store_category_id = false;
			$secondary_store_category_id = false;
  			foreach ( $terms as $term ) {

	            // look up store category 
	            if ( isset( $categories_map_store[ $term->term_id ] ) ) {
    		        $store_category_id = @$categories_map_store[ $term->term_id ];
	            }
	            
	            // check store category 
	            if ( intval( $store_category_id ) > 0 ) {

	            	if ( ! $primary_store_category_id ) {
	    		        $primary_store_category_id = $store_category_id;
	            	} else {
	            		$secondary_store_category_id = $store_category_id;
	            	}
	            }

  			}

			$this->logger->info('mapped primary_store_category_id: '.$primary_store_category_id);
			$this->logger->info('mapped secondary_store_category_id: '.$secondary_store_category_id);

            if ( intval( $primary_store_category_id ) > 0 ) {
				$item->Storefront = new StorefrontType();
				$item->Storefront->StoreCategoryID = $primary_store_category_id;
            }

            if ( intval( $secondary_store_category_id ) > 0 ) {
				$item->Storefront->StoreCategory2ID = $secondary_store_category_id;
            }
            
		}

		// optional secondary store category
		if ( intval($profile_details['store_category_2_id']) > 0 ) {
			$item->Storefront->StoreCategory2ID = $profile_details['store_category_2_id'];
		}


		// adjust Site if required - eBay Motors (beta)
		$cm = new EbayCategoriesModel();
		$primary_category = $cm->getItem( $item->PrimaryCategory->CategoryID );
		if ( $primary_category['site_id'] == 100 ) {
			$item->setSite('eBayMotors');
			// echo "<pre>";print_r($primary_category);echo"</pre>";die();
		}

		return $item;

	} /* end of buildCategories() */


	// adjust profile details from product level options
	public function adjustProfileDetails( $id, $post_id, $profile_details ) {

		// use parent post_id for split variations
		if ( ProductWrapper::isSingleVariation( $post_id ) ) {
			$post_id = ProductWrapper::getVariationParent( $post_id );
		}

		// check for custom product level condition options
		if ( get_post_meta( $post_id, '_ebay_condition_id', true ) )
			$profile_details['condition_id']			= get_post_meta( $post_id, '_ebay_condition_id', true );
		if ( get_post_meta( $post_id, '_ebay_condition_description', true ) )
			$profile_details['condition_description']	= get_post_meta( $post_id, '_ebay_condition_description', true );

		// check for custom product level seller profiles
		if ( get_post_meta( $post_id, '_ebay_seller_shipping_profile_id', true ) )
			$profile_details['seller_shipping_profile_id']			= get_post_meta( $post_id, '_ebay_seller_shipping_profile_id', true );
		if ( get_post_meta( $post_id, '_ebay_seller_payment_profile_id', true ) )
			$profile_details['seller_payment_profile_id']			= get_post_meta( $post_id, '_ebay_seller_payment_profile_id', true );
		if ( get_post_meta( $post_id, '_ebay_seller_return_profile_id', true ) )
			$profile_details['seller_return_profile_id']			= get_post_meta( $post_id, '_ebay_seller_return_profile_id', true );

		// check for custom product level ship to locations
		if ( get_post_meta( $post_id, '_ebay_shipping_ShipToLocations', true ) )
			$profile_details['ShipToLocations']						= get_post_meta( $post_id, '_ebay_shipping_ShipToLocations', true );
		if ( get_post_meta( $post_id, '_ebay_shipping_ExcludeShipToLocations', true ) )
			$profile_details['ExcludeShipToLocations']				= get_post_meta( $post_id, '_ebay_shipping_ExcludeShipToLocations', true );

		// check for custom product level shipping options
		$product_shipping_service_type = get_post_meta( $post_id, '_ebay_shipping_service_type', true );
		if ( ( $product_shipping_service_type != '' ) && ( $product_shipping_service_type != 'disabled' ) ) {
			$profile_details['shipping_service_type']               = $product_shipping_service_type;
			$profile_details['loc_shipping_options']                = get_post_meta( $post_id, '_ebay_loc_shipping_options', true );
			$profile_details['int_shipping_options']                = get_post_meta( $post_id, '_ebay_int_shipping_options', true );
			$profile_details['PackagingHandlingCosts']              = get_post_meta( $post_id, '_ebay_PackagingHandlingCosts', true );
			$profile_details['InternationalPackagingHandlingCosts'] = get_post_meta( $post_id, '_ebay_InternationalPackagingHandlingCosts', true );
		}

		return $profile_details;

	} /* end of adjustProfileDetails() */


	public function buildSellerProfiles( $id, $item, $post_id, $profile_details ) {

		$SellerProfiles = new SellerProfilesType();

		if ( @$profile_details['seller_shipping_profile_id'] ) {
			$SellerProfiles->SellerShippingProfile = new SellerShippingProfileType();
			$SellerProfiles->SellerShippingProfile->setShippingProfileID( $profile_details['seller_shipping_profile_id'] );
		}

		if ( @$profile_details['seller_payment_profile_id'] ) {
			$SellerProfiles->SellerPaymentProfile = new SellerPaymentProfileType();
			$SellerProfiles->SellerPaymentProfile->setPaymentProfileID( $profile_details['seller_payment_profile_id'] );
		}

		if ( @$profile_details['seller_return_profile_id'] ) {
			$SellerProfiles->SellerReturnProfile = new SellerReturnProfileType();
			$SellerProfiles->SellerReturnProfile->setReturnProfileID( $profile_details['seller_return_profile_id'] );
		}

		$item->setSellerProfiles( $SellerProfiles );

		return $item;
	} /* end of buildSellerProfiles() */


	public function buildPrices( $id, $item, $post_id, $profile_details, $listing ) {

		// price has been calculated when applying the profile
		$start_price  = $listing['price'];

		// support for WooCommerce Name Your Price plugin
		$nyp_enabled = get_post_meta( $post_id, '_nyp', true ) == 'yes' ? true : false;
		if ( $nyp_enabled ) {
			$suggested_price = get_post_meta( $post_id, '_suggested_price', true );
			if ( $suggested_price ) $start_price = $suggested_price;
		}

		// handle StartPrice on product level
		if ( $product_start_price = get_post_meta( $post_id, '_ebay_start_price', true ) ) {
			$start_price  = $product_start_price;
		}

		// Set the Listing Starting Price and Buy It Now Price
		$item->StartPrice = new AmountType();
		$item->StartPrice->setTypeValue( $start_price );
		$item->StartPrice->setTypeAttribute('currencyID', $profile_details['currency'] );

		// optional BuyItNow price
		if ( intval($profile_details['fixed_price']) != 0) {
			$buynow_price = $this->lm->applyProfilePrice( $listing['price'], $profile_details['fixed_price'] );
			$item->BuyItNowPrice = new AmountType();
			$item->BuyItNowPrice->setTypeValue( $buynow_price );
			$item->BuyItNowPrice->setTypeAttribute('currencyID', $profile_details['currency'] );
		}
		if ( $buynow_price = get_post_meta( $post_id, '_ebay_buynow_price', true ) ) {
			$item->BuyItNowPrice = new AmountType();
			$item->BuyItNowPrice->setTypeValue( $buynow_price );
			$item->BuyItNowPrice->setTypeAttribute('currencyID', $profile_details['currency'] );
		}

		// optional ReservePrice
		if ( $product_reserve_price = get_post_meta( $post_id, '_ebay_reserve_price', true ) ) {
			$item->ReservePrice = new AmountType();
			$item->ReservePrice->setTypeValue( $product_reserve_price );
			$item->ReservePrice->setTypeAttribute('currencyID', $profile_details['currency'] );
		}

		// optional DiscountPriceInfo.OriginalRetailPrice
		if ( intval($profile_details['strikethrough_pricing']) != 0) {
			if ( method_exists( ProductWrapper, 'getOriginalPrice' ) ) {
				$original_price = ProductWrapper::getOriginalPrice( $post_id );
				if ( ( $original_price ) && ( $start_price != $original_price ) ) {
					$item->DiscountPriceInfo = new DiscountPriceInfoType();
					$item->DiscountPriceInfo->OriginalRetailPrice = new AmountType();
					$item->DiscountPriceInfo->OriginalRetailPrice->setTypeValue( $original_price );
					$item->DiscountPriceInfo->OriginalRetailPrice->setTypeAttribute('currencyID', $profile_details['currency'] );
				}
			}
		}

	



		return $item;
	} /* end of buildPrices() */


	public function buildImages( $id, $item, $post_id, $profile_details, $session ) {

		$images          = $this->getProductImagesURL( $post_id );
		$main_image      = $this->getProductMainImageURL( $post_id );
		if ( ( trim($main_image) == '' ) && ( sizeof($images) > 0 ) ) $main_image = $images[0];


		// handle product image
		$item->PictureDetails = new PictureDetailsType();
		$item->PictureDetails->setGalleryURL( $this->encodeUrl( $main_image ) );
		$item->PictureDetails->addPictureURL( $this->encodeUrl( $main_image ) );
		
		// handle gallery type
		$gallery_type = isset( $profile_details['gallery_type'] ) ? $profile_details['gallery_type'] : 'Gallery';
		$gallery_type = in_array( $gallery_type, array('Gallery','Plus','Featured') ) ? $gallery_type : 'Gallery';
		if ( $profile_details['with_gallery_image'] ) $item->PictureDetails->GalleryType = $gallery_type;
        

		return $item;
	} /* end of buildImages() */


	public function buildProductOptions( $id, $item, $post_id, $profile_details ) {

		// add SKU - omit if empty
		$product_sku = ProductWrapper::getSKU( $post_id );
		if ( trim( $product_sku ) == '' ) $product_sku = false;

		if ( $product_sku ) $item->SKU = $product_sku;

		// include prefilled info by default
		$include_prefilled_info = isset( $profile_details['include_prefilled_info'] ) ? (bool)$profile_details['include_prefilled_info'] : true;  

		// set UPC from SKU - if enabled
		if ( ($product_sku) && ( @$profile_details['use_sku_as_upc'] == '1' ) ) {
			$ProductListingDetails = new ProductListingDetailsType();
			$ProductListingDetails->setUPC( $product_sku );
			$ProductListingDetails->setListIfNoProduct( true );
			$ProductListingDetails->setIncludeStockPhotoURL( true );
			$ProductListingDetails->setIncludePrefilledItemInformation( $include_prefilled_info ? 1 : 0 );
			$ProductListingDetails->setUseFirstProduct( true );
			// $ProductListingDetails->setUseStockPhotoURLAsGallery( true );
			$item->setProductListingDetails( $ProductListingDetails );
		}

		// set UPC from product - if provided
		if ( $product_upc = get_post_meta( $post_id, '_ebay_upc', true ) ) {
			$ProductListingDetails = new ProductListingDetailsType();
			$ProductListingDetails->setUPC( $product_upc );
			$ProductListingDetails->setListIfNoProduct( true );
			$ProductListingDetails->setIncludeStockPhotoURL( true );
			$ProductListingDetails->setIncludePrefilledItemInformation( $include_prefilled_info ? 1 : 0 );
			$ProductListingDetails->setUseFirstProduct( true );
			$item->setProductListingDetails( $ProductListingDetails );
		}


		// add subtitle if enabled
		if ( @$profile_details['subtitle_enabled'] == 1 ) {
			
			// check if custom post meta field '_ebay_subtitle' exists
			if ( get_post_meta( $post_id, '_ebay_subtitle', true ) ) {
				$subtitle = get_post_meta( $post_id, '_ebay_subtitle', true );
			} elseif ( get_post_meta( $post_id, 'ebay_subtitle', true ) ) {
				$subtitle = get_post_meta( $post_id, 'ebay_subtitle', true );
			} else {
				// check for custom subtitle from profile
				$subtitle = @$profile_details['custom_subtitle'];
			}

			// if empty use product excerpt
			if ( $subtitle == '' ) {
				$the_post = get_post( $post_id );
				$subtitle = strip_tags( $the_post->post_excerpt );
			}
			
			// limit to 55 chars to avoid error
			$subtitle = substr( $subtitle, 0, 55 );

			$item->setSubTitle( $subtitle );			
			$this->logger->debug( 'setSubTitle: '.$subtitle );
		}

		// item condition description
		$condition_description = false;
		if ( @$profile_details['condition_description'] != '' ) {
			$condition_description =  $profile_details['condition_description'];
			$templatesModel = new TemplatesModel();
			$condition_description = $templatesModel->processAllTextShortcodes( $post_id, $condition_description );
			$item->setConditionDescription( $condition_description );
		}

		return $item;
	} /* end of buildProductOptions() */


	public function buildProfileOptions( $item, $profile_details ) {

		// Set Local Info
		$item->Currency = $profile_details['currency'];
		$item->Country = $profile_details['country'];
		$item->Location = $profile_details['location'];
		$item->DispatchTimeMax = $profile_details['dispatch_time'];

		// item condition
		if ( $profile_details['condition_id'] != 'none' ) {
			$item->ConditionID = $profile_details['condition_id'];
		}

		// postal code
		if ( $profile_details['postcode'] != '' ) {
			$item->PostalCode = $profile_details['postcode'];
		}

		// handle VAT (percent)
		if ( $profile_details['tax_mode'] == 'fix' ) {
			$item->VATDetails = new VATDetailsType();
			$item->VATDetails->VATPercent = floatval( $profile_details['vat_percent'] );
		}

		// use Sales Tax Table
		if ( $profile_details['tax_mode'] == 'ebay_table' ) {
			$item->UseTaxTable = true;
		}

		// private listing
		if ( @$profile_details['private_listing'] == 1 ) {
			$item->setPrivateListing( true );
		}

		// bold title
		if ( @$profile_details['bold_title'] == 1 ) {
			$item->setListingEnhancement('BoldTitle');
		}

		$item->setHitCounter( $profile_details['counter_style'] );
		// $item->setListingEnhancement('Highlight');



		return $item;
	} /* end of buildProfileOptions() */


	// schedule listing
	public function buildSchedule( $item, $profile_details ) {


		return $item;
	} /* end of buildSchedule() */


	public function buildPayment( $item, $profile_details ) {

		// Set Payment Methods
		// $item->PaymentMethods[] = 'PersonalCheck';
		// $item->PaymentMethods[] = 'PayPal';
		// $item->PayPalEmailAddress = 'youraccount@yourcompany.com';
		foreach ( $profile_details['payment_options'] as $payment_method ) {

			if ( $payment_method['payment_name'] == '' ) continue;			

			# BuyerPaymentMethodCodeType
			$item->addPaymentMethods( $payment_method['payment_name'] );
			if ( $payment_method['payment_name'] == 'PayPal' ) {
				$item->PayPalEmailAddress = get_option( 'wplister_paypal_email' );
			}
		}

        // handle require immediate payment option
        if ( @$profile_details['autopay'] == '1' ) {
			$item->setAutoPay( true );
        } else {
			$item->setAutoPay( 0 );        	
        }

		// ReturnPolicy
		$item->ReturnPolicy = new ReturnPolicyType();
		if ( $profile_details['returns_accepted'] == 1 ) {
			$item->ReturnPolicy->ReturnsAcceptedOption = 'ReturnsAccepted';
			$item->ReturnPolicy->ReturnsWithinOption   = $profile_details['returns_within'];
			$item->ReturnPolicy->Description           = stripslashes( $profile_details['returns_description'] );

			if ( ( isset($profile_details['RestockingFee']) ) && ( $profile_details['RestockingFee'] != '' ) ) {
				$item->ReturnPolicy->RestockingFeeValueOption = $profile_details['RestockingFee'];
			}

			if ( ( isset($profile_details['ShippingCostPaidBy']) ) && ( $profile_details['ShippingCostPaidBy'] != '' ) ) {
				$item->ReturnPolicy->ShippingCostPaidByOption = $profile_details['ShippingCostPaidBy'];
			}

			if ( ( isset($profile_details['RefundOption']) ) && ( $profile_details['RefundOption'] != '' ) ) {
				$item->ReturnPolicy->RefundOption = $profile_details['RefundOption'];
			}

		} else {
			$item->ReturnPolicy->ReturnsAcceptedOption = 'ReturnsNotAccepted';
		}			

		return $item;
	} /* end of buildPayment() */


	public function buildShipping( $id, $item, $post_id, $profile_details ) {

		// handle flat and calc shipping
		$this->logger->info('shipping_service_type: '.$profile_details['shipping_service_type'] );
		// $isFlat = $profile_details['shipping_service_type'] != 'calc' ? true : false;
		// $isCalc = $profile_details['shipping_service_type'] == 'calc' ? true : false;

		// handle flat and calc shipping (new version)
		$service_type = $profile_details['shipping_service_type'];
		if ( $service_type == '' )     $service_type = 'Flat';
		if ( $service_type == 'flat' ) $service_type = 'Flat';
		if ( $service_type == 'calc' ) $service_type = 'Calculated';
		$isFlatLoc = ( in_array( $service_type, array('Flat','FreightFlat','FlatDomesticCalculatedInternational') ) ) ? true : false;
		$isFlatInt = ( in_array( $service_type, array('Flat','FreightFlat','CalculatedDomesticFlatInternational') ) ) ? true : false;
		$hasWeight = ( in_array( $service_type, array('Calculated','FreightFlat','FlatDomesticCalculatedInternational','CalculatedDomesticFlatInternational') ) ) ? true : false;
		$isCalcLoc = ! $isFlatLoc;
		$isCalcInt = ! $isFlatInt;

		$shippingDetails = new ShippingDetailsType();
		$shippingDetails->ShippingType = $service_type;
		$this->logger->info('shippingDetails->ShippingType: '.$shippingDetails->ShippingType );

		// local shipping options
		$localShippingOptions = $profile_details['loc_shipping_options'];
		$this->logger->debug('localShippingOptions: '.print_r($localShippingOptions,1));

		$pr = 1;
		foreach ($localShippingOptions as $opt) {

			$price = $this->getDynamicShipping( $opt['price'], $post_id );
			$add_price = $this->getDynamicShipping( $opt['add_price'], $post_id );
			if ( $price == '' ) $price = 0;
			if ( $opt['service_name'] == '' ) continue;

			$ShippingServiceOptions = new ShippingServiceOptionsType();
			$ShippingServiceOptions->setShippingService( $opt['service_name'] );
			$ShippingServiceOptions->setShippingServicePriority($pr);
			
			// set shipping costs for flat services
			if ( $isFlatLoc ) {
				$ShippingServiceOptions->setShippingServiceCost( $price );		
				// FreeShipping is only allowed for the first shipping service
				if ( ( $price == 0 ) && ( $pr == 1 ) ) $ShippingServiceOptions->setFreeShipping( true );

				// price for additonal items
				if ( trim( $add_price ) == '' ) {
					$ShippingServiceOptions->setShippingServiceAdditionalCost( $price );
				} else {
					$ShippingServiceOptions->setShippingServiceAdditionalCost( $add_price );
				}				
			} else {
				// enable FreeShipping option for calculated shipping services if specified in profile or product meta
				$free_shipping_enabled = isset( $profile_details['shipping_loc_enable_free_shipping'] ) ? $profile_details['shipping_loc_enable_free_shipping'] : false;			
				$free_shipping_enabled = $free_shipping_enabled || get_post_meta( $post_id, '_ebay_shipping_loc_enable_free_shipping', true );
				if ( ( $free_shipping_enabled ) && ( $pr == 1 ) ) $ShippingServiceOptions->setFreeShipping( true );
			}

			$localShippingServices[]=$ShippingServiceOptions;
			$pr++;
			
			$EbayShippingModel = new EbayShippingModel();
			$lastShippingCategory = $EbayShippingModel->getShippingCategoryByServiceName( $opt['service_name'] );
			$this->logger->debug('ShippingCategory: '.print_r($lastShippingCategory,1));
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
				// if ( ( $price == '' ) || ( $opt['service_name'] == '' ) ) continue;
				if ( $price == '' ) $price = 0;
				if ( $opt['location'] == '' ) continue;
				if ( $opt['service_name'] == '' ) continue;

				$InternationalShippingServiceOptions = new InternationalShippingServiceOptionsType();
				$InternationalShippingServiceOptions->setShippingService( $opt['service_name'] );
				$InternationalShippingServiceOptions->setShippingServicePriority($pr);
				// $InternationalShippingServiceOptions->setShipToLocation( $opt['location'] );
				if ( is_array( $opt['location'] ) ) {
					foreach ( $opt['location'] as $location ) {
						$InternationalShippingServiceOptions->addShipToLocation( $location );
					}
				} else {
					$InternationalShippingServiceOptions->setShipToLocation( $opt['location'] );
				}

				$InternationalShippingServiceOptions->setShipToLocation( $opt['location'] );

				// set shipping costs for flat services
				if ( $isFlatInt ) {
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
		if ( isset( $intlShipping[0]['service_name'] ) && ( $intlShipping[0]['service_name'] != '' ) )
			$shippingDetails->setInternationalShippingServiceOption($shippingInternational,null);

		// set CalculatedShippingRate
		if ( $isCalcLoc || $isCalcInt ) {
			$calculatedShippingRate = new CalculatedShippingRateType();
			$calculatedShippingRate->setOriginatingPostalCode( $profile_details['postcode'] );
			
			// set ShippingPackage if calculated shipping is used
			if ( $isCalcInt ) $calculatedShippingRate->setShippingPackage( $profile_details['shipping_package'] );
			if ( $isCalcLoc ) $calculatedShippingRate->setShippingPackage( $profile_details['shipping_package'] );

			if ( $isCalcLoc ) {
				$calculatedShippingRate->setPackagingHandlingCosts( floatval( @$profile_details['PackagingHandlingCosts'] ) );	
			} 
			if ( $isCalcInt ) {
				$calculatedShippingRate->setPackagingHandlingCosts( floatval( @$profile_details['PackagingHandlingCosts'] ) );	
				$calculatedShippingRate->setInternationalPackagingHandlingCosts( floatval( @$profile_details['InternationalPackagingHandlingCosts'] ) );
			}

			list( $weight_major, $weight_minor ) = ProductWrapper::getEbayWeight( $post_id );
			$calculatedShippingRate->setWeightMajor( floatval( $weight_major) );
			$calculatedShippingRate->setWeightMinor( floatval( $weight_minor) );

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

		// set ShippingPackageDetails
		if ( $hasWeight ) {
			$shippingPackageDetails = new ShipPackageDetailsType();

			// set ShippingPackage if calculated shipping is used
			if ( $isCalcInt ) $shippingPackageDetails->setShippingPackage( $profile_details['shipping_package'] );
			if ( $isCalcLoc ) $shippingPackageDetails->setShippingPackage( $profile_details['shipping_package'] );
			
			list( $weight_major, $weight_minor ) = ProductWrapper::getEbayWeight( $post_id );
			$shippingPackageDetails->setWeightMajor( floatval( $weight_major) );
			$shippingPackageDetails->setWeightMinor( floatval( $weight_minor) );

			$dimensions = ProductWrapper::getDimensions( $post_id );
			if ( trim( @$dimensions['width']  ) != '' ) $shippingPackageDetails->setPackageWidth( $dimensions['width'] );
			if ( trim( @$dimensions['length'] ) != '' ) $shippingPackageDetails->setPackageLength( $dimensions['length'] );
			if ( trim( @$dimensions['height'] ) != '' ) $shippingPackageDetails->setPackageDepth( $dimensions['height'] );

			// debug
			// $weight = ProductWrapper::getWeight( $post_id ) ;
			// $this->logger->info('weight: '.print_r($weight,1));
			// $this->logger->info('dimensions: '.print_r($dimensions,1));

			$item->setShippingPackageDetails( $shippingPackageDetails );
		}


		// set local shipping discount profile
		if ( $isFlatLoc ) {
			$local_profile_id = isset( $profile_details['shipping_loc_flat_profile'] ) ?  $profile_details['shipping_loc_flat_profile'] : false;			
			if ( $custom_profile_id = get_post_meta( $post_id, '_ebay_shipping_loc_flat_profile', true ) ) $local_profile_id = $custom_profile_id;
		} else {
			$local_profile_id = isset( $profile_details['shipping_loc_calc_profile'] ) ?  $profile_details['shipping_loc_calc_profile'] : false;						
			if ( $custom_profile_id = get_post_meta( $post_id, '_ebay_shipping_loc_calc_profile', true ) ) $local_profile_id = $custom_profile_id;
		}
		if ( $local_profile_id ) {
			$shippingDetails->setShippingDiscountProfileID( $local_profile_id );
		}

		// set international shipping discount profile
		if ( $isFlatLoc ) {
			$int_profile_id = isset( $profile_details['shipping_int_flat_profile'] ) ?  $profile_details['shipping_int_flat_profile'] : false;			
			if ( $custom_profile_id = get_post_meta( $post_id, '_ebay_shipping_int_flat_profile', true ) ) $int_profile_id = $custom_profile_id;
		} else {
			$int_profile_id = isset( $profile_details['shipping_int_calc_profile'] ) ?  $profile_details['shipping_int_calc_profile'] : false;						
			if ( $custom_profile_id = get_post_meta( $post_id, '_ebay_shipping_int_calc_profile', true ) ) $int_profile_id = $custom_profile_id;
		}
		if ( $int_profile_id ) {
			$shippingDetails->setInternationalShippingDiscountProfileID( $int_profile_id );
		}

		// PromotionalShippingDiscount
		$PromotionalShippingDiscount = isset( $profile_details['PromotionalShippingDiscount'] ) ?  $profile_details['PromotionalShippingDiscount'] : false;						
		if ( $PromotionalShippingDiscount == '1' )
			$shippingDetails->setPromotionalShippingDiscount( true );

		// InternationalPromotionalShippingDiscount
		$InternationalPromotionalShippingDiscount = isset( $profile_details['InternationalPromotionalShippingDiscount'] ) ?  $profile_details['InternationalPromotionalShippingDiscount'] : false;						
		if ( $InternationalPromotionalShippingDiscount == '1' ) 
			$shippingDetails->setInternationalPromotionalShippingDiscount( true );


		// ShipToLocations 
		if ( is_array( $ShipToLocations = maybe_unserialize( $profile_details['ShipToLocations'] ) ) ) {
			foreach ( $ShipToLocations as $location ) {
				$item->addShipToLocations( $location );
			}
		}

		// ExcludeShipToLocations 
		if ( is_array( $ExcludeShipToLocations = maybe_unserialize( $profile_details['ExcludeShipToLocations'] ) ) ) {
			foreach ( $ExcludeShipToLocations as $location ) {
				$shippingDetails->addExcludeShipToLocation( $location );
			}
		}

		// global shipping
		if ( @$profile_details['global_shipping'] == 1 ) {
			$shippingDetails->setGlobalShipping( true ); // available since api version 781
		}
		if ( get_post_meta( $post_id, '_ebay_global_shipping', true ) == 'yes' ) {
			$shippingDetails->setGlobalShipping( true );
		}

		// Payment Instructions
		if ( trim( @$profile_details['payment_instructions'] ) != '' ) {
			$shippingDetails->setPaymentInstructions( nl2br( $profile_details['payment_instructions'] ) );
		}
		if ( trim( get_post_meta( $post_id, '_ebay_payment_instructions', true ) ) != '' ) {
			$shippingDetails->setPaymentInstructions( nl2br( get_post_meta( $post_id, '_ebay_payment_instructions', true ) ) );
		}

		// COD cost
		if ( isset( $profile_details['cod_cost'] ) && trim( $profile_details['cod_cost'] ) ) {
			$shippingDetails->setCODCost( str_replace( ',', '.', $profile_details['cod_cost'] ) );
		}
		
		// check if we have local pickup only
		if ( ( count($localShippingOptions) == 1 ) && ( $lastShippingCategory == 'PICKUP' ) ) {

			$item->setShipToLocations( 'None' );
			$item->setDispatchTimeMax( null );
			$this->logger->info('PICKUP ONLY mode');

			// don't set ShippingDetails for pickup-only in UK!
			if ( $item->Site != 'UK' ) {
				$item->setShippingDetails($shippingDetails);
			}

		} else {
			$item->setShippingDetails($shippingDetails);
		}


		return $item;

	} /* end of buildShipping() */

	public function buildItemSpecifics( $id, $item, $listing, $post_id ) {

    	// new ItemSpecifics
    	$ItemSpecifics = new NameValueListArrayType();

		// get listing data
		// $listing = $this->lm->getItem( $id );

		// get product attributes
		$processed_attributes = array();
        $attributes = ProductWrapper::getAttributes( $post_id );
		$this->logger->info('product attributes: '. ( sizeof($attributes)>0 ? print_r($attributes,1) : '-- empty --' ) );

		// apply item specifics from profile
		$specifics = $listing['profile_data']['details']['item_specifics'];

		// merge item specifics from product
		$product_specifics = get_post_meta( $post_id, '_ebay_item_specifics', true );
		if ( ! empty($product_specifics) )
			$specifics = array_merge( $specifics, $product_specifics ); 

		$this->logger->debug('item_specifics: '.print_r($specifics,1));
        foreach ($specifics as $spec) {
        	if ( $spec['value'] != '' ) {

        		// fixed value
        		$value = $spec['value'];
        		$value = html_entity_decode( $value, ENT_QUOTES );
        		if ( $this->mb_strlen( $value ) > 50 ) continue;

	            $NameValueList = new NameValueListType();
		    	$NameValueList->setName ( $spec['name']  );
	    		$NameValueList->setValue( $value );
	        	if ( ! in_array( $spec['name'], $this->variationAttributes ) ) {
		        	$ItemSpecifics->addNameValueList( $NameValueList );
		        	$processed_attributes[] = $spec['name'];
					$this->logger->info("specs: added custom value: {$spec['name']} - $value");
	        	}

        	} elseif ( $spec['attribute'] != '' ) {

        		// pull value from product attribute
        		$value = isset( $attributes[ $spec['attribute'] ] ) ? $attributes[ $spec['attribute'] ] : '';
        		$value = html_entity_decode( $value, ENT_QUOTES );

        		// process custom attributes
        		$custom_attributes = apply_filters( 'wplister_custom_attributes', array() );
        		foreach ( $custom_attributes as $attrib ) {
        			if ( $spec['attribute'] == $attrib['id'] ) {
        				$value = get_post_meta( $post_id, $attrib['meta_key'], true );
        			}
        		}
        		// if ( '_sku' == $spec['attribute'] ) $value = ProductWrapper::getSKU( $post_id );
        		if ( $this->mb_strlen( $value ) > 50 ) continue;

	            $NameValueList = new NameValueListType();
		    	$NameValueList->setName ( $spec['name']  );
	    		$NameValueList->setValue( $value );
	        	if ( ! in_array( $spec['name'], $this->variationAttributes ) ) {
		        	$ItemSpecifics->addNameValueList( $NameValueList );
		        	$processed_attributes[] = $spec['attribute'];
					$this->logger->info("specs: added product attribute: {$spec['name']} - $value");
	        	}
        	}
        }

        // skip if item has no attributes
        // if ( count($attributes) == 0 ) return $item;

    	// add ItemSpecifics from product attributes
    	// disabled for now, since it causes duplicates and it's not actually required anymore
    	// enabled again - mostly for free version
    	// TODO: make this an option (globally?)
        foreach ($attributes as $name => $value) {

    		$value = html_entity_decode( $value, ENT_QUOTES );
    		if ( $this->mb_strlen( $value ) > 50 ) continue;

            $NameValueList = new NameValueListType();
	    	$NameValueList->setName ( $name  );
    		$NameValueList->setValue( $value );
        	
        	// only add attribute to ItemSpecifics if not already present in variations or processed attributes
        	if ( ( ! in_array( $name, $this->variationAttributes ) ) && ( ! in_array( $name, $processed_attributes ) ) ) {
	        	$ItemSpecifics->addNameValueList( $NameValueList );
        	}
        }

        if ( count($ItemSpecifics) > 0 ) {
    		$item->setItemSpecifics( $ItemSpecifics );        	
			$this->logger->info( count($ItemSpecifics) . " item specifics were added.");
        }

		return $item;

	} /* end of buildItemSpecifics() */

	public function buildVariations( $id, $item, $profile_details, $listing, $session ) {

		// build variations
		$item->Variations = new VariationsType();

		// get product variations
		// $listing = $this->lm->getItem( $id );
        $variations = ProductWrapper::getVariations( $listing['post_id'] );

        // get max_quantity from profile
        $max_quantity = ( isset( $profile_details['max_quantity'] ) && intval( $profile_details['max_quantity'] )  > 0 ) ? $profile_details['max_quantity'] : PHP_INT_MAX ; 

        // loop each combination
        foreach ($variations as $var) {

        	$newvar = new VariationType();

        	// handle price
			$newvar->StartPrice = $this->lm->applyProfilePrice( $var['price'], $profile_details['start_price'] );

        	// handle variation quantity - if no quantity set in profile
        	// if ( intval( $item->Quantity ) == 0 ) {
        	if ( intval( $profile_details['quantity'] ) == 0 ) {
        		$newvar->Quantity   = min( $max_quantity, intval( $var['stock'] ) );
        	} else {
	        	$newvar->Quantity 	= min( $max_quantity, $item->Quantity ); // should be removed in future versions
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
        // $this->logger->info('variationAttributes: '.print_r($this->variationAttributes,1));


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

    			$image_url = $this->encodeUrl( $var['image'] );


				if ( ! $image_url ) continue;
				$this->logger->info( "using variation image: ".$image_url );

		    	$VariationSpecificPictureSet = new VariationSpecificPictureSetType();
    	    	$VariationSpecificPictureSet->setVariationSpecificValue( $VariationValue );
        		$VariationSpecificPictureSet->addPictureURL( $image_url );

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

		// $isCalc = $profile_details['shipping_service_type'] == 'calc' ? true : false;
		$service_type = $profile_details['shipping_service_type'];
		$isCalc = ( in_array( $service_type, array('calc','FlatDomesticCalculatedInternational' ,'CalculatedDomesticFlatInternational') ) ) ? true : false;

		if ( $isCalc ) {

			// get weight and dimensions from first variation
            $first_variation = reset( $variations );
			$weight_major = $first_variation['weight_major'];
			$weight_minor = $first_variation['weight_minor'];
			$dimensions   = $first_variation['dimensions'];

			$item->ShippingDetails->CalculatedShippingRate->setWeightMajor( floatval( $weight_major ) );
			$item->ShippingDetails->CalculatedShippingRate->setWeightMinor( floatval( $weight_minor ) );

			if ( trim( @$dimensions['width']  ) != '' ) $item->ShippingDetails->CalculatedShippingRate->setPackageWidth( $dimensions['width'] );
			if ( trim( @$dimensions['length'] ) != '' ) $item->ShippingDetails->CalculatedShippingRate->setPackageLength( $dimensions['length'] );
			if ( trim( @$dimensions['height'] ) != '' ) $item->ShippingDetails->CalculatedShippingRate->setPackageDepth( $dimensions['height'] );

			// update ShippingPackageDetails with weight and dimensions of first variations
			$shippingPackageDetails = new ShipPackageDetailsType();
			$shippingPackageDetails->setWeightMajor( floatval( $weight_major) );
			$shippingPackageDetails->setWeightMinor( floatval( $weight_minor) );
			if ( trim( @$dimensions['width']  ) != '' ) $shippingPackageDetails->setPackageWidth( $dimensions['width'] );
			if ( trim( @$dimensions['length'] ) != '' ) $shippingPackageDetails->setPackageLength( $dimensions['length'] );
			if ( trim( @$dimensions['height'] ) != '' ) $shippingPackageDetails->setPackageDepth( $dimensions['height'] );
			$item->setShippingPackageDetails( $shippingPackageDetails );

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

	} /* end of buildVariations() */

	public function getVariationImages( $post_id ) {

		// check if product has variations
        if ( ! ProductWrapper::hasVariations( $post_id ) ) return array();

		// get variations
        $variations = ProductWrapper::getVariations( $post_id );
        $variation_images = array();

        foreach ( $variations as $var ) {

        	if ( ! in_array( $var['image'], $variation_images ) ) {
        		$variation_images[] = $this->removeHttpsFromUrl( $var['image'] );
        	}

        }
		$this->logger->info("variation images: ".print_r($variation_images,1));

        return $variation_images;
	}


	public function flattenVariations( $id, $item, $post_id, $profile_details ) {
		$this->logger->info("flattenVariations($id)");

		// get product variations
		// $p = $this->lm->getItem( $id );
        $variations      = ProductWrapper::getVariations( $post_id );
        $this->variationAttributes = array();
        $total_stock = 0;

        // find default variation
        $default_variation = reset( $variations );
        foreach ( $variations as $var ) {

	        // find default variation
        	if ( $var['is_default'] ) $default_variation = $var;

		    // build array of variation attributes, which will be needed in builtItemSpecifics()
            foreach ($var['variation_attributes'] as $name => $value) {
	        	$this->variationAttributes[] = $name;
	        }

	        // count total stock
	        $total_stock += $var['stock'];
        }

        // list accumulated stock quantity if not set in profile
        if ( ! $item->Quantity )
        	$item->Quantity = $total_stock;

		// fetch default variations start price
		if ( intval($item->StartPrice->value) == 0 ) {

			$start_price = $default_variation['price'];
			$start_price = $this->lm->applyProfilePrice( $start_price, $profile_details['start_price'] );
			$item->StartPrice->setTypeValue( $start_price );
			$this->logger->info("using default variations price: ".print_r($item->StartPrice->value,1));

		}


    	// ebay doesn't allow different weight and dimensions for varations
    	// so for calculated shipping services we just fetch those from the default variation
    	// and overwrite 

		// $isCalc = $profile_details['shipping_service_type'] == 'calc' ? true : false;
		$service_type = $profile_details['shipping_service_type'];
		$isCalc    = ( in_array( $service_type, array('calc','FlatDomesticCalculatedInternational' ,'CalculatedDomesticFlatInternational') ) ) ? true : false;
		$hasWeight = ( in_array( $service_type, array('calc','FreightFlat','FlatDomesticCalculatedInternational','CalculatedDomesticFlatInternational') ) ) ? true : false;

		if ( $isCalc ) {

			// get weight and dimensions from default variation
			$weight_major = $default_variation['weight_major'];
			$weight_minor = $default_variation['weight_minor'];
			$dimensions   = $default_variation['dimensions'];

			$item->ShippingDetails->CalculatedShippingRate->setWeightMajor( floatval( $weight_major ) );
			$item->ShippingDetails->CalculatedShippingRate->setWeightMinor( floatval( $weight_minor ) );

			if ( trim( @$dimensions['width']  ) != '' ) $item->ShippingDetails->CalculatedShippingRate->setPackageWidth( $dimensions['width'] );
			if ( trim( @$dimensions['length'] ) != '' ) $item->ShippingDetails->CalculatedShippingRate->setPackageLength( $dimensions['length'] );
			if ( trim( @$dimensions['height'] ) != '' ) $item->ShippingDetails->CalculatedShippingRate->setPackageDepth( $dimensions['height'] );

			// debug
			$this->logger->info('default variations weight: '.print_r($weight,1));
			$this->logger->info('default variations dimensions: '.print_r($dimensions,1));
		}

		// set ShippingPackageDetails
		if ( $hasWeight ) {
			
			// get weight and dimensions from default variation
			$weight_major = $default_variation['weight_major'];
			$weight_minor = $default_variation['weight_minor'];
			$dimensions   = $default_variation['dimensions'];

			$shippingPackageDetails = new ShipPackageDetailsType();
			$shippingPackageDetails->setWeightMajor( floatval( $weight_major) );
			$shippingPackageDetails->setWeightMinor( floatval( $weight_minor) );

			if ( trim( @$dimensions['width']  ) != '' ) $shippingPackageDetails->setPackageWidth( $dimensions['width'] );
			if ( trim( @$dimensions['length'] ) != '' ) $shippingPackageDetails->setPackageLength( $dimensions['length'] );
			if ( trim( @$dimensions['height'] ) != '' ) $shippingPackageDetails->setPackageDepth( $dimensions['height'] );

			$item->setShippingPackageDetails( $shippingPackageDetails );
		}

		return $item;

	} /* end of flattenVariations() */



	public function checkItem( $item, $reviseItem = false ) {

		$success = true;
		$this->VariationsHaveStock = false;


		// check StartPrice, Quantity and SKU
		if ( is_object( $item->Variations ) ) {
			// item has variations

			$VariationsHaveStock = false;
			$VariationsSkuArray = array();
			$VariationsSkuAreUnique = true;
			$VariationsSkuMissing = false;

			// check each variation
			foreach ($item->Variations->Variation as $var) {
				
				// StartPrice must be greater than 0
				if ( floatval($var->StartPrice) == 0 ) {
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
				} else {
					$VariationsSkuMissing = true;
				}

				// VariationSpecifics values can't be longer than 50 characters
				foreach ($var->VariationSpecifics->NameValueList as $spec) {
					if ( strlen($spec->Value) > 50 ) {
						$longMessage = __('eBay does not allow attribute values longer than 50 characters.','wplister');
						$longMessage .= '<br>';
						$longMessage .= __('You need to shorten this value:','wplister') . ' <code>'.$spec->Value.'</code>';
						$success = false;
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

			if ( $VariationsSkuMissing ) {
				$longMessage = __('Some variations are missing a SKU.','wplister');
				$longMessage .= '<br>';
				$longMessage .= __('It is required to assign a unique SKU to each variation to prevent inventory sync issues.','wplister');
				// $success = false;
			}

			if ( ! $VariationsHaveStock && ! $reviseItem ) {
				$longMessage = __('None of these variations are in stock.','wplister');
				$success = false;
			}

			// make this info available to reviseItem()
			$this->VariationsHaveStock = $VariationsHaveStock;

		} else {
			// item has no variations

			// StartPrice must be greater than 0
			if ( floatval($item->StartPrice) == 0 ) {
				$longMessage = __('Price can not be zero.','wplister');
				$success = false;
			}

			// check minimum start price if found
			$min_prices = get_option( 'wplister_MinListingStartPrices', array() );
			$listing_type = $item->ListingType ? $item->ListingType : 'FixedPriceItem';
			if ( isset( $min_prices[ $listing_type ] ) ) {
				$min_price = $min_prices[ $listing_type ];
				if ( $item->StartPrice->value < $min_price ) {
					$longMessage = sprintf( __('eBay requires a minimum price of %s for this listing type.','wplister'), $min_price );
					$success = false;
				}
			}

		}

		// ItemSpecifics values can't be longer than 50 characters
		foreach ($item->ItemSpecifics->NameValueList as $spec) {
			if ( strlen($spec->Value) > 50 ) {
				$longMessage = __('eBay does not allow attribute values longer than 50 characters.','wplister');
				$longMessage .= '<br>';
				$longMessage .= __('You need to shorten this value:','wplister') . ' <code>'.$spec->Value.'</code>';
				$success = false;
			}
		}

		// PrimaryCategory->CategoryID must be greater than 0
		if ( intval( @$item->PrimaryCategory->CategoryID ) == 0 ) {
			$longMessage = __('There has been no primary category assigned.','wplister');
			$success = false;
		}

		// check for main image
		if ( trim( @$item->PictureDetails->GalleryURL ) == '' ) {
			$longMessage = __('You need to add at least one image to your product.','wplister');
			$success = false;
		}

		if ( ! $success && ! $this->is_ajax() ) {
			$this->showMessage( $longMessage, 1, true );
		} elseif ( ( $longMessage != '' ) && ! $this->is_ajax() ) {
			$this->showMessage( $longMessage, 0, true );
		}

		$htmlMsg  = '<div id="message" class="error" style="display:block !important;"><p>';
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
		$title = html_entity_decode( $title, ENT_QUOTES, 'UTF-8' );

        // limit item title to 80 characters
        if ( $this->mb_strlen($title) > 80 ) $title = $this->mb_substr( $title, 0, 77 ) . '...';

		$this->logger->info('prepareTitle() out: ' . $title );
		return $title;
	}
	

	public function getFinalHTML( $id, $ItemObj ) {
		
		// get item data
		$item = $this->lm->getItem( $id );

		// use latest post_content from product
		$post = get_post( $item['post_id'] );
		if ( ! empty($post->post_content) ) $item['post_content'] = $post->post_content;

		// load template
		$template = new TemplatesModel( $item['template'] );
		$html = $template->processItem( $item, $ItemObj );

		// strip invalid XML characters
		$html = $this->stripInvalidXml( $html );

		// return html
		return $html;
	}

	public function getPreviewHTML( $template_id, $id = false ) {
		
		// get item data
		$item = $this->lm->getItemForPreview();
		if ( ! $item ) {
			return '<div style="text-align:center; margin-top:5em;">You need to prepare at least one listing in order to preview a listing template.</div>';
		}

		// use latest post_content from product
		$post = get_post( $item['post_id'] );
		if ( ! empty($post->post_content) ) $item['post_content'] = $post->post_content;

		// load template
		if ( ! $template_id ) $template_id = $item['template'];
		$template = new TemplatesModel( $template_id );
		$html = $template->processItem( $item, false, true );

		// return html
		return $html;
	}


	public function getProductMainImageURL( $post_id, $checking_parent = false ) {

		// check if custom post meta field '_ebay_gallery_image_url' exists
		if ( get_post_meta( $post_id, '_ebay_gallery_image_url', true ) ) {
			return $this->removeHttpsFromUrl( get_post_meta( $post_id, '_ebay_gallery_image_url', true ) );
		}
		// check if custom post meta field 'ebay_image_url' exists
		if ( get_post_meta( $post_id, 'ebay_image_url', true ) ) {
			return $this->removeHttpsFromUrl( get_post_meta( $post_id, 'ebay_image_url', true ) );
		}

		// this seems to be neccessary for listing previews on some installations 
		if ( ! function_exists('get_post_thumbnail_id')) 
		require_once( ABSPATH . 'wp-includes/post-thumbnail-template.php');

		// get main product image (post thumbnail)
		$large_image_url = ProductWrapper::getImageURL( $post_id );
		// if ( $large_image_url ) {
			$image_url = $large_image_url;
		// } else {
		// 	$images = $this->getProductImagesURL( $post_id ); // disabled as it could lead to infinite recursion issue
		// 	$image_url = @$images[0];
		// }

		// check if featured image comes from nextgen gallery
		if ( $this->is_plugin_active('nextgen-gallery/nggallery.php') ) {
			$thumbnail_id = get_post_meta($post_id, '_thumbnail_id', true);
			if ( 'ngg' == substr($thumbnail_id, 0, 3) ) {
				$imageID = str_replace('ngg-', '', $thumbnail_id);
				$picture = nggdb::find_image($imageID);
				$image_url = $picture->imageURL;
				// $this->logger->info( "NGG - picture: " . print_r($picture,1) );
				$this->logger->info( "NGG - image_url: " . print_r($image_url,1) );
			}
		}

		// filter image_url hook
		$image_url = apply_filters( 'wplister_get_product_main_image', $image_url, $post_id );

		// if no main image found, check parent product
		if ( ( $image_url == '' ) && ( ! $checking_parent ) ) {
			// $parents = get_post_ancestors( $post_id );
			$post = get_post($post_id);
			$parent_id = isset($post->post_parent) ? $post->post_parent : false;
			if ( $parent_id ) {
				return $this->getProductMainImageURL( $parent_id, true);
			}
		}

		// ebay doesn't accept https - only http and ftp
		$image_url = $this->removeHttpsFromUrl( $image_url );
		
		return $image_url;

	}

	public function getProductImagesURL( $id ) {
		global $wpdb;

    	$results = $wpdb->get_results( 
			"
			SELECT id, guid 
			FROM {$wpdb->prefix}posts
			WHERE post_type = 'attachment' 
			  AND post_parent = '$id' 
			ORDER BY menu_order
			"
		);
		$this->logger->debug( "getProductImagesURL( $id ) : " . print_r($results,1) );
        #echo "<pre>";print_r($results);echo"</pre>";#die();

		// fetch images using default size
		$size = get_option( 'wplister_default_image_size', 'full' );
		
		$images = array();
		foreach($results as $row) {
            // $url = wp_get_attachment_url( $row->id, $size );
            $url = $row->guid ? $row->guid : wp_get_attachment_url( $row->id, $size );
			$images[] = $url;
		}

		// support for WooCommerce 2.0 Product Gallery
		if ( get_option( 'wplister_wc2_gallery_fallback','attached' ) == 'none' ) $images = array(); // discard images if fallback is disabled
		$product_image_gallery = get_post_meta( $id, '_product_image_gallery', true );

		// use parent product for single (split) variation
		if ( ProductWrapper::isSingleVariation( $id ) ) {
			$parent_id = ProductWrapper::getVariationParent( $id );
			$product_image_gallery = get_post_meta( $parent_id, '_product_image_gallery', true );
		}

		if ( $product_image_gallery ) {
			
			// build clean array with main image as first item
			$images = array();
			$images[] = $this->getProductMainImageURL( $id );

			$image_ids = explode(',', $product_image_gallery );
			foreach ( $image_ids as $image_id ) {
	            $url = wp_get_attachment_url( $image_id, $size );
				if ( $url && ! in_array($url, $images) ) $images[] = $url;
			}
			
			$this->logger->info( "found WC2 product gallery images for product #$id " . print_r($images,1) );
		}

		// Shopp stores images in db by default...
		if ( ProductWrapper::plugin == 'shopp' ) {
			$images = ProductWrapper::getAllImages( $id );
			// $this->logger->info( "SHOPP - getAllImages( $id ) : " . print_r($images,1) );
		}

		$product_images = array();
		foreach($images as $imageurl) {
			$product_images[] = $this->removeHttpsFromUrl( $imageurl );
		}

		// call wplister_product_images filter 
		// hook into this from your WP theme's functions.php - this won't work in listing templates!
		$product_images = apply_filters( 'wplister_product_images', $product_images, $id );

		return $product_images;
	}


	// ebay doesn't accept image urls using https - only http and ftp
	function removeHttpsFromUrl( $url ) {

		// fix relative urls
		if ( '/wp-content/' == substr( $url, 0, 12 ) ) {
			$url = str_replace('/wp-content', content_url(), $url);
		}

		// fix https urls
		$url = str_replace( 'https://', 'http://', $url );
		$url = str_replace( ':443', '', $url );

		return $url;
	}
	
	// encode special characters and spaces for PictureURL
	function encodeUrl( $url ) {
		$url = rawurlencode( $url );
		// $url = str_replace(' ', '%20', $url );
		$url = str_replace('%2F', '/', $url );
		$url = str_replace('%3A', ':', $url );
		return $url;
	}

	// Removes invalid XML characters
	// Not all valid utf-8 characters are allowed in XML documents. For XML 1.0 the standard says:
	// Char ::= #x9 | #xA | #xD | [#x20-#xD7FF] | [#xE000-#xFFFD] | [#x10000-#x10FFFF]
	function stripInvalidXml( $value ) {
	    $ret = "";
	    $current;
	    if (empty($value))
	        return $ret;

	    $length = strlen($value);
	    for ($i=0; $i < $length; $i++) {

	        $current = ord($value{$i});
	        if (($current == 0x9) ||
	            ($current == 0xA) ||
	            ($current == 0xD) ||
	            (($current >= 0x20) && ($current <= 0xD7FF)) ||
	            (($current >= 0xE000) && ($current <= 0xFFFD)) ||
	            (($current >= 0x10000) && ($current <= 0x10FFFF))) {

	            $ret .= chr($current);

	        } else {

	            $ret .= " ";

	        }
	    }

	    return $ret;	    
	} // stripInvalidXml()

} // class ItemBuilderModel
