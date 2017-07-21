<?php
/**
 * Novalnet Invoice Payment
 *
 * This gateway is used for real time processing of invoice payment of customers.
 *
 * Copyright (c) Novalnet
 *
 * This script is only free to the use for merchants of Novalnet. If
 * you have found this script useful a small recommendation as well as a
 * comment on merchant form would be greatly appreciated.
 *
 * @class   WC_Gateway_Novalnet_Invoice
 * @extends NN_Payment_Gateways
 * @package Novalnet/Classes/Payment
 * @author  Novalnet
 * @located at  /includes/gateways
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * WC_Gateway_Novalnet_Invoice Class.
 */
class WC_Gateway_Novalnet_Invoice extends NN_Payment_Gateways {


	/**
	 * Id for the gateway.
	 *
	 * @var string
	 */
	public $id                = 'novalnet_invoice';

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
	private $paygate_url      = 'https://payport.novalnet.de/paygate.jsp';

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
		add_action( 'woocommerce_update_options_payment_gateways_novalnet_invoice', array( $this, 'backend_validation' ) );

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

		// Guarantee payment process.
		$this->show_guarantee_payment();

		// Check for change payment method.
		$this->check_change_payment_method();

		// Display form fields.
		wc_novalnet_payment_template( $this->id, $this->settings );
	}

	/**
	 * Validate payment fields on the frontend.
	 */
	public function validate_fields() {

		// Unset other payment session.
		$this->unset_other_payment_session();

		$session_invoice = WC()->session->novalnet_invoice;
		$error = '';

		// Assigning post values in session.
		novalnet_instance()->novalnet_functions()->set_post_value_session( $this->id,
			$session_invoice, array(
			'novalnet_invoice_new_pin',
			'novalnet_invoice_pin',
			'novalnet_invoice_pin_by_tel',
			'novalnet_invoice_pin_by_mobile',
			'novalnet_invoice_dob',
			)
		);

		$session_invoice = WC()->session->novalnet_invoice;

		// Process Guaranteed payment.
		if ( ! WC()->session->__isset( 'novalnet_invoice_tid' ) && 'yes' === $this->settings ['guarantee_payment'] && ( WC()->session->__isset( 'novalnet_invoice_guarantee_payment' ) || WC()->session->__isset( 'novalnet_invoice_guarantee_payment_error' ) ) ) {
			$error = novalnet_instance()->novalnet_functions()->check_guarantee_payment( $session_invoice, $this->settings ['force_normal_payment'], $this->id );

			// PIN validation.
		} elseif ( WC()->session->__isset( 'novalnet_invoice_tid' ) ) {
			$error = novalnet_instance()->novalnet_functions()->validate_pin( $session_invoice, $this->id );

			// Callback field validation.
		} elseif ( novalnet_instance()->novalnet_functions()->validate_fraud_module( $this->settings, $this->id ) ) {
			$error = novalnet_instance()->novalnet_functions()->validate_callback_fields( $session_invoice [ 'novalnet_invoice_pin_by_' . $this->settings ['fraud_module'] ], $this->id, $this->settings ['fraud_module'] );
		}

		// Validate error.
		if ( '' !== $error ) {
			WC()->session->__unset( $this->id );

			// Display message and redirect to checkout page.
			$this->display_info( $error, 'error' );
			return $this->novalnet_redirect();
		}
		WC()->session->set( $this->id, $session_invoice );
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
		if ( WC()->session->__isset( 'novalnet_invoice_tid' ) ) {
			$pin_status = $this->process_pin_call();
			if ( ! wc_novalnet_status_check( $pin_status ) ) {

				// Display message.
				$message = 'success';
				if ( '0529009' !== $pin_status ['status'] ) {
					$message = 'error';
				}
				$this->display_info( wc_novalnet_response_text( $pin_status ), $message );
				 return $this->novalnet_redirect();
			}
			$server_response = WC()->session->novalnet;
			$server_response ['tid_status'] = $pin_status ['tid_status'];
		} else {
			$params = $this->form_payment_params( $wc_order );
			if ( WC()->session->__isset( 'novalnet_invoice_guarantee_payment' ) && WC()->session->novalnet_invoice_guarantee_payment ) {

				// Assign guarantee parameters.
				$params = array_merge( $params, wc_novalnet_get_payment_type( 'guarantee_novalnet_invoice' ) );
				$params ['birth_date']   = WC()->session->novalnet_invoice ['novalnet_invoice_dob'];
			}

			// Perform Novalnet server call.
			$server_response = $this->perform_payment_call( $params, $this->paygate_url );
			// Assign time limit and tid in session.
			if ( $this->success_status( $this->id, $server_response ) && WC()->session->__isset( 'novalnet_invoice_fraud_check_validate' ) ) {
				return $this->set_time_limit( $server_response );
			}
		}

		// Perform for success / failure process.
		return $this->check_transaction_status( $server_response, $wc_order, $this->id );
	}


	/**
	 * Check if the gateway is available for use.
	 *
	 * @return boolean
	 */
	public function is_available() {

		if ( empty( $this->settings ['enabled'] ) || 'yes' !== $this->settings ['enabled'] || novalnet_instance()->novalnet_functions()->global_config_validation( $this->global_settings ) || novalnet_instance()->novalnet_functions()->restrict_payment_method( $this->settings ) || novalnet_instance()->novalnet_functions()->check_payment_availablity( $this->id ) ) {

			return false;
		}
		return true;
	}

	/**
	 * Forming gateway parameters.
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
		// Invoice due date.
		if ( wc_novalnet_digits_check( $this->settings ['payment_duration'] ) ) {
			$params ['due_date'] = wc_novalnet_format_due_date( $this->settings ['payment_duration'] );
		}

		$params ['invoice_ref']  = 'BNR-' . $params ['product'] . '-' . $params ['order_no'];
		$params ['invoice_type'] = 'INVOICE';

		// Assign & Checks for fraud module.
		if ( WC()->session->__isset( 'novalnet_invoice_fraud_check_validate' ) && ! WC()->session->__isset( 'novalnet_invoice_guarantee_payment' ) ) {
			$this->form_fraud_module_params( $params, WC()->session->novalnet_invoice [ 'novalnet_invoice_pin_by_' . $this->settings ['fraud_module'] ] );
		}

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

		// Basic payment fields.
		$this->form_fields = $this->basic_payment_config();

		// Fraud module fields.
		$this->fraud_module_config( $this->form_fields );

		// Payment due date field.
		$this->form_fields ['payment_duration'] = array(
			'title'       => __( 'Payment due date (in days)', 'wc-novalnet' ),
			'type'        => 'text',
			'description' => __( 'Enter the number of days to transfer the payment amount to Novalnet (must be greater than 7 days). In case if the field is empty, 14 days will be set as due date by default', 'wc-novalnet' ),
			'desc_tip'    => true,
			'custom_attributes' => array(
				'autocomplete' => 'OFF',
			),
		);

		// Callback order status & Payment reference configurations.
		$this->invoice_payments_config( $this->form_fields );

		// Other configurations.
		$this->other_payment_config( $this->form_fields );

		// Guarantee payment field.
		$this->guarantee_payment_config( $this->form_fields );
	}

	/**
	 * Check backend validation.
	 *
	 * @since 11.1.0
	 */
	public function backend_validation() {
		novalnet_instance()->novalnet_functions()->validate_payment_reference( $this->id );
		novalnet_instance()->novalnet_functions()->validate_guarantee_process( $this->id );
	}
}
