<?php
/**
 * hooks to alter the WooCommerce backend
 */

class WPL_WooBackendIntegration {

	function __construct() {

		// custom column for products table
		add_filter( 'manage_edit-product_columns', array( &$this, 'wpl_woocommerce_edit_product_columns' ), 11 );
		add_action( 'manage_product_posts_custom_column', array( &$this, 'wplister_woocommerce_custom_product_columns' ), 3 );

		// hook into save_post to mark listing as changed when a product is updated
		add_action( 'save_post', array( &$this, 'wplister_on_woocommerce_product_quick_edit_save' ), 10, 2 );

		// custom views for products table
		add_filter( 'parse_query', array( &$this, 'wplister_woocommerce_admin_product_filter_query' ) );
		add_filter( 'views_edit-product', array( &$this, 'wplister_add_woocommerce_product_views' ) );

		add_action( 'post_submitbox_misc_actions', array( &$this, 'wplister_product_submitbox_misc_actions' ), 100 );
		add_action( 'woocommerce_process_product_meta', array( &$this, 'wplister_product_handle_submitbox_actions' ), 100, 2 );

	}



	/**
	 * Columns for Products page
	 **/
	// add_filter('manage_edit-product_columns', 'wpl_woocommerce_edit_product_columns', 11 );

	function wpl_woocommerce_edit_product_columns($columns){
		
		$columns['listed'] = '<img src="'.WPLISTER_URL.'/img/hammer-dark-16x16.png" title="'.__('Listing status', 'wplister').'" />';		
		return $columns;
	}


	/**
	 * Custom Columns for Products page
	 **/
	// add_action('manage_product_posts_custom_column', 'wplister_woocommerce_custom_product_columns', 3 );

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

		// don't mark as changed when revising anyway
		if ( isset( $_POST['wpl_ebay_revise_on_update'] ) ) return;

		$lm = new ListingsModel();
		$lm->markItemAsModified( $post_id );

