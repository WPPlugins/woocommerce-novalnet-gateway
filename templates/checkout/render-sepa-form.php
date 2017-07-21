<?php
/**
 * Direct Debit SEPA Payment Form.
 *
 * @author  Novalnet
 * @package Novalnet-gateway/Templates
 * @version 11.2.0
 */

if ( ! defined( 'ABSPATH' ) ) :
	exit; // Exit if accessed directly.
endif;

if ( ! function_exists( 'wc_get_template' ) ) :
	$settings = array_merge( novalnet_instance()->novalnet_functions()->get_basic_vendor_details(), get_option( 'woocommerce_novalnet_sepa_settings' ) );
endif;

$params = array();
if ( ! WC()->session->__isset( 'novalnet_sepa_tid' ) ) :

	// Enqueue script.
	wp_enqueue_script( 'wc-novalnet-sepa-script', novalnet_instance()->plugin_url() . '/assets/js/novalnet-sepa.js', array( 'jquery', 'jquery-payment' ), NN_VERSION, true );
	$params['error_message'] = __( 'Your account details are invalid', 'wc-novalnet' );
	$params['country_error_message'] = __( 'Please select the country', 'wc-novalnet' );
	$params['mandate_error_message'] = __( 'Please accept the SEPA direct debit mandate', 'wc-novalnet' );
	$new_account_style = '';
	if ( 'one_click_shop' === $settings ['payment_process'] && ! WC()->session->__isset( 'novalnet_change_payment_method' ) ) :

		// Get masked details.
		$payment_details = wc_novalnet_get_sepa_bank_details( 'novalnet_sepa' );

		if ( ! empty( $payment_details ) ) :
			WC()->session->set( 'novalnet_sepa_reference_tid', $payment_details ['tid'] );
			$payment_details = wc_novalnet_unserialize_data( $payment_details ['bank_details'] );
			if ( ! empty( $payment_details ) ) :
				$new_account_style = 'display:none;'; ?>
				<p>
				 <a id="novalnet_sepa_payment_option" style="cursor: pointer;"><?php echo wp_kses_data( 'Enter new account details', 'wc-novalnet' ); ?></a>
				</p>
				<div id="novalnet_sepa_one_click_shop">

				 <input type="hidden" name="novalnet_sepa_one_click_shop_process" id="novalnet_sepa_one_click_shop_process" value="<?php echo ( empty( WC()->session->novalnet_sepa ['novalnet_sepa_payment_form'] ) ? 'true' : 'false' ) ?>">
					<?php
					woocommerce_form_field(
						'',
						array(
						'class' => array(
						'form-row-wide',
						),
						'default' => $payment_details ['account_holder'],
						'label'   => __( 'Account holder', 'wc-novalnet' ),
						'custom_attributes' => array(
							'readonly' => 'true',
							'disabled' => 'disabled',
						),
						)
					);

				  woocommerce_form_field(
					  '',
					  array(
					  'class' => array(
					  'form-row-wide',
					  ),
					  'default' => $payment_details ['iban'],
					  'label'   => __( 'IBAN', 'wc-novalnet' ),
					  'custom_attributes' => array(
					  'readonly' => 'true',
					  'disabled' => 'disabled',
					  ),
					  )
				  );

				if ( '123456' !== $payment_details ['bic'] ) :
					  woocommerce_form_field(
						  '',
						  array(
						  'class' => array(
						  'form-row-wide',
						  ),
						  'default' => $payment_details ['bic'],
						  'label'   => __( 'BIC', 'wc-novalnet' ),
						  'custom_attributes' => array(
							  'readonly' => 'true',
							  'disabled' => 'disabled',
						  ),
						  )
					  );

				endif; ?>
				</div>
		<?php endif;
		endif;
	endif;

	// Check for affiliate.
	wc_novalnet_process_affiliate_action( $settings );

	$params ['unique_id']        = wc_novalnet_random_string();
	$params ['vendor']          = $settings ['vendor_id'];
	$params ['authcode']        = $settings ['auth_code'];
	$params ['iban']            = '';
	$params ['bic']             = '';
	$params ['auto_refill']     = 'false';
	$params ['payment_data_refill'] = 'false';
	$params ['remote_ip'] = wc_novalnet_get_ip_address();
	$sepa_hash = '';

	// Process payment Refill.
	if ( $settings ['payment_refill'] ) :

		// Get SEPA payment details.
		$payment_details = wc_novalnet_get_sepa_hash();
		$payment_details = ( ! empty( $payment_details ['bank_details'] ) && ! empty( $payment_details ['payment_type'] ) && 'novalnet_sepa' === $payment_details ['payment_type'] ) ? wc_novalnet_unserialize_data( ( $payment_details ['bank_details'] ) ) : '';
		if ( ! empty( $payment_details ['hash'] ) ) :
			$params ['payment_data_refill'] = 'true';
			$sepa_hash = $payment_details ['hash'];
		endif;
	endif;

	// Process auto refill.
	if ( $settings ['auto_refill'] && ! empty( WC()->session->sepa_hash ) ) :
		$params['auto_refill'] = 'true';
		$sepa_hash = esc_html( WC()->session->sepa_hash );
	endif;
	if ( wc_novalnet_compare_version( '3.0.0', WOOCOMMERCE_VERSION, '<' ) ) {
		$sepa_country = WC()->customer->country;
	} else {
		$sepa_country = WC()->customer->get_billing_country();
	}
	?>
		<div id='novalnet_sepa_payment_form' style="<?php echo esc_attr( $new_account_style ); ?>">
			<input type='hidden' id="novalnet_sepa_unique_id"  name='novalnet_sepa_unique_id' value=''/>
			<input type='hidden' id='novalnet_sepa_hash'  name='novalnet_sepa_hash' value=''/>
			<input type='hidden' id='novalnet_sepa_refill_hash'  name='novalnet_sepa_refill_hash' value='<?php echo esc_html( $sepa_hash ); ?>'/>
	<?php
	woocommerce_form_field(
		'novalnet_sepa_account_holder',
		array(
		'required'     => true,
		'class' => array(
			'form-row-wide',
		),
		'label'       => __( 'Account holder', 'wc-novalnet' ),
		'placeholder' => __( 'Account holder', 'wc-novalnet' ),
		'custom_attributes' => array(
			'onkeypress' => 'return novalnet_functions.allow_name_key( event );',
			'class' => 'input-text',
			'autocomplete' => 'OFF',
		),
		)
	);

	woocommerce_form_field(
		'novalnet_sepa_bank_country',
		array(
		'required' => true,
		'type'     => 'country',
		'class'    => array(
			'form-row-wide',
		),
		'default' => $sepa_country,
		'label' => __( 'Bank country', 'wc-novalnet' ),
		)
	);

	woocommerce_form_field(
		'novalnet_sepa_iban',
		array(
		'required'     => true,
		'class' => array(
			'form-row-wide',
		),
		'label'       => __( 'IBAN or Account number', 'wc-novalnet' ),
		'id'          => 'novalnet_sepa_iban',
		'placeholder' => __( 'IBAN or Account number', 'wc-novalnet' ),
		'custom_attributes' => array(
			'onkeypress'   => 'return novalnet_functions.allow_alphanumeric( event );',
			'class'        => 'input-text',
			'autocomplete' => 'OFF',
		),
		)
	);
	echo "<span id='novalnet_sepa_iban_span'></span>";
	woocommerce_form_field(
		'novalnet_sepa_bic',
		array(
		'required'     => true,
		'class' => array(
			'form-row-wide',
		),
		'label'       => __( 'BIC or Bank code', 'wc-novalnet' ),
		'id'          => 'novalnet_sepa_bic',
		'placeholder' => __( 'BIC or Bank code', 'wc-novalnet' ),
		'custom_attributes' => array(
			'onkeypress'   => 'return novalnet_functions.allow_alphanumeric( event );',
			'class'        => 'input-text',
			'autocomplete' => 'OFF',
		),
		)
	);

	echo "<span id='novalnet_sepa_bic_span'></span>";

	woocommerce_form_field(
		'novalnet_sepa_mandate_confirm',
		array(
			'type'  => 'checkbox',
			'label' => __( 'I hereby grant the SEPA direct debit mandate and confirm that the given IBAN and BIC are correct', 'wc-novalnet' ),
		)
	);

	if ( novalnet_instance()->novalnet_functions()->validate_fraud_module( $settings, 'novalnet_sepa' ) && ! WC()->session->__isset( 'novalnet_sepa_guarantee_payment' ) && ! WC()->session->__isset( 'novalnet_sepa_guarantee_payment_error' ) ) :

		// Shows Fraud module callback fields.
		$field_name = ( 'tel' === $settings ['fraud_module'] ) ? __( 'Telephone number', 'wc-novalnet' ) : __( 'Mobile number', 'wc-novalnet' );
		woocommerce_form_field(
			'novalnet_sepa_pin_by_' . $settings ['fraud_module'],
			array(
				'required'     => true,
				'autocomplete' => 'OFF',
				'label'        => $field_name,
				'placeholder'  => $field_name,
				'class' => array(
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

	?></div><?php

	// Shows DOB field.
if ( WC()->session->__isset( 'novalnet_sepa_guarantee_payment' ) ) :

	woocommerce_form_field(
		'novalnet_sepa_dob',
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

if ( WC()->session->__isset( 'novalnet_sepa_guarantee_payment_error' ) ) :
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
elseif ( WC()->session->__isset( 'novalnet_sepa_tid' ) ) :

	// Shows PIN field.
	woocommerce_form_field(
		'novalnet_sepa_pin',
		array(
			'required'     => true,
			'autocomplete' => 'OFF',
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
		'novalnet_sepa_new_pin',
		array(
			'type' => 'checkbox',
			'label' => __( 'Forgot your PIN?', 'wc-novalnet' ),
		)
	);
endif;
wp_localize_script( 'wc-novalnet-sepa-script', 'novalnet_sepa', $params );
