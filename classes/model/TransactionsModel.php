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

	function mapItemDetailToDB( $Detail ) {
		//#type $Detail TransactionType

		$data['item_id']                   = $Detail->Item->ItemID;
		$data['transaction_id']            = $Detail->TransactionID;
		$data['date_created']              = $this->convertEbayDateToSql( $Detail->CreatedDate );
		$data['price']                     = $Detail->TransactionPrice->value;
		$data['quantity']                  = $Detail->QuantityPurchased;
		$data['buyer_userid']              = $Detail->Buyer->UserID;
		$data['buyer_name']                = $Detail->Buyer->RegistrationAddress->Name;
		$data['buyer_email']               = $Detail->Buyer->Email;
		
		$data['eBayPaymentStatus']         = $Detail->Status->eBayPaymentStatus;
		$data['CheckoutStatus']            = $Detail->Status->CheckoutStatus;
		$data['ShippingService']           = $Detail->ShippingServiceSelected->ShippingService;
		//$data['ShippingAddress_Country'] = $Detail->Buyer->BuyerInfo->ShippingAddress->Country;
		//$data['ShippingAddress_Zip']     = $Detail->Buyer->BuyerInfo->ShippingAddress->PostalCode;
		$data['ShippingAddress_City']      = $Detail->Buyer->BuyerInfo->ShippingAddress->CityName;
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
			if ( get_option( 'wplister_foreign_transactions' ) != 1 ) {
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
			$data['buyer_name'] = $Detail->Buyer->BuyerInfo->ShippingAddress->Name;
		}


        // save GetSellerTransactions reponse in details
		$data['details'] = $this->encodeObject( $Detail );

		return $data;
	}


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
	function getTransactionByOrderID( $wp_order_id ) {
		global $wpdb;

		$transaction = $wpdb->get_row( "
			SELECT *
			FROM $this->tablename
			WHERE wp_order_id = '$wp_order_id'
		", ARRAY_A );

		return $transaction;
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


	function getPageItems( $current_page, $per_page ) {
		global $wpdb;

        $orderby = (!empty($_REQUEST['orderby'])) ? $_REQUEST['orderby'] : 'date_created'; //If no sort, default to title
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
