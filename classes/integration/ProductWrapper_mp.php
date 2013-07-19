<?php
/**
 * wrapper functions to access products on WooCommerce
 */

class ProductWrapper {
	
	const plugin = 'mp';
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
		$prices = get_post_meta( $post_id, 'mp_price', true);
		return $prices[0];
	}	
	
	// set product price
	static function setPrice( $post_id, $price ) {
	}	

	// get product sku
	static function getSKU( $post_id ) {
		$meta_array = get_post_meta( $post_id, 'mp_sku', true);
		return $meta_array[0];
	}	
	
	// set product sku
	static function setSKU( $post_id, $sku ) {
	}	

	// get product stock (deprecated)
	static function getStock( $post_id ) {
		$meta_array = get_post_meta( $post_id, 'mp_inventory', true);
		return $meta_array[0];
	}	
	
	// set product stock (deprecated)
	static function setStock( $post_id, $stock ) {
		$meta_array = array( 0 => $stock );
		return update_post_meta( $post_id, 'mp_inventory', $meta_array);
	}	


	
	// get product weight
	static function getWeight( $post_id, $include_weight_unit = false ) {
		$meta_array = get_post_meta( $post_id, 'mp_shipping', true);
		return $meta_array['weight'];
	}	
	
	// get product weight as major weight and minor
	static function getEbayWeight( $post_id ) {
		$weight_value = self::getWeight( $post_id );
		$weight_major = $weight_value;
		$weight_minor = 0;
		return array( $weight_major, $weight_minor );
	}	

	// get name of main product category
	static function getProductCategoryName( $post_id ) {
		return '';
	}	
	
	// get product dimensions array
	static function getDimensions( $post_id ) {
		$dimensions = array();
		return $dimensions;
	}	
	
	// get product featured image
	static function getImageURL( $post_id ) {

		// this seems to be neccessary for listing previews on some installations 
		if ( ! function_exists('get_post_thumbnail_id')) 
		require_once( ABSPATH . 'wp-includes/post-thumbnail-template.php');

		$large_image_url = wp_get_attachment_image_src( get_post_thumbnail_id( $post_id ), 'large');
		return $large_image_url[0];
	}	
	
	// get all product addons
	static function getAddons( $post_id ) {
		return array();
	}	

	// get all product attributes
	static function getAttributes( $post_id ) {

		$attributes = array();
		return $attributes;
		// Array
		// (
		//     [Platform] => Nintendo DS
		//     [Genre] => Puzzle
		// )
	}	
	
	// check if product has variations
	static function hasVariations( $post_id ) {
		
		$meta_array = get_post_meta( $post_id, 'mp_var_name', true);
		if ( $meta_array[0] != '' ) return true;
		return false;

	}	

	// get all product variations
	static function getVariations( $post_id ) {

		$variations = array();

		$var_names = get_post_meta( $post_id, 'mp_var_name', true);
		$var_prices = get_post_meta( $post_id, 'mp_price', true);
		$mp_inventory = get_post_meta( $post_id, 'mp_inventory', true);
		$mp_sku = get_post_meta( $post_id, 'mp_sku', true);


		foreach ($var_names as $var_id => $var_name) {
			
			$var = array();
			$var['post_id']              = $post_id;
			$var['variation_attributes'] = array( 'Variation' => $var_name );
			$var['price']                = $var_prices[ $var_id ];
			$var['stock']                = $mp_inventory[ $var_id ];
			$var['sku']                  = $mp_sku[ $var_id ];
			$var['weight']               = self::getWeight( $post_id );
			$var['dimensions']           = self::getDimensions( $post_id );
			$var['image']                = self::getImageURL( $post_id );

			list( $weight_major, $weight_minor ) = self::getEbayWeight( $post_id );
			$var['weight_major']     = $weight_major;
			$var['weight_minor']     = $weight_minor;

			$variations[] = $var;

		}

		return $variations;

		// echo "<pre>";print_r($variations);die()echo"</pre>";
		/* the returned array looks like this:
		    
		    [0] => Array
		        (
		            [post_id] => 1126
					[variation_attributes] => Array
	                (
	                    [Size] => large
	                    [Colour] => yellow
	                )
		            [price] => 
		            [stock] => 
		            [weight] => 
		            [sku] => 
		            [image] => http://www.example.com/wp-content/uploads/2011/09/days-end.jpg
		        )

		*/		

	}	

	// get a list of all available attribute names
	static function getAttributeTaxonomies() {
		$attributes = array();
		return $attributes;
	}	

	// check if current page is products list page
	static function isProductsPage() {
		global $pagenow;

		if ( ( isset( $_GET['post_type'] ) ) &&
		     ( $_GET['post_type'] == self::getPostType() ) &&
			 ( $pagenow == 'edit.php' ) ) {
			return true;
		}
		return false;
	}	
	
	// check if product is single variation (Woo)
	static function isSingleVariation( $post_id ) {
        return false;
	}	
	
	
}




// testing area...


/**
 * Columns for Products page
 **/
add_filter('manage_edit-product_columns', 'wpl_marketpress_edit_product_columns', 11 );

function wpl_marketpress_edit_product_columns($columns){
	
	$columns['listed'] = '<img src="'.WPLISTER_URL.'/img/hammer-dark-16x16.png" title="'.__('Listing status', 'wplister').'" />';		
	return $columns;
}


/**
 * Custom Columns for Products page
 **/
add_action('manage_product_posts_custom_column', 'wpl_marketpress_custom_product_columns', 3 );

function wpl_marketpress_custom_product_columns( $column ) {
	global $post;
	// $product = new WC_Product($post->ID);

	switch ($column) {
		case "listed" :
			$listingsModel = new ListingsModel();
			$status = $listingsModel->getStatusFromPostID( $post->ID );
			if ( ! $status ) break;

			switch ($status) {
				case 'published':
				case 'changed':
					$ebayUrl = $listingsModel->getViewItemURLFromPostID( $post->ID );
					echo '<a href="'.$ebayUrl.'" title="View on eBay" target="_blank"><img src="'.WPLISTER_URL.'/img/ebay-16x16.png" alt="yes" /></a>';
					break;
				
				case 'prepared':
					echo '<img src="'.WPLISTER_URL.'/img/hammer-orange-16x16.png" title="prepared" />';
					break;
				
				case 'verified':
					echo '<img src="'.WPLISTER_URL.'/img/hammer-green-16x16.png" title="prepared" />';
					break;
				
				case 'ended':
					echo '<img src="'.WPLISTER_URL.'/img/hammer-16x16.png" title="ended" />';
					break;
				
				default:
					echo '<img src="'.WPLISTER_URL.'/img/hammer-16x16.png" alt="yes" />';
					break;
			}

		break;

	} // switch ($column)

}


