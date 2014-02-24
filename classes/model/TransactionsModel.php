<?php
/**
 * TransactionsModel class
 *
 * responsible for managing transactions and talking to ebay
 * 
 */

// list of used EbatNs classes:

// require_once 'EbatNs_ServiceProxy.php';
// require_once 'EbatNs_DatabaseProvider.php';
// require_once 'EbatNs_Logger.php';

// require_once 'GetSellerTransactionsRequestType.php';
// require_once 'GetSellerTransactionsResponseType.php';

class TransactionsModel extends WPL_Model {
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

	function TransactionsModel() {
		global $wpl_logger;
		$this->logger = &$wpl_logger;

		global $wpdb;
		$this->tablename = $wpdb->prefix . 'ebay_transactions';
	}


	function updateTransactions( $session, $days = null, $current_page = 1 ) {
		$this->logger->info('*** updateTransactions('.$days.') - page '.$current_page);

		$this->initServiceProxy($session);

		// set request handler
		$this->_cs->setHandler( 'TransactionType', array( & $this, 'handleTransactionType' ) );
		// $this->_cs->setHandler( 'PaginationResultType', array( & $this, 'handlePaginationResultType' ) );

		// build request
		$req = new GetSellerTransactionsRequestType();
		$req->setIncludeContainingOrder(true);

		// check if we need to calculate lastdate
		if ( $this->current_lastdate ) {
			$lastdate = $this->current_lastdate;
			$this->logger->info('used current_lastdate from last run: '.$lastdate);
		} else {

			// period 30 days, which is the maximum allowed
			$now = time();
			$lastdate = $this->getDateOfLastTransaction();
			$this->logger->info('getDateOfLastTransaction() returned: '.$lastdate);
			if ($lastdate) $lastdate = mysql2date('U', $lastdate);

			// if last date is older than 30 days, fall back to default
			if ( $lastdate < $now - 3600 * 24 * 30 ) {
				$this->logger->info('resetting lastdate - fall back default ');
				$lastdate = false;
			} 

		}

		// save lastdate for next page
		$this->current_lastdate = $lastdate;

		// parameter $days has priority
		if ( $days ) {
			$req->NumberOfDays  = $days;
			$this->NumberOfDays = $days;
			$this->logger->info('NumberOfDays: '.$req->NumberOfDays);

		// default: transactions since last change
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


		// get transactions (single page)
		$this->logger->info('fetching transactions - page '.$this->current_page);
		$res = $this->_cs->GetSellerTransactions( $req );

		$this->total_pages = $res->PaginationResult->TotalNumberOfPages;
		$this->total_items = $res->PaginationResult->TotalNumberOfEntries;

		// get transaction with pagination helper (doesn't work as expected)
		// EbatNs_PaginationHelper($proxy, $callName, $request, $responseElementToMerge = '__COUNT_BY_HANDLER', $maxEntries = 200, $pageSize = 200, $initialPage = 1)
		// $helper = new EbatNs_PaginationHelper( $this->_cs, 'GetSellerTransactions', $req, 'TransactionArray', 20, 10, 1);
		// $res = $helper->QueryAll();


		// handle response and check if successful
		if ( $this->handleResponse($res) ) {
			$this->logger->info( "*** Transactions updated successfully." );
			// $this->logger->info( "*** PaginationResult:".print_r($res->PaginationResult,1) );
			// $this->logger->info( "*** processed response:".print_r($res,1) );

			$this->logger->info( "*** current_page: ".$this->current_page );
			$this->logger->info( "*** total_pages: ".$this->total_pages );
			$this->logger->info( "*** total_items: ".$this->total_items );

			// fetch next page recursively - only in days mode
			if ( $res->HasMoreTransactions ) {
				$this->current_page++;
				$this->updateTransactions( $session, $days, $this->current_page );
			}


		} else {
			$this->logger->error( "Error on transactions update".print_r( $res, 1 ) );			
		}
	}

	function updateSingleTransaction( $session, $id ) {

		$this->initServiceProxy($session);

		// get transaction item to update
		$transaction = $this->getItem( $id );

		// build request
		$req = new GetItemTransactionsRequestType();
		$req->ItemID 		= $transaction['item_id'];
		$req->TransactionID = $transaction['transaction_id'];

		$this->logger->info('ItemID: '.$req->ItemID);
		$this->logger->info('TransactionID: '.$req->TransactionID);

		// $req->DetailLevel = $Facet_DetailLevelCodeType->ReturnAll;
		$req->setDetailLevel('ReturnAll');
		$req->setIncludeContainingOrder(true);

		// download the data
		$res = $this->_cs->GetItemTransactions( $req );

		// handle response and check if successful
		if ( $this->handleResponse($res) ) {

			// since GetItemTransactions returns the Item object outside of the Transaction object, 
			// we need to rearrange it before we pass it to handleTransactionType()
			$Transaction = $res->TransactionArray[0];
			$Transaction->Item = $res->Item;
			$this->handleTransactionType( 'TransactionType', $Transaction );

			$this->logger->info( sprintf("Transaction %s updated successfully.", $req->TransactionID ) );
		} else {
			$this->logger->error( "Error on transactions update".print_r( $res, 1 ) );			
		}
	}

	function handlePaginationResultType( $type, & $Detail ) {
		//#type $Detail PaginationResultType
		$this->total_pages = $Detail->TotalNumberOfPages;
		$this->total_items = $Detail->TotalNumberOfEntries;
		$this->logger->info( 'handlePaginationResultType()'.print_r( $Detail, 1 ) );
	}

	function handleTransactionType( $type, & $Detail ) {
		//global $wpdb;
		//#type $Detail TransactionType
		$this->logger->debug( 'handleTransactionType()'.print_r( $Detail, 1 ) );

		// map TransactionType to DB columns
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
			$data['item_title'] = $Detail->Variation->VariationTitle;

		}


		$this->insertOrUpdate( $data, $hasVariations, $VariationSpecifics );

		// this will remove item from result
		return true;
	}
	function insertOrUpdate( $data, $hasVariations, $VariationSpecifics ) {
		global $wpdb;

		// try to get existing transaction by transaction id
		$transaction = $this->getTransactionByTransactionID( $data['transaction_id'] );

		if ( $transaction ) {

			// update existing transaction
			$this->logger->info( 'update transaction #'.$data['transaction_id'].' for item #'.$data['item_id'] );
			$wpdb->update( $this->tablename, $data, array( 'transaction_id' => $data['transaction_id'] ) );


			$this->addToReport( 'updated', $data );
		
		} else {
		
			// create new transaction
			$this->logger->info( 'insert transaction #'.$data['transaction_id'].' for item #'.$data['item_id'] );
			$result = $wpdb->insert( $this->tablename, $data );
			if ( ! $result ) {
				$this->logger->error( 'insert transaction failed - MySQL said: '.$wpdb->last_error );
				$this->addToReport( 'error', $data, false, false, $wpdb->last_error );
				return false;
			}
			$id = $wpdb->insert_id;
			// $this->logger->info( 'insert_id: '.$id );


			// update listing sold quantity and status

			// get current values from db
			$quantity_purchased = $data['quantity'];
			$quantity_total = $wpdb->get_var( 'SELECT quantity FROM '.$wpdb->prefix.'ebay_auctions WHERE ebay_id = '.$data['item_id'] );
			$quantity_sold = $wpdb->get_var( 'SELECT quantity_sold FROM '.$wpdb->prefix.'ebay_auctions WHERE ebay_id = '.$data['item_id'] );

			// increase the listing's quantity_sold
			$quantity_sold = $quantity_sold + $quantity_purchased;
			$wpdb->update( $wpdb->prefix.'ebay_auctions', 
				array( 'quantity_sold' => $quantity_sold ), 
				array( 'ebay_id' => $data['item_id'] ) 
			);

			// add history record
			$history_message = "Sold quantity increased by $quantity_purchased for listing #{$data['item_id']} - sold $quantity_sold";
			$history_details = array( 'newstock' => $newstock );
			$this->addHistory( $data['transaction_id'], 'reduce_stock', $history_message, $history_details );


			// mark listing as sold when last item is sold
			if ( $quantity_sold == $quantity_total ) {
				$wpdb->update( $wpdb->prefix.'ebay_auctions', 
					array( 'status' => 'sold', 'date_finished' => $data['date_created'], ), 
					array( 'ebay_id' => $data['item_id'] ) 
				);
				$this->logger->info( 'marked item #'.$data['item_id'].' as SOLD ');
			}



			$newstock = false;
			$wp_order_id = false;


			$this->addToReport( 'inserted', $data, $newstock, $wp_order_id );

		}

	} // insertOrUpdate()

