<?php
/**
 * TemplatesPage class
 * 
 */

class TemplatesPage extends WPL_Page {

	const slug = 'templates';

	public function onWpInit() {
		// parent::onWpInit();

		// Add custom screen options
		$load_action = "load-".$this->main_admin_menu_slug."_page_wplister-".self::slug;
		add_action( $load_action, array( &$this, 'addScreenOptions' ) );

		add_action('wp_ajax_wpl_get_copy_template_form', array( &$this, 'ajax_wpl_get_copy_template_form' ));
		add_action('wp_ajax_wpl_duplicate_template', array( &$this, 'ajax_wpl_duplicate_template' ));

		add_action('wp_ajax_wpl_get_tpl_css', array( &$this, 'ajax_wpl_get_tpl_css' ));

		$this->handleSubmitOnInit();
	}

	public function onWpAdminMenu() {
		parent::onWpAdminMenu();

		add_submenu_page( self::ParentMenuId, $this->getSubmenuPageTitle( 'Templates' ), __('Templates','wplister'), 
						  self::ParentPermissions, $this->getSubmenuId( 'templates' ), array( &$this, 'onDisplayTemplatesPage' ) );
	}

	public function handleSubmit() {
        $this->logger->debug("handleSubmit()");

		// handle save template
		if ( $this->requestAction() == 'save_template' ) {
			$this->saveTemplate();
			if ( @$_POST['return_to'] == 'listings' ) {
				wp_redirect( get_admin_url().'admin.php?page=wplister' );
			}
		}
		// handle download template
		if ( $this->requestAction() == 'download_listing_template' ) {
			$this->downloadTemplate();
		}
		// handle delete action
		if ( $this->requestAction() == 'delete_listing_template' ) {
			$templatesModel = new TemplatesModel();
			$templates = $templatesModel->deleteTemplate( $_REQUEST['template'] );	
			$this->showMessage( "Template deleted: ".$_REQUEST['template'] );	
		}

	}

	public function handleSubmitOnInit() {

		// handle preview action
		if ( $this->requestAction() == 'preview_template' ) {
			$this->previewTemplate( $_REQUEST['template'] );
			exit();
		}

	}

	function addScreenOptions() {
		$option = 'per_page';
		$args = array(
	    	'label' => 'Templates',
	        'default' => 20,
	        'option' => 'templates_per_page'
	        );
		add_screen_option( $option, $args );
		$this->temmplatesTable = new TemplatesTable();
		add_thickbox();

        wp_enqueue_style( 'farbtastic' );
		wp_enqueue_script( 'farbtastic' );

 	    // If the WordPress version is greater than or equal to 3.5, then load the new WordPress color picker.
		/*
		global $wp_version;
	    if ( 3.5 <= $wp_version ){
	        wp_enqueue_style( 'wp-color-picker' );
	        wp_enqueue_script( 'wp-color-picker' );
	    } else {
	        wp_enqueue_style( 'farbtastic' );
			wp_enqueue_script( 'farbtastic' );
	    }
	    */

	}
	


	public function onDisplayTemplatesPage() {
		WPL_Setup::checkSetup();
	
		// edit template
		if ( ( $this->requestAction() == 'edit' ) || ( $this->requestAction() == 'add_new_template' ) ) {

			$this->displayEditPage();

		// show list
		} else {

			$this->displayListPage();			

		}

	}


	private function displayListPage() {

		// handle upload template
		if ( $this->requestAction() == 'wpl_upload_template' ) {
			$this->uploadTemplate();
		}

		// init model
		$templatesModel = new TemplatesModel();
	
		// get all items
		$templates = $templatesModel->getAll();

	    //Create an instance of our package class...
	    $templatesTable = new TemplatesTable();
    	//Fetch, prepare, sort, and filter our data...
	    $templatesTable->prepare_items( $templates );

	    // refresh cache of template names and descriptions
	    $this->refreshTemplatesCache( $templates );

		// process errors 		
		#if ($this->IC->message) $this->showMessage( $this->IC->message,1 );
		
		$aData = array(
			'plugin_url'				=> self::$PLUGIN_URL,
			'message'					=> $this->message,

			'templates'					=> $templates,
			'templatesTable'			=> $templatesTable,
		
			'form_action'				=> 'admin.php?page='.self::ParentMenuId.'-templates'
		);

		$this->display( 'templates_page', $aData );
		
	}


