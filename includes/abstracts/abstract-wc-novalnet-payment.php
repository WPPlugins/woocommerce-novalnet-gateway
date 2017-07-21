<?php
/**
 * Novalnet Payment Gateway class.
 *
 * Extended by individual payment gateways to handle payments.
 *
 * @class    NN_Payment_Gateways
 * @extends  WC_Payment_Gateway
 * @version  11.2.0
 * @package  Novalnet-gateway/Abstracts
 * @category Abstract Class
 * @author   Novalnet
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * NN_Payment_Gateways Abstract Class.
 */
abstract class NN_Payment_Gateways extends WC_Payment_Gateway {


	/**
	 * Maintain Log object.
	 *
	 * @var obj
	 */
	public $novalnet_log;

	/**
	 * Perform the payment call to Novalnet server.
	 *
	 * @since 11.0.0
	 * @param array  $request_param The request parameters.
	 * @param string $url           The request url.
	 *
	 * @return array
	 */
	public function perform_payment_call( $request_param, $url = 'https://payport.novalnet.de/paygate.jsp' ) {

		// Log to maintain payment call process.
		$this->maintain_debug_log( "Initiate payment call to $url" );

		// Submit the given request and convert the query string to array.
		return novalnet_instance()->novalnet_functions()->submit_request( $request_param,  $url );
	}

	/**
	 * Perform encoding process for the given data.
	 *
	 * @since 10.0.0
	 * @param array  $data The encode values.
	 * @param string $key  The payment access key value.
	 *
	 * @return string
	 */
	public function encode_data( $data, $key ) {

		try {
			$crc = sprintf( '%u', crc32( $data ) );
			$data = bin2hex( $crc . '|' . $data . $key );
			$data = strrev( base64_encode( $data ) );
		} catch ( Exception $e ) {

			// Error log for the exception.
			$this->novalnet_log->add( 'novalneterrorlog', 'Encode error occured: ' . $e->getMessage() );
		}
		return $data;
	}

	/**
	 * Perform decoding process for the given data.
	 *
	 * @since 10.0.0
	 * @param string $data The decode values.
	 * @param string $key  The payment access key value.
	 *
	 * @return string|int
	 */
	public function decode_data( $data, $key ) {

		try {
			$data = base64_decode( strrev( $data ) );
			$data = pack( 'H' . strlen( $data ), $data );

			// Using payment access key.
			$data  = substr( $data, 0, stripos( $data, $key ) );
			$pos   = strpos( $data, '|' );
			$value = trim( substr( $data, $pos + 1 ) );
		} catch ( Exception $e ) {

			// Error log for the exception.
			$this->novalnet_log->add( 'novalneterrorlog', 'Decode error occured: ' . $e->getMessage() );
		}
		return $value;
	}

	/**
	 * Fetch all the global configuration values from the database.
	 *
	 * @since 11.0.0
	 *
	 * @return array
	 */
	public function global_configurations() {

		return array_merge(
			novalnet_instance()->novalnet_functions()->get_basic_vendor_details(), array(
			'key_password'          => get_option( 'novalnet_key_password' ),
			'manual_limit'          => novalnet_instance()->novalnet_functions()->get_manual_check_limit(),
			'payment_logo'          => get_option( 'novalnet_payment_logo' ),
			'referrer_id'           => get_option( 'novalnet_referrer_id' ),
			'notify_url'            => get_option( 'novalnet_callback_notify_url' ),
			'debug_log'             => get_option( 'novalnet_debug_log' ),
			'callback_emailtoaddr'  => get_option( 'novalnet_callback_emailtoaddr' ),
			'callback_emailbccaddr' => get_option( 'novalnet_callback_emailbccaddr' ),
			)
		);
	}

	/**
	 * Return basic payment configurations fields.
	 *
	 * @since 11.0.0
	 *
	 * @return array
	 */
	public function basic_payment_config() {

		$payment_details    = wc_novalnet_payment_details( $this->id );
		return array(
			'enabled'         => array(
				'title'       => __( 'Enable payment method', 'wc-novalnet' ),
				'type'        => 'checkbox',
				'label'       => ' ',
			),
			'title_en'        => array(
				'title'       => __( 'Payment title in English', 'wc-novalnet' ),
				'type'        => 'text',
				'description' => '',
				'default'     => $payment_details ['title_en'],
				'custom_attributes' => array(
					'autocomplete' => 'OFF',
				),
			),
			'description_en'  => array(
				'title'       => __( 'Description in English', 'wc-novalnet' ),
				'type'        => 'textarea',
				'description' => '',
				'default'     => $payment_details ['description_en'],
			),
			'title_de'        => array(
				'title'       => __( 'Payment title in German', 'wc-novalnet' ),
				'type'        => 'text',
				'description' => '',
				'default'     => $payment_details ['title_de'],
				'custom_attributes' => array(
					'autocomplete' => 'OFF',
				),
			),
			'description_de'  => array(
				'title'       => __( 'Description in German', 'wc-novalnet' ),
				'type'        => 'textarea',
				'description' => '',
				'default'     => $payment_details ['description_de'],
			),
			'test_mode' => array(
				'title'       => __( 'Enable test mode', 'wc-novalnet' ),
				'type'        => 'select',
				'options'     => array(
					'0' => __( 'No', 'wc-novalnet' ),
					'1' => __( 'Yes', 'wc-novalnet' ),
				),
				'description' => __( 'The payment will be processed in the test mode therefore amount for this transaction will not be charged', 'wc-novalnet' ),
				'desc_tip'    => true,
				'default'     => '0',
			),
		);
	}

	/**
	 * Return fraud module payment configurations fields.
	 *
	 * @since 11.0.0
	 *
	 * @param array $form_fields The form fields.
	 */
	public function fraud_module_config( &$form_fields ) {

		$form_fields ['fraud_module']   = array(
			'title'       => __( 'Enable fraud prevention', 'wc-novalnet' ),
			'type'        => 'select',
			'options'     => array(
				''       => __( 'None', 'wc-novalnet' ),
				'tel'    => __( 'PIN by callback', 'wc-novalnet' ),
				'mobile' => __( 'PIN by SMS', 'wc-novalnet' ),
			),
			'description' => __( 'To authenticate the buyer for a transaction, the PIN will be automatically generated and sent to the buyer. This service is only available for customers from DE, AT, CH', 'wc-novalnet' ),
			'desc_tip'    => true,
			'default'     => '0',
		);
		$form_fields ['pin_amt_limit'] = array(
			'title'       => __( 'Minimum value of goods for the fraud module', 'wc-novalnet' ),
			'type'        => 'text',
			'description' => __( '(in minimum unit of currency. E.g. enter 100 which is equal to 1.00)', 'wc-novalnet' ),
			'desc_tip'    => __( 'Enter the minimum value of goods from which the fraud module should be activated', 'wc-novalnet' ),
			'custom_attributes' => array(
				'autocomplete' => 'OFF',
			),
		);
	}

	/**
	 * Shopping type payment configurations fields.
	 *
	 * @since 11.0.0
	 *
	 * @param array $form_fields The form fields.
	 */
	public function shopping_type_payment_config( &$form_fields ) {

		$form_fields ['payment_process'] = array(
		 'title'       => __( 'Shopping type', 'wc-novalnet' ),
		 'type'        => 'select',
		 'options'     => array(
		  'none'             => __( 'Select shopping type', 'wc-novalnet' ),
		  'one_click_shop'   => __( 'One click shopping', 'wc-novalnet' ),
		  'zero_amount_book' => __( 'Zero amount booking', 'wc-novalnet' ),
		 ),
		 'description' => __( '<span id="novalnet_reference_alert" style="color:red;"></span>', 'wc-novalnet' ),
		);
	}

