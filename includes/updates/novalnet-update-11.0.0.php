<?php
/**
 * Update Novalnet to 11.2.0.
 *
 * @author   Novalnet
 * @category Admin
 * @package  Novalnet-gateway/Updates
 * @version  11.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
global $wpdb;

// Update Novalnet Transaction table.
if ( wc_novalnet_check_valid_table( 'novalnet_transaction_detail' ) && wc_novalnet_check_valid_column( 'novalnet_transaction_detail', 'active' ) ) {

	wc_novalnet_query_process( $wpdb->query( "ALTER TABLE {$wpdb->prefix}novalnet_transaction_detail DROP COLUMN status, DROP COLUMN active, DROP COLUMN process_key" ) );// db call ok; no-cache ok.
}

// Update Novalnet callback table.
if ( wc_novalnet_check_valid_table( 'novalnet_callback_history' ) ) {
	if ( wc_novalnet_check_valid_column( 'novalnet_callback_history', 'currency' ) ) {
		wc_novalnet_query_process( $wpdb->query( "ALTER TABLE {$wpdb->prefix}novalnet_callback_history DROP COLUMN currency" ) ); // db call ok; no-cache ok.
	}

	if ( wc_novalnet_check_valid_column( 'novalnet_callback_history', 'product_id' ) ) {
		wc_novalnet_query_process( $wpdb->query( "ALTER TABLE {$wpdb->prefix}novalnet_callback_history DROP COLUMN product_id" ) ); // db call ok; no-cache ok.
	}
}
