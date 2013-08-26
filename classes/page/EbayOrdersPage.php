<?php
/**
 * EbayOrdersPage class
 * 
 */

class EbayOrdersPage extends WPL_Page {

	const slug = 'orders';

	public function onWpInit() {
		// parent::onWpInit();

		// Add custom screen options
		add_action( "load-wp-lister_page_wplister-".self::slug, array( &$this, 'addScreenOptions' ) );

		// handle actions
		$this->handleActionsOnInit();
	}

	public function onWpAdminMenu() {
		parent::onWpAdminMenu();

		add_submenu_page( self::ParentMenuId, $this->getSubmenuPageTitle( 'Orders' ), __('Orders','wplister'), 
						  self::ParentPermissions, $this->getSubmenuId( 'orders' ), array( &$this, 'onDisplayEbayOrdersPage' ) );
	}

	public function handleActionsOnInit() {
        $this->logger->debug("handleActionsOnInit()");

		// these actions have to wait until 'init'
		if ( $this->requestAction() == 'view_ebay_order_details' ) {
			$this->showOrderDetails( $_REQUEST['ebay_order'] );
			exit();
		}

	}

	function addScreenOptions() {
		$option = 'per_page';
		$args = array(
	    	'label' => 'Orders',
	        'default' => 20,
	        'option' => 'orders_per_page'
	        );
		add_screen_option( $option, $args );
		$this->ordersTable = new EbayOrdersTable();
	
	    // add_thickbox();
		wp_enqueue_script( 'thickbox' );
		wp_enqueue_style( 'thickbox' );

	}
	


	public function onDisplayEbayOrdersPage() {
		WPL_Setup::checkSetup();

		// handle update ALL from eBay action
		if ( $this->requestAction() == 'update_orders' ) {
			$this->initEC();
			// $tm = $this->EC->loadEbayOrders();
			$tm = $this->EC->updateEbayOrders();
			$this->EC->updateListings();
			$this->EC->closeEbay();

			// show ebay_order report
			$msg  = $tm->count_total .' '. __('order(s) found on eBay.','wplister') . '<br>';
			$msg .= __('Timespan','wplister') .': '. $tm->getHtmlTimespan() . '&nbsp;&nbsp;';
			$msg .= '<a href="#" onclick="jQuery(\'#ebay_order_report\').toggle();return false;">'.__('show details','wplister').'</a>';
			$msg .= $tm->getHtmlReport();
			$this->showMessage( $msg );
		}

		// handle update from eBay action
		if ( $this->requestAction() == 'update' ) {
			if ( isset( $_REQUEST['ebay_order'] ) ) {
				$this->initEC();
				$tm = $this->EC->updateEbayOrders( false, $_REQUEST['ebay_order'] );
				$this->EC->updateListings();
				$this->EC->closeEbay();
				// $this->showMessage( __('Selected orders were updated from eBay.','wplister') );		

				// show ebay_order report
				$msg  = $tm->count_total .' '. __('orders were updated from eBay.','wplister') . '<!br>' . '&nbsp;&nbsp;';
				$msg .= '<a href="#" onclick="jQuery(\'#ebay_order_report\').toggle();return false;">'.__('show details','wplister').'</a>';
				$msg .= $tm->getHtmlReport();
				$this->showMessage( $msg );

			} else {
				$this->showMessage( __('You need to select at least one item from the list below in order to use bulk actions.','wplister'),1 );						
			}
		}

		// handle delete action
		if ( $this->requestAction() == 'delete' ) {
			if ( isset( $_REQUEST['ebay_order'] ) ) {
				$tm = new EbayOrdersModel();
				foreach ( $_REQUEST['ebay_order'] as $id ) {
					$tm->deleteItem( $id );
				}
				$this->showMessage( __('Selected items were removed.','wplister') );
			} else {
				$this->showMessage( __('You need to select at least one item from the list below in order to use bulk actions.','wplister'),1 );						
			}
		}



	    //Create an instance of our package class...
	    $ordersTable = new EbayOrdersTable();
    	//Fetch, prepare, sort, and filter our data...
	    $ordersTable->prepare_items();
		
		$aData = array(
			'plugin_url'				=> self::$PLUGIN_URL,
			'message'					=> $this->message,

			'ordersTable'				=> $ordersTable,
			'preview_html'				=> isset($preview_html) ? $preview_html : '',
		
			'form_action'				=> 'admin.php?page='.self::ParentMenuId.'-orders'
		);
		$this->display( 'orders_page', $aData );
		

	}

	public function showOrderDetails( $id ) {
	
		// init model
		$ordersModel = new EbayOrdersModel();		

		// get ebay_order record
		$ebay_order = $ordersModel->getItem( $id );
		
		// get WooCommerce order
		$wc_order_notes = $this->get_order_notes( $ebay_order['post_id'] );

		// get auction item record
		// $listingsModel = new ListingsModel();		
		// $auction_item = $listingsModel->getItemByEbayID( $ebay_order['item_id'] );
		
		$aData = array(
			'ebay_order'				=> $ebay_order,
			'wc_order_notes'			=> $wc_order_notes,
			// 'auction_item'			=> $auction_item
		);
		$this->display( 'order_details', $aData );
		
	}

	public function get_order_notes( $id ) {

		$notes = array();

		$args = array(
			'post_id' => $id,
			'approve' => 'approve',
			'type' => ''
		);

		remove_filter('comments_clauses', 'woocommerce_exclude_order_comments');

		$comments = get_comments( $args );

		foreach ($comments as $comment) :
			// $is_customer_note = get_comment_meta($comment->comment_ID, 'is_customer_note', true);
			// $comment->comment_content = make_clickable($comment->comment_content);
			$notes[] = $comment;
		endforeach;

		add_filter('comments_clauses', 'woocommerce_exclude_order_comments');

		return (array) $notes;

	}



}
