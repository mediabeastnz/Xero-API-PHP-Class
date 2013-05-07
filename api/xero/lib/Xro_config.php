<?php	
	// need to define this here as PHP has strict rules on declaring properties with ". ."
	
	define('BASE_PATH',dirname(__FILE__));
	
	define("CALLBACK_URL", "http://". $_SERVER['HTTP_HOST'] ."/api/xero/authorise.php");
	define("XERO_RSA_PRIVATE", BASE_PATH."/privatekey.pem");
	define("XERO_RSA_PUBLIC", BASE_PATH."/publickey.cer");

	define("XERO_CURLOPT_SSLCERT", BASE_PATH."/entrust-cert.pem");
	define("XERO_CURLOPT_SSLKEYPASSWD", "cr381ve");
	define("XERO_CURLOPT_SSLKEY", BASE_PATH."/entrust-private-nopass.pem");	
	
	class Xro_config
	{	
		protected $debug = false;
		
		// public, partner, or private
		protected $xro_app_type = "Partner"; 
		
		// Signs your requests to a unique name e.g. Google
		protected $user_agent = "Trackthat"; 
		
		// local
		protected $oauth_callback = CALLBACK_URL;
		
		// production
		//private $oauth_callback = '';
		                       	 
		protected $signatures = array(
			// local
			//'consumer_key' => 'R0C90NNYCKUJWRB1A3DV3PSYBNGR8N',
			//'shared_secret' => 'TRXQBWP1W6YQ9WWSSOTQFITDJI8WSI',
			
			// production
			'consumer_key' => 'RAACAXYZIV16AXBPWA3LYR62RPJWSH',
			'shared_secret' => 'FWM7LJJEIC5TOVHR4TCJ9GACYTPEW7',
			'rsa_private_key' => XERO_RSA_PRIVATE,
			'rsa_public_key'	=> XERO_RSA_PUBLIC
		 );
	}
?>