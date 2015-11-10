<?php

class WPL_Setup extends WPL_Core {
	
	// check if setup is incomplete and display next step
	public function checkSetup( $page = false ) {
		global $pagenow;

		// check if safe mode is enabled
		$this->isPhpSafeMode();

		// check if incomatible plugins are active
		$this->checkPlugins();

		// check if a recent version of WooCommerce is installed
		$this->checkWooCommerce();

		// check if cURL is loaded
		if ( ! $this->isCurlLoaded() ) return false;

		// check for windows server
		// if ( $this->isWindowsServer() ) return false;
		$this->isWindowsServer( $page );

		// create folders if neccessary
		if ( $this->checkFolders() ) return false;

		// check for updates
		$this->checkForUpdates();

		// check if cron is working properly
		$this->checkCron();

		// check if any sites need to be refreshed
		$this->checkSites();

		// check database after migration
		// $this->checkDatabase();
		// $this->checkDbForInvalidAccounts();

		// check for multisite installation
		// if ( $this->checkMultisite() ) return false;

		$current_tab = isset( $_REQUEST['tab'] ) ? $_REQUEST['tab'] : false;

		// setup wizard
		// if ( self::getOption('ebay_token') == '' ) {
		if ( ( '1' == self::getOption('setup_next_step') ) && ( $current_tab != 'accounts') ) {
		
			$msg1 = __('You have not linked WP-Lister to your eBay account yet.','wplister');
			$msg2 = __('To complete the setup procedure go to %s and follow the instructions.','wplister');
			$accounts_page = get_option( 'wplister_enable_accounts_page' ) ? 'wplister-settings-accounts' : 'wplister-settings&tab=accounts';
			$link = sprintf( '<a href="admin.php?page=%s">%s</a>', $accounts_page, __('Account Settings','wplister') );
			$msg2 = sprintf($msg2, $link);
			$msg = "<b>$msg1</b></p><p>$msg2";
			wple_show_message($msg,'info');
		
		} elseif ( '2' == self::getOption('setup_next_step') ) {
		
			$title = __('Setup - Step 2','wplister');
			$msg1  = __('Before creating your first profile, we need to download certain information which are specific to the eBay site you selected.','wplister');
			$msg2  = __('This includes shipping options, payment methods, your custom store categories as well as the whole eBay category tree, which might take a while.','wplister');

			// old button
			// $button = '<a href="#" id="btn_update_ebay_data" onclick="return false;" class="button-primary">'.__('Update eBay details','wplister').'</a>';

			// new button - use site_id of default (first) account
			$account = WPLE()->accounts[ get_option('wplister_default_account_id') ];
	        $button = '<a href="#" data-site_id="'.$account->site_id.'" data-account_id="'.$account->id.'" class="btn_update_ebay_data_for_site button-primary">'.__('Refresh eBay details','wplister').'</a>';

			$msg   = "<p><b>$title</b></p><p>$msg1</p><p>$msg2</p>";
			$msg  .= $button;
			wple_show_message($msg,'info');

			// // remember when WP-Lister was connected to an eBay account for the first time
			// update_option( 'ignore_orders_before_ts', time() );
		
		} elseif ( '3' == self::getOption('setup_next_step') ) {
		
			$tm = new TemplatesModel();
			$templates = $tm->getAll();
			if ( sizeof($templates) > 0 ) {
				self::updateOption('setup_next_step', '4');
			} else {
				$title = __('Setup - Step 3','wplister');
				$msg1 = __('Create a default listing template.','wplister');
				$msg2 = __('To create your first listing template click on %s.','wplister').'<br>';
				if ( @$_GET['action'] == 'add_new_template' )
					$msg2 = __('Replace the default text according to your requirements and save your template to continue.','wplister');
				$link = '<a href="admin.php?page=wplister-templates&action=add_new_template">'.__('New Template', 'wplister').'</a>';
				$msg2 = sprintf($msg2, $link);
				$msg = "<p><b>$title</b></p><p><b>$msg1</b></p><p>$msg2</p>";
				wple_show_message($msg,'info');			
			}
		
		} elseif ( '4' == self::getOption('setup_next_step') ) {
		
			$pm = new ProfilesModel();
			$profiles = $pm->getAll();
			if ( sizeof($profiles) > 0 ) {
				self::updateOption('setup_next_step', '0');
			} else {
				$title = __('Setup - Step 4','wplister');
				$msg1  = __('The final step: create your first listing profile.', 'wplister');
				$msg2  = __('Click on %s and start defining your listing options.<br>After saving your profile, visit your Products page and select the products to list on eBay.','wplister');
				$link  = '<a href="admin.php?page=wplister-profiles&action=add_new_profile">'.__('New Profile', 'wplister').'</a>';
				$msg2  = sprintf($msg2, $link);
				$msg   = "<b>$msg1</b></p><p>$msg2";
				wple_show_message($msg,'info');
			}
		
		} elseif ( '5' == self::getOption('setup_next_step') ) {
		
			$title = __('Setup is complete.','wplister');
			$msg1  = __('You are ready now to list your first items.', 'wplister');
			$msg2  = __('Visit your Products page, select a few items and select "List on eBay" from the bulk actions menu.','wplister');
			$msg   = "<b>$msg1</b></p><p>$msg2";
			wple_show_message($msg,'info');
			update_option('wplister_setup_next_step', '0');
		
		}

		// db upgrade
		WPLE_UpgradeHelper::upgradeDB();

		// check token expiration date
		self::checkToken();

		// // fetch user details if not done yet
		// if ( ( self::getOption('ebay_token') != '' ) && ( ! self::getOption('ebay_user') ) ) {
		// 	$this->initEC();
		// 	$UserID = $this->EC->GetUser();
		// 	$this->EC->closeEbay();
		// }
		
		// // fetch user details if not done yet
		// if ( ( self::getOption('ebay_token') != '' ) && ( ! self::getOption('ebay_seller_profiles_enabled') ) ) {
		// 	$this->initEC();
		// 	$this->EC->GetUserPreferences();
		// 	$this->EC->closeEbay();
		// }

		// // fetch token expiration date if not done yet
		// if ( ( self::getOption('ebay_token') != '' ) && ( ! self::getOption('ebay_token_expirationtime') ) ) {
		// 	$this->initEC();
		// 	$expdate = $this->EC->GetTokenStatus();
		// 	$this->EC->closeEbay();
		// }
				
	}


