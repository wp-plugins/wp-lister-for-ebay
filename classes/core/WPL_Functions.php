<?php
/**
 * globally available functions
 */


// custom tooltips
function wplister_tooltip( $desc ) {
    global $woocommerce_fraud_prevention_admin;
    echo '<img class="help_tip" data-tip="' . esc_attr( $desc ) . '" src="' . WPLISTER_URL . '/img/help.png" height="16" width="16" />';
}

