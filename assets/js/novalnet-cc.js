/**
 * Novalnet Credit Card action.
 *
 * @category  Novalnet Credit Card action
 * @package   Novalnet
 * @copyright Novalnet (https://www.novalnet.de)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/* Initiate Credit card process */
(function($){

	novalnet_creditcard = {

		// Assign target Origin.
		target_origin : 'https://secure.novalnet.de',

		process : function() {
			novalnet_creditcard.initiate_payment_cc_process();
			$( document ).ajaxComplete(
				function( event, xhr, settings ) {
					var response = $.parseJSON( xhr.responseText );
					novalnet_creditcard.initiate_payment_cc_process();
				}
			);
		},

		/* Initiate Credit card payment process */
		initiate_payment_cc_process : function () {

			if ( 'true' === $( '#novalnet_cc_one_click_shop_process' ).val() ) {

				novalnet_functions.show_one_click_form( 'novalnet_cc' );
			} else {
				novalnet_functions.show_payment_form( 'novalnet_cc' );
			}
			$( '#novalnet_cc_payment_option' ).live(
				'click', function (event) {
					if ( 'none' === $( '#novalnet_cc_one_click_shop' ).css( 'display' ) ) {
						novalnet_functions.show_one_click_form( 'novalnet_cc' );
					} else {
						novalnet_functions.show_payment_form( 'novalnet_cc' );
						var iframe = document.getElementById( 'novalnet_cc_iframe' ).contentWindow;

						$( '#novalnet_cc_iframe' ).css( 'height', 0 );

						// Initiate post message to get Iframe height.
						iframe.postMessage( {
							callBack : 'getHeight'
						}, novalnet_creditcard_iframe.target_origin );
					}
					event.preventDefault();
					event.stopImmediatePropagation();
				}
			);
		}
	};

	$( document ).ready(function () {
		novalnet_creditcard.process();
	});
})(jQuery);
