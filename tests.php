<?php
// run configuration file
include("./config/application.php");

// to access these test simple add "?test=invoices" or "?test=contacts" to the url


/**
 * CONTACTS TEST 
 */
// If Xero connection is present then go ahead and send to xero api
if(XERO_CONNECTION_STATUS == "Connected" && $_GET['test'] == 'contacts'){
	
	$xero_name = 'Bob Jones';
	
	$xero_new_contact = array( 
		array( 
			"ContactStatus" => "ACTIVE",
			"Name" => $xero_name, 
			"FirstName" => 'Bob', 
			"LastName" => 'Jones', 
			"EmailAddress" => 'bob.jones@example.com',
			"BankAccountDetails" => '00-000-000-0000',
			"Addresses" => array( 
				"Address" => array( 
					array( 
						"AddressType" => "POBOX", 
						"AddressLine1" => '101 Some Street', 
						"AddressLine2" => 'Suburb',
						"City" => 'City', 
						"PostalCode" => '0000',
						"Country" => 'Nu Zealund'
					),  
					array( 
						"AddressType" => "STREET", 
						"AddressLine1" => '101 Some Street', 
						"AddressLine2" => 'Suburb',
						"City" => 'City', 
						"PostalCode" => '0000',
						"Country" => 'Nu Zealund'
					) 
				) 
			),
			"Phones" => array( 
				"Phone" => array( 
					array( 
						"PhoneType" => "DEFAULT", 
						"PhoneNumber" => '000000000'
					),  
					array( 
						"PhoneType" => "MOBILE", 
						"PhoneNumber" => '000000'
					) 
				) 
			) 
		) 
	);
	
	// attempt to try sending information to xero api
	$xero_result = $xero->Contacts( $xero_new_contact );
	$xero_api_response = $xero->api_response($xero_result);
	if($xero_api_response != 1){
		$GLOBALS['xero_response'] = $xero_api_response;
		$GLOBALS['xero_response_type'] = 'err';
	}else{
		$GLOBALS['xero_response'] = 'Contact has now been sent to Xero';
		$GLOBALS['xero_response_type'] = '';
	}						
} // end of check xero session is active





/**
 * INVOICE TEST 
 */
// If Xero connection is present then go ahead and send to xero api
// if there was a sufficient amount of data supplied then do xero api call 
if(XERO_CONNECTION_STATUS == "Connected" && $_GET['test'] == 'invoices'){
	
	$xero_name = 'Bob Jones';

	// line items array
	$line_item_array[] = array(
		"Description" => 'A new iPhone',
        "Quantity" => '1',
        "UnitAmount" => '1000000',
        //"AccountCode" => XERO_SALES_ACCOUNT
        "AccountCode" => '200'
	);

	// start invoice array
	$new_invoice = array(
	    array(
	        "Type"=>"ACCREC",
	        "Contact" => array(
	            "Name" => $xero_name
	        ),
	        "InvoiceNumber" => 'INV-1234',
	        "Reference" => 'My Invoice Test',
	        "Date" => '2013-01-01',
	        "DueDate" => '2013-02-02',
	        "Status" => "AUTHORISED",
	        "LineItems"=> array(
	            "LineItem" => $line_item_array
	        )
	    )
	);
	
	$xero_result = $xero->Invoices( $new_invoice );
	$xero_api_response = $xero->api_response($xero_result);
	if($xero_api_response != 1){
		$GLOBALS['xero_response'] = $xero_api_response;
		$GLOBALS['xero_response_type'] = 'err';

		// set new invoice response message as we need to tell the user to try again
		$invoice_response_msg = "Invoice was not approved as there was an error. Please try again.";
		$invoice_response_type = 'err';
		
	}else{
		$invoice_response_msg = "Invoice has now been Approved, you can now send it and apply payments.";
		$invoice_response_type = '';

		$xero_invoice_id = $xero_result->Invoices->Invoice->InvoiceID; // get actual id of Xero invoice

		// EXAMPLE - update system invoice to say it's in Xero
	}		
	
	// xero response																				
	$GLOBALS['xero_response'] = $invoice_response_msg;
	$GLOBALS['xero_response_type'] = $invoice_response_type;
						
} // end of if connected to Xero




/**
 * INVOICE TEST 
 */
if(XERO_CONNECTION_STATUS == "Connected" && $_GET['test'] == 'payments'){
	$xero_result = $xero->Payments(false, date("Y-m-d\TH:i:s", strtotime("-7 days"))); // get all invoices that have been modified from the last 7 days.
	$xero_api_response = $xero->api_response($xero_result);
	if($xero_api_response == 1){
		foreach($xero_result as $payments){
			foreach($payments as $payment){
				// EXAMPLE - here you could do a bulk update of payments for system invoices
				echo '<pre>';
				print_r($payment);									
				echo '</pre>';
			}
		}
	}	
} // end of if XERO connected






// quick trick to display repsonses from above
$xero_response = xero_response();
if($xero_response){
	echo $xero_response;
}
?>