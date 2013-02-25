PHP-Xero PHP Wrapper
====================

Introduction
------------
A class for interacting with the xero (xero.com) Public/Partner application API. More documentation for Xero can be found at http://blog.xero.com/developer/api-overview/  
It is suggested you become familiar with the API before using this class, otherwise it may not make much sense to you - http://blog.xero.com/developer/api/

I have gathered a bunch of tools from a few people so thank you to all of them (See Authors on github). I have have built onto top of them to provide a easier to use class.
- https://github.com/csantala/XeroOAuth-PHP - provided the core of the class (Chris Santala)
- https://raw.github.com/XeroAPI/PHP-Xero - i used a few function from here, I basically wanted the __call($name, $arguments) function which was great! (Andy Smith, David Pitman, Ronan Quirke)

Todo
------------
. Test more
. Test partner side
. Store credentials in a database (maybe on for partner as public only does 30 mins at a time)
. instead of using $_SESSION to check if connect we can use database results which should be safer.


Requires
--------
PHP5+


Authors
--------
Myles Beardsmore (minor changes to the above code wrappers)


License
-------
License:
The MIT License

Copyright (c) 2007 Andy Smith (Oauth* classes)
Copyright (c) 2010 David Pitman (Xero class)
Copyright (c) 2012 Ronan Quirke, Xero (Xero class)
Copyright (c) 2012 Chris Santala, XeroOAuth (XeroXeroOAuth class) - CORE

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in
all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
THE SOFTWARE.


Usage
-----