	private function displayEditPage() {

		if ( $this->requestAction() == 'add_new_template' ) {
			
			// add new template
			$template = false;
			$templatesModel 		= new TemplatesModel();
			$item 					= $templatesModel->newItem();
			$html 					= $templatesModel->getHTML(); 
			$css					= $templatesModel->getCSS ();
			$header					= $templatesModel->getHeader();
			$footer					= $templatesModel->getFooter();				
			$functions				= $templatesModel->getFunctions();				

		} else {

			// edit template
			$template 				= urldecode( $_REQUEST['template'] );
			$templatesModel 		= new TemplatesModel( $template );
			$item 					= $templatesModel->getItem();
			$html					= $templatesModel->getHTML();
			$css					= $templatesModel->getCSS ();
			$header					= $templatesModel->getHeader();
			$footer					= $templatesModel->getFooter();				
			$functions				= $templatesModel->getFunctions();				
		}

		// init template
		$templatesModel->initTemplate();

		// remove template header from stylesheet
		if ( preg_match('/^\/\*.*^\*\//uUsm', $css, $matches ) ) {
			$css = str_replace($matches[0], '', $css);
		}

		// check for CDATA tag in html, header and footer
		if ( strpos($html, '<![CDATA[') > 0 ) {
			$this->showMessage( "Warning: Your template HTML code contains CDATA tags which can break the listing process. You should remove them as they don't fullfill any purpose in an eBay listing anyway.", 1 );
		}
		if ( strpos($header, '<![CDATA[') > 0 ) {
			$this->showMessage( "Warning: Your template header contains CDATA tags which can break the listing process. You should remove them as they don't fullfill any purpose in an eBay listing anyway.", 1 );
		}
		if ( strpos($footer, '<![CDATA[') > 0 ) {
			$this->showMessage( "Warning: Your template footer contains CDATA tags which can break the listing process. You should remove them as they don't fullfill any purpose in an eBay listing anyway.", 1 );
		}

		$listingsModel = new ListingsModel();
		$prepared_listings  = $listingsModel->getAllPreparedWithTemplate( $template );
		$verified_listings  = $listingsModel->getAllVerifiedWithTemplate( $template );
		$published_listings = $listingsModel->getAllPublishedWithTemplate( $template );

		$aData = array(
			'plugin_url'				=> self::$PLUGIN_URL,
			'message'					=> $this->message,

			'item'						=> $item,
			'html'						=> $html,
			'css'						=> $css,
			'header'					=> $header,
			'footer'					=> $footer,
			'functions'					=> $functions,
			'template_location'			=> $item['template_path'],
			'add_new_template'			=> ( $this->requestAction() == 'add_new_template' ) ? true : false,
			'tpl_fields'  			    => $templatesModel->fields,

			'prepared_listings'         => $prepared_listings,
			'verified_listings'         => $verified_listings,
			'published_listings'        => $published_listings,
			'disable_wysiwyg_editor'    => self::getOption( 'disable_wysiwyg_editor', 0 ),
			
			'form_action'				=> 'admin.php?page='.self::ParentMenuId.'-templates'
		);
		$this->display( 'templates_edit_page', $aData );

	}


	private function refreshTemplatesCache( $templates ) {

		// build array with foldername as keys
		$templates_cache = array();		
		foreach ($templates as $tpl) {
			$templates_cache[ $tpl['template_id'] ] = $tpl;
		}
		
		// save as option
		self::updateOption( 'templates_cache', $templates_cache );
		// $this->logger->info( print_r($templates_cache,1));

	}


