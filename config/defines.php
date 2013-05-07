<?php
/* XERO  *********************************************/

// Instead of checking the database before every request I have made the following available as a constant
// 
$xero_api_qry = mysql_query("SELECT * FROM `api_xero` LIMIT 0,1");
$xero_api_total = mysql_num_rows($xero_api_qry);
if($xero_api_total > 0){
	$xero_api_row = mysql_fetch_object($xero_api_qry);
	if($xero_api_row->status == "Connected" && isset($xero_api_row->access_token)){
	
		define('XERO_ACCESS_TOKEN', $xero_api_row->access_token);
		define('XERO_ACCESS_TOKEN_SECRET', $xero_api_row->access_token_secret);
		define('XERO_OAUTH_SESSION_HANDLE', $xero_api_row->oauth_session_handle);		
		define('XERO_CONNECTION_STATUS', $xero_api_row->status);
		define('XERO_CONNECTION_START', $xero_api_row->connection_start);
		define('XERO_SALES_ACCOUNT', $xero_api_row->default_sales_account);
		define('XERO_PURCHASES_ACCOUNT', $xero_api_row->default_purchases_account);
			 
	}else{
		define('XERO_CONNECTION_STATUS', $xero_api_row->status);
	}
}
?>