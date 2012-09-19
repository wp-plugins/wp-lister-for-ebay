<?php
/**
 * wrapper functions to access products on Shopp
 */

class ProductWrapper {
	
	const plugin = 'shopp';
	const post_type = 'shopp_product';
	const taxonomy  = 'shopp_category';
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
		global $wpdb;
		$item = $wpdb->get_var("
			SELECT price
			FROM {$wpdb->prefix}shopp_price
			WHERE product = '$post_id'
		");
		return $item;	
	}	
	
	// set product price
	static function setPrice( $post_id, $price ) {
	}	

	// get product sku
	static function getSKU( $post_id ) {
		global $wpdb;
		$item = $wpdb->get_var("
			SELECT sku
			FROM {$wpdb->prefix}shopp_price
			WHERE product = '$post_id'
		");
		return $item;	
	}	
	
	// set product sku
	static function setSKU( $post_id, $sku ) {
	}	

	// get product stock
	static function getStock( $post_id ) {
		global $wpdb;
		$item = $wpdb->get_var("
			SELECT stock
			FROM {$wpdb->prefix}shopp_price
			WHERE product = '$post_id'
		");
		return $item;	
	}	
	
	// set product stock
	static function setStock( $post_id, $stock ) {
	}	

	
	// get product weight
	static function getWeight( $post_id, $include_weight_unit = false ) {
		$Product = new Product($post_id);
		$Product->load_data(array('prices'));
		// echo "<pre></pre>";
		// print_r($Product);
		// die();
		return $Product->prices[0]->dimensions['weight'];
	}	

	// get product dimensions array
	static function getDimensions( $post_id ) {
		$Product = new Product($post_id);
		$Product->load_data(array('prices'));
		return $Product->prices[0]->dimensions;
	}	
	
	// get product featured image
	static function getImageURL( $post_id ) {

		$product = new Product( $post_id );
		$product->load_data();
		$first_image_id = key( $product->images );
		$first_image_url = site_url() . '/?siid='.$first_image_id;
		// echo $first_image_url;

		return $first_image_url;
	}	
	
	// get all product attributes
	static function getAttributes( $post_id ) {
		// TODO
		$attributes = array();
		return $attributes;
	}	

	// check if product has variations
	static function hasVariations( $post_id ) {
	
		$Product = new Product($post_id,'id');
		$Product->load_data(array('summary'));

		if ( "on" == $Product->variants ) return true;
		return false;
	}	

	// get all product variations
	static function getVariations( $post_id ) {
		global $wpl_logger;
		$variations = array();
		$wpl_logger->info('getVariations() for post_id '.print_r($post_id,1));

		// get product options
		$meta = shopp_product_meta($post_id, 'options');
		$v = $meta['v'];

		// build clean options array
		$options = array();
		foreach ( $v as $menus ) {
			$options[$menus['name']] = array();
			foreach ( $menus['options'] as $option ) {
				$options[$menus['name']][] = $option['name'];
			}
		}
		$wpl_logger->info('options:'.print_r($options,1));

		// $options: 
		// Array (
		//     [Size] => Array
		//             [0] => L
		//             [1] => XL
		//     [Color] => Array
		//             [0] => black
		//             [1] => white
		// )

		 
		// build available_variations array
		$available_variations = array();
		$first_loop = true;
		$tmpArray = array();

		// loop attributes
		foreach ($options as $option_name => $option_values) {
			
			// loop attribute values
			foreach ($option_values as $value) {
	
				if ( $first_loop ) {
					// init empty array
					$available_variations[] = array( $option_name => $value );
				} else {
					// loop available_variations
					foreach ($available_variations as $var) {
						$new_attribute = array( $option_name => $value );
						$tmpArray[] = array_merge( $var, $new_attribute );
					}
					// $available_variations = $tmpArray;
				}
			}
			$first_loop = false;
		}
		$available_variations = $tmpArray;
		$wpl_logger->info('available_variations:'.print_r($available_variations,1));

		// Shopp doesn't use images for variations
		$product_image = self::getImageURL( $post_id );

		// loop variations
		foreach ($available_variations as $var) {
			
			// fetch price data for this variation
			$priceObj = shopp_product_variant( array( 'product' => $post_id, 'option' => $var ) );
			// $wpl_logger->info('priceObj:'.print_r($priceObj,1));

			// build variation array for wp-lister
			$newvar = array();
			$newvar['post_id'] = $post_id;
			$newvar['variation_attributes'] = $var;
			// $newvar['group_name'] = $attribute_labels[ $key ];
			
			$newvar['price']      = $priceObj->promoprice;
			$newvar['stock']      = $priceObj->stock;
			$newvar['weight']     = $priceObj->dimensions['weight'];
			$newvar['dimensions'] = $priceObj->dimensions;
			$newvar['sku']        = $priceObj->sku;
			$newvar['image']      = $product_image;

			// add to collection
			$variations[] = $newvar;			
			
		}
		return $variations;

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

		$attributes = array();
		return $attributes;

	}	


	// check if current page is products list page
	static function isProductsPage() {
		global $pagenow;

		if ( ( $_GET['page'] == 'shopp-products' ) &&
			 ( $pagenow == 'admin.php' ) ) {
			return true;
		}
		return false;
	}	

	
}

