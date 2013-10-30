<?php
/**
 * WPL_API_Hooks
 *
 * implements public action hooks for 3rd party developers
 */

class WPL_API_Hooks extends WPL_Core {
	
	public function __construct() {
		parent::__construct();

		// revise inventory status on eBay
		add_action( 'wplister_revise_inventory_status', array( &$this, 'wplister_revise_inventory_status' ), 10, 1 );

		// revise item on eBay
		add_action( 'wplister_revise_item', array( &$this, 'wplister_revise_item' ), 10, 1 );

		// re-apply profile and mark listing item as changed
		add_action( 'wplister_product_has_changed', array( &$this, 'wplister_product_has_changed' ), 10, 1 );

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

	// re-apply profile and mark listing item as changed
	function wplister_product_has_changed( $post_id ) {

		$lm = new ListingsModel();
		$lm->markItemAsModified( $post_id );

	}


}

// global $wplister_api;
$wplister_api = new WPL_API_Hooks();
