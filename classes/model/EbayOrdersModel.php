<?php
/**
 * EbayOrdersModel class
 *
 * responsible for managing orders and talking to ebay
 * 
 */

class EbayOrdersModel extends WPL_Model {
	var $_session;
	var $_cs;

	var $count_total    = 0;
	var $count_skipped  = 0;
	var $count_updated  = 0;
	var $count_inserted = 0;
	var $count_failed   = 0;
	var $report         = array();
	var $ModTimeTo      = false;
	var $ModTimeFrom    = false;
	var $NumberOfDays   = false;

	var $total_items;
	var $total_pages;
	var $current_page;
	var $current_lastdate;

	function EbayOrdersModel() {
		global $wpl_logger;
		$this->logger = &$wpl_logger;

		global $wpdb;
		$this->tablename = $wpdb->prefix . 'ebay_orders';
	}


	function updateOrders( $session, $days = null, $current_page = 1, $order_ids = false ) {
		$this->logger->info('*** updateOrders('.$days.') - page '.$current_page);

		$this->initServiceProxy($session);

		// set request handler
		$this->_cs->setHandler( 'OrderType', array( & $this, 'handleOrderType' ) );
		// $this->_cs->setHandler( 'PaginationResultType', array( & $this, 'handlePaginationResultType' ) );

		// build request
		$req = new GetOrdersRequestType();
		$req->setOrderRole( 'Seller' );
		// $req->setIncludeContainingOrder(true);

		// check if we need to calculate lastdate
		if ( $this->current_lastdate ) {
			$lastdate = $this->current_lastdate;
			$this->logger->info('used current_lastdate from last run: '.$lastdate);
		} else {

			// period 30 days, which is the maximum allowed
			$now = time();
			$lastdate = $this->getDateOfLastOrder();
			$this->logger->info('getDateOfLastOrder() returned: '.$lastdate);
			if ($lastdate) $lastdate = mysql2date('U', $lastdate);

			// if last date is older than 30 days, fall back to default
			if ( $lastdate < $now - 3600 * 24 * 30 ) {
				$this->logger->info('resetting lastdate - fall back default ');
				$lastdate = false;
			} 

		}

		// save lastdate for next page
		$this->current_lastdate = $lastdate;

		// fetch orders by IDs
		if ( is_array( $order_ids ) ) {
			$OrderIDArray = new OrderIDArrayType();
			foreach ( $order_ids as $id ) {
				$order = $this->getItem( $id );
				$OrderIDArray->addOrderID( $order['order_id'] );
			}
			$req->setOrderIDArray( $OrderIDArray );
		// parameter $days
		} elseif ( $days ) {
			$req->NumberOfDays  = $days;
			$this->NumberOfDays = $days;
			$this->logger->info('NumberOfDays: '.$req->NumberOfDays);

		// default: orders since last change
		} elseif ( $lastdate ) {
			$req->ModTimeFrom  = gmdate( 'Y-m-d H:i:s', $lastdate );
			$req->ModTimeTo    = gmdate( 'Y-m-d H:i:s', time() );
			$this->ModTimeFrom = $req->ModTimeFrom;
			$this->ModTimeTo   = $req->ModTimeTo;
			$this->logger->info('lastdate: '.$lastdate);
			$this->logger->info('ModTimeFrom: '.$req->ModTimeFrom);
			$this->logger->info('ModTimeTo: '.$req->ModTimeTo);

		// fallback: last 7 days (max allowed by ebay: 30 days)
		} else {
			$days = 7;
			$req->NumberOfDays  = $days;
			$this->NumberOfDays = $days;
			$this->logger->info('NumberOfDays (fallback): '.$req->NumberOfDays);
		}


		// $req->DetailLevel = $Facet_DetailLevelCodeType->ReturnAll;
		if ( ! $this->is_ajax() ) $req->setDetailLevel('ReturnAll');

		// set pagination for first page
		$items_per_page = 100; // should be set to 200 for production
		$this->current_page = $current_page;

		$Pagination = new PaginationType();
		$Pagination->setEntriesPerPage( $items_per_page );
		$Pagination->setPageNumber( $this->current_page );
		$req->setPagination( $Pagination );


		// get orders (single page)
		$this->logger->info('fetching orders - page '.$this->current_page);
		$res = $this->_cs->GetOrders( $req );

		$this->total_pages = $res->PaginationResult->TotalNumberOfPages;
		$this->total_items = $res->PaginationResult->TotalNumberOfEntries;

		// get order with pagination helper (doesn't work as expected)
		// EbatNs_PaginationHelper($proxy, $callName, $request, $responseElementToMerge = '__COUNT_BY_HANDLER', $maxEntries = 200, $pageSize = 200, $initialPage = 1)
		// $helper = new EbatNs_PaginationHelper( $this->_cs, 'GetOrders', $req, 'OrderArray', 20, 10, 1);
		// $res = $helper->QueryAll();


		// handle response and check if successful
		if ( $this->handleResponse($res) ) {
			$this->logger->info( "*** Orders updated successfully." );
			// $this->logger->info( "*** PaginationResult:".print_r($res->PaginationResult,1) );
			// $this->logger->info( "*** processed response:".print_r($res,1) );

			$this->logger->info( "*** current_page: ".$this->current_page );
			$this->logger->info( "*** total_pages: ".$this->total_pages );
			$this->logger->info( "*** total_items: ".$this->total_items );

			// fetch next page recursively - only in days mode
			if ( $res->HasMoreOrders ) {
				$this->current_page++;
				$this->updateOrders( $session, $days, $this->current_page );
			}


		} else {
			$this->logger->error( "Error on orders update".print_r( $res, 1 ) );			
		}
	}

