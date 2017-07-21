/**
 * Novalnet PayPal action.
 *
 * @category  Novalnet PayPal action
 * @package   Novalnet
 * @copyright Novalnet (https://www.novalnet.de)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/* Initiate PayPal process */
(function($){

	novalnet_paypal = {

		process : function() {
			novalnet_paypal.initiate_payment_paypal_process();
			$( document ).ajaxComplete(
				function( event, xhr, settings ) {
					var response = $.parseJSON( xhr.responseText );
					novalnet_paypal.initiate_payment_paypal_process();
				}
			);
		},

		/* Initiate PayPal payment process */
		initiate_payment_paypal_process : function () {
			if ( 'true' === $( '#novalnet_paypal_one_click_shop_process' ).val() ) {
				novalnet_functions.show_one_click_form( 'novalnet_paypal' );
			} else {
				novalnet_functions.show_payment_form( 'novalnet_paypal' );
			}
			$( '#novalnet_paypal_payment_option' ).on(
				'click', function (event) {
					if ( 'none' === $( '#novalnet_paypal_one_click_shop' ).css( 'display' ) ) {
						novalnet_functions.show_one_click_form( 'novalnet_paypal' );
					} else {
						novalnet_functions.show_payment_form( 'novalnet_paypal' );
					}
					event.preventDefault();
					event.stopImmediatePropagation();
				}
			);
		},
	};
	$( document ).ready(function () {
		novalnet_paypal.process();
	});
})(jQuery);
