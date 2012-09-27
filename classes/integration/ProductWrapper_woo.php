<?php
/**
 * wrapper functions to access products on WooCommerce
 */

class ProductWrapper {
	
	const plugin = 'woo';
	const post_type = 'product';
	const taxonomy  = 'product_cat';
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
		return get_post_meta( $post_id, '_price', true);
	}	
	
	// set product price
	static function setPrice( $post_id, $price ) {
		update_post_meta( $post_id, '_price', $price);
		update_post_meta( $post_id, '_regular_price', $price);
	}	

	// get product sku
	static function getSKU( $post_id ) {
		return get_post_meta( $post_id, '_sku', true);
	}	
	
	// set product sku
	static function setSKU( $post_id, $sku ) {
		return update_post_meta( $post_id, '_sku', $sku);
	}	

	// get product stock (deprecated)
	static function getStock( $post_id ) {
		return get_post_meta( $post_id, '_stock', true);
	}	
	
	// set product stock (deprecated)
	static function setStock( $post_id, $stock ) {
		return update_post_meta( $post_id, '_stock', $stock);
	}	

	
	// get product weight
	static function getWeight( $post_id, $include_weight_unit = false ) {
		return get_post_meta( $post_id, '_weight', true);
	}	

	// get product dimensions array
	static function getDimensions( $post_id ) {
		$dimensions = array();
		$unit = get_option( 'woocommerce_dimension_unit' );
		$dimensions['length'] = get_post_meta( $post_id, '_length', true);
		$dimensions['height'] = get_post_meta( $post_id, '_height', true);
		$dimensions['width']  = get_post_meta( $post_id, '_width',  true);
		$dimensions['length_unit'] = $unit;
		$dimensions['height_unit'] = $unit;
		$dimensions['width_unit']  = $unit;
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
	
	// get all product attributes
	static function getAttributes( $post_id ) {
		global $woocommerce;
		$attributes = array();

		$product = new WC_Product( $post_id );
		$attribute_taxnomies = $product->get_attributes();

		foreach ($attribute_taxnomies as $attribute) {
			$terms = wp_get_post_terms( $post_id, $attribute['name'] );
			if ( is_wp_error($terms) ) {
				// echo "post id: $post_id <br>";
				// echo "attribute name: " . $attribute['name']."<br>";
				// echo "attribute: " . print_r( $attribute )."<br>";
				// echo "error: " . $terms->get_error_message();
				continue;
			}
			if ( count( $terms ) > 0 ) {
				$attribute_name = $woocommerce->attribute_label( $attribute['name'] );
				$attributes[ $attribute_name ] = $terms[0]->name;
			}
		}

		return $attributes;
		// Array
		// (
		//     [Platform] => Nintendo DS
		//     [Genre] => Puzzle
		// )
	}	
	
	// check if product has variations
	static function hasVariations( $post_id ) {
		
		$product = new WC_Product( $post_id );
		$variations = $product->get_available_variations();

		if ( ! is_array($variations) ) return false;
		if ( 0 == count($variations) ) return false;
		return true;

	}	

	// get all product variations
	static function getVariations( $post_id ) {
		global $woocommerce;

		$product = new WC_Product( $post_id );
		$available_variations = $product->get_available_variations();
		$attributes = $product->get_variation_attributes();

		// echo "<pre>";print_r($available_variations);die();echo"</pre>";
		// echo "<pre>";print_r($attributes);die();echo"</pre>";
		// (
		//     [pa_size] => Array
		//         (
		//             [0] => x-large
		//             [1] => large
		//             [2] => medium
		//             [3] => small
		//         )

		//     [pa_colour] => Array
		//         (
		//             [0] => yellow
		//             [1] => orange
		//         )

		// ) 

		// build array of attribute labels
		$attributes_labels = array();
		foreach ( $attributes as $name => $options ) {

			$label = $woocommerce->attribute_label($name); 
			if ($label == '') $label = $name;
			$id   = "attribute_".sanitize_title($name);
			$attribute_labels[ $id ] = $label;

		} // foreach $attributes

		// print_r($attribute_labels);die();
		// (
		//     [attribute_pa_size] => Size
		//     [attribute_pa_colour] => Colour
		// )		

		// loop variations
		foreach ($available_variations as $var) {
			
			// find child post_id for this variation
			$var_id = $var['variation_id'];

			// build variation array for wp-lister
			$newvar = array();
			$newvar['post_id'] = $var_id;
			// $newvar['term_id'] = $var->term_id;
			
			$attributes = $var['attributes'];
			$newvar['variation_attributes'] = array();
			foreach ($attributes as $key => $value) {	// this loop will only run once for one dimensional variations
				// $newvar['name'] = $value; #deprecated
				// v2
				$taxonomy = str_replace('attribute_', '', $key); // attribute_pa_color -> pa_color
				$term = get_term_by('slug', $value, $taxonomy );
				$newvar['variation_attributes'][ $attribute_labels[ $key ] ] = $term->name;
			}
			// $newvar['group_name'] = $attribute_labels[ $key ]; #deprecated
			
			$newvar['price']      = self::getPrice( $var_id );
			$newvar['stock']      = self::getStock( $var_id );
			$newvar['weight']     = self::getWeight( $var_id );
			$newvar['dimensions'] = self::getDimensions( $var_id );
			$newvar['sku']        = self::getSKU( $var_id );

			$var_image 		  = self::getImageURL( $var_id );
			$newvar['image']  = ($var_image == '') ? self::getImageURL( $post_id ) : $var_image;

			// add to collection
			$variations[] = $newvar;			
			
		}

		return $variations;

		// echo "<pre>";print_r($variations);die();echo"</pre>";
		/* the returned array looks like this:
		    
		    [0] => Array
		        (
		            [post_id] => 1126
					[variation_attributes] => Array
	                (
	                    [Size] => large
	                    [Colour] => yellow
	                )
		            [name] => yellow
		            [group_name] => Colour
		            [price] => 
		            [stock] => 
		            [weight] => 
		            [sku] => 
		            [image] => http://www.example.com/wp-content/uploads/2011/09/days-end.jpg
		        )

		    [1] => Array
		        (
		            [post_id] => 1253
					[variation_attributes] => Array
	                (
	                    [Size] => large
	                    [Colour] => orange
	                )
		            [name] => orange
		            [group_name] => Colour
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
		global $woocommerce;

		// $attribute_taxonomies = $woocommerce->get_attribute_taxonomies();
		$attribute_taxonomies = $woocommerce->get_attribute_taxonomy_names();
		// print_r($attribute_taxonomies);
		
		$attributes = array();
		foreach ($attribute_taxonomies as $tax) {
			$attrib = new stdClass();
			$attrib->name = $woocommerce->attribute_label( $tax );
			$attrib->label = $woocommerce->attribute_label( $tax );
			$attributes[] = $attrib;
		}
		// print_r($attributes);die();

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


	/*
	 * private functions (WooCommerce only)
	 */

	// find variation by attributes (private)
	static function findVariationID( $parent_id, $VariationSpecifics ) {
		global $wpl_logger;
		$variations = self::getVariations( $parent_id );
		foreach ($variations as $var) {
			$diffs = array_diff_assoc( $var['variation_attributes'], $VariationSpecifics );
			if ( count($diffs) == 0 ) {
				$wpl_logger->info('findVariationID('.$parent_id.') found: '.$var['post_id']);
				$wpl_logger->info('VariationSpecifics: '.print_r($VariationSpecifics,1));
				return $var['post_id'];
			}
		}
		return false;
	}	
	
	
	
}




// testing area...


/**
 * Columns for Products page
 **/
add_filter('manage_edit-product_columns', 'wpl_woocommerce_edit_product_columns', 11 );

function wpl_woocommerce_edit_product_columns($columns){
	
	$columns['listed'] = '<img src="'.WPLISTER_URL.'/img/hammer-dark-16x16.png" title="'.__('Listing status', 'wplister').'" />';		
	return $columns;
}


/**
 * Custom Columns for Products page
 **/
add_action('manage_product_posts_custom_column', 'wpl_woocommerce_custom_product_columns', 3 );

function wpl_woocommerce_custom_product_columns( $column ) {
	global $post, $woocommerce;
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


