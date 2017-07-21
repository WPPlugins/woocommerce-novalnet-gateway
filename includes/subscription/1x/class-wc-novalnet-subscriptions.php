<?php
/**
 * Handling Novalnet subscription functions.
 *
 * @class    NN_Subscription_1x
 * @version  11.2.0
 * @package  Novalnet-gateway/Classes/
 * @category Class
 * @author   Novalnet
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * NN_Subscription_1x Class.
 */
class NN_Subscription_1x extends NN_Payment_Gateways {


	/**
	 * For change payment method.
	 *
	 * @var $change_payment
	 */
	public $change_payment;

	/**
	 * The single instance of the class.
	 *
	 * @var   NN_Subscription_1x The single instance of the class.
	 * @since 11.0.0
	 */
	protected static $_instance = null;

	/**
	 * Main NN_Subscription_1x Instance.
	 *
	 * Ensures only one instance of NN_Subscription_1x is loaded or can be loaded.
	 *
	 * @since  11.0.0
	 * @static
	 *
	 * @return NN_Subscription_1x Main instance
	 */
	public static function instance() {

		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	/**
	 * NN_Subscription_1x Constructor
	 */
	public function __construct() {

		// Initialize Log & vendor details.
		$this->initialize_basic_details();

		// Subscription script.
		add_action( 'admin_enqueue_scripts', array( &$this, 'novalnet_subscription_enqueue_scripts' ) );

		// Get return URL for subscription change payment method.
		add_action( 'novalnet_return_url', array( &$this, 'get_subscription_change_payment_return_url' ) );

		// Get error return URL for subscription change payment method.
		add_action( 'novalnet_error_return_url', array( &$this, 'get_subscription_change_payment_return_url' ) );

		// Process subscription activate.
		add_action( 'novalnet_activate_subscription', array( $this, 'activate_subscription_order' ) );

		// Process subscription cancel.
		add_action( 'novalnet_cancel_subscription', array( $this, 'cancel_subscription' ) );

		// Return subscription supports.
		add_filter( 'novalnet_subscription_supports', array( $this, 'get_subscription_supports' ), 10, 3 );

		// Return the current recurring payment method.
		add_filter( 'novalnet_get_recurring_payment_method', array( $this, 'is_novalnet_recurring_payment_method' ) );

		// Checking whether subscription order or not.
		add_filter( 'novalnet_check_subscription', array( $this, 'is_shop_subscription' ) );

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

		// Form  subscription parameters.
		add_filter( 'novalnet_form_subscription_parameters', array( $this, 'form_subscription_params' ), 10, 5 );

		// Get subscription details.
		add_filter( 'novalnet_get_subscription_details', array( $this, 'get_subscription_details' ) );

		// Customize front-end subscriptiion cancel URL.
		add_filter( 'woocommerce_my_account_my_subscriptions_actions', array( $this, 'add_myaccount_subscription_process' ), 11, 2 );

		// Customize back-end subscriptiion cancel URL.
		add_filter( 'woocommerce_subscriptions_list_table_column_status_content', array( $this, 'add_admin_subscription_process' ), 10, 3 );

		// Customize suspend/ cancel link in back-end.
		add_filter( 'woocommerce_subscriptions_list_table_actions', array( $this, 'customize_subscription_action' ), 10, 2 );

		// Process subscription action.
		add_filter( 'woocommerce_subscription_can_be_changed_to_on-hold', array( $this, 'suspend_subscription_process' ), 10, 3 );
		add_filter( 'woocommerce_subscription_can_be_changed_to_active', array( $this, 'reactivate_subscription_process' ), 10, 3 );
		add_filter( 'woocommerce_subscription_can_be_changed_to_cancelled', array( $this, 'cancel_subscription_process' ), 10, 3 );

		// Process next payment date change.
		add_action( 'woocommerce_subscription_set_next_payment_date', array( &$this, 'update_next_payment_date_process' ), 10, 3 );

		// Process recurring amount change.
		add_action( 'woocommerce_process_shop_order_meta', array( $this, 'perform_subscription_recurring_amount_update' ) );

		// Action to unset postmeta.
		add_action( 'suspended_subscription', array( $this, 'unset_post_meta' ), 10, 2 );
		add_action( 'unable_to_suspend_subscription', array( $this, 'unset_post_meta' ), 10, 2 );
		add_action( 'reactivated_subscription', array( $this, 'unset_post_meta' ), 10, 2 );
		add_action( 'cancelled_subscription', array( $this, 'unset_post_meta' ), 10, 2 );
		add_action( 'unable_to_cancel_subscription', array( $this, 'unset_post_meta' ), 10, 2 );
		add_action( 'admin_init', array( $this, 'unset_post_meta' ) );
	}

	/**
	 * Adding subscription script.
	 *
	 * @since 11.0.0
	 */
	public function novalnet_subscription_enqueue_scripts() {

		if ( isset( $_GET ['page'] ) &&  'subscriptions' === $_GET ['page'] ) { // input var okay.

			// Enqueue style & script.
			wp_enqueue_script( 'wc-novalnet-subscription-script', novalnet_instance()->plugin_url() . '/assets/js/novalnet-subscription.js', array( 'jquery' ), NN_VERSION, true );
			wp_localize_script(
				'wc-novalnet-subscription-script', 'novalnet_subscription', array(
				'reason_list'         => wc_novalnet_subscription_cancel_form(), // Display Subscription cancel reason.
				'error_message'       => __( 'Please select the reason of subscription cancellation', 'wc-novalnet' ),
				)
			);
		}
	}

	/**
	 * Unset postmeta.
	 *
	 * @since 11.0.0
	 * @param int    $user_id          The user id.
	 * @param string $subscription_key The subscription key.
	 */
	public function unset_post_meta( $user_id = '', $subscription_key = '' ) {

		$request = $_REQUEST; // input var okay.
		if ( ! empty( $request ['new_status'] ) && ! empty( $request ['subscription'] ) ) {
			$subscription_key = $request ['subscription'];
		}
		$subscription_key = explode( '_', $subscription_key );
		delete_post_meta( $subscription_key ['0'], '_nn_subscription_updated' );
	}

	/**
	 * Cancel subscription process in shop.
	 *
	 * @since 11.0.0
	 * @param WC_Order $wc_order The order object.
	 */
	public function cancel_subscription( $wc_order ) {

		if ( $this->get_order_subscription_length( $wc_order ) !== '1' ) {
			WC_Subscriptions_Manager::cancel_subscriptions_for_order( $wc_order );
		}
	}

	/**
	 * Create / Initiate recurring order.
	 *
	 * @since 11.0.0
	 * @param WC_Subscription $subscription_order   The subscription order object.
	 * @param array           $subscription_details The subscription details.
	 *
	 * @return int
	 */
	public function create_renewal_order( $subscription_order, $subscription_details ) {

		return WC_Subscriptions_Renewal_Order::generate_paid_renewal_order( $subscription_order->user_id, $subscription_details ['subscription_key'] );
	}

	/**
	 * Return Renewal object.
	 *
	 * @since 11.0.0
	 * @param int $renewal_order_id The renewal order id.
	 */
	public function get_renewal_object( $renewal_order_id ) {

		// Renewal order object.
		return new WC_Order( $renewal_order_id );
	}

	/**
	 * Update Next payment date.
	 *
	 * @since 11.0.0
	 * @param date            $date                 The next payment date.
	 * @param WC_Subscription $subscription         The subscription order object.
	 * @param array           $subscription_details The subscription details.
	 */
	public function update_next_payment_date( $date, $subscription, $subscription_details ) {

		WC_Subscriptions_Manager::update_next_payment_date( date( 'Y-m-d H:i:s', strtotime( $date ) ), $subscription_details ['subscription_key'], $subscription->user_id, 'user' );
	}

	/**
	 * Update recurring amount.
	 *
	 * @since 11.0.0
	 * @param int $post_id The post id.
	 */
	public function perform_subscription_recurring_amount_update( $post_id ) {

		$wc_order = new WC_Order( $post_id );
		$request = $_REQUEST; // input var okay.

		// Check Novalnet payment.
		if ( wc_novalnet_check_string( $wc_order->recurring_payment_method ) ) {

			// Return the status if subscription already updated.
			if ( get_post_meta( $post_id, '_nn_subscription_updated', true ) ) {
				return false;
			}
			$subscription_details = wc_novalnet_order_no_details( $post_id, 'novalnet_subscription_details' );
			$update_amount = sprintf( '%0.2f',  str_replace( ',', '.', $request ['_order_recurring_total'] ) ) * 100;

			// Check for amount update.
			if ( ! empty( $request ['_order_recurring_total'] ) && $subscription_details ['recurring_amount'] !== $update_amount ) {
				$transaction_details = wc_novalnet_get_transaction_details( $post_id, 'subscription' );

				// Submit XML call.
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
				$this->maintain_debug_log( 'Subscription amount update call initiated for the order id $post_id and the status was ' . $response ['status'] );
				if ( wc_novalnet_status_check( $response ) ) {
						update_post_meta( $wc_order->id, '_nn_subscription_updated', true );
						$message = wc_novalnet_format_text( sprintf( __( 'Subscription recurring amount %s has been updated successfully', 'wc-novalnet' ), wc_novalnet_shop_amount_format( $update_amount / 100 ) ) );
						novalnet_instance()->novalnet_functions()->update_comments( $wc_order, $message );
						wc_novalnet_db_update_query(
							array(
							'recurring_amount' => $update_amount,
							), array(
							'order_no' => $post_id,
							), 'novalnet_subscription_details'
						);
				} else {
					$message = wc_novalnet_format_text( sprintf( __( 'Amount update for the next recurring process is failed in Novalnet due to %s', 'wc-novalnet' ),  wc_novalnet_response_text( $response ) ) );
					novalnet_instance()->novalnet_functions()->update_comments( $wc_order, $message );
					WC_Admin_Meta_Boxes::add_error( $message );
					wc_novalnet_safe_redirect(
						add_query_arg(
							array(
							'action'  => 'edit',
							'post'    => $wc_order->id,
							'message' => '',
							)
						)
					);
				}
			}
		}
	}

	/**
	 * Changing Next payment date process.
	 *
	 * @since 11.0.0
	 * @param boolean $is_set           For process next payment date.
	 * @param date    $next_payment     The next payment date.
	 * @param string  $subscription_key The subscription key.
	 *
	 * @return boolean
	 */
	public function update_next_payment_date_process( $is_set, $next_payment, $subscription_key ) {

		$request = $_REQUEST; // input var okay.
		if ( ! empty( $request ['action'] ) && 'wcs_update_next_payment_date' === $request ['action'] ) {
			$get_order_key = explode( '_', $subscription_key );
			$order_id = $get_order_key ['0'];

			// Check Novalnet payment.
			if ( wc_novalnet_check_string( get_post_meta( $order_id, '_recurring_payment_method', true ) ) ) {
				$new_recurring_date = date( 'Y-m-d', $next_payment );

				$subscription       = WC_Subscriptions_Manager::get_subscription( $subscription_key );

				if ( date( 'Y-m-d', strtotime( $new_recurring_date ) ) !== $new_recurring_date ) {
					$response ['message'] = sprintf( '<div class="error">%s</div>', __( 'Due date is not valid', 'wc-novalnet' ) );
					return false;
				}

				$wc_order = new WC_Order( $order_id );
				$transaction_details  = wc_novalnet_get_transaction_details( $wc_order->id, 'subscription' );
				$subscription_details = wc_novalnet_order_no_details( $order_id, 'novalnet_subscription_details' );
				$updated_recurring    = date( 'Y-m-d', strtotime( $subscription_details ['next_payment_date'] ) );
				$expiry_date          = date( 'Y-m-d', strtotime( $subscription ['expiry_date'] ) );
				$new_date_object      = new DateTime( $new_recurring_date );
				$next_payment_object  = new DateTime( $updated_recurring );
				$date_difference = $next_payment_object->diff( $new_date_object );

				if ( ( ( strtotime( $new_recurring_date ) < strtotime( $updated_recurring ) ) || $date_difference->invert )  || ( ! empty( $subscription ['expiry_date'] )&& ! empty( $expiry_date ) && ( strtotime( $new_recurring_date ) >= strtotime( $expiry_date ) ) ) ) {
					return false;
				} elseif ( 0 < $date_difference->days  ) {

					// Submit XML call.
					$response = novalnet_instance()->novalnet_functions()->perform_xmlrequest(
						array(
						'vendor_id'       => $transaction_details ['vendor_id'],
						'vendor_authcode' => $transaction_details ['auth_code'],
						'product_id'      => $transaction_details ['product_id'],
						'request_type'    => 'SUBSCRIPTION_PAUSE',
						'tid'             => $transaction_details ['tid'],
						'subs_id'         => $transaction_details ['subs_id'],
						'pause_period'    => $date_difference->days,
						'pause_time_unit' => 'd',
						)
					);

					// Log for change next recurring date process.
					$this->maintain_debug_log( "Subscription change next payment date call initiated for the order id $order_id and the status was " . $response ['status'] );
					if ( wc_novalnet_status_check( $response ) ) {
						$next_payment_date = wc_novalnet_next_subscription_date( $response );
						$message = wc_novalnet_format_text( sprintf( __( 'Subscription renewal date has been successfully changed to %s', 'wc-novalnet' ),  wc_novalnet_formatted_date( $next_payment_date ) ) );

						// Update Novalnet comments.
						novalnet_instance()->novalnet_functions()->update_comments( $wc_order, $message );

						// Update Next payment date in Novalnet subscription table.
						wc_novalnet_db_update_query(
							array(
							'next_payment_date' => $next_payment_date,
							), array(
							'order_no' => $subscription ['order_id'],
							), 'novalnet_subscription_details'
						);
						return $is_set;
					} else {
						$message = wc_novalnet_format_text( sprintf( __( 'Next recurring date change process is failed in Novalnet due to: %s', 'wc-novalnet' ),  wc_novalnet_response_text( $response ) ) );
						novalnet_instance()->novalnet_functions()->update_comments( $wc_order, $message );
						return false;
					}
				}
			}
		}
		return $is_set;
	}

	/**
	 * Cancel the subscription process.
	 *
	 * @since 11.0.0
	 * @param boolean         $can_update   For process cancel action.
	 * @param WC_Subscription $subscription The subscription object.
	 * @param WC_Order        $wc_order     The order object.
	 *
	 * @return boolean
	 */
	public function cancel_subscription_process( $can_update, $subscription, $wc_order ) {

		$request = $_REQUEST; // input var okay.
		if ( $can_update && wc_novalnet_check_string( $wc_order->payment_method ) && ! empty( $request ['new_status'] ) && 'cancelled' === $request ['new_status'] ) {

			// Return the status if subscription already updated.
			if ( get_post_meta( $wc_order->id, '_nn_subscription_updated', true ) ) {
				return $can_update;
			}

			// Get transaction details.
			$transaction_details = wc_novalnet_get_transaction_details( $wc_order->id );

			// Get subscrition cancellation reason.
			$reason = wc_novalnet_subscription_cancel_list();
			$response = novalnet_instance()->novalnet_functions()->submit_request(
				array_merge(
					wc_novalnet_built_api_params( $transaction_details ), array(
					'cancel_sub'    => 1,
					'cancel_reason' => $reason [ $request['novalnet_subscription_cancel_reason'] ], // input var okay.
					'lang'          => $this->language,
					)
				)
			);

			// Log for subscription suspend process.
			$this->maintain_debug_log( 'Subscription cancellation call initiated for the order id ' . $wc_order->id . ' and the status was ' . $response ['status'] );
			if ( wc_novalnet_status_check( $response ) ) {

				// Update Novalnet comments.
				novalnet_instance()->novalnet_functions()->update_comments( $wc_order, wc_novalnet_format_text( sprintf( __( 'Subscription has been canceled due to: %s', 'wc-novalnet' ), $reason[ $request['novalnet_subscription_cancel_reason'] ] ) ) ); // input var okay.

				// Update suspended reason and date in Novalnet subscription table.
				wc_novalnet_db_update_query(
					array(
					'termination_at' => date( 'Y-m-d H:i:s' ),
					'termination_reason' => $reason[ $request['novalnet_subscription_cancel_reason'] ], // input var okay.
					), array(
					'order_no' => $wc_order->id,
					), 'novalnet_subscription_details'
				);

			} else {

				$message = wc_novalnet_format_text( sprintf( __( 'Cancellation of subscription is failed due to: %s', 'wc-novalnet' ), wc_novalnet_response_text( $response ) ) );
				novalnet_instance()->novalnet_functions()->update_comments( $wc_order, $message );

				// Process subscription error.
				$this->subscription_error_process( $message, 'error' );
				return false;
			}

			// Set value to notify subscription updated.
			update_post_meta( $wc_order->id, '_nn_subscription_updated', true );
		}
		return $can_update;
	}

	/**
	 * Suspend the subscription process.
	 *
	 * @since 11.0.0
	 * @param boolean         $can_update   For process suspend action.
	 * @param WC_Subscription $subscription The subscription object.
	 * @param WC_Order        $wc_order     The order object.
	 *
	 * @return boolean
	 */
	public function suspend_subscription_process( $can_update, $subscription, $wc_order ) {

		$request = $_REQUEST; // input var okay.

		 // Checks Novalnet payment.
		if ( $can_update && wc_novalnet_check_string( $wc_order->payment_method ) && ! empty( $request ['new_status'] ) && 'on-hold' === $request ['new_status'] ) { // input var okay.

			// Return the status if subscription already updated.
			if ( get_post_meta( $wc_order->id, '_nn_subscription_updated', true ) ) {
				return $can_update;
			}

			// Get transaction details.
			$transaction_details = wc_novalnet_get_transaction_details( $wc_order->id, 'subscription' );

			// Perform XML request.
			$response = novalnet_instance()->novalnet_functions()->perform_xmlrequest(
				array(
				'vendor_id'       => $transaction_details ['vendor_id'],
				'vendor_authcode' => $transaction_details ['auth_code'],
				'product_id'      => $transaction_details ['product_id'],
				'request_type'    => 'SUBSCRIPTION_PAUSE',
				'tid'             => $transaction_details ['tid'],
				'subs_id'         => $transaction_details ['subs_id'],
				'suspend'         => 1,
				)
			);

			// Log for subscription suspend process.
			$this->maintain_debug_log( 'Subscription suspend call initiated for the order id ' . $wc_order->id . ' and the status was ' . $response ['status'] );
			if ( wc_novalnet_status_check( $response ) ) {
				$message = wc_novalnet_format_text( sprintf( __( 'This subscription transaction has been suspended on %s', 'wc-novalnet' ), wc_novalnet_formatted_date() ) );

				// Update suspended date in Novalnet subscription table.
				wc_novalnet_db_update_query(
					array(
					'suspended_date' => date( 'Y-m-d H:i:s' ),
					), array(
					'order_no' => $wc_order->id,
					), 'novalnet_subscription_details'
				);

				// Update Novalnet comments.
				novalnet_instance()->novalnet_functions()->update_comments( $wc_order, $message );
			} else {

				// Process subscription error.
				$this->subscription_error_process( wc_novalnet_format_text( sprintf( __( 'Subscription suspend has been failed due to: %s', 'wc-novalnet' ), wc_novalnet_response_text( $response ) ) ), 'error' );
				return false;
			}

			// Set value to notify subscription updated.
			update_post_meta( $wc_order->id, '_nn_subscription_updated', true );
		}
		return $can_update;
	}

	/**
	 * Reactivate the subscription process.
	 *
	 * @since 11.0.0
	 * @param boolean         $can_update   For process reactivate action.
	 * @param WC_Subscription $subscription The subscription object.
	 * @param WC_Order        $wc_order     The order object.
	 *
	 * @return boolean
	 */
	public function reactivate_subscription_process( $can_update, $subscription, $wc_order ) {

		$request = $_REQUEST; // input var okay.

		// Checks Novalnet payment.
		if ( $can_update && wc_novalnet_check_string( $wc_order->payment_method ) && ! empty( $request ['new_status'] ) && 'active' === $request ['new_status'] ) { // input var okay.

			// Return the status if subscription already updated.
			if ( get_post_meta( $wc_order->id, '_nn_subscription_updated', true ) ) {
				return $can_update;
			}

			// Get transaction details.
			$transaction_details = wc_novalnet_get_transaction_details( $wc_order->id, 'subscription' );
			$period = $subscription ['period'];
			$interval = $subscription ['interval'];

			// Get subscription details.
			$subscription_details     = wc_novalnet_get_subs_details( '', $wc_order->id );
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
			$this->maintain_debug_log( 'Subscription reactive call initiated for the order id ' . $wc_order->id . ' and the status was ' . $response ['status'] );
			if ( wc_novalnet_status_check( $response ) ) {
				$next_payment_date = wc_novalnet_next_subscription_date( $response );

				$this->update_next_payment_date(
					$next_payment_date, $wc_order, array(
					'subscription_key' => $subscription ['order_id'] . '_' . $subscription ['product_id'],
					)
				);

				// Update Next payment date in Novalnet subscription table.
				wc_novalnet_db_update_query(
					array(
					'next_payment_date' => $next_payment_date,
					), array(
					'order_no' => $wc_order->id,
					), 'novalnet_subscription_details'
				);

				novalnet_instance()->novalnet_functions()->update_comments( $wc_order, wc_novalnet_format_text( sprintf( __( 'Subscription has been successfully activated on %s', 'wc-novalnet' ), wc_novalnet_formatted_date() ) . wc_novalnet_format_text( __( ' Next charging date: ', 'wc-novalnet' ) . wc_novalnet_formatted_date( $next_payment_date ) ) ) );
			} else {

				// Process subscription error.
				$this->subscription_error_process( wc_novalnet_format_text( sprintf( __( 'Subscription activation has been failed due to: %s', 'wc-novalnet' ), wc_novalnet_response_text( $response ) ) ), 'error' );
				return false;
			}

			// Set value to notify subscription updated.
			update_post_meta( $wc_order->id, '_nn_subscription_updated', true );
		}
		return $can_update;
	}

	/**
	 * Customizing subscription cancel link to
	 * show Novalnet cancel reasons in front-end.
	 *
	 * @since 11.0.0
	 * @param array           $actions      The action data.
	 * @param WC_Subscription $subscription The subscription object.
	 *
	 * @return array
	 */
	public function add_myaccount_subscription_process( $actions, $subscription ) {

		// Checks for Novalnet payment to overwrite cancel URL.
		foreach ( $subscription as $key => $val ) {
			$payment = get_post_meta( $val ['order_id'], '_recurring_payment_method', true );
			$is_subscription = wc_novalnet_check_string( $payment );
			if ( $is_subscription ) {
				// Hide customer subscription cancel, reactivate, suspend, pay options.
				foreach ( array(
				 'cancel',
				 'suspend',
				 'reactivate',
				 'pay',
				) as $value ) {
					if ( ! empty( $actions [ $key ] [ $value ] ) ) {
						unset( $actions [ $key ] [ $value ] );
					}
				}
				$gateway_status = get_post_meta( $val ['order_id'], '_novalnet_gateway_status', true );

				// Checks Novalnet TID status and restrict change payment method option.
				if ( ! empty( $gateway_status ) && '100' !== $gateway_status && ! empty( $actions [ $key ] ['change_payment_method'] ) ) {
					unset( $actions [ $key ] ['change_payment_method'] );
				}
			}
		}

		return $actions;
	}

	/**
	 * Customizing subscription cancel link to
	 * show Novalnet cancel reasons in back-end.
	 *
	 * @since 11.0.0
	 * @param string   $column_content The column value.
	 * @param WC_Order $item           The order items.
	 * @param array    $actions        The action data.
	 *
	 * @return string
	 */
	public function add_admin_subscription_process( $column_content, $item, $actions ) {

		// Checking for Novalnet payment.
		if ( wc_novalnet_check_string( get_post_meta( $item ['order_id'], '_recurring_payment_method', true ) ) && ! empty( $actions ['cancelled'] ) ) {

			// Customizing the subscription cancel URL to display subscription cancel reasons.
			$cancel_url = explode( 'new_status=cancelled', $column_content );
			$column_content = implode( 'new_status=cancelled&novalnet-api=novalnet_subscription_cancel', $cancel_url );
		}
		return $column_content;
	}

	/**
	 * Customizing subscription suspend/ cancel.
	 *
	 * @since 11.0.0
	 * @param WC_Order $actions The action data.
	 * @param array    $item    The order items.
	 *
	 * @return array
	 */
	public function customize_subscription_action( $actions, $item ) {

		// Checking for Novalnet payment.
		if ( wc_novalnet_check_string( get_post_meta( $item ['order_id'], '_recurring_payment_method', true ) ) ) {

			$next_payment_timestamp = WC_Subscriptions_Manager::get_next_payment_date( $item['subscription_key'], $item['user_id'], 'timestamp' );

			if ( empty( $next_payment_timestamp ) ) {
				unset( $actions['cancelled'], $actions['on-hold'] );
			}
		}
		return $actions;
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

		$novalnet_payment = $this->is_novalnet_recurring_payment_method( $wc_order );

		if ( $is_change_payment && $novalnet_payment ) {
			$payment_parameters ['amount'] = '0';
			$payment_parameters ['subs_py_update'] = wc_novalnet_get_subs_id( $wc_order->id );
			return $payment_parameters;
		}

		// Checks for Novalnet subscription.
		if ( $this->is_shop_subscription( $wc_order ) && $subscription_enabled ) {

			// Calculate trial period.
			$trial_period = wc_novalnet_calculate_subscription_period( WC_Subscriptions_Order::get_subscription_trial_length( $wc_order ), WC_Subscriptions_Order::get_subscription_trial_period( $wc_order ) );

			$is_trial_period = empty( $trial_period );

			if ( $this->get_order_subscription_length( $wc_order ) !== '1' || ! $is_trial_period ) {

				// Calculate recurring period.
				$recurring_period = wc_novalnet_calculate_subscription_period( WC_Subscriptions_Order::get_subscription_interval( $wc_order ), WC_Subscriptions_Order::get_subscription_period( $wc_order ) );

				// Server parameters.
				$payment_parameters ['tariff_period'] = $recurring_period;
				if ( ! $is_trial_period ) {
					$payment_parameters ['tariff_period'] = $trial_period;
				}
				$payment_parameters ['tariff_period2']        = $recurring_period;
				$payment_parameters ['tariff']                = $subs_tariff;
				$payment_parameters ['tariff_period2_amount'] = novalnet_instance()->novalnet_functions()->get_recurring_amount_cart( $wc_order );

				if ( $is_change_payment ) {
					$payment_parameters ['amount'] = '0';
					$subscription_key = WC_Subscriptions_Manager::get_subscription_key( $wc_order->id );
					$next_payment_date = date( 'Y-m-d', WC_Subscriptions_Manager::get_next_payment_date( $subscription_key, 'timestamp', $wc_order->order_date ) );

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
	 * Check Novalnet recurring payment method.
	 *
	 * @since 11.0.0
	 * @param WC_Order $wc_order The order object.
	 *
	 * @return boolean
	 */
	public function is_novalnet_recurring_payment_method( $wc_order ) {
		return wc_novalnet_check_string( get_post_meta( $wc_order->id, '_recurring_payment_method', true ) );
	}

	/**
	 * Checking for subscription active.
	 *
	 * @since 11.0.0
	 * @param WC_order $wc_order The order object.
	 *
	 * @return boolean
	 */
	public function is_shop_subscription( $wc_order ) {

		return class_exists( 'WC_Subscriptions_Order' ) && WC_Subscriptions_Order::order_contains_subscription( $wc_order );
	}

	/**
	 * Renewal order count.
	 *
	 * @since 11.0.0
	 * @param WC_order $wc_order The order object.
	 *
	 * @return int
	 */
	public function get_renewal_order_count( $wc_order ) {

		return WC_Subscriptions_Renewal_Order::get_renewal_order_count( $wc_order->id );
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

		$order_item_id = wc_novalnet_get_product_item_value( $wc_order->id );
		$item_id       = $wc_order->get_items();
		if ( ! empty( $item_id [ $order_item_id ] ['subscription_length'] ) ) {
			return $item_id [ $order_item_id ] ['subscription_length'];
		}
		return '';
	}

	/**
	 * Activate the subscription.
	 *
	 * @since 11.0.0
	 * @param WC_order $wc_order The order object.
	 */
	public function activate_subscription_order( $wc_order ) {

		// Activate subscription.
		WC_Subscriptions_Manager::activate_subscriptions_for_order( $wc_order );
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
		return $return_url;
	}

	/**
	 * Subscription error process.
	 *
	 * @since 11.0.0
	 * @param string $message The message value.
	 * @param string $type    The type value.
	 */
	public function subscription_error_process( $message, $type = 'messages' ) {

		// Throw error message for front-end since can't customize the admin error message.
		if ( ! wc_novalnet_check_admin() ) {
			wc_add_notice( $message, $type );
			wc_novalnet_safe_redirect( get_permalink( woocommerce_get_page_id( 'myaccount' ) ) );
		}
	}

	/**
	 * Fetch subscription details.
	 *
	 * @since 11.0.0
	 * @param int $post_id The post id.
	 *
	 * @return array
	 */
	public function get_subscription_details( $post_id ) {

		$subscription_key = WC_Subscriptions_Manager::get_subscription_key( $post_id );
		return array_merge(
			explode( '_', $subscription_key ), array(
			'subscription_key' => $subscription_key,
			)
		);
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

		if ( 'novalnet_paypal' !== $payment_type ) {
			$supports [] = 'subscription_payment_method_change';
		}

		// Disable Credit card 3D secure payment for change payment method.
		if ( 'novalnet_cc' === $payment_type && isset( $settings ['cc_secure_enabled'] ) && $settings ['cc_secure_enabled'] ) {
			$key = array_search( 'subscription_payment_method_change', $supports, true );
			unset( $supports [ $key ] );
		}

		// Disable guarantee payment for change payment method.
		if ( in_array( $payment_type, array( 'novalnet_sepa', 'novalnet_invoice' ), true ) && ! empty( $settings ['guarantee_payment'] ) && 'yes' === $settings ['guarantee_payment'] && ! empty( $settings ['force_normal_payment'] ) && 'no' === $settings ['force_normal_payment'] ) {
			$key = array_search( 'subscription_payment_method_change', $supports, true );
			unset( $supports [ $key ] );
		}

		return $supports;
	}
}

// Initiate NN_Subscription_1x if subscription plugin available.
new NN_Subscription_1x;
