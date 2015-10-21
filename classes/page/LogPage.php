<?php
/**
 * LogPage class
 * 
 */

class LogPage extends WPL_Page {

	const slug = 'log';

	public function onWpInit() {
		// parent::onWpInit();

		// Add custom screen options
		// add_action( "load-wp-lister_page_wplister-".self::slug, array( &$this, 'addScreenOptions' ) );
		$load_action = "load-".$this->main_admin_menu_slug."_page_wplister-".self::slug;
		add_action( $load_action, array( &$this, 'addScreenOptions' ) );

	}

	public function onWpAdminMenu() {
		parent::onWpAdminMenu();

		if ( current_user_can('manage_ebay_options') && ( self::getOption( 'log_to_db' ) == '1' ) ) {
			add_submenu_page( self::ParentMenuId, $this->getSubmenuPageTitle( 'Logs' ), __('Logs','wplister'), 
							  self::ParentPermissions, $this->getSubmenuId( 'log' ), array( &$this, 'onDisplayLogPage' ) );
		}
	}

	public function handleSubmit() {
        WPLE()->logger->debug("handleSubmit()");

		if ( $this->requestAction() == 'display_log_entry' ) {
			$this->displayLogEntry( $_REQUEST['log_id'] );
			exit();
		}

		// handle delete action
		if ( $this->requestAction() == 'delete' ) {
			$log_ids = @$_REQUEST['log'];
			if ( is_array($log_ids)) {
				foreach ($log_ids as $id) {
					$this->deleteLogEntry( $id );
				}
				$this->showMessage( __('Selected items were removed.','wplister') );
			}
		}

		if ( $this->requestAction() == 'wpl_clear_ebay_log' ) {
			$this->clearLog();
			$this->showMessage( __('Database log has been cleared.','wplister') );
		}
		if ( $this->requestAction() == 'wpl_optimize_ebay_log' ) {
			$count = $this->optimizeLog();
			$this->showMessage( $count . ' ' . __('expired records have been removed and the database table has been optimized.','wplister') );
		}

	}

	function addScreenOptions() {
		$option = 'per_page';
		$args = array(
	    	'label' => 'Log entries',
	        'default' => 20,
	        'option' => 'logs_per_page'
	        );
		add_screen_option( $option, $args );
		$this->logsTable = new LogTable();

	    // add_thickbox();
		wp_enqueue_script( 'thickbox' );
		wp_enqueue_style( 'thickbox' );
	}
	

	public function onDisplayLogPage() {

		// get all items
		#$logs = $logModel->getAll();

	    //Create an instance of LogTable
	    $logTable = new LogTable();
	    $logTable->prepare_items();

	    // parse errors - tmp var $items required for php 5.4
	    $items = $logTable->items;
	    foreach ( $items as & $item ) {
    		$item['errors'] = $this->parseErrors( $item );
	    }
	    $logTable->items = $items;

		$aData = array(
			'plugin_url'				=> self::$PLUGIN_URL,
			'message'					=> $this->message,

			// 'logs'						=> $logs,
			'logTable'					=> $logTable,
			'tableSize'					=> $this->getTableSize(),
	
			'form_action'				=> 'admin.php?page='.self::ParentMenuId.'-log'
		);
		$this->display( 'log_page', $aData );
	}


	public function parseErrors( $item ) {

		// successful requests have no errors
    	if ( $item['success'] == 'Success' ) return array();

		// check for errors and warnings
		$response = $item['response'];
		$errors   = array();

		if ( preg_match_all("/<ShortMessage>(.*)<\/ShortMessage>/", $response, $matches_sm) ) {
		 	
		 	preg_match_all("/<SeverityCode>(.*)<\/SeverityCode>/",  $response, $matches_sc );
		 	preg_match_all("/<ErrorCode>(.*)<\/ErrorCode>/",        $response, $matches_ec );
			preg_match_all("/<LongMessage>(.*)<\/LongMessage>/",    $response, $matches_lm );

			foreach ($matches_sm[1] as $key => $sm ) {

				$ec = $matches_ec[1][$key];
				$sc = $matches_sc[1][$key];
				$lm = $matches_lm[1][$key];

				$err = new stdClass();
				$err->SeverityCode = $sc;
				$err->ErrorCode    = $ec;
				$err->ShortMessage = $sm;
				$err->LongMessage  = $lm;

				$errors[] = $err;

				// $errors .= '<b>'.$sc.':</b> ';
				// $errors .= $sm . ' ('.$ec.')<br>';
				// $errors .= '<small>'.$lm.'</small><br>';

			}

		}

		return $errors;
	}