	// function updateSingleOrder( $session, $id ) {

	// 	$this->initServiceProxy($session);

	// 	// get order item to update
	// 	$order = $this->getItem( $id );

	// 	// build request
	// 	$req = new GetItemOrdersRequestType();
	// 	$req->ItemID 		= $order['item_id'];
	// 	$req->OrderID = $order['order_id'];

	// 	$this->logger->info('ItemID: '.$req->ItemID);
	// 	$this->logger->info('OrderID: '.$req->OrderID);

	// 	// $req->DetailLevel = $Facet_DetailLevelCodeType->ReturnAll;
	// 	$req->setDetailLevel('ReturnAll');
	// 	$req->setIncludeContainingOrder(true);

	// 	// download the data
	// 	$res = $this->_cs->GetItemOrders( $req );

	// 	// handle response and check if successful
	// 	if ( $this->handleResponse($res) ) {

	// 		// since GetItemOrders returns the Item object outside of the Order object, 
	// 		// we need to rearrange it before we pass it to handleOrderType()
	// 		$Order = $res->OrderArray[0];
	// 		$Order->Item = $res->Item;
	// 		$this->handleOrderType( 'OrderType', $Order );

	// 		$this->logger->info( sprintf("Order %s updated successfully.", $req->OrderID ) );
	// 	} else {
	// 		$this->logger->error( "Error on orders update".print_r( $res, 1 ) );			
	// 	}
	// }

	// function handlePaginationResultType( $type, & $Detail ) {
	// 	//#type $Detail PaginationResultType
	// 	$this->total_pages = $Detail->TotalNumberOfPages;
	// 	$this->total_items = $Detail->TotalNumberOfEntries;
	// 	$this->logger->info( 'handlePaginationResultType()'.print_r( $Detail, 1 ) );
	// }