    // revert a duplicate transaction and restore stock if required
	public function revertTransaction( $id ) {
		global $wpdb;

		// get transaction record
		$transaction = $this->getItem( $id );
		if ( ! $transaction ) return false;


		// restore listing's quantity_sold
		// get current values from db
		$quantity_purchased = $transaction['quantity'];
		$quantity_sold = $wpdb->get_var( 'SELECT quantity_sold FROM '.$wpdb->prefix.'ebay_auctions WHERE ebay_id = '.$transaction['item_id'] );

		// decrease the listing's quantity_sold
		$quantity_sold = $quantity_sold - $quantity_purchased;
		$wpdb->update( $wpdb->prefix.'ebay_auctions', 
			array( 'quantity_sold' => $quantity_sold ), 
			array( 'ebay_id' => $transaction['item_id'] ) 
		);


		// check if we need to restore product stock
		list( $reduced_product_id, $new_stock_value ) = $this->checkIfStockWasReducedForItemID( $transaction );
		if ( $reduced_product_id ) {
			// echo "<pre>stock was reduced to ";print_r($new_stock_value);echo"</pre>";#die();

			// restore product stock 
			$newstock = ProductWrapper::increaseStockBy( $reduced_product_id, $transaction['quantity'] );
			$this->addHistory( $transaction['transaction_id'], 'restored_stock', 'Product stock was restored', array( 'product_id' => $reduced_product_id, 'newstock' => $newstock ) );
		}

		// update status
		$this->updateById( $id, array( 'status' => 'reverted' ) );
		$this->addHistory( $transaction['transaction_id'], 'revert_transaction', 'Transaction was reverted' );

		return true;
	} // revertTransaction()

