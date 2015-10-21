<?php
/**
 * WPLE_AccountsPage class
 * 
 */

class WPLE_AccountsPage extends WPL_Page {

	const slug = 'accounts';

	public function onWpInit() {

		// Add custom screen options
		if ( ! isset($_GET['tab']) || $_GET['tab'] == 'accounts' ) {
			// $load_action = "load-".$this->main_admin_menu_slug."_page_wplister-".self::slug;
			$load_action = "load-".$this->main_admin_menu_slug."_page_wplister-".'settings';
			add_action( $load_action, array( &$this, 'addScreenOptions' ) );
		}

		if ( get_option( 'wplister_enable_accounts_page' ) ) {
			$load_action = "load-".$this->main_admin_menu_slug."_page_wplister-".'settings';
			add_action( $load_action.'-accounts', array( &$this, 'addScreenOptions' ) );
		}

	}

	
	public function handleActions() {
        // WPLE()->logger->debug("handleActions()");

		// add new account (triggered by 'Fetch eBay Token' button on accounts page)
		if ( $this->requestAction() == 'wplister_add_account' ) {
			$this->newAccount();
		}

		// fetch token for account (triggered from edit account page, right sidebar)
		if ( $this->requestAction() == 'wplister_fetch_ebay_token' ) {
			$this->fetchTokenForAccount( $_REQUEST['account_id'] );
		}

		// update account details from ebay
		if ( $this->requestAction() == 'update_account' ) {
			$this->updateAccount( $_REQUEST['ebay_account'] );
		}

		// delete account
		if ( $this->requestAction() == 'delete_account' ) {
			$account = new WPLE_eBayAccount( $_REQUEST['ebay_account'] );
			$account->delete();
			$this->showMessage( __('Account has been deleted.','wplister') );
		}

		// enable account
		if ( $this->requestAction() == 'enable_account' ) {
			$account = new WPLE_eBayAccount( $_REQUEST['ebay_account'] );
			$account->active = 1;
			$account->update();
			$this->showMessage( __('Account has been enabled.','wplister') );
		}

		// disable account
		if ( $this->requestAction() == 'disable_account' ) {
			$account = new WPLE_eBayAccount( $_REQUEST['ebay_account'] );
			$account->active = 0;
			$account->update();
			$this->showMessage( __('Account has been disabled.','wplister') );
		}

		// set default account
		if ( $this->requestAction() == 'make_default' ) {
			$this->makeDefaultAccount( $_REQUEST['ebay_account'] );
			$this->showMessage( __('Default account has been changed successfully.','wplister') );
		}

		// add empty developer account
		if ( $this->requestAction() == 'wple_add_dev_account' ) {
			$this->addEmptyAccount();
		}

		// assign invalid data to default account
		if ( $this->requestAction() == 'wple_assign_invalid_data_to_default_account' ) {
			WPL_Setup::fixItemsUsingInvalidAccounts();
		}


	}
	

	public function updateAccount( $id ) {

		$account = new WPLE_eBayAccount( $id );
		if ( ! $account ) return;

		// update user details
		$account->updateUserDetails();

		$this->showMessage( __('Account details have been updated.','wplister') );
	}

	public function addEmptyAccount() {

		$account = new WPLE_eBayAccount();

		$account->title                    = 'New Account (DEV)';
		$account->active                   = 0;
		$account->site_id                  = 0;
		$account->site_code                = 'US';
		$account->user_name                = 'NONE';
		$account->sandbox_mode             = 1;
		$account->ebay_motors              = 0;
		$account->add();

		$this->showMessage( 'New developer account has been added.' );
	}

	public function makeDefaultAccount( $id ) {

		$account = new WPLE_eBayAccount( $id );
		if ( ! $account ) return;

		// update default account
		update_option( 'wplister_default_account_id', 			$account->id );

		// backwards compatibility
		update_option( 'wplister_ebay_site_id', 				$account->site_id );
		update_option( 'wplister_ebay_token', 					$account->token );
		update_option( 'wplister_ebay_token_userid', 			$account->user_name );
		update_option( 'wplister_sandbox_enabled', 				$account->sandbox_mode );
		update_option( 'wplister_ebay_token_expirationtime', 	$account->valid_until );
		update_option( 'wplister_enable_ebay_motors', 			$account->ebay_motors ); // deprecated
		update_option( 'wplister_ebay_seller_profiles_enabled', $account->seller_profiles );
		update_option( 'wplister_default_ebay_category_id', 	$account->default_ebay_category_id );
		update_option( 'wplister_paypal_email', 				$account->paypal_email );
		update_option( 'wplister_oosc_mode', 					$account->oosc_mode );
		update_option( 'wplister_ebay_user', 					maybe_unserialize( $account->user_details ) );
		update_option( 'wplister_categories_map_ebay', 			maybe_unserialize( $account->categories_map_ebay ) );
		update_option( 'wplister_categories_map_store', 		maybe_unserialize( $account->categories_map_store ) );

	}

