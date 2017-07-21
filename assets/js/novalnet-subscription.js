/**
 * Novalnet Subscription action.
 *
 * @category  Novalnet Subscription action
 * @package   Novalnet
 * @copyright Novalnet (https://www.novalnet.de)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/* Initiate subscription process */
(function($){

	novalnet_subscription_functions = {

		/* Subscription cancel validation */
		process_subscription_cancel : function () {
			if ('0' === $( '#novalnet_subscription_cancel_reason' ).val() ) {
				alert( novalnet_subscription.error_message );
				return false;
			}
			novalnet_functions.load_block( 'novalnet_subscription_cancel', null );
		},

		process_admin_payment : function () {

			// Assign values for admin change payment method in subscription.
			$( 'input[id="_payment_method_meta[post_meta][novalnet_payment]"]' ).attr( 'type', 'hidden' );
			$( '.edit_address' ).on(
				'click', function() {
					$( 'input[id="_payment_method_meta[post_meta][novalnet_payment]"]' ).val( '1' );
				}
			);
			$( 'input[id="_payment_method_meta[post_meta][novalnet_payment_change]"]' ).replaceWith( '<p class="form-field form-field-wide"><input id="novalnet_payment_change" name="novalnet_payment_change" type="checkbox" value="1" style="width:5%" >' + novalnet_subscription.change_payment_text + '</p>' );
			if ('true' == novalnet_subscription.hide_other_subscription_options ) {
				$( '#billing-schedule' ).css( 'display', 'none' );
			}
		},

		/* Customizing the shop subscription cancel button process */
		process_cancel_option : function () {

			$( '.cancelled' ).click(
				function( evt ) {
					var submit_url = $( this ).children( 'a' ).attr( 'href' );
					if (0 < submit_url.indexOf( "novalnet-api" ) ) {
						$( '#novalnet_subscription_cancel' ).remove();
						$( this ).closest( 'td' ).append( novalnet_subscription.reason_list );
						$( ' #novalnet_subscription_cancel_reason' ).css( 'position', 'absolute' );
						evt.preventDefault();
						evt.stopImmediatePropagation();
					}
					$( '#novalnet_subscription_cancel' ).attr( 'method', 'POST' );
					$( '#novalnet_subscription_cancel' ).attr( 'action', submit_url );
				}
			);
		}
	};

	$( document ).ready(
		function() {
			novalnet_subscription_functions.process_admin_payment();
			novalnet_subscription_functions.process_cancel_option();
		}
	);
})(jQuery);
