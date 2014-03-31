<?php

class WPL_InventoryCheck extends WPL_Model  {
	

	// check_wc_out_of_sync
	public function checkProductInventory( $mode = 'published' ) {

		// get all published listings
		$lm = new ListingsModel();
		$listings = $mode == 'published' ? $lm->getAllPublished() : $lm->getAllEnded();
		$out_of_sync_products = array();
		$published_count = 0;

		// process published listings
		foreach ( $listings as $item ) {

			// check wc product
			$post_id = $item['post_id'];
			$_product = ProductWrapper::getProduct( $post_id );
			// echo "<pre>";print_r($_product);echo"</pre>";die();


			// get stock level and price
			$stock = ProductWrapper::getStock( $item['post_id'] );
			$price = ProductWrapper::getPrice( $item['post_id'] );
			// $item['price_max'] = $price;
			// echo "<pre>";print_r($price);echo"</pre>";#die();
			// echo "<pre>";print_r($item);echo"</pre>";die();

			// apply profile settings to stock level
			$profile_data    = $lm->decodeObject( $item['profile_data'], true );
			$profile_details = $profile_data['details'];
			$item['qty']     = $item['quantity'] - $item['quantity_sold'];
			// echo "<pre>";print_r($profile_details);echo"</pre>";#die();

	        // apply max_quantity from profile
    	    $max_quantity = ( isset( $profile_details['max_quantity'] ) && intval( $profile_details['max_quantity'] )  > 0 ) ? $profile_details['max_quantity'] : false ; 
    	    if ( $max_quantity )
    	    	$stock = min( $max_quantity, intval( $stock ) );


			// check if product has variations
			if ( $_product ) {
				$variations = $_product->product_type == 'variable' ? ProductWrapper::getVariations( $item['post_id'] ) : array();
			} else {
				$variations = array();
			}

			// get total stock for all variations
			if ( ! empty( $variations ) ) {

				// reset prices and stock
				$stock          = 0;
				$price_min      = PHP_INT_MAX;
				$price_max      = 0;
				$ebay_stock     = 0;
				$ebay_price_min = PHP_INT_MAX;
				$ebay_price_max = 0;

				// check WooCommerce variations
				foreach ($variations as $var) {

					// total stock
		    	    if ( $max_quantity )
		    	    	$stock += min( $max_quantity, intval( $var['stock'] ) );
		    	    else 
						$stock += $var['stock'];

					// min / max prices
					$price_min = min( $price_min, $var['price'] );
					$price_max = max( $price_max, $var['price'] );

				}

				// check eBay variations
		        $cached_variations = maybe_unserialize( $item['variations'] );
				foreach ($cached_variations as $var) {
					$ebay_stock    += $var['stock'];
					$ebay_price_min = min( $ebay_price_min, $var['price'] );
					$ebay_price_max = max( $ebay_price_max, $var['price'] );
				}

				// set default values
				$price             = $price;
				$item['qty']       = $ebay_stock;
				$item['price']     = $ebay_price_min;
				$item['price_max'] = $ebay_price_max;
				// echo "<pre>";print_r($cached_variations);echo"</pre>";die();
			}


			// check if product and ebay listing are in sync
			$in_sync = true;

			// check stock level
			if ( $stock != $item['qty'] )
				$in_sync = false;

			// check price
			if ( round( $price, 2 ) != round( $item['price'], 2 ) )
				$in_sync = false;

			// check max price
			if ( isset( $price_max ) && isset( $item['price_max'] ) && ( round( $price_max, 2 ) != round ( $item['price_max'], 2 ) ) )
				$in_sync = false;

			// if in sync, continue with next item
			if ( $in_sync )
				continue;


			// mark listing as changed 
			if ( isset( $_REQUEST['mark_as_changed'] ) ) {

				if ( $_product ) {
					// only existing products can have a profile re-applied
					$lm->markItemAsModified( $item['post_id'] );
				}

				// in case the product is locked or missing, force the listing to be changed
				$lm->updateListing( $item['id'], array( 'status' => 'changed' ) );

				$item['status'] = 'changed';
			}


			// add to list of out of sync products
			$item['price_woo']      = $price;
			$item['price_woo_max']  = isset( $price_max ) ? $price_max : false;
			$item['stock']          = $stock;
			$item['exists']         = $_product ? true : false;
			$item['type']           = $_product ? $_product->product_type : 'missing';
			$out_of_sync_products[] = $item;

			// count products which have not yet been marked as changed
			if ( $item['status'] == 'published' ) $published_count += 1;
		}

		// return if empty
		if ( empty( $out_of_sync_products ) ) {
			$this->showMessage('All '.$mode.' listings seem to be in sync.', 0, 1);
			return;			
		}

		$msg = '<p>';
		$msg .= 'Warning: '.sizeof($out_of_sync_products).' '.$mode.' listings are out of sync or missing in WooCommerce.';
		$msg .= '</p>';

		// table header
		$msg .= '<table style="width:100%">';
		$msg .= "<tr>";
		$msg .= "<th style='text-align:left'>Product</th>";
		$msg .= "<th style='text-align:left'>Local Qty</th>";
		$msg .= "<th style='text-align:left'>eBay Qty</th>";
		$msg .= "<th style='text-align:left'>Local Price</th>";
		$msg .= "<th style='text-align:left'>eBay Price</th>";
		$msg .= "<th style='text-align:left'>eBay ID</th>";
		$msg .= "<th style='text-align:left'>Status</th>";
		$msg .= "</tr>";

		// table rows
		foreach ( $out_of_sync_products as $item ) {
			// echo "<pre>";print_r($item['ebay_id']);echo"</pre>";#die();

			// get column data
			$qty          = $item['qty'];
			$stock        = $item['stock'];
			$title        = $item['auction_title'];
			$post_id      = $item['post_id'];
			$ebay_id      = $item['ebay_id'];
			$status       = $item['status'];
			$exists       = $item['exists'];
			$locked       = $item['locked'] ? 'locked' : '';
			$price        = woocommerce_price( $item['price'] );
			$price_woo    = woocommerce_price( $item['price_woo'] );
			$product_type = $item['type'] == 'simple' ? '' : $item['type'];

			// highlight changed values
			$changed_stock     =   intval( $item['qty']   )     ==   intval( $item['stock']     )     ? false : true;
			$changed_price     = floatval( $item['price'] )     == floatval( $item['price_woo'] )     ? false : true;
			$changed_price_max = floatval(@$item['price_max'] ) == floatval( $item['price_woo_max'] ) ? false : true;
			$stock_css         = $changed_stock                       ? 'color:darkred;' : '';
			$price_css         = $changed_price || $changed_price_max ? 'color:darkred;' : '';

			// build links
			$ebay_url = $item['ViewItemURL'] ? $item['ViewItemURL'] : $ebay_url = 'http://www.ebay.com/itm/'.$ebay_id;
			$ebay_link = '<a href="'.$ebay_url.'" target="_blank">'.$ebay_id.'</a>';
			$edit_link = '<a href="post.php?action=edit&post='.$post_id.'" target="_blank">'.$title.'</a>';

			// mark non existent products
			if ( ! $exists ) {
				$stock    = 'N/A';
				$post_id .= ' missing!';
			}

			// show price range for variations
			if ( $item['price_woo_max'] )
				$price_woo .= ' - '.woocommerce_price( $item['price_woo_max'] );
			if ( @$item['price_max'] )
				$price .= ' - '.woocommerce_price( $item['price_max'] );

			// build table row
			$msg .= "<tr>";
			$msg .= "<td>$edit_link <span style='color:silver'>$locked $product_type (#$post_id)</span></td>";
			$msg .= "<td style='$stock_css'>$stock</td>";
			$msg .= "<td style='$stock_css'>$qty</td>";
			$msg .= "<td style='$price_css'>$price_woo</td>";
			$msg .= "<td style='$price_css'>$price</td>";
			$msg .= "<td>$ebay_link</td>";
			$msg .= "<td>$status</td>";
			$msg .= "</tr>";
		}
		$msg .= '</table>';

		// buttons
		$msg .= '<p>';

		// show 'check again' button
		$url  = 'admin.php?page=wplister-tools&action=check_wc_out_of_sync&mode='.$mode.'&_wpnonce='.wp_create_nonce('e2e_tools_page');
		$msg .= '<a href="'.$url.'" class="button">'.__('Check again','wplister').'</a> &nbsp; ';

		// show 'mark all as changed' button
		if ( $mode == 'published' )
		if ( $published_count ) {
			$url = 'admin.php?page=wplister-tools&action=check_wc_out_of_sync&mark_as_changed=yes&_wpnonce='.wp_create_nonce('e2e_tools_page');
			$msg .= '<a href="'.$url.'" class="button">'.__('Mark all as changed','wplister').'</a> &nbsp; ';
			$msg .= 'Click this button to mark all found listings as changed in WP-Lister, then revise all changed listings.';
		} else {
			$msg .= '<a id="btn_revise_all_changed_items_reminder" class="btn_revise_all_changed_items_reminder button wpl_job_button">' . __('Revise all changed items','wplister') . '</a>';
			$msg .= ' &nbsp; ';
			// $msg .= 'Click to revise all changed items. If there are still unsynced items after revising, you might have to reapply the listing profile.';
			$msg .= 'Click to revise all changed items.';
		}
		$msg .= '</p>';		

		$this->showMessage( $msg, 1, 1 );

	} // checkProductInventory()






