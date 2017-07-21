<?php
/**
 * Amount book
 *
 * Handling amount booking process
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
 * NN_Meta_Box_Amount_Book Class
 */
class NN_Meta_Box_Amount_Book extends NN_Admin_Meta_Boxes {


	/**
	 * Output the metabox.
	 *
	 * @param WP_Post $post The Order object.
	 */
	public static function output( $post ) {

		include_once dirname( dirname( ( __FILE__ ) ) ) . '/views/html-novalnet-amount-book.php';
	}

	/**
	 * Save meta box data.
	 *
	 * @param WP_Post $post_id Post ID of the order.
	 */
	public static function save( $post_id ) {

		$message_type = '';
		$request = wp_unslash( $_REQUEST ); // Input var okay.

		$book_amount = sanitize_text_field( $request['novalnet_book_amount'] );

		if ( empty( $book_amount ) ) {
			WC_Admin_Meta_Boxes::add_error( __( 'The amount is invalid', 'wc-novalnet' ) );

			// Redirect to order view page.
			parent::redirect_process( $post_id );
		}
		$book_amount = $book_amount;
		$wc_order    = new WC_Order( $post_id );

		// Select transaction details.
		$get_details = parent::$transaction_details;
		$params      = wc_novalnet_unserialize_data( $get_details ['payment_params'] );
		$params ['amount']       = $book_amount;
		$params ['payment_ref']  = $get_details ['tid'];
		$params ['remote_ip']    = wc_novalnet_get_ip_address();

		// Forming SEPA due date.
		if ( ! empty( $params ['sepa_due_date'] ) ) {
			$novalnet_payment_method = novalnet_instance()->novalnet_functions()->get_novalnet_payment_method( $wc_order );

			$order_configuration      = wc_novalnet_payment_config( $novalnet_payment_method );
			$params ['sepa_due_date'] = wc_novalnet_format_due_date( 7 );
			if ( wc_novalnet_digits_check( $order_configuration ['sepa_payment_duration'] ) && $order_configuration ['sepa_payment_duration'] >= 7 ) {
				$params ['sepa_due_date'] = wc_novalnet_format_due_date( $order_configuration ['sepa_payment_duration'] );
			}
		}

		// Submit the request.
		$response = novalnet_instance()->novalnet_functions()->submit_request( array_filter( $params ) );

		// Log for amount book.
		parent::maintain_debug_log( "Amount booking call initiated for the order id $post_id" );
		if ( wc_novalnet_status_check( $response ) ) {

			$novalnet_payment_method_title = novalnet_instance()->novalnet_functions()->get_novalnet_payment_method_title( $wc_order );

			// Form basic comments.
			$transaction_comments = novalnet_instance()->novalnet_functions()->form_comments(
				array(
				'title'     => $novalnet_payment_method_title,
				'tid'       => $response['tid'],
				'test_mode' => ( '1' === $response ['test_mode'] ),
				)
			);
			$message = sprintf( __( 'Your order has been booked with the amount of %1$s. Your new TID for the booked amount: %2$s', 'wc-novalnet' ), wc_novalnet_shop_amount_format( $book_amount / 100 ), $response ['tid'] );

			// Get callback amount.
			$callback_amount = $request ['novalnet_book_amount'];
			if ( '34' === $params ['key'] && '90' === $response ['tid_status'] ) {
				$callback_amount = '0';
			}

			// Update Novalnet comments.
			novalnet_instance()->novalnet_functions()->update_comments( $wc_order, wc_novalnet_format_text( $transaction_comments ), false, 'transaction_info' );

			novalnet_instance()->novalnet_functions()->update_comments( $wc_order, wc_novalnet_format_text( $message ) );

			// Update transaction details.
			wc_novalnet_db_update_query(
				array(
				'tid'             => $response ['tid'],
				'gateway_status'  => $response ['tid_status'],
				'amount'          => $request ['novalnet_book_amount'],
				'booked'          => 1,
				'payment_ref'     => 1,
				'callback_amount' => $callback_amount,
				'payment_params'  => '',
				), array(
				'order_no'        => $post_id,
				)
			);

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
