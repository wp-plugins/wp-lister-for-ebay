<?php
/**
 * wrapper functions to access products on WooCommerce
 */

class ProductWrapper {
	
	const plugin = 'woo';
	const post_type = 'product';
	const taxonomy  = 'product_cat';
	const menu_page_position = '57.26';
	
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
		$sale_price = get_post_meta( $post_id, '_sale_price', true);
		if ( floatval($sale_price) > 0 ) return $sale_price;
		return get_post_meta( $post_id, '_price', true);
	}	
	static function getOriginalPrice( $post_id ) {
		return get_post_meta( $post_id, '_regular_price', true);
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

		$weight = get_post_meta( $post_id, '_weight', true);

		// check parent if variation has no weight
		if ( $weight == '' ) {
			$parent_id = self::getVariationParent( $post_id );
			if ( $parent_id ) $weight = self::getWeight( $parent_id );
		}

		return $weight;
	}	

	// get product weight as major weight and minor
	static function getEbayWeight( $post_id ) {
		$weight_value = self::getWeight( $post_id );
		$weight_unit  = get_option( 'woocommerce_weight_unit' );

		// convert value to major and minor if unit is gram or ounces
		if ( 'g' == $weight_unit ) {
			$kg = intval( $weight_value / 1000 );
			$g = $weight_value - $kg * 1000 ;
			$weight_major = $kg;
			$weight_minor = $g;
		} elseif ( 'kg' == $weight_unit ) {
			$kg = intval( $weight_value );
			$g = ($weight_value - $kg) * 1000 ;
			$weight_major = $kg;
			$weight_minor = $g;
		} elseif ( 'lbs' == $weight_unit ) {
			$lbs = intval( $weight_value );
			$oz = ($weight_value - $lbs) * 16 ;
			$weight_major = $lbs;
			$weight_minor = $oz;
		} elseif ( 'oz' == $weight_unit ) {
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
		if ( ! $terms || ! is_array($terms) ) return '';
		$category_name = $terms[0]->name;
		return $category_name;
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

		// check parent if variation has no dimensions
		if ( ($dimensions['length'] == '') && ($dimensions['width'] == '') ) {
			$parent_id = self::getVariationParent( $post_id );
			if ( $parent_id ) $dimensions = self::getDimensions( $parent_id );
		}

		return $dimensions;
	}	
	
	// get product featured image
	static function getImageURL( $post_id ) {

		// this seems to be neccessary for listing previews on some installations 
		if ( ! function_exists('get_post_thumbnail_id')) 
		require_once( ABSPATH . 'wp-includes/post-thumbnail-template.php');

		// fetch images using default size
		$size = get_option( 'wplister_default_image_size', 'full' );
		$large_image_url = wp_get_attachment_image_src( get_post_thumbnail_id( $post_id ), $size );
		return $large_image_url[0];
	}	
	
	// get all product attributes
	static function getAttributes( $post_id ) {
		global $woocommerce;
		$attributes = array();

		$product = self::getProduct( $post_id );
		if ( ! $product ) return array();
		
		$attribute_taxnomies = $product->get_attributes();
		
		global $wpl_logger;
		$wpl_logger->debug('attribute_taxnomies: '.print_r($attribute_taxnomies,1));

		foreach ($attribute_taxnomies as $attribute) {

			if ( $attribute['is_taxonomy'] ) {

				// handle taxonomy attributes
				$terms = wp_get_post_terms( $post_id, $attribute['name'] );
				$wpl_logger->debug('terms: '.print_r($terms,1));
				if ( is_wp_error($terms) ) {
					// echo "post id: $post_id <br>";
					// echo "attribute name: " . $attribute['name']."<br>";
					// echo "attribute: " . print_r( $attribute )."<br>";
					// echo "error: " . $terms->get_error_message();
					continue;
				}
				if ( count( $terms ) > 0 ) {
					$attribute_name = $woocommerce->attribute_label( $attribute['name'] );
					$attribute_name = html_entity_decode( $attribute_name, ENT_QUOTES, 'UTF-8' ); // US Shoe Size (Men&#039;s) => US Shoe Size (Men's)
					$attributes[ $attribute_name ] = $terms[0]->name;
				}
	
			} else {

				// handle custom product attributes
				$attribute_name = $attribute['name'];
				$attribute_name = html_entity_decode( $attribute_name, ENT_QUOTES, 'UTF-8' ); // US Shoe Size (Men&#039;s) => US Shoe Size (Men's)
				$attributes[ $attribute_name ] = $attribute['value'];

			}

		}

		return $attributes;
		// Array
		// (
		//     [Platform] => Nintendo DS
		//     [Genre] => Puzzle
		// )
	}	
	
	// check if product is a single variation (that has been split)
	// static function isVariation( $post_id ) {

	// 	$product = self::getProduct( $post_id );
	// 	if ( $product->product_type == 'variation' ) return true;

	// 	return false;

	// }	
	// get parent post_id for a single variation
	// static function getParentID( $post_id ) {

	// 	$product = self::getProduct( $post_id );
	// 	if ( $product->product_type == 'variation' ) return $product->post->parent_id;

	// 	return false;

	// }	

	// check if product has variations
	static function hasVariations( $post_id ) {

		$product = self::getProduct( $post_id );
		if ( $product && $product->product_type == 'variable' ) return true;

		// $variations = $product->get_available_variations();
		// if ( ! is_array($variations) ) return false;
		// if ( 0 == count($variations) ) return false;

		return false;

	}	

	// get all product addons (requires Product Add-Ons extension)
	static function getAddons( $post_id ) {
		global $wpl_logger;
		$addons = array();
		// $wpl_logger->info('getAddons() for post_id '.print_r($post_id,1));

		// check if addons are enabled
		$product_addons = get_post_meta( $post_id, '_product_addons', true );
		if ( ! is_array($product_addons) ) return array();
		if ( 0 == sizeof($product_addons) ) return array();

		// get available addons for prices
		// $available_addons = shopp_product_addons( $post_id );
		// $meta = shopp_product_meta($post_id, 'options');
		// $a = $meta['a'];
		// $wpl_logger->info('a:'.print_r($a,1));

		// build clean options array
		$options = array();
		foreach ( $product_addons as $product_addon ) {
			$addonGroup = new stdClass();
			$addonGroup->name    = $product_addon['name'];
			$addonGroup->options = array();

			foreach ( $product_addon['options'] as $option ) {
				$addonObj = new stdClass();
				$addonObj->id    = sanitize_key( $option['label'] );
				$addonObj->name  = $option['label'];
				$addonObj->price = $option['price'];				

				$addonGroup->options[] = $addonObj;
			}
			$options[] = $addonGroup;
		}
		$wpl_logger->info('addons:'.print_r($options,1));

		return $options;
	}	

	// get all product variations
	static function getVariations( $post_id ) {
		global $woocommerce;
		global $product; // make $product globally available for some badly coded themes...		

		$product = self::getProduct( $post_id );
		if ( $product->product_type != 'variable' ) return array();

		// force all variations to show, regardless if woocommerce_hide_out_of_stock_items is yes or no
		// by forcing visibility to true
		add_filter( 'woocommerce_product_is_visible', array( 'ProductWrapper', 'returnTrue' ), 999, 2 );

		$available_variations  = $product->get_available_variations();
		$variation_attributes  = $product->get_variation_attributes();
		$default_attributes    = $product->get_variation_default_attributes();
		$has_default_variation = false;

		// remove filter again
		remove_filter( 'woocommerce_product_is_visible', array( 'ProductWrapper', 'returnTrue' ), 999, 2 );

		// echo "<pre>";print_r($default_attributes);echo"</pre>";
		// echo "<pre>";print_r($available_variations);die();echo"</pre>";
		// echo "<pre>";print_r($variation_attributes);die();echo"</pre>";
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
		foreach ( $variation_attributes as $name => $options ) {

			$label = $woocommerce->attribute_label($name); 
			if ($label == '') $label = $name;
			$label = html_entity_decode( $label, ENT_QUOTES, 'UTF-8' ); // US Shoe Size (Men&#039;s) => US Shoe Size (Men's)
			
			$id   = "attribute_".sanitize_title($name);
			$attribute_labels[ $id ] = $label;

		} // foreach $variation_attributes

		// print_r($attribute_labels);die();
		// (
		//     [attribute_pa_size] => Size
		//     [attribute_pa_colour] => Colour
		// )		

		// loop variations
		$variations = array();
		foreach ($available_variations as $var) {
			
			// find child post_id for this variation
			$var_id = $var['variation_id'];

			// build variation array for wp-lister
			$newvar = array();
			$newvar['post_id'] = $var_id;
			// $newvar['term_id'] = $var->term_id;
			
			$attributes = $var['attributes'];
			$newvar['variation_attributes'] = array();
			$attributes_without_values = array();
			foreach ($attributes as $key => $value) {	// this loop will only run once for one dimensional variations
				// $newvar['name'] = $value; #deprecated
				// v2
				$taxonomy = str_replace('attribute_', '', $key); // attribute_pa_color -> pa_color
				$term = get_term_by('slug', $value, $taxonomy );
				// echo "<pre>";print_r($key);echo"</pre>";#die();
				// echo "<pre>";print_r($term);echo"</pre>";#die();
				if ( $term ) {
					// handle proper attribute taxonomies
					$term_name = html_entity_decode( $term->name, ENT_QUOTES, 'UTF-8' ); // US Shoe Size (Men&#039;s) => US Shoe Size (Men's)
					$newvar['variation_attributes'][ @$attribute_labels[ $key ] ] = $term_name;
					$value = $term->slug;
				} elseif ( $value ) {
					// handle fake custom product attributes
					$newvar['variation_attributes'][ @$attribute_labels[ $key ] ] = $value;
					// echo "no term found for $value<br>";
				} else {
					// handle product attributes without value ("all Colors")
					$newvar['variation_attributes'][ @$attribute_labels[ $key ] ] = '_ALL_';
					$attributes_without_values[] = $key;
					// echo "no value found for $key<br>";
				}

				// check for default variation
				if ( isset( $default_attributes[ $taxonomy ] ) && $default_attributes[ $taxonomy ] == $value ) {
					$newvar['is_default']  = true;
					$has_default_variation = true;
				} else {
					$newvar['is_default']  = false;
				}

			}
			// $newvar['group_name'] = $attribute_labels[ $key ]; #deprecated
			
			$newvar['price']      = self::getPrice( $var_id );
			$newvar['stock']      = self::getStock( $var_id );
			$newvar['sku']        = self::getSKU( $var_id );
			$newvar['weight']     = self::getWeight( $var_id );
			$newvar['dimensions'] = self::getDimensions( $var_id );

			// check parent if variation has no dimensions
			// if ( ($newvar['dimensions']['length'] == 0) && ($newvar['dimensions']['width'] == 0) ) {
			// 	$newvar['dimensions'] = self::getDimensions( $post_id );
			// }

			// ebay weight
			list( $weight_major, $weight_minor ) = self::getEbayWeight( $var_id );
			$newvar['weight_major']     = $weight_major;
			$newvar['weight_minor']     = $weight_minor;

			$var_image 		  = self::getImageURL( $var_id );
			$newvar['image']  = ($var_image == '') ? self::getImageURL( $post_id ) : $var_image;

			// do we have some attributes without values that need post-processing?
			if ( sizeof($attributes_without_values) > 0 ) {

				// echo "<pre>";print_r($attributes_without_values);echo"</pre>";die();
				foreach ($attributes_without_values as $key) {	

					// v2
					$taxonomy = str_replace('attribute_', '', $key); // attribute_pa_color -> pa_color

					$all_values = $variation_attributes[ $taxonomy ];
					// echo "<pre>all values for $taxonomy: ";print_r($all_values);echo"</pre>";#die();

					// create a new variation for each value
					if ( is_array( $all_values ) )
					foreach ($all_values as $value) {
						$term = get_term_by('slug', $value, $taxonomy );
						// echo "<pre>";print_r($term);echo"</pre>";#die();
	
						if ( $term ) {
							// handle proper attribute taxonomies
							$term_name = html_entity_decode( $term->name, ENT_QUOTES, 'UTF-8' ); // US Shoe Size (Men&#039;s) => US Shoe Size (Men's)
							$newvar['variation_attributes'][ @$attribute_labels[ $key ] ] = $term_name;
							$variations[] = $newvar;			
						}

					}

				}

			} else {

				// add single variation to collection
				$variations[] = $newvar;			
				// echo "<pre>";print_r($newvar);echo"</pre>";die();

			}

			// if no default variation was found, make the first on default
			if ( ! $has_default_variation && sizeof($variations) ) {
				$variations[0]['is_default'] = true;
			}
			
		}

        // global $wpl_logger;
        // $wpl_logger->info( 'getVariations() result: '.print_r($variations,1));

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
		            [price] => 
		            [stock] => 
		            [weight] => 
		            [sku] => 
		            [is_default] => true
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
		            [is_default] => false
		            [image] => http://www.example.com/wp-content/uploads/2011/09/days-end.jpg
		        )

		*/		

	}	

	static function returnTrue( $param1, $param2 = false ) {
		return true;
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

			// US Shoe Size (Men&#039;s) => US Shoe Size (Men's)
			$attrib->name  = html_entity_decode( $woocommerce->attribute_label( $tax ), ENT_QUOTES, 'UTF-8' );
			$attrib->label = html_entity_decode( $woocommerce->attribute_label( $tax ), ENT_QUOTES, 'UTF-8' );

			$attributes[]  = $attrib;
		}
		// print_r($attributes);die();

        // global $wpl_logger;
        // $wpl_logger->info( 'getAttributeTaxonomies() result: '.print_r($attributes,1));

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

	// check if product is single variation
	static function isSingleVariation( $post_id ) {
        return self::getVariationParent( $post_id ) ? true : false;
	}	
	

	/*
	 * private functions (WooCommerce only)
	 */

	// check if product is single variation (used for split variations) (private)
	static function getVariationParent( $post_id ) {

        if ( ! $post_id ) return false;
        $post = get_post( $post_id );

        if ( empty( $post->post_parent ) || $post->post_parent == $post->ID )
                return false;

        return $post->post_parent;
	}	
	
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
	
	// get WooCommerce product object (private)
	static function getProduct( $post_id, $is_variation = false ) {

		// use get_product() on WC 2.0+
		if ( function_exists('get_product') ) {
			return get_product( $post_id );
		} else {
			// instantiate WC_Product on WC 1.x
			return $is_variation ? new WC_Product_Variation( $post_id ) : new WC_Product( $post_id );
		}

	}	
	
	
	
}


