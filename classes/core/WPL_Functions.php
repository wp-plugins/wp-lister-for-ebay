<?php
/**
 * globally available functions
 */


// custom tooltips
function wplister_tooltip( $desc ) {
	if ( defined('WPLISTER_RESELLER_VERSION') ) $desc = apply_filters( 'wplister_tooltip_text', $desc );
	if ( defined('WPLISTER_RESELLER_VERSION') && apply_filters( 'wplister_reseller_disable_tooltips', false ) ) return;
    echo '<img class="help_tip" data-tip="' . esc_attr( $desc ) . '" src="' . WPLISTER_URL . '/img/help.png" height="16" width="16" />';
}

// fetch eBay ItemID for a specific product_id / variation_id
// Note: this function does not return archived listings
function wplister_get_ebay_id_from_post_id( $post_id ) {
	$ebay_id = WPLE_ListingQueryHelper::getEbayIDFromPostID( $post_id );
	return $ebay_id;
}

// fetch fetch eBay items by column
// example: wple_get_listings_where( 'status', 'changed' );
function wple_get_listings_where( $column, $value ) {
	return WPLE_ListingQueryHelper::getWhere( $column, $value );
}


// show admin message (since 2.0.2)
function wple_show_message( $message, $type = 'info', $params = null ) {
	WPLE()->messages->add_message( $message, $type, $params );
}

// get instance of WP-Lister object
function WPLE() {
	return WPL_WPLister::get_instance();
}
