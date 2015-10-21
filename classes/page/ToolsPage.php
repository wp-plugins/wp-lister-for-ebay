<?php
/**
 * ToolsPage class
 * 
 */

class ToolsPage extends WPL_Page {

	const slug = 'tools';
	var $debug = false;
	var $resultsHtml = '';

	public function onWpInit() {
		// parent::onWpInit();

		// custom (raw) screen options for tools page
		add_screen_options_panel('wplister_tools_options', '', array( &$this, 'renderSettingsOptions'), $this->main_admin_menu_slug.'_page_wplister-tools' );

		// load styles and scripts for this page only
		// add_action( 'admin_print_styles', array( &$this, 'onWpPrintStyles' ) );
		add_action( 'admin_enqueue_scripts', array( &$this, 'onWpEnqueueScripts' ) );		
		add_thickbox();
	}

	public function onWpAdminMenu() {
		parent::onWpAdminMenu();

		add_submenu_page( self::ParentMenuId, $this->getSubmenuPageTitle( 'Tools' ), __('Tools','wplister'), 
						  self::ParentPermissions, $this->getSubmenuId( 'tools' ), array( &$this, 'onDisplayToolsPage' ) );
	}

	public function handleSubmit() {
        WPLE()->logger->debug("handleSubmit()");

		// force wp update check
		if ( $this->requestAction() == 'force_update_check') {				

            // global $wpdb;
            // $wpdb->query("update wp_options set option_value='' where option_name='_site_transient_update_plugins'");
            // set_site_transient('update_plugins', null);
            delete_site_transient('update_plugins');

			// $this->showMessage( 
			// 	'<big>'. __('Check for updates was initiated.','wplister') . '</big><br><br>'
			// 	. __('You can visit your WordPress Updates now.','wplister') . '<br><br>'
			// 	. __('Since the updater runs in the background, it might take a little while before new updates appear.','wplister') . '<br><br>'
			// 	. '<a href="update-core.php" class="button-primary">'.__('view updates','wplister') . '</a>'
			// );
		}

	}
	

	public function getCurrentSqlTime( $gmt = false ) {
		global $wpdb;
		if ( $gmt ) $wpdb->query("SET time_zone='+0:00'");
		$sql_time = $wpdb->get_var("SELECT NOW()");
		return $sql_time;
	}
	

