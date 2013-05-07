<?php
/* ENVIRONMENT *********************************************/
define('ENVIRONMENT', 'production');

/* ERROR_REPORTING *********************************************/
if (defined('ENVIRONMENT')){
	switch (ENVIRONMENT){
		case 'development':
			error_reporting(E_ALL);
			ini_set('display_errors', 1);
		break;
	
		case 'production':
			error_reporting(0);
		break;

		default:
			exit('The application environment is not set correctly.');
	}
}


/* SESSIONS *********************************************/
session_cache_limiter('');
session_start();


/* DATABASE  *********************************************/
require_once("config/database.php");

/* DEFINES  *********************************************/
require_once("config/defines.php");

/* FUNCTIONS  *********************************************/
require_once("functions/functions.common.php");

/* XERO *********************************************/
require('api/xero/setup.php');
include('api/xero/lib/Xero.php');

/* Define Class Instances *********************************************/
$xero = new Xero; 
?>