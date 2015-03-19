<?php
/*
Plugin Name: MJR Bitcoin
Plugin URI: http://gordon.knoppe.net/articles/category/attach-files/
Description: Creates a bitcoin paywall, and records transactions
Author: Mike Rosengrant
Author URI: http://mjrosengrant.com
*/

//include 'mjr_bitcoin_widget.php';

//I began this plugin based on several different open source projects, Including
// http://gordon.knoppe.net/articles/category/attach-files/ - Provided the general layout of a plugin
//

include "receive_payments/include.php";
	
if ( !mjr_bitcoin_mysql_table_exists($wpdb, $table_prefix."mjr_bc_invoices") ) 
	mjr_bitcoin_mysql_install($wpdb, $table_prefix);

if ( mjr_bitcoin_mysql_table_exists($wpdb, $table_prefix."mjr_bc_invoices") ) {
	//Adds meta box to the 
	add_action( 'add_meta_boxes', 'myplugin_add_meta_box' );


} // End of Plugin Actions



add_action( 'save_post', 'myplugin_save_meta_box_data' );
add_action( 'add_meta_boxes', 'myplugin_add_meta_box' );

register_activation_hook( __FILE__, 'myplugin_activate' );


/* ---------------------------------------------
					FUNCTIONS
------------------------------------------------*/

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


function mjr_bitcoin_mysql_install ( $wpdb, $table_prefix ) {
	
	global $wpdb;
	$charset_collate = $wpdb->get_charset_collate();

	require_once(ABSPATH . 'wp-admin/upgrade-functions.php');

	//Creates Invoice Table
  	$invoice_sql = 'CREATE TABLE IF NOT EXISTS ' . $table_prefix . 'mjr_bc_invoices (
		invoice_id INTEGER, 
		price_in_usd DOUBLE, 
		price_in_btc DOUBLE, 
		product_url TEXT, 
		PRIMARY KEY (invoice_id))';
	dbDelta($invoice_sql);

	//Creates invoice payment Table
	$invoice_payment_sql = 'CREATE TABLE IF NOT EXISTS ' . $table_prefix . 'mjr_bc_invoice_payments (
		transaction_hash CHAR(64), 
		value DOUBLE, 
		invoice_id INTEGER, 
		PRIMARY KEY (transaction_hash))';
	dbDelta($invoice_payment_sql);


	//Creates pending invoices table
	$pending_invoice_sql = 'CREATE TABLE IF NOT EXISTS ' . $table_prefix . 'mjr_bc_pending_invoice_payments (
		transaction_hash CHAR(64), 
		value DOUBLE, 
		invoice_id INTEGER, 
		PRIMARY KEY (transaction_hash))';
	dbDelta($pending_invoice_sql);


}

function mjr_bitcoin_mysql_table_exists( $wpdb, $table_name ) {
	global $wpdb;
	if ( !$wpdb->get_results("SHOW TABLES LIKE '%$table_name%'") ) return FALSE;
	else return TRUE;
}


function mjr_bitcoin_mysql_warning() {
	global $wpdb;
	echo '<div class="updated"><h3>WARNING! The MJR Bitcoin MySQL databases were not created! ' . 
	$wpdb->last_error . '</h3></div>';
}


?>