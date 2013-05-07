<?php
 /**
  * Xero.php
  * 
  * A Xero API authentication and usage library.
  * 
  * Constructed from code by Ronan Quirke
  * @link https://github.com/XeroAPI/XeroOAuth-PHP 
  *
  * @author Chris Santala <csantala@gmail.com>
  * @author Ronan Quirke
  *
  * DISCLAIMER OF WARRANTY
  * 
  * This source code is provided "as is" and without warranties as to performance or merchantability. 
  * The author and/or distributors of this source code may have made statements about this source code.
  * Any such statements do not constitute warranties and shall not be relied on by the user in deciding whether to use this source code.
  * This source code is provided without any express or implied warranties whatsoever. 
  * Because of the diversity of conditions and hardware under which this source code may be used, no warranty of fitness for a particular purpose is offered.
  * The user is advised to test the source code thoroughly before relying on it. The user must assume the entire risk of using the source code. Have fun.
  */


require('OAuthSimple.php');
require('Xro_config.php');

if(session_id() == ''){
	session_start();
}

class Xero extends Xro_config {
	
	// different app method defaults
	protected $xro_defaults = array('xero_url' => 'https://api.xero.com/api.xro/2.0',
		'site' => 'https://api.xero.com',
		'authorize_url' => 'https://api.xero.com/oauth/Authorize',
		'signature_method' => 'HMAC-SHA1');
	                     
	protected $xro_private_defaults = array('xero_url' => 'https://api.xero.com/api.xro/2.0',
		'site' => 'https://api.xero.com',
		'authorize_url' => 'https://api.xero.com/oauth/Authorize',
		'signature_method' => 'RSA-SHA1');
	                     
	protected $xro_partner_defaults = array( 'xero_url' => 'https://api-partner.network.xero.com/api.xro/2.0',
		'site' => 'https://api-partner.network.xero.com',
		'authorize_url' => 'https://api.xero.com/oauth/Authorize',
		'accesstoken_url' => 'https://api-partner.xero.com/oauth/AccessToken',
		'signature_method' => 'RSA-SHA1');
	                     
	protected $xro_partner_mac_defaults = array('xero_url' => 'https://api-partner2.network.xero.com/api.xro/2.0',
		'site' => 'https://api-partner2.network.xero.com',
		'authorize_url' => 'https://api.xero.com/oauth/Authorize',
		'accesstoken_url' => 'https://api-partner2.xero.com/oauth/AccessToken',
		'signature_method' => 'RSA-SHA1');
	                     
	// standard Xero OAuth stuff
	protected $xro_consumer_options = array( 'request_token_path' => '/oauth/RequestToken',
		'access_token_path' => '/oauth/AccessToken',
		'authorize_path' => '/oauth/Authorize');
									 
	function __construct() {
		
		switch ($this->xro_app_type) {
		case "Private":
		    $this->xro_settings = $this->xro_private_defaults;
		    $_GET['oauth_verifier'] = 1;
		   	$_COOKIE['oauth_token_secret'] = $this->signatures['shared_secret'];
		   	$_GET['oauth_token'] =  $this->signatures['consumer_key'];
		    break;
		case "Public":
		    $this->xro_settings = $this->xro_defaults;
		    break;
		case "Partner":
		    $this->xro_settings = $this->xro_partner_defaults;
		    break;
		case "Partner_Mac":
		    $this->xro_settings = $this->xro_partner_mac_defaults;
		    break;
		}
	}


