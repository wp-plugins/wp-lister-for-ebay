<?php

class WPL_AjaxHandler extends WPL_Core {

	public function __construct() {
		parent::__construct();
		
		// called from category tree
		add_action('wp_ajax_e2e_get_ebay_categories_tree',  array( &$this, 'ajax_get_ebay_categories_tree' ) );		
		add_action('wp_ajax_e2e_get_store_categories_tree', array( &$this, 'ajax_get_store_categories_tree' ) );		

		// called from edit products page
		add_action('wp_ajax_wpl_getCategorySpecifics',  array( &$this, 'ajax_getCategorySpecifics' ) );		
		add_action('wp_ajax_wpl_getCategoryConditions', array( &$this, 'ajax_getCategoryConditions' ) );		
		
		// called from jobs window
		add_action('wp_ajax_wpl_jobs_load_tasks', array( &$this, 'jobs_load_tasks' ) );	
		add_action('wp_ajax_wpl_jobs_run_task', array( &$this, 'jobs_run_task' ) );	
		add_action('wp_ajax_wpl_jobs_complete_job', array( &$this, 'jobs_complete_job' ) );	

		// logfile viewer
		add_action('wp_ajax_wplister_tail_log', array( &$this, 'ajax_wplister_tail_log' ) );

		// profile selector
		add_action('wp_ajax_wple_select_profile', array( &$this, 'ajax_wple_select_profile' ) );
		add_action('wp_ajax_wple_show_profile_selection', array( &$this, 'ajax_wple_show_profile_selection' ) );

		// product matcher
		add_action('wp_ajax_wple_show_product_matches', array( &$this, 'ajax_wple_show_product_matches' ) );

		// handle dynamic listing galleries
		add_action('wp_ajax_wpl_gallery', array( &$this, 'ajax_wpl_gallery' ) );
		add_action('wp_ajax_nopriv_wpl_gallery', array( &$this, 'ajax_wpl_gallery' ) );

		// handle request for eBay store categories (JSON)
		add_action('wp_ajax_wpl_ebay_store_categories', array( &$this, 'ajax_wpl_ebay_store_categories' ) );
		add_action('wp_ajax_nopriv_wpl_ebay_store_categories', array( &$this, 'ajax_wpl_ebay_store_categories' ) );

		// handle incoming ebay notifications
		add_action('wp_ajax_handle_ebay_notify', array( &$this, 'ajax_handle_ebay_notify' ) );
		add_action('wp_ajax_nopriv_handle_ebay_notify', array( &$this, 'ajax_handle_ebay_notify' ) );
	}
	

	// show profile selection
	public function ajax_wple_show_profile_selection() {

		// fetch profiles
		$pm = new ProfilesModel();
		$profiles = $pm->getAll();

		// load template
		$tpldata = array(
			'plugin_url'				=> self::$PLUGIN_URL,
			'message'					=> $this->message,
			'profiles'					=> $profiles,				
			'form_action'				=> 'admin.php?page='.self::ParentMenuId
		);

		WPLE()->pages['listings']->display( 'profile/select_profile', $tpldata );
		exit();
	
	} // ajax_wple_show_profile_selection()


	// match product
	public function ajax_wple_select_profile() {

		// TODO: check nonce
		if ( isset( $_REQUEST['profile_id'] ) && isset( $_REQUEST['product_ids'] ) ) {

			$profile_id  = $_REQUEST['profile_id'];
			$product_ids = $_REQUEST['product_ids'];
			$select_mode = $_REQUEST['select_mode'];
			$default_account_id = get_option( 'wplister_default_account_id', 1 );

			$lm = new ListingsModel();
			if ( 'products' == $select_mode ) {

		        // get profile
				$pm = new ProfilesModel();
				$profile = $pm->getItem( $profile_id );
		
				// prepare new listings from products
				// $response = $lm->prepareListings( $product_ids, $profile_id );
				$response = $lm->prepareListings( $product_ids, $profile_id );

		        $lm->applyProfileToNewListings( $profile );		      

			} elseif ( 'listings' == $select_mode ) {

				// change profile for existing listings
				// $profile = WPLE_AmazonProfile::getProfile( $profile_id ); // doesn't work
				$pm = new ProfilesModel();
				$profile = $pm->getItem( $profile_id );
				// $items = $lm->applyProfileToListings( $profile, $product_ids );
				foreach ($product_ids as $listing_id) {
					$item = ListingsModel::getItem( $listing_id );
					$lm->applyProfileToItem( $profile, $item );
				}

				// build response
				$response = new stdClass();
				// $response->success     = $prepared_count ? true : false;
				$response->success        = true;
				$response->msg 			  = sprintf( __('Profile "%s" was applied to %s items.','wplister'), $profile['profile_name'], count($product_ids) );
				$this->returnJSON( $response );
				exit();
			} else {
				die('invalid select mode: '.$select_mode);
			}
		
			if ( $response->success ) {

				// store ASIN as product meta
				// update_post_meta( $post_id, '_wple_asin', $asin );

				// show message
				if ( $response->skipped_count ) {
					$response->msg = sprintf( __('%s product(s) have been prepared and %s products were skipped.','wplister'), $response->prepared_count, $response->skipped_count );
				} else {
					$response->msg = sprintf( __('%s product(s) have been prepared.','wplister'), $response->prepared_count );
				}

				// include link to prepared listings
				$response->msg .= '&nbsp; <a href="admin.php?page=wplister&listing_status=prepared" class="button button-small">'.__('View prepared listings','wplister').'</a>';

				// show shorter message if no listings were prepared
				if ( ! $response->prepared_count )
					$response->msg = sprintf( __('%s products have been skipped.','wplister'), $response->skipped_count );

				if ( $response->errors )
					$response->msg .= '<br>'.join('<br>',$response->errors);
				if ( $response->warnings )
					$response->msg .= '<br>'.join('<br>',$response->warnings);


				$this->returnJSON( $response );
				exit();

			} else {
				if ( isset($lm->lastError) ) echo $lm->lastError."\n";
				echo "Failed to prepare product!";
				exit();
			}

		}

	} // ajax_wple_select_profile()


