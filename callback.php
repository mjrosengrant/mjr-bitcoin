
<?php global $wpdb;

	if (!$result) {
	    die(__LINE__ . ' Invalid query: ' . $wpdb->last_error);
	}

	$invoice_id = $_GET['invoice_id'];
	$transaction_hash = $_GET['transaction_hash'];
	$value_in_btc = $_GET['value'] / 100000000;

	//Commented out to test, uncomment when live
	/*if ($_GET['test'] == true) {
	  echo 'Ignoring Test Callback';
	  return;
	}*/


	if ($_GET['address'] != $my_bitcoin_address) {
	    echo 'Incorrect Receiving Address';
	  return;
	}

	if ($_GET['secret'] != $secret) {
	  echo 'Invalid Secret';
	  return;
	}

	if ($_GET['confirmations'] >= 4) {
	  	//Add the invoice to the database
		$result = $wpdb->query(
			"REPLACE INTO invoice_payments (invoice_id, transaction_hash, value) 
			VALUES ($invoice_id, '$transaction_hash', $value_in_btc)");
	  	//Delete from pending
	  	$wpdb->query("delete from pending_invoice_payments where invoice_id = $invoice_id limit 1");

	  	if($result) {
		   	echo "*ok*";
	  	}
	} 
	else {
	   	//Waiting for confirmations
	   	//create a pending payment entry
			$wpdb->query("replace INTO pending_invoice_payments (invoice_id, transaction_hash, value) 
	   	values($invoice_id, '$transaction_hash', $value_in_btc)");

	   	echo "Waiting for confirmations";
	}



	}

?>