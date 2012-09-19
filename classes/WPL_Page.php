<?php
/**
 * WPL_Page
 *
 * This class provides methods for single admin pages
 * 
 */

// helper class for custom screen options - will be removed in future versions
require_once( WPLISTER_PATH . '/includes/screen-options/screen-options.php' );

class WPL_Page extends WPL_Core {
	
	public function __construct() {
		parent::__construct();

		self::$PLUGIN_URL = WPLISTER_URL;
		self::$PLUGIN_DIR = WPLISTER_PATH;

		add_action( 'admin_menu', 			array( &$this, 'onWpAdminMenu' ), 20 );

		if ( is_admin() ) {
			add_action( 'plugins_loaded', 	array( &$this, 'handleSubmit' ) );
		}

	}
	
	// these methods can be overriden
	public function onWpAdminMenu() {
	}	
	public function handleSubmit() {
	}	


	// display view
	protected function display( $insView, $inaData = array(), $echo = true ) {
		$sFile = dirname(__FILE__).DS.self::ViewDir.DS.$insView.self::ViewExt;
		
		if ( !is_file( $sFile ) ) {
			$this->showMessage("View not found: ".$sFile,1,1);
			return false;
		}
		
		if ( count( $inaData ) > 0 ) {
			extract( $inaData, EXTR_PREFIX_ALL, 'wpl' );
		}
		
		ob_start();
			include( $sFile );
			$sContents = ob_get_contents();
		ob_end_clean();

		// change admin footer on wplister pages
		add_filter('admin_footer_text', array( &$this, 'change_admin_footer_text') ); 
		add_filter('update_footer', array( &$this, 'change_admin_footer_version') ); 

		// MOVED to wp-lister.php
		// fix thickbox display problems caused by other plugins 
		// like woocommerce-pip which enqueues media-upload on every admin page
		// if ( did_action( 'init' ) ) wp_dequeue_script( 'media-upload' );
		

		if ($echo) {
			echo $sContents;
			return true;
		} else {
			return $sContents;
		}
	
	}

	function change_admin_footer_text() {  
	    echo '<span id="footer-thankyou">';
	    echo sprintf( __('Thank you for listing with %s','wplister'), '<a href="http://www.wplab.de/plugins/wp-lister/" target="_blank">WP-Lister</a>' );
	    echo '</span>';
	}  
	function change_admin_footer_version( $version ) {  
	    return $version . ' / WP-Lister '.$this->get_plugin_version();
	}  

	function get_plugin_version() {
	    
	    if ( ! function_exists( 'get_plugins' ) )
	    	require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
	    
	    // $plugin_folder = get_plugins( '/' . plugin_basename( dirname( __FILE__ ) ) );
	    // $plugin_file = basename( ( __FILE__ ) );
	    $plugin_folder = get_plugins( '/' . plugin_basename( WPLISTER_PATH ) );
	    $plugin_file = 'wp-lister.php';

	    return $plugin_folder[$plugin_file]['Version'];

	}

}

