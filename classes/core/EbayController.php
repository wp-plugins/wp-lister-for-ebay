<?php

class EbayController {
    
    var $logger;

    var $apiurl;
    var $signin;
    var $devId;
    var $appId;
    var $certId;
    var $RuName;
    var $siteId;
    var $sandbox;
    var $compLevel;

    #public $data = array();    
    public $session;            // ebay session
    public $sp;                 // ebay service proxy    
    public $message = false;    
    public $error = false;
    public $lastResults = array();

    public function __construct() {
        // global $wpl_logger;
        // $this->logger = &$wpl_logger;
        // $this->config();

        // set up autoloader for eBay classes
        self::loadEbayClasses();
    }

    public function config() {
        // add EbatNs folder to include path - required for SDK
        // $incPath = WPLISTER_PATH . '/includes/EbatNs';
        // set_include_path( get_include_path() . ':' . $incPath );        
    }

    static function loadEbayClasses() {

        // make sure this only runs once
        $autoload_functions = spl_autoload_functions();
        if ( is_array( $autoload_functions ) ) {
            foreach ( $autoload_functions as $func ) {
                // return if WPL_Autoloader already loaded
                if ( is_array($func) && $func[1] == 'autoloadEbayClasses' )
                    return;
            }
        }

        // we want to be patient when talking to ebay
        if( ! ini_get('safe_mode') ) @set_time_limit(600);

        ini_set( 'mysql.connect_timeout', 600 );
        ini_set( 'default_socket_timeout', 600 );

        // add EbatNs folder to include path - required for SDK
        $incPath = WPLISTER_PATH . '/includes/EbatNs';
        set_include_path( get_include_path() . ':' . $incPath );

        // TODO: check if set_include_path() was successfull!

        // use autoloader to load EbatNs classes
        spl_autoload_register('WPL_Autoloader::autoloadEbayClasses');

    } // loadEbayClasses()


    function GetEbaySignInUrl($RuName = null, $Params = null)
    {
        $s = $this->session;
        if ($s->getAppMode() == 0) 
            $url = 'https://signin.' . self::getDomainnameBySiteId( $s->getSiteId() ) . '/ws/eBayISAPI.dll?SignIn';
        else 
            $url = 'https://signin.sandbox.' . self::getDomainnameBySiteId( $s->getSiteId() ) . '/ws/eBayISAPI.dll?SignIn';
        if ($RuName != null)
            $url .= '&runame=' . $RuName;
        if ($Params != null)
            $url .= '&ruparams=' . $Params;
        return $url;
    }
    
    
    // get SessionID and build AuthURL
    public function getAuthUrl(){ 

        // fetch SessionID - valid for about 5 minutes
        $SessionID = $this->GetSessionID( $this->RuName );

        // save SessionID to DB
        update_option('wplister_ebay_sessionid', $SessionID);
        WPLE()->logger->info( 'new SessionID: ' . $SessionID );


        // build auth url
        $query = array( 'RuName' => $this->RuName, 'SessID' => $SessionID );
        $url = $this->GetEbaySignInUrl() . '&' . http_build_query( $query, '', '&' );
        WPLE()->logger->info( 'AuthUrl: ' . $url );

        return $url;
    }
 
    // do FetchToken and save to DB
    public function doFetchToken( $account_id = false ){ 
        
        // $account_id = $account_id ? $account_id : get_option('wplister_default_account_id'); // we can *not* fall back to the default account here, or adding a new account would overwrite the default account's token
        $SessionID  = get_option('wplister_ebay_sessionid');        
        $token      = $this->FetchToken( $SessionID );

        if ($token) {

            if ( $account_id ) {
                $account = new WPLE_eBayAccount( $account_id );
                $account->token = $token;
                $account->update();
            }

            // check if setup wizard is still active
            if ( get_option( 'wplister_setup_next_step' ) == 1 ) {

                // update legacy data
                update_option('wplister_ebay_token', $token);

                // move setup to step 2
                update_option('wplister_setup_next_step', '2');                

                // remember when WP-Lister was connected to an eBay account for the first time
                update_option( 'ignore_orders_before_ts', time() );
            }

            // // obsolete - already called in fetchTokenForAccount()
            // update_option('wplister_ebay_token_is_invalid', false );

        }
        
        return $token;
    }
 
    // do getTokenExpirationTime and save to DB (deprecated)
    public function getTokenExpirationTime( $site_id, $sandbox_enabled ){ 

        $token = get_option('wplister_ebay_token');
        $expdate = $this->fetchTokenExpirationTime( $token );

        // update legacy option (1.x)
        update_option('wplister_ebay_token_expirationtime', $expdate);
        
        return $expdate;
    }
 