	function api_response($xero_result){
		// double check they are connected
		if(XERO_CONNECTION_STATUS == "Connected"){
			// lets check for erros and store them and display them
			if ( is_object($xero_result) ) { // NOTE: disabled this as i think it was causing unneccasary errors
				$xero_xml = $xero_result->asXML();
				$xero_response = (string) $xero_result->Status;
				// maybe store this in the database or log file
				if($xero_response != "OK" || !isset($xero_response)){
					// store all the data we need in an array
					$xero_response_msg = array(
						"ErrorNumber" =>  (string) $xero_result->ErrorNumber,
						"Type" =>  (string) $xero_result->Type,
						"Message" =>  (string) $xero_result->Message,
						"Message_2" =>  (string) $xero_result->Elements->DataContractBase->ValidationErrors->ValidationError->Message,
						"Occured" => date("Y-m-d H:i:s")
					);
					
					// store array fields in database
					$xero_response_sql = sprintf(
						"INSERT INTO `api_xero_responses` (
							`ErrorNumber`,
							`Type`,
							`Message`,
							`Message_2`,
							`Occured`
						) VALUES (
							%s,
							%s,
							%s,
							%s,
							%s
						)",
						quote_smart($xero_response_msg['ErrorNumber']),
						quote_smart($xero_response_msg['Type']),
						quote_smart($xero_response_msg['Message']),
						quote_smart($xero_response_msg['Message_2']),
						quote_smart($xero_response_msg['Occured'])
					);
					@mysql_query($xero_response_sql);
					
					$returned_error = 'There was an error sending information to Xero, please try again.<br /> Error: [ '.$xero_response_msg['Message_2'].' ]';
					
					return $returned_error;
					
				}else{
					// everything is fine should we do something?
					$returned_error = "1";
					return $returned_error;
				}
			}else{ // NOTE: disabled this as i think it was causing unneccasary errors
				// it's not an object so we cannot carry on
				$returned_error = 'Unable to connect to Xero, please refresh this page and try again.';
				//return $returned_error;
			}
		}else{ // end of if connected 
			// can't carry on if they are no longer connected
			$returned_error = 'There was an error sending information to Xero, please make sure you are connected and try again.';
			return $returned_error;	
		}
	}

	function oauth() {

		$oauthObject = new OAuthSimple();
		$output = 'Authorizing...';

		# Set some standard curl options....
		$options = $this->set_curl_options();
		         	
		// In step 3, a verifier will be submitted.  If it's not there, we must be
		// just starting out. Let's do step 1 then.
		if (!isset($_GET['oauth_verifier'])) { 
		    ///////////////////////////////////\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\
		    // Step 1: Get a Request Token
		    //
		    // Get a temporary request token to facilitate the user authorization 
		    // in step 2. We make a request to the OAuthGetRequestToken endpoint,
		    // submitting the scope of the access we need (in this case, all the 
		    // user's calendars) and also tell Google where to go once the token
		    // authorization on their side is finished.
		    //
		    $result = $oauthObject->sign(array(
		        'path' => $this->xro_settings['site'].$this->xro_consumer_options['request_token_path'],
		        'parameters' => array(
		        'scope' => $this->xro_settings['xero_url'],
		        'oauth_callback' => $this->oauth_callback,
		        'oauth_signature_method' => $this->xro_settings['signature_method']),
		        'signatures'=> $this->signatures));

		    // The above object generates a simple URL that includes a signature, the 
		    // needed parameters, and the web page that will handle our request.  I now
		    // "load" that web page into a string variable.
		    $ch = curl_init();
		    
			curl_setopt_array($ch, $options);
		
		   if($this->debug){
		    	echo 'signed_url: ' . $result['signed_url'] . '<br/>';
		   }
		
		    curl_setopt($ch, CURLOPT_URL, $result['signed_url']);
		    $r = curl_exec($ch);
		    if($this->debug){
		  	  echo 'CURL ERROR: ' . curl_error($ch) . '<br/>';
		    }
		
		    curl_close($ch);
		
			if($this->debug){
		    	echo 'CURL RESULT: ' . print_r($r) . '<br/>';
		    }
		    
		    // We parse the string for the request token and the matching token
		    // secret. Again, I'm not handling any errors and just plough ahead 
		    // assuming everything is hunky dory.
		    parse_str($r, $returned_items);
		    $request_token = $returned_items['oauth_token'];
		    $request_token_secret = $returned_items['oauth_token_secret'];
		
			if($this->debug){
		    	echo 'request_token: ' . $request_token . '<br/>';
		    }

		    // We will need the request token and secret after the authorization.
		    // Google will forward the request token, but not the secret.
		    // Set a cookie, so the secret will be available once we return to this page.
		    setcookie("oauth_token_secret", $request_token_secret, time()+18000);
		    //
		    //////////////////////////////////////////////////////////////////////
		    
		    ///////////////////////////////////\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\
		    // Step 2: Authorize the Request Token
		    //
		    // Generate a URL for an authorization request, then redirect to that URL
		    // so the user can authorize our access request.  The user could also deny
		    // the request, so don't forget to add something to handle that case.
		    $result = $oauthObject->sign(array(
		        'path' => $this->xro_settings['authorize_url'],
		        'parameters' => array(
		        'oauth_token' => $request_token,
		        'oauth_signature_method' => $this->xro_settings['signature_method']),
		        'signatures' => $this->signatures));
		
		    // See you in a sec in step 3.
		    if($this->debug){
		  	 	 echo 'signed_url: ' . $result['signed_url'];
		  	  }else{
		  	 	 header("Location:$result[signed_url]");
		  	  }
		    exit;
		    //////////////////////////////////////////////////////////////////////
		}
		else {
		    ///////////////////////////////////\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\
		    // Step 3: Exchange the Authorized Request Token for an
		    //         Access Token.
		    //
		    // We just returned from the user authorization process on Google's site.
		    // The token returned is the same request token we got in step 1.  To 
		    // sign this exchange request, we also need the request token secret that
		    // we baked into a cookie earlier. 
		    //
		
		    // Fetch the cookie and amend our signature array with the request
		    // token and secret.
		    $this->signatures['oauth_secret'] = $_COOKIE['oauth_token_secret'];
		    $this->signatures['oauth_token'] = $_GET['oauth_token'];
		    
		    // only need to do this for non-private apps
		    if($this->xro_app_type != 'Private') {
				// Build the request-URL...
				$result = $oauthObject->sign(array(
					'path' => $this->xro_settings['site'].$this->xro_consumer_options['access_token_path'],
					'parameters' => array(
					'oauth_verifier' => $_GET['oauth_verifier'],
					'oauth_token' => $_GET['oauth_token'],
					'oauth_signature_method' => $this->xro_settings['signature_method']),
					'signatures'=> $this->signatures));
			
				// ... and grab the resulting string again. 
				$ch = curl_init();
				curl_setopt_array($ch, $options);
				curl_setopt($ch, CURLOPT_URL, $result['signed_url']);
				curl_setopt($ch, CURLOPT_USERAGENT, $this->user_agent);
				$r = curl_exec($ch);
	
				// Voila, we've got an access token.
				parse_str($r, $returned_items);		   
				$access_token = $returned_items['oauth_token'];
				$access_token_secret = $returned_items['oauth_token_secret'];
				$oauth_session_handle = $returned_items['oauth_session_handle'];
			} 
			else {
				$access_token = $this->signatures['oauth_token'];
				$access_token_secret = $this->signatures['oauth_secret'];
			}
				
		}
	    		
	    //////////////////////////////////////////////////////////////////////
	    $_SESSION['xero']['timer'] = time()+1800;
	    
	    // check if there are already active access tokens
	    $token_query = mysql_query("SELECT * FROM `api_xero` WHERE `id` = '1'");
		$token_total = mysql_num_rows($token_query);
		if($token_total > 0){
			$token_row = mysql_fetch_object($token_query);
			
			// make sure it's an active connection
			if($token_row->status == "Connected" && isset($token_row->access_token)){
				
				// use the tokens from the database
			    $this->signatures['oauth_token'] = $token_row->acess_token;
			    $this->signatures['oauth_secret'] = $token_row->access_token_secret;
			    if ($this->xro_app_type != "Public") {
					$this->signatures['oauth_session_handle'] = $token_row->oauth_session_handle;
				}
			}else{
				// nothing exists so use new tokens
			    $this->signatures['oauth_token'] = $access_token;
			    $this->signatures['oauth_secret'] = $access_token_secret;
			    if ($this->xro_app_type != "Public") {
					$this->signatures['oauth_session_handle'] = $oauth_session_handle;
					$oauth_s_h = $oauth_session_handle;
				}else{
					$oauth_s_h = $access_token_secret;
				}
				
			    // store newly created access tokens for future use
				$sql = sprintf(
					"UPDATE 
						`api_xero`
					SET 
						`access_token` = %s,
						`access_token_secret` = %s,
						`oauth_session_handle` = %s,
						`connection_start` = %s,
						`user_id` = %s,
						`status` = %s
					WHERE 
						`id` = %s",
						quote_smart($access_token),
						quote_smart($access_token_secret),
						quote_smart($oauth_s_h),
						quote_smart(time()),
						quote_smart($_SESSION['user_id']),
						quote_smart('Connected'),
						quote_smart(1)
				);		
					
				if(mysql_query($sql)){
					return $this->signatures; 
				}else{
					echo ' An error has occured please contat support.'.mysql_error();
					exit;
				}		
				
			}
		}else{
			// nothing exists
		    $this->signatures['oauth_token'] = $access_token;
		    $this->signatures['oauth_secret'] = $access_token_secret;
		    if ($this->xro_app_type != "Public") {
				$this->signatures['oauth_session_handle'] = $oauth_session_handle;
				$oauth_s_h = $oauth_session_handle;
			}else{
				$oauth_s_h = $access_token_secret;
			}
	    
		    // store newly created access tokens for future use
			$sql = sprintf(
				"UPDATE 
					`api_xero`
				SET 
					`access_token` = %s,
					`access_token_secret` = %s,
					`oauth_session_handle` = %s,
					`connection_start` = %s,
					`user_id` = %s,
					`status` = %s
				WHERE 
					`id` = %s",
					quote_smart($access_token),
					quote_smart($access_token_secret),
					quote_smart($oauth_s_h),
					quote_smart(time()),
					quote_smart($_SESSION['user_id']),
					quote_smart('Connected'),
					quote_smart(1)
			);		
				
			if(mysql_query($sql)){
				return $this->signatures; 
			}else{
				echo ' An error has occured please contact support.';
				exit;
			}		
			
		}
		
		return $this->signatures; 
				
	}
	
	
	public function __call($name, $arguments) {
		
		// first check if token needs to be renewed or not
		if(strtotime('+25 minutes', XERO_CONNECTION_START) <= time()){
			
			/**
			 * renew_access_token() fetchs a new token from xero replacing the existing one.
			 * @var [type]
			 * returns array()
			 */
			$renewed_tokens = $this->renew_access_token(); // renew tokens

			$oauthObject = new OAuthSimple();
		
			# Set some standard curl options....
			$options = $this->set_curl_options();						

			$this->signatures['oauth_token'] = $renewed_tokens['XERO_ACCESS_TOKEN'];
		    $this->signatures['oauth_secret'] = $renewed_tokens['XERO_ACCESS_TOKEN_SECRET'];
		    if ($this->xro_app_type != "Public") {
		     	$this->signatures['oauth_session_handle'] = $renewed_tokens['XERO_OAUTH_SESSION_HANDLE'];
			}

		}else{
			$oauthObject = new OAuthSimple();
		
			# Set some standard curl options....
			$options = $this->set_curl_options();						

			$this->signatures['oauth_token'] = XERO_ACCESS_TOKEN;
		    $this->signatures['oauth_secret'] = XERO_ACCESS_TOKEN_SECRET;
		    if ($this->xro_app_type != "Public") {
		     	$this->signatures['oauth_session_handle'] = XERO_OAUTH_SESSION_HANDLE;
			}

		}


		// figure out what method to use
		$name = strtolower($name);
		$valid_methods = array('accounts','contacts','creditnotes','currencies','invoices','organisation','payments','taxrates','trackingcategories','items','banktransactions','brandingthemes');
		$valid_post_methods = array('banktransactions','contacts','creditnotes','expenseclaims','invoices','items','manualjournals','receipts');
		$valid_put_methods = array('payments');
		$valid_get_methods = array('accounts','banktransactions','brandingthemes','contacts','creditnotes','currencies','employees','expenseclaims','invoices','items','journals','manualjournals','organisation','payments','receipts','taxrates','trackingcategories','users');
		$methods_map = array(
			'accounts' => 'Accounts',
			'banktransactions' => 'BankTransactions',
			'brandingthemes' => 'BrandingThemes',
			'contacts' => 'Contacts',
			'creditnotes' => 'CreditNotes',
			'currencies' => 'Currencies',
			'employees' => 'Employees',
			'expenseclaims' => 'ExpenseClaims',
			'invoices' => 'Invoices',
			'items' => 'Items',
			'journals' => 'Journals',
			'manualjournals' => 'ManualJournals',
			'organisation' => 'Organisation',
			'payments' => 'Payments',
			'receipts' => 'Receipts',
			'taxrates' => 'TaxRates',
			'trackingcategories' => 'TrackingCategories',
			'users' => 'Users'
		);

		
		if ( !in_array($name,$valid_methods) ) {
			// this doesn't exist yet - make Exception class for errors
			throw new XeroException('The selected method does not exist. Please use one of the following methods: '.implode(', ',$methods_map));
		}
		
		
		if ( (count($arguments) == 0) || ( is_string($arguments[0]) ) || ( is_numeric($arguments[0]) ) || ( $arguments[0] === false ) ) {
			if ( !in_array($name, $valid_get_methods) ) {
				return false;
			}
			
			$filterid = ( count($arguments) > 0 ) ? strip_tags(strval($arguments[0])) : false;
			if($arguments[1]!=false) $modified_after = ( count($arguments) > 1 ) ? str_replace( 'X','T', date( 'Y-m-dXH:i:s', strtotime($arguments[1])) ) : false;
			if($arguments[2]!=false) $where = ( count($arguments) > 2 ) ? $arguments[2] : false;
			if ( is_array($where) && (count($where) > 0) ) {
				$temp_where = '';
				foreach ( $where as $wf => $wv ) {
					if ( is_bool($wv) ) {
						$wv = ( $wv ) ? "%3d%3dtrue" : "%3d%3dfalse";
					} else if ( is_array($wv) ) {
						if ( is_bool($wv[1]) ) {
							$wv = ($wv[1]) ? rawurlencode($wv[0]) . "true" : rawurlencode($wv[0]) . "false" ;
						} else {
							$wv = rawurlencode($wv[0]) . "%22{$wv[1]}%22" ;
						}
					} else {
						$wv = "%3d%22$wv%22";
					}
					$temp_where .= "%26%26$wf$wv";
				}
				$where = strip_tags(substr($temp_where, 6));
			} else {
				$where = strip_tags(strval($where));
			}
			
			$order = ( count($arguments) > 3 ) ? strip_tags(strval($arguments[3])) : false;
			$acceptHeader = ( !empty( $arguments[4] ) ) ? $arguments[4] : '';
			$method = $methods_map[$name];
			$xero_url = $this->xro_settings['xero_url'].'/'.$method;
			if ( $filterid ) {
				$xero_url .= "/".$filterid;
			}
			if ( isset($where) && !empty($where) ) {
				$xero_url .= "?where=".$where;
			}
			if ( $order  && !$where) {
				$xero_url .= "?order=".$order;
			}elseif($order){
				$xero_url .= "&order=".$order;
			}else{
				// dont set $order
			}
			
		    // Xero API Access:
		    $oauthObject->reset();
		    $result = $oauthObject->sign(array(
		        'path' => $xero_url,
		        'parameters' => array(
				'oauth_signature_method' => $this->xro_settings['signature_method']),
		       	'signatures'=> $this->signatures
			));	
			
			$ch = curl_init();
			if($acceptHeader == 'pdf' ) { 
				curl_setopt($ch, CURLOPT_HTTPHEADER, array ("Accept: application/".$acceptHeader)); 
			}
			curl_setopt($ch, CURLOPT_URL, $oauthObject->to_url());
			if(isset($modified_after) && $modified_after != false) { 
				curl_setopt($ch, CURLOPT_HTTPHEADER, array("If-Modified-Since: $modified_after"));
			}
			curl_setopt($ch, CURLOPT_USERAGENT, $this->user_agent);
			curl_setopt_array($ch, $options);

			$temp_xero_response = curl_exec($ch);
			curl_close($ch);
			parse_str($temp_xero_response, $returned_items);	

			if ( $acceptHeader=='pdf' ) {
				return $temp_xero_response;
			}

			/*if (isset($returned_items['oauth_problem'])) {			
				// check for expired access token and renew (partner app only)
				//if ($this->xro_app_type == "Partner" && $returned_items['oauth_problem'] == 'token_expired') {
				if ($returned_items['oauth_problem'] == 'token_expired') {
					$this->renew_access_token();
				}
			}*/	
			
			try {
				if(@simplexml_load_string( $temp_xero_response ) == false){
					throw new XeroException($temp_xero_response);
					$xero_xml = false;
				}else{
					$xero_xml = simplexml_load_string( $temp_xero_response );
				}
			}

			catch (XeroException $e){
				return $e->getMessage();
			}

			if (isset($xero_xml) ) {
				return $xero_xml;
			} elseif(isset($xero_xml)) {
				return ArrayToXML::toArray( $xero_xml );
			}
		
		/****************************** POST / PUT METHODS *************************************/													
		} elseif ( (count($arguments) == 1) || ( is_array($arguments[0]) ) || ( is_a( $arguments[0], 'SimpleXMLElement' ) ) ) {

			if ( !(in_array($name, $valid_post_methods) || in_array($name, $valid_put_methods)) ) {
				return false;
			}
			
			// create xml for posting
			$method = $methods_map[$name];
			if ( is_a( $arguments[0], 'SimpleXMLElement' ) ) {
				$post_body = $arguments[0]->asXML();
			} elseif ( is_array( $arguments[0] ) ) {
				$post_body = ArrayToXML::toXML( $arguments[0], $rootNodeName = $method );
			}
			$post_body = trim(substr($post_body, (stripos($post_body, ">")+1) ));
			
			
			/****************************** POST METHOD *************************************/													
			if ( in_array( $name, $valid_post_methods ) ) {
				$xero_url = $this->xro_settings['xero_url'].'/'.$method;

			    $oauthObject->reset();
			    $result = $oauthObject->sign(array(
			        'path' => $xero_url,
			        'action' => 'POST',
			        'parameters'=> array(
					'oauth_signature_method' => $this->xro_settings['signature_method'],
					'xml' => array('xml'=>$post_body)),
			        'signatures'=> $this->signatures));

				$ch = curl_init();
				curl_setopt_array($ch, $options);
				curl_setopt($ch, CURLOPT_URL, $xero_url);
				curl_setopt($ch, CURLOPT_POST, true);
				curl_setopt($ch, CURLOPT_POSTFIELDS, $oauthObject->to_postdata() );
				curl_setopt($ch, CURLOPT_HEADER, $oauthObject->to_header());
				
				
			/****************************** PUT METHOD *************************************/													
			} else {
				$xero_url = $this->xro_settings['xero_url'].'/'.$method;
				
			    $oauthObject->reset();
			    $result = $oauthObject->sign(array(
			        'path' => $xero_url,
			        'action' => 'PUT',
			        'parameters'=> array(
					'oauth_signature_method' => $this->xro_settings['signature_method']),
			        'signatures'=> $this->signatures));
				
				
				$xml = $post_body;
				$fh  = fopen('php://memory', 'w+');
				fwrite($fh, $xml);
				rewind($fh);
				$ch = curl_init($oauthObject->to_url());
				curl_setopt_array($ch, $options);
				curl_setopt($ch, CURLOPT_PUT, true);
				curl_setopt($ch, CURLOPT_INFILE, $fh);
				curl_setopt($ch, CURLOPT_INFILESIZE, strlen($xml));
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			}
			
			
			
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_USERAGENT, $this->user_agent);
			$xero_response = curl_exec($ch);
			if (isset($fh)) fclose($fh);
			
			try {
				if(@simplexml_load_string( $xero_response )==false){
					throw new XeroException($xero_response);
				}else{
					$xero_xml = simplexml_load_string( $xero_response );
				}
			}
			catch (XeroException $e){
				//display custom message
				return $e->getMessage() . "<br/>";
			}

			curl_close($ch);
			if (!isset($xero_xml) ) {
				return false;
			}
			if ( isset($xero_xml)) {
				return $xero_xml;
			} elseif(isset($xero_xml)) {
				return ArrayToXML::toArray( $xero_xml );
			}
		
		} else {
			return false;
		}
		
	}
	
	
	public function __get($name) {
		return $this->$name();
	}
	

	function renew_access_token() {
		# Set some standard curl options....
		$options = $this->set_curl_options();
		
		if(XERO_CONNECTION_STATUS == "Connected"){
						
			$this->signatures['oauth_token'] = XERO_ACCESS_TOKEN;
		    $this->signatures['oauth_secret'] = XERO_ACCESS_TOKEN_SECRET;
		    if ($this->xro_app_type != "Public") {
		     	$this->signatures['oauth_session_handle'] = XERO_OAUTH_SESSION_HANDLE;
			 }else{ 
			 	$this->signatures['oauth_session_handle'] = NULL;
			 }
			
			$oauthObject = new OAuthSimple();
		
			$oauthObject->reset();
	    	$result = $oauthObject->sign(array(
	        	'path' => $this->xro_settings['site'].$this->xro_consumer_options['access_token_path'],
	        	'parameters'=> array(
	            	'scope' => $this->xro_settings['xero_url'],
	            	'oauth_session_handle' => $this->signatures['oauth_session_handle'],
	            	'oauth_token' => $this->signatures['oauth_token'],
	            	'oauth_signature_method' => $this->xro_settings['signature_method']),
	            	'signatures'=> $this->signatures
	            )
	         );
			
			$ch = curl_init();
			curl_setopt_array($ch, $options);
		    curl_setopt($ch, CURLOPT_URL, $result['signed_url']);
			$r = curl_exec($ch);
			parse_str($r, $returned_items);	
			
			if($this->debug){
				echo 'CURL ERROR: '.curl_error($ch);
			}
						
			curl_close($ch);

			if (!isset($returned_items['oauth_problem'])) { // if there's no errors then update with new tokens

				// save the new tokens in the database for future use.
				$renew_sql = sprintf(
					"UPDATE 
						`api_xero`
					SET 
						`access_token` = %s,
						`access_token_secret` = %s,
						`oauth_session_handle` = %s,
						`connection_start` = %s,
						`user_id` = %s,
						`status` = %s
					WHERE 
						`id` = %s",
						quote_smart($returned_items['oauth_token']),
						quote_smart($returned_items['oauth_token_secret']),
						quote_smart($returned_items['oauth_session_handle']),
						quote_smart(time()),
						quote_smart($_SESSION['user_id']),
						quote_smart('Connected'),
						quote_smart(1)
				);		
					
				if(!mysql_query($renew_sql)){
					echo ' An error has occured please contat support.';
					exit;
				}else{
					$_SESSION['xero']['timer'] = time()+1800; // not really using SESSIONS
				}
				//echo '<p>renewal OK...</p>';
				//exit();
				
				// re-define as we need it at the time of running this script.
				return array('XERO_ACCESS_TOKEN' => $returned_items['oauth_token'], 'XERO_ACCESS_TOKEN_SECRET' => $returned_items['oauth_token_secret'], 'XERO_OAUTH_SESSION_HANDLE' => $returned_items['oauth_session_handle']);		

			}else{
				// re-define as we need it at the time of running this script.
				return array('XERO_ACCESS_TOKEN' => XERO_ACCESS_TOKEN, 'XERO_ACCESS_TOKEN_SECRET' => XERO_ACCESS_TOKEN_SECRET, 'XERO_OAUTH_SESSION_HANDLE' => XERO_OAUTH_SESSION_HANDLE);		
				
				//$this->renew_access_token();
				//echo '<p>running renewal again...'.$returned_items['oauth_problem'].'</p>';
				//exit();
			}


		}else{
			// not connected so we cannot renew, instead we need a complete reconnect.
			// in order to do the following we will have to run this function before any headers are sent.
			$this->oauth();
		}
	}

	// A function to set up the CURL options - instead of settin them each time
	protected function set_curl_options() {
		
		if ($this->xro_app_type == "Partner") {
		$options[CURLOPT_SSLCERT] = XERO_CURLOPT_SSLCERT;
		$options[CURLOPT_SSLKEYPASSWD] = XERO_CURLOPT_SSLKEYPASSWD; 
		$options[CURLOPT_SSLKEY] = XERO_CURLOPT_SSLKEY;
		}
		$options[CURLOPT_VERBOSE] = 1;
    	$options[CURLOPT_RETURNTRANSFER] = 1;
    	$options[CURLOPT_SSL_VERIFYHOST] = 2;
    	$options[CURLOPT_SSL_VERIFYPEER] = true;
    	
		return $options;
	}
	
} // end of Xero class


