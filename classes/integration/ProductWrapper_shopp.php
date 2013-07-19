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
	// get product categories taxonomy
	static function getTaxonomy() {
		return self::taxonomy;
	}	
	
	// get product price
	static function getPrice( $post_id ) {

		$Product = new Product($post_id);
		$Product->load_data(array('prices'));

		if ( "on" == $Product->variants ) {

			// find lowest variation price
            $price_min = 1000000; // one million should be a high enough ceiling
            foreach ($Product->prices as $priceObj) {
            	if ( $priceObj->context == 'variation') {
	                if ( $priceObj->price < $price_min ) $price_min = $priceObj->price;
            	}
            }
			// global $wpl_logger;
			// $wpl_logger->info('getPrice() minimum price for variation: '.print_r($price_min,1));
            return $price_min;

		} else {
			return $Product->prices[0]->price;			
		}

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
	
	// get all product images (Shopp)
	static function getAllImages( $post_id ) {

		$product = new Product( $post_id );
		$product->load_data();

		$images = array();
		foreach ($product->images as $id => $image) {
		 	$images[] = site_url() . '/?siid='.$image->id;
		} 

		return $images;
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

	// get all product addons (shopp only)
	static function getAddons( $post_id ) {
		global $wpl_logger;
		$addons = array();
		$wpl_logger->info('getAddons() for post_id '.print_r($post_id,1));

		// check if addons are enabled
		$Product = new Product($post_id);
		$Product->load_data(array('summary'));
		if ( "off" == $Product->addons ) return array();

		// get available addons for prices
		$available_addons = shopp_product_addons( $post_id );
		$wpl_logger->info('addons (v2):'.print_r($available_addons,1));

		// get product options
		$meta = shopp_product_meta($post_id, 'options');
		$a = $meta['a'];
		$wpl_logger->info('a:'.print_r($a,1));

		// build clean options array
		$options = array();
		foreach ( $a as $menus ) {
			$addonGroup = new stdClass();
			$addonGroup->name = $menus['name'];
			$addonGroup->options = array();
			foreach ( $menus['options'] as $option ) {
				$addonObj = new stdClass();
				$addonObj->id = $option['id'];
				$addonObj->name = $option['name'];

				// get addon price /*
				// $wpl_logger->info('fetching addon price for option: '.print_r($option['id'],1));
				// $priceObj = shopp_product_addon( $option['id'] );
				// $wpl_logger->info('fetching addon price for product : '.$post_id.' - addon name: '.$option['name']);
				// $priceObj = shopp_product_addon( array( 'product' => $post_id, 'option' => array( 'addonmenu' => $option['name'] ) ) );
				// $wpl_logger->info('priceObj:'.print_r($priceObj,1));
				// $addonObj->price = $priceObj->price;

				// get addon price (v2)
				foreach ($available_addons as $av_addon) {
					if ( $av_addon->label == $option['name'] ) {
						$addonObj->price = $av_addon->price;						
					}
				}
				
				$addonGroup->options[] = $addonObj;
			}
			$options[] = $addonGroup;
		}
		$wpl_logger->info('addons:'.print_r($options,1));

		return $options;
	}	

	// get all product variations
	static function getVariations( $post_id ) {

		global $wpl_logger;
		$variations = array();
		$wpl_logger->info('getVariations() for post_id '.print_r($post_id,1));

		// get available variations 
		$variants = shopp_product_variants( $post_id );
		$available_variations = $variants;
		$wpl_logger->debug('variants:'.print_r($variants,1));


		// get product options
		$meta = shopp_product_meta($post_id, 'options');
		$v = $meta['v'];
		$wpl_logger->debug('v:'.print_r($v,1));

		// build clean options array (v2)
		// indexed by options id
		$new_options = array();
		foreach ( $v as $menus ) {
			foreach ( $menus['options'] as $option ) {
				$new_options[$option['id']] = array();
				$new_options[$option['id']]['name']  = $menus['name'];
				$new_options[$option['id']]['value'] = $option['name'];
			}
		}
		$wpl_logger->debug('new_options:'.print_r($new_options,1));

		// build clean options array (deprecated)
		/*
		$options = array();
		foreach ( $v as $menus ) {
			$options[$menus['name']] = array();
			foreach ( $menus['options'] as $option ) {
				$options[$menus['name']][] = $option['name'];
			}
		}
		$wpl_logger->debug('options:'.print_r($options,1));
		*/
	
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
		// $available_variations = array();
		// $first_loop = true;
		// $tmpArray = array();

		// loop attributes (v1)
		/*
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
			$wpl_logger->debug('loop:'.print_r($available_variations,1));
			$first_loop = false;
		}
		if ( ! empty($tmpArray) ) $available_variations = $tmpArray;
		
		$wpl_logger->debug('available_variations:'.print_r($available_variations,1));
		*/
	
		// Shopp doesn't use images for variations
		$product_image = self::getImageURL( $post_id );

		// loop variations
		foreach ($available_variations as $var) {
			
			// fetch price data for this variation
			// $priceObj = shopp_product_variant( array( 'product' => $post_id, 'option' => $var ) );
			$priceObj = $var;
			// $wpl_logger->info('priceObj:'.print_r($priceObj,1));

			// build variation array for wp-lister
			$newvar = array();
			$newvar['post_id'] = $post_id;
			$newvar['variation_attributes'] = array();
	
			// parse and assign options
			$options = explode(',', $var->options);
			foreach ($options as $option_id) {
				$option_name  = $new_options[ $option_id ]['name'];
				$option_value = $new_options[ $option_id ]['value'];
				$newvar['variation_attributes'][ $option_name ] = $option_value;		
			}
			
			$newvar['price']      = $priceObj->promoprice;
			$newvar['stock']      = $priceObj->stock;
			$newvar['weight']     = $priceObj->dimensions['weight'];
			$newvar['dimensions'] = $priceObj->dimensions;
			$newvar['sku']        = $priceObj->sku;
			$newvar['image']      = $product_image;

			// TODO: check weight unit
			$newvar['weight_major']     = $priceObj->dimensions['weight'];;
			$newvar['weight_minor']     = 0;

			// add to collection
			$variations[] = $newvar;			
			
		}
		// $wpl_logger->debug('variations:'.print_r($variations,1));
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

	// check if product is single variation (Woo)
	static function isSingleVariation( $post_id ) {
        return false;
	}	
	
	
}