	private function saveTemplate() {

		// set templates root folder
		$upload_dir = wp_upload_dir();
		$templates_dir = $upload_dir['basedir'].'/wp-lister/templates/';

		// handle add_new_template
		// if ( $this->getValueFromPost('add_new_template') == 1 ) {
		if ( isset( $_REQUEST['wpl_add_new_template'] ) ) {

			// check folder name
			$dirname = strtolower( sanitize_file_name( $this->getValueFromPost( 'template_name' ) ) );
			if ( $dirname == '') {
				$this->showMessage( "Could not create template. No template name was provided.", 1 );	
				return false;				
			}
			
			// tpl_dir is the full path to the template
			$tpl_dir = $templates_dir . $dirname;

			// if folder exists, append '-1', '-2', .. '-99'
			if ( is_dir( $tpl_dir ) ) {
				for ($i=1; $i < 100; $i++) { 
					$new_tpl_dir = $tpl_dir . '-' . $i;					
					if ( ! is_dir( $new_tpl_dir ) ) {
						$tpl_dir = $new_tpl_dir;
						break;
					}
				}
			}

			// make new folder
			$result  = @mkdir( $tpl_dir );

			// handle errors
			if ($result===false) {
				$this->showMessage( "Could not create template folder: " . $tpl_dir, 1 );	
				return false;
			} else {
				$this->showMessage( __('New template created in folder:','wplister') .' '. basename($tpl_dir) );
			}

			// init default template to handle setting
			$templatesModel = new TemplatesModel();
			$templatesModel->folderpath = WPLISTER_PATH . '/templates/default/';
			$templatesModel->initTemplate();
		
		// save existing template
		} else {
			
			$dirname = $this->getValueFromPost( 'template_id' );
			$tpl_dir = $templates_dir . $dirname;

			// re-apply profile to all published
			$listingsModel = new ListingsModel();
			$items = $listingsModel->getAllPublishedWithTemplate( $dirname );
			if ( ! empty( $items ) ) {
		        foreach ($items as $item) {

		        	// don't mark locked items as changed
		        	if ( ! $item['locked'] )
			        	$listingsModel->updateListing( $item['id'], array('status' => 'changed') );
			        
		        }
				$this->showMessage( sprintf( __('%s published items marked as changed.','wplister'), count($items) ) );			
			}

			// init template to handle setting
			$templatesModel = new TemplatesModel( $dirname );
			$templatesModel->initTemplate();
		
		}

		// destination files
		$file_html					= $tpl_dir . '/template.html';
		$file_css					= $tpl_dir . '/style.css';
		$file_header				= $tpl_dir . '/header.php';
		$file_footer				= $tpl_dir . '/footer.php';
		$file_functions				= $tpl_dir . '/functions.php';
		$file_settings				= $tpl_dir . '/config.json';
		
		$tpl_html	 				= stripslashes( $this->getValueFromPost( 'tpl_html' ) );
		$tpl_css	 				= stripslashes( $this->getValueFromPost( 'tpl_css'  ) );
		$tpl_header	 				= stripslashes( $this->getValueFromPost( 'tpl_header'  ) );
		$tpl_footer	 				= stripslashes( $this->getValueFromPost( 'tpl_footer'  ) );
		$tpl_functions	 			= stripslashes( $this->getValueFromPost( 'tpl_functions'  ) );
		
		$template_name 				= stripslashes( $this->getValueFromPost( 'template_name'  ) );
		$template_description 		= stripslashes( $this->getValueFromPost( 'template_description'  ) );
		$template_version 			= stripslashes( $this->getValueFromPost( 'template_version'  ) );

		// handle custom fields settings
		$settings = array();
		if ( is_array( $templatesModel->fields ) ) {
			foreach ($templatesModel->fields as $field_id => $field) {
				$value = $this->getValueFromPost( 'tpl_field_'.$field_id );
				if ( $value ) {
					$settings[ $field_id ] = stripslashes( $value );
				}
			}
		}

		// add template header
		$header_css = "/* \n";
		$header_css .= "Template: $template_name\n";
		$header_css .= "Description: $template_description\n";
		$header_css .= "Version: $template_version\n";
		$header_css .= "*/\n";
		$tpl_css = $header_css . $tpl_css;

		// update template files
		$result = file_put_contents($file_css , $tpl_css);
		$result = file_put_contents($file_functions , $tpl_functions);
		$result = file_put_contents($file_footer , $tpl_footer);
		$result = file_put_contents($file_header , $tpl_header);
		$result = file_put_contents($file_html, $tpl_html);
		$result = file_put_contents($file_settings, json_encode( $settings ) );

		// catch any errors about permissions, safe mode, etc.
	    global $php_errormsg;
		ini_set('track_errors', 1); 
		if ( ! touch( $file_css ) ) {
			$this->showMessage( $php_errormsg, true );				
		}

		// proper error handling
		if ($result===false) {
			$this->showMessage( "WP-Lister failed to save your template because it could not write to the file <pre>$file_css</pre> Please check the file and folder permissions and make sure that PHP safe_mode is disabled.", true );	
		} else {
			// hide double success message when adding new template
			if ( !isset( $_REQUEST['wpl_add_new_template'] ) ) $this->showMessage( __('Template saved.','wplister') );

			// if we were updating this template as part of setup, move to next step
			if ( '3' == self::getOption('setup_next_step') ) self::updateOption('setup_next_step', 4);

		}

	}


