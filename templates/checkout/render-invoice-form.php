<?php
/**
 * Invoice payment Form.
 *
 * @author  Novalnet
 * @package Novalnet-gateway/Templates
 * @version 11.2.0
 */

if ( ! defined( 'ABSPATH' ) ) :
	exit; // Exit if accessed directly.
endif;

if ( ! function_exists( 'wc_get_template' ) ) :
	$settings = get_option( 'woocommerce_novalnet_invoice_settings' );
endif;

if ( ! WC()->session->__isset( 'novalnet_invoice_tid' ) ) :

	// Shows Dob field.
	if ( WC()->session->__isset( 'novalnet_invoice_guarantee_payment' ) ) :
		woocommerce_form_field(
			'novalnet_invoice_dob',
			array(
				'required' => true,
				'label' => __( 'Your date of birth', 'wc-novalnet' ),
				'placeholder' => 'YYYY-MM-DD',
				'class' => array(
					'form-row-wide'
				),
				'custom_attributes' => array(
					 'onkeypress' => 'return novalnet_functions.allow_date( event );',
					 'pattern' => '[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])',
					 'class' => 'date-picker',
					 'autocomplete' => 'OFF',
				),
			)
		);
	endif;

	if ( WC()->session->__isset( 'novalnet_invoice_guarantee_payment_error' ) ) :
	    echo wp_kses( '<p style="color:red;">' . __( 'The payment cannot be processed, because the basic requirements haven’t been met.', 'wc-novalnet' ) . '</p>', array(
				'p' => array(
					'style' => true,
				),
					'font' => array(
					'color' => true,
				),
			)
	    );
	endif;

	if ( novalnet_instance()->novalnet_functions()->validate_fraud_module( $settings, 'novalnet_invoice' ) && ! WC()->session->__isset( 'novalnet_invoice_guarantee_payment' ) && ! WC()->session->__isset( 'novalnet_invoice_guarantee_payment_error' ) ) :

		// Shows Fraud module callback field.
		$field_name = ( 'tel' === $settings ['fraud_module'] ) ? __( 'Telephone number', 'wc-novalnet' ) : __( 'Mobile number', 'wc-novalnet' );

		woocommerce_form_field(
			'novalnet_invoice_pin_by_' . $settings ['fraud_module'],
			array(
				'required'    => true,
				'label'       => $field_name,
				'placeholder' => $field_name,
				'class'       => array(
					'form-row-wide'
				),
				'custom_attributes' => array(
					'onkeypress'   => 'return novalnet_functions.allow_alphanumeric( event );',
					'class'        => 'input-text',
					'autocomplete' => 'OFF',
				),
			)
		);
	endif;
elseif ( WC()->session->__isset( 'novalnet_invoice_tid' ) ) :

	// Shows PIN field.
	woocommerce_form_field(
		'novalnet_invoice_pin',
		array(
			'required'     => true,
			'label'        => __( 'Transaction PIN', 'wc-novalnet' ),
			'placeholder'  => __( 'Transaction PIN', 'wc-novalnet' ),
			'custom_attributes' => array(
				'onkeypress'   => 'return novalnet_functions.allow_alphanumeric( event );',
				'class'        => 'input-text',
				'autocomplete' => 'OFF',
			),
		)
	);

	woocommerce_form_field(
		'novalnet_invoice_new_pin',
		array(
			'type' => 'checkbox',
			'label' => __( 'Forgot your PIN?', 'wc-novalnet' ),
		)
	);
endif;
