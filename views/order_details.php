<?php

	$d = $wpl_ebay_order['details'];

?><html>
<head>
    <title>eBay order <?php echo $wpl_ebay_order['order_id'] ?></title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <style type="text/css">
        body,td,p { color:#2f2f2f; font:12px/16px "Open Sans",sans-serif; }
        a { text-decoration: none; }
        a:hover { color: #000; }
    </style>
</head>

<body>

    <h2>Details for order <?php echo $wpl_ebay_order['order_id'] ?></h2>

    <table width="100%" border="0">
        <tr>
            <td width="20%">            
                <b>Date:</b>
            </td><td>
                <?php echo $wpl_ebay_order['date_created'] ?>
            </td>
        </tr>
        <tr>
            <td>            
                <b>eBay Buyer:</b>
            </td><td>
                <?php echo $d->BuyerUserID ?>
            </td>
        </tr>
        <tr>
            <td>            
                <b>Buyer Email:</b>
            </td><td>
                <?php echo $d->TransactionArray[0]->Buyer->Email ?>
            </td>
        </tr>
        <?php if ( $d->BuyerCheckoutMessage != '' ) : ?>
        <tr>
            <td>            
                <b>Message:</b>
            </td><td>
                <?php echo $d->BuyerCheckoutMessage ?>
            </td>
        </tr>
        <?php endif; ?>
    </table>

        
    <h2>Shipping and Payment</h2>

    <table width="100%" border="0">
        <tr><td width="50%" valign="top">
            
            <b>Shipping address:</b><br>
            <?php echo $d->ShippingAddress->Name ?> <br>
            <?php echo $d->ShippingAddress->Street1 ?> <br>
            <?php if ($d->ShippingAddress->Street2): ?>
            <?php echo $d->ShippingAddress->Street2 ?> <br>
            <?php endif; ?>
            <?php echo $d->ShippingAddress->PostalCode ?> 
            <?php echo $d->ShippingAddress->CityName ?> <br>
            <?php echo $d->ShippingAddress->CountryName ?> <br>
            <br>
            <b>Shipping service:</b><br>
            <?php echo $d->ShippingServiceSelected->ShippingService ?> <br>
            Shipping cost: <?php echo $d->ShippingServiceSelected->ShippingServiceCost->value ?> <?php echo $d->ShippingServiceSelected->ShippingServiceCost->attributeValues['currencyID'] ?> <br>
            <br>

        </td><td width="50%" valign="top">

            <b>Payment address:</b><br>
            <?php if ( @$d->Buyer->RegistrationAddress ) : ?>
                <?php echo $d->Buyer->RegistrationAddress->Name ?> <br>
                <?php echo $d->Buyer->RegistrationAddress->Street1 ?> <br>
                <?php if ($d->Buyer->RegistrationAddress->Street2): ?>
                <?php echo $d->Buyer->RegistrationAddress->Street2 ?> <br>
                <?php endif; ?>
                <?php echo $d->Buyer->RegistrationAddress->PostalCode ?> 
                <?php echo $d->Buyer->RegistrationAddress->CityName ?> <br>
                <?php echo $d->Buyer->RegistrationAddress->CountryName ?> <br>
            <?php else: ?>
                No registration address provided.<br>
            <?php endif; ?>
            <br>
            <b>Payment method:</b><br>
            <?php echo $d->CheckoutStatus->PaymentMethod ?> <br>
            <br>
            <br>
            <b>Order Total:</b><br>
            <?php echo $d->Total->value ?> <?php echo $d->Total->attributeValues['currencyID'] ?> <br>
            <br>
            
        </td></tr>
    </table>

    <h2>Purchased Items</h2>

    <table width="100%" border="0">
        <tr><th>            
            <?php echo __('Quantity','wplister') ?> 
        </th><th>
            <?php echo __('Name','wplister') ?> 
        </th><th>
            <?php echo __('Price','wplister') ?> 
        </th></tr>

        <?php foreach ( $wpl_ebay_order['items'] as $item ) : ?>

            <tr><td width="20%">                      
                <?php echo $item['quantity'] ?> 
            </td><td>
                <a href="admin.php?page=wplister&amp;s=<?php echo $item['item_id'] ?>" target="_blank">
                    <?php echo $item['title'] ?>
                </a>
                <?php if ( isset( $item['VariationSpecifics'] ) && is_array( $item['VariationSpecifics'] ) ) : ?>
                    <?php foreach ($item['VariationSpecifics'] as $attribute_name => $attribute_value) : ?>
                        <br>
                        <?php echo $attribute_name ?>:
                        <?php echo $attribute_value ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </td><td>
                <?php #echo woocommerce_price( $item['TransactionPrice'] ) ?> 
                <?php echo $item['TransactionPrice'] ?> 
                <?php echo $wpl_ebay_order['currency'] ?> 
            </td></tr>

        <?php endforeach; ?>

    </table>
    
    <?php if ( is_array( $wpl_ebay_order['history'] ) ) : ?>

        <h2>History</h2>

        <table width="100%" border="0">
            <tr><th>            
                <?php echo __('Date','wplister') ?> 
            </th><th>
                <?php echo __('Time','wplister') ?> 
            </th><th>
                <?php echo __('Message','wplister') ?> 
            </th><th>
                <?php #echo __('Success','wplister') ?> 
            </th></tr>

            <?php foreach ( $wpl_ebay_order['history'] as $record ) : ?>

                <tr><td width="16%">                      
                    <?php // echo date( get_option( 'date_format' ), $record->time ) ?> 
                    <?php echo date( 'Y-m-d', $record->time ) ?> 
                </td><td width="12%">                      
                    <?php echo date( 'H:i:s', $record->time ) ?> 
                </td><td>
                    <?php echo $record->msg ?> <br>
                    
                    <?php if ( isset( $record->details['ebay_id'] ) ) : ?>
                        <a href="admin.php?page=wplister&amp;s=<?php echo $record->details['ebay_id'] ?>" target="_blank">
                            &raquo; find item <?php echo $record->details['ebay_id'] ?>
                        </a>
                    <?php endif; ?>

                    <?php if ( isset( $record->details['product_id'] ) ) : ?>
                        <a href="post.php?action=edit&amp;post=<?php echo $record->details['product_id'] ?>" target="_blank">
                            &raquo; edit product <?php echo $record->details['product_id'] ?>
                        </a>
                    <?php endif; ?>

                </td><td>
                    <?php echo $record->success ? '<span style="color:darkgreen;">OK</span>' : '<span style="color:darkred;">FAILED</span>' ?> 
                </td></tr>

            <?php endforeach; ?>

        </table>

    <?php endif; ?>
    
           
    <?php if ( is_array( $wpl_wc_order_notes ) ) : ?>

        <h2>Order Notes</h2>

        <table width="100%" border="0">
            <tr><th>            
                <?php echo __('Date','wplister') ?> 
            </th><th>
                <?php echo __('Time','wplister') ?> 
            </th><th>
                <?php echo __('Message','wplister') ?> 
            </th></tr>

            <?php foreach ( $wpl_wc_order_notes as $record ) : ?>

                <tr><td width="16%">                      
                    <?php echo date( get_option( 'date_format' ), strtotime($record->comment_date) ) ?> 
                </td><td width="12%">                      
                    <?php echo date( 'H:i:s', strtotime($record->comment_date) ) ?> 
                </td><td>
                    <?php echo $record->comment_content ?> 
                </td></tr>

            <?php endforeach; ?>

        </table>

    <?php endif; ?>
    
    <h2>Debug Data</h2>
    <a href="#" onclick="jQuery(this).hide();jQuery('#wplister_order_details_debug').slideDown();return false;" class="button">Show Debug Info</a>
    <pre id="wplister_order_details_debug" style="display:none"><?php print_r( $wpl_ebay_order ) ?></pre>
           
    <pre><?php #print_r( $d ); ?></pre>
    <pre><?php #print_r( $wpl_ebay_order ); ?></pre>


</body>
</html>



