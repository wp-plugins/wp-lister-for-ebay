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
 * Our theme for this list table is going to be templates.
 */
class CategoriesMapTable extends WP_List_Table {

    var $total_items;

    /** ************************************************************************
     * REQUIRED. Set up a constructor that references the parent constructor. We 
     * use the parent reference to set some default configs.
     ***************************************************************************/
    function __construct(){
        global $status, $page;
                
        //Set parent defaults
        parent::__construct( array(
            'singular'  => 'category',     //singular name of the listed records
            'plural'    => 'categories',    //plural name of the listed records
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
            case 'category':
                return $item['category'];
            default:
                return print_r($item,true); //Show the whole array for troubleshooting purposes
        }
    }
    
    function column_category( $item ) {
        $category_name = $item['category'];

        // indicate if category was imported from eBay
        $term_id = $item['term_id'];
        if ( $ebay_cat_id = get_woocommerce_term_meta( $term_id, '_ebay_category_id' ) ) {
            $full_cat_path = strip_tags( EbayCategoriesModel::getFullStoreCategoryName( $ebay_cat_id ) );
            $tooltip_msg   = 'This category has been imported from eBay.<br><br>Original eBay Store category:<br><b>'.$full_cat_path.'</b><br>(ID '.$ebay_cat_id.')';
            $img_url       = WPLISTER_URL . '/img/info.png';
            $category_name .= '&nbsp;<img src="'.$img_url.'" style="height:12px; padding:0;" class="tips" data-tip="'.$tooltip_msg.'"/>';
        }

        return $category_name;
    }
    
    function column_ebay_category( $item ) {
        $account_id = ( isset($_REQUEST['account_id']) ? $_REQUEST['account_id'] : get_option('wplister_default_account_id') );
        $site_id    = isset( WPLE()->accounts[ $account_id ] ) ? WPLE()->accounts[ $account_id ]->site_id : false;

        $id   = $item['term_id'];
        $name = $item['ebay_category_name'];
        $leaf = EbayCategoriesModel::getCategoryType( $item['ebay_category_id'], $site_id ) == 'leaf' ? true : false;
        $name = apply_filters( 'wplister_get_ebay_category_name', $name, $item['ebay_category_id'] );

        if ( $item['ebay_category_id'] && ! $name ) $name = '<span style="color:darkred;">' . __('Unknown category ID','wplister').': '.$item['ebay_category_id'] . '</span>';
        elseif ( $item['ebay_category_id'] && ! $leaf ) $name .= '<br><span style="color:darkred;">' . __('This is not a leaf category','wplister').'!</span>';

        $tpl  = '
        <div class="row-actions-wrapper" style="position:relative;">
            <p class="categorySelector" style="margin:0;">
                <input type="hidden" name="wpl_e2e_ebay_category_id['.$id.']"   id="ebay_category_id_'.$id.'"   value="' . $item['ebay_category_id'] .'" class="" />
                <!--
                <!input type="text"   name="wpl_e2e_ebay_category_name['.$id.']" id="ebay_category_name_'.$id.'" value="' . $item['ebay_category_name'] . '" class="text_input" disabled="true" style="width:35%"/>
                -->
                <span id="ebay_category_name_'.$id.'" class="text_input" >' . $name . '</span>
            </p>
            <span class="row-actions" id="sel_ebay_cat_id_'.$id.'" >
                <input type="button" class="button btn_select_category" value="' . __('select','wplister') . '" >
                <input type="button" class="button btn_remove_category" value="' . __('remove','wplister') . '" >
            </span>
        </div>
        ';

        return $tpl;
    }
        
     function column_store_category( $item ) {
        $account_id = ( isset($_REQUEST['account_id']) ? $_REQUEST['account_id'] : get_option('wplister_default_account_id') );

        $id   = $item['term_id'];
        $name = $item['store_category_name'];
        $leaf = EbayCategoriesModel::getStoreCategoryType( $item['store_category_id'], $account_id ) == 'leaf' ? true : false;
        $name = apply_filters( 'wplister_get_store_category_name', $name, $item['store_category_id'] );

        if ( $item['store_category_id'] && ! $name ) $name = '<span style="color:darkred;">' . __('Unknown category ID','wplister').': '.$item['store_category_id'] . '</span>';
        // elseif ( $item['store_category_id'] && ! $leaf ) $name .= '<br><span style="color:darkred;">' . __('This is not a leaf category','wplister').'!</span>';

        $tpl  = '
        <div class="row-actions-wrapper" style="position:relative;">
            <p class="categorySelector" style="margin:0;">
                <input type="hidden" name="wpl_e2e_store_category_id['.$id.']"   id="store_category_id_'.$id.'"   value="' . $item['store_category_id'] .'" class="" />
                <!--
                <!input type="text"   name="wpl_e2e_store_category_name['.$id.']" id="store_category_name_'.$id.'" value="' . $item['store_category_name'] . '" class="text_input" disabled="true" style="width:35%"/>
                -->
                <span id="store_category_name_'.$id.'" class="text_input" >' . $name . '</span>
            </p>
            <span class="row-actions" id="sel_store_cat_id_'.$id.'" >
                <input type="button" class="button btn_select_category" value="' . __('select','wplister') . '" >
                <input type="button" class="button btn_remove_category" value="' . __('remove','wplister') . '" >
            </span>
        </div>
        ';

        return $tpl;
    }


    function extra_tablenav( $which ) {
        if ( 'top' != $which ) return;
        $account_id = ( isset($_REQUEST['account_id']) ? $_REQUEST['account_id'] : get_option('wplister_default_account_id') );
        $selected_account = WPLE()->accounts[ $account_id ];
        ?>
        <div class="alignleft actions" style="">

            <?php if ( WPLE()->multi_account ) : ?>

                <select name="account_id">
                    <!-- <option value=""><?php _e('All accounts','wplister') ?></option> -->
                    <?php foreach ( WPLE()->accounts as $account ) : ?>
                        <option value="<?php echo $account->id ?>"
                            <?php if ( $account_id == $account->id ) echo 'selected'; ?>
                            ><?php echo $account->title ?></option>
                    <?php endforeach; ?>
                </select>            

                <input type="submit" name="select_account" id="post-query-submit" class="button" value="Select Account">

                <a href="#" data-site_id="<?php echo $selected_account->site_id ?>" data-account_id="<?php echo $selected_account->id ?>" class="btn_update_ebay_data_for_site button" style="vertical-align:bottom"><?php echo sprintf( __('Refresh categories for account %s','wplister'), $selected_account->title ) ?></a>

            <?php else : ?>

                <input type="hidden" name="account_id" value="<?php echo $account_id ?>">

                <a href="#" data-site_id="<?php echo $selected_account->site_id ?>" data-account_id="<?php echo $selected_account->id ?>" class="btn_update_ebay_data_for_site button" style="vertical-align:bottom"><?php echo sprintf( __('Refresh categories for account %s','wplister'), $selected_account->title ) ?></a>

            <?php endif; ?>

            &nbsp;
            <input type="submit" value="<?php echo __('Save changes','wplister') ?>" name="submit" class="button-primary">

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
            <div class="alignleft actions" style="display:none">
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
            // 'cb'             => '<input type="checkbox" />', //Render a checkbox instead of text
            'category'          => __('Local category','wplister'),
            'ebay_category'     => __('eBay category','wplister'),
            'store_category'    => __('eBay Store category','wplister')
        );
        return $columns;
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
     * @uses $this->set_pagination_args()
     **************************************************************************/
    function prepare_items() {
        
        $per_page = 1000;

        $this->_column_headers = $this->get_column_info();        
        // $this->items = $data;        
        // echo "<pre>";print_r($data);echo "</pre>";

        $total_items = count( $this->items );
        $this->set_pagination_args( array(
            'total_items' => $total_items,                  //WE have to calculate the total number of items
            'per_page'    => $per_page,                     //WE have to determine how many items to show on a page
            'total_pages' => ceil($total_items/$per_page)   //WE have to calculate the total number of pages
        ) );

    }
    
}



