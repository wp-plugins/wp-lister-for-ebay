<?php
/* 
Plugin Name: WP-Lister for eBay
Plugin URI: http://www.wplab.com/plugins/wp-lister/
Description: List your products on eBay the easy way. 
Version: 1.0.7.4
Author: Matthias Krok
Author URI: http://www.wplab.com/ 
Max WP Version: 3.4.2
Text Domain: wp-lister
License: GPL2+
*/


// include base classes
define('WPLISTER_VERSION', '1.0.7.4' );
define('WPLISTER_PATH', realpath( dirname(__FILE__) ) );
define('WPLISTER_URL', WP_PLUGIN_URL . '/' . basename(dirname(__FILE__)) . '/' );
require_once( WPLISTER_PATH . '/classes/WPL_Autoloader.php' );
require_once( WPLISTER_PATH . '/classes/WPL_Core.php' );
require_once( WPLISTER_PATH . '/classes/WPL_BasePlugin.php' );
require_once( WPLISTER_PATH . '/classes/WPL_Logger.php' );
require_once( WPLISTER_PATH . '/classes/WPL_EbatNs_Logger.php' );
require_once( WPLISTER_PATH . '/classes/WPL_Page.php' );
require_once( WPLISTER_PATH . '/classes/WPL_Model.php' );
require_once( WPLISTER_PATH . '/classes/WPL_AjaxHandler.php' );
require_once( WPLISTER_PATH . '/classes/WPL_Setup.php' );
require_once( WPLISTER_PATH . '/classes/WPL_Install_Uninstall.php' );
require_once( WPLISTER_PATH . '/classes/EbayController.php' );

// set up autoloader
spl_autoload_register('WPL_Autoloader::autoload');

// init logger
define( 'WPLISTER_DEBUG', get_option('wplister_log_level') );
$wpl_logger = new WPL_Logger();

if ( ! defined('WPLISTER_LIGHT')) define('WPLISTER_LIGHT', true );

class WPL_WPLister extends WPL_BasePlugin {
	
	var $pages = array();
	
	public function __construct() {
		parent::__construct();
		
		if ( is_admin() ) {
			$oInstall 	= new WPLister_Install( __FILE__ );
			$oUninstall = new WPLister_Uninstall( __FILE__ );
			$this->loadPages();
		}
	}
		
	public function loadPages() {

		$this->pages['listings']     = new ListingsPage();
		$this->pages['profiles']     = new ProfilesPage();
		$this->pages['templates']    = new TemplatesPage();
		$this->pages['transactions'] = new TransactionsPage();
		$this->pages['tools']        = new ToolsPage();
		$this->pages['settings']     = new SettingsPage();
		$this->pages['tutorial']     = new HelpPage();
		$this->pages['log']          = new LogPage();

	}
		
	public function onWpInit() {

		// load language
		load_plugin_textdomain( 'wplister', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );	

		// add cron handler
		add_action('wplister_update_auctions', array( &$this, 'cron_update_auctions' ) );

	}
	
	public function onWpAdminInit() {

		add_action( 'admin_print_styles', array( &$this, 'onWpPrintStyles' ) );

    	// add / fix enqueued scripts - only on wplister pages
    	if  ( ( isset( $_GET['page'] ) ) && ( substr( $_GET['page'], 0, 8 ) == 'wplister') ) {
		    add_action( 'wp_print_scripts', array( &$this, 'onWpPrintScripts' ), 99 );
    	}

    	// modify bulk actions menu - only on products list page
		if ( ProductWrapper::isProductsPage() ) {
			add_action( 'admin_footer', array( &$this, 'modifyProductsBulkActionMenu' ) );
			add_action( 'admin_print_styles', array( &$this, 'printProductsPageStyles' ) );
		}
	}
	
	public function onWpPrintStyles() {
		if  ( ( isset( $_GET['page'] ) ) && ( substr( $_GET['page'], 0, 8 ) == 'wplister') ) {
			wp_register_style( 'wplister_style', self::$PLUGIN_URL.'css/style.css' );
			wp_enqueue_style( 'wplister_style' );
		}
	}

	// add custom bulk action 'prepare_auction' for cpt products
	// should be called by 'admin_footer' action
	public function modifyProductsBulkActionMenu() {	
		?>
	    <script type="text/javascript">
    	    jQuery(document).ready(function() {
        	    jQuery('<option>').val('prepare_auction').text('<?php echo __('Prepare Listings','wplister') ?>').appendTo("select[name='action']");
            	jQuery('<option>').val('prepare_auction').text('<?php echo __('Prepare Listings','wplister') ?>').appendTo("select[name='action2']");
	        });
    	</script>
    	<?php
	}

	// add custom bulk action 'prepare_auction' for cpt products
	// should be called by 'admin_footer' action
	public function printProductsPageStyles() {	
		?>
    	<style type="text/css">
			table.wp-list-table .column-listed { width: 25px; }    	
    	</style>
    	<?php
	}

	public function onWpPrintScripts() {
		global $wp_scripts;

    	// fix thickbox display problems caused by other plugins 
        wp_dequeue_script( 'media-upload' );
        
        // if any registered script depends on media-upload, dequeue that too
        foreach ( $wp_scripts->registered as $script ) {
            if ( in_array( 'media-upload', $script->deps ) ) {
                wp_dequeue_script( $script->handle );
            }
        }

	}
	
	// update auctions - called by wp_cron if activated
	public function cron_update_auctions() {
        $this->logger->info("WP-CRON: cron_update_auctions()");

		$this->initEC();
		$this->EC->loadTransactions();
		$this->EC->updateListings();
		$this->EC->closeEbay();
        $this->logger->info("WP-CRON: cron_update_auctions() finished");
	}

	
} // class WPL_WPLister

// instantiate object
$oWPL_WPLister = new WPL_WPLister();

