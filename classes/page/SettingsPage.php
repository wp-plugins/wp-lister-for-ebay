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
		add_screen_options_panel('wplister_setting_options', '', array( &$this, 'renderSettingsOptions'), $this->main_admin_menu_slug.'_page_wplister-settings' );

		// Add custom screen options
		$load_action = "load-".$this->main_admin_menu_slug."_page_wplister-".self::slug;
		add_action( $load_action, array( &$this, 'addScreenOptions' ) );

		// network admin page
		add_action( 'network_admin_menu', array( &$this, 'onWpAdminMenu' ) ); 

	}

	public function onWpAdminMenu() {
		parent::onWpAdminMenu();

		add_submenu_page( self::ParentMenuId, $this->getSubmenuPageTitle( 'Settings' ), __('Settings','wplister'), 
						  'manage_ebay_options', $this->getSubmenuId( 'settings' ), array( &$this, 'onDisplaySettingsPage' ) );
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

		// save advanced settings
		if ( $this->requestAction() == 'save_wplister_advanced_settings' ) {
			$this->saveAdvancedSettings();
		}

		// save category map
		if ( $this->requestAction() == 'save_wplister_categories_map' ) {
			$this->saveCategoriesSettings();
		}

		// import category map
		if ( $this->requestAction() == 'wplister_import_categories_map' ) {
			$this->handleImportCategoriesMap();
		}

		// export category map
		if ( $this->requestAction() == 'wplister_export_categories_map' ) {
			$this->handleExportCategoriesMap();
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

        $default_tab = is_network_admin() ? 'license' : 'settings';
        $active_tab = isset( $_GET[ 'tab' ] ) ? $_GET[ 'tab' ] : $default_tab;
        if ( 'categories' == $active_tab ) return $this->displayCategoriesPage();
        if ( 'developer'  == $active_tab ) return $this->displayDeveloperPage();
        if ( 'advanced'   == $active_tab ) return $this->displayAdvancedSettingsPage();
	
		// action remove_token
		if ( $this->requestAction() == 'remove_token' ) {
			check_admin_referer('remove_token');

			// remove_token
			self::updateOption('ebay_token','');
			self::updateOption('ebay_token_expirationtime','');
			self::updateOption('ebay_token_userid','');
			self::updateOption('ebay_user','');

			// check if we have a token
			if ( self::getOption('ebay_token') == '' ) {
				$this->showMessage( "Please link WP-Lister to your eBay account." );
			}

			WPL_Setup::checkSetup('settings');
		}

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
			'ebay_token_userid'			=> self::getOption( 'ebay_token_userid' ),
			'ebay_user'					=> self::getOption( 'ebay_user' ),

			'option_cron_auctions'		=> self::getOption( 'cron_auctions' ),
			'option_enable_ebay_motors'	=> self::getOption( 'enable_ebay_motors' ),
			'option_ebay_update_mode'	=> self::getOption( 'ebay_update_mode', 'order' ),
	
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

	    $default_category_id = self::getOption('default_ebay_category_id');
	    $default_category_name = EbayCategoriesModel::getFullEbayCategoryName( $default_category_id );
	    if ( ! $default_category_name ) $default_category_name = 'None';

		$aData = array(
			'plugin_url'				=> self::$PLUGIN_URL,
			'message'					=> $this->message,

			'shop_categories'			=> $shop_categories,
			'categoriesMapTable'		=> $categoriesMapTable,
			'default_category_id'		=> $default_category_id,
			'default_category_name'		=> $default_category_name,

			'settings_url'				=> 'admin.php?page='.self::ParentMenuId.'-settings',
			'form_action'				=> 'admin.php?page='.self::ParentMenuId.'-settings'.'&tab=categories'
		);
		$this->display( 'settings_categories', $aData );
	}


	public function displayAdvancedSettingsPage() {

        $wp_roles = new WP_Roles();
        // echo "<pre>";print_r($wp_roles);echo"</pre>";#die();

		$aData = array(
			'plugin_url'				=> self::$PLUGIN_URL,
			'message'					=> $this->message,

			'process_shortcodes'		=> self::getOption( 'process_shortcodes', 'content' ),
			'remove_links'				=> self::getOption( 'remove_links', 'default' ),
			'default_image_size'		=> self::getOption( 'default_image_size', 'full' ),
			'wc2_gallery_fallback'		=> self::getOption( 'wc2_gallery_fallback', 'attached' ),
			'hide_dupe_msg'				=> self::getOption( 'hide_dupe_msg' ),
			'option_uninstall'			=> self::getOption( 'uninstall' ),
			'option_foreign_transactions' => self::getOption( 'foreign_transactions' ),
			'option_allow_backorders'   => self::getOption( 'allow_backorders', 0 ),
			'option_preview_in_new_tab' => self::getOption( 'preview_in_new_tab', 0 ),
			'option_disable_wysiwyg_editor' => self::getOption( 'disable_wysiwyg_editor', 0 ),
			'enable_item_compat_tab' 	=> self::getOption( 'enable_item_compat_tab', 1 ),
			'option_local_timezone' 	=> self::getOption( 'local_timezone', '' ),
			'text_admin_menu_label' 	=> self::getOption( 'admin_menu_label', 'WP-Lister' ),
			'timezones' 				=> self::get_timezones(),
            'available_roles'           => $wp_roles->role_names,
            'wp_roles'         			=> $wp_roles->roles,

			'settings_url'				=> 'admin.php?page='.self::ParentMenuId.'-settings',
			'form_action'				=> 'admin.php?page='.self::ParentMenuId.'-settings'.'&tab=advanced'
		);
		$this->display( 'settings_advanced', $aData );
	}

	public function displayDeveloperPage() {

		$aData = array(
			'plugin_url'				=> self::$PLUGIN_URL,
			'message'					=> $this->message,

			'update_channel'			=> self::getOption( 'update_channel', 'stable' ),
			'ajax_error_handling'		=> self::getOption( 'ajax_error_handling', 'halt' ),
			'php_error_handling'		=> self::getOption( 'php_error_handling', 0 ),
			'disable_variations'		=> self::getOption( 'disable_variations', 0 ),
			'enable_messages_page'		=> self::getOption( 'enable_messages_page', 0 ),
			'log_record_limit'			=> self::getOption( 'log_record_limit', 4096 ),
			'xml_formatter'				=> self::getOption( 'xml_formatter', 'default' ),

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

			// reminder to update categories when site id changes
			$changed_site_id = false;
			$old_ebay_site_id = self::getOption( 'ebay_site_id' );
			if ( $old_ebay_site_id != $this->getValueFromPost( 'text_ebay_site_id' ) ) {
				// $msg  = __('You switched to a different eBay site. Please make sure that you update eBay details on the Tools page.','wplister');
				$msg  = '<p>';
				$msg .= __('You switched to a different eBay site.','wplister') . ' ';
				$msg .= __('Please update site specific eBay details like categories, shipping services and payment options.','wplister');
				$msg .= '&nbsp;&nbsp;';
				$msg .= '<a id="btn_update_ebay_data" class="button wpl_job_button">' . __('Update eBay data','wplister') . '</a>';
				$msg .= '</p>';
				$this->showMessage( $msg );
				$changed_site_id = true;
			}

			self::updateOption( 'ebay_site_id',			$this->getValueFromPost( 'text_ebay_site_id' ) );
			self::updateOption( 'paypal_email',			$this->getValueFromPost( 'text_paypal_email' ) );
			
			self::updateOption( 'cron_auctions',		$this->getValueFromPost( 'option_cron_auctions' ) );
			self::updateOption( 'enable_ebay_motors', 	$this->getValueFromPost( 'option_enable_ebay_motors' ) );
			self::updateOption( 'ebay_update_mode', 	$this->getValueFromPost( 'option_ebay_update_mode' ) );

			$this->handleCronSettings( $this->getValueFromPost( 'option_cron_auctions' ) );
			if ( ! $changed_site_id ) $this->showMessage( __('Settings saved.','wplister') );
		}
	}

	protected function saveAdvancedSettings() {

		// TODO: check nonce
		if ( isset( $_POST['wpl_e2e_process_shortcodes'] ) ) {

        	$wp_roles = new WP_Roles();
        	$available_roles = $wp_roles->role_names;

        	// echo "<pre>";print_r($wp_roles);echo"</pre>";die();

			$wpl_caps = array(
				'manage_ebay_listings'  => 'Manage Listings',
				'manage_ebay_options'   => 'Manage Settings',
				'prepare_ebay_listings' => 'Prepare Listings',
				'publish_ebay_listings' => 'Publish Listings',
			);

			// echo "<pre>";print_r($_POST['wpl_permissions']);echo"</pre>";die();
			$permissions = $_POST['wpl_permissions'];

			foreach ( $available_roles as $role => $role_name ) {

				// admin permissions can't be modified
				if ( $role == 'administrator' ) continue;

				// get the the role object
				$role_object = get_role( $role );

				foreach ( $wpl_caps as $capability_name => $capability_title ) {

					if ( isset( $permissions[ $role ][ $capability_name ] ) ) {

						// add capability to this role
						$role_object->add_cap( $capability_name );

					} else {

						// remove capability from this role
						$role_object->remove_cap( $capability_name );

					}
				
				}

			}

			self::updateOption( 'process_shortcodes', 	$this->getValueFromPost( 'process_shortcodes' ) );
			self::updateOption( 'remove_links',     	$this->getValueFromPost( 'remove_links' ) );
			self::updateOption( 'default_image_size',   $this->getValueFromPost( 'default_image_size' ) );
			self::updateOption( 'wc2_gallery_fallback', $this->getValueFromPost( 'wc2_gallery_fallback' ) );
			self::updateOption( 'hide_dupe_msg',    	$this->getValueFromPost( 'hide_dupe_msg' ) );
			self::updateOption( 'uninstall',			$this->getValueFromPost( 'option_uninstall' ) );
			self::updateOption( 'foreign_transactions',	$this->getValueFromPost( 'option_foreign_transactions' ) );
			self::updateOption( 'preview_in_new_tab',	$this->getValueFromPost( 'option_preview_in_new_tab' ) );
			self::updateOption( 'disable_wysiwyg_editor',	$this->getValueFromPost( 'option_disable_wysiwyg_editor' ) );
			self::updateOption( 'enable_item_compat_tab', 	$this->getValueFromPost( 'enable_item_compat_tab' ) );
			self::updateOption( 'local_timezone',		$this->getValueFromPost( 'option_local_timezone' ) );
			self::updateOption( 'allow_backorders',		$this->getValueFromPost( 'option_allow_backorders' ) );
			self::updateOption( 'admin_menu_label',		$this->getValueFromPost( 'text_admin_menu_label' ) );

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

			// save default ebay category
			self::updateOption( 'default_ebay_category_id', $this->getValueFromPost( 'default_ebay_category_id' ) );

			$this->showMessage( __('Categories mapping updated.','wplister') );
		}
	}
	
	
	protected function saveDeveloperSettings() {

		// TODO: check nonce
		if ( isset( $_POST['wpl_e2e_option_sandbox_enabled'] ) ) {

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
					if ( isset($tokens['sandbox']) && $tokens['sandbox']['token'] ) {
						self::updateOption( 'ebay_token', $tokens['sandbox']['token'] );
						self::updateOption( 'sandbox_enabled',	$this->getValueFromPost( 'option_sandbox_enabled' ) );
						$this->initEC();
						$UserID = $this->EC->GetUser();
						$this->EC->GetUserPreferences();
						$this->EC->closeEbay();
						$this->showMessage( __('Enabled sandbox mode.','wplister') . ' ' .
											sprintf( "Your token for %s was restored.", $UserID ) );
					} else {
						$this->showMessage( __('Enabled sandbox mode.','wplister') );
					}

				} else {
					
					// backup token
					$tokens['sandbox'] = array();
					$tokens['sandbox']['mode'] = 'sandbox';
					$tokens['sandbox']['token'] = self::getOption( 'ebay_token' );
					self::updateOption( 'ebay_tokens', $tokens );
					
					// restore production token
					if ( isset($tokens['production']) && $tokens['production']['token'] ) {
						self::updateOption( 'ebay_token', $tokens['production']['token'] );
						self::updateOption( 'sandbox_enabled',	$this->getValueFromPost( 'option_sandbox_enabled' ) );
						$this->initEC();
						$UserID = $this->EC->GetUser();
						$this->EC->GetUserPreferences();
						$this->EC->closeEbay();
						$this->showMessage( __('Switched to production mode.','wplister') . ' ' .
											sprintf( "Your token for %s was restored.", $UserID ) );
					} else {
						$this->showMessage( __('Switched to production mode.','wplister') );
					}

				}
			}

			self::updateOption( 'log_level',			$this->getValueFromPost( 'text_log_level' ) );
			self::updateOption( 'log_to_db',			$this->getValueFromPost( 'option_log_to_db' ) );
			self::updateOption( 'sandbox_enabled',		$this->getValueFromPost( 'option_sandbox_enabled' ) );
			self::updateOption( 'ajax_error_handling',	$this->getValueFromPost( 'ajax_error_handling' ) );
			self::updateOption( 'php_error_handling',	$this->getValueFromPost( 'php_error_handling' ) );
			self::updateOption( 'disable_variations',	$this->getValueFromPost( 'disable_variations' ) );
			self::updateOption( 'enable_messages_page',	$this->getValueFromPost( 'enable_messages_page' ) );
			self::updateOption( 'log_record_limit',		$this->getValueFromPost( 'log_record_limit' ) );
			self::updateOption( 'xml_formatter',		$this->getValueFromPost( 'xml_formatter' ) );

			$this->handleChangedUpdateChannel();

			// new manual token ?
			if ( $oldToken != $this->getValueFromPost( 'text_ebay_token' ) ) {
				self::updateOption( 'ebay_token', $this->getValueFromPost( 'text_ebay_token' ) );
				$this->initEC();
				$UserID = $this->EC->GetUser();
				$this->EC->GetUserPreferences();
				$this->EC->closeEbay();
				$this->showMessage( __('Your token was changed.','wplister') );
				$this->showMessage( __('Your UserID is','wplister') . ' ' . $UserID );
			}

			$this->showMessage( __('Settings updated.','wplister') );

		}
	}
	
	protected function handleChangedUpdateChannel() {


	}
	
	protected function loadProductCategories() {
	global $wpdb;

		$flatlist = array();
		$tree = get_terms( ProductWrapper::getTaxonomy(), 'orderby=count&hide_empty=0' );

		if ( ! is_wp_error($tree) ) {
			$result = $this->parseTree( $tree );
			$flatlist = $this->printTree( $result );
			// echo "<pre>";print_r($flatlist);echo "</pre>";		
		}

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




    // export rulesets as csv
    protected function handleImportCategoriesMap() {

        $uploaded_file = $this->process_upload();
        if (!$uploaded_file) return;

        // handle JSON export
        $json = file_get_contents($uploaded_file);
        $data = json_decode($json, true);
        // echo "<pre>";print_r($data);echo"</pre>";#die();

        if ( is_array($data) && ( sizeof($data) == 3 ) ) {

			// save categories mapping
			self::updateOption( 'categories_map_ebay',		$data['categories_map_ebay'] );
			self::updateOption( 'categories_map_store',		$data['categories_map_store'] );
			self::updateOption( 'default_ebay_category_id', $data['default_ebay_category_id'] );

			// show result
            $count_ebay  = sizeof($data['categories_map_ebay']);
            $count_store = sizeof($data['categories_map_store']);
            $this->showMessage( $count_ebay . ' ebay categories and '.$count_store.' store categories were imported.');

        } else {
            $this->showMessage( 'The uploaded file could not be imported. Please make sure you use a JSON backup file exported from this plugin.');                
        }

    }

    // export rulesets as csv
    protected function handleExportCategoriesMap() {

    	// get data
        $data = array();
		$data['categories_map_ebay']  		= self::getOption( 'categories_map_ebay'  );
		$data['categories_map_store'] 		= self::getOption( 'categories_map_store' );
		$data['default_ebay_category_id'] 	= self::getOption( 'default_ebay_category_id' );

        // send JSON file
        header("Content-Disposition: attachment; filename=wplister_categories.json");
        echo json_encode( $data );
        exit;

    }


    /**
     * process file upload
     **/
    public function process_upload() {

        $this->target_path = WP_CONTENT_DIR.'/uploads/wplister_categories.json';

        if(isset($_FILES['wpl_file_upload'])) {

            $target_path = $this->target_path;
            //echo "<br />Target Path: ".$target_path;

            // delete last import
            if ( file_exists($target_path) ) unlink($target_path);

            // echo '<div id="message" class="X-updated X-fade"><p>';
            if(move_uploaded_file($_FILES['wpl_file_upload']['tmp_name'], $target_path))
            {
                // echo "The file ".  basename( $_FILES['wpl_file_upload']['name'])." has been uploaded";               
                // $file_name = WP_CSV_TO_DB_URL.'/uploads/'.basename( $_FILES['wpl_file_upload']['name']);
                // update_option('wp_csvtodb_input_file_url', $file_name);
                // return true;
                return $target_path;
            } 
            else
            {
                echo "There was an error uploading the file, please try again!";
            }
            // echo '</p></div>';
            return false;
        }
        echo "no file_upload set";
        return false;
    }


    function get_timezones() {

		// create an array listing the time zones
		// http://www.ultramegatech.com/2009/04/working-with-time-zones-in-php/
		$zonelist = array(
			'America/Anchorage'              => '(GMT-09:00) Alaska',
			'America/Los_Angeles'            => '(GMT-08:00) Pacific Time (US &amp; Canada)',
			'America/Tijuana'                => '(GMT-08:00) Tijuana, Baja California',
			'America/Denver'                 => '(GMT-07:00) Mountain Time (US &amp; Canada)',
			'America/Chihuahua'              => '(GMT-07:00) Chihuahua',
			'America/Mazatlan'               => '(GMT-07:00) Mazatlan',
			'America/Phoenix'                => '(GMT-07:00) Arizona',
			'America/Regina'                 => '(GMT-06:00) Saskatchewan',
			'America/Tegucigalpa'            => '(GMT-06:00) Central America',
			'America/Chicago'                => '(GMT-06:00) Central Time (US &amp; Canada)',
			'America/Mexico_City'            => '(GMT-06:00) Mexico City',
			'America/Monterrey'              => '(GMT-06:00) Monterrey',
			'America/New_York'               => '(GMT-05:00) Eastern Time (US &amp; Canada)',
			'America/Bogota'                 => '(GMT-05:00) Bogota',
			'America/Lima'                   => '(GMT-05:00) Lima',
			'America/Rio_Branco'             => '(GMT-05:00) Rio Branco',
			'America/Indiana/Indianapolis'   => '(GMT-05:00) Indiana (East)',
			'America/Caracas'                => '(GMT-04:30) Caracas',
			'America/Halifax'                => '(GMT-04:00) Atlantic Time (Canada)',
			'America/Manaus'                 => '(GMT-04:00) Manaus',
			'America/Santiago'               => '(GMT-04:00) Santiago',
			'America/La_Paz'                 => '(GMT-04:00) La Paz',
			'America/St_Johns'               => '(GMT-03:30) Newfoundland',
			'America/Argentina/Buenos_Aires' => '(GMT-03:00) Georgetown',
			'America/Sao_Paulo'              => '(GMT-03:00) Brasilia',
			'America/Godthab'                => '(GMT-03:00) Greenland',
			'America/Montevideo'             => '(GMT-03:00) Montevideo',
			'Atlantic/South_Georgia'         => '(GMT-02:00) Mid-Atlantic',
			'Atlantic/Azores'                => '(GMT-01:00) Azores',
			'Atlantic/Cape_Verde'            => '(GMT-01:00) Cape Verde Is.',
			'Europe/Dublin'                  => '(GMT) Dublin',
			'Europe/Lisbon'                  => '(GMT) Lisbon',
			'Europe/London'                  => '(GMT) London',
			'Africa/Monrovia'                => '(GMT) Monrovia',
			'Atlantic/Reykjavik'             => '(GMT) Reykjavik',
			'Africa/Casablanca'              => '(GMT) Casablanca',
			'Europe/Belgrade'                => '(GMT+01:00) Belgrade',
			'Europe/Bratislava'              => '(GMT+01:00) Bratislava',
			'Europe/Budapest'                => '(GMT+01:00) Budapest',
			'Europe/Ljubljana'               => '(GMT+01:00) Ljubljana',
			'Europe/Prague'                  => '(GMT+01:00) Prague',
			'Europe/Sarajevo'                => '(GMT+01:00) Sarajevo',
			'Europe/Skopje'                  => '(GMT+01:00) Skopje',
			'Europe/Warsaw'                  => '(GMT+01:00) Warsaw',
			'Europe/Zagreb'                  => '(GMT+01:00) Zagreb',
			'Europe/Brussels'                => '(GMT+01:00) Brussels',
			'Europe/Copenhagen'              => '(GMT+01:00) Copenhagen',
			'Europe/Madrid'                  => '(GMT+01:00) Madrid',
			'Europe/Paris'                   => '(GMT+01:00) Paris',
			'Africa/Algiers'                 => '(GMT+01:00) West Central Africa',
			'Europe/Amsterdam'               => '(GMT+01:00) Amsterdam',
			'Europe/Berlin'                  => '(GMT+01:00) Berlin',
			'Europe/Rome'                    => '(GMT+01:00) Rome',
			'Europe/Stockholm'               => '(GMT+01:00) Stockholm',
			'Europe/Vienna'                  => '(GMT+01:00) Vienna',
			'Europe/Minsk'                   => '(GMT+02:00) Minsk',
			'Africa/Cairo'                   => '(GMT+02:00) Cairo',
			'Europe/Helsinki'                => '(GMT+02:00) Helsinki',
			'Europe/Riga'                    => '(GMT+02:00) Riga',
			'Europe/Sofia'                   => '(GMT+02:00) Sofia',
			'Europe/Tallinn'                 => '(GMT+02:00) Tallinn',
			'Europe/Vilnius'                 => '(GMT+02:00) Vilnius',
			'Europe/Athens'                  => '(GMT+02:00) Athens',
			'Europe/Bucharest'               => '(GMT+02:00) Bucharest',
			'Europe/Istanbul'                => '(GMT+02:00) Istanbul',
			'Asia/Jerusalem'                 => '(GMT+02:00) Jerusalem',
			'Asia/Amman'                     => '(GMT+02:00) Amman',
			'Asia/Beirut'                    => '(GMT+02:00) Beirut',
			'Africa/Windhoek'                => '(GMT+02:00) Windhoek',
			'Africa/Harare'                  => '(GMT+02:00) Harare',
			'Asia/Kuwait'                    => '(GMT+03:00) Kuwait',
			'Asia/Riyadh'                    => '(GMT+03:00) Riyadh',
			'Asia/Baghdad'                   => '(GMT+03:00) Baghdad',
			'Africa/Nairobi'                 => '(GMT+03:00) Nairobi',
			'Asia/Tbilisi'                   => '(GMT+03:00) Tbilisi',
			'Europe/Moscow'                  => '(GMT+03:00) Moscow',
			'Europe/Volgograd'               => '(GMT+03:00) Volgograd',
			'Asia/Tehran'                    => '(GMT+03:30) Tehran',
			'Asia/Muscat'                    => '(GMT+04:00) Muscat',
			'Asia/Baku'                      => '(GMT+04:00) Baku',
			'Asia/Yerevan'                   => '(GMT+04:00) Yerevan',
			'Asia/Yekaterinburg'             => '(GMT+05:00) Ekaterinburg',
			'Asia/Karachi'                   => '(GMT+05:00) Karachi',
			'Asia/Tashkent'                  => '(GMT+05:00) Tashkent',
			'Asia/Kolkata'                   => '(GMT+05:30) Calcutta',
			'Asia/Colombo'                   => '(GMT+05:30) Sri Jayawardenepura',
			'Asia/Katmandu'                  => '(GMT+05:45) Kathmandu',
			'Asia/Dhaka'                     => '(GMT+06:00) Dhaka',
			'Asia/Almaty'                    => '(GMT+06:00) Almaty',
			'Asia/Novosibirsk'               => '(GMT+06:00) Novosibirsk',
			'Asia/Rangoon'                   => '(GMT+06:30) Yangon (Rangoon)',
			'Asia/Krasnoyarsk'               => '(GMT+07:00) Krasnoyarsk',
			'Asia/Bangkok'                   => '(GMT+07:00) Bangkok',
			'Asia/Jakarta'                   => '(GMT+07:00) Jakarta',
			'Asia/Brunei'                    => '(GMT+08:00) Beijing',
			'Asia/Chongqing'                 => '(GMT+08:00) Chongqing',
			'Asia/Hong_Kong'                 => '(GMT+08:00) Hong Kong',
			'Asia/Urumqi'                    => '(GMT+08:00) Urumqi',
			'Asia/Irkutsk'                   => '(GMT+08:00) Irkutsk',
			'Asia/Ulaanbaatar'               => '(GMT+08:00) Ulaan Bataar',
			'Asia/Kuala_Lumpur'              => '(GMT+08:00) Kuala Lumpur',
			'Asia/Singapore'                 => '(GMT+08:00) Singapore',
			'Asia/Taipei'                    => '(GMT+08:00) Taipei',
			'Australia/Perth'                => '(GMT+08:00) Perth',
			'Asia/Seoul'                     => '(GMT+09:00) Seoul',
			'Asia/Tokyo'                     => '(GMT+09:00) Tokyo',
			'Asia/Yakutsk'                   => '(GMT+09:00) Yakutsk',
			'Australia/Darwin'               => '(GMT+09:30) Darwin',
			'Australia/Adelaide'             => '(GMT+09:30) Adelaide',
			'Australia/Canberra'             => '(GMT+10:00) Canberra',
			'Australia/Melbourne'            => '(GMT+10:00) Melbourne',
			'Australia/Sydney'               => '(GMT+10:00) Sydney',
			'Australia/Brisbane'             => '(GMT+10:00) Brisbane',
			'Australia/Hobart'               => '(GMT+10:00) Hobart',
			'Asia/Vladivostok'               => '(GMT+10:00) Vladivostok',
			'Pacific/Guam'                   => '(GMT+10:00) Guam',
			'Pacific/Port_Moresby'           => '(GMT+10:00) Port Moresby',
			'Asia/Magadan'                   => '(GMT+11:00) Magadan',
			'Pacific/Fiji'                   => '(GMT+12:00) Fiji',
			'Asia/Kamchatka'                 => '(GMT+12:00) Kamchatka',
			'Pacific/Auckland'               => '(GMT+12:00) Auckland',
			'Pacific/Tongatapu'              => '(GMT+13:00) Nukualofa',
			'Kwajalein'                      => '(GMT-12:00) International Date Line West',
			'Pacific/Midway'                 => '(GMT-11:00) Midway Island',
			'Pacific/Samoa'                  => '(GMT-11:00) Samoa',
			'Pacific/Honolulu'               => '(GMT-10:00) Hawaii'
		);
		
		return $zonelist;
	}










	protected function handleCronSettings( $schedule ) {
        $this->logger->info("handleCronSettings( $schedule )");

        // remove scheduled event
	    $timestamp = wp_next_scheduled(  'wplister_update_auctions' );
    	wp_unschedule_event( $timestamp, 'wplister_update_auctions' );

		if ( !wp_next_scheduled( 'wplister_update_auctions' ) ) {
			wp_schedule_event( time(), $schedule, 'wplister_update_auctions' );
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
