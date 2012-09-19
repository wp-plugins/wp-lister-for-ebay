<?php
/**
 * WPL_BasePlugin
 *
 * This class contains general purpose methods that are useful for most plugins.
 * (most methods were moved to WPL_Core...)
 */

class WPL_BasePlugin extends WPL_Core {
	
	public function __construct() {
		parent::__construct();

		self::$PLUGIN_NAME = basename(__FILE__);
		self::$PLUGIN_PATH = plugin_basename( dirname(__FILE__) );
		self::$PLUGIN_DIR = WP_PLUGIN_DIR.DS.self::$PLUGIN_PATH.DS;
		self::$PLUGIN_URL = WP_PLUGIN_URL.'/'.self::$PLUGIN_PATH.'/';

		// add link to settings on plugins page
		add_action( 'plugin_action_links', array( &$this, 'onWpPluginActionLinks' ), 10, 4 );

		// required for saving custom screen options 
		add_filter('set-screen-option', array( &$this, 'set_screen_option_handler' ), 100, 3);
	}
	
	
	
	// add link to settings on plugins page
	public function onWpPluginActionLinks( $inaLinks, $insFile ) {
		// if ( $insFile == plugin_basename( __FILE__ ) ) {
		if ( $insFile == 'wp-lister/wp-lister.php' ) {
			$sSettingsLink = '<a href="'.admin_url( "admin.php" ).'?page=wplister-settings">' . __( 'Settings', 'wplister' ) . '</a>';
			array_unshift( $inaLinks, $sSettingsLink );
		}
		return $inaLinks;
	}
	
	// required for saving custom screen options 
	function set_screen_option_handler($status, $option, $value) {
  		return $value;
	}



}

