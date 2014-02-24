<?php
/**
 * ListingsModel class
 *
 * responsible for managing listings and talking to ebay
 * 
 */

class ListingsModel extends WPL_Model {

	var $_session;
	var $_cs;

	function ListingsModel()
	{
		global $wpl_logger;
		$this->logger = &$wpl_logger;

		global $wpdb;
		$this->tablename = $wpdb->prefix . 'ebay_auctions';
	}


	function getPageItems( $current_page, $per_page ) {
		global $wpdb;

        $orderby = (!empty($_REQUEST['orderby'])) ? $_REQUEST['orderby'] : 'id'; //If no sort, default to title
        $order = (!empty($_REQUEST['order'])) ? $_REQUEST['order'] : 'desc'; //If no order, default to asc
        $offset = ( $current_page - 1 ) * $per_page;

        $join_sql  = '';
        $where_sql = '';

        // filter listing_status
		$listing_status = ( isset($_REQUEST['listing_status']) ? $_REQUEST['listing_status'] : 'all');
		if ( $listing_status == 'all' ) {
			$where_sql = "WHERE NOT status = 'archived' ";
		} elseif ( $listing_status == 'relist' ) {
			$where_sql = "WHERE ( status = 'ended' OR status = 'sold' ) AND quantity > 0 ";
		} else {
			$where_sql = "WHERE status = '".$listing_status."' ";
		} 

        // filter search_query
		$search_query = ( isset($_REQUEST['s']) ? $_REQUEST['s'] : false);
		if ( $search_query ) {
			$join_sql = "
				LEFT JOIN {$wpdb->prefix}ebay_profiles p  ON l.profile_id =  p.profile_id
				LEFT JOIN {$wpdb->prefix}postmeta      pm ON l.post_id    = pm.post_id AND pm.meta_key = '_sku'
			";
			$where_sql = "
				WHERE l.auction_title LIKE '%".$search_query."%'
				    OR l.template     LIKE '%".$search_query."%'
				    OR p.profile_name LIKE '%".$search_query."%'
				    OR l.history      LIKE '%".$search_query."%'
					OR l.ebay_id          = '".$search_query."'
					OR l.auction_type     = '".$search_query."'
					OR l.listing_duration = '".$search_query."'
					OR l.status           = '".$search_query."'
					OR l.post_id          = '".$search_query."'
					OR pm.meta_value      = '".$search_query."'
			";
		} 


        // get items
		$items = $wpdb->get_results("
			SELECT *
			FROM $this->tablename l
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
				FROM $this->tablename l
	            $join_sql
	            $where_sql
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

		if ( !empty($item) ) $item['profile_data'] = $this->decodeObject( $item['profile_data'], true );
		// $item['details'] = $this->decodeObject( $item['details'] );

		return $item;
	}

	function getItemForPreview() {
		global $wpdb;
		$item = $wpdb->get_row("
			SELECT *
			FROM $this->tablename
			ORDER BY id DESC
			LIMIT 1
		", ARRAY_A);

		if ( !empty($item) ) $item['profile_data'] = $this->decodeObject( $item['profile_data'], true );
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

	function getItemByEbayID( $id, $decode_details = true ) {
		global $wpdb;
		$item = $wpdb->get_row("
			SELECT *
			FROM $this->tablename
			WHERE ebay_id = '$id'
		");
		if (!$item) return false;
		if (!$decode_details) return $item;

		$item->profile_data = $this->decodeObject( $item->profile_data, true );
		$item->details = $this->decodeObject( $item->details );

		return $item;
	}

	// find listing by current or previous item ID
	function findItemByEbayID( $id, $decode_details = true ) {
		global $wpdb;
		$item = $wpdb->get_row("
			SELECT *
			FROM $this->tablename
			WHERE ebay_id = '$id'
			   OR history LIKE '%$id%'
		");
		if (!$item) return false;
		if (!$decode_details) return $item;

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
			  AND NOT status = 'archived'
		");
		return $item;
	}
	function getEbayIDFromPostID( $post_id ) {
		global $wpdb;
		$item = $wpdb->get_var("
			SELECT ebay_id
			FROM $this->tablename
			WHERE post_id = '$post_id'
			  AND NOT status = 'archived'
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
			ORDER BY id DESC
		");
		return $item;
	}
	function getListingIDFromPostID( $post_id ) {
		global $wpdb;
		$item = $wpdb->get_var("
			SELECT id
			FROM $this->tablename
			WHERE post_id = '$post_id'
			ORDER BY id DESC
		");
		return $item;
	}
	function getAllListingsFromPostID( $post_id ) {
		global $wpdb;
		$items = $wpdb->get_results("
			SELECT *
			FROM $this->tablename
			WHERE post_id = '$post_id'
			ORDER BY id DESC
		");
		return $items;
	}
	function getAllListingsFromParentID( $post_id ) {
		global $wpdb;
		$items = $wpdb->get_results("
			SELECT *
			FROM $this->tablename
			WHERE parent_id = '$post_id'
			ORDER BY id DESC
		");
		return $items;
	}
	function getViewItemURLFromPostID( $post_id ) {
		global $wpdb;
		$item = $wpdb->get_var("
			SELECT ViewItemURL
			FROM $this->tablename
			WHERE post_id = '$post_id'
			ORDER BY id DESC
		");
		return $item;
	}

	function getStatusSummary() {
		global $wpdb;
		$result = $wpdb->get_results("
			SELECT status, count(*) as total
			FROM $this->tablename
			GROUP BY status
		");

		$summary = new stdClass();
		// $summary->prepared = false;
		// $summary->changed = false;
		foreach ($result as $row) {
			$status = $row->status;
			$summary->$status = $row->total;
		}

		// count relist candidates
		$relist = $wpdb->get_var("
			SELECT COUNT( id ) AS relist
			FROM $this->tablename
			WHERE ( status = 'ended' OR status = 'sold' ) AND quantity > 0
		");
		$summary->relist = $relist;

		// count total items as well
		$total_items = $wpdb->get_var("
			SELECT COUNT( id ) AS total_items
			FROM $this->tablename
			WHERE NOT status = 'archived'
		");
		$summary->total_items = $total_items;

		return $summary;
	}

	function getHistory( $ebay_id ) {
		global $wpdb;
		$item = $wpdb->get_var("
			SELECT history
			FROM $this->tablename
			WHERE ebay_id = '$ebay_id'
		");
		return maybe_unserialize( $item );
	}

	function setHistory( $ebay_id, $history ) {
		global $wpdb;

		$data = array( 
			'history' => maybe_serialize( $history )
		);

		$result = $wpdb->update( $this->tablename, $data, array( 'ebay_id' => $ebay_id ) );
		return $result;
	}

	function addItemIdToHistory( $ebay_id, $previous_id ) {
		global $wpdb;
	
		$history = $this->getHistory( $ebay_id );

		$this->logger->info( "addItemIdToHistory($ebay_id, $previous_id) " );
		$this->logger->info( "history: ".print_r($history,1) );

		// init empty history
		if ( ! isset($history['previous_ids'] ) ) {
			$history = array(
				'previous_ids' => array()
			);
		}

		// return if ID already exists in history
		if ( in_array( $previous_id, $history['previous_ids'] ) ) return;

		// add ID to history
		$history['previous_ids'][] = $previous_id;		

		// update history
		$this->setHistory( $ebay_id, $history );

	}


	function isUsingEPS( $id ) {
		$this->logger->info( "isUsingEPS( $id ) " );

		$listing_item = $this->getItem( $id );
		$profile_details = $listing_item['profile_data']['details'];

        $with_additional_images = isset( $profile_details['with_additional_images'] ) ? $profile_details['with_additional_images'] : false;
        if ( $with_additional_images == '0' ) $with_additional_images = false;

        return $with_additional_images;
	}

	function isUsingVariationImages( $id ) {
		$this->logger->info( "isUsingVariationImages( $id ) " );

		$listing_item = $this->getItem( $id );
		$profile_details = $listing_item['profile_data']['details'];

        $with_variation_images = isset( $profile_details['with_variation_images'] ) ? $profile_details['with_variation_images'] : false;
        if ( $with_variation_images == '0' ) $with_variation_images = false;

        return $with_variation_images;
	}

	// check if there are existing variations on eBay which do not exist in WooCommerce and need to be deleted
    function fixDeletedVariations( $item, $listing_item ) {

        $cached_variations  = maybe_unserialize( $listing_item['variations'] );
        if ( empty($cached_variations) ) return $item;

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
	        	$newvar->setDelete( true );

				$item->Variations->addVariation( $newvar );
                $this->logger->info('added variation to be deleted: '.print_r($newvar,1) );

                // TODO: update VariationSpecificsSet ?
        	}
        }

    	return $item;
	}

    function checkIfVariationExistsInItem( $variation, $item ) {
    	$variation_attributes = $variation['variation_attributes'];

        // loop cached variations
        foreach ( $item->Variations->Variation as $Variation ) {
            $found_match = true;

            // compare variation attributes
        	foreach ($Variation->VariationSpecifics->NameValueList as $spec) {
        		$name = $spec->Name;
        		$val  = $spec->Value;
        		if ( isset( $variation_attributes[ $name ] ) ) {

        			if ( $variation_attributes[ $name ] == $val ) {
	                	// $this->logger->info('found matching name value pair: '.print_r($spec,1) );
        				// $found_match = true;
        			} else {
	                	// $this->logger->info('variation spec value does not match with "'.$variation_attributes[ $name ].'": '.print_r($spec,1) );
        				$found_match = false;
        			}

        		} else {
                	// $this->logger->info('variation spec name does not exist "'.$name.'" does not exist in attributes: '.print_r($variation_attributes,1) );
    				$found_match = false;        			
        		}
        	}

            if ( $found_match ) {
                // $this->logger->info('found matching variation by attributes: '.print_r($Variation->VariationSpecifics->NameValueList,1) );
                return true;
            }

        }

        return false;
    } // checkIfVariationExistsInItem()


	// check if there are new variations in WooCommerce which do not exist in the cache
    function matchCachedVariations( $item, $filter_unchanged = false ) {
        $success   = true;
        $new_count = 0;

        // make sure we have an actual listing item
        if ( is_numeric( $item ) ) $item = $this->getItem( $item );
        if ( ! $item ) return false;

        $cached_variations  = maybe_unserialize( $item['variations'] );
        $product_variations = ProductWrapper::getVariations( $item['post_id'] );

        // TODO: update cache
        if ( empty($cached_variations) ) return false;

        // loop product variations (what we want listed)
        foreach ( $product_variations as $key => $pv ) {
            
            // check if variation exists in cache
            if ( $cv = $this->checkIfVariationExistsInCache( $pv, $cached_variations ) ) {

            	// check if price or quantity have changed - if told to do so
            	if ( $filter_unchanged ) {
            		if ( ! $this->checkIfVariationInventoryHasChanged( $pv, $cv ) ) {
            			// remove unchanged variations from the list
	                    unset( $product_variations[ $key ] );
            		}
            	}

            } else {

                // check stock level
                if ( $pv['stock'] > 0 ) {

                    $new_count++;
                    $success = false;

                    // $this->logger->debug('found NEW variation: '.print_r( $pv, 1 ) );
                    // $this->logger->info( 'found NEW variation: '.$pv['sku'] );

                } else {
                    // no stock, so just remove from list
                    unset( $product_variations[ $key ] );
                    // $this->logger->info( 'removed out of stock variation: '.$pv['sku'] );
                }

            }

        }

        $result = new stdClass();
        $result->success    = $success;
        $result->new_count  = $new_count;
        $result->variations = $product_variations;

        return $result;
    } // matchCachedVariations()

    function checkIfVariationExistsInCache( $pv, &$cached_variations ) {

        // loop cached variations
        foreach ( $cached_variations as $key => $cv ) {
            
            // compare SKU
            if ( $pv['sku'] == $cv['sku'] ) {

                // remove from list 
                unset( $cached_variations[ $key ] );

                // $this->logger->info('matched variation by SKU: '.$cv['sku'] );
                return $cv;
            }

            // compare variation attributes
            if ( serialize( $pv['variation_attributes'] ) == serialize( $cv['variation_attributes'] ) ) {

                // remove from list 
                unset( $cached_variations[ $key ] );

                $this->logger->info('matched variation by attributes: '.serialize($cv['variation_attributes']) );
                return $cv;
            }

        }

        return false;
    } // checkIfVariationExistsInCache()

    function generateVariationKeyFromAttributes( $variation_attributes ) {
        // $this->logger->info('generateVariationKeyFromAttributes() called: '.print_r($variation_attributes,1) );

    	// sort attributes alphabetically
    	ksort( $variation_attributes );
    	$key = '';

    	foreach ($variation_attributes as $attribute => $value) {
    		$key .= $attribute.'__'.$value.'|';
    	}

        $this->logger->info('generateVariationKeyFromAttributes() returned: '.$key );
        return $key;
    } // generateVariationKeyFromAttributes()

    function checkIfVariationInventoryHasChanged( $pv, $cv ) {

        // compare stock level
        if ( $pv['stock'] != $cv['stock'] ) {
            $this->logger->info('found changed stock level for variation: '.$cv['sku'] );
            return true;        	
        }
        
        // compare price
        if ( $pv['price'] != $cv['price'] ) {
            $this->logger->info('found changed price for variation: '.$cv['sku'] );
            return true;        	
        }
        
        return false;
    } // checkIfVariationInventoryHasChanged()


	
	function listingUsesFixedPriceItem( $listing_item )
	{
		// regard auction_type by default
		$useFixedPriceItem = ( 'FixedPriceItem' == $listing_item['auction_type'] ) ? true : false;

		// but switch to AddItem if BestOffer is enabled
		$profile_details = $listing_item['profile_data']['details'];
        if ( @$profile_details['bestoffer_enabled'] == '1' ) $useFixedPriceItem = false;

		// or switch to AddItem if product level listing type is Chinese
		$product_listing_type = get_post_meta( $listing_item['post_id'], '_ebay_auction_type', true );
        if ( $product_listing_type == 'Chinese' ) $useFixedPriceItem = false;

        // or switch to AddItem when relisting an ended auction as fixed price
        $ItemDetails = $this->decodeObject( $listing_item['details'] );
        if ( $ItemDetails && is_object( $ItemDetails ) ) {
        	if ( $ItemDetails->ListingType == 'Chinese' )
 				$useFixedPriceItem = false;        	
        }

        // never use FixedPriceItem if variations are disabled
        if ( get_option( 'wplister_disable_variations' ) == '1' ) $useFixedPriceItem = false;

		return $useFixedPriceItem;
	} 

	// handle additional requests after AddItem(), ReviseItem(), etc.
	function postProcessListing( $id, $ItemID, $item, $listing_item, $res, $session ) {
	}

	function addItem( $id, $session )
	{
		// skip this item if item status not allowed
		$allowed_statuses = array( 'prepared', 'verified' );
		if ( ! $this->itemHasAllowedStatus( $id, $allowed_statuses ) ) return $this->result;

		// build item
		$ibm = new ItemBuilderModel();
		$item = $ibm->buildItem( $id, $session );
		if ( ! $ibm->checkItem($item) ) return $ibm->result;

		// eBay Motors (beta)
		if ( $item->Site == 'eBayMotors' ) $session->setSiteId( 100 );

		// preparation - set up new ServiceProxy with given session
		$this->initServiceProxy($session);

		// switch to FixedPriceItem if product has variations
		$listing_item = $this->getItem( $id );
		// $useFixedPriceItem = ( ProductWrapper::hasVariations( $listing_item['post_id'] ) ) ? true : false;
		// $useFixedPriceItem = ( 'FixedPriceItem' == $listing_item['auction_type'] ) ? true : false;

		$this->logger->info( "Adding #$id: ".$item->Title );
		if ( $this->listingUsesFixedPriceItem( $listing_item ) ) {

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
			$this->postProcessListing( $id, $res->ItemID, $item, $listing_item, $res, $session );

			$this->logger->info( "Item #$id sent to ebay, ItemID is ".$res->ItemID );

		} // call successful

		return $this->result;

	} // addItem()

	function relistItem( $id, $session )
	{
		// skip this item if item status not allowed
		$allowed_statuses = array( 'ended', 'sold' );
		if ( ! $this->itemHasAllowedStatus( $id, $allowed_statuses ) ) return $this->result;

		// reapply profile before relisting an ended item
        $this->reapplyProfileToItem( $id );

		// build item
		$ibm = new ItemBuilderModel();
		$item = $ibm->buildItem( $id, $session );
		if ( ! $ibm->checkItem($item) ) return $ibm->result;

		// eBay Motors (beta)
		if ( $item->Site == 'eBayMotors' ) $session->setSiteId( 100 );

		// preparation - set up new ServiceProxy with given session
		$this->initServiceProxy($session);

		// switch to FixedPriceItem if product has variations
		$listing_item = $this->getItem( $id );
		// $useFixedPriceItem = ( ProductWrapper::hasVariations( $listing_item['post_id'] ) ) ? true : false;
		// $useFixedPriceItem = ( 'FixedPriceItem' == $listing_item['auction_type'] ) ? true : false;

		// add old ItemID for relisting
		$item->setItemID( $listing_item['ebay_id'] );

		$this->logger->info( "Relisting #$id (ItemID ".$listing_item['ebay_id'].") - ".$item->Title );
		if ( $this->listingUsesFixedPriceItem( $listing_item ) ) {

			$req = new RelistFixedPriceItemRequestType(); 
			$req->setItem($item);
			
			$this->logger->debug( "Request: ".print_r($req,1) );
			$res = $this->_cs->RelistFixedPriceItem($req); 

		} else {

			$req = new RelistItemRequestType(); 
			$req->setItem($item);
			
			$this->logger->debug( "Request: ".print_r($req,1) );
			$res = $this->_cs->RelistItem($req); 

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
			$this->postProcessListing( $id, $res->ItemID, $item, $listing_item, $res, $session );

			$this->logger->info( "Item #$id relisted on ebay, NEW ItemID is ".$res->ItemID );

		} // call successful

		return $this->result;

	} // relistItem()

	function autoRelistItem( $id, $session )
	{
		// skip this item if item status not allowed
		$allowed_statuses = array( 'ended', 'sold' );
		if ( ! $this->itemHasAllowedStatus( $id, $allowed_statuses ) ) return $this->result;

		// build item
		$ibm  = new ItemBuilderModel();
		$item = new ItemType();
		$item = $ibm->setEbaySite( $item, $session );			

		// eBay Motors (beta)
		if ( $item->Site == 'eBayMotors' ) $session->setSiteId( 100 );

		// preparation - set up new ServiceProxy with given session
		$this->initServiceProxy($session);

		// add old ItemID for relisting
		$listing_item = $this->getItem( $id );
		$item->setItemID( $listing_item['ebay_id'] );

		// switch to FixedPriceItem if product has variations
		$this->logger->info( "Auto-Relisting #$id (ItemID ".$listing_item['ebay_id'].") - ".$item->Title );
		if ( $this->listingUsesFixedPriceItem( $listing_item ) ) {

			$req = new RelistFixedPriceItemRequestType(); 
			$req->setItem($item);
			
			$this->logger->debug( "Request: ".print_r($req,1) );
			$res = $this->_cs->RelistFixedPriceItem($req); 

		} else {

			$req = new RelistItemRequestType(); 
			$req->setItem($item);
			
			$this->logger->debug( "Request: ".print_r($req,1) );
			$res = $this->_cs->RelistItem($req); 

		}

		// handle response and check if successful
		if ( $this->handleResponse($res) ) {

			// save new ebay ID and details to db
			$listingFee = $this->getListingFeeFromResponse( $res );
			$data['ebay_id']     = $res->ItemID;
			$data['fees']        = $listingFee;
			$data['status']      = 'published';
			$data['relist_date'] = NULL;
			$this->updateListing( $id, $data );
			
			// get details like ViewItemURL from ebay automatically
			$this->updateItemDetails( $id, $session );
			$this->postProcessListing( $id, $res->ItemID, $item, $listing_item, $res, $session );

			$this->logger->info( "Item #$id auto-relisted on ebay, NEW ItemID is ".$res->ItemID );

		} // call successful

		return $this->result;

	} // autoRelistItem()

	function reviseItem( $id, $session, $force_full_update = false )
	{
		// skip this item if item status not allowed
		$allowed_statuses = array( 'published', 'changed' );
		if ( ! $this->itemHasAllowedStatus( $id, $allowed_statuses ) ) return $this->result;

		// check if product has variations
		$listing_item = $this->getItem( $id );
		// $useFixedPriceItem = ( ProductWrapper::hasVariations( $listing_item['post_id'] ) ) ? true : false;
		// $useFixedPriceItem = ( 'FixedPriceItem' == $listing_item['auction_type'] ) ? true : false;

		// handle locked items
		if ( $listing_item['locked'] && ! $force_full_update ) {
			return $this->reviseInventoryStatus( $id, $session, false );
		}

		// build item
		$ibm = new ItemBuilderModel();
		$item = $ibm->buildItem( $id, $session, true );
		if ( ! $ibm->checkItem( $item, true ) ) return $ibm->result;

		// check for variations to be deleted
		$item = $this->fixDeletedVariations( $item, $listing_item );

		// if quantity is zero, end item instead
		if ( ( $item->Quantity == 0 ) && ( ! $ibm->VariationsHaveStock ) ) {
			$this->logger->info( "Item #$id has no stock, switching from reviseItem() to endItem()" );
			return $this->endItem( $id, $session );
		}

		// checkItem should run after check for zero quantity - not it shouldn't as VariationsHaveStock will be undefined
		// TODO: separate quantity checks from checkItem() and run checkQuantity() first, maybe end item, if not then run other sanity checks
		// (This helps users who use the import plugin and WP-Lister Pro but forgot to set a primary category in their profile)
		// if ( ! $ibm->checkItem($item) ) return $ibm->result;
		
		// eBay Motors (beta)
		if ( $item->Site == 'eBayMotors' ) $session->setSiteId( 100 );

		// preparation - set up new ServiceProxy with given session
		$this->initServiceProxy($session);

		// set ItemID to revise
		$item->setItemID( $this->getEbayIDFromID($id) );
		$this->logger->info( "Revising #$id: ".$listing_item['auction_title'] );

		// switch to FixedPriceItem if product has variations
		if ( $this->listingUsesFixedPriceItem( $listing_item ) ) {

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

			// update listing status
			$data['status'] = 'published';
			if ( 291 == $this->handle_error_code ) $data['status'] = 'ended'; 
			$this->updateListing( $id, $data );
			
			// get details like ViewItemURL from ebay automatically
			$this->updateItemDetails( $id, $session );
			$this->postProcessListing( $id, $res->ItemID, $item, $listing_item, $res, $session );

			$this->logger->info( "Item #$id was revised, ItemID is ".$res->ItemID );

		} // call successful

		return $this->result;

	} // reviseItem()

	function reviseInventoryStatus( $id, $session, $cart_item = false )
	{
		// skip this item if item status not allowed
		$allowed_statuses = array( 'published', 'changed' );
		if ( ! $this->itemHasAllowedStatus( $id, $allowed_statuses ) ) return $this->result;

		// check listing type and if product has variations 
		$listing_item = $this->getItem( $id );
		$profile_details = $listing_item['profile_data']['details'];
		$post_id = $listing_item['post_id'];

		// check listing type - ignoring best offer etc...
		$useFixedPriceItem = ( 'FixedPriceItem' == $listing_item['auction_type'] ) ? true : false;
		$product_listing_type = get_post_meta( $post_id, '_ebay_auction_type', true );
        if ( $product_listing_type == 'Chinese' ) $useFixedPriceItem = false;

		// ReviseInventoryStatus only works on FixedPriceItems so use ReviseItem otherwise
		if ( ! $useFixedPriceItem ) {
			$this->logger->info( "Item #$id is not of type FixedPriceItem, switching to reviseItem()" );
			return $this->reviseItem( $id, $session, true );			
		}

		// check for single variation in cart
		$isVariationInCart = ( $cart_item && is_object($cart_item) && $cart_item->variation_id ) ? true : false;

		// check for variable product (update all variations)
		$isVariableProduct = ProductWrapper::hasVariations( $post_id );

		// fall back to reviseItem if cart variation without SKU
		if ( $isVariationInCart && ! $cart_item->sku ) {
			$this->logger->info( "Item #$id has variations without SKU, switching to reviseItem()" );
			return $this->reviseItem( $id, $session, true );			
		}

		// if stock level is zero, end item instead
		if ( ! $this->checkStockLevel( $listing_item ) ) {
			$this->logger->info( "Item #$id has no stock, switching from reviseInventoryStatus() to endItem()" );
			return $this->endItem( $id, $session );
		}

        // get max_quantity from profile
        $max_quantity = ( isset( $profile_details['max_quantity'] ) && intval( $profile_details['max_quantity'] )  > 0 ) ? $profile_details['max_quantity'] : PHP_INT_MAX ; 
												
		// set inventory status
		if ( $isVariableProduct ) {

			// get all variations
			$variations = ProductWrapper::getVariations( $post_id );
			// echo "<pre>";print_r($variations);echo"</pre>";die();	

            // check variations cache
            $result = $this->matchCachedVariations( $listing_item, $filter_unchanged = true );
            if ( $result && $result->success ) 
                $variations = $result->variations;

            // if there are new variations, fall back to reviseItem
            if ( $result && ! $result->success ) {
				$this->logger->info( "Item #$id has NEW variations, switching to reviseItem()" );
				return $this->reviseItem( $id, $session, true );			
            }

            // do nothing if no changed variations found
            if ( sizeof( $variations ) == 0 ) {
				$this->logger->info( "Item #$id has NO CHANGED variations - skipping revise request..." );
				$this->result->success = true;
				$this->result->errors = false;
            	return $this->result;
            }

            // check if all variations have unique SKUs
			if ( ! $this->checkVariationSKUs( $variations ) ) {
				$this->logger->info( "Item #$id does not have unique SKUs, switching to reviseItem()" );
				return $this->reviseItem( $id, $session, true );			
			}

			// calc number of requests
			$batch_size = 4;
			// $requests_required = intval( sizeof($variations) / $batch_size ) + 1;

			// revise inventory of up to 4 variations at a time
			for ( $offset=0; $offset < sizeof($variations); $offset += $batch_size ) { 

				// revise inventory status
				$res = $this->reviseVariableInventoryStatus( $id, $post_id, $session, $variations, $max_quantity, $offset, $batch_size );		

			}

		} else {

			// preparation - set up new ServiceProxy with given session
			$this->initServiceProxy($session);

			// build request
			$req = new ReviseInventoryStatusRequestType(); 

			// set ItemID
			$stat = new InventoryStatusType();
			$stat->setItemID( $this->getEbayIDFromID($id) );

			if ( $isVariationInCart && $cart_item->sku ) {

				// get stock level for this variation in cart
				$variation_qty = get_post_meta( $cart_item->variation_id, '_stock', true );
				$stat->setQuantity( min( $max_quantity, $variation_qty ) );
				$stat->setSKU( $cart_item->sku );
				$req->addInventoryStatus( $stat );
				$this->logger->info( "Revising inventory status for cart variation #$id ($post_id) - sku: ".$stat->SKU." - qty: ".$stat->Quantity );

			} else {
				// default - simple product
				$stat->setQuantity( min( $max_quantity, $listing_item['quantity'] ) );
				$stat->setStartPrice( $listing_item['price'] );
				$req->addInventoryStatus( $stat );
				$this->logger->info( "Revising inventory status #$id ($post_id) - qty: ".$stat->Quantity );
			}

			// revise inventory
			$this->logger->debug( "Request: ".print_r($req,1) );
			$res = $this->_cs->ReviseInventoryStatus($req); 

		}

		// handle response and check if successful
		if ( $this->handleResponse($res) ) {

			// update listing status for ended items
			if ( 291 == $this->handle_error_code ) {
				$this->updateListing( $id, array( 'status' => 'ended' ) );				
			} elseif ( ! $cart_item ) {
				$this->updateListing( $id, array( 'status' => 'published' ) );				
			}

			$this->logger->info( "Inventory status for #$id was revised successfully" );

		} // call successful

		return $this->result;

	} // reviseInventoryStatus()


	private function reviseVariableInventoryStatus( $id, $post_id, $session, $variations, $max_quantity, $offset = 0, $batch_size = 4 ) {
		$this->logger->info( "reviseVariableInventoryStatus() #$id - variations: ".sizeof($variations)." - offset: ".$offset );

		// preparation - set up new ServiceProxy with given session
		$this->initServiceProxy($session);

		// build request
		$req = new ReviseInventoryStatusRequestType(); 

		// set ItemID
		$stat = new InventoryStatusType();
		$stat->setItemID( $this->getEbayIDFromID($id) );

		// slice variations array
		$variations = array_slice( $variations, $offset, $batch_size );

		foreach ( $variations as $var ) {

			$stat = new InventoryStatusType();
			$stat->setItemID( $this->getEbayIDFromID($id) );
			$stat->setSKU( $var['sku'] );
			$stat->setQuantity( min( $max_quantity, $var['stock'] ) );
			$stat->setStartPrice( $var['price'] );

			$req->addInventoryStatus( $stat );
			$this->logger->info( "Revising inventory status for product variation #$id ($post_id) - sku: ".$stat->SKU." - qty: ".$stat->Quantity );
		}

		// revise inventory
		$this->logger->debug( "Request: ".print_r($req,1) );
		$res = $this->_cs->ReviseInventoryStatus($req); 

		// process result and update variation cache
		$InventoryStatusNodes = method_exists($res, 'getInventoryStatus') ? $res->getInventoryStatus() : false;
		$this->logger->debug( "ReviseInventoryStatus response node: ".print_r( $InventoryStatusNodes, 1) );
		if ( is_array($InventoryStatusNodes) ) {

			$listing_item = $this->getItem( $id );	
			$variations = maybe_unserialize( $listing_item['variations'] );	
			foreach ( $InventoryStatusNodes as $node ) {

				// find variation in cache
				// ReviseInventoryStatus is only used if there are SKUs, so we don't need to generate key from attributes (which are not provided in the result anyway)
				$key = $node->SKU;

				// update variations cache
				if ( isset( $variations[$key] ) ) {
					$variations[$key]['stock'] = $node->Quantity; 
					$variations[$key]['price'] = $node->StartPrice->value; 
				}

				// if zero stock, remove from cache - eBay does the same
				if ( $node->Quantity == 0 )	unset( $variations[$key] );

			}
			$this->updateListing( $id, array( 'variations' => maybe_serialize( $variations ) ) );

		}


		return $res;

	} // reviseVariableInventoryStatus()


	function checkVariationSKUs( $variations ) {
		$VariationsSkuAreUnique = true;
		$VariationsSkuMissing   = false;
		$VariationsSkuArray     = array();

		// check each variation
		foreach ($variations as $var) {
			
			// SKUs must be unique - if present
			if ( ($var['sku']) != '' ) {
				if ( in_array( $var['sku'], $VariationsSkuArray )) {
					$VariationsSkuAreUnique = false;
				} else {
					$VariationsSkuArray[] = $var['sku'];
				}
			} else {
				$VariationsSkuMissing = true;
			}

		}

		if ( $VariationsSkuMissing )
			return false;

		if ( ! $VariationsSkuAreUnique )
			return false;

		return true;
	} // checkVariationSKUs()


	function checkStockLevel( $listing_item ) {
		if ( ! is_array( $listing_item) ) $listing_item = (array) $listing_item;

		$post_id         = $listing_item['post_id'];
		$profile_details = $listing_item['profile_data']['details'];
		$locked          = $listing_item['locked'];

		if ( ProductWrapper::hasVariations( $post_id ) ) {

		    $variations = ProductWrapper::getVariations( $post_id );
		    $stock = 0;

		    foreach ( $variations as $var ) {
		    	$stock += intval( $var['stock'] );
		    }

		} else {

			$stock = ProductWrapper::getStock( $post_id );

		}

		// fixed profile quantity will always be in stock - except for locked items
    	if ( ! $locked && ( intval( $profile_details['quantity'] ) > 0 ) ) $stock = $profile_details['quantity'];
		$this->logger->info( "checkStockLevel() result: ".$stock );

		return ( intval($stock) > 0 ) ? $stock : false;

	} // checkStockLevel()


	function verifyAddItem( $id, $session )
	{
		// skip this item if item status not allowed
		$allowed_statuses = array( 'prepared', 'verified' );
		if ( ! $this->itemHasAllowedStatus( $id, $allowed_statuses ) ) return $this->result;

		// build item
		$ibm = new ItemBuilderModel();
		$item = $ibm->buildItem( $id, $session );
		if ( ! $ibm->checkItem($item) ) return $ibm->result;

		// eBay Motors (beta)
		if ( $item->Site == 'eBayMotors' ) $session->setSiteId( 100 );

		// preparation - set up new ServiceProxy with given session
		$this->initServiceProxy($session);

		// switch to FixedPriceItem if product has variations
		$listing_item = $this->getItem( $id );
		// $useFixedPriceItem = ( ProductWrapper::hasVariations( $listing_item['post_id'] ) ) ? true : false;
		// $useFixedPriceItem = ( 'FixedPriceItem' == $listing_item['auction_type'] ) ? true : false;

		$this->logger->info( "Verifying #$id: ".$item->Title );
		if ( $this->listingUsesFixedPriceItem( $listing_item ) ) {

			$req = new VerifyAddFixedPriceItemRequestType(); 
			$req->setItem($item);
			
			$this->logger->debug( "Request: ".print_r($req,1) );
			$res = $this->_cs->VerifyAddFixedPriceItem($req); 

		} else {

			$req = new VerifyAddItemRequestType(); 
			$req->setItem($item);
			
			$this->logger->debug( "Request: ".print_r($req,1) );
			$res = $this->_cs->VerifyAddItem($req); 

		}

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


	function endItem( $id, $session )
	{
		// skip this item if item status not allowed
		$allowed_statuses = array( 'published', 'changed' );
		if ( ! $this->itemHasAllowedStatus( $id, $allowed_statuses ) ) return $this->result;

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

			// mark as sold if no stock remaining
			if ( ! $this->checkStockLevel( $item ) )
				$data['status'] = 'sold';

			$this->updateListing( $id, $data );
			
			$this->logger->info( "Item #$id was ended manually. " );

		} // call successful

		return $this->result;

	} // endItem()


	function itemHasAllowedStatus( $id, $allowed_statuses )
	{
		$item = $this->getItem( $id );
		if ( in_array( $item['status'], $allowed_statuses ) ) {
			return true;
		} else {
			$this->logger->info("skipped item $id with status ".$item['status']);
			$this->logger->debug("allowed_statuses: ".print_r($allowed_statuses,1) );
			$msg = sprintf( 'Skipped %s item "%s" as its listing status is neither %s', $item['status'], $item['auction_title'], join( $allowed_statuses, ' nor ' ) );
			if ( sizeof($allowed_statuses) == 1 )
				$msg = sprintf( 'Skipped %s item "%s" as its listing status is not %s', $item['status'], $item['auction_title'], join( $allowed_statuses, ' or ' ) );

			if ( $this->is_ajax() ) {
				$this->showMessage( $msg, true, false );
			} else {
				$this->showMessage( $msg, true, true );				
			}

			// create error object
			$errorObj = new stdClass();
			$errorObj->SeverityCode = 'Info';
			$errorObj->ErrorCode 	= 102;
			$errorObj->ShortMessage = 'Invalid listing status';
			$errorObj->LongMessage 	= $this->message;
			$errorObj->HtmlMessage 	= $this->message;
			// $errors[] = $errorObj;

			// save results as local property
			$this->result = new stdClass();
			$this->result->success = false;
			$this->result->errors  = array( $errorObj );

			return false;
		}

	} // itemHasAllowedStatus()


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


	public function getLatestDetails( $ebay_id, $session ) {
		global $wpdb;

		// get item data
		// $item = $this->getItemByEbayID( $id );

		// preparation
		$this->initServiceProxy($session);

		// $this->_cs->setHandler('ItemType', array(& $this, 'updateItemDetail'));

		// download the shipping data
		$req = new GetItemRequestType();
        $req->setItemID( $ebay_id );

		$res = $this->_cs->GetItem($req);		

		// handle response and check if successful
		if ( $this->handleResponse($res) ) {
			$this->logger->info( "Item #$ebay_id was fetched from eBay... ".$res->ItemID );
			return $res->Item;
		} // call successful

		return $this->result;

	}

	public function updateItemDetails( $id, $session ) {
		global $wpdb;

		// get item data
		$item = $this->getItem( $id );

		// preparation
		$this->initServiceProxy($session);

		$this->_cs->setHandler('ItemType', array(& $this, 'handleItemDetail'));

		// download the shipping data
		$req = new GetItemRequestType();
        $req->setItemID( $item['ebay_id'] );
		#$req->setDetailName( 'PaymentOptionDetails' );
		#$req->setActiveList( true );

		$res = $this->_cs->GetItem($req);		

		// handle response and check if successful
		if ( $this->handleResponse($res) ) {
			$this->logger->info( "Item #$id was updated from eBay, ItemID is ".$item['ebay_id'] );
		} // call successful

		return $this->result;

	}


	function handleItemDetail($type, & $Detail)
	{
		global $wpdb;
		
		//#type $Detail ItemType
		
		// map ItemType to DB columns
		$data = $this->mapItemDetailToDB( $Detail );

		$this->logger->debug('Detail: '.print_r($Detail,1) );
		$this->logger->debug('data: '.print_r($data,1) );

		$result = $wpdb->update( $this->tablename, $data, array( 'ebay_id' => $Detail->ItemID ) );
		if ( $result === false ) {
			$this->logger->error('sql: '.$wpdb->last_query );
			$this->logger->error( mysql_error() );		
		}


		// check for an updated ItemID 

		// if item was relisted manually on ebay.com
		if ( $Detail->ListingDetails->RelistedItemID ) {
		
			// keep item id in history
			$this->addItemIdToHistory( $Detail->ItemID, $Detail->ItemID );

			// mark as relisted - ie. should be updated once again
			$wpdb->update( $this->tablename, array( 'status' => 'relisted' ), array( 'ebay_id' => $Detail->ItemID ) );

			// update the listings ebay_id
			$wpdb->update( $this->tablename, array( 'ebay_id' => $Detail->ListingDetails->RelistedItemID ), array( 'ebay_id' => $Detail->ItemID ) );

		}

		// if item was relisted through WP-Lister
		if ( $Detail->RelistParentID ) {
		
			// keep item id in history
			$this->addItemIdToHistory( $Detail->ItemID, $Detail->RelistParentID );

		}

		#$this->logger->info('sql: '.$wpdb->last_query );
		#$this->logger->info( mysql_error() );

		return true;
	} // handleItemDetail()

	function mapItemDetailToDB( $Detail )
	{
		//#type $Detail ItemType
		$data['ebay_id'] 			= $Detail->ItemID;
		$data['auction_title'] 		= $Detail->Title;
		$data['auction_type'] 		= $Detail->ListingType;
		$data['listing_duration'] 	= $Detail->ListingDuration;
		$data['date_published']     = $this->convertEbayDateToSql( $Detail->ListingDetails->StartTime );
		$data['end_date']     		= $this->convertEbayDateToSql( $Detail->ListingDetails->EndTime );
		$data['price'] 				= $Detail->SellingStatus->CurrentPrice->value;
		$data['quantity_sold'] 		= $Detail->SellingStatus->QuantitySold;
		$data['quantity'] 			= $Detail->Quantity;
		$data['ViewItemURL'] 		= $Detail->ListingDetails->ViewItemURL;
		$data['GalleryURL'] 		= $Detail->PictureDetails->GalleryURL;

		// check if this item has variations
		if ( count( @$Detail->Variations->Variation ) > 0 ) {

			$variations = array();
			foreach ($Detail->Variations->Variation as $Variation ) {
				$new_var = array();
				$new_var['sku']      = $Variation->SKU;
				$new_var['price']    = $Variation->StartPrice->value;
				$new_var['stock']    = $Variation->Quantity - $Variation->SellingStatus->QuantitySold;

				$new_var['variation_attributes'] = array();
				foreach ( $Variation->VariationSpecifics as $VariationSpecifics ) {
					$name = $VariationSpecifics->Name;
					$new_var['variation_attributes'][ $name ] = $VariationSpecifics->Value[0]; 
				}
				
				// use SKU as array key - or generate key from attributes
				$key = $Variation->SKU;
				if ( ! $key ) $key = $this->generateVariationKeyFromAttributes( $new_var['variation_attributes'] );

				// add variation to cache
				$variations[$key] = $new_var;
			}
			$data['variations'] = maybe_serialize( $variations );
			$this->logger->info('updated variations cache: '.print_r($variations,1) );
			// echo "<pre>";print_r($variations);echo"</pre>";

			// if this item has variations, we don't update quantity
			unset( $data['quantity'] );
			$this->logger->info('skip quantity for variation #'.$Detail->ItemID );
		}

		// set status to ended if end_date is in the past
		// if ( time() > mysql2date('U', $data['end_date']) ) {

		// set status to ended if ListingStatus is Ended or Completed
		if ( $Detail->SellingStatus->ListingStatus != 'Active' ) {
			$data['status'] 		= 'ended';

			// but mark as sold if no stock remaining
			// $lm = new ListingsModel();
			$item = $this->getItemByEbayID( $data['ebay_id'] );
			if ( $item && ! $this->checkStockLevel( $item ) ) $data['status'] = 'sold';

		} else {
			$data['status'] 		= 'published';			
		}

		$data['details'] = $this->encodeObject( $Detail );

		return $data;
	} // mapItemDetailToDB()



	public function updateListing( $id, $data ) {
		global $wpdb;

		// handle NULL values
		foreach ($data as $key => $value) {
			if ( NULL === $value ) {
				$wpdb->query( "UPDATE {$this->tablename} SET $key = NULL WHERE id = $id" );
				$this->logger->info('SQL to set NULL value: '.$wpdb->last_query );
				$this->logger->info( mysql_error() );
				unset( $data[$key] );
			}
		}

		// update
		$wpdb->update( $this->tablename, $data, array( 'id' => $id ) );

		$this->logger->debug('sql: '.$wpdb->last_query );
		$this->logger->info( mysql_error() );
	}


	public function updateEndedListings( $sm = false ) {
		global $wpdb;

		// set listing status to ended for all listings with an end_date in the past
		$items = $this->getAllPastEndDate();

		foreach ($items as $item) {
			// if quantity sold is greater than quantity, mark as sold instead of ended
			// $status = intval( $item['quantity_sold'] ) < intval( $item['quantity'] ) ? 'ended' : 'sold';
			
			// default status is ended
			$status = 'ended';
			
			// check eBay available quantity first - if all were sold 
			if ( intval( $item['quantity_sold'] ) >= intval( $item['quantity'] ) ) {

				// if eBay indicates item was sold, check WooCommerce stock - updateDetails does the same
				$item = $this->getItemByEbayID( $data['ebay_id'] );
				if ( $item && ! $this->checkStockLevel( $item ) ) 
					$status = 'sold';

			}

			$wpdb->update( $this->tablename, array( 'status' => $status ), array( 'id' => $item['id'] ) );
			$this->logger->info('updateEndedListings() changed item '.$item['ebay_id'].' to status '.$status);
		}


		#$this->logger->info('sql: '.$wpdb->last_query );
		#$this->logger->info( mysql_error() );
	}



	function getItemsForGallery( $type = 'new', $related_to_id, $limit = 12 ) {
		global $wpdb;	

		switch ($type) {
			case 'ending':
				$wpdb->query("SET time_zone='+0:00'"); // tell SQL to use GMT
				$where_sql = "WHERE status = 'published' AND end_date < NOW()";
				$order_sql = "ORDER BY end_date DESC";
				break;
			
			case 'featured':
				$where_sql = "	JOIN {$wpdb->prefix}postmeta pm ON ( li.post_id = pm.post_id )
								WHERE status = 'published' 
								  AND pm.meta_key = '_featured'
								  AND pm.meta_value = 'yes'
							";
				$order_sql = "ORDER BY date_published, end_date DESC";
				break;
			
			case 'related': // combines upsell and crossell
				$listing         = $this->getItem($related_to_id);
				$upsell_ids      = get_post_meta( $listing['post_id'], '_upsell_ids', true );
				$crosssell_ids   = get_post_meta( $listing['post_id'], '_crosssell_ids', true );
				$inner_where_sql = '1 = 0';

				if ( is_array( $upsell_ids ) )
				foreach ($upsell_ids as $post_id) {
					$inner_where_sql .= ' OR post_id = "'.$post_id.'" ';
				}

				if ( is_array( $crosssell_ids ) )
				foreach ($crosssell_ids as $post_id) {
					$inner_where_sql .= ' OR post_id = "'.$post_id.'" ';
				}

				$where_sql = "	WHERE status = 'published' 
								  AND ( $inner_where_sql )
							";
				$order_sql = "ORDER BY date_published, end_date DESC";
				break;
			
			case 'new':
			default:
				$where_sql = "WHERE status = 'published' ";
				$order_sql = "ORDER BY date_published DESC";
				break;
		}

		$items = $wpdb->get_results("
			SELECT *
			FROM $this->tablename li
			$where_sql
			$order_sql
			LIMIT $limit
		", ARRAY_A);		

		if ( $type == 'ending' )
			$wpdb->query("SET time_zone='SYSTEM'"); // revert back to original

		return $items;		
	}

	function getAllSelected() {
		global $wpdb;	
		$items = $wpdb->get_results("
			SELECT * 
			FROM $this->tablename
			WHERE status = 'selected'
			   OR status = 'reselected'
			   OR status = 'changed_profile'
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
			   OR status = 'relisted'
			ORDER BY id DESC
		", ARRAY_A);		

		return $items;		
	}
	function getAllArchived() {
		global $wpdb;	
		$items = $wpdb->get_results("
			SELECT * 
			FROM $this->tablename
			WHERE status = 'archived'
			ORDER BY id DESC
		", ARRAY_A);		

		return $items;		
	}
	function getAllEnded() {
		global $wpdb;	
		$items = $wpdb->get_results("
			SELECT * 
			FROM $this->tablename
			WHERE status = 'ended'
			ORDER BY id DESC
		", ARRAY_A);		

		return $items;		
	}
	function getAllRelisted() {
		global $wpdb;	
		$items = $wpdb->get_results("
			SELECT * 
			FROM $this->tablename
			WHERE status = 'relisted'
			ORDER BY id DESC
		", ARRAY_A);		

		return $items;		
	}
	function getAllScheduled( $pending_only = false ) {
		global $wpdb;	

		// by default only return pending listings - relist dates in the past
		$condition = $pending_only ? 'AND relist_date < NOW()' : ''; 

		$items = $wpdb->get_results("
			SELECT * 
			FROM $this->tablename
			WHERE status = 'ended'
			  AND relist_date IS NOT NULL
			  $condition
			ORDER BY relist_date ASC
		", ARRAY_A);		

		return $items;		
	}
	function getAllWithProfile( $profile_id ) {
		global $wpdb;	
		$items = $wpdb->get_results("
			SELECT * 
			FROM $this->tablename
			WHERE profile_id = '$profile_id'
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
	function getAllVerifiedWithProfile( $profile_id ) {
		global $wpdb;	
		$items = $wpdb->get_results("
			SELECT * 
			FROM $this->tablename
			WHERE status = 'verified'
			  AND profile_id = '$profile_id'
			ORDER BY id DESC
		", ARRAY_A);		

		return $items;		
	}
	function getAllPublishedWithProfile( $profile_id ) {
		global $wpdb;	
		$items = $wpdb->get_results("
			SELECT * 
			FROM $this->tablename
			WHERE ( status = 'published' OR status = 'changed' )
			  AND profile_id = '$profile_id'
			ORDER BY id DESC
		", ARRAY_A);		

		return $items;		
	}
	function getAllEndedWithProfile( $profile_id ) {
		global $wpdb;	
		$items = $wpdb->get_results("
			SELECT * 
			FROM $this->tablename
			WHERE status = 'ended'
			  AND profile_id = '$profile_id'
			ORDER BY id DESC
		", ARRAY_A);		

		return $items;		
	}
	function getAllLockedWithProfile( $profile_id ) {
		global $wpdb;	
		$items = $wpdb->get_results("
			SELECT * 
			FROM $this->tablename
			WHERE locked = '1'
			  AND profile_id = '$profile_id'
			ORDER BY id DESC
		", ARRAY_A);		

		return $items;		
	}
	function getAllPreparedWithTemplate( $template ) {
		global $wpdb;	
		$items = $wpdb->get_results("
			SELECT * 
			FROM $this->tablename
			WHERE status = 'prepared'
			  AND template LIKE '%$template'
			ORDER BY id DESC
		", ARRAY_A);		

		return $items;		
	}
	function getAllVerifiedWithTemplate( $template ) {
		global $wpdb;	
		$items = $wpdb->get_results("
			SELECT * 
			FROM $this->tablename
			WHERE status = 'verified'
			  AND template LIKE '%$template'
			ORDER BY id DESC
		", ARRAY_A);		

		return $items;		
	}
	function getAllPublishedWithTemplate( $template ) {
		global $wpdb;	
		$items = $wpdb->get_results("
			SELECT * 
			FROM $this->tablename
			WHERE ( status = 'published' OR status = 'changed' )
			  AND template LIKE '%$template'
			ORDER BY id DESC
		", ARRAY_A);		

		return $items;		
	}
	function getAllPastEndDate() {
		global $wpdb;	
		$wpdb->query("SET time_zone='+0:00'"); // tell SQL to use GMT
		$items = $wpdb->get_results("
			SELECT id 
			FROM $this->tablename
			WHERE NOT status = 'ended'
			  AND NOT status = 'sold'
			  AND NOT status = 'archived'
			  AND NOT listing_duration = 'GTC'
			  AND end_date < NOW()
			ORDER BY id DESC
		", ARRAY_A);		
		$wpdb->query("SET time_zone='SYSTEM'"); // revert back to original

		return $items;		
	}
	function getItemsByIdArray( $listing_ids ) {
		global $wpdb;	
		if ( ! is_array( $listing_ids )  ) return array();
		if ( sizeof( $listing_ids ) == 0 ) return array();

		$where = ' id = ' . join( ' OR id = ', $listing_ids);
		$items = $wpdb->get_results("
			SELECT * 
			FROM $this->tablename
			WHERE $where
			ORDER BY id DESC
		", ARRAY_A);		
		echo mysql_error();

		return $items;		
	}

	function getAllDuplicateProducts() {
		global $wpdb;	
		$items = $wpdb->get_results("
			SELECT post_id, COUNT(*) c
			FROM $this->tablename
			WHERE NOT status = 'archived'
			GROUP BY post_id 
			HAVING c > 1
		", OBJECT_K);		

		if ( ! empty($items) ) {
			foreach ($items as &$item) {
				
				$listings = $this->getAllListingsFromPostID( $item->post_id );
				$item->listings = $listings;

			}
		}

		return $items;		
	}

	function getRawPostExcerpt( $post_id ) {
		global $wpdb;	
		$excerpt = $wpdb->get_var("
			SELECT post_excerpt 
			FROM {$wpdb->prefix}posts
			WHERE ID = '$post_id'
		");

		return $excerpt;		
	}



	public function selectedProducts() {
		global $wpdb;	
		$items = $wpdb->get_results("
			SELECT * 
			FROM $this->tablename
			WHERE status = 'selected'
			   OR status = 'reselected'
			   OR status = 'changed_profile'
			ORDER BY id DESC
		", ARRAY_A);		

		return $items;		
	}

	public function setListingQuantity( $post_id, $quantity ) {
		global $wpdb;	
		$wpdb->update( $this->tablename, array( 'quantity' => $quantity ), array( 'post_id' => $post_id ) );
	}

	public function markItemAsModified( $post_id ) {
		global $wpdb;	

		// get single listing for post_id
		$listing_id = $this->getListingIDFromPostID( $post_id );
        $this->reapplyProfileToItem( $listing_id );

        // process all listings for post_id
		$listings = $this->getAllListingsFromPostID( $post_id );
        if ( is_array( $listings ) && ( sizeof( $listings ) > 1 ) ) {
        	foreach ( $listings as $listing_item ) {
		        $this->reapplyProfileToItem( $listing_item->id );
        	}
        }

        // process split variations - fetched by parent_id
		$listings = $this->getAllListingsFromParentID( $post_id );
        if ( is_array( $listings ) ) {
        	foreach ( $listings as $listing_item ) {
		        $this->reapplyProfileToItem( $listing_item->id );
				$this->logger->info('reapplied profile to SPLIT variation for post_id '.$post_id.' - listing_id: '.$listing_item->id );
        	}
        }

        return $listing_id;
        
		// set published items to changed
		// $wpdb->update( $this->tablename, array( 'status' => 'changed' ), array( 'status' => 'published', 'post_id' => $post_id ) );

		// set verified items to prepared
		// $wpdb->update( $this->tablename, array( 'status' => 'prepared' ), array( 'status' => 'verified', 'post_id' => $post_id ) );
	}


	public function reSelectListings( $ids ) {
		global $wpdb;
		foreach( $ids as $id ) {
			$status = $this->getStatus( $id );
			if ( ( $status == 'published' ) || ( $status == 'changed' ) ) {
				$wpdb->update( $this->tablename, array( 'status' => 'changed_profile' ), array( 'id' => $id ) );
			} elseif ( $status == 'ended' ) {
				$wpdb->update( $this->tablename, array( 'status' => 'reselected' ), array( 'id' => $id ) );
			} else {
				$wpdb->update( $this->tablename, array( 'status' => 'selected' ), array( 'id' => $id ) );
			}
		}
	}


	public function processSingleVariationTitle( $title, $variation_attributes ) {
    	
    	$title = trim( $title );
    	if ( ! is_array( $variation_attributes ) ) return $title;

    	foreach ( $variation_attributes as $attrib_name => $attrib_value ) { // wpec?
    		$title .= ' - ' . $attrib_value;
    	}

    	return $title;
	}

	public function prepareListings( $ids ) {
		$listings = array();
		foreach( $ids as $id ) {
			if ( $listing_id = $this->prepareProductForListing( $id ) ) {
				$listings[] = $listing_id;
			}
		}
		return $listings;
	}

	public function prepareProductForListing( $post_id, $post_content = false, $post_title = false, $parent_id = false ) {
		global $wpdb;
		
		// get wp post record
		$post = get_post( $post_id );
		$post_title   = $post_title ? $post_title : $post->post_title;
		$post_content = $post_content ? $post_content : $post->post_content;

		// skip pending products and drafts
		if ( $post->post_status != 'publish' ) { 
			if ( isset( $_REQUEST['action'] ) && $_REQUEST['action'] == 'wpl_prepare_single_listing' )
				$this->showMessage( __('Skipped product draft','wplister') . ': ' . $post_title, 2, 1 );
			return false; 
		}


		// support for qTranslate
		if ( function_exists('qtrans_useCurrentLanguageIfNotFoundUseDefaultLanguage') ) {
			$post_title   = qtrans_useCurrentLanguageIfNotFoundUseDefaultLanguage( $post_title );
			$post_content = qtrans_useCurrentLanguageIfNotFoundUseDefaultLanguage( $post_content );			
		}

		// gather product data
		$data['post_id']       = $post_id;
		$data['parent_id']     = $parent_id;
		$data['auction_title'] = $post_title;
		$data['post_content']  = $post_content;
		$data['price']         = ProductWrapper::getPrice( $post_id );
		$data['status']        = 'selected';
		
		$this->logger->info('insert new auction '.$post_id.' - title: '.$data['auction_title']);
		$this->logger->debug( print_r($post,1) );
		
		// insert in auctions table
		$wpdb->insert( $this->tablename, $data );

		$this->logger->debug('sql: '.$wpdb->last_query );
		$this->logger->debug( mysql_error() );
		
		return $wpdb->insert_id;
		
	}

	function applyProfilePrice( $product_price, $profile_price ) {
		$price = $this->calculateProfilePrice( $product_price, $profile_price );
		$price = apply_filters( 'wplister_ebay_price', $price );
		return $price;
	}

	function calculateProfilePrice( $product_price, $profile_price ) {
		$this->logger->debug('calculateProfilePrice(): '.$product_price.' - '.$profile_price );

		// remove all spaces from profile setting
		$profile_price = str_replace( ' ','', trim($profile_price) );
		
		// return product price if profile is empty
		if ( $profile_price == '' ) return $product_price;
	
		// parse percent syntax
		// examples: +10% | -10% | 90%
		if ( preg_match('/([\+\-]?)([0-9\.]+)(\%)/',$profile_price, $matches) ) {
			$this->logger->debug('percent mode');
			$this->logger->debug('matches:' . print_r($matches,1) );

			$modifier      = $matches[1];
			$value         = $matches[2];
			$fullexpr      = $matches[1].$matches[2].$matches[3];
			$profile_price = str_replace( $fullexpr, '', $profile_price ); // remove matched expression from profile price
			
			if ($modifier == '+') {
				$product_price = $product_price + ( $product_price * $value/100 );							
			} elseif ($modifier == '-') {
				$product_price = $product_price - ( $product_price * $value/100 );				
			} else {
				$product_price =                  ( $product_price * $value/100 );
			}
		
		}
						
		// return product price if profile is empty - or has been emptied
		if ( $profile_price == '' ) return $product_price;


		// parse value syntax
		// examples: +5 | -5 | 5
		if ( preg_match('/([\+\-]?)([0-9\.]+)/',$profile_price, $matches) ) {
			$this->logger->debug('value mode');
			$this->logger->debug('matches:' . print_r($matches,1) );

			$modifier = $matches[1];
			$value = $matches[2];
			
			if ($modifier == '+') {
				$product_price = $product_price + $value;				
			} elseif ($modifier == '-') {
				$product_price = $product_price - $value;				
			} else {
				$product_price =                  $value;
			}
		
		}
	
		return $product_price;		
	
	} // calculateProfilePrice()

	public function applyProfileToItem( $profile, $item, $update_title = true ) {
		global $wpdb;

		// get item data
		$id 		= $item['id'];
		$post_id 	= $item['post_id'];
		$status 	= $this->getStatus( $id );
		$ebay_id 	= $this->getEbayIDFromID( $id );
		$post_title = get_the_title( $item['post_id'] );

		// skip ended auctions - or not, if you want to relist them...
		// if ( $status == 'ended' ) return;

		// use parent title for single (split) variation
		if ( ProductWrapper::isSingleVariation( $post_id ) ) {
			$parent_id = ProductWrapper::getVariationParent( $post_id );
			$post_title = get_the_title( $parent_id );

			// check if parent product has a custom eBay title set
			if ( get_post_meta( $parent_id, '_ebay_title', true ) ) 
				$post_title = trim( get_post_meta( $parent_id, '_ebay_title', true ) );

			// get variations
    	    $variations = ProductWrapper::getVariations( $parent_id );

    	    // find this variation in all variations of this parent
    	    foreach ($variations as $var) {
    	    	if ( $var['post_id'] == $post_id ) {

	    	    	// append attribute values to title
    	    		$post_title = $this->processSingleVariationTitle( $post_title, $var['variation_attributes'] );

    	    	}
    	    }

		}

		// gather profile data
		$data = array();
		$data['profile_id'] 		= $profile['profile_id'];
		$data['auction_type'] 		= $profile['type'];
		$data['listing_duration'] 	= $profile['listing_duration'];
		$data['template'] 			= $profile['details']['template'];
		$data['quantity'] 			= $profile['details']['quantity'];
		$data['date_created'] 		= date( 'Y-m-d H:i:s' );
		$data['profile_data'] 		= $this->encodeObject( $profile );
		// echo "<pre>";print_r($data);echo"</pre>";die();
		
		// add prefix and suffix to product title
		if ( $update_title ) {

			// append space to prefix, prepend space to suffix
			// TODO: make this an option
			$title_prefix = trim( $profile['details']['title_prefix'] ) . ' ';
			$title_suffix = ' ' . trim( $profile['details']['title_suffix'] );

			// custom post meta fields override profile values
			if ( get_post_meta( $post_id, 'ebay_title_prefix', true ) ) {
				$title_prefix = trim( get_post_meta( $post_id, 'ebay_title_prefix', true ) ) . ' ';
			}
			if ( get_post_meta( $post_id, 'ebay_title_suffix', true ) ) {
				$title_prefix = trim( get_post_meta( $post_id, 'ebay_title_suffix', true ) ) . ' ';
			}

			$data['auction_title'] = trim( $title_prefix . $post_title . $title_suffix );

			// custom post meta title override
			if ( get_post_meta( $post_id, '_ebay_title', true ) ) {
				$data['auction_title']  = trim( get_post_meta( $post_id, '_ebay_title', true ) );
			} elseif ( get_post_meta( $post_id, 'ebay_title', true ) ) {
				$data['auction_title']  = trim( get_post_meta( $post_id, 'ebay_title', true ) );
			}

			// process attribute shortcodes in title - like [[attribute_Brand]]
			$templatesModel = new TemplatesModel();
			// $this->logger->info('auction_title before processing: '.$data['auction_title'].'');
			$data['auction_title'] = $templatesModel->processAllTextShortcodes( $item['post_id'], $data['auction_title'], 80 );
			$this->logger->info('auction_title after processing : '.$data['auction_title'].'');

		}

		// apply profile price
		$data['price'] = ProductWrapper::getPrice( $post_id );
		$data['price']  = $this->applyProfilePrice( $data['price'], $profile['details']['start_price'] );
		
		// fetch product stock if no quantity set in profile - and apply max_quantity limit
		if ( intval( $data['quantity'] ) == 0 ) {
			$max = ( isset( $profile['details']['max_quantity'] ) && intval( $profile['details']['max_quantity'] )  > 0 ) ? $profile['details']['max_quantity'] : PHP_INT_MAX ; 
			$data['quantity'] = min( $max , intval( ProductWrapper::getStock( $post_id ) ) );						
		}
		
		// default new status is 'prepared'
		$data['status'] = 'prepared';

		// except for already published items where it is 'changed'
		if ( intval($ebay_id) > 0 ) 		$data['status'] = 'changed';
		
		// ended items stay 'ended' and sold items stay sold
		if ( $status == 'ended' ) 			$data['status'] = 'ended';
		if ( $status == 'sold'  ) 			$data['status'] = 'sold';
		if ( $status == 'archived' ) 		$data['status'] = 'archived';

		// locked items simply keep their status
		if ( @$item['locked'] ) 			$data['status'] = $status;

		// but if apply_changes_to_all_locked checkbox is ticked, even locked published items will be marked as 'changed'
		if ( @$item['locked'] && ($status == 'published') && isset($_POST['wpl_e2e_apply_changes_to_all_locked']) )
											$data['status'] = 'changed';

		// except for selected items which shouldn't be locked in the first place
		if ( $status == 'selected' ) 		$data['status'] = 'prepared';
		// and reselected items which have already been 'ended'
		if ( $status == 'reselected' ) 		$data['status'] = 'ended';
		// and items which have already been 'changed' and now had a new profile applied
		if ( $status == 'changed_profile' ) $data['status'] = 'changed';

		// debug
		if ( $status != $data['status'] ) {
			$this->logger->info('applyProfileToItem('.$id.') old status: '.$status );
			$this->logger->info('applyProfileToItem('.$id.') new status: '.$data['status'] );
		}

		// update auctions table
		$wpdb->update( $this->tablename, $data, array( 'id' => $id ) );

		// $this->logger->info('updating listing ID '.$id);
		// $this->logger->info('data: '.print_r($data,1));
		// $this->logger->info('sql: '.$wpdb->last_query);
		// $this->logger->info('error: '.mysql_error());


	} // applyProfileToItem()

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
		if ( !$id ) return;
		$item = $this->getItem( $id );
		if ( empty($item) ) return;

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
