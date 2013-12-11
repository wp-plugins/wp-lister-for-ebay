<?php
/**
 * EbayCategoriesModel class
 *
 * responsible for managing ebay categories and store categories and talking to ebay
 * 
 */

// list of used EbatNs classes:

// require_once 'EbatNs_ServiceProxy.php';
// require_once 'GetCategoriesRequestType.php';
// require_once 'GetStoreRequestType.php';
// require_once 'CategoryType.php';	
// require_once 'EbatNs_DatabaseProvider.php';	
// require_once 'EbatNs_Logger.php';
// require_once 'GetCategoryFeaturesRequestType.php';

class EbayCategoriesModel extends WPL_Model {
	const table = 'ebay_categories';

	var $_session;
	var $_cs;
	var $_categoryVersion;
	var $_siteid;

	function EbayCategoriesModel()
	{
		global $wpl_logger;
		$this->logger = &$wpl_logger;

		global $wpdb;
		$this->tablename = $wpdb->prefix . self::table;
	}
	
	function initCategoriesUpdate($session, $siteid)
	{
		$this->initServiceProxy($session);
		$this->logger->info('initCategoriesUpdate()');

		// set handler to receive CategoryType items from result
		$this->_cs->setHandler('CategoryType', array(& $this, 'storeCategory'));	
		
		// we will not know the version till the first call went through !
		$this->_categoryVersion = -1;
		$this->_siteid = $siteid;
		
		// truncate the db
		global $wpdb;
		$wpdb->query('truncate '.$this->tablename);
		
		// download the data of level 1 only !
		$req = new GetCategoriesRequestType();
		$req->CategorySiteID = $siteid;
		$req->LevelLimit = 1;
		$req->DetailLevel = 'ReturnAll';
		
		$res = $this->_cs->GetCategories($req);
		$this->_categoryVersion = $res->CategoryVersion;
		
		// let's update the version information on the top-level entries
		$data['version'] = $this->_categoryVersion;
		$data['site_id'] = $this->_siteid;
		$wpdb->update( $this->tablename, $data, array( 'parent_cat_id' => '0') );
        echo mysql_error();

		// include other update tasks
		$tasks = array();
		$tasks[] = array( 
			'task'        => 'loadShippingServices', 
			'displayName' => 'update shipping services', 
			'params'      => array() 
		);
		$tasks[] = array( 
			'task'        => 'loadPaymentOptions', 
			'displayName' => 'update payment options'
		);
		$tasks[] = array( 
			'task'        => 'loadStoreCategories', 
			'displayName' => 'update custom store categories'
		);


		// include eBay Motors for US site
		if ( ( $siteid == 0 ) && ( get_option( 'wplister_enable_ebay_motors' ) == 1 ) ) {

			// insert top level motors category manually
			$data['cat_id']        = 6000;
			$data['parent_cat_id'] = 0;
			$data['level']         = 1;
			$data['leaf']          = 0;
			$data['cat_name']      = 'eBay Motors';
			$data['site_id']       = 100;
			$wpdb->insert( $this->tablename, $data );

			// $task = array( 
			// 	'task'        => 'loadEbayCategoriesBranch', 
			// 	'displayName' => 'eBay Motors', 
			// 	'cat_id'      => '6000' 
			// );
			// $tasks[] = $task;

		}

		// fetch the data back from the db and add a task for each top-level id
		$rows = $wpdb->get_results( "select cat_id, cat_name from $this->tablename where parent_cat_id=0", ARRAY_A );
        echo mysql_error();
		foreach ($rows as $row)
		{
			$this->logger->info('adding task for category #'.$row['cat_id'] . ' - '.$row['cat_name']);
			
			$task = array( 
				'task'        => 'loadEbayCategoriesBranch', 
				'displayName' => $row['cat_name'], 
				'cat_id'      => $row['cat_id'] 
			);
			$tasks[] = $task;
		}
		return $tasks;
	}
	
