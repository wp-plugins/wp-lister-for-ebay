<?php

class WPL_AjaxHandler extends WPL_Core {

	public function __construct() {
		parent::__construct();
		
		// called from category tree
		add_action('wp_ajax_e2e_get_ebay_categories_tree',  array( &$this, 'ajax_get_ebay_categories_tree' ) );		
		add_action('wp_ajax_e2e_get_store_categories_tree', array( &$this, 'ajax_get_store_categories_tree' ) );		

		// called from edit products page
		add_action('wp_ajax_wpl_getCategorySpecifics', array( &$this, 'ajax_getCategorySpecifics' ) );		
		add_action('wp_ajax_wpl_getCategoryConditions', array( &$this, 'ajax_getCategoryConditions' ) );		
		
		// called from jobs window
		add_action('wp_ajax_wpl_jobs_load_tasks', array( &$this, 'jobs_load_tasks' ) );	
		add_action('wp_ajax_wpl_jobs_run_task', array( &$this, 'jobs_run_task' ) );	
		add_action('wp_ajax_wpl_jobs_complete_job', array( &$this, 'jobs_complete_job' ) );	

		// logfile viewer
		add_action('wp_ajax_wplister_tail_log', array( &$this, 'ajax_wplister_tail_log' ) );

		// handle dynamic listing galleries
		add_action('wp_ajax_wpl_gallery', array( &$this, 'ajax_wpl_gallery' ) );
		add_action('wp_ajax_nopriv_wpl_gallery', array( &$this, 'ajax_wpl_gallery' ) );

		// handle incoming ebay notifications
		add_action('wp_ajax_handle_ebay_notify', array( &$this, 'ajax_handle_ebay_notify' ) );
		add_action('wp_ajax_nopriv_handle_ebay_notify', array( &$this, 'ajax_handle_ebay_notify' ) );
	}
	
	// fetch category specifics
	public function ajax_getCategorySpecifics() {
		
		$category_id = $_REQUEST['id'];

		$this->initEC();
		$result = $this->EC->getCategorySpecifics( $category_id );
		$this->EC->closeEbay();

		$this->returnJSON( $result );
		exit();
	}
	
