<?php
	include('setup.php');
	include('lib/Xero.php');

	$xero = new Xero; 
	
	$invoicesData = $xero->Invoices;
	$invoices = $invoicesData->Invoices->Invoice;

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
		<h1>Xero Invoices</h1>
		<p style="margin:15px"><a href="<?php echo $web_root?>">home</a></p>
		<hr>
		<div id="body">	
			<p>
				<table cellpadding="2" width="100%">
					<thead>
						<tr>
							<th>Invoice Number</th>
							<th>Name</th>
							<th>Reference</th>
							<th>Amount Due</th>
							<th>Amount Paid</th>
						</tr>
					</thead>
					<?php foreach($invoices as $invoice): ?>
					<tr>
					 	<td><?php echo $invoice->InvoiceNumber ?></td>
					 	<td><?php echo $invoice->Contact->Name ?></td>
					 	<td><?php echo $invoice->Reference ?></td>
					 	<td><?php echo $invoice->AmountDue ?></td>
					 	<td><?php echo $invoice->AmountPaid ?></td>
					</tr>
					<?php endforeach ?>
				</table>
			</p>	
		</div>
	</div>
</body>
</html>