    // establish connection to eBay API
    public function initEbay( $site_id, $sandbox_enabled, $token = false, $account_id = false ){ 

        // init autoloader fro EbatNs classes
        $this->loadEbayClasses();

        WPLE()->logger->info("initEbay( $account_id )");
        // require_once 'EbatNs_ServiceProxy.php';
        // require_once 'EbatNs_Logger.php';

        // hide inevitable cURL warnings from SDK 
        // *** DISABLE FOR DEBUGGING ***
        $this->error_reporting_level = error_reporting();
        WPLE()->logger->debug( 'original error reporting level: '.$this->error_reporting_level );

        // // regard php_error_handling option
        // // first bit (1) will show all php errors if set
        // if ( 1 & get_option( 'wplister_php_error_handling', 0 ) ) {
        //     error_reporting( E_ALL | E_STRICT );            
        // } else {
        //     // only show fatal errors (default)
        //     error_reporting( E_ERROR );            
        // }
        error_reporting( E_ERROR );            
        WPLE()->logger->debug( 'new error reporting level: '.error_reporting() );

        $this->siteId = $site_id;
        $this->sandbox = $sandbox_enabled;
        #$this->compLevel = 765;

        if ( $sandbox_enabled ) {
            
            // sandbox keys
            $this->devId  = 'db0c17b6-c357-4a38-aa60-7e80158f57dc';
            $this->appId  = 'LWSWerbu-c159-4552-8411-1406ca5a2bba';
            $this->certId = '33272b6e-ef02-4d22-a487-a1a3f02b9c66';
            $this->RuName = 'LWS_Werbung_Gmb-LWSWerbu-c159-4-tchfyrowj';

            $this->apiurl = 'https://api.sandbox.ebay.com/ws/api.dll';
            $this->signin = 'https://signin.sandbox.ebay.com/ws/eBayISAPI.dll?SignIn&';

        } else {

            // production keys
            $this->devId  = 'db0c17b6-c357-4a38-aa60-7e80158f57dc';
            $this->appId  = 'LWSWerbu-6147-43ed-9835-853f7b5dc6cb';
            $this->certId = '61212d27-f74b-416b-8d48-3160f245443f';
            $this->RuName = 'LWS_Werbung_Gmb-LWSWerbu-6147-4-ywstl';

            $this->apiurl = 'https://api.ebay.com/ws/api.dll';
            $this->signin = 'https://signin.ebay.com/ws/eBayISAPI.dll?SignIn&';
        }

        // filter RuName
        if ( defined('WPLISTER_RESELLER_VERSION') ) {
            $this->RuName = apply_filters( 'wplister_runame', $this->RuName, $sandbox_enabled );            
        }

        // init session
        $session = new EbatNs_Session();

        // depends on the site working on (needs ID-Value !)
        $session->setSiteId($site_id);
        $session->wple_account_id = $account_id;

        // regard WP proxy server
        if ( defined('WP_USEPROXY') && WP_USEPROXY ) {
            if ( defined('WP_PROXY_HOST') && defined('WP_PROXY_PORT') )
                $session->setProxyServer( WP_PROXY_HOST . ':' . WP_PROXY_PORT );
        }

        // environment (0=production, 1=sandbox)
        if ( $sandbox_enabled == '1' ) {
            WPLE()->logger->info('initEbay(): SANDBOX ENABLED');
            $session->setAppMode(1);    // this must be set *before* setting the keys (appId, devId, ...)
        } else {
            $session->setAppMode(0);    
        }

        $session->setAppId($this->appId);
        $session->setDevId($this->devId);
        $session->setCertId($this->certId);

        if ( $token ) { 
            
            // use a token as credential
            $session->setTokenMode(true);

            // do NOT use a token file !
            $session->setTokenUsePickupFile(false);

            // token of the user
            $session->setRequestToken($token);

        } else {
            $session->setTokenMode(false);
        }

        // creating a proxy for UTF8
        $sp = new EbatNs_ServiceProxy($session, 'EbatNs_DataConverterUtf8');

        // // logger doc: http://www.intradesys.com/de/forum/1528
        // if ( get_option('wplister_log_level') > 5 ) {
        //     #$sp->attachLogger( new EbatNs_Logger(false, 'stdout', true, false) );
        //     $sp->attachLogger( new EbatNs_Logger(false, WPLE()->logger->file ) );
        // }

        // attach custom DB Logger for Tools page
        // if ( get_option('wplister_log_to_db') == '1' ) {
        if ( isset($_REQUEST['page']) && $_REQUEST['page'] == 'wplister-tools' ) {
            $sp->attachLogger( new WPL_EbatNs_Logger( false, 'db', $account_id, $site_id ) );
        }
        
        // save service proxy - and session
        $this->sp = $sp;
        $this->session = $session;

    }

    // re-attach logger - required to log multiple requests in the same session
    public function initLogger(){ 
        $this->sp->attachLogger( new WPL_EbatNs_Logger( false, 'db', $this->session->wple_account_id, $this->siteId ) );
    }

    // close connection to eBay API
    public function closeEbay(){ 
        // restore error reporting level 
        error_reporting( $this->error_reporting_level );
        // WPLE()->logger->info( 'switched back error reporting level to: '.error_reporting() );
    }
 