	// fetch category specifics
	public function ajax_getCategorySpecifics() {
		
		$category_id = $_REQUEST['id'];
		$account_id  = isset( $_REQUEST['account_id'] ) ? $_REQUEST['account_id'] : get_option( 'wplister_default_account_id' );
		$site_id     = isset( $_REQUEST['site_id'] )    ? $_REQUEST['site_id']    : 0;

		// $this->initEC( $account_id );
		// $result = $this->EC->getCategorySpecifics( $category_id );
		// $this->EC->closeEbay();

		// improved version of the above, using ebay_categories as cache
		$specifics = EbayCategoriesModel::getItemSpecificsForCategory( $category_id, $site_id, $account_id );
		// $result         = array( $category_id => $specifics );

		$this->returnJSON( $specifics );
		exit();
	}
	
	// fetch category conditions
	public function ajax_getCategoryConditions() {
		
		$category_id = $_REQUEST['id'];
		$account_id  = isset( $_REQUEST['account_id'] ) ? $_REQUEST['account_id'] : get_option( 'wplister_default_account_id' );
		$site_id     = isset( $_REQUEST['site_id'] )    ? $_REQUEST['site_id']    : 0;

		// $this->initEC( $account_id );
		// $result = $this->EC->getCategoryConditions( $category_id );
		// $this->EC->closeEbay();

		// improved version of the above, using ebay_categories as cache
		$conditions = EbayCategoriesModel::getConditionsForCategory( $category_id, $site_id, $account_id );

		$this->returnJSON( $conditions );
		exit();
	}
	
	function shutdown_handler() {
		global $wpl_shutdown_handler_enabled;
		if ( ! $wpl_shutdown_handler_enabled ) return;

		// check for fatal error
        $error = error_get_last();
        if ($error['type'] === E_ERROR) {

	        $logmsg  = "<br><br>";
	        $logmsg .= "<b>There has been a fatal PHP error - the server said:</b><br>";
	        $logmsg .= '<span style="color:darkred">'.$error['message']."</span><br>";
	        $logmsg .= "In file: <code>".$error['file']."</code> (line ".$error['line'].")<br>";

	        $logmsg .= "<br>";
	        $logmsg .= "<b>Please contact support in order to resolve this.</b><br>";
	        $logmsg .= "If this error is related to memory limits or timeouts, you need to contact your server administrator or hosting provider.<br>";
	        echo $logmsg;

		} 
		// debug all errors
		// echo "<br>Last error: <pre>".print_r($error,1)."</pre>"; 
	}

