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

        // filter listing_status
        $where_sql = '';
		$listing_status = ( isset($_REQUEST['listing_status']) ? $_REQUEST['listing_status'] : 'all');
		if ( $listing_status != 'all' ) {
			$where_sql = "WHERE status = '".$listing_status."'";
		} 

        // filter search_query
		$search_query = ( isset($_REQUEST['s']) ? $_REQUEST['s'] : false);
		if ( $search_query ) {
			$where_sql = "
				WHERE auction_title LIKE '%".$search_query."%'
				   OR ebay_id = '".$search_query."'
			";
		} 


        // get items
		$items = $wpdb->get_results("
			SELECT *
			FROM $this->tablename
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

		return $useFixedPriceItem;
	} 

	// handle additional requests after AddItem(), ReviseItem(), etc.
	function postProcessListing( $id, $ItemID, $item, $listing_item, $res, $session ) {
	}

	function addItem( $id, $session )
	{
		// skip this item if item status not allowed
		$allowed_statuses = array( 'prepared', 'verified' );
		if ( ! $this->itemHasAllowedStatus( $id, $allowed_statuses ) ) return false;

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
		if ( ! $this->itemHasAllowedStatus( $id, $allowed_statuses ) ) return false;

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

	function reviseItem( $id, $session )
	{
		// skip this item if item status not allowed
		$allowed_statuses = array( 'published', 'changed' );
		if ( ! $this->itemHasAllowedStatus( $id, $allowed_statuses ) ) return false;

		// check if product has variations
		$listing_item = $this->getItem( $id );
		// $useFixedPriceItem = ( ProductWrapper::hasVariations( $listing_item['post_id'] ) ) ? true : false;
		// $useFixedPriceItem = ( 'FixedPriceItem' == $listing_item['auction_type'] ) ? true : false;

		// build item
		$ibm = new ItemBuilderModel();
		$item = $ibm->buildItem( $id, $session, true );
		if ( ! $ibm->checkItem($item) ) return $ibm->result;

		// if quantity is zero, end item instead
		if ( ( $item->Quantity == 0 ) && ( ! $ibm->VariationsHaveStock ) ) {
			$this->logger->info( "Item #$id has no stock, switching to endItem()" );
			return $this->endItem( $id, $session );
		}
		
		// eBay Motors (beta)
		if ( $item->Site == 'eBayMotors' ) $session->setSiteId( 100 );

		// preparation - set up new ServiceProxy with given session
		$this->initServiceProxy($session);

		// set ItemID to revise
		$item->setItemID( $this->getEbayIDFromID($id) );
		$this->logger->info( "Revising #$id: ".$p['auction_title'] );

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

			// save ebay ID and fees to db
			#$listingFee = $this->getListingFeeFromResponse( $res );
			#$data['ebay_id'] = $res->ItemID;
			#$data['fees'] = $listingFee;
			$data['status'] = 'published';
			$this->updateListing( $id, $data );
			
			// get details like ViewItemURL from ebay automatically
			#$this->updateItemDetails( $id, $session );
			$this->postProcessListing( $id, $res->ItemID, $item, $listing_item, $res, $session );

			$this->logger->info( "Item #$id was revised, ItemID is ".$res->ItemID );

		} // call successful

		return $this->result;

	} // reviseItem()


	function verifyAddItem( $id, $session )
	{
		// skip this item if item status not allowed
		$allowed_statuses = array( 'prepared', 'verified' );
		if ( ! $this->itemHasAllowedStatus( $id, $allowed_statuses ) ) return false;

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
		if ( ! $this->itemHasAllowedStatus( $id, $allowed_statuses ) ) return false;

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


	function itemHasAllowedStatus( $id, $allowed_statuses )
	{
		$item = $this->getItem( $id );
		if ( in_array( $item['status'], $allowed_statuses ) ) {
			return true;
		} else {
			$this->logger->info("skipped item $id with status ".$item['status']);
			$this->logger->debug("allowed_statuses: ".print_r($allowed_statuses,1) );
			$this->showMessage( sprintf( 'Skipped %s item "%s" as its listing status is not %s', $item['status'], $item['auction_title'], join( $allowed_statuses, ' or ' ) ), false, true );
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
			$this->logger->info('skip quantity for variation #'.$Detail->ItemID );
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



	function getItemsForGallery( $type = 'new', $related_to_id, $limit = 12 ) {
		global $wpdb;	

		switch ($type) {
			case 'ending':
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

				foreach ($upsell_ids as $post_id) {
					$inner_where_sql .= ' OR post_id = "'.$post_id.'" ';
				}
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

		return $items;		
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
			   OR status = 'relisted'
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
		$items = $wpdb->get_results("
			SELECT id 
			FROM $this->tablename
			WHERE NOT status = 'ended'
			  AND NOT listing_duration = 'GTC'
			  AND end_date < NOW()
			ORDER BY id DESC
		", ARRAY_A);		

		return $items;		
	}

	function getAllDuplicateProducts() {
		global $wpdb;	
		$items = $wpdb->get_results("
			SELECT post_id, COUNT(*) c
			FROM $this->tablename
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

		// $listingsModel = new ListingsModel();
		$listing_id = $this->getListingIDFromPostID( $post_id );
        $this->reapplyProfileToItem( $listing_id );

		// set published items to changed
		// $wpdb->update( $this->tablename, array( 'status' => 'changed' ), array( 'status' => 'published', 'post_id' => $post_id ) );

		// set verified items to prepared
		// $wpdb->update( $this->tablename, array( 'status' => 'prepared' ), array( 'status' => 'verified', 'post_id' => $post_id ) );
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
			$data['auction_title'] = $templatesModel->processAllTextShortcodes( $item['post_id'], $data['auction_title'] );
			$this->logger->info('auction_title after processing : '.$data['auction_title'].'');

		}

		// apply profile price
		$data['price'] = ProductWrapper::getPrice( $post_id );
		$data['price']  = $this->applyProfilePrice( $data['price'], $profile['details']['start_price'] );
		
		// fetch product stock if no quantity set in profile
		if ( intval( $data['quantity'] ) == 0 ) {
			$data['quantity'] = intval( ProductWrapper::getStock( $post_id ) );
		}
		
		// default new status is 'prepared'
		$data['status'] = 'prepared';
		// except for already published items where it is 'changed'
		if ( intval($ebay_id) > 0 ) $data['status'] = 'changed';

		// update auctions table
		$wpdb->update( $this->tablename, $data, array( 'id' => $id ) );

		// $this->logger->info('updating listing ID '.$id);
		// $this->logger->info('data: '.print_r($data,1));
		// $this->logger->info('sql: '.$wpdb->last_query);
		// $this->logger->info('error: '.mysql_error());


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
