<?php
/**
 * globally available functions
 */


// custom tooltips
function wplister_tooltip( $desc ) {
	if ( defined('WPLISTER_RESELLER_VERSION') ) $desc = apply_filters( 'wplister_tooltip_text', $desc );
    echo '<img class="help_tip" data-tip="' . esc_attr( $desc ) . '" src="' . WPLISTER_URL . '/img/help.png" height="16" width="16" />';
}

