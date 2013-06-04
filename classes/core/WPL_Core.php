<?php
/**
 * WPL_Core
 *
 * This class contains methods that should be available for all classes
 * 
 */

if ( ! defined( 'DS' ) ) define( 'DS', DIRECTORY_SEPARATOR );

class WPL_Core {
	
	static public $PLUGIN_URL;
	static public $PLUGIN_DIR;
	static public $PLUGIN_VERSION;

	const ParentTitle		= 'WP-Lister';
	const ParentName		= 'eBay';
	const ParentPermissions	= 'edit_pages';
	const ParentMenuId		= 'wplister';
	
	const InputPrefix 		= 'wpl_e2e_';
	const OptionPrefix 		= 'wplister_';

	// const ViewExt			= '.php';
	// const ViewDir			= '../views';

	var $logger;
	var $message;
	var $messages = array();
	var $EC;
	
	public function __construct() {
		global $wpl_logger;
		$this->logger = &$wpl_logger;

		add_action( 'init', 				array( &$this, 'onWpInit' ), 1 );
		add_action( 'admin_init', 			array( &$this, 'onWpAdminInit' ) );

		$this->config();
	}

	// these methods can be overriden
	public function config() {
	}
	public function onWpInit() {
	}	
	public function onWpAdminInit() {
	}

	/* Generic message display */
	public function showMessage($message, $errormsg = false, $echo = false) {		
		$class = ($errormsg) ? 'error' : 'updated fade';
		$this->message .= '<div id="message" class="'.$class.'" style="display:block !important"><p>'.$message.'</p></div>';
		if ($echo) echo $this->message;
	}


	// init eBay connection
	public function initEC() { 
		$this->EC = new EbayController();
		$this->EC->initEbay( self::getOption('ebay_site_id'), 
							 self::getOption('sandbox_enabled'),
							 self::getOption('ebay_token') );
	}
	

	/* prefixed request handlers */
	protected function getAnswerFromPost( $insKey, $insPrefix = null ) {
		if ( is_null( $insPrefix ) ) {
			$insKey = self::InputPrefix.$insKey;
		}
		return ( isset( $_POST[$insKey] )? 'Y': 'N' );
	}
	
	protected function getValueFromPost( $insKey, $insPrefix = null ) {
		if ( is_null( $insPrefix ) ) {
			$insKey = self::InputPrefix.$insKey;
		}
		return ( isset( $_POST[$insKey] ) ? $_POST[$insKey] : false );
	}

	protected function requestAction() {
		if ( ( isset($_REQUEST['action']  ) ) && ( $_REQUEST['action']  != '' ) && ( $_REQUEST['action']  != '-1' ) ) return $_REQUEST['action'];
		if ( ( isset($_REQUEST['action2'] ) ) && ( $_REQUEST['action2'] != '' ) && ( $_REQUEST['action2'] != '-1' ) ) return $_REQUEST['action2'];
		return false;
	}

	
	/* prefixed option handlers */
	static public function getOption( $insKey, $default = null ) {
		return get_option( self::OptionPrefix.$insKey, $default );
	}
	
	static public function addOption( $insKey, $insValue ) {
		return add_option( self::OptionPrefix.$insKey, $insValue );
	}
	
	static public function updateOption( $insKey, $insValue ) {
		return update_option( self::OptionPrefix.$insKey, $insValue );
	}
	
	static public function deleteOption( $insKey ) {
		return delete_option( $insKey );
	}

	/* template methods */
	protected function getImageUrl( $insImage ) {
		return self::$PLUGIN_URL.'img/'.$insImage;
	}
	
	protected function getSubmenuPageTitle( $insTitle ) {
		return $insTitle.' - '.self::ParentTitle;
	}
	
	protected function getSubmenuId( $insId ) {
		return self::ParentMenuId.'-'.$insId;
	}

	/* more template methods */	
	// protected function redirect( $insUrl, $innTimeout = 1 ) {
	// 	echo '
	// 		<script type="text/javascript">
	// 			function redirect() {
	// 				window.location = "'.$insUrl.'";
	// 			}
	// 			//var oTimer = setTimeout( "redirect()", "'.($innTimeout * 1000).'" );
	// 		</script>'; 
	// }
			

}

