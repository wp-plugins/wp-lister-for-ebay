<?php
/**
 * EbayMessagesModel class
 *
 * responsible for managing messages and talking to ebay
 * 
 */

class EbayMessagesModel extends WPL_Model {
	var $_session;
	var $_cs;

	var $count_total    = 0;
	var $count_skipped  = 0;
	var $count_updated  = 0;
	var $count_inserted = 0;
	var $count_failed   = 0;
	var $report         = array();
	var $ModTimeTo      = false;
	var $ModTimeFrom    = false;
	var $NumberOfDays   = false;

	var $total_items;
	var $total_pages;
	var $current_page;
	var $current_lastdate;

	function EbayMessagesModel() {
		global $wpl_logger;
		$this->logger = &$wpl_logger;

		global $wpdb;
		$this->tablename = $wpdb->prefix . 'ebay_messages';
	}


	function updateMessages( $session, $days = null, $current_page = 1, $message_ids = false ) {
		$this->logger->info('*** updateMessages('.$days.') - page '.$current_page);

		$this->initServiceProxy($session);

		// set request handler
		$this->_cs->setHandler( 'MyMessagesMessageType', array( & $this, 'handleMyMessagesMessageType' ) );
		// $this->_cs->setHandler( 'PaginationResultType', array( & $this, 'handlePaginationResultType' ) );

		// build request
		$req = new GetMyMessagesRequestType();
		// $req->setMessageRole( 'Seller' );
		// $req->setIncludeContainingMessage(true);

		// check if we need to calculate lastdate
		if ( $this->current_lastdate ) {
			$lastdate = $this->current_lastdate;
			$this->logger->info('used current_lastdate from last run: '.$lastdate);
		} else {

			// period 30 days, which is the maximum allowed
			$now = time();
			// $lastdate = $this->getDateOfLastMessage();
			// $this->logger->info('getDateOfLastMessage() returned: '.$lastdate);
			$lastdate = null;
			if ($lastdate) $lastdate = mysql2date('U', $lastdate);

			// if last date is older than 30 days, fall back to default
			if ( $lastdate < $now - 3600 * 24 * 30 ) {
				$this->logger->info('resetting lastdate - fall back default ');
				$lastdate = false;
			} 

		}

		// save lastdate for next page
		$this->current_lastdate = $lastdate;

		/*
		// fetch messages by IDs
		if ( is_array( $message_ids ) ) {
			$MessageIDArray = new MessageIDArrayType();
			foreach ( $message_ids as $id ) {
				$message = $this->getItem( $id );
				$MessageIDArray->addMessageID( $message['message_id'] );
			}
			$req->setMessageIDArray( $MessageIDArray );
		// parameter $days
		} elseif ( $days ) {
			$req->NumberOfDays  = $days;
			$this->NumberOfDays = $days;
			$this->logger->info('NumberOfDays: '.$req->NumberOfDays);

		// default: messages since last change
		} elseif ( $lastdate ) {
			$req->ModTimeFrom  = gmdate( 'Y-m-d H:i:s', $lastdate );
			$req->ModTimeTo    = gmdate( 'Y-m-d H:i:s', time() );
			$this->ModTimeFrom = $req->ModTimeFrom;
			$this->ModTimeTo   = $req->ModTimeTo;
			$this->logger->info('lastdate: '.$lastdate);
			$this->logger->info('ModTimeFrom: '.$req->ModTimeFrom);
			$this->logger->info('ModTimeTo: '.$req->ModTimeTo);

		// fallback: one day (max allowed by ebay: 30 days)
		} else {
			$days = 1;
			$req->NumberOfDays  = $days;
			$this->NumberOfDays = $days;
			$this->logger->info('NumberOfDays (fallback): '.$req->NumberOfDays);
		}
		*/

		// $req->DetailLevel = $Facet_DetailLevelCodeType->ReturnMessages;
		// if ( ! $this->is_ajax() ) $req->setDetailLevel('ReturnSummary');
		
		// $req->setFolderID( 0 ); // Inbox (FolderID = 0) and Sent (FolderID = 1)
		// $req->setDetailLevel( 'ReturnSummary' );
		// $req->setDetailLevel( 'ReturnMessages' );
		$req->setDetailLevel( 'ReturnHeaders' );

		// set pagination for first page
		$items_per_page = 100; // should be set to 200 for production
		$this->current_page = $current_page;

		$Pagination = new PaginationType();
		$Pagination->setEntriesPerPage( $items_per_page );
		$Pagination->setPageNumber( $this->current_page );
		$req->setPagination( $Pagination );


		// get messages (single page)
		$this->logger->info('fetching messages - page '.$this->current_page);
		$res = $this->_cs->GetMyMessages( $req );

		$this->total_pages = $res->PaginationResult->TotalNumberOfPages;
		$this->total_items = $res->PaginationResult->TotalNumberOfEntries;

		// get message with pagination helper (doesn't work as expected)
		// EbatNs_PaginationHelper($proxy, $callName, $request, $responseElementToMerge = '__COUNT_BY_HANDLER', $maxEntries = 200, $pageSize = 200, $initialPage = 1)
		// $helper = new EbatNs_PaginationHelper( $this->_cs, 'GetMyMessages', $req, 'MessageArray', 20, 10, 1);
		// $res = $helper->QueryAll();


		// handle response and check if successful
		if ( $this->handleResponse($res) ) {
			$this->logger->info( "*** Messages updated successfully." );
			// $this->logger->info( "*** PaginationResult:".print_r($res->PaginationResult,1) );
			// $this->logger->info( "*** processed response:".print_r($res,1) );

			$this->logger->info( "*** current_page: ".$this->current_page );
			$this->logger->info( "*** total_pages: ".$this->total_pages );
			$this->logger->info( "*** total_items: ".$this->total_items );

			// fetch next page recursively - only in days mode
			/*
			if ( $res->HasMoreMessages ) {
				$this->current_page++;
				$this->updateMessages( $session, $days, $this->current_page );
			}
			*/

		} else {
			$this->logger->error( "Error on messages update".print_r( $res, 1 ) );			
		}
	}

