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
		if ( ! $terms ) return '';
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

		$large_image_url = wp_get_attachment_image_src( get_post_thumbnail_id( $post_id ), 'large');
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
		
		$product = self::getProduct( $post_id );
		if ( $product->product_type == 'variable' ) return true;

		// $variations = $product->get_available_variations();
		// if ( ! is_array($variations) ) return false;
		// if ( 0 == count($variations) ) return false;

		return false;

	}	

	// get all product addons (requires Product Add-Ons extension)
	static function getAddons( $post_id ) {
		global $wpl_logger;
		$addons = array();
		$wpl_logger->info('getAddons() for post_id '.print_r($post_id,1));

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

		$product = self::getProduct( $post_id );
		$available_variations = $product->get_available_variations();
		$variation_attributes = $product->get_variation_attributes();

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
					$newvar['variation_attributes'][ @$attribute_labels[ $key ] ] = $term->name;
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
					foreach ($all_values as $value) {
						$term = get_term_by('slug', $value, $taxonomy );
						// echo "<pre>";print_r($term);echo"</pre>";#die();
	
						if ( $term ) {
							// handle proper attribute taxonomies
							$newvar['variation_attributes'][ @$attribute_labels[ $key ] ] = $term->name;
							$variations[] = $newvar;			
						}

					}

				}

			} else {

				// add single variation to collection
				$variations[] = $newvar;			
				// echo "<pre>";print_r($newvar);echo"</pre>";die();

			}

			
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





class WpLister_Product_MetaBox {

	function __construct() {

		add_action( 'add_meta_boxes', array( &$this, 'add_meta_box' ) );
		add_action( 'woocommerce_process_product_meta', array( &$this, 'save_meta_box' ), 0, 2 );

	}

	function add_meta_box() {

		$title = __('eBay', 'wplister');
		// add_meta_box( 'wplister-ebay-details', $title, array( &$this, 'meta_box' ), 'product', 'normal', 'core');
		add_meta_box( 'wplister-ebay-details', $title, array( &$this, 'meta_box' ), 'product', 'normal', 'default');
	}

