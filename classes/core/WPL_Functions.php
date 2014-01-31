<?php
/**
 * globally available functions
 */


// custom tooltips
function wplister_tooltip( $desc ) {
    echo '<img class="help_tip" data-tip="' . esc_attr( $desc ) . '" src="' . WPLISTER_URL . '/img/help.png" height="16" width="16" />';
}

