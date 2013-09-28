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
					$this->EC->closeEbay();
					$this->showMessage( __('Your UserID is','wplister') . ' ' . $UserID );
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
	
				// update_ebay_transactions
				if ( $_POST['action'] == 'update_ebay_transactions_30') {				
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
	
	
			} else {
				die ('not allowed');
			}

		} // if $_REQUEST['action']

	} // handleActions()
	

	public function onDisplayToolsPage() {
		WPL_Setup::checkSetup();

		$this->handleActions();

		$aData = array(
			'plugin_url'				=> self::$PLUGIN_URL,
			'message'					=> $this->message,		
			'results'					=> isset($this->results) ? $this->results : '',
			'resultsHtml'				=> isset($this->resultsHtml) ? $this->resultsHtml : '',
			'debug'						=> isset($debug) ? $debug : '',
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

		// JobRunner
		wp_register_script( 'wpl_JobRunner', self::$PLUGIN_URL.'/js/classes/JobRunner.js', array( 'jquery', 'jquery-ui-core', 'jquery-ui-progressbar' ), WPLISTER_VERSION );
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

	}


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

	}

}