    // get SessionID for Auth&Auth
    public function GetSessionID( $RuName ){ 
        // require_once 'GetSessionIDRequestType.php';

        // prepare request
        $req = new GetSessionIDRequestType();
        $req->setRuName($RuName);
        #$req->setErrorLanguage('en_US');
        
        // send request
        $res = $this->sp->GetSessionID($req);

        // handle errors like blocked ips
        if ( $res->Ack != 'Success' ) {
            echo "<h1>Problem connecting to eBay</h1>";
            echo "<p>WP-Lister can't seem to establish a connection to eBay's servers. This could be caused by a firewall blocking cURL from accessing unkown ip addresses.</p>";
            echo "<p>Only your hosting company can sort out the problems causing cURL not to connect properly. Your hosting company's server administrator should be able to resolve the permission problems preventing cURL from working. They've probably got overly limiting restrictions configured on the server, preventing it from being able to do the communication required for listing items on eBay.</p>";
            echo "<p>debug output:</p>";
            echo "<pre>"; print_r($res); echo "</pre>";
            echo "<pre>"; print_r($req); echo "</pre>";
            die();
        }

        // TODO: handle error        
        return ( $res->SessionID );
        
    }
    public function FetchToken( $SessionID ){ 
        // require_once 'FetchTokenRequestType.php';

        // prepare request
        $req = new FetchTokenRequestType();
        $req->setSessionID($SessionID);
        #$req->setErrorLanguage(0);
        
        // send request
        $res = $this->sp->FetchToken($req);

        // TODO: handle error
        if ( ! $res->eBayAuthToken ) {
            echo "<pre>Error in FetchToken(): ";print_r($res);echo"</pre>";
            // echo "<pre>Request: ";print_r($req);echo"</pre>";
            return false;
        }

        return ( $res->eBayAuthToken );        
    }

    public function fetchTokenExpirationTime( $SessionID ){ 
        // require_once 'GetTokenStatusRequestType.php';

        // prepare request
        $req = new GetTokenStatusRequestType();
        $req->setSessionID($SessionID);
        #$req->setErrorLanguage(0);
        
        // send request
        $res = $this->sp->GetTokenStatus($req);

        // TODO: handle error        
        return ( $res->ExpirationTime );
        
    }

    // ajax: initialize categories update
    // returns: tasklist
    public function initCategoriesUpdate( $site_id ){ 
        $cm = new EbayCategoriesModel();
        return $cm->initCategoriesUpdate( $this->session, $site_id );
    }
    // ajax: load single branch of ebay categories
    // returns: result
    public function loadEbayCategoriesBranch( $cat_id, $site_id ){ 
        // $site_id = get_option('wplister_ebay_site_id');
        $cm = new EbayCategoriesModel();
        return $cm->loadEbayCategoriesBranch( $cat_id, $this->session, $site_id );
    }

    // // load full categories list and insert to db (old)
    // public function loadCategories(){ 
    //     $site_id = get_option('wplister_ebay_site_id');
    //     $cm = new EbayCategoriesModel();
    //     $cm->downloadCategories( $this->session, $site_id );
    // }

    // load Store categories list and insert to db
    public function loadStoreCategories( $account_id ) { 
        $cm = new EbayCategoriesModel();
        $cm->downloadStoreCategories( $this->session, $account_id );
    }

    // load shipping services and insert to db
    public function loadShippingServices( $site_id ){ 
        $sm = new EbayShippingModel();
        $sm->downloadCountryDetails( $this->session, $site_id );
        $sm->downloadShippingLocations( $this->session, $site_id );
        $sm->downloadShippingDetails( $this->session, $site_id );
        $sm->downloadDispatchTimes( $this->session, $site_id );      
        $sm->downloadShippingPackages( $this->session, $site_id );      
        $sm->downloadExcludeShippingLocations( $this->session, $site_id );
        $sm->fetchDoesNotApplyText( $this->session, $site_id );
        // $sm->downloadShippingDiscountProfiles( $this->session );      
    }

    // load shipping services and insert to db
    public function loadPaymentOptions( $site_id ){ 
        $sm = new EbayPaymentModel();
        $sm->downloadPaymentDetails( $this->session, $site_id );      
        $sm->downloadMinimumStartPrices( $this->session, $site_id );      
        $sm->downloadReturnPolicyDetails( $this->session, $site_id );      

        // set date of last update for site
        $Site = new WPLE_eBaySite( $site_id );
        $Site->last_refresh = date('Y-m-d H:i:s');
        $Site->update();
    }

    // load user / account specific details from eBay
    public function loadUserAccountDetails() { 

        // update user details
        $this->initLogger();
        $this->GetUser();
        $this->initLogger();
        $this->GetUserPreferences();

        // TODO: store ShippingDiscountProfiles in ebay_accounts table
        $sm = new EbayShippingModel();
        $sm->downloadShippingDiscountProfiles( $this->session );      
    }

    // load available dispatch times
    public function loadDispatchTimes(){ 
        $sm = new EbayShippingModel();
        return $sm->downloadDispatchTimes( $this->session );      
    }
    
    // load available shipping packages
    public function loadShippingPackages(){ 
        $sm = new EbayShippingModel();
        return $sm->downloadShippingPackages( $this->session );      
    }

    // load available shipping discount profiles
    public function loadShippingDiscountProfiles(){ 
        $sm = new EbayShippingModel();
        return $sm->downloadShippingDiscountProfiles( $this->session );      
    }


    // update transactions
    // public function loadTransactions( $days = null ){ 
    //     $tm = new TransactionsModel();
    //     $tm->updateTransactions( $this->session, $days );
    //     return $tm;
    // }
    // update ebay orders (deprecated)
    public function loadEbayOrders( $days = null ){ 
        $m = new EbayOrdersModel();
        $m->updateOrders( $this->session, $days );
        return $m;
    }
    // update ebay orders (new)
    public function updateEbayOrders( $days = false, $order_ids = false ){ 
        $m = new EbayOrdersModel();
        $m->updateOrders( $this->session, $days, 1, $order_ids );
        return $m;
    }

