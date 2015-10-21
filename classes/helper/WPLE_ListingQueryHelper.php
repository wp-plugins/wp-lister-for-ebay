<?php
/**
 * WPLE_ListingQueryHelper class
 *
 * provides static methods to query the ebay_auctions table
 * 
 */

class WPLE_ListingQueryHelper {

	const TABLENAME = 'ebay_auctions';


	static function getAllSelected() {
		global $wpdb;	
		$table = $wpdb->prefix . self::TABLENAME;

		$items = $wpdb->get_results("
			SELECT * 
			FROM $table
			WHERE status = 'selected'
			   OR status = 'reselected'
			   OR status = 'changed_profile'
			ORDER BY id DESC
		", ARRAY_A);		

		return $items;		
	}
	static function getAllPrepared() {
		global $wpdb;	
		$table = $wpdb->prefix . self::TABLENAME;

		$items = $wpdb->get_results("
			SELECT * 
			FROM $table
			WHERE status = 'prepared'
			ORDER BY id DESC
		", ARRAY_A);		

		return $items;		
	}
	static function getAllVerified() {
		global $wpdb;	
		$table = $wpdb->prefix . self::TABLENAME;

		$items = $wpdb->get_results("
			SELECT * 
			FROM $table
			WHERE status = 'verified'
			ORDER BY id DESC
		", ARRAY_A);		

		return $items;		
	}
	// // deprecated
	// static function getAllChanged() {
	// 	global $wpdb;	
	// 	$table = $wpdb->prefix . self::TABLENAME;

	// 	$items = $wpdb->get_results("
	// 		SELECT * 
	// 		FROM $table
	// 		WHERE status = 'changed'
	// 		ORDER BY id DESC
	// 	", ARRAY_A);		

	// 	return $items;		
	// }

	static function getAllChangedItemsToRevise() {
		global $wpdb;	
		$table = $wpdb->prefix . self::TABLENAME;

		$items = $wpdb->get_results("
			SELECT id, auction_title, site_id, account_id, post_id, eps
			FROM $table
			WHERE status = 'changed'
			ORDER BY id DESC
		", ARRAY_A);		

		return $items;		
	}

	static function getAllEndedItemsToRelist() {
		global $wpdb;	
		$table = $wpdb->prefix . self::TABLENAME;

		$items = $wpdb->get_results("
			SELECT id, auction_title, site_id, account_id, post_id, eps
			FROM $table
			WHERE ( status = 'ended' OR status = 'sold' ) 
			  AND ( quantity - quantity_sold > 0 )
			ORDER BY id DESC
		", ARRAY_A);		

		return $items;		
	}

	static function getAllPublished( $limit = null, $offset = null ) {
		global $wpdb;	
		$table = $wpdb->prefix . self::TABLENAME;

		$limit  = intval($limit); 
		$offset = intval($offset);
		$limit_sql = $limit ? " LIMIT $limit OFFSET $offset" : '';

		$items = $wpdb->get_results("
			SELECT * 
			FROM $table
			WHERE status = 'published'
			   OR status = 'changed'
			   OR status = 'relisted'
			ORDER BY id DESC
			$limit_sql
		", ARRAY_A);		

		return $items;		
	}

	// unused
	static function getAllArchived() {
		global $wpdb;	
		$table = $wpdb->prefix . self::TABLENAME;

		$items = $wpdb->get_results("
			SELECT * 
			FROM $table
			WHERE status = 'archived'
			ORDER BY id DESC
		", ARRAY_A);		

		return $items;		
	}

	static function getAllEnded( $limit = null, $offset = null ) {
		global $wpdb;	
		$table = $wpdb->prefix . self::TABLENAME;

		$limit  = intval($limit); 
		$offset = intval($offset);
		$limit_sql = $limit ? " LIMIT $limit OFFSET $offset" : '';

		$items = $wpdb->get_results("
			SELECT * 
			FROM $table
			WHERE status = 'ended'
			ORDER BY id DESC
			$limit_sql
		", ARRAY_A);		

		return $items;		
	}

	static function getAllRelisted() {
		global $wpdb;	
		$table = $wpdb->prefix . self::TABLENAME;

		$items = $wpdb->get_results("
			SELECT * 
			FROM $table
			WHERE status = 'relisted'
			ORDER BY id DESC
		", ARRAY_A);		

		return $items;		
	}

	static function getAllWithStatus( $status ) {
		global $wpdb;	
		$table = $wpdb->prefix . self::TABLENAME;

		$items = $wpdb->get_results( $wpdb->prepare("
			SELECT * 
			FROM $table
			WHERE status = %s
			ORDER BY id DESC
		", $status 
		), ARRAY_A );

		return $items;		
	}



	static function getAllScheduled( $pending_only = false ) {
		global $wpdb;	
		$table = $wpdb->prefix . self::TABLENAME;


		// by default only return pending listings - relist dates in the past
		$condition = $pending_only ? 'AND relist_date <= NOW()' : ''; 

		$wpdb->query("SET time_zone='+0:00'"); // tell SQL to use GMT
		$items = $wpdb->get_results("
			SELECT * 
			FROM $table
			WHERE status = 'ended'
			  AND relist_date IS NOT NULL
			  $condition
			ORDER BY relist_date ASC
		", ARRAY_A);		
		$wpdb->query("SET time_zone='SYSTEM'"); // revert back to original

		return $items;		
	}

	static function getAllWithProfile( $profile_id ) {
		global $wpdb;	
		$table = $wpdb->prefix . self::TABLENAME;

		$items = $wpdb->get_results( $wpdb->prepare("
			SELECT * 
			FROM $table
			WHERE profile_id = %s
			ORDER BY id DESC
		", $profile_id 
		), ARRAY_A );

		return $items;		
	}

	// get limited $item arrays for applyProfileToItem()
	static function getAllPreparedWithProfile( $profile_id ) {
		global $wpdb;	
		$table = $wpdb->prefix . self::TABLENAME;

		$items = $wpdb->get_results( $wpdb->prepare("
			SELECT id, post_id, locked, status 
			FROM $table
			WHERE status = 'prepared'
			  AND profile_id = %s
			ORDER BY id DESC
		", $profile_id 
		), ARRAY_A );

		return $items;		
	}

	// get limited $item arrays for applyProfileToItem()
	static function getAllVerifiedWithProfile( $profile_id ) {
		global $wpdb;	
		$table = $wpdb->prefix . self::TABLENAME;

		$items = $wpdb->get_results( $wpdb->prepare("
			SELECT id, post_id, locked, status 
			FROM $table
			WHERE status = 'verified'
			  AND profile_id = %s
			ORDER BY id DESC
		", $profile_id 
		), ARRAY_A );

		return $items;		
	}

	// get limited $item arrays for applyProfileToItem()
	static function getAllPublishedWithProfile( $profile_id ) {
		global $wpdb;	
		$table = $wpdb->prefix . self::TABLENAME;

		$items = $wpdb->get_results( $wpdb->prepare("
			SELECT id, post_id, locked, status 
			FROM $table
			WHERE ( status = 'published' OR status = 'changed' )
			  AND profile_id = %s
			ORDER BY id DESC
		", $profile_id 
		), ARRAY_A );

		return $items;		
	}

	// get limited $item arrays for applyProfileToItem()
	static function getAllEndedWithProfile( $profile_id ) {
		global $wpdb;	
		$table = $wpdb->prefix . self::TABLENAME;

		$items = $wpdb->get_results( $wpdb->prepare("
			SELECT id, post_id, locked, status 
			FROM $table
			WHERE status = 'ended'
			  AND profile_id = %s
			ORDER BY id DESC
		", $profile_id 
		), ARRAY_A );

		return $items;		
	}

	// count items using profile and status (optimized version of the above methods)
	static function countItemsUsingProfile( $profile_id, $status = false ) {
		global $wpdb;	
		$table = $wpdb->prefix . self::TABLENAME;

		$where_and_sql = $status ? " AND status = '".esc_sql($status)."' " : '';
		if ( $status == 'locked' )    $where_and_sql = " AND locked = '1' ";
		if ( $status == 'published' ) $where_and_sql = " AND ( status = 'published' OR status = 'changed' ) ";

		$item_count = $wpdb->get_var( $wpdb->prepare("
			SELECT count(id) 
			FROM $table
			WHERE profile_id = %s
			$where_and_sql
		", $profile_id ) );

		return $item_count;
	}


	// unused
	static function getAllPreparedWithTemplate( $template ) {
		global $wpdb;	
		$table = $wpdb->prefix . self::TABLENAME;

		$template = esc_sql( $template );
		$items = $wpdb->get_results("
			SELECT * 
			FROM $table
			WHERE status = 'prepared'
			  AND template LIKE '%$template'
			ORDER BY id DESC
		", ARRAY_A);		

		return $items;		
	}

	// unused
	static function getAllVerifiedWithTemplate( $template ) {
		global $wpdb;	
		$table = $wpdb->prefix . self::TABLENAME;

		$template = esc_sql( $template );
		$items = $wpdb->get_results("
			SELECT * 
			FROM $table
			WHERE status = 'verified'
			  AND template LIKE '%$template'
			ORDER BY id DESC
		", ARRAY_A);		

		return $items;		
	}

	static function getAllPublishedWithTemplate( $template ) {
		global $wpdb;	
		$table = $wpdb->prefix . self::TABLENAME;

		$template = esc_sql( $template );
		$items = $wpdb->get_results("
			SELECT * 
			FROM $table
			WHERE ( status = 'published' OR status = 'changed' )
			  AND template LIKE '%$template'
			ORDER BY id DESC
		", ARRAY_A);		

		return $items;		
	}

	// count items using template and status (optimized version of the above methods)
	static function countItemsUsingTemplate( $template, $status = false ) {
		global $wpdb;	
		$table = $wpdb->prefix . self::TABLENAME;

		$where_and_sql = $status ? " AND status = '".esc_sql($status)."' " : '';
		if ( $status == 'locked' )    $where_and_sql = " AND locked = '1' ";
		if ( $status == 'published' ) $where_and_sql = " AND ( status = 'published' OR status = 'changed' ) ";

		$template = esc_sql( $template );
		$item_count = $wpdb->get_var("
			SELECT count(id) 
			FROM $table
			WHERE template LIKE '%$template'
			$where_and_sql
		");

		return $item_count;
	}


	static function getAllPastEndDate() {
		global $wpdb;	
		$table = $wpdb->prefix . self::TABLENAME;

		$wpdb->query("SET time_zone='+0:00'"); // tell SQL to use GMT
		$items = $wpdb->get_results("
			SELECT id 
			FROM $table
			WHERE status <> 'ended'
			  AND status <> 'sold'
			  AND status <> 'archived'
			  AND listing_duration <> 'GTC'
			  AND end_date < NOW()
			ORDER BY id DESC
		", ARRAY_A);		
		$wpdb->query("SET time_zone='SYSTEM'"); // revert back to original

		return $items;		
	}

	static function getAllOldListingsToBeArchived() {
		global $wpdb;	
		$table = $wpdb->prefix . self::TABLENAME;

		$items = $wpdb->get_results("
			SELECT id 
			FROM $table
			WHERE ( status = 'ended' OR status = 'sold' )
			  AND end_date < NOW() - INTERVAL 90 DAY
			ORDER BY id DESC
		", ARRAY_A);		

		return $items;		
	}


	static function getAllDuplicateProducts() {
		global $wpdb;	
		$table = $wpdb->prefix . self::TABLENAME;

		$items = $wpdb->get_results("
			SELECT post_id, account_id, COUNT(*) c
			FROM $table
			WHERE status <> 'archived'
			GROUP BY post_id, account_id
			HAVING c > 1
			LIMIT 1000
		", OBJECT_K);		

		// if ( ! empty($items) ) {
		// 	foreach ($items as &$item) {
				
		// 		$listings = WPLE_ListingQueryHelper::getAllListingsFromPostID( $item->post_id );
		// 		$item->listings = $listings;

		// 	}
		// }

		return $items;		
	}


	static function getAll() {
		global $wpdb;
		$table = $wpdb->prefix . self::TABLENAME;

		$items = $wpdb->get_results("
			SELECT *
			FROM $table
			ORDER BY id DESC
		", ARRAY_A);

		return $items;
	}

	static function getWhere( $column, $value ) {
		global $wpdb;	
		$table = $wpdb->prefix . self::TABLENAME;

		$items = $wpdb->get_results( $wpdb->prepare("
			SELECT *
			FROM $table
			WHERE $column = %s
		", $value 
		), OBJECT_K);		

		return $items;
	}

	static function getItemsByIdArray( $listing_ids ) {
		global $wpdb;	
		$table = $wpdb->prefix . self::TABLENAME;

		if ( ! is_array( $listing_ids )  ) return array();
		if ( sizeof( $listing_ids ) == 0 ) return array();

		// sanitize input
		$id_list = implode( ',', esc_sql( $listing_ids ) ); 

		// $where = ' id = ' . join( ' OR id = ', $listing_ids);
		$items = $wpdb->get_results("
			SELECT * 
			FROM $table
			WHERE id IN ( $id_list )
			ORDER BY id DESC
		", ARRAY_A);		
		echo $wpdb->last_error;

		return $items;		
	}



	static function getStatus( $id ) {
		global $wpdb;
		$table = $wpdb->prefix . self::TABLENAME;

		$item = $wpdb->get_var( $wpdb->prepare("
			SELECT status
			FROM $table
			WHERE id = %s
		", $id ) );
		return $item;
	}

	static function getAccountID( $id ) {
		global $wpdb;
		$table = $wpdb->prefix . self::TABLENAME;

		// if there are multiple listing IDs, use the first one
		if ( is_array($id) ) $id = $id[0];

		$item = $wpdb->get_var( $wpdb->prepare("
			SELECT account_id
			FROM $table
			WHERE id = %s
		", $id ) );
		return $item;
	}

	static function getEbayIDFromPostID( $post_id ) {
		global $wpdb;
		$table = $wpdb->prefix . self::TABLENAME;

		$item = $wpdb->get_var( $wpdb->prepare("
			SELECT ebay_id
			FROM $table
			WHERE post_id    = %s
			  AND status <> 'archived'
		", $post_id ) );
		return $item;
	}

	static function getStatusFromPostID( $post_id ) {
		global $wpdb;
		$table = $wpdb->prefix . self::TABLENAME;

		$item = $wpdb->get_var( $wpdb->prepare("
			SELECT status
			FROM $table
			WHERE post_id = %s
			  AND status <> 'archived'
			ORDER BY id DESC
		", $post_id ) );
		return $item;
	}

	static function getListingIDFromPostID( $post_id ) {
		global $wpdb;
		$table = $wpdb->prefix . self::TABLENAME;

		$item = $wpdb->get_var( $wpdb->prepare("
			SELECT id
			FROM $table
			WHERE post_id = %s
			  AND status <> 'archived'
			ORDER BY id DESC
		", $post_id ) );
		return $item;
	}


	static function getAllListingsFromPostID( $post_id ) {
		global $wpdb;
		$table = $wpdb->prefix . self::TABLENAME;

		$items = $wpdb->get_results( $wpdb->prepare("
			SELECT *
			FROM $table
			WHERE post_id = %s
			  AND status <> 'archived'
			ORDER BY id DESC
		", $post_id ) );
		return $items;
	}

	static function getAllListingsFromPostOrParentID( $post_id ) {
		global $wpdb;
		$table = $wpdb->prefix . self::TABLENAME;

		$items = $wpdb->get_results( $wpdb->prepare("
			SELECT *
			FROM $table
			WHERE status <> 'archived'
			  AND ( post_id = %s
			   OR parent_id = %s )
			ORDER BY id ASC
		", $post_id, $post_id ) );
		return $items;
	}

	static function getAllListingsFromParentID( $post_id ) {
		global $wpdb;
		$table = $wpdb->prefix . self::TABLENAME;

		$items = $wpdb->get_results( $wpdb->prepare("
			SELECT *
			FROM $table
			WHERE parent_id = %s
			ORDER BY id DESC
		", $post_id ) );
		return $items;
	}

	static function getAllListingsForProductAndAccount( $post_id, $account_id ) {
		global $wpdb;
		$table = $wpdb->prefix . self::TABLENAME;

		$items = $wpdb->get_results( $wpdb->prepare("
			SELECT *
			FROM $table
			WHERE post_id    = %s
			  AND account_id = %s
			  AND status <> 'archived'
			ORDER BY id DESC
		", $post_id, $account_id ) );
		return $items;
	}

	static function getViewItemURLFromPostID( $post_id ) {
		global $wpdb;
		$table = $wpdb->prefix . self::TABLENAME;

		$item = $wpdb->get_var( $wpdb->prepare("
			SELECT ViewItemURL
			FROM $table
			WHERE post_id = %s
			  AND status <> 'archived'
			ORDER BY id DESC
		", $post_id ) );
		return $item;
	}




	static function getItemForPreview() {
		global $wpdb;
		$table = $wpdb->prefix . self::TABLENAME;

		$item = $wpdb->get_row("
			SELECT *
			FROM $table
			ORDER BY id DESC
			LIMIT 1
		", ARRAY_A);

		if ( !empty($item) ) $item['profile_data'] = WPL_Model::decodeObject( $item['profile_data'], true );
		// $item['details'] = WPL_Model::decodeObject( $item['details'] );

		return $item;
	}

	// probably unused
	static function getTitleFromItemID( $id ) {
		global $wpdb;
		$table = $wpdb->prefix . self::TABLENAME;

		$item = $wpdb->get_var( $wpdb->prepare("
			SELECT auction_title
			FROM $table
			WHERE ebay_id = %s
		", $id ) );
		return $item;
	}

	// helper method to get untampered post excerpt
	static function getRawPostExcerpt( $post_id ) {
		global $wpdb;	
		$excerpt = $wpdb->get_var( $wpdb->prepare("
			SELECT post_excerpt 
			FROM {$wpdb->prefix}posts
			WHERE ID = %s
		", $post_id ) );

		return $excerpt;		
	}


	static function productExistsInAccount( $post_id, $account_id ) {
		global $wpdb;	
		$table = $wpdb->prefix . self::TABLENAME;

		$item = $wpdb->get_row( $wpdb->prepare("
			SELECT *
			FROM $table
			WHERE post_id    = %s
			  AND account_id = %s
			  AND status <> 'archived'
		", $post_id, $account_id 
		), OBJECT);		

		return $item;
	}

	static function selectedProducts() {
		global $wpdb;	
		$table = $wpdb->prefix . self::TABLENAME;

		$items = $wpdb->get_results("
			SELECT * 
			FROM $table
			WHERE status = 'selected'
			   OR status = 'reselected'
			   OR status = 'changed_profile'
			ORDER BY id DESC
		", ARRAY_A);		

		return $items;		
	}

	// find listing by current item ID - fall back to previous item ID
	static function findItemByEbayID( $id, $decode_details = true ) {
		global $wpdb;
		$table = $wpdb->prefix . self::TABLENAME;

		$item = $wpdb->get_row( $wpdb->prepare("
			SELECT *
			FROM $table
			WHERE ebay_id = %s
		", $id ) );

		// if no listing was found, check previous item IDs 
		if ( ! $item ) {
			$id = esc_sql( $id );
			$item = $wpdb->get_row("
				SELECT *
				FROM $table
				WHERE history LIKE '%$id%'
			");
		}

		if (!$item) return false;
		if (!$decode_details) return $item;

		$item->profile_data = WPL_Model::decodeObject( $item->profile_data, true );
		$item->details      = WPL_Model::decodeObject( $item->details );

		return $item;
	}




	static function deleteItem( $id ) {
		global $wpdb;
		$table = $wpdb->prefix . self::TABLENAME;

		$wpdb->query( $wpdb->prepare("
			DELETE
			FROM $table
			WHERE id = %s
		", $id ) );
	}

	static public function cleanArchive() {
		global $wpdb;
		$table = $wpdb->prefix . self::TABLENAME;

		$wpdb->query("DELETE FROM $table WHERE status = 'archived' AND ( ebay_id = '' OR ebay_id IS NULL ) ");
		echo $wpdb->last_error;

		return $wpdb->rows_affected;
	} // cleanArchive()

	// set locked status of all items at once
	static public function lockAll( $locked = false ) {
		global $wpdb;
		$table = $wpdb->prefix . self::TABLENAME;

		$locked = $locked ? 1 : 0;

		$result = $wpdb->query( $wpdb->prepare("UPDATE {$table} SET locked = %d WHERE status <> 'archived' ", $locked ) );
		echo $wpdb->last_error;
		return $result;
	}



	static function getItemsForGallery( $type = 'new', $related_to_id, $limit = 12 ) {
		global $wpdb;	
		$table = $wpdb->prefix . self::TABLENAME;

		switch ($type) {
			case 'ending':
				$wpdb->query("SET time_zone='+0:00'"); // tell SQL to use GMT
				$where_sql = "WHERE status = 'published' AND end_date < NOW()";
				$order_sql = "ORDER BY end_date DESC";
				break;
			
			case 'featured':
				$where_sql = "	JOIN {$wpdb->prefix}postmeta pm ON ( li.post_id = pm.post_id )
								WHERE status = 'published' 
								  AND pm.meta_key = '_featured'
								  AND pm.meta_value = 'yes'
							";
				$order_sql = "ORDER BY date_published, end_date DESC";
				break;
			
			case 'related': // combines upsell and crossell
				$listing         = ListingsModel::getItem($related_to_id);
				$upsell_ids      = get_post_meta( $listing['post_id'], '_upsell_ids', true );
				$crosssell_ids   = get_post_meta( $listing['post_id'], '_crosssell_ids', true );
				$inner_where_sql = '1 = 0';

				if ( is_array( $upsell_ids ) )
				foreach ($upsell_ids as $post_id) {
					$post_id = esc_sql( $post_id );
					$inner_where_sql .= ' OR post_id = "'.$post_id.'" ';
				}

				if ( is_array( $crosssell_ids ) )
				foreach ($crosssell_ids as $post_id) {
					$post_id = esc_sql( $post_id );
					$inner_where_sql .= ' OR post_id = "'.$post_id.'" ';
				}

				$where_sql = "	WHERE status = 'published' 
								  AND ( $inner_where_sql )
							";
				$order_sql = "ORDER BY date_published, end_date DESC";
				break;
			
			case 'new':
			default:
				$where_sql = "WHERE status = 'published' ";
				$order_sql = "ORDER BY date_published DESC";
				break;
		}

		$limit = esc_sql( $limit );
		$items = $wpdb->get_results("
			SELECT DISTINCT li.*
			FROM $table li
			$where_sql
			$order_sql
			LIMIT $limit
		", ARRAY_A);		
		// echo "<pre>";print_r($wpdb->last_query);echo"</pre>";#die();

		if ( $type == 'ending' )
			$wpdb->query("SET time_zone='SYSTEM'"); // revert back to original

		return $items;		
	}


	static function getStatusSummary() {
		global $wpdb;
		$table = $wpdb->prefix . self::TABLENAME;

		$result = $wpdb->get_results("
			SELECT status, count(*) as total
			FROM $table
			GROUP BY status
		");

		$summary = new stdClass();
		// $summary->prepared = false;
		// $summary->changed = false;
		foreach ($result as $row) {
			$status = $row->status;
			if ( ! $status ) continue;
			$summary->$status = $row->total;
		}

		// count locked items
		$locked = $wpdb->get_var("
			SELECT COUNT( id ) AS locked
			FROM $table
			WHERE locked = '1'
			  AND status <> 'archived'
		");
		$summary->locked = $locked;

		// count relist candidates
		$relist = $wpdb->get_var("
			SELECT COUNT( id ) AS relist
			FROM $table
			WHERE ( status = 'ended' OR status = 'sold' ) 
			  AND ( quantity - quantity_sold > 0 )
		");
		$summary->relist = $relist;

		// count items scheduled for autorelist
		$autorelist = $wpdb->get_var("
			SELECT COUNT( id ) AS relist
			FROM $table
			WHERE relist_date IS NOT NULL
		");
		$summary->autorelist = $autorelist;

		// count total items as well
		$total_items = $wpdb->get_var("
			SELECT COUNT( id ) AS total_items
			FROM $table
			WHERE status <> 'archived'
		");
		$summary->total_items = $total_items;

		return $summary;
	}


	static function getPageItems( $current_page, $per_page ) {
		global $wpdb;
		$table = $wpdb->prefix . self::TABLENAME;

        $orderby  = (!empty($_REQUEST['orderby'])) ? esc_sql( $_REQUEST['orderby'] ) : 'id';
        $order    = (!empty($_REQUEST['order']))   ? esc_sql( $_REQUEST['order']   ) : 'desc';
        $offset   = ( $current_page - 1 ) * $per_page;
        $per_page = esc_sql( $per_page );

        $join_sql  = '';
        $where_sql = '';

        // filter listing_status
		$listing_status = ( isset($_REQUEST['listing_status']) ? esc_sql( $_REQUEST['listing_status'] ) : 'all');
		if ( ! $listing_status || $listing_status == 'all' ) {
			$where_sql = "WHERE status <> 'archived' ";
		} elseif ( $listing_status == 'relist' ) {
			$where_sql = "WHERE ( status = 'ended' OR status = 'sold' ) AND ( quantity - quantity_sold > 0 ) "; 
		} elseif ( $listing_status == 'autorelist' ) {
			$where_sql = "WHERE relist_date IS NOT NULL ";
		} elseif ( $listing_status == 'locked' ) {
			$where_sql = "WHERE locked = '1' AND status <> 'archived' ";
		} else {
			$where_sql = "WHERE status = '".$listing_status."' ";
		} 

        // filter profile_id
		$profile_id = ( isset($_REQUEST['profile_id']) ? esc_sql( $_REQUEST['profile_id'] ) : false);
		if ( $profile_id ) {
			$where_sql .= "
				 AND l.profile_id = '".$profile_id."'
			";
		} 

        // filter account_id
		$account_id = ( isset($_REQUEST['account_id']) ? esc_sql( $_REQUEST['account_id'] ) : false);
		if ( $account_id ) {
			$where_sql .= "
				 AND l.account_id = '".$account_id."'
			";
		} 

        // filter search_query
		$search_query = ( isset($_REQUEST['s']) ? esc_sql( $_REQUEST['s'] ) : false);
		if ( $search_query ) {
			$join_sql = "
				LEFT JOIN {$wpdb->prefix}ebay_profiles p  ON l.profile_id =  p.profile_id
				LEFT JOIN {$wpdb->prefix}postmeta      pm ON l.post_id    = pm.post_id AND pm.meta_key = '_sku'
			";
			$where_sql .= "
				 AND ( l.auction_title LIKE '%".$search_query."%'
				    OR l.template     LIKE '%".$search_query."%'
				    OR p.profile_name LIKE '%".$search_query."%'
				    OR l.history      LIKE '%".$search_query."%'
					OR l.ebay_id          = '".$search_query."'
					OR l.auction_type     = '".$search_query."'
					OR l.listing_duration = '".$search_query."'
					OR l.status           = '".$search_query."'
					OR l.post_id          = '".$search_query."'
					OR pm.meta_value      = '".$search_query."' )
			";
		} 


        // get items
		$items = $wpdb->get_results("
			SELECT *, l.details as details
			FROM $table l
            $join_sql 
            $where_sql
			ORDER BY $orderby $order
            LIMIT $offset, $per_page
		", ARRAY_A);

		// get total items count - if needed
		if ( ( $current_page == 1 ) && ( count( $items ) < $per_page ) ) {
			$total_items = count( $items );
		} else {
			$total_items = $wpdb->get_var("
				SELECT COUNT(*)
				FROM $table l
	            $join_sql
	            $where_sql
				ORDER BY $orderby $order
			");			
		}

		$result = new stdClass();
		$result->items       = $items;
		$result->total_items = $total_items;

		return $result;
	} // getPageItems()



} // class WPLE_ListingQueryHelper
