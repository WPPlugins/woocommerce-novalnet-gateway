<?php
/**
 * Handling Novalnet validation / process functions
 *
 * @class    NN_Functions
 * @version  11.2.0
 * @package  Novalnet-gateway/Classes/
 * @category Class
 * @author   Novalnet
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * NN_Functions Class.
 */
class NN_Functions {


	/**
	 * The single instance of the class.
	 *
	 * @var   NN_Functions The single instance of the class.
	 * @since 11.0.0
	 */
	protected static $_instance = null;

	/**
	 * Main NN_Functions Instance.
	 *
	 * Ensures only one instance of NN_Functions is loaded or can be loaded.
	 *
	 * @since  11.0.0
	 * @static
	 * @return NN_Callback_Api Main instance.
	 */
	public static function instance() {

		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	/**
	 * Validate pin field.
	 *
	 * @since 11.0.0
	 * @param array  $session The session value.
	 * @param string $payment The payment ID.
	 *
	 * @return string
	 */
	public function validate_pin( $session, $payment ) {

		// Message based on the fraud module enable.
		if ( empty( $session [ $payment . '_new_pin' ] ) ) {
			if ( '' === $session [ $payment . '_pin' ] ) {
				return __( 'Enter your PIN', 'wc-novalnet' );
			} elseif ( ! wc_novalnet_alphanumeric_check( $session [ $payment . '_pin' ] ) ) {
				return __( 'The PIN you entered is incorrect', 'wc-novalnet' );
			}
		}
		return '';
	}

	/**
	 * Checks the cart amount with the manual check limit
	 * value to process the payment as on-hold transaction.
	 *
	 * @since 11.0.0
	 * @param int $order_amount        The order amount.
	 * @param int $manual_check_amount The manual check amount.
	 *
	 * @return boolean
	 */
	public function manual_limit_check( $order_amount, $manual_check_amount  ) {

		// Manual check limit process.
		return ( '' !== $manual_check_amount && wc_novalnet_digits_check( $manual_check_amount ) && (int) $order_amount >= $manual_check_amount );
	}

	/**
	 * Validate fraud module callback fields.
	 *
	 * @since 11.0.0
	 * @param string $fraud_module_value The fraud module value.
	 * @param string $payment            The payment ID.
	 * @param string $fraud_module       The fraud module type.
	 *
	 * @return string
	 */
	public function validate_callback_fields( $fraud_module_value, $payment, $fraud_module ) {

		// Vaidate callback fields.
		if ( ! wc_novalnet_digits_check( $fraud_module_value ) ) {
			$phone = __( 'telephone number', 'wc-novalnet' );
			if ( 'mobile' === $fraud_module ) {
				$phone = __( 'mobile number', 'wc-novalnet' );
			}
			return sprintf( __( 'Please enter your %s', 'wc-novalnet' ), $phone );
		}

		// Set fraud module session.
		WC()->session->set( $payment . '_fraud_check_validate', true );
		return '';
	}

	/**
	 * Validates the Novalnet global configuration
	 *
	 * @since 11.0.0
	 * @param array $option The novalnet options value.
	 *
	 * @return boolean
	 */
	public function global_config_validation( $option ) {

		// Unset subs_payments to trim the option since its value is array.
		unset( $option ['subs_payments'] );
		$options = array_map( 'trim', $option );

		// Validate global configuration fields.
		return ( ! wc_novalnet_digits_check( $options ['tariff_id'] ) || ( $options ['enable_subs'] && ! wc_novalnet_digits_check( $options ['subs_tariff_id'] ) ) || ! wc_novalnet_validate_email( $options ['callback_emailtoaddr'] ) || ! wc_novalnet_validate_email( $options ['callback_emailbccaddr'] ) );
	}

	/**
	 * Validate the config for Novalnet
	 * global configuration back-end.
	 *
	 * @since  11.0.0
	 * @return boolean
	 */
	public function validate_configuration() {

		$request = $_REQUEST; // input var okay.
		$options = array();
		foreach ( $request as $k => $v ) {
			$key = str_replace( 'novalnet_', '', $k );
			 $options [ $key ] = $v;
		}
		// Validate global configuration fields.
		return ( $this->global_config_validation( $options ) || empty( $request ['novalnet_public_key'] ) );
	}

	/**
	 * Submit the given request and convert the
	 * query string to array.
	 *
	 * @since 11.0.0
	 * @param array  $request The request data.
	 * @param string $url     The request url.
	 *
	 * @return array
	 */
	public function submit_request( $request, $url = 'https://payport.novalnet.de/paygate.jsp' ) {

		// Perform server call and format the response.
		wp_parse_str( wc_novalnet_server_request( $request, $url ), $response );
		return $response;
	}

	/**
	 * Get the manual check limit value from database.
	 *
	 * @since  11.0.0
	 * @return string
	 */
	public function get_manual_check_limit() {

		// Manual check limit value to process transaction as on-hold.
		return trim( get_option( 'novalnet_manual_limit' ) );
	}

	/**
	 * Perform the XML request call to Novalnet server.
	 *
	 * @since 11.0.0
	 * @param array $request_parameters The request data.
	 *
	 * @return array
	 */
	public function perform_xmlrequest( $request_parameters ) {

		$request_parameters['remote_ip'] = wc_novalnet_get_ip_address();
		// Forming XML format.
		$parameters = '<?xml version="1.0" encoding="UTF-8"?><nnxml><info_request>';
		foreach ( $request_parameters as $key => $value ) {
			$parameters .= "<$key>$value</$key>";
		}
		$parameters .= '</info_request></nnxml>';
		return json_decode( wc_novalnet_serialize_data( (array) simplexml_load_string( wc_novalnet_server_request( $parameters, 'https://payport.novalnet.de/nn_infoport.xml' ) ) ), 1 );
	}

	/**
	 * Form payment comments.
	 *
	 * @since 11.0.0
	 * @param array $data The comment data.
	 *
	 * @return string
	 */
	public function form_comments( $data ) {

		$comments = '';
		if ( ! empty( $data ['tid'] ) ) {
			$comments = $data ['title'] . PHP_EOL . sprintf( __( 'Novalnet transaction ID: %s', 'wc-novalnet' ), $data ['tid'] );
			if ( ! empty( $data ['test_mode'] ) ) {
				$comments .= PHP_EOL . __( 'Test order', 'wc-novalnet' );
			}
		}
		return $comments;
	}

	/**
	 * Form Bank details comments.
	 *
	 * @since 11.0.0
	 * @param array   $invoice_details   The invoice details.
	 * @param boolean $reference_details The reference details.
	 *
	 * @return string
	 */
	public function form_bank_comments( $invoice_details, $reference_details = true ) {

		$novalnet_comments  = PHP_EOL . PHP_EOL . __( 'Please transfer the amount to the below mentioned account details of our payment processor Novalnet', 'wc-novalnet' ) . PHP_EOL;

		// Check for due_date value.
		if ( ! empty( $invoice_details ['due_date'] ) ) {
			$novalnet_comments .= __( 'Due date: ', 'wc-novalnet' ) . wc_novalnet_formatted_date( $invoice_details ['due_date'] ) . PHP_EOL;
		}
		$novalnet_comments .= __( 'Account holder: NOVALNET AG', 'wc-novalnet' ) . PHP_EOL;

		// Novalnet version compatibility check.
		if ( ! empty( $invoice_details ['bank_iban'] ) ) {
			$prefix = 'bank';
			$novalnet_comments .= 'Bank: ' . $invoice_details ['bank_name'] . PHP_EOL;
		} else {
			$prefix = 'invoice';
			$novalnet_comments .= 'Bank: ' . $invoice_details ['invoice_bankname'] . PHP_EOL;
		}
		$novalnet_comments .= 'IBAN: ' . $invoice_details [ $prefix . '_iban' ] . PHP_EOL;
		$novalnet_comments .= 'BIC: ' . $invoice_details [ $prefix . '_bic' ] . PHP_EOL;

		// Format the amount.
		$novalnet_comments .= __( 'Amount: ', 'wc-novalnet' ) . wc_novalnet_shop_amount_format( $invoice_details ['amount'] / 100 ) . PHP_EOL;
		$notification = '';
		$reference_comments = '';

		// Form reference comments.
		if ( $reference_details ) {

			$order_no = $invoice_details ['order_no'];
			if ( ! empty( $invoice_details ['response_order_no'] ) ) {
				$order_no = $invoice_details ['response_order_no'];
			}

			// Check for selected payment references.
			$references = array_diff(
				array(
					$invoice_details ['invoice_ref']                 => $invoice_details ['payment_reference_1'],
					'TID ' . $invoice_details ['tid']                => $invoice_details ['payment_reference_2'],
					__( 'Order number ', 'wc-novalnet' ) . $order_no => $invoice_details ['payment_reference_3'],
					), array( 'no' )
			);

			$reference_count = count( $references );

		    if ( 0 < $reference_count ) {
			if ( 1 < $reference_count ) {
				$increment_id = 1;
				$notification = __( 'Please use any one of the following references as the payment reference, as only through this way your payment is matched and assigned to the order: ', 'wc-novalnet' );
				$reference_comments = '';
			} else {
				$notification = __( 'Please use the following payment reference for your money transfer, as only through this way your payment is matched and assigned to the order: ', 'wc-novalnet' );
			}
			foreach ( $references as $key => $value ) {
				if ( 1 < $reference_count ) {
					$reference_comments .= PHP_EOL . sprintf( __( 'Payment Reference %s: ', 'wc-novalnet' ), $increment_id++ );
				} elseif ( 1 === $reference_count ) {
					$reference_comments .= PHP_EOL . __( 'Payment Reference: ', 'wc-novalnet' );
				}
				$reference_comments .= $key;
			    }
			}
		}
		return wc_novalnet_format_text( $novalnet_comments . $notification . $reference_comments );
	}

	/**
	 * Update transaction order comments in
	 * order and customer note.
	 *
	 * @since 11.0.0
	 * @param WC_Order $wc_order             The order object.
	 * @param string   $transaction_comments The transaction comments.
	 * @param boolean  $append               The append value.
	 * @param string   $type                 The comment type.
	 * @param string   $customer_note        Check for the customer note.
	 */
	public function update_comments( $wc_order, $transaction_comments, $append = true, $type = 'note', $customer_note = true ) {

		$novalnet_customer_note = novalnet_instance()->novalnet_functions()->get_novalnet_customer_note( $wc_order );

		if ( 'note' === $type ) {
				$wc_order->add_order_note( $transaction_comments, $customer_note );
		} elseif ( 'transaction_info' === $type ) {

			if ( $append && ! empty( $novalnet_customer_note ) ) {
				if ( wc_novalnet_compare_version( '3.0.0', WOOCOMMERCE_VERSION, '<' ) ) {
					$wc_order->customer_note .= PHP_EOL . PHP_EOL . $transaction_comments;
				} else {
					$novalnet_customer_note .= PHP_EOL . PHP_EOL . $transaction_comments;
				}
			} elseif ( $append ) {
				if ( wc_novalnet_compare_version( '3.0.0', WOOCOMMERCE_VERSION, '<' ) ) {
					$wc_order->customer_note .= $transaction_comments;
				} else {
					$novalnet_customer_note .= $transaction_comments;
				}
			} else {
				if ( wc_novalnet_compare_version( '3.0.0', WOOCOMMERCE_VERSION, '<' ) ) {
					$wc_order->customer_note = $transaction_comments;
				} else {
					$novalnet_customer_note = $transaction_comments;
				}
			}

			$get_novalnet_id = novalnet_instance()->novalnet_functions()->get_novalnet_id( $wc_order );

			// Add customer note.
			if ( wc_novalnet_compare_version( '3.0.0', WOOCOMMERCE_VERSION, '<' ) ) {
				$novalnet_customer_notes = $wc_order->customer_note;
			} else {
				$novalnet_customer_notes = $novalnet_customer_note;
			}

			if ( wc_novalnet_compare_version( '3.0.0', WOOCOMMERCE_VERSION, '<' ) ) {
				wc_novalnet_db_update_query(
					array(
						'post_excerpt' => $novalnet_customer_notes,
					), array(
						'ID'        => $get_novalnet_id,
					), 'posts'
				);
			} else {
				$wc_order->set_customer_note( $novalnet_customer_notes );
				// Save the content.
				$wc_order->save();
			}

			if ( ! wc_novalnet_compare_version( '2.3.0', WOOCOMMERCE_VERSION ) ) {
				$wc_order->add_order_note( $transaction_comments, true );
			} else {
				$wc_order->add_order_note( $transaction_comments );
			}
		}
	}


	/**
	 * Forms the customer payment parameters.
	 *
	 * @since 11.0.0
	 * @param WC_Order $order The order object.
	 *
	 * @return array
	 */
	public function form_user_payment_parameter( $order ) {

		$billing_details = novalnet_instance()->novalnet_functions()->get_novalnet_billing_detail( $order );
		$user_id = novalnet_instance()->novalnet_functions()->get_novalnet_user_id( $order );

		$name = wc_novalnet_retrieve_name(
			array(
				$billing_details['billing_first_name'],
				$billing_details['billing_last_name'],
			)
		);

		$customer_no = 'guest';
		if ( $user_id > 0 ) {
			$customer_no = $user_id;
		}

		$street = $billing_details['billing_address_1'];
		if ( ! empty( $billing_details['billing_address_2'] ) ) {
			$street .= ', ' . $billing_details['billing_address_2'];
		}

		// Returns customer details.
		return array_map(
			'trim', array(
				'gender'           => 'u',
				'customer_no'      => $customer_no,
				'first_name'       => $name['0'],
				'last_name'        => $name['1'],
				'email'            => $billing_details['billing_email'],
				'street'           => $street,
				'search_in_street' => 1,
				'city'             => $billing_details['billing_city'],
				'zip'              => $billing_details['billing_postcode'],
				'country_code'     => $billing_details['billing_country'],
				'country'          => $billing_details['billing_country'],
				'tel'              => $billing_details['billing_phone'],
				'company'          => $billing_details['billing_company'],
			)
		);
	}

	/**
	 * Cart recurring amount.
	 *
	 * @since 11.0.0
	 * @param WC_Order $wc_order The order object.
	 *
	 * @return int
	 */
	public function get_recurring_amount_cart( $wc_order ) {

		// Converting the amount into cents.
		return wc_novalnet_formatted_amount( WC_Subscriptions_Order::get_recurring_total( $wc_order ) );
	}

	/**
	 * Get basic parameters.
	 *
	 * @since  11.0.0
	 * @return array
	 */
	public function get_basic_vendor_details() {

		// Basic vendor details.
		return array(
			'vendor_id'      => get_option( 'novalnet_vendor_id' ),
			'auth_code'      => get_option( 'novalnet_auth_code' ),
			'product_id'     => get_option( 'novalnet_product_id' ),
			'tariff_id'      => get_option( 'novalnet_tariff_id' ),
			'enable_subs'    => get_option( 'novalnet_enable_subs' ),
			'subs_tariff_id' => get_option( 'novalnet_subs_tariff_id' ),
			'subs_payments'  => get_option( 'novalnet_subs_payments' ),
		);
	}

	/**
	 * Update the affiliate and get the session values
	 *
	 * @since  11.1.0
	 *
	 * @param WC_Order $wc_order          The WC_Order object.
	 * @param string   $payment           The payment ID.
	 * @param array    $vendor_details    The vendor details.
	 * @param string   $payment_param     The payment parameters.
	 * @param array    $session_values    The session value.
	 * @param string   $response_order_no Customized order number.
	 */
	public function update_payment_process_details( $wc_order, $payment, &$vendor_details, &$payment_param, &$session_values, $response_order_no ) {

		$user_id = novalnet_instance()->novalnet_functions()->get_novalnet_user_id( $wc_order );
		$novalnet_order_id = novalnet_instance()->novalnet_functions()->get_novalnet_id( $wc_order );
		// Get payment session values.
		$session_values    = WC()->session->get( $payment );

		wc_novalnet_process_affiliate_action( $vendor_details );

		// Get payment parameters.
		if ( ! empty( $session_values ['payment_params'] ) ) {
			$payment_param = $session_values ['payment_params'];
		}

		// Insert the affiliate details.
		if ( WC()->session->__isset( 'novalnet_affiliate_id' ) ) {
			wc_novalnet_db_insert_query(
				array(
				'aff_id'       => $vendor_details ['vendor_id'],
				'customer_id'  => $user_id,
				'aff_shop_id'  => $novalnet_order_id,
				'aff_order_no' => $response_order_no,
				), 'novalnet_aff_user_detail'
			);
		}
	}

	/**
	 * Update the recurring payment.
	 *
	 * @since  11.1.0
	 *
	 * @param int    $post_id         The post ID value.
	 * @param array  $server_response Response of the transaction.
	 * @param string $payment         The payment ID.
	 * @param array  $settings        The payment settings.
	 * @param array  $language        The blog language.
	 */
	public function update_recurring_payment( $post_id, $server_response, $payment, $settings, $language ) {

		if ( wc_novalnet_check_string( get_post_meta( $post_id, '_payment_method', true ) ) ) {
			if ( ! wc_novalnet_status_check( $server_response ) ) {
				$confirm_response = NN_Meta_Box_Manage_Transaction::save( $post_id, $server_response ['tid'], wc_novalnet_get_payment_type( $payment, 'key' ) );
				$server_response ['tid_status'] = $confirm_response ['status'];
			}

			update_post_meta( $post_id, '_novalnet_gateway_status', 100 );
		}

		// Update payment method and title for change payment method.
		if ( ! wc_novalnet_is_subscription_2x() ) {
			update_post_meta( $post_id, '_payment_method', $payment );
			update_post_meta( $post_id, '_payment_method_title', $settings [ 'title_' . $language ] );
		}
	}

	/**
	 * Handle subscription process
	 *
	 * @since  11.0.0
	 *
	 * @param boolean  $subscription_order Check for Novalnet subscription.
	 * @param int      $post_id            The post ID value.
	 * @param string   $payment            The payment ID.
	 * @param array    $server_response    Response of the transaction.
	 * @param WC_Order $wc_order           The WC_Order object.
	 * @param array    $vendor_details     The vendor details.
	 * @param string   $comments           The payment settings.
	 * @param string   $tariff             The tariff ID.
	 *
	 * @return string
	 */
	public function handle_subscription_post_process( $subscription_order, $post_id, $payment, $server_response, $wc_order, $vendor_details, &$comments, &$tariff ) {
		global $post_type, $wc_order_types;

		$novalnet_shop_type = novalnet_instance()->novalnet_functions()->get_novalnet_shop_type( $wc_order );
		if ( apply_filters( 'novalnet_check_subscription', $post_id ) || 'shop_subscription' === $novalnet_shop_type || $subscription_order ) {
			if ( $subscription_order ) {

				$tariff = $vendor_details ['subs_tariff_id'];
				$subscription_order_details = apply_filters( 'novalnet_get_subscription_details', $post_id );
				$novalnet_order_id = novalnet_instance()->novalnet_functions()->get_novalnet_id( $wc_order );

				// Get subscription ID.
				$subscription_order_id = ! empty( $subscription_order_details ['0'] ) ? $subscription_order_details ['0'] : $novalnet_order_id;

				// Insert the subscription details.
				wc_novalnet_db_insert_query(
					array(
					'order_no'               => $post_id,
					'recurring_payment_type' => $payment,
					'payment_type'           => $payment,
					'tid'                    => $server_response ['tid'],
					'recurring_amount'       => wc_novalnet_formatted_amount( get_post_meta( $subscription_order_id, '_order_total', true ) ),
					'recurring_tid'          => $server_response ['tid'],
					'signup_date'            => date( 'Y-m-d H:i:s' ),
					'subs_id'                => $server_response ['subs_id'],
					'next_payment_date'      => wc_novalnet_next_subscription_date( $server_response ),
					'subscription_length'    => apply_filters( 'novalnet_get_order_subscription_length', $wc_order ),
					), 'novalnet_subscription_details'
				);

				// Activate the subscription for the order.
				do_action( 'novalnet_activate_subscription', $wc_order, $comments, $server_response ['tid'] );
				return '';
			} else {

				// Cancel subscription if transaction not processed as subscription in Novalnet.
				do_action( 'novalnet_cancel_subscription', $wc_order );
				return PHP_EOL . PHP_EOL . wc_novalnet_format_text( __( 'This is not processed as a subscription order!', 'wc-novalnet' ) );
			}
		}
		return '';
	}

	/**
	 * Assign post values in session.
	 *
	 * @since 11.0.0
	 *
	 * @param string $payment    The payment ID.
	 * @param array  $session    The session data.
	 * @param array  $post_array The post data.
	 */
	public function set_post_value_session( $payment, &$session, $post_array ) {

		$request = $_REQUEST; // input var okay.

		// Set post values in session.
		foreach ( $post_array as $value ) {
			$session_value = '';
			if ( ! empty( $session [ $value ] ) ) {
				$session_value = sanitize_text_field( $session [ $value ] );
			}

			$session [ $value ] = $session_value;
			if ( isset( $request [ $value ] ) && '' !== sanitize_text_field( $request [ $value ] ) ) {
				$session [ $value ] = sanitize_text_field( $request [ $value ] );
			}
		}

		// Storing the values in session.
		WC()->session->set( $payment, $session );
	}

	/**
	 * Checks valid payment reference.
	 *
	 * @since 11.0.0
	 *
	 * @param string $payment The payment ID.
	 */
	public static function validate_payment_reference( $payment ) {

		$request = $_REQUEST; // input var okay.

		if ( wc_novalnet_check_admin() && empty( $request [ 'woocommerce_' . $payment . '_payment_reference_1' ] ) && empty( $request [ 'woocommerce_' . $payment . '_payment_reference_2' ] ) && empty( $request [ 'woocommerce_' . $payment . '_payment_reference_3' ] ) ) {
			WC_Admin_Meta_Boxes::add_error( __( 'Please select atleast one payment reference', 'wc-novalnet' ) );
			wc_novalnet_safe_redirect( admin_url( 'admin.php?page=wc-settings&tab=checkout&section=' . $request ['section'] ) );
		}
	}

	/**
	 * Validate Customer details.
	 *
	 * @since 11.1.0
	 *
	 * @param array $customer_parameters The customer parameters.
	 *
	 * @return boolean
	 */
	public static function validate_customer_parameters( $customer_parameters ) {

		return ( ! empty( $customer_parameters['first_name'] ) && ! empty( $customer_parameters['last_name'] ) && ! empty( $customer_parameters['email'] ) && ! empty( $customer_parameters['street'] ) && ! empty( $customer_parameters['city'] ) && ! empty( $customer_parameters['zip'] ) && ! empty( $customer_parameters['country_code'] ) );
	}

	/**
	 * Validate payment input fileds.
	 *
	 * @since 11.1.0
	 *
	 * @param array $input_values The payment values.
	 * @param array $field_names  Field names need to check.
	 *
	 * @return boolean.
	 */
	public function validate_payment_input_field( $input_values, $field_names ) {
		foreach ( $field_names as $field_name ) {
			if ( empty( $input_values[ $field_name ] ) ) {
				return false;
			}
		}
		return true;
	}

	/**
	 * Checks Guarantee process.
	 *
	 * @since 11.1.0
	 *
	 * @param string $payment The payment ID.
	 */
	public static function validate_guarantee_process( $payment ) {

		$request = $_REQUEST; // input var okay.

		if ( wc_novalnet_check_admin() && ! empty( $request [ 'woocommerce_' . $payment . '_guarantee_payment' ] ) ) {

			// Check and assign default values.
			$minimum_amount = trim( $request [ 'woocommerce_' . $payment . '_guarantee_payment_minimum_order_amount' ] );
			$maximum_amount = trim( $request [ 'woocommerce_' . $payment . '_guarantee_payment_maximum_order_amount' ] );

			if ( '' === $minimum_amount ) {
				$minimum_amount = 2000;
			}

			if ( '' === $maximum_amount ) {
				$maximum_amount = 500000;
			}

			// Validate GUARANTEE minimum amount.
			if ( ! wc_novalnet_digits_check( $minimum_amount ) ) {
				WC_Admin_Meta_Boxes::add_error( __( 'The amount is invalid', 'wc-novalnet' ) );
				wc_novalnet_safe_redirect( admin_url( 'admin.php?page=wc-settings&tab=checkout&section=' . $request ['section'] ) );
			} elseif ( $minimum_amount < 2000 || $minimum_amount > 500000 ) {
				WC_Admin_Meta_Boxes::add_error( __( 'The minimum amount should be at least 20,00 EUR but not more than 5.000,00 EUR', 'wc-novalnet' ) );
				wc_novalnet_safe_redirect( admin_url( 'admin.php?page=wc-settings&tab=checkout&section=' . $request ['section'] ) );
			}

			// Validate GUARANTEE maximum amount.
			if ( ! wc_novalnet_digits_check( $maximum_amount ) ) {
				WC_Admin_Meta_Boxes::add_error( __( 'The amount is invalid', 'wc-novalnet' ) );
				wc_novalnet_safe_redirect( admin_url( 'admin.php?page=wc-settings&tab=checkout&section=' . $request ['section'] ) );
			} elseif ( $maximum_amount <= $minimum_amount || $maximum_amount > 500000 ) {
				WC_Admin_Meta_Boxes::add_error( __( 'The maximum amount should be greater than minimum order amount, but not more than 5.000,00 EUR', 'wc-novalnet' ) );
				wc_novalnet_safe_redirect( admin_url( 'admin.php?page=wc-settings&tab=checkout&section=' . $request ['section'] ) );
			}
		}
	}

	/**
	 * Checks guarantee payment.
	 *
	 * @since 11.0.0
	 *
	 * @param array  $payment_session      The payment session data.
	 * @param array  $force_normal_payment Process as normal payment value.
	 * @param string $payment              The payment ID.
	 *
	 * @return string
	 */
	public function check_guarantee_payment( $payment_session, $force_normal_payment, $payment ) {

		$message = '';

		// Validate Age.
		if ( WC()->session->__isset( $payment . '_guarantee_payment' ) ) {
		    $date_check = $payment_session [ $payment . '_dob' ];
			if ( empty( $date_check ) ) {
				$message = __( 'Please enter your date of birth', 'wc-novalnet' );
			} elseif ( time() < strtotime( '+18 years', strtotime( $date_check ) ) ) {
				$message = __( 'You need to be at least 18 years old', 'wc-novalnet' );
			} elseif ( ! preg_match("/[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/", $date_check ) ) {
				$message = __( 'The date format is invalid', 'wc-novalnet' );
			}

			// Show error for guarantee payment.
		} elseif ( WC()->session->__isset( $payment . '_guarantee_payment_error' ) ) {
			$message = __( 'The payment cannot be processed, because the basic requirements haven’t been met.', 'wc-novalnet' );
		}

		if ( 'yes' === $force_normal_payment && '' !== $message ) {
			WC()->session->__unset( $payment . '_guarantee_payment' );
			$message = '';
		}

		return $message;
	}


	/**
	 * Restrict the gateway as per the Fraud module
	 * time limit.
	 *
	 * @since 11.0.0
	 * @param string $payment The payment ID.
	 *
	 * @return boolean
	 */
	public function check_payment_availablity( $payment ) {

		$check_payment = false;
		if ( ! wc_novalnet_check_admin() ) {
			if ( WC()->session->__isset( $payment . '_time_limit' ) && time() > WC()->session->get( $payment . '_time_limit' ) ) {

				// Unset time limit session if time exceeds.
				WC()->session->__unset( $payment . '_invalid_count' );
				WC()->session->__unset( $payment . '_time_limit' );
			}

			// hide payment.
			$check_payment = WC()->session->__isset( $payment . '_invalid_count' );
		}

		// Show payment.
		return $check_payment;
	}


	/**
	 * Restrict the gateway as per the payment
	 * module visiblity process.
	 *
	 * @since 11.0.0
	 * @param array $settings The gateway settings.
	 *
	 * @return boolean
	 */
	public function restrict_payment_method( $settings ) {

		if ( ! wc_novalnet_check_admin() && ! empty( $settings ['min_amount'] ) ) {
			$order_amount = wc_novalnet_formatted_amount( WC()->session->total );
			return ( 0 < $order_amount && 0 < $settings ['min_amount'] && $settings ['min_amount'] > $order_amount );
		}
		return false;
	}

	/**
	 * Validate fraud module was enabled or not.
	 *
	 * @since 11.0.0
	 * @param array  $settings The gateway settings.
	 * @param string $payment  The payment ID.
	 *
	 * @return boolean
	 */
	public function validate_fraud_module( $settings, $payment ) {

		$request = $_REQUEST; // input var okay.
		
		// Billing address.
		$billing_address = novalnet_instance()->novalnet_functions()->get_novalnet_gurantee_billing_detail();

		// Checks Fraud module availablity.
		if ( ! ( empty( $request ['pay_for_order'] ) && empty( $request ['change_payment_method'] ) && in_array( $billing_address['country'], array( 'AT', 'DE', 'CH' ), true ) && '' !== $settings ['fraud_module'] && (  '' === $settings ['pin_amt_limit'] || $settings ['pin_amt_limit'] <= (int) wc_novalnet_formatted_amount( WC()->session->total ) ) ) ) {
			WC()->session->__unset( $payment . '_fraud_check_validate' );
			return false;
		}
		return true;
	}

	/**
	 * Get post parent id
	 *
	 * @since 11.0.0
	 * @param WC_Order $wc_order The subscription order object.
	 *
	 * @return int
	 */
	public function get_order_post_id( $wc_order ) {
		if ( wc_novalnet_compare_version( '3.0.0', WOOCOMMERCE_VERSION, '>=' ) ) {
			$parent_id = $wc_order->get_parent_id();
			if( ! empty( $parent_id ) ) {
				return $parent_id;
			}
			return $wc_order->get_id();
		}

		if ( ! empty( $wc_order->post->post_parent ) ) {
			return $wc_order->post->post_parent;
		}
		return $wc_order->id;
	}

	/**
	 * Get post parent id
	 *
	 * @since 11.0.0
	 * @param WC_Order $wc_order The subscription order object.
	 *
	 * @return int
	 */
	public function get_novalnet_gurantee_billing_detail( ) {

		if ( wc_novalnet_compare_version( '3.0.0', WOOCOMMERCE_VERSION, '>=' ) ) {
			return array(
				'country'   => WC()->customer->get_billing_country(),
				'post_code' => WC()->customer->get_billing_postcode(),
				'city'      => WC()->customer->get_billing_city(),
				'address'   => WC()->customer->get_billing_address(),
				'address2'  => WC()->customer->get_billing_address_2(),
			);
		}

		return array(
			'country'   => WC()->customer->get_country(),
			'post_code' => WC()->customer->get_postcode(),
			'city'      => WC()->customer->get_city(),
			'address'   => WC()->customer->get_address(),
			'address2'  => WC()->customer->get_address_2(),
		);
	}

	/**
	 * Get customer billing details.
	 *
	 * @since 11.0.0
	 *
	 * @param $order order object.
	 *
	 * @return int
	 */
	public function get_novalnet_billing_detail( $order ) {

		if ( wc_novalnet_compare_version( '3.0.0', WOOCOMMERCE_VERSION, '>=' ) ) {
		return array(
				'billing_first_name' => $order->get_billing_first_name(),
				'billing_last_name'  => $order->get_billing_last_name(),
				'billing_address_1'  => $order->get_billing_address_1(),
				'billing_address_2'  => $order->get_billing_address_2(),
				'billing_email'      => $order->get_billing_email(),
				'billing_city'       => $order->get_billing_city(),
				'billing_postcode'   => $order->get_billing_postcode(),
				'billing_country'    => $order->get_billing_country(),
				'billing_phone'      => $order->get_billing_phone(),
				'billing_company'    => $order->get_billing_company(),
		);
		}

		return array(
			'billing_first_name' => $order->billing_first_name,
			'billing_last_name'  => $order->billing_last_name,
			'billing_address_1'  => $order->billing_address_1,
			'billing_address_2'  => $order->billing_address_2,
			'billing_email'      => $order->billing_email,
			'billing_city'       => $order->billing_city,
			'billing_postcode'   => $order->billing_postcode,
			'billing_country'    => $order->billing_country,
			'billing_phone'      => $order->billing_phone,
			'billing_company'    => $order->billing_company,
		);
	}

	/**
	 * Get Novalnet payment method
	 *
	 * @since 11.2.0
	 * @param order $order The order object.
	 *
	 * @return int
	 */
	public function get_novalnet_payment_method( $order ) {

		// To get payment method based on version
		if ( wc_novalnet_compare_version( '3.0.0', WOOCOMMERCE_VERSION, '<' ) ) {
			return $order->payment_method;
		}
		return $order->get_payment_method();
	}
	
	/**
	 * Get Novalnet payment method
	 *
	 * @since 11.2.0
	 * @param order $order The order object.
	 *
	 * @return int
	 */
	public function get_novalnet_payment_method_title( $order ) {

		// To get payment method based on version
		if ( wc_novalnet_compare_version( '3.0.0', WOOCOMMERCE_VERSION, '<' ) ) {
			return $order->payment_method_title;
		}
		return $order->get_payment_method_title();
	}

	/**
	 * Get Novalnet customer note
	 *
	 * @since 11.2.0
	 * @param order $order The order object.
	 *
	 * @return int
	 */
	public function get_novalnet_customer_note( $order ) {

		// To get customer note based on version
		if ( wc_novalnet_compare_version( '3.0.0', WOOCOMMERCE_VERSION, '<' ) ) {
			return $order->customer_note;
		}
		return $order->get_customer_note();
	}
	
	/**
	 * Get order id from object
	 *
	 * @since 11.2.0
	 * @param order $order The order object.
	 *
	 * @return int
	 */
	public function get_novalnet_id( $order ) {

		// To get customer note based on version
		if ( wc_novalnet_compare_version( '3.0.0', WOOCOMMERCE_VERSION, '<' ) ) {
			return $order->id;
		}
		return $order->get_id();
	}

	/**
	 * Get order id from object
	 *
	 * @since 11.2.0
	 * @param order $order The order object.
	 *
	 * @return int
	 */
	public function get_novalnet_order_total( $order ) {

		// To get customer note based on version
		if ( wc_novalnet_compare_version( '3.0.0', WOOCOMMERCE_VERSION, '<' ) ) {
			return $order->order_total;
		}
		return $order->get_total();
	}

	/**
	 * Get order id from object
	 *
	 * @since 11.2.0
	 * @param order $order The order object.
	 *
	 * @return int
	 */
	public function get_novalnet_user_id( $order ) {

		// To get customer note based on version
		if ( wc_novalnet_compare_version( '3.0.0', WOOCOMMERCE_VERSION, '<' ) ) {
			return $order->user_id;
		}
		return $order->get_user_id();
	}

	/**
	 * Get order id from object
	 *
	 * @since 11.2.0
	 * @param order $order The order object.
	 *
	 * @return int
	 */
	public function get_novalnet_shop_type( $order ) {

		// To get customer note based on version
		if ( wc_novalnet_compare_version( '3.0.0', WOOCOMMERCE_VERSION, '<' ) ) {
			return $order->order_type;
		}
		$post_id = $order->get_id();
		return get_post_type( $post_id );
	}
	
	/**
	 * Get trial period from subscription
	 *
	 * @since 11.2.0
	 * @param $subscription_order The subscription order object.
	 *
	 * @return int
	 */
	public function get_novalnet_trial_period( $subscription_order ) {

		// To get customer note based on version
		if ( wc_novalnet_compare_version( '3.0.0', WOOCOMMERCE_VERSION, '<' ) ) {
			return $subscription_order->trial_period;
		}
		return $subscription_order->get_trial_period();
	}
	
	/**
	 * Get get billing interval from subscription
	 *
	 * @since 11.2.0
	 * @param $subscription_order The subscription order object.
	 *
	 * @return int
	 */
	public function get_novalnet_billing_interval( $subscription_order ) {

		// To get customer note based on version
		if ( wc_novalnet_compare_version( '3.0.0', WOOCOMMERCE_VERSION, '<' ) ) {
			return $subscription_order->billing_interval;
		}
		return $subscription_order->get_billing_interval();
	}
	
	/**
	 * Get get billing period from subscription
	 *
	 * @since 11.2.0
	 * @param $subscription_order The subscription order object.
	 *
	 * @return int
	 */
	public function get_novalnet_billing_period( $subscription_order ) {

		// To get customer note based on version
		if ( wc_novalnet_compare_version( '3.0.0', WOOCOMMERCE_VERSION, '<' ) ) {
			return $subscription_order->billing_period;
		}
		return $subscription_order->get_billing_period();
	}

	/**
	 * Get get date from subscription.
	 *
	 * @since 11.2.0
	 * @param $subscription_order The subscription order object.
	 *
	 * @return int
	 */
	public function get_novalnet_next_payment( $subscription_order ) {

		// To get customer note based on version
		if ( wc_novalnet_compare_version( '3.0.0', WOOCOMMERCE_VERSION, '<' ) ) {
			return $subscription_order->schedule_next_payment;
		}
		return $subscription_order->get_date( 'next_payment' );
	}

	/**
	 * Get get status from subscription.
	 *
	 * @since 11.2.0
	 * @param $subscription The order object.
	 *
	 * @return int
	 */
	public function get_novalnet_status( $subscription ) {

		// To get customer note based on version
		if ( wc_novalnet_compare_version( '3.0.0', WOOCOMMERCE_VERSION, '<' ) ) {
			return $subscription->post->post_status;
		}
		return $subscription->get_status();
	}

	/**
	 * Get get post parent from subscription.
	 *
	 * @since 11.2.0
	 * @param $subscription The subscription order object.
	 *
	 * @return int
	 */
	public function get_novalnet_post_parent( $subscription ) {

		// To get customer note based on version
		if ( wc_novalnet_compare_version( '3.0.0', WOOCOMMERCE_VERSION, '<' ) ) {
			return $subscription->post->post_parent;
		}
		return $subscription->get_post_parent();
	}
	
	/**
	 * Get chosed country 
	 *
	 * @since 11.2.0
	 *
	 * @return sring
	 */
	public function get_novalnet_country() {

		// To get customer note based on version
		if ( wc_novalnet_compare_version( '3.0.0', WOOCOMMERCE_VERSION, '<' ) ) {
			return WC()->customer->country;
		}
		return WC()->customer->get_billing_country();
	}
	
	/**
	 * Process order to complete
	 *
	 * @since 11.2.0
	 *
	 * @return sring
	 */
	public function complete_payment($wc_order, $tid, $transaction_comments) {

		// To get customer note based on version
		if ( wc_novalnet_compare_version( '3.0.0', WOOCOMMERCE_VERSION, '>=' ) ) {
			$wc_order->set_customer_note($transaction_comments);
		}
		
		// Payment complete process
		$wc_order->payment_complete( $tid );
	}
	
	/**
	 * Cancel order if it is not completed
	 *
	 * @since 11.2.0
	 *
	 * @return sring
	 */
	public function novalnet_cancel_order($wc_order, $transaction_comments) {

		if ( wc_novalnet_compare_version( '3.0.0', WOOCOMMERCE_VERSION, '>=' ) ) {
				$wc_order->set_customer_note( $transaction_comments );
				// Cancel order.
				$wc_order->update_status('cancelled');
			} else {
				// Cancel order.
				$wc_order->cancel_order();
			}
	}
}
