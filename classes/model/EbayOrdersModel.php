<?php
/**
 * EbayOrdersModel class
 *
 * responsible for managing orders and talking to ebay
 * 
 */

class EbayOrdersModel extends WPL_Model {

	const TABLENAME = 'ebay_orders';

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
		// global $wpl_logger;
		// $this->logger = &$wpl_logger;

		global $wpdb;
		$this->tablename = $wpdb->prefix . 'ebay_orders';
	}


	function updateOrders( $session, $days = null, $current_page = 1, $order_ids = false ) {
		WPLE()->logger->info('*** updateOrders('.$days.') - page '.$current_page);

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
			WPLE()->logger->info('used current_lastdate from last run: '.$lastdate);
		} else {

			// period 30 days, which is the maximum allowed
			$now = time();
			$lastdate = $this->getDateOfLastOrder( $this->account_id );
			WPLE()->logger->info("getDateOfLastOrder( {$this->account_id} ) returned: ".$lastdate);
			if ($lastdate) $lastdate = mysql2date('U', $lastdate);

			// if last date is older than 30 days, fall back to default
			if ( $lastdate < $now - 3600 * 24 * 30 ) {
				WPLE()->logger->info('resetting lastdate - fall back default ');
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
			WPLE()->logger->info('NumberOfDays: '.$req->NumberOfDays);

		// default: orders since last change
		} elseif ( $lastdate ) {
			$req->ModTimeFrom  = gmdate( 'Y-m-d H:i:s', $lastdate );
			$req->ModTimeTo    = gmdate( 'Y-m-d H:i:s', time() );
			$this->ModTimeFrom = $req->ModTimeFrom;
			$this->ModTimeTo   = $req->ModTimeTo;
			WPLE()->logger->info('lastdate: '.$lastdate);
			WPLE()->logger->info('ModTimeFrom: '.$req->ModTimeFrom);
			WPLE()->logger->info('ModTimeTo: '.$req->ModTimeTo);

		// fallback: one day (max allowed by ebay: 30 days)
		} else {
			$days = 1;
			$req->NumberOfDays  = $days;
			$this->NumberOfDays = $days;
			WPLE()->logger->info('NumberOfDays (fallback): '.$req->NumberOfDays);
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
		WPLE()->logger->info('fetching orders - page '.$this->current_page);
		$res = $this->_cs->GetOrders( $req );

		$this->total_pages = $res->PaginationResult->TotalNumberOfPages;
		$this->total_items = $res->PaginationResult->TotalNumberOfEntries;

		// get order with pagination helper (doesn't work as expected)
		// EbatNs_PaginationHelper($proxy, $callName, $request, $responseElementToMerge = '__COUNT_BY_HANDLER', $maxEntries = 200, $pageSize = 200, $initialPage = 1)
		// $helper = new EbatNs_PaginationHelper( $this->_cs, 'GetOrders', $req, 'OrderArray', 20, 10, 1);
		// $res = $helper->QueryAll();


		// handle response and check if successful
		if ( $this->handleResponse($res) ) {
			WPLE()->logger->info( "*** Orders updated successfully." );
			// WPLE()->logger->info( "*** PaginationResult:".print_r($res->PaginationResult,1) );
			// WPLE()->logger->info( "*** processed response:".print_r($res,1) );

			WPLE()->logger->info( "*** current_page: ".$this->current_page );
			WPLE()->logger->info( "*** total_pages: ".$this->total_pages );
			WPLE()->logger->info( "*** total_items: ".$this->total_items );

			// fetch next page recursively - only in days mode
			if ( $res->HasMoreOrders ) {
				$this->current_page++;
				$this->updateOrders( $session, $days, $this->current_page );
			}


		} else {
			WPLE()->logger->error( "Error on orders update".print_r( $res, 1 ) );			
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

	// 	WPLE()->logger->info('ItemID: '.$req->ItemID);
	// 	WPLE()->logger->info('OrderID: '.$req->OrderID);

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

	// 		WPLE()->logger->info( sprintf("Order %s updated successfully.", $req->OrderID ) );
	// 	} else {
	// 		WPLE()->logger->error( "Error on orders update".print_r( $res, 1 ) );			
	// 	}
	// }

	// function handlePaginationResultType( $type, $Detail ) {
	// 	//#type $Detail PaginationResultType
	// 	$this->total_pages = $Detail->TotalNumberOfPages;
	// 	$this->total_items = $Detail->TotalNumberOfEntries;
	// 	WPLE()->logger->info( 'handlePaginationResultType()'.print_r( $Detail, 1 ) );
	// }

	function handleOrderType( $type, $Detail ) {
		//global $wpdb;
		//#type $Detail OrderType
		// WPLE()->logger->info( 'handleOrderType()'.print_r( $Detail, 1 ) );

		// map OrderType to DB columns
		$data = $this->mapItemDetailToDB( $Detail );
		if (!$data) return true;
		// WPLE()->logger->info( 'handleOrderType() mapped data: '.print_r( $data, 1 ) );

		$this->insertOrUpdate( $data, $Detail );

		// this will remove item from result
		return true;
	}
	function insertOrUpdate( $data, $Detail ) {
		global $wpdb;

		// try to get existing order by order id
		$order = $this->getOrderByOrderID( $data['order_id'] );

		if ( $order ) {

			// update existing order
			WPLE()->logger->info( 'update order #'.$data['order_id'] );
			$result = $wpdb->update( $this->tablename, $data, array( 'order_id' => $data['order_id'] ) );
			if ( $result === false ) {
				WPLE()->logger->error( 'failed to update order - MySQL said: '.$wpdb->last_error );
				wple_show_message( 'Failed to update order #'.$data['order_id'].' - MySQL said: '.$wpdb->last_error, 'error' );
			}
			$insert_id = $order['id'];


			$this->addToReport( 'updated', $data );
		
		} else {
		
			// create new order
			WPLE()->logger->info( 'insert order #'.$data['order_id'] );
			$result = $wpdb->insert( $this->tablename, $data );
			if ( $result === false ) {
				WPLE()->logger->error( 'insert order failed - MySQL said: '.$wpdb->last_error );
				$this->addToReport( 'error', $data, false, $wpdb->last_error );
				wple_show_message( 'Failed to insert order #'.$data['order_id'].' - MySQL said: '.$wpdb->last_error, 'error' );
				return false;
			}
			$Details       = maybe_unserialize( $data['details'] );
			$order_post_id = false;
			$insert_id     = $wpdb->insert_id;
			// WPLE()->logger->info( 'insert_id: '.$insert_id );

			// process order line items
			$tm = new TransactionsModel();
			foreach ( $Details->TransactionArray as $Transaction ) {

				// avoid empty transaction id (auctions)
				$transaction_id = $Transaction->TransactionID;
				if ( intval( $transaction_id ) == 0 ) {
					// use negative OrderLineItemID to separate from real TransactionIDs
					$transaction_id = 0 - str_replace('-', '', $Transaction->OrderLineItemID);
				}

				// check if we already processed this TransactionID
				if ( $existing_transaction = $tm->getTransactionByTransactionID( $transaction_id ) ) {

					// add history record
					$history_message = "Skipped already processed transaction {$transaction_id}";
					$history_details = array( 'ebay_id' => $ebay_id );
					$this->addHistory( $data['order_id'], 'skipped_transaction', $history_message, $history_details );

					// TODO: optionally update transaction to reflect correct CompleteStatus etc. - like so:
					// $tm->updateTransactionFromEbayOrder( $data, $Transaction );

					// skip processing listing items
					continue;
				}

				// check if item has variation 
				$hasVariations = false;
				$VariationSpecifics = array();
		        if ( is_object( @$Transaction->Variation ) ) {
					foreach ($Transaction->Variation->VariationSpecifics as $spec) {
		                $VariationSpecifics[ $spec->Name ] = $spec->Value[0];
		            }
					$hasVariations = true;
		        } 

				// update listing sold quantity and status
				$this->processListingItem( $data['order_id'], $Transaction->Item->ItemID, $Transaction->QuantityPurchased, $data, $VariationSpecifics, $Transaction );

				// create transaction record for future reference
				$tm->createTransactionFromEbayOrder( $data, $Transaction );
			}



			$this->addToReport( 'inserted', $data, $order_post_id );

		}

	} // insertOrUpdate()




	// check if woocommcer order exists and has not been moved to the trash
	static function wooOrderExists( $post_id ) {

		$_order = new WC_Order();
		if ( $_order->get_order( $post_id ) ) {

			// WPLE()->logger->info( 'post_status for order ID '.$post_id.' is '.$_order->post_status );
			if ( $_order->post_status == 'trash' ) return false;

			return $_order->id;

		}

		return false;
	} // wooOrderExists()


	// update listing sold quantity and status
	function processListingItem( $order_id, $ebay_id, $quantity_purchased, $data, $VariationSpecifics, $Transaction ) {
		global $wpdb;
		$has_been_replenished = false;

		// check if this listing exists in WP-Lister
		$listing_id = $wpdb->get_var( $wpdb->prepare("SELECT id FROM {$wpdb->prefix}ebay_auctions WHERE ebay_id = %s", $ebay_id ) );
		if ( ! $listing_id ) {
			$history_message = "Skipped foreign item #{$ebay_id}";
			$history_details = array( 'ebay_id' => $ebay_id );
			$this->addHistory( $order_id, 'skipped_item', $history_message, $history_details );
			return;
		}

		// get current values from db
		// $quantity_purchased = $data['quantity'];
		$quantity_total = $wpdb->get_var( $wpdb->prepare("SELECT quantity      FROM {$wpdb->prefix}ebay_auctions WHERE ebay_id = %s", $ebay_id ) );
		$quantity_sold  = $wpdb->get_var( $wpdb->prepare("SELECT quantity_sold FROM {$wpdb->prefix}ebay_auctions WHERE ebay_id = %s", $ebay_id ) );

		// increase the listing's quantity_sold
		$quantity_sold = $quantity_sold + $quantity_purchased;
		$wpdb->update( $wpdb->prefix.'ebay_auctions', 
			array( 'quantity_sold' => $quantity_sold ), 
			array( 'ebay_id' => $ebay_id ) 
		);

		// add history record
		$history_message = "Sold quantity increased by $quantity_purchased for listing #{$ebay_id} - sold $quantity_sold";
		$history_details = array( 'ebay_id' => $ebay_id, 'quantity_sold' => $quantity_sold, 'quantity_total' => $quantity_total );
		$this->addHistory( $order_id, 'reduce_stock', $history_message, $history_details );



		// mark listing as sold when last item is sold - unless Out Of Stock Control (oosc) is enabled
        if ( ! ListingsModel::thisAccountUsesOutOfStockControl( $data['account_id'] ) ) {
			if ( $quantity_sold == $quantity_total && ! $has_been_replenished ) {

                // make sure this product is out of stock before we mark listing as sold - free version excluded
                $listing_item = ListingsModel::getItem( $listing_id );
                if ( WPLISTER_LIGHT || ListingsModel::checkStockLevel( $listing_item ) == false ) {

					$wpdb->update( $wpdb->prefix.'ebay_auctions', 
						array( 'status' => 'sold', 'date_finished' => $data['date_created'], ), 
						array( 'ebay_id' => $ebay_id ) 
					);
					WPLE()->logger->info( 'marked item #'.$ebay_id.' as SOLD ');

				}
			}
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
		$history = $wpdb->get_var( $wpdb->prepare("
			SELECT history
			FROM $this->tablename
			WHERE order_id = %s
		", $order_id ) );

		// init with empty array
		$history = maybe_unserialize( $history );
		if ( ! $history ) $history = array();

		// prevent fatal error if $history is not an array
		if ( ! is_array( $history ) ) {
			WPLE()->logger->error( "invalid history value in EbayOrdersModel::addHistory(): ".$history);

			// build history record
			$rec = new stdClass();
			$rec->action  = 'reset_history';
			$rec->msg     = 'Corrupted history data was cleared';
			$rec->details = array();
			$rec->success = false;
			$rec->time    = time();

			$history = array();
			$history[] = $record;
		}

		// add record
		$history[] = $record;

		// update history
		$history = serialize( $history );
		$wpdb->query( $wpdb->prepare("
			UPDATE $this->tablename
			SET history    = %s
			WHERE order_id = %s
		", $history, $order_id ) );

	}

	function mapItemDetailToDB( $Detail ) {
		//#type $Detail OrderType

		$data['date_created']              = self::convertEbayDateToSql( $Detail->CreatedTime );
		$data['LastTimeModified']          = self::convertEbayDateToSql( $Detail->CheckoutStatus->LastModifiedTime );

		$data['order_id']            	   = $Detail->OrderID;
		$data['total']                     = $Detail->Total->value;
		$data['currency']                  = $Detail->Total->attributeValues['currencyID'];
		$data['buyer_userid']              = $Detail->BuyerUserID;

		$data['CompleteStatus']            = $Detail->OrderStatus;
		$data['eBayPaymentStatus']         = $Detail->CheckoutStatus->eBayPaymentStatus;
		$data['PaymentMethod']             = $Detail->CheckoutStatus->PaymentMethod;
		$data['CheckoutStatus']            = $Detail->CheckoutStatus->Status;

		$data['ShippingService']           = $Detail->ShippingServiceSelected->ShippingService;
		$data['ShippingAddress_City']      = $Detail->ShippingAddress->CityName;
		$data['buyer_name']                = $Detail->Buyer->RegistrationAddress->Name;
		$data['buyer_email']               = $Detail->TransactionArray[0]->Buyer->Email;

		$data['site_id']    	 		   = $this->site_id;
		$data['account_id']    	 		   = $this->account_id;

		// use buyer name from shipping address if registration address is empty
		if ( $data['buyer_name'] == '' ) {
			$data['buyer_name'] = $Detail->ShippingAddress->Name;
		}

		// process transactions / items
		$items = array();
		foreach ( $Detail->TransactionArray as $Transaction ) {
			$VariationSpecifics = false;
			$sku = $Transaction->Item->SKU;

			// process variation details
			if ( is_object( @$Transaction->Variation ) ) {
				$VariationSpecifics = array();
				$sku = $Transaction->Variation->SKU;

				if ( is_array($Transaction->Variation->VariationSpecifics) )
				foreach ( $Transaction->Variation->VariationSpecifics as $varspec ) {
					$attribute_name  = $varspec->Name;
					$attribute_value = $varspec->Value[0];
					$VariationSpecifics[ $attribute_name ] = $attribute_value;
				}
			}

			$newitem = array();
			$newitem['item_id']            = $Transaction->Item->ItemID;
			$newitem['title']              = $Transaction->Item->Title;
			$newitem['sku']                = $sku;
			$newitem['quantity']           = $Transaction->QuantityPurchased;
			$newitem['transaction_id']     = $Transaction->TransactionID;
			$newitem['OrderLineItemID']    = $Transaction->OrderLineItemID;
			$newitem['TransactionPrice']   = $Transaction->TransactionPrice->value;
			$newitem['VariationSpecifics'] = $VariationSpecifics;
			$items[] = $newitem;
			// echo "<pre>";print_r($Transaction);echo"</pre>";die();
		}
		$data['items'] = serialize( $items );


		// maybe skip orders from foreign sites
		if ( get_option( 'wplister_skip_foreign_site_orders' ) ) {

			// get WP-Lister eBay site
			$ebay_sites	   = EbayController::getEbaySites();
			$wplister_site = $ebay_sites[ get_option( 'wplister_ebay_site_id' ) ];

			// check if sites match - skip if they don't
			if ( $Transaction->TransactionSiteID != $wplister_site ) {
				WPLE()->logger->info( "skipped order #".$Detail->OrderID." from foreign site #".$Detail->Item->Site." / ".$Transaction->TransactionSiteID );			
				$this->addToReport( 'skipped', $data );
				return false;						
			}

		}

		// skip orders that are older than the oldest order in WP-Lister / when WP-Lister was first connected to eBay
		if ( $first_order_date_created_ts = $this->getDateOfFirstOrder() ) {

			// convert to timestamps
			$this_order_date_created_ts = strtotime( $data['date_created'] );

			// skip if order date is older
			if ( $this_order_date_created_ts < $first_order_date_created_ts ) {
				WPLE()->logger->info( "skipped old order #".$Detail->OrderID." created at ".$data['date_created'] );			
				WPLE()->logger->info( "timestamps: $this_order_date_created_ts / ".date('Y-m-d H:i:s',$this_order_date_created_ts)." (order)  <  $first_order_date_created_ts ".date('Y-m-d H:i:s',$first_order_date_created_ts)." (ref)" );			
				$this->addToReport( 'skipped', $data );
				return false;						
			}

		}


        // save GetOrders reponse in details
		$data['details'] = self::encodeObject( $Detail );

		WPLE()->logger->info( "IMPORTING order #".$Detail->OrderID );							

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

		$html  = '<div class="ebay_order_report" style="display:none">';
		$html .= '<br>';
		$html .= __('New orders created','wplister') .': '. $this->count_inserted .' '. '<br>';
		$html .= __('Existing orders updated','wplister')  .': '. $this->count_updated  .' '. '<br>';
		if ( $this->count_skipped ) $html .= __('Old or foreign orders skipped','wplister')  .': '. $this->count_skipped  .' '. '<br>';
		if ( $this->count_failed ) $html .= __('Orders failed to create','wplister')  .': '. $this->count_failed  .' '. '<br>';
		$html .= '<br>';

		if ( $this->count_skipped ) $html .= __('Note: Orders from foreign eBay sites were skipping during update.','wplister') . '<br><br>';

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
		$items = $wpdb->get_results( "
			SELECT *
			FROM $this->tablename
			ORDER BY id DESC
		", ARRAY_A );

		return $items;
	}

	function getItem( $id ) {
		global $wpdb;

		$item = $wpdb->get_row( $wpdb->prepare("
			SELECT *
			FROM $this->tablename
			WHERE id = %s
		", $id 
		), ARRAY_A );

		// decode OrderType object with eBay classes loaded
		$item['details'] = self::decodeObject( $item['details'], false, true );
		$item['history'] = maybe_unserialize( $item['history'] );
		$item['items']   = maybe_unserialize( $item['items'] );

		return $item;
	}

	static function getWhere( $column, $value ) {
		global $wpdb;	
		$table = $wpdb->prefix . self::TABLENAME;

		$items = $wpdb->get_results( $wpdb->prepare("
			SELECT *
			FROM $table
			WHERE $column = %s
		", $value 
		), OBJECT_K);		

		return $items;
	}

	function getOrderByOrderID( $order_id ) {
		global $wpdb;

		$order = $wpdb->get_row( $wpdb->prepare("
			SELECT *
			FROM $this->tablename
			WHERE order_id = %s
		", $order_id 
		), ARRAY_A );

		return $order;
	}
	function getAllOrderByOrderID( $order_id ) {
		global $wpdb;

		$order = $wpdb->get_results( $wpdb->prepare("
			SELECT *
			FROM $this->tablename
			WHERE order_id = %s
		", $order_id 
		), ARRAY_A );

		return $order;
	}

	function getOrderByPostID( $post_id ) {
		global $wpdb;

		$order = $wpdb->get_row( $wpdb->prepare("
			SELECT *
			FROM $this->tablename
			WHERE post_id = %s
		", $post_id 
		), ARRAY_A );

		return $order;
	}

	function getAllDuplicateOrders() {
		global $wpdb;	
		$items = $wpdb->get_results("
			SELECT order_id, COUNT(*) c
			FROM $this->tablename
			GROUP BY order_id 
			HAVING c > 1
		", OBJECT_K);		

		if ( ! empty($items) ) {
			$order = array();
			foreach ($items as &$item) {
				$orders[] = $item->order_id;
			}
			$items = $orders;
		}

		return $items;		
	}

	// get the newest modification date of all orders in WP-Lister
	function getDateOfLastOrder( $account_id ) {
		global $wpdb;
		$lastdate = $wpdb->get_var( $wpdb->prepare("
			SELECT LastTimeModified
			FROM $this->tablename
			WHERE account_id = %s
			ORDER BY LastTimeModified DESC LIMIT 1
		", $account_id ) );

		// if there are no orders yet, check the date of the last transaction
		if ( ! $lastdate ) {
			$tm = new TransactionsModel();
			$lastdate = $tm->getDateOfLastCreatedTransaction( $account_id );
			if ($lastdate) {
				// add two minutes to prevent importing the same transaction again
				$lastdate = mysql2date('U', $lastdate) + 120;
				$lastdate = date('Y-m-d H:i:s', $lastdate );
			}
		}
		return $lastdate;
	}

	// get the creation date of the oldest order in WP-Lister - as unix timestamp
	function getDateOfFirstOrder() {
		global $wpdb;

		// regard ignore_orders_before_ts timestamp if set
		if ( $ts = get_option('ignore_orders_before_ts') ) {
			WPLE()->logger->info( "getDateOfFirstOrder() - using ignore_orders_before_ts: $ts (raw)");
			return $ts;
		}

		$date = $wpdb->get_var( "
			SELECT date_created
			FROM $this->tablename
			ORDER BY date_created ASC LIMIT 1
		" );

		return strtotime($date);
	}

	function deleteItem( $id ) {
		global $wpdb;
		$wpdb->query( $wpdb->prepare("
			DELETE
			FROM $this->tablename
			WHERE id = %s
		", $id ) );
	}

	function updateWpOrderID( $id, $wp_order_id ) {
		global $wpdb;
		$wpdb->query( $wpdb->prepare("
			UPDATE $this->tablename
			SET post_id = %s
			WHERE id    = %s
		", $wp_order_id, $id ) );
		echo $wpdb->last_error;
	}

	function getStatusSummary() {
		global $wpdb;
		$result = $wpdb->get_results("
			SELECT CompleteStatus, count(*) as total
			FROM $this->tablename
			GROUP BY CompleteStatus
		");

		$summary = new stdClass();
		foreach ($result as $row) {
			$CompleteStatus = $row->CompleteStatus;
			$summary->$CompleteStatus = $row->total;
		}

		// count total items as well
		$total_items = $wpdb->get_var("
			SELECT COUNT( id ) AS total_items
			FROM $this->tablename
		");
		$summary->total_items = $total_items;

		return $summary;
	}


	function getPageItems( $current_page, $per_page ) {
		global $wpdb;

        $orderby  = (!empty($_REQUEST['orderby'])) ? esc_sql( $_REQUEST['orderby'] ) : 'date_created';
        $order    = (!empty($_REQUEST['order']))   ? esc_sql( $_REQUEST['order']   ) : 'desc';
        $offset   = ( $current_page - 1 ) * $per_page;
        $per_page = esc_sql( $per_page );

        $join_sql  = '';
        $where_sql = 'WHERE 1 = 1 ';

        // filter order_status
		$order_status = ( isset($_REQUEST['order_status']) ? esc_sql( $_REQUEST['order_status'] ) : 'all');
		if ( $order_status != 'all' ) {
			$where_sql .= "AND o.CompleteStatus = '".$order_status."' ";
		} 

        // filter account_id
		$account_id = ( isset($_REQUEST['account_id']) ? esc_sql( $_REQUEST['account_id'] ) : false);
		if ( $account_id ) {
			$where_sql .= "
				 AND o.account_id = '".$account_id."'
			";
		} 

        // filter search_query
		$search_query = ( isset($_REQUEST['s']) ? esc_sql( $_REQUEST['s'] ) : false);
		if ( $search_query ) {
			$where_sql .= "
				AND  ( o.buyer_name   LIKE '%".$search_query."%'
					OR o.items        LIKE '%".$search_query."%'
					OR o.buyer_userid     = '".$search_query."'
					OR o.buyer_email      = '".$search_query."'
					OR o.order_id         = '".$search_query."'
					OR o.post_id          = '".$search_query."'
					OR o.ShippingAddress_City LIKE '%".$search_query."%' )
			";
		} 


        // get items
		$items = $wpdb->get_results("
			SELECT *
			FROM $this->tablename o
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
				FROM $this->tablename o
	            $join_sql 
    	        $where_sql
				ORDER BY $orderby $order
			");			
		}

		// foreach( $items as &$profile ) {
		// 	$profile['details'] = self::decodeObject( $profile['details'] );
		// }

		return $items;
	}




} // class EbayOrdersModel