	function loadEbayCategoriesBranch( $cat_id, $session, $siteid)
	{
		$this->initServiceProxy($session);
		$this->logger->info('loadEbayCategoriesBranch() #'.$cat_id );

		// handle eBay Motors category
		if ( $cat_id == 6000 ) $siteid = 100;

		// set handler to receive CategoryType items from result
		$this->_cs->setHandler('CategoryType', array(& $this, 'storeCategory'));	
		$this->_siteid = $siteid;

		// call GetCategories()
		$req = new GetCategoriesRequestType();
		$req->CategorySiteID = $siteid;
		$req->LevelLimit = 255;
		$req->DetailLevel = 'ReturnAll';
		$req->ViewAllNodes = true;
		$req->CategoryParent = $cat_id;
		$this->_cs->GetCategories($req);

	}
	
	function downloadCategories($session, $siteid)
	{
		$this->initServiceProxy($session);
		$this->logger->info('downloadCategories() - DEPRECATED');

		// // set handler to receive CategoryType items from result
		// $this->_cs->setHandler('CategoryType', array(& $this, 'storeCategory'));	
		
		// // we will not know the version till the first call went through !
		// $this->_categoryVersion = -1;
		
		// // truncate the db
		// global $wpdb;
		// $wpdb->query('truncate '.$this->tablename);
		
		// // download the data of level 1 only !
		// $req = new GetCategoriesRequestType();
		// $req->CategorySiteID = $siteid;
		// $req->LevelLimit = 1;
		// $req->DetailLevel = 'ReturnAll';
		
		// $res = $this->_cs->GetCategories($req);
		// $this->_categoryVersion = $res->CategoryVersion;
		
		// // let's update the version information on the top-level entries
		// $data['version'] = $this->_categoryVersion;
		// $wpdb->update( $this->tablename, $data, array( 'parent_cat_id' => '0') );
		
		// // fetch the data back from the db and run a query for
		// // each top-level id
		// $rows = $wpdb->get_results( "select cat_id, cat_name from $this->tablename where parent_cat_id=0", ARRAY_A );
		// foreach ($rows as $row)
		// {
		// 	#echo "Loading tree for " . $row['cat_id'] . "<br>\n";
		// 	$this->logger->info('Loading tree for category #'.$row['cat_id'] . ' - '.$row['cat_name']);
			
		// 	$req = new GetCategoriesRequestType();
		// 	$req->CategorySiteID = $siteid;
		// 	$req->LevelLimit = 255;
		// 	$req->DetailLevel = 'ReturnAll';
		// 	$req->ViewAllNodes = true;
		// 	$req->CategoryParent = $row['cat_id'];
		// 	$this->_cs->GetCategories($req);
		// }
	}
	
	function storeCategory($type, & $Category)
	{
		global $wpdb;
		
		//#type $Category CategoryType
		$data['cat_id'] = $Category->CategoryID;
		if ( $Category->CategoryParentID[0] == $Category->CategoryID ) {

			// avoid duplicate main categories due to the structure of the response
			if ( $this->getItem( $Category->CategoryID ) ) return true;

			$data['parent_cat_id'] = '0';

		} else {
			$data['parent_cat_id'] = $Category->CategoryParentID[0];			
		}
		$data['cat_name'] = $Category->CategoryName;
		$data['level']    = $Category->CategoryLevel;
		$data['leaf']     = $Category->LeafCategory ? $Category->LeafCategory : 0;
		$data['version']  = $this->_categoryVersion ? $this->_categoryVersion : 0;
		$data['site_id']  = $this->_siteid;
		
		// remove unrecognizable chars from category name
		// $data['cat_name'] = trim(str_replace('?','', $data['cat_name'] ));

		$wpdb->insert( $this->tablename, $data );
		$mysql_error = mysql_error();
		if ( $mysql_error ) {
			$this->logger->error('failed to insert category '.$data['cat_id'] . ' - ' . $data['cat_name'] );
			$this->logger->error('mysql said: '.$mysql_error );
			$this->logger->error('data: '. print_r( $data, 1 ) );
		} else {
			$this->logger->info('category inserted() '.$data['cat_id'] . ' - ' . $data['cat_name'] );
		}
					
		return true;
	}
	
	

