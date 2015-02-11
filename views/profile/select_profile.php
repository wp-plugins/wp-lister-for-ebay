<?php
// echo "<pre>";print_r($wpl_profiles);echo"</pre>";die();
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
        table.wple_profile_results {
            border-bottom: 1px solid #ccc;
            border-spacing: 0;
            margin-top: 1em;
        }
        .wple_profile_results td {
            /*background-color:#ffc;*/
            /*vertical-align: top;*/
            border-top: 1px solid #ccc;
        }
        .wple_profile_results td.info {
            padding: 10px 15px;
        }
        .wple_profile_results td.info a {
            /*margin-top: 0.5em;*/
        }
        .wple_profile_results tr:hover td.hover {
        	background-color:#ffe;
        }

    </style>
</head>

<body>

    <?php if ( ! empty( $wpl_profiles ) ) : ?>
    
        <h3 class="wple_tb_title"><?php echo __('Select Profile','wplister') ?></h3>
 
        <table class="wple_profile_results" style="width:100%">
        <?php foreach ($wpl_profiles as $profile ) : ?>
        
            <tr><td class="info hover">
                <big>
                    <?php echo $profile['profile_name'] ?>
                    <?php if ( WPLE()->multi_account ) : ?>
                        &nbsp;<span style="color:silver;"><?php echo WPLE()->accounts[ $profile['account_id'] ]->title ?></span>
                    <?php endif; ?>
                </big><br>

                <small><?php echo $profile['profile_description'] ?></small>
                <br>

            </td><td class="info hover" style="text-align:right">
       
                <a href="#" onclick="WPLE.ProfileSelector.select(this,'<?php echo $profile['profile_id'] ?>');return false;" class="button button-secondary">
                    <?php echo __('Select Profile','wplister') ?>
                </a>

            </td></tr>

        <?php endforeach; ?>

        </table>
    
    <?php else : ?>

        <h3 class="wple_tb_title">No profiles found</h3>
 
        <p>
            You need to create a listing profile and assign a suitable feed template before you can start listing new products on eBay.
        </p>
        <p>
            If your products already exist on eBay, you can just search for matching products by clicking the magnifier icon on the right and link your WooCommerce products to existing eBay products.
        </p>
    <?php endif; ?>

    <!-- <h3>Debug</h3> -->
    <!-- <pre><?php #print_r( $wpl_profiles ) ?></pre> -->



</body>
</html>