    // update ebay messages
    public function updateEbayMessages( $days = false, $message_ids = false ){ 

        if ( ! get_option( 'wplister_enable_messages_page' ) ) return;
        $m = new EbayMessagesModel();
        $m->updateMessages( $this->session, $days, 1, $message_ids );
        if ( $message_ids ) return $m;

        // automatically fetch message body for up to 10 messages
        $message_ids_to_update = EbayMessagesModel::getMessageIDsToFetch( $this->session->wple_account_id );
        if ( ! empty($message_ids_to_update) ) {
            $m->updateMessages( $this->session, $days, 1, $message_ids_to_update );
        }

        return $m;
    }

    // update listings
    // - update ended listings
    // - process auto relist schedule
    public function updateListings(){ 
        $lm = new ListingsModel();
        $lm->updateEndedListings( $this->session );

        $this->processAutoRelistSchedule();
    }

    // process listings scheduled for auto relist
    public function processAutoRelistSchedule(){ 
    }

    // get category conditions
    public function getCategoryConditions( $category_id, $site_id = false ) { 
        if ( ! $site_id ) $site_id = $this->siteId;
        $cm = new EbayCategoriesModel();
        // always update conditions before specifics
        $conditions = $cm->fetchCategoryConditions( $this->session, $category_id, $site_id );
        $specifics  = $cm->fetchCategorySpecifics(  $this->session, $category_id, $site_id );
        return $conditions;
    }

    // get category specifics
    public function getCategorySpecifics( $category_id, $site_id = false ) { 
        if ( ! $site_id ) $site_id = $this->siteId;
        $cm = new EbayCategoriesModel();
        // always update conditions before specifics
        $conditions = $cm->fetchCategoryConditions( $this->session, $category_id, $site_id );
        $specifics  = $cm->fetchCategorySpecifics(  $this->session, $category_id, $site_id );
        return $specifics;
    }



    // process $this->lastResults and look for errors and/or warnings
    public function processLastResults(){ 
        WPLE()->logger->debug('processLastResults()'.print_r( $this->lastResults, 1 ));

        $this->isSuccess = true;
        $this->hasErrors = false;
        $this->hasWarnings = false;

        foreach ($this->lastResults as $result) {
            if ( ! $result->success ) $this->isSuccess = false;
        }

    }



    // call verifyAddItem on selected items
    public function verifyItems( $id ){ 
        WPLE()->logger->info('EC::verifyItems('.$id.')');
        
        $sm = new ListingsModel();

        if ( is_array( $id )) {
            foreach( $id as $single_id ) {
                $this->lastResults[] = $sm->verifyAddItem( $single_id, $this->session );   
            }
            $this->processLastResults();
        } else {
            $this->lastResults[] = $sm->verifyAddItem( $id, $this->session );          
            $this->processLastResults();
            return $this->lastResults;
        }
        
    }

    // call ReviseItem on selected items
    public function reviseItems( $id ){ 
        
        $sm = new ListingsModel();

        if ( is_array( $id )) {
            foreach( $id as $single_id ) {
                $this->lastResults[] = $sm->reviseItem( $single_id, $this->session );  
            }
            $this->processLastResults();
        } else {
            $this->lastResults[] = $sm->reviseItem( $id, $this->session );         
            $this->processLastResults();
            return $this->lastResults;
        }
        
    }

    // call ReviseInventoryStatus on selected cart items
    public function reviseInventoryForCartItems( $cart_items ){ 
        
        $sm = new ListingsModel();
        if ( ! is_array( $cart_items ) ) return;
        
        foreach( $cart_items as $item ) {
            $this->lastResults[] = $sm->reviseInventoryStatus( $item->listing_id, $this->session, $item );  
        }
        
        $this->processLastResults();
    }

    // call ReviseInventoryStatus on selected products
    // (called from 'wplister_revise_inventory_status' api hook)
    public function reviseInventoryForProducts( $product_ids ){ 

        if ( ! is_array( $product_ids ) && ! is_numeric( $product_ids ) ) return; 
        if ( ! is_array( $product_ids ) ) $product_ids = array( $product_ids );
        
        $lm = new ListingsModel();
        foreach( $product_ids as $post_id ) {
            $listing_id = WPLE_ListingQueryHelper::getListingIDFromPostID( $post_id );

            // if no listing found, check parent_id for variations
            if ( ! $listing_id ) {
                $_product = get_product( $post_id );
                if ( ! $_product ) continue;

                if ( $_product->product_type == 'variation' ) {
                    $listing_id = WPLE_ListingQueryHelper::getListingIDFromPostID( $_product->parent->id );                                        
                }
            }

            // check if API is allowed to relist ended items
            if ( get_option( 'wplister_api_enable_auto_relist' ) ) {

                // check listing status - only ended and sold items can be relisted
                $allowed_statuses = array( 'ended', 'sold' );
                if ( $lm->itemHasAllowedStatus( $listing_id, $allowed_statuses ) ) {

                    // ok, we have an ended item - check if it's in stock
                    $listing_item = ListingsModel::getItem( $listing_id );
                    if ( ListingsModel::checkStockLevel( $listing_item ) ) {

                        // let's relist
                        $this->lastResults[] = $lm->relistItem( $listing_id, $this->session );  
                        continue;

                    } // is in stock

                } // is ended

            } // if API relist enabled

            // revise inventory status (default)
            $this->lastResults[] = $lm->reviseInventoryStatus( $listing_id, $this->session, false );  

        } // 
        
        $this->processLastResults();
    }

