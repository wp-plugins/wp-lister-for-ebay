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
class LogTable extends WP_List_Table {

    const TABLENAME = 'ebay_log';
    var $total_items;

    /** ************************************************************************
     * REQUIRED. Set up a constructor that references the parent constructor. We 
     * use the parent reference to set some default configs.
     ***************************************************************************/
    function __construct(){
        global $status, $page;
                
        //Set parent defaults
        parent::__construct( array(
            'singular'  => 'log',     //singular name of the listed records
            'plural'    => 'logs',    //plural name of the listed records
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
            case 'timestamp':
                #return mysql2date( get_option('date_format'), $item[$column_name] );
            case 'callname':
            case 'ebay_id':
            case 'success':
                return $item[$column_name];
            case 'user':
                return $item['user_id'];
            default:
                return print_r($item,true); //Show the whole array for troubleshooting purposes
        }
    }

    function column_success($item){

        if ( $item['success'] == 'Success' ) {
            return '<span style="color:green">Success</span>';
        }

        if ( $item['success'] == 'Warning' ) {
            return '<span style="color:darkorange">Warning</span>';
        }

        if ( $item['success'] == 'Failure' ) {

            $details = '';
            if ( preg_match("/cURL error:(.*)/", $item['response'], $matches) ) {
                $LongMessage = $matches[1];
                $details .= ': <span style="color:#555">'.$LongMessage.' (cURL)</span>';
            }

            return '<span style="color:#B00">Failed</span>'.$details;
        }

        if ( $item['success'] == 'PartialFailure' ) {

            $details = '';
            if ( preg_match("/<LongMessage>(.*)<\/LongMessage>/", $item['response'], $matches) ) {
                $LongMessage = $matches[1];
                $details .= ': <span style="color:#555">'.$LongMessage.'</span>';
            }

            return '<span style="color:#B00">Partial Failure</span>'.$details;
        }

        return $item['success'];
    }    

    function column_user($item){
        if ( ! $item['user_id'] ) return '<i>cron</i>';
        $user_info = get_userdata($item['user_id']);
        if ( $user_info ) return $user_info->user_login;
        return false;
    }
        
    function column_callname($item){        
        //Build row action
        $link = sprintf('<a href="?page=%s&action=%s&log_id=%s&width=820&height=550" class="thickbox">%s</a>',$_REQUEST['page'],'display_log_entry',$item['id'],$item['callname']);

        if ( 'GeteBayDetails' == $item['callname'] ) {
            if ( preg_match("/<DetailName>(.*)<\/DetailName>/", $item['request'], $matches) ) {
                $match = str_replace('<![CDATA[', '', $matches[1] );
                $match = str_replace(']]>', '', $match );
                $link .= ' - ' . strip_tags( $match );
            }
        }

        if ( ( 'GetOrders' == $item['callname'] ) || ( 'GetSellerTransactions' == $item['callname'] ) ) {
            if ( preg_match("/<PageNumber>(.*)<\/PageNumber>/", $item['request'], $matches) ) {
                $match = str_replace('<![CDATA[', '', $matches[1] );
                $match = str_replace(']]>', '', $match );
                $link .= ' - Page ' . strip_tags( $match );
            }
            if ( preg_match("/<ModTimeFrom>(.*)<\/ModTimeFrom>/", $item['request'], $matches) ) {
                $match = str_replace('<![CDATA[', '', $matches[1] );
                $match = str_replace(']]>', '', $match );
                $link .= '<br>Since: ' . strip_tags( $match );
            }
            if ( preg_match("/<NumberOfDays>(.*)<\/NumberOfDays>/", $item['request'], $matches) ) {
                $match = str_replace('<![CDATA[', '', $matches[1] );
                $match = str_replace(']]>', '', $match );
                $link .= '<br>Days: ' . strip_tags( $match );
            }
        }

        if ( 'GetMyeBaySelling' == $item['callname'] ) {
            if ( preg_match("/<SoldList>(.*)<\/SoldList>/", $item['request'], $matches) ) {
                $link .= ' SoldList ';
            }
            if ( preg_match("/<UnsoldList>(.*)<\/UnsoldList>/", $item['request'], $matches) ) {
                $link .= ' UnsoldList ';
            }
            if ( preg_match("/<ActiveList>(.*)<\/ActiveList>/", $item['request'], $matches) ) {
                $link .= ' ActiveList ';
            }
            if ( preg_match("/<DurationInDays>(.*)<\/DurationInDays>/", $item['request'], $matches) ) {
                $match = str_replace('<![CDATA[', '', $matches[1] );
                $match = str_replace(']]>', '', $match );
                $link .= ' (' . strip_tags( $match ) . ' days) ';
            }
            if ( preg_match("/<PageNumber>(.*)<\/PageNumber>/", $item['request'], $matches) ) {
                $match = str_replace('<![CDATA[', '', $matches[1] );
                $match = str_replace(']]>', '', $match );
                $link .= ' - Page ' . strip_tags( $match );
            }
        }

        if ( 'CompleteSale' == $item['callname'] ) {
            if ( preg_match("/<OrderID>(.*)<\/OrderID>/", $item['request'], $matches) ) {
                $match = str_replace('<![CDATA[', '', $matches[1] );
                $match = str_replace(']]>', '', $match );
                $link .= ' - ' . strip_tags( $match );
            }
        }

        if ( 'GetCategories' == $item['callname'] ) {
            if ( preg_match("/<CategoryParent>(.*)<\/CategoryParent>/", $item['request'], $matches) ) {
                $match = str_replace('<![CDATA[', '', $matches[1] );
                $match = str_replace(']]>', '', $match );
                $link .= ' - ' . strip_tags( $match );
            }
        }

        if ( 'GetNotificationPreferences' == $item['callname'] ) {
            if ( preg_match("/<PreferenceLevel>(.*)<\/PreferenceLevel>/", $item['request'], $matches) ) {
                $match = str_replace('<![CDATA[', '', $matches[1] );
                $match = str_replace(']]>', '', $match );
                $link .= ' - ' . strip_tags( $match );
            }
        }

        if ( 'SetNotificationPreferences' == $item['callname'] ) {
            if ( preg_match("/<EventType>(.*)<\/EventType>/", $item['request'], $matches) ) {
                $link .= ' - ' . 'User Events';
            }
        }

        if ( 'GetMyMessages' == $item['callname'] ) {
            if ( preg_match("/<MessageID>(.*)<\/MessageID>/", $item['request'], $matches) ) {
                $match = str_replace('<![CDATA[', '', $matches[1] );
                $match = str_replace(']]>', '', $match );
                $link .= ' - ' . strip_tags( $match );
            }
            if ( preg_match("/<DetailLevel>(.*)<\/DetailLevel>/", $item['request'], $matches) ) {
                $match = str_replace('<![CDATA[', '', $matches[1] );
                $match = str_replace(']]>', '', $match );
                $link .= ' <span style="color:silver">' . strip_tags( $match ) . '</span>';
            }
        }

        if ( in_array( $item['callname'], array('GetCategorySpecifics','GetCategoryFeatures') ) ) {
            if ( preg_match("/<CategoryID>(.*)<\/CategoryID>/", $item['request'], $matches) ) {
                $match = str_replace('<![CDATA[', '', $matches[1] );
                $match = str_replace(']]>', '', $match );
                $category_id = strip_tags( $match );
                $link .= ' - ' . $category_id;
                $link .= '<br><span style="color: silver; font-size: small;">';
                $link .= EbayCategoriesModel::getFullEbayCategoryName( $category_id, $item['site_id'] );
                $link .= '</span>';
            }
        }

        if ( in_array( $item['callname'], array('wplister_revise_inventory_status','wplister_revise_item') ) ) {
            if ( is_numeric( $item['request'] ) ) {
                $link .= ' - ID: ' . $item['request'];
            }
        }

        // if ( preg_match("/<ShortMessage>(.*)<\/ShortMessage>/", $item['response'], $matches) ) {
        //     $ShortMessage = $matches[1];
        //     if ( $item['success'] == 'Warning' ) {
        //         $link .= '<br><span style="color:darkorange">Warning: '.$ShortMessage.'</span>';
        //     } else {
        //         $link .= '<br><span style="color:#B00">Error: '.$ShortMessage.'</span>';               
        //     }
        // }

        $link .= $this->displayErrors( $item['errors'] );
        $link .= $this->displayExtraMessage( $item );

        return $link;
    }

    function displayErrors( $errors ) {
        $html = '';
        foreach ( $errors as $err ) {

            $color_code = 'darkorange';
            if ( $err->SeverityCode == 'Error' ) $color_code = '#B00'; // errors are red

            $html .= '<div class="error_details" style="margin-top:.5em">';
            $html .= '<b style="color:'.$color_code.'">'.$err->SeverityCode.':</b> ';
            $html .= $err->ShortMessage . ' <br>';
            $html .= '<small>'.$err->LongMessage.' ('.$err->ErrorCode.')</small>';
            $html .= '</div>';
            
        }
        return $html;
    }    

    function displayExtraMessage( $item ) {
        $html = '';
        if ( 'GetMyMessages' == $item['callname'] ) return $html;
        
        // show extra <Message>
        if ( preg_match("/<Message>(.*)<\/Message>/Usm", $item['response'], $matches_msg) ) {
            $message = strip_tags( html_entity_decode( $matches_msg[1] ) );
            if ( strlen( $message ) > 100 ) {
                $message = html_entity_decode( $matches_msg[1] );
            }

            $color_code = '';
            $html .= '<div class="error_details" style="margin-top:.5em">';
            $html .= '<b style="color:'.$color_code.'">'.'Message'.':</b> ';
            $html .= $message . ' <br>';
            $html .= '</div>';
        }          
        return $html;
    }    


    function column_ebay_id($item) {

        // use ebay_id column if set
        if ( $item['ebay_id'] ) return $item['ebay_id'];

        // check for ItemID in request
        if ( preg_match("/<ItemID>(.*)<\/ItemID>/", $item['request'], $matches) ) {
            $match = str_replace('<![CDATA[', '', $matches[1] );
            $match = str_replace(']]>', '', $match );
            return $match;
        }

    }

    function column_account($item) {
        $account_title = isset( WPLE()->accounts[ $item['account_id'] ] ) ? WPLE()->accounts[ $item['account_id'] ]->title : '<span style="color:darkred">Invalid Account ID: '.$item['account_id'].'</span>';
        if ( ! $item['account_id'] ) $account_title = '&mdash;';
        return sprintf('%1$s <br><span style="color:silver">%2$s</span>',
            /*$1%s*/ $account_title,
            /*$2%s*/ EbayController::getEbaySiteCode( $item['site_id'] )
        );
    }

    function column_cb($item){
        return sprintf(
            '<input type="checkbox" name="%1$s[]" value="%2$s" />',
            /*$1%s*/ $this->_args['singular'],  //Let's simply repurpose the table's singular label ("listing")
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
            'timestamp'      	=> __('Date','wplister'),
            'callname'			=> __('Request','wplister'),
            'ebay_id'			=> __('Item ID','wplister'),
            'user'	     		=> __('User','wplister'),
            'account'           => __('Account','wplister'),
            'success'           => __('Status','wplister'),
        );
        // if ( ! WPLE()->multi_account ) unset( $columns['account'] );

        return $columns;
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
     * so you will need to create those manually in order for bulk actions to function.
     * 
     * @return array An associative array containing all the bulk actions: 'slugs'=>'Visible Titles'
     **************************************************************************/
    function get_bulk_actions() {
        $actions = array(
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
        
        //Detect when a bulk action is being triggered...
        if( 'delete'===$this->current_action() ) {
            #wp_die('Items deleted (or they would be if we had items to delete)!');
        }
        
    }

    // status filter links
    // http://wordpress.stackexchange.com/questions/56883/how-do-i-create-links-at-the-top-of-wp-list-table
    function get_views(){
       $views = array();
       $current = ( !empty($_REQUEST['log_status']) ? $_REQUEST['log_status'] : 'all');
       $base_url = esc_url_raw( remove_query_arg( array( 'action', 'log', 'log_status' ) ) );

       // get status summary
       $summary = $this->getStatusSummary();

       // All link
       $class = ($current == 'all' ? ' class="current"' :'');
       $all_url = remove_query_arg( 'log_status', $base_url );
       $views['all']  = "<a href='{$all_url }' {$class} >".__('All','wplister')."</a>";
       $views['all'] .= '<span class="count">('.$summary->all_status_count.')</span>';

       // Success link
       $Success_url = add_query_arg( 'log_status', 'Success', $base_url );
       $class = ($current == 'Success' ? ' class="current"' :'');
       $views['Success'] = "<a href='{$Success_url}' {$class} >".__('Successful','wplister')."</a>";
       if ( isset($summary->Success) ) $views['Success'] .= '<span class="count">('.$summary->Success.')</span>';

       // Warning link
       $Warning_url = add_query_arg( 'log_status', 'Warning', $base_url );
       $class = ($current == 'Warning' ? ' class="current"' :'');
       $views['Warning'] = "<a href='{$Warning_url}' {$class} >".__('Warnings','wplister')."</a>";
       if ( isset($summary->Warning) ) $views['Warning'] .= '<span class="count">('.$summary->Warning.')</span>';

       // Failure link
       $Failure_url = add_query_arg( 'log_status', 'Failure', $base_url );
       $class = ($current == 'Failure' ? ' class="current"' :'');
       $views['Failure'] = "<a href='{$Failure_url}' {$class} >".__('Failed','wplister')."</a>";
       if ( isset($summary->Failure) ) $views['Failure'] .= '<span class="count">('.$summary->Failure.')</span>';

       // PartialFailure link
       if ( isset($summary->PartialFailure) ) {
           $PartialFailure_url = add_query_arg( 'log_status', 'PartialFailure', $base_url );
           $class = ($current == 'PartialFailure' ? ' class="current"' :'');
           $views['PartialFailure'] = "<a href='{$PartialFailure_url}' {$class} >".__('Partial Failure','wplister')."</a>";
           $views['PartialFailure'] .= '<span class="count">('.$summary->PartialFailure.')</span>';       
       }

       // unknown link
       if ( isset($summary->unknown) ) {
           $unknown_url = add_query_arg( 'log_status', 'unknown', $base_url );
           $class = ($current == 'unknown' ? ' class="current"' :'');
           $views['unknown'] = "<a href='{$unknown_url}' {$class} >".__('Unknown','wplister')."</a>";
           $views['unknown'] .= '<span class="count">('.$summary->unknown.')</span>';       
       }

       return $views;
    }    
        
    function getStatusSummary() {
        global $wpdb;

        // check if MySQL server has gone away and reconnect if required - WP 3.9+
        if ( method_exists( $wpdb, 'check_connection') ) $wpdb->check_connection();

        // process search query
        $where_sql = " WHERE callname <> '' ";
        $where_sql = $this->add_searchquery_to_where_sql( $where_sql );

        $result = $wpdb->get_results("
            SELECT success as status, count(*) as total
            FROM {$wpdb->prefix}ebay_log
            $where_sql
            GROUP BY status
        ");

        $summary = new stdClass();
        $summary->all_status_count = 0;
        foreach ($result as $row) {
            $status = $row->status ? $row->status : 'unknown';
            $summary->$status = $row->total;
            $summary->all_status_count += $row->total;
        }

        return $summary;
    }


    function extra_tablenav( $which ) {
        if ( 'top' != $which ) return;
        $callname = ( isset($_REQUEST['callname']) ? $_REQUEST['callname'] : false);
        $wpl_callnames = array(
            'AddItem',
            'AddFixedPriceItem',
            'VerifyAddItem',
            'VerifyAddFixedPriceItem',
            'ReviseItem',
            'ReviseFixedPriceItem',
            'ReviseInventoryStatus',
            'EndItem',
            'EndFixedPriceItem',
            'RelistItem',
            'RelistFixedPriceItem',
            'GetItem',
            'GetOrders',
            'GetCategoryFeatures',
            'GetCategorySpecifics',
            'GetStore',
            'GetUser',
            'GetUserPreferences',
            'GeteBayDetails',
            'GetShippingDiscountProfiles',
            'CompleteSale',
        );
        $usertype = ( isset($_REQUEST['usertype']) ? $_REQUEST['usertype'] : false);
        $wpl_usertypes = array(
            'cron' => 'Background requests',
            'not_cron' => 'Manual requests',
        );
        ?>
        <div class="alignleft actions" style="">

            <select name="callname">
                <option value=""><?php _e('All requests','wplister') ?></option>
                <?php foreach ($wpl_callnames as $call) : ?>
                    <option value="<?php echo $call ?>"
                        <?php if ( $callname == $call ) echo 'selected'; ?>
                        ><?php echo $call ?></option>
                <?php endforeach; ?>
            </select>            

            <select name="usertype">
                <option value=""><?php _e('All users','wplister') ?></option>
                <?php foreach ($wpl_usertypes as $type => $label) : ?>
                    <option value="<?php echo $type ?>"
                        <?php if ( $usertype == $type ) echo 'selected'; ?>
                        ><?php echo $label ?></option>
                <?php endforeach; ?>
            </select>            

            <input type="submit" name="" id="post-query-submit" class="button" value="Filter">

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
    function prepare_items( $data = false ) {                
        
        // process bulk actions
        $this->process_bulk_action();
                        
        // get pagination state
        $current_page = $this->get_pagenum();
        $per_page = $this->get_items_per_page('logs_per_page', 20);
        
        // define columns
        $this->_column_headers = $this->get_column_info();
        
        // fetch logs
        $this->items = $this->getPageItems( $current_page, $per_page );
        $total_items = $this->total_items;

        // register our pagination options & calculations.
        $this->set_pagination_args( array(
            'total_items' => $total_items,
            'per_page'    => $per_page,
            'total_pages' => ceil($total_items/$per_page)
        ) );

    }


    function add_searchquery_to_where_sql( $where_sql ) {

        if ( isset( $_REQUEST['s'] ) && ( $_REQUEST['s'] ) ) {

            $query = esc_sql( $_REQUEST['s'] );

            // enable deep search by default - make sure not to miss anything
            // if ( isset( $_REQUEST['deep'] ) ) {
            if ( true ) {

                // deep: search full request and response
                $where_sql .= " 
                    AND ( 
                            ( request LIKE '%$query%' ) OR 
                            ( response LIKE '%$query%' ) 
                        )
                ";

            } else {

                // fast: search callname and ebay_id only
                $where_sql .= " 
                    AND ( 
                            ( callname = '$query' ) OR 
                            ( ebay_id = '$query' AND ebay_id > 0 ) 
                        )
                ";

            }

        }
        // echo "<pre>";print_r($where_sql);echo"</pre>";die();

        return $where_sql;
    }


    function getPageItems( $current_page, $per_page ) {
        global $wpdb;

        // $this->tablename = $wpdb->prefix . 'ebay_log';
        $table = $wpdb->prefix . self::TABLENAME;

        $orderby  = (!empty($_REQUEST['orderby'])) ? esc_sql( $_REQUEST['orderby'] ) : 'id';
        $order    = (!empty($_REQUEST['order']))   ? esc_sql( $_REQUEST['order']   ) : 'desc';
        $offset   = ( $current_page - 1 ) * $per_page;
        $per_page = esc_sql( $per_page );

        // handle filters
        // $where_sql = ' WHERE 1 = 1 ';
        $where_sql = " WHERE callname <> '' ";

        // search box
        $where_sql = $this->add_searchquery_to_where_sql( $where_sql );

        // views
        if ( isset( $_REQUEST['log_status'] ) ) {
            $status = esc_sql( $_REQUEST['log_status'] );
            if ( in_array( $status, array('Success','Warning','Failure','PartialFailure','unknown') ) ) {
                if ( $status == 'unknown' ) {
                    $where_sql .= " AND success IS NULL ";
                } else {
                    $where_sql .= " AND success = '$status' ";
                }
            }
        }

        // callname
        if ( isset( $_REQUEST['callname'] ) && $_REQUEST['callname'] ) {
            $callname = esc_sql( $_REQUEST['callname'] );
            $where_sql .= " AND callname = '$callname' ";
        }

        // usertype
        if ( isset( $_REQUEST['usertype'] ) && $_REQUEST['usertype'] ) {
            $usertype = esc_sql( $_REQUEST['usertype'] );
            if ( in_array( $usertype, array('cron','not_cron') ) ) {
                if ( $usertype == 'cron' ) {
                    $where_sql .= " AND ( user_id IS NULL OR user_id = '0' ) ";
                } else {
                    $where_sql .= " AND ( user_id IS NOT NULL AND user_id <> '0' ) ";
                }
            }
        }


        // get items
        $items = $wpdb->get_results("
            SELECT *
            FROM $table
            $where_sql
            ORDER BY $orderby $order
            LIMIT $offset, $per_page
        ", ARRAY_A);
        // echo "<pre>";print_r($wpdb->last_query);echo"</pre>";#die();
        
        // get total items count - if needed
        if ( ( $current_page == 1 ) && ( count( $items ) < $per_page ) ) {
            $this->total_items = count( $items );
        } else {
            $this->total_items = $wpdb->get_var("
                SELECT COUNT(*)
                FROM $table
                $where_sql
                ORDER BY $orderby $order
            ");         
        }

        return $items;
    } // getPageItems()

    
}