	// run single task
	public function jobs_run_task() {

		// quit if no job name provided
		if ( ! isset( $_REQUEST['job'] ) ) return false;
		if ( ! isset( $_REQUEST['task'] ) ) return false;

		$job        = $_REQUEST['job'];
		$task       = $_REQUEST['task'];
		$site_id    = isset( $task['site_id'] ) ? $task['site_id'] : false;
		$account_id = isset( $task['account_id'] ) ? $task['account_id'] : false;

		// register shutdown handler
		global $wpl_shutdown_handler_enabled;
		$wpl_shutdown_handler_enabled = true;
		register_shutdown_function( array( $this, 'shutdown_handler' ) );

		WPLE()->logger->info('running task: '.print_r($task,1));

		// handle job name
		switch ( $task['task'] ) {
			case 'loadShippingServices':
				
				// call EbayController
				$this->initEC( $account_id, $site_id );
				$result = $this->EC->loadShippingServices( $site_id );
				$this->EC->closeEbay();

				// build response
				$response = new stdClass();
				$response->job  	= $job;
				$response->task 	= $task;
				$response->result 	= $result;
				$response->errors   = array();
				$response->success  = true;
				
				$this->returnJSON( $response );
				exit();
			
			case 'loadPaymentOptions':
				
				// call EbayController
				$this->initEC( $account_id, $site_id );
				$result = $this->EC->loadPaymentOptions( $site_id );
				$this->EC->closeEbay();

				// build response
				$response = new stdClass();
				$response->job  	= $job;
				$response->task 	= $task;
				$response->result 	= $result;
				$response->errors   = array();
				$response->success  = true;
				
				$this->returnJSON( $response );
				exit();
			
			case 'loadStoreCategories':
				
				// call EbayController
				$this->initEC( $account_id );
				$result = $this->EC->loadStoreCategories( $account_id );
				$this->EC->closeEbay();

				// build response
				$response = new stdClass();
				$response->job  	= $job;
				$response->task 	= $task;
				$response->result 	= $result;
				$response->errors   = array();
				$response->success  = true;
				
				$this->returnJSON( $response );
				exit();
			
			case 'loadUserAccountDetails':
				
				// call EbayController
				$this->initEC( $account_id );
				$result = $this->EC->loadUserAccountDetails();
				$this->EC->closeEbay();

		        // update account (seller profiles etc.)
		        $account = new WPLE_eBayAccount( $account_id );
		        if ( $account ) $account->updateUserDetails();

				// build response
				$response = new stdClass();
				$response->job  	= $job;
				$response->task 	= $task;
				$response->result 	= $result;
				$response->errors   = array();
				$response->success  = true;
				
				$this->returnJSON( $response );
				exit();
			
			case 'loadEbayCategoriesBranch':
				
				// call EbayController
				$this->initEC( $account_id, $site_id );
				$result = $this->EC->loadEbayCategoriesBranch( $task['cat_id'], $task['site_id'] );
				$this->EC->closeEbay();

				// build response
				$response = new stdClass();
				$response->job  	= $job;
				$response->task 	= $task;
				$response->result 	= $result;
				$response->errors   = array();
				$response->success  = true;
				
				$this->returnJSON( $response );
				exit();
			
			case 'verifyItem':
				
				// call EbayController
				$this->initEC( $account_id );
				$results = $this->EC->verifyItems( $task['id'] );
				$this->EC->closeEbay();
				$this->handleSubTasksInResults( $results, $job, $task );

				// build response
				$response = new stdClass();
				$response->job  	= $job;
				$response->task 	= $task;
				$response->errors   = $results[0]->errors;
				$response->success  = $results[0]->success;
			
				$this->returnJSON( $response );
				exit();
			
			case 'publishItem':
				
				// call EbayController
				$this->initEC( $account_id );
				$results = $this->EC->sendItemsToEbay( $task['id'] );
				$this->EC->closeEbay();
				$this->handleSubTasksInResults( $results, $job, $task );

				// build response
				$response = new stdClass();
				$response->job  	= $job;
				$response->task 	= $task;
				$response->errors   = $results[0]->errors;
				$response->success  = $results[0]->success;
				
				$this->returnJSON( $response );
				exit();
			
			case 'reviseItem':
				
				// call EbayController
				$this->initEC( $account_id );
				$results = $this->EC->reviseItems( $task['id'] );
				$this->EC->closeEbay();
				$this->handleSubTasksInResults( $results, $job, $task );

				// build response
				$response = new stdClass();
				$response->job  	= $job;
				$response->task 	= $task;
				$response->errors   = $results[0]->errors;
				$response->success  = $results[0]->success;
				
				$this->returnJSON( $response );
				exit();
			
			case 'updateItem':
				
				// call EbayController
				$this->initEC( $account_id );
				$results = $this->EC->updateItemsFromEbay( $task['id'] );
				$this->EC->closeEbay();

				// build response
				$response = new stdClass();
				$response->job  	= $job;
				$response->task 	= $task;
				$response->errors   = $results[0]->errors;
				$response->success  = $results[0]->success;
				
				$this->returnJSON( $response );
				exit();
			
			case 'endItem':
				
				// call EbayController
				$this->initEC( $account_id );
				$results = $this->EC->endItemsOnEbay( $task['id'] );
				$this->EC->closeEbay();
				$this->handleSubTasksInResults( $results, $job, $task );

				// build response
				$response = new stdClass();
				$response->job  	= $job;
				$response->task 	= $task;
				$response->errors   = $results[0]->errors;
				$response->success  = $results[0]->success;
				
				$this->returnJSON( $response );
				exit();
			
			case 'relistItem':
				
				// call EbayController
				$this->initEC( $account_id );
				$results = $this->EC->relistItems( $task['id'] );
				$this->EC->closeEbay();
				$this->handleSubTasksInResults( $results, $job, $task );

				// build response
				$response = new stdClass();
				$response->job  	= $job;
				$response->task 	= $task;
				$response->errors   = $results[0]->errors;
				$response->success  = $results[0]->success;
				
				$this->returnJSON( $response );
				exit();
			
			case 'uploadToEPS':
				
				// call EbayController
				$this->initEC( $account_id );

				$lm = new ListingsModel();
				$eps_url = $lm->uploadPictureToEPS( $task['img'], $task['id'], $this->EC->session );

				$this->EC->closeEbay();

				// build response
				$response = new stdClass();
				$response->job  	= $job;
				$response->task 	= $task;
				// $response->errors   = $eps_url ? false : $lm->result->errors;
				$response->errors   = is_array( $lm->result->errors ) ? $lm->result->errors : array();
				// $response->success  = $lm->result->success;
				$response->success  = $eps_url ? true : false;
				
				$this->returnJSON( $response );
				exit();
			
			case 'applyProfileDelayed':
				
				$profile_id = $task['profile_id'];
				$offset     = $task['offset'];
				$limit      = $task['limit'];

				$profilesModel = new ProfilesModel();
		        $profile = $profilesModel->getItem( $profile_id );

		        $lm = new ListingsModel();
				$items1 = WPLE_ListingQueryHelper::getAllPreparedWithProfile( $profile_id );
				$items2 = WPLE_ListingQueryHelper::getAllVerifiedWithProfile( $profile_id );
				$items3 = WPLE_ListingQueryHelper::getAllPublishedWithProfile( $profile_id );
				$items  = array_merge( $items1, $items2, $items3 );
				$total_items = sizeof($items);

				// extract batch
				$items = array_slice( $items, $offset, $limit );

				// apply profile to items
		        $lm->applyProfileToItems( $profile, $items );

		        // reset reminder option when last batch is run
		        if ( $offset + $limit >= $total_items ) {
		        	update_option( 'wple_job_reapply_profile_id', '' );
		        }

				// build response
				$response = new stdClass();
				$response->job  	= $job;
				$response->task 	= $task;
				$response->errors   = array();
				// $response->errors   = array( array( 'HtmlMessage' => ' Profile was applied to '.sizeof($items).' items ') );
				$response->success  = true;
				
				$this->returnJSON( $response );
				exit();
			
			default:
				// echo "unknown task";
				// exit();
		}

	}
	
