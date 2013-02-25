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
session_start();

define('BASE_PATH',realpath('.'));

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
		    setcookie("oauth_token_secret", $request_token_secret, time()+1800);
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
				// $oauth_session_handle = $returned_items['oauth_session_handle'];
			} 
			else {
				$access_token = $this->signatures['oauth_token'];
				$access_token_secret = $this->signatures['oauth_secret'];
			}
				
		}
	    
	    $this->signatures['oauth_token'] = $access_token;
	    $this->signatures['oauth_secret'] = $access_token_secret;
	    if ($this->xro_app_type =! "Public") {
			$this->signatures['oauth_session_handle'] = $oauth_session_handle;
		}
	    //////////////////////////////////////////////////////////////////////
	    
	    $_SESSION['access_token'] = $access_token;
		$_SESSION['access_token_secret'] = $access_token_secret;
		$_SESSION['oauth_session_handle'] = $access_token_secret;
	    
		return $this->signatures; 
				
	}
	
	public function __call($name, $arguments) {
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

		$oauthObject = new OAuthSimple();
		
		$this->signatures['oauth_token'] = $_SESSION['access_token'];
	    $this->signatures['oauth_secret'] = $_SESSION['access_token_secret'];
	    if ($this->xro_app_type =! "Public") {
	     	$this->signatures['oauth_session_handle'] = $_SESSION['oauth_session_handle'];
		 }
		
		if ( !in_array($name,$valid_methods) ) {
			// this doesn't exist yet - make Exception class for errors
			//echo 'The selected method does not exist. Please use one of the following methods: '.implode(', ',$methods_map).'.<br />'.$name.'<br />'.$valid_methods;
			throw new XeroException('The selected method does not exist. Please use one of the following methods: '.implode(', ',$methods_map));
		}
		
		if ( (count($arguments) == 0) || ( is_string($arguments[0]) ) || ( is_numeric($arguments[0]) ) || ( $arguments[0] === false ) ) {
			// It's a GET
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
			if ( $order ) {
				$xero_url .= "&order=".$order;
			}
			
			# Set some standard curl options....
			$options = $this->set_curl_options();
			 
		    //////////////////////////////////////////////////////////////////////
	
		    // Xero API Access:
		    $oauthObject->reset();
		    $result = $oauthObject->sign(array(
		        'path' => $xero_url,
		        'parameters' => array(
				'oauth_signature_method' => $this->xro_settings['signature_method']),
		       	'signatures'=> $this->signatures
			));	
										
			$ch = curl_init();
			curl_setopt_array($ch, $options);
			if ( $acceptHeader == 'pdf' ) { curl_setopt($ch, CURLOPT_HTTPHEADER, array ("Accept: application/".$acceptHeader)); }
			curl_setopt($ch, CURLOPT_URL, $result['signed_url']);
			if ( isset($modified_after) && $modified_after != false) { curl_setopt($ch, CURLOPT_HTTPHEADER, array("If-Modified-Since: $modified_after"));}
			curl_setopt($ch, CURLOPT_USERAGENT, $this->user_agent);
			$temp_xero_response = curl_exec($ch);
			curl_close($ch);
			parse_str($temp_xero_response, $returned_items);	
						
			if ( $acceptHeader=='pdf' ) {
				return $temp_xero_response;
			}

			if (isset($returned_items['oauth_problem'])) {			
				// check for expired access token and renew (partner app only)
			    // then re-call API
				if ($this->xro_app_type == "Partner" && $returned_items['oauth_problem'] == 'token_expired') {
					$this->renew_access_token();
					// this is probably a bad idea
				}
				else {
					// dump to screen error and terminate.
					session_unset(); 
					echo "<pre>"; print_r($returned_items); echo '</pre><br /><p>Public Connections only allow for 30 minute windows... <a href="index.php">Go Home</a> and connect</p>'; exit;
				}
			}	
			
			try {
				if(@simplexml_load_string( $temp_xero_response ) == false){
					throw new XeroException($temp_xero_response);
					$xero_xml = false;
				}else{
					$xero_xml = simplexml_load_string( $temp_xero_response );
				}
			}

			catch (XeroException $e)
			{
				return $e->getMessage();
			}

			if (isset($xero_xml) ) {
				return $xero_xml;
			} elseif(isset($xero_xml)) {
				return ArrayToXML::toArray( $xero_xml );
			}
													
		} elseif ( (count($arguments) == 1) || ( is_array($arguments[0]) ) || ( is_a( $arguments[0], 'SimpleXMLElement' ) ) ) {
			// It's a POST
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
			
			// POST
			if ( in_array( $name, $valid_post_methods ) ) {
				$xero_url = $this->xro_settings['xero_url'].'/'.$method;

				# Set some standard curl options....
				$options = $this->set_curl_options();
				
			    $oauthObject->reset();
			    $result = $oauthObject->sign(array(
			        'path' => $xero_url,
			        'action' => 'POST',
			        'parameters'=> array(
					'oauth_signature_method' => $this->xro_settings['signature_method'],
					'xml' => array('xml'=>$post_body)),
			        'signatures'=> $this->signatures));

				$ch = curl_init();
				curl_setopt($ch,CURLOPT_SSL_VERIFYPEER, false);
				curl_setopt($ch, CURLOPT_URL, $xero_url);
				curl_setopt($ch, CURLOPT_POST, true);
				curl_setopt($ch, CURLOPT_POSTFIELDS, $oauthObject->to_postdata() );
				curl_setopt($ch, CURLOPT_HEADER, $oauthObject->to_header());
				
			// PUT
			} else {
				$xero_url = $this->xro_settings['xero_url'].'/'.$method;
				
				# Set some standard curl options....
				$options = $this->set_curl_options();				
				
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
				curl_setopt($ch,CURLOPT_SSL_VERIFYPEER, false);
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

			catch (XeroException $e)
			{
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
	
	// TEST THIS - this is yet to be tested
	function renew_access_token() {
		
		# Set some standard curl options....
		$options = $this->set_curl_options();
		
		$this->signatures['oauth_token'] = $_SESSION['access_token'];
	    $this->signatures['oauth_secret'] = $_SESSION['access_token_secret'];
	    if ($this->xro_app_type =! "Public") {
	     	$this->signatures['oauth_session_handle'] = $_SESSION['oauth_session_handle'];
		 }
		else $this->signatures['oauth_session_handle'] = NULL;
		
		$oauthObject = new OAuthSimple();
	
		$oauthObject->reset();
    	$result = $oauthObject->sign(array(
        	'path' => $this->xro_settings['site'].$this->xro_consumer_options['access_token_path'],
        	'parameters'=> array(
            'scope' => $this->xro_settings['xero_url'],
            'oauth_session_handle' => $this->signatures['oauth_session_handle'],
            'oauth_token' => $this->signatures['oauth_token'],
            'oauth_signature_method' => $this->xro_settings['signature_method']),
        'signatures'=> $this->signatures));
		
		$ch = curl_init();
		curl_setopt_array($ch, $options);
	    curl_setopt($ch, CURLOPT_URL, $result['signed_url']);
		$r = curl_exec($ch);
		parse_str($r, $returned_items);	

		// pop new access token into session
		$_SESSION['access_token'] = $returned_items['oauth_token'];
		$_SESSION['access_token_secret'] = $returned_items['oauth_token_secret'];
		$_SESSION['oauth_session_handle'] = $returned_items['oauth_session_handle'];	   

		curl_close($ch);
	}

	// A function to set up the CURL options - instead of settin them each time
	protected function set_curl_options() {
		
		if ($this->xro_app_type == "Partner") {
			$options[CURLOPT_SSLCERT] = '/[path]/entrust-cert.pem';
			$options[CURLOPT_SSLKEYPASSWD] = '[password]'; 
			$options[CURLOPT_SSLKEY] = '/[path]/entrust-private.pem';
		}
		$options[CURLOPT_VERBOSE] = 1;
    	$options[CURLOPT_RETURNTRANSFER] = 1;
    	$options[CURLOPT_SSL_VERIFYHOST] = 0;
    	$options[CURLOPT_SSL_VERIFYPEER] = 0;
    	/*$useragent = (isset($useragent)) ? (empty($useragent) ? 'Trackthat' : $useragent) : 'Trackthat'; 
    	$options[CURLOPT_USERAGENT] = $useragent;*/
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