<?php
/**
 * Handling Novalnet subscription functions.
 *
 * @class    NN_Subscription_2x
 * @version  11.2.0
 * @package  Novalnet-gateway/Classes/
 * @category Class
 * @author   Novalnet
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * NN_Subscription_2x Class.
 */
class NN_Subscription_2x extends NN_Payment_Gateways {


	/**
	 * For change payment method.
	 *
	 * @var $change_payment.
	 */
	public $change_payment;

	/**
	 * The single instance of the class.
	 *
	 * @var   NN_Subscription_2x The single instance of the class
	 * @since 11.0.0
	 */
	protected static $_instance = null;

	/**
	 * Main NN_Subscription_2x Instance.
	 *
	 * Ensures only one instance of NN_Subscription_2x is loaded or can be loaded.
	 *
	 * @since  11.0.0
	 * @static
	 *
	 * @return NN_Subscription_2x Main instance.
	 */
	public static function instance() {

		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	/**
	 * NN_Subscription_2x Constructor.
	 */
	public function __construct() {
		// Initialize Log & vendor details.
		$this->initialize_basic_details();

		// Subscription script.
		add_action( 'admin_enqueue_scripts', array( &$this, 'novalnet_subscription_enqueue_scripts' ) );

		// Load Iframe in shop admin.
		add_action( 'woocommerce_admin_order_data_after_billing_address', array( &$this, 'novalnet_subscription_add_iframe' ) );

		// Get return URL for subscription change payment method.
		add_action( 'novalnet_return_url', array( &$this, 'get_subscription_change_payment_return_url' ) );

		// Get error return URL for subscription change payment method.
		add_action( 'novalnet_error_return_url', array( &$this, 'get_subscription_change_payment_return_url' ) );

		// Get subscription success URL.
		add_action( 'novalnet_subscription_change_payment_method_success_url', array( &$this, 'get_subscription_success_url' ), 10, 2 );

		// Process back-end change payment method.
		add_filter( 'woocommerce_subscription_validate_payment_meta', array( &$this, 'process_admin_payment_process' ), 11, 2 );

		// Return subscription supports.
		add_filter( 'novalnet_subscription_supports', array( $this, 'get_subscription_supports' ), 10, 3 );

		// Return the current recurring payment method.
		add_filter( 'novalnet_get_recurring_payment_method', array( $this, 'is_novalnet_recurring_payment_method' ) );

		// Process subscription activate.
		add_action( 'novalnet_activate_subscription', array( $this, 'activate_subscription_order' ), 10, 3 );

		// Checking whether subscription order or not.
		add_filter( 'novalnet_check_subscription', array( $this, 'is_shop_subscription' ) );

		// Process subscription cancel.
		add_action( 'novalnet_cancel_subscription', array( $this, 'cancel_subscription' ) );

		// Return renewal order details.
		add_filter( 'novalnet_get_renewal_order_details', array( $this, 'get_renewal_object' ) );

		// Create renewal order.
		add_filter( 'novalnet_create_renewal_order', array( $this, 'create_renewal_order' ), 10, 2 );

		// Process subscription next payment date.
		add_action( 'novalnet_update_next_payment_date', array( $this, 'update_next_payment_date' ), 10, 3 );

		// Get renewal order count.
		add_filter( 'novalnet_get_renewal_order_count', array( $this, 'get_renewal_order_count' ) );

		// Get subscription length.
		add_filter( 'novalnet_get_order_subscription_length', array( $this, 'get_order_subscription_length' ), 10 );

		// Form subscription parameters.
		add_filter( 'novalnet_form_subscription_parameters', array( $this, 'form_subscription_params' ), 10, 5 );

		// Get subscription details.
		add_filter( 'novalnet_get_subscription_details', array( $this, 'get_subscription_details' ) );

		// Shows back-end change payment method form.
		add_filter( 'woocommerce_subscription_payment_meta', 'NN_Subscription_2x::add_novalnet_payment_meta_details' );

		// Customize back-end subscription cancel URL.
		add_filter( 'woocommerce_subscription_list_table_actions', array( $this, 'add_admin_subscription_process' ), 9, 2 );

		// Process subscription action.
		add_filter( 'woocommerce_can_subscription_be_updated_to_on-hold', array( $this, 'suspend_subscription_process' ), 10, 2 );
		add_filter( 'woocommerce_can_subscription_be_updated_to_active', array( $this, 'reactivate_subscription_process' ), 10, 2 );
		add_filter( 'woocommerce_can_subscription_be_updated_to_pending-cancel', array( $this, 'cancel_subscription_process' ), 10, 2 );
		add_filter( 'woocommerce_can_subscription_be_updated_to_cancelled', array( $this, 'cancel_subscription_process' ), 10, 2 );

		// Process next payment date change.
		add_action( 'woocommerce_process_shop_order_meta', array( $this, 'update_next_payment_date_process' ) );

		// Restrict subscription option.
		add_filter( 'wcs_view_subscription_actions', array( $this, 'add_myaccount_subscription_process' ), 10, 2 );

		// Process recurring amount change.
		add_action( 'woocommerce_saved_order_items', array( $this, 'perform_subscription_recurring_amount_update' ) );

		// Action to unset postmeta.
		add_action( 'woocommerce_subscription_status_on-hold', array( $this, 'unset_post_meta' ) );
		add_action( 'unable_to_suspend_subscription', array( $this, 'unset_post_meta' ) );
		add_action( 'woocommerce_subscription_status_active', array( $this, 'unset_post_meta' ) );
		add_action( 'unable_to_activate_subscription', array( $this, 'unset_post_meta' ) );
		add_action( 'woocommerce_subscription_status_cancelled', array( $this, 'unset_post_meta' ) );
		add_action( 'unable_to_cancel_subscription', array( $this, 'unset_post_meta' ) );
		add_action( 'admin_init', array( $this, 'unset_post_meta' ) );
	}

	/**
	 * Unset postmeta.
	 *
	 * @since 11.0.0
	 */
	public function unset_post_meta() {

		$request = $_REQUEST; // input var okay.
		$post_id = '';

		if ( ( ! empty( $request ['post_ID'] ) && ! empty( $request ['post_type'] ) && 'shop_subscription' === $request ['post_type']  ) ) {
			$post_id = $request ['post_ID'];
		} elseif ( ! empty( $request ['post'] ) && ! empty( $request ['action'] ) ) {
			$post_id = $request ['post'];
		} elseif ( ! empty( $request ['subscription_id'] ) && ! empty( $request ['change_subscription_to'] ) ) {
			$post_id = $request ['subscription_id'];
		}
		delete_post_meta( $post_id, '_nn_subscription_updated' );
	}

	/**
	 * Customize the my-account page to show
	 * execute novalnet subscription process.
	 *
	 * @since 11.0.0
	 * @param array           $actions      The action data.
	 * @param WC_Subscription $subscription The subscription object.
	 *
	 * @return array
	 */
	public function add_myaccount_subscription_process( $actions, $subscription ) {

		$novalnet_payment_method = novalnet_instance()->novalnet_functions()->get_novalnet_payment_method( $subscription );
		// Check for Novalnet payment.
		$is_subscription = wc_novalnet_check_string( $novalnet_payment_method );

		if ( $is_subscription ) {
			// Hide customer subscription cancel, reactivate, suspend options.
			foreach ( array(
			 'cancel',
			 'suspend',
			 'reactivate',
			) as $value ) {
				if ( ! empty( $actions [ $value ] ) ) {
					unset( $actions [ $value ] );
				}
			}
			$gateway_status = get_post_meta( novalnet_instance()->novalnet_functions()->get_order_post_id( $subscription ), '_novalnet_gateway_status', true );

			// Checks Novalnet TID status.
			if ( ! empty( $gateway_status ) && '100' !== $gateway_status ) {
				unset( $actions['change_payment_method'] );
			}
		}

		return $actions;
	}

	/**
	 * Adding subscription script.
	 *
	 * @since 11.0.0
	 */
	public function novalnet_subscription_enqueue_scripts() {
		global $post_type;

		if ( isset( $post_type ) && 'shop_subscription' === $post_type ) {
			$request = $_REQUEST; // input var okay.

			// Enqueue style & script.
			wp_enqueue_script( 'wc-novalnet-subscription-script', novalnet_instance()->plugin_url() . '/assets/js/novalnet-subscription.js', array( 'jquery' ), NN_VERSION, true );
			$params = array(
			 'reason_list'         => wc_novalnet_subscription_cancel_form(), // Display Subscription cancel reason.
			 'change_payment_text' => __( 'Change Payment', 'wc-novalnet' ),
			 'error_message'       => __( 'Please select the reason of subscription cancellation', 'wc-novalnet' ),
			);

			if ( wc_novalnet_check_admin() && ! empty( $request ['post'] ) && ! empty( $request ['action'] ) ) {
				$wc_order = new WC_Order( $request ['post'] );
				$novalnet_payment_method = novalnet_instance()->novalnet_functions()->get_novalnet_payment_method( $wc_order );
				if ( ! empty( $novalnet_payment_method ) && wc_novalnet_check_string( $novalnet_payment_method ) ) {
					$params ['hide_other_subscription_options'] = 'true';
				}
			}
			wp_localize_script( 'wc-novalnet-subscription-script', 'novalnet_subscription', $params );
		}
	}

	/**
	 * Cancel subscription process in shop.
	 *
	 * @since 11.0.0
	 * @param WC_Order $wc_order The order object.
	 */
	public function cancel_subscription( $wc_order ) {

		$get_novalnet_id = novalnet_instance()->novalnet_functions()->get_novalnet_id( $wc_order );
		$subscription_id = $this->get_subscription_details( $get_novalnet_id );
		if ( ! empty( $subscription_id ['0'] ) ) {
			$subscription_order = new WC_Subscription( $subscription_id ['0'] );

			$novalnet_next_payment = novalnet_instance()->novalnet_functions()->get_novalnet_next_payment( $subscription_order );
			if ( ! empty( $novalnet_next_payment ) ) {
				WC_Subscriptions_Manager::cancel_subscriptions_for_order( $wc_order );
			}
		}
	}

	/**
	 * Create / Initiate recurring order.
	 *
	 * @since 11.0.0
	 * @param WC_Subscription $subscription_order The subscription object.
	 *
	 * @return int
	 */
	public function create_renewal_order( $subscription_order ) {
		return wcs_create_renewal_order( $subscription_order );
	}

	/**
	 * Return Renewal object.
	 *
	 * @since 11.0.0
	 * @param WC_Order $renewal_order The renewal order object.
	 *
	 * @return WC_Order
	 */
	public function get_renewal_object( $renewal_order ) {

		return $renewal_order;
	}

	/**
	 * Update Next payment date.
	 *
	 * @since 11.0.0
	 * @param date            $date         The next payment date.
	 * @param WC_Subscription $subscription The subscription object.
	 */
	public function update_next_payment_date( $date, $subscription ) {

		$subs_post_id = novalnet_instance()->novalnet_functions()->get_novalnet_id( $subscription );
		// $subscription_details used in subscription version 1x.
		update_post_meta( $subs_post_id, '_schedule_next_payment', date( 'Y-m-d H:i:s', strtotime( $date ) ) );
	}

	/**
	 * Update subscription recurring amount
	 *
	 * @since 11.0.0
	 * @param int $order_id The order id.
	 */
	public function perform_subscription_recurring_amount_update( $order_id ) {

		$request = $_REQUEST; // input var okay.
		if ( ! empty( $request ['action'] ) && 'woocommerce_save_order_items' === $request ['action'] ) {

			// Initiating order object.
			$wc_order        = new WC_Order( $order_id );
			$novalnet_payment_method = novalnet_instance()->novalnet_functions()->get_novalnet_payment_method( $wc_order );

			$original_post_id = novalnet_instance()->novalnet_functions()->get_order_post_id( $wc_order );

			if ( wc_novalnet_check_string( $novalnet_payment_method ) && ! empty( $original_post_id ) ) {

				$subscription_details  = wc_novalnet_order_no_details( $original_post_id, 'novalnet_subscription_details' );
				$formatted_amount      = get_post_meta( $order_id, '_order_total', true );
				$update_amount         = wc_novalnet_formatted_amount( $formatted_amount );

				if ( ! empty( $update_amount ) && (int) $subscription_details ['recurring_amount'] !== (int) $update_amount ) {

					$transaction_details = wc_novalnet_get_transaction_details( $original_post_id, 'subscription' );

					$response = novalnet_instance()->novalnet_functions()->perform_xmlrequest(
						array(
						'vendor_id'         => $transaction_details ['vendor_id'],
						'vendor_authcode'   => $transaction_details ['auth_code'],
						'product_id'        => $transaction_details ['product_id'],
						'request_type'      => 'SUBSCRIPTION_UPDATE',
						'subs_tid'          => $transaction_details ['tid'],
						'payment_ref'       => $transaction_details ['tid'],
						'subs_id'           => $transaction_details ['subs_id'],
						'tid'               => $transaction_details ['tid'],
						'amount'            => $update_amount,
						'update_flag'       => 'amount',
						)
					);

					// Log for subscription amount update process.
					$this->maintain_debug_log( "Subscription amount update call initiated for the order id $order_id and the status was " . $response ['status'] );
					if ( wc_novalnet_status_check( $response ) ) {
							 $message = wc_novalnet_format_text( sprintf( __( 'Subscription recurring amount %s has been updated successfully', 'wc-novalnet' ), wc_novalnet_shop_amount_format( $formatted_amount ) ) );
							 novalnet_instance()->novalnet_functions()->update_comments( $wc_order, $message );
							 wc_novalnet_db_update_query(
								 array(
								 'recurring_amount' => $update_amount,
								 ), array(
								 'order_no' => $original_post_id,
								 ), 'novalnet_subscription_details'
							 );
					} else {
						$message = wc_novalnet_format_text( sprintf( __( 'Amount update for the next recurring process is failed in Novalnet due to: %s', 'wc-novalnet' ),  wc_novalnet_response_text( $response ) ) );
						novalnet_instance()->novalnet_functions()->update_comments( $wc_order, $message );
					}
				}
			}
		}
	}

	/**
	 * Changing Next payment date process
	 *
	 * @since 11.0.0
	 * @param int $subscription_id The subscription id.
	 */
	public function update_next_payment_date_process( $subscription_id ) {
		global $post_type;
		$request = $_REQUEST; // input var okay.
		$subscription           = new WC_Order( $subscription_id );
		$scheduled_date_time    = get_post_meta( $subscription_id, '_schedule_next_payment', true );
		$scheduled_date         = date( 'Y-m-d', strtotime( $scheduled_date_time ) );
		$novalnet_payment_method = novalnet_instance()->novalnet_functions()->get_novalnet_payment_method( $subscription );

		// Checks for Novalnet payment.
		if ( 'shop_subscription' === $post_type && wc_novalnet_check_string( $novalnet_payment_method ) && ! empty( $request['next_payment_timestamp_utc'] ) ) {
			
			// Requested date.
			$updated_date           = date( 'Y-m-d', $request['next_payment_timestamp_utc'] );

			// Check for the previous date.
			if ( $updated_date !== $scheduled_date ) {
				// Check for the future date.
				if ( $updated_date < $scheduled_date ) {
					 wcs_add_admin_notice( __( 'The date should be in future.', 'wc-novalnet' ), 'error' );

					 // Redirect to subscription page.
					 wc_novalnet_safe_redirect(
						 add_query_arg(
							 array(
							 'action'  => 'edit',
							 'post'    => $subscription_id,
							 ), admin_url( 'post.php' )
						 )
					 );
				}
				$date_difference     = wcs_estimate_periods_between( strtotime( $scheduled_date_time ), strtotime( $updated_date ), 'day' );

				// Get transaction details.
				$original_post_id = novalnet_instance()->novalnet_functions()->get_order_post_id( $subscription );

				$transaction_details = wc_novalnet_get_transaction_details( $original_post_id, 'subscription' );
				if ( ! empty( $date_difference ) ) {

					// Submit XML request.
					$response = novalnet_instance()->novalnet_functions()->perform_xmlrequest(
						array(
						'vendor_id'       => $transaction_details ['vendor_id'],
						'vendor_authcode' => $transaction_details ['auth_code'],
						'product_id'      => $transaction_details ['product_id'],
						'request_type'    => 'SUBSCRIPTION_PAUSE',
						'tid'             => $transaction_details ['tid'],
						'subs_id'         => $transaction_details ['subs_id'],
						'pause_period'    => $date_difference,
						'pause_time_unit' => 'd',
						)
					);

					// Log for change next recurring date process.
					$this->maintain_debug_log( "Subscription change next payment date call initiated for the order id $subscription_id" );
					if ( wc_novalnet_status_check( $response ) ) {
						$next_payment_date = wc_novalnet_next_subscription_date( $response );
						$message = wc_novalnet_format_text( sprintf( __( 'Subscription renewal date has been successfully changed to %s', 'wc-novalnet' ),  wc_novalnet_formatted_date( $next_payment_date ) ) );

						// Update Next payment date in Novalnet subscription table.
						wc_novalnet_db_update_query(
							array(
							'next_payment_date' => $next_payment_date,
							), array(
							'order_no' => $original_post_id,
							), 'novalnet_subscription_details'
						);

						$subs_post_id = novalnet_instance()->novalnet_functions()->get_novalnet_id( $subscription );
						// Update Novalnet comments.
						novalnet_instance()->novalnet_functions()->update_comments( $subscription, $message );
						wcs_add_admin_notice( $message );
						update_post_meta( $subs_post_id, '_schedule_next_payment', $next_payment_date );
						if ( wc_novalnet_compare_version( '3.0.0', WOOCOMMERCE_VERSION, '>=' ) ) {
							$subscription->save();
						}
					} else {
						$message = wc_novalnet_format_text( sprintf( __( 'Next recurring date change process is failed in Novalnet due to: %s', 'wc-novalnet' ),  wc_novalnet_response_text( $response ) ) );
						$subscription->add_order_note( $message );
						wcs_add_admin_notice( $message, 'error' );

						// Redirect to subscription page.
						wc_novalnet_safe_redirect(
							add_query_arg(
								array(
								'action'  => 'edit',
								'post'    => $subscription_id,
								), admin_url( 'post.php' )
							)
						);
					}
				}
			}
		}
	}

	/**
	 * Cancel the subscription process.
	 *
	 * @since 11.0.0
	 * @param boolean         $can_update   For process cancel action.
	 * @param WC_Subscription $subscription The subscription object.
	 *
	 * @return boolean
	 */
	public function cancel_subscription_process( $can_update, $subscription ) {

		$request = $_REQUEST; // input var okay.

		$novalnet_payment_method = novalnet_instance()->novalnet_functions()->get_novalnet_payment_method( $subscription );
		$subs_post_id = novalnet_instance()->novalnet_functions()->get_novalnet_id( $subscription );
		$novalnet_status = novalnet_instance()->novalnet_functions()->get_novalnet_status( $subscription );

		// Check Novalnet payment.
		if ( $can_update && ! wc_novalnet_check_string( $novalnet_status, 'cancel' ) && wc_novalnet_check_string( $novalnet_payment_method ) && ! get_post_meta( $subs_post_id, '_nn_subscription_updated', true ) && $this->check_subscription_status( $request, 'cancel' ) ) {

			// Check for cancel subscription reason.
			if ( empty( $request['novalnet_subscription_cancel_reason'] ) ) {
				$this->subscription_error_process( __( 'Please select the reason of subscription cancellation','wc-novalnet' ) );
			}

			// Get subscription ID.
			$subs_order_id = $this->get_subscription_order_id( $subscription );

			// Get transaction details.
			$transaction_details = wc_novalnet_get_transaction_details( $subs_order_id );

			// Get subscrition cancellation reason.
			$reason = wc_novalnet_subscription_cancel_list();
			$response = novalnet_instance()->novalnet_functions()->submit_request(
				array_merge(
					wc_novalnet_built_api_params( $transaction_details ), array(
					'cancel_sub'    => 1,
					'cancel_reason' => $reason [ $request['novalnet_subscription_cancel_reason'] ],
					'lang'          => wc_novalnet_shop_language(),
					)
				)
			);

			// Log for subscription cancel process.
			$this->maintain_debug_log( 'Subscription cancellation call initiated for the order id ' . $subs_post_id . ' and the status was ' . $response ['status'] );
			if ( wc_novalnet_status_check( $response ) ) {

				// Update Novalnet comments.
				novalnet_instance()->novalnet_functions()->update_comments( $subscription, wc_novalnet_format_text( sprintf( __( 'Subscription has been canceled due to: %s', 'wc-novalnet' ), $reason [ $request['novalnet_subscription_cancel_reason'] ] ) ) );

				// Update suspended reason and date in Novalnet subscription table.
				wc_novalnet_db_update_query(
					array(
					'termination_at' => date( 'Y-m-d H:i:s' ),
					'termination_reason' => $reason[ $request['novalnet_subscription_cancel_reason'] ],
					), array(
					'order_no' => $subs_order_id,
					), 'novalnet_subscription_details'
				);
			} else {

				// Process subscription error.
				$this->subscription_error_process( wc_novalnet_format_text( sprintf( __( 'Cancellation of subscription is failed due to: %s', 'wc-novalnet' ), wc_novalnet_response_text( $response ) ) ) );
			}

			// Set value to notify subscription updated.
			update_post_meta( $subs_post_id, '_nn_subscription_updated', true );
		}
		return $can_update;
	}

	/**
	 * Get subscription order id.
	 *
	 * @since 11.0.0
	 * @param WC_Subscription $subscription The subscription object.
	 *
	 * @return int
	 */
	public function get_subscription_order_id( $subscription ) {

		$subs_post_id = novalnet_instance()->novalnet_functions()->get_novalnet_id( $subscription );
		$subs_order_id = $subs_post_id;
		if ( wc_novalnet_compare_version( '3.0.0', WOOCOMMERCE_VERSION, '<' ) ) {
			if ( ! empty( $subscription->order->id ) ) {
				$subs_order_id = $subscription->order->id;
			}
		} else {
			$get_parent_id = $subscription->get_parent_id();
			if ( ! empty( $get_parent_id ) ) {
				$subs_order_id = $subscription->get_parent_id();
			}
		}
		return $subs_order_id;
	}

	/**
	 * Suspend the subscription process.
	 *
	 * @since 11.0.0
	 * @param boolean         $can_update   For process suspend action.
	 * @param WC_Subscription $subscription The subscription object.
	 *
	 * @return boolean
	 */
	public function suspend_subscription_process( $can_update, $subscription ) {

		$request = $_REQUEST; // input var okay.

		$novalnet_payment_method = novalnet_instance()->novalnet_functions()->get_novalnet_payment_method( $subscription );
		$subs_post_id = novalnet_instance()->novalnet_functions()->get_novalnet_id( $subscription );

		// Checks Novalnet payment.
		if ( $can_update && wc_novalnet_check_string( $novalnet_payment_method ) && ! get_post_meta( $subs_post_id, '_nn_subscription_updated', true ) && $this->check_subscription_status( $request, 'on-hold', 'active' ) ) {

			$subs_order_id = $this->get_subscription_order_id( $subscription );

			// Get transaction details.
			$transaction_details = wc_novalnet_get_transaction_details( $subs_order_id, 'subscription' );

			// Perform XML request.
			$response = novalnet_instance()->novalnet_functions()->perform_xmlrequest(
				array(
				'vendor_id'       => $transaction_details ['vendor_id'],
				'vendor_authcode' => $transaction_details ['auth_code'],
				'product_id'      => $transaction_details ['product_id'],
				'request_type'    => 'SUBSCRIPTION_PAUSE',
				'tid'             => $transaction_details ['tid'],
				'subs_id'         => $transaction_details ['subs_id'],
				'suspend'         => '1',
				)
			);

			// Log for subscription suspend process.
			$this->maintain_debug_log( 'Subscription suspend call initiated for the order id ' . $subs_post_id );

			// Handle response.
			if ( wc_novalnet_status_check( $response ) ) {
				$message = wc_novalnet_format_text( sprintf( __( 'This subscription transaction has been suspended on %s', 'wc-novalnet' ), wc_novalnet_formatted_date() ) );

				// Update suspended date in Novalnet subscription table.
				wc_novalnet_db_update_query(
					array(
					'suspended_date' => date( 'Y-m-d H:i:s' ),
					), array(
					'order_no' => $subs_order_id,
					), 'novalnet_subscription_details'
				);

				// Update Novalnet comments.
				novalnet_instance()->novalnet_functions()->update_comments( $subscription, $message );

			} else {

				// Process subscription error.
				$this->subscription_error_process( wc_novalnet_format_text( sprintf( __( 'Subscription suspend has been failed due to: %s', 'wc-novalnet' ), wc_novalnet_response_text( $response ) ) ) );
			}

			// Set value to notify subscription updated.
			update_post_meta( $subs_post_id, '_nn_subscription_updated', true );
		}
		return $can_update;
	}

	/**
	 * Reactivate the subscription process.
	 *
	 * @since 11.0.0
	 * @param boolean         $can_update   For process reactivate action.
	 * @param WC_Subscription $subscription The subscription object.
	 *
	 * @return boolean
	 */
	public function reactivate_subscription_process( $can_update, $subscription ) {

		$request = $_REQUEST; // input var okay.

		$novalnet_payment_method = novalnet_instance()->novalnet_functions()->get_novalnet_payment_method( $subscription );
		$subs_post_id = novalnet_instance()->novalnet_functions()->get_novalnet_id( $subscription );

		// Checks Novalnet payment.
		if ( $can_update && wc_novalnet_check_string( $novalnet_payment_method ) && ! get_post_meta( $subs_post_id, '_nn_subscription_updated', true ) && $this->check_subscription_status( $request, 'active', 'on-hold' ) ) {
			$subs_order_id = $this->get_subscription_order_id( $subscription );

			// Get transaction details.
			$transaction_details  = wc_novalnet_get_transaction_details( $subs_order_id, 'subscription' );

			$period   = get_post_meta( $subs_post_id, '_billing_period', true );
			$interval = get_post_meta( $subs_post_id, '_billing_interval', true );

			// Get subscription details.
			$subscription_details     = wc_novalnet_get_subs_details( '', $subs_order_id );
			$formatted_previous_cycle = date( 'Y-m-d', strtotime( $subscription_details ['next_payment_date'] ) );
			$previous_next_subs_cycle = strtotime( $formatted_previous_cycle );
			$next_subs_cycle          = $formatted_previous_cycle;
			$current_date             = strtotime( date( 'Y-m-d' ) );

			// Form request parameters.
			$request_parameters = array(
				'vendor_id'       => $transaction_details ['vendor_id'],
				'vendor_authcode' => $transaction_details ['auth_code'],
				'product_id'      => $transaction_details ['product_id'],
				'request_type'    => 'SUBSCRIPTION_PAUSE',
				'tid'             => $transaction_details ['tid'],
				'subs_id'         => $transaction_details ['subs_id'],
				'suspend'         => 0,
			);

			if ( $previous_next_subs_cycle <= $current_date ) {

				while ( strtotime( $next_subs_cycle ) <= $current_date ) {
					$next_subs_cycle = date( 'Y-m-d', strtotime( $next_subs_cycle . '+' . $interval . ' ' . $period ) );
				}

				if ( strtotime( $next_subs_cycle ) > $current_date ) {

					// Calculate date difference.
					$difference          = date_diff( date_create( $formatted_previous_cycle ), date_create( $next_subs_cycle ) );

					if ( $difference->days > 0 ) {
						$request_parameters ['pause_period'] = $difference->days;
						$request_parameters ['pause_time_unit'] = 'd';
					}
				}
			}

			// Submit XML call.
			$response = novalnet_instance()->novalnet_functions()->perform_xmlrequest( $request_parameters );

			// Log for subscription reactive process.
			$this->maintain_debug_log( 'Subscription reactive call initiated for the order id ' . $subs_post_id );
			if ( wc_novalnet_status_check( $response ) ) {
				$next_payment_date = wc_novalnet_next_subscription_date( $response );
				update_post_meta( $subs_post_id, '_schedule_next_payment', date( 'Y-m-d H:i:s', strtotime( $next_payment_date ) ) );

				// Update Next payment date in Novalnet subscription table.
				wc_novalnet_db_update_query(
					array(
					'next_payment_date' => $next_payment_date,
					), array(
					'order_no' => $subs_order_id,
					), 'novalnet_subscription_details'
				);

				novalnet_instance()->novalnet_functions()->update_comments( $subscription, wc_novalnet_format_text( sprintf( __( 'Subscription has been successfully activated on %s.', 'wc-novalnet' ), wc_novalnet_formatted_date() ) . __( ' Next charging date: ', 'wc-novalnet' ) . wc_novalnet_formatted_date( $next_payment_date ) ) );
			} else {

				// Process subscription error.
				$this->subscription_error_process( wc_novalnet_format_text( sprintf( __( 'Subscription activation has been failed due to: %s', 'wc-novalnet' ), wc_novalnet_response_text( $response ) ) ) );
			}

			// Set value to notify subscription updated.
			update_post_meta( $subs_post_id, '_nn_subscription_updated', true );
		}
		return $can_update;
	}

	/**
	 * Customizing admin subscription cancel link to
	 * show Novalnet cancel reasons.
	 *
	 * @since 11.0.0
	 * @param array           $actions      The action data.
	 * @param WC_Subscription $subscription The subscription object.
	 *
	 * @return array
	 */
	public function add_admin_subscription_process( $actions, $subscription ) {

		$novalnet_payment_method = novalnet_instance()->novalnet_functions()->get_novalnet_payment_method( $subscription );
		$subs_post_id = novalnet_instance()->novalnet_functions()->get_novalnet_id( $subscription );
		$novalnet_status = novalnet_instance()->novalnet_functions()->get_novalnet_status( $subscription );

		// Checks for Novalnet payment to overwrite cancel URL.
		$payment_method = $novalnet_payment_method;
		if ( ! empty( $novalnet_payment_method ) ) {
			$payment_method = $novalnet_payment_method;
		} 
		if ( ( wc_novalnet_check_string( $payment_method ) || wc_novalnet_check_string( $novalnet_payment_method ) ) && 'wc-pending-cancel' !== $novalnet_status ) {

			if ( ! empty( $actions['cancelled'] ) ) {
				$action_url = explode( '?', $actions['cancelled'] );
				$actions['cancelled'] = $action_url['0'] . '?novalnet-api=novalnet_subscription_cancel&' . $action_url['1'];
			}

			$novalnet_next_payment = novalnet_instance()->novalnet_functions()->get_novalnet_next_payment( $subscription );

			if ( empty( $novalnet_next_payment ) ) {
				unset( $actions['cancelled'], $actions['on-hold'] );
			}
		}

		return $actions;
	}

	/**
	 * Change payment method process in
	 * shop back-end.
	 *
	 * @since 11.0.0
	 * @param string $payment_type The payment type.
	 * @param array  $post_meta    The post meta data.
	 *
	 * @throws Exception For admin process.
	 */
	public function process_admin_payment_process( $payment_type, $post_meta ) {

		$request = $_REQUEST; // input var okay.

		if ( ! empty( $request ['post_type'] ) && 'shop_subscription' === $request ['post_type'] && wc_novalnet_check_string( $payment_type ) && ! empty( $request ['novalnet_payment_change'] ) ) {
		    
		    $settings = wc_novalnet_payment_config( $payment_type );
		    if ( 'novalnet_cc' === $payment_type && ! empty( $settings [ 'cc_secure_enabled' ] ) ) {
				return '';
		    }

			// Checks for Novalnet payment.
			if ( wc_novalnet_check_payment_method_change( $request, $payment_type ) ) {

				throw new Exception( __( 'Please accept the change of payment method by clicking on the checkbox', 'wc-novalnet' ) );
			}

			if ( in_array( $payment_type, array( 'novalnet_cc', 'novalnet_sepa', 'novalnet_invoice', 'novalnet_prepayment' ), true ) ) {

				if ( get_post_meta( $request ['post_ID'], '_nn_subscription_updated', true ) ) {
					return '';
				}
				$subscription_order = new WC_Order( $request ['post_ID'] );
				$original_post_id   = novalnet_instance()->novalnet_functions()->get_order_post_id( $subscription_order );
				$wc_order           = new WC_Order( $original_post_id );
				$gateway_status     = get_post_meta( $original_post_id, '_novalnet_gateway_status', true );
				if ( ! empty( $gateway_status ) && '100' !== $gateway_status ) {
					throw new Exception( __( 'Your order is not confirmed, kindly contact the merchant.', 'wc-novalnet' ) );
				}

				// Generate basic parameters.
				$data   = $this->generate_payment_parameters( $wc_order, array_merge( $this->global_configurations(), $settings ), $payment_type, true );

				$params = $data ['payment_parameters'];

				// Get payment configurations.
				$order_configuration = wc_novalnet_payment_config( $payment_type );

				if ( in_array( $payment_type, array( 'novalnet_invoice', 'novalnet_prepayment' ), true ) ) {
					$params ['invoice_ref']  = 'BNR-' . $params ['product'] . '-' . $params ['order_no'];
					$params ['invoice_type'] = 'PREPAYMENT';
					if ( 'novalnet_invoice' === $payment_type ) {
						$params ['invoice_type'] = 'INVOICE';
						$params ['due_date'] = wc_novalnet_format_due_date( 14 );
						if ( wc_novalnet_digits_check( $order_configuration ['payment_duration'] ) ) {
							$params ['due_date'] = wc_novalnet_format_due_date( $order_configuration ['payment_duration'] );
						}
					}
					$params ['key'] = '27';
				} elseif ( 'novalnet_sepa' === $request['_payment_method'] ) {
					$request ['novalnet_sepa_account_holder'] = $post_meta ['post_meta'] ['novalnet_sepa_account_holder'] ['value'];
					if ( ! novalnet_instance()->novalnet_functions()->validate_payment_input_field( $request, array(
						'novalnet_sepa_account_holder',
						'novalnet_sepa_hash',
						'novalnet_sepa_unique_id',
					) ) ) {
						throw new Exception( __( 'Your account details are invalid', 'wc-novalnet' ) );
					} elseif ( $order_configuration ['sepa_payment_duration'] >= 7 ) {
						$params ['sepa_due_date'] = wc_novalnet_format_due_date( $order_configuration ['sepa_payment_duration'] );
					}
					$params ['key']                 = '37';
					$params ['bank_account_holder'] = $post_meta ['post_meta'] ['novalnet_sepa_account_holder'] ['value'];
					$params ['sepa_hash']           = $request ['novalnet_sepa_hash'];
					$params ['sepa_unique_id']      = $request ['novalnet_sepa_unique_id'];
					$params ['iban_bic_confirmed']  = '1';
				} elseif ( 'novalnet_cc' === $request['_payment_method'] ) {
					if ( ! novalnet_instance()->novalnet_functions()->validate_payment_input_field( $request, array(
						'novalnet_cc_pan_hash',
						'novalnet_cc_unique_id',
					) ) ) {
						throw new Exception( __( 'Your account details are invalid', 'wc-novalnet' ) );
					}
					$params ['key']       = '6';
					$params ['pan_hash']  = $request ['novalnet_cc_pan_hash'];
					$params ['unique_id'] = $request ['novalnet_cc_unique_id'];
				}
				$name = wc_novalnet_retrieve_name(
					array(
					$request ['_billing_first_name'],
					$request ['_billing_last_name'],
					)
				);

				// Perform payment call.
				if ( ! empty( $request ['_billing_address_2'] ) ) {
					$request ['_billing_address_1'] .= ', ' . $request ['_billing_address_2'];
				}
				$customer_no = 'guest';
				if ( $request ['user_ID'] > 0 ) {
					$customer_no = $request ['user_ID'];
				}

				$customer_parameters = array(
					'gender'           => 'u',
					'customer_no'      => $customer_no,
					'first_name'       => $name['0'],
					'last_name'        => $name['1'],
					'email'            => $request ['_billing_email'],
					'street'           => $request ['_billing_address_1'],
					'search_in_street' => '1',
					'city'             => $request ['_billing_city'],
					'zip'              => $request ['_billing_postcode'],
					'country_code'     => $request ['_billing_country'],
					'country'          => $request ['_billing_country'],
					'tel'              => $request ['_billing_phone'],
				);

				if ( ! novalnet_instance()->novalnet_functions()->validate_customer_parameters( $customer_parameters ) ) {
					throw new Exception( __( 'Customer name/email fields are not valid', 'wc-novalnet' ) );
				}
				$server_response = $this->perform_payment_call( array_merge( $params, $customer_parameters ) );

				// Log for subscription admin change payment method process.
				$this->maintain_debug_log( 'Subscription change payment method from admin call initiated for the order id ' . $request ['post_ID'] . ' and the status was ' . $server_response ['status'] );

				if ( ! $this->success_status( $payment_type, $server_response ) ) {

					// Get message.
					$message = wc_novalnet_response_text( $server_response );

					// Throw exception error for admin change payment method.
					throw new Exception( $message );
				}

				$notice = $this->admin_transaction_success( $server_response, $wc_order, $payment_type, $subscription_order );

				// Update Novalnet comments.
				novalnet_instance()->novalnet_functions()->update_comments( $subscription_order, $notice );

				update_post_meta( $request ['post_ID'], '_nn_version', NN_VERSION );
				wcs_add_admin_notice( $notice );
				update_post_meta( $request ['post_ID'], '_nn_subscription_updated', true );
			}
	    }
	}

	/**
	 * Transaction success process for completing the order.
	 *
	 * @since 10.0.0
	 * @param array    $server_response    The server response data.
	 * @param WC_Order $wc_order           The order object.
	 * @param string   $payment_type       The payment type value.
	 * @param string   $subscription_order The subscription order object.
	 *
	 * @return array|string
	 */
	public function admin_transaction_success( $server_response, $wc_order, $payment_type, $subscription_order ) {
		$payment_param     = '';

		// Retrieve vendor details.
		$vendor_details = novalnet_instance()->novalnet_functions()->get_basic_vendor_details();

		// Get post ID of the Parent order.
		$post_id = novalnet_instance()->novalnet_functions()->get_order_post_id( $wc_order );

		// Fetch transaction tariff id.
		$tariff = $vendor_details ['tariff_id'];

		$subs_id = '';

		if ( $is_subscription = ! empty( $server_response ['subs_id'] ) ) {
			$subs_id = $server_response ['subs_id'];
		}

		// Request sent to process change payment method in Novalnet server.
		$subscription_details = wc_novalnet_get_subs_details( '', $post_id );

		$settings  = wc_novalnet_payment_config( $payment_type );

		// Check for recurring payment type available in Novalnet table for the payment.
		if ( ! empty( $subscription_details ['recurring_payment_type'] ) ) {

			// Check testmode.
			$test_mode         = ! empty( $server_response ['test_mode'] ) || $settings ['test_mode'];

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

			// Form order comments.
			$transaction_comments = novalnet_instance()->novalnet_functions()->form_comments(
				array(
					'test_mode' => $test_mode,
					'tid'       => $server_response ['tid'],
					'title'     => $settings [ 'title_' . $this->language ],
				)
			);

			// Update Novalnet comments.
			novalnet_instance()->novalnet_functions()->update_comments( $subscription_order, $transaction_comments, true, 'transaction_info' );

			return wc_novalnet_format_text( sprintf( __( 'Successfully changed the payment method for next subscription on %s', 'wc-novalnet' ),  wc_novalnet_formatted_date() ) );
		}

		update_post_meta( $post_id, '_novalnet_gateway_status', $server_response['tid_status'] );

		// Check testmode.
		$test_mode = ! empty( $server_response ['test_mode'] ) || $settings ['test_mode'];

		list( $transaction_comments, $novalnet_comments, $bank_details ) = $this->prepare_payment_comments( $server_response, $payment_type, $vendor_details ['product_id'], $settings, $test_mode );

		$transaction_comments = $transaction_comments . $novalnet_comments;

		// Handle subscription process.
		novalnet_instance()->novalnet_functions()->handle_subscription_post_process( $is_subscription, $post_id, $payment_type, $server_response, $wc_order, $vendor_details, $transaction_comments, $tariff );

		// Converting the amount into cents.
		$order_total = novalnet_instance()->novalnet_functions()->get_novalnet_order_total( $wc_order );
		$amount      = wc_novalnet_formatted_amount( $order_total );
		$key         = wc_novalnet_get_payment_type( $payment_type, 'key' );

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
		$user_id = novalnet_instance()->novalnet_functions()->get_novalnet_user_id( $wc_order );
		$customer_details = novalnet_instance()->novalnet_functions()->get_novalnet_billing_detail( $wc_order );

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
			'bank_details'           => $bank_details,
			'payment_params'         => $payment_param,
			), 'novalnet_transaction_detail'
		);

