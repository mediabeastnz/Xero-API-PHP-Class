<?php
/********************************************* Xero Response() *********************************************/
function xero_response() {
	if (isset($GLOBALS['xero_response'])) {
		if($GLOBALS['xero_response_type'] == "err") {
			$responsemessage  = '';
		}elseif($GLOBALS['response_type'] == "info") {
			$responsemessage  = '<div class="alert alert">';
		}else{
			$responsemessage  = '<div class="alert alert-success">';
		}
		$responsemessage  .=  $GLOBALS['xero_response'].'</div>';
		return $responsemessage;
	}	
}


/********************************************* Apply stripslashes recursively *********************************************/
function stripslashes_deep($value) {
   if (get_magic_quotes_gpc()) {
	   $value = is_array($value) ?
	   array_map('stripslashes_deep', $value) :
	   stripslashes($value);
	}
   return $value;
}


/********************************************* Qoute variables for safe insertion *********************************************/
function quote_smart($value) {
   // Stripslashes if we need to
   $value = stripslashes_deep($value);
   
   if (is_array($value)) {
      $str = "";
	  $count = 0;
      foreach($value as $value_2) {
         if ($count >= 1) $str .= ', ';
         $str .= $value_2;
		 $count = $count + 1;
	  }
	  $value = $str;
	  unset($str);
   }
   // Quote it if it's not an integer
   if (!is_numeric($value)) {
       $value = "'" . mysql_real_escape_string($value) . "'";
   }
   return $value;
}
?>