	// check_wc_out_of_stock
	public function checkProductStock() {

		// get all published listings
		$lm = new ListingsModel();
		$listings = $lm->getAllPublished();
		$out_of_stock_products = array();

		// process published listings
		foreach ( $listings as $item ) {

			// get wc product
			$_product = ProductWrapper::getProduct( $item['post_id'] );

			// check stock level
			// $stock = ProductWrapper::getStock( $item['post_id'] );
			$stock = $_product ? $_product->get_total_stock() : 0;
			if ( $stock > 0 )
				continue;

			// mark listing as changed
			if ( isset( $_REQUEST['mark_as_changed'] ) ) {
				$lm->updateListing( $item['id'], array( 'status' => 'changed' ) );
				$item['status'] = 'changed';
			}

			// add to list of out of stock products
			$item['stock']  = $stock;
			$item['exists'] = $_product ? true : false;
			$out_of_stock_products[] = $item;

		}

		// return if empty
		if ( empty( $out_of_stock_products ) ) {
			$this->showMessage('No out of stock products found.', 0, 1);
			return;			
		}

		$msg = '<p>';
		$msg .= 'Warning: Some published listings are out of stock or missing in WooCommerce.';
		$msg .= '</p>';

		// table header
		$msg .= '<table style="width:100%">';
		$msg .= "<tr>";
		$msg .= "<th style='text-align:left'>Stock</th>";
		$msg .= "<th style='text-align:left'>Product</th>";
		$msg .= "<th style='text-align:left'>Qty</th>";
		$msg .= "<th style='text-align:left'>eBay ID</th>";
		$msg .= "<th style='text-align:left'>Status</th>";
		$msg .= "</tr>";

		// table rows
		foreach ( $out_of_stock_products as $item ) {

			// get column data
			$qty     = $item['quantity'];
			$stock   = $item['stock'] . ' x ';
			$title   = $item['auction_title'];
			$post_id = $item['post_id'];
			$ebay_id = $item['ebay_id'];
			$status  = $item['status'];
			$exists  = $item['exists'];

			// build links
			$ebay_url = $item['ViewItemURL'] ? $item['ViewItemURL'] : $ebay_url = 'http://www.ebay.com/itm/'.$ebay_id;
			$ebay_link = '<a href="'.$ebay_url.'" target="_blank">'.$ebay_id.'</a>';
			$edit_link = '<a href="post.php?action=edit&post='.$post_id.'" target="_blank">'.$title.'</a>';

			// mark non existent products
			if ( ! $exists ) {
				$stock    = 'N/A';
				$post_id .= ' missing!';
			}

			// build table row
			$msg .= "<tr>";
			$msg .= "<td>$stock</td>";
			$msg .= "<td>$edit_link (ID $post_id)</td>";
			$msg .= "<td>$qty x </td>";
			$msg .= "<td>$ebay_link</td>";
			$msg .= "<td>$status</td>";
			$msg .= "</tr>";
		}
		$msg .= '</table>';


		$msg .= '<p>';
		$url = 'admin.php?page=wplister-tools&action=check_wc_out_of_stock&mark_as_changed=yes&_wpnonce='.wp_create_nonce('e2e_tools_page');
		$msg .= '<a href="'.$url.'" class="button">'.__('Mark all as changed','wplister').'</a> &nbsp; ';
		$msg .= 'Click this button to mark all found listings as changed in WP-Lister, then revise all changed listings.';
		$msg .= '</p>';

		$this->showMessage( $msg, 1, 1 );


	} // checkProductStock()






