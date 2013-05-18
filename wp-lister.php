<?php
/* 
Plugin Name: WP-Lister for eBay
Plugin URI: http://www.wplab.com/plugins/wp-lister/
Description: List your products on eBay the easy way.
Version: 1.2.1
Author: Matthias Krok
Author URI: http://www.wplab.com/ 
Max WP Version: 3.5.1
Text Domain: wp-lister
License: GPL2+
*/


// include base classes
define('WPLISTER_VERSION', '1.2.1' );
define('WPLISTER_PATH', realpath( dirname(__FILE__) ) );
define('WPLISTER_URL', plugins_url() . '/' . basename(dirname(__FILE__)) . '/' );
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
global $wpl_logger;
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


		// custom toolbar
		add_action( 'admin_bar_menu', array( &$this, 'customize_toolbar' ), 999 );

	}
		
	public function loadPages() {

		if ( is_network_admin() ) {
	
			$this->pages['sites']    	 = new NetworkAdminPage();
			$this->pages['settings']     = new SettingsPage();
	
		} else {

			if ( ( is_multisite() ) && ( self::getOption('is_enabled') == 'N' ) ) return;

			$this->pages['listings']     = new ListingsPage();
			$this->pages['profiles']     = new ProfilesPage();
			$this->pages['templates']    = new TemplatesPage();
			$this->pages['transactions'] = new TransactionsPage();
			$this->pages['tools']        = new ToolsPage();
			$this->pages['settings']     = new SettingsPage();
			$this->pages['tutorial']     = new HelpPage();
			$this->pages['log']          = new LogPage();

		}

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
		add_action( 'admin_print_styles', array( &$this, 'printOrdersPageStyles' ) );
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

	public function printProductsPageStyles() {	
		?>
    	<style type="text/css">
			table.wp-list-table .column-listed { width: 25px; }    	
    	</style>
    	<?php
	}
	public function printOrdersPageStyles() {	
		?>
    	<style type="text/css">
			table.wp-list-table .column-ebay { width: 25px; }    	
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



	// custom toolbar bar
	function customize_toolbar( $wp_admin_bar ) {

		// top level 'eBay'
		$args = array(
			'id'    => 'wplister_top',
			'title' => __('eBay', 'wplister'),
			'href'  => admin_url( 'admin.php?page=wplister' ),
			'meta'  => array('class' => 'wplister-toolbar-top')
		);
		$wp_admin_bar->add_node($args);
		
		// Listings page	
		$args = array(
			'id'    => 'wplister_listings',
			'title' => __('Listings', 'wplister'),
			'href'  => admin_url( 'admin.php?page=wplister' ),
			'parent'  => 'wplister_top',
			'meta'  => array('class' => 'wplister-toolbar-page')
		);
		$wp_admin_bar->add_node($args);

		// Profiles page
		$args = array(
			'id'    => 'wplister_profiles',
			'title' => __('Profiles', 'wplister'),
			'href'  => admin_url( 'admin.php?page=wplister-profiles' ),
			'parent'  => 'wplister_top',
			'meta'  => array('class' => 'wplister-toolbar-page')
		);
		$wp_admin_bar->add_node($args);

		// Settings page
		$args = array(
			'id'    => 'wplister_settings',
			'title' => __('Settings', 'wplister'),
			'href'  => admin_url( 'admin.php?page=wplister-settings' ),
			'parent'  => 'wplister_top',
			'meta'  => array('class' => 'wplister-toolbar-page')
		);
		$wp_admin_bar->add_node($args);

		// Settings - General tab
		$args = array(
			'id'    => 'wplister_settings_general',
			'title' => __('General Settings', 'wplister'),
			'href'  => admin_url( 'admin.php?page=wplister-settings' ),
			'parent'  => 'wplister_settings',
			'meta'  => array('class' => 'wplister-toolbar-page')
		);
		$wp_admin_bar->add_node($args);

		// Settings - Categories tab
		$args = array(
			'id'    => 'wplister_settings_categories',
			'title' => __('Categories', 'wplister'),
			'href'  => admin_url( 'admin.php?page=wplister-settings&tab=categories' ),
			'parent'  => 'wplister_settings',
			'meta'  => array('class' => 'wplister-toolbar-page')
		);
		$wp_admin_bar->add_node($args);

		// Settings - License tab
		$args = array(
			'id'    => 'wplister_settings_license',
			'title' => __('License', 'wplister'),
			'href'  => admin_url( 'admin.php?page=wplister-settings&tab=license' ),
			'parent'  => 'wplister_settings',
			'meta'  => array('class' => 'wplister-toolbar-page')
		);
		$wp_admin_bar->add_node($args);

		// Settings - Developer tab
		$args = array(
			'id'    => 'wplister_settings_developer',
			'title' => __('Developer', 'wplister'),
			'href'  => admin_url( 'admin.php?page=wplister-settings&tab=developer' ),
			'parent'  => 'wplister_settings',
			'meta'  => array('class' => 'wplister-toolbar-page')
		);
		$wp_admin_bar->add_node($args);


		if ( current_user_can('administrator') && ( get_option( 'wplister_log_to_db' ) == '1' ) ) {
		
			// Logs page
			$args = array(
				'id'    => 'wplister_log',
				'title' => __('Logs', 'wplister'),
				'href'  => admin_url( 'admin.php?page=wplister-log' ),
				'parent'  => 'wplister_top',
				'meta'  => array('class' => 'wplister-toolbar-page')
			);
			$wp_admin_bar->add_node($args);

		}

		// admin only from here on
		if ( ! current_user_can('administrator') ) return;

		// product page
		global $post;
		global $wp_query;
		$post_id = false;

		if ( isset( $wp_query->post->post_type ) && ( $wp_query->post->post_type == 'product' ) ) {
			$post_id = $wp_query->post->ID;
		} elseif ( isset( $post->post_type ) && ( $post->post_type == 'product' ) ) {
			$post_id = $post->ID;
		}

		if ( $post_id ) {

			$lm = new ListingsModel();
			$ebay_id = $lm->getEbayIDFromPostID( $post_id );
			$url = $lm->getViewItemURLFromPostID( $post_id );

			// View on eBay link
			$args = array(
				'id'    => 'wplister_view_on_ebay',
				'title' => __('View item on eBay', 'wplister'), # ." ($ebay_id)",
				'href'  => $url,
				'parent'  => 'wplister_top',
				'meta'  => array('target' => '_blank', 'class' => 'wplister-toolbar-link')
			);
			if ( $url ) $wp_admin_bar->add_node($args);

			// get all items
			$listings = $lm->getAllListingsFromPostID( $post_id );
			foreach ($listings as $listing) {

				$args = array(
					'id'    => 'wplister_view_on_ebay_'.$listing->id,
					'title' => '#'.$listing->ebay_id . ': ' . $listing->auction_title,
					'href'  => $listing->ViewItemURL,
					'parent'  => 'wplister_view_on_ebay',
					'meta'  => array('target' => '_blank', 'class' => 'wplister-toolbar-link')
				);
				if ( $listing->ViewItemURL ) $wp_admin_bar->add_node($args);

			}

		}



	}


	
} // class WPL_WPLister

// instantiate object
$oWPL_WPLister = new WPL_WPLister();

