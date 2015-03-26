<?php

class Blockchain_Delegate{
	public $test;
	public $blockchain_root = "https://blockchain.info/"; 

	
	public function __construct(){
		$this->test = "Blockchain Constructor Run";
	}


	public function usd_to_btc($price_in_usd){
		$url =  $this->blockchain_root . "tobtc?currency=USD&value=" . $price_in_usd;
		$price_in_btc = file_get_contents($url);
		return $price_in_btc;
	}

	public function btc_to_usd($btc_amt){
		
		//Find out how much 1 dollar is in bitcoin
		$one_usd_in_btc = $this->usd_to_btc(1);

		//Multiply by the reciprocal to see one bitcoin = x dollars
		$one_btc_in_usd = 1/$one_usd_in_btc;

		//Multiply one btc by the btc_amt
		return $one_btc_in_usd * $btc_amt;

	}


	public function generateReceivingAddress($callback_url){
		$results = file_get_contents(
			'https://blockchain.info/api/receive?method=create&address=$receiving_address&callback=$callback_url');
		return $results;
	}

	public function generateQRUrl($my_bitcoin_address, $price_in_btc){
    	//Url to get QR code. Not sure of best way to return it yet
    	return $this->blockchain_root . "qr?data=bitcoin:".$my_bitcoin_address ."%3Famount=".$price_in_btc."%26label=Pay-Demo&size=125";
	}







}



?>