	public function handleActions() {

		// check action
		if ( isset($_REQUEST['action']) ) {

			// check_ebay_connection
			if ( $_REQUEST['action'] == 'check_ebay_connection') {				
				$msg = $this->checkEbayConnection();
				// $this->showMessage( $msg );
				return;
			}

			// custom debug code
			if ( $_REQUEST['action'] == 'wple_debug_1') {				
				global $wpdb;

				$post_title = 'this string is 256 characters long - which is 1 too many........................................................................................................................................................................................................';
				// $post_title = 'TEST';

				$data = array();
				$data['auction_title'] = $post_title;
				$data['status']        = 'TEST';

				$table = $wpdb->prefix . 'ebay_auctions';
				$result = $wpdb->insert( $table, $data );

				echo "<pre>result: ";print_r($result);echo"</pre>";#die();
				echo "<pre>length: ";print_r(strlen($post_title));echo"</pre>";#die();
				echo "<pre>insert_id: ";print_r($wpdb->insert_id);echo"</pre>";#die();
				echo "<pre>last query: ";print_r($wpdb->last_query);echo"</pre>";#die();

				return;
			}

			// check nonce
			if ( check_admin_referer( 'e2e_tools_page' ) ) {

				// check_ebay_time_offset
				if ( $_REQUEST['action'] == 'check_ebay_time_offset') {				
					$this->checkEbayTimeOffset();
				}
				// view_logfile
				if ( $_REQUEST['action'] == 'view_logfile') {				
					$this->viewLogfile();
				}
				// wplister_clear_log
				if ( $_REQUEST['action'] == 'wplister_clear_log') {				
					$this->clearLogfile();
					$this->showMessage('Log file was cleared.');
				}


				// check_wc_out_of_sync
				if ( $_REQUEST['action'] == 'check_wc_out_of_sync') {				
					require_once( WPLISTER_PATH . '/classes/core/WPL_InventoryCheck.php' );

					$ic = new WPL_InventoryCheck();
					$mode            = isset( $_REQUEST['mode'] )   		 ? $_REQUEST['mode']   			: 'published';
					$prices          = isset( $_REQUEST['prices'] ) 		 ? $_REQUEST['prices'] 			: false;
					$mark_as_changed = isset( $_REQUEST['mark_as_changed'] ) ? $_REQUEST['mark_as_changed'] : false;
					$step            = isset( $_REQUEST['step']   ) 		 ? $_REQUEST['step']   			: 0;

					// check new batch of items
					$new_items_were_processed = $ic->checkProductInventory( $mode, $prices, $step );

					if ( $new_items_were_processed ) {

						// continue with step+1
						$msg = 'Checking inventory, please wait... ';
						if ( $mark_as_changed == 'yes' ) {
							$msg = 'Updating listing status, please wait... ';
						} 

						$step++;
						$msg .= '<br><small>Step '.$step.' / '.($step * $ic->batch_size).' items checked </small>';

						// build button, which is triggered by js automatically
						$url  = 'admin.php?page=wplister-tools&action=check_wc_out_of_sync&mode='.$mode.'&prices='.$prices.'&mark_as_changed='.$mark_as_changed.'&step='.$step.'&_wpnonce='.wp_create_nonce('e2e_tools_page');
						$msg .= '<a href="'.$url.'" id="wple_auto_next_step" class="button" style="display:none">Next</a>';
						wple_show_message( $msg );

					} else {
				
						// show results
						$ic->showProductInventoryCheckResult( $mode );

						// clear tmp data
						update_option('wple_inventory_check_queue_data', '');

					}

				} // check_wc_out_of_sync

				// check_wc_out_of_stock
				if ( $_REQUEST['action'] == 'check_wc_out_of_stock') {				
					require_once( WPLISTER_PATH . '/classes/core/WPL_InventoryCheck.php' );

					$ic = new WPL_InventoryCheck();
					$mark_as_changed = isset( $_REQUEST['mark_as_changed'] ) ? $_REQUEST['mark_as_changed'] : false;
					$step            = isset( $_REQUEST['step']   ) 		 ? $_REQUEST['step']   			: 0;

					// check new batch of items
					$new_items_were_processed = $ic->checkProductStock( $step );

					if ( $new_items_were_processed ) {

						// continue with step+1
						$msg = 'Checking for out of stock products, please wait... ';
						if ( $mark_as_changed == 'yes' ) {
							$msg = 'Updating listing status, please wait... ';
						} 

						$step++;
						$msg .= '<br><small>Step '.$step.' / '.($step * $ic->batch_size).' items checked </small>';

						// build button, which is triggered by js automatically
						$url  = 'admin.php?page=wplister-tools&action=check_wc_out_of_stock&mark_as_changed='.$mark_as_changed.'&step='.$step.'&_wpnonce='.wp_create_nonce('e2e_tools_page');
						$msg .= '<a href="'.$url.'" id="wple_auto_next_step" class="button" style="display:none">Next</a>';
						wple_show_message( $msg );

					} else {
				
						// show results
						$ic->showProductStockCheckResult();

						// clear tmp data
						update_option('wple_inventory_check_queue_data', '');

					}

				} // check_wc_out_of_stock

				// check_wc_sold_stock
				if ( $_REQUEST['action'] == 'check_wc_sold_stock') {				
					require_once( WPLISTER_PATH . '/classes/core/WPL_InventoryCheck.php' );
					$ic = new WPL_InventoryCheck();
					$ic->checkSoldStock();
				}


				// check_ebay_image_requirements
				if ( $_REQUEST['action'] == 'check_ebay_image_requirements') {				
					$this->checkProductImages();
				}

				// check_missing_ebay_transactions
				if ( $_REQUEST['action'] == 'check_missing_ebay_transactions') {				
					$this->checkTransactions( true );
				}

				// fix_cog_on_imported_orders
				if ( $_REQUEST['action'] == 'fix_cog_on_imported_orders') {				
					$this->fixCostOfGoods();
				}

				// lock_all_listings
				if ( $_REQUEST['action'] == 'lock_all_listings') {				
					$count = WPLE_ListingQueryHelper::lockAll( 1 );
		    		$this->showMessage( $count .' '. 'items were locked.' );
				}
				// unlock_all_listings
				if ( $_REQUEST['action'] == 'unlock_all_listings') {				
					$count = WPLE_ListingQueryHelper::lockAll( 0 );
		    		$this->showMessage( $count .' '. 'items were unlocked.' );
				}

				// import_wpla_product_ids
				if ( $_REQUEST['action'] == 'import_wpla_product_ids') {				
					$count = self::importWplaProductIds();
				}

				// assign_all_data_to_default_account
				if ( $_REQUEST['action'] == 'assign_all_data_to_default_account') {				
					WPL_Setup::assignAllDataToDefaultAccount();
		    		$this->showMessage( sprintf( 'All listings, orders and profiles have been assigned to your default account %s.', get_option('wplister_default_account_id') ) );
				}


				// GetTokenStatus
				if ( $_REQUEST['action'] == 'GetTokenStatus') {				
					$this->initEC();
					$expdate = $this->EC->GetTokenStatus();
					$this->EC->closeEbay();
					$msg = __('Your token will expire on','wplister') . ' ' . $expdate; 
					$msg .= ' (' . human_time_diff( strtotime($expdate) ) . ' from now)';
					$this->showMessage( $msg );
				}
				// GetUser
				if ( $_REQUEST['action'] == 'GetUser') {				
					$this->initEC();
					$UserID = $this->EC->GetUser();
					$this->EC->GetUserPreferences();
					$this->EC->closeEbay();
					$this->showMessage( __('Your UserID is','wplister') . ' ' . $UserID );
				}

				// GetNotificationPreferences
				if ( $_REQUEST['action'] == 'GetNotificationPreferences') {				
					$this->initEC();
					$debug = $this->EC->GetNotificationPreferences();
					$this->EC->closeEbay();
					wple_show_message( '<pre>'.print_r($debug,1).'</pre>' );
				}
				// EnableUserNotificationPreferences
				if ( $_REQUEST['action'] == 'EnableUserNotificationPreferences') {				
					$this->initEC();
					$debug = $this->EC->SetUserNotificationPreferences( 'Enable' );
					$this->EC->closeEbay();
					wple_show_message( '<pre>'.print_r($debug,1).'</pre>' );
				}
				// DisableUserNotificationPreferences
				if ( $_REQUEST['action'] == 'DisableUserNotificationPreferences') {				
					$this->initEC();
					$debug = $this->EC->SetUserNotificationPreferences( 'Disable' );
					$this->EC->closeEbay();
					wple_show_message( '<pre>'.print_r($debug,1).'</pre>' );
				}
				// ResetNotificationPreferences
				if ( $_REQUEST['action'] == 'ResetNotificationPreferences') {				
					$this->initEC();
					$debug = $this->EC->ResetNotificationPreferences();
					$this->EC->closeEbay();
					wple_show_message( '<pre>'.print_r($debug,1).'</pre>' );
				}
				// GetNotificationsUsage
				if ( $_REQUEST['action'] == 'GetNotificationsUsage') {				
					$this->initEC();
					$debug = $this->EC->GetNotificationsUsage();
					$this->EC->closeEbay();
					wple_show_message( '<pre>'.print_r($debug,1).'</pre>' );
				}
	
				// update_ebay_transactions
				if ( $_REQUEST['action'] == 'update_ebay_transactions_30') {				
					$this->initEC();
					$tm = $this->EC->loadTransactions( 30 );
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
	
				// update_ebay_orders
				if ( $_REQUEST['action'] == 'update_ebay_orders_30') {				
					$this->initEC();
					$om = $this->EC->loadEbayOrders( 30 );
					$this->EC->updateListings();
					$this->EC->closeEbay();

					// show report
					$msg  = $om->count_total .' '. __('Orders were loaded from eBay.','wplister') . '<br>';
					$msg .= __('Timespan','wplister') .': '. $om->getHtmlTimespan();
					$msg .= '&nbsp;&nbsp;';
					$msg .= '<a href="#" onclick="jQuery(\'.ebay_order_report\').toggle();return false;">'.__('show details','wplister').'</a>';
					$msg .= $om->getHtmlReport();
					$this->showMessage( $msg );
				}
	
				// wple_upgrade_tables_to_utf8mb4
				if ( $_REQUEST['action'] == 'wple_upgrade_tables_to_utf8mb4') {				
					$this->upgradeTablesUTF8MB4();
				}

				// wple_run_daily_schedule
				if ( $_REQUEST['action'] == 'wple_run_daily_schedule') {
					do_action( 'wple_daily_schedule' );
				}
				
				// wple_run_update_schedule
				if ( $_REQUEST['action'] == 'wple_run_update_schedule') {
					do_action( 'wplister_update_auctions' );
				}

	
			} else {
				die ('not allowed');
			}

		} // if $_REQUEST['action']

	} // handleActions()
	

	public function onDisplayToolsPage() {

		$this->check_wplister_setup();

		$this->handleActions();

		$aData = array(
			'plugin_url'				=> self::$PLUGIN_URL,
			'message'					=> $this->message,		
			'results'					=> isset($this->results) ? $this->results : '',
			'resultsHtml'				=> isset($this->resultsHtml) ? $this->resultsHtml : '',
			'debug'						=> isset($debug) ? $debug : '',
			'log_size'					=> file_exists(WPLE()->logger->file) ? filesize(WPLE()->logger->file) : '',
			'form_action'				=> 'admin.php?page='.self::ParentMenuId.'-tools'
		);
		$this->display( 'tools_page', $aData );
	}

	public function checkEbayTimeOffset() {

		$this->initEC();

		$ebay_time    = $this->EC->getEbayTime();
		$php_time     = date( 'Y-m-d H:i:s', time() );
		$sql_time     = $this->getCurrentSqlTime( false );
		$sql_time_gmt = $this->getCurrentSqlTime( true );
		
		$ebay_time_ts = strtotime( substr($ebay_time,0,16) );
		$sql_time_ts  = strtotime( substr( $sql_time,0,16) );
		$time_diff    = $ebay_time_ts - $sql_time_ts;
		$hours_offset = intval ($time_diff / 3600);

		$msg  = '';
		$msg .= 'eBay time GMT: '. $ebay_time . "<br>";
		$msg .= 'SQL time GMT : '. $sql_time_gmt . "<br>";
		$msg .= 'PHP time GMT : '. $php_time . "<br><br>";					
		$msg .= 'Local SQL time: '. $sql_time . "<br>";
		$msg .= 'Time difference: '.	human_time_diff( $ebay_time_ts, $sql_time_ts ) . "<!br>";					
		$msg .= ' ( offset: '.	$hours_offset . " )<br>";					
		$this->showMessage( $msg );

		$this->EC->closeEbay();
	}

	public function viewLogfile() {

		echo "<pre>";
		echo readfile( WPLE()->logger->file );
		echo "<br>logfile: " . WPLE()->logger->file . "<br>";
		echo "</pre>";

	}

	public function clearLogfile() {
		file_put_contents( WPLE()->logger->file, '' );
	}

	public function renderSettingsOptions() {
		?>
		<div class="hidden" id="screen-options-wrap" style="display: block;">
			<form method="post" action="" id="dev-settings">
				<h5>Show on screen</h5>
				<div class="metabox-prefs">
						<label for="dev-hide">
							<input type="checkbox" onclick="jQuery('.dev_box').toggle();" value="dev" id="dev-hide" name="dev-hide" class="hide-column-tog">
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
		// wp_register_style('jQueryUITheme', plugins_url( 'css/smoothness/jquery-ui-1.8.22.custom.css' , WPLISTER_PATH.'/wp-lister-ebay.php' ) );
		// wp_enqueue_style('jQueryUITheme'); 

	}

	public function onWpEnqueueScripts() {

		// testing:
		// jQuery UI progressbar
        wp_enqueue_script('jquery-ui-core');
        wp_enqueue_script('jquery-ui-progressbar');

        // only enqueue JobRunner.js on WP-Lister's pages
        if ( ! isset( $_REQUEST['page'] ) ) return;
       	if ( substr( $_REQUEST['page'], 0, 8 ) != 'wplister' ) return;

		// JobRunner
		wp_register_script( 'wpl_JobRunner', self::$PLUGIN_URL.'js/classes/JobRunner.js', array( 'jquery', 'jquery-ui-core', 'jquery-ui-progressbar' ), WPLISTER_VERSION );
		wp_enqueue_script( 'wpl_JobRunner' );

		wp_localize_script('wpl_JobRunner', 'wpl_JobRunner_i18n', array(
				'msg_loading_tasks' 	=> __('fetching list of tasks', 'wplister').'...',
				'msg_estimating_time' 	=> __('estimating time left', 'wplister').'...',
				'msg_finishing_up' 		=> __('finishing up', 'wplister').'...',
				'msg_all_completed' 	=> __('All {0} tasks have been completed.', 'wplister'),
				'msg_processing' 		=> __('processing {0} of {1}', 'wplister'),
				'msg_time_left' 		=> __('about {0} remaining', 'wplister'),
				'footer_dont_close' 	=> __("Please don't close this window until all tasks are completed.", 'wplister')
			)
		);

	    // jQuery UI Dialog
    	// wp_enqueue_style( 'wp-jquery-ui-dialog' );
	    // wp_enqueue_script ( 'jquery-ui-dialog' ); 

	} // onWpEnqueueScripts



	// convert plugin tables to utf8mb4
	// (this should happen automatically on WP4.2, but WordPress only converts utf8 tables and leaves latin1 tables unchanged)
	public function upgradeTablesUTF8MB4() {
		global $wpdb;

		// get list of our tables
		$tables = $wpdb->get_col( "SHOW TABLES LIKE '{$wpdb->prefix}ebay_%'" );
		if ( empty($tables) ) {
			wple_show_message('no tables found.','error');
			return;
		}

		// convert all tables
		foreach ( $tables as $table ) {
			$converted = WPLE_UpgradeHelper::convert_custom_table_to_utf8mb4( $table );
			if ( $converted ) {
				wple_show_message('Table <i>'.$table.'</i> was converted.');
			} else {
				wple_show_message('Table <i>'.$table.'</i> was not converted.','error');
			}
		}

	} // upgradeTablesUTF8MB4()



	// Import WPLA Product IDs
	public function importWplaProductIds() {
		global $wpdb;

		// fetch all UPCs
		$sql      = "SELECT post_id FROM `{$wpdb->prefix}postmeta` WHERE meta_key = '_amazon_id_type' AND meta_value = 'UPC' ";
		$products = $wpdb->get_col($sql);
		$count    = 0;
		foreach ( $products as $post_id ) {
			$upc = get_post_meta( $post_id, '_amazon_product_id', true );
			if ( empty( $upc ) ) continue;
			update_post_meta( $post_id, '_ebay_upc', $upc );
			$count++;
		}
		wple_show_message( $count .' '. 'UPCs were imported.' );

		// fetch all EANs
		$sql      = "SELECT post_id FROM `{$wpdb->prefix}postmeta` WHERE meta_key = '_amazon_id_type' AND meta_value = 'EAN' ";
		$products = $wpdb->get_col($sql);
		$count    = 0;
		foreach ( $products as $post_id ) {
			$ean = get_post_meta( $post_id, '_amazon_product_id', true );
			if ( empty( $ean ) ) continue;
			update_post_meta( $post_id, '_ebay_ean', $ean );
			$count++;
		}
		wple_show_message( $count .' '. 'EANs were imported.' );

		return $count;
	} // importWplaProductIds()



	// create missing COG data eBay orders
	public function fixCostOfGoods() {

		$om = new EbayOrdersModel();
		$orders = $om->getAll();
		// echo "<pre>";print_r($orders);echo"</pre>";#die();

		$updated_orders = 0;

		// loop orders
		foreach ($orders as $order) {

			$post_id = $order['post_id'];
			if ( ! $post_id ) continue;

			// check if order exist - prevent fatal error in WC_COG::set_order_item_cost_meta()
			$_order = wc_get_order( $post_id );
			if ( ! $_order ) continue;

			// skip orders with existing cog data
			if ( get_post_meta( $post_id, '_wc_cog_order_total_cost', true ) ) continue;


			// trigger COG update
			do_action( 'wplister_after_create_order', $post_id );
			// WC_COG::set_order_item_cost_meta( $post_id ); // might work as well...

			$updated_orders++;
		}


		$msg = $updated_orders . ' orders were updated.<br><br>';
		wple_show_message( $msg );

	} // fixCostOfGoods



	// create missing transactions from eBay orders
	public function checkTransactions( $show_message = false ) {

		$om = new EbayOrdersModel();
		$tm = new TransactionsModel();
		$orders = $om->getAll();
		// echo "<pre>";print_r($orders);echo"</pre>";#die();
		$created_transactions = 0;
		$pending_orders = 0;

		// loop orders
		foreach ($orders as $order) {
			
			$order_details = $om->decodeObject( $order['details'], false, true );
			// echo "<pre>";print_r($order_details);echo"</pre>";#die();

			// skip if this order has been processed already
			if ( $tm->getTransactionByEbayOrderID( $order['order_id'] ) )
				continue;

			// limit processing to 500 orders at a time
			if ( $created_transactions >= 500 ) {
				$pending_orders++;				
				continue;
			}

			// loop transactions
			$transactions = $order_details->TransactionArray;
			foreach ($transactions as $Transaction) {

				// echo "<pre>";print_r($Transaction->TransactionID);echo"</pre>";#die();
				// $transaction_id = $Transaction->TransactionID;

				// create transaction
				$txn_id = $tm->createTransactionFromEbayOrder( $order, $Transaction );
				// echo "<pre>created transaction ";print_r($Transaction->TransactionID);echo"</pre>";#die();
				$created_transactions++;
			}

		}

		$msg = $created_transactions . ' transactions were created.<br><br>';
		if ( $pending_orders ) {
			$msg .= 'There are ' . $pending_orders . ' more orders to process. Please run this check again until all orders have been processed.';
		} else {
			$msg .= 'Please visit the <a href="admin.php?page=wplister-transactions">Transactions</a> page to check for duplicates.';
		}
		if ( $show_message ) $this->showMessage( $msg );

		// return number of orders which still need to be processed
		return $pending_orders;
	} // checkTransactions



	public function upscaleImage( $image_file ) {

		$upload_dir = wp_upload_dir();
		$image_path = $upload_dir['basedir'] .'/'. $image_file;

		$image = wp_get_image_editor( $image_path ); // Return an implementation that extends <tt>WP_Image_Editor</tt>

		if ( ! is_wp_error( $image ) ) {

			$size = $image->get_size();
			// echo "<pre>";print_r($size);echo"</pre>";#die();

			// resize() was tweaked to allow upscaling
		    $image->resize( 500, 500, false );
		    $result = $image->save( $image_path );
			// echo "<pre>";print_r($result);echo"</pre>";#die();

			$size = $image->get_size();
			// echo "<pre>";print_r($size);echo"</pre>";#die();

			return $size;

		} else {
			echo "<pre>";print_r($image);echo"</pre>";#die();
			return false;
		}

	}

	// allow resize() to upscale images
	public function filter_image_resize_dimensions($default, $orig_w, $orig_h, $dest_w, $dest_h, $crop) {
	    if ( $crop ) return null; // let the wordpress default function handle this

        // don't crop, just resize using $dest_w x $dest_h as a maximum bounding box
        $crop_w = $orig_w;
        $crop_h = $orig_h;

        $s_x = 0;
        $s_y = 0;

        // note the use of wp_expand_dimensions() instead of wp_constrain_dimensions()
        list( $new_w, $new_h ) = wp_expand_dimensions( $orig_w, $orig_h, $dest_w, $dest_h );

        // the return array matches the parameters to imagecopyresampled()
	    return array( 0, 0, (int) $s_x, (int) $s_y, (int) $new_w, (int) $new_h, (int) $crop_w, (int) $crop_h );

	} // filter image_resize_dimensions 

	public function checkProductImages() {

		// get all listings
		$listings       = WPLE_ListingQueryHelper::getAll();
		$found_images   = array();
		$found_products = array();

		// allow WP to upscale images
		if ( isset( $_REQUEST['resize_images'] ) ) {
			add_filter('image_resize_dimensions', array( $this, 'filter_image_resize_dimensions' ), 10, 6);
		}


		// process published listings
		foreach ( $listings as $item ) {

			// get featured image id
			$post_id = $item['post_id'];
			$attachment_id = get_post_thumbnail_id( $post_id );
			if ( ! $attachment_id ) continue;

			// $image_attributes = wp_get_attachment_image_src( $attachment_id, 'full' ); 
			// $img_width  = $image_attributes[1];
			// $img_height = $image_attributes[2];

			// get attachment meta data
			$meta = wp_get_attachment_metadata( $attachment_id ); 
			if ( empty ( $meta ) ) continue;
			// echo "<pre>";print_r($meta);echo"</pre>";#die();

			// check if at least one side is 500px or longer
			if ( ( $meta['width'] >= 500 ) || ( $meta['height'] >= 500 ) )
				continue;

			// echo "<pre>";print_r($attachment_id);echo"</pre>";#die();

			// resize image
			if ( isset( $_REQUEST['resize_images'] ) ) {
				$size = $this->upscaleImage( $meta['file'] );
				if ( $size ) {

					// update attachment meta sizes
					// echo "<pre>new size: ";print_r($size);echo"</pre>";#die();
					$meta['width']  = $size['width'];
					$meta['height'] = $size['height'];
					// echo wp_update_attachment_metadata( $post_id, $meta );
					update_post_meta( $attachment_id, '_wp_attachment_metadata', $meta );

					// clear EPS cache for listing item
					ListingsModel::updateListing( $item['id'], array( 'eps' => NULL ) );

					$this->showMessage( sprintf('Resized image <code>%s</code> to %s x %s.', $meta['file'], $meta['width'], $meta['height'] ) );
					continue;					
				}
			}

			// get image url
			$image_attributes    = wp_get_attachment_image_src( $attachment_id, 'full' ); 
			$meta['url']         = $image_attributes[0];

			$meta['post_id']     = $post_id;
			$meta['ebay_id']     = $item['ebay_id'];
			$meta['ViewItemURL'] = $item['ViewItemURL'];

			// add to list of found images
			$found_images[ $attachment_id ] = $meta;

		}
		// echo "<pre>";print_r($found_images);echo"</pre>";

		// return if empty
		if ( empty( $found_images ) ) {
			$this->showMessage('All images are okay.');
			return;			
		}


		$msg = '<p>';
		$msg .= 'Warning: Some product images do not meet the requirements.';
		$msg .= '</p>';

		// table header
		$msg .= '<table style="width:100%">';
		$msg .= "<tr>";
		$msg .= "<th style='text-align:left'>Width</th>";
		$msg .= "<th style='text-align:left'>Height</th>";
		$msg .= "<th style='text-align:left'>File</th>";
		$msg .= "<th style='text-align:left'>Product</th>";
		$msg .= "<th style='text-align:left'>eBay ID</th>";
		$msg .= "<th style='text-align:left'>ID</th>";
		$msg .= "</tr>";

		// table rows
		foreach ( $found_images as $attachment_id => $item ) {

			// get column data
			$post_id = $item['post_id'];
			$ebay_id = $item['ebay_id'];
			$width   = $item['width'];
			$height  = $item['height'];
			$file    = $item['file'];
			$url     = $item['url'];
			$title   = get_the_title( $item['post_id'] );

			// build links
			$ebay_url = $item['ViewItemURL'] ? $item['ViewItemURL'] : $ebay_url = 'http://www.ebay.com/itm/'.$ebay_id;
			$ebay_link = '<a href="'.$ebay_url.'" target="_blank">'.$ebay_id.'</a>';
			$edit_link = '<a href="post.php?action=edit&post='.$post_id.'" target="_blank">'.$title.'</a>';
			$file_link = '<a href="'.$url.'" target="_blank">'.$file.'</a>';

			// build table row
			$msg .= "<tr>";
			$msg .= "<td>$width</td>";
			$msg .= "<td>$height</td>";
			$msg .= "<td>$file_link</td>";
			$msg .= "<td>$edit_link (ID $post_id)</td>";
			$msg .= "<td>$ebay_link</td>";
			$msg .= "<td>$attachment_id</td>";
			$msg .= "</tr>";
		}
		$msg .= '</table>';


		$msg .= '<p>';
		$url = 'admin.php?page=wplister-tools&action=check_ebay_image_requirements&resize_images=yes&_wpnonce='.wp_create_nonce('e2e_tools_page');
		$msg .= '<a href="'.$url.'" class="button">'.__('Resize all','wplister').'</a> &nbsp; ';
		$msg .= 'Click this button to upscale all found images to 500px.';
		$msg .= '</p>';

		$this->showMessage( $msg, 1 );


	} // checkProductImages()



	public function sendCurlRequest( $url, $usePost = false ) {


		// Setup cURL Session
		$cURLhandle = curl_init() ;
		curl_setopt($cURLhandle, CURLOPT_URL, $url ) ;
		// curl_setopt($cURLhandle, CURLOPT_FOLLOWLOCATION, TRUE) ;
		curl_setopt($cURLhandle, CURLOPT_MAXREDIRS, 5 ) ;
		// curl_setopt($cURLhandle, CURLOPT_USERAGENT, $c_cURLopt_UserAgent) ;
		curl_setopt($cURLhandle, CURLOPT_NOBODY, FALSE) ;
		curl_setopt($cURLhandle, CURLOPT_POST, $usePost) ;
		curl_setopt($cURLhandle, CURLOPT_SSL_VERIFYPEER, FALSE) ;
		curl_setopt($cURLhandle, CURLOPT_SSL_VERIFYHOST, 0) ;
		// curl_setopt($cURLhandle, CURLOPT_MAXCONNECTS, 10) ;
		curl_setopt($cURLhandle, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1) ;
		// curl_setopt($cURLhandle, CURLOPT_CLOSEPOLICY, CURLCLOSEPOLICY_LEAST_RECENTLY_USED) ;
		curl_setopt($cURLhandle, CURLOPT_TIMEOUT, 10 ) ;
		curl_setopt($cURLhandle, CURLOPT_CONNECTTIMEOUT, 5 ) ;
		// curl_setopt($cURLhandle, CURLOPT_FAILONERROR, TRUE); // there w
		// curl_setopt($cURLhandle, CURLOPT_HTTPHEADER, $In_Headers) ;
		if ($usePost) {
			curl_setopt($cURLhandle, CURLOPT_POSTFIELDS, $In_POST) ;
		}
		curl_setopt($cURLhandle, CURLOPT_HEADER, FALSE) ;
		curl_setopt($cURLhandle, CURLOPT_VERBOSE, FALSE) ;
		curl_setopt($cURLhandle, CURLOPT_RETURNTRANSFER, TRUE) ;

		// only enable CURLOPT_FOLLOWLOCATION if safe_mode and open_base_dir are not in use
        if ( ini_get('open_basedir') == '' && ! ini_get('safe_mode') )
			curl_setopt($cURLhandle, CURLOPT_FOLLOWLOCATION, TRUE);

		// force SSLv3 - prevent SSL23_GET_SERVER_HELLO:unknown protocol error (?)
		// curl_setopt($cURLhandle, CURLOPT_SSLVERSION, 3);

		// Make cURL Call
		$cURLresponse_data        = curl_exec($cURLhandle) ;
		$cURLresponse_errorNumber = curl_errno($cURLhandle) ;

		// in case XML response has leading junk characters, or no XML declaration...
		// $cURLresponse_data = stristr($cURLresponse_data,"<?xml") ;



		// Acquire More Info About Last cURL Call
		$cURLresponse_errorString    = curl_error($cURLhandle) ;
		$cURLresponse_info           = curl_getinfo($cURLhandle) ;
		$cURLresponse_info_HTTPcode  = (string) ((isset($cURLresponse_info["http_code"])) ? ($cURLresponse_info["http_code"]) : ("")) ;
		$cURLresponse_info_TotalTime = (string) ((isset($cURLresponse_info["total_time"])) ? ($cURLresponse_info["total_time"]) : ("")) ;
		$cURLresponse_info_DLsize    = (string) ((isset($cURLresponse_info["size_download"])) ? ($cURLresponse_info["size_download"]) : ("")) ;

		// Close cURL Session
		curl_close($cURLhandle) ;


		$result = array();
		$result['body']     	= $cURLresponse_data ;
		$result['error_number'] = $cURLresponse_errorNumber ;
		$result['error_string'] = $cURLresponse_errorString ;
		$result['httpcode']     = $cURLresponse_info_HTTPcode ;
		$result['total_time']   = $cURLresponse_info_TotalTime ;
		$result['dlsize']       = $cURLresponse_info_DLsize ;
		$result['post']         = $usePost ;

        if ( $this->debug )	$this->showMessage( '<b>CURL returned:</b><pre>' . htmlspecialchars($cURLresponse_data).'</pre>' );
        if ( $this->debug )	$this->showMessage( '<b>CURL request details:</b><pre>' . htmlspecialchars(print_r($cURLresponse_data,1)).'</pre>' );
		// echo "<pre>";print_r($result);echo"</pre>";#die();

		return $result;

	}

	public function sendWpRequest( $url, $usePost = false ) {
	}


	public function checkPaypalConnection() {

		$url = 'https://www.paypal.com/cgi-bin/webscr';
		$response = wp_remote_get( $url );

		if ( ! is_wp_error( $response ) && $response['response']['code'] >= 200 && $response['response']['code'] < 300 ) {
    		$this->showMessage('Connection to paypal.com established' );
    		$success = true;
    	} elseif ( is_wp_error( $response ) ) {
    		$this->showMessage( 'wp_remote_post() failed. WP-Lister won\'t work with your server. Contact your hosting provider. Error:', 'woocommerce' ) . ' ' . $response->get_error_message();
    		$success = false;
    	} else {
        	$this->showMessage( 'wp_remote_post() failed. WP-Lister may not work with your server.' );
            $this->showMessage( 'HTTP status code: ' . wp_remote_retrieve_response_code( $response ) );
    		$success = false;
    	}

    	return $success;
	}


	public function addLogMessage( $msg, $success = true, $details = false ) {

		if ( $success ) {
			$this->resultsHtml .= $this->icon_success;
		} else {
			$this->resultsHtml .= $this->icon_error;
		}

		if ( $details ) {
			$details = '<div class="details">'.$details.'</div>';
		}

		$this->resultsHtml .= $msg.'<br>'.$details;

	}


	public function checkUrl( $url, $display_url, $expected_http_code = 200, $match_content = false, $use_curl = false ) {

		// set user agent - paypal.com return 403 for default WP user agent
		$args = array(
		    'timeout'     => 5,
		    'redirection' => 5,
		    // 'user-agent'  => 'WordPress/' . $wp_version . '; ' . get_bloginfo( 'url' ),
		    'user-agent'  => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_10_4) AppleWebKit/600.7.12 (KHTML, like Gecko) Version/8.0.7 Safari/600.7.12',
		    'sslverify'   => true,
		);

		// wp_remote_get()
		$response = wp_remote_get( $url, $args );
        $body = is_array( $response ) ? $response['body'] : '';

		if ( ! is_wp_error( $response ) && $response['response']['code'] == $expected_http_code ) {
    		$this->addLogMessage( 'Connection to '.$display_url.' established' );
    		$success = true;
    	} elseif ( is_wp_error( $response ) ) {
    		$details  = 'wp_remote_get() failed to connect to ' . $url . '<br>';
    		$details .= 'Error:' . ' ' . $response->get_error_message() . '<br>';
    		// $details .= 'Please contact your hosting provider.<br>';
    		$this->addLogMessage( 'Connection to '.$display_url.' failed', false, $details );
    		$success = false;
    	} else {
    		$details  = 'wp_remote_get() returned an unexpected HTTP status code: ' . wp_remote_retrieve_response_code( $response );
    		$details .= '<br>url: ' . $url;
    		$this->addLogMessage( 'Connection to '.$display_url.' failed', false, $details );
    		$success = false;
    	}

        // show raw result (if debug enabled)
		if ( $this->debug )	$this->showMessage( '<b>returned content:</b><pre>' . htmlspecialchars($body).'</pre>' );

    	// should we check the response as well?
    	if ( ! $success || ! $match_content ) return $success;

    	if ( ! strpos( $body, $match_content ) ) {
    		$details  = 'Failed to match the servers response.';
    		$this->addLogMessage( 'Connection to '.$display_url.' failed', false, $details );
    		$success = false;    		
    	}

    	return $success;

	}