	function downloadStoreCategories($session)
	{
		global $wpdb;
		$this->initServiceProxy($session);
		$this->logger->info('downloadStoreCategories()');
		
		// download store categories
		$req = new GetStoreRequestType();
		$req->CategoryStructureOnly = true;
		
		$res = $this->_cs->GetStore($req);
		
		// empty table
		$wpdb->query( "DELETE FROM {$wpdb->prefix}ebay_store_categories" );
		
		// insert each category
		foreach( $res->Store->CustomCategories as $Category ) {
		
			$this->handleStoreCategory( $Category, 1, 0 );

		}
	}
		
	
	function handleStoreCategory( & $Category, $level, $parent_cat_id )
	{
		global $wpdb;
		if ( $level > 5 ) return false;		

		$data = array();
		$data['cat_id'] 		= $Category->CategoryID;
		$data['cat_name'] 		= $Category->Name;
		$data['order'] 			= $Category->Order;
		$data['leaf'] 			= is_array( $Category->ChildCategory ) ? '0' : '1';
		$data['level'] 			= $level;
		$data['parent_cat_id'] 	= $parent_cat_id;
	
		// move "Other" category to the end of the list
		if ( $data['order'] == 0 ) $data['order'] = 999;

		// insert row - and manually set field type to string. 
		// without parameter '%s' $wpdb would convert cat_id to int instead of bigint - on some servers!
		$wpdb->insert( $wpdb->prefix.'ebay_store_categories', $data, '%s' );

		// handle children recursively
		if ( is_array( $Category->ChildCategory ) ) {
			foreach ( $Category->ChildCategory as $ChildCategory ) {
				$this->handleStoreCategory( $ChildCategory, $level + 1, $Category->CategoryID );
			}
		}

	}
	

	
	function getCategoryConditions($session, $category_id )
	{

		// adjust Site if required - eBay Motors (beta)
		$primary_category = $this->getItem( $category_id );
		if ( $primary_category['site_id'] == 100 ) {
			$session->setSiteId( 100 );
		}

		$this->initServiceProxy($session);
		
		// download store categories
		$req = new GetCategoryFeaturesRequestType();
		$req->setCategoryID( $category_id );
		$req->setDetailLevel( 'ReturnAll' );
		
		$res = $this->_cs->GetCategoryFeatures($req);
		$this->logger->info('getCategoryConditions() for category ID '.$category_id);
		// $this->logger->info('getCategoryConditions: '.print_r($res,1));

		// $conditions as array
		// if (!isset($res->Category[0]->ConditionValues->Condition)) return null;
		if ( count($res->Category[0]->ConditionValues->Condition) > 0 )
		foreach ($res->Category[0]->ConditionValues->Condition as $Condition) {
			$conditions[$Condition->ID] = $Condition->DisplayName;
		}
		$this->logger->info('getCategoryConditions: '.print_r($conditions,1));
		
		if (!is_array($conditions)) $conditions = 'none';
		return array( $category_id => $conditions );
	}
		
	
	function getCategorySpecifics($session, $category_id )
	{

		// adjust Site if required - eBay Motors (beta)
		$primary_category = $this->getItem( $category_id );
		if ( $primary_category['site_id'] == 100 ) {
			$session->setSiteId( 100 );
		}

		$this->initServiceProxy($session);
		
		// download store categories
		$req = new GetCategorySpecificsRequestType();
		$req->setCategoryID( $category_id );
		$req->setDetailLevel( 'ReturnAll' );
		
		$res = $this->_cs->GetCategorySpecifics($req);
		$this->logger->info('getCategorySpecifics() for category ID '.$category_id);
		// $this->logger->info('getCategorySpecifics: '.print_r($res,1));

		// $specifics as array
		// if (!isset($res->Category[0]->ConditionValues->Condition)) return null;
		if ( count($res->Recommendations[0]->NameRecommendation) > 0 )
		foreach ($res->Recommendations[0]->NameRecommendation as $Recommendation) {
			$new_specs                = new stdClass();
			$new_specs->Name          = $Recommendation->Name;
			$new_specs->ValueType     = $Recommendation->ValidationRules->ValueType;
			$new_specs->MinValues     = $Recommendation->ValidationRules->MinValues;
			$new_specs->MaxValues     = $Recommendation->ValidationRules->MaxValues;
			$new_specs->SelectionMode = $Recommendation->ValidationRules->SelectionMode;

			if ( is_array( $Recommendation->ValueRecommendation ) ) {
				foreach ($Recommendation->ValueRecommendation as $recommendedValue) {
					$new_specs->recommendedValues[] = $recommendedValue->Value;
				}
			}

			#$specifics[$Recommendation->Name] = $new_specs;
			$specifics[] = $new_specs;
		}
		$this->logger->info('getCategorySpecifics: '.print_r($specifics,1));
		
		if (!is_array($specifics)) $specifics = 'none';
		return array( $category_id => $specifics );
	}
		
	
	

	
	/* the following methods could go into another class, since they use wpdb instead of EbatNs_DatabaseProvider */
	
