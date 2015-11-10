<?php
/**
 * ListingsPage class
 * 
 */

class ListingsPage extends WPL_Page {

	const slug = 'auctions';

	function config()
	{
		add_action( 'admin_menu', array( &$this, 'onWpTopAdminMenu' ), 10 );
		add_action( 'admin_menu', array( &$this, 'fixSubmenu' ), 30 );

		// add_action( 'network_admin_menu', array( &$this, 'onWpNetworkAdminMenu' ) ); 
	}
	
	public function onWpInit() {

		// Add custom screen options
		add_action( "load-toplevel_page_wplister", array( &$this, 'addScreenOptions' ) );

		// $this->handleSubmitOnInit();
		add_action( "wp_loaded", array( &$this, 'handleSubmitOnInit' ), 1 );
	}

	// public function onWpNetworkAdminMenu() {
	// 	$settingsPage = WPLE()->pages['settings'];

	// 	$page_id = add_menu_page( $this->app_name, $this->main_admin_menu_label, self::ParentPermissions, 
	// 				   self::ParentMenuId, array( $settingsPage, 'onDisplaySettingsPage' ), $this->getImageUrl( 'hammer-16x16.png' ), ProductWrapper::menu_page_position );
	// }

	public function onWpTopAdminMenu() {
		$page_id = add_menu_page( $this->app_name, $this->main_admin_menu_label, self::ParentPermissions, 
					   self::ParentMenuId, array( $this, 'onDisplayListingsPage' ), $this->getImageUrl( 'hammer-16x16.png' ), ProductWrapper::menu_page_position );
		// $page_id: toplevel_page_wplister
	}

	public function handleSubmitOnInit() {
        WPLE()->logger->debug("handleSubmit()");

		if ( $this->requestAction() == 'prepare_auction' ) {

			$listingsModel = new ListingsModel();
	        $listings = $listingsModel->prepareListings( $_REQUEST['post'] );
	        
	        // redirect to listings page
			wp_redirect( get_admin_url().'admin.php?page=wplister' );
			exit();
		}

		if ( $this->requestAction() == 'reselect' ) {

			ListingsModel::reSelectListings( $_REQUEST['auction'] );
	        
	        // redirect to listings page
			wp_redirect( get_admin_url().'admin.php?page=wplister' );
			exit();
		}

		if ( $this->requestAction() == 'apply_listing_profile' ) {

	        WPLE()->logger->info( 'apply_listing_profile' );
	        #WPLE()->logger->info( print_r( $_REQUEST, 1 ) );
			$profilesModel = new ProfilesModel();
	        $profile = $profilesModel->getItem( $_REQUEST['wpl_e2e_profile_to_apply'] );

			$listingsModel = new ListingsModel();
	        $items = $listingsModel->applyProfileToNewListings( $profile );

			// remember selected profile
			self::updateOption('last_selected_profile', intval( $_REQUEST['wpl_e2e_profile_to_apply'] ) );
	        
	        // redirect to listings page
			if ( @$_REQUEST['wpl_e2e_verify_after_profile']=='1') {
				// verify new listings if asked to
				wp_redirect( get_admin_url().'admin.php?page=wplister&action=verifyPreparedItemsNow' );
			} else {
				wp_redirect( get_admin_url().'admin.php?page=wplister' );
			}
			exit();
		}

		// handle preview action
		if ( $this->requestAction() == 'preview_auction' ) {
			$this->previewListing( $_REQUEST['auction'] );
			exit();
		}

	} // handleSubmitOnInit()

