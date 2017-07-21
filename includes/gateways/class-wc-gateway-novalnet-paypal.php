<?php
/**
 * Novalnet PayPal Payment.
 *
 * This gateway is used for real time processing of paypal transaction of customers.
 *
 * Copyright (c) Novalnet
 *
 * This script is only free to the use for merchants of Novalnet. If
 * you have found this script useful a small recommendation as well as a
 * comment on merchant form would be greatly appreciated.
 *
 * @class   WC_Gateway_Novalnet_Paypal
 * @extends NN_Payment_Gateways
 * @package Novalnet/Classes/Payment
 * @author  Novalnet
 * @located at  /includes/gateways
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * WC_Gateway_Novalnet_Paypal Class.
 */
class WC_Gateway_Novalnet_Paypal extends NN_Payment_Gateways {


	/**
	 * Id for the gateway.
	 *
	 * @var string
	 */
	public $id                = 'novalnet_paypal';

	/**
	 * Global settings of Novalnet.
	 *
	 * @var array
	 */
	public $global_settings   = array();

	/**
	 * Settings of the gateway.
	 *
	 * @var array
	 */
	public $settings          = array();

	/**
	 * Paygate URL.
	 *
	 * @var string
	 */
	private $paygate_url      = 'https://payport.novalnet.de/paypal_payport';

	/**
	 * Gateway shows fields on the checkout.
	 *
	 * @var bool
	 */
	public $has_fields        = true;

	/**
	 * Constructor for the gateway.
	 */
	public function __construct() {

		// Assign payment details.
		$this->assign_basic_payment_details();

		// Handle redirection payment response.
		add_action( 'woocommerce_api_response_novalnet_paypal', array( $this, 'check_novalnet_payment_response' ), 10 );

		$this->description .= wpautop( __( 'Please don’t close the browser after successful payment, until you have been redirected back to the Shop', 'wc-novalnet' ) );
	}

	/**
	 * Returns the gateway icon.
	 *
	 * @return string
	 */
	public function get_icon() {

		return apply_filters( 'woocommerce_gateway_icon', $this->built_logo( $this->global_settings ['payment_logo'], $this->id, $this->title ), $this->id );
	}

	/**
	 * Validate payment fields on the frontend.
	 */
	public function validate_fields() {

		// Unset other payment session.
		$this->unset_other_payment_session();
		$session_paypal = WC()->session->novalnet_paypal;

		// Assigning post values in session.
		novalnet_instance()->novalnet_functions()->set_post_value_session( $this->id,
			$session_paypal, array(
			'novalnet_paypal_one_click_shop_process',
			'novalnet_paypal_payment_form',
			'novalnet_paypal_reference_tid',
			)
		);

		$session_paypal = WC()->session->novalnet_paypal;
		if ( ! empty( $session_paypal['novalnet_paypal_one_click_shop_process'] ) && 'true' === $session_paypal['novalnet_paypal_one_click_shop_process'] && WC()->session->__isset( 'novalnet_paypal_reference_tid' ) ) {
			$session_paypal ['novalnet_paypal_reference_tid'] = WC()->session->novalnet_paypal_reference_tid;
		} else {
			$session_paypal ['novalnet_paypal_payment_form']  = true;
			WC()->session->__unset( 'novalnet_paypal_reference_tid' );
		}
		WC()->session->set( $this->id, $session_paypal );
	}

	/**
	 * Displays the payment form, payment description on checkout.
	 */
	public function payment_fields() {

		// Hide multiple payment fields.
		wc_novalnet_hide_multiple_payment();

		WC()->session->__unset( 'novalnet_receipt_page' );

		// Unset payment session for ignored payments.
		if ( WC()->session->__isset( 'chosen_payment_method' ) && WC()->session->chosen_payment_method !== $this->id ) {
			WC()->session->__unset( $this->id );
		}

		// Check for change payment method.
		$this->check_change_payment_method();

		// Display form fields.
		wc_novalnet_payment_template( $this->id, $this->settings );
	}

	/**
	 * Process payment flow of the gateway.
	 *
	 * @param int $order_id the order id.
	 *
	 * @return array
	 */
	public function process_payment( $order_id ) {

		$wc_order = new WC_Order( $order_id );
		if ( ! empty( WC()->session->novalnet_paypal_reference_tid ) ) {

			// Perform Novalnet server call.
			$server_response = $this->perform_payment_call( $this->form_payment_params( $wc_order ) );

			// Checks transaction status.
			return $this->check_transaction_status( $server_response, $wc_order, $this->id );
		} else {

			return array(
			 'result'   => 'success',
			 'redirect' => $wc_order->get_checkout_payment_url( true ),
			);
		}
	}

