<?php # based on http://exdwh.com/ebay/signin.txt

// deprecated in WP-Lister 1.1

class EbayGetSessionID {

	var $_session;
	var $apiurl;
	var $signin;
	var $devId;
	var $appId;
	var $certId;
	var $RuName;
	var $siteId;
	var $sandbox;
	var $compLevel;

	function EbayGetSessionID( $site_id, $sandbox_enabled = false ) {
		global $wpl_logger;
		$this->logger = &$wpl_logger;

		$this->siteId = $site_id;
		$this->sandbox = $sandbox_enabled;
		$this->compLevel = 765;

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

	}

	function getToken( $SessionID ) {
		$body = "\n  <SessionID>{$SessionID}</SessionID>\n";
		$token = $this->TradeAPI( 'FetchToken', $body, 'eBayAuthToken' );
		#echo '<p/>Your token is:<p/>' . $token;
		$this->logger->info('FetchToken: '.$token);
		return $token;
	}

	function getTokenExpirationTime( $token ) {
		$body = "\n  <RequesterCredentials><eBayAuthToken>{$token}</eBayAuthToken></RequesterCredentials>\n";
		$expdate = $this->TradeAPI( 'GetTokenStatus', $body, 'ExpirationTime' );
		$this->logger->info('getTokenExpirationTime: '.$expdate);
		$expdate = str_replace('T', ' ', $expdate);
		$expdate = str_replace('.000Z', '', $expdate);
		#echo '<p/>Your token expires on:<p/>' . $expdate;
		return $expdate;
	}


	function TradeAPI( $call, $body, $field ) {

		if ( ( $response = file_get_contents( $this->apiurl, 'r', stream_context_create( array( 'http' => array(
			'method' => 'POST',

			'header' =>
				"Content-Type: text/xml; charset=utf-8\r\n"
				. "X-EBAY-API-SITEID: {$this->siteId}\r\n"
				. "X-EBAY-API-COMPATIBILITY-LEVEL: {$this->compLevel}\r\n"
				. "X-EBAY-API-CALL-NAME: {$call}\r\n"

				// these headers are only required for GetSessionID and FetchToken
				. "X-EBAY-API-DEV-NAME: {$this->devId}\r\n"
				. "X-EBAY-API-APP-NAME: {$this->appId}\r\n"
				. "X-EBAY-API-CERT-NAME: {$this->certId}\r\n",

			'content' => $request =
				"<?xml version='1.0' encoding='utf-8'?>\n"
				. "<{$call} xmlns='urn:ebay:apis:eBLBaseComponents'>{$body}</{$call}>"
			) ) ) ) ) === FALSE ) 
		{
			exit( 'No response from eBay server!' );
		}

		// echo "<pre>request:\n";
		// echo	"Content-Type: text/xml; charset=utf-8\r\n"
		// 		. "X-EBAY-API-SITEID: {$this->siteId}\r\n"
		// 		. "X-EBAY-API-COMPATIBILITY-LEVEL: {$this->compLevel}\r\n"
		// 		. "X-EBAY-API-CALL-NAME: {$call}\r\n"

		// 		// these headers are only required for GetSessionID and FetchToken
		// 		. "X-EBAY-API-DEV-NAME: {$this->devId}\r\n"
		// 		. "X-EBAY-API-APP-NAME: {$this->appId}\r\n"
		// 		. "X-EBAY-API-CERT-NAME: {$this->certId}\r\n";
		// echo 	htmlentities("<{$call} xmlns='urn:ebay:apis:eBLBaseComponents'>{$body}</{$call}>");
		// echo "</pre>";


		// found open tag?
		if ( ( $begin = strpos( $response, "<{$field}>" ) ) !== FALSE ) {
			// skip open tag
			$begin += strlen( $field ) + 2;

			// found close tag?
			if ( ( $end = strpos( $response, "</{$field}>", $begin ) ) !== FALSE ) {
				return substr( $response, $begin, $end - $begin );
			}
		}

		echo "<pre>response:\n";
		echo( htmlentities($response) );
		echo "</pre>";

		exit( "Field <b>{$field}</b> not found in eBay response!<p/>\n\n{$response}" );
	}




}
