<?php
/**
 * PayPal Payment Form.
 *
 * @author  Novalnet
 * @package Novalnet-gateway/Templates
 * @version 11.2.0
 */

if ( ! defined( 'ABSPATH' ) ) :
	exit; // Exit if accessed directly.
endif;

if ( ! function_exists( 'wc_get_template' ) ) :
	$settings = get_option( 'woocommerce_novalnet_paypal_settings' );
endif;

$params = array();
$new_details_style = '';

// Enqueue script.
wp_enqueue_script( 'wc-novalnet-paypal-script', novalnet_instance()->plugin_url() . '/assets/js/novalnet-paypal.js', array( 'jquery', 'jquery-payment' ), NN_VERSION, true );
if ( 'one_click_shop' === $settings ['payment_process'] && ! WC()->session->__isset( 'novalnet_change_payment_method' ) ) :

	// Get masked details.
	$payment_details = wc_novalnet_get_bank_details( 'novalnet_paypal' );
	if ( ! empty( $payment_details ) ) :
		WC()->session->set( 'novalnet_paypal_reference_tid', $payment_details ['tid'] );
		$payment_details = wc_novalnet_unserialize_data( $payment_details ['bank_details'] );
		if ( ! empty( $payment_details ) ) :
			$new_details_style = 'display:none;'; ?>
			<p>
			 <a id="novalnet_paypal_payment_option" style="cursor: pointer;"><?php echo wp_kses_data( 'Proceed with new PayPal account details', 'wc-novalnet' ); ?></a>
			</p>
			<div id="novalnet_paypal_one_click_shop">

				<p>
					<?php echo wp_kses( __( 'Once the order is submitted, the payment will be processed as a reference transaction at Novalnet', 'wc-novalnet' ), array() );
					echo wp_kses( wpautop( $settings ['payment_instruction'] ), array(
						'p' => array(),
						'font' => array(
							'color' => true,
						),
						'b' => array(),
					) );

					echo ( $settings ['test_mode'] ) ? wp_kses(
						wpautop( '<font color="red">' . __( 'The payment will be processed in the test mode therefore amount for this transaction will not be charged', 'wc-novalnet' ) ) . '</font>', array(
							'p' => array(),
							'font' => array(
								'color' => true,
							),
						)
					) : ''; ?>
				</p>
				<input type="hidden" name="novalnet_paypal_one_click_shop_process" id="novalnet_paypal_one_click_shop_process" value="<?php echo empty( WC()->session->novalnet_paypal ['novalnet_paypal_payment_form'] ) ? 'true' : 'false' ?>">
				<?php if ( ! empty( $payment_details ['paypal_transaction_id'] ) ) :
					woocommerce_form_field(
						'',
						array(
							'class' => array(
								'form-row-wide',
							),
							'default' => $payment_details ['paypal_transaction_id'],
							'label' => __( 'PayPal transaction ID', 'wc-novalnet' ),
							'custom_attributes' => array(
								'readonly' => 'true',
								'disabled' => 'disabled',
							),
						)
					);
				endif;
				woocommerce_form_field(
					'',
					array(
						'class' => array(
							'form-row-wide',
						),
						'default' => WC()->session->novalnet_paypal_reference_tid,
						'label' => __( 'Novalnet Transaction ID', 'wc-novalnet' ),
						'custom_attributes' => array(
							'readonly' => 'true',
							'disabled' => 'disabled',
						),
					)
				); ?>
			</div>
	<?php endif;
	endif;
endif; ?>
	<div id="novalnet_paypal_payment_form" style="<?php echo esc_attr( $new_details_style ); ?>">
		<p id="novalnet_paypal_payment_form" class="form-row form-row-wide"> <?php
		echo wp_kses(
			wpautop( wc_novalnet_get_payment_text( $settings, wc_novalnet_shop_language(), 'novalnet_paypal', 'description' ) ) . wpautop( __( 'Please don’t close the browser after successful payment, until you have been redirected back to the Shop', 'wc-novalnet' ) ), array(
				'p' => array(),
				'font' => array(
					'color' => true,
				),
				'b' => array(),
			)
		);
		echo wp_kses( wpautop( $settings ['payment_instruction'] ), array(
			'p' => array(),
			'font' => array(
				'color' => true,
			),
			'b' => array(),
		) );
		echo ( $settings ['test_mode'] ) ? wp_kses(
			wpautop( '<font color="red">' . __( 'The payment will be processed in the test mode therefore amount for this transaction will not be charged', 'wc-novalnet' ) ) . '</font>', array(
				'p' => array(),
				'font' => array(
					'color' => true,
				),
				'b' => array(),
			)
		) : '';
		?>
		</p>
	</div>
	<?php
	wp_localize_script( 'wc-novalnet-paypal-script', 'novalnet_paypal', $params );
