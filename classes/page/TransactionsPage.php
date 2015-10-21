<?php
/**
 * TransactionsPage class
 * 
 */

class TransactionsPage extends WPL_Page {

	const slug = 'transactions';

	public function onWpInit() {
		// parent::onWpInit();

		// Add custom screen options
		$load_action = "load-".$this->main_admin_menu_slug."_page_wplister-".self::slug;
		add_action( $load_action, array( &$this, 'addScreenOptions' ) );

		// handle actions
		$this->handleActionsOnInit();
	}

	public function onWpAdminMenu() {
		parent::onWpAdminMenu();

		$page = isset( $_GET['page'] ) ? $_GET['page'] : '';
		if ( ( $page != 'wplister-transactions') && ( 'transaction' != get_option( 'wplister_ebay_update_mode', 'order' ) ) ) return;

		add_submenu_page( self::ParentMenuId, $this->getSubmenuPageTitle( 'Transactions' ), __('Transactions','wplister'), 
						  self::ParentPermissions, $this->getSubmenuId( 'transactions' ), array( &$this, 'onDisplayTransactionsPage' ) );
	}

	public function handleActionsOnInit() {
        WPLE()->logger->debug("handleActionsOnInit()");

		// these actions have to wait until 'init'
		if ( $this->requestAction() == 'view_trx_details' ) {
			$this->showTransactionDetails( $_REQUEST['transaction'] );
			exit();
		}


	}

	function addScreenOptions() {
		$option = 'per_page';
		$args = array(
	    	'label' => 'Transactions',
	        'default' => 20,
	        'option' => 'transactions_per_page'
	        );
		add_screen_option( $option, $args );
		$this->transactionsTable = new TransactionsTable();
	
	    // add_thickbox();
		wp_enqueue_script( 'thickbox' );
		wp_enqueue_style( 'thickbox' );

	}
	


	public function onDisplayTransactionsPage() {
		$this->check_wplister_setup();

		// handle update ALL from eBay action
		if ( $this->requestAction() == 'update_transactions' ) {
			$this->initEC();
			$tm = $this->EC->loadTransactions();
			$this->EC->updateListings();
			$this->EC->closeEbay();

			// show transaction report
			$msg  = $tm->count_total .' '. __('Transactions were loaded from eBay.','wplister') . '<br>';
			$msg .= __('Timespan','wplister') .': '. $tm->getHtmlTimespan();
			$msg .= '&nbsp;&nbsp;';
			$msg .= '<a href="#" onclick="jQuery(\'#transaction_report\').toggle();return false;">'.__('show details','wplister').'</a>';
			$msg .= $tm->getHtmlReport();
			$this->showMessage( $msg );
		}
		// handle update from eBay action
		if ( $this->requestAction() == 'update' ) {
			if ( isset( $_REQUEST['transaction'] ) ) {
				$this->initEC();
				$this->EC->updateTransactionsFromEbay( $_REQUEST['transaction'] );
				$this->EC->closeEbay();
				$this->showMessage( __('Selected transactions were updated from eBay.','wplister') );		
			} else {
				$this->showMessage( __('You need to select at least one item from the list below in order to use bulk actions.','wplister'),1 );						
			}
		}
		// handle delete action
		if ( $this->requestAction() == 'delete' ) {
			if ( isset( $_REQUEST['transaction'] ) ) {
				$this->initEC();
				$this->EC->deleteTransactions( $_REQUEST['transaction'] );
				$this->EC->closeEbay();
				$this->showMessage( __('Selected items were removed.','wplister') );
			} else {
				$this->showMessage( __('You need to select at least one item from the list below in order to use bulk actions.','wplister'),1 );						
			}
		}
		// handle wpl_revert_transaction action
		if ( $this->requestAction() == 'wpl_revert_transaction' ) {
			if ( isset( $_REQUEST['id'] ) ) {
				$tm = new TransactionsModel();
				$tm->revertTransaction( $_REQUEST['id'] );
				$this->showMessage( __('Selected transaction was reverted.','wplister') );
			} else {
				$this->showMessage( __('You need to select at least one item from the list below in order to use bulk actions.','wplister'),1 );						
			}
		}

		// show warning if duplicate transactions found
		$this->checkForDuplicates();

	    //Create an instance of our package class...
	    $transactionsTable = new TransactionsTable();
    	//Fetch, prepare, sort, and filter our data...
	    $transactionsTable->prepare_items();
		
		$aData = array(
			'plugin_url'				=> self::$PLUGIN_URL,
			'message'					=> $this->message,

			'transactionsTable'			=> $transactionsTable,
			'preview_html'				=> isset($preview_html) ? $preview_html : '',
		
			'form_action'				=> 'admin.php?page='.self::ParentMenuId.'-transactions'
		);
		$this->display( 'transactions_page', $aData );
		

	}

