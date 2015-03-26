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
        $this->premium_pages = array();

        register_activation_hook( __FILE__, array($this, 'run_install'));
	    load_plugin_textdomain( 'mjr_bc', false, dirname( plugin_basename( __FILE__ ) ) . '/lang' );
		add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ) );
		add_action( 'save_post', array( $this, 'save_meta_box_data' ) );
		add_filter( 'the_password_form', array($this, 'print_qr' ) );
 		add_filter( 'the_content', array( $this, 'append_post_notification' ) );
        register_deactivation_hook( __FILE__, array($this, 'run_uninstall'));
	}

	public function run_install(){
		$this->installer->install();
	}

	public function run_uninstall(){
		$this->installer->uninstall();
	}

	public function append_post_notification( $content ) {
		global $post;
		//1196 is the post_id for test outputs
		if($post->ID == "1196"){ 
	    	$out =  $content .  __( '<h3>1 BTC = $' . $this->bchain_delegate->btc_to_usd(1) . "</h3>", 'mjr_bc-locale');

	 		return $out;
 		}

 		return $content;
	}

	public function print_qr($content){
		$my_bitcoin_address = "1EV6zsBQjX7ukR3f7NbUAJfSFQ71LfX2vf";
		$price_in_btc = 0006;
		
		global $post;
		$usd_price = get_post_meta( $post->ID, 'price_in_usd', true );
		$btc_price = $this->bchain_delegate->usd_to_btc($usd_price);

		setlocale(LC_MONETARY, 'en_US');

		$url = $this->bchain_delegate->generateQRUrl($my_bitcoin_address, 0.0006);
		$content =
		'
            <div class="blockchain stage-ready" style="text-align:center">
                To view this post please send ' . $btc_price . ' BTC ($' . number_format($usd_price,2) .')
                to <br /> <b>'.$my_bitcoin_address.'</b> <br /> 
                <img style="margin:5px" id="qrsend" src="'.$url. '" alt=""/>
                Please note this is still under development, and sending money to this address will do nothing for you.
            </div>
		';
		//$content = "Is this working?";
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
		$isPremiumCur = get_post_meta( $post->ID, 'isPremium', true );
		$price_in_usdCur = get_post_meta( $post->ID, 'price_in_usd', true );

		$boxChecked = "";
		if($isPremiumCur == 1){
			$boxChecked = "checked";
		}

		// Display the form, using the current value.
		echo '<label for="premium_checkbox">';
		_e( 'Make This Post Premium', 'myplugin_textdomain' );
		echo '</label> ';
		echo '<input type="checkbox" id="premium_checkbox" name="premium_checkbox"';
        echo ' value=1 size="25" '. $boxChecked . '/><br>';
        
        echo '<label for="price_in_usd">';
        _e('How much should this post cost? (USD)', 'myplugin_textdomain');
        echo '</label>';
		echo '<input type="text" id="price_in_usd" name="price_in_usd"';
		echo 'value ="' . esc_attr( $price_in_usdCur ) . '" />';

	}


	/**
	* Save the meta when the post is saved.
	*
	* @param int $post_id The ID of the post being saved.
	*/
	public function save_meta_box_data( $post_id ) {
	
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
		
		$isPremium;
		$price_in_usd;
		// Sanitize the user input.
		if(isset($_POST['premium_checkbox']) && $_POST['premium_checkbox'] == 1){ 
			$isPremium = sanitize_text_field($_POST['premium_checkbox']);
		}

		if(isset($_POST['price_in_usd']) && $_POST['price_in_usd'] != ""){ 
			$price_in_usd = sanitize_text_field( $_POST['price_in_usd'] );
		}

		// Update the meta field.
		update_post_meta( $post_id, 'isPremium', $isPremium );
		update_post_meta( $post_id, 'price_in_usd', $price_in_usd);

	}

}
$mjr_bc = Mjr_Bitcoin::get_instance();



?>