	function handleMyMessagesMessageType( $type, & $Detail ) {
		//global $wpdb;
		//#type $Detail MyMessagesMessageType
		// $this->logger->info( 'handleMyMessagesMessageType()'.print_r( $Detail, 1 ) );

		// map MyMessagesMessageType to DB columns
		$data = $this->mapItemDetailToDB( $Detail );
		if (!$data) return true;
		$this->logger->info( 'handleMyMessagesMessageType() mapped data: '.print_r( $data, 1 ) );

		$this->insertOrUpdate( $data, $Detail );

		// this will remove item from result
		return true;
	}

	function insertOrUpdate( $data, $Detail ) {
		global $wpdb;

		// try to get existing message by message id
		$message = $this->getMessageByMessageID( $data['message_id'] );

		if ( $message ) {

			// update existing message
			$this->logger->info( 'update message #'.$data['message_id'] );
			$wpdb->update( $this->tablename, $data, array( 'message_id' => $data['message_id'] ) );
			$insert_id = $message['id'];

			$this->addToReport( 'updated', $data );
		
		} else {
		
			// create new message
			$this->logger->info( 'insert message #'.$data['message_id'] );
			$result = $wpdb->insert( $this->tablename, $data );
			if ( ! $result ) {
				$this->logger->error( 'insert message failed - MySQL said: '.$wpdb->last_error );
				$this->addToReport( 'error', $data, false, $wpdb->last_error );
				return false;
			}
			$Details       = maybe_unserialize( $data['details'] );
			$message_post_id = false;
			$insert_id     = $wpdb->insert_id;
			// $this->logger->info( 'insert_id: '.$insert_id );

			$this->addToReport( 'inserted', $data, $message_post_id );

		}

	} // insertOrUpdate()


