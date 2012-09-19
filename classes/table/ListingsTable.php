<?php

/*************************** LOAD THE BASE CLASS *******************************
 *******************************************************************************
 * The WP_List_Table class isn't automatically available to plugins, so we need
 * to check if it's available and load it if necessary.
 */
if(!class_exists('WP_List_Table')){
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}




/************************** CREATE A PACKAGE CLASS *****************************
 *******************************************************************************
 * Create a new list table package that extends the core WP_List_Table class.
 * WP_List_Table contains most of the framework for generating the table, but we
 * need to define and override some methods so that our data can be displayed
 * exactly the way we need it to be.
 * 
 * To display this example on a page, you will first need to instantiate the class,
 * then call $yourInstance->prepare_items() to handle any data manipulation, then
 * finally call $yourInstance->display() to render the table to the page.
 * 
 * Our theme for this list table is going to be profiles.
 */
class ListingsTable extends WP_List_Table {

    /** ************************************************************************
     * REQUIRED. Set up a constructor that references the parent constructor. We 
     * use the parent reference to set some default configs.
     ***************************************************************************/
    function __construct(){
        global $status, $page;
                
        //Set parent defaults
        parent::__construct( array(
            'singular'  => 'auction',     //singular name of the listed records
            'plural'    => 'auctions',    //plural name of the listed records
            'ajax'      => false        //does this table support ajax?
        ) );
        
        // get array of profile names
        $profilesModel = new ProfilesModel();
        $this->profiles = $profilesModel->getAllNames();
    }
    
    
    /** ************************************************************************
     * Recommended. This method is called when the parent class can't find a method
     * specifically build for a given column. Generally, it's recommended to include
     * one method for each column you want to render, keeping your package class
     * neat and organized. For example, if the class needs to process a column
     * named 'title', it would first see if a method named $this->column_title() 
     * exists - if it does, that method will be used. If it doesn't, this one will
     * be used. Generally, you should try to use custom column methods as much as 
     * possible. 
     * 
     * Since we have defined a column_title() method later on, this method doesn't
     * need to concern itself with any column with a name of 'title'. Instead, it
     * needs to handle everything else.
     * 
     * For more detailed insight into how columns are handled, take a look at 
     * WP_List_Table::single_row_columns()
     * 
     * @param array $item A singular item (one full row's worth of data)
     * @param array $column_name The name/slug of the column to be processed
     * @return string Text or HTML to be placed inside the column <td>
     **************************************************************************/
    function column_default($item, $column_name){
        switch($column_name){
            case 'type':
            case 'quantity_sold':
            case 'ebay_id':
            case 'status':
                return $item[$column_name];
            case 'fees':
            case 'price':
                return number_format_i18n( $item[$column_name], 2 );
            case 'end_date':
            case 'date_published':
            	// use date format from wp
                return mysql2date( get_option('date_format'), $item[$column_name] );
            case 'template':
                return basename( $item['template'] );
            case 'profile':
                return isset($item['profile_id']) ? $this->profiles[ $item['profile_id'] ] : '';
            default:
                return print_r($item,true); //Show the whole array for troubleshooting purposes
        }
    }
    
        
    /** ************************************************************************
     * Recommended. This is a custom column method and is responsible for what
     * is rendered in any column with a name/slug of 'title'. Every time the class
     * needs to render a column, it first looks for a method named 
     * column_{$column_title} - if it exists, that method is run. If it doesn't
     * exist, column_default() is called instead.
     * 
     * This example also illustrates how to implement rollover actions. Actions
     * should be an associative array formatted as 'slug'=>'link html' - and you
     * will need to generate the URLs yourself. You could even ensure the links
     * 
     * 
     * @see WP_List_Table::::single_row_columns()
     * @param array $item A singular item (one full row's worth of data)
     * @return string Text to be placed inside the column <td> (profile title only)
     **************************************************************************/
    function column_auction_title($item){
        
        // get current page with paging as url param
        $page = $_REQUEST['page'];
        if ( isset( $_REQUEST['paged'] )) $page .= '&paged='.$_REQUEST['paged'];

        //Build row actions
        $actions = array(
            'preview_auction' => sprintf('<a href="?page=%s&action=%s&auction=%s&width=820&height=550" class="thickbox">%s</a>',$page,'preview_auction',$item['id'],__('Preview','wplister')),
            'edit'           => sprintf('<a href="?page=%s&action=%s&auction=%s">%s</a>',$page,'edit',$item['id'],__('Edit','wplister')),
            'verify'          => sprintf('<a href="?page=%s&action=%s&auction=%s">%s</a>',$page,'verify',$item['id'],__('Verify','wplister')),
            'publish2e'       => sprintf('<a href="?page=%s&action=%s&auction=%s">%s</a>',$page,'publish2e',$item['id'],__('Publish','wplister')),
            'open'            => sprintf('<a href="%s" target="_blank">%s</a>',$item['ViewItemURL'],__('View on eBay','wplister')),
            'revise'          => sprintf('<a href="?page=%s&action=%s&auction=%s">%s</a>',$page,'revise',$item['id'],__('Revise','wplister')),
            'end_item'        => sprintf('<a href="?page=%s&action=%s&auction=%s">%s</a>',$page,'end_item',$item['id'],__('End Listing','wplister')),
            #'update'         => sprintf('<a href="?page=%s&action=%s&auction=%s">%s</a>',$page,'update',$item['id'],__('Update','wplister')),
            #'open'           => sprintf('<a href="%s" target="_blank">%s</a>',$item['ViewItemURL'],__('Open in new tab','wplister')),
            #'delete'         => sprintf('<a href="?page=%s&action=%s&auction=%s">%s</a>',$page,'delete',$item['id'],__('Delete','wplister')),
        );

        $profile_data = maybe_unserialize( $item['profile_data'] );
        
        // show variations
        if ( ProductWrapper::hasVariations( $item['post_id'] ) ) {
            $item['auction_title'] .= ' (<a href="#" onClick="jQuery(\'#pvars_'.$item['id'].'\').toggle();return false;">&raquo;Variations</a>)<br>';
            // $item['auction_title'] .= '<pre>'.print_r( ProductWrapper::getVariations( $item['post_id'] ), 1 )."</pre>";
            $variations = ProductWrapper::getVariations( $item['post_id'] );

            $listingsModel = new ListingsModel();

            $variations_html = '<div id="pvars_'.$item['id'].'" class="variations_list" style="display:none;margin-bottom:10px;">';
            $variations_html .= '<table style="margin-bottom: 8px;">';
            foreach ($variations as $var) {

                // first column: quantity
                $variations_html .= '<tr><td align="right">';
                $variations_html .= intval( $var['stock'] ) . '&nbsp;x';
                $variations_html .= '</td><td>';

                foreach ($var['variation_attributes'] as $name => $value) {
                    $variations_html .= $name.': '.$value ;
                    $variations_html .= '</td><td>';
                }
                // $variations_html .= '('.$var['sku'].') ';
                // $variations_html .= '('.$var['image'].') ';
                
                // last column: price
                $price = $listingsModel->applyProfilePrice( $var['price'], $profile_data['details']['start_price'] );
                $variations_html .= number_format_i18n( $price, 2 );

                $variations_html .= '</td></tr>';

            }
            $variations_html .= '</table>';


            $variations_html .= '</div>';
            $item['auction_title'] .= $variations_html;
        }

        // disable some actions depending on status
        if ( $item['status'] != 'published' ) unset( $actions['end_item'] );
        if ( $item['status'] != 'prepared' ) unset( $actions['verify'] );
        if ( $item['status'] != 'changed' ) unset( $actions['revise'] );
        if (($item['status'] != 'prepared' ) &&
            ($item['status'] != 'verified')) unset( $actions['publish2e'] );
        if (($item['status'] != 'published' ) &&
            ($item['status'] != 'changed') &&
            ($item['status'] != 'ended')) unset( $actions['open'] );
        if ( $item['status'] == 'ended' ) unset( $actions['edit'] );
        if ( $item['status'] == 'ended' ) unset( $actions['preview_auction'] );

        //Return the title contents
        //return sprintf('%1$s <span style="color:silver">%2$s</span>%3$s',
        return sprintf('%1$s %2$s',
            /*$1%s*/ $item['auction_title'],
            /*$2%s*/ //$item['profile_id'],
            /*$3%s*/ $this->row_actions($actions)
        );
    }

