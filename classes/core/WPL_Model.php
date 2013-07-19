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

		if ( $str = '{}' ) return serialize( $obj );
		else return $str;
	}	
	
	// flexible object decoder
	public function decodeObject( $str, $assoc = false, $loadEbayClasses = false ) {

		// load eBay classes if required
		if ( $loadEbayClasses ) EbayController::loadEbayClasses();

		if ( $str == '' ) return false; 

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
		$class = ($errormsg) ? 'error' : 'updated fade';
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
			

			// some errors like #240 may return an extra ErrorParameters array
			// deactivated for now since a copy of this will be found in $res->getMessage()
			// if ( isset( $error->ErrorParameters ) ) { 
			// 	$extraMsg  = '<div id="message" class="updated" style="display:block !important;"><p>';
			// 	$extraMsg .= print_r( $error->ErrorParameters, 1 );
			// 	$extraMsg .= '</p></div>';
			// 	if ( ! $this->is_ajax() ) echo $extraMsg;
			// } else {
			// 	$extraMsg = '';
			// }

			// display error message - if this is not an ajax request
			$class = ( $error->SeverityCode == 'Error') ? 'error' : 'updated';
			$htmlMsg  = '<div id="message" class="'.$class.'" style="display:block !important;"><p>';
			$htmlMsg .= '<b>' . $error->SeverityCode . ': ' . $shortMessage . '</b>' . ' (#'  . $error->getErrorCode() . ') ';
			$htmlMsg .= '<br>' . $longMessage . '';
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
			$extraMsg  = '<div id="message" class="updated" style="display:block !important;">';
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

		// $this->logger->info('handleResponse() - result: '.print_r($this->result,1));

		return $success;

	} // handleResponse()

	function is_ajax() {
		return defined( 'DOING_AJAX' ) && DOING_AJAX;
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

}

