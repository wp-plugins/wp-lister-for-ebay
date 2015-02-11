<?php

    $wpl_default_matcher_selection = get_option( 'wplister_default_matcher_selection', 'title' );
    if ( ! $wpl_query_select ) $wpl_query_select = $wpl_default_matcher_selection;

?><html>
<head>
    <title>request details</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <style type="text/css">
        pre {
        	background-color: #eee;
        	border: 1px solid #ccc;
        	padding: 20px;
        }
        .wplister_match_results td {
            /*background-color:#ffc;*/
            border-bottom: 1px solid #ccc;
            vertical-align: top;
        }
        .wplister_match_results td.img {
            height:75px; 
            text-align: center; 
            vertical-align: middle
        }
        .wplister_match_results td.info {
            padding: 10px 15px;
        }
        .wplister_match_results td.info a {
            margin-top: 1em;
        }
        .wplister_match_results tr:hover td.hover {
            background-color:#ffe;
        }

        #wplister_matcher_query_form {
            float: right;
            width: 66%;
        }
        #wplister_matcher_query_form {
            /*float: right;*/
            width: 98%;
            position: absolute;
            bottom: 0;
            left: 0;
            background-color: #eee;
            padding: 1%;
        }
        #wplister_matcher_query_input {
            width: 65%;
        }
        #wplister_matcher_query_select {
            width: 14%;
        }
        #wplister_matcher_query_form .button {
        	width: 19%;
        }

    </style>
</head>

<body>

    <!-- <h3>Matching products for <i><?php #echo $wpl_query ?></i> -->

        <!-- <form id="custom_query_form" method="post" action="<?php echo $wpl_form_action; ?>" > -->
        <!-- <form id="wplister_matcher_query_form" method="post" action="admin-ajax.php" onsubmit="WPLA.ProductMatcher.submitQuery();event.preventDefault();return false;"> -->
        <form id="wplister_matcher_query_form" action="#">
            <!-- <big>Matching products for </big> -->
            <input type="hidden" name="action" value="<?php echo $_REQUEST['action'] ?>" />
            <input type="hidden" name="id"     value="<?php echo $_REQUEST['id'] ?>" />
            <input type="text"   name="query"  value="<?php echo $wpl_query ?>" id="wplister_matcher_query_input" />
            <!-- <input type="text"   name="query"  value="<?php echo $wpl_query ?>" id="wplister_matcher_query_select" /> -->
            <select id="wplister_matcher_query_select" name="query_select" class="select">
                <option value="title" data-value="<?php echo htmlspecialchars( $wpl_query_product->post->post_title ) ?>" <?php if ( $wpl_query_select == 'title' ): ?>selected="selected"<?php endif; ?> ><?php echo __('Title','wplister'); ?></option>
                <option value="sku"   data-value="<?php echo htmlspecialchars( $wpl_query_product->sku )              ?>" <?php if ( $wpl_query_select == 'sku'   ): ?>selected="selected"<?php endif; ?> ><?php echo __('SKU','wplister');   ?></option>
                <?php foreach ($wpl_query_product_attributes as $attribute_label => $attribute_value) : ?>
                    <option value="<?php echo $attribute_label ?>" data-value="<?php echo htmlspecialchars( $attribute_value ) ?>" <?php if ( $wpl_query_select == $attribute_label ): ?>selected="selected"<?php endif; ?> ><?php echo $attribute_label ?></option>
                <?php endforeach; ?>
            </select>
            <input type="submit" name="submit" value="<?php echo 'Search' ?>"   onclick="WPLE_ProductMatcher_submitQuery();return false;" class="button" />
        </form>

    <!-- </h3> -->
 
    <?php if ( is_array($wpl_products) && ! empty($wpl_products) ) : ?>
    <table class="wplister_match_results" style="width:100%">
    <?php foreach ($wpl_products as $product ) : ?>
    
        <tr><td class="img">
            <a href="<?php echo $product->DetailsURL ?>" title="Click on the image to open this product on eBay" target="_blank">
                <?php if ( isset( $product->StockPhotoURL ) && $product->StockPhotoURL ) : ?>
                    <img src="<?php echo $product->StockPhotoURL ?>" />
                <?php elseif ( isset( $product->ListPrice ) ) : ?>
                    no stock photo available
                <?php endif; ?>
            </a>
        </td><td class="info hover">
            <?php echo $product->Title ?><br>

            <small>
            <?php if ( isset( $product->EPID ) ) : ?>
                EPID: <?php echo $product->EPID ?><br>
            <?php endif; ?>

            <?php if ( isset( $product->DomainName ) ) : ?>
                Domain: <?php echo $product->DomainName ?><br>
            <?php endif; ?>
            </small>

        </td><td class="info hover" style="text-align:right; width:20%;">

            <?php if ( isset( $product->lowest_price ) && $product->lowest_price ) : ?>
                <big><?php echo woocommerce_price( $product->lowest_price ) ?></big>
                <br>
            <?php elseif ( isset( $product->ListPrice ) ) : ?>
                <big><?php echo $product->ListPrice->Amount ?>&nbsp;<?php echo $product->ListPrice->CurrencyCode ?></big>
                <br>
            <?php endif; ?>
    
            <a href="<?php echo $product->DetailsURL ?>" target="_blank" class="button button-small">
                Details
            </a>
            <a href="#" onclick="jQuery('#wpl_ebay_epid').attr('value','<?php echo $product->EPID ?>');tb_remove();return false;" class="button button-small">
                Select
            </a>
            <!--
            <a href="#" onclick="WPLA.ProductMatcher.match(this,'<?php echo $wpl_post_id ?>','<?php echo $product->EPID ?>');return false;" class="button button-small">
                Select
            </a>
            -->
        </td></tr>

    <?php endforeach; ?>
    </table>
    <?php else : ?>
        <p>
            <?php echo sprintf( __('There were no products found for query %s.','wplister'), $wpl_query ) ?>
        </p>
    <?php endif; ?>


    <!-- <h3>Debug</h3> -->
    <!-- <pre><?php #print_r( $wpl_products ) ?></pre> -->

    <script type="text/javascript">

        function WPLE_ProductMatcher_submitQuery() {
            var params = jQuery('#wplister_matcher_query_form').serialize();

            // var params = {
            //     action: 'wplister_show_product_matches',
            //     post_id: post_id,
            //     nonce: 'TODO'
            // };
            var jqxhr = jQuery.get( ajaxurl, params )
            .success( function( response ) { 

                jQuery('#TB_ajaxContent').html( response );

            })
            .error( function(e,xhr,error) { 
                alert( "There was a problem matching this product. The server responded:\n\n" + e.responseText ); 
                console.log( "error", xhr, error ); 
                console.log( e.responseText ); 
                console.log( "ajaxurl", ajaxurl ); 
                console.log( "params", params ); 
            });

        }

        jQuery('#wplister_matcher_query_select').change(function(){
            // var selected_property = jQuery('#wplister_matcher_query_select').val();
            var selected_property = jQuery('#wplister_matcher_query_select').find(":selected").data('value');
            // alert(selected_property);

            jQuery('#wplister_matcher_query_input').attr('value', selected_property );

            return false;
        });
        // jQuery('#wplister_matcher_query_select').change();

    </script>


</body>
</html>
