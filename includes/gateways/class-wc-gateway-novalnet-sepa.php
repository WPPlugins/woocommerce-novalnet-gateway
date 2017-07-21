<?php
/**
 * Novalnet Direct Debit SEPA Payment.
 *
 * This gateway is used for real time processing of bank data of customers.
 *
 * Copyright (c) Novalnet
 *
 * This script is only free to the use for merchants of Novalnet. If
 * you have found this script useful a small recommendation as well as a
 * comment on merchant form would be greatly appreciated.
 *
 * @class   WC_Gateway_Novalnet_Sepa
 * @extends NN_Payment_Gateways
 * @package Novalnet/Classes/Payment
 * @author  Novalnet
 * @located at  /includes/gateways
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * WC_Gateway_Novalnet_Sepa Class.
 */
class WC_Gateway_Novalnet_Sepa extends NN_Payment_Gateways {


	/**
	 * Id for the gateway.
	 *
	 * @var string
	 */
	public $id                = 'novalnet_sepa';

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
		add_action( 'woocommerce_update_options_payment_gateways_novalnet_sepa', array( $this, 'backend_validation' ) );

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
		wc_novalnet_payment_template( $this->id, array_merge(
			$this->settings, array(
			'vendor_id' => $this->global_settings ['vendor_id'],
			'auth_code' => $this->global_settings ['auth_code'],
			)
		) );
	}

	/**
	 * Validate payment fields on the frontend.
	 */
	public function validate_fields() {

		// Unset other payment session.
		$this->unset_other_payment_session();

		$session_sepa = WC()->session->novalnet_sepa;
		$error = '';

		// Assigning post values in session.
		novalnet_instance()->novalnet_functions()->set_post_value_session( $this->id,
			$session_sepa, array(
			'novalnet_sepa_account_holder',
			'novalnet_sepa_hash',
			'novalnet_sepa_unique_id',
			'novalnet_sepa_one_click_shop_process',
			'novalnet_sepa_payment_form',
			'novalnet_sepa_reference_tid',
			'novalnet_sepa_new_pin',
			'novalnet_sepa_pin',
			'novalnet_sepa_pin_by_tel',
			'novalnet_sepa_pin_by_mobile',
			'novalnet_sepa_dob',
			)
		);

		$session_sepa               = WC()->session->novalnet_sepa;
		if ( WC()->session->__isset( 'novalnet_sepa_tid' ) ) {
			$error = novalnet_instance()->novalnet_functions()->validate_pin( $session_sepa, $this->id );
		} else {

			// Check fraud module.
			$validate_fraud_module = novalnet_instance()->novalnet_functions()->validate_fraud_module( $this->settings, $this->id );

			// Process Guaranteed payment.
			if ( 'yes' === $this->settings ['guarantee_payment'] && ( WC()->session->__isset( 'novalnet_sepa_guarantee_payment' ) || WC()->session->__isset( 'novalnet_sepa_guarantee_payment_error' ) ) ) {

				$error = novalnet_instance()->novalnet_functions()->check_guarantee_payment( $session_sepa, $this->settings ['force_normal_payment'], $this->id );

				$validate_fraud_module = false;
				if ( '' !== $error ) {
					WC()->session->__unset( $this->id );

					// Display message.
					$this->display_info( $error, 'error' );

					// Redirect to checkout page.
					return $this->novalnet_redirect();
				}
			}

			if ( ! empty( $session_sepa['novalnet_sepa_one_click_shop_process'] ) && 'true' === $session_sepa['novalnet_sepa_one_click_shop_process'] && WC()->session->__isset( 'novalnet_sepa_reference_tid' ) ) {
				$session_sepa ['novalnet_sepa_reference_tid'] = WC()->session->novalnet_sepa_reference_tid;
			} elseif ( empty( $session_sepa['novalnet_sepa_one_click_shop_process'] ) || ( 'true' !== $session_sepa['novalnet_sepa_one_click_shop_process'] ) ) {
				$session_sepa ['novalnet_sepa_reference_tid'] = false;
				$session_sepa ['novalnet_sepa_payment_form']  = true;

				// Check SEPA details.
				if ( ! novalnet_instance()->novalnet_functions()->validate_payment_input_field( $session_sepa, array(
					'novalnet_sepa_hash',
					'novalnet_sepa_unique_id',
					'novalnet_sepa_account_holder',
				) ) || preg_match( '/[#%\^<>@$=*!]/', $session_sepa ['novalnet_sepa_account_holder'] ) ) {
					WC()->session->__unset( $this->id );

					// Display message.
					$this->display_info( __( 'Your account details are invalid', 'wc-novalnet' ), 'error' );

					// Redirect to checkout page.
					return $this->novalnet_redirect();
				}
			} else {
				$session_sepa ['novalnet_sepa_payment_form'] = true;
				WC()->session->__unset( 'novalnet_sepa_reference_tid' );
			}

			// Fraud module validation.
			if ( ! WC()->session->__isset( 'novalnet_sepa_guarantee_payment' ) && $validate_fraud_module && empty( $session_sepa ['novalnet_sepa_reference_tid'] ) ) {

				$error = novalnet_instance()->novalnet_functions()->validate_callback_fields( $session_sepa [ 'novalnet_sepa_pin_by_' . $this->settings ['fraud_module'] ], $this->id, $this->settings ['fraud_module'] );
				$session_sepa [ 'novalnet_sepa_pin_by_' . $this->settings ['fraud_module'] ] = $session_sepa [ 'novalnet_sepa_pin_by_' . $this->settings ['fraud_module'] ];
			}
		}

		// Validate error.
		if ( '' !== $error ) {
			WC()->session->__unset( $this->id );

			// Display message.
			$this->display_info( $error, 'error' );

			// Redirect to checkout page.
			return $this->novalnet_redirect();
		}

		WC()->session->set( $this->id, $session_sepa );
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
		if ( WC()->session->__isset( 'novalnet_sepa_tid' ) ) {
			$pin_status = $this->process_pin_call();
			if ( '100' !== $pin_status ['status'] ) {

				// Display message.
				$message = 'success';
				if ( '0529009' !== $pin_status ['status'] ) {
					$message = 'error';
				}
				$this->display_info( wc_novalnet_response_text( $pin_status ), $message );
				 return $this->novalnet_redirect();
			}
			$server_response                  = WC()->session->novalnet;
			$server_response ['tid_status'] = $pin_status ['tid_status'];
		} else {
			$params = $this->form_payment_params( $wc_order );
			if ( WC()->session->__isset( 'novalnet_sepa_guarantee_payment' ) && WC()->session->novalnet_sepa_guarantee_payment ) {

				// Assign guarantee parameters.
				$params = array_merge( $params, wc_novalnet_get_payment_type( 'guarantee_novalnet_sepa' ) );
				$params ['birth_date']   = WC()->session->novalnet_sepa ['novalnet_sepa_dob'];
			}

			// Perform Novalnet server call.
			$server_response = $this->perform_payment_call( $params, $this->paygate_url );
			// Assign time limit and tid in session.
			if ( $this->success_status( $this->id, $server_response ) && WC()->session->__isset( 'novalnet_sepa_fraud_check_validate' ) ) {
				return $this->set_time_limit( $server_response );
			}
		}

		// Checks transaction status.
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
	 * Form gateway parameters to process in the Novalnet server.
	 *
	 * @param WC_Order $wc_order the order object.
	 *
	 * @return array
	 */
	public function form_payment_params( $wc_order ) {
		global $current_user;

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

		// Sepa due date value assigned as configured.
		if ( wc_novalnet_digits_check( $this->settings ['sepa_payment_duration'] ) && $this->settings ['sepa_payment_duration'] >= 7 ) {
			$params ['sepa_due_date'] = wc_novalnet_format_due_date( $this->settings ['sepa_payment_duration'] );
		}

		if ( ! empty( WC()->session->novalnet_sepa ['novalnet_sepa_reference_tid'] ) ) {
			$params ['payment_ref'] = WC()->session->novalnet_sepa ['novalnet_sepa_reference_tid'];
		} else {
			$params ['bank_account_holder'] = WC()->session->novalnet_sepa ['novalnet_sepa_account_holder'];
			$params ['sepa_hash']           = WC()->session->novalnet_sepa ['novalnet_sepa_hash'];
			$params ['sepa_unique_id']      = WC()->session->novalnet_sepa ['novalnet_sepa_unique_id'];
			$params ['iban_bic_confirmed']  = 1;
			// Check for auto refill.
			if ( $this->settings['auto_refill'] ) {
				WC()->session->sepa_hash = $params ['sepa_hash'];
			}
			if ( empty( WC()->session->novalnet_change_payment_method ) ) {

				// Check for reference transaction.
				if ( empty( WC()->session->novalnet_change_payment_method ) && 'one_click_shop' === $this->settings ['payment_process'] && ! empty( $current_user->ID ) ) {
					$params ['create_payment_ref'] = '1';
				}

				// Assign zero amount booking.
				if ( empty( $params['tariff_period'] ) && 'zero_amount_book' === $this->settings['payment_process'] && ! WC()->session->__isset( 'novalnet_sepa_guarantee_payment' ) ) {
					$params ['create_payment_ref'] = '1';
					$this->assign_zero_amount( $params );
				}
			}

			// Checks & Assign Fraud module parameters.
			if ( WC()->session->__isset( 'novalnet_sepa_fraud_check_validate' ) && ! WC()->session->__isset( 'novalnet_sepa_guarantee_payment' ) ) {
				$this->form_fraud_module_params( $params, WC()->session->novalnet_sepa [ 'novalnet_sepa_pin_by_' . $this->settings ['fraud_module'] ] );
			}
		}

		// Log to notify payment parameters formed successfully.
		if ( $this->global_settings ['debug_log'] ) {
			$this->novalnet_log->add( 'novalnetpayments', 'Payment parameters formed successfully for the payment' . $this->id );
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

		// Shopping type configurations.
		$this->shopping_type_payment_config( $this->form_fields );

		// SEPA due date configuration.
		$this->form_fields ['sepa_payment_duration'] = array(
			'title'       => __( 'SEPA payment duration (in days)', 'wc-novalnet' ),
			'type'        => 'text',
			'description' => __( 'Enter the number of days after which the payment should be processed (must be greater than 6 days)', 'wc-novalnet' ),
			'desc_tip'    => true,
			'custom_attributes' => array(
				'autocomplete' => 'OFF',
			),
		);

		// Refill payment data configuration.
		$this->form_fields ['payment_refill'] = array(
		 'title'       => __( 'Enable auto-fill for payment data', 'wc-novalnet' ),
		 'type'        => 'select',
		 'options'     => array(
		  '0' => __( 'No', 'wc-novalnet' ),
		  '1' => __( 'Yes', 'wc-novalnet' ),
		 ),
		 'description' => __( 'For the registered users SEPA direct debit details will be filled automatically in the payment form', 'wc-novalnet' ),
		 'desc_tip'    => true,
		);

		$this->form_fields ['auto_refill'] = array(
		 'title'       => __( 'Enable auto-fill', 'wc-novalnet' ),
		 'type'        => 'select',
		 'options'     => array(
		  '0' => __( 'No', 'wc-novalnet' ),
		  '1' => __( 'Yes', 'wc-novalnet' ),
		 ),
		 'description' => __( 'The payment details will be filled automatically in the payment form during the checkout process', 'wc-novalnet' ),
		 'desc_tip'    => true,
		);

		// Fraud module configurations.
		$this->fraud_module_config( $this->form_fields );

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

		novalnet_instance()->novalnet_functions()->validate_guarantee_process( $this->id );

		$request = $_REQUEST; // input var okay.

		if ( wc_novalnet_check_admin() && ! empty( $request ['woocommerce_novalnet_sepa_sepa_payment_duration'] ) && ( $request ['woocommerce_novalnet_sepa_sepa_payment_duration'] < 7 ) ) {
			WC_Admin_Meta_Boxes::add_error( __( 'SEPA Due date is not valid', 'wc-novalnet' ) );

			wc_novalnet_safe_redirect( admin_url( 'admin.php?page=wc-settings&tab=checkout&section=' . $request ['section'] ) );
		}
	}
}