	function handleOrderType( $type, & $Detail ) {
		//global $wpdb;
		//#type $Detail OrderType
		$this->logger->info( 'handleOrderType()'.print_r( $Detail, 1 ) );

		// map OrderType to DB columns
		$data = $this->mapItemDetailToDB( $Detail );
		if (!$data) return true;


		// check if item has variation 
		$hasVariations = false;
		$VariationSpecifics = array();
        if ( is_object( @$Detail->Variation ) ) {
			foreach ($Detail->Variation->VariationSpecifics as $spec) {
                $VariationSpecifics[ $spec->Name ] = $spec->Value[0];
            }
			$hasVariations = true;
        } 

		// handle variation
		if ( $hasVariations ) {
			// use variation title
			// $data['item_title'] = $Detail->Variation->VariationTitle;
		}


		$this->insertOrUpdate( $data, $hasVariations, $VariationSpecifics );

		// this will remove item from result
		return true;
	}
	function insertOrUpdate( $data, $hasVariations, $VariationSpecifics ) {
		global $wpdb;

		// try to get existing order by order id
		$order = $this->getOrderByOrderID( $data['order_id'] );

		if ( $order ) {

			// update existing order
			$this->logger->info( 'update order #'.$data['order_id'].' for item #'.$data['item_id'] );
			$wpdb->update( $this->tablename, $data, array( 'order_id' => $data['order_id'] ) );


			$this->addToReport( 'updated', $data );
		
		} else {
		
			// create new order
			$this->logger->info( 'insert order #'.$data['order_id'].' for item #'.$data['item_id'] );
			$result = $wpdb->insert( $this->tablename, $data );
			if ( ! $result ) {
				$this->logger->error( 'insert order failed - MySQL said: '.$wpdb->last_error );
				$this->addToReport( 'error', $data, false, $wpdb->last_error );
				return false;
			}
			$Details       = maybe_unserialize( $data['details'] );
			$order_post_id = false;
			$id            = $wpdb->insert_id;
			// $this->logger->info( 'insert_id: '.$id );

			// process order line items
			foreach ( $Details->TransactionArray as $Transaction ) {

				// update listing sold quantity and status
				$this->processListingItem( $data['order_id'], $Transaction->Item->ItemID, $Transaction->QuantityPurchased, $data, $VariationSpecifics );

			}



			$this->addToReport( 'inserted', $data, $order_post_id );

		}

	} // insertOrUpdate()


	// update listing sold quantity and status
	function processListingItem( $order_id, $ebay_id, $quantity_purchased, $data, $VariationSpecifics ) {
		global $wpdb;

		// check if this listing exists in WP-Lister
		$listing_id = $wpdb->get_var( 'SELECT id FROM '.$wpdb->prefix.'ebay_auctions WHERE ebay_id = '.$ebay_id );
		if ( ! $listing_id ) {
			$history_message = "Skipped foreign item #{$ebay_id}";
			$history_details = array( 'ebay_id' => $ebay_id );
			$this->addHistory( $order_id, 'skipped_item', $history_message, $history_details );
			return;
		}

		// get current values from db
		// $quantity_purchased = $data['quantity'];
		$quantity_total = $wpdb->get_var( 'SELECT quantity FROM '.$wpdb->prefix.'ebay_auctions WHERE ebay_id = '.$ebay_id );
		$quantity_sold = $wpdb->get_var( 'SELECT quantity_sold FROM '.$wpdb->prefix.'ebay_auctions WHERE ebay_id = '.$ebay_id );

		// increase the listing's quantity_sold
		$quantity_sold = $quantity_sold + $quantity_purchased;
		$wpdb->update( $wpdb->prefix.'ebay_auctions', 
			array( 'quantity_sold' => $quantity_sold ), 
			array( 'ebay_id' => $ebay_id ) 
		);

		// add history record
		$history_message = "Sold quantity increased by $quantity_purchased for listing #{$ebay_id} - sold $quantity_sold";
		$history_details = array( 'newstock' => $newstock, 'ebay_id' => $ebay_id );
		$this->addHistory( $order_id, 'reduce_stock', $history_message, $history_details );


		// mark listing as sold when last item is sold
		if ( $quantity_sold == $quantity_total ) {
			$wpdb->update( $wpdb->prefix.'ebay_auctions', 
				array( 'status' => 'sold', 'date_finished' => $data['date_created'], ), 
				array( 'ebay_id' => $ebay_id ) 
			);
			$this->logger->info( 'marked item #'.$ebay_id.' as SOLD ');
		}



	} // processListingItem()


