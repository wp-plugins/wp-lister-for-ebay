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
    
    function column_ebay_category( $item ) {

        $id   = $item['term_id'];
        $name = $item['ebay_category_name'];
        $leaf = EbayCategoriesModel::getCategoryType( $item['ebay_category_id'] ) == 'leaf' ? true : false;
        $name = apply_filters( 'wplister_get_ebay_category_name', $name, $item['ebay_category_id'] );

        if ( $item['ebay_category_id'] && ! $name ) $name = '<span style="color:darkred;">' . __('Unknown category ID','wplister').': '.$item['ebay_category_id'] . '</span>';
        elseif ( $item['ebay_category_id'] && ! $leaf ) $name .= '<br><span style="color:darkred;">' . __('This is not a leaf category','wplister').'!</span>';

        $tpl  = '
        <div class="row-actions-wrapper" style="position:relative;">
            <p class="categorySelector" style="margin:0;">
                <input type="hidden" name="wpl_e2e_ebay_category_id['.$id.']"   id="ebay_category_id_'.$id.'"   value="' . $item['ebay_category_id'] .'" class="" />
                <!input type="text"   name="wpl_e2e_ebay_category_name['.$id.']" id="ebay_category_name_'.$id.'" value="' . $item['ebay_category_name'] . '" class="text_input" disabled="true" style="width:35%"/>
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

        $id   = $item['term_id'];
        $name = $item['store_category_name'];
        $leaf = EbayCategoriesModel::getStoreCategoryType( $item['store_category_id'] ) == 'leaf' ? true : false;
        $name = apply_filters( 'wplister_get_store_category_name', $name, $item['store_category_id'] );

        if ( $item['store_category_id'] && ! $name ) $name = '<span style="color:darkred;">' . __('Unknown category ID','wplister').': '.$item['store_category_id'] . '</span>';
        elseif ( $item['store_category_id'] && ! $leaf ) $name .= '<br><span style="color:darkred;">' . __('This is not a leaf category','wplister').'!</span>';

        $tpl  = '
        <div class="row-actions-wrapper" style="position:relative;">
            <p class="categorySelector" style="margin:0;">
                <input type="hidden" name="wpl_e2e_store_category_id['.$id.']"   id="store_category_id_'.$id.'"   value="' . $item['store_category_id'] .'" class="" />
                <!input type="text"   name="wpl_e2e_store_category_name['.$id.']" id="store_category_name_'.$id.'" value="' . $item['store_category_name'] . '" class="text_input" disabled="true" style="width:35%"/>
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
            'category'          => __('Category','wplister'),
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
    function prepare_items( $data ) {
        
        $per_page = 1000;

        $this->_column_headers = $this->get_column_info();        
        $this->items = $data;        
        // echo "<pre>";print_r($data);echo "</pre>";

        $total_items = count( $this->items );
        $this->set_pagination_args( array(
            'total_items' => $total_items,                  //WE have to calculate the total number of items
            'per_page'    => $per_page,                     //WE have to determine how many items to show on a page
            'total_pages' => ceil($total_items/$per_page)   //WE have to calculate the total number of pages
        ) );

    }
    
}



