/**
 * Novalnet Extension action.
 *
 * @category  Novalnet Extension action
 * @package   Novalnet
 * @copyright Novalnet (https://www.novalnet.de)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

(function($){

	/* Meta box functions */
	novalnet_meta_box = {

		/* Amount update validation */
		process_amount_update : function () {

			if ( '' === $( '#novalnet_update_amount' ).val() || 0 >= $( '#novalnet_update_amount' ).val() ) {
				alert( novalnet_admin_meta_boxes.empty_amount );
				return false;
			} else if ( ! window.confirm( novalnet_admin_meta_boxes.amount_update_message ) ) {
				return false;
			}
			novalnet_functions.load_block( 'novalnet-amount-update', null );
		},

		/* Amount refund validation */
		process_amount_refund : function () {

			if ( '' === $( '#novalnet_refund_amount' ).val() || 0 >= $( '#novalnet_refund_amount' ).val() ) {
				alert( novalnet_admin_meta_boxes.empty_amount );
				return false;
			} else if ( $( '#refund_payment_type' ).length && 'sepa' === $( '#refund_payment_type' ).val() && ( '' === $( '#sepa_account_holder' ).val() || '' === $( '#sepa_iban' ).val() || '' === $( '#sepa_bic' ).val() ) ) {
				alert( novalnet_admin_meta_boxes.account_details_invalid );
				return false;
			} else if ( ! window.confirm( novalnet_admin_meta_boxes.amount_refund_message ) ) {
				return false;
			}

			novalnet_functions.load_block( 'novalnet-amount-refund', null );
		},

		/* Amount book validation */
		process_amount_book : function () {
			if ( '' === $( '#novalnet_book_amount' ).val() || 0 >= $( '#novalnet_book_amount' ).val() ) {
				alert( novalnet_admin_meta_boxes.empty_amount );
				return false;
			} else if ( ! window.confirm( novalnet_admin_meta_boxes.book_update_message ) ) {
				return false;
			}


			novalnet_functions.load_block( 'novalnet-amount-book', null );

		},

		/* Manage transaction validation */
		process_manage_transaction : function () {

			if ('' === $( '#transaction_status' ).val() ) {
				alert( novalnet_admin_meta_boxes.select_status );
				return false;
			} else if ( 100 == $( "#transaction_status option:selected" ).val() ) { 
				if ( ! window.confirm( novalnet_admin_meta_boxes.capture_update_message ) ) {
					return false;
				}
			} else if ( 103 == $( "#transaction_status option:selected" ).val() ) { 
				if ( ! window.confirm( novalnet_admin_meta_boxes.cancel_update_message ) ) {
					return false;
				}
			}

			novalnet_functions.load_block( 'novalnet-manage-transaction', null );
		},

		/* Process refund type option */
		show_refund_type : function () {
			( 'sepa' == $( '#refund_payment_type' ).val() ) ? $( '#refund_sepa_form' ).css( 'display', 'block' ) : $( '#refund_sepa_form' ).css( 'display', 'none' );
		}
	}
})(jQuery);
