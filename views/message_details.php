<?php

	$d = $wpl_ebay_message['details'];

?><html>
<head>
    <title>Transaction details</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <style type="text/css">
        body,td,p { color:#2f2f2f; font:12px/16px "Open Sans",sans-serif; }
        a { text-decoration: none; }
        a:hover { color: #000; }
    </style>
</head>

<body>

    <h2>Details for eBay message <?php echo $wpl_ebay_message['message_id'] ?></h2>

    <table width="100%" bmessage="0">
        <tr>
            <td width="20%">            
                <b>Date:</b>
            </td><td>
                <?php echo $wpl_ebay_message['received_date'] ?>
            </td>
        </tr>
        <tr>
            <td>            
                <b>Sender:</b>
            </td><td>
                <?php echo $d->Sender ?>
            </td>
        </tr>
        <tr>
            <td>            
                <b>Subject:</b>
            </td><td>
                <?php echo $d->Subject ?>
            </td>
        </tr>
        <tr>
            <td>            
                <b>Type:</b>
            </td><td>
                <?php echo $d->MessageType ?>
            </td>
        </tr>
        <tr>
            <td>            
                <b>Read:</b>
            </td><td>
                <?php echo $d->Read ? 'yes' : 'no' ?>
            </td>
        </tr>
        <tr>
            <td>            
                <b>Replied:</b>
            </td><td>
                <?php echo $d->Replied ? 'yes' : 'no' ?>
            </td>
        </tr>
        <?php if ( $wpl_ebay_message['msg_content'] ) : ?>
        <tr>
            <td>            
                <b>Message:</b>
            </td><td>
                <?php echo $wpl_ebay_message['message_content'] ?>
            </td>
        </tr>
        <?php endif; ?>
    </table>

        
    <h2>Item Details</h2>

    <table width="100%" bmessage="0">
        <tr>
            <td width="20%">            
                <b>Item ID:</b>
            </td><td>
                <?php if ( isset( $wpl_ebay_message['item_id'] ) ) : ?>
                    <a href="admin.php?page=wplister&amp;s=<?php echo $wpl_ebay_message['item_id'] ?>" target="_blank">
                        <?php echo $wpl_ebay_message['item_id'] ?>
                    </a>
                <?php endif; ?>
            </td>
        </tr>
        <tr>
            <td>            
                <b>Title:</b>
            </td><td>
                <?php echo $wpl_ebay_message['item_title'] ?>
            </td>
        </tr>
        <tr>
            <td>            
                <b>End Date:</b>
            </td><td>
                <?php echo $d->ItemEndTime ?>
            </td>
        </tr>
    </table>
   
              
    <h2>Debug Data</h2>
    <a href="#" onclick="jQuery(this).hide();jQuery('#wplister_message_details_debug').slideDown();return false;" class="button">Show Debug Info</a>
    <pre id="wplister_message_details_debug" style="display:none"><?php print_r( $wpl_ebay_message ) ?></pre>
           
    <pre><?php #print_r( $d ); ?></pre>
    <pre><?php #print_r( $wpl_ebay_message ); ?></pre>


</body>
</html>