	// fetch category conditions
	public function ajax_getCategoryConditions() {
		
		$category_id = $_REQUEST['id'];

		$this->initEC();
		$result = $this->EC->getCategoryConditions( $category_id );
		$this->EC->closeEbay();

		$this->returnJSON( $result );
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

		$job  = $_REQUEST['job'];
		$task = $_REQUEST['task'];

		// register shutdown handler
		global $wpl_shutdown_handler_enabled;
		$wpl_shutdown_handler_enabled = true;
		register_shutdown_function( array( $this, 'shutdown_handler' ) );

		$this->logger->info('running task: '.print_r($task,1));

		// handle job name
		switch ( $task['task'] ) {
			case 'loadShippingServices':
				
				// call EbayController
				$this->initEC();
				$result = $this->EC->loadShippingServices();
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
				$this->initEC();
				$result = $this->EC->loadPaymentOptions();
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
				$this->initEC();
				$result = $this->EC->loadStoreCategories();
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
			
			case 'loadEbayCategoriesBranch':
				
				// call EbayController
				$this->initEC();
				$result = $this->EC->loadEbayCategoriesBranch( $task['cat_id'] );
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
				$this->initEC();
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
				$this->initEC();
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
				$this->initEC();
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
				$this->initEC();
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
				$this->initEC();
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
				$this->initEC();
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
				$this->initEC();

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
        $lm = new ListingsModel();
		$listing_ids = ( isset( $_REQUEST['listing_ids'] ) && is_array( $_REQUEST['listing_ids'] ) ) ? $_REQUEST['listing_ids'] : false;
		if ( $listing_ids ) 
	        $items = $lm->getItemsByIdArray( $listing_ids );

		// handle job name
		switch ( $jobname ) {
			case 'updateEbayData':
				
				// call EbayController
				$this->initEC();
				$tasks = $this->EC->initCategoriesUpdate();
				$this->EC->closeEbay();

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
		        $lm = new ListingsModel();
		        $items = $lm->getAllPrepared();
		        
		        // create job from items and send response
		        $response = $this->_create_bulk_listing_job( 'verifyItem', $items, $jobname );
				$this->returnJSON( $response );
				exit();
			
			case 'publishAllVerifiedItems':
				
				// get verified items
		        $lm = new ListingsModel();
		        $items = $lm->getAllVerified();
		        
		        // create job from items and send response
		        $response = $this->_create_bulk_listing_job( 'publishItem', $items, $jobname );
				$this->returnJSON( $response );
				exit();
			
			case 'reviseAllChangedItems':
				
				// get changed items
		        $lm = new ListingsModel();
		        $items = $lm->getAllChanged();
		        
		        // create job from items and send response
		        $response = $this->_create_bulk_listing_job( 'reviseItem', $items, $jobname );
				$this->returnJSON( $response );
				exit();
			
			case 'updateAllPublishedItems':
				
				// get published items
		        $lm = new ListingsModel();
		        $items = $lm->getAllPublished();
		        
		        // create job from items and send response
		        $response = $this->_create_bulk_listing_job( 'updateItem', $items, $jobname );
				$this->returnJSON( $response );
				exit();
			
			case 'updateAllRelistedItems':
				
				// get published items
		        $lm = new ListingsModel();
		        $items = $lm->getAllRelisted();
		        
		        // create job from items and send response
		        $response = $this->_create_bulk_listing_job( 'updateItem', $items, $jobname );
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
			$this->logger->info('adding task for item #'.$item['id'] . ' - '.$item['auction_title']);
			
			$tasks = $this->_prepare_eps_tasks( $item, $taskname, $tasks );

			$task = array( 
				'task'        => $taskname, 
				'displayName' => $item['auction_title'], 
				'id'          => $item['id'] 
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

	public function returnJSON( $data ) {
		global $wpl_shutdown_handler_enabled;
		$wpl_shutdown_handler_enabled = false;
		header('content-type: application/json; charset=utf-8');
		echo json_encode( $data );
	}
	
	// get categories tree node - used on ProfilesPage
	public function ajax_get_ebay_categories_tree() {
	
		$path = $_POST["dir"];	
		$parent_cat_id = basename( $path );
		$categories = EbayCategoriesModel::getChildrenOf( $parent_cat_id );		
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
	
		$path = $_POST["dir"];	
		$parent_cat_id = basename( $path );
		$categories = EbayCategoriesModel::getChildrenOfStoreCategory( $parent_cat_id );		
		$categories = apply_filters( 'wplister_get_store_categories_node', $categories, $parent_cat_id, $path );
		
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

	// show dynamic listing gallery
	public function ajax_wpl_gallery() {
	
		$type    = isset( $_REQUEST['type'] )  ? $_REQUEST['type']  : 'new';	
		$limit   = isset( $_REQUEST['limit'] ) ? $_REQUEST['limit'] : 12;	
		$id      = isset( $_REQUEST['id'] )    ? $_REQUEST['id']    : false;	

		$lm = new ListingsModel();
		$items = $lm->getItemsForGallery( $type, $id, $limit );
		// echo "<pre>";print_r($items);echo"</pre>";die();

		// get from_item and template path
		$view = WPLISTER_PATH.'/views/template/gallery.php';
		$from_item = $id ? $lm->getItem( $id ) : false;
		if ( $from_item ) {
			// if gallery.php exists in listing template, use it
			$gallery_tpl_file = WPLISTER_PATH.'/../../' . $from_item['template'] . '/gallery.php';
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
	}

	// handle calls to logfile viewer based on php-tail
	// http://code.google.com/p/php-tail
	public function ajax_wplister_tail_log() {

		require_once( WPLISTER_PATH . '/includes/php-tail/PHPTail.php' );
		
		// Initilize a new instance of PHPTail
		$tail = new PHPTail( $this->logger->file, 3000 );

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

		// TODO: call loadEbayClasses() instead
		require_once WPLISTER_PATH . '/includes/EbatNs/' . 'EbatNs_NotificationClient.php';
		require_once WPLISTER_PATH . '/includes/EbatNs/' . 'EbatNs_ResponseError.php';

		$handler = new EbatNs_NotificationClient();
		$body = file_get_contents('php://input');
		$res = $handler->getResponse($body);
	
		$this->logger->info('handle_ebay_notify() - time: '.date('Y-m-d H:i:s') );
		#$this->logger->info('POST:'.print_r($_POST,1));
		$this->logger->info('REQUEST:'.print_r($_REQUEST,1));
		$this->logger->info('SERVER:'.print_r($_SERVER,1));
		
		$headers = getallheaders();
		$this->logger->info('headers:'.print_r($headers,1));
		$this->logger->info('body:'.print_r($body,1));
		$this->logger->info('response:'.print_r($res,1));

		$msg = 'I received a notification at '.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']."\n\n";
		$msg .= 'Body: '.print_r($body,1)."\n\n";
		$msg .= 'Response: '.print_r($res,1)."\n";
		$msg .= 'REQUEST: '.print_r($_REQUEST,1)."\n";
		$msg .= 'SERVER: '.print_r($_SERVER,1)."\n";
		$msg .= 'Headers: '.print_r($headers,1)."\n";

		$to = get_option('admin_email', 'support@wplab.com');
		$subject = 'New eBay platform notification';
		wp_mail($to, $subject, $msg);
		
		echo 'OK';
		exit();
	}
		


}

// instantiate object
$oWPL_AjaxHandler = new WPL_AjaxHandler();

