<?php
/**
 * hooks to alter the WooCommerce frontend
 */

class WPL_WooFrontendIntegration {

	function __construct() {

		add_action( 'woocommerce_single_product_summary', array( &$this, 'show_single_product_info' ), 10 );
		add_filter( 'woocommerce_loop_add_to_cart_link', array( &$this, 'handle_add_to_cart_link' ), 10, 3 );

		// add item compatibility table tab
        add_filter( 'woocommerce_product_tabs', array( &$this, 'add_custom_product_tabs' ) );

	}


	// show current ebay status - WooCommerce 2.0 only
	function handle_add_to_cart_link( $html, $product, $link = false ) {

		$auction_display_mode = get_option( 'wplister_local_auction_display', 'off' );

		if ( $auction_display_mode == 'forced' ) {

			if ( $listing = $this->is_on_ebay( $product->id ) ) {

				// replace add to cart button with view details button
				$html = sprintf('<a href="%s" class="add_to_cart_button button product_type_simple">%s</a>', get_permalink( $product->id ), __('View details','wplister') );

			}

		} elseif ( $auction_display_mode != 'off' ) {

			if ( $listing = $this->is_on_auction( $product->id ) ) {

				// replace add to cart button with view details button
				$html = sprintf('<a href="%s" class="add_to_cart_button button product_type_simple">%s</a>', get_permalink( $product->id ), __('View details','wplister') );

			}

		}

		return $html;
	}


	// show current ebay status
	function show_single_product_info() {
		global $post;

		$auction_display_mode = get_option( 'wplister_local_auction_display', 'off' );

		if ( $auction_display_mode == 'forced' ) {

			if ( $listing = $this->is_on_ebay( $post->ID ) ) {

				// view on ebay button
				echo '<p>';
				echo sprintf('<a href="%s" class="single_add_to_cart_button button alt" target="_blank">%s</a>', $listing->ViewItemURL, __('View on eBay','wplister') );
				echo '</p>';

				// hide woo elements
				echo '<style> form.cart { display:none } </style>';

			}

		} elseif ( $auction_display_mode != 'off' ) {

			if ( $listing = $this->is_on_auction( $post->ID ) ) {
				// echo "<pre>";print_r($listing);echo"</pre>";die();

				$details = $this->getItemDetails( $listing->ebay_id );

				if ( $details['BidCount'] == 0 ) {
					
					// do nothing if "only if bids" is enabled and there are more than 12 hours left
					// $auction_display_mode = get_option( 'wplister_local_auction_display', 'off' );
					$hours_left           = ( strtotime($listing->end_date) - gmdate('U') ) / 3600;
					if ( ( $hours_left > 12 ) && ( $auction_display_mode == 'if_bid' ) ) return;

					// start price
					echo '<p itemprop="price" class="price startprice">'.__('Starting bid','wplister').': <span class="amount">'.woocommerce_price($listing->price).'</span></p>';
				} else {
					// current price
					echo '<p itemprop="price" class="price startprice">'.__('Current bid','wplister').': <span class="amount">'.woocommerce_price($details['CurrentPrice']).'</span>';
					echo ' ('.$details['BidCount']. __('bids','wplister').')';
					echo '</p>';
				}

				// auction message
				if ( $listing->end_date ) {
					$msg = __('This item is currently on auction and will end in %s','wplister');
					$msg = sprintf( $msg, human_time_diff( strtotime( $listing->end_date ) ) );
				} else {
					$msg = __('This item is currently on auction on eBay.','wplister');					
				}
				echo '<p>'.$msg.'</p>';

				// view on ebay button
				echo '<p>';
				echo sprintf('<a href="%s" class="single_add_to_cart_button button alt" target="_blank">%s</a>', $listing->ViewItemURL, __('View on eBay','wplister') );
				echo '</p>';

				// hide woo elements
				echo '<style> form.cart, p.price { display:none }  p.startprice { display:inline }  </style>';

			}

		}

	} // show_single_product_info()


	// get current details
	function getItemDetails( $ebay_id ) {

		$transient_key = 'wplister_ebay_details_'.$ebay_id;

		$details = get_transient( $transient_key );
		if ( empty( $details ) ){
		   
			// fetch ebay details and update transient
			$item_details = $this->updateItemDetails( $ebay_id );

			$details = array(
				'StartTime'     => $item_details->ListingDetails->StartTime,
				'EndTime'       => $item_details->ListingDetails->EndTime,
				'Quantity'      => $item_details->Quantity,
				'QuantitySold'  => $item_details->SellingStatus->QuantitySold,
				'BidCount'      => $item_details->SellingStatus->BidCount,
				'CurrentPrice'  => $item_details->SellingStatus->CurrentPrice->value,
				'ListingStatus' => $item_details->SellingStatus->ListingStatus,
			);

			set_transient($transient_key, $details, 60 );
		}

		return $details;

	} // getItemDetails()


