<?php
/**
 * Amount update
 *
 * Handling amount update process
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
 * NN_Meta_Box_Amount_Update Class
 */
class NN_Meta_Box_Amount_Update extends NN_Admin_Meta_Boxes {


	/**
	 * Output the metabox
	 *
	 * @param WP_Post $post                The Order object.
	 * @param array   $transaction_details Novalnet transaction details of the order.
	 */
	public static function output( $post, $transaction_details ) {

		include_once dirname( dirname( ( __FILE__ ) ) ) . '/views/html-novalnet-amount-update.php';
	}

	/**
	 * Save meta box data
	 *
	 * @param WP_Post $post_id Post ID of the order.
	 */
	public static function save( $post_id ) {

		$message_type = '';
		$request = $_REQUEST; // Input var okay.

		$wc_order = new WC_Order( $post_id );

		$update_amount = sanitize_text_field( $request ['novalnet_update_amount'] );

		// Built API params.
		$api_params = wc_novalnet_built_api_params( parent::$transaction_details );
		$api_params ['edit_status'] = 1;
		$api_params ['status'] = '100';
		$api_params ['update_inv_amount'] = 1;
		$api_params ['amount'] = $update_amount;

		if ( '27' === parent::$transaction_details ['payment_id'] ) {
		    $strtotime_value = strtotime( $request ['novalnet_due_date'] );

		    // Validate due date.
		    if ( ( ( date( 'Y-m-d', $strtotime_value ) !== $request ['novalnet_due_date'] ) ) ) {
			    WC_Admin_Meta_Boxes::add_error( __( 'Due date is not valid', 'wc-novalnet' ) );

			    // Redirect to order view page.
			    parent::redirect_process( $post_id );
		    }
		    $api_params ['due_date'] = $request ['novalnet_due_date'];
		}

		// Submit the request.
		$response = novalnet_instance()->novalnet_functions()->submit_request( $api_params );

		// Log for amount update.
		parent::maintain_debug_log( "Manage transaction call initiated for the order id $post_id" );
		if ( wc_novalnet_status_check( $response ) ) {
			$message = sprintf( __( 'The transaction amount %1$s has been updated successfully on %2$s', 'wc-novalnet' ), wc_novalnet_shop_amount_format( $update_amount / 100 ), wc_novalnet_formatted_date() );

			// Update transaction details.
			wc_novalnet_db_update_query(
				array(
				'amount'   => $update_amount,
				), array(
				'order_no' => $post_id,
				)
			);
			if ( '27' === parent::$transaction_details ['payment_id'] ) {

				// For older version get bank details from novalnet_invoice_details table.
				if ( empty( parent::$transaction_details ['bank_details'] ) && wc_novalnet_check_valid_table( 'novalnet_invoice_details' ) ) {

					$get_bank_details = wc_novalnet_order_no_details( $post_id, 'novalnet_invoice_details' );
					parent::$transaction_details ['bank_details'] = $get_bank_details ['invoice_bank_details'];
				}

				// Form bank details array.
				$get_bank_details = wc_novalnet_unserialize_data( parent::$transaction_details ['bank_details'] );
				$get_bank_details ['due_date'] = $request ['novalnet_due_date'];
				$get_bank_details ['amount']   = $update_amount;

				$payment_method = novalnet_instance()->novalnet_functions()->get_novalnet_payment_method( $wc_order );
				// Form payment reference value if not exist.
				$payment_settings = wc_novalnet_payment_config( $payment_method );
				self::get_payment_reference( $get_bank_details, $payment_settings );

				if ( empty( $get_bank_details ['order_no'] ) ) {
					$get_bank_details ['order_no'] = $post_id;
				}

				$payment_method_title = novalnet_instance()->novalnet_functions()->get_novalnet_payment_method_title( $wc_order );
				// Form basic comments.
				$novalnet_comments = novalnet_instance()->novalnet_functions()->form_comments(
					array(
					'title'     => $payment_method_title,
					'test_mode' => parent::$transaction_details ['test_mode'],
					'tid'       => parent::$transaction_details ['tid'],
					)
				);

				$get_bank_details ['tid'] = parent::$transaction_details ['tid'];

				// Form bank details comments.
				$novalnet_comments .= novalnet_instance()->novalnet_functions()->form_bank_comments( $get_bank_details );

				// Update Novalnet comments.
				novalnet_instance()->novalnet_functions()->update_comments( $wc_order, wc_novalnet_format_text( $novalnet_comments ), false, 'transaction_info' );

				$get_bank_details ['amount'] = $update_amount;

				// Update Bank details.
				wc_novalnet_db_update_query(
					array(
					'bank_details' => wc_novalnet_serialize_data( $get_bank_details ),
					), array(
					'order_no'     => $post_id,
					)
				);
			} else {

				// Update callback amount.
				wc_novalnet_db_update_query(
					array(
					'callback_amount' => $update_amount,
					), array(
					'order_no'        => $post_id,
					)
				);
			}

			// Update Novalnet comments.
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

	/**
	 * Get payment reference values
	 *
	 * @param array $get_bank_details The bank details of the order.
	 * @param array $payment_settings The payment settings.
	 */
	public static function get_payment_reference( &$get_bank_details, $payment_settings ) {

		if ( empty( $get_bank_details ['payment_reference_1'] ) ) {
			$get_bank_details ['payment_reference_1']   = $payment_settings ['payment_reference_1'];
		}
		if ( empty( $get_bank_details ['payment_reference_2'] ) ) {
			$get_bank_details ['payment_reference_2']   = $payment_settings ['payment_reference_2'];
		}
		if ( empty( $get_bank_details ['payment_reference_3'] ) ) {
			$get_bank_details ['payment_reference_3']   = $payment_settings ['payment_reference_3'];
		}
	}
}