	/**
	 * Pending status payment configurations fields.
	 *
	 * @since 11.0.0
	 *
	 * @param array $form_fields The form fields.
	 */
	public function pending_status_payment_config( &$form_fields ) {

		$form_fields ['pending_status'] = array(
			'title'   => __( 'Order status for the pending payment', 'wc-novalnet' ),
			'type'    => 'select',
			'default' => wc_novalnet_format_default_order_status( 'on-hold' ),
			'options' => wc_novalnet_get_shop_order_status(),
		);
	}

	/**
	 * Return other payment configuration fields.
	 *
	 * @since 11.0.0
	 *
	 * @param array $form_fields The form fields.
	 */
	public function other_payment_config( &$form_fields ) {

		$form_fields ['order_success_status'] = array(
			'title'       => __( 'Order completion status ', 'wc-novalnet' ),
			'type'        => 'select',
			'default'     => wc_novalnet_format_default_order_status( 'processing' ),
			'options'     => wc_novalnet_get_shop_order_status(),
			'description' => '',
		);
		$form_fields ['payment_instruction'] = array(
			'title'       => __( 'Notification for the buyer', 'wc-novalnet' ),
			'type'        => 'textarea',
			'description' => __( 'The entered text will be displayed on the checkout page', 'wc-novalnet' ),
			'desc_tip'    => true,
		);
		$form_fields ['instructions']  = array(
			'title'       => __( 'Thank you page instructions', 'wc-novalnet' ),
			'type'        => 'textarea',
		);
		$form_fields ['email_notes']   = array(
			'title' => __( 'E-mail instructions', 'wc-novalnet' ),
			'type'  => 'textarea',
		);
		$form_fields ['min_amount'] = array(
			'title'       => __( 'Minimum value of goods', 'wc-novalnet' ),
			'type'        => 'text',
			'desc_tip'    => __( 'Enter the minimum value of goods from which the payment method is displayed to the customer during checkout', 'wc-novalnet' ),
			'description' => __( '(in minimum unit of currency. E.g. enter 100 which is equal to 1.00)', 'wc-novalnet' ),
			'custom_attributes' => array(
				'autocomplete' => 'OFF',
			),
		);
		$form_fields ['reference1']    = array(
			'title'       => __( 'Transaction reference 1', 'wc-novalnet' ),
			'type'        => 'text',
			'description' => __( 'This reference will appear in your bank account statement', 'wc-novalnet' ),
			'desc_tip'    => true,
			'custom_attributes' => array(
				'autocomplete' => 'OFF',
			),
		);
		$form_fields ['reference2']    = array(
			'title'       => __( 'Transaction reference 2', 'wc-novalnet' ),
			'type'        => 'text',
			'description' => __( 'This reference will appear in your bank account statement', 'wc-novalnet' ),
			'desc_tip'    => true,
			'custom_attributes' => array(
				'autocomplete' => 'OFF',
			),
		);
	}