    // call AddItem on selected items
    public function sendItemsToEbay( $id ){ 
        
        $sm = new ListingsModel();

        if ( is_array( $id )) {
            foreach( $id as $single_id ) {
                $this->lastResults[] = $sm->addItem( $single_id, $this->session ); 
            }
            $this->processLastResults();
        } else {
            $this->lastResults[] = $sm->addItem( $id, $this->session );            
            $this->processLastResults();
            return $this->lastResults;
        }
        
    }

    // call EddItem on selected items
    public function endItemsOnEbay( $id ){ 
        
        $sm = new ListingsModel();

        if ( is_array( $id )) {
            foreach( $id as $single_id ) {
                $this->lastResults[] = $sm->endItem( $single_id, $this->session ); 
            }
            $this->processLastResults();
        } else {
            $this->lastResults[] = $sm->endItem( $id, $this->session );            
            $this->processLastResults();
            return $this->lastResults;
        }
        
    }

    // call relistItem on selected items
    public function relistItems( $id ){ 
        WPLE()->logger->info('EC::relistItems('.$id.')');
        
        $sm = new ListingsModel();

        if ( is_array( $id )) {
            foreach( $id as $single_id ) {
                $this->lastResults[] = $sm->relistItem( $single_id, $this->session );   
            }
            $this->processLastResults();
        } else {
            $this->lastResults[] = $sm->relistItem( $id, $this->session );          
            $this->processLastResults();
            return $this->lastResults;
        }
        
    }


    // call autoRelistItem on selected items - quick relist without any changes
    public function autoRelistItems( $id ){ 
        WPLE()->logger->info('EC::autoRelistItems('.$id.')');
        
        $sm = new ListingsModel();

        if ( is_array( $id )) {
            foreach( $id as $single_id ) {
                $this->lastResults[] = $sm->autoRelistItem( $single_id, $this->session );   
            }
            $this->processLastResults();
        } else {
            $this->lastResults[] = $sm->autoRelistItem( $id, $this->session );          
            $this->processLastResults();
            return $this->lastResults;
        }
        
    }


    // call GetItemDetails on selected items
    public function updateItemsFromEbay( $id ){ 
        
        $sm = new ListingsModel();

        if ( is_array( $id )) {
            foreach( $id as $single_id ) {
                $this->lastResults[] = $sm->updateItemDetails( $single_id, $this->session );   
            }
            $this->processLastResults();
        } else {
            $this->lastResults[] = $sm->updateItemDetails( $id, $this->session );          
            $this->processLastResults();
            return $this->lastResults;
        }
        
    }


    // delete selected items
    public function deleteListings( $id ){ 
        
        // $sm = new ListingsModel();

        // if ( is_array( $id )) {
        //     foreach( $id as $single_id ) {
        //         $sm->deleteItem( $single_id );  
        //     }
        // } else {
        //     $sm->deleteItem( $id );         
        // }
        
    }

    // delete selected items
    public function deleteProfiles( $id ){ 
        
        $sm = new ProfilesModel();

        if ( is_array( $id )) {
            foreach( $id as $single_id ) {
                $sm->deleteItem( $single_id );  
            }
        } else {
            $sm->deleteItem( $id );         
        }
        
    }

    // delete selected items
    public function deleteTransactions( $id ){ 
        
        $sm = new TransactionsModel();

        if ( is_array( $id )) {
            foreach( $id as $single_id ) {
                $sm->deleteItem( $single_id );  
            }
        } else {
            $sm->deleteItem( $id );         
        }
        
    }


    // call verifyAddItem on all prepared items
    public function verifyAllPreparedItems(){   

        $items = WPLE_ListingQueryHelper::getAllPrepared();
        
        foreach( $items as $item ) {
            $sm->verifyAddItem( $item['id'], $this->session );  
        }
        
    }

    // call AddItem on all verified items
    public function publishAllVerifiedItems(){  

        $items = WPLE_ListingQueryHelper::getAllVerified();
        
        foreach( $items as $item ) {
            $sm->addItem( $item['id'], $this->session );    
        }
        
    }

    // call reviseItem on all changed items
    public function reviseAllChangedItems(){   

        $items = WPLE_ListingQueryHelper::getAllChangedItemsToRevise();
        
        foreach( $items as $item ) {
            $sm->reviseItem( $item['id'], $this->session );  
        }
        
    }

    // call updateItemDetails on all published and changed items
    // public function updateAllPublishedItems(){   

    //     $sm = new ListingsModel();
    //     $items = WPLE_ListingQueryHelper::getAllPublished();
        
    //     foreach( $items as $item ) {
    //         $sm->updateItemDetails( $item['id'], $this->session );  
    //     }
        
    // }


    // call updateSingleTransaction on selected transactions
    public function updateTransactionsFromEbay( $id ){ 
        
        $sm = new TransactionsModel();

        if ( is_array( $id )) {
            foreach( $id as $single_id ) {
                $sm->updateSingleTransaction( $this->session, $single_id );   
            }
        } else {
            $sm->updateSingleTransaction( $this->session, $id );          
        }
        
    }