	function getAll() {
		global $wpdb;	
		$this->tablename = $wpdb->prefix . self::table;
		$profiles = $wpdb->get_results("
			SELECT * 
			FROM $this->tablename
			ORDER BY cat_name
		", ARRAY_A);		

		return $profiles;		
	}

	function getItem( $id ) {
		global $wpdb;	
		$this->tablename = $wpdb->prefix . self::table;
		$item = $wpdb->get_row("
			SELECT * 
			FROM $this->tablename
			WHERE cat_id = '$id'
		", ARRAY_A);		

		return $item;		
	}

	function getCategoryName( $id ) {
		global $wpdb;	
		$this->tablename = $wpdb->prefix . self::table;
		$value = $wpdb->get_var("
			SELECT cat_name 
			FROM $this->tablename
			WHERE cat_id = '$id'
		");		

		return $value;		
	}

	function getCategoryType( $id ) {
		global $wpdb;	
		$this->tablename = $wpdb->prefix . self::table;
		$value = $wpdb->get_var("
			SELECT leaf 
			FROM $this->tablename
			WHERE cat_id = '$id'
		");		

		return $value ? 'leaf' : 'parent';		
	}

	function getChildrenOf( $id ) {
		global $wpdb;	
		$this->tablename = $wpdb->prefix . self::table;
		$items = $wpdb->get_results("
			SELECT DISTINCT * 
			FROM $this->tablename
			WHERE parent_cat_id = '$id'
		", ARRAY_A);		

		return $items;		
	}

	function getStoreCategoryName( $id ) {
		global $wpdb;	
		// $this->tablename = $wpdb->prefix . self::table;
		$this->tablename = $wpdb->prefix . 'ebay_store_categories';
		$value = $wpdb->get_var("
			SELECT cat_name 
			FROM $this->tablename
			WHERE cat_id = '$id'
		");		

		return $value;		
	}
	function getStoreCategoryType( $id ) {
		global $wpdb;	
		// $this->tablename = $wpdb->prefix . self::table;
		$this->tablename = $wpdb->prefix . 'ebay_store_categories';
		$value = $wpdb->get_var("
			SELECT leaf 
			FROM $this->tablename
			WHERE cat_id = '$id'
		");		

		return $value ? 'leaf' : 'parent';		
	}
	function getChildrenOfStoreCategory( $id ) {
		global $wpdb;	
		// $this->tablename = $wpdb->prefix . self::table;
		$this->tablename = $wpdb->prefix . 'ebay_store_categories';
		$items = $wpdb->get_results("
			SELECT DISTINCT * 
			FROM $this->tablename
			WHERE parent_cat_id = '$id'
			ORDER BY `order` ASC
		", ARRAY_A);		

		return $items;		
	}


		
	/* recursively get full ebay category name */	
	function getFullEbayCategoryName( $cat_id ) {
		global $wpdb;
		if ( intval($cat_id) == 0 ) return null;

		$result = $wpdb->get_row('SELECT * FROM '.$wpdb->prefix.'ebay_categories WHERE cat_id = '.$cat_id );
		if ( $result ) { 
			if ( $result->parent_cat_id != 0 ) {
				$parentname = self::getFullEbayCategoryName( $result->parent_cat_id ) . ' &raquo; ';
			} else {
				$parentname = '';
			}
			return $parentname . $result->cat_name;
		}

	}

	/* recursively get full store category name */	
	function getFullStoreCategoryName( $cat_id ) {
		global $wpdb;
		if ( intval($cat_id) == 0 ) return null;

		$result = $wpdb->get_row('SELECT * FROM '.$wpdb->prefix.'ebay_store_categories WHERE cat_id = '.$cat_id );
		if ( $result ) { 
			if ( $result->parent_cat_id != 0 ) {
				$parentname = self::getFullStoreCategoryName( $result->parent_cat_id ) . ' &raquo; ';
			} else {
				$parentname = '';
			}
			return $parentname . $result->cat_name;
		}

	}
	
	
	
	
}
