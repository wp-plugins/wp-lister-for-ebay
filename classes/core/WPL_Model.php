<?php

class WPL_Model {
	
	const OptionPrefix = 'wplister_';

	var $logger;
	public $result;
	
	public function __construct() {
		global $wpl_logger;
		$this->logger = &$wpl_logger;
	}

	// function loadEbayClasses()
	// {
	// 	// we want to be patient when talking to ebay
	// 	set_time_limit(600);

	// 	// add EbatNs folder to include path - required for SDK
	// 	$incPath = WPLISTER_PATH . '/includes/EbatNs';
	// 	set_include_path( get_include_path() . ':' . $incPath );

	// 	// use autoloader to load EbatNs classes
	// 	spl_autoload_register('WPL_Autoloader::autoloadEbayClasses');

	// }

	function initServiceProxy( $session )
	{
		// load required classes - moved to EbayController::initEbay()
		// $this->loadEbayClasses();

		// preparation - set up new ServiceProxy with given session
		$this->_session = $session;
		$this->_cs = new EbatNs_ServiceProxy($this ->_session, 'EbatNs_DataConverterUtf8');

		// attach custom DB Logger if enabled
		if ( get_option('wplister_log_to_db') == '1' ) {
			$this->_cs->attachLogger( new WPL_EbatNs_Logger( false, 'db' ) );
		}

		// attach Logger if log level is debug or greater
		// if ( get_option('wplister_log_level') > 6 ) {
		// 	$this->_cs->attachLogger( new EbatNs_Logger( false, $this->logger->file ) );
		// }

	}

	// flexible object encoder
	public function encodeObject( $obj ) {

		$str = json_encode( $obj );
		#$this->logger->info('json_encode - input: '.print_r($obj,1));
		#$this->logger->info('json_encode - output: '.$str);
		#$this->logger->info('json_last_error(): '.json_last_error() );

		if ( $str == '{}' ) return serialize( $obj );
		else return $str;
	}	
	
	// flexible object decoder
	public function decodeObject( $str, $assoc = false, $loadEbayClasses = false ) {

		// load eBay classes if required
		if ( $loadEbayClasses ) EbayController::loadEbayClasses();

		if ( $str == '' ) return false; 
		if ( is_object($str) || is_array($str) ) return $str;

		// json_decode
		$obj = json_decode( $str, $assoc );
		//$this->logger->info('json_decode: '.print_r($obj,1));
		if ( is_object($obj) || is_array($obj) ) return $obj;
		
		// unserialize fallback
		$obj = maybe_unserialize( $str );
		//$this->logger->info('unserialize: '.print_r($obj,1));
		if ( is_object($obj) || is_array($obj) ) return $obj;
		
		// mb_unserialize fallback
		$obj = $this->mb_unserialize( $str );
		// $this->logger->info('mb_unserialize: '.print_r($obj,1));
		if ( is_object($obj) || is_array($obj) ) return $obj;

		// log error
		$e = new Exception;
		$this->logger->error('backtrace: '.$e->getTraceAsString());
		$this->logger->error('mb_unserialize returned: '.print_r($obj,1));
		$this->logger->error('decodeObject() - not an valid object: '.$str);
		return $str;
	}	

	/**
	 * Mulit-byte Unserialize
	 * UTF-8 will screw up a serialized string
	 */
	function mb_unserialize($string) {

		// special handling for asterisk wrapped in zero bytes
	    $string = str_replace( "\0*\0", "*\0", $string);
	    $string = preg_replace('!s:(\d+):"(.*?)";!se', "'s:'.strlen('$2').':\"$2\";'", $string);
	    $string = str_replace('*\0', "\0*\0", $string);

	    return unserialize($string);
	}

	/* Generic message display */
	public function showMessage($message, $errormsg = false, $echo = false) {		
		$class = ($errormsg) ? 'error' : 'updated fade';			// error or success
		$class = ($errormsg == 2) ? 'updated update-nag' : $class; 	// warning
		$message = '<div id="message" class="'.$class.'" style="display:block !important"><p>'.$message.'</p></div>';
		if ($echo) {
			echo $message;
		} else {
			$this->message .= $message;
		}
	}