    // GetNotificationPreferences
    public function GetNotificationPreferences(){ 
        $req = new GetNotificationPreferencesRequestType();
        
        // 1st request for user prefs
        $req->setPreferenceLevel('User');
        $res = $this->sp->GetNotificationPreferences($req);

        // 2nd request for user data
        $this->initLogger();
        $req->setPreferenceLevel('UserData');
        $res2 = $this->sp->GetNotificationPreferences($req);

        // 3rd request for app data
        $this->initLogger();
        $req->setPreferenceLevel('Application');
        $res3 = $this->sp->GetNotificationPreferences($req);

        // handle result
        return ( print_r( $res, 1 ) . print_r( $res2, 1 )  . print_r( $res3, 1 ) );        
    }

    // SetNotificationPreferences for User
    // inspired by http://jolierouge.net/2011/05/spree-commerce-ebay-trading-api-and-the-ebay-accelerator-toolkit-from-intradesys-ebatns/
    public function SetUserNotificationPreferences( $mode = 'Enable' ){ 
        // $app_url = admin_url().'admin-ajax.php?action=handle_ebay_notify';

        // build request
        $req = new SetNotificationPreferencesRequestType();

        // set UserData
        $UserData = new NotificationUserDataType();
        $UserData->setExternalUserData( admin_url() );
        $req->setUserData( $UserData );


        // set UserDeliveryPreferenceArray
        $UserDeliveryPreferenceArray = new NotificationEnableArrayType();
        $NotificationEnable          = array();
        $mode                        = $mode == 'Enable' ? $mode : 'Disable';

        // subscribe to events
        $events = array(
            'ItemSold',
            'ItemClosed',
            'ItemListed',
            'ItemRevised',
            'BidReceived',
            'EndOfAuction',
            'FeedbackReceived',
            'FixedPriceTransaction',
        );

        foreach ( $events as $event ) {
            $n = new NotificationEnableType();
            $n->setEventType( $event );
            $n->setEventEnable( $mode ); // Enable / Disable
            $NotificationEnable[] = $n;
        }

        $UserDeliveryPreferenceArray->setNotificationEnable( $NotificationEnable, null );
        $req->setUserDeliveryPreferenceArray( $UserDeliveryPreferenceArray );

        
        // send request
        $res = $this->sp->SetNotificationPreferences($req);

        // handle result
        return ( print_r( $res, 1 ) );        
    } // SetUserNotificationPreferences()


    // reset NotificationPreferences for Application
    public function ResetNotificationPreferences(){ 

        // reset application prefs to default
        $req = new SetNotificationPreferencesRequestType();
        $req->ApplicationDeliveryPreferences = new ApplicationDeliveryPreferencesType();
        $req->ApplicationDeliveryPreferences->setAlertEmail('mailto://info@wplab.com');
        $req->ApplicationDeliveryPreferences->setAlertEnable('Enable');
        $req->ApplicationDeliveryPreferences->setApplicationEnable('Enable');
        $req->ApplicationDeliveryPreferences->setApplicationURL( 'http://ping.wplab.com/?key=wple_notify_handler' );
        $req->ApplicationDeliveryPreferences->setPayloadVersion( 927 );

        // // disabled
        // $details = new DeliveryURLDetailType();
        // $details->setDeliveryURLName('wple_notify_handler');
        // $details->setDeliveryURL('mailto://info@wplab.com');
        // $details->setStatus('Disable');
        // $req->ApplicationDeliveryPreferences->setDeliveryURLDetails($details,null);

        // send request
        $res = $this->sp->SetNotificationPreferences($req);
        // echo "<pre>";print_r($req);echo"</pre>";

        // handle result
        return ( print_r( $res, 1 ) );
    } // ResetNotificationPreferences()


    // GetNotificationsUsage
    public function GetNotificationsUsage(){ 

        $EndTime   = gmdate('Y-m-d\TH:i:s').'.000Z';                        // now
        $StartTime = gmdate('Y-m-d\TH:i:s', strtotime('-2 days') ).'.000Z'; // 2 days ago (3 days max)
       
        // send request
        $req = new GetNotificationsUsageRequestType();
        $req->setEndTime( $EndTime );
        $req->setStartTime( $StartTime );
        // $req->setItemID( $ebay_id ); // get detailed events for single item
        $res = $this->sp->GetNotificationsUsage($req);

        // handle result
        return ( print_r( $res, 1 ) );
    }




