<?php
/**
 * Novalnet Giropay Payment.
 *
 * This gateway is used for real time processing of bank data of customers.
 *
 * Copyright (c) Novalnet
 *
 * This script is only free to the use for merchants of Novalnet. If
 * you have found this script useful a small recommendation as well as a
 * comment on merchant form would be greatly appreciated.
 *
 * @class   WC_Gateway_Novalnet_Giropay
 * @extends NN_Payment_Gateways
 * @package Novalnet/Classes/Payment
 * @author  Novalnet
 * @located at  /includes/gateways
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * WC_Gateway_Novalnet_Giropay Class.
 */
class WC_Gateway_Novalnet_Giropay extends NN_Payment_Gateways {


	/**
	 * Id for the gateway.
	 *
	 * @var string
	 */
	public $id                = 'novalnet_giropay';

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
	private $paygate_url      = 'https://payport.novalnet.de/giropay';

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
		add_action( 'woocommerce_api_response_novalnet_giropay', array( $this, 'check_novalnet_payment_response' ) );

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
	}


	/**
	 * Displays the payment form, payment description on checkout.
	 */
	public function payment_fields() {

		// Display payment details.
		$this->display_payment_details(
			array(
			'description'           => $this->description,
			'test_mode'             => $this->settings ['test_mode'],
			'payment_instruction'   => $this->settings ['payment_instruction'],
			)
		);
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
		return array(
		 'result'   => 'success',
		 'redirect' => $wc_order->get_checkout_payment_url( true ),
		);
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

		// Generate basic parameters.
		$novalnet_settings = array_merge(
			$this->global_settings, array(
			'test_mode'  => $this->settings ['test_mode'],
			'reference1' => $this->settings ['reference1'],
			'reference2' => $this->settings ['reference2'],
			)
		);

		// Generate basic parameters.
		$data = $this->generate_payment_parameters( $wc_order, $novalnet_settings, $this->id );
		$params = $data ['payment_parameters'];

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

		if ( WC()->session->__isset( 'current_novalnet_payment' ) && WC()->session->current_novalnet_payment === $this->id ) {
			$order_status = $this->settings ['order_success_status'];
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

		// Other configurations.
		$this->other_payment_config( $this->form_fields );
	}
}
