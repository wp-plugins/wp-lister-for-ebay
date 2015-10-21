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

    // var $last_product_id = 0;
    // var $last_product_variations = array();
    var $selectedItems = false;
    var $profiles = array();
    var $total_items;

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
        
        // get array of profile names - if installation has been completed
        $db_version = get_option('wplister_db_version', 0);
        if ( $db_version ) {
            $profilesModel = new ProfilesModel();
            $this->profiles = $profilesModel->getAllNames();
        } else {
            $this->profiles = array();
        }
    }

    function getProfileData( $item ) {
        $profile_data = ListingsModel::decodeObject( $item['profile_data'], true );
        return $profile_data;        
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
                return $this->number_format( $item[$column_name], 2 );
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
        if ( isset( $_REQUEST['paged'] ))           $page .= '&paged='.$_REQUEST['paged'];
        if ( isset( $_REQUEST['s'] ))               $page .= '&s=' . urlencode( $_REQUEST['s'] );
        if ( isset( $_REQUEST['listing_status'] ))  $page .= '&listing_status='.$_REQUEST['listing_status'];

        // handle preview target
        $preview_target = get_option( 'wplister_preview_in_new_tab' ) == 1 ? '_blank' : '_self';
        $preview_class  = get_option( 'wplister_preview_in_new_tab' ) == 1 ? '' : 'thickbox';

        //Build row actions
        $actions = array(
            'preview_auction' => sprintf('<a href="?page=%s&action=%s&auction=%s&width=820&height=550&TB_iframe=true" target="%s" class="%s">%s</a>',$page,'preview_auction',$item['id'],$preview_target,$preview_class,__('Preview','wplister')),
            'edit'            => sprintf('<a href="?page=%s&action=%s&auction=%s">%s</a>',$page,'edit',$item['id'],__('Edit','wplister')),
            'lock'            => sprintf('<a href="?page=%s&action=%s&auction=%s">%s</a>',$page,'lock',$item['id'],__('Lock','wplister')),
            'unlock'          => sprintf('<a href="?page=%s&action=%s&auction=%s">%s</a>',$page,'unlock',$item['id'],__('Unlock','wplister')),
            'verify'          => sprintf('<a href="?page=%s&action=%s&auction=%s">%s</a>',$page,'verify',$item['id'],__('Verify','wplister')),
            'publish2e'       => sprintf('<a href="?page=%s&action=%s&auction=%s">%s</a>',$page,'publish2e',$item['id'],__('Publish','wplister')),
            'open'            => sprintf('<a href="%s" target="_blank">%s</a>',$item['ViewItemURL'],__('View on eBay','wplister')),
            'revise'          => sprintf('<a href="?page=%s&action=%s&auction=%s">%s</a>',$page,'revise',$item['id'],__('Revise','wplister')),
            'end_item'        => sprintf('<a href="?page=%s&action=%s&auction=%s">%s</a>',$page,'end_item',$item['id'],__('End Listing','wplister')),
            #'open'           => sprintf('<a href="%s" target="_blank">%s</a>',$item['ViewItemURL'],__('Open in new tab','wplister')),
            'relist'          => sprintf('<a href="?page=%s&action=%s&auction=%s">%s</a>',$page,'relist',$item['id'],__('Relist','wplister')),
            'update'          => sprintf('<a href="?page=%s&action=%s&auction=%s">%s</a>',$page,'update',$item['id'],__('Update from eBay','wplister')),
            'delete'          => sprintf('<a href="?page=%s&action=%s&auction=%s">%s</a>',$page,'delete_listing',$item['id'],__('Delete','wplister')),
            'archive'         => sprintf('<a href="?page=%s&action=%s&auction=%s">%s</a>',$page,'archive',$item['id'],__('Archive','wplister')),
        );

        $profile_data  = $this->getProfileData( $item );
        $listing_title = $item['auction_title'];

        // limit item title to 80 characters
        if ( ListingsModel::mb_strlen($listing_title) > 80 ) $listing_title = ListingsModel::mb_substr( $listing_title, 0, 77 ) . '...';
        

        // make title link to products edit page
        $post_id = @$item['parent_id'] ? $item['parent_id'] : $item['post_id'];
        $listing_title = '<a class="product_title_link" href="post.php?post='.$post_id.'&action=edit">'.$listing_title.'</a>';

        // show single (split) variation indicator
        if ( @$item['parent_id'] > 0 ) {
            $tip_msg = 'This is a single split variation.';
            $img_url  = WPLISTER_URL . '/img/info.png';
            $listing_title .= '&nbsp;<img src="'.$img_url.'" style="height:11px; padding:0;" class="tips" data-tip="'.$tip_msg.'"/>&nbsp;';
        } 

        // show locked indicator
        if ( @$item['locked'] ) {
            $tip_msg = 'This listing is currently locked.<br>Only inventory changes and prices will be updated, other changes will be ignored.';
            $img_url  = WPLISTER_URL . '/img/lock-1.png';
            $listing_title .= '&nbsp;<img src="'.$img_url.'" style="height:11px; padding:0;" class="tips" data-tip="'.$tip_msg.'"/>&nbsp;';
        } 


        // show warning if GetItem seems to have failed
        $needs_update = false;
        if ( $item['ebay_id'] ) {
            if ( $item['ViewItemURL'] == '' || $item['details'] == '' ) {

                // add warning message
                $tip_msg = 'There seems to be something wrong with this listing. Please click the <i>Update from eBay</i> link below to fetch the current details from eBay.';
                $img_url  = WPLISTER_URL . '/img/error.gif';
                $listing_title .= '&nbsp;<img src="'.$img_url.'" style="height:12px; padding:0;" class="tips" data-tip="'.$tip_msg.'"/>&nbsp;';

                // remove View on eBay ink
                unset( $actions['open'] );
                $needs_update = true;
            }
        } 

        // hide View on eBay link when there is no ebay_id and no ViewItemURL
        if ( empty($item['ebay_id']) && empty($item['ViewItemURL']) ) unset( $actions['open'] );

        // show warning if WooCommerce product has been deleted
        if ( ! ProductWrapper::getProduct( $item['post_id'] ) && ($item['status'] != 'archived') ) {
            $tip_msg = 'This product has been deleted!<br>Please do <i>not</i> delete products - unless you plan to archive the listing as well.';
            $img_url  = WPLISTER_URL . '/img/error.gif';
            $listing_title .= '&nbsp;<img src="'.$img_url.'" style="height:12px; padding:0;" class="tips" data-tip="'.$tip_msg.'"/>&nbsp;';
            $listing_title = str_replace('product_title_link', 'missing_product_title_link', $listing_title);
        } 

        // add variations link
        $listing_title .= $this->generateVariationsHtmlLink( $item, $profile_data );

        // check if item is scheduled for auto relist
        $listing_title .= $this->generateAutoRelistInfo( $item, $profile_data );


        /*
        // show errors and warnings on published and prepared items
        if ( in_array( $item['status'], array( 'published','changed','prepared','verified' ) ) ) {

            $history = maybe_unserialize( $item['last_errors'] );
            $tips_errors   = array();
            $tips_warnings = array();
            if ( is_array( $history ) ) {
                foreach ($history['errors'] as $result) {
                    $tips_errors[] = '<b>'.$result->SeverityCode.':</b> '.$result->ShortMessage.' ('.$result->ErrorCode.')';
                }
                foreach ($history['warnings'] as $result) {
                    // hide redundant warnings like:
                    // 21917091 - Warning: Requested StartPrice and Quantity revision is redundant
                    // 21917092 - Warning: Requested Quantity revision is redundant.
                    // 21916620 - Warning: Variations with quantity '0' will be removed
                    if ( in_array( $result->ErrorCode, array( 21917091, 21917092, 21916620 ) ) ) continue;
                    $tips_warnings[] = '<b>'.$result->SeverityCode.':</b> '.$result->ShortMessage.' ('.$result->ErrorCode.')';
                }
            }
            if ( ! empty( $tips_errors ) ) {
                $listing_title .= '<br><small style="color:darkred">'.join('<br>',$tips_errors).'</small>';
            }
            if ( ! empty( $tips_warnings ) ) {
                $listing_title .= '<small><br><a href="#" onclick="jQuery(\'#warnings_container_'.$item['id'].'\').slideToggle();return false;">&raquo; '.''.sizeof($tips_warnings).' warning(s)'.'</a></small>';
                $listing_title .= '<div id="warnings_container_'.$item['id'].'" style="display:none">';
                $listing_title .= '<small>'.join('<br>',$tips_warnings).'</small>';
                $listing_title .= '</div>';
            }

        }
        */

        // disable some actions depending on status
        if ( $item['status'] != 'published' )   unset( $actions['lock'] );
        if ( $item['status'] != 'published' )   unset( $actions['end_item'] );
        if ( $item['status'] != 'prepared' )    unset( $actions['verify'] );
        if ( $item['status'] != 'changed' )     unset( $actions['revise'] );
        if (($item['status'] != 'prepared' ) &&
            ($item['status'] != 'verified'))    unset( $actions['publish2e'] );
        if (($item['status'] != 'published' ) &&
            ($item['status'] != 'changed') &&
            ($item['status'] != 'ended'))       unset( $actions['open'] );
        // if ( $item['status'] == 'ended' )       unset( $actions['preview_auction'] ); // uncomment for debugging
        if ( $item['status'] != 'ended' )       unset( $actions['archive'] );
        if ( $item['status'] != 'archived' )    unset( $actions['delete'] );
        if (($item['status'] != 'sold' ) &&
            ($item['status'] != 'ended'))       unset( $actions['relist'] );
        if (($item['status'] != 'relisted' ) && 
           ( $needs_update == false ) )         unset( $actions['update'] );

        if (   $item['locked'] )                unset( $actions['lock'] );
        if (   $item['locked'] )                unset( $actions['edit'] );
        if ( ! $item['locked'] )                unset( $actions['unlock'] );

        // make edit listing link only available to developers
        if ( ! get_option('wplister_enable_item_edit_link') ) {
            unset( $actions['edit'] );
            if ( $item['status'] == 'ended' )   unset( $actions['preview_auction'] ); // developer may preview ended items
        }

        if ( ! current_user_can( 'publish_ebay_listings' ) ) {
            unset( $actions['publish2e'] );
            unset( $actions['revise'] );
            unset( $actions['end_item'] );
            unset( $actions['relist'] );
            unset( $actions['delete'] );
        }

        //Return the title contents
        //return sprintf('%1$s <span style="color:silver">%2$s</span>%3$s',
        return sprintf('%1$s %2$s',
            /*$1%s*/ $listing_title,
            /*$2%s*/ //$item['profile_id'],
            /*$3%s*/ $this->row_actions($actions)
        );
    }

    function generateVariationsHtmlLink( $item, $profile_data ){
        $variations_html = ' ';

        // check for variations
        if ( ProductWrapper::hasVariations( $item['post_id'] ) ) {

            $variations = $this->getProductVariations( $item['post_id'] );

            // check variations cache
            $result = ListingsModel::matchCachedVariations( $item );
            if ( $result && $result->success ) {
                $variations = $result->variations;
            }

            // show warning if no variations found
            if ( ! is_array($variations) || ! sizeof($variations) ) {
                $img_url  = WPLISTER_URL . '/img/error.gif';
                $variations_html .= '(<a href="#" onClick="jQuery(\'#pvars_'.$item['id'].'\').toggle();return false;">&raquo;'.__('Variations','wplister').'</a>)<!br>';
                $variations_html .= '&nbsp;<img src="'.$img_url.'" style="height:12px; padding:0;"/>&nbsp;<br>';
                $variations_html .= '<b style="color:darkred">No variations found.</b><br>';
                $variations_html .= '<div id="pvars_'.$item['id'].'" class="variations_list" style="display:none;margin-bottom:10px;">';
                if ( ! defined('WPLISTER_RESELLER_VERSION') ) {
                    $variations_html .= 'Please read the <a href="http://www.wplab.com/plugins/wp-lister/faq/#Variations" target="_blank">FAQ</a> or contact support.';
                }
                $variations_html .= '</div>';
                return $variations_html;
            }

            // get max_quantity from profile
            $max_quantity = ( isset( $profile_data['details']['max_quantity'] ) && intval( $profile_data['details']['max_quantity'] )  > 0 ) ? $profile_data['details']['max_quantity'] : PHP_INT_MAX ; 

            // add Variations link and container
            $variations_html .= '(<a href="#TB_inline?width=600&inlineId=pvars_'.$item['id'].'" class="thickbox">&raquo;</a>';
            $variations_html .= '<a href="#" onClick="jQuery(\'#pvars_'.$item['id'].'\').toggle();return false;"> '.sizeof($variations).' '.__('Variations','wplister').'</a>)<br>';
            $variations_html .= '<div id="pvars_'.$item['id'].'" class="variations_list" style="display:none;margin-bottom:10px;">';

            // show variation mode message
            if ( isset( $profile_data['details']['variations_mode'] ) && ( $profile_data['details']['variations_mode'] == 'flat' ) ) {
                $variations_html .= '<p><b>' . __('These variations will be listed as a single item.','wplister') . '</b></p>';
            }

            $variations_html .= '<table class="variations_table" style="margin-bottom: 8px;">';

            // header
            $variations_html .= '<tr><th>';
            $variations_html .= '&nbsp;';
            $variations_html .= '</th><th>';
            $first_variation = reset( $variations );
            if ( is_array( $first_variation['variation_attributes'] ) ) {
                foreach ($first_variation['variation_attributes'] as $name => $value) {
                    $variations_html .= $name;
                    $variations_html .= '</th><th>';
                }
            }
            $variations_html .= __('SKU','wplister');
            $variations_html .= '</th><th align="right">';
            $variations_html .= __('Price','wplister');
            $variations_html .= '</th></tr>';

            foreach ($variations as $var) {

                // first column: quantity
                $variations_html .= '<tr><td align="right">';
                $variations_html .= min( $max_quantity, intval( $var['stock'] ) ) . '&nbsp;x';
                $variations_html .= '</td>';

                foreach ($var['variation_attributes'] as $name => $value) {
                    // $variations_html .= $name.': '.$value ;
                    $variations_html .= '<td>';
                    $variations_html .= $value ;
                    $variations_html .= '</td>';
                }
                // $variations_html .= '('.$var['sku'].') ';
                // $variations_html .= '('.$var['image'].') ';
                
                // column: SKU
                $variations_html .= '<td>';
                $variations_html .= $var['sku'] ? $var['sku'] : '<span style="color:darkred">SKU is missing!</span';
                $variations_html .= @$var['is_default'] ? '&nbsp;<span class="tips" data-tip="'.__('Default variation','wplister').'">*</span>' : '';
                $variations_html .= '</td>';

                // last column: price
                $variations_html .= '<td align="right">';
                $price = ListingsModel::applyProfilePrice( $var['price'], @$profile_data['details']['start_price'] );
                $variations_html .= $this->number_format( $price, 2 );

                $variations_html .= '</td></tr>';

            }
            $variations_html .= '</table>';

            // show variation mode message
            if ( isset( $profile_data['details']['variations_mode'] ) && ( $profile_data['details']['variations_mode'] == 'flat' ) ) {
                // $variations_html .= '<p><b>' . __('These variations will be listed as a single item.','wplister') . '</b></p>';
            } else {
    

            }

            // list addons
            $addons = ProductWrapper::getAddons( $item['post_id'] );
            if ( sizeof($addons) > 0 ) {
                $variations_html .= '<table style="margin-bottom: 8px;">';
                foreach ($addons as $addonGroup) {

                    // first column: quantity
                    $variations_html .= '<tr><td colspan="2" align="left"><b>';
                    $variations_html .= $addonGroup->name;
                    $variations_html .= '</b></td></tr>';

                    foreach ($addonGroup->options as $addon) {
                        $variations_html .= '<tr><td align="left">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
                        $variations_html .= $addon->name;
                        $variations_html .= '</td><td align="right">';
                        $variations_html .= $this->number_format( $addon->price, 2 );
                        $variations_html .= '</td></tr>';
                    }
                    
                }
                $variations_html .= '</table>';
            }

            $variations_html .= '</div>';
        }

        return $variations_html;

    } // generateVariationsHtmlLink()

    function generateAutoRelistInfo( $item, $profile_data ){
        $html = '';

        // check if item is currently scheduled to be auto-relisted
        if ( @$item['relist_date'] ) {
            $relist_date = $item['relist_date'];
            $relist_ts   = strtotime( $item['relist_date'] );
            $relist_time = mysql2date( get_option('time_format'), $relist_date );

            $time_diff = human_time_diff( $relist_ts, current_time('timestamp',1) ); 
            // $time_diff .= $relist_ts < current_time('timestamp',1) ? 'ago' : ''; 

            // if ( $relist_ts < time() ) {
            if ( $relist_ts < current_time('timestamp',1) ) {
                $html .= '<br><span style="color:darkred"><i>Scheduled to be relisted '.$time_diff.' ago ('.$relist_time.')</i></span>';
            } else {
                $html .= '<br><span style="color:inherit"><i>Scheduled to be relisted in '.$time_diff.' ('.$relist_time.')</i></span>';
            }

            $html .= '<br><a href="admin.php?page=wplister&action=relist&auction='.$item['id'].'" class="button button-small">'.'Relist Now'.'</a>';
            $html .= '&nbsp;<a href="admin.php?page=wplister&action=cancel_schedule&auction='.$item['id'].'" class="button button-small">'.'Cancel Schedule'.'</a>';

        }

        // check if autorelist is enabled in the applied profile
        $profile_details = $profile_data['details'];
        if ( @$profile_details['autorelist_enabled'] ) {

            // check relist condition
            if ( 'RelistAfterHours' == $profile_details['autorelist_condition'] ) {

                if ( ! @$profile_details['autorelist_after_hours'] ) return;
                $html .= '<br><span style="color:inherit; font-size:11px;"><i>AutoRelist enabled: relist after '.$profile_details['autorelist_after_hours'].' hours</i></span>';

            } elseif ( 'RelistAtTimeOfDay' == $profile_details['autorelist_condition'] ) {

                if ( ! @$profile_details['autorelist_at_timeofday'] ) return;
                $html .= '<br><span style="color:inherit; font-size:11px;"><i>AutoRelist enabled: relist at '.$profile_details['autorelist_at_timeofday'].'</i></span>';
                
            } else { // RelistImmediately

                $html .= '<br><span style="color:inherit; font-size:11px;"><i>AutoRelist enabled: relist immediately</i></span>';

            }

        } // if $profile_details['autorelist_enabled']

        return $html;
    } // generateAutoRelistInfo()



    /**
     * overwrite WP_List_Table::single_row to insert optional message row
     */
    public function single_row( $item ) {
        echo '<tr>';
        $this->single_row_columns( $item );
        $this->displayMessageRow( $item );
        echo '</tr>';
    }

    // show errors and warnings
    function displayMessageRow( $item ){
        $listing_title = '';

        // show errors and warnings on published and prepared items
        if ( in_array( $item['status'], array( 'published','changed','prepared','verified' ) ) ) {

            $history = maybe_unserialize( $item['last_errors'] );
            $tips_errors   = array();
            $tips_warnings = array();
            if ( is_array( $history ) ) {
                foreach ($history['errors'] as $result) {
                    $tips_errors[] = '<b>'.$result->SeverityCode.':</b> '.$result->ShortMessage.' ('.$result->ErrorCode.')';
                }
                foreach ($history['warnings'] as $result) {
                    // hide redundant warnings like:
                    // 21917091 - Warning: Requested StartPrice and Quantity revision is redundant
                    // 21917092 - Warning: Requested Quantity revision is redundant.
                    // 21916620 - Warning: Variations with quantity '0' will be removed
                    if ( in_array( $result->ErrorCode, array( 21917091, 21917092, 21916620 ) ) ) continue;
                    $tips_warnings[] = '<b>'.$result->SeverityCode.':</b> '.$result->ShortMessage.' ('.$result->ErrorCode.')';
                }
            }
            if ( ! empty( $tips_errors ) ) {
                $listing_title .= '<!br><small style="color:darkred">'.join('<br>',$tips_errors).'</small><br>';
            }
            if ( ! empty( $tips_warnings ) ) {
                $listing_title .= '<small><!br><a href="#" onclick="jQuery(\'#warnings_container_'.$item['id'].'\').slideToggle();return false;">&raquo; '.''.sizeof($tips_warnings).' warning(s)'.'</a></small><br>';
                $listing_title .= '<div id="warnings_container_'.$item['id'].'" style="display:none">';
                $listing_title .= '<small>'.join('<br>',$tips_warnings).'</small>';
                $listing_title .= '</div>';
            }

        }

        // show gallery warning on published items - since only published items have an updated details column
        if ( in_array( $item['status'], array( 'published' ) ) ) {

            // check PictureDetails.GalleryStatus
            $item_details = WPL_Model::decodeObject( $item['details'], false, true );
            if ( is_object( $item_details ) && is_object( $item_details->PictureDetails ) && $item_details->PictureDetails->getGalleryStatus() == 'ImageProcessingError' ) {
                $msg = '<b>eBay could not process your gallery image:</b><br>'.$item_details->PictureDetails->getGalleryErrorInfo();
                $listing_title .= '<small style="color:darkred">'.$msg.'</small><br>';
            }

        }

        if ( empty($listing_title) ) return;

        echo '</tr>';
        echo '<tr>';
        // echo '<td colspan="'.sizeof( $this->_column_headers[0] ).'">';
        echo '<td>&nbsp;</td>';
        echo '<td colspan="7">';
        echo $listing_title;
        echo '</td>';

    } // displayMessageRow()


    function column_img($item) {
        $post_id = $item['post_id'];
        if ( ! $post_id ) return '';

        $thumb = get_the_post_thumbnail( $post_id, 'thumbnail' );
        $link  = 'admin.php?page=wplister&action=preview_auction&auction='.$item['id'].'&width=820&height=550&TB_iframe=true';
        $thumb_link = '<a href="'.$link.'" class="thickbox">'.$thumb.'</a>';

        return $thumb_link;
    }
      
    function column_ebay_id($item) {

        // check for previous item IDs
        $history = maybe_unserialize( $item['history'] );
        if ( ! is_array($history)) $history = array();
        $previous_ids = isset($history['previous_ids']) && is_array($history['previous_ids']) ? $history['previous_ids'] : array();

        // if no previous ids, return 
        if ( empty( $previous_ids ) ) return $item['ebay_id'];

        // build previous ids html
        $html = '';
        $html .= '<a href="#" onClick="jQuery(\'#previds_'.$item['id'].'\').toggle();return false;" style="color:#555" title="Click to show previous eBay IDs">'.$item['ebay_id'].'</a><br>';
        $html .= '<div id="previds_'.$item['id'].'" class="variations_list" style="display:none;margin-bottom:10px;">';

        foreach ($previous_ids as $key => $id) {
            $color = 'silver';
            if ( isset($_POST['s']) && $_POST['s'] == $id ) $color = '#555';
            $html .= '<span style="color:'.$color.'">'.$id.'</span><br>';
        }
        $html .= '</div>';

        return $html;
    }
      
    function column_ebay_id_DISABLED($item) {

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

        // get profile details
        $profile_data = $this->getProfileData( $item );

        $quantity = $this->calculate_quantity( $item, $profile_data );
        $message = '';


        if ( $message ) $quantity .= '&nbsp;'.$message;

        return $quantity;
	}
	  
    function calculate_quantity( $item, $profile_data ) {
        
        // use profile quantity for flattened variations
        if ( isset( $profile_data['details']['variations_mode'] ) && ( $profile_data['details']['variations_mode'] == 'flat' ) ) {

            if ( $item['quantity_sold'] > 0 ) {
                $qty_available = $item['quantity'] - $item['quantity_sold'];
                $quantity = $qty_available . ' / ' . $item['quantity'];
                // prevent negative qty when sold or ended product has been updated in wc
                if ( $quantity < 0 ) $quantity = $item['quantity'];
            } else {
                $quantity = $item['quantity']; 
            }

            if ( $quantity )
                return $quantity;
        }


        // if item has variations count them...
        if ( ProductWrapper::hasVariations( $item['post_id'] ) ) {

            $variations = $this->getProductVariations( $item['post_id'] );

            $quantity = 0;
            foreach ($variations as $var) {
                $quantity += intval( $var['stock'] );
            }
            return $quantity;
        }

        // fetch latest quantity for changed items
        // if ( $item['status'] == 'changed' ) {
        //     $profile_data = maybe_unserialize( $item['profile_data'] );
        //     if ( intval($profile_data['details']['quantity']) == 0 ) {
        //         $latest_quantity = ProductWrapper::getStock( $item['post_id'] );
        //         $$item['quantity'] = $latest_quantity;
        //     }
        // }        

        // show sold items if there are any - except for changed items, which would possibly show negative values
        if ( $item['quantity_sold'] > 0 ) {
            $qty_available = $item['quantity'] - $item['quantity_sold'];
            return $qty_available . ' / ' . $item['quantity'];
        }

        return $item['quantity'];
    }
    
    function column_price($item){

        $display_price = $this->get_display_price( $item );
        $OriginalPrice = ListingsModel::thisListingHasPromotionalSale( $item );

        if ( $OriginalPrice = ListingsModel::thisListingHasPromotionalSale( $item ) ) {
            $OriginalPrice  = $this->number_format( $OriginalPrice, 2 );             
            $tip_msg        = '<b>Promotional sale is active.</b><br>Price and shipping will not be revised.';
            $img_url        = WPLISTER_URL . '/img/info.png';
            $display_price .= '<br>';
            $display_price .= '&nbsp;<img src="'.$img_url.'" style="height:11px; padding:0;" class="tips" data-tip="'.$tip_msg.'"/>';
            $display_price .= '&nbsp;<i><s>'.$OriginalPrice.'</s></i>';
        }

        return $display_price;
    }
    
    function get_display_price($item){
        
        // if item has variations check each price...
        if ( ProductWrapper::hasVariations( $item['post_id'] ) ) {

            // handle StartPrice on product level
            if ( $product_start_price = get_post_meta( $item['post_id'], '_ebay_start_price', true ) ) {
                return $this->number_format( $product_start_price, 2 );
            }

            $variations = $this->getProductVariations( $item['post_id'] );
            if ( ! is_array($variations) || ! sizeof($variations) ) return '';

            $price_min = 1000000; // one million should be a high enough ceiling
            $price_max = 0;
            foreach ($variations as $var) {
                $price = $var['price'];
                if ( $price > $price_max ) $price_max = $price;
                if ( $price < $price_min ) $price_min = $price;
            }

            // apply price modifiers
            $profile_data = $this->getProfileData( $item );
            $price_min = ListingsModel::applyProfilePrice( $price_min, @$profile_data['details']['start_price'] );
            $price_max = ListingsModel::applyProfilePrice( $price_max, @$profile_data['details']['start_price'] );

            // use lowest price for flattened variations
            if ( isset( $profile_data['details']['variations_mode'] ) && ( $profile_data['details']['variations_mode'] == 'flat' ) ) {
                return $this->number_format( $price_min, 2 );
            }


            if ( $price_min == $price_max ) {
                return $this->number_format( $price_min, 2 );
            } else {
                return $this->number_format( $price_min, 2 ) . ' - ' . $this->number_format( $price_max, 2 );
            }
        }

        // use price from ebay_auctions by default
        $start_price = $item['price'];

        // handle StartPrice on product level
        if ( $product_start_price = get_post_meta( $item['post_id'], '_ebay_start_price', true ) )
            $start_price  = $product_start_price;

        return $this->number_format( $start_price, 2 );
    }
    
    function column_end_date($item) {

        $profile_data = $this->getProfileData( $item );
        
        if ( $item['date_finished'] && in_array( $item['status'], array( 'ended', 'sold', 'archived' ) ) ) {
            $date  = $item['date_finished'];
            $value = mysql2date( get_option('date_format'), $date );
            $html  = '<span style="color:darkgreen">'.$value.'</span>';
        } elseif ( ( is_array($profile_data['details']) ) && ( 'GTC' == @$profile_data['details']['listing_duration'] ) ) {
            $value = 'GTC';
            $html  = '<span style="color:silver">'.$value.'</span>';
    	} else {
			$date  = $item['end_date'];
	    	$value = mysql2date( get_option('date_format'), $date );
			$html  = '<span>'.$value.'</span>';
    	}

        // indicate if OOSC is enabled
        if ( ListingsModel::thisListingUsesOutOfStockControl( $item ) ) {
            $tip_msg = 'The <i>Out Of Stock Control</i> option is enabled.<br><br>This listing will not be ended when it is out of stock.';
            $img_url = WPLISTER_URL . '/img/info.png';
            $html   .= '&nbsp;<img src="'.$img_url.'" style="height:11px; padding:0;" class="tips" data-tip="'.$tip_msg.'"/>&nbsp;';
        }

        return $html;
	}
	  
	
    function column_status($item){

        switch( $item['status'] ){
            case 'prepared':
                $color = 'orange';
                $value = __('prepared','wplister');
				break;
            case 'verified':
                $color = '#21759B';
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
            case 'archived':
                $color = '#777';
                $value = __('archived','wplister');
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
            case 'relisted':
                $color = 'purple';
                $value = __('relisted','wplister');
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

        if ( ! $profile_name ) {
            $profile_name = '<span style="color:red;">'. __('Profile missing','wplister') .'!</span>';
            if ( $item['profile_id'] ) $profile_name .= ' (' . $item['profile_id'] . ')';
            return $profile_name;
        }

        $edit_url = "admin.php?page=wplister-profiles&action=edit";
        $edit_url = add_query_arg( 'profile', $item['profile_id'], $edit_url );
        $edit_url = add_query_arg( 'return_to', 'listings', $edit_url );

        if ( isset($_REQUEST['s']) )                $edit_url = add_query_arg( 's', $_REQUEST['s'], $edit_url );
        if ( isset($_REQUEST['listing_status']) )   $edit_url = add_query_arg( 'listing_status', $_REQUEST['listing_status'], $edit_url );

        return sprintf(
            '<a href="%1$s" title="%2$s">%3$s</a>',
            /*$1%s*/ $edit_url,  
            /*$2%s*/ __('Edit','wplister'),  
            /*$3%s*/ $profile_name        
        );
    }
    
    function column_template($item){

        $template_id = basename( $item['template'] );
        $template_name = TemplatesModel::getNameFromCache( $template_id );

        if ( ! $template_name ) {
            $template_name = '<span style="color:red;">'. __('Template missing','wplister') .'!</span>';
            if ( $template_id ) $template_name .= ' (' . $template_id . ')';
            return $template_name;
        }

        $edit_url = "admin.php?page=wplister-templates&action=edit";
        $edit_url = add_query_arg( 'template', $item['template'], $edit_url );
        $edit_url = add_query_arg( 'return_to', 'listings', $edit_url );

        if ( isset($_REQUEST['s']) )                $edit_url = add_query_arg( 's', $_REQUEST['s'], $edit_url );
        if ( isset($_REQUEST['listing_status']) )   $edit_url = add_query_arg( 'listing_status', $_REQUEST['listing_status'], $edit_url );

        return sprintf(
            '<a href="%1$s" title="%2$s">%3$s</a>',
            /*$1%s*/ $edit_url,  
            /*$2%s*/ __('Edit','wplister'),  
            /*$3%s*/ $template_name        
        );
    }

    function column_account($item) {
        $account_title = isset( WPLE()->accounts[ $item['account_id'] ] ) ? WPLE()->accounts[ $item['account_id'] ]->title : '<span style="color:darkred">Invalid Account ID: '.$item['account_id'].'</span>';
        return sprintf('%1$s <br><span style="color:silver">%2$s</span>',
            /*$1%s*/ $account_title,
            /*$2%s*/ EbayController::getEbaySiteCode( $item['site_id'] )
        );
    }

    function column_sku($item){
        return get_post_meta( $item['post_id'], '_sku', true );
    }
    
    // optional column - can be activated by filter
    function column_weight($item){
        return ProductWrapper::getWeight( $item['post_id'] );
    }

   
    // get product variations - if possible from cache
    function getProductVariations( $post_id ) {

        return WPLE()->memcache->getShortProductVariations( $post_id );

        // update cache if required
        // if ( $this->last_product_id != $post_id ) {
        //     $this->last_product_variations = ProductWrapper::getVariations( $post_id );
        //     $this->last_product_id         = $post_id;
        // }

        // return $this->last_product_variations;
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
            'ebay_id'           => __('eBay ID','wplister'),
            'img'               => __('Image','wplister'),
            'auction_title' 	=> __('Title','wplister'),
            'sku'               => __('SKU','wplister'),
            'quantity'			=> __('Quantity','wplister'),
            'quantity_sold'		=> __('Sold','wplister'),
            'price'				=> __('Price','wplister'),
            'fees'              => __('Fees','wplister'),
            // 'weight'			=> __('Weight','wplister'),
            'date_published'	=> __('Created at','wplister'),
            'end_date'          => __('Ends at','wplister'),
            'profile'           => __('Profile','wplister'),
            'template'          => __('Template','wplister'),
            'account'           => __('Account','wplister'),
            'status'            => __('Status','wplister'),
        );

        // if ( ! WPLE()->multi_account ) 
        //     unset( $columns['account'] );

        if ( ! get_option( 'wplister_enable_thumbs_column' ) )
            unset( $columns['img'] );

        // usage:
        // add_filter( 'wplister_listing_columns', 'my_custom_wplister_columns' );
        // function my_custom_wplister_columns( $columns ) {
        //     $columns['weight'] = 'Weight';
        //     return $columns;
        // }
        $columns = apply_filters( 'wplister_listing_columns', $columns );

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
            'price'             => array('price',false),
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
            'verify'              => __('Verify with eBay','wplister'),
            'publish2e'           => __('Publish to eBay','wplister'),
            'update'              => __('Update details from eBay','wplister'),
            // 'reselect'            => __('Select another profile','wplister'),
            'wple_change_profile' => __('Select another profile','wplister'),
            'reapply'             => __('Re-apply profile','wplister'),
            'revise'              => __('Revise items','wplister'),
            'end_item'            => __('End listings','wplister'),
            'relist'              => __('Re-list ended items','wplister'),
            'lock'                => __('Lock listings','wplister'),
            'unlock'              => __('Unlock listings','wplister'),
            'archive'             => __('Move to archive','wplister'),
            'delete_listing'      => __('Delete listings','wplister'),
            'cancel_schedule'     => __('Cancel relist schedule','wplister'),
            'wple_reset_status'   => __('Reset ended items','wplister'),
            'wple_clear_eps_data' => __('Clear EPS cache','wplister'),
        );

        if ( ! current_user_can( 'publish_ebay_listings' ) ) {
            unset( $actions['publish2e'] );
            unset( $actions['revise'] );
            unset( $actions['end_item'] );
            unset( $actions['relist'] );
            unset( $actions['delete_listing'] );
        }

        if ( isset($_GET['listing_status']) && ( $_GET['listing_status'] == 'archived' ) ) {
            unset( $actions['archive'] );
        } else {
            unset( $actions['delete_listing'] );            
        }

        if ( ! isset($_GET['listing_status']) || ( $_GET['listing_status'] != 'autorelist' ) ) {
            unset( $actions['cancel_schedule'] );            
        }

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
        if( 'delete_listing'===$this->current_action() ) {
            #wp_die('Items deleted (or they would be if we had items to delete)!');
            #$wpdb->query("DELETE FROM {$wpdb->prefix}ebay_auctions WHERE id = ''",)
        }

        if( 'verify'===$this->current_action() ) {
			#echo "<br>verify handler<br>";			
        }
        
    }

    // status filter links
    // http://wordpress.stackexchange.com/questions/56883/how-do-i-create-links-at-the-top-of-wp-list-table
    function get_views(){
        $views    = array();
        $current  = ( !empty($_REQUEST['listing_status']) ? $_REQUEST['listing_status'] : 'all');
        $base_url = esc_url_raw( remove_query_arg( array( 'action', 'auction', 'listing_status' ) ) );

        // handle profile query
        if ( isset($_REQUEST['profile_id']) && $_REQUEST['profile_id'] ) {
            $base_url = add_query_arg( 'profile_id', $_REQUEST['profile_id'], $base_url );
        }
        // handle search query
        if ( isset($_REQUEST['s']) && $_REQUEST['s'] ) {
            $base_url = add_query_arg( 's', $_REQUEST['s'], $base_url );
        }

        // get listing status summary
        $summary = WPLE_ListingQueryHelper::getStatusSummary();

        // All link
        $class = ($current == 'all' ? ' class="current"' :'');
        $all_url = remove_query_arg( 'listing_status', $base_url );
        $views['all']  = "<a href='{$all_url }' {$class} >".__('All','wplister')."</a>";
        $views['all'] .= '<span class="count">('.$summary->total_items.')</span>';

        // prepared link
        $prepared_url = add_query_arg( 'listing_status', 'prepared', $base_url );
        $class = ($current == 'prepared' ? ' class="current"' :'');
        $views['prepared'] = "<a href='{$prepared_url}' {$class} >".__('Prepared','wplister')."</a>";
        if ( isset($summary->prepared) ) $views['prepared'] .= '<span class="count">('.$summary->prepared.')</span>';

        // verified link
        $verified_url = add_query_arg( 'listing_status', 'verified', $base_url );
        $class = ($current == 'verified' ? ' class="current"' :'');
        $views['verified'] = "<a href='{$verified_url}' {$class} >".__('Verified','wplister')."</a>";
        if ( isset($summary->verified) ) $views['verified'] .= '<span class="count">('.$summary->verified.')</span>';

        // published link
        $published_url = add_query_arg( 'listing_status', 'published', $base_url );
        $class = ($current == 'published' ? ' class="current"' :'');
        $views['published'] = "<a href='{$published_url}' {$class} >".__('Published','wplister')."</a>";
        if ( isset($summary->published) ) $views['published'] .= '<span class="count">('.$summary->published.')</span>';

        // changed link
        $changed_url = add_query_arg( 'listing_status', 'changed', $base_url );
        $class = ($current == 'changed' ? ' class="current"' :'');
        $views['changed'] = "<a href='{$changed_url}' {$class} >".__('Changed','wplister')."</a>";
        if ( isset($summary->changed) ) $views['changed'] .= '<span class="count">('.$summary->changed.')</span>';

        // ended link
        $ended_url = add_query_arg( 'listing_status', 'ended', $base_url );
        $class = ($current == 'ended' ? ' class="current"' :'');
        $views['ended'] = "<a href='{$ended_url}' {$class} >".__('Ended','wplister')."</a>";
        if ( isset($summary->ended) ) $views['ended'] .= '<span class="count">('.$summary->ended.')</span>';

        // archived link
        $archived_url = add_query_arg( 'listing_status', 'archived', $base_url );
        $class = ($current == 'archived' ? ' class="current"' :'');
        $views['archived'] = "<a href='{$archived_url}' {$class} >".__('Archived','wplister')."</a>";
        if ( isset($summary->archived) ) $views['archived'] .= '<span class="count">('.$summary->archived.')</span>';

        // sold link
        $sold_url = add_query_arg( 'listing_status', 'sold', $base_url );
        $class = ($current == 'sold' ? ' class="current"' :'');
        $views['sold'] = "<a href='{$sold_url}' {$class} >".__('Sold','wplister')."</a>";
        if ( isset($summary->sold) ) $views['sold'] .= '<span class="count">('.$summary->sold.')</span>';

        // relist link
        $sold_url = add_query_arg( 'listing_status', 'relist', $base_url );
        $class = ($current == 'relist' ? ' class="current"' :'');
        $views['relist'] = "<a href='{$sold_url}' {$class} title='Show ended listings which are in stock and can be relisted.'>".__('Relist','wplister')."</a>";
        if ( isset($summary->relist) ) $views['relist'] .= '<span class="count">('.$summary->relist.')</span>';

        // autorelist link
        if ( $summary->autorelist ) {
           $sold_url = add_query_arg( 'listing_status', 'autorelist', $base_url );
           $class = ($current == 'autorelist' ? ' class="current"' :'');
           $views['autorelist'] = "<a href='{$sold_url}' {$class} title='Show ended listings which are scheduled to be relisted.'>".__('Scheduled','wplister')."</a>";
           if ( isset($summary->autorelist) ) $views['autorelist'] .= '<span class="count">('.$summary->autorelist.')</span>';        
        }

        // locked link
        if ( $summary->locked ) {
           $sold_url = add_query_arg( 'listing_status', 'locked', $base_url );
           $class = ($current == 'locked' ? ' class="current"' :'');
           $views['locked'] = "<a href='{$sold_url}' {$class} title='Show locked listings'>".__('Locked','wplister')."</a>";
           if ( isset($summary->locked) ) $views['locked'] .= '<span class="count">('.$summary->locked.')</span>';        
        }

        return $views;
    }    

    function extra_tablenav( $which ) {
        if ( 'top' != $which ) return;
        $pm = new ProfilesModel();
        $wpl_profiles = $pm->getAll();
        $profile_id = ( isset($_REQUEST['profile_id']) ? $_REQUEST['profile_id'] : false);
        $account_id = ( isset($_REQUEST['account_id']) ? $_REQUEST['account_id'] : false);
        // echo "<pre>";print_r($wpl_profiles);echo"</pre>";die();
        ?>
        <div class="alignleft actions" style="">

            <select name="profile_id">
                <option value=""><?php _e('All profiles','wplister') ?></option>
                <?php foreach ($wpl_profiles as $profile) : ?>
                    <option value="<?php echo $profile['profile_id'] ?>"
                        <?php if ( $profile_id == $profile['profile_id'] ) echo 'selected'; ?>
                        ><?php echo $profile['profile_name'] ?></option>
                <?php endforeach; ?>
            </select>            

            <?php if ( WPLE()->multi_account ) : ?>

            <select name="account_id">
                <option value=""><?php _e('All accounts','wplister') ?></option>
                <?php foreach ( WPLE()->accounts as $account ) : ?>
                    <option value="<?php echo $account->id ?>"
                        <?php if ( $account_id == $account->id ) echo 'selected'; ?>
                        ><?php echo $account->title ?></option>
                <?php endforeach; ?>
            </select>            

            <?php endif; ?>

            <input type="submit" name="" id="post-query-submit" class="button" value="Filter">


            <!--
            <a class="btn_verify_all_prepared_items button wpl_job_button"
               title="<?php echo __('Verify all prepared items with eBay and get listing fees.','wplister') ?>"
                ><?php echo __('Verify all prepared items','wplister'); ?></a>
            -->

            <?php #if ( current_user_can( 'publish_ebay_listings' ) ) : ?>
            <!--
            <a class="btn_publish_all_verified_items button wpl_job_button"
               title="<?php echo __('Publish all verified items on eBay.','wplister') ?>"
                ><?php echo __('Publish all verified items','wplister'); ?></a>
            -->
            <?php #endif; ?>


        </div>
        <?php
    }

    /**
     * Generates the table navigation above or bellow the table and removes the
     * _wp_http_referer and _wpnonce because it generates a error about URL too large
     * 
     * @param string $which 
     * @return void
     */
    function display_tablenav( $which ) {
        ?>
        <div class="tablenav <?php echo esc_attr( $which ); ?>">
            <div class="alignleft actions">
                <?php $this->bulk_actions(); ?>
            </div>
            <?php
                $this->extra_tablenav( $which );
                $this->pagination( $which );
            ?>
            <br class="clear" />
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
        $per_page = $this->get_items_per_page('listings_per_page', 20);

        // regard max table rows limit
        if ( $max_per_page = get_option( 'wplister_force_table_items_limit' ) )
            $per_page = min( $per_page, $max_per_page );
        
        // define columns
        $this->_column_headers = $this->get_column_info();
        
        // fetch listings from model - if no selected products were found
        if ( ! $this->selectedItems ) {

            $result = WPLE_ListingQueryHelper::getPageItems( $current_page, $per_page );
            $this->items       = $result->items;
            $this->total_items = $result->total_items;

        } else {

            $this->items = $this->selectedItems;
            $this->total_items = count($this->selectedItems);

        }

        // register our pagination options & calculations.
        $this->set_pagination_args( array(
            'total_items' => $this->total_items,
            'per_page'    => $per_page,
            'total_pages' => ceil($this->total_items/$per_page)
        ) );

    }

    // small helper to make sure $price is not a string    
    function number_format( $price, $decimals = 2 ) {
        return number_format_i18n( floatval($price), $decimals );
    }
    
    
}

