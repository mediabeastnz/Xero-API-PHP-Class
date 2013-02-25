<?php
	include('setup.php');
	include('lib/Xero.php');

	$xero = new Xero; 
	
	$contactsData = $xero->Contacts;
	$contacts = $contactsData->Contacts->Contact;
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<link rel="stylesheet" type="text/css" href="<?php echo $web_root?>/css/style.css" />
	<title>Xero Library</title>
</head>
<body>
	<div id="container">
		<h1>Xero Contacts</h1>
		<p style="margin:15px"><a href="<?php echo $web_root?>">home</a></p>
		<hr>
		<div id="body">	
			<p>
				<table cellpadding="2" width="100%">
					<thead>
						<tr>
							<th>Name</th>
							<th>Email</th>
							<th>Type</th>
							<th>Status</th>
						</tr>
					</thead>
					<?php foreach($contacts as $contact): ?>
					<tr>
					 	<td><?php echo $contact->Name ?><br />
					 		<small><?php echo $contact->FirstName ?>&nbsp;<?php echo $contact->LastName ?></small>
					 	</td>
					 	<td><?php echo $contact->EmailAddress ?></td>
					 	<td>
					 		<?php if($contact->IsCustomer){ echo 'Customer'; }elseif($contact->IsSupplier){ echo 'Supplier'; }else{ echo 'NA'; } ?>
					 	</td>
					 	<td><?php echo $contact->ContactStatus ?></td>
					</tr>
					<?php endforeach ?>
				</table>
			</p>	
		</div>
	</div>
</body>
</html>