	function addScreenOptions() {
		
		if ( ( isset($_GET['action']) ) && ( $_GET['action'] == 'edit' ) ) {
			// on edit page render developers options
			add_screen_options_panel('wplister_developer_options', '', array( &$this, 'renderDeveloperOptions'), 'toplevel_page_wplister' );

		} else {
			// on listings page render table options
			$option = 'per_page';
			$args = array(
		    	'label' => 'Listings',
		        'default' => 20,
		        'option' => 'listings_per_page'
		        );
			add_screen_option( $option, $args );
			$this->listingsTable = new ListingsTable();
		}
	
	    // add_thickbox();
		wp_enqueue_script( 'thickbox' );
		wp_enqueue_style( 'thickbox' );

		// ProfileSelector
		wp_register_script( 'wple_profile_selector', self::$PLUGIN_URL.'/js/classes/ProfileSelector.js?ver='.time(), array( 'jquery' ) );
		wp_enqueue_script ( 'wple_profile_selector' );
		wp_localize_script( 'wple_profile_selector', 'wple_ProfileSelector_i18n', array(
				'WPLE_URL' 	=> WPLISTER_URL
			)
		);

	} // addScreenOptions()
	


	public function handleActions() {

		// handle save listing
		if ( $this->requestAction() == 'save_listing' ) {
			$this->saveListing();
		}

		// set account_id
		$account_id = isset( $_REQUEST['auction'] ) ? WPLE_ListingQueryHelper::getAccountID( $_REQUEST['auction'] ) : false;

		// handle verify action
		if ( $this->requestAction() == 'verify' ) {
			$this->initEC( $account_id );
			$this->EC->verifyItems( $_REQUEST['auction'] );
			$this->EC->closeEbay();
			if ( $this->EC->isSuccess ) {
				$this->showMessage( __('Selected items were verified with eBay.','wplister') );
			} else {
				$this->showMessage( __('There were some problems verifying your items.','wplister'), 1 );					
			}
		}
		// handle revise action
		if ( $this->requestAction() == 'revise' ) {
			$this->initEC( $account_id );
			$this->EC->reviseItems( $_REQUEST['auction'] );
			$this->EC->closeEbay();
			if ( $this->EC->isSuccess ) {
				$this->showMessage( __('Selected items were revised on eBay.','wplister') );
			} else {
				$this->showMessage( __('There were some problems revising your items.','wplister'), 1 );					
			}
		}
		// handle publish to eBay action
		if ( $this->requestAction() == 'publish2e' ) {
			$this->initEC( $account_id );
			$this->EC->sendItemsToEbay( $_REQUEST['auction'] );
			$this->EC->closeEbay();
			if ( $this->EC->isSuccess ) {
				$this->showMessage( __('Selected items were published on eBay.','wplister') );
			} else {
				$this->showMessage( __('Some items could not be published.','wplister'), 1 );					
			}
		}
		// handle relist action
		if ( $this->requestAction() == 'relist' ) {
			$this->initEC( $account_id );
			$this->EC->relistItems( $_REQUEST['auction'] );
			$this->EC->closeEbay();
			if ( $this->EC->isSuccess ) {
				$this->showMessage( __('Selected items were re-listed on eBay.','wplister') );
			} else {
				$this->showMessage( __('There were some problems relisting your items.','wplister'), 1 );					
			}
		}
		// handle end_item action
		if ( $this->requestAction() == 'end_item' ) {
			$this->initEC( $account_id );
			$this->EC->endItemsOnEbay( $_REQUEST['auction'] );
			$this->EC->closeEbay();
			$this->showMessage( __('Selected listings were ended.','wplister') );
		}
		// handle update from eBay action
		if ( $this->requestAction() == 'update' ) {
			$this->initEC( $account_id );
			$this->EC->updateItemsFromEbay( $_REQUEST['auction'] );
			$this->EC->closeEbay();
			$this->showMessage( __('Selected items were updated from eBay.','wplister') );
		}
		// handle delete action
		if ( isset( $_REQUEST['auction'] ) && ( $this->requestAction() == 'delete_listing' ) ) {

	        $id = $_REQUEST['auction'];

	        if ( is_array( $id )) {
	            foreach( $id as $single_id ) {
	                WPLE_ListingQueryHelper::deleteItem( $single_id );  
	            }
	        } else {
	            WPLE_ListingQueryHelper::deleteItem( $id );         
	        }

			$this->showMessage( __('Selected items were removed.','wplister') );
		}

		// handle archive action
		if ( $this->requestAction() == 'archive' ) {

	        $id = $_REQUEST['auction'];
	        $data = array( 'status' => 'archived' );

	        if ( is_array( $id )) {
	            foreach( $id as $single_id ) {
	                ListingsModel::updateListing( $single_id, $data );
	            }
	        } else {
	            ListingsModel::updateListing( $id, $data );
	        }

			$this->showMessage( __('Selected items were archived.','wplister') );
		}

		// handle wple_reset_status action
		if ( $this->requestAction() == 'wple_reset_status' ) {

	        $lm = new ListingsModel();
	        $id = $_REQUEST['auction'];
	        $data = array( 
				'status'         => 'prepared',
				'ebay_id'        => NULL,
				'end_date'       => NULL,
				'date_published' => NULL,
				'last_errors'    => '',
	        );

	        if ( is_array( $id ) ) {
	            foreach( $id as $single_id ) {
	            	$status = WPLE_ListingQueryHelper::getStatus( $single_id );
	            	if ( ! in_array( $status, array('ended','sold','archived') ) ) {
	            		wple_show_message("Item with status <i>$status</i> was skipped. Only ended and sold items can have their status reset to <i>prepared</i>.", 'warn' );
	            		continue;
	            	}
	                ListingsModel::updateListing( $single_id, $data );
			        $lm->reapplyProfileToItem( $single_id );
	            }
				wple_show_message( __('Selected items had their status reset to prepared.','wplister') );
	        }

		}

		// handle wple_clear_eps_data action
		if ( $this->requestAction() == 'wple_clear_eps_data' ) {
	        $id = $_REQUEST['auction'];

	        if ( is_array( $id ) ) {
	            foreach( $id as $single_id ) {
	                ListingsModel::updateWhere( 
	                	array( 'id' => $single_id ), 
	                	array( 'eps' => '' ) 
	                );
	            }
				wple_show_message( __('EPS cache was cleared for selected items.','wplister') );
	        }

		}

		// handle lock action
		if ( $this->requestAction() == 'lock' ) {

	        $id = $_REQUEST['auction'];
	        $data = array( 'locked' => true );

	        if ( is_array( $id )) {
	            foreach( $id as $single_id ) {
	                ListingsModel::updateListing( $single_id, $data );
	            }
	        } else {
	            ListingsModel::updateListing( $id, $data );
	        }

			$this->showMessage( __('Selected items were locked.','wplister') );
		}

		// handle unlock action
		if ( $this->requestAction() == 'unlock' ) {

	        $id = $_REQUEST['auction'];
	        $data = array( 'locked' => false );

	        if ( is_array( $id )) {
	            foreach( $id as $single_id ) {
	                ListingsModel::updateListing( $single_id, $data );
	            }
	        } else {
	            ListingsModel::updateListing( $id, $data );
	        }

			$this->showMessage( __('Selected items were unlocked.','wplister') );
		}

		// handle cancel_schedule action
		if ( $this->requestAction() == 'cancel_schedule' ) {

	        $id = $_REQUEST['auction'];
	        $data = array( 'relist_date' => null );

	        if ( is_array( $id )) {
	            foreach( $id as $single_id ) {
	                ListingsModel::updateListing( $single_id, $data );
	            }
	        } else {
	            ListingsModel::updateListing( $id, $data );
	        }

			$this->showMessage( __('Selected items were unscheduled from auto relist.','wplister') );
		}

		// clean listing archive
		if ( $this->requestAction() == 'wpl_clean_listing_archive' ) {
	        WPLE_ListingQueryHelper::cleanArchive();				
			$this->showMessage( __('Archive was cleared.','wplister') );
		}

		// handle toolbar action - prepare listing from product
		if ( $this->requestAction() == 'wpl_prepare_single_listing' ) {

	        // get profile
			$profilesModel = new ProfilesModel();
	        $profile = isset( $_REQUEST['profile_id'] ) ? $profilesModel->getItem( $_REQUEST['profile_id'] ) : false;

	        if ( $profile ) {
		
				// prepare product
				$listingsModel = new ListingsModel();
		        $listing_id = $listingsModel->prepareProductForListing( $_REQUEST['product_id'], $profile['profile_id'] );

				if ( $listing_id ) {
			        $listingsModel->applyProfileToNewListings( $profile );		      
					$this->showMessage( __('New listing was prepared from product.','wplister') );
				} else {
					$msg = __('Could not create a new listing from this product.','wplister');
					if ( $listingsModel->errors )
						$msg .= '<br>'.join('<br>',$listingsModel->errors);
					if ( $listingsModel->warnings )
						$msg .= '<br>'.join('<br>',$listingsModel->warnings);
					$this->showMessage( $msg, 2 );
				}			


	        } elseif ( isset( $_REQUEST['product_id'] ) ) {

				// prepare product
				$listingsModel = new ListingsModel();
		        $listingsModel->prepareProductForListing( $_REQUEST['product_id'] );

	        }

		}


		// handle reapply profile action
		if ( $this->requestAction() == 'reapply' ) {
			$listingsModel = new ListingsModel();
	        $listingsModel->reapplyProfileToItems( $_REQUEST['auction'] );
			$this->showMessage( __('Profiles were re-applied to selected items.','wplister') );
		}

		// cancel (re-)selecting profile process
		if ( $this->requestAction() == 'cancel_profile_selection' ) {
			ListingsModel::cancelSelectingListings();
		}

	} // handleActions()



