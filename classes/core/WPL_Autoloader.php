<?php

class WPL_Autoloader {

	/**
	 * Namespace.
	 */
	protected static $namespaces = array(
	    'WPL'
	);

	/**
	 * @param string $className
	 * @return string|false
	 */
	public static function autoload($className)
	{

	    if (($classPath = self::getClassPath($className)) !== false) {
	        return include $classPath;
	    } else {
	        return false;
	    }

	}

	public static function autoloadEbayClasses($className)
	{
		// global $wpl_logger;
		// $wpl_logger->info('autoloadEbayClasses: '.$className);

	    if (($classPath = self::getEbayClassPath($className)) !== false) {
	        return include $classPath;
	    } else {
	        return false;
	    }

	}

	/**
	 * @param string $className
	 * @return string|false
	 */
	private static function getClassPath($className)
	{

		// load models
		if ( 'Model' == substr($className, -5) ) {
            $path = WPLISTER_PATH . '/classes/model/' . $className . '.php';
            if (is_readable($path)) {
                return $path;
            }			
		}

		// load pages
		if ( 'Page' == substr($className, -4) ) {
            $path = WPLISTER_PATH . '/classes/page/' . $className . '.php';
            if (is_readable($path)) {
                return $path;
            }			
		}

		// load tables
		if ( 'Table' == substr($className, -5) ) {
            $path = WPLISTER_PATH . '/classes/table/' . $className . '.php';
            if (is_readable($path)) {
                return $path;
            }			
		}

		// load ProductWrapper
		if ( 'ProductWrapper' == $className ) {
			return self::selectProductWrapper();
		}

		// load OrderWrapper
		if ( 'OrderWrapper' == $className ) {
			return self::selectOrderWrapper();
		}

		// conventional autoloader
	    $parts = explode("_", $className);

	    foreach (self::$namespaces as $ns) {
	        if (count($parts) && $parts[0] == $ns) {
	            $path = WPLISTER_PATH . '/classes' . DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, $parts) . '.php';
	            if (is_readable($path)) {
	                return $path;
	            }
	        }
	    }
	    return false;
	}

	/**
	 * @param string $className
	 * @return string|false
	 */
	private static function getEbayClassPath($className)
	{

		// load EbatNs (ebay sdk) classes
        $path = WPLISTER_PATH . '/includes/EbatNs/' . $className . '.php';
        if (is_readable($path)) {
            return $path;
        }			
	    return false;
	}

	// load integration wrapper for active shop plugin
	private static function selectProductWrapper() {

		if ( self::is_plugin_active('woocommerce/woocommerce.php') ) {
			$path = WPLISTER_PATH . '/classes/integration/ProductWrapper_woo.php'; 
		} elseif ( self::is_plugin_active('wp-e-commerce/wp-shopping-cart.php') ) {
			$path = WPLISTER_PATH . '/classes/integration/ProductWrapper_wpec.php';
		} elseif ( self::is_plugin_active('jigoshop/jigoshop.php') ) {
			$path = WPLISTER_PATH . '/classes/integration/ProductWrapper_jigo.php';
		} elseif ( self::is_plugin_active('shopp/Shopp.php') ) {
			$path = WPLISTER_PATH . '/classes/integration/ProductWrapper_shopp.php';
		} elseif ( self::is_plugin_active('marketpress/marketpress.php') ) {
			$path = WPLISTER_PATH . '/classes/integration/ProductWrapper_mp.php';
		} else {
			$path = WPLISTER_PATH . '/classes/integration/ProductWrapper.php';			
		}

        if (is_readable($path)) {
            return $path;
        } else {
        	return false;
        }

	}

	// load integration wrapper for active shop plugin
	private static function selectOrderWrapper() {

		if ( self::is_plugin_active('woocommerce/woocommerce.php') ) {
			$path = WPLISTER_PATH . '/classes/integration/OrderWrapper_woo.php'; 
		} elseif ( self::is_plugin_active('wp-e-commerce/wp-shopping-cart.php') ) {
			$path = WPLISTER_PATH . '/classes/integration/OrderWrapper_wpec.php';
		// } elseif ( self::is_plugin_active('shopp/Shopp.php') ) {
		// 	$path = WPLISTER_PATH . '/classes/integration/OrderWrapper_shopp.php';
		// } elseif ( self::is_plugin_active('marketpress/marketpress.php') ) {
		// 	$path = WPLISTER_PATH . '/classes/integration/OrderWrapper_mp.php';
		} else {
			$path = WPLISTER_PATH . '/classes/integration/OrderWrapper.php';			
		}

        if (is_readable($path)) {
            return $path;
        } else {
        	return false;
        }

	}

	// check if given WordPress plugin is active
	private static function is_plugin_active( $plugin ) {

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

