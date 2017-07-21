<?php
/**
 * Manage transaction meta box
 *
 * @author  Novalnet
 * @package Novalnet-gateway/admin/views
 * @version 11.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Manage transaction View: Page - view order
 */
woocommerce_wp_select(
	array(
	'id'      => 'transaction_status',
	'name'    => 'transaction_status',
	'label'   => __( 'Please select status', 'wc-novalnet' ),
	'options' => array(
		''    => __( '--Select--', 'wc-novalnet' ),
		'100' => __( 'Confirm', 'wc-novalnet' ),
		'103' => __( 'Cancel', 'wc-novalnet' ),
	 ),
	)
);

wc_novalnet_built_button(
	array(
	'id'    => 'novalnet_transaction_process',
	'type'  => '_Manage_Transaction',
	'tip'   => __( 'Manage Transaction', 'wc-novalnet' ),
	'title' => __( 'Update', 'wc-novalnet' ),
	)
);
