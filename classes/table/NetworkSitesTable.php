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
class NetworkSitesTable extends WP_List_Table {

    var $total_items;

    /** ************************************************************************
     * REQUIRED. Set up a constructor that references the parent constructor. We 
     * use the parent reference to set some default configs.
     ***************************************************************************/
    function __construct(){
        global $status, $page;
                
        //Set parent defaults
        parent::__construct( array(
            'singular'  => 'site',     //singular name of the listed records
            'plural'    => 'sites',    //plural name of the listed records
            'ajax'      => false        //does this table support ajax?
        ) );
        
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
            case 'blog_id':
                return $item[$column_name];
            case 'LastTimeModified':
                // use date format from wp
                $date = mysql2date( get_option('date_format'), $item[$column_name] );
                $time = mysql2date( 'H:i', $item[$column_name] );
                return sprintf('%1$s <br><span style="color:silver">%2$s</span>', $date, $time );
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
    function column_blog_title($item){

        // get blog details
        $blog_details = get_blog_details( $item[ 'blog_id' ] );

        // switch to blog and load details about WP-Lister installation
        $this->fetchBlogDetails( $item['blog_id'] );

        
        //Build row actions
        $actions = array(
            // 'create_order' => sprintf('<a href="?page=%s&action=%s&site=%s">%s</a>',$_REQUEST['page'],'create_order',$item['id'],__('Create Order','wplister')),
            'dashboard'  => sprintf('<a href="%s/wp-admin/">%s</a>', $blog_details->siteurl, __('Dashboard','wplister') ),
            'settings'   => sprintf('<a href="%s/wp-admin/admin.php?page=wplister-settings">%s</a>', $blog_details->siteurl, __('Settings','wplister') ),
            // 'reinstall'  => sprintf('<a href="?page=%s&action=%s&site=%s">%s</a>',$_REQUEST['page'],'reinstall',$item['blog_id'],__('Re-Install','wplister')),
            'install'    => sprintf('<a href="?page=%s&action=%s&site=%s">%s</a>',$_REQUEST['page'],'install',$item['blog_id'],__('Install','wplister')),
            'uninstall'  => sprintf('<a href="?page=%s&action=%s&site=%s">%s</a>',$_REQUEST['page'],'uninstall',$item['blog_id'],__('Uninstall','wplister')),
            'activate'   => sprintf('<a href="?page=%s&action=%s&site=%s">%s</a>',$_REQUEST['page'],'activate',$item['blog_id'],__('Activate','wplister')),
            'deactivate' => sprintf('<a href="?page=%s&action=%s&site=%s">%s</a>',$_REQUEST['page'],'deactivate',$item['blog_id'],__('Deactivate','wplister')),
            // 'edit'      => sprintf('<a href="?page=%s&action=%s&site=%s">%s</a>',$_REQUEST['page'],'edit',$item['blog_id'],__('Edit','wplister')),
        );

        // echo "<pre>";print_r($blog_details);echo"</pre>";#die();
        if ( $this->blog->enabled == 'Y' ) {
            unset( $actions['activate'] );
            unset( $actions['reinstall'] );            
            unset( $actions['uninstall'] );            
            unset( $actions['install'] );            
        } elseif ( $this->blog->enabled == 'N' ) {
            unset( $actions['deactivate'] );            
            unset( $actions['settings'] );            
            unset( $actions['install'] );            
        } else {
            unset( $actions['deactivate'] );            
            unset( $actions['settings'] );            
            unset( $actions['uninstall'] );            
            unset( $actions['activate'] );            
        }


        //Return the title contents
        return sprintf('%1$s %2$s',
            /*$1%s*/ $blog_details->blogname,
            /*$2%s*/ $this->row_actions($actions)
        );
    }


    function fetchBlogDetails( $blog_id ){

        $this->blog = new stdClass();

        switch_to_blog( $blog_id );

        $this->blog->token   = get_option( 'wplister_ebay_token' );
        $this->blog->user    = get_option( 'wplister_ebay_token_userid' );
        $this->blog->dbver   = get_option( 'wplister_db_version' );
        $this->blog->enabled = get_option( 'wplister_is_enabled');
        $this->blog->setup   = get_option( 'wplister_setup_next_step' );


        // check template folder
        $uploads = wp_upload_dir();
        $this->blog->tpldir = $uploads['basedir'] . '/wp-lister/templates';

        restore_current_blog();

    }


    // function column_status($item){
    //     return $this->blog->enabled ? 'enabled' : 'disabled';
    // }

    function column_ebay_id($item){
        return $this->blog->user;
    }

    function column_db_version($item){
        return $this->blog->dbver;
    }

    function column_setup_step($item){
        if ( $this->blog->setup == '0' ) return 'OK';
        return 'Step ' . $this->blog->setup;
    }

    function column_tpl_dir($item){
        $msg = '';

        if ( is_dir( $this->blog->tpldir )) {
           // $msg .= 'OK: ' . $this->blog->tpldir;
           $msg .= 'OK';
        } else {
           $msg .= 'MISSING: ' . $this->blog->tpldir;            
        }

        // $msg .= '<br>';
        // if ( is_dir( dirname( $this->blog->tpldir ) )) {
        //    $msg .= 'OK: ' . dirname( $this->blog->tpldir ) ;
        // } else {
        //    $msg .= 'MISSING: ' . dirname( $this->blog->tpldir ) ;            
        // }
        return $msg;
    }


    function column_status($item){

        $status = $this->blog->enabled == 'Y' ? 'enabled' : 'disabled';

        switch( $this->blog->enabled ){
            case 'Y':
                $color = 'darkgreen';
                $value = __('enabled','wplister');
                break;
            case 'N':
                $color = 'darkred';
                $value = __('disabled','wplister');
                break;
            default:
                $color = 'gray';
                $value = __('not installed','wplister');
        }

        //Return the title contents
        return sprintf('<span style="color:%1$s">%2$s</span>',
            /*$1%s*/ $color,
            /*$2%s*/ $value
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
            /*$1%s*/ $this->_args['singular'],  //Let's simply repurpose the table's singular label ("profile")
            /*$2%s*/ $item['id']                //The value of the checkbox should be the record's id
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
            'cb'                => '<input type="checkbox" />', //Render a checkbox instead of text
            // 'blog_id'        => __('Site ID','wplister'),
            'blog_title'        => __('Title','wplister'),
            'status'            => __('Status','wplister'),
            'ebay_id'           => __('eBay ID','wplister'),
            'db_version'        => __('DB Version','wplister'),
            'setup_step'        => __('Setup','wplister'),
            'tpl_dir'           => __('Folder','wplister'),
            // 'LastTimeModified'  => __('Last change','wplister')
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
     * This method merely defines which columns should be sortable and makes them
     * clickable - it does not handle the actual sorting. You still need to detect
     * the ORDERBY and ORDER querystring variables within prepare_items() and sort
     * your data accordingly (usually by modifying your query).
     * 
     * @return array An associative array containing all the columns that should be sortable: 'slugs'=>array('data_values',bool)
     **************************************************************************/
    // function get_sortable_columns() {
    //     $sortable_columns = array(
    //         'blog_id'           => array('blog_id',false),     //true means its already sorted
    //         'LastTimeModified'  => array('LastTimeModified',false)
    //     );
    //     return $sortable_columns;
    // }
    
    
    /** ************************************************************************
     * Optional. If you need to include bulk actions in your list table, this is
     * the place to define them. Bulk actions are an associative array in the format
     * 'slug'=>'Visible Title'
     * 
     * If this method returns an empty value, no bulk action will be rendered. If
     * you specify any bulk actions, the bulk actions box will be rendered with
     * the table automatically on display().
     * 
     * Also note that list tables are not automatically wrapped in <form> elements,
     * so you will need to create those manually in order for bulk actions to function.
     * 
     * @return array An associative array containing all the bulk actions: 'slugs'=>'Visible Titles'
     **************************************************************************/
    function get_bulk_actions() {
        $actions = array(
            'activate'     => __('Activate','wplister'),
            'deactivate'   => __('Deactivate','wplister'),
            'update_db'    => __('Update DB','wplister'),
            // 'delete'    => __('Delete','wplister')
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
        
        if( 'update'===$this->current_action() ) {
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
    function prepare_items() {
        
        // process bulk actions
        $this->process_bulk_action();
                        
        // get pagination state
        $current_page = $this->get_pagenum();
        $per_page = $this->get_items_per_page('sites_per_page', 20);
        
        // define columns
        // $this->_column_headers = $this->get_column_info();
        
        // define columns
        $columns = $this->get_columns();
        $hidden = array();
        $sortable = array();
        $this->_column_headers = array($columns, $hidden, $sortable);
        
        // fetch sites
        // $blogs = get_blog_list( 0, 'all' );
        global $wpdb;
        $blogs = $wpdb->get_results( 
            "SELECT blog_id, path FROM {$wpdb->blogs} 
            WHERE site_id = '{$wpdb->siteid}' 
            /* AND blog_id != {$wpdb->blogid} */
            AND spam = '0' 
            AND deleted = '0' 
            AND archived = '0' 
            order by blog_id", ARRAY_A
        ); 

        // $this->items = $sitesModel->getPageItems( $current_page, $per_page );
        $this->items = $blogs;
        $total_items = count( $blogs );

        // register our pagination options & calculations.
        $this->set_pagination_args( array(
            'total_items' => $total_items,
            'per_page'    => $per_page,
            'total_pages' => ceil($total_items/$per_page)
        ) );

    }
    
}