	// add message history entry
	function addHistory( $message_id, $action, $msg, $details = array(), $success = true ) {
		global $wpdb;

		// build history record
		$record = new stdClass();
		$record->action  = $action;
		$record->msg     = $msg;
		$record->details = $details;
		$record->success = $success;
		$record->time    = time();

		// load history
		$history = $wpdb->get_var( "
			SELECT history
			FROM $this->tablename
			WHERE message_id = '$message_id'
		" );

		// init with empty array
		$history = maybe_unserialize( $history );
		if ( ! $history ) $history = array();

		// prevent fatal error if $history is not an array
		if ( ! is_array( $history ) ) {
			$this->logger->error( "invalid history value in EbayMessagesModel::addHistory(): ".$history);

			// build history record
			$rec = new stdClass();
			$rec->action  = 'reset_history';
			$rec->msg     = 'Corrupted history data was cleared';
			$rec->details = array();
			$rec->success = 'ERROR';
			$rec->time    = time();

			$history = array();
			$history[] = $record;
		}

		// add record
		$history[] = $record;

		// update history
		$history = serialize( $history );
		$wpdb->query( "
			UPDATE $this->tablename
			SET history = '$history'
			WHERE message_id = '$message_id'
		" );

	}

	function mapItemDetailToDB( $Detail ) {
		//#type $Detail MyMessagesMessageType

		$data['message_id']      = $Detail->MessageID;
		$data['received_date']   = $this->convertEbayDateToSql( $Detail->ReceiveDate );
		$data['expiration_date'] = $this->convertEbayDateToSql( $Detail->ExpirationDate );
		$data['subject']         = $Detail->Subject;
		$data['sender']          = $Detail->Sender;
		$data['flag_read']       = $Detail->Read;
		$data['flag_replied']    = $Detail->Replied;
		$data['flag_flagged']    = $Detail->Flagged;
		$data['item_title']      = $Detail->ItemTitle;
		$data['item_id']         = $Detail->ItemID;
		$data['folder_id']       = $Detail->Folder->FolderID;
		$data['msg_text']        = $Detail->Text;
		$data['msg_content']     = $Detail->Content;
		$data['response_url']    = $Detail->ResponseDetails->ResponseURL;

        // save GetMyMessages reponse in details
		$data['details'] = $this->encodeObject( $Detail );

		$this->logger->info( "IMPORTING message #".$Detail->MessageID );							

		return $data;
	}


	function addToReport( $status, $data, $wp_message_id = false, $error = false ) {

		$rep = new stdClass();
		$rep->status        = $status;
		$rep->message_id    = $data['message_id'];
		$rep->received_date = $data['received_date'];
		$rep->total         = $data['total'];
		$rep->data          = $data;
		// $rep->newstock   = $newstock;
		$rep->wp_message_id = $wp_message_id;
		$rep->error         = $error;

		$this->report[] = $rep;

		switch ($status) {
			case 'skipped':
				$this->count_skipped++;
				break;
			case 'updated':
				$this->count_updated++;
				break;
			case 'inserted':
				$this->count_inserted++;
				break;
			case 'error':
			case 'failed':
				$this->count_failed++;
				break;
		}
		$this->count_total++;

	}

	function getHtmlTimespan() {
		if ( $this->NumberOfDays ) {
			return sprintf( __('the last %s days','wplister'), $this->NumberOfDays );
		} elseif ( $this->ModTimeFrom ) {
			return sprintf( __('from %s to %s','wplister'), $this->ModTimeFrom , $this->ModTimeTo );
		}
	}

	function getHtmlReport() {

		$html  = '<div id="ebay_message_report" style="display:none">';
		$html .= '<br>';
		$html .= __('New messages created','wplister') .': '. $this->count_inserted .' '. '<br>';
		$html .= __('Existing messages updated','wplister')  .': '. $this->count_updated  .' '. '<br>';
		$html .= '<br>';

		$html .= '<table style="width:99%">';
		$html .= '<tr>';
		$html .= '<th align="left">'.__('Received at','wplister').'</th>';
		$html .= '<th align="left">'.__('Message ID','wplister').'</th>';
		$html .= '<th align="left">'.__('Subject','wplister').'</th>';
		$html .= '<th align="left">'.__('eBay ID','wplister').'</th>';
		$html .= '<th align="left">'.__('Title','wplister').'</th>';
		$html .= '<th align="left">'.__('Sender','wplister').'</th>';
		$html .= '</tr>';
		
		foreach ($this->report as $item) {
			$html .= '<tr>';
			$html .= '<td>'.$item->received_date.'</td>';
			$html .= '<td>'.$item->message_id.'</td>';
			$html .= '<td>'.@$item->data['subject'].'</td>';
			$html .= '<td>'.@$item->data['item_id'].'</td>';
			$html .= '<td>'.@$item->data['item_title'].'</td>';
			$html .= '<td>'.@$item->data['sender'].'</td>';
			$html .= '</tr>';
			if ( $item->error ) {
				$html .= '<tr>';
				$html .= '<td colspan="7" style="color:darkred;">ERROR: '.$item->error.'</td>';
				$html .= '</tr>';			
			}
		}

		$html .= '</table>';
		$html .= '</div>';
		return $html;
	}

	/* the following methods could go into another class, since they use wpdb instead of EbatNs_DatabaseProvider */

	function getAll() {
		global $wpdb;
		$profiles = $wpdb->get_results( "
			SELECT *
			FROM $this->tablename
			ORDER BY id DESC
		", ARRAY_A );

		return $profiles;
	}

	function getItem( $id ) {
		global $wpdb;

		$item = $wpdb->get_row( "
			SELECT *
			FROM $this->tablename
			WHERE id = '$id'
		", ARRAY_A );

		// decode MyMessagesMessageType object with eBay classes loaded
		$item['details'] = $this->decodeObject( $item['details'], false, true );

		return $item;
	}

	function getMessageByMessageID( $message_id ) {
		global $wpdb;

		$message = $wpdb->get_row( "
			SELECT *
			FROM $this->tablename
			WHERE message_id = '$message_id'
		", ARRAY_A );

		return $message;
	}

	function getMessageByPostID( $post_id ) {
		global $wpdb;

		$message = $wpdb->get_row( "
			SELECT *
			FROM $this->tablename
			WHERE post_id = '$post_id'
		", ARRAY_A );

		return $message;
	}

	function getDateOfLastMessage() {
		global $wpdb;
		$lastdate = $wpdb->get_var( "
			SELECT LastTimeModified
			FROM $this->tablename
			ORDER BY LastTimeModified DESC LIMIT 1
		" );

		// if there are no messages yet, check the date of the last transaction
		if ( ! $lastdate ) {
			$tm = new TransactionsModel();
			$lastdate = $tm->getDateOfLastCreatedTransaction();
			if ($lastdate) {
				// add one minute to prevent importing the same transaction again
				$lastdate = mysql2date('U', $lastdate) + 60;
				$lastdate = date('Y-m-d H:i:s', $lastdate );
			}
		}
		return $lastdate;
	}

	function deleteItem( $id ) {
		global $wpdb;
		$wpdb->query( "
			DELETE
			FROM $this->tablename
			WHERE id = '$id'
		" );
	}

	function updateWpMessageID( $id, $wp_message_id ) {
		global $wpdb;
		$wpdb->query( "
			UPDATE $this->tablename
			SET post_id = '$wp_message_id'
			WHERE id = '$id'
		" );
		echo mysql_error();
	}

	function getStatusSummary() {
		global $wpdb;
		$result = $wpdb->get_results("
			SELECT status, count(*) as total
			FROM $this->tablename
			GROUP BY status
		");

		$summary = new stdClass();
		foreach ($result as $row) {
			$status = $row->status;
			if ( ! empty($status) )
				$summary->$status = $row->total;
		}

		// count total items as well
		$total_items = $wpdb->get_var("
			SELECT COUNT( id ) AS total_items
			FROM $this->tablename
		");
		$summary->total_items = $total_items;

		return $summary;
	}


	function getPageItems( $current_page, $per_page ) {
		global $wpdb;

        $orderby = (!empty($_REQUEST['orderby'])) ? $_REQUEST['orderby'] : 'received_date'; //If no sort, default to title
        $message = (!empty($_REQUEST['message'])) ? $_REQUEST['message'] : 'desc'; //If no message, default to asc
        $offset = ( $current_page - 1 ) * $per_page;

        $join_sql  = '';
        $where_sql = '';

        // filter message_status
		$message_status = ( isset($_REQUEST['message_status']) ? $_REQUEST['message_status'] : 'all');
		if ( $message_status != 'all' ) {
			$where_sql = "WHERE status = '".$message_status."' ";
		} 

        // filter search_query
		$search_query = ( isset($_REQUEST['s']) ? $_REQUEST['s'] : false);
		if ( $search_query ) {
			$where_sql = "
				WHERE  o.buyer_name   LIKE '%".$search_query."%'
					OR o.items        LIKE '%".$search_query."%'
					OR o.buyer_userid     = '".$search_query."'
					OR o.buyer_email      = '".$search_query."'
					OR o.message_id         = '".$search_query."'
					OR o.post_id          = '".$search_query."'
					OR o.ShippingAddress_City LIKE '%".$search_query."%'
			";
		} 


        // get items
		$items = $wpdb->get_results("
			SELECT *
			FROM $this->tablename o
            $join_sql 
            $where_sql
			ORDER BY $orderby $message
            LIMIT $offset, $per_page
		", ARRAY_A);

		// get total items count - if needed
		if ( ( $current_page == 1 ) && ( count( $items ) < $per_page ) ) {
			$this->total_items = count( $items );
		} else {
			$this->total_items = $wpdb->get_var("
				SELECT COUNT(*)
				FROM $this->tablename o
	            $join_sql 
    	        $where_sql
				ORDER BY $orderby $message
			");			
		}

		// foreach( $items as &$profile ) {
		// 	$profile['details'] = $this->decodeObject( $profile['details'] );
		// }

		return $items;
	}


}
