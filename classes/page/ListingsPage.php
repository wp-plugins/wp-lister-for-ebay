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
	}
	
	public function onWpInit() {

		// Add custom screen options
		add_action( "load-toplevel_page_wplister", array( &$this, 'addScreenOptions' ) );
		
		// handle preview action
		if ( $this->requestAction() == 'preview_auction' ) {
			$this->previewListing( $_REQUEST['auction'] );
			exit();
		}

	}

	public function onWpTopAdminMenu() {

		$page_id = add_menu_page( self::ParentTitle, __('WP-Lister','wplister'), self::ParentPermissions, 
					   self::ParentMenuId, array( $this, 'onDisplayListingsPage' ), $this->getImageUrl( 'hammer-16x16.png' ), ProductWrapper::menu_page_position );
	}

	public function handleSubmit() {
        $this->logger->debug("handleSubmit()");

		if ( $this->requestAction() == 'prepare_auction' ) {

			$listingsModel = new ListingsModel();
			if ( ProductWrapper::plugin == 'shopp' ) {
		        $listingsModel->prepareListings( $_REQUEST['selected'] );
			} else {
		        $listingsModel->prepareListings( $_REQUEST['post'] );
			}
	        
	        // redirect to listings page
			wp_redirect( get_admin_url().'admin.php?page=wplister' );
			exit();
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

			// verify new listings if asked to
			// if ( @$_REQUEST['wpl_e2e_verify_after_profile']=='1') {

			//	$this->logger->info( 'verifying new items NOW' );
	
			// 	// get session
			// 	$this->initEC();
				
			// 	// verify prepared items
			// 	foreach( $items as $item ) {
			// 		$listingsModel->verifyAddItem( $item['id'], $this->EC->session );
			// 	}		
			// 	$this->EC->closeEbay();
			// }

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
				$this->showMessage( __('Selected items were revised on eBay.','wplister') );
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
			if ( $this->requestAction() == 'delete' ) {
				$this->initEC();
				$this->EC->deleteListings( $_REQUEST['auction'] );
				$this->EC->closeEbay();
				$this->showMessage( __('Selected items were removed.','wplister') );
			}
			// // handle verify_all_prepared_items action
			// if ( $this->requestAction() == 'verify_all_prepared_items' ) {
			// 	$this->initEC();
			// 	$this->EC->verifyAllPreparedItems();
			// 	$this->EC->closeEbay();
			// 	$this->showMessage( __('All prepared items were verified with eBay.','wplister') );
			// }
			// // handle publish_all_verified_items action
			// if ( $this->requestAction() == 'publish_all_verified_items' ) {
			// 	$this->initEC();
			// 	$this->EC->publishAllVerifiedItems();
			// 	$this->EC->closeEbay();
			// 	$this->showMessage( __('All verified items were published with eBay.','wplister') );
			// }
			// // handle revise_all_changed_items action
			// if ( $this->requestAction() == 'revise_all_changed_items' ) {
			// 	$this->initEC();
			// 	$this->EC->reviseAllChangedItems();
			// 	$this->EC->closeEbay();
			// 	$this->showMessage( __('All changes have been uploaded to eBay.','wplister') );
			// }
			// // handle update_all_published_items action
			// if ( $this->requestAction() == 'update_all_published_items' ) {
			// 	$this->initEC();
			// 	$this->EC->updateAllPublishedItems();
			// 	$this->EC->closeEbay();
			// 	$this->showMessage( __('All published items have been updated from eBay.','wplister') );
			// }


			// handle reapply profile action
			if ( $this->requestAction() == 'reapply' ) {
				$listingsModel = new ListingsModel();
		        $listingsModel->reapplyProfileToItems( $_REQUEST['auction'] );
				$this->showMessage( __('Profiles were re-applied to selected items.','wplister') );
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

	

	public function previewListing( $id ) {
	
		// init model
		$listingsModel = new ListingsModel();
		$preview_html = $listingsModel->getFinalHTML( $id );
		echo $preview_html;
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