	function addScreenOptions() {
		
		// render table options
		$option = 'per_page';
		$args = array(
	    	'label' => 'Accounts',
	        'default' => 20,
	        'option' => 'accounts_per_page'
	        );
		add_screen_option( $option, $args );
		$this->accountsTable = new WPLE_AccountsTable();
	
	    // add_thickbox();
		wp_enqueue_script( 'thickbox' );
		wp_enqueue_style( 'thickbox' );

	}
	

	public function displayAccountsPage() {

		// handle actions and show notes
		$this->handleActions();

		if ( $this->requestAction() == 'save_account' ) {
			$this->saveAccount();
		}
		if ( $this->requestAction() == 'edit_account' ) {
			return $this->displayEditAccountsPage();
		}

		if ( $default_account_id = get_option( 'wplister_default_account_id' ) ) {
			$default_account = WPLE_eBayAccount::getAccount( $default_account_id );
			if ( ! $default_account ) {
				$this->showMessage( __('Your default account does not exist anymore. Please select a new default account.','wplister'),1);
			} else {
				// make sure the eBay token stored in wp_options matches the default account
				$ebay_token_v1 = get_option('wplister_ebay_token');
				if ( $ebay_token_v1 != $default_account->token ) {
					// update_option( 'wplister_ebay_token', $default_account->token );
					$this->makeDefaultAccount( $default_account->id ); // update everything, including expiration time
					$this->showMessage( __('A new eBay token was found and your default account has been updated accordingly.','wplister'),2);
				}
			}
		}

		// refresh enabled sites automatically for now
		$this->fixEnabledSites();

		// check for data linked to deleted accounts
		WPL_Setup::checkDbForInvalidAccounts();

	    // create table and fetch items to show
	    $this->accountsTable = new WPLE_AccountsTable();
	    $this->accountsTable->prepare_items();

	    $form_action = 'admin.php?page='.self::ParentMenuId.'-settings'.'&tab=accounts';
	    if ( @$_REQUEST['page'] == 'wplister-settings-accounts' )
		    $form_action = 'admin.php?page=wplister-settings-accounts';

		$active_tab = 'accounts';
		$aData = array(
			'plugin_url'				=> self::$PLUGIN_URL,
			'message'					=> $this->message,

			'accountsTable'				=> $this->accountsTable,
			'ebay_accounts'				=> WPLE_eBayAccount::getAll( true ),
			'ebay_sites'				=> EbayController::getEbaySites(),
			'active_ebay_sites'	    	=> WPLE_eBaySite::getAll(),
			'default_account'			=> get_option( 'wplister_default_account_id' ),

			'settings_url'				=> 'admin.php?page='.self::ParentMenuId.'-settings',
			'auth_url'					=> $form_action.'&action=wplRedirectToAuthURL',
			'form_action'				=> $form_action
		);
		$this->display( 'settings_accounts', $aData );
	}


	public function displayEditAccountsPage() {

	    // get account
	    $account_id = $_REQUEST['ebay_account'];
	    $account = new WPLE_eBayAccount( $account_id );
	    if ( ! $account ) die('wrong account');

	    $account->user_details = maybe_unserialize( $account->user_details );

	    $form_action = 'admin.php?page='.self::ParentMenuId.'-settings'.'&tab=accounts';
	    if ( @$_REQUEST['page'] == 'wplister-settings-accounts' )
		    $form_action = 'admin.php?page=wplister-settings-accounts';

		$active_tab = 'accounts';
		$aData = array(
			'plugin_url'				=> self::$PLUGIN_URL,
			'message'					=> $this->message,

			'account'					=> $account,
			// 'ebay_sites'				=> WPLE_eBaySite::getAll(),
			'ebay_sites'				=> EbayController::getEbaySites(),
			'default_account'			=> get_option( 'wplister_default_account_id' ),

			'settings_url'				=> 'admin.php?page='.self::ParentMenuId.'-settings',
			'auth_url'					=> $form_action.'&action=wplRedirectToAuthURL'.'&site_id='.$account->site_id.'&sandbox='.$account->sandbox_mode,
			'form_action'				=> $form_action
		);
		$this->display( 'account/account_edit_page', $aData );
	}