	// handle subtasks
	public function handleSubTasksInResults( $results, $job, $task ) {

		// if ( isset( $results[0]->subtasks ) ) {

		// 	// build response
		// 	$response = new stdClass();
		// 	$response->job  	= $job;
		// 	$response->task 	= $task;
		// 	$response->errors   = $results[0]->errors;
		// 	$response->success  = $results[0]->success;
		// 	$this->returnJSON( $response );
		// 	exit;

		// }

	}
	
	// load task list
	public function jobs_load_tasks() {

		// quit if no job name provided
		if ( ! isset( $_REQUEST['job'] ) ) return false;
		$jobname = $_REQUEST['job'];

		// check if an array of listing IDs was provided
		$listing_ids = ( isset( $_REQUEST['listing_ids'] ) && is_array( $_REQUEST['listing_ids'] ) ) ? $_REQUEST['listing_ids'] : false;
		if ( $listing_ids ) {
	        $items = WPLE_ListingQueryHelper::getItemsByIdArray( $listing_ids );
		}

		// handle job name
		switch ( $jobname ) {
			case 'updateEbayData':
				
				// call EbayController
				$site_id    = ( isset($_REQUEST['site_id']) ? $_REQUEST['site_id'] : get_option('wplister_ebay_site_id') );
				$account_id = ( isset($_REQUEST['account_id']) ? $_REQUEST['account_id'] : get_option('wplister_default_account_id') );

				$this->initEC( $account_id );
				$tasks = $this->EC->initCategoriesUpdate( $site_id );
				$this->EC->closeEbay();

				// update store categories for each account using this site_id
				$accounts = WPLE_eBayAccount::getAll();
				foreach ( $accounts as $account ) {

					if ( $site_id != $account->site_id ) continue;
				
					// add task - load user specific details
					$tasks[] = array( 
						'task'        => 'loadUserAccountDetails', 
						'displayName' => 'update eBay account details for '.$account->title,
						'account_id'  => $account->id,
					);

					// add task - load store categories
					$tasks[] = array( 
						'task'        => 'loadStoreCategories', 
						'displayName' => 'update custom store categories for '.$account->title,
						'account_id'  => $account->id,
					);

				} // for each account


				// build response
				$response = new stdClass();
				$response->tasklist = $tasks;
				$response->total_tasks = count( $tasks );
				$response->error    = '';
				$response->success  = true;
				
				// create new job
				$newJob = new stdClass();
				$newJob->jobname = $jobname;
				$newJob->tasklist = $tasks;
				$job = new JobsModel( $newJob );
				$response->job_key = $job->key;

				$this->returnJSON( $response );
				exit();
			
			case 'verifyItems':
				
		        $response = $this->_create_bulk_listing_job( 'verifyItem', $items, $jobname );
				$this->returnJSON( $response );
				exit();
			
			case 'publishItems':
				
		        $response = $this->_create_bulk_listing_job( 'publishItem', $items, $jobname );
				$this->returnJSON( $response );
				exit();
			
			case 'reviseItems':
				
		        $response = $this->_create_bulk_listing_job( 'reviseItem', $items, $jobname );
				$this->returnJSON( $response );
				exit();
			
			case 'updateItems':
				
		        $response = $this->_create_bulk_listing_job( 'updateItem', $items, $jobname );
				$this->returnJSON( $response );
				exit();
			
			case 'endItems':
				
		        $response = $this->_create_bulk_listing_job( 'endItem', $items, $jobname );
				$this->returnJSON( $response );
				exit();
			
			case 'relistItems':
				
		        $response = $this->_create_bulk_listing_job( 'relistItem', $items, $jobname );
				$this->returnJSON( $response );
				exit();
			
			case 'verifyAllPreparedItems':
				
				// get prepared items
		        $items = WPLE_ListingQueryHelper::getAllPrepared();
		        
		        // create job from items and send response
		        $response = $this->_create_bulk_listing_job( 'verifyItem', $items, $jobname );
				$this->returnJSON( $response );
				exit();
			
			case 'publishAllVerifiedItems':
				
				// get verified items
		        $items = WPLE_ListingQueryHelper::getAllVerified();
		        
		        // create job from items and send response
		        $response = $this->_create_bulk_listing_job( 'publishItem', $items, $jobname );
				$this->returnJSON( $response );
				exit();
			
			case 'publishAllPreparedItems':
				
				// get prepared items
		        $items = WPLE_ListingQueryHelper::getAllPrepared();
		        
		        // create job from items and send response
		        $response = $this->_create_bulk_listing_job( 'publishItem', $items, $jobname );
				$this->returnJSON( $response );
				exit();
			
			case 'reviseAllChangedItems':
				
				// get changed items
		        $items = WPLE_ListingQueryHelper::getAllChangedItemsToRevise();
		        
		        // create job from items and send response
		        $response = $this->_create_bulk_listing_job( 'reviseItem', $items, $jobname );
				$this->returnJSON( $response );
				exit();
			
			case 'relistAllRestockedItems':
				
				// get restocked items
		        $items = WPLE_ListingQueryHelper::getAllEndedItemsToRelist();
		        
		        // create job from items and send response
		        $response = $this->_create_bulk_listing_job( 'relistItem', $items, $jobname );
				$this->returnJSON( $response );
				exit();
			
			case 'updateAllPublishedItems':
				
				// get published items
		        $items = WPLE_ListingQueryHelper::getAllPublished();
		        
		        // create job from items and send response
		        $response = $this->_create_bulk_listing_job( 'updateItem', $items, $jobname );
				$this->returnJSON( $response );
				exit();
			
			case 'updateAllRelistedItems':
				
				// get published items
		        $items = WPLE_ListingQueryHelper::getAllRelisted();
		        
		        // create job from items and send response
		        $response = $this->_create_bulk_listing_job( 'updateItem', $items, $jobname );
				$this->returnJSON( $response );
				exit();
			
			case 'runDelayedProfileApplication':
				
				// get items using given profile
				$profile_id = get_option('wple_job_reapply_profile_id' );
				if ( ! $profile_id ) return;

				$items1 = WPLE_ListingQueryHelper::getAllPreparedWithProfile( $profile_id );
				$items2 = WPLE_ListingQueryHelper::getAllVerifiedWithProfile( $profile_id );
				$items3 = WPLE_ListingQueryHelper::getAllPublishedWithProfile( $profile_id );
				$items  = array_merge( $items1, $items2, $items3 );

				$total_items = sizeof($items);
				$batch_size  = get_option( 'wplister_apply_profile_batch_size', 1000 );
				$tasks       = array();

				// echo "<pre>profile_id: ";echo $profile_id;echo"</pre>";
				// echo "<pre>total: ";echo $total_items;echo"</pre>";die();
		        
				for ( $page=0; $page < ($total_items / $batch_size); $page++ ) { 

					$from = $page * $batch_size + 1;
					$to   = $page * $batch_size + $batch_size;
					$to   = min( $to, $total_items );

					// add task - load user specific details
					$tasks[] = array( 
						'task'        => 'applyProfileDelayed', 
						'displayName' => 'Apply profile to items '.$from.' to '.$to,
						'profile_id'  => $profile_id,
						'offset'      => $page * $batch_size,
						'limit'       => $batch_size,
					);

				}

				// build response
				$response = new stdClass();
				$response->tasklist    = $tasks;
				$response->total_tasks = count( $tasks );
				$response->error       = '';
				$response->success     = true;
				
				// create new job
				$newJob = new stdClass();
				$newJob->jobname = $jobname;
				$newJob->tasklist = $tasks;
				$job = new JobsModel( $newJob );
				$response->job_key = $job->key;

				$this->returnJSON( $response );
				exit();
			
			default:
				// echo "unknown job";
				// break;
		}
		// exit();

	}