    function column_ebay_id_DISABLED($item){

        //Build row actions
        #if ( intval($item['ebay_id']) > 0)
        if ( trim($item['ViewItemURL']) != '')
        $actions = array(
            'open' 		=> sprintf('<a href="%s" target="_blank">%s</a>',$item['ViewItemURL'],__('View on eBay','wplister')),
        );
        
        //Return the title contents
        return sprintf('%1$s %2$s',
            /*$1%s*/ $item['ebay_id'],
            /*$2%s*/ $this->row_actions($actions)
        );
	}
	  
    function column_quantity($item){
        
        // if item has variations count them...
        if ( ProductWrapper::hasVariations( $item['post_id'] ) ) {

            $variations = ProductWrapper::getVariations( $item['post_id'] );

            $quantity = 0;
            foreach ($variations as $var) {
                $quantity += intval( $var['stock'] );
            }
            return $quantity;
        }

        return $item['quantity'];
    }
    
    function column_price($item){
        
        // if item has variations check each price...
        if ( ProductWrapper::hasVariations( $item['post_id'] ) ) {

            $variations = ProductWrapper::getVariations( $item['post_id'] );

            $price_min = 1000000; // one million should be a high enough ceiling
            $price_max = 0;
            foreach ($variations as $var) {
                $price = $var['price'];
                if ( $price > $price_max ) $price_max = $price;
                if ( $price < $price_min ) $price_min = $price;
            }

            // apply price modifiers
            $listingsModel = new ListingsModel();
            $profile_data = maybe_unserialize( $item['profile_data'] );
            $price_min = $listingsModel->applyProfilePrice( $price_min, $profile_data['details']['start_price'] );
            $price_max = $listingsModel->applyProfilePrice( $price_max, $profile_data['details']['start_price'] );

            if ( $price_min == $price_max ) {
                return number_format_i18n( $price_min, 2 );
            } else {
                return number_format_i18n( $price_min, 2 ) . ' - ' . number_format_i18n( $price_max, 2 );
            }
        }

        return number_format_i18n( $item['price'], 2 );
    }
    
