<?php
/**
 * Amount-update meta box
 *
 * @author  Novalnet
 * @package Novalnet-gateway/admin/views
 * @version 11.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Amount update View: Page - view order
 */
if ( '27' === $transaction_details ['args']['payment_id'] ) :

	$invoice_details = ( '' !== $transaction_details ['args'] ['bank_details'] ) ? wc_novalnet_unserialize_data( $transaction_details ['args'] ['bank_details'] ) : '';
	if ( empty( $invoice_details ['due_date'] ) && wc_novalnet_check_valid_table( 'novalnet_invoice_details' ) ) :
		$invoice_details = wc_novalnet_order_no_details( $post->ID, 'novalnet_invoice_details' );
		$invoice_details ['due_date'] = $invoice_details ['invoice_due_date'];
	endif;
	if ( empty( $invoice_details ['due_date'] ) ) :
		echo '<div style="color:red;">' . esc_attr( __( 'This operation is not possible for this order.', 'wc-novalnet' ) ) . '</div>';
	else :
		woocommerce_wp_text_input(
			array(
				'id'          => 'novalnet_update_amount',
				'class'       => 'wc_input_price',
				'label'       => __( 'Update transaction amount', 'wc-novalnet' ),
				'description' => __( '(in minimum unit of currency. E.g. enter 100 which is equal to 1.00)', 'wc-novalnet' ),
				'custom_attributes' => array(
					'onkeypress'    => 'return novalnet_functions.allow_numbers(event)',
					'autocomplete'  => 'OFF',
				),
				'value'       => $transaction_details ['args'] ['amount'],
			)
		);
		woocommerce_wp_text_input(
			array(
				'id'                => 'novalnet_due_date',
				'label'             => __( 'Transaction due date', 'wc-novalnet' ),
				'placeholder'       => 'YYYY-MM-DD',
				'class'             => 'date-picker',
				'value'             => $invoice_details ['due_date'],
				'custom_attributes' => array(
					'pattern' => '[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])',
				),
			)
		);
		wc_novalnet_built_button(
			array(
				'id'    => 'novalnet_transaction_process',
				'type'  => '_Amount_Update',
				'tip'   => __( 'Change the amount / due date ', 'wc-novalnet' ),
				'title' => __( 'Update', 'wc-novalnet' ),
			)
		);
	endif;
else :
	woocommerce_wp_text_input(
		array(
			'id'          => 'novalnet_update_amount',
			'class'       => 'wc_input_price',
			'label'       => __( 'Update transaction amount', 'wc-novalnet' ),
			'description' => __( '(in minimum unit of currency. E.g. enter 100 which is equal to 1.00)', 'wc-novalnet' ),
			'custom_attributes' => array(
				'onkeypress'    => 'return novalnet_functions.allow_numbers(event)',
				'autocomplete'  => 'OFF',
			),
			'value'       => $transaction_details ['args'] ['amount'],
			)
	);
	wc_novalnet_built_button(
		array(
			'id'    => 'novalnet_transaction_process',
			'type'  => '_Amount_Update',
			'tip'   => __( 'Amount update', 'wc-novalnet' ),
			'title' => __( 'Update', 'wc-novalnet' ),
		)
	);
endif;
