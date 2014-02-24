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
		add_action( 'save_post', array( &$this, 'wplister_on_woocommerce_product_bulk_edit_save' ), 20, 2 );
		add_action( 'save_post', array( &$this, 'wplister_on_woocommerce_product_quick_edit_save' ), 20, 2 );

		// show messages when listing was updated from edit product page
		add_action( 'post_updated_messages', array( &$this, 'wplister_product_updated_messages' ), 20, 1 );

		// custom views for products table
		add_filter( 'parse_query', array( &$this, 'wplister_woocommerce_admin_product_filter_query' ) );
		add_filter( 'views_edit-product', array( &$this, 'wplister_add_woocommerce_product_views' ) );

		// submitbox actions
		add_action( 'post_submitbox_misc_actions', array( &$this, 'wplister_product_submitbox_misc_actions' ), 100 );
		add_action( 'woocommerce_process_product_meta', array( &$this, 'wplister_product_handle_submitbox_actions' ), 100, 2 );

		// hook into WooCommerce orders to create product objects for ebay listings (debug)
		// add_action( 'woocommerce_order_get_items', array( &$this, 'wpl_woocommerce_order_get_items' ), 10, 2 );
		add_filter( 'woocommerce_get_product_from_item', array( &$this, 'wpl_woocommerce_get_product_from_item' ), 10, 3 );

		// add Prepare Listing action link on products table
		add_filter( 'post_row_actions', array( &$this, 'wpl_post_row_actions' ), 10, 2 );

	}



	/**
	 * add Prepare Listing action link on products table
	 **/
	// add_filter( 'post_row_actions', array( &$this, 'wpl_post_row_actions' ), 10, 2 );

	function wpl_post_row_actions( $actions, $post ){

		// skip if this is not a WC product
		if ( $post->post_type == 'product' ) {

			// get listing status
			$listingsModel = new ListingsModel();
			$status = $listingsModel->getStatusFromPostID( $post->ID );
			
			// skip if listing exists
			if ( $status ) return $actions;

			// TODO: check if product is in stock and not currently published on eBay!
			// if ( ! get_post_meta( $post->ID, '_ebay_item_id', true ) )
			$actions['prepare_auction'] = "<a title='" . esc_attr( __('Prepare this product to be listed on eBay.','wplister') ) . "' href='" . wp_nonce_url( admin_url( 'admin.php?page=wplister' . '&amp;action=wpl_prepare_single_listing&amp;product_id=' . $post->ID ), 'prepare_listing_' . $post->ID ) . "'>" . __( 'List on eBay', 'wplister' ) . "</a>";

		}

		return $actions;
	}

	/**
	 * fix order line items
	 **/
	// add_filter('woocommerce_get_product_from_item', 'wpl_woocommerce_get_product_from_item', 10, 2 );

	function wpl_woocommerce_get_product_from_item( $_product, $item, $order ){
		// global $wpl_logger;

		// $wpl_logger->info('wpl_woocommerce_get_product_from_item - item: '.print_r($item,1));
		// $wpl_logger->info('wpl_woocommerce_get_product_from_item - _product: '.print_r($_product,1));
		// $wpl_logger->info('wpl_woocommerce_get_product_from_item - order: '.print_r($order,1));

		// if this is not a valid WC product object, post processing or email generation might fail
		if ( ! $_product ) {

			// check if this order was created by WP-Lister
			if ( isset( $order->order_custom_fields['_ebay_order_id'] ) ) {

				// create a new ebay product object to allow email templates or other plugins to do $_product->get_sku() and more...
				$_product = new WC_Product_Ebay( $item['product_id'] );
				// $wpl_logger->info('wpl_woocommerce_get_product_from_item - NEW _product: '.print_r($_product,1));

			}

		}

		return $_product;
	}

	/**
	 * debug order line items
	 **/
	// add_filter('woocommerce_order_get_items', 'wpl_woocommerce_order_get_items', 10, 2 );

	function wpl_woocommerce_order_get_items( $items, $order ){
		global $wpl_logger;
		$wpl_logger->info('wpl_woocommerce_order_get_items - items: '.print_r($items,1));
		// $wpl_logger->info('wpl_woocommerce_order_get_items - order: '.print_r($order,1));
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
						echo '<a href="'.$ebayUrl.'" title="View on eBay" target="_blank"><img src="'.WPLISTER_URL.'img/ebay-16x16.png" alt="yes" /></a>';
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


	// hook into save_post to mark listing as changed when a product is updated via quick edit
	function wplister_on_woocommerce_product_quick_edit_save( $post_id, $post ) {

		if ( !$_POST ) return $post_id;
		if ( is_int( wp_is_post_revision( $post_id ) ) ) return;
		if ( is_int( wp_is_post_autosave( $post_id ) ) ) return;
		if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) return $post_id;
		// if ( !isset($_POST['woocommerce_quick_edit_nonce']) || (isset($_POST['woocommerce_quick_edit_nonce']) && !wp_verify_nonce( $_POST['woocommerce_quick_edit_nonce'], 'woocommerce_quick_edit_nonce' ))) return $post_id;
		if ( !current_user_can( 'edit_post', $post_id )) return $post_id;
		if ( $post->post_type != 'product' ) return $post_id;

		// global $woocommerce, $wpdb;
		// $product = self::getProduct( $post_id );

		// don't mark as changed when listing has been revised earlier in this request
		if ( isset( $_POST['wpl_ebay_revise_on_update'] ) ) return;

		$lm = new ListingsModel();
		$lm->markItemAsModified( $post_id );

		// Clear transient
		// $woocommerce->clear_product_transients( $post_id );
	}
	// add_action( 'save_post', 'wplister_on_woocommerce_product_quick_edit_save', 10, 2 );


	// hook into save_post to mark listing as changed when a product is updated via bulk update
	function wplister_on_woocommerce_product_bulk_edit_save( $post_id, $post ) {

		if ( is_int( wp_is_post_revision( $post_id ) ) ) return;
		if ( is_int( wp_is_post_autosave( $post_id ) ) ) return;
		if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) return $post_id;
		if ( ! isset( $_REQUEST['woocommerce_bulk_edit_nonce'] ) || ! wp_verify_nonce( $_REQUEST['woocommerce_bulk_edit_nonce'], 'woocommerce_bulk_edit_nonce' ) ) return $post_id;
		if ( ! current_user_can( 'edit_post', $post_id ) ) return $post_id;
		if ( $post->post_type != 'product' ) return $post_id;

		// $lm = new ListingsModel();
		// $lm->markItemAsModified( $post_id );
		do_action( 'wplister_product_has_changed', $post_id );

	}
	// add_action( 'save_post', 'wplister_on_woocommerce_product_bulk_edit_save', 10, 2 );




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
		if ( ! in_array($status, array('published','changed','ended','sold','prepared','verified') ) ) return;

		// get first item
		$listings = $listingsModel->getAllListingsFromPostID( $post->ID );
		if ( sizeof($listings) == 0 ) return;
		$item = $listings[0];

        // show locked indicator
        if ( @$item->locked ) {
            $tip_msg = 'This listing is currently locked.<br>Only inventory changes and prices will be updated, other changes will be ignored.';
            $img_url = WPLISTER_URL . '/img/lock-1.png';
            $locktip = '<img src="'.$img_url.'" style="height:11px; padding:0;" class="tips" data-tip="'.$tip_msg.'"/>&nbsp;';
        } 

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
			<b><?php echo $item->status; ?></b>

			<?php if ( isset($locktip) ) echo $locktip ?>

			<?php if ( isset($item->ViewItemURL) && $item->ViewItemURL ) : ?>
				<a href="<?php echo $item->ViewItemURL ?>" target="_blank" style="float:right;">
					<?php echo __('View on eBay', 'wplister') ?>
				</a>
			<?php elseif ( $item->status == 'prepared' ) : ?>
				<a href="admin.php?page=wplister&amp;action=verify&amp;auction=<?php echo $item->id ?>" style="float:right;">
					<?php echo __('Verify', 'wplister') ?>
				</a>
			<?php elseif ( $item->status == 'verified' ) : ?>
				<a href="admin.php?page=wplister&amp;action=publish2e&amp;auction=<?php echo $item->id ?>" style="float:right;">
					<?php echo __('Publish', 'wplister') ?>
				</a>
			<?php endif; ?>

			<br>

			<?php 
				// show revise checkbox for published listings
				if ( in_array($status, array('published','changed') ) )
					$this->wplister_product_submitbox_revise_checkbox( $item );
			?>

			<?php if ( in_array($status, array('ended','sold') ) ) : ?>
				<a href="admin.php?page=wplister&amp;action=relist&amp;auction=<?php echo $item->id ?>" 
					onclick="return confirm('Are you sure you want to relist this product on eBay?');" style="float:right;">
					<?php echo __('Relist', 'wplister') ?>
				</a>
			<?php endif; ?>

		</div>
		<?php
	} // wplister_product_submitbox_misc_actions()

	// draw checkbox to revise item
	function wplister_product_submitbox_revise_checkbox( $item ) {
		global $woocommerce;

		// prevent wp_kses_post() from removing the data-tip attribute
		global $allowedposttags;
		$allowedposttags['img']['data-tip'] = true;

		if ( $item->locked ) {

			$tip = 'This listing is locked. WP-Lister will update prices and stock levels automatically on eBay when updating the product.<br>'; 
			$tip .= __('If the product is out of stock, the listing will be ended on eBay.', 'wplister');
			$tip = '<img class="help_tip" data-tip="' . esc_attr( $tip ) . '" src="' . $woocommerce->plugin_url() . '/assets/images/help.png" height="16" width="16" />';

			woocommerce_wp_checkbox( array( 
				'id'    => 'wpl_ebay_revise_on_update', 
				'label' => __('Revise inventory on update', 'wplister') . $tip,
				// 'description' => __('Revise on eBay', 'wplister'),
				'value' => 'yes'
			) );

		} else {

			$tip = __('Revise eBay listing when updating the product', 'wplister') . '. '; 
			$tip .= __('If the product is out of stock, the listing will be ended on eBay.', 'wplister');
			$tip = '<img class="help_tip" data-tip="' . esc_attr( $tip ) . '" src="' . $woocommerce->plugin_url() . '/assets/images/help.png" height="16" width="16" />';

			woocommerce_wp_checkbox( array( 
				'id'    => 'wpl_ebay_revise_on_update', 
				'label' => __('Revise listing on update', 'wplister') . $tip,
				// 'description' => __('Revise on eBay', 'wplister'),
				'value' => get_option( 'wplister_revise_on_update_default', false )
			) );

		}

	} // wplister_product_submitbox_revise_checkbox()

	// if product has been imported from ebay...
	function wplister_product_submitbox_imported_status() {
		global $post;
		global $woocommerce;

		$item_source = get_post_meta( $post->ID, '_ebay_item_source', true );
		if ( ! $item_source ) return;

		$ebay_id = get_post_meta( $post->ID, '_ebay_item_id', true );

		// get ViewItemURL - fall back to generic url on ebay.com
		$listingsModel = new ListingsModel();
		$ebay_url = $listingsModel->getViewItemURLFromPostID( $post->ID );
		if ( ! $ebay_url ) $ebay_url = 'http://www.ebay.com/itm/'.$ebay_id;

		?>

		<div class="misc-pub-section" id="wplister-submit-options">

			<?php _e( 'This product was imported', 'wplister' ); ?>
				<!-- <b><?php echo $item->status; ?></b> &nbsp; -->
				<a href="<?php echo $ebay_url ?>" target="_blank" style="float:right;">
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

			// call markItemAsModified() to re-apply the listing profile
			$lm = new ListingsModel();
			$lm->markItemAsModified( $post_id );

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


	function wplister_product_updated_messages( $messages ) {
		global $post, $post_ID;

		// fetch last results
		$update_results = get_option( 'wplister_last_product_update_results', array() );
		if ( ! is_array($update_results) ) $update_results = array();

		// do nothing if no result for this product exists
		if ( ! isset( $update_results[ $post_ID ] ) ) return $messages;

		// show errors later
		add_action( 'admin_notices', array( &$this, 'wplister_product_updated_notices' ), 20 );

		$success = $update_results[ $post_ID ]->success;
		// $errors  = $update_results[ $post_ID ]->errors;

		// add message
		if ( $success )
			$messages['product'][1] = sprintf( __( 'Product and eBay listing were updated. <a href="%s">View Product</a>', 'wplister' ), esc_url( get_permalink($post_ID) ) );

		return $messages;
	}

	function wplister_product_updated_notices() {
		global $post, $post_ID;

		// fetch last results
		$update_results = get_option( 'wplister_last_product_update_results', array() );
		if ( ! is_array($update_results) ) $update_results = array();
		if ( ! isset( $update_results[ $post_ID ] ) ) return;


		$success = $update_results[ $post_ID ]->success;
		$errors  = $update_results[ $post_ID ]->errors;

		foreach ($errors as $error) {
			// hide redundant warnings like:
			// 21917092 - Warning: Requested Quantity revision is redundant.
			// 21916620 - Warning: Variations with quantity '0' will be removed
			if ( ! in_array( $error->ErrorCode, array( 21917091, 21917092, 21916620 ) ) )
				echo $error->HtmlMessage;
			
		}

		// unset last result
		unset( $update_results[ $post_ID ] );
		update_option( 'wplister_last_product_update_results', $update_results );

	} // wplister_product_updated_notices()



} // class WPL_WooBackendIntegration
global $WPL_WooBackendIntegration;
$WPL_WooBackendIntegration = new WPL_WooBackendIntegration();
