<?php

	$d = $wpl_transaction['details'];

?><html>
<head>
    <title>Transaction details</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <style type="text/css">
        body,td,p { color:#2f2f2f; font:12px/16px Verdana, Arial, Helvetica, sans-serif; }
    </style>
</head>

<body>

    <h2>Details for transaction #<?php echo $wpl_transaction['id'] ?></h2>

    <table width="100%" border="0">
        <tr>
            <td width="20%">            
                <b>Datum:</b>
            </td><td>
                <?php echo $wpl_transaction['date_created'] ?>
            </td>
        </tr>
        <tr>
            <td>            
                <b>eBay Item ID:</b>
            </td><td>
                <?php echo $wpl_transaction['item_id'] ?>
            </td>
        </tr>
        <tr>
            <td>            
                <b>eBay Buyer:</b>
            </td><td>
                <?php echo $d->Buyer->UserID ?>
            </td>
        </tr>
        <tr>
            <td>            
                <b>Buyer Email:</b>
            </td><td>
                <?php echo $d->Buyer->Email ?>
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
        <tr><td width="50%">
            
            <b>Shipping address:</b><br>
            <?php echo $d->Buyer->BuyerInfo->ShippingAddress->Name ?> <br>
            <?php echo $d->Buyer->BuyerInfo->ShippingAddress->Street1 ?> <br>
            <?php if ($d->Buyer->BuyerInfo->ShippingAddress->Street2): ?>
            <?php echo $d->Buyer->BuyerInfo->ShippingAddress->Street2 ?> <br>
            <?php endif; ?>
            <?php echo $d->Buyer->BuyerInfo->ShippingAddress->PostalCode ?> 
            <?php echo $d->Buyer->BuyerInfo->ShippingAddress->CityName ?> <br>
            <?php echo $d->Buyer->BuyerInfo->ShippingAddress->CountryName ?> <br>
            <br>
            <b>Shipping service:</b><br>
            <?php echo $d->ShippingServiceSelected->ShippingService ?> <br>
            <br>

        </td><td width="50%">

            <b>Payment address:</b><br>
            <?php echo $d->Buyer->RegistrationAddress->Name ?> <br>
            <?php echo $d->Buyer->RegistrationAddress->Street1 ?> <br>
            <?php if ($d->Buyer->RegistrationAddress->Street2): ?>
            <?php echo $d->Buyer->RegistrationAddress->Street2 ?> <br>
            <?php endif; ?>
            <?php echo $d->Buyer->RegistrationAddress->PostalCode ?> 
            <?php echo $d->Buyer->RegistrationAddress->CityName ?> <br>
            <?php echo $d->Buyer->RegistrationAddress->CountryName ?> <br>
            <br>
            <b>Payment method:</b><br>
            <?php echo $d->Status->PaymentMethodUsed ?> <br>
            <br>
            
        </td></tr>
    </table>

    <h2>Order</h2>

    <table width="100%" border="0">
        <tr><th>            
            <?php echo __('Quantity','wplister') ?> 
        </th><th>
            <?php echo __('Name','wplister') ?> 
        </th><th>
            <?php echo __('Price','wplister') ?> 
        </th></tr>
        <tr><td width="20%">                      
            <?php echo $wpl_transaction['quantity'] ?> 
        </td><td>
            <?php echo $wpl_auction_item->auction_title ?>
            <?php if ( is_object( @$d->Variation ) ) : ?>
                <?php foreach ($d->Variation->VariationSpecifics as $spec) : ?>
                    <br> -
                    <?php echo $spec->Name ?>:
                    <?php echo $spec->Value[0] ?>
                <?php endforeach; ?>
            <?php endif; ?>
        </td><td>
            <?php echo number_format_i18n( $wpl_auction_item->price, 2 ) ?> &euro;
        </td></tr>
    </table>

        
	
           
    <pre><?php #print_r( $d ); ?></pre>
    <pre><?php #print_r( $wpl_auction_item ); ?></pre>


</body>
</html>