SETUP
1. edit Xro_config.php
1.1 set $xro_app_type
1.2 set $oauth_callback to point to authorise.php (ie https://domain.com/authorise.php)
1.3. set consumer_key and shared_secret

XERO APP SETTINGS (https://api.xero.com/Application)
URL of your company or product: https://[domain]/[path]/authorise.php
OAuth callback domain: [domain]

Instantiate the Xero class with your credentials.
Then call any of the methods as outlined in the API.  Calling an API method name as a property is the same as calling that API method with no options. Calling the API method as a method with an array as the only input param with like calling the corresponding POST or PUT API method.  You can make more complex GET requests using up to four params on the method.  If you have read the xero api documentation, it should be clear.


### GET Request usage

Retrieving a result set from Xero involves identifying the endpoint you want to access, and optionally, setting some parameters to further filter the result set.
There are 5 possible parameters:

1. Record filter: The first parameter could be a boolean "false" or a unique resource identifier: document ID or unique number eg: $xero->Invoices('INV-2011', false, false, false, false);
2. Modified since: second parameter could be a date/time filter to only return data modified since a certain date/time eg: $xero->Invoices(false, "2012-05-11T00:00:00");
3. Custom filters: an array of filters, with array keys being filter fields (left of operand), and array values being the right of operand values.  The array value can be a string or an array(operand, value), or a boolean eg: $xero->Invoices(false, false, $filterArray);
4. Order by: set the ordering of the result set eg: $xero->Invoices('', '', '', 'Date', '');
5. Accept type: this only needs to be set if you want to retrieve a PDF version of a document, eg: $xero->Invoices($invoice_id, '', '', '', 'pdf');
		
Further details on filtering GET requests here: http://blog.xero.com/developer/api-overview/http-get/

### Example Usage:
<code>
<?php 
require('setup.php');
include('lib/Xero.php');
session_start();
if (isset($_GET['logoff'])) {
	session_unset();
}

$xero = new Xero; 

if($_GET['do'] == "create_contact"){ //the input format for creating a new contact see http://blog.xero.com/developer/api/contacts/ to understand more 
	$new_contact = array( 
		array( 
			"Name" => "MJ", 
			"FirstName" => "Michael", 
			"LastName" => "Jackson", 
			"Addresses" => array( 
				"Address" => array( 
					array( 
						"AddressType" => "POBOX", 
						"AddressLine1" => "PO Box 100", 
						"City" => "Someville", 
						"PostalCode" => "3890" ), 
					array( 
						"AddressType" => "STREET", 
						"AddressLine1" => "1 Some Street", 
						"City" => "Someville", 
						"PostalCode" => "3890" 
					) 
				) 
			) 
		) 
	);

    //create the contact
    $contact_result = $xero->Contacts( $new_contact );

    //echo the results back
    if ( is_object($contact_result) ) {
   	 	//use this to see the source code if the $format option is "xml"
   	 	echo htmlentities($contact_result->asXML());
    } else {
   	 	//use this to see the source code if the $format option is "json" or not specified
    	echo json_encode($contact_result);
    }
}


if($_GET['do'] == "create_invoice_and_payment"){		
    //the input format for creating a new invoice (or credit note) see http://blog.xero.com/developer/api/invoices/
    $invNumber = rand(1, 20);
    $new_invoice = array(
        array(
            "Type"=>"ACCREC",
            "Contact" => array(
                "Name" => "MJ"
            ),
            "InvoiceNumber" => "I00".$invNumber,
            "Reference" => "J0011",
            "Date" => date("Y-m-d"),
            "DueDate" => date("Y-m-d", strtotime("+30 days")),
            "Status" => "AUTHORISED",
            "LineAmountTypes" => "Exclusive",
            "LineItems"=> array(
                "LineItem" => array(
                    array(
                        "Description" => "Just another test invoice",
                        "Quantity" => "2.0000",
                        "UnitAmount" => "250.00",
                        "AccountCode" => "200"
                    )
                )
            )
        )
    );

    //the input format for creating a new payment see http://blog.xero.com/developer/api/payments/ to understand more
    $new_payment = array(
        array(
            "Invoice" => array(
                "InvoiceNumber" => "I00".$invNumber
            ),
            "Account" => array(
                "Code" => "200"
            ),
            "Date" => date("Y-m-d", strtotime("+5 days")),
            "Amount"=>"100.00",
        )
    );


    //raise an invoice
    $invoice_result = $xero->Invoices( $new_invoice );
    //put the payment to the agove invoice
    $payment_result = $xero->Payments( $new_payment );

    //echo the results back
    if ( is_object($invoice_result) ) {
   	 	//use this to see the source code if the $format option is "xml"
   	 	echo htmlentities($invoice_result->asXML());
    } else {
   	 	//use this to see the source code if the $format option is "json" or not specified
    	echo json_encode($invoice_result);
    }
    echo '<hr />';
    if ( is_object($payment_result) ) {
   	 	//use this to see the source code if the $format option is "xml"
   	 	echo htmlentities($payment_result->asXML());
    } else {
   	 	//use this to see the source code if the $format option is "json" or not specified
    	echo json_encode($payment_result);
    }

}
	
if($_GET['do'] == "pdf_an_invoice"){ // first get an invoice number to use 
	$org_invoices = $xero->Invoices; 
	$invoice_count = sizeof($org_invoices->Invoices->Invoice); $invoice_index = rand(0,$invoice_count); 
	$invoice_id = (string) $org_invoices->Invoices->Invoice[$invoice_index]->InvoiceID; 
	if(!$invoice_id) echo "You will need some invoices for this...";

	// now retrieve that and display the pdf
	$pdf_invoice = $xero->Invoices($invoice_id, '', '', '', 'pdf');
	header('Content-type: application/pdf'); header('Content-Disposition: inline; filename="the.pdf"'); 
	echo ($pdf_invoice);
}

// OTHER COOL STUFF
//get details of an account, with the name "Test Account"
//$result = $xero->Accounts(false, false, array("Name"=>"Test Account") );
//the params above correspond to the "Optional params for GET Accounts" on http://blog.xero.com/developer/api/accounts/

//to do a POST request, the first and only param must be a multidimensional array as shown above in $new_contact etc.

//get details of all accounts
//$all_accounts = $xero->Accounts;

//echo the results back
//if ( is_object($invoice_result) ) {
	 	//use this to see the source code if the $format option is "xml"
	 	//echo htmlentities($payment_result->asXML()) . "<hr />";
//} else {
	 //use this to see the source code if the $format option is "json" or not specified
	//echo json_encode($payment_result) . "<hr />";
//}
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<title>Xero Library</title>
	<link rel="stylesheet" type="text/css" href="<?=$web_root?>/css/style.css" />
</head>
<body>
	<div id="container">
		<h1>Xero API PHP</h1>
		<div id="body">
			<?php if (isset($_SESSION['access_token'])): ?>
				<p><a href="<?php echo $web_root?>/list_contacts.php">List Contacts</a><br />
					<a href="<?php echo $web_root?>/list_invoices.php">List Invoices</a></p>
				<p><em>Only create a client once as Xero might not be able to create the same one.</em><br />
					<a href="<?php echo $web_root?>/index.php?do=create_contact">Create Contact</a><br />
					<a href="<?php echo $web_root?>/index.php?do=create_invoice_and_payment">Create Invoice &amp; Payment</a><br />
					<a href="<?php echo $web_root?>/index.php?do=pdf_an_invoice">PDF an Invoice</a></p>
				<p><a href="<?php echo $web_root?>?logoff=true">Logoff Xero</a></p>
			<?php else: ?>
				<p><a href="<?php echo $web_root?>/authorise.php"><img src="<?php echo $web_root?>/connect_xero_button_blue.png" border="0"></a></p>
			<?php endif ?>
		</div>
	</div>
</body>
</html>
</code>