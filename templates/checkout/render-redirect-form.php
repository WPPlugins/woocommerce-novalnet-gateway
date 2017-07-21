<?php
/**
 * Novalnet redirect form.
 *
 * @author  Novalnet
 * @package Novalnet-gateway/Templates
 * @version 11.2.0
 */

if ( ! defined( 'ABSPATH' ) ) :
	exit; // Exit if accessed directly.
endif;

// Auto submit the payment form to Novalnet server.
wc_enqueue_js('
	novalnet_functions.load_block( "content", "' . esc_js( __( 'After the successful verification, you will be redirected to Novalnet secure order page to proceed with the payment', 'wc-novalnet' ) ) . '" );
	$( "#submit_novalnet_payment_form" ).click();
');
echo '<form id="novalnet_payment_redirect_form" action="' . esc_attr( $contents ['paygate_url'] ) . '" method="post" target="_top">';
foreach ( $contents ['params'] as $key => $value ) {
	echo '<input type="hidden" name="' . esc_html( $key ) . '" id="' . esc_html( $key ) . '" value="' . esc_html( $value ) . '" />';
}
echo '<input type="submit" class="button-alt" id="submit_novalnet_payment_form" value="' . esc_html( __( 'Pay', 'wc-novalnet' ) ) . '" /><a class="button cancel" href="' . esc_url( WC()->cart->get_checkout_url() ) . '">' . esc_html( __( 'Cancel', 'wc-novalnet' ) ) . '</a> </form>';
