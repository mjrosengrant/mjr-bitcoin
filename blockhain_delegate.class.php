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



}



?>