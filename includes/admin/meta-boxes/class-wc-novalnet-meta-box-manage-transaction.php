<?php
/**
 * Manage transaction
 *
 * Handling Capture / void process
 *
 * @author   Novalnet
 * @category Admin
 * @package  Novalnet-gateway/Admin/Meta Boxes
 * @version  11.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * NN_Meta_Box_Manage_Transaction Class
 */
class NN_Meta_Box_Manage_Transaction extends NN_Admin_Meta_Boxes {


	/**
	 * Output the metabox
	 *
	 * @param WP_Post $post The Order object.
	 */
	public static function output( $post ) {

		include_once dirname( dirname( ( __FILE__ ) ) ) . '/views/html-novalnet-manage-transaction.php';
	}

	/**
	 * Save meta box data
	 *
	 * @param WP_Post $post_id Post ID of the order.
	 * @param int     $tid     Transaction ID of the order.
	 * @param int     $key     Payment key of the transaction.
	 */
	public static function save( $post_id, $tid = '', $key = '' ) {

		$message_type = '';
		$request = $_REQUEST; // Input var okay.

		// Get transaction details & built API params.
		$api_params = wc_novalnet_built_api_params( parent::$transaction_details );
		$api_params['edit_status'] = 1;
		$org_tid = $api_params ['tid'];
		if ( '' !== $tid ) {
			$api_params ['tid'] = $tid;
			$api_params ['key'] = $key;
		}
		if ( empty( $request ['transaction_status'] ) ) {
			$api_params ['status'] = '100';
		} else {
			$api_params['status']  = $request ['transaction_status'];
		}

		// Submit the request.
		$response = novalnet_instance()->novalnet_functions()->submit_request( $api_params );
		if ( ! empty( $tid ) ) {
			$api_params ['tid'] = $org_tid;
			return array_merge( $api_params, $response );
		}
		$wc_order = new WC_Order( $post_id );

		// Log for manage transaction.
		parent::maintain_debug_log( "Manage transaction call initiated for the order id $post_id and the status was " . $response ['status'] );
		if ( wc_novalnet_status_check( $response ) ) {
			$update_param = array(
				'gateway_status' => $response ['tid_status'],
			);
			if ( '90' === $response ['tid_status'] ) {
				$update_param ['callback_amount'] = '0';
			}

			$payment_details = wc_novalnet_unserialize_data( parent::$transaction_details ['bank_details'] );
			if ( isset( $payment_details ['paypal_transaction_id'] ) && isset( $response ['paypal_transaction_id'] ) ) {
				$update_param ['bank_details'] = wc_novalnet_serialize_data( array(
					'paypal_transaction_id' => $response ['paypal_transaction_id'],
					'tid'                   => $api_params ['tid'],
				) );
			}

			if ( wc_novalnet_status_check( $api_params ) ) {
				
				// Notification for CAPTURE process.
				$message = sprintf( __( 'The transaction has been confirmed on %s', 'wc-novalnet' ), wc_novalnet_formatted_date() );

			$wc_order->update_status( get_option( 'novalnet_onhold_success_status' ) );
			} else {

				// Notification for VOID process.
				$message = sprintf( __( 'The transaction has been canceled on %s', 'wc-novalnet' ), wc_novalnet_formatted_date() );
				$wc_order->update_status( get_option( 'novalnet_onhold_cancel_status' ) );
				if ( apply_filters( 'novalnet_check_subscription', $wc_order ) ) {

					// Cancel the subscription.
					do_action( 'novalnet_cancel_subscription', $wc_order );
				}
				WC_Admin_Meta_Boxes::add_error( $message );
			}
			update_post_meta( $post_id, '_novalnet_gateway_status', $api_params['status'] );

			// Update transaction details.
			wc_novalnet_db_update_query( $update_param, array(
				'order_no'       => $post_id,
				)
			);
			novalnet_instance()->novalnet_functions()->update_comments( $wc_order, wc_novalnet_format_text( $message ) );
			$message_type = 1;
		} else {

			// Get message.
			$message = wc_novalnet_response_text( $response );
			WC_Admin_Meta_Boxes::add_error( $message );
			$wc_order->add_order_note( $message );
		}
		
		// Redirect to order view page.
		parent::redirect_process( $post_id, $message_type );
	}
}