	// create bulk listing job
	public function _create_bulk_listing_job( $taskname, $items, $jobname ) {

		// create tasklist
        $tasks = array();
        foreach( $items as $item ) {
			WPLE()->logger->info('adding task for item #'.$item['id'] . ' - '.$item['auction_title']);
			
			$tasks = $this->_prepare_eps_tasks( $item, $taskname, $tasks );

			$task = array( 
				'task'        => $taskname, 
				'displayName' => $item['auction_title'], 
				'id'          => $item['id'],
				'site_id'     => $item['site_id'],
				'account_id'  => $item['account_id']
			);
			$tasks[] = $task;
        }

		// build response
		$response = new stdClass();
		$response->tasklist = $tasks;
		$response->total_tasks = count( $tasks );
		$response->error    = '';
		$response->success  = true;
		
		// create new job
		$newJob = new stdClass();
		$newJob->jobname = $jobname;
		$newJob->tasklist = $tasks;
		$job = new JobsModel( $newJob );
		$response->job_key = $job->key;

		return $response;
	}


	// load task list
	public function _prepare_eps_tasks( $item, $taskname, $tasks ) {

		// process only verify, publish and revise actions
		if ( ! in_array( $taskname, array('verifyItem','publishItem','reviseItem') ) ) return $tasks;


		return $tasks;
	}


