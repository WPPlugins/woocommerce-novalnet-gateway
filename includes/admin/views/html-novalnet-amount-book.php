<?php
/**
 * Amount-book meta box
 *
 * @author  Novalnet
 * @package Novalnet-gateway/admin/views
 * @version 11.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Amount book View: Page - view order
 */
woocommerce_wp_text_input(
	array(
		'id'                => 'novalnet_book_amount',
		'data_type'         => 'price',
		'custom_attributes' => array(
			'onkeypress'    => 'return novalnet_functions.allow_numbers(event)',
			'autocomplete'  => 'OFF',
		 ),
		 'label'       => __( 'Transaction booking amount', 'wc-novalnet' ),
		 'description' => __( '(in minimum unit of currency. E.g. enter 100 which is equal to 1.00)', 'wc-novalnet' ),
		 'value'       => wc_novalnet_formatted_amount( get_post_meta( $post->ID, '_order_total', true ) ),
	)
);
wc_novalnet_built_button(
	array(
		'id'    => 'novalnet_transaction_process',
		'type'  => '_Amount_Book',
		'tip'   => __( 'Book transaction', 'wc-novalnet' ),
		'title' => __( 'Update', 'wc-novalnet' ),
	)
);
