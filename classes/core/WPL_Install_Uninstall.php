<?php

class WPLister_Install {
	
	public function __construct( $file = false ) {
		if ( $file ) {
			register_activation_hook( $file, array( &$this, 'onWpActivatePlugin' ) );
			add_action( 'wpmu_new_blog', array( &$this, 'onWpmuNewBlog' ), 10, 6 );
		}
	}
 
	public function onWpActivatePlugin( $networkwide ) {
		global $wpdb;
        // global $wpl_logger;
        // $wpl_logger = new WPL_Logger();
		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

	    // check for multisite installation
	    if (function_exists('is_multisite') && is_multisite()) {

	        // check if it is a network activation - if so, run the activation function for each blog id
	        if ($networkwide) {
                // $old_blog = $wpdb->blogid;

	            // Get all blog ids
	            $blogids = $wpdb->get_col($wpdb->prepare("SELECT blog_id FROM $wpdb->blogs"));
	            foreach ($blogids as $blog_id) {

	                switch_to_blog($blog_id);

					$this->createOptions( $networkwide );
					$this->createFolders();
					// $this->createTables();

					restore_current_blog();
	            }

	            // switch_to_blog($old_blog);
	            // return;
	        }   

	    } else {
	    	// no multisite
			$this->createOptions( $networkwide );
			$this->createFolders();
			// $this->createTables();
	    }

        // debug:
        // echo "<br> blogid: ".$wpdb->blogid;
        // echo "<br> networkwide: ".print_r($networkwide,1);
        // echo "<br> is_main_site(): ".print_r(is_main_site(),1);
        // echo "<br> get_current_site(): ".print_r(get_current_site(),1);
        // die();

	}
	
	public function onWpmuNewBlog( $blog_id, $user_id, $domain, $path, $site_id, $meta ) {
	    if (is_plugin_active_for_network('wp-lister/wp-lister.php')) {
	        switch_to_blog($blog_id);
			$this->createOptions( $networkwide );
			$this->createFolders();
			// $this->createTables();
			restore_current_blog();
	    }
	}

	public function createOptions( $networkwide ) {

        if ( $networkwide ) {
			WPL_WPLister::addOption( 'is_network_activated', '1' );
			WPL_WPLister::addOption( 'is_enabled', 'Y' );
        } else {
			WPL_WPLister::addOption( 'is_network_activated', '0' );
			WPL_WPLister::addOption( 'is_enabled', 'Y' );
        }

		WPL_WPLister::addOption( 'ebay_token',			'' );
		WPL_WPLister::addOption( 'ebay_site_id',		'0' );
		WPL_WPLister::addOption( 'sandbox_enabled',		'0' );
		
		WPL_WPLister::addOption( 'paypal_email',		'' );
		WPL_WPLister::addOption( 'cron_auctions',		'' );
		WPL_WPLister::addOption( 'log_level',			'' );
		WPL_WPLister::addOption( 'log_to_db',			'0' );
		WPL_WPLister::addOption( 'uninstall',			'0' );
		WPL_WPLister::addOption( 'db_version',			'1' );
		WPL_WPLister::addOption( 'ebay_update_mode',	'order' );

		WPL_WPLister::addOption( 'setup_next_step',		'1' );

	}
	
	public function createFolders() {
		// global $wpl_logger;
		// $wpl_logger->info('creating wp-content/uploads/wp-lister/templates etc.');		

		// make subdirectories in wp-content/uploads
		$uploads = wp_upload_dir();
		$uploaddir = $uploads['basedir'];

		$wpldir = $uploaddir . '/wp-lister';
		if ( !is_dir($wpldir) ) mkdir($wpldir);

		$tpldir = $wpldir . '/templates';
		if ( !is_dir($tpldir) ) mkdir($tpldir);

		// $wpl_logger->info('template folder: '.$tpldir);		
	
	}
	