	// complete job
	public function jobs_complete_job() {

		// quit if no job name provided
		if ( ! isset( $_REQUEST['job'] ) ) return false;

		// mark job as completed
		$job = new JobsModel( $_REQUEST['job'] );
		$job->completeJob();

		if ( 'updateEbayData' == $job->item['job_name'] ) {
			// if we were updating ebay details as part of setup, move to next step
			if ( '2' == self::getOption('setup_next_step') ) self::updateOption('setup_next_step', 3);
		}

		// build response
		$response = new stdClass();
		$response->msg    = $job->item['job_name'].' completed';
		$response->error    = '';
		$response->success  = true;
		$response->job_key = $job->key;

		$this->returnJSON( $response );
		exit();


	}

	public function addAdminMessagesToResult( $data ) {
		if ( ! is_object($data) ) return $data;
		if ( ! isset($data->errors) ) return $data;
		if ( ! is_array($data->errors) ) $data->errors = array();

		// merge admin notices with result errors
		$admin_errors = WPLE()->messages->get_admin_notices_for_json_result();
		$data->errors = array_merge( $data->errors, $admin_errors );

		return $data;
	}

	public function returnJSON( $data ) {
		global $wpl_shutdown_handler_enabled;
		$wpl_shutdown_handler_enabled = false;

		// add WPLE admin messages to result errors
		$data = $this->addAdminMessagesToResult( $data );

		header('content-type: application/json; charset=utf-8');
		echo json_encode( $data );
	}
	
	// get categories tree node - used on ProfilesPage
	public function ajax_get_ebay_categories_tree() {

		$site_id = isset($_REQUEST['site_id']) ? $_REQUEST['site_id'] : get_option('wplister_ebay_site_id');
	
		$path = $_POST["dir"];	
		$parent_cat_id = basename( $path );
		$categories = EbayCategoriesModel::getChildrenOf( $parent_cat_id, $site_id );		
		$categories = apply_filters( 'wplister_get_ebay_categories_node', $categories, $parent_cat_id, $path );

		if( count($categories) > 0 ) { 
			echo "<ul class=\"jqueryFileTree\" style=\"display: none;\">";
			// All dirs
			foreach( $categories as $cat ) {
				if ( $cat['leaf'] == '0' ) {
					echo '<li class="directory collapsed"><a href="#" rel="' 
						. ($_POST['dir'] . $cat['cat_id']) . '/">'. ($cat['cat_name']) . '</a></li>';
				}
			}
			// All files
			foreach( $categories as $cat ) {
				if ( $cat['leaf'] == '1' ) {
					$ext = 'txt';
					echo '<li class="file ext_txt"><a href="#" rel="' 
						. ($_POST['dir'] . $cat['cat_id']) . '">' . ($cat['cat_name']) . '</a></li>';
				}
			}
			echo "</ul>";	
		}
		exit();
	}

	// get categories tree node - used on ProfilesPage
	public function ajax_get_store_categories_tree() {
	
		$account_id = isset($_REQUEST['account_id']) ? $_REQUEST['account_id'] : get_option('wplister_default_account_id');
		$site_id    = WPLE()->accounts[ $account_id ]->site_id;

		$path = $_POST["dir"];	
		$parent_cat_id = basename( $path );
		$categories = EbayCategoriesModel::getChildrenOfStoreCategory( $parent_cat_id, $account_id );		
		$categories = apply_filters( 'wplister_get_store_categories_node', $categories, $parent_cat_id, $path );
		
		if( count($categories) > 0 ) { 

			echo "<ul class=\"jqueryFileTree\" style=\"display: none;\">";

			// add reference to parent node - non-leaf Store Categories are allowed as well
			if ( $parent_cat_id != 0 ) {
				$parent_path = substr( $_POST['dir'], 0, -1 ); // strip trailing slash
				echo '<li class="file ext_txt"><a href="#" rel="' . $parent_path . '">' . '[use this category]' . '</a></li>';
			}

			// All dirs
			foreach( $categories as $cat ) {
				if ( $cat['leaf'] == '0' ) {
					echo '<li class="directory collapsed"><a href="#" rel="' 
						. ($_POST['dir'] . $cat['cat_id']) . '/">'. ($cat['cat_name']) . '</a></li>';
				}
			}
			// All files
			foreach( $categories as $cat ) {
				if ( $cat['leaf'] == '1' ) {
					$ext = 'txt';
					echo '<li class="file ext_txt"><a href="#" rel="' 
						. ($_POST['dir'] . $cat['cat_id']) . '">' . ($cat['cat_name']) . '</a></li>';
				}
			}

			echo "</ul>";	
		}
		exit();
	}


