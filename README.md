PHP-Xero PHP Wrapper (Partner)
====================

Introduction
------------
A class for interacting with the Xero Partner application API. 
More documentation for Xero can be found at http://blog.xero.com/developer/api-overview/  
It is suggested you become familiar with the API before using this class, otherwise it may not make much sense to you - http://blog.xero.com/developer/api/

I have gathered a bunch of tools from a few people so thank you to all of them (See Authors on github). I have have built onto top of them to provide a easier to use class.

I have included a entire working structure including .sql files for quick implementation and testing.
I have made this public as I found it quite difficult to find any information on using the Xero API (partner). Enjoy!


Issues
-------
This hasn't been fully tested and will require testing before it can be used on a live app.
Leave any issues if you find any, always good to improve.


Requires
--------
PHP5+


Authors
--------
Myles Beardsmore (changes to the above code wrappers and implementation of all classes)


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




Setup
-------

Before this class will work you will have need to have registered a app with Xero and create all the neccessary certificates.
See Xro_config.php for all required settings e.g. cert files, callback url and more.

1. edit Xro_config.php
1.1 set $xro_app_type
1.2 set $oauth_callback to point to authorise.php (ie https://domain.com/authorise.php)
1.3. set consumer_key and shared_secret

XERO APP SETTINGS (https://api.xero.com/Application)
URL of your app: https://[domain]/[path]/authorise.php
OAuth callback domain: [domain]

DATABASE
1. make a new database called "xero"
2. run xero.sql - to import tables i've created for testing.
3. make sure there is 1 row in "xero_api" table with the id of 1 - used to update credentials 


Usage
--------

-- read test.php for examples of usage and testing.

### GET Request usage
Retrieving a result set from Xero involves identifying the endpoint you want to access, and optionally, setting some parameters to further filter the result set.
There are 5 possible parameters:

1. Record filter: The first parameter could be a boolean "false" or a unique resource identifier: document ID or unique number eg: $xero->Invoices('INV-2011', false, false, false, false);
2. Modified since: second parameter could be a date/time filter to only return data modified since a certain date/time eg: $xero->Invoices(false, "2012-05-11T00:00:00");
3. Custom filters: an array of filters, with array keys being filter fields (left of operand), and array values being the right of operand values.  The array value can be a string or an array(operand, value), or a boolean eg: $xero->Invoices(false, false, $filterArray);
4. Order by: set the ordering of the result set eg: $xero->Invoices('', '', '', 'Date', '');
5. Accept type: this only needs to be set if you want to retrieve a PDF version of a document, eg: $xero->Invoices($invoice_id, '', '', '', 'pdf');


// @Author Myles Beardsmore