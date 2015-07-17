
// init namespace
if ( typeof WPLE != 'object') var WPLE = {};


// revealing module pattern
WPLE.ProfileSelector = function () {
    
    // this will be a private property
    var self = {};
    var item_ids = [];
    var select_mode = '';
    
    // this will be a public method
    var init = function () {
        self = this; // assign reference to current object to "self"
    
        // jobs window "close" button
        jQuery('#wple_profile_selector_window .btn_close').click( function(event) {
            tb_remove();                    
        }).hide();

    }

    var select = function ( obj, profile_id ) {

        // console.log( 'selecting #'+profile_id+' to ASIN '+asin );
        // console.log( obj );

        // load task list
        var params = {
            action: 'wple_select_profile',
            profile_id: profile_id,
            product_ids: item_ids,
            select_mode: window.wple_select_mode,
            nonce: 'TODO'
        };
        // var jqxhr = jQuery.getJSON( ajaxurl, params ) // GET doesn't work when preparing hundreds of products
        var jqxhr = jQuery.post( ajaxurl, params, null, 'json' )
        .success( function( response ) { 

            if ( response.success ) {
                
                // request was successful
                tb_remove();                    

                var logMsg = '<div id="message" class="updated" style="display:block !important;"><p>' + 
                // 'Profile '+profile_id+' was applied to '+item_ids.length+' product(s).' +
                response.msg +
                '</p></div>';
                jQuery('.wrap > h2').append( logMsg );

                // update ebay column in products table
                // console.log( "updating ebay column for profile id", profile_id ); 
                var column_html = '<img src="'+wple_ProfileSelector_i18n.WPLE_URL+'/img/hammer-orange-16x16.png" alt="prepared" />';
                for (var i = item_ids.length - 1; i >= 0; i--) {
                    post_id = item_ids[i];
                    jQuery('tr#post-'+post_id+' .column-listed_on_ebay').html( column_html );
                };

            } else {
                var logMsg = '<div id="message" class="updated" style="display:block !important;"><p>' + 
                'I could not find any items. Sorry.' +
                '</p></div>';
                jQuery('#ajax-response').append( logMsg );
    
                alert( "There was a problem preparing these products. The server responded:\n\n" + response.msg ); 
                console.log( "response", response ); 

            }


        })
        .error( function(e,xhr,error) { 
            alert( "There was a problem preparing this product. The server responded:\n\n" + e.responseText ); 
            console.log( "error", xhr, error ); 
            console.log( e.responseText ); 
            console.log( "ajaxurl", ajaxurl ); 
            console.log( "params", params ); 
        });

    }

    // show jobs window
    var showWindow = function ( title ) {

        // show jobs window
        // var tbHeight = tb_getPageSize()[1] - 160;
        // var tbURL = "#TB_inline?height="+tbHeight+"&width=500&modal=true&inlineId=wple_profile_selector_window_container"; 
        // var tbURL = ajaxurl + "?action=wple_show_profile_selection&width=640&height=420"; // width parameter causes 404 error on some themes
        var tbURL = ajaxurl + "?action=wple_show_profile_selection"; 

        // jQuery('#wple_jobs_log').html('').css('height', tbHeight - 130 );
        // jQuery('#wple_jobs_title').html( title );
        // jQuery('#wple_jobs_message').html('fetching list of tasks...');
        // jQuery('#wple_jobs_message').html( wple_ProfileSelector_i18n.msg_loading_tasks );
        // jQuery('#wple_jobs_footer_msg').html( "Please don't close this window until all tasks are completed." );
        // jQuery('#wple_jobs_footer_msg').html( wple_ProfileSelector_i18n.footer_dont_close );

        // hide close button
        // jQuery('#wple_profile_selector_window .btn_close').hide();

        // show window
        tb_show("Select a listing profile for "+item_ids.length+" "+window.wple_select_mode, tbURL);             

    }

    // get selected products
    var getSelectedProducts = function ( select_mode ) {
        item_ids = [];

        // create array of selected product IDs
        var checked_items = jQuery(".check-column input:checked[name='post[]']");

        // create array of selected listing IDs
        if ( 'listings' == select_mode ) {
            checked_items = jQuery(".check-column input:checked[name='auction[]']");
        }

        checked_items.each( function(index, checkbox) {
             item_ids.push( checkbox.value );
             console.log( 'checked listing ID', checkbox.value );
        });
        console.log( item_ids );

        return item_ids;
    }

    // set selected products
    var setSelectedProducts = function ( product_ids ) {
        item_ids = product_ids;
    }

    return {
        // declare which properties and methods are supposed to be public
        init: init,
        select: select,
        getSelectedProducts: getSelectedProducts,
        setSelectedProducts: setSelectedProducts,
        showWindow: showWindow
    }
}();





// init 
jQuery( document ).ready( function () {

    // handle bulk actions click
    jQuery(".tablenav .actions input[type='submit'].action").on('click', function() {
        
        if ( 'doaction'  == this.id ) var selected_action = jQuery("select[name='action']").first().val();
        if ( 'doaction2' == this.id ) var selected_action = jQuery("select[name='action2']").first().val();

        // console.log( 'selected_action', selected_action );
        var select_mode = false;

        // if ( 'list_on_ebay' == selected_action ) {
        if ( 'prepare_auction' == selected_action ) {
            select_mode = 'products';
        }
        if ( 'wple_change_profile' == selected_action ) {
            select_mode = 'listings';
        }
        if ( ! select_mode ) return;
        console.log( 'select_mode', select_mode );

        // check if any items were selected
        var item_ids = WPLE.ProfileSelector.getSelectedProducts( select_mode );
        if ( item_ids.length > 0 ) {

            window.wple_select_mode = select_mode;
            WPLE.ProfileSelector.showWindow();
            return false;

        }

        return false;
    })

    // handle click on magnifier icon on Products page
    jQuery("a.wple_btn_select_profile_for_product").on('click', function() {
        
        var post_id = jQuery(this).data('post_id');
        // console.log( 'post_id', post_id );

        WPLE.ProfileSelector.setSelectedProducts( [ post_id ] );
        window.wple_select_mode = 'products';
        WPLE.ProfileSelector.showWindow();

        return false;
    })

    
    // handle list on ebay bulk action
    // jQuery("input#doaction").click(function() {
    //     var action = jQuery("select[name='action']").val();
    //     if ( 'list_on_ebay' == action ) {
    //         WPLE.ProfileSelector.showWindow();
    //         return false;
    //     }
    // });
    // jQuery("input#doaction2").click(function() {
    //     var action = jQuery("select[name='action']").val();
    //     if ( 'list_on_ebay' == action ) {
    //         WPLE.ProfileSelector.showWindow();
    //         return false;
    //     }
    // });

}); 



// implement String.format()
// http://stackoverflow.com/questions/610406/javascript-equivalent-to-printf-string-format
// if (!String.prototype.format) {
//     String.prototype.format = function() {
//         var args = arguments;
//         return this.replace(/{(\d+)}/g, function(match, number) { 
//             return typeof args[number] != 'undefined'
//                 ? args[number]
//                 : match
//             ;
//         });
//     };
// }