	public function onDisplayListingsPage() {
		$this->check_wplister_setup();
	
		// handle actions
		$this->handleActions();

		// do we have new products with no profile yet?
		$selectedProducts = WPLE_ListingQueryHelper::selectedProducts();
		if ( $selectedProducts ) {
	
			$this->displayPrepareListingsPage( $selectedProducts );

		// edit listing
		} elseif ( $this->requestAction() == 'edit' ) {

			$this->displayEditPage();

		// show list
		} else {

			// show warning if duplicate products found
			$this->checkForDuplicates();

			// check for profile waiting to be applied
			$this->checkForDelayedProfiles();

	        // get listing status summary
	        $summary = WPLE_ListingQueryHelper::getStatusSummary();

	        // check for changed items and display reminder
	        if ( isset($summary->changed) && current_user_can( 'publish_ebay_listings' ) ) {
				$msg  = '<p>';
				$msg .= sprintf( __('There are %s changed item(s) which need to be revised on eBay to apply their latest changes.','wplister'), $summary->changed );
				// $msg .= '<br><br>';
				$msg .= '&nbsp;&nbsp;';
				$msg .= '<a id="btn_revise_all_changed_items_reminder" class="btn_revise_all_changed_items_reminder button wpl_job_button">' . __('Revise all changed items','wplister') . '</a>';
				$msg .= '</p>';
				$this->showMessage( $msg );				
	        }

	        // check for items to be relisted and display message
	        $listing_status = isset( $_REQUEST['listing_status'] ) ? $_REQUEST['listing_status'] : false;
	        if ( isset($summary->relist) && current_user_can( 'publish_ebay_listings' ) && $listing_status == 'relist' ) {
				$msg  = '<p>';
				$msg .= sprintf( __('There are %s items which are currently ended on eBay, but are in stock on your website and can be relisted.','wplister'), $summary->relist );
				// $msg .= '<br><br>';
				$msg .= '&nbsp;&nbsp;';
				$msg .= '<a id="btn_relist_all_restocked_items" class="btn_relist_all_restocked_items button wpl_job_button">' . __('Relist all restocked items','wplister') . '</a>';
				$msg .= '</p>';
				$this->showMessage( $msg );				
	        }

	        // check for relisted items and display reminder
	        if ( isset($summary->relisted) ) {
				$msg  = '<p>';
				$msg .= sprintf( __('There are %s manually relisted item(s) which need to be updated from eBay to fetch their latest changes.','wplister'), $summary->relisted );
				$msg .= '&nbsp;&nbsp;';
				$msg .= '<a id="btn_update_all_relisted_items_reminder" class="btn_update_all_relisted_items_reminder button wpl_job_button">' . __('Update all relisted items','wplister') . '</a>';
				$msg .= '</p>';
				$this->showMessage( $msg, 2 );				
	        }

			// get all items
			// $listings = WPLE_ListingQueryHelper::getAll();
	
		    //Create an instance of our package class...
		    // $this->listingsTable = new ListingsTable();
	    	//Fetch, prepare, sort, and filter our data...
		    $this->listingsTable->prepare_items();
	
			$aData = array(
				'plugin_url'				=> self::$PLUGIN_URL,
				'message'					=> $this->message,
	
				'listingsTable'				=> $this->listingsTable,
				'preview_html'				=> isset($preview_html) ? $preview_html : '',
			
				'form_action'				=> 'admin.php?page='.self::ParentMenuId
			);
			$this->display( 'listings_page', $aData );
		
		}

	} // onDisplayListingsPage()


