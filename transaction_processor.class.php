<?php

class TransactionProcessor(){

	private $blockchain_root = "https://blockchain.info/"; 
	private $mysite_root = "http://mjrosengrant.com/";
	private $secret = "^y69=>>l2V+8oddcEz7]q08G|xu4R5";
	private $my_bitcoin_address = "1EV6zsBQjX7ukR3f7NbUAJfSFQ71LfX2vf";
	
	//invoice_id must be posted to this page
	$invoice_id = intval($_GET['invoice_id']);
	$product_url = '';
	$price_in_usd = 0;
	$price_in_btc = 0;
	$amount_paid_btc = 0;
	$amount_pending_btc = 0;


	//Pulls accountinfo from DB
	function setAccountInfo(){
		$invoice_id = intval($_GET['invoice_id']);

		global $wpdb;

		//Get the invoice from the database
		$result = dbDelta("SELECT price_in_usd, product_url, price_in_btc FROM invoices WHERE invoice_id = $invoice_id",);

		if (!$result) {
		    die(__LINE__ . ' Invalid query: ' . $wpdb->last_error);
		}

		while($row = mysql_fetch_array($result)) {
			$product_url = $row['product_url'];  
			$price_in_usd = $row['price_in_usd'];
			$price_in_btc = $row['price_in_btc'];  
		}
	}


	//Need to convert DB calls into the Wordpress format
	function getPendingAmountPaid(){

		$amount_pending_btc = 0;

		//find the pending amount paid
		$result = mysql_query("select value from pending_invoice_payments where invoice_id = $invoice_id");
		$amount_pending_btc = "";
		while($row = mysql_fetch_array($result)){
	 		$amount_pending_btc += $row['value'];   
		}

		return $amount_pending_btc;

	}


	//Need to convert DB calls into the Wordpress format
	function getConfirmedAmountPaid(){
		//find the confirmed amount paid
		$result = dbDelta("SELECT value FROM invoice_payments WHERE invoice_id = $invoice_id");
		         
		while($row = mysql_fetch_array($result)){
			$amount_paid_btc += $row['value']; 
		}

		return $amount_paid_btc;

	}


	//Doesn't really do anything right now. I have to figure out the best way to return the data.
	//It just depends on how I end up using it. 
	function getPaymentStatus(){
		
		if ($amount_paid_btc  == 0 && $amount_pending_btc == 0) 
			echo 'Payment not received';
		
		else if ($amount_paid_btc < $price_in_btc)  	
			echo 'Waiting for Payment Confirmation: <a href="./order_status.php?invoice_id= echo $invoice_id ">Refresh</a>';
		
		else
			echo 'Thank You for your purchase';

	}



	//callback is where information is transaction is verified
	function callback(){
		global $wpdb;

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




}






?>