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
	var $variationSplitAttributes = array();
	var $tmpVariationSpecificsSet = array();
	var $result = false;
	var $site_id = null;
	var $listing_id = null;
	var $account_id = null;

	function ItemBuilderModel()
	{
		// global $wpl_logger;
		// $this->logger = &$wpl_logger;

		// provide listings model
		$this->lm = new ListingsModel();
	}



	function buildItem( $id, $session, $reviseItem = false, $preview = false )
	{

		// fetch record from db
		$listing         = ListingsModel::getItem( $id );
		$post_id 		 = $listing['post_id'];
		$profile_details = $listing['profile_data']['details'];
		$hasVariations   = ProductWrapper::hasVariations( $post_id );
		$isVariation     = ProductWrapper::isSingleVariation( $post_id );
			
		// remember listing id and account id for checkItem() and buildPayment()
		$this->listing_id = $id;
		$this->account_id = $listing['account_id'];

		// adjust profile details from product level options
		$profile_details = $this->adjustProfileDetails( $id, $post_id, $profile_details );


		// create Item
		$item = new ItemType();

		// set quantity
		// $item->Quantity = $listing['quantity'];

		// get current quantity from WooCommerce
		$woocom_stock   = ProductWrapper::getStock( $post_id );

        // get max_quantity from profile
        $max_quantity   = ( isset( $profile_details['max_quantity'] ) && intval( $profile_details['max_quantity'] )  > 0 ) ? $profile_details['max_quantity'] : PHP_INT_MAX ; 
		$item->Quantity = min( $max_quantity, intval( $woocom_stock ) );

        // handle fixed quantity
    	if ( intval( $profile_details['quantity'] ) > 0 ) {
        	$item->Quantity = $profile_details['quantity'];
    	}
    	if ( $item->Quantity < 0 ) $item->Quantity = 0; // prevent error for negative qty


		// set listing title
		$item->Title = $this->prepareTitle( $listing['auction_title'] );

		// set listing duration
		$product_listing_duration = get_post_meta( $post_id, '_ebay_listing_duration', true );
		$item->ListingDuration = $product_listing_duration ? $product_listing_duration : $listing['listing_duration'];

		// omit ListingType when revising item
		if ( ! $reviseItem ) {
			$product_listing_type = get_post_meta( $post_id, '_ebay_auction_type', true );
			$ListingType = $product_listing_type ? $product_listing_type : $listing['auction_type'];

			// handle classified ads
			if ( $ListingType == 'ClassifiedAd' ) {
				$ListingType = 'LeadGeneration';
				$item->setListingSubtype2( 'ClassifiedAd' );
			}
			$item->setListingType( $ListingType );
		}


		// set eBay Site
		$item = $this->setEbaySite( $item, $session );			

		// add prices
		$item = $this->buildPrices( $id, $item, $post_id, $profile_details, $listing );			

		// add images
		$item = $this->buildImages( $id, $item, $post_id, $profile_details, $session );			


		// if this is a split variation, use parent post_id for all further processing
		if ( $isVariation ) {

			// prepare item specifics / variation attributes
			$this->prepareSplitVariation( $id, $post_id, $listing );	

			// use parent post_id for all further processing
			$post_id = ProductWrapper::getVariationParent( $post_id );
		}


		// add various options from $profile_details
		$item = $this->buildProfileOptions( $item, $profile_details );			

		// add various options that depend on $profile_details and $post_id
		$item = $this->buildProductOptions( $id, $item, $post_id, $profile_details, $listing, $hasVariations, $isVariation );			

		// add payment and return options
		$item = $this->buildPayment( $item, $profile_details );			

		// add shipping services and options
		$item = $this->buildShipping( $id, $item, $post_id, $profile_details, $listing, $isVariation );			

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

		// add part compatibility list
		$item = $this->buildCompatibilityList( $id, $item, $listing, $post_id );			

		// set listing description - after $item has been built
		$item->Description = $this->getFinalHTML( $id, $item, $preview );


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
			WPLE()->logger->info('UUID src: '.$uuid_src);
		}

		// filter final item object before it's sent to eBay
		$item = apply_filters( 'wplister_filter_listing_item', $item, $listing, $profile_details, $post_id );
		$item = apply_filters( 'wple_filter_listing_item', $item, $listing, $profile_details, $post_id, $reviseItem );

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
			// (disabled for now - eBay does seem to allow title changes when an item has sales)
			// if ( $listing['quantity_sold'] > 0 ) {
			// 	$item->setTitle( null );
			// 	$item->setSubTitle( null );
			// }

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

		// remember site_id for checkItem()	
		$this->site_id = $site_id;

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
			// WPLE()->logger->info('terms: '.print_r($terms,1));

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

			WPLE()->logger->info('mapped primary_category_id: '.$primary_category_id);
			WPLE()->logger->info('mapped secondary_category_id: '.$secondary_category_id);

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

		// if no secondary category, set to zero
		if ( ! $item->SecondaryCategory->CategoryID ) {
			$item->SecondaryCategory = new CategoryType();
			$item->SecondaryCategory->CategoryID = 0;			
		}		


		// handle optional store category
		$store_category_1_id = get_post_meta( $post_id, '_ebay_store_category_1_id', true );
		if ( intval( $store_category_1_id ) > 0 ) {
			$item->Storefront = new StorefrontType();
			$item->Storefront->StoreCategoryID = $store_category_1_id;
		} elseif ( intval($profile_details['store_category_1_id']) > 0 ) {
			$item->Storefront = new StorefrontType();
			$item->Storefront->StoreCategoryID = $profile_details['store_category_1_id'];
		} else {
			// get store categories map
			$categories_map_store = get_option( 'wplister_categories_map_store' );

			// fetch products local category terms
			$terms = wp_get_post_terms( $post_id, ProductWrapper::getTaxonomy() );
			// WPLE()->logger->info('terms: '.print_r($terms,1));

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

			WPLE()->logger->info('mapped primary_store_category_id: '.$primary_store_category_id);
			WPLE()->logger->info('mapped secondary_store_category_id: '.$secondary_store_category_id);

            if ( intval( $primary_store_category_id ) > 0 ) {
				$item->Storefront = new StorefrontType();
				$item->Storefront->StoreCategoryID = $primary_store_category_id;
            }

            if ( intval( $secondary_store_category_id ) > 0 ) {
				$item->Storefront->StoreCategory2ID = $secondary_store_category_id;
            }
            
		}

		// optional secondary store category - from profile
		if ( intval($profile_details['store_category_2_id']) > 0 ) {
			$item->Storefront->StoreCategory2ID = $profile_details['store_category_2_id'];
		}

		// optional secondary store category - from product
		$store_category_2_id = get_post_meta( $post_id, '_ebay_store_category_2_id', true );
		if ( intval($store_category_2_id) > 0 ) {
			$item->Storefront->StoreCategory2ID = $store_category_2_id;
		}


		// adjust Site if required - eBay Motors (beta)
		if ( $item->Site == 'US' ) {
			// if primary category's site_id is 100, set Site to eBayMotors
			$primary_category = EbayCategoriesModel::getItem( $item->PrimaryCategory->CategoryID );
			if ( $primary_category['site_id'] == 100 ) {
				$item->setSite('eBayMotors');
			}
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

		// check for custom product level bestoffer options
		if ( get_post_meta( $post_id, '_ebay_bestoffer_enabled', true ) )
			$profile_details['bestoffer_enabled']			= get_post_meta( $post_id, '_ebay_bestoffer_enabled', true );
		if ( get_post_meta( $post_id, '_ebay_bo_autoaccept_price', true ) )
			$profile_details['bo_autoaccept_price']			= get_post_meta( $post_id, '_ebay_bo_autoaccept_price', true );
		if ( get_post_meta( $post_id, '_ebay_bo_minimum_price', true ) )
			$profile_details['bo_minimum_price']			= get_post_meta( $post_id, '_ebay_bo_minimum_price', true );

		// check for custom product level autopay options
		if ( get_post_meta( $post_id, '_ebay_autopay', true ) )
			$profile_details['autopay']						= get_post_meta( $post_id, '_ebay_autopay', true );

		// check for custom product level seller profiles
		// if ( get_post_meta( $post_id, '_ebay_seller_shipping_profile_id', true ) )
		// 	$profile_details['seller_shipping_profile_id']			= get_post_meta( $post_id, '_ebay_seller_shipping_profile_id', true );
		if ( get_post_meta( $post_id, '_ebay_seller_payment_profile_id', true ) )
			$profile_details['seller_payment_profile_id']			= get_post_meta( $post_id, '_ebay_seller_payment_profile_id', true );
		if ( get_post_meta( $post_id, '_ebay_seller_return_profile_id', true ) )
			$profile_details['seller_return_profile_id']			= get_post_meta( $post_id, '_ebay_seller_return_profile_id', true );

		// check for custom product level shipping options - if enabled
		$product_shipping_service_type = get_post_meta( $post_id, '_ebay_shipping_service_type', true );
		if ( ( $product_shipping_service_type != '' ) && ( $product_shipping_service_type != 'disabled' ) ) {

			$profile_details['shipping_service_type']               = $product_shipping_service_type;
			$profile_details['loc_shipping_options']                = get_post_meta( $post_id, '_ebay_loc_shipping_options', true );
			$profile_details['int_shipping_options']                = get_post_meta( $post_id, '_ebay_int_shipping_options', true );
			$profile_details['PackagingHandlingCosts']              = get_post_meta( $post_id, '_ebay_PackagingHandlingCosts', true );
			$profile_details['InternationalPackagingHandlingCosts'] = get_post_meta( $post_id, '_ebay_InternationalPackagingHandlingCosts', true );
			$profile_details['shipping_loc_enable_free_shipping']   = get_post_meta( $post_id, '_ebay_shipping_loc_enable_free_shipping', true );
			$profile_details['shipping_package']   					= get_post_meta( $post_id, '_ebay_shipping_package', true );

			// check for custom product level seller profiles
			if ( get_post_meta( $post_id, '_ebay_seller_shipping_profile_id', true ) ) {

				$product_level_profile_id = get_post_meta( $post_id, '_ebay_seller_shipping_profile_id', true );
				$profile_details['seller_shipping_profile_id'] = $product_level_profile_id;

				// // check if shipping profile id exists (done in buildSellerProfiles())
				// $seller_shipping_profiles	= get_option('wplister_ebay_seller_shipping_profiles');
				// foreach ( $seller_shipping_profiles as $profile ) {
				// 	if ( $profile->ProfileID == $product_level_profile_id )
				// 		$profile_details['seller_shipping_profile_id'] = $product_level_profile_id;
				// }

			}

			// check for custom product level ship to locations
			if ( get_post_meta( $post_id, '_ebay_shipping_ShipToLocations', true ) )
				$profile_details['ShipToLocations']						= get_post_meta( $post_id, '_ebay_shipping_ShipToLocations', true );
			if ( get_post_meta( $post_id, '_ebay_shipping_ExcludeShipToLocations', true ) )
				$profile_details['ExcludeShipToLocations']				= get_post_meta( $post_id, '_ebay_shipping_ExcludeShipToLocations', true );

		}

		return $profile_details;

	} /* end of adjustProfileDetails() */


	public function buildSellerProfiles( $id, $item, $post_id, $profile_details ) {

		$SellerProfiles = new SellerProfilesType();

		if ( @$profile_details['seller_shipping_profile_id'] ) {

			// get seller profiles for account
			$accounts = WPLE()->accounts;
			if ( isset( $accounts[ $this->account_id ] ) && ( $accounts[ $this->account_id ]->shipping_profiles ) ) {
				$seller_shipping_profiles = maybe_unserialize( $accounts[ $this->account_id ]->shipping_profiles );
			} else {
				$seller_shipping_profiles = get_option( 'wplister_ebay_seller_shipping_profiles' );
			}

			// check if shipping profile id exists
			// TODO: show warning to user if non-existing seller profile was ignored
			// $seller_shipping_profiles	= get_option('wplister_ebay_seller_shipping_profiles');
			$profile_exists = false;
			foreach ( $seller_shipping_profiles as $profile ) {
				if ( $profile->ProfileID == $profile_details['seller_shipping_profile_id'] )
					$profile_exists = true; 
			}

			if ( $profile_exists ) {
				$SellerProfiles->SellerShippingProfile = new SellerShippingProfileType();
				$SellerProfiles->SellerShippingProfile->setShippingProfileID( $profile_details['seller_shipping_profile_id'] );
			}

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
		$item->StartPrice->setTypeValue( self::dbSafeFloatval( $start_price ) );
		$item->StartPrice->setTypeAttribute('currencyID', $profile_details['currency'] );

		// optional BuyItNow price
		if ( intval($profile_details['fixed_price']) != 0) {
			$buynow_price = ListingsModel::applyProfilePrice( $listing['price'], $profile_details['fixed_price'] );
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

			// mode 1 - use sale price
			if ( 1 == $profile_details['strikethrough_pricing'] ) {
				$original_price = ProductWrapper::getOriginalPrice( $post_id );
				if ( ( $original_price ) && ( $start_price != $original_price ) ) {
					$item->DiscountPriceInfo = new DiscountPriceInfoType();
					$item->DiscountPriceInfo->OriginalRetailPrice = new AmountType();
					$item->DiscountPriceInfo->OriginalRetailPrice->setTypeValue( $original_price );
					$item->DiscountPriceInfo->OriginalRetailPrice->setTypeAttribute('currencyID', $profile_details['currency'] );
				}
			}

			// mode 2 - use MSRP
			if ( 2 == $profile_details['strikethrough_pricing'] ) {
				$msrp_price = get_post_meta( $post_id, '_msrp_price', true ); // simple product
				if ( ( $msrp_price ) && ( $start_price != $msrp_price ) ) {
					$item->DiscountPriceInfo = new DiscountPriceInfoType();
					$item->DiscountPriceInfo->OriginalRetailPrice = new AmountType();
					$item->DiscountPriceInfo->OriginalRetailPrice->setTypeValue( $msrp_price );
					$item->DiscountPriceInfo->OriginalRetailPrice->setTypeAttribute('currencyID', $profile_details['currency'] );
				}
			}

		} // OriginalRetailPrice / STP

	



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


	public function buildProductListingDetails( $id, $item, $post_id, $profile_details, $listing, $hasVariations, $isVariation, $product_sku ) {

		// if this is a single split variation, use variation post_id - but remember parent_id to fetch Brand
		$parent_id = $post_id;
		if ( $isVariation ) $post_id = $listing['post_id'];

		// handle Product ID (UPC, EAN, MPN, etc.)
		$autofill_missing_gtin = get_option('wplister_autofill_missing_gtin');
		$DoesNotApplyText = WPLE_eBaySite::getSiteObj( $this->site_id )->DoesNotApplyText;
		$DoesNotApplyText = empty( $DoesNotApplyText ) ? 'Does not apply' : $DoesNotApplyText;

		// build ProductListingDetails
		$ProductListingDetails = new ProductListingDetailsType();
		$has_details           = false;

		// set UPC from SKU - if enabled
		if ( $product_sku && ( $profile_details['use_sku_as_upc'] == '1' ) ) {
			$ProductListingDetails->setUPC( $product_sku );
			$has_details = true;
		}

		// set UPC from product - if provided
		if ( $product_upc = get_post_meta( $post_id, '_ebay_upc', true ) ) {
			$ProductListingDetails->setUPC( $product_upc );
			$has_details = true;
		} elseif ( $autofill_missing_gtin == 'upc' && ! $hasVariations ) {
			$ProductListingDetails->setUPC( $DoesNotApplyText );
			$has_details = true;
		}

		// set EAN from product - if provided
		if ( $product_ean = get_post_meta( $post_id, '_ebay_ean', true ) ) {
			$ProductListingDetails->setEAN( $product_ean );
			$has_details = true;
		} elseif ( $autofill_missing_gtin == 'ean' && ! $hasVariations ) {
			$ProductListingDetails->setEAN( $DoesNotApplyText );
			$has_details = true;
		}

		// set ISBN from product - if provided
		if ( $product_isbn = get_post_meta( $post_id, '_ebay_isbn', true ) ) {
			$ProductListingDetails->setISBN( $product_isbn );
			$has_details = true;
		}

		// set EPID from product - if provided
		if ( $product_epid = get_post_meta( $post_id, '_ebay_epid', true ) ) {
			$ProductListingDetails->setProductReferenceID( $product_epid );
			$has_details = true;
		}

		// set Brand/MPN from product - if provided
		$product_brand = get_post_meta( $parent_id, '_ebay_brand', true );
		$product_mpn   = get_post_meta( $post_id,   '_ebay_mpn',   true );
		if ( $product_brand && $product_mpn ) {

			// Note: MPN is always paired with Brand for single-variation listings, 
			// but for multiple-variation listings, only the Brand value should be specified in the BrandMPN container 
			// and the MPN for each product variation will be specified through a VariationSpecifics.NameValueList container.
			// (the above might be wrong - submitting a BrandMPN container without MPN set results in error 37...)
			$ProductListingDetails->BrandMPN = new BrandMPNType();
			$ProductListingDetails->BrandMPN->setBrand( $product_brand );

			if ( $product_mpn ) {
				$ProductListingDetails->BrandMPN->setMPN( $product_mpn );
			}

			$has_details = true;
		}

		// include prefilled info - if enabled in profile
		$include_prefilled_info = isset( $profile_details['include_prefilled_info'] ) ? (bool)$profile_details['include_prefilled_info'] : true;  
		if ( $include_prefilled_info ) {
			$ProductListingDetails->setUseFirstProduct( true );
			$ProductListingDetails->setIncludeStockPhotoURL( true );
			$ProductListingDetails->setIncludePrefilledItemInformation( $include_prefilled_info ? 1 : 0 );
			// $ProductListingDetails->setUseStockPhotoURLAsGallery( true );			
		}

		// only set ProductListingDetails if at least one product ID is set
		if ( $has_details ) {
			$item->setProductListingDetails( $ProductListingDetails );			
			// WPLE()->logger->info("buildProductListingDetails: " . print_r($item->getProductListingDetails(),1) );
		}

		return $item;
	} /* end of buildProductListingDetails() */


	public function buildProductOptions( $id, $item, $post_id, $profile_details, $listing, $hasVariations, $isVariation ) {

		// get product SKU
		$product_sku = ProductWrapper::getSKU( $post_id );

		// if this is a single split variation, use variation SKU instead of parent SKU
		if ( $isVariation ) $product_sku = ProductWrapper::getSKU( $listing['post_id'] );

		// set SKU - if not empty
		if ( trim( $product_sku ) == '' ) $product_sku = false;
		if ( $product_sku ) $item->SKU = $product_sku;

		// build buildProductListingDetails (UPC, EAN, MPN, etc.)
		$item = $this->buildProductListingDetails( $id, $item, $post_id, $profile_details, $listing, $hasVariations, $isVariation, $product_sku );

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
			WPLE()->logger->debug( 'setSubTitle: '.$subtitle );
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

		// disable GetItFast if dispatch time does not allow it - fixes revising imported items
		if ( intval($profile_details['dispatch_time']) > 1 ) {
			$item->setGetItFast( 0 );
		}

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
			$item->VATDetails->VATPercent = self::dbSafeFloatval( $profile_details['vat_percent'] );
		}

		// handle B2B option
		if ( @$profile_details['b2b_only'] == 1 ) {
			if ( $item->getVATDetails() == null ) $item->VATDetails = new VATDetailsType();
			$item->VATDetails->BusinessSeller = true;
			$item->VATDetails->RestrictedToBusiness = true;
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

		// no payment options for classified ads
		if ( $item->ListingType == 'LeadGeneration' ) return $item;

		// get paypal email address
		$accounts = WPLE()->accounts;
		if ( isset( $accounts[ $this->account_id ] ) && ( $accounts[ $this->account_id ]->paypal_email ) ) {
			$PayPalEmailAddress = $accounts[ $this->account_id ]->paypal_email;
		} else {
			$PayPalEmailAddress = get_option( 'wplister_paypal_email' );
		}

		// set payment methods
		foreach ( $profile_details['payment_options'] as $payment_method ) {

			if ( $payment_method['payment_name'] == '' ) continue;			

			# BuyerPaymentMethodCodeType
			$item->addPaymentMethods( $payment_method['payment_name'] );
			if ( $payment_method['payment_name'] == 'PayPal' ) {
				$item->PayPalEmailAddress = $PayPalEmailAddress;
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


	public function buildShipping( $id, $item, $post_id, $profile_details, $listing, $isVariation ) {

		// no shipping options for classified ads
		if ( $item->ListingType == 'LeadGeneration' ) return $item;

		// handle flat and calc shipping
		WPLE()->logger->info('shipping_service_type: '.$profile_details['shipping_service_type'] );
		// $isFlat = $profile_details['shipping_service_type'] != 'calc' ? true : false;
		// $isCalc = $profile_details['shipping_service_type'] == 'calc' ? true : false;

		// if this is a single split variation, use variation post_id instead of parent post_id for weight and dimensions
		$actual_post_id = $isVariation ? $listing['post_id'] : $post_id;

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
		WPLE()->logger->info('shippingDetails->ShippingType: '.$shippingDetails->ShippingType );

		// local shipping options
		$localShippingOptions = $profile_details['loc_shipping_options'];
		WPLE()->logger->debug('localShippingOptions: '.print_r($localShippingOptions,1));

		$pr = 1;
		$localShippingServices = array();
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
				// enable FreeShipping option for calculated shipping services if specified in profile (or product meta)
				$free_shipping_enabled = isset( $profile_details['shipping_loc_enable_free_shipping'] ) ? $profile_details['shipping_loc_enable_free_shipping'] : false;			
				// $free_shipping_enabled = $free_shipping_enabled || get_post_meta( $post_id, '_ebay_shipping_loc_enable_free_shipping', true );
				if ( ( $free_shipping_enabled ) && ( $pr == 1 ) ) $ShippingServiceOptions->setFreeShipping( true );
			}

			$localShippingServices[] = $ShippingServiceOptions;
			$pr++;
			
			$EbayShippingModel = new EbayShippingModel();
			$lastShippingCategory = $EbayShippingModel->getShippingCategoryByServiceName( $opt['service_name'] );
			WPLE()->logger->debug('ShippingCategory: '.print_r($lastShippingCategory,1));
		}
		// apply filter and set shipping services
		$localShippingServices = apply_filters( 'wple_local_shipping_services', $localShippingServices, $post_id, $actual_post_id, $listing );
		$shippingDetails->setShippingServiceOptions( $localShippingServices, null );


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
		WPLE()->logger->debug('intlShipping: '.print_r($intlShipping,1));

		$pr = 1;
		$shippingInternational = array();
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
				$shippingInternational[] = $InternationalShippingServiceOptions;
				$pr++;
			// }
		}
		
		// filter international shipping services
		$shippingInternational = apply_filters( 'wple_international_shipping_services', $shippingInternational, $post_id, $actual_post_id, $listing );

		// only set international shipping if $intlShipping array contains one or more valid items
		if ( isset( $intlShipping[0]['service_name'] ) && ( $intlShipping[0]['service_name'] != '' ) )
			$shippingDetails->setInternationalShippingServiceOption( $shippingInternational, null );


		// set CalculatedShippingRate
		if ( $isCalcLoc || $isCalcInt ) {
			$calculatedShippingRate = new CalculatedShippingRateType();
			$calculatedShippingRate->setOriginatingPostalCode( $profile_details['postcode'] );
			
			// set ShippingPackage if calculated shipping is used
			if ( $isCalcInt ) $calculatedShippingRate->setShippingPackage( $profile_details['shipping_package'] );
			if ( $isCalcLoc ) $calculatedShippingRate->setShippingPackage( $profile_details['shipping_package'] );

			if ( $isCalcLoc ) {
				$calculatedShippingRate->setPackagingHandlingCosts( self::dbSafeFloatval( @$profile_details['PackagingHandlingCosts'] ) );	
			} 
			if ( $isCalcInt ) {
				// $calculatedShippingRate->setPackagingHandlingCosts( self::dbSafeFloatval( @$profile_details['PackagingHandlingCosts'] ) );	
				$calculatedShippingRate->setInternationalPackagingHandlingCosts( self::dbSafeFloatval( @$profile_details['InternationalPackagingHandlingCosts'] ) );
			}

			list( $weight_major, $weight_minor ) = ProductWrapper::getEbayWeight( $actual_post_id );
			$calculatedShippingRate->setWeightMajor( self::dbSafeFloatval( $weight_major) );
			$calculatedShippingRate->setWeightMinor( self::dbSafeFloatval( $weight_minor) );

			$dimensions = ProductWrapper::getDimensions( $actual_post_id );
			if ( trim( @$dimensions['width']  ) != '' ) $calculatedShippingRate->setPackageWidth( $dimensions['width'] );
			if ( trim( @$dimensions['length'] ) != '' ) $calculatedShippingRate->setPackageLength( $dimensions['length'] );
			if ( trim( @$dimensions['height'] ) != '' ) $calculatedShippingRate->setPackageDepth( $dimensions['height'] );

			// debug
			// $weight = ProductWrapper::getWeight( $actual_post_id ) ;
			// WPLE()->logger->info('weight: '.print_r($weight,1));
			// WPLE()->logger->info('dimensions: '.print_r($dimensions,1));


			$shippingDetails->setCalculatedShippingRate( $calculatedShippingRate );
		}

		// handle option to always send weight and dimensions
		if ( get_option( 'wplister_send_weight_and_size', 'default' ) == 'always' ) {
			$hasWeight = ProductWrapper::getWeight( $actual_post_id );
		}

		// set ShippingPackageDetails
		if ( $hasWeight ) {
			$shippingPackageDetails = new ShipPackageDetailsType();

			// set ShippingPackage if calculated shipping is used
			if ( $isCalcInt ) $shippingPackageDetails->setShippingPackage( $profile_details['shipping_package'] );
			if ( $isCalcLoc ) $shippingPackageDetails->setShippingPackage( $profile_details['shipping_package'] );
			
			list( $weight_major, $weight_minor ) = ProductWrapper::getEbayWeight( $actual_post_id );
			$shippingPackageDetails->setWeightMajor( self::dbSafeFloatval( $weight_major) );
			$shippingPackageDetails->setWeightMinor( self::dbSafeFloatval( $weight_minor) );

			$dimensions = ProductWrapper::getDimensions( $actual_post_id );
			if ( trim( @$dimensions['width']  ) != '' ) $shippingPackageDetails->setPackageWidth( $dimensions['width'] );
			if ( trim( @$dimensions['length'] ) != '' ) $shippingPackageDetails->setPackageLength( $dimensions['length'] );
			if ( trim( @$dimensions['height'] ) != '' ) $shippingPackageDetails->setPackageDepth( $dimensions['height'] );

			// debug
			// $weight = ProductWrapper::getWeight( $actual_post_id ) ;
			// WPLE()->logger->info('weight: '.print_r($weight,1));
			// WPLE()->logger->info('dimensions: '.print_r($dimensions,1));

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

		// store pickup
		if ( @$profile_details['store_pickup'] == 1 ) {
			$item->PickupInStoreDetails = new PickupInStoreDetailsType();
			$item->PickupInStoreDetails->setEligibleForPickupInStore( true ); 
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
			WPLE()->logger->info('PICKUP ONLY mode');

			// don't set ShippingDetails for pickup-only in UK!
			if ( $item->Site != 'UK' ) {
				$item->setShippingDetails($shippingDetails);
			}

		} else {
			$item->setShippingDetails($shippingDetails);
		}

		// force AutoPay off for Freight shipping 
		if ( $service_type == 'FreightFlat' ) {
			$item->setAutoPay( 0 );			
		}

		return $item;

	} /* end of buildShipping() */

	public function buildItemSpecifics( $id, $item, $listing, $post_id ) {

    	// new ItemSpecifics
    	$ItemSpecifics = new NameValueListArrayType();

		// get listing data
		// $listing = ListingsModel::getItem( $id );

		// get product attributes
		$processed_attributes = array();
        $attributes = ProductWrapper::getAttributes( $post_id );
		WPLE()->logger->info('product attributes: '. ( sizeof($attributes)>0 ? print_r($attributes,1) : '-- empty --' ) );

		// apply item specifics from profile
		$specifics = $listing['profile_data']['details']['item_specifics'];

		// merge item specifics from product
		$product_specifics = get_post_meta( $post_id, '_ebay_item_specifics', true );
		if ( ! empty($product_specifics) )
			$specifics = array_merge( $specifics, $product_specifics ); 

		// WPLE()->logger->info('item_specifics: '.print_r($specifics,1));
		// WPLE()->logger->debug('get variationAttributes: '.print_r($this->variationAttributes,1));
		// WPLE()->logger->debug('get variationSplitAttributes: '.print_r($this->variationSplitAttributes,1));
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
					WPLE()->logger->info("specs: added custom value: {$spec['name']} - $value");
	        	}

        	} elseif ( $spec['attribute'] != '' ) {

        		// pull value from product attribute
        		$value = isset( $attributes[ $spec['attribute'] ] ) ? $attributes[ $spec['attribute'] ] : '';
        		$value = html_entity_decode( $value, ENT_QUOTES );

        		// process custom attributes
        		$custom_attributes = apply_filters( 'wplister_custom_attributes', array() );
        		foreach ( $custom_attributes as $attrib ) {
        			if ( $spec['attribute'] == $attrib['id'] ) {

        				// pull value from attribute
        				if ( isset( $attrib['meta_key'] ) ) {
	        				$value = get_post_meta( $post_id, $attrib['meta_key'], true );

	        				// for split variations, check for value on variation level
	        				if ( $post_id != $listing['post_id'] ) {
	        					$variation_value = get_post_meta( $listing['post_id'], $attrib['meta_key'], true );
								if ( $variation_value ) $value = $variation_value;
								// WPLE()->logger->info("specs: variation_value for: {$spec['name']} - " . $variation_value );
	        				}
        				}

        				// set fixed value (since 2.0.9.5)
        				if ( isset( $attrib['value'] ) ) {
	        				$value = $attrib['value'];
        				}

        				// use callback (since 2.0.9.6)
        				if ( isset( $attrib['callback'] ) && is_callable( $attrib['callback'] ) ) {
	        				$value = call_user_func( $attrib['callback'], $post_id, $id );
        				}

        			}
        		}
        		// if ( '_sku' == $spec['attribute'] ) $value = ProductWrapper::getSKU( $post_id );

        		// handle variation attributes for a single split variation
        		// instead of listing all values, use the correct attribute value from variationSplitAttributes
        		if ( array_key_exists( $spec['attribute'], $this->variationSplitAttributes ) ) {
        			$value = $this->variationSplitAttributes[ $spec['attribute'] ];
        		}

        		// skip empty values
        		if ( ! $value ) {
					WPLE()->logger->info("specs: skipped empty product attribute: {$spec['name']} - " . $value );
        			continue;
        		}

	            $NameValueList = new NameValueListType();
		    	$NameValueList->setName ( $spec['name'] );
	    		// $NameValueList->setValue( $value );
	
	    		// support for multi value attributes
	    		// $value = 'blue|red|green';
	    		$values = explode('|', $value);
	    		foreach ($values as $value) {
	        		if ( $this->mb_strlen( $value ) > 50 ) continue;
		    		$NameValueList->addValue( $value );
	    		}	        	

	        	if ( ! in_array( $spec['name'], $this->variationAttributes ) ) {
		        	$ItemSpecifics->addNameValueList( $NameValueList );
		        	$processed_attributes[] = $spec['attribute'];
					WPLE()->logger->info("specs: added product attribute: {$spec['name']} - " . join(', ',$values) );
	        	}
        	}
        }

        // skip if item has no attributes
        // if ( count($attributes) == 0 ) return $item;

        // get excluded attributes and merge with processed attributes
        $excluded_attributes  = $this->getExcludedAttributes();
		$processed_attributes = array_merge( $processed_attributes, $excluded_attributes ); 
		$convert_attributes_mode = get_option( 'wplister_convert_attributes_mode', 'all' );

    	// add ItemSpecifics from product attributes - if enabled
        foreach ($attributes as $name => $value) {

    		$value = html_entity_decode( $value, ENT_QUOTES );
    		if ( $this->mb_strlen( $value ) > 50 ) continue;
			if ( $convert_attributes_mode == 'none' ) continue;

    		// handle variation attributes for a single split variation
    		// instead of listing all values, use the correct attribute value from variationSplitAttributes
    		if ( array_key_exists( $name, $this->variationSplitAttributes ) ) {
    			$value = $this->variationSplitAttributes[ $name ];
    		}

            $NameValueList = new NameValueListType();
	    	$NameValueList->setName ( $name  );
    		
    		// support for multi value attributes
    		// $value = 'blue|red|green';
    		$values = explode('|', $value);
    		foreach ($values as $value) {
	    		$NameValueList->addValue( $value );
	    		if ( $convert_attributes_mode == 'single' ) break; // only use first value in 'single' mode
    		}
        	
        	// only add attribute to ItemSpecifics if not already present in variations or processed attributes
        	if ( ( ! in_array( $name, $this->variationAttributes ) ) && ( ! in_array( $name, $processed_attributes ) ) ) {
	        	$ItemSpecifics->addNameValueList( $NameValueList );
				WPLE()->logger->info("attrib: added product attribute: {$name} - " . join(', ',$values) );
        	}
        }

        if ( count($ItemSpecifics) > 0 ) {
    		$item->setItemSpecifics( $ItemSpecifics );        	
			WPLE()->logger->info( count($ItemSpecifics) . " item specifics were added.");
        }

		return $item;

	} /* end of buildItemSpecifics() */

	public function getExcludedAttributes() {
		$excluded_attributes = get_option('wplister_exclude_attributes');
		if ( ! $excluded_attributes ) return array();

		$attribute_names = split(',', $excluded_attributes);
		$attributes = array();
		foreach ($attribute_names as $name) {
			$attributes[] = trim($name);
		}

		return $attributes;
	} // getExcludedAttributes()

	public function buildCompatibilityList( $id, $item, $listing, $post_id ) {
		if ( get_option( 'wplister_disable_compat_list' ) == 1 ) return $item;

		// get compatibility list and names from product
		$compatibility_list   = get_post_meta( $post_id, '_ebay_item_compatibility_list', true );
		$compatibility_names  = get_post_meta( $post_id, '_ebay_item_compatibility_names', true );
		if ( empty($compatibility_list) ) return $item;

    	// new ItemCompatibilityList
    	$ItemCompatibilityList = new ItemCompatibilityListType();
    	$ItemCompatibilityList->setReplaceAll( 1 );

        foreach ($compatibility_list as $comp) {

        	$ItemCompatibility = new ItemCompatibilityType();
        	$ItemCompatibility->setCompatibilityNotes( $comp->notes );

        	foreach ( $comp->applications as $app ) {

        		$value = html_entity_decode( $app->value, ENT_QUOTES );

	            $NameValueList = new NameValueListType();
		    	$NameValueList->setName ( $app->name  );
	    		$NameValueList->setValue( $value );

	        	$ItemCompatibility->addNameValueList( $NameValueList );
        	}

        	// add to list
        	$ItemCompatibilityList->addCompatibility( $ItemCompatibility );
        }

		$item->setItemCompatibilityList( $ItemCompatibilityList );        	
		WPLE()->logger->info( count($ItemCompatibilityList) . " compatible applications were added.");

		return $item;

	} /* end of buildCompatibilityList() */

	public function buildVariations( $id, $item, $profile_details, $listing, $session ) {

		// build variations
		$item->Variations = new VariationsType();

		// get product variations
		// $listing = ListingsModel::getItem( $id );
        $variations = ProductWrapper::getVariations( $listing['post_id'] );

        // get max_quantity from profile
        $max_quantity = ( isset( $profile_details['max_quantity'] ) && intval( $profile_details['max_quantity'] )  > 0 ) ? $profile_details['max_quantity'] : PHP_INT_MAX ; 

		// get variation attributes / item specifics map according to profile
		$specifics_map = $profile_details['item_specifics'];
    	$collectedMPNs = array();

        // loop each combination
        foreach ($variations as $var) {

        	$newvar = new VariationType();

        	// handle price
			$newvar->StartPrice = self::dbSafeFloatval( ListingsModel::applyProfilePrice( $var['price'], $profile_details['start_price'] ) );

			// handle StartPrice on parent product level
			if ( $product_start_price = get_post_meta( $listing['post_id'], '_ebay_start_price', true ) ) {
				$newvar->StartPrice = self::dbSafeFloatval( $product_start_price );
			}
			// handle StartPrice on variation level
			if ( $product_start_price = get_post_meta( $var['post_id'], '_ebay_start_price', true ) ) {
				$newvar->StartPrice = self::dbSafeFloatval( $product_start_price );
			}

        	// handle variation quantity - if no quantity set in profile
        	// if ( intval( $item->Quantity ) == 0 ) {
        	if ( intval( $profile_details['quantity'] ) == 0 ) {
        		$newvar->Quantity   = min( $max_quantity, intval( $var['stock'] ) );
        	} else {
	        	$newvar->Quantity 	= min( $max_quantity, $item->Quantity ); // should be removed in future versions
        	}
        	if ( $newvar->Quantity < 0 ) $newvar->Quantity = 0; // prevent error for negative qty

			// handle sku
        	if ( $var['sku'] != '' ) {
        		$newvar->SKU = $var['sku'];
        	}

			// // add VariationSpecifics (v2)
			// $VariationSpecifics = new NameValueListArrayType();
			// foreach ($var['variation_attributes'] as $name => $value) {
			//     $NameValueList = new NameValueListType();
			// 	$NameValueList->setName ( $name  );
			// 	$NameValueList->setValue( $value );
			// 	$VariationSpecifics->addNameValueList( $NameValueList );
			// }
			// $newvar->setVariationSpecifics( $VariationSpecifics );

        	// add VariationSpecifics (v3 - regard profile mapping)
        	$VariationSpecifics = new NameValueListArrayType();
            foreach ($var['variation_attributes'] as $name => $value) {

	        	// check for matching attribute name - replace woo attribute name with eBay attribute name
		        foreach ( $specifics_map as $spec ) {
		        	if ( $name == $spec['attribute'] ) {
		        		$name = $spec['name'];
		        	}
				}

	            $NameValueList = new NameValueListType();
    	    	$NameValueList->setName ( $name  );
        		$NameValueList->setValue( $value );
	        	$VariationSpecifics->addNameValueList( $NameValueList );
            }

        	$newvar->setVariationSpecifics( $VariationSpecifics );

			// optional Variation.DiscountPriceInfo.OriginalRetailPrice
			$post_id     = $var['post_id'];
			$start_price = $newvar->StartPrice;
			if ( intval($profile_details['strikethrough_pricing']) != 0) {
	
				// mode 1 - use sale price
				if ( 1 == $profile_details['strikethrough_pricing'] ) {
					$original_price = ProductWrapper::getOriginalPrice( $post_id );
					if ( ( $original_price ) && ( $start_price != $original_price ) ) {
						$newvar->DiscountPriceInfo = new DiscountPriceInfoType();
						$newvar->DiscountPriceInfo->OriginalRetailPrice = new AmountType();
						$newvar->DiscountPriceInfo->OriginalRetailPrice->setTypeValue( $original_price );
						$newvar->DiscountPriceInfo->OriginalRetailPrice->setTypeAttribute('currencyID', $profile_details['currency'] );
					}
				}

				// mode 2 - use MSRP
				if ( 2 == $profile_details['strikethrough_pricing'] ) {
					$msrp_price = get_post_meta( $post_id, '_msrp', true ); // variation
					if ( ( $msrp_price ) && ( $start_price != $msrp_price ) ) {
						$newvar->DiscountPriceInfo = new DiscountPriceInfoType();
						$newvar->DiscountPriceInfo->OriginalRetailPrice = new AmountType();
						$newvar->DiscountPriceInfo->OriginalRetailPrice->setTypeValue( $msrp_price );
						$newvar->DiscountPriceInfo->OriginalRetailPrice->setTypeAttribute('currencyID', $profile_details['currency'] );
					}
				}

			} // OriginalRetailPrice / STP


			// handle variation level Product ID (UPC/EAN)
			$autofill_missing_gtin = get_option('wplister_autofill_missing_gtin');
			$DoesNotApplyText = WPLE_eBaySite::getSiteObj( $this->site_id )->DoesNotApplyText;
			$DoesNotApplyText = empty( $DoesNotApplyText ) ? 'Does not apply' : $DoesNotApplyText;

			// set UPC from SKU - if enabled
			if ( $var['sku'] && ( $profile_details['use_sku_as_upc'] == '1' ) ) {
				$VariationProductListingDetails = new VariationProductListingDetailsType();
				$VariationProductListingDetails->setUPC( $var['sku'] );
				$newvar->setVariationProductListingDetails( $VariationProductListingDetails );
			} elseif ( $product_upc = get_post_meta( $post_id, '_ebay_upc', true ) ) {
				$VariationProductListingDetails = new VariationProductListingDetailsType();
				$VariationProductListingDetails->setUPC( $product_upc );
				$newvar->setVariationProductListingDetails( $VariationProductListingDetails );
			} elseif ( $autofill_missing_gtin == 'upc') {
				$VariationProductListingDetails = new VariationProductListingDetailsType();
				$VariationProductListingDetails->setUPC( $DoesNotApplyText );
				$newvar->setVariationProductListingDetails( $VariationProductListingDetails );
			}

			// set EAN
			if ( $product_ean = get_post_meta( $post_id, '_ebay_ean', true ) ) {
				$VariationProductListingDetails = new VariationProductListingDetailsType();
				$VariationProductListingDetails->setEAN( $product_ean );
				$newvar->setVariationProductListingDetails( $VariationProductListingDetails );
			} elseif ( $autofill_missing_gtin == 'ean') {
				$VariationProductListingDetails = new VariationProductListingDetailsType();
				$VariationProductListingDetails->setEAN( $DoesNotApplyText );
				$newvar->setVariationProductListingDetails( $VariationProductListingDetails );
			}

			// set ISBN
			if ( $product_isbn = get_post_meta( $post_id, '_ebay_isbn', true ) ) {
				$VariationProductListingDetails = new VariationProductListingDetailsType();
				$VariationProductListingDetails->setISBN( $product_isbn );
				$newvar->setVariationProductListingDetails( $VariationProductListingDetails );
			} elseif ( $autofill_missing_gtin == 'isbn') {
				$VariationProductListingDetails = new VariationProductListingDetailsType();
				$VariationProductListingDetails->setISBN( $DoesNotApplyText );
				$newvar->setVariationProductListingDetails( $VariationProductListingDetails );
			}

			// set MPN
			// Note: If Brand and MPN are being used to identify product variations in a multiple-variation listing, 
			// the Brand must be specified at the item level (ItemSpecifics container) 
			// and the MPN for each product variation must be specified at the variation level (VariationSpecifics container). 
			// The Brand name must be the same for all variations within a single listing.
			if ( $product_mpn = get_post_meta( $post_id, '_ebay_mpn', true ) ) {
	
	            $NameValueList = new NameValueListType();
    	    	$NameValueList->setName ( 'MPN' );
        		$NameValueList->setValue( $product_mpn );
	        	$VariationSpecifics->addNameValueList( $NameValueList );

	        	$newvar->setVariationSpecifics( $VariationSpecifics );

	        	$collectedMPNs[] = $product_mpn;
			}


			$item->Variations->addVariation( $newvar );

        } // each variation


        // build temporary array for VariationSpecificsSet
    	$this->tmpVariationSpecificsSet = array();
        foreach ($variations as $var) {

            foreach ($var['variation_attributes'] as $name => $value) {

	        	// check for matching attribute name - replace woo attribute name with eBay attribute name
		        foreach ( $specifics_map as $spec ) {
		        	if ( $name == $spec['attribute'] ) {
			        	$this->variationAttributes[] = $name; // remember original name to exclude in builtItemSpecifics()
		        		$name = $spec['name'];
		        	}
				}

    	    	if ( ! is_array($this->tmpVariationSpecificsSet[ $name ]) ) {
		        	$this->tmpVariationSpecificsSet[ $name ] = array();
    	    	}
	        	if ( ! in_array( $value, $this->tmpVariationSpecificsSet[ $name ], true ) ) {
	        		$this->tmpVariationSpecificsSet[ $name ][] = $value;	        		
	        	}
            }

        }

		// add collected MPNs to tmp array
		foreach ( $collectedMPNs as $value ) {
			$name = 'MPN';

	    	if ( ! is_array($this->tmpVariationSpecificsSet[ $name ]) ) {
	        	$this->tmpVariationSpecificsSet[ $name ] = array();
	    	}
        	if ( ! in_array( $value, $this->tmpVariationSpecificsSet[ $name ], true ) ) {
        		$this->tmpVariationSpecificsSet[ $name ][] = $value;	        		
        	}
        }

        // build VariationSpecificsSet
    	$VariationSpecificsSet = new NameValueListArrayType();
        foreach ($this->tmpVariationSpecificsSet as $name => $values) {

			$NameValueList = new NameValueListType();
        	$NameValueList->setName ( $name );
            foreach ($values as $value) {
	        	$NameValueList->addValue( $value );
	        }
	    	$VariationSpecificsSet->addNameValueList( $NameValueList );

        }
    	$item->Variations->setVariationSpecificsSet( $VariationSpecificsSet );

        
        // build array of variation attributes, which will be needed in builtItemSpecifics()
        // $this->variationAttributes = array();
        foreach ($this->tmpVariationSpecificsSet as $key => $value) {
        	$this->variationAttributes[] = $key;
        }
        WPLE()->logger->debug('set variationAttributes: '.print_r($this->variationAttributes,1));


        // select *one* VariationSpecificsSet for Pictures set
        // currently the first one is selected automatically, but there will be preferences for this later
        $VariationValuesForPictures =  reset($this->tmpVariationSpecificsSet);
        $VariationNameForPictures   =    key($this->tmpVariationSpecificsSet);

        // apply variation image attribute from profile - if set
        $variation_image_attribute = isset( $profile_details['variation_image_attribute'] ) ? $profile_details['variation_image_attribute'] : false;
        if ( $variation_image_attribute && isset( $this->tmpVariationSpecificsSet[ $variation_image_attribute ] ) ) {
	        $VariationValuesForPictures = $this->tmpVariationSpecificsSet[ $variation_image_attribute ];
	        $VariationNameForPictures   = $variation_image_attribute;
        }


        // build Pictures
    	$Pictures = new PicturesType();
    	$Pictures->setVariationSpecificName ( $VariationNameForPictures );
        foreach ($variations as $var) {

        	$VariationValue = $var['variation_attributes'][$VariationNameForPictures];

        	if ( in_array( $VariationValue, $VariationValuesForPictures ) ) {

    			$image_url = $this->encodeUrl( $var['image'] );
    			// $image_url = $this->removeHttpsFromUrl( $image_url );


				if ( ! $image_url ) continue;
				WPLE()->logger->info( "using variation image: ".$image_url );

		    	$VariationSpecificPictureSet = new VariationSpecificPictureSetType();
    	    	$VariationSpecificPictureSet->setVariationSpecificValue( $VariationValue );
        		$VariationSpecificPictureSet->addPictureURL( $image_url );

        		// check for additional variation images (WooCommerce Additional Variation Images Addon) 
        		if ( class_exists('WC_Additional_Variation_Images') ) {

        			$additional_var_images = get_post_meta( $var['post_id'], '_wc_additional_variation_images', true );
					$additional_var_images = empty($additional_var_images) ? false : explode( ',', $additional_var_images );

        			if ( is_array( $additional_var_images ) ) {
        				foreach ( $additional_var_images as $attachment_id ) {

        					// get URL from attachment ID
							$size = get_option( 'wplister_default_image_size', 'full' );
							$large_image_url = wp_get_attachment_image_src( $attachment_id, $size );
			    			$image_url = $this->encodeUrl( $large_image_url[0] );
							WPLE()->logger->info( "found additional variation image: ".$image_url );

					        // upload variation images if enabled
					        if ( $with_additional_images ) 
					        	$image_url = $this->lm->uploadPictureToEPS( $image_url, $id, $session );

					        // add variation image to picture set
			        		$VariationSpecificPictureSet->addPictureURL( $image_url );
							WPLE()->logger->info( "added additional variation image: ".$image_url );
        				}
        			}
        		}

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

			$item->ShippingDetails->CalculatedShippingRate->setWeightMajor( self::dbSafeFloatval( $weight_major ) );
			$item->ShippingDetails->CalculatedShippingRate->setWeightMinor( self::dbSafeFloatval( $weight_minor ) );

			if ( trim( @$dimensions['width']  ) != '' ) $item->ShippingDetails->CalculatedShippingRate->setPackageWidth( $dimensions['width'] );
			if ( trim( @$dimensions['length'] ) != '' ) $item->ShippingDetails->CalculatedShippingRate->setPackageLength( $dimensions['length'] );
			if ( trim( @$dimensions['height'] ) != '' ) $item->ShippingDetails->CalculatedShippingRate->setPackageDepth( $dimensions['height'] );

			// update ShippingPackageDetails with weight and dimensions of first variations
			$shippingPackageDetails = new ShipPackageDetailsType();
			$shippingPackageDetails->setWeightMajor( self::dbSafeFloatval( $weight_major) );
			$shippingPackageDetails->setWeightMinor( self::dbSafeFloatval( $weight_minor) );
			if ( trim( @$dimensions['width']  ) != '' ) $shippingPackageDetails->setPackageWidth( $dimensions['width'] );
			if ( trim( @$dimensions['length'] ) != '' ) $shippingPackageDetails->setPackageLength( $dimensions['length'] );
			if ( trim( @$dimensions['height'] ) != '' ) $shippingPackageDetails->setPackageDepth( $dimensions['height'] );
			$item->setShippingPackageDetails( $shippingPackageDetails );

			// debug
			WPLE()->logger->info('first variations weight: '.print_r($weight,1));
			WPLE()->logger->info('first variations dimensions: '.print_r($dimensions,1));
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
		WPLE()->logger->info("variation images: ".print_r($variation_images,1));

        return $variation_images;
	} // getVariationImages()


	public function prepareSplitVariation( $id, $post_id, $listing ) {
		WPLE()->logger->info("prepareSplitVariation( $id ) - parent_id: ".$listing['parent_id']);
		$parent_id = $listing['parent_id'];

		// get (all) parent variations
        $variations = ProductWrapper::getVariations( $parent_id );

        // find this single variation
        $single_variation = false;
        foreach ($variations as $var) {
        	if ( $var['post_id'] == $post_id ) {
        		$single_variation = $var;
        	}
        }
        if ( ! $single_variation ) return;

	    // add variation attributes to $this->variationSplitAttributes - to be used in builtItemSpecifics()
        foreach ($single_variation['variation_attributes'] as $name => $value) {
        	$this->variationSplitAttributes[ $name ] = $value;
        }
        WPLE()->logger->debug('set variationSplitAttributes: '.print_r($this->variationSplitAttributes,1));

	} // prepareSplitVariation()


	public function flattenVariations( $id, $item, $post_id, $profile_details ) {
		WPLE()->logger->info("flattenVariations($id)");

		// get product variations
		// $p = ListingsModel::getItem( $id );
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
			$start_price = ListingsModel::applyProfilePrice( $start_price, $profile_details['start_price'] );
			$item->StartPrice->setTypeValue( self::dbSafeFloatval( $start_price ) );
			WPLE()->logger->info("using default variations price: ".print_r($item->StartPrice->value,1));

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

			$item->ShippingDetails->CalculatedShippingRate->setWeightMajor( self::dbSafeFloatval( $weight_major ) );
			$item->ShippingDetails->CalculatedShippingRate->setWeightMinor( self::dbSafeFloatval( $weight_minor ) );

			if ( trim( @$dimensions['width']  ) != '' ) $item->ShippingDetails->CalculatedShippingRate->setPackageWidth( $dimensions['width'] );
			if ( trim( @$dimensions['length'] ) != '' ) $item->ShippingDetails->CalculatedShippingRate->setPackageLength( $dimensions['length'] );
			if ( trim( @$dimensions['height'] ) != '' ) $item->ShippingDetails->CalculatedShippingRate->setPackageDepth( $dimensions['height'] );

			// debug
			WPLE()->logger->info('default variations weight: '.print_r($weight,1));
			WPLE()->logger->info('default variations dimensions: '.print_r($dimensions,1));
		}

		// set ShippingPackageDetails
		if ( $hasWeight ) {
			
			// get weight and dimensions from default variation
			$weight_major = $default_variation['weight_major'];
			$weight_minor = $default_variation['weight_minor'];
			$dimensions   = $default_variation['dimensions'];

			$shippingPackageDetails = new ShipPackageDetailsType();
			$shippingPackageDetails->setWeightMajor( self::dbSafeFloatval( $weight_major) );
			$shippingPackageDetails->setWeightMinor( self::dbSafeFloatval( $weight_minor) );

			if ( trim( @$dimensions['width']  ) != '' ) $shippingPackageDetails->setPackageWidth( $dimensions['width'] );
			if ( trim( @$dimensions['length'] ) != '' ) $shippingPackageDetails->setPackageLength( $dimensions['length'] );
			if ( trim( @$dimensions['height'] ) != '' ) $shippingPackageDetails->setPackageDepth( $dimensions['height'] );

			$item->setShippingPackageDetails( $shippingPackageDetails );
		}

		return $item;

	} /* end of flattenVariations() */





	// remove specific item details to allow revising in restricted mode
	// called from ListingsModel::reviseItem()
    function applyRestrictedReviseMode( $item ) {

    	// remove Item->Variations->Pictures node
    	if ( $item->Variations ) {
	    	$item->Variations->setPictures( null );
    	}

    	return $item;
	} // applyRestrictedReviseMode()



	// check if there are existing variations on eBay which do not exist in WooCommerce and need to be deleted
	// called from ListingsModel::reviseItem()
    function fixDeletedVariations( $item, $listing_item ) {

        $cached_variations = maybe_unserialize( $listing_item['variations'] );
        if ( empty($cached_variations) ) return $item;

        // do nothing if this is not a variable item 
        // if a user switches a variable product to simple, addVariation() below will throw a fatal error otherwise
		if ( ! is_object( $item->Variations ) ) return $item;

        // loop cached variations
        foreach ($cached_variations as $key => $var) {
        	
        	if ( ! $this->checkIfVariationExistsInItem( $var, $item ) ) {

        		// build new variation to be deleted
	        	$newvar = new VariationType();

	        	// set quantity to zero - effectively remove variations that have sales
	        	$newvar->Quantity = 0;
				// $newvar->StartPrice = $var['price'];

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

	        	// tell eBay to delete this variation - only possible for items without sales
	        	if ( isset($var['sold']) && ( intval($var['sold']) == 0 ) ) {
		        	$newvar->setDelete( true );
	                WPLE()->logger->info('setDelete(true) - sold qty: '.$var['sold']);
	        	}

				$item->Variations->addVariation( $newvar );
                WPLE()->logger->info('added variation to be deleted: '.print_r($newvar,1) );

                //
                // update VariationSpecificsSet - to avoid Error 21916608: Variation cannot be deleted during restricted revise
                //

		        // build extra (!) temporary array for VariationSpecificsSet
		    	$extraVariationSpecificsSet = array();
	            foreach ($var['variation_attributes'] as $name => $value) {
	    	    	if ( ! is_array($this->tmpVariationSpecificsSet[ $name ]) ) {
			        	$this->tmpVariationSpecificsSet[ $name ] = array(); 	// make sure the second level array exists
	    	    	}
	    	    	if ( ! is_array($extraVariationSpecificsSet[ $name ]) ) {
			        	$extraVariationSpecificsSet[ $name ] = array();			// make sure the second level array exists
	    	    	}
		        	if ( ! in_array( $value, $this->tmpVariationSpecificsSet[ $name ] ) ) {
		        		$extraVariationSpecificsSet[ $name ][]     = $value;	// add extra value which doesn't exist yet        		
		        		$this->tmpVariationSpecificsSet[ $name ][] = $value;	// add extra value which doesn't exist yet        		
		        	}
	            }
		        // build VariationSpecificsSet
		    	// $VariationSpecificsSet = new NameValueListArrayType();
		        foreach ($extraVariationSpecificsSet as $name => $values) {

		        	foreach ($item->Variations->VariationSpecificsSet->NameValueList as $NameValueList) {

		        		// check if this is the attribute we're looking for
		        		if ( $NameValueList->Name != $name ) continue;

						// add missing attribute values
			            foreach ($values as $value) {
				        	$NameValueList->addValue( $value );
				        }

		        	}

		        } // foreach $extraVariationSpecificsSet


        	} // if checkIfVariationExistsInItem()

        } // foreach $cached_variations

    	return $item;
	} // fixDeletedVariations()

    function checkIfVariationExistsInItem( $variation, $item ) {
    	$variation_attributes = $variation['variation_attributes'];

        // loop existing item variations
        foreach ( $item->Variations->Variation as $Variation ) {
            $found_match = true;

            // compare variation SKU
            if ( ! empty( $variation['sku'] ) ) {
            	if ( $variation['sku'] == $Variation->SKU ) {
	                // WPLE()->logger->info('found matching variation by SKU: '.$Variation->SKU);
	                return true;
            	}
            }

            // compare variation attributes
        	foreach ($Variation->VariationSpecifics->NameValueList as $spec) {
        		$name = $spec->Name;
        		$val  = $spec->Value;
        		if ( isset( $variation_attributes[ $name ] ) ) {

        			if ( $variation_attributes[ $name ] == $val ) {
	                	// WPLE()->logger->info('found matching name value pair: '.print_r($spec,1) );
        				// $found_match = true;
        			} else {
	                	// WPLE()->logger->info('variation spec value does not match with "'.$variation_attributes[ $name ].'": '.print_r($spec,1) );
        				$found_match = false;
        			}

        		} else {
                	// WPLE()->logger->info('variation spec name does not exist "'.$name.'" does not exist in attributes: '.print_r($variation_attributes,1) );
    				$found_match = false;        			
        		}
        	}

            if ( $found_match ) {
                // WPLE()->logger->info('found matching variation by attributes: '.print_r($Variation->VariationSpecifics->NameValueList,1) );
                return true;
            }

        }

        return false;
    } // checkIfVariationExistsInItem()










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
				if ( self::dbSafeFloatval( $var->StartPrice ) == 0 ) {
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
				$longMessage .= __('It is required to assign a unique SKU to each variation to prevent issues syncing sales.','wplister');
				// $success = false;
			}

			if ( ! $VariationsHaveStock && ! $reviseItem && ! ListingsModel::thisAccountUsesOutOfStockControl( $this->account_id ) ) {
				$longMessage = __('None of these variations are in stock.','wplister');
				$success = false;
			}

			// make this info available to reviseItem()
			$this->VariationsHaveStock = $VariationsHaveStock;

		} else {
			// item has no variations

			// StartPrice must be greater than 0
			if ( self::dbSafeFloatval( $item->StartPrice->value ) == 0 ) {
				$longMessage = __('Price can not be zero.','wplister');
				$success = false;
			}

			// check minimum start price if found
			// $min_prices = get_option( 'wplister_MinListingStartPrices', array() );
			$min_prices = $this->site_id ? maybe_unserialize( WPLE_eBaySite::getSiteObj( $this->site_id )->MinListingStartPrices ) : array();
			if ( ! is_array($min_prices) ) $min_prices = array();

			$listing_type = $item->ListingType ? $item->ListingType : 'FixedPriceItem';
			if ( isset( $min_prices[ $listing_type ] ) ) {
				$min_price = $min_prices[ $listing_type ];
				if ( $item->StartPrice->value < $min_price ) {
					$longMessage = sprintf( __('eBay requires a minimum price of %s for this listing type.','wplister'), $min_price );
					$success = false;
				}
			}

		}


		// check if any required item specifics are missing
		$primary_category_id = $item->PrimaryCategory->CategoryID;
		$specifics           = EbayCategoriesModel::getItemSpecificsForCategory( $primary_category_id, $this->site_id, $this->account_id );

		foreach ( $specifics as $req_spec ) {

			// skip non-required specs
			if ( ! $req_spec->MinValues ) continue;

			// skip if Name already exists in ItemSpecifics
			if ( self::thisNameExistsInNameValueList( $req_spec->Name, $item->ItemSpecifics->NameValueList ) ) {
				continue;
			}
		
			// skip if Name already exists in VariationSpecificsSet
			if ( is_object( $item->Variations ) ) {
				$VariationSpecificsSet = $item->Variations->getVariationSpecificsSet();
				if ( self::thisNameExistsInNameValueList( $req_spec->Name, $VariationSpecificsSet->NameValueList ) ) {
					continue;
				}
			}
		
			$DoesNotApplyText = WPLE_eBaySite::getSiteObj( $this->site_id )->DoesNotApplyText;
			$DoesNotApplyText = empty( $DoesNotApplyText ) ? 'Does not apply' : $DoesNotApplyText;
        
			// // add missing item specifics
			$NameValueList = new NameValueListType();
			$NameValueList->setName ( $req_spec->Name  );
			$NameValueList->setValue( $DoesNotApplyText );
			$item->ItemSpecifics->addNameValueList( $NameValueList );

			wple_show_message( '<b>Note:</b> Missing item specifics <b>'.$req_spec->Name.'</b> was set to "'.$DoesNotApplyText.'" in order to prevent listing errors.', 'warn' );
		}

		// check if any item specific have more values than allowed
		foreach ( $specifics as $req_spec ) {

			// skip specs without limit
			if ( ! $req_spec->MaxValues ) continue;

			// count values for this item specific
			$number_of_values = self::countValuesForNameInNameValueList( $req_spec->Name, $item->ItemSpecifics->NameValueList );
			if ( $number_of_values <= $req_spec->MaxValues ) continue;

			// remove additional values from item specific
			for ( $i=0; $i < sizeof( $item->ItemSpecifics->NameValueList ); $i++ ) { 
				if ( $item->ItemSpecifics->NameValueList[ $i ]->Name != $req_spec->Name ) continue;
				$values_array =	$item->ItemSpecifics->NameValueList[ $i ]->Value;
				$item->ItemSpecifics->NameValueList[ $i ]->Value = reset( $values_array );
			}

			wple_show_message( '<b>Note:</b> The item specifics <b>'.$req_spec->Name.'</b> has '.$number_of_values.' values, but eBay allows only '.$req_spec->MaxValues.' value(s).<br>In order to prevent listing errors, additional values will be omitted.', 'warn' );
		}


		// ItemSpecifics values can't be longer than 50 characters
		foreach ( $item->ItemSpecifics->NameValueList as $spec ) {
			$values = is_array( $spec->Value ) ? $spec->Value : array( $spec->Value );
			foreach ($values as $value) {
				if ( strlen($value) > 50 ) {
					$longMessage = __('eBay does not allow attribute values longer than 50 characters.','wplister');
					$longMessage .= '<br>';
					$longMessage .= __('You need to shorten this value:','wplister') . ' <code>'.$value.'</code>';
					$success = false;
				}
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

		// remove ReservedPrice on fixed price items
		if ( $item->getReservePrice() && $item->getListingType() == 'FixedPriceItem' ) {
			$item->setReservePrice( null );
			$longMessage = __('Reserve price does not apply to fixed price listings.','wplister');
			// $success = false;
		}

		// omit price and shipping cost when revising an item with promotional sale enabled
		if ( $reviseItem && ListingsModel::thisListingHasPromotionalSale( $this->listing_id ) ) {
			$item->setStartPrice( null );
			$item->setShippingDetails( null );
			wple_show_message( __('Price and shipping were omitted since this item has promotional sale enabled.','wplister'), 'info' );
		}

		if ( ! $success && ! $this->is_ajax() ) {
			wple_show_message( $longMessage, 'error' );
		} elseif ( ( $longMessage != '' ) && ! $this->is_ajax() ) {
			wple_show_message( $longMessage, 'warn' );
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


	static public function thisNameExistsInNameValueList( $name, $NameValueList ) {
		foreach ( $NameValueList as $listitem ) {
			if ( $listitem->Name == $name ) {
				// name exists, check value
				if ( is_array( $listitem->Value ) ) {
					if ( ! $listitem->Value[0]  &&  $listitem->Value[0] !== '0' ) return false;
				} else {
					if ( ! $listitem->Value     &&  $listitem->Value    !== '0' ) return false;
				}
				return true;
			}
		}
		return false;
	}

	static public function countValuesForNameInNameValueList( $name, $NameValueList ) {
		foreach ( $NameValueList as $listitem ) {
			if ( $listitem->Name == $name ) {
				// name found, count array values
				if ( is_array( $listitem->Value ) ) {
					return sizeof( $listitem->Value );
				} else {
					return 1;
				}
			}
		}
		return false;
	}


	public function getDynamicShipping( $price, $post_id ) {
		
		// return price if no mapping
		if ( ! substr( $price, 0, 1 ) == '[' ) return self::dbSafeFloatval($price);

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
			return self::dbSafeFloatval($shipping_cost);
		}
		
		// convert '0.00' to '0' - ebay api doesn't like '0.00'
		if ( $price == 0 ) $price = '0';

		return self::dbSafeFloatval($price);

	}

	// this version of floatval() makes sure to use decimal points, no matter what locale is set for PHP
	static public function dbSafeFloatval( $value ) {
		// WPLE()->logger->info('dbSafeFloatval()  IN: '.$value);

		// set locale to use C style floats for numeric calculations
		setlocale( LC_NUMERIC, 'C' );
		$value = floatval( $value );

		// WPLE()->logger->info('dbSafeFloatval() OUT: '.$value);
	    return $value;
	}


	public function prepareTitleAsHTML( $title ) {

		WPLE()->logger->debug('prepareTitleAsHTML()  in: ' . $title );
		$title = htmlentities( $title, ENT_QUOTES, 'UTF-8', false );
		WPLE()->logger->debug('prepareTitleAsHTML() out: ' . $title );
		return $title;
	}


	public function prepareTitle( $title ) {

		WPLE()->logger->info('prepareTitle()  in: ' . $title );
		$title = html_entity_decode( $title, ENT_QUOTES, 'UTF-8' );

        // limit item title to 80 characters
        if ( $this->mb_strlen($title) > 80 ) $title = self::mb_substr( $title, 0, 77 ) . '...';

		WPLE()->logger->info('prepareTitle() out: ' . $title );
		return $title;
	}
	

	public function getFinalHTML( $id, $ItemObj, $preview = false ) {
		
		// get item data
		$item = ListingsModel::getItem( $id );

		// use latest post_content from product - moved to TemplatesModel
		// $post = get_post( $item['post_id'] );
		// if ( ! empty($post->post_content) ) $item['post_content'] = $post->post_content;

		// load template
		$template = new TemplatesModel( $item['template'] );
		$html = $template->processItem( $item, $ItemObj, $preview );

		// strip invalid XML characters
		$html = $this->stripInvalidXml( $html );

		// return html
		return $html;
	}

	public function getPreviewHTML( $template_id, $id = false ) {
		
		// get item data
		if ( $id ) {
			$item = ListingsModel::getItem( $id );
		} else {
			$item = WPLE_ListingQueryHelper::getItemForPreview();
		}
		if ( ! $item ) {
			return '<div style="text-align:center; margin-top:5em;">You need to prepare at least one listing in order to preview a listing template.</div>';
		}

		// use latest post_content from product - moved to TemplatesModel
		// $post = get_post( $item['post_id'] );
		// if ( ! empty($post->post_content) ) $item['post_content'] = $post->post_content;

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
				// WPLE()->logger->info( "NGG - picture: " . print_r($picture,1) );
				WPLE()->logger->info( "NGG - image_url: " . print_r($image_url,1) );
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

	} // getProductMainImageURL()

	public function getProductImagesURL( $id ) {
		global $wpdb;

    	$results = $wpdb->get_results( $wpdb->prepare(" 
			SELECT id, guid 
			FROM {$wpdb->prefix}posts
			WHERE post_type = 'attachment' 
			  AND post_parent = %s
			ORDER BY menu_order
		", $id ) );
		WPLE()->logger->debug( "getProductImagesURL( $id ) : " . print_r($results,1) );
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
		if ( get_option( 'wplister_wc2_gallery_fallback','none' ) == 'none' ) $images = array(); // discard images if fallback is disabled
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
			
			WPLE()->logger->info( "found WC2 product gallery images for product #$id " . print_r($images,1) );
		}

		$product_images = array();
		foreach($images as $imageurl) {
			$product_images[] = $this->removeHttpsFromUrl( $imageurl );
		}

		// call wplister_product_images filter 
		// hook into this from your WP theme's functions.php - this won't work in listing templates!
		$product_images = apply_filters( 'wplister_product_images', $product_images, $id );

		return $product_images;
	} // getProductImagesURL()


	// ebay doesn't accept image urls using https - only http and ftp
	function removeHttpsFromUrl( $url ) {

		// fix relative urls
		if ( '/wp-content/' == substr( $url, 0, 12 ) ) {
			$url = str_replace('/wp-content', content_url(), $url);
		}
		if ( '//wp-content/' == substr( $url, 0, 13 ) ) {
			$url = str_replace('//wp-content', content_url(), $url);
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
		$url = $this->removeHttpsFromUrl( $url );
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
