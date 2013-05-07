<?php
	// simple script to set links instead of using php self.
	$protocol = "http://";
	if (isset($_SERVER['HTTPS'])) {
	   $protocol = "https://";
	}
	$web_root = $protocol . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']);
	
	// just want to make sure the time zone is correct for the api log
	date_default_timezone_set('Pacific/Auckland');
	
?>