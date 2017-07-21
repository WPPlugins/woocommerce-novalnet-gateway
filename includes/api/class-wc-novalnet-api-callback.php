<?php
/**
 * Novalnet API callback.
 *
 * @class    NN_Callback_Api
 * @version  11.2.0
 * @package  Novalnet-gateway/API
 * @category Class
 * @author   Novalnet
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Novalnet Callback Api Class.
 *
 * @class   Novalnet
 * @version 11.2.0
 */
class NN_Callback_Api extends NN_Payment_Gateways {


	/**
	 * Level - 0 Payment types.
	 *
	 * @var array
	 */
	protected $payments = array(
		'CREDITCARD',
		'INVOICE_START',
		'DIRECT_DEBIT_SEPA',
		'GUARANTEED_INVOICE_START',
		'GUARANTEED_DIRECT_DEBIT_SEPA',
		'PAYPAL',
		'PRZELEWY24',
		'ONLINE_TRANSFER',
		'IDEAL',
		'GIROPAY',
		'EPS',
	);

	/**
	 * Level - 1 Payment types.
	 *
	 * @var array
	 */
	protected $chargebacks = array(
		'RETURN_DEBIT_SEPA',
		'REVERSAL',
		'CREDITCARD_BOOKBACK',
		'CREDITCARD_CHARGEBACK',
		'PAYPAL_BOOKBACK',
		'REFUND_BY_BANK_TRANSFER_EU',
		'PRZELEWY24_REFUND',
	);

	/**
	 * Level - 2 Payment types.
	 *
	 * @var array
	 */
	protected $collections = array(
		'INVOICE_CREDIT',
		'GUARANTEED_INVOICE_CREDIT',
		'CREDIT_ENTRY_CREDITCARD',
		'CREDIT_ENTRY_SEPA',
		'DEBT_COLLECTION_SEPA',
		'DEBT_COLLECTION_CREDITCARD',
		'ONLINE_TRANSFER_CREDIT',
	);

	/**
	 * Novalnet subscriptions catagory.
	 *
	 * @var array
	 */
	protected $subscriptions = array(
		'SUBSCRIPTION_STOP',
	);

	/**
	 * Novalnet payments catagory.
	 *
	 * @var array
	 */
	protected $payment_groups = array(
		'novalnet_cc'         => array(
			'CREDITCARD',
			'CREDITCARD_BOOKBACK',
			'CREDITCARD_CHARGEBACK',
			'CREDIT_ENTRY_CREDITCARD',
			'DEBT_COLLECTION_CREDITCARD',
			'SUBSCRIPTION_STOP',
		),
		'novalnet_sepa'        => array(
			'DIRECT_DEBIT_SEPA',
			'RETURN_DEBIT_SEPA',
			'CREDIT_ENTRY_SEPA',
			'DEBT_COLLECTION_SEPA',
			'GUARANTEED_DIRECT_DEBIT_SEPA',
			'REFUND_BY_BANK_TRANSFER_EU',
			'SUBSCRIPTION_STOP',
		),
		'novalnet_ideal'       => array(
			'IDEAL',
			'REFUND_BY_BANK_TRANSFER_EU',
			'ONLINE_TRANSFER_CREDIT',
			'REVERSAL',
		),
		'novalnet_eps'         => array(
			'EPS',
			'REFUND_BY_BANK_TRANSFER_EU',
		),
		'novalnet_giropay'     => array(
			'GIROPAY',
			'REFUND_BY_BANK_TRANSFER_EU',
		),
		'novalnet_instantbank' => array(
			'ONLINE_TRANSFER',
			'REFUND_BY_BANK_TRANSFER_EU',
			'ONLINE_TRANSFER_CREDIT',
			'REVERSAL',
		),
		'novalnet_paypal'      => array(
			'PAYPAL',
			'PAYPAL_BOOKBACK',
			'SUBSCRIPTION_STOP',
		),
		'novalnet_prepayment'  => array(
			'INVOICE_START',
			'INVOICE_CREDIT',
			'SUBSCRIPTION_STOP',
		),
		'novalnet_invoice'     => array(
			'INVOICE_START',
			'GUARANTEED_INVOICE_START',
			'INVOICE_CREDIT',
			'GUARANTEED_INVOICE_CREDIT',
			'SUBSCRIPTION_STOP',
		),
		'novalnet_przelewy24' => array(
			'PRZELEWY24',
			'PRZELEWY24_REFUND',
		),
	);

	/**
	 * Mandatory Parameters.
	 *
	 * @var array
	 */
	protected $required_params = array(
		'vendor_id',
		'status',
		'payment_type',
		'tid_status',
		'tid',
	);

	/**
	 * Affiliate Parameters.
	 *
	 * @var array
	 */
	protected $affiliate_params = array(
		'vendor_id',
		'vendor_authcode',
		'product_id',
		'aff_id',
		'aff_authcode',
		'aff_accesskey',
	);

	/**
	 * Novalnet success codes.
	 *
	 * @var array
	 */
	protected $success_code = array(
		'PAYPAL' => array(
			'100',
			'90',
			'85',
		),
		'INVOICE_START' => array(
			'100',
			'91',
		),
		'GUARANTEED_INVOICE_START' => array(
			'100',
			'91',
		),
		'CREDITCARD' => array(
			'100',
			'98',
		),
		'DIRECT_DEBIT_SEPA' => array(
			'100',
			'99',
		),
		'GUARANTEED_DIRECT_DEBIT_SEPA' => array(
			'100',
			'99',
		),
		'ONLINE_TRANSFER' => array(
			'100',
		),
		'ONLINE_TRANSFER_CREDIT' => array(
			'100',
		),
		'GIROPAY' => array(
			'100',
		),
		'IDEAL' => array(
			'100',
		),
		'EPS' => array(
			'100',
		),
		'PRZELEWY24' => array(
			'100',
			'86',
		),
	);

	/**
	 * Allowed IP's.
	 *
	 * @var array
	 */
	protected $ip_allowed = array(
		'195.143.189.210',
		'195.143.189.214',
	);

