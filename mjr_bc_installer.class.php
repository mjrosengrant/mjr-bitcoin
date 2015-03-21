<?php
	
class Mjr_Bc_Installer{
	

	function install(){
		file_put_contents("logs/installer_log.txt", "Install function entered! \n",FILE_APPEND);
		$this->create_invoice_tables();
	}

	function uninstall(){
		file_put_contents("logs/installer_log.txt", "Install function entered! \n",FILE_APPEND);
		$this->drop_invoice_tables();
	}

	function create_invoice_tables () {

		global $wpdb;
		global $table_prefix;

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

	function drop_invoice_tables () {

		global $wpdb;
		global $table_prefix;

		$charset_collate = $wpdb->get_charset_collate();
		require_once(ABSPATH . 'wp-admin/upgrade-functions.php');

		$t1 = $table_prefix . 'mjr_bc_invoices';
		$t2 = $table_prefix . 'mjr_bc_invoice_payments';
		$t3 = $table_prefix . 'mjr_bc_pending_invoice_payments';

		$drop_sql = "DROP TABLE $t1, $t2, $t3"; 
		echo '<div class="updated"><h3>$drop_sql \n' . 
		$wpdb->last_error . '</h3></div>';
		$wpdb->query($drop_sql);
	}

	function mjr_bitcoin_mysql_table_exists() {
		global $wpdb;
		global $table_name;
		
		if ( !$wpdb->get_results("SHOW TABLES LIKE '%$table_name%'") ) return FALSE;
		else return TRUE;
	}


	function mjr_bitcoin_mysql_warning() {
		global $wpdb;
		echo '<div class="updated"><h3>WARNING! The MJR Bitcoin MySQL databases were not created! ' . 
		$wpdb->last_error . '</h3></div>';
	}


}



?>