	// check_wc_sold_stock
	public function checkSoldStock() {

		// get all published listings
		$lm = new ListingsModel();
		$listings = $lm->getAllWithStatus('sold');
		$out_of_stock_products = array();

		// process published listings
		foreach ( $listings as $item ) {

			// get wc product
			$_product = ProductWrapper::getProduct( $item['post_id'] );

			// check stock level
			// $stock = ProductWrapper::getStock( $item['post_id'] );
			$stock = $_product ? $_product->get_total_stock() : 0;
			if ( $stock == 0 )
				continue;

			// mark listing as changed
			// if ( isset( $_REQUEST['mark_as_changed'] ) ) {
			// 	$lm->updateListing( $item['id'], array( 'status' => 'changed' ) );
			// 	$item['status'] = 'changed';
			// }

			// add to list of out of stock products
			$item['stock']  = $stock;
			$item['exists'] = $_product ? true : false;
			$out_of_stock_products[] = $item;

		}

		// return if empty
		if ( empty( $out_of_stock_products ) ) {
			$this->showMessage('No sold products have stock in WooCommerce.', 0, 1);
			return;			
		}

		$msg = '<p>';
		$msg .= 'Warning: Some sold listings are still in stock in WooCommerce.';
		$msg .= '</p>';

		// table header
		$msg .= '<table style="width:100%">';
		$msg .= "<tr>";
		$msg .= "<th style='text-align:left'>Stock</th>";
		$msg .= "<th style='text-align:left'>Product</th>";
		$msg .= "<th style='text-align:left'>Qty</th>";
		$msg .= "<th style='text-align:left'>eBay ID</th>";
		$msg .= "<th style='text-align:left'>Ended at</th>";
		$msg .= "<th style='text-align:left'>Status</th>";
		$msg .= "</tr>";

		// table rows
		foreach ( $out_of_stock_products as $item ) {

			// get column data
			$qty     = $item['quantity'] - $item['quantity_sold'];
			$stock   = $item['stock'] . ' x ';
			$title   = $item['auction_title'];
			$post_id = $item['post_id'];
			$ebay_id = $item['ebay_id'];
			$status  = $item['status'];
			$exists  = $item['exists'];
			$date_ended = $item['date_finished'] ? $item['date_finished'] : $item['end_date'];

			// build links
			$ebay_url = $item['ViewItemURL'] ? $item['ViewItemURL'] : $ebay_url = 'http://www.ebay.com/itm/'.$ebay_id;
			$ebay_link = '<a href="'.$ebay_url.'" target="_blank">'.$ebay_id.'</a>';
			$edit_link = '<a href="post.php?action=edit&post='.$post_id.'" target="_blank">'.$title.'</a>';

			// mark non existent products
			if ( ! $exists ) {
				$stock    = 'N/A';
				$post_id .= ' missing!';
			}

			// build table row
			$msg .= "<tr>";
			$msg .= "<td>$stock</td>";
			$msg .= "<td>$edit_link (ID $post_id)</td>";
			$msg .= "<td>$qty x </td>";
			$msg .= "<td>$ebay_link</td>";
			$msg .= "<td>$date_ended</td>";
			$msg .= "<td>$status</td>";
			$msg .= "</tr>";
		}
		$msg .= '</table>';

		// show 'check again' button
		$msg .= '<p>';
		$url  = 'admin.php?page=wplister-tools&action=check_wc_sold_stock&_wpnonce='.wp_create_nonce('e2e_tools_page');
		$msg .= '<a href="'.$url.'" class="button">'.__('Check again','wplister').'</a> &nbsp; ';
		$msg .= '</p>';

		// $msg .= '<p>';
		// $url = 'admin.php?page=wplister-tools&action=check_wc_out_of_stock&mark_as_changed=yes&_wpnonce='.wp_create_nonce('e2e_tools_page');
		// $msg .= '<a href="'.$url.'" class="button">'.__('Mark all as changed','wplister').'</a> &nbsp; ';
		// $msg .= 'Click this button to mark all found listings as changed in WP-Lister, then revise all changed listings.';
		// $msg .= '</p>';

		$this->showMessage( $msg, 1, 1 );


	} // checkSoldStock()

	

} // class WPL_InventoryCheck
