
<?php 

	global $wpdb;

	$blockchain_root = "https://blockchain.info/"; 
	$mysite_root = "http://mjrosengrant.com/";
	$secret = "^y69=>>l2V+65gsfGFgfsfGfgsdFgDFgsfgsdfgdf8oddcEz7]q08G|xu4R5";
	$my_bitcoin_address = "1EV6zsBQjX7ukR3f7NbUAJfSFQ71LfX2vf";

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
			"REPLACE INTO wp_mjr_bc_invoice_payments (invoice_id, transaction_hash, value) 
			VALUES ($invoice_id, '$transaction_hash', $value_in_btc)");
	  	//Delete from pending
	  	$wpdb->query("delete from wp_mjr_bc_pending_invoice_payments where wp_mjr_bc_invoice_id = $invoice_id limit 1");

	  	if($result) {
		   	echo "*ok*";
	  	}
	} 
	else {
   		//Waiting for confirmations
	   	//create a pending payment entry
		$wpdb->query("replace INTO wp_mjr_bc_pending_invoice_payments (invoice_id, transaction_hash, value) 
	   	values($invoice_id, '$transaction_hash', $value_in_btc)");

	   	echo "Waiting for confirmations";
	}


?>