		// Update Novalnet version while processing the current post id.
		update_post_meta( $post_id, '_nn_version', NN_VERSION );

		// Log to notify order got success.
		$this->maintain_debug_log( "Transaction success process completed for the order $post_id TID:" . $server_response ['tid'] );

		return wc_novalnet_format_text( sprintf( __( 'Successfully changed the payment method for next subscription on %s', 'wc-novalnet' ),  wc_novalnet_formatted_date() ) );
	}

	/**
	 * Change payment method Payment form fields / script.
	 *
	 * @since 11.0.0
	 * @param array $payment_meta The payment meta data.
	 *
	 * @return array
	 */
	public static function add_novalnet_payment_meta_details( $payment_meta ) {
		// Enqueue style & script.
		wp_enqueue_script( 'wc-novalnet-sepa-script', novalnet_instance()->plugin_url() . '/assets/js/novalnet-sepa.js', array( 'jquery' ), NN_VERSION, true );
		wp_localize_script(
			'wc-novalnet-sepa-script', 'novalnet_sepa', array(
				'vendor'        => get_option( 'novalnet_vendor_id' ),
				'authcode'      => get_option( 'novalnet_auth_code' ),
				'remote_ip'     => wc_novalnet_get_ip_address(),
				'unique_id'     => wc_novalnet_random_string(), // Generate random string.
				'error_message' => __( 'Your account details are invalid', 'wc-novalnet' ),
				'country_error_message' => __( 'Please select the country', 'wc-novalnet' ),
				'mandate_error' => __( 'Please accept the SEPA direct debit mandate', 'wc-novalnet' ),
				'hash'          => '<input type="hidden" id="novalnet_sepa_hash" name="novalnet_sepa_hash">',
				'hidden_unique_id'     => '<input type="hidden" id="novalnet_sepa_unique_id" name="novalnet_sepa_unique_id">',
				'mandate_text'  => __( 'I hereby grant the SEPA direct debit mandate and confirm that the given IBAN and BIC are correct', 'wc-novalnet' ),
				'admin'         => 'true',
				'sepa_iban_span'         => '<span id="novalnet_sepa_iban_span"></span>',
				'sepa_bic_span'         => '<span id="novalnet_sepa_bic_span"></span>',
			)
		);
		foreach ( array(
		 'novalnet_prepayment',
		 'novalnet_invoice',
		 'novalnet_sepa',
		 'novalnet_paypal',
		) as $payment_type ) {
			$payment_meta[ $payment_type ] = array(
			 'post_meta' => array(
			  'novalnet_payment' => array(
			'label' => ' ',
			  ),
			  'novalnet_payment_change' => array(
			   'value' => '',
			   'label' => ' ',
			  ),
			 ),
			);
		}
		$payment_meta['novalnet_sepa'] = array(
		 'post_meta' => array(
		  'novalnet_payment' => array(
		'label' => ' ',
		  ),
		  'novalnet_payment_change' => array(
		   'value' => '',
		   'label' => ' ',
		  ),
		  'novalnet_sepa_account_holder' => array(
		   'value' => '',
		   'label' => __( 'Account holder', 'wc-novalnet' ) . ' *',
		  ),
		  'novalnet_sepa_bank_country' => array(
		   'value' => '',
		   'label' => __( 'Bank country', 'wc-novalnet' ) . ' *',
		  ),
		  'novalnet_sepa_iban' => array(
		   'value' => '',
		   'label' => __( 'IBAN or Account number', 'wc-novalnet' ) . ' *',
		  ),
		  'novalnet_sepa_bic' => array(
		   'value' => '',
		   'label' => __( 'BIC or Bank code', 'wc-novalnet' ) . ' *',
		  ),
		  'novalnet_sepa_mandate_confirm' => array(
		   'value' => '',
		   'label' => ' ',
		  ),
		 ),
		);

		$payment_meta['novalnet_cc'] = array(
		 'post_meta' => array(
		  'novalnet_payment' => array(
		'label' => ' ',
		  ),
		  'novalnet_payment_change' => array(
		   'value' => '',
		   'label' => ' ',
		  ),
		 ),
		);
		return $payment_meta;
	}

	/**
	 * Add Credit Card iframe.
	 *
	 * @since 11.1.0
	 */
	public function novalnet_subscription_add_iframe() {
		global $post_type;

		// Check for subscription post.
		if ( 'shop_subscription' === $post_type ) {
			$product_activation_key = get_option( 'novalnet_public_key' );

			// Get payment settings.
			$settings = wc_novalnet_payment_config( 'novalnet_cc' );
			if ( isset( $settings ['enabled'] ) && 'yes' === $settings ['enabled'] && empty( $settings ['cc_secure_enabled'] ) ) {
				$configuration = get_option( 'woocommerce_novalnet_cc_iframe_configuration' );
				$configuration ['standard_label'] = $settings ['standard_label'];
				$configuration ['standard_input'] = $settings ['standard_input'];
				$configuration ['standard_css']   = $settings ['standard_css'];
				$configuration ['holder_label_text']        = __( 'Card holder name', 'wc-novalnet' );
				$configuration ['holder_place_holder_text'] = __( 'Name on card', 'wc-novalnet' );
				$configuration ['number_label_text']        = __( 'Card number', 'wc-novalnet' );
				$configuration ['number_place_holder_text'] = __( 'XXXX XXXX XXXX XXXX', 'wc-novalnet' );
				$configuration ['expiry_label_text']        = __( 'Expiry date', 'wc-novalnet' );
				$configuration ['expiry_place_holder_text'] = __( 'MM / YYYY', 'wc-novalnet' );
				$configuration ['cvc_label_text'] 			= __( 'CVC/CVV/CID', 'wc-novalnet' );
				$configuration ['cvc_place_holder_text']    = __( 'XXX', 'wc-novalnet' );
				$configuration ['cvc_hint_text'] 			= __( 'what is this?', 'wc-novalnet' );
				$configuration ['error_text']               = __( 'Your credit card details are invalid', 'wc-novalnet' );

				$language  = wc_novalnet_shop_language();
				$signature = base64_encode( $product_activation_key . '&' .
				wc_novalnet_get_ip_address() . '&' . wc_novalnet_get_ip_address( 'SERVER_ADDR' ) );

				// Enqueue script.
				wp_enqueue_script( 'wc-novalnet-cc-iframe-script', novalnet_instance()->plugin_url() . '/assets/js/novalnet-cc-iframe.js', array( 'jquery' ), NN_VERSION, true );

				$configuration ['admin'] = 'true';

				wp_localize_script( 'wc-novalnet-cc-iframe-script', 'novalnet_cc_iframe', $configuration );

				// Add Iframe and hidden values.
				echo '<iframe onload="novalnet_creditcard_iframe.load_iframe()" scrolling="no" width="100%" id = "novalnet_cc_iframe" src="https://secure.novalnet.de/cc?signature=' . esc_attr( $signature ) . '&ln=' . esc_attr( $language ) . '"></iframe>
				<input type="hidden" name="novalnet_cc_pan_hash" id="novalnet_cc_pan_hash"/>
				<input type="hidden" name="novalnet_cc_unique_id" id="novalnet_cc_unique_id"/>';

				wc_enqueue_js( "
					jQuery( document ).ready(function () {
						jQuery( '.edit_address' ).live( 'click', function( evt ) {
							( 'novalnet_cc' === jQuery( '#_payment_method option:selected' ).val() ) ? jQuery( '#novalnet_cc_iframe' ).show() : jQuery( '#novalnet_cc_iframe' ).hide();
						} );
						jQuery( '#_payment_method' ).live( 'change', function() {
							if ( jQuery( '#_payment_method' ).is(':visible') && 'novalnet_cc' === jQuery( '#_payment_method' ).val() ) {
								jQuery( '#novalnet_cc_iframe' ).show();
							} else {
								jQuery( '#novalnet_cc_iframe' ).hide();
							}
						}).change();
					});
				" );
			}
		}
	}

	/**
	 * Checking for subscription active.
	 *
	 * @since 11.0.0
	 * @param array    $payment_parameters   The payment parameters.
	 * @param WC_Order $wc_order             The order object.
	 * @param string   $subs_tariff          The subscription tariff value.
	 * @param string   $subscription_enabled Check for subscription enable.
	 * @param boolean  $is_change_payment    Check for subscription.
	 *
	 * @return array
	 */
	public function form_subscription_params( $payment_parameters, $wc_order, $subs_tariff, $subscription_enabled, $is_change_payment = true ) {

		// Checks for Novalnet subscription.
		if ( $subscription_enabled && ( $this->is_shop_subscription( $wc_order ) || $is_change_payment ) ) {

			$novalnet_payment_method = novalnet_instance()->novalnet_functions()->get_novalnet_payment_method( $wc_order );
			$novalnet_post_id = novalnet_instance()->novalnet_functions()->get_novalnet_id( $wc_order );

			if ( $is_change_payment ) {
				$subscription_details = wc_novalnet_get_subs_details( '', $novalnet_post_id );
				if ( ! wc_novalnet_check_string( $novalnet_payment_method ) ) {

					// Unset change payment method and process as a new order.
					if ( empty( $subscription_details ['recurring_payment_type'] ) ) {
						if ( isset( WC()->session ) ) {
							WC()->session->__unset( 'novalnet_change_payment_method' );
						}
						$is_change_payment = false;
					}
				}

				// Change order amount as zero.
				$payment_parameters['amount'] = '0';
			}

			$subscription_id    = $this->get_subscription_details( $novalnet_post_id );
			if ( ! empty( $subscription_id ['0'] ) ) {
				$subscription_order = new WC_Subscription( $subscription_id ['0'] );
				$subscription_post_id = $subscription_id ['0'];
			} else {
				$subscription_order   = new WC_Subscription( $novalnet_post_id );
				$subscription_post_id = $novalnet_post_id;
			}

			$novalnet_payment   = $this->is_novalnet_recurring_payment_method( $subscription_order );

			if ( $novalnet_payment && $is_change_payment ) {
				$payment_parameters ['amount'] = 0;
				
				$subs_post_id = novalnet_instance()->novalnet_functions()->get_novalnet_id( $subscription_order );

			    // Send create_payment_ref for zero amount transaction.
			    if ( ! in_array( get_post_meta( $subs_post_id, '_payment_method', true ), array( 'novalnet_prepayment', 'novalnet_invoice' ), true ) ) {
					$payment_parameters ['create_payment_ref'] = '1';
				}
			    $payment_parameters ['subs_py_update'] = wc_novalnet_get_subs_id( $novalnet_post_id );
			    return $payment_parameters;
			}

			$novalnet_subs_trial_period = novalnet_instance()->novalnet_functions()->get_novalnet_trial_period( $subscription_order );
			$novalnet_subs_billing_interval = novalnet_instance()->novalnet_functions()->get_novalnet_billing_interval( $subscription_order );
			$novalnet_subs_billing_period = novalnet_instance()->novalnet_functions()->get_novalnet_billing_period( $subscription_order );

			$create_new_order = ! wc_novalnet_check_string( $novalnet_payment_method );
			if ( $create_new_order ) {

				$novalnet_post_parent = novalnet_instance()->novalnet_functions()->get_novalnet_post_parent( $subscription_order );

				if ( empty( $novalnet_post_parent ) ) {
					$request = $_REQUEST; // input var okay.

					$payment_parameters ['amount'] = wc_novalnet_formatted_amount( $request ['_order_total'] );
				}
				$subscription_details = wc_novalnet_get_subs_details( '', $novalnet_post_id );

				if ( ! empty( $subscription_details ['recurring_payment_type'] ) ) {
					$payment_parameters ['amount'] = '0';
				}

				$subscription_values = array(
				 'free_length' => '',
				 'free_period' => '',
				 'interval'    => $novalnet_subs_billing_interval,
				 'period'      => $novalnet_subs_billing_period,
				 'amount'      => wc_novalnet_formatted_amount( get_post_meta( $subscription_post_id, '_order_total', true ) ), // Converting the amount into cents.
				);
			} else {

				if ( wc_novalnet_compare_version( '3.0.0', WOOCOMMERCE_VERSION, '>=' ) ) {
					// Calculate trial interval.
					$start_timestamp        = $subscription_order->get_time( 'date_created' );
				} else {
					// Calculate trial interval.
					$start_timestamp        = $subscription_order->get_time( 'start' );
				}
				$trial_end_timestamp    = $subscription_order->get_time( 'trial_end' );

				$trial_interval = wcs_estimate_periods_between( $start_timestamp, $trial_end_timestamp, $novalnet_subs_trial_period );

				$subscription_values = array(
				 'free_length' => $trial_interval,
				 'free_period' => $novalnet_subs_trial_period,
				 'interval'    => $novalnet_subs_billing_interval ,
				 'period'      => $novalnet_subs_billing_period,
				 'amount'      => wc_novalnet_formatted_amount( get_post_meta( $subscription_post_id, '_order_total', true ) ), // Converting the amount into cents.
				);
			}

			// Calculate trial period.
			$trial_period     = wc_novalnet_calculate_subscription_period( $subscription_values ['free_length'], $subscription_values ['free_period'] );
			$is_trial_period = empty( $trial_period );
			
			$novalnet_next_payment = novalnet_instance()->novalnet_functions()->get_novalnet_next_payment( $subscription_order );

			if ( ! empty( $novalnet_next_payment ) ) {

				// Calculate recurring period.
				$recurring_period = wc_novalnet_calculate_subscription_period( $subscription_values ['interval'], $subscription_values['period'] );

				// Server parameters.
				$payment_parameters ['tariff_period'] = $recurring_period;
				if ( ! $is_trial_period ) {
					$payment_parameters ['tariff_period'] = $trial_period;
				}
				$payment_parameters ['tariff_period2']        = $recurring_period;
				$payment_parameters ['tariff']                = $subs_tariff;
				$payment_parameters ['tariff_period2_amount'] = $subscription_values['amount'];

				if ( $create_new_order ) {
					$next_payment_date = get_post_meta( $subscription_post_id, '_schedule_next_payment', true );

					// Assign tariff period as days.
					if ( $next_payment_date ) {
						$difference          = date_diff( date_create( date( 'Y-m-d' ) ), date_create( date( 'Y-m-d', strtotime( $next_payment_date ) ) ) );
						if ( $difference->days > 0 ) {
							$payment_parameters ['tariff_period'] = $difference->days . 'd';
						}
					}
				}
			}
		}
		return $payment_parameters;
	}

	/**
	 * Checking for subscription active.
	 *
	 * @since 11.0.0
	 * @param WC_Order $wc_order The order object.
	 *
	 * @return boolean
	 */
	public function is_shop_subscription( $wc_order ) {

		if ( ! is_object( $wc_order ) ) {
			$wc_order = new WC_Order( $wc_order );
		}
		return class_exists( 'WC_Subscriptions_Order' ) && wcs_order_contains_subscription( $wc_order );
	}

	/**
	 * Renewal order count.
	 *
	 * @since 11.0.0
	 * @param WC_Order $wc_order The order object.
	 *
	 * @return int
	 */
	public function get_renewal_order_count( $wc_order ) {

		$get_novalnet_id = novalnet_instance()->novalnet_functions()->get_novalnet_id( $wc_order );
		$subscription = wcs_get_subscription( $get_novalnet_id );
		return count( $subscription->get_related_orders() );
	}

	/**
	 * Renewal order count.
	 *
	 * @since 11.0.0
	 * @param string $url          The URL value.
	 * @param object $subscription The subscription object.
	 *
	 * @return array
	 */
	public function get_subscription_success_url( $url, $subscription ) {
		$url = $subscription->get_view_order_url();
		return array(
			'success_url' => $subscription->get_view_order_url(),
			'notice'      => __( 'Payment method updated.', 'woocommerce-subscriptions' ),
		);
	}

	/**
	 * Check Novalnet recurring payment method.
	 *
	 * @since 11.0.0
	 * @param WC_Order $wc_order The order object.
	 *
	 * @return boolean
	 */
	public function is_novalnet_recurring_payment_method( $wc_order ) {
		$get_novalnet_id = novalnet_instance()->novalnet_functions()->get_novalnet_id( $wc_order );
		return wc_novalnet_check_string( get_post_meta( $get_novalnet_id, '_payment_method', true ) );
	}

	/**
	 * Calculate subscription length.
	 *
	 * @since 11.0.0
	 * @param WC_order $wc_order The order object.
	 *
	 * @return int
	 */
	public function get_order_subscription_length( $wc_order ) {

		$get_novalnet_id = novalnet_instance()->novalnet_functions()->get_novalnet_id( $wc_order );
		$order_item_id = wc_novalnet_get_product_item_value( $get_novalnet_id );

		// Get Subscription length for variable product.
		if ( $variation_id = wc_get_order_item_meta( $order_item_id, '_variation_id' ) ) {
			return get_post_meta( $variation_id, '_subscription_length', true );
		} else {

			// Get Subscription length for the product.
			$item_id       = $wc_order->get_items();
			if ( $subscription_length = get_post_meta( $item_id [ $order_item_id ] ['product_id'], '_subscription_length', true ) ) {
				return $subscription_length;
			}
		}
		return '';
	}

	/**
	 * Activate the subscription.
	 *
	 * @since 11.0.0
	 * @param WC_order $wc_order             The order object.
	 * @param string   $transaction_comments The transaction comments.
	 * @param string   $tid                  The transaction ID.
	 */
	public function activate_subscription_order( $wc_order, $transaction_comments, $tid ) {

		$get_novalnet_id = novalnet_instance()->novalnet_functions()->get_novalnet_id( $wc_order );
		$subscription_details = $this->get_subscription_details( $get_novalnet_id );

		if ( wc_novalnet_compare_version( '3.0.0', WOOCOMMERCE_VERSION, '<' ) ) {
			$subscription_id = ! empty( $subscription_details ['0'] ) ? $subscription_details ['0'] : $wc_order->id;
		} else {
			$subscription_id = ! empty( $subscription_details ['0'] ) ? $subscription_details ['0'] : $get_novalnet_id;
		}

		$subscription_order = new WC_Subscription( $subscription_id );
		novalnet_instance()->novalnet_functions()->update_comments( $subscription_order, $transaction_comments, true, 'transaction_info' );
		$subscription_order->payment_complete( $tid );
	}

	/**
	 * Get subscription change payment method URL
	 *
	 * @since 11.0.0
	 * @param string $return_url Default return URL.
	 *
	 * @return array
	 */
	public function get_subscription_change_payment_return_url( $return_url ) {

		if ( WC()->session->__isset( 'novalnet_change_payment_method' ) ) {
			$subscription = new WC_Order( WC()->session->novalnet_change_payment_method );
			$return_url = $subscription->get_view_order_url();
		}
		return $return_url;
	}

	/**
	 * Subscription error process.
	 *
	 * @since 11.0.0
	 * @param string $message The message value.
	 *
	 * @throws Exception For subscription process.
	 */
	public function subscription_error_process( $message ) {
		throw new Exception( $message );
	}

	/**
	 * Fetch subscription details.
	 *
	 * @since 11.0.0
	 * @param integer $post_id The post id.
	 *
	 * @return array
	 */
	public function get_subscription_details( $post_id ) {

		if ( class_exists( 'WC_Subscriptions' ) ) {
		    return array_keys( wcs_get_subscriptions_for_order( $post_id ) );
		}
		return array();

	}

	/**
	 * Add supports to subscription.
	 *
	 * @since 11.0.0
	 * @param array  $supports     The supports data.
	 * @param string $payment_type The payment type value.
	 * @param string $settings     The payment settings.
	 *
	 * @return array
	 */
	public function get_subscription_supports( $supports, $payment_type, $settings ) {

		// Subscription supports.
		$supports [] = 'subscriptions';
		$supports [] = 'subscription_cancellation';
		$supports [] = 'subscription_suspension';
		$supports [] = 'subscription_reactivation';
		$supports [] = 'subscription_date_changes';
		$supports [] = 'gateway_scheduled_payments';
		$supports [] = 'subscription_amount_changes';
		$supports [] = 'subscription_payment_method_change_customer';
		if ( 'novalnet_paypal' !== $payment_type ) {
			$supports [] = 'subscription_payment_method_change_admin';
		}

		$cc_3d = ( 'novalnet_cc' === $payment_type && ( isset( $settings ['cc_secure_enabled'] ) && $settings ['cc_secure_enabled'] ) );

		// Disable guarantee payment for change payment method.
		if ( in_array( $payment_type, array( 'novalnet_sepa', 'novalnet_invoice' ), true ) && ! empty( $settings ['guarantee_payment'] ) && 'yes' === $settings ['guarantee_payment'] && ! empty( $settings ['force_normal_payment'] ) && 'no' === $settings ['force_normal_payment'] ) {
			$key = array_search( 'subscription_payment_method_change_customer', $supports, true );
			unset( $supports [ $key ] );
		}

		if ( $cc_3d ) {
			$key = array_search( 'subscription_payment_method_change_admin', $supports, true );
			unset( $supports [ $key ] );
		}

		return $supports;
	}

	/**
	 * Check the status of the subscription
	 *
	 * @since 11.0.0
	 * @param array  $request        Request array.
	 * @param string $update_status  Update status of the subscription.
	 * @param string $current_status Current status of the subscription.
	 *
	 * @return boolean
	 */
	public function check_subscription_status( $request, $update_status, $current_status = '' ) {
		return ( ! empty( $request ['action'] ) && wc_novalnet_check_string( $request ['action'], $update_status ) ) || ( ! empty( $request ['action2'] ) && wc_novalnet_check_string( $request ['action2'], $update_status ) ) || ( ! empty( $request ['post_type'] ) && ! empty( $request ['order_status'] ) && ! empty( $request ['post_status'] ) && ( empty( $current_status ) || wc_novalnet_check_string( $request ['post_status'], $current_status ) ) && wc_novalnet_check_string( $request ['order_status'], $update_status )  && 'shop_subscription' === $request ['post_type'] );
	}
}

// Initiate NN_Subscription_2x if subscription plugin available.
new NN_Subscription_2x;
