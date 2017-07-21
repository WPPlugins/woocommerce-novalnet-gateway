<?php
/**
 * Refund meta box
 *
 * @author  Novalnet
 * @package Novalnet-gateway/admin/views
 * @version 11.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Amount refund View: Page - view order
 */
if ( in_array( $transaction_details ['args'] ['payment_id'], array( '27', '33', '49', '50', '69' ), true ) ) :
	woocommerce_wp_select(
		array(
			'id'      => 'refund_payment_type',
			'label'   => __( 'Select the refund option', 'wc-novalnet' ),
			'options' => array(
				'none'  => __( 'None', 'wc-novalnet' ),
				'sepa'  => __( 'Direct Debit SEPA', 'wc-novalnet' ),
			),
			'custom_attributes' => array(
				'autocomplete' => 'OFF',
				'onchange'   => 'return novalnet_meta_box.show_refund_type( event );',
			),
		)
	);
endif;

?><div id="refund_sepa_form" style="display:none;" ><?php

woocommerce_wp_text_input(
	array(
		'id'    => 'sepa_account_holder',
		'label' => __( 'Account holder ', 'wc-novalnet' ),
		'custom_attributes' => array(
			'autocomplete' => 'OFF',
			'onkeypress'   => 'return novalnet_functions.allow_name_key( event );',
		 ),
	)
);

woocommerce_wp_text_input(
	array(
		'id'    => 'sepa_iban',
		'label' => 'IBAN',
		'custom_attributes' => array(
			'autocomplete'  => 'OFF',
			'onkeypress'    => 'return novalnet_functions.allow_alphanumeric( event );',
		 ),
	)
);

woocommerce_wp_text_input(
	array(
		'id'    => 'sepa_bic',
		'label' => 'BIC',
		'custom_attributes' => array(
			'autocomplete'  => 'OFF',
			'onkeypress'    => 'return novalnet_functions.allow_alphanumeric( event );',
		 ),
	)
);

?></div><?php

woocommerce_wp_text_input(
	array(
	'id'          => 'novalnet_refund_amount',
	'class'       => 'wc_input_price',
	'label'       => __( 'Please enter the refund amount', 'wc-novalnet' ),
	'description' => __( '(in minimum unit of currency. E.g. enter 100 which is equal to 1.00)', 'wc-novalnet' ),
	'custom_attributes' => array(
		'onkeypress'    => 'return novalnet_functions.allow_numbers(event)',
		'autocomplete'  => 'OFF',
	 ),
	 'value'       => $transaction_details ['args'] ['amount'],
	)
);

if ( date( 'Y-m-d', strtotime( $post->post_date ) ) !== date( 'Y-m-d' ) ) :
	woocommerce_wp_text_input(
		array(
			'id'          => 'novalnet_refund_reference',
			'label'       => __( 'Refund reference', 'wc-novalnet' ),
			'value'       => '',
			'custom_attributes' => array(
				'autocomplete'  => 'OFF',
			),
		)
	);
endif;

wc_novalnet_built_button(
	array(
		'id'    => 'novalnet_transaction_process',
		'type'  => '_Amount_Refund',
		'tip'   => __( 'Transaction Refund', 'wc-novalnet' ),
		'title' => __( 'Confirm', 'wc-novalnet' ),
	)
);