	public function checkForDuplicates() {

		// show warning if duplicate products found
		$tm = new TransactionsModel();
		$duplicateTransactions = $tm->getAllDuplicateTransactions();
		if ( ! empty($duplicateTransactions) ) {

			// built message
			// $msg  = '<p><b>Warning: '.__('There are duplicate transactions for','wplister').' '.join(', ',$duplicateTransactions).'</b>';
			$msg  = '<p><b>Warning: '.__('There are duplicate transactions which should be removed.','wplister').'</b>';
			$msg .= '<br>';

			// table header
			$msg .= '<table style="width:100%">';
			$msg .= "<tr>";
			$msg .= "<th style='text-align:left'>Date</th>";
			$msg .= "<th style='text-align:left'>Transaction ID</th>";
			$msg .= "<th style='text-align:left'>Order ID</th>";
			$msg .= "<th style='text-align:left'>Last modified</th>";
			$msg .= "<th style='text-align:left'>Qty</th>";
			$msg .= "<th style='text-align:left'>eBay ID</th>";
			$msg .= "<th style='text-align:left'>Stock red.</th>";
			$msg .= "<th style='text-align:left'>New Stock</th>";
			$msg .= "<th style='text-align:left'>Status</th>";
			$msg .= "<th style='text-align:left'>&nbsp;</th>";
			$msg .= "</tr>";

			// table rows
			foreach ($duplicateTransactions as $transaction_id) {

				$transactions = $tm->getAllTransactionsByTransactionID( $transaction_id );
				$last_transaction_id = false;
				foreach ($transactions as $txn) {

					// get column data
					$qty     = $txn['quantity'];
					// $stock   = $txn['stock'] . ' x ';
					// $title   = $txn['auction_title'];
					// $post_id = $txn['post_id'];
					// $ebay_id = $txn['ebay_id'];

					// build links
					// $ebay_url = $txn['ViewItemURL'] ? $txn['ViewItemURL'] : $ebay_url = 'http://www.ebay.com/itm/'.$ebay_id;
					// $ebay_link = '<a href="'.$ebay_url.'" target="_blank">'.$ebay_id.'</a>';
					// $edit_link = '<a href="post.php?action=edit&post='.$post_id.'" target="_blank">'.$title.'</a>';

					// check if stock was reduced
					list( $reduced_product_id, $new_stock_value ) = $tm->checkIfStockWasReducedForItemID( $txn, $txn['item_id'] );

					// color results
					$color_id = 'silver';
					if ( $transaction_id != $last_transaction_id ) {
						$color_id = 'black';
						$last_transaction_id = $transaction_id;						
					}

					$color_status = 'auto';
					if ( $txn['CompleteStatus'] == 'Completed' ) {
						$color_status = 'darkgreen';
					}
					if ( $txn['CompleteStatus'] == 'Cancelled' ) {
						$color_status = 'silver';
					}

					// built buttons
					$actions = '';
					if ( $txn['status'] != 'reverted' && $txn['CompleteStatus'] != 'Completed' ) {
						$button_label = $reduced_product_id ? 'Restore stock' : 'Remove';
						$url = 'admin.php?page=wplister-transactions&action=wpl_revert_transaction&id='.$txn['id'];
						$actions = '<a href="'.$url.'" class="button button-small">'.$button_label.'</a>';
					}

					// build table row
					$msg .= "<tr>";
					$msg .= "<td>".$txn['date_created']."</td>";
					$msg .= "<td style='color:$color_id'>".$txn['transaction_id']."</td>";
					$msg .= "<td>".$txn['order_id']."</td>";
					$msg .= "<td>".$txn['LastTimeModified']."</td>";
					$msg .= "<td>".$txn['quantity']."</td>";
					$msg .= "<td>".$txn['item_id']."</td>";
					$msg .= "<td>".$reduced_product_id."</td>";
					$msg .= "<td>".$new_stock_value."</td>";
					$msg .= "<td style='color:$color_status'>".$txn['CompleteStatus']."</td>";
					$msg .= "<td>".$actions."</td>";
					// $msg .= "<td>$edit_link (ID $post_id)</td>";
					// $msg .= "<td>$qty x </td>";
					// $msg .= "<td>$ebay_link</td>";
					$msg .= "</tr>";

				}
			}
			$msg .= '</table>';

			$msg .= '<br>';
			// $msg .= $table;
			// $msg .= '<br>';
			// $msg .= 'This is caused by...';
			// $msg .= '<br><br>';
			// $msg .= 'To fix this... ';
			$msg .= '</p>';
			$this->showMessage( $msg, 1 );				
		}
	}


	public function showTransactionDetails( $id ) {
	
		// init model
		$transactionsModel = new TransactionsModel();		

		// get transaction record
		$transaction = $transactionsModel->getItem( $id );
		
		// get auction item record
		$auction_item = ListingsModel::getItemByEbayID( $transaction['item_id'] );
		
		$aData = array(
			'transaction'				=> $transaction,
			'auction_item'				=> $auction_item
		);
		$this->display( 'transaction_details', $aData );
		
	}


}