	public function runEbayChecks() {

        // first check with cURL
		$url = 'https://api.ebay.com/wsapi';
        $response = $this->sendCurlRequest( $url );
		if ( $response['httpcode'] == 200 ) {
			$this->results->successEbay_curl = true;
			$this->addLogMessage( 'Connection to api.ebay.com established via cURL' );
		} else {
			$this->results->successEbay_curl = false;
            $this->addLogMessage( 'Failed to contact api.ebay.com via cURL.', false, 'Error: '. $response['error_string'] );
		}

		// try calling eBay API without parameters
		// should return an Error 37 "Input data is invalid" and "SOAP Authentication failed"
		$url = 'https://api.ebay.com/wsapi?callname=GeteBayOfficialTime&siteid=0';
		$this->results->successEbay_1 = $this->checkUrl( $url, 'eBay API', 500, '<ns1:ErrorCode>37</ns1:ErrorCode>' );
		if ( $this->results->successEbay_1 ) return true;

		// alternative url #1
		$url = 'https://api.ebay.com/wsapi';
		$this->results->successEbay_2 = $this->checkUrl( $url, 'eBay API (base)', 200 );		
		// if ( $this->results->successEbay_2 ) return false;

		// alternative url #2
		$url = 'https://api.ebay.com/';
		$this->results->successEbay_3 = $this->checkUrl( $url, 'eBay API (root)', 202 );

		// ebay web site
		$url = 'http://www.ebay.com/';
		$this->results->successEbay_4 = $this->checkUrl( $url, 'eBay (www.ebay.com)', 200 );

		return false;
	}


