<?php
/**
 * ToolsPage class
 * 
 */

class ToolsPage extends WPL_Page {

	const slug = 'tools';

	public function onWpInit() {
		// parent::onWpInit();

		// custom (raw) screen options for tools page
		add_screen_options_panel('wplister_setting_options', '', array( &$this, 'renderSettingsOptions'), 'wp-lister_page_wplister-tools' );

		// load styles and scripts for this page only
		add_action( 'admin_print_styles', array( &$this, 'onWpPrintStyles' ) );
		add_action( 'admin_enqueue_scripts', array( &$this, 'onWpEnqueueScripts' ) );		
		add_thickbox();
	}

	public function onWpAdminMenu() {
		parent::onWpAdminMenu();

		add_submenu_page( self::ParentMenuId, $this->getSubmenuPageTitle( 'Tools' ), __('Tools','wplister'), 
						  self::ParentPermissions, $this->getSubmenuId( 'tools' ), array( &$this, 'onDisplayToolsPage' ) );
	}

	public function handleSubmit() {
        $this->logger->debug("handleSubmit()");

		// force wp update check
		if ( $this->requestAction() == 'force_update_check') {				
			global $wpdb;
			$wpdb->query("update wp_options set option_value='' where option_name='_site_transient_update_plugins'");
			$this->showMessage( 
				__('Check for updates was initiated.','wplister') . ' '
				. __('You can visit your WordPress Updates now.','wplister') . '<br><br>'
				. __('Since the updater runs in the background, it might take a little while before new updates appear.','wplister') . '<br><br>'
				. '&raquo; <a href="update-core.php">'.__('view updates','wplister') . '</a>'
			);
		}

	}
	

	public function onDisplayToolsPage() {
		
		WPL_Setup::checkSetup();

		// check action - and nonce
		if ( isset($_POST['action']) ) {
			if ( check_admin_referer( 'e2e_tools_page' ) ) {

				// test_connection
				if ( $_POST['action'] == 'test_connection') {				
					$this->initEC();
					$debug = $this->EC->testConnection();
					$this->EC->closeEbay();
				}
				// test_connection
				if ( $_POST['action'] == 'view_logfile') {				
					$this->viewLogfile();
				}
				// GetTokenStatus
				if ( $_POST['action'] == 'GetTokenStatus') {				
					$this->initEC();
					$expdate = $this->EC->GetTokenStatus();
					// $expdate = $this->EC->getTokenExpirationTime( self::getOption('ebay_site_id'), 
					// 					 		   self::getOption('sandbox_enabled') );
					$this->EC->closeEbay();
					$this->showMessage( __('Your token will expire on ','wplister') . $expdate );
				}
				// GetNotificationPreferences
				if ( $_POST['action'] == 'GetNotificationPreferences') {				
					$this->initEC();
					$debug = $this->EC->GetNotificationPreferences();
					$this->EC->closeEbay();
				}
				// SetNotificationPreferences
				if ( $_POST['action'] == 'SetNotificationPreferences') {				
					$this->initEC();
					$debug = $this->EC->SetNotificationPreferences();
					$this->EC->closeEbay();
				}
	
				// update_ebay_categories
				// if ( $_POST['action'] == 'update_ebay_categories') {				
				// 	$this->initEC();
				// 	$this->EC->loadCategories();
				// 	$this->EC->closeEbay();
				// 	$this->showMessage( __('Categories updated.','wplister') );
				// }
	
				// update_store_categories
				// if ( $_POST['action'] == 'update_store_categories') {				
				// 	$this->initEC();
				// 	$this->EC->loadStoreCategories();
				// 	$this->EC->closeEbay();
				// 	$this->showMessage( __('Store categories updated.','wplister') );
				// }
	
				// update_ebay_auctions
				if ( $_POST['action'] == 'update_ebay_auctions') {				
					$this->initEC();
					$this->EC->loadListings();
					$this->EC->closeEbay();
					$this->showMessage( __('Active auctions updated.','wplister') );
				}

				// update_ebay_transactions
				if ( $_POST['action'] == 'update_ebay_transactions') {				
					$this->initEC();
					$this->EC->loadTransactions();
					$this->EC->updateListings();
					$this->EC->closeEbay();
					$this->showMessage( __('Transactions updated.','wplister') );
				}
	
				// update ebay shipping and payment data
				// if ( $_POST['action'] == 'update_ebay_details') {				
				// 	$this->initEC();
				// 	$this->EC->loadShippingServices();
				// 	$this->EC->loadPaymentOptions();
				// 	$this->EC->closeEbay();
				// 	$this->showMessage( __('Shipping services and payment options updated.','wplister') );
				// }

	
			} else {
				die ('not allowed');
			}
		}

		$aData = array(
			'plugin_url'				=> self::$PLUGIN_URL,
			'message'					=> $this->message,		
			'debug'						=> isset($debug) ? $debug : '',
			'form_action'				=> 'admin.php?page='.self::ParentMenuId.'-tools'
		);
		$this->display( 'tools_page', $aData );
	}

	public function viewLogfile() {
		global $wpl_logger;

		echo "<pre>";
		echo readfile( $wpl_logger->file );
		echo "<br>logfile: " . $wpl_logger->file . "<br>";
		echo "</pre>";

	}

	public function renderSettingsOptions() {
		?>
		<div class="hidden" id="screen-options-wrap" style="display: block;">
			<form method="post" action="" id="dev-settings">
				<h5>Show on screen</h5>
				<div class="metabox-prefs">
						<label for="dev-hide">
							<input type="checkbox" onclick="jQuery('#DeveloperToolBox').toggle();" value="dev" id="dev-hide" name="dev-hide" class="hide-column-tog">
							Developer options
						</label>
					<br class="clear">
				</div>
			</form>
		</div>
		<?php
	}


	
	public function onWpPrintStyles() {

		// testing:
		// jQuery UI theme - for progressbar
		// wp_register_style('jQueryUITheme', 'http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.13/themes/cupertino/jquery-ui.css');
		wp_register_style('jQueryUITheme', plugins_url( 'css/smoothness/jquery-ui-1.8.22.custom.css' , WPLISTER_PATH.'/wp-lister.php' ) );
		wp_enqueue_style('jQueryUITheme'); 

	}

	public function onWpEnqueueScripts() {

		// testing:
		// jQuery UI progressbar
        wp_enqueue_script('jquery-ui-core');
        wp_enqueue_script('jquery-ui-progressbar');

		// jqueryFileTree
		wp_register_script( 'wpl_JobRunner', self::$PLUGIN_URL.'/js/classes/JobRunner.js', array( 'jquery', 'jquery-ui-core', 'jquery-ui-progressbar' ) );
		wp_enqueue_script( 'wpl_JobRunner' );


	    // jQuery UI Dialog
    	// wp_enqueue_style( 'wp-jquery-ui-dialog' );
	    // wp_enqueue_script ( 'jquery-ui-dialog' ); 

	}


}