	public function displayPrepareListingsPage( $selectedProducts ) {

		// show warning if duplicate products found
		$this->checkForDuplicates();

	    //Create an instance of our package class...
	    // $this->listingsTable = new ListingsTable();
    	//Fetch, prepare, sort, and filter our data...
	    $this->listingsTable->selectedItems = $selectedProducts;
	    $this->listingsTable->prepare_items();

		// get profiles
		$profilesModel = new ProfilesModel();
		$profiles = $profilesModel->getAll();

		$aData = array(
			'plugin_url'				=> self::$PLUGIN_URL,
			'message'					=> $this->message,

			'last_selected_profile'		=> self::getOption('last_selected_profile'),
			'profiles'					=> $profiles,
			'listingsTable'				=> $this->listingsTable,
		
			'form_action'				=> 'admin.php?page='.self::ParentMenuId
		);
		$this->display( 'listings_prepare_page', $aData );

	} // displayPrepareListingsPage()
	

	public function displayEditPage() {

		// get item
		$item = ListingsModel::getItem( $_REQUEST['auction'] );

		// unserialize details
		$this->initEC( $item['account_id'] );
		// $item['details'] = maybe_unserialize( $item['details'] );
		// echo "<pre>";print_r($item);echo"</pre>";die();
		
		// get ebay data
		$countries			 	= EbayShippingModel::getEbayCountries();
		// $template_files 		= $this->getTemplatesList();
		$templatesModel = new TemplatesModel();
		$templates = $templatesModel->getAll();

		$aData = array(
			'plugin_url'				=> self::$PLUGIN_URL,
			'message'					=> $this->message,

			'item'						=> $item,
			'countries'					=> $countries,
			'template_files'			=> $templates,
			
			'form_action'				=> 'admin.php?page='.self::ParentMenuId . ( isset($_REQUEST['paged']) ? '&paged='.$_REQUEST['paged'] : '' )
		);
		$this->display( 'listings_edit_page', array_merge( $aData, $item ) );

	} // displayEditPage()


