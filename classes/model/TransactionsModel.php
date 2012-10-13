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

	function TransactionsModel() {
		global $wpl_logger;
		$this->logger = &$wpl_logger;

		global $wpdb;
		$this->tablename = $wpdb->prefix . 'ebay_transactions';
	}


	function updateTransactions( $session, $days = null ) {
		$this->logger->info('updateTransactions('.$days.')');

		$this->initServiceProxy($session);

		// set request handler
		$this->_cs->setHandler( 'TransactionType', array( & $this, 'handleTransactionType' ) );

		// build request
		$req = new GetSellerTransactionsRequestType();

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

		// parameter $days has priority
		if ( $days ) {
			$req->NumberOfDays = $days;

		// default: transactions since last change
		} elseif ( $lastdate ) {
			$req->ModTimeFrom = gmdate( 'Y-m-d H:i:s', $lastdate );
			$req->ModTimeTo   = gmdate( 'Y-m-d H:i:s', time() );

		// fallback: last 7 days (max allowed by ebay: 30 days)
		} else {
			$days = 7;
			$req->NumberOfDays = $days;
		}

		$this->logger->info('lastdate: '.$lastdate);
		$this->logger->info('ModTimeFrom: '.$req->ModTimeFrom);
		$this->logger->info('ModTimeTo: '.$req->ModTimeTo);

		$req->DetailLevel = $Facet_DetailLevelCodeType->ReturnAll;

		// download the data
		$res = $this->_cs->GetSellerTransactions( $req );

		// handle response and check if successful
		if ( $this->handleResponse($res) ) {
			$this->logger->info( "Transactions updated successfully." );
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

		$req->DetailLevel = $Facet_DetailLevelCodeType->ReturnAll;

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
		} else {
			// create new transaction
			$this->logger->info( 'insert transaction #'.$data['transaction_id'].' for item #'.$data['item_id'] );
			$wpdb->insert( $this->tablename, $data );
			$id = $wpdb->insert_id;

			// mark listing item as sold when last piece is sold
			$total_quantity = $wpdb->get_var( 'SELECT quantity FROM '.$wpdb->prefix.'ebay_auctions WHERE ebay_id = '.$data['item_id'] );
			$qsold = $wpdb->get_var( 'SELECT quantity_sold FROM '.$wpdb->prefix.'ebay_auctions WHERE ebay_id = '.$data['item_id'] );
			if ( $qsold + $data['quantity'] == $total_quantity ) {
				$adata['status'] = 'sold';
				$adata['date_finished'] = $data['date_created'];
				$this->logger->info( 'marked item #'.$data['item_id'].' as SOLD ');
			}

			// update listing's quantity_sold
			$adata['quantity_sold'] = $qsold + $data['quantity'];
			$wpdb->update( $wpdb->prefix.'ebay_auctions', $adata, array( 'ebay_id' => $data['item_id'] ) );


		}

	}

	function mapItemDetailToDB( $Detail ) {
		//#type $Detail TransactionType

		$data['item_id']                   = $Detail->Item->ItemID;
		$data['transaction_id']            = $Detail->TransactionID;
		$data['date_created']              = $Detail->CreatedDate;
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
		$data['LastTimeModified']          = $Detail->Status->LastTimeModified;

		$listingsModel = new ListingsModel();
		$listingItem = $listingsModel->getItemByEbayID( $Detail->Item->ItemID );

		// skip items not found in listings
		if ( $listingItem ) {
			$data['item_title'] = $listingItem->auction_title;
			$this->logger->info( "Transaction for '".$data['item_title']."' - item #".$Detail->Item->ItemID );
		} else {
			$this->logger->info( "skipped transaction ".$Detail->TransactionID." for foreign item #".$Detail->Item->ItemID );			
			return false;
		}


		// use buyer name from shipping address if registration address is empty
		if ( $data['buyer_name'] == '' ) {
			$data['buyer_name']            = $Detail->Buyer->BuyerInfo->ShippingAddress->Name;
		}


        // save GetSellerTransactions reponse in details
		$data['details'] = $this->encodeObject( $Detail );

		return $data;
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