	function meta_box() {
		global $woocommerce, $post;

        ?>
        <style type="text/css">
            #wplister-ebay-details label { 
            	float: left;
            	width:25%;
            	line-height: 2em;
            }
            #wplister-ebay-details input { 
            	width:74%; 
            }
            #wplister-ebay-details .description { 
            	clear: both;
            	margin-left: 25%;
            }
        </style>
        <?php

		woocommerce_wp_text_input( array(
			'id' 			=> 'wpl_ebay_title',
			'label' 		=> __('Listing title', 'wplister'),
			'placeholder' 	=> 'Custom listing title',
			'description' 	=> '',
			'value'			=> get_post_meta( $post->ID, '_ebay_title', true )
		) );

		woocommerce_wp_text_input( array(
			'id' 			=> 'wpl_ebay_subtitle',
			'label' 		=> __('Listing subtitle', 'wplister'),
			'placeholder' 	=> 'Custom listing subtitle',
			'description' 	=> __('Leave empty to use the product excerpt. Will be cut after 55 characters.','wplister'),
			'value'			=> get_post_meta( $post->ID, '_ebay_subtitle', true )
		) );

		woocommerce_wp_text_input( array(
			'id' 			=> 'wpl_ebay_condition_description',
			'label' 		=> __('Condition description', 'wplister'),
			'placeholder' 	=> 'Condition description',
			'description' 	=> __('This field should only be used to further clarify the condition of used items.','wplister'),
			'value'			=> get_post_meta( $post->ID, '_ebay_condition_description', true )
		) );

		woocommerce_wp_select( array(
			'id' 			=> 'wpl_ebay_auction_type',
			'label' 		=> __('Listing Type', 'wplister'),
			'options' 		=> array( 
					''               => __('-- use profile setting --', 'wplister'),
					'Chinese'        => __('Auction', 'wplister'),
					'FixedPriceItem' => __('Fixed Price', 'wplister')
				),
			'value'			=> get_post_meta( $post->ID, '_ebay_auction_type', true )
		) );

		woocommerce_wp_text_input( array(
			'id' 			=> 'wpl_ebay_start_price',
			'label' 		=> __('Price / Start Price', 'wplister'),
			'placeholder' 	=> 'Start Price',
			'value'			=> get_post_meta( $post->ID, '_ebay_start_price', true )
		) );


		// woocommerce_wp_select( array(
		// 	'id' 			=> 'wpl_ebay_condition_id',
		// 	'label' 		=> __('Listing Type', 'wplister'),
		// 	'options' 		=> array( 
		// 			''               => __('-- use profile setting --', 'wplister'),
		// 			'Chinese'        => __('Auction', 'wplister'),
		// 			'FixedPriceItem' => __('Fixed Price', 'wplister')
		// 		),
		// 	'value'			=> get_post_meta( $post->ID, '_ebay_condition_id', true )
		// ) );

		/*
		?>

			<label for="wpl-text-condition_id" class="text_label"><?php echo __('Condition','wplister'); ?>: *</label>
			<select id="wpl-text-condition_id" name="wpl_e2e_condition_id" title="Condition" class=" required-entry select">
			<?php if ( isset( $wpl_available_conditions ) && is_array( $wpl_available_conditions ) ): ?>
				<?php foreach ($wpl_available_conditions as $condition_id => $desc) : ?>
					<option value="" selected="selected"><?php echo __('none','wplister'); ?></option>
					<option value="<?php echo $condition_id ?>" 
						<?php if ( $item_details['condition_id'] == $condition_id ) : ?>
							selected="selected"
						<?php endif; ?>
						><?php echo $desc ?></option>
				<?php endforeach; ?>
			<?php else: ?>
				<option value="" selected="selected"><?php echo __('-- use profile setting --','wplister'); ?></option>
				<option value="1000" selected="selected"><?php echo __('New','wplister'); ?></option>
			<?php endif; ?>
			</select>
			<br class="clear" />

		<?php
		*/

		// woocommerce_wp_checkbox( array( 'id' => 'wpl_update_ebay_on_save', 'wrapper_class' => 'update_ebay', 'label' => __('Update on save?', 'wplister') ) );
	
	}

	function save_meta_box( $post_id, $post ) {

		if ( isset( $_POST['wpl_ebay_title'] ) ) {

			// get field values
			$wpl_ebay_title                 = esc_attr( @$_POST['wpl_ebay_title'] );
			$wpl_ebay_subtitle              = esc_attr( @$_POST['wpl_ebay_subtitle'] );
			$wpl_ebay_global_shipping       = esc_attr( @$_POST['wpl_ebay_global_shipping'] );
			$wpl_ebay_payment_instructions  = esc_attr( @$_POST['wpl_ebay_payment_instructions'] );
			$wpl_ebay_condition_description = esc_attr( @$_POST['wpl_ebay_condition_description'] );
			$wpl_ebay_auction_type          = esc_attr( @$_POST['wpl_ebay_auction_type'] );
			$wpl_ebay_start_price           = esc_attr( @$_POST['wpl_ebay_start_price'] );
			$wpl_ebay_reserve_price         = esc_attr( @$_POST['wpl_ebay_reserve_price'] );
			$wpl_ebay_buynow_price          = esc_attr( @$_POST['wpl_ebay_buynow_price'] );

			// Update order data
			update_post_meta( $post_id, '_ebay_title', $wpl_ebay_title );
			update_post_meta( $post_id, '_ebay_subtitle', $wpl_ebay_subtitle );
			update_post_meta( $post_id, '_ebay_global_shipping', $wpl_ebay_global_shipping );
			update_post_meta( $post_id, '_ebay_payment_instructions', $wpl_ebay_payment_instructions );
			update_post_meta( $post_id, '_ebay_condition_description', $wpl_ebay_condition_description );
			update_post_meta( $post_id, '_ebay_auction_type', $wpl_ebay_auction_type );
			update_post_meta( $post_id, '_ebay_start_price', $wpl_ebay_start_price );
			update_post_meta( $post_id, '_ebay_reserve_price', $wpl_ebay_reserve_price );
			update_post_meta( $post_id, '_ebay_buynow_price', $wpl_ebay_buynow_price );

		}

	} // save_meta_box()

} // class WpLister_Product_MetaBox
$WpLister_Product_MetaBox = new WpLister_Product_MetaBox();








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
add_action('manage_product_posts_custom_column', 'wplister_woocommerce_custom_product_columns', 3 );

function wplister_woocommerce_custom_product_columns( $column ) {
	global $post, $woocommerce;
	// $product = self::getProduct($post->ID);

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
function wplister_on_woocommerce_product_quick_edit_save( $post_id, $post ) {

	if ( !$_POST ) return $post_id;
	if ( is_int( wp_is_post_revision( $post_id ) ) ) return;
	if( is_int( wp_is_post_autosave( $post_id ) ) ) return;
	if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) return $post_id;
	// if ( !isset($_POST['woocommerce_quick_edit_nonce']) || (isset($_POST['woocommerce_quick_edit_nonce']) && !wp_verify_nonce( $_POST['woocommerce_quick_edit_nonce'], 'woocommerce_quick_edit_nonce' ))) return $post_id;
	if ( !current_user_can( 'edit_post', $post_id )) return $post_id;
	if ( $post->post_type != 'product' ) return $post_id;

	// global $woocommerce, $wpdb;
	// $product = self::getProduct( $post_id );

	$lm = new ListingsModel();
	$lm->markItemAsModified( $post_id );

	// Clear transient
	// $woocommerce->clear_product_transients( $post_id );
}

