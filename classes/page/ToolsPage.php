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
		add_screen_options_panel('wplister_setting_options', '', array( &$this, 'renderSettingsOptions'), $this->main_admin_menu_slug.'_page_wplister-tools' );

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

            // global $wpdb;
            // $wpdb->query("update wp_options set option_value='' where option_name='_site_transient_update_plugins'");
            set_site_transient('update_plugins', null);

			$this->showMessage( 
				'<big>'. __('Check for updates was initiated.','wplister') . '</big><br><br>'
				. __('You can visit your WordPress Updates now.','wplister') . '<br><br>'
				. __('Since the updater runs in the background, it might take a little while before new updates appear.','wplister') . '<br><br>'
				. '<a href="update-core.php" class="button-primary">'.__('view updates','wplister') . '</a>'
			);
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

				// check_wc_out_of_stock
				if ( $_REQUEST['action'] == 'check_wc_out_of_stock') {				
					$this->checkProductStock();
				}

				// check_ebay_image_requirements
				if ( $_REQUEST['action'] == 'check_ebay_image_requirements') {				
					$this->checkProductImages();
				}

				// check_missing_ebay_transactions
				if ( $_REQUEST['action'] == 'check_missing_ebay_transactions') {				
					$this->checkTransactions( true );
				}

				// check_wc_out_of_sync
				if ( $_REQUEST['action'] == 'check_wc_out_of_sync') {				
					$this->checkProductInventory();
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
				}
				// SetNotificationPreferences
				if ( $_REQUEST['action'] == 'SetNotificationPreferences') {				
					$this->initEC();
					$debug = $this->EC->SetNotificationPreferences();
					$this->EC->closeEbay();
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
					$msg .= '<a href="#" onclick="jQuery(\'#ebay_order_report\').toggle();return false;">'.__('show details','wplister').'</a>';
					$msg .= $om->getHtmlReport();
					$this->showMessage( $msg );
				}
	
	
			} else {
				die ('not allowed');
			}

		} // if $_REQUEST['action']

	} // handleActions()
	

	public function onDisplayToolsPage() {
		global $wpl_logger;
		WPL_Setup::checkSetup();

		$this->handleActions();

		$aData = array(
			'plugin_url'				=> self::$PLUGIN_URL,
			'message'					=> $this->message,		
			'results'					=> isset($this->results) ? $this->results : '',
			'resultsHtml'				=> isset($this->resultsHtml) ? $this->resultsHtml : '',
			'debug'						=> isset($debug) ? $debug : '',
			'log_size'					=> file_exists($wpl_logger->file) ? filesize($wpl_logger->file) : '',
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
		global $wpl_logger;

		echo "<pre>";
		echo readfile( $wpl_logger->file );
		echo "<br>logfile: " . $wpl_logger->file . "<br>";
		echo "</pre>";

	}

	public function clearLogfile() {
		global $wpl_logger;
		file_put_contents( $wpl_logger->file, '' );
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
		$lm = new ListingsModel();
		$listings = $lm->getAll();
		$found_images = array();
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
					$lm->updateListing( $item['id'], array( 'eps' => NULL ) );

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

	public function checkProductStock() {

		// get all published listings
		$lm = new ListingsModel();
		$listings = $lm->getAllPublished();
		$out_of_stock_products = array();

		// process published listings
		foreach ( $listings as $item ) {

			// check stock level
			$stock = ProductWrapper::getStock( $item['post_id'] );
			if ( $stock > 0 )
				continue;

			// check wc product
			$_product = ProductWrapper::getProduct( $item['post_id'] );
			// echo "<pre>";print_r($_product);echo"</pre>";die();

			// mark listing as changed
			if ( isset( $_REQUEST['mark_as_changed'] ) ) {
				$lm->updateListing( $item['id'], array( 'status' => 'changed' ) );
				$item['status'] = 'changed';
			}

			// add to list of out of stock products
			$item['stock']  = $stock;
			$item['exists'] = $_product ? true : false;
			$out_of_stock_products[] = $item;

		}

		// return if empty
		if ( empty( $out_of_stock_products ) ) {
			$this->showMessage('No out of stock products found.');
			return;			
		}

		$msg = '<p>';
		$msg .= 'Warning: Some published listings are out of stock or missing in WooCommerce.';
		$msg .= '</p>';

		// table header
		$msg .= '<table style="width:100%">';
		$msg .= "<tr>";
		$msg .= "<th style='text-align:left'>Stock</th>";
		$msg .= "<th style='text-align:left'>Product</th>";
		$msg .= "<th style='text-align:left'>Qty</th>";
		$msg .= "<th style='text-align:left'>eBay ID</th>";
		$msg .= "<th style='text-align:left'>Status</th>";
		$msg .= "</tr>";

		// table rows
		foreach ( $out_of_stock_products as $item ) {

			// get column data
			$qty     = $item['quantity'];
			$stock   = $item['stock'] . ' x ';
			$title   = $item['auction_title'];
			$post_id = $item['post_id'];
			$ebay_id = $item['ebay_id'];
			$status  = $item['status'];
			$exists  = $item['exists'];

			// build links
			$ebay_url = $item['ViewItemURL'] ? $item['ViewItemURL'] : $ebay_url = 'http://www.ebay.com/itm/'.$ebay_id;
			$ebay_link = '<a href="'.$ebay_url.'" target="_blank">'.$ebay_id.'</a>';
			$edit_link = '<a href="post.php?action=edit&post='.$post_id.'" target="_blank">'.$title.'</a>';

			// mark non existent products
			if ( ! $exists ) {
				$stock    = 'N/A';
				$post_id .= ' missing!';
			}

			// build table row
			$msg .= "<tr>";
			$msg .= "<td>$stock</td>";
			$msg .= "<td>$edit_link (ID $post_id)</td>";
			$msg .= "<td>$qty x </td>";
			$msg .= "<td>$ebay_link</td>";
			$msg .= "<td>$status</td>";
			$msg .= "</tr>";
		}
		$msg .= '</table>';


		$msg .= '<p>';
		$url = 'admin.php?page=wplister-tools&action=check_wc_out_of_stock&mark_as_changed=yes&_wpnonce='.wp_create_nonce('e2e_tools_page');
		$msg .= '<a href="'.$url.'" class="button">'.__('Mark all as changed','wplister').'</a> &nbsp; ';
		$msg .= 'Click this button to mark all found listings as changed in WP-Lister, then revise all changed listings.';
		$msg .= '</p>';

		$this->showMessage( $msg, 1 );


	} // checkProductStock()


	public function checkProductInventory() {

		// get all published listings
		$lm = new ListingsModel();
		$listings = $lm->getAllPublished();
		$out_of_sync_products = array();
		$published_count = 0;

		// process published listings
		foreach ( $listings as $item ) {

			// check wc product
			$post_id = $item['post_id'];
			$_product = ProductWrapper::getProduct( $post_id );
			// echo "<pre>";print_r($_product);echo"</pre>";die();


			// get stock level and price
			$stock = ProductWrapper::getStock( $item['post_id'] );
			$price = ProductWrapper::getPrice( $item['post_id'] );
			// echo "<pre>";print_r($price);echo"</pre>";#die();
			// echo "<pre>";print_r($item);echo"</pre>";die();

			// apply profile settings to stock level
			$profile_data    = $lm->decodeObject( $item['profile_data'], true );
			$profile_details = $profile_data['details'];
			$item['qty']     = $item['quantity'] - $item['quantity_sold'];
			// echo "<pre>";print_r($profile_details);echo"</pre>";#die();

	        // apply max_quantity from profile
    	    $max_quantity = ( isset( $profile_details['max_quantity'] ) && intval( $profile_details['max_quantity'] )  > 0 ) ? $profile_details['max_quantity'] : false ; 
    	    if ( $max_quantity )
    	    	$stock = min( $max_quantity, intval( $stock ) );


			// check if product has variations
			if ( $_product ) {
				$variations = $_product->product_type == 'variable' ? ProductWrapper::getVariations( $item['post_id'] ) : array();
			} else {
				$variations = array();
			}

			// get total stock for all variations
			if ( ! empty( $variations ) ) {

				// reset prices and stock
				$stock          = 0;
				$price_min      = PHP_INT_MAX;
				$price_max      = 0;
				$ebay_stock     = 0;
				$ebay_price_min = PHP_INT_MAX;
				$ebay_price_max = 0;

				// check WooCommerce variations
				foreach ($variations as $var) {

					// total stock
		    	    if ( $max_quantity )
		    	    	$stock += min( $max_quantity, intval( $var['stock'] ) );
		    	    else 
						$stock += $var['stock'];

					// min / max prices
					$price_min = min( $price_min, $var['price'] );
					$price_max = max( $price_max, $var['price'] );

				}

				// check eBay variations
		        $cached_variations = maybe_unserialize( $item['variations'] );
				foreach ($cached_variations as $var) {
					$ebay_stock    += $var['stock'];
					$ebay_price_min = min( $ebay_price_min, $var['price'] );
					$ebay_price_max = max( $ebay_price_max, $var['price'] );
				}

				// set default values
				$price             = $price;
				$item['qty']       = $ebay_stock;
				$item['price']     = $ebay_price_min;
				$item['price_max'] = $ebay_price_max;
				// echo "<pre>";print_r($cached_variations);echo"</pre>";die();
			}


			// check if product and ebay listing are in sync
			$in_sync = true;

			// check stock level
			if ( $stock != $item['qty'] )
				$in_sync = false;

			// check price
			if ( round( $price, 2 ) != round( $item['price'], 2 ) )
				$in_sync = false;

			// check max price
			if ( isset( $price_max ) && isset( $item['price_max'] ) && ( round( $price_max, 2 ) != round ( $item['price_max'], 2 ) ) )
				$in_sync = false;

			// if in sync, continue with next item
			if ( $in_sync )
				continue;


			// mark listing as changed 
			if ( isset( $_REQUEST['mark_as_changed'] ) ) {

				if ( $_product ) {
					// only existing products can have a profile re-applied
					$lm->markItemAsModified( $item['post_id'] );
				}

				// in case the product is locked or missing, force the listing to be changed
				$lm->updateListing( $item['id'], array( 'status' => 'changed' ) );

				$item['status'] = 'changed';
			}


			// add to list of out of sync products
			$item['price_woo']      = $price;
			$item['price_woo_max']  = isset( $price_max ) ? $price_max : false;
			$item['stock']          = $stock;
			$item['exists']         = $_product ? true : false;
			$item['type']           = $_product ? $_product->product_type : 'missing';
			$out_of_sync_products[] = $item;

			// count products which have not yet been marked as changed
			if ( $item['status'] == 'published' ) $published_count += 1;
		}

		// return if empty
		if ( empty( $out_of_sync_products ) ) {
			$this->showMessage('All published listings seem to be in sync.');
			return;			
		}

		$msg = '<p>';
		$msg .= 'Warning: '.sizeof($out_of_sync_products).' published listings are out of sync or missing in WooCommerce.';
		$msg .= '</p>';

		// table header
		$msg .= '<table style="width:100%">';
		$msg .= "<tr>";
		$msg .= "<th style='text-align:left'>Product</th>";
		$msg .= "<th style='text-align:left'>Local Qty</th>";
		$msg .= "<th style='text-align:left'>eBay Qty</th>";
		$msg .= "<th style='text-align:left'>Local Price</th>";
		$msg .= "<th style='text-align:left'>eBay Price</th>";
		$msg .= "<th style='text-align:left'>eBay ID</th>";
		$msg .= "<th style='text-align:left'>Status</th>";
		$msg .= "</tr>";

		// table rows
		foreach ( $out_of_sync_products as $item ) {

			// get column data
			$qty          = $item['qty'];
			$stock        = $item['stock'];
			$title        = $item['auction_title'];
			$post_id      = $item['post_id'];
			$ebay_id      = $item['ebay_id'];
			$status       = $item['status'];
			$exists       = $item['exists'];
			$locked       = $item['locked'] ? 'locked' : '';
			$price        = woocommerce_price( $item['price'] );
			$price_woo    = woocommerce_price( $item['price_woo'] );
			$product_type = $item['type'] == 'simple' ? '' : $item['type'];

			// highlight changed values
			$changed_stock     =   intval( $item['qty']   )     ==   intval( $item['stock']     )     ? false : true;
			$changed_price     = floatval( $item['price'] )     == floatval( $item['price_woo'] )     ? false : true;
			$changed_price_max = floatval( $item['price_max'] ) == floatval( $item['price_woo_max'] ) ? false : true;
			$stock_css         = $changed_stock                       ? 'color:darkred;' : '';
			$price_css         = $changed_price || $changed_price_max ? 'color:darkred;' : '';

			// build links
			$ebay_url = $item['ViewItemURL'] ? $item['ViewItemURL'] : $ebay_url = 'http://www.ebay.com/itm/'.$ebay_id;
			$ebay_link = '<a href="'.$ebay_url.'" target="_blank">'.$ebay_id.'</a>';
			$edit_link = '<a href="post.php?action=edit&post='.$post_id.'" target="_blank">'.$title.'</a>';

			// mark non existent products
			if ( ! $exists ) {
				$stock    = 'N/A';
				$post_id .= ' missing!';
			}

			// show price range for variations
			if ( $item['price_woo_max'] )
				$price_woo .= ' - '.woocommerce_price( $item['price_woo_max'] );
			if ( $item['price_max'] )
				$price .= ' - '.woocommerce_price( $item['price_max'] );

			// build table row
			$msg .= "<tr>";
			$msg .= "<td>$edit_link <span style='color:silver'>$locked $product_type (#$post_id)</span></td>";
			$msg .= "<td style='$stock_css'>$stock</td>";
			$msg .= "<td style='$stock_css'>$qty</td>";
			$msg .= "<td style='$price_css'>$price_woo</td>";
			$msg .= "<td style='$price_css'>$price</td>";
			$msg .= "<td>$ebay_link</td>";
			$msg .= "<td>$status</td>";
			$msg .= "</tr>";
		}
		$msg .= '</table>';

		// buttons
		$msg .= '<p>';

		// show 'check again' button
		$url  = 'admin.php?page=wplister-tools&action=check_wc_out_of_sync&_wpnonce='.wp_create_nonce('e2e_tools_page');
		$msg .= '<a href="'.$url.'" class="button">'.__('Check again','wplister').'</a> &nbsp; ';

		// show 'mark all as changed' button
		if ( $published_count ) {
			$url = 'admin.php?page=wplister-tools&action=check_wc_out_of_sync&mark_as_changed=yes&_wpnonce='.wp_create_nonce('e2e_tools_page');
			$msg .= '<a href="'.$url.'" class="button">'.__('Mark all as changed','wplister').'</a> &nbsp; ';
			$msg .= 'Click this button to mark all found listings as changed in WP-Lister, then revise all changed listings.';
		} else {
			$msg .= '<a id="btn_revise_all_changed_items_reminder" class="btn_revise_all_changed_items_reminder button wpl_job_button">' . __('Revise all changed items','wplister') . '</a>';
			$msg .= ' &nbsp; ';
			// $msg .= 'Click to revise all changed items. If there are still unsynced items after revising, you might have to reapply the listing profile.';
			$msg .= 'Click to revise all changed items.';
		}
		$msg .= '</p>';		

		$this->showMessage( $msg, 1 );


	} // checkProductInventory()


	public function sendCurlRequest( $url, $usePost = false ) {


		// Setup cURL Session
		$cURLhandle = curl_init() ;
		curl_setopt($cURLhandle, CURLOPT_URL, $url ) ;
		curl_setopt($cURLhandle, CURLOPT_FOLLOWLOCATION, TRUE) ;
		curl_setopt($cURLhandle, CURLOPT_MAXREDIRS, 5 ) ;
		//    curl_setopt($cURLhandle, CURLOPT_USERAGENT, $c_cURLopt_UserAgent) ;
		curl_setopt($cURLhandle, CURLOPT_NOBODY, FALSE) ;
		curl_setopt($cURLhandle, CURLOPT_POST, $usePost) ;
		curl_setopt($cURLhandle, CURLOPT_SSL_VERIFYPEER, FALSE) ;
		curl_setopt($cURLhandle, CURLOPT_SSL_VERIFYHOST, 0) ;
		// curl_setopt($cURLhandle, CURLOPT_MAXCONNECTS, 10) ;
		curl_setopt($cURLhandle, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1) ;
		curl_setopt($cURLhandle, CURLOPT_CLOSEPOLICY, CURLCLOSEPOLICY_LEAST_RECENTLY_USED) ;
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

		// wp_remote_get()
		$response = wp_remote_get( $url );
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
		global $wpl_logger;

		if ( isset($_GET['debug']) ) $this->debug = true;
		$this->icon_success = '<img src="'.WPLISTER_URL.'img/icon-success.png" class="inline_status" />';
		$this->icon_error   = '<img src="'.WPLISTER_URL.'img/icon-error.png"   class="inline_status" />';
		$this->results  	= new stdClass();

		// $this->checkPaypalConnection();
		$this->runEbayChecks();


		// try PayPal
		$url = 'https://www.paypal.com/cgi-bin/webscr';
		$this->results->successPaypal = $this->checkUrl( $url, 'PayPal' );

		// try wordpress.org
		$url = 'http://www.wordpress.org/';
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
