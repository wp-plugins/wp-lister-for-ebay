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
		add_action( "load-wp-lister_page_wplister-".self::slug, array( &$this, 'addScreenOptions' ) );

		// handle actions
		$this->handleActionsOnInit();
	}

	public function onWpAdminMenu() {
		parent::onWpAdminMenu();

		add_submenu_page( self::ParentMenuId, $this->getSubmenuPageTitle( 'Transactions' ), __('Transactions','wplister'), 
						  self::ParentPermissions, $this->getSubmenuId( 'transactions' ), array( &$this, 'onDisplayTransactionsPage' ) );
	}

	public function handleActionsOnInit() {
        $this->logger->debug("handleActionsOnInit()");

		// these actions have to wait until 'init'
		if ( $this->requestAction() == 'view_invoice' ) {
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
			$this->EC->loadTransactions();
			$this->EC->updateListings();
			$this->EC->closeEbay();
			$this->showMessage( __('Transactions were updated from eBay.','wplister') );
		}
		// handle update from eBay action
		if ( $this->requestAction() == 'update' ) {
			$this->initEC();
			$this->EC->updateTransactionsFromEbay( $_REQUEST['transaction'] );
			$this->EC->closeEbay();
			$this->showMessage( __('Selected transactions were updated from eBay.','wplister') );
		}
		// handle delete action
		if ( $this->requestAction() == 'delete' ) {
			$this->initEC();
			$this->EC->deleteTransactions( $_REQUEST['transaction'] );
			$this->EC->closeEbay();
			$this->showMessage( __('Selected items were removed.','wplister') );
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
