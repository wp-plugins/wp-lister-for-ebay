<?php
/**
 * SettingsPage class
 * 
 */

class SettingsPage extends WPL_Page {

	const slug = 'settings';

	public function onWpInit() {
		// parent::onWpInit();

		// custom (raw) screen options for settings page
		add_screen_options_panel('wplister_setting_options', '', array( &$this, 'renderSettingsOptions'), 'wp-lister_page_wplister-settings' );

		// Add custom screen options
		add_action( "load-wp-lister_page_wplister-".self::slug, array( &$this, 'addScreenOptions' ) );

	}

	public function onWpAdminMenu() {
		parent::onWpAdminMenu();

		add_submenu_page( self::ParentMenuId, $this->getSubmenuPageTitle( 'Settings' ), __('Settings','wplister'), 
						  'manage_options', $this->getSubmenuId( 'settings' ), array( &$this, 'onDisplaySettingsPage' ) );
	}

	function addScreenOptions() {
		// load styles and scripts for this page only
		add_action( 'admin_print_styles', array( &$this, 'onWpPrintStyles' ) );
		add_action( 'admin_enqueue_scripts', array( &$this, 'onWpEnqueueScripts' ) );		
		$this->categoriesMapTable = new CategoriesMapTable();
		add_thickbox();
	}
	
	public function handleSubmit() {
        $this->logger->debug("handleSubmit()");

		// handle redirect to ebay auth url
		if ( $this->requestAction() == 'wplRedirectToAuthURL') {				

			// get auth url
			$this->initEC();
			$auth_url = $this->EC->getAuthUrl();
			$this->EC->closeEbay();

			$this->logger->info( "wplRedirectToAuthURL() to: ", $auth_url );
			wp_redirect( $auth_url );
		}

		// save settings
		if ( $this->requestAction() == 'save_wplister_settings' ) {
			$this->saveSettings();
		}

		// save category map
		if ( $this->requestAction() == 'save_wplister_categories_map' ) {
			$this->saveCategoriesSettings();
		}


		// save developer settings
		if ( $this->requestAction() == 'save_wplister_devsettings' ) {
			$this->saveDeveloperSettings();
		}

		// set ebay site
		if ( $this->requestAction() == 'save_ebay_site' ) {
			self::updateOption( 'ebay_site_id',		$this->getValueFromPost( 'text_ebay_site_id' ) );
		}

	}
	

	public function onDisplaySettingsPage() {
		WPL_Setup::checkSetup('settings');

        $active_tab = isset( $_GET[ 'tab' ] ) ? $_GET[ 'tab' ] : 'settings';
        if ( 'categories' == $active_tab ) return $this->displayCategoriesPage();
        if ( 'developer' == $active_tab ) return $this->displayDeveloperPage();
	
		// action FetchToken
		if ( $this->requestAction() == 'FetchToken' ) {

			// FetchToken
			$this->initEC();
			$ebay_token = $this->EC->doFetchToken();
			$this->EC->closeEbay();

			// check if we have a token
			if ( self::getOption('ebay_token') == '' ) {
				$this->showMessage( "There was a problem fetching your token. Make sure you follow the instructions.", 1 );
			}

			WPL_Setup::checkSetup('settings');
		}


		$aData = array(
			'plugin_url'				=> self::$PLUGIN_URL,
			'message'					=> $this->message,

			'text_ebay_token'			=> self::getOption( 'ebay_token' ),
			'text_ebay_site_id'			=> self::getOption( 'ebay_site_id' ),
			'text_paypal_email'			=> self::getOption( 'paypal_email' ),
			'ebay_sites'				=> EbayController::getEbaySites(),

			'option_cron_auctions'		=> self::getOption( 'cron_auctions' ),
			'option_cron_transactions'	=> self::getOption( 'cron_transactions' ),
			'option_uninstall'			=> self::getOption( 'uninstall' ),
	
			'settings_url'				=> 'admin.php?page='.self::ParentMenuId.'-settings',
			'auth_url'					=> 'admin.php?page='.self::ParentMenuId.'-settings'.'&tab='.$active_tab.'&action=wplRedirectToAuthURL',
			'form_action'				=> 'admin.php?page='.self::ParentMenuId.'-settings'.'&tab='.$active_tab
		);
		$this->display( 'settings_page', $aData );
	}