	// show matching products
	public function ajax_wple_show_product_matches() {

		// TODO: check nonce
		if ( isset( $_REQUEST['id'] ) ) {

			// $market = WPLA_AmazonMarket::getMarket( $_REQUEST['market_id'] );
			$product = get_product( $_REQUEST['id'] );
			// echo "<pre>";print_r($product);echo"</pre>";

			if ( $product ) {

				$product_attributes	= ProductWrapper::getAttributes( $product->id, true );

			    $wpl_default_matcher_selection = get_option( 'wple_default_matcher_selection', 'title' );
			    switch ($wpl_default_matcher_selection) {
			    	case 'title':
			    		# product title
						$query = $product->post->post_title;
			    		break;
			    	
			    	case 'sku':
			    		# product sku
						$query = $product->sku;
			    		break;
			    	
			    	default:
			    		# else check for attributes
			    		foreach ($product_attributes as $attribute_label => $attribute_value) {
			    			if ( $attribute_label == $wpl_default_matcher_selection )
			    				$query = $attribute_value;
			    		}
			    		break;
			    }
				// echo '<h2>'.$query.'</h2>';

			    // fall back to title when query is empty
			    if ( empty($query) ) $query = $product->post->post_title;

			    // handle custom query
				if ( isset( $_REQUEST['query'] ) ) $query = trim( $_REQUEST['query'] );

				// get product attributes - if possible from cache
				$transient_key = 'wple_product_match_results_'.sanitize_key( $query );
				$products = get_transient( $transient_key );
				if ( empty( $products ) ){
	
					// call API
					$this->initEC();
					$products = $this->EC->callFindProducts( $query );
					$this->EC->closeEbay();
	
					if ( is_array( $products ) ) {
		
						// save cache
						set_transient( $transient_key, $products, 300 );
					}
					// echo "<pre>";print_r($transient_key);echo"</pre>";#die();
				}
				// echo "<pre>products: ";print_r($products);echo"</pre>";#die();

				// get market / site domain - for "view" links
	            // $market  = new WPLA_AmazonMarket( $account->market_id );

				if ( is_array( $products ) )  {

					// load template
					$tpldata = array(
						'plugin_url'				=> self::$PLUGIN_URL,
						'message'					=> $this->message,
						'query'						=> $query,				
						'query_product'				=> $product,				
						'query_product_attributes'	=> $product_attributes,
						'products'					=> $products,				
						// 'market_url'				=> $market->url,				
						'post_id'					=> $_REQUEST['id'],				
						'query_select'				=> isset($_REQUEST['query_select']) ? $_REQUEST['query_select'] : false,
						'form_action'				=> 'admin.php?page='.self::ParentMenuId
					);

					WPLE()->pages['listings']->display( 'match_product', $tpldata );

				// } elseif ( $product->Error->Message ) {
				// 	$errors  = sprintf( __('There was a problem fetching product details for %s.','wpla'), $product->post->post_title ) .'<br>Error: '. $reports->Error->Message;
				} else {
					$errors  = sprintf( __('There were no products found for query %s.','wpla'), $query );
					echo $errors;
				}
				exit();

			} else {
				echo "invalid product";
			}

		}
	
	} // ajax_wple_show_product_matches()



	// show dynamic listing gallery
	public function ajax_wpl_gallery() {
	
		$default_limit = get_option( 'wplister_gallery_items_limit', 12 );
		$type          = isset( $_REQUEST['type'] )   ? $_REQUEST['type']   : 'new';	
		$limit         = isset( $_REQUEST['limit'] )  ? $_REQUEST['limit']  : $default_limit;	
		$id            = isset( $_REQUEST['id'] )     ? $_REQUEST['id']     : false;	
		$format        = isset( $_REQUEST['format'] ) ? $_REQUEST['format'] : 'html';	

		$items = WPLE_ListingQueryHelper::getItemsForGallery( $type, $id, $limit );
		// echo "<pre>";print_r($items);echo"</pre>";die();

		if ( $format == 'json' ) {

			$json_data = array();
			foreach ($items as $item) {
				$json_item = new stdClass();
				$json_item->ebay_id        = $item['ebay_id'];
				$json_item->post_id        = $item['post_id'];
				$json_item->listing_id     = $item['id'];
				$json_item->title          = $item['auction_title'];
				$json_item->type           = $item['auction_type'];
				$json_item->price          = $item['price'];
				$json_item->quantity       = $item['quantity'];
				$json_item->quantity_sold  = $item['quantity_sold'];
				$json_item->main_image_url = $item['GalleryURL'];
				$json_item->ebay_url       = $item['ViewItemURL'];
				$json_item->site_id        = $item['site_id'];
				$json_item->status         = $item['status'];
				$json_data[] = $json_item;
			}

			// check if callback parameter is set (JSONP support)
			if ( isset($_REQUEST['callback']) ) {				
				header('content-type: application/javascript; charset=utf-8');
			    echo $_REQUEST['callback'] . '(' . json_encode( $json_data ) . ')'; // JSONP 
			} else {
				header('content-type: application/json; charset=utf-8');
				echo json_encode( $json_data ); // plain JSON
			}

			exit();
		}

		// get from_item and template path
		$view = WPLISTER_PATH.'/views/template/gallery.php';
		$from_item = $id ? ListingsModel::getItem( $id ) : false;
		if ( $from_item ) {
			// if gallery.php exists in listing template, use it
			$gallery_tpl_file = WPLISTER_PATH.'/../../' . $from_item['template'] . '/gallery.php';
			if ( file_exists( $gallery_tpl_file ) ) $view = $gallery_tpl_file;
			// the above might fail if wp-content has been moved - better use wp_upload_dir() to get actual template path:
			$upload_dir = wp_upload_dir();
			$gallery_tpl_file = $upload_dir['basedir'] . '/wp-lister/templates/' . basename( $from_item['template'] ) . '/gallery.php';
			if ( file_exists( $gallery_tpl_file ) ) $view = $gallery_tpl_file;
		}

		// load gallery template
		if ( file_exists($view) ) {
			header('X-Frame-Options: GOFORIT');
			include( $view );
		} else {
			echo "file not found: ".$view;
		}
		exit();
	} // ajax_wpl_gallery()


