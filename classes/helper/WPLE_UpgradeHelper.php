<?php

class WPLE_UpgradeHelper {

	const DB_VERSION = 47;
	
	// upgrade db - if required
	static public function maybe_upgrade_db() {

		$current_db_version = get_option('wplister_db_version', 0);
		if ( $current_db_version >= self::DB_VERSION ) return;

		self::upgradeDB();
	}

	// upgrade db
	static public function upgradeDB() {
		global $wpdb;

		$db_version = get_option('wplister_db_version', 0);
		$hide_message = $db_version == 0 ? true : false;
		$msg = false;

		// initialize db with version 4
		if ( 4 > $db_version ) {
			$new_db_version = 4;
		

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
			$wpdb->query($sql);	echo $wpdb->last_error;
						
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
			$wpdb->query($sql);	echo $wpdb->last_error;
						
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
			$wpdb->query($sql);	echo $wpdb->last_error;
						
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
			$wpdb->query($sql);	echo $wpdb->last_error;
						
			// create table: ebay_shipping
			$sql = "CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}ebay_shipping` (
			  `service_id` int(11) DEFAULT NULL,
			  `service_name` varchar(255) DEFAULT NULL,
			  `service_description` varchar(255) DEFAULT NULL,
			  `carrier` varchar(255) DEFAULT NULL,
			  `international` tinyint(4) DEFAULT NULL,
			  `version` int(11) DEFAULT NULL	
			);";
			$wpdb->query($sql);	echo $wpdb->last_error;
			
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
			$wpdb->query($sql);	echo $wpdb->last_error;
			
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
			$wpdb->query($sql);	echo $wpdb->last_error;


			// $db_version = $new_db_version;
			update_option('wplister_db_version', $new_db_version);
			$msg  = __('Database was upgraded to version', 'wplister') .' '. $new_db_version . '.';

		}
		
		/*
		// upgrade to version 2
		if ( 2 > $db_version ) {
			$new_db_version = 2;
		
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
			$wpdb->query($sql);	echo $wpdb->last_error;

			update_option('wplister_db_version', $new_db_version);
			$msg  = __('Database was upgraded to version', 'wplister') .' '. $new_db_version . '.';
		}
		
		// upgrade to version 3
		if ( 3 > $db_version ) {
			$new_db_version = 3;

			// rename column in table: ebay_categories
			$sql = "ALTER TABLE `{$wpdb->prefix}ebay_categories`
			        CHANGE wpsc_category_id wp_term_id INTEGER ";
			$wpdb->query($sql);	echo $wpdb->last_error;

			// rename column in table: ebay_store_categories
			$sql = "ALTER TABLE `{$wpdb->prefix}ebay_store_categories`
			        CHANGE wpsc_category_id wp_term_id INTEGER ";
			$wpdb->query($sql);	echo $wpdb->last_error;
			
			update_option('wplister_db_version', $new_db_version);
			$msg  = __('Database was upgraded to version', 'wplister') .' '. $new_db_version . '.';
		}
		
		// upgrade to version 4
		if ( 4 > $db_version ) {
			$new_db_version = 4;

			// set column type to bigint in table: ebay_store_categories
			$sql = "ALTER TABLE `{$wpdb->prefix}ebay_store_categories`
			        CHANGE cat_id cat_id BIGINT ";
			$wpdb->query($sql);	echo $wpdb->last_error;
			
			// set column type to bigint in table: ebay_store_categories
			$sql = "ALTER TABLE `{$wpdb->prefix}ebay_store_categories`
			        CHANGE parent_cat_id parent_cat_id BIGINT ";
			$wpdb->query($sql);	echo $wpdb->last_error;
			
			update_option('wplister_db_version', $new_db_version);
			$msg  = __('Database was upgraded to version', 'wplister') .' '. $new_db_version . '.';
		}
		*/
	
		// TODO: include upgrade 5-9 in WPLister_Install class
		
		// upgrade to version 5
		if ( 5 > $db_version ) {
			$new_db_version = 5;
		
			// create table: ebay_log
			$sql = "CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}ebay_jobs` (
			  `id` int(11) NOT NULL AUTO_INCREMENT,
			  `job_key` varchar(64) DEFAULT NULL,
			  `job_name` varchar(64) DEFAULT NULL,
			  `tasklist` text DEFAULT NULL,
			  `results` text DEFAULT NULL,
			  `success` varchar(16) DEFAULT NULL,
			  `date_created` datetime DEFAULT NULL,
			  `date_finished` datetime DEFAULT NULL,
			  `user_id` int(11) DEFAULT NULL,	
			  PRIMARY KEY (`id`)	
			);";
			$wpdb->query($sql);	echo $wpdb->last_error;

