<?php

	$d = $wpl_transaction['details'];

?><html>
<head>
    <title>Invoice #<?php echo $wpl_transaction['id'] ?></title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <style type="text/css">
        body,td,p { color:#2f2f2f; font:12px/16px Verdana, Arial, Helvetica, sans-serif; }
        th {
            font-size: 12px;
            text-align: left;
        }
    </style>
</head>

<body>
    <div style="font-family:Verdana, Arial, Helvetica, sans-serif; font-size:12px; margin:0; padding:0;">
        <table cellspacing="0" cellpadding="0" border="0" width="100%">
            <tr>
                <td align="center" valign="top" style="padding:20px 0 20px 0">
                    <table bgcolor="#FFFFFF" cellspacing="0" cellpadding="10" border="0" width="600" style="border:1px solid #E0E0E0;">
                        <!-- [ header starts here] -->

                        <tr>
                            <td valign="top"><a href="http://www.example.com/"><img src="http://dummyimage.com/200x50/ffffff/000000.png&text=your+logo" alt="your logo" style="margin-bottom:10px;" border="0"></a></td>
                        </tr><!-- [ middle starts here] -->

                        <tr>
                            <td valign="top">
                            	<!--
                                <h1 style="font-size:22px; font-weight:normal; line-height:22px; margin:0 0 11px 0;">Hello, [name]</h1>
                                -->

                                <p style="font-size:12px; line-height:16px; margin:0;">
                                	Thank you for your order.<br>
                                	<br>

									You purchased item # <?php echo $wpl_transaction['item_id'] ?> on <?php echo $wpl_transaction['date_created'] ?>. 
								</p>
                            </td>
                        </tr>


                        <tr>
                            <td bgcolor="#FFFFFF" align="left" style="background:#ffffff; text-align:left;">


                                <h2>Order #<?php echo $wpl_transaction['id'] ?></h2>

                                <table width="100%" border="0">
                                    <tr>
                                        <td width="20%">            
                                            <b>Date:</b>
                                        </td><td>
                                            <?php echo $wpl_transaction['date_created'] ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>            
                                            <b>eBay ID:</b>
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
                                        <b>Shipping method:</b><br>
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

                                <h2>Your order</h2>

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
                                    </td><td>
                                        <?php echo number_format_i18n( $wpl_auction_item->price, 2 ) ?> &euro;
                                    </td></tr>
                                </table>

                                    

								<br><br>
                                <p style="font-size:12px; margin:0;">
	                               more information...    
                                <br></p>
                            </td>
                        </tr>

                        <tr>
                            <td bgcolor="#EAEAEA" align="center" style="background:#EAEAEA; text-align:center;">
                                <center>
                                    <p style="font-size:12px; margin:0;">thank you message</p>
                                </center>
                            </td>
                        </tr>

                        <tr>
                            <td bgcolor="#FFFFFF" align="left" style="background:#ffffff; text-align:left;">
                                <p style="font-size:12px; margin:0;">
                                    footer
                                </p>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </div>




</body>
</html>



