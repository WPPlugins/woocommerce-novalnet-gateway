<?php
/**
 * Credit Card Payment Form.
 *
 * @author  Novalnet
 * @package Novalnet-gateway/Templates
 * @version 11.2.0
 */

if ( ! defined( 'ABSPATH' ) ) :
	exit; // Exit if accessed directly.
endif;

if ( ! function_exists( 'wc_get_template' ) ) :
	$settings = get_option( 'woocommerce_novalnet_cc_settings' );
endif;

// Enqueue script.
wp_enqueue_script( 'wc-novalnet-cc-script', novalnet_instance()->plugin_url() . '/assets/js/novalnet-cc.js', array( 'jquery', 'jquery-payment' ), NN_VERSION, true );
$new_card_style = '';

if ( 'one_click_shop' === $settings ['payment_process'] && ! $settings ['cc_secure_enabled'] && ! WC()->session->__isset( 'novalnet_change_payment_method' ) ) :

	// Get masked details.
	$payment_details = wc_novalnet_get_bank_details( 'novalnet_cc' );
	if ( ! empty( $payment_details ) ) :
		WC()->session->set( 'novalnet_cc_reference_tid', $payment_details ['tid'] );
		$payment_details = wc_novalnet_unserialize_data( $payment_details ['bank_details'] );
		if ( ! empty( $payment_details ) ) :
			$new_card_style = 'display:none;'; ?>
			<p>
			 <a id="novalnet_cc_payment_option" style="cursor: pointer;"><?php echo esc_html( 'Enter new card details', 'wc-novalnet' ) ?></a>
			</p>
			<div id="novalnet_cc_one_click_shop">
				<input type="hidden" name="novalnet_cc_one_click_shop_process" id="novalnet_cc_one_click_shop_process" value="<?php echo empty( WC()->session->novalnet_cc ['novalnet_cc_payment_form'] ) ? 'true' : 'false' ?>">
				<?php
				woocommerce_form_field(
					'',
					array(
					'class' => array(
					'form-row-wide',
					),
					'default' => $payment_details ['cc_type'],
					'label' => __( 'Card type', 'wc-novalnet' ),
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
					 'default' => $payment_details ['cc_holder'],
					 'label' => __( 'Card holder name', 'wc-novalnet' ),
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
					 'default' => $payment_details ['cc_no'],
					 'label' => __( 'Card number', 'wc-novalnet' ),
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
					 'default' => $payment_details ['cc_exp_month'] . ' / ' . $payment_details ['cc_exp_year'],
					 'label' => __( 'Expiry date', 'wc-novalnet' ),
					 'custom_attributes' => array(
						'readonly' => 'true',
						'disabled' => 'disabled',
					  ),
					)
				); ?>
			</div>
<?php	 endif;
	endif;
endif; ?>

<div id="novalnet_cc_payment_form" style="<?php echo esc_attr( $new_card_style ); ?>">
<?php 	$product_activation_key = get_option( 'novalnet_public_key' );

		// Get css configuaration.
		$css_configuration      = get_option( 'woocommerce_novalnet_cc_iframe_configuration' );
		$language               = wc_novalnet_shop_language();
		$signature              = base64_encode( $product_activation_key . '&' . wc_novalnet_get_ip_address() . '&' . wc_novalnet_get_ip_address( 'SERVER_ADDR' ) );
		$css_configuration ['standard_label']           = $settings ['standard_label'];
		$css_configuration ['standard_input']           = $settings ['standard_input'];
		$css_configuration ['standard_css']             = $settings ['standard_css'];
		$css_configuration ['holder_label_text']        = __( 'Card holder name', 'wc-novalnet' );
		$css_configuration ['holder_place_holder_text'] = __( 'Name on card', 'wc-novalnet' );
		$css_configuration ['number_label_text']        = __( 'Card number', 'wc-novalnet' );
		$css_configuration ['number_place_holder_text'] = __( 'XXXX XXXX XXXX XXXX', 'wc-novalnet' );
		$css_configuration ['expiry_label_text']        = __( 'Expiry date', 'wc-novalnet' );
		$css_configuration ['expiry_place_holder_text'] = __( 'MM / YYYY', 'wc-novalnet' );
		$css_configuration ['cvc_label_text'] 			= __( 'CVC/CVV/CID', 'wc-novalnet' );
		$css_configuration ['cvc_place_holder_text']    = __( 'XXX', 'wc-novalnet' );
		$css_configuration ['cvc_hint_text'] 			= __( 'what is this?', 'wc-novalnet' );
		$css_configuration ['error_text']               = __( 'Your credit card details are invalid', 'wc-novalnet' );

		// Enqueue script.
		wp_enqueue_script( 'wc-novalnet-cc-iframe-script', novalnet_instance()->plugin_url() . '/assets/js/novalnet-cc-iframe.js', array( 'jquery', 'jquery-payment' ), NN_VERSION, true );

		wp_localize_script( 'wc-novalnet-cc-iframe-script', 'novalnet_cc_iframe', $css_configuration ); ?>

	<iframe onload="novalnet_creditcard_iframe.load_iframe()" frameBorder="0" scrolling="no" width="100%" id = "novalnet_cc_iframe" src = "https://secure.novalnet.de/cc?signature=<?php echo esc_attr( $signature ) . '&ln=' . esc_attr( $language ) ?>" ></iframe>
	<input type="hidden" name="novalnet_cc_pan_hash" id="novalnet_cc_pan_hash"/>
	<input type="hidden" name="novalnet_cc_unique_id" id="novalnet_cc_unique_id"/>
</div>
