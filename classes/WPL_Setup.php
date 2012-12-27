<?php

class WPL_Setup extends WPL_Core {
	
	// check if setup is incomplete and display next step
	public function checkSetup( $page = false ) {
		global $pagenow;

		// check if cURL is loaded
		if ( ! self::isCurlLoaded() ) return false;

		// check for windows server
		if ( self::isWindowsServer() ) return false;

		// check for multisite installation
		// if ( self::checkMultisite() ) return false;

		// setup wizard
		// if ( self::getOption('ebay_token') == '' ) {
		if ( ( '1' == self::getOption('setup_next_step') ) && ( $page != 'settings') ) {
		
			$msg1 = __('You have not linked WP-Lister to your eBay account yet.','wplister');
			$msg2 = __('To complete the setup procedure go to %s and follow the instructions.','wplister');
			$link = '<a href="admin.php?page=wplister-settings">'.__('Settings','wplister').'</a>';
			$msg2 = sprintf($msg2, $link);
			$msg = "<p><b>$msg1</b></p><p>$msg2</p>";
			$this->showMessage($msg);
		
		} elseif ( '2' == self::getOption('setup_next_step') ) {
		
			$title = __('WP-Lister Setup - Step 2','wplister');
			$msg1  = __('Before creating your first profile, we need to download certain information which are specific to the eBay site you selected.','wplister');
			$msg2  = __('This includes shipping options, payment methods, your custom store categories as well as the whole eBay category tree, which might take a while.','wplister');
			$url   = $pagenow . '?page=' . $_GET['page'];
			// $hidden  = '<input type="hidden" name="action" value="update_ebay_details_setup" />';
			$button  = '<a href="#" id="btn_update_ebay_data" class="button-primary">'.__('Update eBay details','wplister').'</a>';
			#$msg2  = sprintf($msg2, $link);
			$msg   = "<p><b>$title</b></p><p>$msg1</p><p>$msg2</p>";
			// $msg  .= "<form method='post' action='$url'>$hidden".wp_nonce_field( 'e2e_tools_page',"_wpnonce", true, false )."$button</form>";
			$msg  .= $button;
			$this->showMessage($msg);
		
		} elseif ( '3' == self::getOption('setup_next_step') ) {
		
			$title = __('WP-Lister Setup - Step 3','wplister');
			$msg1 = __('Create a default listing template.','wplister');
			$msg2 = __('To create your first listing template click on %s.','wplister').'<br>';
			if ( @$_GET['action'] == 'add_new_template' )
				$msg2 = __('Replace the default text according to your requirements and save your template to continue.','wplister');
			$link = '<a href="admin.php?page=wplister-templates&action=add_new_template">'.__('New Template', 'wplister').'</a>';
			$msg2 = sprintf($msg2, $link);
			$msg = "<p><b>$title</b></p><p><b>$msg1</b></p><p>$msg2</p>";
			$this->showMessage($msg);
		
		} elseif ( '4' == self::getOption('setup_next_step') ) {
		
			$title = __('WP-Lister Setup - Step 4','wplister');
			$msg1  = __('The final step: create your first listing profile.', 'wplister');
			$msg2  = __('Click on %s and start defining your listing options.<br>After saving your profile, visit your Products page and select the products to list on eBay.','wplister');
			$link  = '<a href="admin.php?page=wplister-profiles&action=add_new_profile">'.__('New Profile', 'wplister').'</a>';
			$msg2  = sprintf($msg2, $link);
			$msg   = "<p><b>$msg1</b></p><p>$msg2</p>";
			$this->showMessage($msg);
		
		} elseif ( '5' == self::getOption('setup_next_step') ) {
		
			$title = __('WP-Lister Setup is complete.','wplister');
			$msg1  = __('You are ready now to list your first items.', 'wplister');
			$msg2  = __('Visit your Products page, select a few items and select "Prepare listings" from the bulk actions menu.','wplister');
			$msg   = "<p><b>$msg1</b></p><p>$msg2</p>";
			$this->showMessage($msg);
			update_option('wplister_setup_next_step', '0');
		
		}
		
		// db upgrade
		self::upgradeDB();

		// fetch user details if not done yet
		if ( ( self::getOption('ebay_token') != '' ) && ( ! self::getOption('ebay_user') ) ) {
			$this->initEC();
			$UserID = $this->EC->GetUser();
			$this->EC->closeEbay();
			// $this->showMessage( __('Account details were updated.','wplister') . $UserID );
			// $this->showMessage( __('Your UserID is ','wplister') . $UserID );
		}
		
	}