	private function saveListing() {
		global $wpdb;	

		// sql columns
		$item = array();
		$item['id'] 						= $this->getValueFromPost( 'listing_id' );
		$item['auction_title'] 				= stripslashes( $this->getValueFromPost( 'auction_title' ) );
		$item['price'] 						= $this->getValueFromPost( 'price' );
		$item['quantity'] 					= $this->getValueFromPost( 'quantity' );
		$item['listing_duration'] 			= $this->getValueFromPost( 'listing_duration' );
		$item['auction_type'] 				= $this->getValueFromPost( 'auction_type' );
		$item['template']					= $this->getValueFromPost( 'template' );


		// if item is published change status to changed
		if ( 'published' == $this->getValueFromPost( 'status' ) ) {
			$item['status'] = 'changed';
		}

		// handle developer settings
		if ( $this->getValueFromPost( 'enable_dev_mode' ) == '1' ) {
			$item['status']        = $this->getValueFromPost( 'listing_status' );
			$item['ebay_id']       = $this->getValueFromPost( 'ebay_id' );
			$item['post_id']       = $this->getValueFromPost( 'post_id' );
			$item['quantity_sold'] = $this->getValueFromPost( 'quantity_sold' );
			$item['site_id']       = $this->getValueFromPost( 'site_id' );
			$item['account_id']    = $this->getValueFromPost( 'account_id' );
		}

		// update listing
		$result = $wpdb->update( $wpdb->prefix.'ebay_auctions', $item, 
			array( 'id' => $item['id'] ) 
		);

		// proper error handling
		if ($result===false) {
			$this->showMessage( "There was a problem saving your listing.<br>SQL:<pre>".$wpdb->last_query.'</pre>', true );	
			return;
		} else {
			$this->showMessage( __('Listing updated.','wplister') );
		}

		// optionally revise item on save
		if ( 'yes' == $this->getValueFromPost( 'revise_item_on_save' ) ) {
			$account_id = WPLE_ListingQueryHelper::getAccountID( $item['id'] );
			$this->initEC( $account_id );
			$this->EC->reviseItems( $item['id'] );
			$this->EC->closeEbay();
			$this->showMessage( __('Your changes were updated on eBay.','wplister') );
		}
		
	} // saveListing()

