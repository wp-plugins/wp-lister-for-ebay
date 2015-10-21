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

	// const ParentName		= 'eBay';
	// const ParentTitle	= 'eBay';
	const ParentPermissions	= 'manage_ebay_listings';
	const ParentMenuId		= 'wplister';
	
	const InputPrefix 		= 'wpl_e2e_';
	const OptionPrefix 		= 'wplister_';

	// const ViewExt			= '.php';
	// const ViewDir			= '../views';

	var $logger;
	var $message;
	var $messages = array();
	var $EC;
	var $app_name;
	
	public function __construct() {
		// deprecated
		// global $wpl_logger;
		// $this->logger   = &$wpl_logger;
		// $this->logger = WPLE()->logger;

		$this->app_name = apply_filters( 'wplister_app_name', 'eBay' );

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
		if ( defined('WPLISTER_RESELLER_VERSION') ) $message = apply_filters( 'wplister_tooltip_text', $message );
		$class = ($errormsg) ? 'error' : 'updated';			// error or success
		$class = ($errormsg == 2) ? 'update-nag' : $class; 	// top warning
		$this->message .= '<div id="message" class="'.$class.'" style="display:block !important"><p>'.$message.'</p></div>';
		if ($echo) echo $this->message;
	}


	// init eBay connection
	public function initEC( $account_id = null, $site_id = null ) { 

		// make sure the database is up to date
	 	WPLE_UpgradeHelper::maybe_upgrade_db();

	 	// init controller
		$this->EC = new EbayController();	

		// use current default account by default (WPL1)
		$ebay_site_id    = self::getOption('ebay_site_id'); 
		$sandbox_enabled = self::getOption('sandbox_enabled');
		$ebay_token      = self::getOption('ebay_token');

		// set site_id dynamically during authentication
		// if ( isset( $_REQUEST['site_id'] ) && isset( $_REQUEST['action'] ) && $_REQUEST['action'] == 'wplRedirectToAuthURL' ) {
		if ( isset( $_REQUEST['site_id'] ) && isset( $_REQUEST['sandbox'] ) ) {
			$ebay_site_id    = $_REQUEST['site_id']; 
			$sandbox_enabled = $_REQUEST['sandbox'];
			$ebay_token      = '';
		}

		// use specific account if provided in request or parameter
		if ( ! $account_id && isset( $_REQUEST['account_id'] ) ) {
			$account_id = $_REQUEST['account_id'];
		}
		if ( $account_id ) {
			// $account = new WPLE_eBayAccount( $account_id ); // not suitable to check if an account exists
			$account = WPLE_eBayAccount::getAccount( $account_id );
			if ( $account ) {
				$ebay_site_id    = $account->site_id; 
				$sandbox_enabled = $account->sandbox_mode; 
				$ebay_token      = $account->token; 
			} else {
				$msg = sprintf('<b>Warning: You are trying to use an account which does not exist in WP-Lister</b> (ID %s).',$account_id) . '<br>';
				$msg .= 'This can happen when you delete an account from WP-Lister without removing all listings, profiles and orders first.'. '<br><br>';
				$msg .= 'In order to solve this issue, please visit your account settings and follow the instructions to assign all listings, orders and profiles to your default account.';
				wple_show_message($msg,'warn');
			}
		} else {
			$account_id = get_option('wplister_default_account_id');
		}

		if ( $site_id ) $ebay_site_id = $site_id;

		$this->EC->initEbay( $ebay_site_id, 
							 $sandbox_enabled,
							 $ebay_token,
							 $account_id );
	}
	
	public function isStagingSite() {
		$staging_site_pattern = get_option('wplister_staging_site_pattern');
		if ( ! $staging_site_pattern ) return false;

		$domain = $_SERVER["SERVER_NAME"];
		// if ( strpos( ' '.$domain, $staging_site_pattern) > 0 ) {
		if ( preg_match( "/$staging_site_pattern/", $domain ) ) {
			return true;
		}

		return false;
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
		return $insTitle.' - '.$this->app_name;
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

