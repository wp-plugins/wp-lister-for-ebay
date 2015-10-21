<?php
/**
 * NetworkAdminPage class
 * 
 */

class NetworkAdminPage extends WPL_Page {

	const slug = 'sites';

	function config()
	{
		// add_action( 'admin_menu', array( &$this, 'onWpTopAdminMenu' ), 10 );
		// add_action( 'admin_menu', array( &$this, 'fixSubmenu' ), 30 );

		add_action( 'network_admin_menu', array( &$this, 'onWpNetworkAdminMenu' ) ); 
		// add_action( 'network_admin_menu', array( &$this, 'fixSubmenu' ), 30 );
	}

	public function onWpNetworkAdminMenu() {

		$page_id = add_menu_page( __('eBay','wplister'), __('eBay','wplister'), self::ParentPermissions, 
					   self::ParentMenuId, array( $this, 'onDisplayNetworkAdminPage' ), $this->getImageUrl( 'hammer-16x16.png' ), ProductWrapper::menu_page_position );

	}

	public function onWpInit() {
		// parent::onWpInit();

		// Add custom screen options
		// add_action( "load-wp-lister_page_wplister-".self::slug, array( &$this, 'addScreenOptions' ) );

	}

	function addScreenOptions() {
		$option = 'per_page';
		$args = array(
	    	'label' => 'Sites',
	        'default' => 20,
	        'option' => 'sites_per_page'
	        );
		add_screen_option( $option, $args );
		// $this->sitesTable = new SitesTable();
	
	    // add_thickbox();
		wp_enqueue_script( 'thickbox' );
		wp_enqueue_style( 'thickbox' );

	}
	


	public function onDisplayNetworkAdminPage() {
		// $this->check_wplister_setup();

		// handle activate action
		if ( $this->requestAction() == 'activate' ) {
			$blog_id = $_REQUEST['site'];
			$this->enableOnBlog( $blog_id );
			$this->showMessage( __('WP-Lister was activated on the selected sites.','wplister') );
		}
		// handle deactivate action
		if ( $this->requestAction() == 'deactivate' ) {
			$blog_id = $_REQUEST['site'];
			$this->disableOnBlog( $blog_id );
			$this->showMessage( __('WP-Lister was deactivated on the selected sites.','wplister') );
		}
		// handle reinstall action
		if ( $this->requestAction() == 'reinstall' ) {
			$this->reinstallOnBlog( $_REQUEST['site'] );
			$this->showMessage( __('WP-Lister was installed from scratch on the selected sites.','wplister') );
		}
		// handle install action
		if ( $this->requestAction() == 'install' ) {
			$this->installOnBlog( $_REQUEST['site'] );
			$this->showMessage( __('WP-Lister was installed on the selected sites.','wplister') );
		}
		// handle uninstall action
		if ( $this->requestAction() == 'uninstall' ) {
			$this->uninstallOnBlog( $_REQUEST['site'] );
			$this->showMessage( __('WP-Lister was uninstalled on the selected sites.','wplister') );
		}


	    //Create an instance of our package class...
	    $sitesTable = new NetworkSitesTable();
    	//Fetch, prepare, sort, and filter our data...
	    $sitesTable->prepare_items();
		
		$aData = array(
			'plugin_url'				=> self::$PLUGIN_URL,
			'message'					=> $this->message,

			'sitesTable'				=> $sitesTable,
		
			'form_action'				=> 'admin.php?page='.self::ParentMenuId.'-sites'
		);
		$this->display( 'sites_page', $aData );
		

	}

	public function enableOnBlog( $blog_id ) {
        switch_to_blog($blog_id);
        update_option( 'wplister_is_enabled', 'Y' );
		restore_current_blog();
	}
	public function disableOnBlog( $blog_id ) {
        switch_to_blog($blog_id);
        update_option( 'wplister_is_enabled', 'N' );
		restore_current_blog();
	}
	public function reinstallOnBlog( $blog_id ) {

        $installer   = new WPLister_Install();
        $uninstaller = new WPLister_Uninstall();

        switch_to_blog($blog_id);
        $uninstaller->deactivatePlugin();
        $installer->createOptions( true );
		restore_current_blog();
	}
	public function installOnBlog( $blog_id ) {
        $installer   = new WPLister_Install();
        switch_to_blog($blog_id);
        $installer->createOptions( true );
		restore_current_blog();
	}
	public function uninstallOnBlog( $blog_id ) {
        $uninstaller = new WPLister_Uninstall();
        switch_to_blog($blog_id);
        $uninstaller->deactivatePlugin();
		restore_current_blog();
	}

	public function showSiteDetails( $id ) {
	
		// init model
		$sitesModel = new SitesModel();		

		// get transaction record
		$transaction = $sitesModel->getItem( $id );
		
		// get auction item record
		$auction_item = ListingsModel::getItemByEbayID( $transaction['item_id'] );
		
		$aData = array(
			'transaction'				=> $transaction,
			'auction_item'				=> $auction_item
		);
		$this->display( 'transaction_details', $aData );
		
	}


}