	// show dynamic listing gallery
	public function ajax_wpl_ebay_store_categories() {
	
		$default_account_id = get_option( 'wplister_default_account_id' );
		$account_id         = isset( $_REQUEST['account_id'] ) ? intval($_REQUEST['account_id']) : $default_account_id;	

		$store_categories = EbayCategoriesModel::getEntireStoreCategoryTree( 0, $account_id );


		// check if callback parameter is set
		if ( isset($_REQUEST['callback']) ) {
			// return JSONP 
			header('content-type: application/javascript; charset=utf-8');
		    echo $_REQUEST['callback'] . '(' . json_encode( $store_categories ) . ')';
		} else {
			// return plain JSON
			header('content-type: application/json; charset=utf-8');
			echo json_encode( $store_categories );
		}

		exit();
	} // ajax_wpl_ebay_store_categories()


	// handle calls to logfile viewer based on php-tail
	// http://code.google.com/p/php-tail
	public function ajax_wplister_tail_log() {

		require_once( WPLISTER_PATH . '/includes/php-tail/PHPTail.php' );
		
		// Initilize a new instance of PHPTail - 3 sec reload, 512k max
		$tail = new PHPTail( WPLE()->logger->file, 3000, 524288 );

		// handle ajax call
		if(isset($_GET['ajax']))  {
			echo $tail->getNewLines( @$_GET['lastsize'], $_GET['grep'], $_GET['invert']);
			die();
		}

		// else show gui
		$tail->generateGUI();
		die();		
	}

	// there are still problems with eBay's notification system. 
	// this handler is for debugging purposes - it will send request details to the developer
	// for manual test call: www.example.com/wp-admin/admin-ajax.php?action=handle_ebay_notify
	public function ajax_handle_ebay_notify() {
		// require_once WPLISTER_PATH . '/includes/EbatNs/' . 'EbatNs_NotificationClient.php';
		// require_once WPLISTER_PATH . '/includes/EbatNs/' . 'EbatNs_ResponseError.php';

		// load eBay classes
		EbayController::loadEbayClasses();

		$handler = new EbatNs_NotificationClient();
		$body    = file_get_contents('php://input');
		$res     = $handler->getResponse($body);
	
		WPLE()->logger->info('handle_ebay_notify() - time: '.date('Y-m-d H:i:s') );
		//WPLE()->logger->info('POST:  '.print_r($_POST,1));
		WPLE()->logger->info('REQUEST: '.print_r($_REQUEST,1));
		WPLE()->logger->info('SERVER:  '.print_r($_SERVER,1));
		
		$headers = getallheaders();
		WPLE()->logger->info('headers: '.print_r($headers,1));
		WPLE()->logger->info('body:    '.print_r($body,1));
		WPLE()->logger->info('response:'.print_r($res,1));

        // log to db
        $this->dblogger = new WPL_EbatNs_Logger();
        $this->dblogger->updateLog( array(
            'callname'    => 'handle_ebay_notify',
            'request_url' => 'platform notification',
            'request'     => $body."\n\n" . print_r($_REQUEST,1)."\n\n" . print_r($_SERVER,1),
            'response'    => json_encode( $res ),
            'success'     => 'Success'
        ));
		
        // send email (for debugging only)
		$msg = 'A notification was received at '.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']."\n\n";
		$msg .= 'Body:     '.print_r($body,1)."\n\n";
		$msg .= 'Response: '.print_r($res,1)."\n";
		$msg .= 'REQUEST:  '.print_r($_REQUEST,1)."\n";
		$msg .= 'SERVER:   '.print_r($_SERVER,1)."\n";
		$msg .= 'Headers:  '.print_r($headers,1)."\n";

		// $to = get_option('admin_email', 'info@wplab.com');
		$to      = 'info@wplab.com';
		$subject = 'New WPLE platform notification (debug info)';
		wp_mail( $to, $subject, $msg );
		
		echo 'ACK';
		exit();
	}
		


}

// instantiate object
$oWPL_AjaxHandler = new WPL_AjaxHandler();