	public function previewTemplate( $template_id ) {
	
		// init model
		$ibm = new ItemBuilderModel();
		$preview_html = $ibm->getPreviewHTML( $template_id );
		echo $preview_html;
		exit();		

	}



	private function uploadTemplate() {
		
		check_admin_referer('wpl_upload_template');

		// set templates root folder
		$upload_dir = wp_upload_dir();
		$templates_dir = $upload_dir['basedir'].'/wp-lister/templates/';

	    $filename = $_FILES['fupload']['name'];
	    $tmp_name = $_FILES['fupload']['tmp_name'];
	    $type = $_FILES['fupload']['type']; 
	    $name = explode('.', $filename); 
	    $target = $templates_dir;

	    // permission settings for newly created folders
	    $chmod = 0755;  
		
		// set up WP_Filesystem() required for unzip_file
		WP_Filesystem();

	    $saved_file_location = $target . $filename;
	    if (move_uploaded_file($tmp_name, $saved_file_location)) {

	    	// extract zip archive
	        $return = unzip_file($saved_file_location, $target);
			if ( is_wp_error($return) ) {
				$this->showMessage( __('There was a problem while extracting the archive:','wplister') .' '. $return->get_error_message(), 1 ) ;				
			} else {
				$this->showMessage( __('Your listing template was uploaded and installed.','wplister') );				
			}
			// remove archive
			unlink( $saved_file_location );

	    } else {
			$this->showMessage( __('There was a problem while processing your upload.','wplister') );				
	    }

	}

	private function downloadTemplate() {

		// set templates root folder
		$upload_dir = wp_upload_dir();
		$templates_dir = $upload_dir['basedir'].'/wp-lister/templates/';

		$template_id = $_REQUEST['template'];
	    $folder  = $templates_dir . $template_id . '/';
	    $tmpfile = $templates_dir . $template_id . '.zip';

	    $files_to_zip = array( 'style.css', 'template.html', 'header.php', 'footer.php', 'functions.php', 'config.json' );

	    // create ZipArchive
	    $zip = new ZipArchive;
	    $zip->open( $tmpfile , ZipArchive::CREATE );
	    foreach ( $files_to_zip as $file ) {
	      $zip->addFile( $folder . $file, $template_id.'/'.$file );
	    }
	    $zip->close();	    

		header("Content-Description: File Transfer");
		header("Content-Disposition: attachment; filename=".$template_id.".zip");
		header("Content-Type: application/zip");
		header("Content-length: " . filesize( $tmpfile ) . "\n\n");
		header("Content-Transfer-Encoding: binary");
		// output data to the browser
		readfile( $tmpfile );

		// remove archive
		unlink( $tmpfile );

	}


	function ajax_wpl_get_tpl_css() {

		$tpl = $_REQUEST['tpl'];
		$templatesModel = new TemplatesModel( $tpl );
		$templatesModel->initTemplate();

		$css = $templatesModel->getCSS();
		$css = $templatesModel->processCustomFields( $css );
		
		header('Content-Type: text/css');
		echo $css;
		exit;
	}


