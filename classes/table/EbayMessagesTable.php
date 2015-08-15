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
class EbayMessagesTable extends WP_List_Table {

    var $total_items;

    /** ************************************************************************
     * REQUIRED. Set up a constructor that references the parent constructor. We 
     * use the parent reference to set some default configs.
     ***************************************************************************/
    function __construct(){
        global $status, $page;
                
        //Set parent defaults
        parent::__construct( array(
            'singular'  => 'ebay_message',     //singular name of the listed records
            'plural'    => 'messages',    //plural name of the listed records
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
            case 'message_id':
            case 'subject':
            case 'sender':
            case 'item_id':
            case 'item_title':
            case 'flag_read':
            case 'status':
                return $item[$column_name];
            case 'received_date':
            case 'expiration_date':
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
    function column_details($item){
        
        //Build row actions
        $actions = array(
            'view_ebay_message_details' => sprintf('<a href="?page=%s&action=%s&ebay_message=%s&width=600&height=470" class="thickbox">%s</a>',$_REQUEST['page'],'view_ebay_message_details',$item['id'],__('Details','wplister')),
            'reply_on_ebay' => sprintf('<a href="%s" target="_blank">%s</a>',$item['response_url'],__('Reply on eBay','wplister')),
            // 'load_message' => sprintf('<a href="?page=%s&action=%s&ebay_message=%s">%s</a>',$_REQUEST['page'],'load_message',$item['id'],__('Create Message','wplister')),
        );

        if ( ! $item['response_url'] ) unset( $actions['reply_on_ebay'] );
        
        // item title
        $title = $item['subject'];
        if ( ! $item['flag_read'] ) {
            $title = '<b>'.$item['subject'].'</b>';
        }

        // append sender
        if ( $item['sender'] ) {
            $title .= ' <i style="color:silver">'.$item['sender'].'</i>';
        }

        //Return the title contents
        return sprintf('%1$s %2$s',
            /*$1%s*/ $title,
            /*$2%s*/ $this->row_actions($actions)
        );
    }

    function column_item_title($item){
        //Return buyer name and ID
        return sprintf('%1$s <br><a href="admin.php?page=wplister&s=%2$s" target="_blank" title="View item">%2$s</span>',
            /*$1%s*/ $item['item_title'] ? $item['item_title'] : '&mdash;',
            /*$2%s*/ $item['item_id']    ? $item['item_id']    : ''
        );
    }

    function column_flag_read($item){
        switch( $item['flag_read'] ){
            case 1:
                return 'R';
                break;
            default:
                return 'U';
        }
    }

    function column_CompleteStatus($item){

        switch( $item['CompleteStatus'] ){
            case 'Unread':
                $color = 'darkorange';
                $value = __('Unread','wplister');
				break;
            case 'Read':
                $color = 'green';
                $value = __('Read','wplister');
				break;
            default:
                $color = 'black';
                $value = $item['CompleteStatus'];
        }

        //Return the title contents
        return sprintf('<span style="color:%1$s">%2$s</span><br><span style="color:silver">%3$s</span>',
            /*$1%s*/ $color,
            /*$2%s*/ $value,
            /*$2%s*/ $item['CheckoutStatus']
        );
	}

    function column_account($item) {
        $account_title = isset( WPLE()->accounts[ $item['account_id'] ] ) ? WPLE()->accounts[ $item['account_id'] ]->title : 'NONE';
        return sprintf('%1$s <br><span style="color:silver">%2$s</span>',
            /*$1%s*/ $account_title,
            /*$2%s*/ EbayController::getEbaySiteCode( $item['site_id'] )
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
            'received_date'     => __('Received at','wplister'),
            // 'sender'            => __('Sender','wplister'),
            'details'           => __('Subject','wplister'),
            // 'subject'           => __('Subject','wplister'),
            // 'item_id'  			=> __('eBay ID','wplister'),
            'item_title'  		=> __('Product','wplister'),
            // 'flag_read'         => '&nbsp;', // __('Read','wplister'),
            // 'message_id'        => __('Message ID','wplister'),
            // 'status'		 	=> __('Status','wplister'),
            // 'expiration_date'	=> __('Expires at','wplister'),
            'account'           => __('Account','wplister'),
        );
        if ( ! WPLE()->multi_account ) unset( $columns['account'] );

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
    function get_sortable_columns() {
        $sortable_columns = array(
            'received_date'  	=> array('received_date',false),     //true means its already sorted
            'expiration_date' 	=> array('expiration_date',false)
        );
        return $sortable_columns;
    }
    
    
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
     * so you will need to create those manually in message for bulk actions to function.
     * 
     * @return array An associative array containing all the bulk actions: 'slugs'=>'Visible Titles'
     **************************************************************************/
    function get_bulk_actions() {
        $actions = array(
            'update' 	=> __('Update selected messages from eBay','wplister'),
            'delete'    => __('Delete selected messages','wplister')
        );

        // delete messages is only for developers
        if ( ! get_option('wplister_log_level') )
            unset( $actions['delete'] );

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

        if( 'update'===$this->current_action() ) {
			#echo "<br>verify handler<br>";			
        }
        
    }
    

    // status filter links
    // http://wordpress.stackexchange.com/questions/56883/how-do-i-create-links-at-the-top-of-wp-list-table
    function get_views(){
        $views    = array();
        $current  = ( !empty($_REQUEST['message_status']) ? $_REQUEST['message_status'] : 'all');
        $base_url = esc_url_raw( remove_query_arg( array( 'action', 'message', 'message_status' ) ) );

        // handle search query
        if ( isset($_REQUEST['s']) && $_REQUEST['s'] ) {
            $base_url = add_query_arg( 's', $_REQUEST['s'], $base_url );
        }

        // get message status summary
        $om = new EbayMessagesModel();
        $summary = $om->getStatusSummary();

        // All link
        $class = ($current == 'all' ? ' class="current"' :'');
        $all_url = remove_query_arg( 'message_status', $base_url );
        $views['all']  = "<a href='{$all_url }' {$class} >".__('All','wplister')."</a>";
        $views['all'] .= '<span class="count">('.$summary->total_items.')</span>';

        // Read link
        $Read_url = add_query_arg( 'message_status', 'Read', $base_url );
        $class = ($current == 'Read' ? ' class="current"' :'');
        $views['Read'] = "<a href='{$Read_url}' {$class} >".__('Read','wplister')."</a>";
        if ( isset($summary->Read) ) $views['Read'] .= '<span class="count">('.$summary->Read.')</span>';

        // Unread link
        $Unread_url = add_query_arg( 'message_status', 'Unread', $base_url );
        $class = ($current == 'Unread' ? ' class="current"' :'');
        $views['Unread'] = "<a href='{$Unread_url}' {$class} >".__('Unread','wplister')."</a>";
        if ( isset($summary->Unread) ) $views['Unread'] .= '<span class="count">('.$summary->Unread.')</span>';

        return $views;
    }    

    
    function extra_tablenav( $which ) {
        if ( 'top' != $which ) return;
        $account_id = ( isset($_REQUEST['account_id']) ? $_REQUEST['account_id'] : false);
        ?>
        <div class="alignleft actions" style="">

            <?php if ( WPLE()->multi_account ) : ?>

            <select name="account_id">
                <option value=""><?php _e('All accounts','wplister') ?></option>
                <?php foreach ( WPLE()->accounts as $account ) : ?>
                    <option value="<?php echo $account->id ?>"
                        <?php if ( $account_id == $account->id ) echo 'selected'; ?>
                        ><?php echo $account->title ?></option>
                <?php endforeach; ?>
            </select>            

            <input type="submit" name="" id="post-query-submit" class="button" value="Filter">

            <?php endif; ?>

        </div>
        <?php
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
        $per_page = $this->get_items_per_page('messages_per_page', 20);
        
        // define columns
        $this->_column_headers = $this->get_column_info();
        
        // fetch profiles from model
        $messagesModel = new EbayMessagesModel();
        $this->items = $messagesModel->getPageItems( $current_page, $per_page );
        $total_items = $messagesModel->total_items;

        // register our pagination options & calculations.
        $this->set_pagination_args( array(
            'total_items' => $total_items,
            'per_page'    => $per_page,
            'total_pages' => ceil($total_items/$per_page)
        ) );

    }
    
}