	/**
	 * Guarantee payment configuration fields.
	 *
	 * @since 11.0.0
	 *
	 * @param array $form_fields The form fields.
	 */
	public function guarantee_payment_config( &$form_fields ) {

		$form_fields ['guarantee_payment_title'] = array(
			'title' => __( 'Payment guarantee configuration', 'wc-novalnet' ),
			'type' => 'title',
			'description' => sprintf('<strong>%1$s</strong><br/>
			<ul>
				<li>%2$s</li>
				<li>%3$s</li>
				<li>%4$s</li>
				<li>%5$s</li>
				<li>%6$s</li>
				<li>%7$s</li>
				<li>%8$s</li>
			</ul>', __( 'Basic requirements for payment guarantee', 'wc-novalnet' ), __( 'Allowed countries: AT, DE, CH', 'wc-novalnet' ), __( 'Allowed currency: EUR', 'wc-novalnet' ), __( 'Minimum amount of order >= 20,00 EUR', 'wc-novalnet' ), __( 'Maximum amount of order <= 5.000,00 EUR', 'wc-novalnet' ), __( 'Minimum age of end customer >= 18 Years', 'wc-novalnet' ), __( 'The billing address must be the same as the shipping address', 'wc-novalnet' ), __( 'Gift certificates/vouchers are not allowed', 'wc-novalnet' ) ),
		);

		$form_fields ['guarantee_payment'] = array(
			'title'       => __( 'Enable payment guarantee', 'wc-novalnet' ),
			'type'        => 'checkbox',
			'label'       => ' ',
		);

		$form_fields ['guarantee_payment_minimum_order_amount'] = array(
			'title'       => __( 'Minimum order amount', 'wc-novalnet' ),
			'type'        => 'text',
			'description' => __( '(in minimum unit of currency. E.g. enter 100 which is equal to 1.00)', 'wc-novalnet' ),
			'desc_tip' => __( 'This setting will override the default setting made in the minimum order amount. Note that amount should be in the range of 20,00 EUR - 5.000,00 EUR', 'wc-novalnet' ),
			'custom_attributes' => array(
				'autocomplete' => 'OFF',
			),
		);

		$form_fields ['guarantee_payment_maximum_order_amount'] = array(
			'title'       => __( 'Maximum order amount', 'wc-novalnet' ),
			'type'        => 'text',
			'description' => __( '(in minimum unit of currency. E.g. enter 100 which is equal to 1.00)', 'wc-novalnet' ),
			'desc_tip'    => __( 'This setting will override the default setting made in the maximum order amount. Note that amount should be greater than minimum order amount, but not more than 5.000,00 EUR', 'wc-novalnet' ),
			'custom_attributes' => array(
				'autocomplete' => 'OFF',
			),
		);

		// Non-Guarantee payment force field.
		$form_fields ['force_normal_payment'] = array(
			'title'       => __( 'Force Non-Guarantee payment', 'wc-novalnet' ),
			'type'        => 'checkbox',
			'default'     => 'yes',
			'label'       => __( 'If the payment guarantee is activated (True), but the above mentioned requirements are not met, the payment should be processed as non-guarantee payment.', 'wc-novalnet' ),
		);

		wc_enqueue_js( "
			jQuery( document ).ready(function () {
				jQuery( '#woocommerce_" . $this->id . "_guarantee_payment' ).live( 'change', function() {
					if ( jQuery( '#woocommerce_" . $this->id . "_guarantee_payment' ).is( ':checked' ) ) {
						jQuery( '#woocommerce_" . $this->id . "_force_normal_payment').closest( 'tr' ).show();
						jQuery( '#woocommerce_" . $this->id . "_guarantee_payment_minimum_order_amount').closest( 'tr' ).show();
						jQuery( '#woocommerce_" . $this->id . "_guarantee_payment_maximum_order_amount').closest( 'tr' ).show();
					} else {
						jQuery( '#woocommerce_" . $this->id . "_force_normal_payment').closest( 'tr' ).hide();
						jQuery( '#woocommerce_" . $this->id . "_guarantee_payment_minimum_order_amount').closest( 'tr' ).hide();
						jQuery( '#woocommerce_" . $this->id . "_guarantee_payment_maximum_order_amount').closest( 'tr' ).hide();
					}
				}).change();
			});
		" );
	}

	/**
	 * Return Invoice / Prepayment payments configurations fields.
	 *
	 * @since 11.0.0
	 *
	 * @param array $form_fields The form fields.
	 */
	public function invoice_payments_config( &$form_fields ) {

		$form_fields ['payment_reference_1'] = array(
		 'title'   => __( 'Payment Reference 1 (Novalnet Invoice Reference)', 'wc-novalnet' ),
		 'type'    => 'checkbox',
		 'label'   => ' ',
		 'default' => 'yes',
		);
		$form_fields ['payment_reference_2'] = array(
		 'title'   => __( 'Payment Reference 2 (TID)', 'wc-novalnet' ),
		 'type'    => 'checkbox',
		 'label'   => ' ',
		 'default' => 'yes',
		);
		$form_fields ['payment_reference_3'] = array(
		 'title'   => __( 'Payment Reference 3 (Order No)', 'wc-novalnet' ),
		 'type'    => 'checkbox',
		 'label'   => ' ',
		 'default' => 'yes',
		);
		$form_fields ['callback_status'] = array(
		 'title'   => __( 'Callback order status', 'wc-novalnet' ),
		 'type'    => 'select',
		 'default' => wc_novalnet_format_default_order_status( 'completed' ),
		 'options' => wc_novalnet_get_shop_order_status(),
		);
	}

	/**
	 * Built logo with link to display in front-end.
	 *
	 * @since 11.0.0
	 * @param boolean $logo_enabled The logo enabled value.
	 * @param string  $logo         The logo value.
	 * @param string  $title        The title value.
	 *
	 * @return string
	 */
	public function built_logo( $logo_enabled, $logo, $title ) {

		$href_link = __( 'http://www.novalnet.com', 'wc-novalnet' );
		$plugin_url = novalnet_instance()->plugin_url() . '/assets/images/' . $logo . '.png';
		$logo_href = '';
		if ( $logo_enabled ) {
			$logo_href = "<a href='$href_link' target='_blank'><img src='$plugin_url' alt='$title' title='$title' /></a>";
		}
		return $logo_href;
	}

	/**
	 * Forming basic params to process payment in Novalnet server.
	 *
	 * @since 10.0.0
	 * @param WC_Order $wc_order           The order object.
	 * @param array    $config             The config values.
	 * @param string   $payment_type       The payment type value.
	 * @param boolean  $admin_subscription Checks for admin processed order.
	 *
	 * @return array
	 */
	public function generate_payment_parameters( $wc_order, $config, $payment_type, $admin_subscription = false ) {

		// Customize order details to process server request.
		$order_total        = novalnet_instance()->novalnet_functions()->get_novalnet_order_total( $wc_order );
		$order_amount       = wc_novalnet_formatted_amount( $order_total );
		$order_no           = novalnet_instance()->novalnet_functions()->get_order_post_id( $wc_order );
		$wc_order           = new WC_Order( $order_no );
		$language           = strtoupper( $this->language );
		$is_change_payment  = $admin_subscription;

		// Get formated order number.
		$formatted_order_no = ltrim( $wc_order->get_order_number(), _x( '#', 'hash before order number', 'woocommerce' ) );

		// Form customer details parameters.
		$customer_parameters = novalnet_instance()->novalnet_functions()->form_user_payment_parameter( $wc_order );

		// Assign test mode value as 1/ 0 based on configuration value.
		$test_mode = (int) $config ['test_mode'];

		// Get payment key and type.
		$payment_details = wc_novalnet_get_payment_type( $payment_type );

		if ( ! $admin_subscription ) {

			// Check for affiliate.
			wc_novalnet_process_affiliate_action( $config );

			// Set current payment method in session.
			WC()->session->set( 'current_novalnet_payment', $payment_type );

			// Check for change payment method.
			$is_change_payment = WC()->session->__isset( 'novalnet_change_payment_method' );

			if ( ! novalnet_instance()->novalnet_functions()->validate_customer_parameters( $customer_parameters ) ) {
				$this->display_info( __( 'Customer name/email fields are not valid', 'wc-novalnet' ), 'error' );
				wc_novalnet_safe_redirect();
			}
		}

		// Form vendor parameters.
		$vendor_parameters = array(
			'vendor'         => $config ['vendor_id'],
			'auth_code'      => $config ['auth_code'],
			'product'        => $config ['product_id'],
			'key'            => $payment_details ['key'],
			'payment_type'   => $payment_details ['payment_type'],
			'tariff'         => $config ['tariff_id'],
			'test_mode'      => $test_mode,
		);

		// Form order details parameters.
		$order_parameters = array(
			'currency'       => get_woocommerce_currency(),
			'lang'           => $language,
			'remote_ip'      => wc_novalnet_get_ip_address(),
			'amount'         => $order_amount,
			'order_no'       => $formatted_order_no,
			'system_name'    => 'wordpress-woocommerce',
			'system_version' => get_bloginfo( 'version' ) . '-' . WOOCOMMERCE_VERSION . '-NN' . NN_VERSION,
			'system_url'     => site_url(),
			'system_ip'      => wc_novalnet_get_ip_address( 'SERVER_ADDR' ),
			'language'       => $language,
		);

		// Form additional parameters.
		$additional_parameters = array();

		// Append Company parameter.
		$additional_parameters ['company'] = trim( $customer_parameters['company'] );

		// Append Referrer ID.
		$additional_parameters ['referrer_id'] = wc_novalnet_digits_check( $config ['referrer_id'] );

		// Append Notification URL.
		$additional_parameters ['notify_url'] = trim( $config ['notify_url'] );

		// Check and append on-hold parameter.
		$additional_parameters ['on_hold'] = (int) ( ! $is_change_payment && in_array( $payment_type, array( 'novalnet_invoice', 'novalnet_cc', 'novalnet_sepa', 'novalnet_paypal' ), true ) && novalnet_instance()->novalnet_functions()->manual_limit_check( $order_amount, $config['manual_limit'] ) );

		// Add transaction reference.
		$this->add_transaction_reference( $additional_parameters, $config );

		// Combine formed parameters.
		$parameters = array_merge( $vendor_parameters, $customer_parameters, $order_parameters, array_filter( $additional_parameters ) );

		// Get Subscription details parameters if available.
		$parameters = apply_filters( 'novalnet_form_subscription_parameters', $parameters, $wc_order, $config ['subs_tariff_id'], $config ['enable_subs'], $is_change_payment );

		// Forming subscription parameters if enabled.
		return array(
			'payment_parameters' => $parameters,
			'payment_access_key' => $config ['key_password'],
		);
	}


	/**
	 * Add transaction reference.
	 *
	 * @since 11.0.0
	 *
	 * @param array $formed_params The formed parameters.
	 * @param array $config        The global configurations array.
	 */
	public function add_transaction_reference( &$formed_params, $config ) {

		// Transaction reference 1.
		if ( $reference1 = sanitize_text_field( $config ['reference1'] ) ) {
			$formed_params ['inputval1'] = $reference1;
			$formed_params ['input1'] = 'Reference1';
		}

		// Transaction reference 2.
		if ( $reference2 = sanitize_text_field( $config ['reference2'] ) ) {
			$formed_params ['inputval2'] = $reference2;
			$formed_params ['input2'] = 'Reference2';
		}
	}

	/**
	 * Assigning zero amount and storing The formed
	 * parameters in session to store in database.
	 *
	 * @since 11.0.0
	 *
	 * @param array $formed_params The formed parameters.
	 */
	public function assign_zero_amount( &$formed_params ) {

		unset( $formed_params ['on_hold'] );

		// Get needed payment parameters.
		$payment_params = $formed_params;
		unset( $payment_params ['create_payment_ref'] );

		// Get payment session and store the serialized payment parameters.
		$payment_session = WC()->session->get( $this->id );
		$payment_session ['payment_params'] = wc_novalnet_serialize_data( $payment_params );

		// Set assigned payment session.
		WC()->session->set( $this->id, $payment_session );

		// Assign amount as zero.
		$formed_params ['amount'] = '0';

	}

	/**
	 * Encoding required params and generating
	 * the hash and form basic redirect payment params.
	 *
	 * @since 11.0.0
	 *
	 * @param array    $data               The encode values.
	 * @param WC_Order $wc_order           The WC_Order.
	 * @param string   $payment_access_key The payment access key value.
	 */
	public function redirect_payment_params( &$data, $wc_order, $payment_access_key ) {

		// Customize the shop return URL's based on payment process type.
		$shop_return_url       = apply_filters( 'novalnet_return_url', $this->get_return_url( $wc_order ) );
		$shop_error_return_url = apply_filters( 'novalnet_error_return_url', $this->get_return_url( $wc_order ) );

		// Form redirected parameters.
		$data ['uniqid']           = uniqid();
		$data ['return_method']    = $data ['error_return_method'] = 'POST';

		if ( 'novalnet_cc' !== $this->id ) {
			$data ['user_variable_0']  = site_url();
		}
		$data ['implementation']   = 'PHP';
		$data ['return_url']       = esc_url( add_query_arg( 'wc-api', 'response_' . $this->id, $shop_return_url ) );
		$data ['error_return_url'] = esc_url( add_query_arg( 'wc-api', 'response_' . $this->id, $shop_error_return_url ) );
		$novalnet_order_id = novalnet_instance()->novalnet_functions()->get_novalnet_id( $wc_order );

		// Send order numebr in input value.
		$data ['input3']    = 'nn_shopnr';
		$data ['inputval3'] = $novalnet_order_id;

		// Perform data encode.
		foreach ( array(
		 'auth_code',
		 'product',
		 'tariff',
		 'amount',
		 'test_mode',
		 'uniqid',
		) as $key ) {
			$data [ $key ] = $this->encode_data( $data [ $key ], $payment_access_key );
		}

		// Generate hash.
		$data ['hash']   = wc_novalnet_generate_hash( $data, $payment_access_key );
	}

	/**
	 * Show guarantee payment
	 * module visiblity process.
	 *
	 * @since 11.0.0
	 */
	public function show_guarantee_payment() {

		$order_amount = wc_novalnet_formatted_amount( WC()->session->total );

		// Billing address.
		$billing_address = novalnet_instance()->novalnet_functions()->get_novalnet_gurantee_billing_detail();

		// Shipping address.
		$shipping_address = array(
			'country' 	=> WC()->customer->get_shipping_country(),
			'post_code' => WC()->customer->get_shipping_postcode(),
			'city' 		=> WC()->customer->get_shipping_city(),
			'address'   => WC()->customer->get_shipping_address(),
			'address2'  => WC()->customer->get_shipping_address_2(),
		);

		// Default value.
		$minimum_amount = 2000;
		$maximum_amount = 500000;

		if ( $this->settings ['guarantee_payment_minimum_order_amount'] ) {
			$minimum_amount = $this->settings ['guarantee_payment_minimum_order_amount'];
		}
		if ( $this->settings ['guarantee_payment_maximum_order_amount'] ) {
			$maximum_amount = $this->settings ['guarantee_payment_maximum_order_amount'];
		}

		// Payment guarantee process.
		if ( 'yes' === $this->settings ['guarantee_payment'] ) {

			$billing_details = novalnet_instance()->novalnet_functions()->get_novalnet_country();
			if ( in_array( $billing_details, array( 'AT', 'DE', 'CH' ), true ) && 'EUR' === get_woocommerce_currency() && $billing_address === $shipping_address && ( $order_amount >= $minimum_amount && $order_amount <= $maximum_amount ) ) {

				// Process as guarantee payment.
				WC()->session->set( $this->id . '_guarantee_payment', true );
				WC()->session->__unset( $this->id . '_guarantee_payment_error' );
			} elseif ( 'yes' === $this->settings ['force_normal_payment'] ) {

				// Process as normal payment.
				WC()->session->__unset( $this->id . '_guarantee_payment' );
				WC()->session->__unset( $this->id . '_guarantee_payment_error' );
			} else {

				// Show error on payment field/ checkout.
				WC()->session->__unset( $this->id . '_guarantee_payment' );
				WC()->session->set( $this->id . '_guarantee_payment_error', true );
			}
		} else {

			// Process as normal payment.
			WC()->session->__unset( $this->id . '_guarantee_payment_error' );
			WC()->session->__unset( $this->id . '_guarantee_payment' );
		}

	}

	/**
	 * Check for the success status of the
	 * Novalnet payment call.
	 *
	 * @since 11.0.0
	 *
	 * @param string $payment_type The payment type value.
	 * @param array  $data         The given array.
	 *
	 * @return boolean
	 */
	public function success_status( $payment_type, $data ) {

		return wc_novalnet_status_check( $data ) || ( 'novalnet_paypal' === $payment_type &&  wc_novalnet_status_check( $data, 'status', '90' ) );
	}

	/**
	 * Assigning the shop order process based on the
	 * Novalnet server response whether success / failure.
	 *
	 * @since 11.0.0
	 * @param string   $server_response The server response data.
	 * @param WC_Order $wc_order        The order object.
	 * @param string   $payment_type    The payment type value.
	 *
	 * @return array|string
	 */
	public function check_transaction_status( $server_response, $wc_order, $payment_type ) {

		$novalnet_order_id = novalnet_instance()->novalnet_functions()->get_novalnet_id( $wc_order );

		// Log to notify server call return response.
		$this->maintain_debug_log( 'Response successfully reached to shop for the order: ' . $novalnet_order_id );
		if ( $this->success_status( $payment_type, $server_response ) ) {
			return $this->transaction_success( $server_response, $wc_order, $payment_type );
		}
		return $this->transaction_failure( $server_response, $wc_order, $payment_type );
	}

	/**
	 * Transaction success process for completing the order.
	 *
	 * @since 10.0.0
	 * @param array    $server_response The server response data.
	 * @param WC_Order $wc_order        The order object.
	 * @param string   $payment_type    The payment type value.
	 *
	 * @return array|string
	 */
	public function transaction_success( $server_response, $wc_order, $payment_type ) {
		$session_values    = array();
		$payment_param     = '';

		// Check for change payment method process.
		$is_change_payment = WC()->session->__isset( 'novalnet_change_payment_method' );

		// Retrieve vendor details.
		$vendor_details = novalnet_instance()->novalnet_functions()->get_basic_vendor_details();

		// Get post ID of the Parent order.
		$post_id = novalnet_instance()->novalnet_functions()->get_order_post_id( $wc_order );

		// Handle session and affiliate process.
		novalnet_instance()->novalnet_functions()->update_payment_process_details( $wc_order, $payment_type, $vendor_details, $payment_param, $session_values, $server_response ['order_no'] );

		$settings  = wc_novalnet_payment_config( $payment_type );

		$test_mode = ( ! empty( $server_response ['test_mode'] ) || $settings ['test_mode'] );

		// Form order comments.
		list( $tid_details, $transaction_comments, $bank_details ) = $this->prepare_payment_comments( $server_response, $payment_type, $vendor_details ['product_id'], $settings, $test_mode );

		$transaction_comments = $tid_details . $transaction_comments;

		// Fetch transaction tariff id.
		$tariff = $vendor_details ['tariff_id'];

		update_post_meta( $post_id, '_novalnet_gateway_status', $server_response['tid_status'] );
		$subs_id = '';

		if ( $is_subscription = ! empty( $server_response ['subs_id'] ) ) {
			$subs_id = $server_response ['subs_id'];
		}

		// Request sent to process change payment method in Novalnet server.
		if ( $is_change_payment ) {

			$subscription_details = wc_novalnet_get_subs_details( '', $post_id );

			// Check for recurring payment type available in Novalnet table for the payment.
			if ( ! empty( $subscription_details ['recurring_payment_type'] ) ) {

				// Update recurring payment process.
				novalnet_instance()->novalnet_functions()->update_recurring_payment( $post_id, $server_response, $payment_type, $settings, $this->language );

				// Update recurring payment details in Novalnet subscription details.
				wc_novalnet_db_update_query( array(
					'recurring_payment_type' => $payment_type,
					'subs_id'                => $subs_id,
					'recurring_tid'          => $server_response ['tid'],
					), array(
					'order_no' => $post_id,
				), 'novalnet_subscription_details' );

				$novalnet_order_id = novalnet_instance()->novalnet_functions()->get_novalnet_id( $wc_order );
				
				// Get subscription order object.
				$subscription_order = new WC_Order( $novalnet_order_id );

				// Update transaction comments.
				novalnet_instance()->novalnet_functions()->update_comments( $subscription_order, $tid_details, true, 'transaction_info' );

				// Update change payment method notice comments.
				novalnet_instance()->novalnet_functions()->update_comments( $subscription_order, wc_novalnet_format_text( sprintf( __( 'Successfully changed the payment method for next subscription on %s', 'wc-novalnet' ),  wc_novalnet_formatted_date() ) ) );

				// Unset the Novalnet sessions.
				wc_novalnet_unset_payment_session( $payment_type );

				$success_url = $this->get_return_url( $subscription_order );

				if ( 'novalnet_paypal' === $this->id || ( 'novalnet_cc' === $this->id && $this->settings ['cc_secure_enabled'] ) ) {

					// Get success URL for change payment method.
					$data = apply_filters( 'novalnet_subscription_change_payment_method_success_url', $this->get_return_url( $subscription_order ), $subscription_order );
					$success_url = $data ['success_url'];

					// Display shop change payment method notice.
					$this->display_info( $data ['notice'], 'success' );
				}

				return $this->novalnet_redirect( $success_url );
			}
		}

		// Update order comments.
		novalnet_instance()->novalnet_functions()->update_comments( $wc_order, $transaction_comments, true, 'transaction_info' );

		// Handle subscription process.
		$info = novalnet_instance()->novalnet_functions()->handle_subscription_post_process( $is_subscription, $post_id, $payment_type, $server_response, $wc_order, $vendor_details, $tid_details, $tariff );

		// Update order information.
		if ( ! empty( $info ) ) {
			novalnet_instance()->novalnet_functions()->update_comments( $wc_order, $info, true, 'note', false );
		}

		// Complete the payment process.
		novalnet_instance()->novalnet_functions()->complete_payment( $wc_order, $server_response ['tid'], $transaction_comments );

		// Converting the amount into cents.
		$order_total        = novalnet_instance()->novalnet_functions()->get_novalnet_order_total( $wc_order );
		$amount = wc_novalnet_formatted_amount( $order_total );
		$key = wc_novalnet_get_payment_type( $payment_type, 'key' );

		// Check for guarantee payment keys.
		if ( WC()->session->__isset( $payment_type . '_guarantee_payment' ) ) {
			$key = wc_novalnet_get_payment_type( 'guarantee_' . $payment_type, 'key' );
		}

		$this->display_info( wc_novalnet_response_text( $server_response ), 'success' );

		// Unset the Novalnet sessions.
		wc_novalnet_unset_payment_session( $payment_type );

		// Get book status and order amount.
		if ( $booked = (int) ( '' === $payment_param ) ) {
			$order_amount = $amount;
		} else {
			$order_amount = '0';
		}

		// Get callback amount.
		$callback_amount = $amount;
		if ( in_array( $payment_type, array( 'novalnet_invoice', 'novalnet_prepayment' ), true ) || in_array( $server_response ['tid_status'], array( '86', '90' ), true ) ) {
			$callback_amount = '0';
		}
		$customer_details = novalnet_instance()->novalnet_functions()->get_novalnet_billing_detail( $wc_order );

		$user_id = novalnet_instance()->novalnet_functions()->get_novalnet_user_id( $wc_order );
		// Insert the transaction details.
		wc_novalnet_db_insert_query(
			array(
			'order_no'               => $post_id,
			'order_number_formatted' => $server_response ['order_no'],
			'vendor_id'              => $vendor_details ['vendor_id'],
			'auth_code'              => $vendor_details ['auth_code'],
			'product_id'             => $vendor_details ['product_id'],
			'tariff_id'              => $tariff,
			'subs_id'                => $subs_id,
			'payment_id'             => $key,
			'payment_type'           => $payment_type,
			'tid'                    => $server_response ['tid'],
			'gateway_status'         => $server_response ['tid_status'],
			'amount'                 => $order_amount,
			'callback_amount'        => $callback_amount,
			'currency'               => get_woocommerce_currency(),
			'test_mode'              => (int) $test_mode,
			'customer_id'            => $user_id,
			'customer_email'         => $customer_details['billing_email'],
			'date'                   => date( 'Y-m-d H:i:s' ),
			'booked'                 => $booked,
			'payment_ref'            => (int) ! empty( $session_values [ $payment_type . '_reference_tid' ] ),
			'bank_details'           => $bank_details,
			'payment_params'         => $payment_param,
			), 'novalnet_transaction_detail'
		);

		// Update Novalnet version while processing the current post id.
		update_post_meta( $post_id, '_nn_version', NN_VERSION );

		// Log to notify order got success.
		$this->maintain_debug_log( "Transaction success process completed for the order $post_id TID:" . $server_response ['tid'] );

		return $this->novalnet_redirect( $this->get_return_url( $wc_order ) );
	}

	/**
	 * Check and maintain debug log if enabled
	 *
	 * @param string $message Message to be logged.
	 *
	 * @since 11.0.0
	 */
	public function maintain_debug_log( $message ) {

		if ( $this->global_settings ['debug_log'] ) {
			$this->novalnet_log->add( 'novalnetpayments', $message );
		}
	}

	/**
	 * Transaction failure process which cancel the
	 * order and redirect to checkout page with error.
	 *
	 * @since 11.0.0
	 * @param array    $server_response The server response data.
	 * @param WC_Order $wc_order        The order object.
	 * @param string   $payment_type    The payment type value.
	 *
	 * @return array
	 *
	 * @throws Exception For admin change payment method.
	 */
	public function transaction_failure( $server_response, $wc_order, $payment_type ) {

		$front_change_payment = false;
		$novalnet_order_id = novalnet_instance()->novalnet_functions()->get_novalnet_id( $wc_order );

		// Get message.
		$message = wc_novalnet_response_text( $server_response );

		// Log to notify order got failed.
		$this->maintain_debug_log( "Transaction got failed due to: $message for the order" . $novalnet_order_id );

		$front_change_payment = WC()->session->__isset( 'novalnet_change_payment_method' );
		if ( ! $front_change_payment ) {
			$payment_settings = wc_novalnet_payment_config( $payment_type );

			// Form transaction comments.
			$transaction_comments = novalnet_instance()->novalnet_functions()->form_comments(
				array(
				'test_mode' => ( ! empty( $server_response ['test_mode'] ) || $payment_settings ['test_mode'] ),
				'tid'       => $server_response ['tid'],
				'title'     => wc_novalnet_get_payment_text( $payment_settings, $this->language, $payment_type ),
				)
			);

			$transaction_comments .= PHP_EOL . $message;

			// Update transaction comments.
			novalnet_instance()->novalnet_functions()->update_comments( $wc_order, $transaction_comments,true, 'transaction_info' );
			update_post_meta( $novalnet_order_id, '_nn_version', NN_VERSION );

			// Cancel a order
			novalnet_instance()->novalnet_functions()->novalnet_cancel_order( $wc_order, $transaction_comments );

		} else {

			// Update cancelled transaction payment method with old payment method.
			$old_payment_method = get_post_meta( WC()->session->novalnet_change_payment_method, '_old_payment_method', true );
			$old_payment_method_title = get_post_meta( WC()->session->novalnet_change_payment_method, '_old_payment_method_title', true );
			update_post_meta( WC()->session->novalnet_change_payment_method, '_payment_method', $old_payment_method );
			update_post_meta( WC()->session->novalnet_change_payment_method, '_payment_method_title', $old_payment_method_title );

			// Update notice comments.
			$transaction_comments = sprintf( __( 'Recurring change payment method has been failed due to %s', 'wc-novalnet' ), $message );

			// Update transaction comments.
			novalnet_instance()->novalnet_functions()->update_comments( $wc_order, $transaction_comments, true, 'transaction_info' );
		}

		$error_return_url = apply_filters( 'novalnet_error_return_url', WC()->cart->get_checkout_url() );

		// Unset used sessions.
		WC()->session->__unset( 'novalnet_change_payment_method' );
		WC()->session->__unset( 'novalnet_receipt_page' );
		WC()->session->__unset( $payment_type );

		// Display message.
		$this->display_info( $message, 'error' );

		// Redirecting to checkout page.
		return $this->novalnet_redirect( $error_return_url, 'error' );
	}

	/**
	 * Forming the mandatory fraud module parameters.
	 *
	 * @since 11.0.0
	 * @param array $params             The novalnet parameters.
	 * @param int   $fraud_module_value The fraud module values.
	 */
	public function form_fraud_module_params( &$params, $fraud_module_value ) {

		// Fraud module parameters.
		if ( 'tel' === $this->settings ['fraud_module'] ) {
			$params ['pin_by_callback'] = '1';
		} else {
			$params ['pin_by_sms'] = '1';
		}
		$params [ $this->settings ['fraud_module'] ] = $fraud_module_value;
	}

	/**
	 * Prepare the Novalnet transaction comments.
	 *
	 * @since 10.0.0
	 * @param array   $response         The response data.
	 * @param string  $payment_type     The payment type value.
	 * @param int     $product_id       The product id.
	 * @param array   $payment_settings The payment settings.
	 * @param boolean $test_mode        The payment settings.
	 *
	 * @return array
	 */
	public function prepare_payment_comments( $response, $payment_type, $product_id, $payment_settings, $test_mode ) {
		global $current_user;
		$bank_details = '';

		// Initiate TEST ORDER notification to the Merchant.
		if ( get_option( 'novalnet_test_order_notification' ) && ! $payment_settings ['test_mode'] && '1' === $response ['test_mode'] ) {
			$content = sprintf( __( 'Dear client,<br/><br/>We would like to inform you that test order (%s) has been placed in your shop recently.Please make sure your project is in LIVE mode at Novalnet administration portal and Novalnet payments are enabled in your shop system.<br/>Please ignore this email if the order has been placed by you for testing purpose.<br/></br>Regards,<br/>Novalnet AG', 'wc-novalnet' ), $response ['order_no'] );

			$subject = sprintf( __( 'Novalnet test order notification - %s', 'wc-novalnet' ), get_option( 'blogname' ) );

			// Send E-mail notification for test order.
			wc_novalnet_send_mail( true, get_option( 'admin_email' ), $subject, $content );
		}

		// Forming basic comments.
		$tid_details = novalnet_instance()->novalnet_functions()->form_comments(
			array(
			'test_mode' => $test_mode,
			'tid'       => $response ['tid'],
			'title'     => wc_novalnet_get_payment_text( $payment_settings, $this->language, $payment_type ),
			)
		);

		$novalnet_comments = '';

		// Forming bank details comments.
		if ( in_array( $payment_type, array( 'novalnet_invoice', 'novalnet_prepayment' ), true ) ) {

			// Payment reference values.
			$response ['payment_reference_1'] = $payment_settings ['payment_reference_1'];
			$response ['payment_reference_2'] = $payment_settings ['payment_reference_2'];
			$response ['payment_reference_3'] = $payment_settings ['payment_reference_3'];
			$response ['invoice_ref']         = 'BNR-' . $product_id . '-' . $response ['order_no'];
			$response ['amount']              = $response ['amount'] * 100;
			$response ['invoice_bankname']    = $response ['invoice_bankname'] . ' ' . $response ['invoice_bankplace'];

			// Comments with bank details.
			$novalnet_comments .= novalnet_instance()->novalnet_functions()->form_bank_comments( $response );

			$bank_details = array(
				'test_mode'           => (int) $test_mode,
				'invoice_bankname'    => $response ['invoice_bankname'],
				'invoice_iban'        => $response ['invoice_iban'],
				'invoice_bic'         => $response ['invoice_bic'],
				'response_order_no'   => $response ['order_no'],
				'invoice_ref'         => $response ['invoice_ref'],
				'due_date'            => $response ['due_date'],
				'payment_reference_1' => $payment_settings ['payment_reference_1'],
				'payment_reference_2' => $payment_settings ['payment_reference_2'],
				'payment_reference_3' => $payment_settings ['payment_reference_3'],
			);

			// Check and store masked credit card details.
		}

		if ( ! empty( $current_user->ID ) ) {
			if ( 'novalnet_cc' === $payment_type && empty( WC()->session->novalnet_cc ['novalnet_cc_reference_tid'] ) && isset( $this->settings ['payment_process'] ) && 'one_click_shop' === $this->settings ['payment_process'] && ! $this->settings ['cc_secure_enabled'] ) {
				$bank_details = array(
					'cc_type'      => $response ['cc_card_type'],
					'cc_holder'    => $response ['cc_holder'],
					'cc_no'        => $response ['cc_no'],
					'cc_exp_month' => $response ['cc_exp_month'],
					'cc_exp_year'  => $response ['cc_exp_year'],
				);

				// Check and store masked Direct Debit SEPA details.
			} elseif ( 'novalnet_sepa' === $payment_type ) {

				if ( empty( WC()->session->novalnet_sepa ['novalnet_sepa_reference_tid'] ) && isset( $this->settings ['payment_process'] ) && 'one_click_shop' === $this->settings ['payment_process'] ) {
					$bank_details = array(
						'account_holder' => $response ['bankaccount_holder'],
						'iban'           => $response ['iban'],
						'bic'            => $response ['bic'],
					);
				}
				if ( ! empty( WC()->session->novalnet_sepa ['novalnet_sepa_hash'] ) ) {
					$bank_details ['hash'] = WC()->session->novalnet_sepa ['novalnet_sepa_hash'];
				}

				// Check and store PayPal transaction details.
			} elseif ( 'novalnet_paypal' === $payment_type && empty( WC()->session->novalnet_paypal ['novalnet_paypal_reference_tid'] ) && 'one_click_shop' === $this->settings ['payment_process'] ) {
				$bank_details = array(
					'paypal_transaction_id' => $response ['paypal_transaction_id'],
				);
			}
		}

		// Serialize bank details.
		$bank_details = wc_novalnet_serialize_data( $bank_details );

		return array( $tid_details, $novalnet_comments, $bank_details );
	}

	/**
	 * Processing redirect payment process.
	 *
	 * @since 11.0.0
	 * @param array $server_response The server response data.
	 *
	 * @return array|string
	 */
	public function process_redirect_payment_response( $server_response ) {

		// Trim the response values.
		$server_response = array_map( 'trim', $server_response );

		// Get order ID.
		if ( ! empty( $server_response ['inputval3'] ) ) {
			$order_id = $server_response ['inputval3'];
		} else {
			$order_id = WC()->session->novalnet_post_id;
		}

		$wc_order        = new WC_Order( $order_id );

		wc_novalnet_process_affiliate_action( $this->global_settings );

		// Check for hash error.
		if ( ( $this->success_status( $this->id, $server_response ) ) && isset( $server_response ['hash2'] ) && wc_novalnet_generate_hash( $server_response, $this->global_settings ['key_password'] ) !== $server_response ['hash2'] ) {

			$server_response['status_text'] = __( 'While redirecting some data has been changed. The hash check failed', 'wc-novalnet' );

			// Transaction failure process.
			return $this->transaction_failure( $server_response, $wc_order, $this->id );
		}
		$server_response ['test_mode'] = $this->decode_data( $server_response ['test_mode'], $this->global_settings ['key_password'] );

		// Checks transaction status.
		return $this->check_transaction_status( $server_response, $wc_order, $this->id );
	}

	/**
	 * Set time limit for Fraud module
	 * and fraud module process
	 *
	 * @since 11.0.0
	 * @param array $server_response The server response data.
	 *
	 * @return array
	 */
	public function set_time_limit( $server_response ) {

		// Set session to process fraud module.
		WC()->session->set( 'novalnet', $server_response );
		WC()->session->set( $this->id . '_time_limit', time() + ( 30 * 60 ) );
		WC()->session->set( $this->id . '_tid', $server_response['tid'] );
		WC()->session->set( $this->id . '_order_total', wc_novalnet_formatted_amount( WC()->session->total ) );

		// Get the Display message.
		if ( 'mobile' === $this->settings ['fraud_module'] ) {
			$message = __( 'You will shortly receive an SMS containing your transaction PIN to complete the payment', 'wc-novalnet' );
		} else {
			$message = __( 'You will shortly receive a transaction PIN through phone call to complete the payment', 'wc-novalnet' );
		}

		$this->display_info( $message );
		WC()->session->__unset( $this->id . '_fraud_check_validate' );

		// Log to notify PIN generated successfully.
		$this->maintain_debug_log( 'Pin generated for the payment: ' . $this->id );

		// Redirect to checkout page.
		return $this->novalnet_redirect();
	}

	/**
	 * Send PIN to Novalnet server
	 * to Process fraud module second call
	 *
	 * @since 11.0.0
	 *
	 * @return int
	 */
	public function process_pin_call() {

		// Check for amount change while processing pin status call.
		if ( WC()->session->get( $this->id . '_order_total' ) !== wc_novalnet_formatted_amount( WC()->session->total ) ) {
			WC()->session->__unset( $this->id . '_tid' );
			WC()->session->__unset( $this->id . '_order_total' );
			$this->display_info( __( 'The order amount has been changed, please proceed with the new order', 'wc-novalnet' ), 'error' );
			return false;
		}

		// Payment session values.
		$session      = WC()->session->get( $this->id );

		// Using TRANSMIT_PIN_AGAIN as default.
		$request_type = 'TRANSMIT_PIN_AGAIN';

		wc_novalnet_process_affiliate_action( $this->global_settings );

		$request = array(
		 'vendor_id'       => $this->global_settings ['vendor_id'],
		 'vendor_authcode' => $this->global_settings ['auth_code'],
		 'product_id'      => $this->global_settings ['product_id'],
		 'request_type'    => $request_type,
		 'tid'             => WC()->session->get( $this->id . '_tid' ),
		 'lang'            => $this->language,
		);

		// Assign PIN_STATUS request.
		if ( empty( $session [ $this->id . '_new_pin' ] ) ) {
			$request ['pin'] = $session [ $this->id . '_pin' ];
			$request_type = 'PIN_STATUS';
		}
		$request ['request_type'] = $request_type;

		// Perform XML request.
		$response = novalnet_instance()->novalnet_functions()->perform_xmlrequest( $request );

		// Unset the previous pin session values and update in the session.
		unset( $session [ $this->id . '_new_pin' ], $session [ $this->id . '_pin' ] );
		WC()->session->set( $this->id, $session );

		// Check the status to hide the payment.
		if ( wc_novalnet_status_check( $response, 'status', '0529006' ) ) {
			WC()->session->set( $this->id . '_invalid_count', true );
		}

		// Log to maintain PIN_STATUS call process.
		$this->maintain_debug_log( 'Pin sent to the server for the payment: ' . $this->id );

		return $response;
	}

	/**
	 * Receipt page for redirect and iframe process.
	 *
	 * @since 11.0.0
	 *
	 * @param int $order_id The order id.
	 */
	public function receipt_page( $order_id ) {

		$wc_order = new WC_Order( $order_id );
		$novalnet_parameters = $this->form_payment_params( $wc_order );

		$novalnet_order_id = novalnet_instance()->novalnet_functions()->get_novalnet_id( $wc_order );

		// Set post ID in session to use in post response process.
		WC()->session->set( 'novalnet_post_id', $novalnet_order_id );

		if ( ! WC()->session->__isset( 'novalnet_receipt_page' ) ) {

			$contents = array(
			 'paygate_url' => $novalnet_parameters ['paygate_url'],
			 'params'      => $novalnet_parameters ['params'],
			);

			wc_novalnet_load_template( 'render-redirect-form.php', $contents );

			// Assign receipt page session.
			WC()->session->set( 'novalnet_receipt_page', true );
		}
	}

	/**
	 * To get instance values.
	 *
	 * @since 11.0.0
	 */
	public function initialize_basic_details() {

		// Initiate Log.
		$this->novalnet_log    = wc_novalnet_logger();

		// Get global configuraion.
		$this->global_settings = $this->global_configurations();

		// Get language.
		$this->language        = wc_novalnet_shop_language();
	}

	/**
	 * Assigning basic details in gateway instance
	 * variable and using shop actions and filters.
	 *
	 * @since 11.0.0
	 */
	public function assign_basic_payment_details() {

		// Initialize Log & vendor details.
		$this->initialize_basic_details();

		// Initiate payment settings.
		$this->init_settings();

		// Payment title in back-end.
		$this->method_title = wc_novalnet_get_payment_text( $this->settings, $this->language, $this->id, 'admin_title' );

		// Payment title in front-end.
		$this->title = wc_novalnet_get_payment_text( $this->settings, $this->language, $this->id );

		// Payment description.
		$this->description = wc_novalnet_get_payment_text( $this->settings, $this->language, $this->id, 'description' );

		// Gateway view transaction URL.
		$this->view_transaction_url = admin_url( 'admin.php?page=wc-novalnet-admin' );

		// Basic payment supports.
		$this->supports = array(
		 'products',
		 'add-payment-method',
		);

		// Novalnet subscription supports.
		if ( ! empty( $this->global_settings ['subs_payments'] ) && in_array( $this->id, $this->global_settings ['subs_payments'], true ) ) {
			$this->supports = apply_filters( 'novalnet_subscription_supports', $this->supports, $this->id, $this->settings );
		}

		if ( ! wc_novalnet_check_admin() ) {
			$this->chosen = ( WC()->session->__isset( 'chosen_payment_method' ) && WC()->session->chosen_payment_method === $this->id );

			// Assign order status.
			add_filter( 'woocommerce_valid_order_statuses_for_payment_complete', 'wc_novalnet_append_shop_order_status', 10, 2 );
			add_filter( 'woocommerce_payment_complete_order_status', array( &$this, 'get_order_status' ), 10, 2 );
		} else {

			// Display payment configuration fields.
			$this->init_form_fields();
		}

		// Handle redirection payment request form.
		add_action( 'woocommerce_receipt_' . $this->id, array( &$this, 'receipt_page' ) );
		add_action( 'after_woocommerce_pay', 'wc_novalnet_receipt_page_session_unset' );

		// Save gateway settings.
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );

		// Customize front-end my-account option.
		add_filter( 'woocommerce_my_account_my_orders_actions', 'wc_novalnet_filter_my_account', 10, 2 );

		// Process Callback.
		add_action( 'woocommerce_api_novalnet_callback', 'wc_novalnet_process_callback_api_process', 10 );

		// Customize thank you page.
		add_action( 'woocommerce_thankyou_' . $this->id, array( &$this, 'thankyou_page' ) );
		add_action( 'woocommerce_thankyou', 'wc_novalnet_thankyou_page_session_unset' );

		// Customize E-mail.
		add_action( 'woocommerce_email_after_order_table', array( &$this, 'add_email_instructions' ), 10, 2 );
	}

	/**
	 * Align order confirmation mail transaction comments.
	 *
	 * @since 11.0.0
	 *
	 * @param WC_Order $order The order object.
	 */
	public function add_email_instructions( $order ) {

		$novalnet_payment_method = novalnet_instance()->novalnet_functions()->get_novalnet_payment_method( $order );
		// Check Novalnet payment.
		if ( wc_novalnet_check_string( $novalnet_payment_method ) ) {

			$novalnet_customer_note = novalnet_instance()->novalnet_functions()->get_novalnet_customer_note( $order );

			// Check email notes.
			if ( $novalnet_payment_method === $this->id && ! empty( $this->settings ['email_notes'] ) && ! wc_novalnet_check_string( $novalnet_customer_note, $this->settings ['email_notes'] ) ) {
				$novalnet_customer_note .= wpautop( $this->settings ['email_notes'] );
			}

			if ( wc_novalnet_compare_version( '3.0.0', WOOCOMMERCE_VERSION, '<' ) ) {
				$order->customer_note = wpautop( $novalnet_customer_note );
			} else {
				$order->set_customer_note( wpautop( $novalnet_customer_note ) );
			}
		}
	}

	/**
	 * Customizing shop thankyou page.
	 *
	 * @since 10.0.0
	 */
	public function thankyou_page() {

		if ( ! WC()->session->__isset( 'novalnet_thankyou_page' ) ) {
			echo wp_kses( wpautop( $this->settings ['instructions'] ), array() );
			WC()->session->set( 'novalnet_thankyou_page', true );
		}
	}


	/**
	 * Display the gateway details in checkout page.
	 *
	 * @since 11.0.0
	 *
	 * @param array $details The details data.
	 */
	public function display_payment_details( $details ) {

		// Hide multiple payment fields.
		wc_novalnet_hide_multiple_payment();

		// Add payment informations.
		$contents  = wpautop( $details ['description'] );
		$contents .= wpautop( $details ['payment_instruction'] );
		WC()->session->__unset( 'novalnet_receipt_page' );

		// Unset payment session for ignored payments.
		if ( WC()->session->__isset( 'chosen_payment_method' ) && WC()->session->chosen_payment_method !== $this->id ) {
			WC()->session->__unset( $this->id );
		}

		if ( $details ['test_mode'] ) {
			$contents .= wpautop( '<p style="color:red;">' . esc_html__( 'The payment will be processed in the test mode therefore amount for this transaction will not be charged', 'wc-novalnet' ) . '</p>' );
		}

		// Include checkout template.
		wc_novalnet_load_template( 'render-checkout-form.php', $contents );
	}

	/**
	 * Redirects to the given URL.
	 *
	 * @since 11.0.0
	 *
	 * @param string $url      The url value.
	 * @param string $redirect The result type.
	 *
	 * @return array
	 */
	public function novalnet_redirect( $url = '', $redirect = 'success' ) {

		if ( '' === $url ) {
			$url = WC()->cart->get_checkout_url();
		}
		return array(
		 'result'   => $redirect,
		 'redirect' => $url,
		);
	}

	/**
	 * To display the success and failure
	 * messages.
	 *
	 * @since 10.0.0
	 *
	 * @param string $message      The message value.
	 * @param string $message_type The message type value.
	 */
	public function display_info( $message, $message_type = 'success' ) {

		// Log to maintain error validations.
		$this->maintain_debug_log( "Payment has notify message $message_type: $message" );

		wc_add_notice( $message, $message_type );
	}

	/**
	 * Checks and unset the other Novalnet sessions.
	 *
	 * @since 11.0.0
	 */
	public function unset_other_payment_session() {

		foreach ( wc_novalnet_get_payment_type() as $payment ) {
			if ( $this->id !== $payment ) {
				WC()->session->__unset( $payment );
				WC()->session->__unset( $payment . '_tid' );
				WC()->session->__unset( 'novalnet_receipt_page' );
				WC()->session->__unset( 'sepa_hash' );
			}
		}

		$get_request = $_GET; // input var okay.

		// Check for force order.
		if ( ! empty( $get_request['pay_for_order'] ) && ! empty( $get_request['force_pay_order'] ) && empty( $get_request['change_payment_method'] ) ) {

			// Display message.
			$this->display_info( __( 'Novalnet Transaction for the Order has been executed / cancelled already.', 'wc-novalnet' ), 'error' );

			// Redirect to my-account page.
			wc_novalnet_safe_redirect();
		}
	}

	/**
	 * Check for change payment method.
	 *
	 * @since 11.0.0
	 */
	public function check_change_payment_method() {
		$request = $_REQUEST; // input var okay.
		if ( ! empty( $request ['change_payment_method'] ) ) {
			WC()->session->set( 'novalnet_change_payment_method', $request ['change_payment_method'] );
		} else {
			WC()->session->__unset( 'novalnet_change_payment_method' );
		}
	}
}