	/**
	 * Check if the gateway is available for use.
	 *
	 * @return boolean
	 */
	public function is_available() {

		if ( empty( $this->settings ['enabled'] ) || 'yes' !== $this->settings ['enabled'] || novalnet_instance()->novalnet_functions()->global_config_validation( $this->global_settings ) || novalnet_instance()->novalnet_functions()->restrict_payment_method( $this->settings ) ) {
			return false;
		}
		return true;
	}

	/**
	 * Form gateway parameters to process in the Novalnet server.
	 *
	 * @param WC_Order $wc_order the order object.
	 *
	 * @return array
	 */
	public function form_payment_params( $wc_order ) {
		global $current_user;

		// Generate basic parameters.
		$data = $this->generate_payment_parameters(
			$wc_order, array_merge(
				$this->global_settings, array(
				'test_mode'  => $this->settings ['test_mode'],
				'reference1' => $this->settings ['reference1'],
				'reference2' => $this->settings ['reference2'],
				)
			), $this->id
		);

		$params = $data ['payment_parameters'];

		if ( ! empty( WC()->session->novalnet_paypal ['novalnet_paypal_reference_tid'] ) ) {

			// Assign payment reference details.
			$params ['payment_ref'] = WC()->session->novalnet_paypal ['novalnet_paypal_reference_tid'];
			return $params;
		}

		if ( empty( WC()->session->novalnet_change_payment_method ) ) {

			// Check for reference transaction.
			if ( ! WC()->session->__isset( 'novalnet_change_payment_method' ) && 'one_click_shop' === $this->settings ['payment_process'] && ! empty( $current_user->ID ) ) {
				$params ['create_payment_ref'] = '1';
			}

			// Assign zero amount booking.
			if ( empty( $params['tariff_period'] ) && 'zero_amount_book' === $this->settings['payment_process'] ) {
				$params ['create_payment_ref'] = '1';
				$this->assign_zero_amount( $params );
			}
		}

		// Encoding parameters.
		$this->redirect_payment_params( $params, $wc_order, $data ['payment_access_key'] );

		// Log to notify payment parameters formed successfully.
		if ( $this->global_settings ['debug_log'] ) {
			$this->novalnet_log->add( 'novalnetpayments', 'Payment parameters formed successfully for the payment ' . $this->id );
		}
		return array(
		 'params'      => $params,
		 'paygate_url' => $this->paygate_url,
		);
	}

	/**
	 * Returns the order status.
	 *
	 * @param string $order_status the order status.
	 *
	 * @return string
	 */
	public function get_order_status( $order_status ) {

		if ( WC()->session->__isset( 'current_novalnet_payment' ) && WC()->session->current_novalnet_payment === $this->id && WC()->session->__isset( 'novalnet_paypal_order_status' ) ) {
			$order_status = WC()->session->novalnet_paypal_order_status;
		}
		return $order_status;
	}

	/**
	 * Manage redirect process.
	 */
	public function check_novalnet_payment_response() {

		$server_response = $_REQUEST; // input var okay.

		// Checks redirect response.
		if ( ! empty( $server_response['wc-api'] ) && 'response_' . $this->id === $server_response['wc-api'] ) {

			// Set order status in session.
			WC()->session->set( 'novalnet_paypal_order_status', $this->settings ['order_success_status'] );
			if ( in_array( $server_response ['tid_status'], array( '85', '90' ), true ) ) {
				WC()->session->set( 'novalnet_paypal_order_status', $this->settings ['pending_status'] );
			}

			// Process redirect response.
			$status = $this->process_redirect_payment_response( $server_response );

			// Redirect to checkout / success page.
			wc_novalnet_safe_redirect( $status['redirect'] );
		}
	}

	/**
	 * Payment configurations in shop backend.
	 */
	public function init_form_fields() {

		// Basic payment configurations.
		$this->form_fields = $this->basic_payment_config();

		// Shopping type configurations.
		$this->shopping_type_payment_config( $this->form_fields );

		// Paypal pending configurations.
		$this->pending_status_payment_config( $this->form_fields );

		// Other configurations.
		$this->other_payment_config( $this->form_fields );

		wc_enqueue_js( "
			jQuery( document ).ready(function () {

				jQuery( '#woocommerce_novalnet_paypal_payment_process' ).on( 'change', function() {
					if ( 'none' !== jQuery( '#woocommerce_novalnet_paypal_payment_process' ).val() ) {
						jQuery( '#novalnet_reference_alert' ).html('" . __( 'In order to use this option you must have billing agreement option enabled in your PayPal account. Please contact your account manager at PayPal.', 'wc-novalnet' ) . "');
					} else {
						jQuery( '#novalnet_reference_alert' ).html('');
					}
				}).change();
			});
		" );
	}
}
