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
		if ( ( $page != 'wplister-transactions') && ( 'transaction' != get_option( 'wplister_ebay_update_mode', 'transaction' ) ) ) return;

		add_submenu_page( self::ParentMenuId, $this->getSubmenuPageTitle( 'Transactions' ), __('Transactions','wplister'), 
						  self::ParentPermissions, $this->getSubmenuId( 'transactions' ), array( &$this, 'onDisplayTransactionsPage' ) );
	}

	public function handleActionsOnInit() {
        $this->logger->debug("handleActionsOnInit()");

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
		WPL_Setup::checkSetup();

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



	public function showTransactionDetails( $id ) {
	
		// init model
		$transactionsModel = new TransactionsModel();		

		// get transaction record
		$transaction = $transactionsModel->getItem( $id );
		
		// get auction item record
		$listingsModel = new ListingsModel();		
		$auction_item = $listingsModel->getItemByEbayID( $transaction['item_id'] );
		
		$aData = array(
			'transaction'				=> $transaction,
			'auction_item'				=> $auction_item
		);
		$this->display( 'transaction_details', $aData );
		
	}


}