	// upgrade db
	public function upgradeDB() {
		global $wpdb;

		$db_version = get_option('wplister_db_version', 1);
		$msg = false;

		// upgrade to version 2
		if ( 2 > $db_version ) {
			$new_db_version = 2;
		
			// create table: ebay_log
			$sql = "CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}ebay_log` (
			  `id` int(11) NOT NULL AUTO_INCREMENT,
			  `timestamp` datetime DEFAULT NULL,
			  `request_url` text DEFAULT NULL,
			  `request` text DEFAULT NULL,
			  `response` text DEFAULT NULL,
			  `callname` varchar(64) DEFAULT NULL,
			  `success` varchar(16) DEFAULT NULL,
			  `ebay_id` bigint(255) DEFAULT NULL,
			  `user_id` int(11) DEFAULT NULL,	
			  PRIMARY KEY (`id`)	
			);";
			$wpdb->query($sql);	echo mysql_error();

			update_option('wplister_db_version', $new_db_version);
			$msg  = __('WP-Lister database was upgraded to version ', 'wplister') . $new_db_version . '.';
		}
		
		// upgrade to version 3
		if ( 3 > $db_version ) {
			$new_db_version = 3;

			// rename column in table: ebay_categories
			$sql = "ALTER TABLE `{$wpdb->prefix}ebay_categories`
			        CHANGE wpsc_category_id wp_term_id INTEGER ";
			$wpdb->query($sql);	#echo mysql_error();

			// rename column in table: ebay_store_categories
			$sql = "ALTER TABLE `{$wpdb->prefix}ebay_store_categories`
			        CHANGE wpsc_category_id wp_term_id INTEGER ";
			$wpdb->query($sql);	#echo mysql_error();
			
			update_option('wplister_db_version', $new_db_version);
			$msg  = __('WP-Lister database was upgraded to version ', 'wplister') . $new_db_version . '.';
		}
		
		// upgrade to version 4
		if ( 4 > $db_version ) {
			$new_db_version = 4;

			// set column type to bigint in table: ebay_store_categories
			$sql = "ALTER TABLE `{$wpdb->prefix}ebay_store_categories`
			        CHANGE cat_id cat_id BIGINT ";
			$wpdb->query($sql);	#echo mysql_error();
			
			// set column type to bigint in table: ebay_store_categories
			$sql = "ALTER TABLE `{$wpdb->prefix}ebay_store_categories`
			        CHANGE parent_cat_id parent_cat_id BIGINT ";
			$wpdb->query($sql);	#echo mysql_error();
			
			update_option('wplister_db_version', $new_db_version);
			$msg  = __('WP-Lister database was upgraded to version ', 'wplister') . $new_db_version . '.';
		}
		
		// TODO: include upgrade 5-9 in WPLister_Install class
		
		// upgrade to version 5
		if ( 5 > $db_version ) {
			$new_db_version = 5;
		
			// create table: ebay_log
			$sql = "CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}ebay_jobs` (
			  `id` int(11) NOT NULL AUTO_INCREMENT,
			  `job_key` varchar(64) DEFAULT NULL,
			  `job_name` varchar(64) DEFAULT NULL,
			  `tasklist` text DEFAULT NULL,
			  `results` text DEFAULT NULL,
			  `success` varchar(16) DEFAULT NULL,
			  `date_created` datetime DEFAULT NULL,
			  `date_finished` datetime DEFAULT NULL,
			  `user_id` int(11) DEFAULT NULL,	
			  PRIMARY KEY (`id`)	
			);";
			$wpdb->query($sql);	echo mysql_error();