    function column_end_date($item) {

    	if ( $item['date_finished'] ) {
	        $date = $item['date_finished'];
	    	$value = mysql2date( get_option('date_format'), $date );
			return '<span style="color:darkgreen">'.$value.'</span>';
    	} else {
			$date = $item['end_date'];
	    	$value = mysql2date( get_option('date_format'), $date );
			return '<span>'.$value.'</span>';
    	}

	}
	  
	
    function column_status($item){

        switch( $item['status'] ){
            case 'prepared':
                $color = 'orange';
                $value = __('prepared','wplister');
				break;
            case 'verified':
                $color = 'green';
                $value = __('verified','wplister');
				break;
            case 'published':
                $color = 'darkgreen';
                $value = __('published','wplister');
				break;
            case 'sold':
                $color = 'black';
                $value = __('sold','wplister');
                break;
            case 'ended':
                $color = '#777';
                $value = __('ended','wplister');
                break;
            case 'imported':
                $color = 'orange';
                $value = __('imported','wplister');
				break;
            case 'selected':
                $color = 'orange';
                $value = __('selected','wplister');
                break;
            case 'changed':
                $color = 'purple';
                $value = __('changed','wplister');
                break;
            default:
                $color = 'black';
                $value = $item['status'];
        }

        //Return the title contents
        return sprintf('<mark style="background-color:%1$s">%2$s</mark>',
            /*$1%s*/ $color,
            /*$2%s*/ $value
        );
	}
	  
    function column_profile($item){

        $profile_name = @$this->profiles[ $item['profile_id'] ];

        return sprintf(
            '<a href="admin.php?page=wplister-profiles&action=edit&profile=%1$s" title="%2$s">%3$s</a>',
            /*$1%s*/ $item['profile_id'],  
            /*$2%s*/ __('Edit','wplister'),  
            /*$3%s*/ $profile_name        
        );
    }
    
    function column_template($item){

        $template_id = basename( $item['template'] );
        $template_name = TemplatesModel::getNameFromCache( $template_id );

        return sprintf(
            '<a href="admin.php?page=wplister-templates&action=edit&template=%1$s" title="%2$s">%3$s</a>',
            /*$1%s*/ $template_id,  
            /*$2%s*/ __('Edit','wplister'),  
            /*$3%s*/ $template_name        
        );
    }
    
    /** ************************************************************************
     * REQUIRED if displaying checkboxes or using bulk actions! The 'cb' column
     * is given special treatment when columns are processed. It ALWAYS needs to
     * have it's own method.
     * 
     * @see WP_List_Table::::single_row_columns()
     * @param array $item A singular item (one full row's worth of data)
     * @return string Text to be placed inside the column <td> (profile title only)
     **************************************************************************/
    function column_cb($item){
        return sprintf(
            '<input type="checkbox" name="%1$s[]" value="%2$s" />',
            /*$1%s*/ $this->_args['singular'],  //Let's simply repurpose the table's singular label ("listing")
            /*$2%s*/ $item['id']       			//The value of the checkbox should be the record's id
        );
    }
    
    
    /** ************************************************************************
     * REQUIRED! This method dictates the table's columns and titles. This should
     * return an array where the key is the column slug (and class) and the value 
     * is the column's title text. If you need a checkbox for bulk actions, refer
     * to the $columns array below.
     * 
     * The 'cb' column is treated differently than the rest. If including a checkbox
     * column in your table you must create a column_cb() method. If you don't need
     * bulk actions or checkboxes, simply leave the 'cb' entry out of your array.
     * 
     * @see WP_List_Table::::single_row_columns()
     * @return array An associative array containing column information: 'slugs'=>'Visible Titles'
     **************************************************************************/
    function get_columns(){
        $columns = array(
            'cb'        		=> '<input type="checkbox" />', //Render a checkbox instead of text
            'ebay_id'  			=> __('eBay ID','wplister'),
            'auction_title' 	=> __('Title','wplister'),
            'quantity'			=> __('Quantity','wplister'),
            'quantity_sold'		=> __('Sold','wplister'),
            'price'				=> __('Price','wplister'),
            'fees'				=> __('Fees','wplister'),
            'date_published'	=> __('Created at','wplister'),
            'end_date'          => __('Ends at','wplister'),
            'profile'           => __('Profile','wplister'),
            'template'          => __('Template','wplister'),
            'status'		 	=> __('Status','wplister')
        );
        return $columns;
    }
    
