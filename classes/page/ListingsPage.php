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
	// 	global $oWPL_WPLister;
	// 	$settingsPage = $oWPL_WPLister->pages['settings'];

	// 	$page_id = add_menu_page( self::ParentTitle, __('WP-Lister','wplister'), self::ParentPermissions, 
	// 				   self::ParentMenuId, array( $settingsPage, 'onDisplaySettingsPage' ), $this->getImageUrl( 'hammer-16x16.png' ), ProductWrapper::menu_page_position );
	// }

	public function onWpTopAdminMenu() {
		$page_id = add_menu_page( self::ParentTitle, $this->main_admin_menu_label, self::ParentPermissions, 
					   self::ParentMenuId, array( $this, 'onDisplayListingsPage' ), $this->getImageUrl( 'hammer-16x16.png' ), ProductWrapper::menu_page_position );
		// $page_id: toplevel_page_wplister
	}

	public function handleSubmitOnInit() {
        $this->logger->debug("handleSubmit()");

		if ( $this->requestAction() == 'prepare_auction' ) {

			$listingsModel = new ListingsModel();
			if ( ProductWrapper::plugin == 'shopp' ) {
		        $listings = $listingsModel->prepareListings( $_REQUEST['selected'] );
			} else {
		        $listings = $listingsModel->prepareListings( $_REQUEST['post'] );
			}
	        
			if ( is_array($listings) && ( sizeof($listings) > 0 ) ) {
	
		        // redirect to listings page
				wp_redirect( get_admin_url().'admin.php?page=wplister' );
				exit();

			}

		}

		if ( $this->requestAction() == 'reselect' ) {

			$listingsModel = new ListingsModel();
	        $listingsModel->reSelectListings( $_REQUEST['auction'] );
	        
	        // redirect to listings page
			wp_redirect( get_admin_url().'admin.php?page=wplister' );
			exit();
		}

		if ( $this->requestAction() == 'apply_listing_profile' ) {

	        $this->logger->info( 'apply_listing_profile' );
	        #$this->logger->info( print_r( $_REQUEST, 1 ) );
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

	}

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

	}
	


	public function onDisplayListingsPage() {
		WPL_Setup::checkSetup();
	
		// init model
		$listingsModel = new ListingsModel();
		$selectedProducts = $listingsModel->selectedProducts();
		
		// do we have new products with no profile yet?
		if ( $selectedProducts ) {
		
		    //Create an instance of our package class...
		    $listingsTable = new ListingsTable();
	    	//Fetch, prepare, sort, and filter our data...
		    $listingsTable->prepare_items( $selectedProducts );
	
			// get profiles
			$profilesModel = new ProfilesModel();
			$profiles = $profilesModel->getAll();
	
			$aData = array(
				'plugin_url'				=> self::$PLUGIN_URL,
				'message'					=> $this->message,
	
				'last_selected_profile'		=> self::getOption('last_selected_profile'),
				'profiles'					=> $profiles,
				'listingsTable'				=> $listingsTable,
			
				'form_action'				=> 'admin.php?page='.self::ParentMenuId
			);
			$this->display( 'listings_prepare_page', $aData );

		// edit listing
		} elseif ( $this->requestAction() == 'edit' ) {

			// get item
			$listingsModel = new ListingsModel();
			$item = $listingsModel->getItem( $_REQUEST['auction'] );

			// unserialize details
			$this->initEC();
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
		
		// show list
		} else {

			// handle save listing
			if ( $this->requestAction() == 'save_listing' ) {
				$this->saveListing();
			}

			// handle verify action
			if ( $this->requestAction() == 'verify' ) {
				$this->initEC();
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
				$this->initEC();
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
				$this->initEC();
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
				$this->initEC();
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
				$this->initEC();
				$this->EC->endItemsOnEbay( $_REQUEST['auction'] );
				$this->EC->closeEbay();
				$this->showMessage( __('Selected listings were ended.','wplister') );
			}
			// handle update from eBay action
			if ( $this->requestAction() == 'update' ) {
				$this->initEC();
				$this->EC->updateItemsFromEbay( $_REQUEST['auction'] );
				$this->EC->closeEbay();
				$this->showMessage( __('Selected items were updated from eBay.','wplister') );
			}
			// handle delete action
			if ( isset( $_REQUEST['auction'] ) && ( $this->requestAction() == 'delete_listing' ) ) {

		        $lm = new ListingsModel();
		        $id = $_REQUEST['auction'];

		        if ( is_array( $id )) {
		            foreach( $id as $single_id ) {
		                $lm->deleteItem( $single_id );  
		            }
		        } else {
		            $lm->deleteItem( $id );         
		        }

				$this->showMessage( __('Selected items were removed.','wplister') );
			}

			// handle archive action
			if ( $this->requestAction() == 'archive' ) {

		        $lm = new ListingsModel();
		        $id = $_REQUEST['auction'];
		        $data = array( 'status' => 'archived' );

		        if ( is_array( $id )) {
		            foreach( $id as $single_id ) {
		                $lm->updateListing( $single_id, $data );
		            }
		        } else {
		            $lm->updateListing( $id, $data );
		        }

				$this->showMessage( __('Selected items were archived.','wplister') );
			}

			// handle lock action
			if ( $this->requestAction() == 'lock' ) {

		        $lm = new ListingsModel();
		        $id = $_REQUEST['auction'];
		        $data = array( 'locked' => true );

		        if ( is_array( $id )) {
		            foreach( $id as $single_id ) {
		                $lm->updateListing( $single_id, $data );
		            }
		        } else {
		            $lm->updateListing( $id, $data );
		        }

				$this->showMessage( __('Selected items were locked.','wplister') );
			}

			// handle unlock action
			if ( $this->requestAction() == 'unlock' ) {

		        $lm = new ListingsModel();
		        $id = $_REQUEST['auction'];
		        $data = array( 'locked' => false );

		        if ( is_array( $id )) {
		            foreach( $id as $single_id ) {
		                $lm->updateListing( $single_id, $data );
		            }
		        } else {
		            $lm->updateListing( $id, $data );
		        }

				$this->showMessage( __('Selected items were unlocked.','wplister') );
			}

			// handle toolbar action - prepare listing from product
			if ( $this->requestAction() == 'wpl_prepare_single_listing' ) {

		        // get profile
				$profilesModel = new ProfilesModel();
		        $profile = isset( $_REQUEST['profile_id'] ) ? $profilesModel->getItem( $_REQUEST['profile_id'] ) : false;

		        if ( $profile ) {
			
					// prepare product
					$listingsModel = new ListingsModel();
			        $listingsModel->prepareProductForListing( $_REQUEST['product_id'] );

			        $listingsModel->applyProfileToNewListings( $profile );		      
					$this->showMessage( __('New listing was prepared from product.','wplister') );

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

			// show warning if duplicate products found
			$this->checkForDuplicates();

	        // get listing status summary
	        $summary = $listingsModel->getStatusSummary();

	        // check for changed items and display reminder
	        if ( isset($summary->changed) && current_user_can( 'publish_ebay_listings' ) ) {
				$msg  = '<p>';
				$msg .= sprintf( __('WP-Lister has found %s changed item(s) which need to be revised on eBay to apply their latest changes.','wplister'), $summary->changed );
				// $msg .= '<br><br>';
				$msg .= '&nbsp;&nbsp;';
				$msg .= '<a id="btn_revise_all_changed_items_reminder" class="btn_revise_all_changed_items_reminder button wpl_job_button">' . __('Revise all changed items','wplister') . '</a>';
				$msg .= '</p>';
				$this->showMessage( $msg );				

	        }

	        // check for relisted items and display reminder
	        if ( isset($summary->relisted) ) {
				$msg  = '<p>';
				$msg .= sprintf( __('WP-Lister has found %s manually relisted item(s) which need to be updated from eBay to fetch their latest changes.','wplister'), $summary->relisted );
				$msg .= '&nbsp;&nbsp;';
				$msg .= '<a id="btn_update_all_relisted_items_reminder" class="btn_update_all_relisted_items_reminder button wpl_job_button">' . __('Update all relisted items','wplister') . '</a>';
				$msg .= '</p>';
				$this->showMessage( $msg );				

	        }

			// get all items
			// $listings = $listingsModel->getAll();
	
		    //Create an instance of our package class...
		    $listingsTable = new ListingsTable();
	    	//Fetch, prepare, sort, and filter our data...
		    $listingsTable->prepare_items();
	
			$aData = array(
				'plugin_url'				=> self::$PLUGIN_URL,
				'message'					=> $this->message,
	
				'listingsTable'				=> $listingsTable,
				'preview_html'				=> isset($preview_html) ? $preview_html : '',
			
				'form_action'				=> 'admin.php?page='.self::ParentMenuId
			);
			$this->display( 'listings_page', $aData );
		
		}

	}


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
			$item['status'] = $this->getValueFromPost( 'listing_status' );
			$item['ebay_id'] = $this->getValueFromPost( 'ebay_id' );
			$item['post_id'] = $this->getValueFromPost( 'post_id' );
			$item['quantity_sold'] = $this->getValueFromPost( 'ebay_id' );
		}

		// update profile
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
			$this->initEC();
			$this->EC->reviseItems( $item['id'] );
			$this->EC->closeEbay();
			$this->showMessage( __('Your changes were updated on eBay.','wplister') );
		}

		
	}

	public function checkForDuplicates() {

		// skip if dupe warning is disabled
		if ( self::getOption( 'hide_dupe_msg' ) ) return;
	
		// show warning if duplicate products found
		$listingsModel = new ListingsModel();
		$duplicateProducts = $listingsModel->getAllDuplicateProducts();
		if ( ! empty($duplicateProducts) ) {

	        // get current page with paging as url param
	        $page = $_REQUEST['page'];
	        if ( isset( $_REQUEST['paged'] )) $page .= '&paged='.$_REQUEST['paged'];

			$msg  = '<p><b>'.__('WP-Lister has found multiple listings for some of your products.','wplister').'</b>';
			$msg .= '&nbsp; <a href="#" onclick="jQuery(\'#wpl_dupe_details\').toggle()">'.__('Show details','wplister').'</a></p>';
			// $msg .= '<br>';
			$msg .= '<div id="wpl_dupe_details" style="display:none"><p>';
			$msg .= __('Creating multiple listings for one product is not recommended as it can cause issues syncing inventory and other unexpected behaviour.','wplister');
			$msg .= '<br>';
			$msg .= __('Please keep only one listing and move unwanted duplicates to the archive.','wplister');
			$msg .= '<br><br>';
			foreach ($duplicateProducts as $dupe) {


				$msg .= '<b>'.__('Listings for product','wplister').' #'.$dupe->post_id.':</b>';
				$msg .= '<br>';
				
				foreach ($dupe->listings as $listing) {
					$color = $listing->status == 'archived' ? 'silver' : '';
					$msg .= '<span style="color:'.$color.'">';					
					$msg .= '&nbsp;&bull;&nbsp;';					
					$msg .= ''.$listing->auction_title.'';					
					if ($listing->ebay_id) $msg .= ' (#'.$listing->ebay_id.')';
					$msg .= ' &ndash; <i>'.$listing->status.'</i>';					
					$msg .= '<br>';
					if ( in_array( $listing->status, array( 'prepared', 'verified', 'ended', 'sold' ) ) ) {
						$delete_link = sprintf('<a class="delete" href="?page=%s&action=%s&auction=%s">%s</a>',$page,'delete_listing',$listing->id,__('Click to remove this listing','wplister'));
						$archive_link = sprintf('<a class="archive" href="?page=%s&action=%s&auction=%s">%s</a>',$page,'archive',$listing->id,__('Click to move to archive','wplister'));
						$msg .= '&nbsp;&nbsp;&nbsp;&nbsp;'.$archive_link;
						$msg .= '<br>';
					}
					$msg .= '</span>';
				}
				$msg .= '<br>';

			}
			$msg .= __('If you are not planning to use the inventory sync, you can hide this warning in settings.','wplister');
			// $msg .= '<br>';
			// $msg .= 'If you need to list single products multiple times for some reason, please contact support@wplab.com and we will find a solution.';
			$msg .= '</p></div>';
			$this->showMessage( $msg );				
		}
	}

	

	public function previewListing( $id ) {
	
		// init model
		$ibm = new ItemBuilderModel();

		$this->initEC();
		$item = $ibm->buildItem( $id, $this->EC->session );
		
		// if ( ! $ibm->checkItem($item) ) return $ibm->result;
		$ibm->checkItem($item);

		$preview_html = $ibm->getFinalHTML( $id, $item );
		// echo $preview_html;

		$aData = array(
			'item'				=> $item,
			'check_result'		=> $ibm->result,
			'preview_html'		=> $preview_html
		);
		header('Content-Type: text/html; charset=utf-8');
		$this->display( 'listings_preview', $aData );
		exit();		

	}

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
							<input type="checkbox" onclick="jQuery('#DeveloperToolBox').toggle();" value="dev" id="dev-hide" name="dev-hide" class="hide-column-tog">
							Developer options
						</label>
					<br class="clear">
				</div>
			</form>
		</div>
		<?php
	}


}