    // GetUserPreferences
    public function GetUserPreferences( $return_result = false ){ 

        // prepare request
        $req = new GetUserPreferencesRequestType();
        $req->setShowSellerProfilePreferences( true );
        $req->setShowOutOfStockControlPreference( true );
        // $req->setShowSellerExcludeShipToLocationPreference( true );

        // send request
        $res = $this->sp->GetUserPreferences($req);
        // echo "<pre>";print_r($res);echo"</pre>";#die();

        // handle response error
        if ( 'EbatNs_ResponseError' == get_class( $res ) )
            return false;

        $result = new stdClass();
        $result->success                  = true;
        $result->seller_shipping_profiles = array();
        $result->seller_payment_profiles  = array();
        $result->seller_return_profiles   = array();

        $result->SellerProfileOptedIn     = $res->SellerProfilePreferences->SellerProfileOptedIn;
        $result->OutOfStockControl        = $res->OutOfStockControlPreference;

        $profiles = $res->getSellerProfilePreferences()->getSupportedSellerProfiles()->getSupportedSellerProfile();
        // echo "<pre>";print_r($profiles);echo"</pre>";#die();

        // if ( $result->SellerProfileOptedIn ) {
        if ( sizeof( $res->SellerProfilePreferences->SupportedSellerProfiles->SupportedSellerProfile ) > 0 ) {
            
            foreach ( $res->SellerProfilePreferences->SupportedSellerProfiles->SupportedSellerProfile as $profile ) {
            
                $seller_profile = new stdClass();
                $seller_profile->ProfileID    = $profile->ProfileID;
                $seller_profile->ProfileName  = $profile->ProfileName;
                $seller_profile->ProfileType  = $profile->ProfileType;
                $seller_profile->ShortSummary = $profile->ShortSummary;
                
                switch ( $profile->ProfileType ) {
                    case 'SHIPPING':
                        $result->seller_shipping_profiles[] = $seller_profile;
                        break;
                    
                    case 'PAYMENT':
                        $result->seller_payment_profiles[] = $seller_profile;
                        break;
                    
                    case 'RETURN_POLICY':
                        $result->seller_return_profiles[] = $seller_profile;
                        break;
                }

            }
            if ( $return_result ) return $result;

            update_option('wplister_ebay_seller_shipping_profiles', $result->seller_shipping_profiles);
            update_option('wplister_ebay_seller_payment_profiles', $result->seller_payment_profiles);
            update_option('wplister_ebay_seller_return_profiles', $result->seller_return_profiles);

        } else {
            if ( $return_result ) return $result;
            delete_option( 'wplister_ebay_seller_shipping_profiles' );
            delete_option( 'wplister_ebay_seller_payment_profiles' );
            delete_option( 'wplister_ebay_seller_return_profiles' );
        }

        if ( $return_result ) return $result;
        update_option('wplister_ebay_seller_profiles_enabled', $result->SellerProfileOptedIn ? 'yes' : 'no' );
        delete_option( 'wplister_ebay_seller_profiles' );

    }



    // GetUser
    public function GetUser( $return_result = false ){ 

        // prepare request
        $req = new GetUserRequestType();
        
        // send request
        $res = $this->sp->GetUser($req);

        $user = new stdClass();
        $user->UserID              = $res->User->UserID;
        $user->Email               = $res->User->Email;
        $user->FeedbackScore       = $res->User->FeedbackScore;
        $user->FeedbackRatingStar  = $res->User->FeedbackRatingStar;
        $user->NewUser             = $res->User->NewUser;
        $user->IDVerified          = $res->User->IDVerified;
        $user->eBayGoodStanding    = $res->User->eBayGoodStanding;
        $user->Status              = $res->User->Status;
        $user->Site                = $res->User->Site;
        $user->VATStatus           = $res->User->VATStatus;
        $user->PayPalAccountLevel  = $res->User->PayPalAccountLevel;
        $user->PayPalAccountType   = $res->User->PayPalAccountType;
        $user->PayPalAccountStatus = $res->User->PayPalAccountStatus;

        $user->StoreOwner          = $res->User->SellerInfo->StoreOwner;
        $user->StoreURL            = $res->User->SellerInfo->StoreURL;
        $user->SellerBusinessType  = $res->User->SellerInfo->SellerBusinessType;
        $user->ExpressEligible     = $res->User->SellerInfo->ExpressEligible;
        $user->StoreSite           = $res->User->SellerInfo->StoreSite;

        if ( $return_result ) return $user;

        $UserID = $res->User->UserID;
        update_option('wplister_ebay_token_userid', $UserID);
        update_option('wplister_ebay_user', $user);

        return ( $UserID );        
    }

    // GetTokenStatus
    public function GetTokenStatus( $return_result = false ){ 
        // require_once 'GetTokenStatusRequestType.php';

        // prepare request
        $req = new GetTokenStatusRequestType();
        
        // send request
        $res = $this->sp->GetTokenStatus($req);

        $expdate = $res->TokenStatus->ExpirationTime;

        if ( $expdate ) {

            $expdate = str_replace('T', ' ', $expdate);
            $expdate = str_replace('.000Z', '', $expdate);

            if ( $return_result ) return $expdate;

            update_option( 'wplister_ebay_token_expirationtime', $expdate );
            update_option( 'wplister_ebay_token_is_invalid', false );

        }

        // handle result
        return ( $expdate );
        
    }

    // GetApiAccessRules
    public function GetApiAccessRules(){ 
        $req = new GetApiAccessRulesRequestType();
        $res = $this->sp->GetApiAccessRules($req);
        return ( $res );       
    }

    // test connection to ebay api by single GetItem request
    // (used by import plugin until version 1.3.8)
    public function testConnection(){ 
        // require_once 'GeteBayOfficialTimeRequestType.php';
        $req = new GeteBayOfficialTimeRequestType();
        $res = $this->sp->GeteBayOfficialTime($req);
        return ( $res );
    }
     
