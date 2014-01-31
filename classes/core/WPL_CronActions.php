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
		add_action('wplister_update_auctions', array( &$this, 'cron_update_auctions' ) );


	}

	
	// update auctions - called by wp_cron if activated
	public function cron_update_auctions() {
        $this->logger->info("WP-CRON: cron_update_auctions()");

        // check if update is already running
        if ( ! $this->checkLock() ) {
	        $this->logger->error("WP-CRON: already running! terminating execution...");
        	return;
        }

		$this->initEC();
		
		// decide if the old transactions update or the new orders update mode is to be used
		$mode = get_option( 'wplister_ebay_update_mode', 'order' );
		if ( $mode == 'order' ) {
			$this->EC->updateEbayOrders(); // new
		} else {
			$this->EC->loadTransactions(); // old
		}

		// update ended items and process relist schedule
		$this->EC->updateListings(); 

		// clean up
		$this->EC->closeEbay();
		$this->removeLock();

        $this->logger->info("WP-CRON: cron_update_auctions() finished");
	} // cron_update_auctions()

	public function checkLock() {

		// get full path to lockfile
		$uploads        = wp_upload_dir();
		$lockfile       = $uploads['basedir'] . '/' . 'wplister_sync.lock';
		$this->lockfile = $lockfile;

		// skip locking if lockfile is not writeable
		if ( ! is_writable( $lockfile ) && ! is_writable( dirname( $lockfile ) ) ) {
	        $this->logger->error("lockfile not writable: ".$lockfile);
	        return true;
		}

		// create lockfile if it doesn't exist
		if ( ! file_exists( $lockfile ) ) {
			$ts = time();
			file_put_contents( $lockfile, $ts );
	        $this->logger->info("lockfile created at TS $ts: ".$lockfile);
	        return true;
		}

		// lockfile exists - check TS
		$ts = (int) file_get_contents($lockfile); 

		// check if TS is outdated (after 10min.)
		if ( $ts < ( time() - 600 ) ) { 
	        $this->logger->info("stale lockfile found for TS ".$ts.' - '.human_time_diff( $ts ).' ago' );

	        // update lockfile 
			$ts = time();
			file_put_contents( $lockfile, $ts ); 
	        
	        $this->logger->info("lockfile updated for TS $ts: ".$lockfile);
	        return true;
		} else { 
			// process is still alive - can not run twice
	        $this->logger->info("SKIP CRON - sync already running with TS ".$ts.' - '.human_time_diff( $ts ).' ago' );
			return false; 
		} 

		return true;
	} // checkLock()

	public function removeLock() {
		if ( file_exists( $this->lockfile ) ) {
			unlink( $this->lockfile );
	        $this->logger->info("lockfile was removed: ".$this->lockfile);
		}
	}



} // class WPL_CronActions

$WPL_CronActions = new WPL_CronActions();
