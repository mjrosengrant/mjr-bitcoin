<?php
/*
Plugin Name: MJR Bitcoin
Plugin URI: http://gordon.knoppe.net/articles/category/attach-files/
Description: Creates a bitcoin paywall, and records transactions
Author: Mike Rosengrant
Author URI: http://mjrosengrant.com
*/

//I began this plugin based on several different open source projects, Including
//http://code.tutsplus.com/tutorials/two-ways-to-develop-wordpress-plugins-object-oriented-programming--wp-27716
// Add link to bitcoin demo here

require_once "mjr_bc_installer.class.php";
require_once "blockhain_delegate.class.php";

class Mjr_Bitcoin{

	//Singleton Object
	private static $instance = null;

 	//Delegate for Blockchain API calls
 	private $bchain_delegate;
 	private $installer;

	private $blockchain_root = "https://blockchain.info/"; 
	private $mysite_root = "http://mjrosengrant.com/";
	private $secret = "DONTTELLTHEMYOURSECRET";
	private $my_bitcoin_address = "1EV6zsBQjX7ukR3f7NbUAJfSFQ71LfX2vf";

	//Stores page id as index and price in USD as the value
	private $premium_pages = array();

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

        register_activation_hook( __FILE__, array($this, 'run_install'));
	    load_plugin_textdomain( 'mjr_bc', false, dirname( plugin_basename( __FILE__ ) ) . '/lang' );
	    add_action( 'save_post', 'myplugin_save_meta_box_data' );
		add_action( 'add_meta_boxes', 'myplugin_add_meta_box' );
        add_action( 'wp_enqueue_scripts', array( $this, 'register_plugin_scripts' ) );
		add_filter( 'the_password_form', array($this, 'print_qr_code' ) );
 		add_filter( 'the_content', array( $this, 'append_post_notification' ) );
        //register_deactivation_hook( __FILE__, array($this, 'drop_invoice_tables'));
        register_deactivation_hook( __FILE__, array($this, 'run_uninstall'));
	}

	public function run_install(){
		$this->installer->install();
	}

	public function run_uninstall(){
		$this->installer->uninstall();
	}
	 
	public function register_plugin_scripts() {
	 
	   /* wp_register_script( 'mjr_bc', plugins_url( 'mjr_bc/receive_payments/callback.php' ) );
	    wp_enqueue_script( 'mjr_bc' );

	    wp_register_script( 'mjr_bc', plugins_url( 'mjr_bc/receive_payments/include.php' ) );
	    wp_enqueue_script( 'mjr_bc' );

	    wp_register_script( 'mjr_bc', plugins_url( 'mjr_bc/receive_payments/order_status.php' ) );
	    wp_enqueue_script( 'mjr_bc' );*/
	 
	}

	public function append_post_notification( $content ) {
 
	    $notification = __( '<h1>1 BTC = $' . $this->bchain_delegate->btc_to_usd(1) . "</h1>", 'mjr_bc-locale');
	    return $content . $notification;
 
	}

	public function print_qr_code($content){

		/*$content = '<div class="blockchain stage-ready" style="text-align:center">
                Please send' . $price_in_btc . ' BTC to <br /> <b>' . [[address]] . '</b> <br /> 
                <img style="margin:5px" id="qrsend" src=" ' . $blockchain_root . 'qr?data=bitcoin:' . 
                $my_bitcoin_address .'%3Famount==' . $price_in_btc.'%26label=Pay-Demo&size=125" alt=""/>
            </div>';*/

 		$content = $content . " Print qr code function is working!";

 		return $content;

	}

	


	/**
	 * Adds a box to the main column on the Post and Page edit screens.
	 */
	function myplugin_add_meta_box() {

		$screens = array( 'post', 'page' );

		foreach ( $screens as $screen ) {
			add_meta_box(
				'myplugin_sectionid',
				__( 'MJR Bitcoin Settings', 'myplugin_textdomain' ),
				'myplugin_meta_box_callback',
				$screen
			);
		}
	}

	/**
	 * Prints the box content.
	 * 
	 * @param WP_Post $post The object for the current post/page.
	 */
	function myplugin_meta_box_callback( $post ) {

		// Add an nonce field so we can check for it later.
		wp_nonce_field( 'myplugin_meta_box', 'myplugin_meta_box_nonce' );

		/*
		 * Use get_post_meta() to retrieve an existing value
		 * from the database and use the value for the form.
		 */
		$value = get_post_meta( $post->ID, '_my_meta_value_key', true );

		echo '<label for="myplugin_new_field">';
		_e( 'Make this post Premium', 'myplugin_textdomain' );
		echo '</label> ';
		echo '<input type="checkbox" id="myplugin_new_field" name="myplugin_new_field" value="' . esc_attr( $value ) . '" size="25" />';
	}

	/**
	 * When the post is saved, saves our custom data.
	 *
	 * @param int $post_id The ID of the post being saved.
	 */
	function myplugin_save_meta_box_data( $post_id ) {

		/*
		 * We need to verify this came from our screen and with proper authorization,
		 * because the save_post action can be triggered at other times.
		 */

		// Check if our nonce is set.
		if ( ! isset( $_POST['myplugin_meta_box_nonce'] ) ) {
			return;
		}

		// Verify that the nonce is valid.
		if ( ! wp_verify_nonce( $_POST['myplugin_meta_box_nonce'], 'myplugin_meta_box' ) ) {
			return;
		}

		// If this is an autosave, our form has not been submitted, so we don't want to do anything.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		// Check the user's permissions.
		if ( isset( $_POST['post_type'] ) && 'page' == $_POST['post_type'] ) {

			if ( ! current_user_can( 'edit_page', $post_id ) ) {
				return;
			}

		} else {

			if ( ! current_user_can( 'edit_post', $post_id ) ) {
				return;
			}
		}

		/* OK, it's safe for us to save the data now. */
		
		// Make sure that it is set.
		if ( ! isset( $_POST['myplugin_new_field'] ) ) {
			return;
		}

		// Sanitize user input.
		$my_data = sanitize_text_field( $_POST['myplugin_new_field'] );

		// Update the meta field in the database.
		update_post_meta( $post_id, '_my_meta_value_key', $my_data );
	}


}

$mjr_bc = Mjr_Bitcoin::get_instance();



?>
