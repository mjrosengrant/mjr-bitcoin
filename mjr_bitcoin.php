<?php
/*
Plugin Name: MJR Bitcoin
Plugin URI: http://gordon.knoppe.net/articles/category/attach-files/
Description: Creates a bitcoin paywall, and records transactions
Author: Mike Rosengrant
Author URI: http://blog.mjrosengrant.com
*/

//I began this plugin based on several different open source projects, Including
//http://code.tutsplus.com/tutorials/two-ways-to-develop-wordpress-plugins-object-oriented-programming--wp-27716
// Add link to bitcoin demo here

require_once "mjr_bc_installer.class.php";
require_once "blockhain_delegate.class.php";
require_once "metabox_builder.class.php";

class Mjr_Bitcoin{

	//Singleton Object
	private static $instance = null;

 	//Delegate for Blockchain API calls
 	private $bchain_delegate;
 	private $installer;
 	private $menu_builder;

	//Stores page id as index and price in USD as the value
	//private $premium_pages = array();

	//Instantiates the Singleton Object
	public static function get_instance() {
 
        if ( null == self::$instance ) {
            self::$instance = new self;
        }
        return self::$instance;
 
    } // end get_instance;


	private function __construct(){
        
        $this->bchain_delegate = new Blockchain_Delegate();
        $this->installer = new Mjr_Bc_Installer();
        $this->menu_builder = new Metabox_Builder();

        $this->premium_pages = array();

        register_activation_hook( __FILE__, array($this, 'run_install'));
	    load_plugin_textdomain( 'mjr_bc', false, dirname( plugin_basename( __FILE__ ) ) . '/lang' );

		add_filter( 'the_password_form', array($this, 'print_qr' ) );
 		add_filter( 'the_content', array( $this, 'append_post_notification' ) );
        register_deactivation_hook( __FILE__, array($this, 'run_uninstall'));

        wp_register_script( 'callback.php', plugins_url() . "/mjr_bitcoin/callback.php");
	}

	public function run_install(){
		$this->installer->install();
	}

	public function run_uninstall(){
		$this->installer->uninstall();
	}

	public function append_post_notification( $content ) {
		global $post;
		//1196 is the post_id for test outputs in wp at work
		//31 is bitcoin.mjrosengrant.com test post
		if($post->ID == "31"){ 
			$my_bitcoin_address = "1EV6zsBQjX7ukR3f7NbUAJfSFQ71LfX2vf";
			$callback_url = plugins_url() . "/mjr_bitcoin/callback.php";

			$r_addr = $this->bchain_delegate->generateReceivingAddress($my_bitcoin_address, $callback_url);
			
	    	$out = $content .  __( '<h3>1 BTC = $' . $this->bchain_delegate->btc_to_usd(1) . "</h3>", 'mjr_bc-locale');
	    	$out .= $callback_url;
	    	$out = $out . var_dump($r_addr);
	 		return $out;
 		}

 		return $content;
	}

	public function print_qr($content){
		$my_bitcoin_address = "1EV6zsBQjX7ukR3f7NbUAJfSFQ71LfX2vf";
		$callback_url = plugins_url() . "/mjr_bitcoin/callback.php";
		$testCallback = 'http://requestb.in/1h0kzdn1';

		global $post;

		$receive_addr_gen = json_decode($this->bchain_delegate->generateReceivingAddress($my_bitcoin_address, $callback_url),true);
		$receive_addr = $receive_addr_gen['input_address'];

		$usd_price = get_post_meta( $post->ID, 'price_in_usd', true );
		$btc_price = $this->bchain_delegate->usd_to_btc($usd_price);

		$url = $this->bchain_delegate->generateQRUrl($receive_addr, $btc_price);
		$content =
		'
            <div style="text-align:center">
                To view this post please send ' . $btc_price . ' BTC ($' . number_format($usd_price,2) .')
                to <br /> <b>' .$receive_addr. '</b> <br /> 
                <img style="margin:5px" id="qrsend" src="'.$url. '" alt=""/>
                Please note this is still under development, and sending money to this address will do nothing for you.
            </div>
		';
		return $content;
	}


	

}
$mjr_bc = Mjr_Bitcoin::get_instance();

?>
