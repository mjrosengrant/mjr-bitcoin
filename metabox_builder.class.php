<?php
	
class Metabox_Builder{
	
	function __construct(){
		add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ) );
		add_action( 'save_post', array( $this, 'save_meta_box_data' ) );
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



?>