<?php
	require('setup.php');
	
	// connect to database and get or store access tokens
	require_once("../../config/database.php");
	require_once("../../config/defines.php");
	require_once("../../functions/functions.common.php");

	// connected...
	
	require('lib/Xero.php');

	$xero = new Xero; 

	$authorization = $xero->oauth();
	
	if (isset($authorization)) {
		header('Location: ' . $protocol . $_SERVER['HTTP_HOST']."/index.php"); // use CALLBACK_URL
	}
?>