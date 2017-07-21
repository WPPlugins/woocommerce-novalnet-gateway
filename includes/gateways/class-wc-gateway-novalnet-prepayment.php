<?php
/**
 * Novalnet Prepayment Payment.
 *
 * This gateway is used for real time processing of prepayment payment of customers.
 *
 * Copyright (c) Novalnet
 *
 * This script is only free to the use for merchants of Novalnet. If
 * you have found this script useful a small recommendation as well as a
 * comment on merchant form would be greatly appreciated.
 *
 * @class   WC_Gateway_Novalnet_Prepayment
 * @extends NN_Payment_Gateways
 * @package Novalnet/Classes/Payment
 * @author  Novalnet
 * @located at  /includes/gateways
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * WC_Gateway_Novalnet_Prepayment Class.
 */
class WC_Gateway_Novalnet_Prepayment extends NN_Payment_Gateways {


	/**
	 * Id for the gateway.
	 *
	 * @var string
	 */
	var $id                = 'novalnet_prepayment';

	/**
	 * Global settings of Novalnet.
	 *
	 * @var array
	 */
	var $global_settings   = array();

	/**
	 * Settings of the gateway.
	 *
	 * @var array
	 */
	var $settings          = array();

	/**
	 * Paygate URL.
	 *
	 * @var string
	 */
	private $paygate_url   = 'https://payport.novalnet.de/paygate.jsp';

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

		// Validate payment settings.
		add_action( 'woocommerce_update_options_payment_gateways_novalnet_prepayment', array( $this, 'backend_validation' ) );

		// Assign payment details.
		$this->assign_basic_payment_details();
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

		// Check for change payment method.
		$this->check_change_payment_method();
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

		// Perform payment call.
		return $this->check_transaction_status( $this->perform_payment_call( $this->form_payment_params( $wc_order ), $this->paygate_url ), $wc_order, $this->id );
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

		$params ['invoice_ref']  = 'BNR-' . $params ['product'] . '-' . $params ['order_no'];
		$params ['invoice_type'] = 'PREPAYMENT';

		// Log to notify payment parameters formed successfully.
		if ( $this->global_settings ['debug_log'] ) {
			$this->novalnet_log->add( 'novalnetpayments', 'Payment parameters formed successfully for the payment ' . $this->id );
		}
		return $params;
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
	 * Payment configurations in shop backend.
	 */
	public function init_form_fields() {

		// Basic payment configurations.
		$this->form_fields = $this->basic_payment_config();

		// Callback order status & Payment reference configurations.
		$this->invoice_payments_config( $this->form_fields );

		// Other configurations.
		$this->other_payment_config( $this->form_fields );
	}

	/**
	 * Check backend validation.
	 *
	 * @since 11.1.0
	 */
	public function backend_validation() {
		novalnet_instance()->novalnet_functions()->validate_payment_reference( $this->id );
	}
}
