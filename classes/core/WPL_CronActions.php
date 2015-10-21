<?php
/**
 * WPL_CronActions
 *
 * This class contains action hooks that are usually trigger via wp_cron()
 * 
 */

class WPL_CronActions extends WPL_Core {
	
	var $lockfile;

	public function __construct() {
		parent::__construct();
		
		// add cron handler
		add_action('wplister_update_auctions', 			array( &$this, 'cron_update_auctions' ) );
		add_action('wple_daily_schedule', 	   			array( &$this, 'cron_daily_schedule' ) );

		// add internal action hooks
		add_action('wple_clean_log_table', 				array( &$this, 'action_clean_log_table' ) );
		add_action('wple_clean_listing_archive', 		array( &$this, 'action_clean_listing_archive' ) );


	}

	
	// update auctions - called by wp_cron if activated
	public function cron_update_auctions() {
        WPLE()->logger->info("WP-CRON: cron_update_auctions()");

        // check if this is a staging site
        if ( $this->isStagingSite() ) {
	        WPLE()->logger->info("WP-CRON: staging site detected! terminating execution...");
			self::updateOption( 'cron_auctions', '' );
			self::updateOption( 'create_orders', '' );
        	return;
        }

        // check if update is already running
        if ( ! $this->checkLock() ) {
	        WPLE()->logger->error("WP-CRON: already running! terminating execution...");
        	return;
        }

        // get accounts
		$accounts = WPLE_eBayAccount::getAll();
		if ( ! empty( $accounts) ) {

			// loop each active account
			foreach ( $accounts as $account ) {

				$this->initEC( $account->id );
				$this->EC->updateEbayOrders();
				$this->EC->updateListings(); // TODO: specify account
				$this->EC->updateEbayMessages();
				$this->EC->closeEbay();

			}

		} else {

			// fallback to pre 1.5.2 behaviour
			$this->initEC();
			$this->EC->updateEbayOrders(); // force new mode in 1.5.2
			
			// decide if the old transactions update or the new orders update mode is to be used
			// $mode = get_option( 'wplister_ebay_update_mode', 'order' );
			// if ( $mode == 'order' ) {
			// 	$this->EC->updateEbayOrders(); // new
			// } else {
			// 	$this->EC->loadTransactions(); // old
			// }

			// update ended items and process relist schedule
			$this->EC->updateListings(); 
			$this->EC->closeEbay();

		}


		// clean up
		$this->removeLock();

		// store timestamp
		self::updateOption( 'cron_last_run', time() );

        WPLE()->logger->info("WP-CRON: cron_update_auctions() finished");
	} // cron_update_auctions()


	// run daily schedule - called by wp_cron
	public function cron_daily_schedule() {
        WPLE()->logger->info("*** WP-CRON: cron_daily_schedule()");

		// clean log table
		do_action('wple_clean_log_table');

		// clean archive
		do_action('wple_clean_listing_archive');

		// store timestamp
		update_option( 'wple_daily_cron_last_run', time() );

        WPLE()->logger->info("*** WP-CRON: cron_daily_schedule() finished");
	}

	public function action_clean_log_table() {
		global $wpdb;
		if ( get_option('wplister_log_to_db') == '1' ) {
			$days_to_keep = get_option( 'wplister_log_days_limit', 30 );
			$wpdb->query('DELETE FROM '.$wpdb->prefix.'ebay_log WHERE timestamp < DATE_SUB(NOW(), INTERVAL '.intval($days_to_keep).' DAY )');
		}
	} // action_clean_log_table()

	public function action_clean_listing_archive() {
		global $wpdb;
		if ( $days_to_keep = get_option( 'wplister_archive_days_limit', 90 ) ) {			
			$wpdb->query('DELETE FROM '.$wpdb->prefix.'ebay_auctions WHERE status = "archived" AND end_date < DATE_SUB(NOW(), INTERVAL '.intval($days_to_keep).' DAY )');
		}
	} // action_clean_listing_archive()


	public function checkLock() {

		// get full path to lockfile
		$uploads        = wp_upload_dir();
		$lockfile       = $uploads['basedir'] . '/' . 'wplister_sync.lock';
		$this->lockfile = $lockfile;

		// skip locking if lockfile is not writeable
		if ( ! is_writable( $lockfile ) && ! is_writable( dirname( $lockfile ) ) ) {
	        WPLE()->logger->error("lockfile not writable: ".$lockfile);
	        return true;
		}

		// create lockfile if it doesn't exist
		if ( ! file_exists( $lockfile ) ) {
			$ts = time();
			file_put_contents( $lockfile, $ts );
	        WPLE()->logger->info("lockfile created at TS $ts: ".$lockfile);
	        return true;
		}

		// lockfile exists - check TS
		$ts = (int) file_get_contents($lockfile); 

		// check if TS is outdated (after 10min.)
		if ( $ts < ( time() - 600 ) ) { 
	        WPLE()->logger->info("stale lockfile found for TS ".$ts.' - '.human_time_diff( $ts ).' ago' );

	        // update lockfile 
			$ts = time();
			file_put_contents( $lockfile, $ts ); 
	        
	        WPLE()->logger->info("lockfile updated for TS $ts: ".$lockfile);
	        return true;
		} else { 
			// process is still alive - can not run twice
	        WPLE()->logger->info("SKIP CRON - sync already running with TS ".$ts.' - '.human_time_diff( $ts ).' ago' );
			return false; 
		} 

		return true;
	} // checkLock()

	public function removeLock() {
		if ( file_exists( $this->lockfile ) ) {
			unlink( $this->lockfile );
	        WPLE()->logger->info("lockfile was removed: ".$this->lockfile);
		}
	}



} // class WPL_CronActions

$WPL_CronActions = new WPL_CronActions();
