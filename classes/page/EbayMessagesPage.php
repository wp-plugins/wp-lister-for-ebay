<?php
/**
 * EbayMessagesPage class
 * 
 */

class EbayMessagesPage extends WPL_Page {

	const slug = 'messages';

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

		if ( ! get_option( 'wplister_enable_messages_page' ) ) return;

		add_submenu_page( self::ParentMenuId, $this->getSubmenuPageTitle( 'Messages' ), __('Messages','wplister'), 
						  self::ParentPermissions, $this->getSubmenuId( 'messages' ), array( &$this, 'onDisplayEbayMessagesPage' ) );
	}

	public function handleActionsOnInit() {
        WPLE()->logger->debug("handleActionsOnInit()");

		// these actions have to wait until 'init'
		if ( $this->requestAction() == 'view_ebay_message_details' ) {
			$this->showMessageDetails( $_REQUEST['ebay_message'] );
			exit();
		}

	}

	function addScreenOptions() {
		$option = 'per_page';
		$args = array(
	    	'label' => 'Messages',
	        'default' => 20,
	        'option' => 'messages_per_page'
	        );
		add_screen_option( $option, $args );
		$this->messagesTable = new EbayMessagesTable();
	
	    // add_thickbox();
		wp_enqueue_script( 'thickbox' );
		wp_enqueue_style( 'thickbox' );

	}
	


	public function onDisplayEbayMessagesPage() {
		$this->check_wplister_setup();

		// handle update ALL from eBay action
		if ( $this->requestAction() == 'update_messages' ) {

			$accounts = WPLE_eBayAccount::getAll();
			$msg = '';

			// loop each active account
			foreach ( $accounts as $account ) {

				$this->initEC( $account->id );
				$mm = $this->EC->updateEbayMessages();
				$this->EC->closeEbay();

				// show ebay_message report
				$msg .= sprintf( __('%s message(s) found on eBay for account %s.','wplister'), $mm->count_total, $account->title ) . '<br>';
				$msg .= __('Timespan','wplister') .': '. $mm->getHtmlTimespan() . '&nbsp;&nbsp;';
				$msg .= '<a href="#" onclick="jQuery(\'#ebay_message_report\').toggle();return false;">'.__('show details','wplister').'</a>';
				$msg .= $mm->getHtmlReport();
				$msg .= '<hr>';

			}
			$this->showMessage( $msg );

		}

		// handle update from eBay bulk action
		if ( $this->requestAction() == 'update' ) {
			if ( isset( $_REQUEST['ebay_message'] ) ) {

				// use account_id of first item (todo: group items by account)
				$mm         = new EbayMessagesModel();
				$message    = $mm->getItem( $_REQUEST['ebay_message'][0] );
				$account_id = $message['account_id'];

				$this->initEC( $account_id );
				$mm = $this->EC->updateEbayMessages( false, $_REQUEST['ebay_message'] );
				$this->EC->closeEbay();
				// $this->showMessage( __('Selected messages were updated from eBay.','wplister') );		

				// show ebay_message report
				$msg  = $mm->count_total .' '. __('messages were updated from eBay.','wplister') . '<!br>' . '&nbsp;&nbsp;';
				$msg .= '<a href="#" onclick="jQuery(\'#ebay_message_report\').toggle();return false;">'.__('show details','wplister').'</a>';
				$msg .= $mm->getHtmlReport();
				$this->showMessage( $msg );

			} else {
				$this->showMessage( __('You need to select at least one item from the list below in message to use bulk actions.','wplister'),1 );						
			}
		}

		// handle delete action
		if ( $this->requestAction() == 'delete' ) {
			if ( isset( $_REQUEST['ebay_message'] ) ) {
				$mm = new EbayMessagesModel();
				foreach ( $_REQUEST['ebay_message'] as $id ) {
					$mm->deleteItem( $id );
				}
				$this->showMessage( __('Selected items were removed.','wplister') );
			} else {
				$this->showMessage( __('You need to select at least one item from the list below in message to use bulk actions.','wplister'),1 );						
			}
		}


	    //Create an instance of our package class...
	    $messagesTable = new EbayMessagesTable();
    	//Fetch, prepare, sort, and filter our data...
	    $messagesTable->prepare_items();
		
		$aData = array(
			'plugin_url'				=> self::$PLUGIN_URL,
			'message'					=> $this->message,

			'messagesTable'				=> $messagesTable,
		
			'form_action'				=> 'admin.php?page='.self::ParentMenuId.'-messages'
		);
		$this->display( 'messages_page', $aData );
		

	}

	public function showMessageDetails( $id ) {
	
		// init model
		$messagesModel = new EbayMessagesModel();		

		// get ebay_message record
		$ebay_message = $messagesModel->getItem( $id );
		
		$aData = array(
			'ebay_message'				=> $ebay_message,
		);
		$this->display( 'message_details', $aData );
		
	}

}