    // revert a duplicate transaction and restore stock if required
	public function checkIfStockWasReducedForItemID( $txn, $item_id = false ) {
		$product_id      = false;
		$variation_id    = false;
		$new_stock_value = false;
		$ebay_id_matches = false;
		if ( ! $item_id ) $item_id = $txn['item_id'];

		// check if stock was reduced
		$history = maybe_unserialize( $txn['history'] );
		// echo "<pre>";print_r($history);echo"</pre>";die();

		if ( is_array( $history ) )
		foreach ($history as $record) {

			// only process reduce_stock actions
			if ( 'reduce_stock' == $record->action ) {

				// check for matching eBay ID - transaction history might contain multiple transactions for combined orders
				if ( isset( $record->details['ebay_id'] ) )
					$ebay_id_matches = $item_id == $record->details['ebay_id'] ? true : false;

				// only process history records if ebay ID matches transaction's item ID
				if ( $ebay_id_matches ) {

					// get product ID if it exists
					if ( isset( $record->details['product_id'] ) )
						$product_id = $record->details['product_id'];

					// get variation ID if it exists
					if ( isset( $record->details['variation_id'] ) )
						$variation_id = $record->details['variation_id'];

					// get new stock if it exists
					if ( isset( $record->details['newstock'] ) )
						$new_stock_value = $record->details['newstock'];

				}

			} // if reduce stock

		} // each $record

		// return variation id if found
		$product_id = $variation_id ? $variation_id : $product_id;

		return array( $product_id, $new_stock_value );
	} // checkIfStockWasReducedForItemID()

