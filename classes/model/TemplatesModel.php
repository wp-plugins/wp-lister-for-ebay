<?php

class TemplatesModel extends WPL_Model {

	public $foldername;
	public $folderpath;
	public $stylesheet;

	function TemplatesModel( $foldername = false )
	{
		global $wpl_logger;
		$this->logger = &$wpl_logger;

		if ( $foldername ) {

			// folder name
			$foldername = basename($foldername);
			$this->foldername = $foldername;

			// full absolute paths
			$upload_dir = wp_upload_dir();
			$this->folderpath = $upload_dir['basedir'] . '/wp-lister/templates/' . $foldername;
			$this->stylesheet = $this->folderpath . '/style.css';

			// save / return item (?)
			$this->item = $this->getItem( $foldername );
			return $this->item;
		}
	}
	

	function getAll() {

		// get user templates
		$upload_dir = wp_upload_dir();

		$templates = array();
		$files = glob( $upload_dir['basedir'].'/wp-lister/templates/*/template.html' );
		foreach ($files as $file) {
			// save template path relative to WP_CONTENT_DIR
			// $file = str_replace(WP_CONTENT_DIR,'',$file);
			$file = basename(dirname( $file ));
			$templates[] = $this->getItem( $file );
		}

		return $templates;	
	}

	function getBuiltIn() {

		// get build in templates
		$files = glob( WPLISTER_PATH . '/templates/*/template.html' );

		$templates = array();
		foreach ($files as $file) {
			// save template path relative to WP_CONTENT_DIR
			$file = str_replace(WP_CONTENT_DIR,'',$file);
			$templates[] = $this->getItem( $file, false, 'built-in' );
		}

		return $templates;	
	}

	function getItem( $foldername = false, $fullpath = false, $type = 'user' ) {

		// set templates root folder
		$upload_dir = wp_upload_dir();
		$templates_dir = $upload_dir['basedir'].'/wp-lister/templates/';

		if ( $fullpath ) {
			// do nothing
		} elseif ( $foldername ) {
			$fullpath = $templates_dir . $foldername;
		} else {
			$fullpath = $this->folderpath;
		}

		// build item
		$item = array();

		// default template name
		$item['template_name'] = basename($fullpath);
		$item['template_path'] = str_replace(WP_CONTENT_DIR,'',$fullpath);

		// last modified date
		$item['last_modified'] = filemtime($fullpath.'/template.html');

		// template type
		$item['type'] = $type;

		// template slug
		$item['template_id'] = urlencode( $item['template_name'] );

		// check css file for more info
		$stylesheet = $fullpath . '/style.css';
		if ( file_exists( $stylesheet ) ) {

			// $stylesheet = dirname( )
			$tplroot = realpath( dirname($stylesheet).'/..' );
			$tplfolder = basename(dirname($stylesheet));
			
			// wp_get_theme is WP 3.4+ only
			if ( function_exists('wp_get_theme')) {
				$tpl = wp_get_theme( $tplfolder, $tplroot );
				// echo "<pre>";print_r($tpl);echo "</pre>";
				$item['template_name'] = $tpl->Template;
				$item['template_version'] = $tpl->Version;
				$item['template_description'] = $tpl->Description;
			} else {
				$item['template_name'] = basename($fullpath);
				$item['template_version'] = '';
				$item['template_description'] = 'Please update to WordPress 3.4';				
			}
		}


		return $item;		
	}


	function newItem() {
		$item = array(
			"template_id" => false,
			"template_name" => "New listing template",
			"template_path" => "enter a unique folder name here",
			"template_version" => "1.0",
			"template_description" => ""
		);
		$this->folderpath = WPLISTER_PATH . '/templates/default';
		return $item;		
	}