	// update current details from ebay
	function updateItemDetails( $ebay_id ) {

		global $oWPL_WPLister;
		$oWPL_WPLister->initEC();

		$lm = new ListingsModel();
		$details = $lm->getLatestDetails( $ebay_id, $oWPL_WPLister->EC->session );

		return $details;

	} // updateItemDetails()


	// check if product is currently on auction
	function is_on_auction( $post_id ) {

		$lm = new ListingsModel();
		$listings = $lm->getAllListingsFromPostID( $post_id );
		foreach ($listings as $listing) {

			// check listing type on product level
			if ( get_post_meta( $post_id, '_ebay_auction_type', true ) != 'Chinese' ) {

				// check listing type on listing level
				if ( $listing->auction_type != 'Chinese') continue;

			}

			// check status
			if ( ! in_array( $listing->status, array('published','changed') ) )
				 continue;

			// check end date
			if ( $listing->end_date )
				if ( strtotime( $listing->end_date ) < time() ) continue;

			return $listing;
		}

		return false;

	} // is_on_auction()

	// check if product is currently on ebay
	function is_on_ebay( $post_id ) {

		$lm = new ListingsModel();
		$listings = $lm->getAllListingsFromPostID( $post_id );
		foreach ($listings as $listing) {

			// check status
			if ( ! in_array( $listing->status, array('published','changed') ) )
				 continue;

			// check end date
			if ( $listing->end_date )
				if ( strtotime( $listing->end_date ) < time() ) continue;

			return $listing;
		}

		return false;

	} // is_on_ebay()

    public function add_custom_product_tabs( $tabs ) {
		global $post;

		// check if compatibility tab is enabled
		if ( ! get_option( 'wplister_enable_item_compat_tab', 1 ) ) return $tabs;

		// don't add tab if there is no compatibility list
		$compatibility_list   = get_post_meta( $post->ID, '_ebay_item_compatibility_list', true );
		if ( ( ! is_array($compatibility_list) ) || ( sizeof($compatibility_list) == 0 ) ) return $tabs;

        $tabs[ 'ebay_item_compatibility_list' ] = array(
                'title'    => __('Compatibility','wplister'),
                'priority' => 25,
                'callback' => array( $this, 'showCompatibilityList' ),
                // 'content'  => $tab['content'],  // custom field
        );

        return $tabs;
    }


	function showCompatibilityList() {
		global $post;

		// primary ebay category
		$compatibility_list   = get_post_meta( $post->ID, '_ebay_item_compatibility_list', true );
		$compatibility_names  = get_post_meta( $post->ID, '_ebay_item_compatibility_names', true );
		#echo "<pre>";print_r($compatibility_names);echo"</pre>";#die();

		// return if there is no compatibility list
		if ( ( ! is_array($compatibility_list) ) || ( sizeof($compatibility_list) == 0 ) ) return;

		do_action( 'wplister_before_item_compatibility_list', $post->ID );

		echo '<h2>'.  __('Item Compatibility List','wplister') . '</h2>';

		?>
			<table class="ebay_item_compatibility_list">

				<tr>
					<?php foreach ($compatibility_names as $name) : ?>

						<th><?php echo $name ?></th>

					<?php endforeach; ?>

					<th>	
						<?php echo 'Notes' ?>
					</th>

				</tr>

				<?php foreach ($compatibility_list as $comp) : ?>

					<tr>
						<?php foreach ($compatibility_names as $name) : ?>

							<td><?php echo $comp->applications[ $name ]->value ?></td>

						<?php endforeach; ?>

						<td><?php echo $comp->notes ?></td>

					</tr>
					
				<?php endforeach; ?>

			</table>

			<style type="text/css">

				.ebay_item_compatibility_list {
					width: 100%;
				}
				.ebay_item_compatibility_list tr th {
					text-align: left;
					border-bottom: 3px double #bbb;
				}
				.ebay_item_compatibility_list tr td {
					border-bottom: 1px solid #ccc;
				}
				
			</style>

		<?php

		do_action( 'wplister_after_item_compatibility_list', $post->ID );

	}


} // class WPL_WooFrontendIntegration
$WPL_WooFrontendIntegration = new WPL_WooFrontendIntegration();