class ArrayToXML
{
    /**
     * The main function for converting to an XML document.
     * Pass in a multi dimensional array and this recrusively loops through and builds up an XML document.
     *
     * @param array $data
     * @param string $rootNodeName - what you want the root node to be - defaultsto data.
     * @param SimpleXMLElement $xml - should only be used recursively
     * @return string XML
     */
    public static function toXML( $data, $rootNodeName = 'ResultSet', &$xml=null ) {

        // turn off compatibility mode as simple xml throws a wobbly if you don't.
        if ( ini_get('zend.ze1_compatibility_mode') == 1 ) ini_set ( 'zend.ze1_compatibility_mode', 0 );
        if ( is_null( $xml ) ) {
		$xml = simplexml_load_string( "<$rootNodeName />" );
		$rootNodeName = rtrim($rootNodeName, 's');
	}
	// loop through the data passed in.
        foreach( $data as $key => $value ) {

            // no numeric keys in our xml please!
	    $numeric = 0;
            if ( is_numeric( $key ) ) {
                $numeric = 1;
                $key = $rootNodeName;
            }

            // delete any char not allowed in XML element names
            $key = preg_replace('/[^a-z0-9\-\_\.\:]/i', '', $key);

            // if there is another array found recursively call this function
            if ( is_array( $value ) ) {
                $node = ( ArrayToXML::isAssoc( $value ) || $numeric ) ? $xml->addChild( $key ) : $xml;

                // recursive call.
                if ( $numeric ) $key = 'anon';
                ArrayToXML::toXml( $value, $key, $node );
            } else {

                // add single node.
                $xml->$key = $value;
            }
        }

        // pass back as XML
        return $xml->asXML();

    // if you want the XML to be formatted, use the below instead to return the XML
        //$doc = new DOMDocument('1.0');
        //$doc->preserveWhiteSpace = false;
        //$doc->loadXML( $xml->asXML() );
        //$doc->formatOutput = true;
        //return $doc->saveXML();
    }