	function createTransactionFromEbayOrder( $order, $Detail ) {
		global $wpdb;
		$this->logger->debug( 'createTransactionFromEbayOrder()'.print_r( $Detail, 1 ) );

		// map TransactionType to DB columns
		$data = $this->mapItemDetailToDB( $Detail, true );
		if (!$data) return true;

		// todo: check for variations?
		// $this->insertOrUpdate( $data );

		// add some data from order array which is missing in Transactions object
		$data['order_id']             = $order['order_id'];				// add order_id for transactions that were created from eBay orders
		$data['wp_order_id']          = $order['post_id'];
		$data['eBayPaymentStatus']    = $order['eBayPaymentStatus'];
		$data['CheckoutStatus']       = $order['CheckoutStatus'];
		$data['ShippingService']      = $order['ShippingService'];
		$data['ShippingAddress_City'] = $order['ShippingAddress_City'];
		$data['PaymentMethod']        = $order['PaymentMethod'];
		$data['CompleteStatus']       = $order['CompleteStatus'];
		$data['LastTimeModified']     = $order['LastTimeModified'];
		$data['buyer_userid']         = $order['buyer_userid'];
		$data['buyer_name']           = $order['buyer_name'];
		$data['details']              = maybe_serialize( $Detail );
		$data['history']              = $order['history'];

		// create new transaction
		$this->logger->info( 'insert transaction #'.$data['transaction_id'].' for item #'.$data['item_id'].' from order #'.$data['order_id'] );
		$result = $wpdb->insert( $this->tablename, $data );
		if ( ! $result ) {
			$this->logger->error( 'insert transaction failed - MySQL said: '.$wpdb->last_error );
			$this->addToReport( 'error', $data, false, false, $wpdb->last_error );
			return false;
		}
		$id = $wpdb->insert_id;
		// $this->logger->info( 'insert_id: '.$id );

		return $id;
	} // createTransactionFromEbayOrder