	// add order history entry
	function addHistory( $order_id, $action, $msg, $details = array(), $success = true ) {
		global $wpdb;

		// build history record
		$record = new stdClass();
		$record->action  = $action;
		$record->msg     = $msg;
		$record->details = $details;
		$record->success = $success;
		$record->time    = time();

		// load history
		$history = $wpdb->get_var( "
			SELECT history
			FROM $this->tablename
			WHERE order_id = '$order_id'
		" );

		// init with empty array
		$history = maybe_unserialize( $history );
		if ( ! $history ) $history = array();

		// add record
		$history[] = $record;

		// update history
		$history = serialize( $history );
		$wpdb->query( "
			UPDATE $this->tablename
			SET history = '$history'
			WHERE order_id = '$order_id'
		" );

	}

	// convert 2013-02-14T08:00:58.000Z to 2013-02-14 08:00:58
	function convertEbayDateToSql( $ebay_date ) {
		$search = array( 'T', '.000Z' );
		$replace = array( ' ', '' );
		$sql_date = str_replace( $search, $replace, $ebay_date );
		return $sql_date;
	}

	function mapItemDetailToDB( $Detail ) {
		//#type $Detail OrderType

		$data['date_created']              = $this->convertEbayDateToSql( $Detail->CreatedTime );
		$data['LastTimeModified']          = $this->convertEbayDateToSql( $Detail->CheckoutStatus->LastModifiedTime );

		$data['order_id']            	   = $Detail->OrderID;
		$data['total']                     = $Detail->Total->value;
		$data['buyer_userid']              = $Detail->BuyerUserID;

		$data['CompleteStatus']            = $Detail->OrderStatus;
		$data['eBayPaymentStatus']         = $Detail->CheckoutStatus->eBayPaymentStatus;
		$data['PaymentMethod']             = $Detail->CheckoutStatus->PaymentMethod;
		$data['CheckoutStatus']            = $Detail->CheckoutStatus->Status;

		$data['ShippingService']           = $Detail->ShippingServiceSelected->ShippingService;
		$data['ShippingAddress_City']      = $Detail->ShippingAddress->CityName;
		$data['buyer_name']                = $Detail->Buyer->RegistrationAddress->Name;
		$data['buyer_email']               = $Detail->TransactionArray[0]->Buyer->Email;

		// use buyer name from shipping address if registration address is empty
		if ( $data['buyer_name'] == '' ) {
			$data['buyer_name'] = $Detail->ShippingAddress->Name;
		}

		// process transactions / items
		$items = array();
		foreach ( $Detail->TransactionArray as $Transaction ) {
			$newitem = array();
			$newitem['item_id'] = $Transaction->Item->ItemID;
			$newitem['title'] = $Transaction->Item->Title;
			$newitem['sku'] = $Transaction->Item->SKU;
			$newitem['quantity'] = $Transaction->QuantityPurchased;
			$newitem['transaction_id'] = $Transaction->TransactionID;
			$newitem['OrderLineItemID'] = $Transaction->OrderLineItemID;
			$newitem['TransactionPrice'] = $Transaction->TransactionPrice->value;
			$items[] = $newitem;
			// echo "<pre>";print_r($Transaction);echo"</pre>";die();
		}
		$data['items'] = serialize( $items );


        // save GetOrders reponse in details
		$data['details'] = $this->encodeObject( $Detail );

		$this->logger->info( "IMPORTED order #".$Detail->OrderID );							

		return $data;
	}


	function addToReport( $status, $data, $wp_order_id = false, $error = false ) {

		$rep = new stdClass();
		$rep->status           = $status;
		$rep->order_id         = $data['order_id'];
		$rep->date_created     = $data['date_created'];
		$rep->OrderLineItemID  = $data['OrderLineItemID'];
		$rep->LastTimeModified = $data['LastTimeModified'];
		$rep->total            = $data['total'];
		$rep->data             = $data;
		// $rep->newstock         = $newstock;
		$rep->wp_order_id      = $wp_order_id;
		$rep->error            = $error;

		$this->report[] = $rep;

		switch ($status) {
			case 'skipped':
				$this->count_skipped++;
				break;
			case 'updated':
				$this->count_updated++;
				break;
			case 'inserted':
				$this->count_inserted++;
				break;
			case 'error':
			case 'failed':
				$this->count_failed++;
				break;
		}
		$this->count_total++;

	}

	function getHtmlTimespan() {
		if ( $this->NumberOfDays ) {
			return sprintf( __('the last %s days','wplister'), $this->NumberOfDays );
		} elseif ( $this->ModTimeFrom ) {
			return sprintf( __('from %s to %s','wplister'), $this->ModTimeFrom , $this->ModTimeTo );
		}
	}

	function getHtmlReport() {

		$html  = '<div id="ebay_order_report" style="display:none">';
		$html .= '<br>';
		$html .= __('New orders created','wplister') .': '. $this->count_inserted .' '. '<br>';
		$html .= __('Existing orders updated','wplister')  .': '. $this->count_updated  .' '. '<br>';
		if ( $this->count_skipped ) $html .= __('Foreign orders skipped','wplister')  .': '. $this->count_skipped  .' '. '<br>';
		if ( $this->count_failed ) $html .= __('Orders failed to create','wplister')  .': '. $this->count_failed  .' '. '<br>';
		$html .= '<br>';

		if ( $this->count_skipped ) $html .= __('Note: Foreign orders for which no matching item ID could be found in WP-Lister\'s listings table were skipping during update.','wplister') . '<br><br>';

		$html .= '<table style="width:99%">';
		$html .= '<tr>';
		$html .= '<th align="left">'.__('Last modified','wplister').'</th>';
		$html .= '<th align="left">'.__('Order ID','wplister').'</th>';
		$html .= '<th align="left">'.__('Action','wplister').'</th>';
		$html .= '<th align="left">'.__('Total','wplister').'</th>';
		// $html .= '<th align="left">'.__('Title','wplister').'</th>';
		$html .= '<th align="left">'.__('Buyer ID','wplister').'</th>';
		$html .= '<th align="left">'.__('Date created','wplister').'</th>';
		$html .= '</tr>';
		
		foreach ($this->report as $item) {
			$html .= '<tr>';
			$html .= '<td>'.$item->LastTimeModified.'</td>';
			$html .= '<td>'.$item->order_id.'</td>';
			$html .= '<td>'.$item->status.'</td>';
			$html .= '<td>'.$item->total.'</td>';
			// $html .= '<td>'.@$item->data['item_title'].'</td>';
			$html .= '<td>'.@$item->data['buyer_userid'].'</td>';
			$html .= '<td>'.$item->date_created.'</td>';
			$html .= '</tr>';
			if ( $item->error ) {
				$html .= '<tr>';
				$html .= '<td colspan="7" style="color:darkred;">ERROR: '.$item->error.'</td>';
				$html .= '</tr>';			
			}
		}

		$html .= '</table>';
		$html .= '</div>';
		return $html;
	}

	/* the following methods could go into another class, since they use wpdb instead of EbatNs_DatabaseProvider */

	function getAll() {
		global $wpdb;
		$profiles = $wpdb->get_results( "
			SELECT *
			FROM $this->tablename
			ORDER BY id DESC
		", ARRAY_A );

		return $profiles;
	}

	function getItem( $id ) {
		global $wpdb;

		$item = $wpdb->get_row( "
			SELECT *
			FROM $this->tablename
			WHERE id = '$id'
		", ARRAY_A );

		// decode OrderType object with eBay classes loaded
		$item['details'] = $this->decodeObject( $item['details'], false, true );
		$item['history'] = maybe_unserialize( $item['history'] );
		$item['items']   = maybe_unserialize( $item['items'] );

		return $item;
	}

	function getOrderByOrderID( $order_id ) {
		global $wpdb;

		$order = $wpdb->get_row( "
			SELECT *
			FROM $this->tablename
			WHERE order_id = '$order_id'
		", ARRAY_A );

		return $order;
	}

	function getDateOfLastOrder() {
		global $wpdb;
		$lastdate = $wpdb->get_var( "
			SELECT LastTimeModified
			FROM $this->tablename
			ORDER BY LastTimeModified DESC LIMIT 1
		" );

		// if there are no orders yet, check the date of the last transaction
		if ( ! $lastdate ) {
			$tm = new TransactionsModel();
			$lastdate = $tm->getDateOfLastCreatedTransaction();
			if ($lastdate) {
				// add one minute to prevent importing the same transaction again
				$lastdate = mysql2date('U', $lastdate) + 60;
				$lastdate = date('Y-m-d H:i:s', $lastdate );
			}
		}
		return $lastdate;
	}

	function deleteItem( $id ) {
		global $wpdb;
		$wpdb->query( "
			DELETE
			FROM $this->tablename
			WHERE id = '$id'
		" );
	}

	function updateWpOrderID( $id, $wp_order_id ) {
		global $wpdb;
		$wpdb->query( "
			UPDATE $this->tablename
			SET post_id = '$wp_order_id'
			WHERE id = '$id'
		" );
		echo mysql_error();
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

		// foreach( $items as &$profile ) {
		// 	$profile['details'] = $this->decodeObject( $profile['details'] );
		// }

		return $items;
	}




}
