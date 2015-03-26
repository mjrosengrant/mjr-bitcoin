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
		add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ) );
		add_action( 'save_post', array( $this, 'save' ) );

        add_action( 'wp_enqueue_scripts', array( $this, 'register_plugin_scripts' ) );
		add_filter( 'the_password_form', array($this, 'print_qr' ) );
 		add_filter( 'the_content', array( $this, 'append_post_notification' ) );
        register_deactivation_hook( __FILE__, array($this, 'run_uninstall'));
	}

	function register_plugin_scripts(){

	}

	public function run_install(){
		$this->installer->install();
	}

	public function run_uninstall(){
		$this->installer->uninstall();
	}

	public function append_post_notification( $content ) {
		global $post;

		if($post->ID == "1"){ 
	    	return $content .  __( '<h3>1 BTC = $' . $this->bchain_delegate->btc_to_usd(1) . "</h3>", 'mjr_bc-locale');
	    }
 
	}

	public function print_qr($content){
		$my_bitcoin_address = "1EV6zsBQjX7ukR3f7NbUAJfSFQ71LfX2vf";
		$price_in_btc = 0006;

		$url = $this->bchain_delegate->generateQRUrl($my_bitcoin_address, 0.0006);

		$content =
		'
            <div class="blockchain stage-ready" style="text-align:center">
                To view this post please send <?php echo $price_in_btc ?> BTC to <br /> <b>'.$my_bitcoin_address.'</b> <br /> 
                <img style="margin:5px" id="qrsend" src="'.$url. '" alt=""/>
                Please note this is still under development, and sending money to this address will do nothing for you.
            </div>
		';
		
		//$content = $url;
		return $content;
	}


	/**
	 * Adds the meta box container.
	 */
	function add_meta_box( $post_type ) {
    $post_types = array('post', 'page');     //limit meta box to certain post types
        if ( in_array( $post_type, $post_types )) {
			add_meta_box(
				'mjr_bitcoin_metabox'
				,__( 'MJR Bitcoin Settings', 'myplugin_textdomain' )
				,array( $this, 'render_meta_box_content' )
				,$post_type
				,'advanced'
				,'high'
			);
        }
    }

/**
	 * Render Meta Box content.
	 *
	 * @param WP_Post $post The post object.
	 */
	public function render_meta_box_content( $post ) {
	
		// Add an nonce field so we can check for it later.
		wp_nonce_field( 'myplugin_inner_custom_box', 'myplugin_inner_custom_box_nonce' );

		// Use get_post_meta to retrieve an existing value from the database.
		$premium = get_post_meta( $post->ID, '_premium', true );
		$amt_in_usd = get_post_meta( $post->ID, '_amt_in_usd', true );


		// Display the form, using the current value.
		echo '<label for="premium_checkbox">';
		_e( 'Make This Post Premium', 'myplugin_textdomain' );
		echo '</label> ';
		echo '<input type="checkbox" id="premium_checkbox" name="premium_checkbox"';
        echo ' value="' . esc_attr( $premium ) . '" size="25" /><br>';
        
        echo '<label for="amt_in_usd_tb">';
        _e('How much should this post cost? (USD)', 'myplugin_textdomain');
		echo '<input type="text" id="amt_in_usd_tb" name="amt_in_usd_tb "';
		echo 'value ="' . esc_attr( $amt_in_usd ) . '" />';


	}


	/**
	* Save the meta when the post is saved.
	*
	* @param int $post_id The ID of the post being saved.
	*/
	public function save( $post_id ) {
	
		/*
		 * We need to verify this came from the our screen and with proper authorization,
		 * because save_post can be triggered at other times.
		 */

		// Check if our nonce is set.
		if ( ! isset( $_POST['myplugin_inner_custom_box_nonce'] ) )
			return $post_id;

		$nonce = $_POST['myplugin_inner_custom_box_nonce'];

		// Verify that the nonce is valid.
		if ( ! wp_verify_nonce( $nonce, 'myplugin_inner_custom_box' ) )
			return $post_id;

		// If this is an autosave, our form has not been submitted,
                //     so we don't want to do anything.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) 
			return $post_id;

		// Check the user's permissions.
		if ( 'page' == $_POST['post_type'] ) {

			if ( ! current_user_can( 'edit_page', $post_id ) )
				return $post_id;
	
		} else {

			if ( ! current_user_can( 'edit_post', $post_id ) )
				return $post_id;
		}

		/* OK, its safe for us to save the data now. */

		// Sanitize the user input.
		$mydata = sanitize_text_field( $_POST['myplugin_new_field'] );

		// Update the meta field.
		update_post_meta( $post_id, '_my_meta_value_key', $mydata );
	}

}
$mjr_bc = Mjr_Bitcoin::get_instance();



?>
