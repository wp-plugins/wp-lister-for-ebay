<?php
/**
 * wrapper functions to access products on JigoShop
 */

class ProductWrapper {
	
	const plugin = 'jigo';
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
		$sale_price = get_post_meta( $post_id, 'sale_price', true);
		if ( floatval($sale_price) > 0 ) return $sale_price;
		$regular_price = get_post_meta( $post_id, 'regular_price', true);
		if ( floatval($regular_price) > 0 ) return $regular_price;
		return get_post_meta( $post_id, 'price', true);
	}	
	
	// set product price
	static function setPrice( $post_id, $price ) {
		update_post_meta( $post_id, 'price', $price);
		update_post_meta( $post_id, 'regular_price', $price);
	}	

	// get product sku
	static function getSKU( $post_id ) {
		return get_post_meta( $post_id, 'sku', true);
	}	
	
	// set product sku
	static function setSKU( $post_id, $sku ) {
		return update_post_meta( $post_id, 'sku', $sku);
	}	

	// get product stock (deprecated)
	static function getStock( $post_id ) {
		return get_post_meta( $post_id, 'stock', true);
	}	
	
	// set product stock (deprecated)
	static function setStock( $post_id, $stock ) {
		return update_post_meta( $post_id, 'stock', $stock);
	}	

	
	// get product weight
	static function getWeight( $post_id, $include_weight_unit = false ) {
		return get_post_meta( $post_id, 'weight', true);
	}	

	// get product weight as major weight and minor
	static function getEbayWeight( $post_id ) {
		$weight_value = self::getWeight( $post_id );
		$weigth_unit  = Jigoshop_Base::get_options()->get_option('jigoshop_weight_unit');

		// convert value to major and minor if unit is gram or ounces
		if ( 'g' == $weigth_unit ) {
			$kg = intval( $weight_value / 1000 );
			$g = $weight_value - $kg * 1000 ;
			$weight_major = $kg;
			$weight_minor = $g;
		} elseif ( 'oz' == $weigth_unit ) {
			$lbs = intval( $weight_value / 16 );
			$oz = $weight_value - $lbs * 16 ;
			$weight_major = $lbs;
			$weight_minor = $oz;
		} else {
			$weight_major = $weight_value;
			$weight_minor = 0;
		}
		return array( $weight_major, $weight_minor );
	}	

	// get name of main product category
	static function getProductCategoryName( $post_id ) {
		$terms = get_the_terms($post_id, "product_cat");
		$category_name = $terms[0]->name;
		return $category_name;
	}	
	
	// get product dimensions array
	static function getDimensions( $post_id ) {
		$dimensions = array();
		$unit = Jigoshop_Base::get_options()->get_option('jigoshop_dimension_unit');
		$dimensions['length'] = get_post_meta( $post_id, 'length', true);
		$dimensions['height'] = get_post_meta( $post_id, 'height', true);
		$dimensions['width']  = get_post_meta( $post_id, 'width',  true);
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
		$attributes = array();

		$product = new jigoshop_product( $post_id );
		$attribute_taxnomies = $product->get_attributes();
		
		global $wpl_logger;
		$wpl_logger->info('attribute_taxnomies: '.print_r($attribute_taxnomies,1));

		foreach ($attribute_taxnomies as $attribute) {
			$terms = wp_get_post_terms( $post_id, $attribute['name'] );
			$wpl_logger->info('terms: '.print_r($terms,1));
			if ( is_wp_error($terms) ) {
				// echo "post id: $post_id <br>";
				// echo "attribute name: " . $attribute['name']."<br>";
				// echo "attribute: " . print_r( $attribute )."<br>";
				// echo "error: " . $terms->get_error_message();
				continue;
			}
			if ( count( $terms ) > 0 ) {
				$attribute_name = jigoshop_product::attribute_label( $attribute['name'] );
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
	
	// get all product addons
	static function getAddons( $post_id ) {
		return array();
	}	

	// check if product has variations
	static function hasVariations( $post_id ) {
		
		$product = new jigoshop_product( $post_id );
		return $product->is_type('variable');

	}	

	// get all product variations
	static function getVariations( $post_id ) {

		$product = new jigoshop_product( $post_id );
		$children = $product->get_children();

		// echo "<pre>";print_r($product);echo"</pre>";die();

		// $available_variations = $product->get_available_variations();
		$attributes = $product->get_attributes();

		// echo "<pre>";print_r($available_variations);die();echo"</pre>";
		// echo "<pre>";print_r($attributes);echo"</pre>";
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
		$attribute_labels = array();
		foreach ( $attributes as $name => $item ) {

			// $label = jigoshop_product::attribute_label($name); 
			$label = $item['name']; 
			// echo "label for $name: $label <br>";
			if ($label == '') $label = $name;
			$id   = "tax_".sanitize_title($name);
			$attribute_labels[ $id ] = $label;

		} // foreach $attributes

		// print_r($attribute_labels);die();
		// (
		//     [attribute_pa_size] => Size
		//     [attribute_pa_colour] => Colour
		// )		

		// loop variations
		foreach ($children as $var_id) {
			
			// find child post_id for this variation (woo)
			// $var_id = $var['variation_id'];
			
			// get variation (jigo)
			$var = $product->get_child( $var_id );
			// echo "<pre>";print_r($var);echo"</pre>";

			// build variation array for wp-lister
			$newvar = array();
			$newvar['post_id'] = $var_id;
			// $newvar['term_id'] = $var->term_id;
			
			$attributes = $var->get_variation_attributes();
			$newvar['variation_attributes'] = array();
			foreach ($attributes as $key => $value) {	// this loop will only run once for one dimensional variations
				// $newvar['name'] = $value; #deprecated
				// v2
				$taxonomy = str_replace('tax_', '', $key); // tax_color -> color
				$term = get_term_by('slug', $value, $taxonomy );
				if ( $term ) {
					// handle proper attribute taxonomies (woo only)
					$newvar['variation_attributes'][ @$attribute_labels[ $key ] ] = $term->name;
				} else {
					// handle fake custom product attributes (jigo for now)
					$newvar['variation_attributes'][ @$attribute_labels[ $key ] ] = $value;
					// echo "no term found for $value<br>";
				}
			}
			// $newvar['group_name'] = $attribute_labels[ $key ]; #deprecated
			
			$newvar['price']      = self::getPrice( $var_id );
			$newvar['stock']      = self::getStock( $var_id );
			$newvar['weight']     = self::getWeight( $var_id );
			$newvar['dimensions'] = self::getDimensions( $var_id );
			$newvar['sku']        = self::getSKU( $var_id );

			list( $weight_major, $weight_minor ) = self::getEbayWeight( $var_id );
			if ( ($weight_major == 0) && ($weight_minor == 0) ) {
				list( $weight_major, $weight_minor ) = self::getEbayWeight( $post_id );
			}
			$newvar['weight_major']     = $weight_major;
			$newvar['weight_minor']     = $weight_minor;

			$var_image 		  = self::getImageURL( $var_id );
			$newvar['image']  = ($var_image == '') ? self::getImageURL( $post_id ) : $var_image;

			// add to collection
			$variations[] = $newvar;			
			
		}

		// echo "<hr><pre>";print_r($variations);echo"</pre>";
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

		$attribute_taxonomies = jigoshop_product::getAttributeTaxonomies();
		// print_r($attribute_taxonomies);
		
		$attributes = array();
		foreach ($attribute_taxonomies as $tax) {
			$attrib = new stdClass();
			// $attrib->name = jigoshop_product::attribute_label( $tax );
			// $attrib->label = jigoshop_product::attribute_label( $tax );
			$attrib->name = $tax->attribute_name;
			$attrib->label = $tax->attribute_label;
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

	// check if product is single variation (Woo)
	static function isSingleVariation( $post_id ) {
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
add_filter('manage_edit-product_columns', 'wpl_jigoshop_edit_product_columns', 11 );

function wpl_jigoshop_edit_product_columns($columns){
	
	$columns['listed'] = '<img src="'.WPLISTER_URL.'/img/hammer-dark-16x16.png" title="'.__('Listing status', 'wplister').'" />';		
	return $columns;
}


/**
 * Custom Columns for Products page
 **/
add_action('manage_product_posts_custom_column', 'wplister_jigoshop_custom_product_columns', 3 );

function wplister_jigoshop_custom_product_columns( $column ) {
	global $post;
	// $product = new jigoshop_product($post->ID);

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
					echo '<img src="'.WPLISTER_URL.'/img/hammer-green-16x16.png" title="verified" />';
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


// hook into save_post to mark listing as changed when a product is updated
function wplister_on_jigoshop_product_quick_edit_save( $post_id, $post ) {

	if ( !$_POST ) return $post_id;
	if ( is_int( wp_is_post_revision( $post_id ) ) ) return;
	if ( is_int( wp_is_post_autosave( $post_id ) ) ) return;
	if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) return $post_id;
	// if ( !isset($_POST['jigoshop_quick_edit_nonce']) || (isset($_POST['jigoshop_quick_edit_nonce']) && !wp_verify_nonce( $_POST['jigoshop_quick_edit_nonce'], 'jigoshop_quick_edit_nonce' ))) return $post_id;
	if ( !current_user_can( 'edit_post', $post_id )) return $post_id;
	if ( $post->post_type != 'product' ) return $post_id;

	$lm = new ListingsModel();
	$lm->markItemAsModified( $post_id );

}

add_action( 'save_post', 'wplister_on_jigoshop_product_quick_edit_save', 10, 2 );