    /**
     * Convert an XML document to a multi dimensional array
     * Pass in an XML document (or SimpleXMLElement object) and this recrusively loops through and builds a representative array
     *
     * @param string $xml - XML document - can optionally be a SimpleXMLElement object
     * @return array ARRAY
     */
    public static function toArray( $xml ) {
        if ( is_string( $xml ) ) $xml = new SimpleXMLElement( $xml );
        $children = $xml->children();
        if ( !$children ) return (string) $xml;
        $arr = array();
        foreach ( $children as $key => $node ) {
            $node = ArrayToXML::toArray( $node );

            // support for 'anon' non-associative arrays
            if ( $key == 'anon' ) $key = count( $arr );

            // if the node is already set, put it into an array
            if ( array_key_exists($key, $arr) &&  isset( $arr[$key] ) ) {
                if ( !is_array( $arr[$key] ) || !array_key_exists(0,$arr[$key]) ||  ( array_key_exists(0,$arr[$key]) && ($arr[$key][0] == null)) ) $arr[$key] = array( $arr[$key] );
                $arr[$key][] = $node;
            } else {
                $arr[$key] = $node;
            }
        }
        return $arr;
    }

    // determine if a variable is an associative array
    public static function isAssoc( $array ) {
        return (is_array($array) && 0 !== count(array_diff_key($array, array_keys(array_keys($array)))));
    }
}

class XeroException extends Exception { }

class XeroApiException extends XeroException {
	private $xml;

	public function __construct($xml_exception)
	{
		$this->xml = $xml_exception;
		$xml = new SimpleXMLElement($xml_exception);

		list($message) = $xml->xpath('/ApiException/Message');
		list($errorNumber) = $xml->xpath('/ApiException/ErrorNumber');
		list($type) = $xml->xpath('/ApiException/Type');

		parent::__construct((string)$type . ': ' . (string)$message, (int)$errorNumber);

		$this->type = (string)$type;
	}

	public function getXML()
	{
		return $this->xml;
	}

	public static function isException($xml)
	{
		return preg_match('/^<ApiException.*>/', $xml);
	}


}
?>