		// Clear transient
		// $woocommerce->clear_product_transients( $post_id );
	}
	// add_action( 'save_post', 'wplister_on_woocommerce_product_quick_edit_save', 10, 2 );




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
	// add_filter( 'parse_query', 'wplister_woocommerce_admin_product_filter_query' );
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
	        	$post_ids_on_ebay = $wpdb->get_col( $sql );
	        	// echo "<pre>";print_r($post_ids_on_ebay);echo"</pre>";#die();

	        	// find all products that hidden from ebay
	        	$sql = "
	        			SELECT post_id 
	        			FROM {$wpdb->prefix}postmeta 
					    WHERE meta_key   = '_ebay_hide_from_unlisted'
					      AND meta_value = 'yes'
	        	";
	        	$post_ids_hidden_from_ebay = $wpdb->get_col( $sql );
	        	// echo "<pre>";print_r($post_ids_hidden_from_ebay);echo"</pre>";#die();


		    	if ( $_GET['is_on_ebay'] == 'yes' ) {

					// combine arrays
					$post_ids = array_diff( $post_ids_on_ebay, $post_ids_hidden_from_ebay );
		        	// echo "<pre>";print_r($post_ids);echo"</pre>";die();

		        	if ( is_array($post_ids) && ( sizeof($post_ids) > 0 ) ) {
			        	$query->query_vars['post__in'] = $post_ids;
		        	}

		        } elseif ( $_GET['is_on_ebay'] == 'no' ) {

					// combine arrays
					$post_ids = array_merge( $post_ids_on_ebay, $post_ids_hidden_from_ebay );
		        	// echo "<pre>";print_r($post_ids);echo"</pre>";die();

		        	if ( is_array($post_ids) && ( sizeof($post_ids) > 0 ) ) {
			        	$query->query_vars['post__not_in'] = $post_ids;
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

	// # debug final query
	// add_filter( 'posts_results', 'wplister_woocommerce_admin_product_filter_posts_results' );
	// function wplister_woocommerce_admin_product_filter_posts_results( $posts ) {
	// 	global $wp_query;
	// 	echo "<pre>";print_r($wp_query->request);echo"</pre>";#die();
	// 	return $posts;
	// }

	// add custom view to woocommerce products table
	// add_filter( 'views_edit-product', 'wplister_add_woocommerce_product_views' );
	function wplister_add_woocommerce_product_views( $views ) {
		global $wp_query;

		if ( ! current_user_can('edit_others_pages') ) return $views;

		// On eBay
		// $class = ( isset( $wp_query->query['is_on_ebay'] ) && $wp_query->query['is_on_ebay'] == 'no' ) ? 'current' : '';
		$class = ( isset( $_REQUEST['is_on_ebay'] ) && $_REQUEST['is_on_ebay'] == 'yes' ) ? 'current' : '';
		$query_string = remove_query_arg(array( 'is_on_ebay' ));
		$query_string = add_query_arg( 'is_on_ebay', urlencode('yes'), $query_string );
		$views['listed'] = '<a href="'. $query_string . '" class="' . $class . '">' . __('On eBay', 'wplister') . '</a>';

		// Not on eBay
		$class = ( isset( $_REQUEST['is_on_ebay'] ) && $_REQUEST['is_on_ebay'] == 'no' ) ? 'current' : '';
		$query_string = remove_query_arg(array( 'is_on_ebay' ));
		$query_string = add_query_arg( 'is_on_ebay', urlencode('no'), $query_string );
		$views['unlisted'] = '<a href="'. $query_string . '" class="' . $class . '">' . __('Not on eBay', 'wplister') . '</a>';

		// debug query
		// $views['unlisted'] .= "<br>".$wp_query->request."<br>";

		return $views;
	}




	/**
	 * Output product update options.
	 *
	 * @access public
	 * @return void
	 */
	// add_action( 'post_submitbox_misc_actions', 'wplister_product_submitbox_misc_actions', 100 );
	function wplister_product_submitbox_misc_actions() {
		global $post;
		global $woocommerce;

		if ( $post->post_type != 'product' )
			return;

		// if product has been imported from ebay...
		$this->wplister_product_submitbox_imported_status();

		// check listing status
		$listingsModel = new ListingsModel();
		$status = $listingsModel->getStatusFromPostID( $post->ID );
		if ( ! in_array($status, array('published','changed') ) ) return;

		// get first item
		$listings = $listingsModel->getAllListingsFromPostID( $post->ID );
		if ( sizeof($listings) == 0 ) return;
		$item = $listings[0];

		?>
		
		<style type="text/css">
			#wpl_ebay_revise_on_update {
				width: auto;
				/*margin-left: 1em;*/
				float: right;
			}
			.wpl_ebay_revise_on_update_field { margin:0; }
		</style>

		<div class="misc-pub-section" id="wplister-submit-options">

			<input type="hidden" name="wpl_ebay_listing_id" value="<?php echo $item->id ?>" />

			<?php _e( 'eBay listing is', 'wplister' ); ?>
				<b><?php echo $item->status; ?></b> &nbsp;
				<a href="<?php echo $item->ViewItemURL ?>" target="_blank" style="float:right;">
					<?php echo __('View on eBay', 'wplister') ?>
				</a>
			<br>

			<?php

				$tip = __('Revise eBay listing when updating product', 'wplister') . '. '; 
				$tip .= __('If the product is out of stock, the listing will be ended on eBay.', 'wplister');
				$tip = '<img class="help_tip" data-tip="' . esc_attr( $tip ) . '" src="' . $woocommerce->plugin_url() . '/assets/images/help.png" height="16" width="16" />';

				woocommerce_wp_checkbox( array( 
					'id'    => 'wpl_ebay_revise_on_update', 
					'label' => __('Revise listing on update', 'wplister') . $tip,
					// 'description' => __('Revise on eBay', 'wplister'),
					'value' => get_option( 'wplister_revise_on_update_default', false )
				) );

			?>

		</div>
		<?php
	}

		// if product has been imported from ebay...
	function wplister_product_submitbox_imported_status() {
		global $post;
		global $woocommerce;

		$item_source = get_post_meta( $post->ID, '_ebay_item_source', true );
		if ( ! $item_source ) return;

		$ebay_id = get_post_meta( $post->ID, '_ebay_item_id', true );

		?>

		<div class="misc-pub-section" id="wplister-submit-options">

			<?php _e( 'This product was imported', 'wplister' ); ?>
				<!-- <b><?php echo $item->status; ?></b> &nbsp; -->
				<a href="http://www.ebay.com/itm/<?php echo $ebay_id ?>" target="_blank" style="float:right;">
					<?php echo __('View on eBay', 'wplister') ?>
				</a>
			<br>

		</div>
		<?php
	}


	// handle submitbox options
	// add_action( 'woocommerce_process_product_meta', 'wplister_product_handle_submitbox_actions', 100, 2 );
	function wplister_product_handle_submitbox_actions( $post_id, $post ) {
		global $oWPL_WPLister;
		global $wpl_logger;

		if ( isset( $_POST['wpl_ebay_revise_on_update'] ) ) {

			$wpl_logger->info('revising listing '.$_POST['wpl_ebay_listing_id'] );

			// call EbayController
			$oWPL_WPLister->initEC();
			$results = $oWPL_WPLister->EC->reviseItems( $_POST['wpl_ebay_listing_id'] );
			$oWPL_WPLister->EC->closeEbay();

			$wpl_logger->info('revised listing '.$_POST['wpl_ebay_listing_id'] );

			// $message = __('Selected items were revised on eBay.', 'wplister');
			// $message .= ' ID: '.$_POST['wpl_ebay_listing_id'];
			// $class = (false) ? 'error' : 'updated fade';
			// echo '<div id="message" class="'.$class.'" style="display:block !important"><p>'.$message.'</p></div>';


		}

	} // save_meta_box()


} // class WPL_WooBackendIntegration
$WPL_WooBackendIntegration = new WPL_WooBackendIntegration();
















