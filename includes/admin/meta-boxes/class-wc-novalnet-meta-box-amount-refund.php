<?php
/**
 * Amount refund
 *
 * Handling amount refund process
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
 * NN_Meta_Box_Amount_Refund Class
 */
class NN_Meta_Box_Amount_Refund extends NN_Admin_Meta_Boxes {


	/**
	 * Output the metabox
	 *
	 * @param WP_Post $post                The Order object.
	 * @param array   $transaction_details Novalnet transaction details of the order.
	 */
	public static function output( $post, $transaction_details ) {

		include_once dirname( dirname( ( __FILE__ ) ) ) . '/views/html-novalnet-amount-refund.php';
	}

	/**
	 * Save meta box data
	 *
	 * @param WP_Post $post_id Post ID of the order.
	 */
	public static function save( $post_id ) {

		$message_type = '';
		$request = $_REQUEST; // Input var okay.

		$refund_amount = sanitize_text_field( $request ['novalnet_refund_amount'] );

		// Get transaction details & built API params.
		$api_params = array_merge(
			wc_novalnet_built_api_params( parent::$transaction_details ), array(
			'refund_request' => '1',
			'refund_param'   => $refund_amount,
			)
		);
		if ( ! empty( $request ['novalnet_refund_reference'] ) ) {

			$refund_reference = sanitize_text_field( $request ['novalnet_refund_reference'] );
			$api_params ['refund_ref'] = $refund_reference;
		}

		if ( ! empty( $request ['refund_payment_type'] ) && 'sepa' === $request ['refund_payment_type'] ) {
			$api_params ['account_holder']   = $request ['sepa_account_holder'];
			$api_params ['iban']             = $request ['sepa_iban'];
			$api_params ['bic']              = $request ['sepa_bic'];
		}

		// Submit the request.
		$response = novalnet_instance()->novalnet_functions()->submit_request( array_filter( $api_params ) );

		$wc_order = new WC_Order( $post_id );

		// Log for amount refund.
		parent::maintain_debug_log( "Amount refund call initiated for the order id $post_id and the status was " . $response ['status'] );
		if ( wc_novalnet_status_check( $response ) ) {
			$message = sprintf( __( 'The refund has been executed for the TID: %1$s with the amount of %2$s.', 'wc-novalnet' ), $api_params['tid'], strip_tags( wc_novalnet_shop_amount_format( $refund_amount / 100 ) ) );

			// Get the new TID.
			if ( ! empty( $response ['paypal_refund_tid'] ) ) {
				$message .= sprintf( __( ' Your new TID for the refund amount: %s', 'wc-novalnet' ), $response ['paypal_refund_tid'] );
			} elseif ( ! empty( $response['tid'] ) ) {
				$message .= sprintf( __( ' Your new TID for the refund amount: %s', 'wc-novalnet' ), $response ['tid'] );
			}

			// Update transaction details.
			wc_novalnet_db_update_query(
				array(

				// Calculating refunded amount.
				'refunded_amount' => parent::$transaction_details ['refunded_amount'] + $api_params ['refund_param'],
				'gateway_status'  => $response ['tid_status'],
				), array(
				'order_no'        => $post_id,
				)
			);

			// Update order comments.
			novalnet_instance()->novalnet_functions()->update_comments( $wc_order, wc_novalnet_format_text( $message ) );

			if ( '100' !== $response ['tid_status'] ) {
				$wc_order->update_status( 'refunded' );
				update_post_meta( $post_id, '_novalnet_gateway_status', $response ['tid_status'] );

				// Cancel subscription.
				if ( apply_filters( 'novalnet_check_subscription', $wc_order ) ) {

					do_action( 'novalnet_cancel_subscription', $wc_order );
				}
			}

			$message_type = '1';
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
