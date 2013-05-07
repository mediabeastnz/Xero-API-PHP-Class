<?php
/******************************* Database Connections **********************************/
define("DBHOST", "localhost");
define("DBUSER", "root");
define("DBPASS", "root");
define("DBNAME", "xero");

mysql_connect(DBHOST,DBUSER,DBPASS) or die('[Error: Cannot connect to MySQL server]');
mysql_select_db(DBNAME) or die('[Error: Cannot find the database: "'.DBNAME.'"]');
?>