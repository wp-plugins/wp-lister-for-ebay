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

		self::$PLUGIN_URL = WPLISTER_URL;
		self::$PLUGIN_DIR = WPLISTER_PATH;

		// add link to settings on plugins page
		add_action( 'plugin_action_links', array( &$this, 'onWpPluginActionLinks' ), 10, 4 );

		// required for saving custom screen options 
		add_filter('set-screen-option', array( &$this, 'set_screen_option_handler' ), 100, 3);

		$this->initErrorHandler();
	}
	
	// init error handler
	public function initErrorHandler() {

		if ( ! is_admin() ) return;
		if ( ! self::getOption( 'php_error_handling' ) ) return;

        // regard error handling option
        // second bit (2) will register shutdown handler if set
        if ( 2 & get_option( 'wplister_php_error_handling', 0 ) )
			register_shutdown_function( array( $this, 'shutdown_handler' ) );			

	}

	function shutdown_handler() {
		// remember not to call external functions or methods from a shutdown handler as they might or might not be executed.

		// get last error
	    $error = error_get_last();
	    if ( $error == NULL ) return;

		// check if is ajax - doesn't work as it should yet...
		$is_ajax = ( defined( 'DOING_AJAX' ) && DOING_AJAX ) || ( defined( 'WOOCOMMERCE_CHECKOUT' ) && WOOCOMMERCE_CHECKOUT );
		if ( $is_ajax ) echo "/* ";

	    // if fatal error
	    if( $error['type'] === E_ERROR ) {
	        // fatal error has occurred
	        echo "<pre>FATAL ERROR:\n";print_r($error);echo"</pre>";

	        // backtrace - doesn't work as it will only show the shutdown handler :-(
			// $e = new Exception;
			// echo "<pre>Ex. Trace  :\n";print_r( $e->getTraceAsString() );echo"</pre>";

	    } else {

        	// third bit (4) will show non-fatal errors too
        	if ( 4 & get_option( 'wplister_php_error_handling', 0 ) )
    	    	echo "<pre>OK - last error was: \n";print_r($error);echo"</pre>";

	    }

		if ( $is_ajax ) echo " */";
	}
	
	// add link to settings on plugins page
	public function onWpPluginActionLinks( $inaLinks, $insFile ) {
		// if ( $insFile == plugin_basename( __FILE__ ) ) {
		if ( $insFile == 'wp-lister-ebay/wp-lister-ebay.php' ) {
			$sSettingsLink = '<a href="'.admin_url( "admin.php" ).'?page=wplister-settings">' . __( 'Settings', 'wplister' ) . '</a>';
			array_unshift( $inaLinks, $sSettingsLink );
		}
		return $inaLinks;
	}
	
	// required for saving custom screen options 
	function set_screen_option_handler($status, $option, $value) {
  		return $value;
	}


	// check if permissions require updating 
	function checkPermissions() {

		$role = get_role( 'administrator' );

		// check if admin has manage_ebay_options capability
		if ( ! isset( $role->capabilities['manage_ebay_options'] ) ) {

			$Setup = new WPL_Setup();
			$Setup->updatePermissions();

		}

	} // checkPermissions()


}