    // get current time on ebay
    public function getEbayTime(){ 
        // require_once 'GetItemRequestType.php';
        // require_once 'GeteBayOfficialTimeRequestType.php';

        // prepare request
        $req = new GeteBayOfficialTimeRequestType();
        #$req->setItemID($item_id);
        
        // send request
        $res = $this->sp->GeteBayOfficialTime($req);

        // process timestamp
        if ( $res->Ack == 'Success' ) {
            $ts = $res->Timestamp;              // 2013-06-06T07:45:19.898Z
            $ts = str_replace('T', ' ', $ts);   // 2013-06-06 07:45:19.898Z
            $ts = substr( $ts, 0, 19 );         // 2013-06-06 07:45:19
            return $ts;
        }

        // return result on error
        return ( $res );
        
    }

    // call Shopping API to fetch matching products
    public function callFindProducts( $query ) { 
        // $query = "test";

        // $api_url = 'http://open.api.ebay.com/shopping?callname=FindProducts&responseencoding=XML&appid=LWSWerbu-6147-43ed-9835-853f7b5dc6cb&siteid=0&version=525&QueryKeywords=harry%20potter&AvailableItemsOnly=true&MaxEntries=2'
        $api_url = $this->sandbox ? 'http://open.api.sandbox.ebay.com/shopping' : 'http://open.api.ebay.com/shopping';
        $params = array(
            'callname'           => 'FindProducts',
            'responseencoding'   => 'JSON',
            'appid'              => $this->appId,
            'siteid'             => $this->siteId,
            // 'version'            => '885',
            'version'            => '789',
            'QueryKeywords'      => urlencode( $query ),
            'AvailableItemsOnly' => 'true',
            'MaxEntries'         => '2',
        );
        $request_url = add_query_arg( $params, $api_url );
        // echo "<pre>";print_r($request_url);echo"</pre>";#die();
        
        // call API
        $response = wp_remote_get( $request_url );
        // echo "<pre>";print_r($response);echo"</pre>";#die();

        // skip further processing if an error was returned
        if ( is_wp_error( $response ) ) return $response;

        // decode result
        $result = json_decode( $response['body'] );
        // echo "<pre>";print_r($result);echo"</pre>";#die();

        // check if result was decoded
        if ( ! $result ) return 'Unable to parse FindProducts result for query '.$query;

        // check if no products found for query
        if ( $result->Ack == 'Failure' && is_array( $result->Errors ) ) {
            if ( $result->Errors[0]->ErrorCode == '10.20' ) {
                return array();                
            } else {
                return $result->Errors[0]->LongMessage;
            }
        }

        // return products array
        $products = $result->Product;

        // parse products and make EPID available
        foreach ($products as $product) {

            // parse all ProductID nodes
            foreach ( $product->ProductID as $pid ) {
                if ( $pid->Type == 'Reference' ) {
                    $product->EPID = $pid->Value;
                }
            }

        }

        return $products;
    } // callFindProducts()

    // get site code by site_id
    static public function getEbaySiteCode( $site_id ) {
        $sites = self::getEbaySites();
        if ( ! array_key_exists( $site_id, $sites) ) return false;
        return $sites[ $site_id ];        
    } // getEbaySiteCode()

    // TODO: fetch ebaySites from eBay
    static public function getEbaySites() {

        $sites = array (        
            '0'   => 'US', 
            '2'   => 'Canada', 
            '3'   => 'UK', 
            '77'  => 'Germany', 
            '15'  => 'Australia', 
            '71'  => 'France', 
            '100' => 'eBayMotors', 
            '101' => 'Italy', 
            '146' => 'Netherlands', 
            '186' => 'Spain', 
            '203' => 'India', 
            '201' => 'HongKong', 
            '216' => 'Singapore', 
            '207' => 'Malaysia', 
            '211' => 'Philippines', 
            '210' => 'CanadaFrench', 
            '212' => 'Poland', 
            '123' => 'Belgium_Dutch', 
            '23'  => 'Belgium_French', 
            '16'  => 'Austria', 
            '193' => 'Switzerland', 
            '205' => 'Ireland'
        );
        return $sites;
    }

    // 
    static function getDomainnameBySiteId($siteid = 0)
    {
        switch ($siteid) {
            case 0:
                return 'ebay.com';
            case 2:
                return 'ebay.ca';
            case 3:
                return 'ebay.co.uk';
            case 15:
                return 'ebay.com.au';
            case 16:
                return 'ebay.at';
            case 23:
                return 'ebay.be';
            case 71:
                return 'ebay.fr';
            case 77:
                return 'ebay.de';
            case 100:
                return 'ebaymotors.com';
            case 101:
                return 'ebay.it';
            case 123:
                return 'ebay.be';
            case 146:
                return 'ebay.nl';
            case 186:
                return 'ebay.es';
            case 193:
                return 'ebay.ch';
            case 196:
                return 'ebay.tw';
            case 201:
                return 'ebay.hk';
            case 203:
                return 'ebay.in';
            case 207:
                return 'ebay.com.my';
            case 211:
                return 'ebay.ph';
            case 212:
                return 'ebay.pl';
            case 216:
                return 'ebay.com.sg';
            case 218:
                return 'ebay.se';
            case 223:
                return 'ebay.cn';
        }
        return 'ebay.com';

    } // getDomainnameBySiteId()


}
