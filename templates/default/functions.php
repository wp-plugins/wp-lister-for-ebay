<?php

// process template html        
function my_tpl_register_custom_fields() {

	// register custom template options
    // parameters are: $type, $id, $default, $label, $config
	wplister_register_custom_fields( 'title', 'section_title', '', 'Section Header Bar' );
	wplister_register_custom_fields( 'color', 'section_head_color', '#FFFFFF', 'Text Color' );
	wplister_register_custom_fields( 'color', 'section_head_bgcolor_top', '#555555', 'Background Top' );
	wplister_register_custom_fields( 'color', 'section_head_bgcolor_bottom', '#000000', 'Background Bottom' );
	wplister_register_custom_fields( 'title', 'hotline_title', '', 'Hotline Box' );
	wplister_register_custom_fields( 'color', 'hotline_color', '#FFFFFF', 'Text Color' );
	wplister_register_custom_fields( 'color', 'hotline_bgcolor', '#FFA500', 'Background Color' );
	wplister_register_custom_fields( 'title', 'other_title', '', 'Other Colors' );
	wplister_register_custom_fields( 'color', 'title_color', '#000000', 'Product Title' );
	wplister_register_custom_fields( 'title', 'other_title2', '', 'Other Options' );
	
	$radius_options = array(
		'none' => '0px',
		'3 px' => '3px',
		'4 px' => '4px',
		'5 px' => '5px',
		'7 px' => '7px',
		'10 px' => '10px',
		'12 px' => '12px'
	);
	wplister_register_custom_fields( 'select', 'border_radius', '5px', 'Border Radius', array( 'options' => $radius_options ) );
	
	$hotline_options = array(
		'yes' => 'block',
		'no'  => 'none'
	);
	wplister_register_custom_fields( 'select', 'display_hotline', 'block', 'Display Hotline Box', array( 'options' => $hotline_options ) );
	
    // you can register text fields as well
	// wplister_register_custom_fields( 'text', 'test_field', '', 'Test' );

}
add_action( 'wplister_template_init', 'my_tpl_register_custom_fields' );


// implement custom listing shortcode by filtering the processed template html
/*
function my_tpl_process_html( $html, $item, $images ) {
	$html = str_replace( '[[my_custom_listing_shortcode]]', 'MY CUSTOM CONTENT', $html );
	return $html;
}
add_filter( 'wplister_process_template_html', 'my_tpl_process_html', 10, 3 );
*/