	// handle eBay response
	//  - check for request success (or warning)
	//  - display any errors
	//  - display any warning - except #21917103
	//  - returns true on success (even with warnings) and false on failure
	function handleResponse( $res )	{
		$errors = array();

		// if ( ! is_object($res) ) return false;
		$this->handle_error_code = null;

		// echo errors and warnings - call can be successful but with warnings
		if ( $res->getErrors() )
		foreach ($res->getErrors() as $error) {
			// hide warning #21917103 ("Delivery estimate based on shipping service and handling time")
			if ( $error->getErrorCode() == 21917103 ) continue;

			// #37 means soap error - and htmlentities have to be encoded (maybe not anymore?)
			if ( $error->getErrorCode() == 37 ) { 
				$longMessage = htmlspecialchars( $error->getLongMessage() );
				// $longMessage = $error->getLongMessage();
			} else {
				$longMessage = htmlspecialchars( $error->getLongMessage() );
				// $longMessage = $error->getLongMessage();
			}
			$shortMessage = htmlspecialchars( $error->getShortMessage() );

			// #240 - generic error on listing item
			if ( $error->getErrorCode() == 240 ) { 
				$longMessage .= '<br><br>'. '<b>Note:</b> The message above is a generic error message from eBay which is not to be taken literally.';
				$longMessage .= '<br>'. 'Below you find an explaination as to what triggered the above error:';
			}
			
			// #291 - Auction ended / You are not allowed to revise ended listings.
			if ( $error->getErrorCode() == 291 ) { 
				// change status from Error to Warning to allow post processing of this error
				$res->setAck('Warning');
				$this->handle_error_code = 291;
				$longMessage .= '<br><br>'. '<b>Note:</b> Listing status was changed to ended.';				
			}
			
			// #302 - Invalid auction listing type
			if ( $error->getErrorCode() == 302 ) { 
				$longMessage .= '<br><br>'. '<b>Note:</b> eBay does not allow changing the listing type of an active listing.';
				$longMessage .= '<br>'. 'To change a listing type from auction to fixed price or vice versa, you need to end and relist the item.';
			}
			
			// #931 - Auth token is invalid
			if ( $error->getErrorCode() == 931 ) { 
				$shortMessage = 'Your API token is invalid';
				// $longMessage .= '<br><br>'. '<b>Your API token is invalid.</b> Please authenticate WP-Lister with eBay again.';
				$longMessage .= '<br><br>'. '<b>Please authenticate WP-Lister with eBay again.</b>';
				$longMessage .= '<br>'. 'This can happen if you enabled the sandbox mode or if your token has expired.';
				$longMessage .= '<br>'. 'To re-authenticate WP-Lister visit the Settings page, click on "Change Account" and follow the instructions.';
				update_option( 'wplister_ebay_token_is_invalid', true );
			}
			
			// #21916519 - Error: Listing is missing required item specific(s)
			if ( $error->getErrorCode() == 21916519 ) { 
				// $longMessage .= '<br><br>'. '<b>How to add item specifics to your eBay listings</b>'.'<br>';
				$longMessage .= '<br><br>'. '<b>Why am I seeing this error message?</b>'.'<br>';
				$longMessage .= 'eBay requires sellers to provide these item specifics (product attributes) for the selected primary category.'.'<br>';
				$longMessage .= '<br>';
				$longMessage .= 'You have two options to add item specifics to your listings:'.'<!br>';
				$longMessage .= '<ol>';
				$longMessage .= '<li>Create product attributes with the exact same name as required by eBay.'.'</li>';
				if ( WPLISTER_LIGHT ) :
					$longMessage .= '<li>Upgrade to WP-Lister Pro where you can define item specifics in your profile. You can either enter fixed values or map existing WooCommerce product attributes to eBay item specifics.'.'</li>';
				else :
					$longMessage .= '<li>Define item specifics in your listing profile where you can either enter fixed values or map WooCommerce product attributes to eBay item specifics.'.'</li>';
				endif;
				$longMessage .= '</ol>';
				$longMessage .= 'More detailed information about item specifics in WP-Lister Pro can be found here: ';
				$longMessage .= '<a href="http://www.wplab.com/list-your-products-with-item-specifics-recommended-by-ebay/" target="_blank">http://www.wplab.com/list-your-products-with-item-specifics-recommended-by-ebay/</a>';
			}
			
			// #219422   - Error: Invalid PromotionalSale item / Item format does not qualify for promotional sale
			// #21916391 - Error: Not an Active SM subscriber  / "user" is not subscribed to Selling Manager.
			if ( ( $error->getErrorCode() == 219422 ) || ( $error->getErrorCode() == 21916391 ) ) { 
				$longMessage .= '<br><br>'. '<b>Why am I seeing this error message?</b>'.'<br>';
				$longMessage .= 'You might not be allowed to use eBay\'s <i>Selling Manager Pro</i>.'.'<br>';
				$longMessage .= '<br>';
				$longMessage .= 'If you see this error when listing a new item on eBay it will still be listed, ';
				$longMessage .= 'but you should disable the <i>Auto Relist</i> option in your listing profile in the box labeled "Selling Manager Pro" in order to make this error message disappear.';
			}
			
			// #21915307 - Warning: Shipping Service - Pickup is set as last service.
			if ( $error->getErrorCode() == 21915307 ) { 
				$longMessage .= '<br><br>'. '<b>Why am I seeing this message?</b>'.'<br>';
				$longMessage .= 'The warning above can be misleading. What eBay actually means is: ';
				$longMessage .= 'If there are two or more services and one is "pickup", "pickup" must not be specified as the first service.';
			}
			
			// #21916543 - Error: ExternalPictureURL server not available.
			if ( $error->getErrorCode() == 21916543 ) { 
				$longMessage .= '<br><br>'. '<b>Why am I seeing this message?</b>'.'<br>';
				$longMessage .= 'eBay tried to fetch an image from your website but your server did not respond in time.<br>';
				$longMessage .= 'This could be a temporary issue with eBay, but it could as well indicate problems with your server. ';
				$longMessage .= 'You should wait a few hours and see if this issue disappears, but if it persists you should consider moving to a better hosting provider.';
			}
			
			// #488 - Error: Duplicate UUID used.
			if ( $error->getErrorCode() == 488 ) { 
				$longMessage .= '<br><br>'. '<b>Why am I seeing this message?</b>'.'<br>';
				$longMessage .= 'You probably tried to list the same product twice within a short time period.<br>';
				$longMessage .= 'Please wait for about one hour and you will be able to list this product again. ';
			}
			
			// #21917092 - Warning: Requested Quantity revision is redundant.
			if ( $error->getErrorCode() == 21917092 ) { 
			}
			
			// #90002 - soap-fault: org.xml.sax.SAXParseException: The element type "Description" must be terminated by the matching end-tag "</Description>".
			if ( $error->getErrorCode() == 90002 ) { 
				$longMessage .= '<br><br>'. '<b>Why am I seeing this message?</b>'.'<br>';
				$longMessage .= 'Your listing template probably contains CDATA tags which can not be used in a listing description.<br>';
				$longMessage .= 'Please remove all CDATA tags from your listing template and try again - or contact support. ';
			}
			

			// some errors like #240 may return an extra ErrorParameters array
			// deactivated for now since a copy of this will be found in $res->getMessage()
			// if ( isset( $error->ErrorParameters ) ) { 
			// 	$extraMsg  = '<div id="message" class="updated update-nag" style="display:block !important;"><p>';
			// 	$extraMsg .= print_r( $error->ErrorParameters, 1 );
			// 	$extraMsg .= '</p></div>';
			// 	if ( ! $this->is_ajax() ) echo $extraMsg;
			// } else {
			// 	$extraMsg = '';
			// }

			// display error message - if this is not an ajax request
			$class = ( $error->SeverityCode == 'Error') ? 'error' : 'updated update-nag';
			$htmlMsg  = '<div id="message" class="'.$class.'" style="display:block !important;"><p>';
			$htmlMsg .= '<b>' . $error->SeverityCode . ': ' . $shortMessage . '</b>' . ' (#'  . $error->getErrorCode() . ') ';
			$htmlMsg .= '<br>' . $longMessage . '';

			// handle optional ErrorParameters
			if ( ! empty( $error->ErrorParameters ) ) {
				foreach ( $error->ErrorParameters as $param ) {
					$htmlMsg .= '<br><code>' . $param . '</code>';
				}
			}

			$htmlMsg .= '</p></div>';
			// $htmlMsg .= $extraMsg;
			if ( ! $this->is_ajax() ) echo $htmlMsg;

			// save errors and warnings as array of objects
			$errorObj = new stdClass();
			$errorObj->SeverityCode = $error->SeverityCode;
			$errorObj->ErrorCode 	= $error->getErrorCode();
			$errorObj->ShortMessage = $error->getShortMessage();
			$errorObj->LongMessage 	= $longMessage;
			$errorObj->HtmlMessage 	= $htmlMsg;
			$errors[] = $errorObj;

		}

		// some errors like #240 may trigger an extra Message field returned in the response
		if ( $res->getMessage() ) { 
			$class = ( $res->getAck() == 'Failure') ? 'error' : 'updated update-nag';
			$extraMsg  = '<div id="message" class="'.$class.'" style="display:block !important;">';
			$extraMsg .= $res->getMessage();
			$extraMsg .= '</div>';
			if ( ! $this->is_ajax() ) echo $extraMsg;

			// save errors and warnings as array of objects
			$errorObj = new stdClass();
			$errorObj->SeverityCode = 'Info';
			$errorObj->ErrorCode 	= 101;
			$errorObj->ShortMessage = __('Additional details about this error','wplister');
			$errorObj->LongMessage 	= $res->getMessage();
			$errorObj->HtmlMessage 	= $extraMsg;
			$errors[] = $errorObj;
		}


		// check if request was successful
		if ( ($res->getAck() == 'Success') || ($res->getAck() == 'Warning') ) {
			$success = true;
		} else {
			$success = false;
		} 

		// save results as local property
		$this->result = new stdClass();
		$this->result->success = $success;
		$this->result->errors  = $errors;

		// save last result - except for GetItem calls which usually follow ReviseItem calls
		if ( 'GetItemResponseType' != get_class($res) )
			$this->save_last_result();
		// $this->logger->info('handleResponse() - result: '.print_r($this->result,1));

		return $success;

	} // handleResponse()