	public function createTables() {
		global $wpdb;
        global $wpl_logger;
        // $wpl_logger = new WPL_Logger();
		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');


		$wpl_logger->info("creating table {$wpdb->prefix}ebay_auctions");		

		// create table: ebay_auctions
		$sql = "CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}ebay_auctions` (
		  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
		  `ebay_id` bigint(255) DEFAULT NULL,
		  `auction_title` varchar(255) DEFAULT NULL,
		  `auction_type` varchar(255) DEFAULT NULL,
		  `listing_duration` varchar(255) DEFAULT NULL,
		  `date_created` datetime DEFAULT NULL,
		  `date_published` datetime DEFAULT NULL,
		  `date_finished` datetime DEFAULT NULL,
		  `end_date` datetime DEFAULT NULL,
		  `price` float DEFAULT NULL,
		  `quantity` int(11) DEFAULT NULL,
		  `quantity_sold` int(11) DEFAULT NULL,
		  `status` varchar(50) DEFAULT NULL,
		  `details` text,
		  `ViewItemURL` varchar(255) DEFAULT NULL,
		  `GalleryURL` varchar(255) DEFAULT NULL,
		  `post_content` text,
		  `post_id` int(11) DEFAULT NULL,
		  `profile_id` int(11) DEFAULT NULL,
		  `profile_data` text,
		  `template` varchar(255) DEFAULT '',
		  `fees` float DEFAULT NULL,
		  PRIMARY KEY  (`id`)
		);";
		#dbDelta($sql);
		$result = $wpdb->query($sql);

		$wpl_logger->info($sql);		
		$wpl_logger->info(mysql_error());		
		
		
		// create table: ebay_categories
		$sql = "CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}ebay_categories` (
		  `cat_id` bigint(16) DEFAULT NULL,
		  `parent_cat_id` bigint(11) DEFAULT NULL,
		  `level` int(11) DEFAULT NULL,
		  `leaf` tinyint(4) DEFAULT NULL,
		  `version` int(11) DEFAULT NULL,
		  `cat_name` varchar(255) DEFAULT NULL,
		  `wp_term_id` int(11) DEFAULT NULL,
		  KEY `cat_id` (`cat_id`),
		  KEY `parent_cat_id` (`parent_cat_id`)		
		);";
		$wpdb->query($sql);
		
		
		// create table: ebay_store_categories
		$sql = "CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}ebay_store_categories` (
		  `cat_id` bigint(20) DEFAULT NULL,
		  `parent_cat_id` bigint(20) DEFAULT NULL,
		  `level` int(11) DEFAULT NULL,
		  `leaf` tinyint(4) DEFAULT NULL,
		  `version` int(11) DEFAULT NULL,
		  `cat_name` varchar(255) DEFAULT NULL,
		  `order` int(11) DEFAULT NULL,
		  `wp_term_id` int(11) DEFAULT NULL,
		  KEY `cat_id` (`cat_id`),
		  KEY `parent_cat_id` (`parent_cat_id`)		
		);";
		$wpdb->query($sql);
		
		
		// create table: ebay_payment
		$sql = "CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}ebay_payment` (
		  `payment_name` varchar(255) DEFAULT NULL,
		  `payment_description` varchar(255) DEFAULT NULL,
		  `version` int(11) DEFAULT NULL	
		);";
		$wpdb->query($sql);
		
		
		// create table: ebay_profiles
		$sql = "CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}ebay_profiles` (
		  `profile_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
		  `profile_name` varchar(255) DEFAULT NULL,
		  `profile_description` varchar(255) DEFAULT NULL,
		  `listing_duration` varchar(255) DEFAULT NULL,
		  `type` varchar(255) DEFAULT NULL,
		  `details` text,
		  `conditions` text,
		  PRIMARY KEY  (`profile_id`)	
		);";
		$wpdb->query($sql);
		
		
		
		// create table: ebay_shipping
		$sql = "CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}ebay_shipping` (
		  `service_id` int(11) DEFAULT NULL,
		  `service_name` varchar(255) DEFAULT NULL,
		  `service_description` varchar(255) DEFAULT NULL,
		  `carrier` varchar(255) DEFAULT NULL,
		  `international` tinyint(4) DEFAULT NULL,
		  `version` int(11) DEFAULT NULL	
		);";
		$wpdb->query($sql);
		
		// create table: ebay_transactions
		$sql = "CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}ebay_transactions` (
		  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
		  `item_id` bigint(255) DEFAULT NULL,
		  `transaction_id` bigint(255) DEFAULT NULL,
		  `date_created` datetime DEFAULT NULL,
		  `item_title` varchar(255) DEFAULT NULL,
		  `price` float DEFAULT NULL,
		  `quantity` int(11) DEFAULT NULL,
		  `status` varchar(50) DEFAULT NULL,
		  `details` text,
		  `post_id` int(11) DEFAULT NULL,
		  `buyer_userid` varchar(255) DEFAULT NULL,
		  `buyer_name` varchar(255) DEFAULT NULL,
		  `buyer_email` varchar(255) DEFAULT NULL,
		  `eBayPaymentStatus` varchar(50) DEFAULT NULL,
		  `CheckoutStatus` varchar(50) DEFAULT NULL,
		  `ShippingService` varchar(50) DEFAULT NULL,
		  `PaymentMethod` varchar(50) DEFAULT NULL,
		  `ShippingAddress_City` varchar(50) DEFAULT NULL,
		  `CompleteStatus` varchar(50) DEFAULT NULL,
		  `LastTimeModified` datetime DEFAULT NULL,
		  PRIMARY KEY (`id`)
  		);";
		$wpdb->query($sql);
		
		// create table: ebay_log
		$sql = "CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}ebay_log` (
		  `id` int(11) NOT NULL AUTO_INCREMENT,
		  `timestamp` datetime DEFAULT NULL,
		  `request_url` text DEFAULT NULL,
		  `request` text DEFAULT NULL,
		  `response` text DEFAULT NULL,
		  `callname` varchar(64) DEFAULT NULL,
		  `success` varchar(16) DEFAULT NULL,
		  `ebay_id` bigint(255) DEFAULT NULL,
		  `user_id` int(11) DEFAULT NULL,	
		  PRIMARY KEY (`id`)	
		);";
		$wpdb->query($sql);

		// mysql updates - insert new columns
		$this->add_column_if_not_exist( $wpdb->prefix.'ebay_profiles', 'conditions', 'TEXT' );

	}

	// mysql update helper method
	// from http://www.edmondscommerce.co.uk/mysql/mysql-add-column-if-not-exists-php-function/
	function add_column_if_not_exist( $table, $column, $column_attr = "VARCHAR( 255 ) NULL" ){
	    $exists = false;
	    $columns = mysql_query("show columns from $table");
	    while($col = mysql_fetch_assoc($columns)){
	        if($col['Field'] == $column){
	            $exists = true;
	            break;
	        }
	    }      
	    if(!$exists){
	        mysql_query("ALTER TABLE `$table` ADD `$column` $column_attr ");
	    }
	}

}

