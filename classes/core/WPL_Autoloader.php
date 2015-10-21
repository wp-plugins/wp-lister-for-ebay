<?php

class WPL_Autoloader {

	/**
	 * Namespace.
	 */
	protected static $namespaces = array(
	    'WPL'
	);

	/**
	 * class table
	 */
	protected static $class_cache = array(

		// core
		'WPLE_MemCache'      		=> '/classes/core/WPL_MemCache.php',
		'WPLE_AdminMessages'   		=> '/classes/core/WPL_AdminMessages.php',

		// helper
		'WPLE_UpgradeHelper'   		=> '/classes/helper/WPLE_UpgradeHelper.php',
		'WPLE_ListingQueryHelper'   => '/classes/helper/WPLE_ListingQueryHelper.php',
		'WPLE_ValidationHelper'     => '/classes/helper/WPLE_ValidationHelper.php',

		// integration
		'ProductWrapper'   			=> '/classes/integration/ProductWrapper_woo.php',
		'OrderWrapper'   			=> '/classes/integration/OrderWrapper_woo.php',
		'WC_Product_Ebay'   		=> '/classes/integration/WooEbayProduct.php',

		// models		
		'WPLE_eBaySite'         	=> '/classes/model/eBaySite.php',
		'WPLE_eBayAccount'      	=> '/classes/model/eBayAccount.php',

	);

	/**
	 * @param string $className
	 * @return string|false
	 */
	public static function autoload($className)
	{

	    if ( array_key_exists( $className, self::$class_cache ) ) {
   			return include WPLISTER_PATH . self::$class_cache[ $className ];
	    } elseif ( ( $classPath = self::getClassPath($className) ) !== false ) {
	        return include $classPath;
	    } else {
	        return false;
	    }

	}

	public static function autoloadEbayClasses($className)
	{

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
			$className = str_replace( 'WPLE_', '', $className );
            $path = WPLISTER_PATH . '/classes/model/' . $className . '.php';
            if (is_readable($path)) {
                return $path;
            }			
		}

		// load pages
		if ( 'Page' == substr($className, -4) ) {
			$className = str_replace( 'WPLE_', '', $className );
            $path = WPLISTER_PATH . '/classes/page/' . $className . '.php';
            if (is_readable($path)) {
                return $path;
            }			
		}

		// load tables
		if ( 'Table' == substr($className, -5) ) {
			$className = str_replace( 'WPLE_', '', $className );
            $path = WPLISTER_PATH . '/classes/table/' . $className . '.php';
            if (is_readable($path)) {
                return $path;
            }			
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

}