	public function displayCategoriesPage() {

    	$shop_categories = $this->loadProductCategories();

	    //Create an instance of our package class...
	    $categoriesMapTable = new CategoriesMapTable();
    	//Fetch, prepare, sort, and filter our data...
	    $categoriesMapTable->prepare_items( $shop_categories );


		$aData = array(
			'plugin_url'				=> self::$PLUGIN_URL,
			'message'					=> $this->message,

			'shop_categories'			=> $shop_categories,
			'categoriesMapTable'		=> $categoriesMapTable,

			'settings_url'				=> 'admin.php?page='.self::ParentMenuId.'-settings',
			'form_action'				=> 'admin.php?page='.self::ParentMenuId.'-settings'.'&tab=categories'
		);
		$this->display( 'settings_categories', $aData );
	}


	public function displayDeveloperPage() {

		$aData = array(
			'plugin_url'				=> self::$PLUGIN_URL,
			'message'					=> $this->message,

			'update_channel'			=> self::getOption( 'update_channel', 'stable' ),

			'text_ebay_token'			=> self::getOption( 'ebay_token' ),
			'text_log_level'			=> self::getOption( 'log_level' ),

			'option_log_to_db'			=> self::getOption( 'log_to_db' ),
			'option_sandbox_enabled'	=> self::getOption( 'sandbox_enabled' ),

			'settings_url'				=> 'admin.php?page='.self::ParentMenuId.'-settings',
			'form_action'				=> 'admin.php?page='.self::ParentMenuId.'-settings'.'&tab=developer'
		);
		$this->display( 'settings_dev', $aData );
	}


	protected function saveSettings() {

		// TODO: check nonce
		if ( isset( $_POST['wpl_e2e_text_ebay_site_id'] ) ) {

			self::updateOption( 'ebay_site_id',		$this->getValueFromPost( 'text_ebay_site_id' ) );
			self::updateOption( 'paypal_email',		$this->getValueFromPost( 'text_paypal_email' ) );
			
			self::updateOption( 'cron_auctions',	$this->getValueFromPost( 'option_cron_auctions' ) );
			self::updateOption( 'cron_transactions',$this->getAnswerFromPost( 'option_cron_transactions' ) );
			self::updateOption( 'uninstall',		$this->getValueFromPost( 'option_uninstall' ) );

			$this->handleCronSettings( $this->getValueFromPost( 'option_cron_auctions' ) );
			$this->showMessage( __('Settings saved.','wplister') );
		}
	}


	protected function saveCategoriesSettings() {

		// TODO: check nonce
		if ( isset( $_POST['wpl_e2e_ebay_category_id'] ) ) {

			// save ebay categories mapping
			self::updateOption( 'categories_map_ebay',	$this->getValueFromPost( 'ebay_category_id' ) );

			// save store categories mapping
			self::updateOption( 'categories_map_store',	$this->getValueFromPost( 'store_category_id' ) );

			$this->showMessage( __('Categories mapping updated.','wplister') );
		}
	}
	
	
	protected function saveDeveloperSettings() {

		// TODO: check nonce
		if ( isset( $_POST['wpl_e2e_update_channel'] ) ) {

			// toggle sandbox ?
			$oldToken = self::getOption( 'ebay_token' );
			if ( self::getOption( 'sandbox_enabled' ) != $this->getValueFromPost( 'option_sandbox_enabled' ) ) {
				
				$sandbox_enabled = ($this->getValueFromPost( 'option_sandbox_enabled' ) == '1') ? true : false ;
				$tokens = self::getOption( 'ebay_tokens' );
				if (!$tokens) $tokens = array();
				
				if ( $sandbox_enabled ) {
					
					// backup token
					$tokens['production'] = array();
					$tokens['production']['mode'] = 'production';
					$tokens['production']['token'] = self::getOption( 'ebay_token' );
					self::updateOption( 'ebay_tokens', $tokens );
					
					// restore sandbox token
					if ( isset($tokens['sandbox']) ) {
						self::updateOption( 'ebay_token', $tokens['sandbox']['token'] );
						$this->showMessage( "Enabled sandbox mode. Your token was restored." );
					} else {
						$this->showMessage( "Enabled sandbox mode." );
					}

				} else {
					
					// backup token
					$tokens['sandbox'] = array();
					$tokens['sandbox']['mode'] = 'sandbox';
					$tokens['sandbox']['token'] = self::getOption( 'ebay_token' );
					self::updateOption( 'ebay_tokens', $tokens );
					
					// restore production token
					if ( isset($tokens['production']) ) {
						self::updateOption( 'ebay_token', $tokens['production']['token'] );
						$this->showMessage( "Switched to production mode. Your token was restored." );
					} else {
						$this->showMessage( "Switched to production mode." );
					}

				}
			}

			// new manual token ?
			if ( $oldToken != $this->getValueFromPost( 'text_ebay_token' ) ) {
				self::updateOption( 'ebay_token', $this->getValueFromPost( 'text_ebay_token' ) );
				$this->showMessage( __('Your token was changed.','wplister') );
			}


			self::updateOption( 'log_level',		$this->getValueFromPost( 'text_log_level' ) );
			self::updateOption( 'log_to_db',		$this->getValueFromPost( 'option_log_to_db' ) );
			self::updateOption( 'sandbox_enabled',	$this->getValueFromPost( 'option_sandbox_enabled' ) );


			$this->showMessage( __('Settings updated.','wplister') );

		}
	}
	