			update_option('wplister_db_version', $new_db_version);
			$msg  = __('WP-Lister database was upgraded to version ', 'wplister') . $new_db_version . '.';
		}
		

		// upgrade to version 6
		if ( 6 > $db_version ) {
			$new_db_version = 6;

			// add columns to ebay_shipping table
			$sql = "ALTER TABLE `{$wpdb->prefix}ebay_shipping`
			        ADD COLUMN `ShippingCategory` varchar(64) DEFAULT NULL AFTER `carrier`, 
			        ADD COLUMN `WeightRequired` int(10) UNSIGNED NOT NULL DEFAULT 0 AFTER `international`, 
			        ADD COLUMN `DimensionsRequired` int(10) UNSIGNED NOT NULL DEFAULT 0 AFTER `international`, 
			        ADD COLUMN `isCalculated` int(10) UNSIGNED NOT NULL DEFAULT 0 AFTER `international`, 
			        ADD COLUMN `isFlat` int(10) UNSIGNED NOT NULL DEFAULT 0 AFTER `international`;
			";
			$wpdb->query($sql);	#echo mysql_error();
			
			update_option('wplister_db_version', $new_db_version);
			$msg  = __('WP-Lister database was upgraded to version ', 'wplister') . $new_db_version . '.';
		}
		

		// upgrade to version 7  (0.9.7.9)
		if ( 7 > $db_version ) {
			$new_db_version = 7;

			// set admin_email as default license_email
			update_option('wplister_license_email', get_bloginfo('admin_email') );

			update_option('wplister_db_version', $new_db_version);
			$msg  = __('WP-Lister database was upgraded to version ', 'wplister') . $new_db_version . '.';
		}
		

		// upgrade to version 8
		if ( 8 > $db_version ) {
			$new_db_version = 8;

			// add columns to ebay_shipping table
			$sql = "ALTER TABLE `{$wpdb->prefix}ebay_profiles`
			        ADD COLUMN `category_specifics` text DEFAULT NULL;
			";
			$wpdb->query($sql);	#echo mysql_error();
			
			update_option('wplister_db_version', $new_db_version);
			$msg  = __('WP-Lister database was upgraded to version ', 'wplister') . $new_db_version . '.';
		}
		
		// upgrade to version 9  (1.0)
		if ( 9 > $db_version ) {
			$new_db_version = 9;

			// add update channel option
			update_option('wplister_update_channel', 'stable');
			
			update_option('wplister_db_version', $new_db_version);
			$msg  = __('WP-Lister database was upgraded to version ', 'wplister') . $new_db_version . '.';
		}
		
		// upgrade to version 10  (1.0.7)
		if ( 10 > $db_version ) {
			$new_db_version = 10;

			// add column to ebay_transactions table
			$sql = "ALTER TABLE `{$wpdb->prefix}ebay_transactions`
			        ADD COLUMN `wp_order_id` int(10) UNSIGNED NOT NULL DEFAULT 0 AFTER `post_id`
			";
			$wpdb->query($sql);	#echo mysql_error();
	
			update_option('wplister_db_version', $new_db_version);
			$msg  = __('WP-Lister database was upgraded to version ', 'wplister') . $new_db_version . '.';
		}

		// upgrade to version 11  (1.0.8.8)
		if ( 11 > $db_version ) {
			$new_db_version = 11;

			// fetch available dispatch times
			if ( get_option('wplister_ebay_token') != '' ) {
				$this->initEC();
				$result = $this->EC->loadDispatchTimes();
				$this->EC->closeEbay();		
			}
			
			update_option('wplister_db_version', $new_db_version);
			$msg  = __('WP-Lister database was upgraded to version ', 'wplister') . $new_db_version . '.';
		}
		

		// upgrade to version 11  (1.0.9.8)
		if ( 11 > $db_version ) {
			$new_db_version = 11;

			// fetch all transactions
			$sql = "SELECT id FROM `{$wpdb->prefix}ebay_transactions` ";
			$items = $wpdb->get_results($sql);	echo mysql_error();

			// find and assign orders
			$tm = new TransactionsModel();
			foreach ($items as $transaction) {

				// fetch item details
				$item = $tm->getItem( $transaction->id );
				$details = $item['details'];

				// build order title (WooCommerce only)
			    $post_title = 'Order &ndash; '.date('F j, Y @ h:i A', strtotime( $details->CreatedDate ) );

			    // find created order
				$sql = "
					SELECT ID FROM `{$wpdb->prefix}posts`
					WHERE post_title = '$post_title'
					  AND post_status = 'publish'
				";
				$post_id = $wpdb->get_var($sql);	echo mysql_error();
				
				// set order_id for transaction
				$tm->updateWpOrderID( $transaction->id, $post_id );							    

				// Update post data
				update_post_meta( $post_id, '_transaction_id', $transaction->id );
				update_post_meta( $post_id, '_ebay_item_id', $item['item_id'] );
				update_post_meta( $post_id, '_ebay_transaction_id', $item['transaction_id'] );

			}
			
			update_option('wplister_db_version', $new_db_version);
			$msg  = __('WP-Lister database was upgraded to version ', 'wplister') . $new_db_version . '.';
		}

		
		if ( $msg )	$this->showMessage($msg);		

		#debug: update_option('wplister_db_version', 0);
		
	}


	// check if cURL is loaded
	public function isCurlLoaded() {

		if( ! extension_loaded('curl') ) {
			$this->showMessage("
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
			",1);
			return false;
		}

		return true;
	}

	// check server is running windows
	public function isWindowsServer() {

		if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {

			$this->showMessage("
				<b>Server requirements not met</b><br>
				<br>
				Your server seems to run on Windows.<br>
				WP-Lister currently only supports unixoid operating systems like Linux, FreeBSD and OS X.<br>
				<br>
				If you'd like to help make WP-Lister run on Windows, please contact us at info@wplab.com.
			",1);
			return true;
		}

		return false;
	}


	// checks for multisite network
	public function checkMultisite() {

		if ( is_multisite() ) {

			// check for network activation
			if ( ! function_exists( 'is_plugin_active_for_network' ) )
				require_once( ABSPATH . '/wp-admin/includes/plugin.php' );

			if ( function_exists('is_network_admin') && is_plugin_active_for_network( plugin_basename( WPLISTER_PATH.'/wp-lister.php' ) ) )
				$this->showMessage("network activated!",1);
			else
				$this->showMessage("not network activated!");


			// $this->showMessage("
			// 	<b>Multisite installation detected</b><br>
			// 	<br>
			// 	This is a site network...<br>
			// ");
			return true;
		}

		return false;
	}


}