	// update permissions
	public function updatePermissions() {

		$roles = array('administrator', 'shop_manager', 'super_admin');
		foreach ($roles as $role) {
			$role =& get_role($role);
			if ( empty($role) )
				continue;
	 
			$role->add_cap('manage_ebay_listings');
			$role->add_cap('manage_ebay_options');
			$role->add_cap('prepare_ebay_listings');
			$role->add_cap('publish_ebay_listings');

		}

	}


	// check if cURL is loaded
	public function isCurlLoaded() {

		if( ! extension_loaded('curl') ) {
			wple_show_message("
				<b>Required PHP extension missing</b><br>
				<br>
				Your server doesn't seem to have the <a href='http://www.php.net/curl' target='_blank'>cURL</a> php extension installed.<br>
				cURL ist required by WP-Lister to be able to talk with eBay.<br>
				<br>
				On a recent debian based linux server running PHP 5 this should do the trick:<br>
				<br>
				<code>
					apt-get install php5-curl <br>
					/etc/init.d/apache2 restart
				</code>
				<br>
				<br>
				You'll require root access on your server to install additional php extensions!<br>
				If you are on a shared host, you need to ask your hoster to enable the cURL php extension for you.<br>
				<br>
				For more information on how to install the cURL php extension on other servers check <a href='http://stackoverflow.com/questions/1347146/how-to-enable-curl-in-php' target='_blank'>this page on stackoverflow</a>.
			",'error');
			return false;
		}

		return true;
	}

	// check server is running windows
	public function isWindowsServer( $page ) {

		if ( $page != 'settings' ) return;
		if ( defined('WPLE_EXPERIMENTAL_WIN_SUPPORT') ) return;

		if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {

			wple_show_message("
				<b>Warning: Server requirements not met - this server runs on windows.</b><br>
				<br>
				WP-Lister currently only supports unixoid operating systems like Linux, FreeBSD and OS X.<br>
				Support for windows servers is still experimental and should not be used on production sites!
			",'warn');
			return true;
		}

		return false;
	}

	// check if PHP safe_mode is enabled
	public function isPhpSafeMode() {

        if ( ini_get('safe_mode') ) {

			wple_show_message("
				<b>Warning: PHP safe mode is enabled.</b><br>
				<br>
				Your server seems to have PHP safe mode enabled, which can cause unexpected behaviour or prevent WP-Lister from working properly.<br>
				PHP safe mode has been deprecated for years and will be completely removed in the next PHP version - so it is highly recommended to disable it or ask your hoster to do it for you.
			",'warn');
			return true;
		}

		return false;
	}

	// check WP-Lister version
	static public function isV2() {
		return version_compare( WPLISTER_VERSION, '2.0', '>=');
	}


	// checks for incompatible plugins
	public function checkPlugins() {

		// Plugin Name: iThemes Slideshow
		// Plugin URI: http://ithemes.com/purchase/displaybuddy/
		// Version: 2.0.23
		if ( class_exists('pluginbuddy_slideshow') ) {

			wple_show_message("
				<b>Warning: An incompatible plugin was found.</b><br>
				<br>
				You seem to have the <i>iThemes Slideshow</i> plugin installed, which is known to cause issues with WP-Lister.<br>
				Version 2.0.23 of this plugin will slow down loading the listings page if you are using variations. This can render the entire listings page inaccessible, so please deactivate this plugin.
			",'warn');
			return false;

		}

		// Plugin Name: Yet Another Stars Rating
		// Plugin URI: http://wordpress.org/plugins/yet-another-stars-rating/
		// Version: 0.8.2
		if ( defined('YASR_VERSION_NUM') ) {

			wple_show_message("
				<b>Warning: An incompatible plugin was found.</b><br>
				<br>
				You seem to have the <i>Yet Another Stars Rating</i> plugin installed, which is known to cause issues with WP-Lister.<br>
				Version 0.8.2 of this plugin can break the process of preparing new eBay listings, so please deactivate this plugin if you experience any issues when applying a listing profile.
			",'warn');
			return false;

		}

		// Plugin Name: Booki
		// Plugin URI: http://codecanyon.net/item/booki-a-booking-plugin-for-wordpress/7460830
		// Version: 2.6
		if ( defined('BOOKI_VERSION') ) {

			wple_show_message("
				<b>Warning: An incompatible plugin was found.</b><br>
				<br>
				You seem to have the <i>Booki</i> plugin installed, which is known to cause issues with WP-Lister.<br>
				Version 2.6 of this plugin breaks WP-Lister's ability to talk to the eBay API by loading the PayPal SDK libraries on every page load. You need to deactivate this plugin in order to use WP-Lister for eBay.
			",'warn');
			return false;

		}

		// Plugin Name: WooTabs
		// Plugin URI: http://codecanyon.net/item/wootabsadd-extra-tabs-to-woocommerce-product-page/7891253
		// Version: 2.1.3
		if ( class_exists('Woocommerce_Wootabs_Admin') ) {

			wple_show_message("
				<b>Warning: An incompatible plugin was found.</b><br>
				<br>
				You seem to have the <i>WooTabs</i> plugin installed, which is known to cause issues with WP-Lister.<br>
				Version 2.1.3 of this plugin breaks interaction on the edit product page by loading a custom version of the jQuery UI framework instead of using the version included in WordPress.<br>
				<br>
				In order to use WP-Lister, you need to deactivate this plugin and use another tabs plugin - like the <i>WooCommerce Tab Manager</i> extension by WooThemes.
			",'warn');
			return false;

		}

	}

	// check if a recent version of WooCommerce is installed
	public function checkWooCommerce() {

		// check if WooCommerce is installed
		if ( ! defined('WOOCOMMERCE_VERSION') && ! defined('WC_VERSION') ){

			wple_show_message("
				<b>WooCommerce is not installed.</b><br>
				<br>
				WP-Lister requires <a href='http://wordpress.org/plugins/woocommerce/' target='_blank'>WooCommerce</a> to be installed.<br>
			",'error');
			return false;

		}

		// check if WooCommerce is up to date
		$required_version    = '2.2.4';
		$woocommerce_version = defined('WC_VERSION') ? WC_VERSION : WOOCOMMERCE_VERSION;
		if ( version_compare( $woocommerce_version, $required_version ) < 0 ) {

			wple_show_message("
				<b>Warning: Your WooCommerce version is outdated.</b><br>
				<br>
				WP-Lister requires WooCommerce $required_version to be installed. You are using WooCommerce $woocommerce_version.<br>
				You should always keep your site and plugins updated.<br>
			",'error');
			return false;

		}

	}


	// checks for multisite network
	public function checkMultisite() {

		if ( is_multisite() ) {

			// check for network activation
			if ( ! function_exists( 'is_plugin_active_for_network' ) )
				require_once( ABSPATH . '/wp-admin/includes/plugin.php' );

			if ( function_exists('is_network_admin') && is_plugin_active_for_network( plugin_basename( WPLISTER_PATH.'/wp-lister-ebay.php' ) ) )
				wple_show_message("network activated!",1);
			else
				wple_show_message("not network activated!");


			// wple_show_message("
			// 	<b>Multisite installation detected</b><br>
			// 	<br>
			// 	This is a site network...<br>
			// ");
			return true;
		}

		return false;
	}


	// check for updates
	public function checkForUpdates() {
	}

	public function check_for_new_version() {

		if ( class_exists('WPLE_Update_API') ) { 

			$response = WPLEUP()->check_for_new_version( false );
			// echo "<pre>check_for_new_version() returned: ";print_r($response);echo"</pre>";#die();
			if ( ! $response->new_version ) return false;
			return true;

		} else {
			global $WPL_CustomUpdater;
			$update = $WPL_CustomUpdater->check_for_new_version();
		}

		return $update;
	}


	// check if any sites need to be refreshed
	public function checkSites() {
		global $wpdb;

		// return if DB has not been initialized yet
		if ( get_option('wplister_db_version') < 41 ) return;

		// get all enabled sites
		$enabled_sites = $wpdb->get_var("SELECT count(id) FROM ".$wpdb->prefix."ebay_sites WHERE enabled = 1 ");

		// if no sites are enabled, check accounts and enable sites in use
		if ( ! $enabled_sites ) {
			// enable site for each account
			foreach ( WPLE()->accounts as $account ) {
				$wpdb->update( $wpdb->prefix.'ebay_sites', array( 'enabled' => 1 ), array( 'id' => $account->site_id ) );
			}			
			// reload enabled sites
			$enabled_sites = $wpdb->get_var("SELECT count(id) FROM ".$wpdb->prefix."ebay_sites WHERE enabled = 1 ");
			if ( ! $enabled_sites ) return;
		}

		$sites_to_update = $wpdb->get_var("SELECT count(id) FROM ".$wpdb->prefix."ebay_sites WHERE enabled = 1 AND last_refresh IS NULL ");
		if ( ! $sites_to_update ) return;

		// show warning
		$msg1 = __('Site specific eBay details need to be updated.','wplister');
		$msg2 = __('Please visit your %s and click on "Refresh Details".','wplister');
		$accounts_page = get_option( 'wplister_enable_accounts_page' ) ? 'wplister-settings-accounts' : 'wplister-settings&tab=accounts';
		$link = sprintf( '<a href="admin.php?page=%s">%s</a>', $accounts_page, __('Account Settings','wplister') );
		$msg2 = sprintf($msg2, $link);
		$msg = "<b>$msg1</b></p><p>$msg2";
		wple_show_message($msg,'warn');

	} // checkSites()


	// check if WP_Cron is working properly
	public function checkCron() {

		// schedule daily event if not set yet
		if ( ! wp_next_scheduled( 'wple_daily_schedule' ) ) {
			wp_schedule_event( time(), 'daily', 'wple_daily_schedule' );
		}

		// get current schedule settings
		$cron_interval  = get_option( 'wplister_cron_auctions' );
		$next_scheduled = wp_next_scheduled( 'wplister_update_auctions' ) ;
		if ( ! $cron_interval ) return;
		if ( 'external' == $cron_interval ) return;

		// check if schedule is active
		if ( $cron_interval && ! $next_scheduled ) {

			wple_show_message( 
				'<p>'
				. '<b>Warning: WordPress Cron Job has been disabled - scheduled WP-Lister tasks are not executed!</b>'
				. '<br><br>'
				. 'The task schedule has been reset just now in order to automatically fix this.'
				. '<br><br>'
				. 'If this message does not disappear, please visit the <a href="admin.php?page=wplister-settings">Settings</a> page and click <i>Save Settings</i> or contact support.'
				. '</p>'
			,'warn');

			// this should fix it:
			wp_schedule_event( time(), $cron_interval, 'wplister_update_auctions' );
			return;
		} 

		// check if schedule is delayed (by 1d)
		// $next_scheduled = $next_scheduled - 3600*48; // debug only
		if ( $next_scheduled < current_time('timestamp',1) - 3600*25 ) {

			wple_show_message( 
				'<p>'
				. '<b>Attention: WordPress cron jobs seem to be broken on your site!</b>'
				. '<br><br>'
				. 'There are active background jobs which were scheduled to run '
				. human_time_diff( $next_scheduled, current_time('timestamp',1) ) . ' ago, '
				. 'but never have been executed.'
				. '<br><br>'
				. 'You should contact your hoster or site administrator to get this fixed as soon as possible. Until then, WP-Lister will not be able to sync the inventory correctly nor process new orders from eBay.'
				. '<br><br>'
				. 'The quickest way to make sure this will not happen again is using an external cron job to trigger the background tasks every 5 minutes. To do so, change the "update interval" setting option to "use external cron job" and follow the instructions. This is strongly recommended if you are using WP-Lister for Amazon as well.'
				. '<br><br>'
				. 'Keep in mind that this issue is not related to WP-Lister but to WordPress itself. All plugins and features which rely on scheduled tasks are affected by this issue - which includes scheduled posts, internal cleanup routines in WooCommerce and more.'
				. '<br><br>'
				. 'To see all your scheduled tasks and when they were last executed, we recommend installing '
				. '<a href="https://wordpress.org/plugins/debug-bar/" target="_blank">Debug Bar</a> and the '
				. '<a href="https://wordpress.org/plugins/debug-bar-cron/" target="_blank">Debug Bar Cron</a> extension. '
				. 'A possible workaround for sites with broken WP-Cron is the '
				. '<a href="https://wordpress.org/plugins/wp-cron-control/" target="_blank">WP Cron Control</a> plugin, '
				. 'but we recommend to find out what is causing this and fixing it instead.'
				. '</p>'
			,'error');

		}

	} // checkCron()


	// check if database has been corrupted during migration 
	public function checkDatabase() {
		global $wpdb;

		$rows_null_count = $wpdb->get_var("SELECT count(id) FROM ".$wpdb->prefix."ebay_auctions WHERE relist_date = '0000-00-00 00:00:00' OR date_finished = '0000-00-00 00:00:00'  ");
		if ( $rows_null_count ) {
			$wpdb->query("UPDATE ".$wpdb->prefix."ebay_auctions SET date_created   = NULL WHERE date_created   = '0000-00-00 00:00:00' ");
			$wpdb->query("UPDATE ".$wpdb->prefix."ebay_auctions SET date_published = NULL WHERE date_published = '0000-00-00 00:00:00' ");
			$wpdb->query("UPDATE ".$wpdb->prefix."ebay_auctions SET end_date       = NULL WHERE end_date       = '0000-00-00 00:00:00' ");
			$wpdb->query("UPDATE ".$wpdb->prefix."ebay_auctions SET relist_date    = NULL WHERE relist_date    = '0000-00-00 00:00:00' ");
			$wpdb->query("UPDATE ".$wpdb->prefix."ebay_auctions SET date_finished  = NULL WHERE date_finished  = '0000-00-00 00:00:00' ");
			wple_show_message( 'Repaired DB rows: ' . $rows_null_count );
			echo $wpdb->last_error;
		}

	}

	// this might help if there went something wrong during the upgrade from 1.5 to 1.6 or 2.0
	static function assignAllDataToDefaultAccount() {
		global $wpdb;
		$accounts   = WPLE()->accounts;
		$account_id = get_option( 'wplister_default_account_id' );
		if ( ! $account_id ) die('No default account set!');
		if ( ! isset( $accounts[ $account_id ] ) ) die('Invalid default account set!');

		$site_id    = $accounts[ $account_id ]->site_id;
		$site_id    = intval( $site_id    ); // sanitize parameters 
		$account_id = intval( $account_id );

		$wpdb->query("UPDATE ".$wpdb->prefix."ebay_auctions         SET account_id = $account_id, site_id = $site_id ");
		echo $wpdb->last_error;
		$wpdb->query("UPDATE ".$wpdb->prefix."ebay_categories       SET site_id = $site_id ");
		echo $wpdb->last_error;
		$wpdb->query("UPDATE ".$wpdb->prefix."ebay_log              SET account_id = $account_id, site_id = $site_id ");
		echo $wpdb->last_error;
		$wpdb->query("UPDATE ".$wpdb->prefix."ebay_messages         SET account_id = $account_id, site_id = $site_id ");
		echo $wpdb->last_error;
		$wpdb->query("UPDATE ".$wpdb->prefix."ebay_orders           SET account_id = $account_id, site_id = $site_id ");
		echo $wpdb->last_error;
		$wpdb->query("UPDATE ".$wpdb->prefix."ebay_profiles         SET account_id = $account_id, site_id = $site_id ");
		echo $wpdb->last_error;
		$wpdb->query("UPDATE ".$wpdb->prefix."ebay_payment          SET site_id = $site_id ");
		echo $wpdb->last_error;
		$wpdb->query("UPDATE ".$wpdb->prefix."ebay_shipping         SET site_id = $site_id ");
		echo $wpdb->last_error;
		$wpdb->query("UPDATE ".$wpdb->prefix."ebay_store_categories SET account_id = $account_id, site_id = $site_id ");
		echo $wpdb->last_error;
		$wpdb->query("UPDATE ".$wpdb->prefix."ebay_transactions     SET account_id = $account_id, site_id = $site_id ");
		echo $wpdb->last_error;

	} // assignAllDataToDefaultAccount()


	// check for listings, profiles and orders using an invalid / nonexisting account
	static function checkDbForInvalidAccounts() {
		global $wpdb;
		$accounts   = WPLE()->accounts;
		$default_account_id = get_option( 'wplister_default_account_id' );
		$default_account    = isset( $accounts[ $default_account_id ] ) ? $accounts[ $default_account_id ] : false;
		$default_account_title = $default_account ? $default_account->title : 'MISSING DEFAULT ACCOUNT';

		// get list of all active account IDs
		$active_account_ids = array();
		foreach ($accounts as $account) {
			$active_account_ids[] = $account->id;
		}
		$active_account_ids_sql = join(', ', $active_account_ids);

		// find data with invalid account IDs
		$listings_count = $wpdb->get_var("
			SELECT count(account_id)
			  FROM ".$wpdb->prefix."ebay_auctions
			 WHERE NOT account_id IN ( $active_account_ids_sql )
		");
		$profiles_count = $wpdb->get_var("
			SELECT count(account_id)
			  FROM ".$wpdb->prefix."ebay_profiles
			 WHERE NOT account_id IN ( $active_account_ids_sql )
		");
		$orders_count = $wpdb->get_var("
			SELECT count(account_id)
			  FROM ".$wpdb->prefix."ebay_orders
			 WHERE NOT account_id IN ( $active_account_ids_sql )
		");

		// return if no problems found
		if ( ! $listings_count && ! $profiles_count && ! $orders_count ) return;

		// compile summary
		$what_exactly = array();
		if ( $listings_count ) $what_exactly[] = $listings_count . ' listings';
		if ( $profiles_count ) $what_exactly[] = $profiles_count . ' profiles';
		if ( $orders_count   ) $what_exactly[] = $orders_count   . ' orders';
		$what_exactly = join(' and ',$what_exactly);

		$btn_url = 'admin.php?page=wplister-settings&tab=accounts&action=wple_assign_invalid_data_to_default_account';

		// show message
		$msg = sprintf('<b>Warning: There are %s using an account which does not exist anymore.</b>',$what_exactly) . '<br><br>';
		$msg .= 'This can happen when you delete an account from WP-Lister without removing all listings, profiles and orders first.'. '<br>';
		$msg .= sprintf('Please click the button below to assign all found items to your default account <b>%s</b> (ID %s).', $default_account_title, $default_account_id ) . '<br><br>';
		$msg .= sprintf('<a href="%s" class="button button-secondary">Assign found items to default account</a>', $btn_url );
		wple_show_message($msg,'warn');

	} // checkDbForInvalidAccounts()

	// fix listings, profiles and orders using an invalid / nonexisting account
	static function fixItemsUsingInvalidAccounts() {
		global $wpdb;
		$accounts           = WPLE()->accounts;
		$default_account_id = get_option( 'wplister_default_account_id' );
		$default_account    = isset( $accounts[ $default_account_id ] ) ? $accounts[ $default_account_id ] : false;
		if ( ! $default_account ) die('Invalid default account set!');

		// get list of all active account IDs
		$active_account_ids = array();
		foreach ($accounts as $account) {
			$active_account_ids[] = $account->id;
		}
		$active_account_ids_sql = join(', ', $active_account_ids);

		// find data with invalid account IDs
		$listings_count = $wpdb->get_var("
			UPDATE ".$wpdb->prefix."ebay_auctions
			   SET account_id = '$default_account_id'
			 WHERE NOT account_id IN ( $active_account_ids_sql )
		");
		$profiles_count = $wpdb->get_var("
			UPDATE ".$wpdb->prefix."ebay_profiles
			   SET account_id = '$default_account_id'
			 WHERE NOT account_id IN ( $active_account_ids_sql )
		");
		$orders_count = $wpdb->get_var("
			UPDATE ".$wpdb->prefix."ebay_orders
			   SET account_id = '$default_account_id'
			 WHERE NOT account_id IN ( $active_account_ids_sql )
		");
		// echo $wpdb->last_query;
		echo $wpdb->last_error;

		// show message
		$msg = 'All items have been assigned to your default account.';
		wple_show_message($msg);

	} // fixItemsUsingInvalidAccounts()


	// check token expiration date
	public function checkToken() {

		// legacy option - not used anymore
		// $expdate = get_option( 'wplister_ebay_token_expirationtime' );

		// get all accounts
		$accounts = WPLE()->accounts;
		if ( ! is_array($accounts) || empty($accounts) ) return;

		$two_weeks_from_now = time() + 3600 * 24 * 7 * 2;

		foreach ($accounts as $account) {

			// get a valid expiration time
			$expdate = $account->valid_until;
			if ( ! $expdate ) continue;
			if ( ! $exptime = strtotime($expdate) ) continue;
			if ( ! $account->active ) continue;

			if ( $exptime < time() ) {

				// token has already expired
				wple_show_message( 
					'<p>'
					// . '<b>Warning: '. __('Your ebay token has expired on','wplister') . ' ' . $expdate
					. '<b> '. sprintf( __('Warning: The token for your eBay account %s has expired on %s.','wplister'), $account->title, $expdate )
					. ' (' . human_time_diff( strtotime($expdate) ) . ' ago) '.'</b>'
					. '<br><br>'
					. 'To refresh your eBay token, please visit Settings &raquo; Account &raquo; Edit and follow the instructions in the sidebar.'
					. '</p>'
				,'error');

			} elseif ( $exptime < $two_weeks_from_now ) {

				// token will expire soon
				wple_show_message( 
					'<p>'
					// . '<b>Warning: '. __('Your eBay token will expire on','wplister') . ' ' . $expdate
					. '<b> '. sprintf( __('Warning: The token for your eBay account %s will expire on %s.','wplister'), $account->title, $expdate )
					. ' (in ' . human_time_diff( strtotime($expdate) ) . ') '.'</b>'
					. '<br><br>'
					. 'To refresh your eBay token, please visit Settings &raquo; Account &raquo; Edit and follow the instructions in the sidebar.'
					. '</p>'
				,'warn');

			}

		} // foreach account


		// warn about invalid token - could be obsolete since we check for expiry time already, but maybe it's still useful
		if ( $token_data = get_option('wplister_ebay_token_is_invalid') ) {
			if ( is_array( $token_data ) && isset($token_data['site_id'] ) ) {

				$account       = isset( WPLE()->accounts[ $token_data['account_id'] ] ) ? WPLE()->accounts[ $token_data['account_id'] ]: false;
				$account_title = $account ? $account->title . '('.$account->site_code.')' : 'Default';
				$msg1  = sprintf( __('The token for your eBay account %s seems to be invalid.', 'wplister'), $account_title );
				$msg2  = 'To refresh your eBay token, please visit Settings &raquo; Account &raquo; Edit and follow the instructions in the sidebar.';
				$msg   = "<b>$msg1</b></p><p>$msg2";
				wple_show_message($msg,'warn');			

			}
		}
		
	} // checkToken()


	// check folders
	public function checkFolders() {
		// WPLE()->logger->info('creating wp-content/uploads/wp-lister/templates');		

		// create wp-content/uploads/wp-lister/templates if not exists
		$uploads = wp_upload_dir();
		$uploaddir = $uploads['basedir'];

		$wpldir = $uploaddir . '/wp-lister';
		if ( !is_dir($wpldir) ) {

			$result  = @mkdir( $wpldir );
			if ($result===false) {
				wple_show_message( "Could not create template folder: " . $wpldir, 1, 1 );	
				return false;
			}

		}

		$tpldir = $wpldir . '/templates';
		if ( !is_dir($tpldir) ) {

			$result  = @mkdir( $tpldir );
			if ($result===false) {
				wple_show_message( "Could not create template folder: " . $tpldir, 1, 1 );	
				return false;
			}

		}

		// WPLE()->logger->info('template folder: '.$tpldir);		
	
	}
	


}