	function ajax_wpl_get_copy_template_form() {

		// get template
		$template_id 			= urldecode( @$_REQUEST['template_id'] );
		if ( ! $template_id ) die('template not found.');
		$templatesModel 		= new TemplatesModel( $template_id );
		$item 					= $templatesModel->getItem();


		$aData = array(
			'item'						=> $item
		);

		$this->display( 'templates_copy_form', $aData );
		exit;

	}
	

	function ajax_wpl_duplicate_template() {

		// get template
		$template_id 			= urldecode( $this->getValueFromPost( 'template_id' ) );
		if ( ! $template_id ) die('template not found.');
		$templatesModel 		= new TemplatesModel( $template_id );
		$item 					= $templatesModel->getItem();

		// echo "<pre>";print_r($templatesModel);echo"</pre>";die();


		// set templates root folder
		$upload_dir = wp_upload_dir();
		$templates_dir = $upload_dir['basedir'].'/wp-lister/templates/';

		// check folder name
		$dirname = strtolower( sanitize_file_name( $this->getValueFromPost( 'template_name' ) ) );
		if ( $dirname == '') {
			echo( "Could not create template. No template name was provided." );	
			exit;				
		}
		
		// tpl_dir is the full path to the duplicated template
		$tpl_dir = $templates_dir . $dirname;

		// src_tpl_dir is the full path to the original template
		$src_tpl_dir = $templates_dir . $template_id;

		// if folder exists, append '-1', '-2', .. '-99'
		if ( is_dir( $tpl_dir ) ) {
			for ($i=1; $i < 100; $i++) { 
				$new_tpl_dir = $tpl_dir . '-' . $i;					
				if ( ! is_dir( $new_tpl_dir ) ) {
					$tpl_dir = $new_tpl_dir;
					break;
				}
			}
		}

		// make new folder
		$result  = @mkdir( $tpl_dir );

		// handle errors
		if ($result===false) {
			echo( "Could not create template folder: " . $tpl_dir );	
			exit;
		}


		// destination files
		$file_html					= $tpl_dir . '/template.html';
		$file_css					= $tpl_dir . '/style.css';
		$file_header				= $tpl_dir . '/header.php';
		$file_footer				= $tpl_dir . '/footer.php';
		$file_functions				= $tpl_dir . '/functions.php';
		$file_config				= $tpl_dir . '/config.json';
		
		$tpl_html	 				= $templatesModel->getHTML();
		$tpl_css	 				= $templatesModel->getCSS();
		$tpl_header	 				= $templatesModel->getHeader();
		$tpl_footer	 				= $templatesModel->getFooter();
		$tpl_functions	 			= $templatesModel->getFunctions();
		$tpl_config	 				= file_exists( $src_tpl_dir . '/config.json' ) ? file_get_contents( $src_tpl_dir . '/config.json') : false;
		
		$template_name 				= stripslashes( $this->getValueFromPost( 'template_name'  ) );
		$template_description 		= stripslashes( $this->getValueFromPost( 'template_description'  ) );
		$template_version 			= $item[ 'template_version' ];

		// add template header
		$header_css = "/* \n";
		$header_css .= "Template: $template_name\n";
		$header_css .= "Description: $template_description\n";
		$header_css .= "Version: $template_version\n";
		$header_css .= "*/\n";
		$tpl_css = $header_css . $tpl_css;

		// update template files
		$result = file_put_contents($file_css , $tpl_css);
		$result = file_put_contents($file_functions , $tpl_functions);
		$result = file_put_contents($file_footer , $tpl_footer);
		$result = file_put_contents($file_header , $tpl_header);
		$result = file_put_contents($file_html, $tpl_html);		
		if ( $tpl_config ) $result = file_put_contents($file_config, $tpl_config);

		// proper error handling
		if ($result===false) {
			echo( "There was a problem duplicating your template." );	
		} else {
			echo "success";
		}

		exit;

	}
	

}
