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
		add_action( "load-wp-lister_page_wplister-".self::slug, array( &$this, 'addScreenOptions' ) );

	}

	public function onWpAdminMenu() {
		parent::onWpAdminMenu();

		if ( current_user_can('administrator') && ( self::getOption( 'log_to_db' ) == '1' ) ) {
			add_submenu_page( self::ParentMenuId, $this->getSubmenuPageTitle( 'Logs' ), __('Logs','wplister'), 
							  self::ParentPermissions, $this->getSubmenuId( 'log' ), array( &$this, 'onDisplayLogPage' ) );
		}
	}

	public function handleSubmit() {
        $this->logger->debug("handleSubmit()");

		if ( $this->requestAction() == 'display_log_entry' ) {
			$this->displayLogEntry( $_REQUEST['log_id'] );
			exit();
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

		$aData = array(
			'plugin_url'				=> self::$PLUGIN_URL,
			'message'					=> $this->message,

			// 'logs'						=> $logs,
			'logTable'					=> $logTable,
	
			'form_action'				=> 'admin.php?page='.self::ParentMenuId.'-log'
		);
		$this->display( 'log_page', $aData );
	}


	public function displayLogEntry( $id ) {
	global $wpdb;

		$row = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}ebay_log WHERE id = '$id' ");
		echo mysql_error();

		// send log entry to support
		if ( @$_REQUEST['send_to_support']=='yes' ) {

			// trigger full details
			$_GET['desc'] = 'show';

			// get html content
			$content = $this->display( 'log_details', array( 'row' => $row, 'version' => $this->get_plugin_version() ), false );

			// build email
			$to = 'support@wplab.com';
			$subject = 'WP-Lister Debug Log Entry #'.$id;
			$attachments = array();
			$headers = '';
			$message = $content;

			// send email as html
			add_filter('wp_mail_content_type',create_function('', 'return "text/html";'));
			wp_mail($to, $subject, $message, $headers, $attachments);
			
			echo "This log entry was sent to WP-Lister support.";

		} else {
			// display detail page
			$this->display( 'log_details', array( 'row' => $row, 'version' => $this->get_plugin_version() ) );
		}

		exit();		

	}


}