			update_option('wplister_db_version', $new_db_version);
			$msg  = __('Database was upgraded to version', 'wplister') .' '. $new_db_version . '.';
		}
		

		// upgrade to version 6
		if ( 6 > $db_version ) {
			$new_db_version = 6;

			// add columns to ebay_shipping table
			$sql = "ALTER TABLE `{$wpdb->prefix}ebay_shipping`
			        ADD COLUMN `ShippingCategory` varchar(64) DEFAULT NULL AFTER `carrier`, 
			        ADD COLUMN `WeightRequired` int(10) UNSIGNED NOT NULL DEFAULT 0 AFTER `international`, 
			        ADD COLUMN `DimensionsRequired` int(10) UNSIGNED NOT NULL DEFAULT 0 AFTER `international`, 
			        ADD COLUMN `isCalculated` int(10) UNSIGNED NOT NULL DEFAULT 0 AFTER `international`, 
			        ADD COLUMN `isFlat` int(10) UNSIGNED NOT NULL DEFAULT 0 AFTER `international`;
			";
			$wpdb->query($sql);	echo $wpdb->last_error;
			
			update_option('wplister_db_version', $new_db_version);
			$msg  = __('Database was upgraded to version', 'wplister') .' '. $new_db_version . '.';
		}
		

		// upgrade to version 7  (0.9.7.9)
		if ( 7 > $db_version ) {
			$new_db_version = 7;

			// set admin_email as default license_email
			update_option('wplister_license_email', get_bloginfo('admin_email') );

			update_option('wplister_db_version', $new_db_version);
			$msg  = __('Database was upgraded to version', 'wplister') .' '. $new_db_version . '.';
		}
		

		// upgrade to version 8
		if ( 8 > $db_version ) {
			$new_db_version = 8;

			// add columns to ebay_shipping table
			$sql = "ALTER TABLE `{$wpdb->prefix}ebay_profiles`
			        ADD COLUMN `category_specifics` text DEFAULT NULL;
			";
			$wpdb->query($sql);	echo $wpdb->last_error;
			
			update_option('wplister_db_version', $new_db_version);
			$msg  = __('Database was upgraded to version', 'wplister') .' '. $new_db_version . '.';
		}
		
		// upgrade to version 9  (1.0)
		if ( 9 > $db_version ) {
			$new_db_version = 9;

			// add update channel option
			update_option('wplister_update_channel', 'stable');
			update_option('wple_update_channel', 'stable');
			
			update_option('wplister_db_version', $new_db_version);
			$msg  = __('Database was upgraded to version', 'wplister') .' '. $new_db_version . '.';
		}
		
		// upgrade to version 10  (1.0.7)
		if ( 10 > $db_version ) {
			$new_db_version = 10;

			// add column to ebay_transactions table
			$sql = "ALTER TABLE `{$wpdb->prefix}ebay_transactions`
			        ADD COLUMN `wp_order_id` int(10) UNSIGNED NOT NULL DEFAULT 0 AFTER `post_id`
			";
			$wpdb->query($sql);	echo $wpdb->last_error;
	
			update_option('wplister_db_version', $new_db_version);
			$msg  = __('Database was upgraded to version', 'wplister') .' '. $new_db_version . '.';
		}

		// upgrade to version 11  (1.0.8.8)
		if ( 11 > $db_version ) {
			$new_db_version = 11;

			// fetch available dispatch times - disabled in 2.0.3
			// if ( get_option('wplister_ebay_token') != '' ) {
			// 	$this->initEC();
			// 	$result = $this->EC->loadDispatchTimes();
			// 	$this->EC->closeEbay();		
			// }
			
			update_option('wplister_db_version', $new_db_version);
			$msg  = __('Database was upgraded to version', 'wplister') .' '. $new_db_version . '.';
		}
		

		// upgrade to version 12  (1.0.9.8)
		if ( 12 > $db_version ) {
			$new_db_version = 12;

			// fetch all transactions
			$sql = "SELECT id FROM `{$wpdb->prefix}ebay_transactions` ";
			$items = $wpdb->get_results($sql);	echo $wpdb->last_error;

			// find and assign orders
			$tm = new TransactionsModel();
			foreach ($items as $transaction) {

				// fetch item details
				$item = $tm->getItem( $transaction->id );
				$details = $item['details'];

				// build order title (WooCommerce only)
			    $post_title = 'Order &ndash; '.date('F j, Y @ h:i A', strtotime( $details->CreatedDate ) );

			    // find created order
				$sql = "
					SELECT ID FROM `{$wpdb->prefix}posts`
					WHERE post_title = '$post_title'
					  AND post_status = 'publish'
				";
				$post_id = $wpdb->get_var($sql);	echo $wpdb->last_error;
				
				// set order_id for transaction
				$tm->updateWpOrderID( $transaction->id, $post_id );							    

				// Update post data
				update_post_meta( $post_id, '_transaction_id', $transaction->id );
				update_post_meta( $post_id, '_ebay_item_id', $item['item_id'] );
				update_post_meta( $post_id, '_ebay_transaction_id', $item['transaction_id'] );

			}
			
			update_option('wplister_db_version', $new_db_version);
			$msg  = __('Database was upgraded to version', 'wplister') .' '. $new_db_version . '.';
		}


		// upgrade to version 13  (1.1.0.2)
		if ( 13 > $db_version ) {
			$new_db_version = 13;

			// add column to ebay_transactions table
			$sql = "ALTER TABLE `{$wpdb->prefix}ebay_transactions`
			        ADD COLUMN `OrderLineItemID` varchar(64) DEFAULT NULL AFTER `transaction_id`
			";
			$wpdb->query($sql);	echo $wpdb->last_error;
	
			update_option('wplister_db_version', $new_db_version);
			$msg  = __('Database was upgraded to version', 'wplister') .' '. $new_db_version . '.';
		}

		// upgrade to version 14  (1.1.0.4)
		if ( 14 > $db_version ) {
			$new_db_version = 14;

			// remove invalid transactions - update on next cron schedule
			$sql = "DELETE FROM `{$wpdb->prefix}ebay_transactions`
			        WHERE transaction_id = 0
			";
			$wpdb->query($sql);	echo $wpdb->last_error;
	
			update_option('wplister_db_version', $new_db_version);
			$msg  = __('Database was upgraded to version', 'wplister') .' '. $new_db_version . '.';
		}

		// upgrade to version 15  (1.1.5.4)
		if ( 15 > $db_version ) {
			$new_db_version = 15;

			// add column to ebay_categories table
			$sql = "ALTER TABLE `{$wpdb->prefix}ebay_categories`
			        ADD COLUMN `site_id` int(10) UNSIGNED DEFAULT NULL AFTER `wp_term_id`
			";
			$wpdb->query($sql);	echo $wpdb->last_error;
	
			update_option('wplister_db_version', $new_db_version);
			$msg  = __('Database was upgraded to version', 'wplister') .' '. $new_db_version . '.';
		}

		// upgrade to version 16  (1.1.6.3)
		if ( 16 > $db_version ) {
			$new_db_version = 16;

			// add column to ebay_auctions table
			$sql = "ALTER TABLE `{$wpdb->prefix}ebay_auctions`
			        ADD COLUMN `history` TEXT AFTER `fees`
			";
			$wpdb->query($sql);	echo $wpdb->last_error;
	
			update_option('wplister_db_version', $new_db_version);
			$msg  = __('Database was upgraded to version', 'wplister') .' '. $new_db_version . '.';
		}

		// upgrade to version 17  (1.2.0.12)
		if ( 17 > $db_version ) {
			$new_db_version = 17;

			// fetch available shipping packages - disabled in 2.0.3
			// if ( get_option('wplister_ebay_token') != '' ) {
			// 	$this->initEC();
			// 	$result = $this->EC->loadShippingPackages();
			// 	$this->EC->closeEbay();		
			// }
			
			update_option('wplister_db_version', $new_db_version);
			$msg  = __('Database was upgraded to version', 'wplister') .' '. $new_db_version . '.';
		}

		// upgrade to version 18 (1.2.0.18)
		if ( 18 > $db_version ) {
			$new_db_version = 18;

			// set column type to bigint in table: ebay_auctions
			$sql = "ALTER TABLE `{$wpdb->prefix}ebay_auctions`
			        CHANGE post_id post_id BIGINT ";
			$wpdb->query($sql);	echo $wpdb->last_error;
			
			// set column type to bigint in table: ebay_transactions
			$sql = "ALTER TABLE `{$wpdb->prefix}ebay_transactions`
			        CHANGE post_id post_id BIGINT ";
			$wpdb->query($sql);	echo $wpdb->last_error;
			
			// set column type to bigint in table: ebay_transactions
			$sql = "ALTER TABLE `{$wpdb->prefix}ebay_transactions`
			        CHANGE wp_order_id wp_order_id BIGINT ";
			$wpdb->query($sql);	echo $wpdb->last_error;
			
			update_option('wplister_db_version', $new_db_version);
			$msg  = __('Database was upgraded to version', 'wplister') .' '. $new_db_version . '.';
		}

		// upgrade to version 19  (1.2.1.5)
		if ( 19 > $db_version ) {
			$new_db_version = 19;

			// add column to ebay_auctions table
			$sql = "ALTER TABLE `{$wpdb->prefix}ebay_auctions`
			        ADD COLUMN `eps` TEXT AFTER `history`
			";
			$wpdb->query($sql);	echo $wpdb->last_error;
	
			update_option('wplister_db_version', $new_db_version);
			$msg  = __('Database was upgraded to version', 'wplister') .' '. $new_db_version . '.';
		}

		// upgrade to version 20  (1.2.2.16)
		if ( 20 > $db_version ) {
			$new_db_version = 20;

			// add column to ebay_transactions table
			$sql = "ALTER TABLE `{$wpdb->prefix}ebay_transactions`
			        ADD COLUMN `history` TEXT AFTER `details`
			";
			$wpdb->query($sql);	echo $wpdb->last_error;
	
			update_option('wplister_db_version', $new_db_version);
			$msg  = __('Database was upgraded to version', 'wplister') .' '. $new_db_version . '.';
		}

		// upgrade to version 21  (1.2.2.16)
		if ( 21 > $db_version ) {
			$new_db_version = 21;

			// create table: ebay_orders
			$sql = "CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}ebay_orders` (
			  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
			  `order_id` varchar(128) DEFAULT NULL,
			  `date_created` datetime DEFAULT NULL,
			  `total` float DEFAULT NULL,
			  `status` varchar(50) DEFAULT NULL,
			  `post_id` int(11) DEFAULT NULL,
			  `items` text,
			  `details` text,
			  `history` text,
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
			$wpdb->query($sql);	echo $wpdb->last_error;

			update_option('wplister_db_version', $new_db_version);
			$msg  = __('Database was upgraded to version', 'wplister') .' '. $new_db_version . '.';
		}

		// upgrade to version 22  (1.2.4.7)
		if ( 22 > $db_version ) {
			$new_db_version = 22;

			// add column to ebay_profiles table
			$sql = "ALTER TABLE `{$wpdb->prefix}ebay_profiles`
			        ADD COLUMN `sort_order` int(11) NOT NULL AFTER `type`
			";
			$wpdb->query($sql);	echo $wpdb->last_error;
	
			update_option('wplister_db_version', $new_db_version);
			$msg  = __('Database was upgraded to version', 'wplister') .' '. $new_db_version . '.';
		}

		// upgrade to version 23  (1.2.7.3)
		if ( 23 > $db_version ) {
			$new_db_version = 23;

			// fetch user defined shipping discount profiles - disabled in 2.0.3
			// if ( get_option('wplister_ebay_token') != '' ) {
			// 	$this->initEC();
			// 	$result = $this->EC->loadShippingDiscountProfiles();
			// 	$this->EC->closeEbay();		
			// }
			
			update_option('wplister_db_version', $new_db_version);
			$msg  = __('Database was upgraded to version', 'wplister') .' '. $new_db_version . '.';
		}

		// upgrade to version 24  (1.3.0.12)
		if ( 24 > $db_version ) {
			$new_db_version = 24;

			// add column to ebay_profiles table
			$sql = "ALTER TABLE `{$wpdb->prefix}ebay_auctions`
			        ADD COLUMN `locked` int(11) NOT NULL DEFAULT 0 AFTER `status`
			";
			$wpdb->query($sql);	echo $wpdb->last_error;
	
			update_option('wplister_db_version', $new_db_version);
			$msg  = __('Database was upgraded to version', 'wplister') .' '. $new_db_version . '.';
		}

		// upgrade to version 25  (1.3.0.12)
		if ( 25 > $db_version ) {
			$new_db_version = 25;
			$batch_size = 1000;

			// fetch all imported items
			$sql = "SELECT post_id FROM `{$wpdb->prefix}postmeta` WHERE meta_key = '_ebay_item_source' AND meta_value = 'imported' ";
			$imported_products = $wpdb->get_col($sql);	echo $wpdb->last_error;
			$total_number_of_products = sizeof( $imported_products );

			if ( $total_number_of_products > $batch_size ) {			
				// legacy code removed in 2.0.3
			} else {
				// normal mode - lock all at once

				// lock all imported imported_products
				$where_sql = " 1 = 0 ";
				foreach ($imported_products as $post_id) {
					$where_sql .= " OR post_id = '$post_id' ";
				}
				$sql = "UPDATE `{$wpdb->prefix}ebay_auctions` SET locked = '1' WHERE ( $where_sql ) AND status = 'published' ";
				$wpdb->query( $sql );	echo $wpdb->last_error;

				update_option('wplister_db_version', $new_db_version);
				$msg  = __('Database was upgraded to version', 'wplister') .' '. $new_db_version . '.';				
			}

		}

		// upgrade to version 26 (1.3.0.12)
		if ( 26 > $db_version ) {
			$new_db_version = 26;

			// set column type to mediumtext in table: ebay_auctions
			$sql = "ALTER TABLE `{$wpdb->prefix}ebay_auctions`
			        CHANGE history history MEDIUMTEXT ";
			$wpdb->query($sql);	echo $wpdb->last_error;
			
			// set column type to mediumtext in table: ebay_orders
			$sql = "ALTER TABLE `{$wpdb->prefix}ebay_orders`
			        CHANGE history history MEDIUMTEXT ";
			$wpdb->query($sql);	echo $wpdb->last_error;
			
			// set column type to mediumtext in table: ebay_transactions
			$sql = "ALTER TABLE `{$wpdb->prefix}ebay_transactions`
			        CHANGE history history MEDIUMTEXT ";
			$wpdb->query($sql);	echo $wpdb->last_error;
			
			update_option('wplister_db_version', $new_db_version);
			$msg  = __('Database was upgraded to version', 'wplister') .' '. $new_db_version . '.';
		}

		// upgrade to version 27  (1.3.2.5)
		if ( 27 > $db_version ) {
			$new_db_version = 27;

			// add columns to ebay_categories table
			$sql = "ALTER TABLE `{$wpdb->prefix}ebay_categories`
			        ADD COLUMN `specifics` text AFTER `cat_name`,
			        ADD COLUMN `conditions` text AFTER `cat_name`
			";
			$wpdb->query($sql);	echo $wpdb->last_error;
	
			// add columns to ebay_auctions table
			$sql = "ALTER TABLE `{$wpdb->prefix}ebay_auctions`
			        ADD COLUMN `parent_id` bigint(20) NOT NULL AFTER `post_id`,
			        ADD COLUMN `variations` text AFTER `details`
			";
			$wpdb->query($sql);	echo $wpdb->last_error;
	
			update_option('wplister_db_version', $new_db_version);
			$msg  = __('Database was upgraded to version', 'wplister') .' '. $new_db_version . '.';
		}

		// upgrade to version 28  (1.3.2.10)
		if ( 28 > $db_version ) {
			$new_db_version = 28;

			// create table: ebay_messages
			$sql = "CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}ebay_messages` (
			  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
			  `message_id` varchar(128) DEFAULT NULL,
			  `received_date` datetime DEFAULT NULL,
			  `subject` varchar(255) DEFAULT NULL,
			  `sender` varchar(255) DEFAULT NULL,
			  `flag_read` varchar(1) DEFAULT NULL,
			  `flag_replied` varchar(1) DEFAULT NULL,
			  `flag_flagged` varchar(1) DEFAULT NULL,
			  `item_title` varchar(255) DEFAULT NULL,
			  `item_id` bigint(255) DEFAULT NULL,
			  `folder_id` bigint(255) DEFAULT NULL,
			  `msg_text` text,
			  `msg_content` text,
			  `details` text,
			  `expiration_date` datetime DEFAULT NULL,
			  `response_url` varchar(255) DEFAULT NULL,
			  `status` varchar(50) DEFAULT NULL,
			  PRIMARY KEY (`id`)
	  		);";
			$wpdb->query($sql);	echo $wpdb->last_error;

			update_option('wplister_db_version', $new_db_version);
			$msg  = __('Database was upgraded to version', 'wplister') .' '. $new_db_version . '.';
		}

		// upgrade to version 29  (1.3.2.12)
		if ( 29 > $db_version ) {
			$new_db_version = 29;

			// add columns to ebay_auctions table
			$sql = "ALTER TABLE `{$wpdb->prefix}ebay_auctions`
			        ADD COLUMN `relist_date` datetime DEFAULT NULL AFTER `end_date`
			";
			$wpdb->query($sql);	echo $wpdb->last_error;
	
			update_option('wplister_db_version', $new_db_version);
			$msg  = __('Database was upgraded to version', 'wplister') .' '. $new_db_version . '.';
		}

		// upgrade to version 30  (1.3.4.5)
		if ( 30 > $db_version ) {

			// automatically switch old sites from transaction to order mode
			update_option('wplister_ebay_update_mode', 'order');
			update_option('wplister_db_version', 30);
		}


		// upgrade to version 31  (1.3.5.4)
		if ( 31 > $db_version ) {
			$new_db_version = 31;

			// add indices to ebay_log table
			$sql = "ALTER TABLE `{$wpdb->prefix}ebay_log` ADD INDEX `timestamp` (`timestamp`) ";
			$wpdb->query($sql);	echo $wpdb->last_error;
			$sql = "ALTER TABLE `{$wpdb->prefix}ebay_log` ADD INDEX `callname` (`callname`) ";
			$wpdb->query($sql);	echo $wpdb->last_error;
			$sql = "ALTER TABLE `{$wpdb->prefix}ebay_log` ADD INDEX `success` (`success`) ";
			$wpdb->query($sql);	echo $wpdb->last_error;
	
			update_option('wplister_db_version', $new_db_version);
			$msg  = __('Database was upgraded to version', 'wplister') .' '. $new_db_version . '.';
		}

		// upgrade to version 32  (1.3.5.5)
		if ( 32 > $db_version ) {
			$new_db_version = 32;

			// add column to ebay_transactions table
			$sql = "ALTER TABLE `{$wpdb->prefix}ebay_transactions`
			        ADD COLUMN `order_id` varchar(64) DEFAULT NULL AFTER `transaction_id`
			";
			$wpdb->query($sql);	echo $wpdb->last_error;

			// add indices to ebay_transactions table
			$sql = "ALTER TABLE `{$wpdb->prefix}ebay_transactions` ADD INDEX `item_id` (`item_id`) ";
			$wpdb->query($sql);	echo $wpdb->last_error;
			$sql = "ALTER TABLE `{$wpdb->prefix}ebay_transactions` ADD INDEX `transaction_id` (`transaction_id`) ";
			$wpdb->query($sql);	echo $wpdb->last_error;
			$sql = "ALTER TABLE `{$wpdb->prefix}ebay_transactions` ADD INDEX `order_id` (`order_id`) ";
			$wpdb->query($sql);	echo $wpdb->last_error;
	
			// add index to ebay_orders table
			$sql = "ALTER TABLE `{$wpdb->prefix}ebay_orders` ADD INDEX `order_id` (`order_id`) ";
			$wpdb->query($sql);	echo $wpdb->last_error;
	
			update_option('wplister_db_version', $new_db_version);
			$msg  = __('Database was upgraded to version', 'wplister') .' '. $new_db_version . '.';
		}

		// upgrade to version 33  (1.3.5.6)
		if ( 33 > $db_version ) {
			$new_db_version = 33;

			if ( WPL_Setup::isV2() ) {
				// disable transaction conversion when updating from an ancient version (1.3.5)
				$more_orders_to_process = false;
			} else {
				$more_orders_to_process = isset(WPLE()->pages['tools']) ? WPLE()->pages['tools']->checkTransactions() : false;
			}

			// check if database upgrade is finished yet
			if ( $more_orders_to_process ) {
				$msg  = __('Database upgrade is in progress', 'wplister') .'...';
				if ( ($msg) && (!$hide_message) ) wple_show_message($msg,'info');
				return;
			} else {
				update_option('wplister_db_version', $new_db_version);
				$msg  = __('Database was upgraded to version', 'wplister') .' '. $new_db_version . '.';
			}
		}

		// upgrade to version 34  (1.3.5.7)
		if ( 34 > $db_version ) {
			$new_db_version = 34;

			// fetch exclude shipping locations - disabled in 2.0.3
			// if ( get_option('wplister_ebay_token') != '' ) {
			// 	$this->initEC();
	    	//	$sm = new EbayShippingModel();
    	    //	$result = $sm->downloadExcludeShippingLocations( $this->EC->session );      
			// 	$this->EC->closeEbay();		
			// }
			
			update_option('wplister_db_version', $new_db_version);
			$msg  = __('Database was upgraded to version', 'wplister') .' '. $new_db_version . '.';
		}

		// upgrade to version 35  (1.5.0)
		if ( 35 > $db_version ) {
			$new_db_version = 35;

			// change price column type to DECIMAL(13,2)
			$sql = "ALTER TABLE `{$wpdb->prefix}ebay_auctions`
			        CHANGE price price DECIMAL(13,2) ";
			$wpdb->query($sql);	echo $wpdb->last_error;
						
			$sql = "ALTER TABLE `{$wpdb->prefix}ebay_orders`
			        CHANGE total total DECIMAL(13,2) ";
			$wpdb->query($sql);	echo $wpdb->last_error;
						
			update_option('wplister_db_version', $new_db_version);
			$msg  = __('Database was upgraded to version', 'wplister') .' '. $new_db_version . '.';
		}

		// upgrade to version 36  (1.5.0)
		if ( 36 > $db_version ) {
			$new_db_version = 36;

			// add indices to ebay_auctions table
			$sql = "ALTER TABLE `{$wpdb->prefix}ebay_auctions` ADD INDEX `ebay_id` (`ebay_id`) ";
			$wpdb->query($sql);	echo $wpdb->last_error;
			$sql = "ALTER TABLE `{$wpdb->prefix}ebay_auctions` ADD INDEX `status` (`status`) ";
			$wpdb->query($sql);	echo $wpdb->last_error;
			$sql = "ALTER TABLE `{$wpdb->prefix}ebay_auctions` ADD INDEX `post_id` (`post_id`) ";
			$wpdb->query($sql);	echo $wpdb->last_error;
			$sql = "ALTER TABLE `{$wpdb->prefix}ebay_auctions` ADD INDEX `profile_id` (`profile_id`) ";
			$wpdb->query($sql);	echo $wpdb->last_error;
			$sql = "ALTER TABLE `{$wpdb->prefix}ebay_auctions` ADD INDEX `locked` (`locked`) ";
			$wpdb->query($sql);	echo $wpdb->last_error;
			$sql = "ALTER TABLE `{$wpdb->prefix}ebay_auctions` ADD INDEX `relist_date` (`relist_date`) ";
			$wpdb->query($sql);	echo $wpdb->last_error;
	
			update_option('wplister_db_version', $new_db_version);
			$msg  = __('Database was upgraded to version', 'wplister') .' '. $new_db_version . '.';
		}

		// upgrade to version 37  (1.5.2)
		if ( 37 > $db_version ) {
			$new_db_version = 37;

			// create table: ebay_accounts
			$sql = "CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}ebay_accounts` (
			  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
			  `title` varchar(128) NOT NULL,
			  `site_id` int(11) DEFAULT NULL,
			  `site_code` varchar(16) DEFAULT NULL,
			  `sandbox_mode` varchar(16) DEFAULT NULL,
			  `user_name` varchar(32) DEFAULT NULL,
			  `user_details` text NOT NULL,
			  `active` int(11) DEFAULT NULL,
			  `token` text NOT NULL,
			  `valid_until` datetime DEFAULT NULL,
			  `ebay_motors` int(11) DEFAULT NULL,
			  `seller_profiles` int(11) DEFAULT NULL,
			  `shipping_profiles` text NOT NULL,
			  `payment_profiles` text NOT NULL,
			  `return_profiles` text NOT NULL,
			  `categories_map_ebay` text NOT NULL,
			  `categories_map_store` text NOT NULL,
			  `default_ebay_category_id` bigint(20) DEFAULT NULL,
			  `paypal_email` varchar(64) DEFAULT NULL,
			  `sync_orders` int(11) DEFAULT NULL,
			  `sync_products` int(11) DEFAULT NULL,
			  `last_orders_sync` datetime DEFAULT NULL,
			  PRIMARY KEY  (`id`)
			) DEFAULT CHARSET=utf8 ;";
			$wpdb->query($sql);

			// add column to ebay_auctions table
			$sql = "ALTER TABLE `{$wpdb->prefix}ebay_auctions`
			        ADD COLUMN `site_id` int(11) DEFAULT NULL AFTER `eps`,
			        ADD COLUMN `account_id` int(11) DEFAULT NULL AFTER `eps`
			";
			$wpdb->query($sql);

			// add column to ebay_log table
			$sql = "ALTER TABLE `{$wpdb->prefix}ebay_log`
			        ADD COLUMN `site_id` int(11) DEFAULT NULL AFTER `user_id`,
			        ADD COLUMN `account_id` int(11) DEFAULT NULL AFTER `user_id`
			";
			$wpdb->query($sql);

			// add column to ebay_messages table
			$sql = "ALTER TABLE `{$wpdb->prefix}ebay_messages`
			        ADD COLUMN `site_id` int(11) DEFAULT NULL AFTER `status`,
			        ADD COLUMN `account_id` int(11) DEFAULT NULL AFTER `status`
			";
			$wpdb->query($sql);

			// add column to ebay_orders table
			$sql = "ALTER TABLE `{$wpdb->prefix}ebay_orders`
			        ADD COLUMN `site_id` int(11) DEFAULT NULL AFTER `LastTimeModified`,
			        ADD COLUMN `account_id` int(11) DEFAULT NULL AFTER `LastTimeModified`
			";
			$wpdb->query($sql);

			// add column to ebay_transactions table
			$sql = "ALTER TABLE `{$wpdb->prefix}ebay_transactions`
			        ADD COLUMN `site_id` int(11) DEFAULT NULL AFTER `LastTimeModified`,
			        ADD COLUMN `account_id` int(11) DEFAULT NULL AFTER `LastTimeModified`
			";
			$wpdb->query($sql);

			// add column to ebay_payment table
			$sql = "ALTER TABLE `{$wpdb->prefix}ebay_payment`
			        ADD COLUMN `site_id` int(11) DEFAULT NULL AFTER `version`
			";
			$wpdb->query($sql);

			// add column to ebay_shipping table
			$sql = "ALTER TABLE `{$wpdb->prefix}ebay_shipping`
			        ADD COLUMN `site_id` int(11) DEFAULT NULL AFTER `version`
			";
			$wpdb->query($sql);

			// add column to ebay_profiles table
			$sql = "ALTER TABLE `{$wpdb->prefix}ebay_profiles`
			        ADD COLUMN `site_id` int(11) DEFAULT NULL AFTER `category_specifics`,
			        ADD COLUMN `account_id` int(11) DEFAULT NULL AFTER `category_specifics`
			";
			$wpdb->query($sql);

			// add column to ebay_store_categories table
			$sql = "ALTER TABLE `{$wpdb->prefix}ebay_store_categories`
			        ADD COLUMN `site_id` int(11) DEFAULT NULL AFTER `wp_term_id`,
			        ADD COLUMN `account_id` int(11) DEFAULT NULL AFTER `wp_term_id`
			";
			$wpdb->query($sql);
						
			update_option('wplister_db_version', $new_db_version);
			$msg  = __('Database was upgraded to version', 'wplister') .' '. $new_db_version . '.';
		}

		// upgrade to version 38  (1.5.2)
		if ( 38 > $db_version ) {
			$new_db_version = 38;

			$token    = get_option( 'wplister_ebay_token' );
			$site_id  = get_option( 'wplister_ebay_site_id' );
			$accounts = WPLE_eBayAccount::getAll( true );
			$sites    = EbayController::getEbaySites();

			// if there is a token but no accounts in table...
			if ( $token && ( sizeof($accounts) == 0 ) ) {

				// migrate current account to new default account
				$default_account = new WPLE_eBayAccount();
				$default_account->title                    = 'Default';
				$default_account->active                   = '1';
				$default_account->site_id                  = $site_id;
				$default_account->site_code                = $sites[ $site_id ];
				$default_account->token                    = $token;
				$default_account->user_name                = get_option( 'wplister_ebay_token_userid' );
				$default_account->sandbox_mode             = get_option( 'wplister_sandbox_enabled' );
				$default_account->valid_until              = get_option( 'wplister_ebay_token_expirationtime' );
				$default_account->ebay_motors              = get_option( 'wplister_enable_ebay_motors' ); // deprecated
				$default_account->seller_profiles          = get_option( 'wplister_ebay_seller_profiles_enabled' ) == 'yes' ? 1 : 0;
				$default_account->default_ebay_category_id = get_option( 'wplister_default_ebay_category_id' );
				$default_account->paypal_email 			   = get_option( 'wplister_paypal_email' );
				$default_account->user_details             = serialize( maybe_unserialize( get_option( 'wplister_ebay_user' ) ) );
				$default_account->categories_map_ebay      = serialize( maybe_unserialize( get_option( 'wplister_categories_map_ebay' ) ) );
				$default_account->categories_map_store     = serialize( maybe_unserialize( get_option( 'wplister_categories_map_store' ) ) );
				$default_account->add();
				// echo "<pre>";print_r($default_account);echo"</pre>";#die();


				// apply new account_id all over the site
				$default_account_id = $default_account->id;

				// update ebay_auctions table
				$sql = "UPDATE `{$wpdb->prefix}ebay_auctions` SET
				        `site_id`    = '$site_id',
				        `account_id` = '$default_account_id'  ";
				$wpdb->query($sql);

				// update ebay_log table
				$sql = "UPDATE `{$wpdb->prefix}ebay_log` SET
				        `site_id`    = '$site_id',
				        `account_id` = '$default_account_id'  ";
				$wpdb->query($sql);

				// update ebay_messages table
				$sql = "UPDATE `{$wpdb->prefix}ebay_messages` SET
				        `site_id`    = '$site_id',
				        `account_id` = '$default_account_id'  ";
				$wpdb->query($sql);

				// update ebay_orders table
				$sql = "UPDATE `{$wpdb->prefix}ebay_orders` SET
				        `site_id`    = '$site_id',
				        `account_id` = '$default_account_id'  ";
				$wpdb->query($sql);

				// update ebay_transactions table
				$sql = "UPDATE `{$wpdb->prefix}ebay_transactions` SET
				        `site_id`    = '$site_id',
				        `account_id` = '$default_account_id'  ";
				$wpdb->query($sql);

				// update ebay_profiles table
				$sql = "UPDATE `{$wpdb->prefix}ebay_profiles` SET
				        `site_id`    = '$site_id',
				        `account_id` = '$default_account_id'  ";
				$wpdb->query($sql);

				// update ebay_store_categories table
				$sql = "UPDATE `{$wpdb->prefix}ebay_store_categories` SET
				        `site_id`    = '$site_id',
				        `account_id` = '$default_account_id'  ";
				$wpdb->query($sql);


				// update ebay_payment table
				$sql = "UPDATE `{$wpdb->prefix}ebay_payment` SET
				        `site_id`    = '$site_id'  ";
				$wpdb->query($sql);

				// update ebay_shipping table
				$sql = "UPDATE `{$wpdb->prefix}ebay_shipping` SET
				        `site_id`    = '$site_id'  ";
				$wpdb->query($sql);

				update_option( 'wplister_default_account_id', $default_account_id );

				// make sure to reload accounts - which requires db version 38
				update_option('wplister_db_version', $new_db_version);
				WPLE()->loadAccounts();
			}
					
			update_option('wplister_db_version', $new_db_version);
			$msg  = __('Database was upgraded to version', 'wplister') .' '. $new_db_version . '.';
		}

		// upgrade to version 39  (1.6.0.6)
		if ( 39 > $db_version ) {
			$new_db_version = 39;

			// add column to ebay_auctions table
			$sql = "ALTER TABLE `{$wpdb->prefix}ebay_auctions`
			        ADD COLUMN `last_errors` TEXT AFTER `history`
			";
			$wpdb->query($sql);	echo $wpdb->last_error;
	
			update_option('wplister_db_version', $new_db_version);
			$msg  = __('Database was upgraded to version', 'wplister') .' '. $new_db_version . '.';
		}

		// upgrade to version 40  (1.6.0.7)
		if ( 40 > $db_version ) {
			$new_db_version = 40;

			// add column to ebay_orders table
			$sql = "ALTER TABLE `{$wpdb->prefix}ebay_orders`
			        ADD COLUMN `currency` varchar(16) AFTER `total`
			";
			$wpdb->query($sql);	echo $wpdb->last_error;
	
			update_option('wplister_db_version', $new_db_version);
			$msg  = __('Database was upgraded to version', 'wplister') .' '. $new_db_version . '.';
		}

		// upgrade to version 41  (1.6.0.10)
		if ( 41 > $db_version ) {
			$new_db_version = 41;

			// create table: ebay_sites
			$sql = "CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}ebay_sites` (
			  `id` int(11) DEFAULT NULL,
			  `title` varchar(128) NOT NULL,
			  `code` varchar(16) DEFAULT NULL,
			  `url` varchar(64) DEFAULT NULL,
			  `enabled` int(11) DEFAULT NULL,
			  `sort_order` int(11) DEFAULT NULL,
			  `last_refresh` datetime DEFAULT NULL,
			  `categories_map_ebay` text NOT NULL,
			  `DispatchTimeMaxDetails` text NOT NULL,
			  `MinListingStartPrices` text NOT NULL,
			  `ReturnsWithinOptions` text NOT NULL,
			  `CountryDetails` text NOT NULL,		  
			  `ShippingPackageDetails` text NOT NULL,
			  `ShippingCostPaidByOptions` text NOT NULL,
			  `ShippingLocationDetails` text NOT NULL,
			  `ExcludeShippingLocationDetails` text NOT NULL,
			  PRIMARY KEY  (`id`)
			) DEFAULT CHARSET=utf8 ;";
			$wpdb->query($sql);

			// build sites data
			$ebay_sites = EbayController::getEbaySites();
			$sort_order = 1;
			foreach ( $ebay_sites as $site_id => $site_title ) {

				$data = array(
					'id'         => $site_id,
					'title'      => $site_title,
					'url'        => EbayController::getDomainnameBySiteId( $site_id ),
					'sort_order' => $sort_order,
				);
				$wpdb->insert( $wpdb->prefix.'ebay_sites', $data );
				$sort_order++;

			}

			// enable site for each account
			foreach ( WPLE()->accounts as $account ) {
				$wpdb->update( $wpdb->prefix.'ebay_sites', array( 'enabled' => 1 ), array( 'id' => $account->site_id ) );
			}

			update_option('wplister_db_version', $new_db_version);
			$msg  = __('Database was upgraded to version', 'wplister') .' '. $new_db_version . '.';
		}

		// upgrade to version 42  (1.6.0.12 / 2.0.1)
		if ( 42 > $db_version ) {
			$new_db_version = 42;

			// add columns to ebay_accounts table
			$sql = "ALTER TABLE `{$wpdb->prefix}ebay_accounts`
			        ADD COLUMN `oosc_mode` int(11) AFTER `ebay_motors`,
			        ADD COLUMN `shipping_discount_profiles` text NOT NULL AFTER `return_profiles`
			";
			$wpdb->query($sql);	echo $wpdb->last_error;
	
			// refresh accounts
			WPLE()->loadAccounts();

			// if there is a valid default account, copy site specific data from wp_options to wp_ebay_sites
			$accounts           = WPLE()->accounts;
			$default_account_id = get_option( 'wplister_default_account_id' );
			$default_account    = isset( $accounts[ $default_account_id ] ) ? $accounts[ $default_account_id ] : false;
			if ( $default_account ) {
				$data = array(
					'categories_map_ebay'            => serialize( maybe_unserialize( get_option( 'wplister_categories_map_ebay' ) ) ),
					'DispatchTimeMaxDetails'         => serialize( maybe_unserialize( get_option( 'wplister_DispatchTimeMaxDetails' ) ) ),
					'MinListingStartPrices'          => serialize( maybe_unserialize( get_option( 'wplister_MinListingStartPrices' ) ) ),
					'ReturnsWithinOptions'           => serialize( maybe_unserialize( get_option( 'wplister_ReturnsWithinOptions' ) ) ),
					'CountryDetails'                 => serialize( maybe_unserialize( get_option( 'wplister_CountryDetails' ) ) ),
					'ShippingPackageDetails'         => serialize( maybe_unserialize( get_option( 'wplister_ShippingPackageDetails' ) ) ),
					'ShippingCostPaidByOptions'      => serialize( maybe_unserialize( get_option( 'wplister_ShippingCostPaidByOptions' ) ) ),
					'ShippingLocationDetails'        => serialize( maybe_unserialize( get_option( 'wplister_ShippingLocationDetails' ) ) ),
					'ExcludeShippingLocationDetails' => serialize( maybe_unserialize( get_option( 'wplister_ExcludeShippingLocationDetails' ) ) ),
				);
				$wpdb->update( $wpdb->prefix.'ebay_sites', $data, array( 'id' => $default_account->site_id ) );
			}

			update_option('wplister_db_version', $new_db_version);
			$msg  = __('Database was upgraded to version', 'wplister') .' '. $new_db_version . '.';
		}

		// upgrade to version 43  (2.0.8)
		if ( 43 > $db_version ) {
			$new_db_version = 43;

			// add indices to ebay_auctions table
			$sql = "ALTER TABLE `{$wpdb->prefix}ebay_auctions` ADD INDEX `parent_id` (`parent_id`) ";
			$wpdb->query($sql);	echo $wpdb->last_error;
			$sql = "ALTER TABLE `{$wpdb->prefix}ebay_auctions` ADD INDEX `site_id` (`site_id`) ";
			$wpdb->query($sql);	echo $wpdb->last_error;
			$sql = "ALTER TABLE `{$wpdb->prefix}ebay_auctions` ADD INDEX `account_id` (`account_id`) ";
			$wpdb->query($sql);	echo $wpdb->last_error;
	
			update_option('wplister_db_version', $new_db_version);
			$msg  = __('Database was upgraded to version', 'wplister') .' '. $new_db_version . '.';
		}

		// upgrade to version 44 (2.0.8.7)
		if ( 44 > $db_version ) {
			$new_db_version = 44;

			// set column type to mediumtext in table: ebay_accounts
			$sql = "ALTER TABLE `{$wpdb->prefix}ebay_accounts`  CHANGE shipping_profiles shipping_profiles MEDIUMTEXT ";
			$wpdb->query($sql);	echo $wpdb->last_error;
			$sql = "ALTER TABLE `{$wpdb->prefix}ebay_accounts`  CHANGE payment_profiles  payment_profiles  MEDIUMTEXT ";
			$wpdb->query($sql);	echo $wpdb->last_error;
			$sql = "ALTER TABLE `{$wpdb->prefix}ebay_accounts`  CHANGE return_profiles   return_profiles   MEDIUMTEXT ";
			$wpdb->query($sql);	echo $wpdb->last_error;
			$sql = "ALTER TABLE `{$wpdb->prefix}ebay_accounts`  CHANGE shipping_discount_profiles shipping_discount_profiles MEDIUMTEXT ";
			$wpdb->query($sql);	echo $wpdb->last_error;
			$sql = "ALTER TABLE `{$wpdb->prefix}ebay_accounts`  CHANGE categories_map_ebay  categories_map_ebay  MEDIUMTEXT ";
			$wpdb->query($sql);	echo $wpdb->last_error;
			$sql = "ALTER TABLE `{$wpdb->prefix}ebay_accounts`  CHANGE categories_map_store categories_map_store MEDIUMTEXT ";
			$wpdb->query($sql);	echo $wpdb->last_error;
			
			update_option('wplister_db_version', $new_db_version);
			$msg  = __('Database was upgraded to version', 'wplister') .' '. $new_db_version . '.';
		}

		// upgrade to version 45  (2.0.9.5)
		if ( 45 > $db_version ) {
			$new_db_version = 45;

			// add column to ebay_sites table
			$sql = "ALTER TABLE `{$wpdb->prefix}ebay_sites`
			        ADD COLUMN `DoesNotApplyText` varchar(128) NOT NULL AFTER `ExcludeShippingLocationDetails`
			";
			$wpdb->query($sql);	echo $wpdb->last_error;
	
			update_option('wplister_db_version', $new_db_version);
			$msg  = __('Database was upgraded to version', 'wplister') .' '. $new_db_version . '.';
		}

		// upgrade to version 46 (2.0.9.8.2)
		if ( 46 > $db_version ) {
			$new_db_version = 46;

			// set column type to mediumtext in table: ebay_auctions
			$sql = "ALTER TABLE `{$wpdb->prefix}ebay_auctions`
			        CHANGE details details MEDIUMTEXT ";
			$wpdb->query($sql);	echo $wpdb->last_error;
			
			// set column type to mediumtext in table: ebay_orders
			$sql = "ALTER TABLE `{$wpdb->prefix}ebay_orders`
			        CHANGE details details MEDIUMTEXT ";
			$wpdb->query($sql);	echo $wpdb->last_error;
			
			// set column type to mediumtext in table: ebay_transactions
			$sql = "ALTER TABLE `{$wpdb->prefix}ebay_transactions`
			        CHANGE details details MEDIUMTEXT ";
			$wpdb->query($sql);	echo $wpdb->last_error;		
			
			update_option('wplister_db_version', $new_db_version);
			$msg  = __('Database was upgraded to version', 'wplister') .' '. $new_db_version . '.';
		}

		// upgrade to version 47 (2.0.9.8.2)
		if ( 47 > $db_version ) {
			$new_db_version = 47;

			// restructure categories table
			$sql = "ALTER TABLE `{$wpdb->prefix}ebay_categories`
			        CHANGE conditions features MEDIUMTEXT ";
			$wpdb->query($sql);	echo $wpdb->last_error;		
			$sql = "ALTER TABLE `{$wpdb->prefix}ebay_categories`
			        CHANGE specifics specifics MEDIUMTEXT ";
			$wpdb->query($sql);	echo $wpdb->last_error;		
			$sql = "ALTER TABLE `{$wpdb->prefix}ebay_categories`
			        CHANGE wp_term_id last_updated datetime ";
			$wpdb->query($sql);	echo $wpdb->last_error;		

			update_option('wplister_db_version', $new_db_version);
			$msg  = __('Database was upgraded to version', 'wplister') .' '. $new_db_version . '.';
		}

		// upgrade to version 48 (2.0.9.8.2)
		if ( 48 > $db_version ) {
			$new_db_version = 48;

			// remove legacy data
			$sql = "DELETE FROM `{$wpdb->prefix}postmeta` WHERE meta_key    =    '_ebay_category_specifics' ";
			$wpdb->query($sql);	echo $wpdb->last_error;		
			$sql = "DELETE FROM `{$wpdb->prefix}options`  WHERE option_name LIKE '_transient_wplister_ebay_item_conditions_%' ";
			$wpdb->query($sql);	echo $wpdb->last_error;		
			$sql = "DELETE FROM `{$wpdb->prefix}options`  WHERE option_name LIKE '_transient_timeout_wplister_ebay_item_conditions_%' ";
			$wpdb->query($sql);	echo $wpdb->last_error;		

			update_option('wplister_db_version', $new_db_version);
			$msg  = __('Database was upgraded to version', 'wplister') .' '. $new_db_version . '.';
		}


		// show update message
		if ( $msg && ! $hide_message ) wple_show_message($msg,'info');		

		#debug: update_option('wplister_db_version', 0);
		
	} // upgradeDB()


	/**
	 * If a table only contains utf8 or utf8mb4 or latin1 columns, convert it to utf8mb4.
	 * (modified version of maybe_convert_table_to_utf8mb4() in wp core)
	 *
	 * @since 0.9.6.5
	 *
	 * @param string $table The table to convert.
	 * @return bool true if the table was converted, false if it wasn't.
	 */
	static function convert_custom_table_to_utf8mb4( $table ) {
		global $wpdb;
		global $wp_version;

		// do nothing before wp42
		if ( version_compare( $wp_version, '4,2', '<') ) {
			wple_show_message('WordPress 4.2 or better required - your version is '.$wp_version, 'error');
			return false;
		}

		// get column information
		$results = $wpdb->get_results( "SHOW FULL COLUMNS FROM `$table`" );
		if ( ! $results ) {
			wple_show_message("no columns found for $table",'error');
			return false;
		}

		// check charset for each column
		foreach ( $results as $column ) {
			if ( $column->Collation ) {
				list( $charset ) = explode( '_', $column->Collation );
				$charset = strtolower( $charset );
				if ( 'utf8' !== $charset && 'utf8mb4' !== $charset && 'latin1' !== $charset ) {
					// Don't upgrade tables that have non-utf8 and non-latin1 columns.
					wple_show_message("skipped column $column in table $table with charset: $charset",'error');
					return false;
				}
			}
		}

		// convert
		return $wpdb->query( "ALTER TABLE $table CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci" );
	}

} // class WPLE_UpgradeHelper