add_action( 'save_post', 'wplister_on_woocommerce_product_quick_edit_save', 10, 2 );




/*
add_action( 'pre_get_posts', 'wplister_pre_get_posts' ); //hook into the query before it is executed

function wplister_pre_get_posts( $query )
{
    global $custom_where_string;
	$custom_where_string = ''; //used to save the generated where string between filter functions

    //if the custom parameter is used
    // if(isset($query->query_vars['_spec'])){
    if(isset( $_GET['is_on_ebay'] )){

        //here you can parse the contents of $query->query_vars['_spec'] to modify the query
        //even the first WHERE starts with AND, because WP adds a "WHERE 1=1" in front of every WHERE section
        $custom_where_string = 'AND ...';

        //only if the custom parameter is used, hook into the generation of the query
        // add_filter('posts_where', 'wplister_posts_where');
    }
}

function wplister_posts_where( $where )
{
    global $custom_where_string;

    echo "<pre>";print_r($where);echo"</pre>";die();

    //append our custom where expression(s)
    $where .= $custom_where_string;

    //clean up to avoid unexpected things on other queries
    remove_filter('posts_where', 'wplister_posts_where');
    $custom_where_string = '';

    return $where;
}
*/

// filter the products in admin based on ebay status
function wplister_woocommerce_admin_product_filter_query( $query ) {
	global $typenow, $wp_query, $wpdb;

    if ( $typenow == 'product' ) {

    	// filter by ebay status
    	if ( ! empty( $_GET['is_on_ebay'] ) ) {

        	// find all products that are already on ebay
        	$sql = "
        			SELECT {$wpdb->prefix}posts.ID 
        			FROM {$wpdb->prefix}posts 
				    LEFT JOIN {$wpdb->prefix}ebay_auctions
				         ON ( {$wpdb->prefix}posts.ID = {$wpdb->prefix}ebay_auctions.post_id )
				    WHERE {$wpdb->prefix}ebay_auctions.ebay_id != ''
        	";
        	$results = $wpdb->get_col( $sql );
        	// echo "<pre>";print_r($results);echo"</pre>";die();

	    	if ( $_GET['is_on_ebay'] == 'yes' ) {

	        	if ( is_array($results) && ( sizeof($results) > 0 ) ) {
		        	$query->query_vars['post__in'] = $results;
	        	}

	        } elseif ( $_GET['is_on_ebay'] == 'no' ) {

	        	if ( is_array($results) && ( sizeof($results) > 0 ) ) {
		        	$query->query_vars['post__not_in'] = $results;
	        	}

	        	// $query->query_vars['meta_value'] 	= null;
	        	// $query->query_vars['meta_key'] 		= '_ebay_item_id';

	        	// $query->query_vars['meta_query'] = array(
				// 	'relation' => 'OR',
				// 	array(
				// 		'key' => '_ebay_item_id',
				// 		'value' => ''
				// 	),
				// 	array(
				// 		'key' => '_ebay_item_id',
				// 		'value' => '',
				// 		'compare' => 'NOT EXISTS'
				// 	)
				// );

	        }
        }

	}

}
add_filter( 'parse_query', 'wplister_woocommerce_admin_product_filter_query' );

// # debug final query
// add_filter( 'posts_results', 'wplister_woocommerce_admin_product_filter_posts_results' );
// function wplister_woocommerce_admin_product_filter_posts_results( $posts ) {
// 	global $wp_query;
// 	echo "<pre>";print_r($wp_query->request);echo"</pre>";#die();
// 	return $posts;
// }

// add custom view to woocommerce products table
function wplister_add_woocommerce_product_views( $views ) {
	global $wp_query;

	if ( ! current_user_can('edit_others_pages') ) return $views;
	// $class = ( isset( $wp_query->query['is_on_ebay'] ) && $wp_query->query['is_on_ebay'] == 'no' ) ? 'current' : '';
	$class = ( isset( $_REQUEST['is_on_ebay'] ) && $_REQUEST['is_on_ebay'] == 'no' ) ? 'current' : '';
	$query_string = remove_query_arg(array( 'is_on_ebay' ));
	$query_string = add_query_arg( 'is_on_ebay', urlencode('no'), $query_string );
	$views['unlisted'] = '<a href="'. $query_string . '" class="' . $class . '">' . __('Not on eBay', 'wplister') . '</a>';

	// debug query
	// $views['unlisted'] .= "<br>".$wp_query->request."<br>";

	return $views;
}

add_filter( 'views_edit-product', 'wplister_add_woocommerce_product_views' );