class WpLister_Product_MetaBox {

	function __construct() {

		add_action( 'add_meta_boxes', array( &$this, 'add_meta_box' ) );
		add_action( 'woocommerce_process_product_meta', array( &$this, 'save_meta_box' ), 0, 2 );

	}

	function add_meta_box() {

		$title = __('eBay Options', 'wplister');
		add_meta_box( 'wplister-ebay-details', $title, array( &$this, 'meta_box_basic' ), 'product', 'normal', 'default');

		$title = __('Advanced eBay Options', 'wplister');
		add_meta_box( 'wplister-ebay-advanced', $title, array( &$this, 'meta_box_advanced' ), 'product', 'normal', 'default');

		$title = __('eBay Shipping Options', 'wplister');
		add_meta_box( 'wplister-ebay-shipping', $title, array( &$this, 'meta_box_shipping' ), 'product', 'normal', 'default');

		$this->enqueueFileTree();

	}

	function meta_box_basic() {
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

		woocommerce_wp_text_input( array(
			'id' 			=> 'wpl_ebay_start_price',
			'label' 		=> __('Price / Start Price', 'wplister'),
			'placeholder' 	=> 'Start Price',
			'value'			=> get_post_meta( $post->ID, '_ebay_start_price', true )
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

		woocommerce_wp_select( array(
			'id' 			=> 'wpl_ebay_listing_duration',
			'label' 		=> __('Listing Duration', 'wplister'),
			'options' 		=> array( 
					''               => __('-- use profile setting --', 'wplister'),
					'Days_1'         => '1 ' . __('Day', 'wplister'),
					'Days_3'         => '3 ' . __('Days', 'wplister'),
					'Days_5'         => '5 ' . __('Days', 'wplister'),
					'Days_7'         => '7 ' . __('Days', 'wplister'),
					'Days_10'        => '10 ' . __('Days', 'wplister'),
					'Days_30'        => '30 ' . __('Days', 'wplister'),
					'Days_60'        => '60 ' . __('Days', 'wplister'),
					'Days_90'        => '90 ' . __('Days', 'wplister'),
					'GTC'            =>  __('Good Till Canceled', 'wplister')
				),
			'value'			=> get_post_meta( $post->ID, '_ebay_listing_duration', true )
		) );

	}

	function meta_box_advanced() {
		global $woocommerce, $post;

        ?>
        <style type="text/css">
            #wplister-ebay-advanced label { 
            	float: left;
            	width:25%;
            	line-height: 2em;
            }
            #wplister-ebay-advanced input { 
            	width:74%; 
            }
            #wplister-ebay-advanced .description { 
            	clear: both;
            	margin-left: 25%;
            }
            #wplister-ebay-advanced h2 {
            	padding-top: 0.5em;
            	padding-bottom: 0.5em;
            	margin-top: 1em;
            	margin-bottom: 0;
            	border-top: 1px solid #555;
            	border-top: 2px dashed #ddd;
            }

        </style>
        <?php

		woocommerce_wp_text_input( array(
			'id' 			=> 'wpl_ebay_buynow_price',
			'label' 		=> __('Buy Now Price', 'wplister'),
			'placeholder' 	=> 'Buy Now Price',
			'value'			=> get_post_meta( $post->ID, '_ebay_buynow_price', true )
		) );

		woocommerce_wp_text_input( array(
			'id' 			=> 'wpl_ebay_reserve_price',
			'label' 		=> __('Reserve Price', 'wplister'),
			'placeholder' 	=> 'Reserve Price',
			'description' 	=> __('The lowest price at which you are willing to sell the item. Not all categories support a reserve price.','wplister'),
			'value'			=> get_post_meta( $post->ID, '_ebay_reserve_price', true )
		) );

		woocommerce_wp_text_input( array(
			'id' 			=> 'wpl_ebay_upc',
			'label' 		=> __('UPC', 'wplister'),
			'placeholder' 	=> 'Enter a Universal Product Code (UPC) to use product details from the eBay catalog.',
			'value'			=> get_post_meta( $post->ID, '_ebay_upc', true )
		) );

		woocommerce_wp_text_input( array(
			'id' 			=> 'wpl_ebay_gallery_image_url',
			'label' 		=> __('Gallery Image URL', 'wplister'),
			'placeholder' 	=> 'Enter an URL if you want to use a custom gallery image on eBay.',
			'value'			=> get_post_meta( $post->ID, '_ebay_gallery_image_url', true )
		) );

		woocommerce_wp_checkbox( array( 
			'id'    => 'wpl_ebay_global_shipping', 
			'label' => __('Global Shipping', 'wplister'),
			'value' => get_post_meta( $post->ID, '_ebay_global_shipping', true )
		) );

		woocommerce_wp_checkbox( array( 
			'id'    => 'wpl_ebay_hide_from_unlisted', 
			'label' => __('Hide from eBay', 'wplister'),
			'value' => get_post_meta( $post->ID, '_ebay_hide_from_unlisted', true )
		) );

		woocommerce_wp_textarea_input( array( 
			'id'    => 'wpl_ebay_payment_instructions', 
			'label' => __('Payment Instructions', 'wplister'),
			'value' => get_post_meta( $post->ID, '_ebay_payment_instructions', true )
		) );

		$this->showCategoryOptions();


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

	function showCategoryOptions() {
		global $post;

		echo '<h2>'.  __('eBay categories','wplister') . '</h2>';

		// primary ebay category
		$ebay_category_1_id   = get_post_meta( $post->ID, '_ebay_category_1_id', true );
		$ebay_category_1_name = $ebay_category_1_id ? EbayCategoriesModel::getFullEbayCategoryName( $ebay_category_1_id ) : '-- default --';

		// secondary ebay category
		$ebay_category_2_id   = get_post_meta( $post->ID, '_ebay_category_2_id', true );
		$ebay_category_2_name = $ebay_category_2_id ? EbayCategoriesModel::getFullEbayCategoryName( $ebay_category_2_id ) : '-- default --';

		?>
		<div style="position:relative; margin: 0 5px;">
			<label for="wpl-text-ebay_category_1_name" class="text_label"><?php echo __('Primary eBay category','wplister'); ?></label>
			<input type="hidden" name="wpl_ebay_category_1_id" id="ebay_category_id_1" value="<?php echo $ebay_category_1_id ?>" class="" />
			<span  id="ebay_category_name_1" class="text_input" style="width:45%;float:left;"><?php echo $ebay_category_1_name ?></span>
			<div class="category_row_actions">
				<input type="button" value="<?php echo __('select','wplister'); ?>" class="button-secondary btn_select_ebay_category" onclick="">
				<input type="button" value="<?php echo __('remove','wplister'); ?>" class="button-secondary btn_remove_ebay_category" onclick="">
			</div>
		</div>
		<br style="clear:both" />
		<div style="position:relative; margin: 0 5px;">
			<label for="wpl-text-ebay_category_2_name" class="text_label"><?php echo __('Secondary eBay category','wplister'); ?></label>
			<input type="hidden" name="wpl_ebay_category_2_id" id="ebay_category_id_2" value="<?php echo $ebay_category_2_id ?>" class="" />
			<span  id="ebay_category_name_2" class="text_input" style="width:45%;float:left;"><?php echo $ebay_category_2_name ?></span>
			<div class="category_row_actions">
				<input type="button" value="<?php echo __('select','wplister'); ?>" class="button-secondary btn_select_ebay_category" onclick="">
				<input type="button" value="<?php echo __('remove','wplister'); ?>" class="button-secondary btn_remove_ebay_category" onclick="">
			</div>
		</div>
		<br style="clear:both" />

		<!-- hidden ajax categories tree -->
		<div id="ebay_categories_tree_wrapper">
			<div id="ebay_categories_tree_container"></div>
		</div>

		<style type="text/css">

			#ebay_categories_tree_wrapper,
			#store_categories_tree_wrapper {
				/*max-height: 320px;*/
				/*margin-left: 35%;*/
				overflow: auto;
				width: 65%;
				display: none;
			}

			#wplister-ebay-advanced .category_row_actions {
				position: absolute;
				top: 0;
				right: 0;
			}
            #wplister-ebay-advanced .category_row_actions input { 
            	width: auto; 
            }


			a.link_select_category {
				float: right;
				padding-top: 3px;
				text-decoration: none;
			}
			a.link_remove_category {
				padding-left: 3px;
				text-decoration: none;
			}
			
		</style>

		<script type="text/javascript">

			/* recusive function to gather the full category path names */
	        function wpl_getCategoryPathName( pathArray, depth ) {
				var pathname = '';
				if (typeof depth == 'undefined' ) depth = 0;

	        	// get name
		        if ( depth == 0 ) {
		        	var cat_name = jQuery('[rel=' + pathArray.join('\\\/') + ']').html();
		        } else {
			        var cat_name = jQuery('[rel=' + pathArray.join('\\\/') +'\\\/'+ ']').html();
		        }

		        // console.log('path...: ', pathArray.join('\\\/') );
		        // console.log('catname: ', cat_name);
		        // console.log('pathArray: ', pathArray);

		        // strip last (current) item
		        popped = pathArray.pop();
		        // console.log('popped: ',popped);

		        // call self with parent path
		        if ( pathArray.length > 2 ) {
			        pathname = wpl_getCategoryPathName( pathArray, depth + 1 ) + ' &raquo; ' + cat_name;
		        } else if ( pathArray.length > 1 ) {
			        pathname = cat_name;
		        }

		        return pathname;

	        }

			jQuery( document ).ready(
				function () {


					// select ebay category button
					jQuery('input.btn_select_ebay_category').click( function(event) {
						// var cat_id = jQuery(this).parent()[0].id.split('sel_ebay_cat_id_')[1];
						e2e_selecting_cat = ('ebay_category_name_1' == jQuery(this).parent().parent().first().find('.text_input')[0].id) ? 1 : 2;

						var tbHeight = tb_getPageSize()[1] - 120;
						var tbURL = "#TB_inline?height="+tbHeight+"&width=500&inlineId=ebay_categories_tree_wrapper"; 
	        			tb_show("Select a category", tbURL);  
						
					});
					// remove ebay category button
					jQuery('input.btn_remove_ebay_category').click( function(event) {
						var cat_id = ('ebay_category_name_1' == jQuery(this).parent().parent().first().find('.text_input')[0].id) ? 1 : 2;
						
						jQuery('#ebay_category_id_'+cat_id).attr('value','');
						jQuery('#ebay_category_name_'+cat_id).html('');
					});
			
					// select store category button
					jQuery('input.btn_select_store_category').click( function(event) {
						// var cat_id = jQuery(this).parent()[0].id.split('sel_store_cat_id_')[1];
						e2e_selecting_cat = ('store_category_name_1' == jQuery(this).parent().parent().first().find('.text_input')[0].id) ? 1 : 2;

						var tbHeight = tb_getPageSize()[1] - 120;
						var tbURL = "#TB_inline?height="+tbHeight+"&width=500&inlineId=store_categories_tree_wrapper"; 
	        			tb_show("Select a category", tbURL);  
						
					});
					// remove store category button
					jQuery('input.btn_remove_store_category').click( function(event) {
						var cat_id = ('store_category_name_1' == jQuery(this).parent().parent().first().find('.text_input')[0].id) ? 1 : 2;
						
						jQuery('#store_category_id_'+cat_id).attr('value','');
						jQuery('#store_category_name_'+cat_id).html('');
					});
			
			
					// jqueryFileTree 1 - ebay categories
				    jQuery('#ebay_categories_tree_container').fileTree({
				        root: '/0/',
				        script: ajaxurl+'?action=e2e_get_ebay_categories_tree',
				        expandSpeed: 400,
				        collapseSpeed: 400,
				        loadMessage: 'loading eBay categories...',
				        multiFolder: false
				    }, function(catpath) {

						// get cat id from full path
				        var cat_id = catpath.split('/').pop(); // get last item - like php basename()

				        // get name of selected category
				        var cat_name = '';

				        var pathname = wpl_getCategoryPathName( catpath.split('/') );
						// console.log('pathname: ',pathname);
				        
				        // update fields
				        jQuery('#ebay_category_id_'+e2e_selecting_cat).attr( 'value', cat_id );
				        jQuery('#ebay_category_name_'+e2e_selecting_cat).html( pathname );
				        
				        // close thickbox
				        tb_remove();

				        // if ( e2e_selecting_cat == 1 ) {
				        // 	updateItemSpecifics();
				        // 	updateItemConditions();
				        // }

				    });
		
					// jqueryFileTree 2 - store categories
				    jQuery('#store_categories_tree_container').fileTree({
				        root: '/0/',
				        script: ajaxurl+'?action=e2e_get_store_categories_tree',
				        expandSpeed: 400,
				        collapseSpeed: 400,
				        loadMessage: 'loading store categories...',
				        multiFolder: false
				    }, function(catpath) {

						// get cat id from full path
				        var cat_id = catpath.split('/').pop(); // get last item - like php basename()

				        // get name of selected category
				        var cat_name = '';

				        var pathname = wpl_getCategoryPathName( catpath.split('/') );
						// console.log('pathname: ',pathname);
				        
				        // update fields
				        jQuery('#store_category_id_'+e2e_selecting_cat).attr( 'value', cat_id );
				        jQuery('#store_category_name_'+e2e_selecting_cat).html( pathname );
				        
				        // close thickbox
				        tb_remove();

				    });
		


				}
			);
		
		
		</script>

		<?php

	}

	function meta_box_shipping() {
		global $woocommerce, $post;

        ?>
        <style type="text/css">
            #wplister-ebay-shipping label { 
            	float: left;
            	width:25%;
            	line-height: 2em;
            }
            #wplister-ebay-shipping input { 
            	/*width:74%; */
            }
            #wplister-ebay-shipping .description { 
            	/*clear: both;*/
            	/*margin-left: 25%;*/
            }
            #wplister-ebay-shipping .ebay_shipping_options_wrapper h2 {
            	padding-top: 0.5em;
            	padding-bottom: 0.5em;
            	margin-top: 1em;
            	margin-bottom: 0;
            	border-top: 1px solid #555;
            	border-top: 2px dashed #ddd;
            }

        </style>
        <?php

		$this->showShippingOptions();

	}

	function showShippingOptions() {
		global $woocommerce, $post;

		$wpl_loc_flat_shipping_options = EbayShippingModel::getAllLocal('flat');
		$wpl_int_flat_shipping_options = EbayShippingModel::getAllInternational('flat');
		$wpl_shipping_locations        = EbayShippingModel::getShippingLocations();
		$wpl_countries                 = EbayShippingModel::getEbayCountries();

		$wpl_loc_calc_shipping_options   = EbayShippingModel::getAllLocal('calculated');
		$wpl_int_calc_shipping_options   = EbayShippingModel::getAllInternational('calculated');
		$wpl_calc_shipping_enabled       = in_array( get_option('wplister_ebay_site_id'), array(0,2,15,100) );
		$wpl_available_shipping_packages = get_option('wplister_ShippingPackageDetails');

		// make sure that at least one payment and shipping option exist
		$item_details['loc_shipping_options'] = ProfilesModel::fixShippingArray( get_post_meta( $post->ID, '_ebay_loc_shipping_options', true ) );
		$item_details['int_shipping_options'] = ProfilesModel::fixShippingArray( get_post_meta( $post->ID, '_ebay_int_shipping_options', true ) );
		
		$item_details['PackagingHandlingCosts']              = get_post_meta( $post->ID, '_ebay_PackagingHandlingCosts', true );
		$item_details['InternationalPackagingHandlingCosts'] = get_post_meta( $post->ID, '_ebay_InternationalPackagingHandlingCosts', true );
		$item_details['shipping_service_type']               = get_post_meta( $post->ID, '_ebay_shipping_service_type', true );
		if ( ! $item_details['shipping_service_type'] ) $item_details['shipping_service_type'] = 'disabled';

		// echo '<h2>'.  __('Shipping Options','wplister') . '</h2>';
		?>
			<!-- service type selector -->
			<label for="wpl-text-loc_shipping_service_type" class="text_label"><?php echo __('Custom shipping options','wplister'); ?></label>
			<select name="wpl_e2e_shipping_service_type" id="wpl-text-loc_shipping_service_type" 
					class="required-entry select select_shipping_type" style="width:auto;"
					onchange="handleShippingTypeSelectionChange(this)">
				<option value="disabled" <?php if ( @$item_details['shipping_service_type'] == 'disabled' ): ?>selected="selected"<?php endif; ?>><?php echo __('-- use profile setting --','wplister'); ?></option>
				<option value="flat"     <?php if ( @$item_details['shipping_service_type'] == 'flat' ): ?>selected="selected"<?php endif; ?>><?php echo __('Use Flat Shipping','wplister'); ?></option>
				<option value="calc"     <?php if ( @$item_details['shipping_service_type'] == 'calc' ): ?>selected="selected"<?php endif; ?>><?php echo __('Use Calculated Shipping','wplister'); ?></option>
				<option value="FlatDomesticCalculatedInternational" <?php if ( @$item_details['shipping_service_type'] == 'FlatDomesticCalculatedInternational' ): ?>selected="selected"<?php endif; ?>><?php echo __('Use Flat Domestic and Calculated International Shipping','wplister'); ?></option>
				<option value="CalculatedDomesticFlatInternational" <?php if ( @$item_details['shipping_service_type'] == 'CalculatedDomesticFlatInternational' ): ?>selected="selected"<?php endif; ?>><?php echo __('Use Calculated Domestic and Flat International Shipping','wplister'); ?></option>
				<option value="FreightFlat" <?php if ( @$item_details['shipping_service_type'] == 'FreightFlat' ): ?>selected="selected"<?php endif; ?>><?php echo __('Use Freight Shipping','wplister'); ?></option>
			</select>
		<?php
		
		echo '<div class="ebay_shipping_options_wrapper">';
		echo '<h2>'.  __('Domestic shipping','wplister') . '</h2>';
		include( WPLISTER_PATH . '/views/profile/edit_shipping_loc.php' );

		echo '<h2>'.  __('International shipping','wplister') . '</h2>';
		include( WPLISTER_PATH . '/views/profile/edit_shipping_int.php' );
		echo '</div>';

		echo '<script>';
		include( WPLISTER_PATH . '/views/profile/edit_shipping.js' );		
		echo '</script>';
		
	}

	function enqueueFileTree() {

		// jqueryFileTree
		wp_register_style('jqueryFileTree_style', WPLISTER_URL.'/js/jqueryFileTree/jqueryFileTree.css' );
		wp_enqueue_style('jqueryFileTree_style'); 

		// jqueryFileTree
		wp_register_script( 'jqueryFileTree', WPLISTER_URL.'/js/jqueryFileTree/jqueryFileTree.js', array( 'jquery' ) );
		wp_enqueue_script( 'jqueryFileTree' );

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
			$wpl_ebay_listing_duration      = esc_attr( @$_POST['wpl_ebay_listing_duration'] );
			$wpl_ebay_start_price           = esc_attr( @$_POST['wpl_ebay_start_price'] );
			$wpl_ebay_reserve_price         = esc_attr( @$_POST['wpl_ebay_reserve_price'] );
			$wpl_ebay_buynow_price          = esc_attr( @$_POST['wpl_ebay_buynow_price'] );
			$wpl_ebay_upc          			= esc_attr( @$_POST['wpl_ebay_upc'] );
			$wpl_ebay_hide_from_unlisted  	= esc_attr( @$_POST['wpl_ebay_hide_from_unlisted'] );
			$wpl_ebay_category_1_id      	= esc_attr( @$_POST['wpl_ebay_category_1_id'] );
			$wpl_ebay_category_2_id      	= esc_attr( @$_POST['wpl_ebay_category_2_id'] );
			$wpl_ebay_gallery_image_url   	= esc_attr( @$_POST['wpl_ebay_gallery_image_url'] );

			// Update order data
			update_post_meta( $post_id, '_ebay_title', $wpl_ebay_title );
			update_post_meta( $post_id, '_ebay_subtitle', $wpl_ebay_subtitle );
			update_post_meta( $post_id, '_ebay_global_shipping', $wpl_ebay_global_shipping );
			update_post_meta( $post_id, '_ebay_payment_instructions', $wpl_ebay_payment_instructions );
			update_post_meta( $post_id, '_ebay_condition_description', $wpl_ebay_condition_description );
			update_post_meta( $post_id, '_ebay_listing_duration', $wpl_ebay_listing_duration );
			update_post_meta( $post_id, '_ebay_auction_type', $wpl_ebay_auction_type );
			update_post_meta( $post_id, '_ebay_start_price', $wpl_ebay_start_price );
			update_post_meta( $post_id, '_ebay_reserve_price', $wpl_ebay_reserve_price );
			update_post_meta( $post_id, '_ebay_buynow_price', $wpl_ebay_buynow_price );
			update_post_meta( $post_id, '_ebay_upc', $wpl_ebay_upc );
			update_post_meta( $post_id, '_ebay_hide_from_unlisted', $wpl_ebay_hide_from_unlisted );
			update_post_meta( $post_id, '_ebay_category_1_id', $wpl_ebay_category_1_id );
			update_post_meta( $post_id, '_ebay_category_2_id', $wpl_ebay_category_2_id );
			update_post_meta( $post_id, '_ebay_gallery_image_url', $wpl_ebay_gallery_image_url );

			// shipping options
			$ebay_shipping_service_type = esc_attr( @$_POST['wpl_e2e_shipping_service_type'] );

			if ( $ebay_shipping_service_type != 'disabled' ) {
	
				update_post_meta( $post_id, '_ebay_shipping_service_type', $ebay_shipping_service_type );

				$details = ProfilesPage::getPreprocessedPostDetails();
				update_post_meta( $post_id, '_ebay_loc_shipping_options', $details['loc_shipping_options'] );
				update_post_meta( $post_id, '_ebay_int_shipping_options', $details['int_shipping_options'] );

				update_post_meta( $post_id, '_ebay_shipping_package', esc_attr( @$_POST['wpl_e2e_shipping_package'] ) );
				update_post_meta( $post_id, '_ebay_PackagingHandlingCosts', esc_attr( @$_POST['wpl_e2e_PackagingHandlingCosts'] ) );
				update_post_meta( $post_id, '_ebay_InternationalPackagingHandlingCosts', esc_attr( @$_POST['wpl_e2e_InternationalPackagingHandlingCosts'] ) );

			} else {

				delete_post_meta( $post_id, '_ebay_shipping_service_type' );
				delete_post_meta( $post_id, '_ebay_loc_shipping_options' );
				delete_post_meta( $post_id, '_ebay_int_shipping_options' );
				delete_post_meta( $post_id, '_ebay_shipping_package' );
				delete_post_meta( $post_id, '_ebay_PackagingHandlingCosts' );
				delete_post_meta( $post_id, '_ebay_InternationalPackagingHandlingCosts' );

			}
			// echo "<pre>";print_r($_POST);echo"</pre>";die();

		}

	} // save_meta_box()

} // class WpLister_Product_MetaBox
$WpLister_Product_MetaBox = new WpLister_Product_MetaBox();
















