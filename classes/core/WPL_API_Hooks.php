<?php
/**
 * WPL_API_Hooks
 *
 * implements public action hooks for 3rd party developers
 *
 * TODO: document other filter hooks intended for 3rd party developers, mainly:
 *  - wplister_get_product_main_image
 *  - wplister_product_images
 *  - wplister_ebay_price
 *  - wplister_before_process_template_html
 *  - wplister_process_template_html
 *  - wplister_listing_columns
 */

class WPL_API_Hooks extends WPL_Core {
	
	public function __construct() {
		parent::__construct();

		// revise inventory status on eBay
		add_action( 'wplister_revise_inventory_status', array( &$this, 'wplister_revise_inventory_status' ), 10, 1 );

		// revise item on eBay
		add_action( 'wplister_revise_item', array( &$this, 'wplister_revise_item' ), 10, 1 );
		add_action( 'wplister_relist_item', array( &$this, 'wplister_relist_item' ), 10, 1 );

		// re-apply profile and mark listing item as changed
		add_action( 'wplister_product_has_changed', array( &$this, 'wplister_product_has_changed' ), 10, 1 );

		// process inventory changes from amazon
		add_action( 'wpla_inventory_status_changed', array( &$this, 'wpla_inventory_status_changed' ), 10, 1 );

		// handle ajax requests from third party CSV import plugins
		add_action( 'wp_ajax_woo-product-importer-ajax',      	array( &$this, 'handle_third_party_ajax_csv_import' ), 1, 1 );	// Woo Product Importer 		https://github.com/dgrundel/woo-product-importer
		add_action( 'wp_ajax_woocommerce_csv_import_request', 	array( &$this, 'handle_third_party_ajax_csv_import' ), 1, 1 );	// Product CSV Import Suite 	http://www.woothemes.com/products/product-csv-import-suite/
		add_action( 'wp_ajax_runImport',      					array( &$this, 'handle_third_party_ajax_csv_import' ), 1, 1 );	// WooCommerce CSV importer 	http://wordpress.org/plugins/woocommerce-csvimport/
		// add_action( 'load-all-import_page_pmxi-admin-import', array( &$this, 'handle_third_party_ajax_csv_import' ), 1, 1 );	// WP All Import				
		add_action( 'pmxi_saved_post', 							array( &$this, 'wplister_product_has_changed' ), 20, 1 ); 		// WP All Import				http://www.wpallimport.com/documentation/advanced/action-reference/

		// trigger 3rd party import mode if called from custom cron implementation
		// example: /wp-content/plugins/wwc-amz-aff/do-cron.php for WooCommerce Amazon Affiliates plugin
		// deactivated as it seems to cause problems with wwc-amz-aff
		// if ( 'do-cron.php' == basename( $_SERVER['SCRIPT_NAME'] ) )
		// 	$this->handle_third_party_ajax_csv_import();

		// example of using wplister_custom_attributes filter to add SKU as a virtual attribute
		add_filter( 'wplister_custom_attributes', array( &$this, 'wplister_custom_attributes' ), 10, 1 );

	}
	
	
	// revise inventory status for given product_id or array of product_ids 
	function wplister_revise_inventory_status( $products ) {

		// call EbayController
		$this->initEC();
		$results = $this->EC->reviseInventoryForProducts( $products );
		$this->EC->closeEbay();

		return isset( $this->EC->lastResults ) ? $this->EC->lastResults : false;

	}

	// revise ebay item for given product_id 
	function wplister_revise_item( $post_id ) {

		// call markItemAsModified() to re-apply the listing profile
		$lm = new ListingsModel();
		$lm->markItemAsModified( $post_id );

		$listing_id = $lm->getListingIDFromPostID( $post_id );
		$this->logger->info('revising listing '.$listing_id );

		// call EbayController
		$this->initEC();
		$results = $this->EC->reviseItems( $listing_id );
		$this->EC->closeEbay();

		$this->logger->info('revised listing '.$listing_id );
		return isset( $this->EC->lastResults ) ? $this->EC->lastResults : false;

	}

	// relist ebay item for given product_id 
	function wplister_relist_item( $post_id ) {

		// call markItemAsModified() to re-apply the listing profile
		$lm = new ListingsModel();
		$lm->markItemAsModified( $post_id );

		$listing_id = $lm->getListingIDFromPostID( $post_id );
		$this->logger->info('relisting item '.$listing_id );

		// call EbayController
		$this->initEC();
		$results = $this->EC->relistItems( $listing_id );
		$this->EC->closeEbay();

		$this->logger->info('relisted item '.$listing_id );
		return isset( $this->EC->lastResults ) ? $this->EC->lastResults : false;

	}