	public function processItem( $item ) {
		
		$listing = new ListingsModel();

		// load template content
		$tpl_html = $this->getContent();

		// handle errors
		if ( ! $tpl_html ) {
			$this->logger->error( 'template not found ' . $item['template'] );
			$this->logger->error( 'should be here: ' . WP_CONTENT_DIR . '/uploads/wp-lister/templates/' . $item['template']  );
			echo 'Template not found: '.$item['template'];
			die();
		}
		// $this->logger->debug( 'template loaded from ' . $tpl_path );
		// $this->logger->info( $tpl_html );
		
		
		// remove ALL links from post content by default
		// TODO: make this an option in settings
		$item['post_content'] = preg_replace('#<a.*?>([^<]*)</a>#i', '$1', $item['post_content'] );

		// replace shortcodes
		$tpl_html = str_replace( '[[product_title]]', $listing->prepareTitleAsHTML( $item['auction_title'] ), $tpl_html );
		$tpl_html = str_replace( '[[product_content]]', apply_filters('the_content', $item['post_content'] ), $tpl_html );

		$tpl_html = str_replace( '[[product_excerpt]]', $listing->getRawPostExcerpt( $item['post_id'] ), $tpl_html );
		$tpl_html = str_replace( '[[product_additional_content]]', $listing->getRawPostExcerpt( $item['post_id'] ), $tpl_html );
		
		$tpl_html = str_replace( '[[product_price]]', number_format_i18n( floatval($item['price']), 2 ), $tpl_html );
		$tpl_html = str_replace( '[[product_price_raw]]', $item['price'], $tpl_html );

		$tpl_html = str_replace( '[[product_weight]]', ProductWrapper::getWeight( $item['post_id'], true ), $tpl_html );

		// dimensions
		$dimensions = ProductWrapper::getDimensions( $item['post_id'] );
		$width  = @$dimensions['width']  . ' ' . @$dimensions['width_unit'];
		$height = @$dimensions['height'] . ' ' . @$dimensions['height_unit'];
		$length = @$dimensions['length'] . ' ' . @$dimensions['length_unit'];
		$tpl_html = str_replace( '[[product_width]]' , $width,  $tpl_html );
		$tpl_html = str_replace( '[[product_height]]', $height, $tpl_html );
		$tpl_html = str_replace( '[[product_length]]', $length,  $tpl_html );		

		// attributes
		$product_attributes = ProductWrapper::getAttributes( $item['post_id'] );
		if ( preg_match_all("/\\[\\[attribute_(.*)\\]\\]/uUsm", $tpl_html, $matches ) ) {

			foreach ( $matches[1] as $attribute ) {

				if ( isset( $product_attributes[ $attribute ] )){
					$attribute_value = $product_attributes[ $attribute ];
				} else {					
					$attribute_value = '';
				}
				$tpl_html = str_replace( '[[attribute_'.$attribute.']]', $attribute_value,  $tpl_html );		

			}

		}

		// handle images...
		$main_image = $listing->getProductMainImageURL( $item['post_id'] );
		$images = $listing->getProductImagesURL( $item['post_id'] );
		$this->logger->debug( 'images found ' . print_r($images,1) );
		
		// [[product_main_image]]
		$the_main_image = '<img class="wpl_product_image" src="'.$main_image.'" alt="main product image" />';
		$tpl_html = str_replace( '[[product_main_image]]', $the_main_image, $tpl_html );

		// [[product_main_image_url]]
		$tpl_html = str_replace( '[[product_main_image_url]]', $main_image, $tpl_html );
		
		// handle [[add_img_1]] to [[add_img_9]]
		// and [[add_img_url_1]] to [[add_img_url_9]]
		for ($i=0; $i < 9; $i++) { 
			
			if ( isset( $images[ $i ] ) ) {
				$img_url = $images[ $i ];
				$img_tag = '<img class="wpl_additional_product_image img_'.($i+1).'" src="'.$img_url.'" />';
			} else {
				$img_url = '';
				$img_tag = '';
			}

			$tpl_html = str_replace( '[[img_'.($i+1).']]',     $img_tag, $tpl_html );
			$tpl_html = str_replace( '[[img_url_'.($i+1).']]', $img_url, $tpl_html );

		}

		// handle all additional images
		// [[additional_product_images]]
		$imagelist = '';
		if ( count($images) > 1 ) {

			// loop all images
			for ($i=0; $i < count($images); $i++) { 
				$image_url = $images[$i];
				$image_alt = basename( $images[$i] );
				$imagelist .= '<a onmouseover="javascript:if (typeof wplOnThumbnailHover == \'function\') wplOnThumbnailHover(\''.$image_url.'\');return false;" href="#">';
				$imagelist .= '<img class="wpl_thumb thumb_'.($i+1).'" src="'.$image_url.'" alt="'.$image_alt.'" /></a>'."\n";
			}
			
		}		
		$tpl_html = str_replace( '[[additional_product_images]]', $imagelist, $tpl_html );
		

		// return html
		return $tpl_html;
	}


	function getContent() {

		// load template.html
		$tpl_html = $this->getHTML();

		// load and embed style.css
		$tpl_css  = $this->getCSS();
		$tpl_html = '<style type="text/css">'.$tpl_css.'</style>'."\n\n".$tpl_html;

		// include header.php
		$tpl_header  = $this->getDynamicContent( $this->folderpath . '/header.php' );
		$tpl_html = $tpl_header."\n\n".$tpl_html;

		// include footer.php
		$tpl_footer  = $this->getDynamicContent( $this->folderpath . '/footer.php' );
		$tpl_html = $tpl_html."\n\n".$tpl_footer;

		return $tpl_html;

	}

	function getHTML( $folder = false) {
		if ( ! $folder ) $folder = $this->folderpath;
		$file = $folder . '/template.html';		
		return file_get_contents( $file );
	}
	function getCSS( $folder = false ) {
		if ( ! $folder ) $folder = $this->folderpath;
		$file = $folder . '/style.css';		
		return file_get_contents( $file );
	}
	function getHeader( $folder = false ) {
		if ( ! $folder ) $folder = $this->folderpath;
		$file = $folder . '/header.php';		
		return @file_get_contents( $file );
	}
	function getFooter( $folder = false ) {
		if ( ! $folder ) $folder = $this->folderpath;
		$file = $folder . '/footer.php';		
		return @file_get_contents( $file );
	}

	public function getDynamicContent( $sFile, $inaData = array() ) {

		if ( !is_file( $sFile ) ) {
			$this->showMessage("File not found: ".$sFile,1,1);
			return false;
		}
		
		if ( count( $inaData ) > 0 ) {
			extract( $inaData, EXTR_PREFIX_ALL, 'wpl' );
		}
		
		ob_start();
			include( $sFile );
			$sContents = ob_get_contents();
		ob_end_clean();
		
		return $sContents;

	}

	function deleteTemplate( $id ) {
		$item = $this->getItem( $id );
		$fullpath = WP_CONTENT_DIR . $item['template_path'];

		// delete each template file
		$files = glob( $fullpath . '/*' );
		foreach ($files as $file) {
			unlink($file);
		}

		// delete folder
		rmdir($fullpath);

	}

	static function getCache() {
		
		$templates_cache = get_option( 'wplister_templates_cache' );

		if ( $templates_cache == '' ) 
			return array();

		return $templates_cache;
	}

	static function getNameFromCache( $id ) {
		
		$templates_cache = self::getCache();

		if ( isset( $templates_cache[ $id ] ) ) 
			return $templates_cache[ $id ]['template_name'];

		return $id;
	}

	function insertTemplate($id, $data) {
	}
	function updateTemplate($id, $data) {
	}
	function duplicateTemplate($id) {
	}


}