class WPLister_Uninstall {
	
	// TODO: when uninstalling, maybe have an option to backup and restore settings
	
	public function __construct( $file = false ) {
		if ($file) register_deactivation_hook( $file, array( &$this, 'onWpDeactivatePlugin' ) );
	}
	
	public function onWpDeactivatePlugin( $networkwide ) {
		global $wpdb;

	    // check for multisite installation
	    if (function_exists('is_multisite') && is_multisite()) {

	        // check if it is a network (de)activation - if so, run the (de)activation function for each blog id
	        if ($networkwide) {

	            // Get all blog ids
	            $blogids = $wpdb->get_col($wpdb->prepare("SELECT blog_id FROM $wpdb->blogs"));
	            foreach ($blogids as $blog_id) {

	                switch_to_blog($blog_id);
					$this->deactivatePlugin();
					restore_current_blog();
	            }

	        }   

	    } else {
	    	// no multisite
			$this->deactivatePlugin();
	    }

	}
	
	public function deactivatePlugin() {
		global $wpdb;

		// always uninstall on multisite networks
		if ( ( is_multisite() ) || ( WPL_WPLister::getOption('uninstall') == 1 ) ) {

			// remove tables
			$wpdb->query( 'DROP TABLE '.$wpdb->prefix.'ebay_auctions' );
			$wpdb->query( 'DROP TABLE '.$wpdb->prefix.'ebay_categories' );
			$wpdb->query( 'DROP TABLE '.$wpdb->prefix.'ebay_store_categories' );
			$wpdb->query( 'DROP TABLE '.$wpdb->prefix.'ebay_payment' );
			$wpdb->query( 'DROP TABLE '.$wpdb->prefix.'ebay_profiles' );
			$wpdb->query( 'DROP TABLE '.$wpdb->prefix.'ebay_shipping' );
			$wpdb->query( 'DROP TABLE '.$wpdb->prefix.'ebay_transactions' );
			$wpdb->query( 'DROP TABLE '.$wpdb->prefix.'ebay_log' );			
			$wpdb->query( 'DROP TABLE '.$wpdb->prefix.'ebay_jobs' );			

			// remove options
			$wpdb->query( 'DELETE FROM '.$wpdb->prefix."options WHERE option_name LIKE 'wplister_%' " );

			// clear options from cache
			wp_cache_flush();

		}

	}
}