	protected function loadProductCategories() {
	global $wpdb;

		$tree = get_terms( ProductWrapper::getTaxonomy(), 'orderby=count&hide_empty=0' );

		$result = $this->parseTree( $tree );
		$flatlist = $this->printTree( $result );
		// echo "<pre>";print_r($flatlist);echo "</pre>";

		return $flatlist;
	}

	// parses wp terms array into a hierarchical tree structure
	function parseTree( $tree, $root = 0 ) {
		$return = array();

		// Traverse the tree and search for direct children of the root
		foreach ( $tree as $key => $item ) {

			// A direct child item is found
			if ( $item->parent == $root ) {

				// Remove item from tree (we don't need to traverse this again)
				unset( $tree[ $key ] );
				
				// Append the item into result array and parse its children
				$item->children = $this->parseTree( $tree, $item->term_id );
				$return[] = $item;

			}
		}
		return empty( $return ) ? null : $return;
	}

	function printTree( $tree, $depth = 0, $result = array() ) {
		$categories_map_ebay  = self::getOption( 'categories_map_ebay'  );
		$categories_map_store = self::getOption( 'categories_map_store' );
	    if( ($tree != 0) && (count($tree) > 0) ) {
	        foreach($tree as $node) {
	        	// indent category name accourding to depth
	            $node->name = str_repeat('&ndash; ' , $depth) . $node->name;
	            // echo $node->name;
	            
	            // get ebay category and (full) name
	            $ebay_category_id  = @$categories_map_ebay[$node->term_id];
	            $store_category_id = @$categories_map_store[$node->term_id];

	            // add item to results - excluding children
	            $tmpnode = array(
					'term_id'             => $node->term_id,
					'parent'              => $node->parent,
					'category'            => $node->name,
					'ebay_category_id'    => $ebay_category_id,
					'ebay_category_name'  => EbayCategoriesModel::getFullEbayCategoryName( $ebay_category_id ),
					'store_category_id'   => $store_category_id,
					'store_category_name' => EbayCategoriesModel::getFullStoreCategoryName( $store_category_id ),
					'description'         => $node->description
	            );

	            $result[] = $tmpnode;
	            $result = $this->printTree( $node->children, $depth+1, $result );
	        }
	    }
	    return $result;
	}


	protected function handleCronSettings( $schedule ) {
        $this->logger->info("handleCronSettings( $schedule )");

        // remove scheduled event
	    $timestamp = wp_next_scheduled( 'e2e_update_auctions' );
    	wp_unschedule_event($timestamp, 'e2e_update_auctions' );
	    $timestamp = wp_next_scheduled( 'wplister_update_auctions' );
    	wp_unschedule_event($timestamp, 'wplister_update_auctions' );

		if ( !wp_next_scheduled( 'wplister_update_auctions' ) ) {
			wp_schedule_event(time(), $schedule, 'wplister_update_auctions');
		}
        
	}


	public function onWpPrintStyles() {

		// jqueryFileTree
		wp_register_style('jqueryFileTree_style', self::$PLUGIN_URL.'/js/jqueryFileTree/jqueryFileTree.css' );
		wp_enqueue_style('jqueryFileTree_style'); 

	}

	public function onWpEnqueueScripts() {

		// jqueryFileTree
		wp_register_script( 'jqueryFileTree', self::$PLUGIN_URL.'/js/jqueryFileTree/jqueryFileTree.js', array( 'jquery' ) );
		wp_enqueue_script( 'jqueryFileTree' );

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

}
