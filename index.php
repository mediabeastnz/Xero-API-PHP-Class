<?php
// run configuration file
include("./config/application.php");

?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<title>Xero API Class</title>
	<link rel="stylesheet" type="text/css" href="./css/style.css" />
</head>
<body>
	<div id="container">
		<div id="body">
			<?php
			// xero logoff error messages.		
			if($_GET['xero_logoff']){
				if($_GET['xero_logoff'] == 1){
					echo 'Xero has now been disconnected from Trackthat.';
				}else{
					echo 'There was an issues disconnecting Xero.';
				}
			}
			?>

			<!-- this form should update the xer_api table with the defualts -->
			<form action="#" method="post">
				<h2>Xero API Class<small>- For partner and public apps</small></h2>
				<?php if(XERO_CONNECTION_STATUS == "Connected"){ ?>
				<?php
					$xero_result = $xero->Organisation;
					$xero_api_response = $xero->api_response($xero_result);
					if($xero_api_response != 1){
						$GLOBALS['xero_response'] = $xero_api_response;
						$GLOBALS['xero_response_type'] = 'err';
					}else{
						$organisations = $xero_result->Organisations->Organisation;
					}				
										
					$xero_result = $xero->Accounts;
					$xero_api_response = $xero->api_response($xero_result);
					if($xero_api_response != 1){
						$GLOBALS['xero_response'] = $xero_api_response;
						$GLOBALS['xero_response_type'] = 'err';
					}else{
						$chart_of_accounts = $xero_result->Accounts->Account;
					}		
				?>
				<h4>Connection Status: Connected</h4>
					<a href="preferences.php?a=xero_logoff" title="Disconnecting will stop all data being transferred to and from Xero. This could cause synchronisation issues.">Disconnect Xero</a><br />
					<?php
					$xxx = XERO_CONNECTION_START+1800;
					echo '<p style="padding-top:5px;"><small>Organisation connected: <strong>'.$organisations->Name.'</strong><br />';
					echo 'Time until next renewal:  <strong>'.round(abs($xxx - strtotime("now")) / 60,0).' minutes</strong> ( '.date("H:i a", strtotime('+30 minutes', XERO_CONNECTION_START)).' )</small></p>';
					?>
				<label for="xero_sales_account">Sales Account</label>
			    	<select name="xero_sales_account" id="xero_sales_account">
			    		<option>Select a Sales Account</option>
				    <?php
				    	foreach($chart_of_accounts as $xero_account){
				    		if(XERO_SALES_ACCOUNT == $xero_account->Code){
					    		echo '<option value="'.XERO_SALES_ACCOUNT.'" selected="selected">'.$xero_account->Code.' - '.$xero_account->Name.' ('.$xero_account->Type.')</option>';	
				    		}
					    	echo '<option value="'.$xero_account->Code.'">'.$xero_account->Code.' - '.$xero_account->Name.' ('.$xero_account->Type.')</option>';
				    	}
				    ?>
			    	</select><br />
				<label for="xero_purchases_account">Purchases Account</label>
			    	<select name="xero_purchases_account" id="xero_purchases_account">
			    		<option>Select a Purchases Account</option>
				    <?php
				    	foreach($chart_of_accounts as $xero_account){
				    		if(XERO_PURCHASES_ACCOUNT == $xero_account->Code){
					    		echo '<option value="'.XERO_PURCHASES_ACCOUNT.'" selected="selected">'.$xero_account->Code.' - '.$xero_account->Name.' ('.$xero_account->Type.')</option>';	
				    		}
					    	echo '<option value="'.$xero_account->Code.'">'.$xero_account->Code.' - '.$xero_account->Name.' ('.$xero_account->Type.')</option>';
				    	}
				    ?>
			    	</select>
					<button type="submit" disabled="disabled">Save Account settings</button>
					<br /><br />
				<?php }else{ ?>
				<br />
				<h4>Connection Status: Disconnected</h4>
					<a href="./api/xero/authorise.php"><img src="./api/xero/connect_xero_button_blue.png" title="Connect to Xero" alt="Connect to Xero" /></a>
				<?php } ?>
			</form>

		</div>
	</div>
</body>
</html>