	// re-apply profile and mark listing item as changed
	function wplister_product_has_changed( $post_id ) {

		$lm = new ListingsModel();
		$listing_id = $lm->markItemAsModified( $post_id );

		// handle locked items
		$listing = $lm->getItem( $listing_id );
		if ( $listing['locked'] ) 
			do_action( 'wplister_revise_inventory_status', $post_id );

	}

	// process inventory changes from amazon
	function wpla_inventory_status_changed( $post_id ) {
		// $this->wplister_revise_inventory_status( $post_id );
		do_action( 'wplister_revise_inventory_status', $post_id );
	}



	/**
	 *  support for Woo Product Importer plugin
	 *  https://github.com/dgrundel/woo-product-importer
	 *  
	 *  support for WooCommerce Product CSV Import Suite
	 *  http://www.woothemes.com/products/product-csv-import-suite/
	 *
	 *  Third party CSV import plugins usually call wp_update_post() before update_post_meta() so WP will trigger the save_post action before price and stock have been updated.
	 *  We need to disable the original save_post hook and collect post IDs to mark them as modified at shutdown (including further processing for locked items)
	 */

	function handle_third_party_ajax_csv_import() {
		global $wpl_logger;
		$wpl_logger->info("CSV import mode ENABLED");

		// disable default action for save_post
		global $WPL_WooBackendIntegration;
		remove_action( 'save_post', array( &$WPL_WooBackendIntegration, 'wplister_on_woocommerce_product_quick_edit_save' ), 10, 2 );

		// add new save_post action to collect changed post IDs
		add_action( 'save_post', array( &$this, 'collect_updated_products' ), 10, 2 );

		// add shutdown handler
		register_shutdown_function( array( &$this, 'update_products_on_shutdown' ) );

	}

	// collect changed product IDs
	function collect_updated_products( $post_id, $post ) {
		// global $wpl_logger;
		// $wpl_logger->info("collect_updated_products( $post_id )");

		if ( !$_POST ) return $post_id;
		// if ( is_int( wp_is_post_revision( $post_id ) ) ) return;
		// if( is_int( wp_is_post_autosave( $post_id ) ) ) return;
		// if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) return $post_id;
		if ( ! current_user_can( 'edit_post', $post_id )) return $post_id;
		if ( ! in_array( $post->post_type, array( 'product', 'product_variation' ) ) ) return $post_id;

		// if this is a single variation use parent_id 
		// if ( $parent_id = ProductWrapper::getVariationParent( $post_id ) ) {
		if ( $post->post_type == 'product_variation' ) {
			$parent_id = ProductWrapper::getVariationParent( $post_id );
			// $wpl_logger->info("single variation found - use parent $parent_id for $post_id");
			$post_id = $parent_id;
		}

		// get queue
		$collected_products = get_option( 'wplister_updated_products_queue', array() );
		if ( ! is_array( $collected_products ) ) $collected_products = array();

		// add product_id to queue - if it doesn't exist
		if ( ! in_array( $post_id, $collected_products ) )
			$collected_products[] = $post_id;

		// $wpl_logger->info("collected products".print_r($collected_products,1));

		// update queue
		update_option( 'wplister_updated_products_queue', $collected_products );
	}

	function update_products_on_shutdown() {

		// get queue
		$collected_products = get_option( 'wplister_updated_products_queue', array() );
		if ( ! is_array( $collected_products ) ) $collected_products = array();

		// DEBUG
		// global $wpl_logger;
		// $wpl_logger->info("update_products_on_shutdown() - collected_products: ".print_r($collected_products,1));

		// mark each queued product as modified
		foreach ($collected_products as $post_id ) {
			do_action( 'wplister_product_has_changed', $post_id );
		}

		// clear queue
		delete_option( 'wplister_updated_products_queue' );

	}

	// example of using wplister_custom_attributes filter to add SKU as a virtual attribute 
	function wplister_custom_attributes( $attributes ) {

		$attributes[] = array(
			'label'    => 'SKU',
			'id'       => '_sku',
			'meta_key' => '_sku'
		);

		return $attributes;
	}	

}

// global $wplister_api;
$wplister_api = new WPL_API_Hooks();