	public function displayLogEntry( $id ) {
		global $wpdb;
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}ebay_log WHERE id = %d", $id ) );
		if ( ! $row ) die('invalid record id');

		// send log entry to support
		if ( @$_REQUEST['send_to_support']=='yes' ) {

			$this->sendRecordToSupport( $id, $row );

		} else {

			$this->display( 'log_details', array( 'row' => $row, 'version' => WPLE_VERSION ) );

		}
		exit();
	}

	public function sendRecordToSupport( $id, $row ) {

		// check nonce
		if ( ! check_admin_referer( 'wple_send_to_support' ) ) return;

		// get html content
		$_GET['desc'] = 'show'; // trigger full details
		$content = $this->display( 'log_details', array( 'row' => $row, 'version' => WPLE_VERSION ), false );

		// build email
		$to          = 'support@wplab.com';
		$subject     = 'WP-Lister for eBay log record #'.$id.' - '. str_replace( 'http://','', get_bloginfo('wpurl') );

		$user_name   = $_REQUEST['user_name'] ? $_REQUEST['user_name'] : 'unknown user';
		$user_email  = sanitize_email( $_REQUEST['user_email'] );
		$user_msg    = stripslashes( $_REQUEST['user_msg'] );
		$headers     = 'From: '.$user_name.' <'.$user_email.'>' . "\r\n";
		$attachments = array();

		$message  = '';
		$message .= 'Name:  '.$user_name.'<br>';
		$message .= 'Email: '.$user_email.'<br>';
		$message .= 'Message: <br><br>'.nl2br($user_msg).'<br>';
		$message .= '<hr>';
		$message .= $content;
		$message .= '<hr>';

		// send email as html
		add_filter('wp_mail_content_type',create_function('', 'return "text/html";'));
		wp_mail($to, $subject, $message, $headers, $attachments);
		
		echo '<br><div style="text-align:center;font-family:sans-serif;">';
		echo 'Your log entry was sent to WP Lab support.';
		echo '<br><br>';
		echo 'Thank you for helping us improve WP-Lister for eBay.</div>';
	}

	public function getTableSize() {
		global $wpdb;
		$dbname = $wpdb->dbname;
		$table  = $wpdb->prefix.'ebay_log';

		// check if MySQL server has gone away and reconnect if required - WP 3.9+
		if ( method_exists( $wpdb, 'check_connection') ) $wpdb->check_connection();

		$sql = "
			SELECT round(((data_length + index_length) / 1024 / 1024), 1) AS 'size' 
			FROM information_schema.TABLES 
			WHERE table_schema = '$dbname'
			  AND table_name = '$table' ";
		// echo "<pre>";print_r($sql);echo"</pre>";#die();

		$size = $wpdb->get_var($sql);
		if ( $wpdb->last_error ) echo 'Error in getTableSize(): '.$wpdb->last_error;

		return $size;
	}

	public function deleteLogEntry( $id ) {
		global $wpdb;
		$wpdb->delete( $wpdb->prefix.'ebay_log',  array( 'id' => $id ) );
		if ( $wpdb->last_error ) echo 'Error in deleteLogEntry(): '.$wpdb->last_error;
	}

	public function clearLog() {
		global $wpdb;
		$table = $wpdb->prefix.'ebay_log';

		$wpdb->query("DELETE FROM $table");
		if ( $wpdb->last_error ) echo 'Error in clearLog(): '.$wpdb->last_error;

		$wpdb->query("OPTIMIZE TABLE $table");
		if ( $wpdb->last_error ) echo 'Error in clearLog(): '.$wpdb->last_error;
	}

	public function optimizeLog() {
		global $wpdb;
		$table = $wpdb->prefix.'ebay_log';

		$days_to_keep = intval( self::getOption( 'log_days_limit', 30 ) );
		$delete_count = $wpdb->get_var('SELECT count(id) FROM '.$wpdb->prefix.'ebay_log WHERE timestamp < DATE_SUB(NOW(), INTERVAL '.$days_to_keep.' DAY )');
		if ( $delete_count ) {
			$wpdb->query('DELETE FROM '.$wpdb->prefix.'ebay_log WHERE timestamp < DATE_SUB(NOW(), INTERVAL '.$days_to_keep.' DAY )');
			// $this->showMessage( 'Log entries removed: ' . $delete_count );
		}
		if ( $wpdb->last_error ) echo 'Error in optimizeLog(): '.$wpdb->last_error;

		$wpdb->query("OPTIMIZE TABLE $table");
		if ( $wpdb->last_error ) echo 'Error in optimizeLog(): '.$wpdb->last_error;

		return $delete_count;
	}


}
