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
        global $wpl_logger;
        $this->logger = &$wpl_logger;
        // $this->config();
    }

    public function config() {
        // add EbatNs folder to include path - required for SDK
        // $incPath = WPLISTER_PATH . '/includes/EbatNs';
        // set_include_path( get_include_path() . ':' . $incPath );        
    }

    static function loadEbayClasses() {
        // we want to be patient when talking to ebay
        if( ! ini_get('safe_mode') ) set_time_limit(600);

        ini_set( 'mysql.connect_timeout', 600 );
        ini_set( 'default_socket_timeout', 600 );

        // add EbatNs folder to include path - required for SDK
        $incPath = WPLISTER_PATH . '/includes/EbatNs';
        set_include_path( get_include_path() . ':' . $incPath );

        // TODO: check if set_include_path() was successfull!

        // use autoloader to load EbatNs classes
        spl_autoload_register('WPL_Autoloader::autoloadEbayClasses');

    }


    function GetEbaySignInUrl($RuName = null, $Params = null)
    {
        $s = $this->session;
        if ($s->getAppMode() == 0) 
            $url = 'https://signin.' . $this->_getDomainnameBySiteId( $s->getSiteId() ) . '/ws/eBayISAPI.dll?SignIn';
        else 
            $url = 'https://signin.sandbox.' . $this->_getDomainnameBySiteId( $s->getSiteId() ) . '/ws/eBayISAPI.dll?SignIn';
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

        // build auth url
        $query = array( 'RuName' => $this->RuName, 'SessID' => $SessionID );
        $url = $this->GetEbaySignInUrl() . '&'. http_build_query( $query );
        $this->logger->info( 'AuthUrl: ' . $url );

        return $url;
    }
 
    // do FetchToken and save to DB
    public function doFetchToken(){ 
        
        $SessionID = get_option('wplister_ebay_sessionid');        
        $token = $this->FetchToken( $SessionID );

        if ($token) {
            update_option('wplister_ebay_token', $token);
            update_option('wplister_setup_next_step', '2');
            update_option('wplister_ebay_token_is_invalid', false );
        }
        
    }
 
    // do getTokenExpirationTime and save to DB (deprecated)
    public function getTokenExpirationTime( $site_id, $sandbox_enabled ){ 

        $token = get_option('wplister_ebay_token');
        $expdate = $this->fetchTokenExpirationTime( $token );

        update_option('wplister_ebay_token_expirationtime', $expdate);
        return $expdate;
    }
 
    // establish connection to eBay API
    public function initEbay( $site_id, $sandbox_enabled, $token = false){ 

        // init autoloader fro EbatNs classes
        $this->loadEbayClasses();

        $this->logger->info('initEbay()');
        // require_once 'EbatNs_ServiceProxy.php';
        // require_once 'EbatNs_Logger.php';

        // hide inevitable cURL warnings from SDK 
        // *** DISABLE FOR DEBUGGING ***
        $this->error_reporting_level = error_reporting();
        $this->logger->info( 'original error reporting level: '.$this->error_reporting_level );

        // regard php_error_handling option
        // first bit (1) will show all php errors if set
        if ( 1 & get_option( 'wplister_php_error_handling', 0 ) ) {
            error_reporting( E_ALL | E_STRICT );            
        } else {
            // only show fatal errors (default)
            error_reporting( E_ERROR );            
        }
        $this->logger->info( 'new error reporting level: '.error_reporting() );

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

        // init session
        $session = new EbatNs_Session();

        // depends on the site working on (needs ID-Value !)
        $session->setSiteId($site_id);

        // environment (0=production, 1=sandbox)
        if ( $sandbox_enabled == '1' ) {
            $this->logger->info('initEbay(): SANDBOX ENABLED');
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
        //     $sp->attachLogger( new EbatNs_Logger(false, $this->logger->file ) );
        // }

        // attach custom DB Logger for Tools page
        // if ( get_option('wplister_log_to_db') == '1' ) {
        if ( 'wplister-tools' == $_REQUEST['page'] ) {
            $sp->attachLogger( new WPL_EbatNs_Logger( false, 'db' ) );
        }
        
        // save service proxy - and session
        $this->sp = $sp;
        $this->session = $session;

    }

    // close connection to eBay API
    public function closeEbay(){ 
        // restore error reporting level 
        error_reporting( $this->error_reporting_level );
        // $this->logger->info( 'switched back error reporting level to: '.error_reporting() );
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
    public function initCategoriesUpdate(){ 
        $site_id = get_option('wplister_ebay_site_id');
        $cm = new EbayCategoriesModel();
        return $cm->initCategoriesUpdate( $this->session, $site_id );
    }
    // ajax: load single branch of ebay categories
    // returns: result
    public function loadEbayCategoriesBranch( $cat_id ){ 
        $site_id = get_option('wplister_ebay_site_id');
        $cm = new EbayCategoriesModel();
        return $cm->loadEbayCategoriesBranch( $cat_id, $this->session, $site_id );
    }

    // load full categories list and insert to db (old)
    public function loadCategories(){ 
        $site_id = get_option('wplister_ebay_site_id');
        $cm = new EbayCategoriesModel();
        $cm->downloadCategories( $this->session, $site_id );
    }

    // load Store categories list and insert to db
    public function loadStoreCategories(){ 
        $cm = new EbayCategoriesModel();
        $cm->downloadStoreCategories( $this->session );
    }

    // load shipping services and insert to db
    public function loadShippingServices(){ 
        $sm = new EbayShippingModel();
        $sm->downloadCountryDetails( $this->session );
        $sm->downloadShippingLocations( $this->session );
        $sm->downloadShippingDetails( $this->session );
        $sm->downloadDispatchTimes( $this->session );      
        $sm->downloadShippingPackages( $this->session );      
        $sm->downloadShippingDiscountProfiles( $this->session );      
        $sm->downloadExcludeShippingLocations( $this->session );
    }

    // load shipping services and insert to db
    public function loadPaymentOptions(){ 
        $sm = new EbayPaymentModel();
        $sm->downloadPaymentDetails( $this->session );      
        $sm->downloadMinimumStartPrices( $this->session );      
        $sm->downloadReturnPolicyDetails( $this->session );      

        // update user details
        $this->GetUser();
        $this->GetUserPreferences();
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
    public function loadTransactions( $days = null ){ 
        $tm = new TransactionsModel();
        $tm->updateTransactions( $this->session, $days );
        return $tm;
    }
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
    public function updateEbayMessages( $days = false, $order_ids = false ){ 
        $m = new EbayMessagesModel();
        $m->updateMessages( $this->session, $days, 1, $order_ids );
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
    public function getCategoryConditions( $category_id ){ 
        $cm = new EbayCategoriesModel();
        return $cm->getCategoryConditions( $this->session, $category_id );
    }

    // get category specifics
    public function getCategorySpecifics( $category_id ){ 
        $cm = new EbayCategoriesModel();
        return $cm->getCategorySpecifics( $this->session, $category_id );
    }



    // process $this->lastResults and look for errors and/or warnings
    public function processLastResults(){ 
        $this->logger->debug('processLastResults()'.print_r( $this->lastResults, 1 ));

        $this->isSuccess = true;
        $this->hasErrors = false;
        $this->hasWarnings = false;

        foreach ($this->lastResults as $result) {
            if ( ! $result->success ) $this->isSuccess = false;
        }

    }



    // call verifyAddItem on selected items
    public function verifyItems( $id ){ 
        $this->logger->info('EC::verifyItems('.$id.')');
        
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
    public function reviseInventoryForProducts( $product_ids ){ 

        if ( ! is_array( $product_ids ) && ! is_numeric( $product_ids ) ) return; 
        if ( ! is_array( $product_ids ) ) $product_ids = array( $product_ids );
        
        $lm = new ListingsModel();
        foreach( $product_ids as $post_id ) {
            $listing_id = $lm->getListingIDFromPostID( $post_id );
            $this->lastResults[] = $lm->reviseInventoryStatus( $listing_id, $this->session, false );  
        }
        
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
        $this->logger->info('EC::relistItems('.$id.')');
        
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
        $this->logger->info('EC::autoRelistItems('.$id.')');
        
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

        $sm = new ListingsModel();
        $items = $sm->getAllPrepared();
        
        foreach( $items as $item ) {
            $sm->verifyAddItem( $item['id'], $this->session );  
        }
        
    }

    // call AddItem on all verified items
    public function publishAllVerifiedItems(){  

        $sm = new ListingsModel();
        $items = $sm->getAllVerified();
        
        foreach( $items as $item ) {
            $sm->addItem( $item['id'], $this->session );    
        }
        
    }

    // call reviseItem on all changed items
    public function reviseAllChangedItems(){   

        $sm = new ListingsModel();
        $items = $sm->getAllChanged();
        
        foreach( $items as $item ) {
            $sm->reviseItem( $item['id'], $this->session );  
        }
        
    }

    // call updateItemDetails on all published and changed items
    public function updateAllPublishedItems(){   

        $sm = new ListingsModel();
        $items = $sm->getAllPublished();
        
        foreach( $items as $item ) {
            $sm->updateItemDetails( $item['id'], $this->session );  
        }
        
    }


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
        // require_once 'GetNotificationPreferencesRequestType.php';

        // prepare request
        $req = new GetNotificationPreferencesRequestType();
        $req->setPreferenceLevel('Application');
        #$req->setPreferenceLevel('User');
        
        // send request
        $res = $this->sp->GetNotificationPreferences($req);

        // second request for user data
        $req->setPreferenceLevel('User');
        $res2 = $this->sp->GetNotificationPreferences($req);

        // handle result
        return ( print_r( $res, 1 ) . print_r( $res2, 1 ) );
        
    }

    // SetNotificationPreferences
    public function SetNotificationPreferences(){ 
        // require_once 'SetNotificationPreferencesRequestType.php';

        $app_url = admin_url().'admin-ajax.php?action=handle_ebay_notify';

        // prepare request
        #$req = new SetNotificationPreferencesRequestType();
        #$req->setDeliveryURLName('http://www.example.com/wp-admin/admin-ajax.php?action=handle_ebay_notify');



        # example from http://jolierouge.net/2011/05/spree-commerce-ebay-trading-api-and-the-ebay-accelerator-toolkit-from-intradesys-ebatns/
        $req = new SetNotificationPreferencesRequestType();

        // ApplicationDeliveryPreferences
        $req->ApplicationDeliveryPreferences = new ApplicationDeliveryPreferencesType();
        $req->ApplicationDeliveryPreferences->setApplicationEnable('Enable');
        $req->ApplicationDeliveryPreferences->setApplicationURL($app_url);
        //$req->ApplicationDeliveryPreferences->setAlertEmail("youremail");
        $req->ApplicationDeliveryPreferences->setAlertEnable("Enable");

        // ApplicationDeliveryPreferences.DeliveryURLDetails
        $details = new DeliveryURLDetailType();
        $details->setDeliveryURLName('wplister_notify_handler');
        $details->setDeliveryURL($app_url.'&details=true');
        $details->setStatus('Enable');
        $req->ApplicationDeliveryPreferences->setDeliveryURLDetails($details,null);

        // UserDeliveryPreferenceArray
        $user = new NotificationEnableArrayType();
        $notifs = array();
         // put all of the notices you want here.
        foreach (array('BidReceived',
                       'EndOfListing',
                       'FixedPriceEndOfTransaction',
                       'FixedPriceTransaction',
                       'ItemListed',
                       'ItemSold',
                       'FeedbackReceived') as $event) {
          $n = new NotificationEnableType();
          $n->setEventType($event);
          $n->setEventEnable('Enable');
          $notifs[] = $n;
        }
        $user->setNotificationEnable($notifs,null);
        $req->setUserDeliveryPreferenceArray($user);

        
        // send request
        $res = $this->sp->SetNotificationPreferences($req);

        // handle result
        return ( print_r( $res, 1 ) );
        
    }



    // GetUserPreferences
    public function GetUserPreferences(){ 

        // prepare request
        $req = new GetUserPreferencesRequestType();
        $req->setShowSellerProfilePreferences( true );
        // $req->setShowSellerExcludeShipToLocationPreference( true );

        // send request
        $res = $this->sp->GetUserPreferences($req);
        // echo "<pre>";print_r($res);echo"</pre>";#die();

        // handle response error
        if ( 'EbatNs_ResponseError' == get_class( $res ) )
            return false;

        $SellerProfileOptedIn = $res->SellerProfilePreferences->SellerProfileOptedIn;
        update_option('wplister_ebay_seller_profiles_enabled', $SellerProfileOptedIn ? 'yes' : 'no' );

        $profiles = $res->getSellerProfilePreferences()->getSupportedSellerProfiles()->getSupportedSellerProfile();
        // echo "<pre>";print_r($profiles);echo"</pre>";#die();

        // if ( $SellerProfileOptedIn ) {
        if ( sizeof( $res->SellerProfilePreferences->SupportedSellerProfiles->SupportedSellerProfile ) > 0 ) {
            
            $seller_shipping_profiles = array();
            $seller_payment_profiles = array();
            $seller_return_profiles = array();

            foreach ( $res->SellerProfilePreferences->SupportedSellerProfiles->SupportedSellerProfile as $profile ) {
            
                $seller_profile = new stdClass();
                $seller_profile->ProfileID    = $profile->ProfileID;
                $seller_profile->ProfileName  = $profile->ProfileName;
                $seller_profile->ProfileType  = $profile->ProfileType;
                $seller_profile->ShortSummary = $profile->ShortSummary;
                
                switch ( $profile->ProfileType ) {
                    case 'SHIPPING':
                        $seller_shipping_profiles[] = $seller_profile;
                        break;
                    
                    case 'PAYMENT':
                        $seller_payment_profiles[] = $seller_profile;
                        break;
                    
                    case 'RETURN_POLICY':
                        $seller_return_profiles[] = $seller_profile;
                        break;
                }

            }
            update_option('wplister_ebay_seller_shipping_profiles', $seller_shipping_profiles);
            update_option('wplister_ebay_seller_payment_profiles', $seller_payment_profiles);
            update_option('wplister_ebay_seller_return_profiles', $seller_return_profiles);

        } else {
            delete_option( 'wplister_ebay_seller_shipping_profiles' );
            delete_option( 'wplister_ebay_seller_payment_profiles' );
            delete_option( 'wplister_ebay_seller_return_profiles' );
        }

        delete_option( 'wplister_ebay_seller_profiles' );

    }



    // GetUser
    public function GetUser(){ 

        // prepare request
        $req = new GetUserRequestType();
        
        // send request
        $res = $this->sp->GetUser($req);

        $UserID = $res->User->UserID;
        update_option('wplister_ebay_token_userid', $UserID);

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

        update_option('wplister_ebay_user', $user);

        return ( $UserID );        
    }

    // GetTokenStatus
    public function GetTokenStatus(){ 
        // require_once 'GetTokenStatusRequestType.php';

        // prepare request
        $req = new GetTokenStatusRequestType();
        
        // send request
        $res = $this->sp->GetTokenStatus($req);

        $expdate = $res->TokenStatus->ExpirationTime;

        if ( $expdate ) {

            $expdate = str_replace('T', ' ', $expdate);
            $expdate = str_replace('.000Z', '', $expdate);

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

    // TODO: fetch ebaySites from eBay
    public function getEbaySites() {

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
    function _getDomainnameBySiteId($siteid = 0)
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
                return 'ebay.my';
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

    } // _getDomainnameBySiteId()


}
