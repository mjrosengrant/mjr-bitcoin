<?php
class Blockchain_Delegate{
	public $test = null;
	

	public function __construct(){
		$test = "Blockchain_Delegate Instantiated";

	}

	public function usd_to_btc($price_in_usd){
		$price_in_btc = file_get_contents($blockchain_root . "tobtc?currency=USD&value=" . $price_in_usd);
		return $price_in_btc;
	}


}



?>