	public function checkForDuplicates() {

		// skip if dupe warning is disabled
		if ( self::getOption( 'hide_dupe_msg' ) ) return;
	
		// show warning if duplicate products found
		$duplicateProducts = WPLE_ListingQueryHelper::getAllDuplicateProducts();
		if ( ! empty($duplicateProducts) ) {

	        // get current page with paging as url param
	        $page = $_REQUEST['page'];
	        if ( isset( $_REQUEST['paged'] )) $page .= '&paged='.$_REQUEST['paged'];

			$msg  = '<p><b>'.sprintf( __('Warning: There are duplicate listings for %s product(s).','wplister'), sizeof($duplicateProducts) ).'</b>';
			$msg .= '&nbsp; <a href="#" onclick="jQuery(\'#wpl_dupe_details\').toggle()" class="button button-small">'.__('Show details','wplister').'</a></p>';
			// $msg .= '<br>';
			$msg .= '<div id="wpl_dupe_details" style="display:none"><p>';
			$msg .= __('Creating multiple listings for one product is not recommended as it can cause issues syncing sales and other unexpected behaviour.','wplister');
			$msg .= '<br>';
			$msg .= __('Please keep only one listing and move unwanted duplicates to the archive.','wplister');
			$msg .= '<br><br>';

			$msg .= $this->renderDupeTable( $duplicateProducts );

			$msg .= __('If you are not planning to use the synchronize sales option, you can hide this warning in settings.','wplister');
			// $msg .= '<br>';
			// $msg .= 'If you need to list single products multiple times for some reason, please contact support@wplab.com and we will find a solution.';
			$msg .= '</p></div>';
			$this->showMessage( $msg, 2 );				
		}

	} // checkForDuplicates()

	public function renderDupeTable( $listings, $column = 'post_id' ) {
		if ( empty($listings) ) return '';

        // get current page with paging as url param
        $page = $_REQUEST['page'];
        if ( isset( $_REQUEST['paged'] )) $page .= '&paged='.$_REQUEST['paged'];
		$msg = '';

		foreach ($listings as $dupe) {

			$account_title = WPLE_eBayAccount::getAccountTitle( $dupe->account_id );

			$msg .= '<b>'.__('Listings for product','wplister').' #'.$dupe->post_id.' ('.$account_title.'):</b>';
			$msg .= '<br>';

			$duplicateListings = WPLE_ListingQueryHelper::getAllListingsForProductAndAccount( $dupe->post_id, $dupe->account_id );
			
			foreach ($duplicateListings as $listing) {
				$color = $listing->status == 'archived' ? 'silver' : '';
				$msg .= '<span style="color:'.$color.'">';					
				$msg .= '&nbsp;&bull;&nbsp;';					
				$msg .= ''.$listing->auction_title.'';					
				if ($listing->ebay_id) $msg .= ' (#'.$listing->ebay_id.')';
				$msg .= ' &ndash; <i>'.$listing->status.'</i>';					
				$msg .= '<br>';
				if ( in_array( $listing->status, array( 'prepared', 'verified', 'ended', 'sold' ) ) ) {
					$archive_link = sprintf('<a class="archive button button-small" href="?page=%s&action=%s&auction=%s">%s</a>',$page,'archive',$listing->id,__('Click to move to archive','wplister'));
					$msg .= '&nbsp;&nbsp;&nbsp;&nbsp;'.$archive_link;
					$msg .= '<br>';
				}
				if ( in_array( $listing->status, array( 'selected' ) ) ) {
					$delete_link = sprintf('<a class="delete button button-small button-primary" href="?page=%s&action=%s&auction=%s">%s</a>',$page,'delete_listing',$listing->id,__('Click to remove this listing','wplister'));
					$msg .= '&nbsp;&nbsp;&nbsp;&nbsp;'.$delete_link;
					$msg .= '<br>';
				}
				$msg .= '</span>';
			}
			$msg .= '<br>';

		}

		return $msg;
	} // renderDupeTable()