	// add transaction history entry
	function addHistory( $transaction_id, $action, $msg, $details = array(), $success = true ) {
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
			WHERE transaction_id = '$transaction_id'
		" );

		// init with empty array
		$history = maybe_unserialize( $history );
		if ( ! $history ) $history = array();

		// prevent fatal error if $history is not an array
		if ( ! is_array( $history ) ) {
			$this->logger->error( "invalid history value in TransactionsModel::addHistory(): ".$history);

			// build history record
			$rec = new stdClass();
			$rec->action  = 'reset_history';
			$rec->msg     = 'Corrupted history data was cleared';
			$rec->details = array();
			$rec->success = 'ERROR';
			$rec->time    = time();

			$history = array();
			$history[] = $record;
		}

		// add record
		$history[] = $record;

		// update history
		$history = serialize( $history );
		$wpdb->query( "
			UPDATE $this->tablename
			SET history = '$history'
			WHERE transaction_id = '$transaction_id'
		" );

	}

	function mapItemDetailToDB( $Detail, $always_process_foreign_transactions = false ) {
		//#type $Detail TransactionType
		// echo "<pre>";print_r($Detail);echo"</pre>";#die();

		$data['item_id']                   = $Detail->Item->ItemID;
		$data['transaction_id']            = $Detail->TransactionID;
		$data['date_created']              = $this->convertEbayDateToSql( $Detail->CreatedDate );
		$data['price']                     = $Detail->TransactionPrice->value;
		$data['quantity']                  = $Detail->QuantityPurchased;
		$data['buyer_userid']              = @$Detail->Buyer->UserID;
		$data['buyer_name']                = @$Detail->Buyer->RegistrationAddress->Name;
		$data['buyer_email']               = @$Detail->Buyer->Email;
		
		$data['eBayPaymentStatus']         = $Detail->Status->eBayPaymentStatus;
		$data['CheckoutStatus']            = $Detail->Status->CheckoutStatus;
		$data['ShippingService']           = @$Detail->ShippingServiceSelected->ShippingService;
		//$data['ShippingAddress_Country'] = $Detail->Buyer->BuyerInfo->ShippingAddress->Country;
		//$data['ShippingAddress_Zip']     = $Detail->Buyer->BuyerInfo->ShippingAddress->PostalCode;
		$data['ShippingAddress_City']      = @$Detail->Buyer->BuyerInfo->ShippingAddress->CityName;
		$data['PaymentMethod']             = $Detail->Status->PaymentMethodUsed;
		$data['CompleteStatus']            = $Detail->Status->CompleteStatus;
		$data['LastTimeModified']          = $this->convertEbayDateToSql( $Detail->Status->LastTimeModified );
		$data['OrderLineItemID']           = $Detail->OrderLineItemID;

		$listingsModel = new ListingsModel();
		$listingItem = $listingsModel->getItemByEbayID( $Detail->Item->ItemID );

		// skip items not found in listings
		if ( $listingItem ) {

			$data['post_id']    = $listingItem->post_id;
			$data['item_title'] = $listingItem->auction_title;
			$this->logger->info( "process transaction #".$Detail->TransactionID." for item '".$data['item_title']."' - #".$Detail->Item->ItemID );
			$this->logger->info( "post_id: ".$data['post_id']);

		} else {

			$data['post_id']    = false;
			$data['item_title'] = $Detail->Item->Title;

			// only skip if foreign_transactions option is disabled
			if ( ( get_option( 'wplister_foreign_transactions' ) != 1 ) && ! $always_process_foreign_transactions ) {
				$this->logger->info( "skipped transaction #".$Detail->TransactionID." for foreign item #".$Detail->Item->ItemID );			
				$this->addToReport( 'skipped', $data );
				return false;			
			} else {
				$this->logger->info( "IMPORTED transaction #".$Detail->TransactionID." for foreign item #".$Detail->Item->ItemID );							
			}

		}

		// avoid empty transaction id
		if ( intval($data['transaction_id']) == 0 ) {
			// use negative OrderLineItemID to separate from real TransactionIDs
			$data['transaction_id'] = 0 - str_replace('-', '', $data['OrderLineItemID']);
		}

		// use buyer name from shipping address if registration address is empty
		if ( $data['buyer_name'] == '' ) {
			$data['buyer_name'] = @$Detail->Buyer->BuyerInfo->ShippingAddress->Name;
		}


        // save GetSellerTransactions reponse in details
		$data['details'] = $this->encodeObject( $Detail );

		return $data;
	} // mapItemDetailToDB


	function addToReport( $status, $data, $newstock = false, $wp_order_id = false, $error = false ) {

		$rep = new stdClass();
		$rep->status           = $status;
		$rep->item_id          = $data['item_id'];
		$rep->transaction_id   = $data['transaction_id'];
		$rep->date_created     = $data['date_created'];
		$rep->OrderLineItemID  = $data['OrderLineItemID'];
		$rep->LastTimeModified = $data['LastTimeModified'];
		$rep->data             = $data;
		$rep->newstock         = $newstock;
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

		$html  = '<div id="transaction_report" style="display:none">';
		$html .= '<br>';
		$html .= __('New transactions created','wplister') .': '. $this->count_inserted .' '. '<br>';
		$html .= __('Existing transactions updated','wplister')  .': '. $this->count_updated  .' '. '<br>';
		if ( $this->count_skipped ) $html .= __('Foreign transactions skipped','wplister')  .': '. $this->count_skipped  .' '. '<br>';
		if ( $this->count_failed ) $html .= __('Transactions failed to create','wplister')  .': '. $this->count_failed  .' '. '<br>';
		$html .= '<br>';

		if ( $this->count_skipped ) $html .= __('Note: Foreign transactions for which no matching item ID could be found in WP-Lister\'s listings table were skipping during update.','wplister') . '<br><br>';

		$html .= '<table style="width:99%">';
		$html .= '<tr>';
		$html .= '<th align="left">'.__('Last modified','wplister').'</th>';
		$html .= '<th align="left">'.__('Transaction ID','wplister').'</th>';
		$html .= '<th align="left">'.__('Action','wplister').'</th>';
		$html .= '<th align="left">'.__('Item ID','wplister').'</th>';
		$html .= '<th align="left">'.__('Title','wplister').'</th>';
		$html .= '<th align="left">'.__('Buyer ID','wplister').'</th>';
		$html .= '<th align="left">'.__('Date created','wplister').'</th>';
		$html .= '</tr>';
		
		foreach ($this->report as $item) {
			$html .= '<tr>';
			$html .= '<td>'.$item->LastTimeModified.'</td>';
			$html .= '<td>'.$item->transaction_id.'</td>';
			$html .= '<td>'.$item->status.'</td>';
			$html .= '<td>'.$item->item_id.'</td>';
			$html .= '<td>'.@$item->data['item_title'].'</td>';
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

		// decode TransactionType object with eBay classes loaded
		$item['details'] = $this->decodeObject( $item['details'], false, true );
		$item['history'] = maybe_unserialize( $item['history'] );

		return $item;
	}

	function getTransactionByTransactionID( $transaction_id ) {
		global $wpdb;

		$transaction = $wpdb->get_row( "
			SELECT *
			FROM $this->tablename
			WHERE transaction_id = '$transaction_id'
		", ARRAY_A );

		return $transaction;
	}
	function getAllTransactionsByTransactionID( $transaction_id ) {
		global $wpdb;

		$transaction = $wpdb->get_results( "
			SELECT *
			FROM $this->tablename
			WHERE transaction_id = '$transaction_id'
			ORDER BY LastTimeModified DESC
		", ARRAY_A );

		return $transaction;
	}
	function getTransactionByOrderID( $wp_order_id ) {
		global $wpdb;

		$transaction = $wpdb->get_row( "
			SELECT *
			FROM $this->tablename
			WHERE wp_order_id = '$wp_order_id'
		", ARRAY_A );

		return $transaction;
	}

	function getTransactionByEbayOrderID( $order_id ) {
		global $wpdb;

		$transaction = $wpdb->get_row( "
			SELECT *
			FROM $this->tablename
			WHERE order_id = '$order_id'
		", ARRAY_A );

		return $transaction;
	}


	function getAllDuplicateTransactions() {
		global $wpdb;	
		$items = $wpdb->get_results("
			SELECT transaction_id, COUNT(*) c
			FROM $this->tablename
			WHERE status IS NULL OR NOT status = 'reverted'
			GROUP BY transaction_id 
			HAVING c > 1
		", OBJECT_K);		

		if ( ! empty($items) ) {
			$transactions = array();
			foreach ($items as &$item) {
				$transactions[] = $item->transaction_id;
			}
			$items = $transactions;
		}

		return $items;		
	}


	function getDateOfLastTransaction() {
		global $wpdb;
		return $wpdb->get_var( "
			SELECT LastTimeModified
			FROM $this->tablename
			ORDER BY LastTimeModified DESC LIMIT 1
		" );
	}
	function getDateOfLastCreatedTransaction() {
		global $wpdb;
		return $wpdb->get_var( "
			SELECT date_created
			FROM $this->tablename
			ORDER BY date_created DESC LIMIT 1
		" );
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
			SET wp_order_id = '$wp_order_id'
			WHERE id = '$id'
		" );
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

        $orderby = (!empty($_REQUEST['orderby'])) ? $_REQUEST['orderby'] : 'date_created'; //If no sort, default to title
        $order = (!empty($_REQUEST['order'])) ? $_REQUEST['order'] : 'desc'; //If no order, default to asc
        $offset = ( $current_page - 1 ) * $per_page;

        $join_sql  = '';
        $where_sql = '';

        // filter transaction_status
		$transaction_status = ( isset($_REQUEST['transaction_status']) ? $_REQUEST['transaction_status'] : 'all');
		if ( $transaction_status != 'all' ) {
			$where_sql = "WHERE CompleteStatus = '".$transaction_status."' ";
		} 

        // filter search_query
		$search_query = ( isset($_REQUEST['s']) ? $_REQUEST['s'] : false);
		if ( $search_query ) {
			$where_sql = "
				WHERE  t.buyer_name   LIKE '%".$search_query."%'
					OR t.item_title   LIKE '%".$search_query."%'
					OR t.transaction_id   = '".$search_query."'
					OR t.order_id         = '".$search_query."'
					OR t.item_id          = '".$search_query."'
					OR t.buyer_userid     = '".$search_query."'
					OR t.buyer_email      = '".$search_query."'
			";
		} 


        // get items
		$items = $wpdb->get_results("
			SELECT *
			FROM $this->tablename t
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
				FROM $this->tablename t
	            $join_sql 
    	        $where_sql
				ORDER BY $orderby $order
			");			
		}


		// foreach( $items as &$profile ) {
		// 	$profile['details'] = $this->decodeObject( $profile['details'] );
		// }

		return $items;
	}



	public function updateById( $id, $data ) {
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


}