    /** ************************************************************************
     * Optional. If you want one or more columns to be sortable (ASC/DESC toggle), 
     * you will need to register it here. This should return an array where the 
     * key is the column that needs to be sortable, and the value is db column to 
     * sort by. Often, the key and value will be the same, but this is not always
     * the case (as the value is a column name from the database, not the list table).
     * 
     * @return array An associative array containing all the columns that should be sortable: 'slugs'=>array('data_values',bool)
     **************************************************************************/
    function get_sortable_columns() {
        $sortable_columns = array(
            'date_published'  	=> array('date_published',false),     //true means its already sorted
            'end_date'  		=> array('end_date',false),
            'auction_title'     => array('auction_title',false),
            'status'            => array('status',false)
        );
        return $sortable_columns;
    }
    
    
    /** ************************************************************************
     * Optional. If you need to include bulk actions in your list table, this is
     * the place to define them. Bulk actions are an associative array in the format
     * 'slug'=>'Visible Title'
     * 
     * Also note that list tables are not automatically wrapped in <form> elements,
     * so you will need to create those manually in order for bulk actions to function.
     * 
     * @return array An associative array containing all the bulk actions: 'slugs'=>'Visible Titles'
     **************************************************************************/
    function get_bulk_actions() {
        $actions = array(
            'verify'    => __('Verify with eBay','wplister'),
            'publish2e' => __('Publish to eBay','wplister'),
            'update' 	=> __('Update details from eBay','wplister'),
            'reselect'  => __('Select another profile','wplister'),
            'reapply'   => __('Re-apply profile','wplister'),
            'revise'    => __('Revise items','wplister'),
            'end_item'  => __('End auctions prematurely','wplister'),
            'delete'    => __('Delete','wplister')
        );
        return $actions;
    }
    
    
    /** ************************************************************************
     * Optional. You can handle your bulk actions anywhere or anyhow you prefer.
     * For this example package, we will handle it in the class to keep things
     * clean and organized.
     * 
     * @see $this->prepare_items()
     **************************************************************************/
    function process_bulk_action() {
        global $wbdb;
        
        //Detect when a bulk action is being triggered...
        if( 'delete'===$this->current_action() ) {
            #wp_die('Items deleted (or they would be if we had items to delete)!');
            #$wpdb->query("DELETE FROM {$wpdb->prefix}ebay_auctions WHERE id = ''",)
        }

        if( 'verify'===$this->current_action() ) {
			#echo "<br>verify handler<br>";			
        }
        
    }
    
    
    /** ************************************************************************
     * REQUIRED! This is where you prepare your data for display. This method will
     * usually be used to query the database, sort and filter the data, and generally
     * get it ready to be displayed. At a minimum, we should set $this->items and
     * $this->set_pagination_args(), although the following properties and methods
     * are frequently interacted with here...
     * 
     * @uses $this->_column_headers
     * @uses $this->items
     * @uses $this->get_columns()
     * @uses $this->get_sortable_columns()
     * @uses $this->get_pagenum()
     * @uses $this->set_pagination_args()
     **************************************************************************/
    function prepare_items( $items = false ) {
        
        // process bulk actions
        $this->process_bulk_action();
                        
        // get pagination state
        $current_page = $this->get_pagenum();
        $per_page = $this->get_items_per_page('listings_per_page', 20);
        
        // define columns
        $this->_column_headers = $this->get_column_info();
        
        // fetch listings from model - if no parameter passed
        if ( ! $items ) {

            $listingsModel = new ListingsModel();
            $this->items = $listingsModel->getPageItems( $current_page, $per_page );
            $total_items = $listingsModel->total_items;

        } else {

            $this->items = $items;
            $total_items = count($items);

        }

        // register our pagination options & calculations.
        $this->set_pagination_args( array(
            'total_items' => $total_items,
            'per_page'    => $per_page,
            'total_pages' => ceil($total_items/$per_page)
        ) );

    }
    
}

