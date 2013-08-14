<?php
/**
 * wrapper functions to access products on WP e-commerce
 */

class ProductWrapper {
	
	const plugin = 'wpec';
	const post_type = 'wpsc-product';
	const taxonomy  = 'wpsc_product_category';
	const menu_page_position = '27.42';
	
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
		$sale_price = get_post_meta( $post_id, '_wpsc_special_price', true);
		if ( floatval($sale_price) > 0 ) return $sale_price;
		return get_post_meta( $post_id, '_wpsc_price', true);
	}	
	
	// set product price
	static function setPrice( $post_id, $price ) {
		return update_post_meta( $post_id, '_wpsc_price', $price);
	}	

	// get product sku
	static function getSKU( $post_id ) {
		return get_post_meta( $post_id, '_wpsc_sku', true);
	}	
	
	// set product sku
	static function setSKU( $post_id, $sku ) {
		return update_post_meta( $post_id, '_wpsc_sku', $sku);
	}	

	// get product stock (private)
	static function getStock( $post_id ) {
		return get_post_meta( $post_id, '_wpsc_stock', true);
	}	
	
	// set product stock (private)
	static function setStock( $post_id, $stock ) {
		return update_post_meta( $post_id, '_wpsc_stock', $stock);
	}	

	// get product weight
	static function getWeight( $post_id, $include_weight_unit = false ) {
		$metadata = get_post_meta( $post_id, '_wpsc_product_metadata', true);

		$weight      = $metadata['weight'];
		$weight_unit = $metadata['weight_unit'];
		$weight      = wpsc_convert_weight( $weight, 'pound', $weight_unit ); // wpsc always stores weight in lbs

		if ( $include_weight_unit ) {
			// $weight = number_format_i18n( floatval( $metadata['weight'] ), 2 );
			return $weight . ' ' . $weight_unit;
		} 
		return $weight;
	}	

	// get product weight unit (wpsc only)
	static function getWeightUnit( $post_id ) {
		$metadata = get_post_meta( $post_id, '_wpsc_product_metadata', true);
		return $metadata['weight_unit'];
	}	

	// get product weight as major weight and minor
	static function getEbayWeight( $post_id ) {
		$weight_value = self::getWeight( $post_id );
		$weight_unit  = self::getWeightUnit( $post_id );

		// convert value to major and minor if unit is gram or ounces
		if ( 'gram' == $weight_unit ) {
			$kg = intval( $weight_value / 1000 );
			$g = $weight_value - $kg * 1000 ;
			$weight_major = $kg;
			$weight_minor = $g;
		} elseif ( 'kilogram' == $weight_unit ) {
			$kg = intval( $weight_value );
			$g = ($weight_value - $kg) * 1000 ;
			$weight_major = $kg;
			$weight_minor = $g;
		} elseif ( 'pound' == $weight_unit ) {
			$lbs = intval( $weight_value );
			$oz = ($weight_value - $lbs) * 16 ;
			$weight_major = $lbs;
			$weight_minor = $oz;
		} elseif ( 'ounce' == $weight_unit ) {
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
		return '';
	}	
	
	// get product dimensions array
	static function getDimensions( $post_id ) {
		$metadata = get_post_meta( $post_id, '_wpsc_product_metadata', true);
		return $metadata['dimensions'];
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
		global $wpdb;

		$sql = "
		SELECT DISTINCT wp_postmeta.meta_key, wp_postmeta.meta_value
		FROM   wp_posts
		       LEFT JOIN wp_postmeta
		              ON wp_posts.ID = wp_postmeta.`post_id`
		WHERE  wp_posts.`post_type` = 'wpsc-product'
		   AND NOT Substring(wp_postmeta.meta_key, 1, 1) = '_'
		   AND wp_posts.ID = '".$post_id."'
		";

		$attribute_taxonomies = $wpdb->get_results( $sql );
		// echo"<pre>";print_r($attribute_taxonomies);
		
		$attributes = array();
		foreach ($attribute_taxonomies as $tax) {
			$attributes[ $tax->meta_key ] = $tax->meta_value;
		}
		// print_r($attributes);die();

		return $attributes;
	}	

	// get all product addons
	static function getAddons( $post_id ) {
		return array();
	}	

	// check if product has variations
	static function hasVariations( $post_id ) {
		return wpsc_product_has_children( $post_id );
	}	
	
	// find attribute name by variation_group_id
	static function findVariationGroupName( $variation_group_id, $variation_groups ) {
		
		foreach ($variation_groups as $variation_group) {
		 	if ( $variation_group->term_id == $variation_group_id ) {
				return $variation_group->name;
			}
		} 
		return false;
	}	
	
	// get all product variations
	static function getVariations( $parent_id ) {

		$variations = array();
		$wpsc_variations = new wpsc_variations( $parent_id );

		// first step - get all post_ids and assign attributes and values
		// loop variation groups
		foreach ($wpsc_variations->all_associated_variations as $variation_group_id => $variations_array) {

			$variation_group_name = self::findVariationGroupName( $variation_group_id, $wpsc_variations->variation_groups );

			// loop variations
			foreach ($variations_array as $var) {
				
				if ( $var->term_id == 0) continue;

				// find child post_id for this variation
				$post_ids = wpsc_get_child_object_in_terms_var( $parent_id, $var->term_id, 'wpsc-variation');
				// print_r($post_ids);
				// loop all returnes post_ids
				foreach ($post_ids as $object_id) {
					$post_id = $object_id['object_id'];
					// echo "post id: $post_id ";
					$variations[ $post_id ]['post_id'] = $post_id;

					// if ( isset( $var->term_group )) {
					// }
					$attribute_value = $var->name;
					$attribute_label = $variation_group_name;
					@$variations[ $post_id ]['variation_attributes'][ $attribute_label ] = $attribute_value;
					// @$variations[ $post_id ]['term_id'] = $var->term_id;
					// @$variations[ $post_id ]['term_group'] = $var->term_group;
					// @$variations[ $post_id ]['variation_group_id'][] = $variation_group_id;
				}
								
			}
			
		}


		// second step - loop all found product variations and fill in additional adata
		foreach ($variations as &$var) {
			
			$post_id = $var['post_id'];			
			$var['price']  = self::getPrice( $post_id );
			$var['stock']  = self::getStock( $post_id );
			$var['weight'] = self::getWeight( $post_id );
			$var['sku'] 	  = self::getSKU( $post_id );
			$var['image']  = self::getImageURL( $post_id );

			list( $weight_major, $weight_minor ) = self::getEbayWeight( $post_id );
			$var['weight_major']     = $weight_major;
			$var['weight_minor']     = $weight_minor;
			
		}

		return $variations;
	}	


		// print_r($variations);die();
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

		    [1] => Array
		        (
		            [post_id] => 1253
					[variation_attributes] => Array
	                (
	                    [Size] => large
	                    [Colour] => orange
	                )
		            [price] => 
		            [stock] => 
		            [weight] => 
		            [sku] => 
		            [image] => http://www.example.com/wp-content/uploads/2011/09/days-end.jpg
		        )

		*/		

	// get a list of all available attribute names
	static function getAttributeTaxonomies() {
		global $wpdb;

		$sql = "
		SELECT DISTINCT wp_postmeta.meta_key
		FROM   wp_posts
		       LEFT JOIN wp_postmeta
		              ON wp_posts.ID = wp_postmeta.`post_id`
		WHERE  wp_posts.`post_type` = 'wpsc-product'
		   AND NOT Substring(wp_postmeta.meta_key, 1, 1) = '_'
		";

		$attribute_taxonomies = $wpdb->get_col( $sql );
		// print_r($attribute_taxonomies);
		
		$attributes = array();
		foreach ($attribute_taxonomies as $tax) {
			$attrib = new stdClass();
			$attrib->name = $tax;
			$attrib->label = $tax;
			$attributes[] = $attrib;
		}
		// print_r($attributes);die();

		return $attributes;
	}	
	
	// check if current page is products list page
	static function isProductsPage() {
		global $pagenow;

		if ( ( $_GET['post_type'] == self::getPostType() ) &&
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
	 * private functions (WP e-Commerce only)
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


// hook into save_post to mark listing as changed when a product is updated
function wplister_on_wpec_product_quick_edit_save( $post_id, $post ) {

	if ( !$_POST ) return $post_id;
	if ( is_int( wp_is_post_revision( $post_id ) ) ) return;
	if ( is_int( wp_is_post_autosave( $post_id ) ) ) return;
	if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) return $post_id;
	// if ( !isset($_POST['wpec_quick_edit_nonce']) || (isset($_POST['wpec_quick_edit_nonce']) && !wp_verify_nonce( $_POST['wpec_quick_edit_nonce'], 'wpec_quick_edit_nonce' ))) return $post_id;
	if ( !current_user_can( 'edit_post', $post_id )) return $post_id;
	if ( $post->post_type != 'wpsc-product' ) return $post_id;

	$lm = new ListingsModel();
	$lm->markItemAsModified( $post_id );

}

add_action( 'save_post', 'wplister_on_wpec_product_quick_edit_save', 10, 2 );





// testing area...


/**
 * Columns for Products page
 **/
add_filter('manage_edit-wpsc-product_columns', 'wpl_wpec_edit_product_columns', 11 );
add_filter('manage_wpsc-product_posts_columns', 'wpl_wpec_edit_product_columns', 11 );

function wpl_wpec_edit_product_columns($columns){
	$columns['listed'] = '<img src="'.WPLISTER_URL.'/img/hammer-dark-16x16.png" title="'.__('Listing status', 'wplister').'" />';		
	return $columns;
}


/**
 * Custom Columns for Products page
 **/
add_action('manage_pages_custom_column', 'wpl_wpec_custom_product_columns', 3 );

function wpl_wpec_custom_product_columns( $column ) {
	global $post;

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