	protected function saveAccount() {

		// TODO: check nonce
		if ( isset( $_POST['wplister_account_id'] ) ) {

			// get account
			$account               = new WPLE_eBayAccount( $_POST['wplister_account_id'] );
			$account->title        = stripslashes( $_POST['wplister_title'] );
			$account->site_id      = $_POST['wplister_site_id'];
			$account->site_code    = EbayController::getEbaySiteCode( $account->site_id );
			$account->token        = $_POST['wplister_token'];
			$account->sandbox_mode = $_POST['wplister_sandbox_mode'];
			$account->active       = $_POST['wplister_account_is_active'];
			$account->paypal_email = $_POST['wplister_paypal_email'];
			$account->oosc_mode    = @$_POST['wplister_oosc_mode'];
			$account->update();

			// update user details
			// $account->updateUserDetails();

			// set enabled flag for site
			$site = WPLE_eBaySite::getSiteObj($account->site_id);
			$site->enabled = 1;
			$site->update();	
			
			// update default account
			if ( $account->id == get_option('wplister_default_account_id') ) {
				$this->makeDefaultAccount( $account->id );
			}

			$this->showMessage( __('Account was updated.','wplister') );
		}
	}


	protected function fixEnabledSites() {
		global $wpdb;

		// disable all sites
		$wpdb->update( $wpdb->prefix.'ebay_sites', array( 'enabled' => 0 ), array( 'enabled' => 1 ) );

		// enable site for each account
		foreach ( WPLE()->accounts as $account ) {
			$wpdb->update( $wpdb->prefix.'ebay_sites', array( 'enabled' => 1 ), array( 'id' => $account->site_id ) );
		}			

	}


	protected function fetchTokenForAccount( $account_id ) {

		// call FetchToken
		$this->initEC( $account_id );
		$ebay_token = $this->EC->doFetchToken( $account_id );
		$this->EC->closeEbay();

		// check if we have a token
		if ( $ebay_token ) {

			// update token expiry date (and other details)
			$this->updateAccount( $account_id );

			// update legacy option
			update_option( 'wplister_ebay_token_is_invalid', false );

			$this->showMessage( __('eBay token was updated.','wplister') );
		} else {
			$this->showMessage( "There was a problem fetching your token. Make sure you follow the instructions.", 1 );
		}

	}


	protected function newAccount() {

		// call FetchToken
		$this->initEC();
		$ebay_token = $this->EC->doFetchToken( false );
		$this->EC->closeEbay();

		// check if we have a token
		if ( $ebay_token ) {

			// create new account
			$account = new WPLE_eBayAccount();
			// $account->title     = stripslashes( $_POST['wplister_account_title'] );
			$account->title        = 'My Account';
			$account->site_id      = $_REQUEST['site_id'];
			$account->site_code    = EbayController::getEbaySiteCode( $_REQUEST['site_id'] );
			$account->sandbox_mode = $_REQUEST['sandbox'];
			$account->token        = $ebay_token;
			$account->active       = 1;
			$account->add();

			// set enabled flag for site
			$site = WPLE_eBaySite::getSiteObj($account->site_id);
			$site->enabled = 1;
			$site->update();	

			// update user details
			$account->updateUserDetails();

			// set default account automatically
			if ( ! get_option( 'wplister_default_account_id' ) ) {
				update_option( 'wplister_default_account_id', $account->id );
				$this->makeDefaultAccount( $account->id );
			}

			$this->check_wplister_setup('settings');

			$this->showMessage( __('New account was added.','wplister') .' '. __('Please refresh account and site specific details now.','wplister')   );

		} else {
			$this->showMessage( "There was a problem fetching your token. Make sure you follow the instructions.", 1 );
		}

	}


}