	public function checkEbayConnection() {

		if ( isset($_GET['debug']) ) $this->debug = true;
		$this->icon_success = '<img src="'.WPLISTER_URL.'img/icon-success.png" class="inline_status" />';
		$this->icon_error   = '<img src="'.WPLISTER_URL.'img/icon-error.png"   class="inline_status" />';
		$this->results  	= new stdClass();

		// $this->checkPaypalConnection();
		$this->runEbayChecks();


		// try PayPal
		// $url = 'https://www.paypal.com/cgi-bin/webscr';
		$url = 'https://www.paypal.com/';
		$this->results->successPaypal = $this->checkUrl( $url, 'PayPal' );

		// try wordpress.org
		$url = 'https://www.wordpress.org/';
		$this->results->successWordPress = $this->checkUrl( $url, 'WordPress.org' );

		// try PayPal
		// if ( ! $this->results->successWordPress ) {
		// 	$url = 'https://www.paypal.com/cgi-bin/webscr';
		// 	$this->results->successPaypal = $this->checkUrl( $url, 'PayPal' );
		// }

		// try update.wplab.com
		$url = 'http://update.wplab.de/api/';
		$this->results->successWplabApi = $this->checkUrl( $url, 'WP Lab update server' );

		// try wplab.com
		if ( ! $this->results->successWplabApi ) {
			$url = 'http://www.wplab.com/';
			$this->results->successWplabWeb = $this->checkUrl( $url, 'WP Lab web server' );
		}

        // now the same with cURL
        // $response = $this->sendCurlRequest( $url );

		// if ( $response['httpcode'] == 200 ) {
		// 	$this->showMessage( 'Connection to api.ebay.com established (curl)' );
		// }

		// $body = $response['body'];
		// if ( preg_match("/<ns1:ErrorCode>(.*)<\/ns1:ErrorCode>/", $body, $matches) ) {
            // $this->showMessage( $this->icon_success.'Connection to api.ebay.com established (curl)' );
		// } else {
            // $this->showMessage( 'Error while contacting api.ebay.com via cURL: ' . $response['error_string'], 1 );
		// }

		// call GetApiAccessRules
		$this->initEC();
		$result = $this->EC->GetApiAccessRules();
		$this->EC->closeEbay();

	} // checkEbayConnection()

} // class ToolsPage
