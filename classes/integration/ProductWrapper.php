<?php
/**
 * wrapper functions to access products
 * This version is actually a dummy class which will be used to prevent errors if no shop plugin was found.
 */

class ProductWrapper {
	
	const plugin = 'none';
	const post_type = 'product';
	const taxonomy  = 'product_category';
	const menu_page_position = 57;
	
	// get custom post type
	static function getPostType() {
		return self::post_type;
	}	
	// get product catrgories taxonomy
	static function getTaxonomy() {
		return self::taxonomy;
	}	
	
	// get product price
	static function getPrice( $post_id ) {
	}	
	
	// set product price
	static function setPrice( $post_id, $price ) {
	}	

	// get product sku
	static function getSKU( $post_id ) {
	}	
	
	// set product sku
	static function setSKU( $post_id, $price ) {
	}	

	// get product stock
	static function getStock( $post_id ) {
	}	
	
	// set product stock
	static function setStock( $post_id, $stock ) {
	}	
	// decrease product stock
	static function decreaseStockBy( $post_id, $by, $VariationSpecifics = array(), $transaction_id = false ) {
	}	
	// increase product stock
	static function increaseStockBy( $post_id, $by, $VariationSpecifics = array() ) {
	}	
	
	// get product weight
	static function getWeight( $post_id, $include_weight_unit = false ) {
	}	

	// get product weight as major weight and minor
	static function getEbayWeight( $post_id ) {
		$weight_value = self::getWeight( $post_id );
		$weight_major = $weight_value;
		$weight_minor = 0;
		return array( $weight_major, $weight_minor );
	}	

	// get product dimensions array
	static function getDimensions( $post_id ) {
		return array();
	}	
	
	// get name of main product category
	static function getProductCategoryName( $post_id ) {
	}	
	
	// get product featured image
	static function getImageURL( $post_id ) {
	}	
	
	// get all product attributes
	static function getAttributes( $post_id ) {
		return array();
	}	

	// get all product addons
	static function getAddons( $post_id ) {
		return array();
	}	

	// check if product has variations
	static function hasVariations( $post_id ) {
		return false;
	}	

	// get all product variations
	static function getVariations( $post_id ) {
	}	

	// get a list of all available attribute names
	static function getAttributeTaxonomies() {
	}	

	// check if current page is products list page
	static function isProductsPage() {
		return false;
	}	
	
	
}