	/**
	 * Callback test mode.
	 *
	 * @var int
	 */
	protected $test_mode;

	/**
	 * Callback debug mode.
	 *
	 * @var int
	 */
	protected $debug_mode;

	/**
	 * Request parameters.
	 *
	 * @var array
	 */
	protected $server_request = array();

	/**
	 * Order reference values.
	 *
	 * @var array
	 */
	protected $order_reference = array();

	/**
	 * Success status values.
	 *
	 * @var boolean
	 */
	protected $success_status;

	/**
	 * NN_Callback_Api The single instance of the class.
	 *
	 * @since 11.0.0
	 *
	 * @var null/object
	 */
	protected static $_instance = null;

	/**
	 * Main NN_Callback_Api Instance.
	 *
	 * Ensures only one instance of NN_Functions is loaded or can be loaded.
	 *
	 * @since  11.0.0
	 * @static
	 * @return NN_Callback_Api Main instance.
	 */
	static function instance() {

		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	/**
	 * Callback api process.
	 *
	 * @since 11.0.0
	 * @param array $request Get the server request.
	 */
	public function callback_api_process( $request ) {

		$request = array_map( 'trim', $request );

		// Hook to align email order comments.
		add_action( 'woocommerce_email_after_order_table', 'wc_novalnet_novalnet_align_email', 10, 2 );

		// Initialize Log & vendor details.
		$this->initialize_basic_details();

		// Backend callback option.
		$this->debug_mode = get_option( 'novalnet_callback_debug_mode' );
		$this->test_mode  = get_option( 'novalnet_callback_test_mode' );

		// Authenticating the server request based on IP.
		$client_ip_addr = wc_novalnet_get_ip_address();
		if ( ! in_array( $client_ip_addr, $this->ip_allowed, true ) && ! $this->test_mode ) {
			$this->display_message( 'Novalnet callback received. Unauthorised access from the IP ' . $client_ip_addr, true );
		}

		// Affiliate activation process.
		if ( ! empty( $request ['vendor_activation'] ) ) {

			// Validate the callback mandatory affiliate parameters.
			 $this->validate_required_fields( $this->affiliate_params, $request );
			$this->server_request = $request;

			wc_novalnet_db_insert_query(
				array(
				'vendor_id'       => $this->server_request ['vendor_id'],
				'vendor_authcode' => $this->server_request ['vendor_authcode'],
				'product_id'      => $this->server_request ['product_id'],
				'product_url'     => $this->server_request ['product_url'],
				'activation_date' => date( 'Y-m-d H:i:s', strtotime( $this->server_request ['activation_date'] ) ),
				'aff_id'          => $this->server_request ['aff_id'],
				'aff_authcode'    => $this->server_request ['aff_authcode'],
				'aff_accesskey'   => $this->server_request ['aff_accesskey'],
				), 'novalnet_aff_account_detail'
			);

			// Send notification mail to the configured E-mail.
			$this->send_notification_mail( 'Novalnet callback script executed successfully with Novalnet account activation information.' );
		}

		// Get request parameters.
		$this->server_request  = $this->validate_server_request( $request );

		// Check for success status.
		$this->success_status  = ( wc_novalnet_status_check( $this->server_request ) && wc_novalnet_status_check( $this->server_request, 'tid_status' ) );

		// Get order reference.
		$this->order_reference = $this->get_order_reference();

		// Create order object.
		$this->wc_order = new WC_Order( $this->order_reference ['order_no'] );

		// Order number check.
		if ( ! empty( $this->server_request ['order_no'] ) &&  ltrim( $this->wc_order->get_order_number(), _x( '#', 'hash before order number', 'woocommerce' ) ) !== $this->server_request ['order_no']  ) {
			$this->display_message( 'Novalnet callback script order number not valid' );
		}

		// level 0 payments - Initial payments.
		$this->zero_level_process();

		// level 1 payments - Type of charge backs.
		$this->first_level_process();

		// level 2 payments - Type of credit entry.
		$this->second_level_process();

		// Subscription stop process.
		$this->subscription_stop_process();

		if ( ! $this->success_status ) {
			$this->display_message( 'Novalnet callback received. Status is not valid: Only 100 is allowed' );
		}
		$this->display_message( 'Novalnet callback script executed already' );
	}

	/**
	 * Validate required fields
	 *
	 * @since 11.0.0
	 * @param array $required_params Required params.
	 * @param array $request         Get the server request.
	 */
	public function validate_required_fields( $required_params, $request ) {

		foreach ( $required_params as $params ) {
			if ( empty( $request [ $params ] ) ) {
				$this->display_message( "Required param ( $params ) missing!" );
			} elseif ( in_array( $params, array( 'tid', 'tid_payment', 'signup_tid' ), true ) && ! preg_match( '/^\d{17}$/', $request [ $params ] ) ) {
				$this->display_message( 'Novalnet callback received. Invalid TID [ ' . $request [ $params ] . ' ] for Order.' );
			}
		}
	}

	/**
	 * Get the required TID parameter.
	 *
	 * @since 11.0.0
	 * @param array $request Get the server request.
	 *
	 * @return string
	 */
	public function get_required_tid( $request ) {

		$shop_tid = 'tid';
		if ( ! empty( $request ['payment_type'] ) && in_array( $request ['payment_type'], array_merge( $this->chargebacks, $this->collections ), true ) ) {
			$shop_tid = 'tid_payment';
		} elseif ( isset( $request ['subs_billing'] ) && '1' === $request ['subs_billing'] ) {
			$shop_tid = 'signup_tid';
		}
		return $shop_tid;
	}

	/**
	 * Validate and return the server request.
	 *
	 * @since 11.0.0
	 * @param array $request Get the server request.
	 *
	 * @return array
	 */
	public function validate_server_request( $request ) {

		$this->required_params [] = $shop_tid_key = $this->get_required_tid( $request );

		// Validate the callback mandatory request parameters.
		$this->validate_required_fields( $this->required_params, $request );

		if ( ! empty( $request ['payment_type'] ) && ! in_array( $request ['payment_type'], array_merge( $this->payments, $this->chargebacks, $this->collections, $this->subscriptions ), true ) ) {
			$this->display_message( 'Novalnet callback received. Payment type ( ' . $request ['payment_type'] . ' ) is mismatched!' );
		} elseif ( 'SUBSCRIPTION_STOP' !== $request ['payment_type'] && ( ! wc_novalnet_digits_check( $request ['amount'] ) || $request ['amount'] < 0 ) ) {
			$this->display_message( 'Novalnet callback received. The requested amount ( ' . $request ['amount'] . ' ) is not valid' );
		}

		$request ['shop_tid'] = $request [ $shop_tid_key ];
		return $request;
	}

	/**
	 * Get post id.
	 *
	 * @since 11.0.0
	 * @param int $wc_order_id The order id of the order.
	 *
	 * @return int
	 */
	public function get_post_id( $wc_order_id ) {

		$post_id = '';
		if ( ! empty( $wc_order_id ) ) {
			$post_id = wc_novalnet_order_post_id( $wc_order_id );
			if ( empty( $post_id ) ) {
				$post_id = $wc_order_id;
			}
		}
		return $post_id;
	}


	/**
	 * Get order reference.
	 *
	 * @since  10.0.0
	 * @return array
	 */
	public function get_order_reference() {

		$wc_order_id = '';
		if ( ! empty( $this->server_request ['order_no'] ) ) {
			$wc_order_id = $this->server_request ['order_no'];
		}
		$transaction_details = array();
		$post_id = $this->get_post_id( $wc_order_id );

		// Get recurring details.
		if ( ! empty( $this->server_request ['subs_billing'] ) && '1' === $this->server_request ['subs_billing'] || 'SUBSCRIPTION_STOP' === $this->server_request ['payment_type'] ) {
			$recurring_details = wc_novalnet_get_subs_details( $this->server_request ['shop_tid'] );
			$post_id = $recurring_details ['order_no'];
		}

		$transaction_details = wc_novalnet_get_callback_details( $this->server_request ['shop_tid'], $post_id );

		if ( empty( $transaction_details ) ) {

			$original_post_id = wc_novalnet_original_post_id( $this->server_request ['shop_tid'] );

			if ( ! empty( $original_post_id ) ) {

				if ( 'ONLINE_TRANSFER_CREDIT' === $this->server_request ['payment_type'] ) {
					$transaction_details ['order_no'] = $post_id;
					$this->update_initial_payment( $wc_order_id, $transaction_details, false );
					$transaction_details = wc_novalnet_get_callback_details( $this->server_request ['shop_tid'], $post_id );
				} else {

					// Get Global configuration.
					$transaction_details = $this->global_settings;

					// Fetch transaction tariff id.
					if ( apply_filters( 'novalnet_check_subscription', $original_post_id ) ) {
						$transaction_details ['tariff_id'] = $transaction_details ['subs_tariff_id'];
					}
					$transaction_details ['order_no'] = $original_post_id;
					$recurring_payment = get_post_meta( $original_post_id, '_recurring_payment_method', true );

					// Get payment type.
					$transaction_details ['payment_type']    = ( ! empty( $recurring_payment ) ) ? $recurring_payment : get_post_meta( $original_post_id, '_payment_method', true );

					$transaction_details ['amount']          = wc_novalnet_formatted_amount( get_post_meta( $post_id, '_order_total', true ) );
					$transaction_details ['callback_amount'] = get_post_meta( $original_post_id, '_nn_callback_amount', true );
				}
			} else {
				$transaction_details ['order_no'] = $post_id;
				$this->update_initial_payment( $wc_order_id, $transaction_details, true );
			}
		}

		// Assign payment type based on the order for subscription.
		if ( ! empty( $this->server_request ['subs_billing'] ) ) {
			$subscription_details = apply_filters( 'novalnet_get_subscription_details', $transaction_details ['order_no'] );
			if ( ! empty( $subscription_details ['0'] ) ) {
			    $subscription_order_id = $subscription_details ['0'];
			} else {
			    $subscription_order_id = $transaction_details ['order_no'];
			}

			// Get subscription payment type.
			$transaction_details ['payment_type'] = get_post_meta( $subscription_order_id, '_payment_method', true );
		}
		// Check for payment_type.
		if ( ! empty( $this->server_request ['payment_type'] ) && ! in_array( $this->server_request ['payment_type'], $this->payment_groups [ $transaction_details ['payment_type'] ], true ) ) {
			$this->display_message( 'Novalnet callback received. Payment type [ ' . $this->server_request ['payment_type'] . '] is not valid.' );
		}
		return $transaction_details;
	}

	/**
	 * Callback API Level zero process.
	 *
	 * @since 11.0.0
	 */
	public function zero_level_process() {

		$order_id = novalnet_instance()->novalnet_functions()->get_novalnet_id( $this->wc_order );
		$payment_method_title = novalnet_instance()->novalnet_functions()->get_novalnet_payment_method_title( $this->wc_order );
		$payment_method = novalnet_instance()->novalnet_functions()->get_novalnet_payment_method( $this->wc_order );
		if ( in_array( $this->server_request ['payment_type'], $this->payments, true ) && wc_novalnet_status_check( $this->server_request ) && in_array( $this->server_request ['tid_status'], $this->success_code [ $this->server_request ['payment_type'] ], true ) ) {

			// Subscription process.
			if ( '1' === $this->server_request ['subs_billing'] ) {
				$subscription_details = apply_filters( 'novalnet_get_subscription_details', $this->order_reference ['order_no'] );

				if ( ! empty( $subscription_details ['0'] ) ) {
					$subscription_order = new WC_Order( $subscription_details ['0'] );
				} else {
					$subscription_order = new WC_Order( $this->order_reference ['order_no'] );
				}

				// Intialize recurring order.
				$callback_comments  = $this->recurring_order_creation( $subscription_order, $subscription_details );
				$total_length       = apply_filters( 'novalnet_get_order_subscription_length', $subscription_order );

				// Check subscription length.
				if ( $total_length > 0 && apply_filters( 'novalnet_get_renewal_order_count', $subscription_order ) >= $total_length ) {

					// Log for subscription cancellation request.
					$this->maintain_debug_log( 'Subscription cancelled in Novalnet server due to subscription total length reached for the TID: ' . $this->server_request ['shop_tid'] );

					// Cancel subscription in Novalnet server.
					novalnet_instance()->novalnet_functions()->submit_request(
						array(
						'vendor'        => $this->order_reference ['vendor_id'],
						'auth_code'     => $this->order_reference ['auth_code'],
						'product'       => $this->order_reference ['product_id'],
						'tariff'        => $this->order_reference ['tariff_id'],
						'key'           => $this->order_reference ['payment_id'],
						'tid'           => $this->server_request ['shop_tid'],
						'cancel_sub'    => 1,
						'cancel_reason' => __( 'Other', 'wc-novalnet' ),
						'lang'          => $this->language,
						)
					);
				}
				// Send notification mail to the configured E-mail.
				$this->send_notification_mail( $callback_comments );

			}

			if ( in_array( $this->server_request ['payment_type'], array( 'PAYPAL', 'PRZELEWY24' ), true ) && $this->success_status && ( (int) $this->order_reference ['callback_amount'] < (int) $this->order_reference ['amount'] ) ) {
				$callback_comments = wc_novalnet_format_text( sprintf( __( 'Novalnet Callback Script executed successfully for the TID: %1$s with amount %2$s on %3$s.', 'wc-novalnet' ), $this->server_request ['tid'], wc_novalnet_shop_amount_format( $this->server_request ['amount'] / 100 ), wc_novalnet_formatted_date() ) );
				$order_id = novalnet_instance()->novalnet_functions()->get_novalnet_id( $this->wc_order );

				// Update transaction details.
				wc_novalnet_db_update_query(
					array(
					'gateway_status'  => $this->server_request ['tid_status'],
					'callback_amount' => $this->order_reference ['callback_amount'] + $this->server_request ['amount'],
					), array(
					'order_no'        => $order_id,
					)
				);
				// Update order comments.
				novalnet_instance()->novalnet_functions()->update_comments( $this->wc_order, $callback_comments, true, 'note', false );
				$payment_settings = wc_novalnet_payment_config( $payment_method );

				// Update order status.
				$this->wc_order->update_status( $payment_settings ['order_success_status'] );
				update_post_meta( $order_id, '_novalnet_gateway_status', $this->server_request ['tid_status'] );

				// Log callback process.
				$this->log_callback_details( $order_id );

				// Send notification mail to the configured E-mail.
				$this->send_notification_mail( $callback_comments );
			}

			// After execution.
			$this->display_message( 'Novalnet Callbackscript received. Payment type ( ' . $this->server_request ['payment_type'] . ' ) is not applicable for this process!' );
		}

		// Handle Przelewy failure.
		if ( 'PRZELEWY24' === $this->server_request ['payment_type'] && ! $this->success_status && '86' !== $this->server_request ['tid_status'] ) {

			// Forming comments.
			$novalnet_comments = novalnet_instance()->novalnet_functions()->form_comments(
				array(
				'test_mode' => $this->server_request ['test_mode'],
				'tid'       => $this->server_request ['shop_tid'],
				'title'     => $payment_method_title,
			) );

			$novalnet_comments .= PHP_EOL . sprintf( __( 'The transaction has been canceled due to: %s', 'wc-novalnet' ), wc_novalnet_response_text( $this->server_request ) );

			// Update order comments.
			novalnet_instance()->novalnet_functions()->update_comments( $this->wc_order, $novalnet_comments );

			// Cancel order.
			novalnet_instance()->novalnet_functions()->novalnet_cancel_order( $this->wc_order, $novalnet_comments );

			// Send notification mail to the configured E-mail.
			$this->send_notification_mail( $novalnet_comments );
		}
	}

	/**
	 * Callback API Level 1 process.
	 *
	 * @since 11.0.0
	 */
	public function first_level_process() {

		$order_id = novalnet_instance()->novalnet_functions()->get_novalnet_id( $this->wc_order );

		if ( in_array( $this->server_request ['payment_type'], $this->chargebacks, true ) && $this->success_status ) {
			// Prepare callback comments.
			$comments = __( 'Novalnet callback received. Chargeback executed successfully for the TID: %1$s amount: %2$s on %3$s. The subsequent TID: %4$s.', 'wc-novalnet' );
			if ( in_array( $this->server_request ['payment_type'], array( 'PAYPAL_BOOKBACK', 'CREDITCARD_BOOKBACK', 'REFUND_BY_BANK_TRANSFER_EU', 'PRZELEWY24_REFUND' ), true ) ) {
				$comments = __( 'Novalnet callback received. Refund/Bookback executed successfully for the TID: %1$s amount: %2$s on %3$s. The subsequent TID: %4$s.', 'wc-novalnet' );
			}
			$callback_comments = wc_novalnet_format_text( sprintf( $comments, $this->server_request ['shop_tid'], wc_novalnet_shop_amount_format( $this->server_request ['amount'] / 100 ), date_i18n( get_option( 'date_format' ), strtotime( date( 'Y-m-d' ) ) ), $this->server_request ['tid'] ) );

			// Update order comments.
			novalnet_instance()->novalnet_functions()->update_comments( $this->wc_order, $callback_comments, true, 'note', false );

			// Log callback process.
			$this->log_callback_details( $order_id );

			// Send notification mail to the configured E-mail.
			$this->send_notification_mail( $callback_comments );
		}
	}

	/**
	 * Callback API Level 2 process.
	 *
	 * @since 11.0.0
	 */
	public function second_level_process() {

		$order_id = novalnet_instance()->novalnet_functions()->get_novalnet_id( $this->wc_order );
		$payment_method_title = novalnet_instance()->novalnet_functions()->get_novalnet_payment_method_title( $this->wc_order );
		$payment_method = novalnet_instance()->novalnet_functions()->get_novalnet_payment_method( $this->wc_order );

		if ( in_array( $this->server_request ['payment_type'], $this->collections, true ) && $this->success_status ) {
			if ( in_array( $this->server_request ['payment_type'], array( 'INVOICE_CREDIT', 'GUARANTEED_INVOICE_CREDIT', 'ONLINE_TRANSFER_CREDIT' ), true ) ) {
				if ( (int) $this->order_reference ['callback_amount'] < (int) $this->order_reference ['amount'] ) {

					// Prepare callback comments.
					$callback_comments = wc_novalnet_format_text( sprintf( __( 'Novalnet Callback Script executed successfully for the TID: %1$s with amount %2$s on %3$s. Please refer PAID transaction in our Novalnet Merchant Administration with the TID: %4$s', 'wc-novalnet' ), $this->server_request ['shop_tid'], wc_novalnet_shop_amount_format( $this->server_request ['amount'] / 100 ), wc_novalnet_formatted_date(), $this->server_request ['tid'] ) );

					// Calculate total amount.
					$paid_amount = $this->order_reference ['callback_amount'] + $this->server_request ['amount'];

					$additional_note = '';

					// Check for full payment.
					if ( (int) $paid_amount >= $this->order_reference ['amount'] ) {

						if ( 'ONLINE_TRANSFER_CREDIT' === $this->server_request ['payment_type'] ) {
							$formatted_order_no = ltrim( $this->wc_order->get_order_number(), _x( '#', 'hash before order number', 'woocommerce' ) );

							$additional_note = wc_novalnet_format_text( sprintf( __( 'The amount of %1$s for the order %2$s has been paid. Please verify received amount and TID details, and update the order status accordingly.', 'wc-novalnet' ), wc_novalnet_shop_amount_format( $this->server_request ['amount'] / 100 ), $formatted_order_no ) );

							// Update order comments.
							novalnet_instance()->novalnet_functions()->update_comments( $this->wc_order, $callback_comments, true, 'note', false );

							// Update callback comments.
							$this->wc_order->add_order_note( $additional_note );

							// Update Callback amount.
							wc_novalnet_db_update_query(
								array(
									'callback_amount' => $paid_amount,
								), array(
									'order_no'        => $order_id,
								)
							);

							// Log callback process.
							$this->log_callback_details( $order_id );

							// Send notification mail to the configured E-mail.
							$this->send_notification_mail( $callback_comments . PHP_EOL . $additional_note );

						} else {

							// Get payment title.
							$payment_title = get_post_meta( $order_id, '_recurring_payment_method_title', true );
							if ( empty( $payment_title ) ) {
								$payment_title = $payment_method_title;
							}

							// Form transaction comments.
							$transaction_comments = novalnet_instance()->novalnet_functions()->form_comments(
								array(
								'title'     => $payment_title,
								'tid'       => $this->server_request ['shop_tid'],
								'test_mode' => $this->server_request ['test_mode'],
								)
							);

							// Update callback comments.
							novalnet_instance()->novalnet_functions()->update_comments( $this->wc_order, $transaction_comments, false, 'transaction_info' );

							// Update callback comments.
							$payment_settings = wc_novalnet_payment_config( $payment_method );
							$this->wc_order->update_status( $payment_settings ['callback_status'] );
						}

						if ( (int) $paid_amount > $this->order_reference ['amount'] ) {
							$additional_note .= 'Customer paid amount is greater than order amount.';
						}
					}

					// Update order comments.
					novalnet_instance()->novalnet_functions()->update_comments( $this->wc_order, $callback_comments, true, 'note', false );

					// Update Callback amount.
					wc_novalnet_db_update_query(
						array(
							'callback_amount' => $paid_amount,
						), array(
							'order_no'        => $order_id,
						)
					);

					// Log callback process.
					$this->log_callback_details( $order_id );

					// Send notification mail to the configured E-mail.
					$this->send_notification_mail( $callback_comments, $additional_note );
				}

				// After execution.
				$this->display_message( 'Novalnet callback script executed already' );
			}

			// After execution.
			$this->display_message( 'Novalnet Callbackscript received. Payment type ( ' . $this->server_request ['payment_type'] . ' ) is not applicable for this process!' );
		}
	}

	/**
	 * Subscription stop process.
	 *
	 * @since 11.0.0
	 */
	public function subscription_stop_process() {

		if ( ( $this->success_status && 'SUBSCRIPTION_STOP' === $this->server_request ['payment_type'] ) || ( ! empty( $this->server_request ['subs_billing'] ) && ! wc_novalnet_status_check( $this->server_request ) && 'SUBSCRIPTION_STOP' !== $this->server_request ['payment_type'] ) ) {

			$order_id = novalnet_instance()->novalnet_functions()->get_novalnet_id( $this->wc_order );
			// Prepare callback comments.
			if ( empty( $this->server_request ['termination_reason'] ) ) {
				$this->server_request ['termination_reason'] = $this->server_request ['status_message'];
			}
			$callback_comments = wc_novalnet_format_text( sprintf( __( 'Novalnet callback script received. Subscription has been stopped for the TID: %1$s on %2$s. Subscription has been canceled due to: %3$s', 'wc-novalnet' ), $this->server_request ['shop_tid'], wc_novalnet_formatted_date(), $this->server_request ['termination_reason'] ) );

			// Cancel the subscription.
			do_action( 'novalnet_cancel_subscription', $this->wc_order );

			// Update order comments without bank details.
			$order_details = apply_filters( 'novalnet_get_subscription_details', $order_id );
			$wc_order      = new WC_Order( $order_details ['0'] );
			novalnet_instance()->novalnet_functions()->update_comments( $wc_order, $callback_comments, true, 'note', false );

			// log for subscription process.
			$this->maintain_debug_log( "$callback_comments" );

			// Send notification mail to the configured E-mail.
			$this->send_notification_mail( $callback_comments );
		}
	}

	/**
	 * Update / initialize the payment.
	 *
	 * @since 11.0.0
	 * @param int   $wc_order_id           The order id of the processing order.
	 * @param array $transaction_details   Stored Novalnet transaction details.
	 * @param array $communication_failure Check for communication failure payment.
	 */
	public function update_initial_payment( $wc_order_id, $transaction_details, $communication_failure ) {

		$wc_order = new WC_Order( $transaction_details ['order_no'] );
		$post_id  = $transaction_details ['order_no'];
		$order_id = novalnet_instance()->novalnet_functions()->get_novalnet_id( $wc_order );
		$payment_method = novalnet_instance()->novalnet_functions()->get_novalnet_payment_method( $wc_order );

		if ( ! empty( $this->server_request ['payment_type'] ) && ! in_array( $this->server_request ['payment_type'], $this->payment_groups [ $payment_method ], true ) ) {
			$this->display_message( 'Novalnet callback received. Payment type [ ' . $this->server_request ['payment_type'] . '] is not valid.' );
		}
		$this->server_request ['payment_method'] = $payment_method;

		// Get payment settings.
		$payment_settings         = wc_novalnet_payment_config( $this->server_request ['payment_method'] );
		$is_subscription = apply_filters( 'novalnet_check_subscription', $post_id );
		update_post_meta( $post_id, '_novalnet_gateway_status', $this->server_request ['tid_status'] );
		$bank_details = '';
		$novalnet_payment_method_title = novalnet_instance()->novalnet_functions()->get_novalnet_payment_method_title( $wc_order );

		// Forming comments.
		$novalnet_comments = novalnet_instance()->novalnet_functions()->form_comments(
			array(
			'test_mode' => $this->server_request ['test_mode'],
			'tid'       => $this->server_request ['shop_tid'],
			'title'     => $novalnet_payment_method_title,
			)
		);
		$message = wc_novalnet_response_text( $this->server_request );
		if ( in_array( $this->server_request ['tid_status'], $this->success_code [ $this->server_request ['payment_type'] ], true ) ) {
			$transaction_details = $this->global_settings;
			if ( 'INVOICE_START' === $this->server_request ['payment_type'] ) {
				$invoice_details = $this->server_request;
				$invoice_details ['payment_reference_1'] = $payment_settings ['payment_reference_1'];
				$invoice_details ['payment_reference_2'] = $payment_settings ['payment_reference_2'];
				$invoice_details ['payment_reference_3'] = $payment_settings ['payment_reference_3'];
				$invoice_details ['response_order_no']   = $wc_order_id;
				$invoice_details ['invoice_ref']         = 'BNR-' . $invoice_details ['product_id'] . '-' . $wc_order_id;
				$invoice_details ['invoice_bankname']    = $invoice_details ['invoice_bankname'] . ' ' . $invoice_details ['invoice_bankplace'];
				$invoice_details ['tid'] = $this->server_request ['shop_tid'];

				// Comments with bank details.
				$novalnet_comments .= novalnet_instance()->novalnet_functions()->form_bank_comments( $invoice_details );
				$bank_details = wc_novalnet_serialize_data(
					array(
					'test_mode'           => $this->server_request ['test_mode'],
					'invoice_bankname'    => $this->server_request ['invoice_bankname'],
					'invoice_bankplace'   => $this->server_request ['invoice_bankplace'],
					'invoice_iban'        => $this->server_request ['invoice_iban'],
					'invoice_bic'         => $this->server_request ['invoice_bic'],
					'response_order_no'   => $this->server_request ['order_no'],
					'invoice_ref'         => $this->server_request ['invoice_ref'],
					'due_date'            => $this->server_request ['due_date'],
					'payment_reference_1' => $payment_settings ['payment_reference_1'],
					'payment_reference_2' => $payment_settings ['payment_reference_2'],
					'payment_reference_3' => $payment_settings ['payment_reference_3'],
					)
				);
			}
			$this->insert_transaction_details( $post_id, $wc_order, $transaction_details, $bank_details );

			// Insert the subscription details.
			if ( ! empty( $this->server_request ['subs_id'] ) && $is_subscription ) {
				wc_novalnet_db_insert_query(
					array(
					'order_no'               => $post_id,
					'recurring_payment_type' => $payment_method,
					'payment_type'           => $payment_method,
					'tid'                    => $this->server_request ['tid'],
					'recurring_amount'       => novalnet_instance()->novalnet_functions()->get_recurring_amount_cart( $wc_order ),
					'recurring_tid'          => $this->server_request ['tid'],
					'signup_date'            => date( 'Y-m-d H:i:s' ),
					'subs_id'                => $this->server_request ['subs_id'],
					'next_payment_date'      => wc_novalnet_next_subscription_date( $this->server_request ),
					'subscription_length'    => apply_filters( 'novalnet_get_order_subscription_length', $wc_order ),
					), 'novalnet_subscription_details'
				);

				// Activate the subscription for the order.
				do_action( 'novalnet_activate_subscription', $wc_order, $novalnet_comments, $this->server_request ['tid'] );
			}

			// Update order comments.
			if ( $communication_failure ) {
				novalnet_instance()->novalnet_functions()->update_comments( $wc_order, $novalnet_comments, true, 'transaction_info' );
			} else {
				novalnet_instance()->novalnet_functions()->update_comments( $wc_order, $novalnet_comments, false, 'transaction_info' );
			}

			if ( 'ONLINE_TRANSFER_CREDIT' !== $this->server_request ['payment_type'] ) {

				// Set order status.
				add_filter( 'woocommerce_valid_order_statuses_for_payment_complete', 'wc_novalnet_append_shop_order_status', 10, 2 );
				add_filter( 'woocommerce_payment_complete_order_status', array( $this, 'callback_order_status' ) );

				// Complete the payment.
				$wc_order->payment_complete( $this->server_request ['shop_tid'] );
			}
		} else {

			$novalnet_comments .= PHP_EOL . $message;
			// Update order comments.
			novalnet_instance()->novalnet_functions()->update_comments( $wc_order, $novalnet_comments, true, 'transaction_info' );

			// Cancel order.
			novalnet_instance()->novalnet_functions()->novalnet_cancel_order( $wc_order, $novalnet_comments );
		}

		// Log callback process.
		$this->log_callback_details( $post_id );

		// Update order Novalnet version.
		update_post_meta( $order_id, '_nn_version', NN_VERSION );

		if ( $communication_failure ) {
			$this->display_message( $novalnet_comments );
		}
	}

	/**
	 * Log callback process.
	 *
	 * @since 11.0.0
	 *
	 * @param int $post_id The post id of the processing order.
	 */
	public function log_callback_details( $post_id ) {

		wc_novalnet_db_insert_query(
			array(
			'payment_type'  => $this->server_request ['payment_type'],
			'status'       => $this->server_request ['status'],
			'callback_tid' => $this->server_request ['tid'],
			'org_tid'      => $this->server_request ['shop_tid'],
			'amount'       => $this->server_request ['amount'],
			'order_no'     => $post_id,
			'date'         => date( 'Y-m-d H:i:s' ),
			), 'novalnet_callback_history'
		);
	}

	/**
	 * Creation of order.
	 *
	 * @since 10.0.0
	 * @param array $subscription_order   Array contains subsription order.
	 * @param array $subscription_details Array contains subsription order details.
	 *
	 * @return string
	 */
	public function recurring_order_creation( $subscription_order, $subscription_details ) {

		$subs_post_id = novalnet_instance()->novalnet_functions()->get_novalnet_id( $subscription_order );
		$novalnet_customer_note = novalnet_instance()->novalnet_functions()->get_novalnet_customer_note( $subscription_order );
		$subs_payment_method = novalnet_instance()->novalnet_functions()->get_novalnet_payment_method( $subscription_order );

		$order_comments = $novalnet_customer_note;

		// Forming comments.
		$novalnet_comments = $novalnet_comments = novalnet_instance()->novalnet_functions()->form_comments(
			array(
			'title'     => get_post_meta( $subs_post_id, '_payment_method_title', true ),
			'tid'       => $this->server_request ['tid'],
			'test_mode' => $this->server_request ['test_mode'],
			)
		);
		$bank_details    = '';

		// Forming comments with bank details.
		if ( 'INVOICE_START' === $this->server_request ['payment_type'] ) {
			$this->server_request ['invoice_bankname']    = $this->server_request ['invoice_bankname'] . ' ' . $this->server_request ['invoice_bankname'];
			$novalnet_comments .= novalnet_instance()->novalnet_functions()->form_bank_comments( $this->server_request, false );
			$bank_details = wc_novalnet_serialize_data(
				array(
				'test_mode'           => $this->server_request ['test_mode'],
				'invoice_bankname'    => $this->server_request ['invoice_bankname'],
				'invoice_iban'        => $this->server_request ['invoice_iban'],
				'invoice_bic'         => $this->server_request ['invoice_bic'],
				'response_order_no'   => $this->server_request ['order_no'],
				'invoice_ref'         => $this->server_request ['invoice_ref'],
				'due_date'            => $this->server_request ['due_date'],
				'payment_reference_1' => 'no',
				'payment_reference_2' => 'no',
				'payment_reference_3' => 'no',
				)
			);
		}

		$transaction_details     = $this->order_reference;
		$next_payment_date = wc_novalnet_next_subscription_date( $this->server_request );
		$next_payment_text = '';
		if ( '' !== $next_payment_date ) {
			$next_payment_text   = PHP_EOL . __( ' Next charging date: ', 'wc-novalnet' ) . wc_novalnet_formatted_date( $next_payment_date );
			$novalnet_comments  .= $next_payment_text;
			do_action( 'novalnet_update_next_payment_date', $next_payment_date, $subscription_order, $subscription_details );
		}

		// Update the new comments with parent subscription order to send the comments in mail.
		wp_update_post(
			array(
			'ID'            => $subs_post_id,
			'post_excerpt'  => $novalnet_comments,
			)
		);
		$this->server_request ['payment_method'] = get_post_meta( $subs_post_id, '_payment_method', true );

		add_filter( 'woocommerce_valid_order_statuses_for_payment_complete', 'wc_novalnet_append_shop_order_status', 10, 2 );
		add_filter( 'woocommerce_payment_complete_order_status', array( $this, 'callback_order_status' ) );

		// Creating recurring order.
		$recurring_order = apply_filters( 'novalnet_create_renewal_order', $subscription_order, $subscription_details );
		$recurring_order = apply_filters( 'novalnet_get_renewal_order_details', $recurring_order );
		$recurring_post_id = novalnet_instance()->novalnet_functions()->get_novalnet_id( $recurring_order );
		update_post_meta( $recurring_post_id, '_payment_method', $this->server_request ['payment_method'] );
		update_post_meta( $recurring_post_id, '_payment_method_title', get_post_meta( $subs_post_id, '_payment_method_title', true ) );

		// Reverting the updated comments & replace with parent subscription comments.
		wp_update_post(
			array(
			'ID'            => $subs_post_id,
			'post_excerpt'  => $order_comments,
			)
		);

		// Inserting transaction details.
		$this->insert_transaction_details( $recurring_post_id, $recurring_order, $transaction_details, $bank_details, $subs_payment_method );
		update_post_meta( $recurring_post_id, '_nn_version', NN_VERSION );
		update_post_meta( $recurring_post_id, '_paid_date', current_time( 'mysql' ) );
		update_post_meta( $recurring_post_id, '_novalnet_gateway_status', $this->server_request ['tid_status'] );

		// Update order comments.
		novalnet_instance()->novalnet_functions()->update_comments( $recurring_order, $novalnet_comments, false , 'transaction_info' );

		// Prepare callback comments.
		$callback_comments = wc_novalnet_format_text( sprintf( __( 'Novalnet Callback Script executed successfully for the subscription TID: %1$s with amount %2$s on %3$s. Please refer PAID transaction in our Novalnet Merchant Administration with the TID: %4$s', 'wc-novalnet' ), $this->server_request ['shop_tid'], wc_novalnet_shop_amount_format( $this->server_request ['amount'] / 100 ), wc_novalnet_formatted_date(), $this->server_request ['tid'] ) );
		$callback_comments .= $next_payment_text;

		// Update order comments.
		novalnet_instance()->novalnet_functions()->update_comments( $subscription_order, $callback_comments, true, 'note', false );

		// Log callback process.
		$this->log_callback_details( $recurring_post_id );

		// Log payment details.
		$this->maintain_debug_log( 'Recurring order created order number ' . $recurring_post_id );

		// Complete the payment.
		$recurring_order->payment_complete( $this->server_request ['tid'] );

		// Return the comments.
		return $callback_comments;
	}

	/**
	 * Inserting transaction details.
	 *
	 * @since 11.0.0
	 * @param int      $post_id        The post ID of the processing order.
	 * @param WC_Order $wc_order       The order object.
	 * @param array    $vendor_details Novalnet vendor details.
	 * @param string   $bank_details   Payment bank details.
	 * @param string   $payment_type   Type of the processing payment.
	 */
	public function insert_transaction_details( $post_id, $wc_order, $vendor_details, $bank_details, $payment_type = '' ) {

		$subs_id = '';
		if ( ! empty( $this->server_request ['subs_id'] ) ) {
			$subs_id = $this->server_request ['subs_id'];
		}

		// Get Tariff id.
		$tariff_id = $vendor_details ['tariff_id'];

		$payment_method = novalnet_instance()->novalnet_functions()->get_novalnet_payment_method( $wc_order );
		
		// Get payment type.
		if ( '' === $payment_type ) {
			$payment_type = $payment_method;
		}

		$order_amount = $this->server_request ['amount'];

		$tid = $this->server_request ['tid'];

		// Get customer paid amount.
		$paid_amount = $this->server_request ['amount'];
		if ( 'INVOICE_START' === $this->server_request ['payment_type'] || ( 'PAYPAL' === $this->server_request ['payment_type'] && '90' === $this->server_request ['tid_status'] ) ) {
			$paid_amount = '0';
		} elseif ( 'ONLINE_TRANSFER_CREDIT' === $this->server_request ['payment_type'] ) {
			$order_amount = wc_novalnet_formatted_amount( $wc_order->order_total );
			$paid_amount  = '0';
			$tid = $this->server_request ['tid_payment'];
		}

		// Get formated order number.
		$formatted_order_no = ltrim( $wc_order->get_order_number(), _x( '#', 'hash before order number', 'woocommerce' ) );
		$novalnet_user_id = novalnet_instance()->novalnet_functions()->get_novalnet_user_id( $wc_order );
		$customer_details = novalnet_instance()->novalnet_functions()->get_novalnet_billing_detail( $wc_order );

		wc_novalnet_db_insert_query(
			array(
			'order_no'               => $post_id,
			'order_number_formatted' => $formatted_order_no,
			'vendor_id'              => $vendor_details ['vendor_id'],
			'auth_code'              => $vendor_details ['auth_code'],
			'product_id'             => $vendor_details ['product_id'],
			'tariff_id'              => $tariff_id,
			'subs_id'                => $subs_id,
			'payment_id'             => wc_novalnet_get_payment_type( $payment_type, 'key' ),
			'payment_type'           => $payment_type,
			'tid'                    => $tid,
			'gateway_status'         => $this->server_request ['tid_status'],
			'amount'                 => $order_amount,
			'callback_amount'        => $paid_amount,
			'currency'               => $this->server_request ['currency'],
			'test_mode'              => $this->server_request ['test_mode'],
			'customer_id'            => $novalnet_user_id,
			'customer_email'         => $customer_details['billing_email'],
			'date'                   => date( 'Y-m-d H:i:s' ),
			'booked'                 => 1,
			'payment_ref'            => 0,
			'bank_details'           => $bank_details,
			'payment_params'         => '',
			), 'novalnet_transaction_detail'
		);
	}

	/**
	 * Display the callback messages.
	 *
	 * @since 11.0.0
	 * @param string  $message Message for the executed process.
	 * @param boolean $force_display Display the message if debug mode is off.
	 */
	public function display_message( $message, $force_display = false ) {

		// Log callback details.
		$this->maintain_debug_log( "Novalnet callback message : $message " . http_build_query( $this->server_request ) );

		// Display message based on backend debug / test mode option.
		if ( $this->debug_mode || $force_display ) {
			wp_die( wp_kses( $message, array() ) , 'Novalnet Callback', array(
				'response' => '200',
			) );
		}
		wp_die( '' , 'Novalnet Callback', array(
			'response' => '200',
		) );

	}

	/**
	 * Send notification mail.
	 *
	 * @since 11.0.0
	 * @param string $comments        Formed comments.
	 * @param string $additional_note Additional note.
	 */
	public function send_notification_mail( $comments, $additional_note = '' ) {

		wc_novalnet_send_mail( get_option( 'novalnet_enable_callback' ), get_option( 'novalnet_callback_emailtoaddr' ), 'Novalnet Callback Script Access Report - WooCommerce', $comments, get_option( 'novalnet_callback_emailbccaddr' ) );
		$this->display_message( $comments . $additional_note );
	}

	/**
	 * Get the success status.
	 *
	 * @since 11.0.0
	 * @param string $order_status Default order status value.
	 *
	 * @return string
	 */
	public function callback_order_status( $order_status ) {
		$payment_settings = wc_novalnet_payment_config( $this->server_request ['payment_method'] );
		if ( ! empty( $payment_settings ['pending_status'] ) && ( ( 'PAYPAL' === $this->server_request ['payment_type'] && in_array( $this->server_request ['tid_status'], array( '90', '85' ), true ) ) || ( 'PRZELEWY24' === $this->server_request ['payment_type'] && '86' === $this->server_request ['tid_status'] ) ) ) {
			$order_status = $payment_settings ['pending_status'];
		} elseif ( ! empty( $payment_settings ['order_success_status'] ) ) {
			$order_status = $payment_settings ['order_success_status'];
		}
		return $order_status;
	}
}
$request  = $_REQUEST; // input var okay.

// Initiate callback api process.
novalnet_instance()->novalnet_callback_api()->callback_api_process( $request );
