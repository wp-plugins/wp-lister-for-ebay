<?php
/**
 * hooks to alter the WooCommerce frontend
 */

class WPL_WooFrontendIntegration {

	function __construct() {

		add_action( 'woocommerce_single_product_summary', array( &$this, 'show_single_product_info' ), 10 );
		add_filter( 'woocommerce_loop_add_to_cart_link', array( &$this, 'handle_add_to_cart_link' ), 10, 3 );

	}


	// show current ebay status - WooCommerce 2.0 only
	function handle_add_to_cart_link( $html, $product, $link ) {

		if ( get_option( 'wplister_local_auction_display', 'off' ) != 'off' ) {

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

		if ( get_option( 'wplister_local_auction_display', 'off' ) != 'off' ) {

			if ( $listing = $this->is_on_auction( $post->ID ) ) {
				// echo "<pre>";print_r($listing);echo"</pre>";die();

				$details = $this->getItemDetails( $listing->ebay_id );

				if ( $details['BidCount'] == 0 ) {
					if ( get_option( 'wplister_local_auction_display', 'off' ) == 'if_bid' ) return;
					// start price
					echo '<p itemprop="price" class="price startprice">'.__('Starting bid','wplister').': <span class="amount">'.woocommerce_price($listing->price).'</span></p>';
				} else {
					// current price
					echo '<p itemprop="price" class="price startprice">'.__('Current bid','wplister').': <span class="amount">'.woocommerce_price($details['CurrentPrice']).'</span>';
					echo ' ('.$details['BidCount']. __('bids','wplister').')';
					echo '</p>';
				}

				// auction message
				$msg = __('This item is currently on auction and will end in %s','wplister');
				echo '<p>';
				echo sprintf( $msg, human_time_diff( strtotime( $listing->end_date ) ) );
				echo '</p>';

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

			// check listing type
			if ( $listing->auction_type != 'Chinese') continue;

			// check end date
			if ( strtotime( $listing->end_date ) < time() ) continue;

			return $listing;
		}

		return false;

	} // is_on_auction()


} // class WPL_WooFrontendIntegration
$WPL_WooFrontendIntegration = new WPL_WooFrontendIntegration();