	// check if we need to apply a profile to all its items
	public function checkForDelayedProfiles() {

		$profile_id = get_option('wple_job_reapply_profile_id' );
		if ( ! $profile_id ) return;

		$msg  = '<p>';
		$msg .= 'Please wait a moment while the profile is applied to all linked items.';
		$msg .= '&nbsp;&nbsp;';
		$msg .= '<a id="btn_run_delayed_profile_application" class="btn_run_delayed_profile_application button wpl_job_button">' . __('Apply Profile','wplister') . '</a>';
		$msg .= '</p>';
		wple_show_message( $msg, 'warn' );				

	} // checkForDelayedProfiles()


	public function previewListing( $id ) {
	
		// init model
		$ibm        = new ItemBuilderModel();
		$account_id = WPLE_ListingQueryHelper::getAccountID( $id );
		$account    = WPLE_eBayAccount::getAccount( $account_id );

		$this->initEC( $account_id );
		$item = $ibm->buildItem( $id, $this->EC->session, false, true );
		
		// if ( ! $ibm->checkItem($item) ) return $ibm->result;
		$ibm->checkItem($item);

		// $preview_html = $ibm->getFinalHTML( $id, $item, true );
		$preview_html = $item->Description;
		// echo $preview_html;

		// set condition name
		$item->ConditionName = $this->getConditionDisplayName( $item->getConditionID() );

		$aData = array(
			'item'				=> $item,
			'site_id'			=> $account ? $account->site_id : false,
			'check_result'		=> $ibm->result,
			'preview_html'		=> $preview_html
		);
		header('Content-Type: text/html; charset=utf-8');
		$this->display( 'listings_preview', $aData );
		exit();		

	} // previewListing()


	public function getConditionDisplayName( $ConditionID ) {

		$conditions = array(
			1000 => 'New',
			1500 => 'New other',
			1750 => 'New with defects',
			2000 => 'Manufacturer refurbished',
			2500 => 'Seller refurbished',
			3000 => 'Used',
			4000 => 'Very Good',
			5000 => 'Good',
			6000 => 'Acceptable',
			7000 => 'For parts or not working',
		);

		if ( ! isset( $conditions[ $ConditionID ] ) ) return $ConditionID;

		return $conditions[ $ConditionID ];
	} // getConditionDisplayName()

	public function fixSubmenu() {
		global $submenu;
		if ( isset( $submenu[self::ParentMenuId] ) ) {
			$submenu[self::ParentMenuId][0][0] = __('Listings','wplister');
		}
	}


	public function renderDeveloperOptions() {
		?>
		<div class="hidden" id="screen-options-wrap" style="display: block;">
			<form method="post" action="" id="dev-settings">
				<h5>Show on screen</h5>
				<div class="metabox-prefs">
						<label for="dev-hide">
							<input type="checkbox" onclick="jQuery('.dev_box').toggle();" value="dev" id="dev-hide" name="dev-hide" class="hide-column-tog">
							Developer options
						</label>
					<br class="clear">
				</div>
			</form>
		</div>
		<?php
	}


}