	function save_last_result() {
		// make sure we are updating a product
		if ( ! isset($_POST['action'])    || $_POST['action']    != 'editpost' ) return;
		if ( ! isset($_POST['post_type']) || $_POST['post_type'] != 'product'  ) return;
		if ( ! isset($_POST['post_ID']) ) return;
		$post_id = $_POST['post_ID'];

		// fetch last results
		$update_results = get_option( 'wplister_last_product_update_results', array() );
		if ( ! is_array($update_results) ) $update_results = array();

		// update last results
		$update_results[ $post_id ] = $this->result;
		update_option( 'wplister_last_product_update_results', $update_results );

	} // save_last_result()

	function is_ajax() {
		return ( defined( 'DOING_AJAX' ) && DOING_AJAX ) || ( defined( 'WOOCOMMERCE_CHECKOUT' ) && WOOCOMMERCE_CHECKOUT ) || ( isset($_POST['action']) && ( $_POST['action'] == 'editpost' ) ) ;
	}

	// check if given WordPress plugin is active
	public function is_plugin_active( $plugin ) {

		if ( is_multisite() ) {

			// check for network activation
			if ( ! function_exists( 'is_plugin_active_for_network' ) )
				require_once( ABSPATH . '/wp-admin/includes/plugin.php' );

			if ( function_exists('is_plugin_active_for_network') && is_plugin_active_for_network( $plugin ) )
				return true;				

		}

    	return in_array( $plugin, (array) get_option( 'active_plugins', array() ) );
	}

	// custom mb_strlen implementation
	public function mb_strlen( $string ) {

		// use mb_strlen() if available
		if ( function_exists('mb_strlen') ) return mb_strlen( $string );

		// fallback if PHP was compiled without multibyte support
		$length = preg_match_all( '(.)su', $string, $matches );
    	return $length;

	}

	// custom mb_substr implementation
	public function mb_substr( $string, $start, $length ) {

		// use mb_substr() if available
		if ( function_exists('mb_substr') ) return mb_substr( $string, $start, $length );

		// fallback if PHP was compiled without multibyte support
		// $string = substr( $string, $start, $length );

		// snippet from http://www.php.net/manual/en/function.mb-substr.php#107698
	    $string = join("", array_slice( preg_split("//u", $string, -1, PREG_SPLIT_NO_EMPTY), $start, $length ) );

    	return $string;

	}

	// convert 2013-02-14T08:00:58.000Z to 2013-02-14 08:00:58
	public function convertEbayDateToSql( $ebay_date ) {
		$search = array( 'T', '.000Z' );
		$replace = array( ' ', '' );
		$sql_date = str_replace( $search, $replace, $ebay_date );
		return $sql_date;
	}

	// convert 2013-02-14 08:00:58 to 2013-02-14T08:00:58.000Z
	public function convertSqlDateToEbay( $sql_date ) {
		$iso_date = date('Y-m-d\TH:i:s', strtotime( $sql_date ) ) . '.000Z';
		return $iso_date;
	}


	public function convertTimestampToLocalTime( $timestamp ) {

		// set this to the time zone provided by the user
		$tz = get_option('wplister_local_timezone');
		if ( ! $tz ) $tz = 'Europe/London';
		 
		// create the DateTimeZone object for later
		$dtzone = new DateTimeZone($tz);
		 
		// first convert the timestamp into a string representing the local time
		$time = date('r', $timestamp);
		 
		// now create the DateTime object for this time
		$dtime = new DateTime($time);
		 
		// convert this to the user's timezone using the DateTimeZone object
		$dtime->setTimeZone($dtzone);
		 
		// print the time using your preferred format
		// $time = $dtime->format('g:i A m/d/y');
		$time = $dtime->format('Y-m-d H:i:s'); // SQL date format

		return $time;
	}

	public function convertLocalTimeToTimestamp( $time ) {

		// time to convert (just an example)
		// $time = 'Tuesday, April 21, 2009 2:32:46 PM';
		 
		// set this to the time zone provided by the user
		$tz = get_option('wplister_local_timezone');
		if ( ! $tz ) $tz = 'Europe/London';
		 
		// create the DateTimeZone object for later
		$dtzone = new DateTimeZone($tz);
		 
		// now create the DateTime object for this time and user time zone
		$dtime = new DateTime($time, $dtzone);
		 
		// print the timestamp
		$timestamp = $dtime->format('U');

		return $timestamp;
	}


}

