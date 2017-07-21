/**
 * Novalnet Credit Card iframe action.
 *
 * @category  Novalnet Credit Card action
 * @package   Novalnet
 * @copyright Novalnet (https://www.novalnet.de)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/* Initiate Credit card process */
(function($){

	novalnet_creditcard_iframe = {
		// Assign target Origin.
		target_origin : 'https://secure.novalnet.de',

		/* Initiate Credit card Iframe process */
		process : function() {

			if ( undefined !== novalnet_cc_iframe.admin ) {
				$( '.edit_address' ).on(
					'click', function() {
						var iframe = document.getElementById( 'novalnet_cc_iframe' ).contentWindow;

						// Initiate post message to get Iframe height.
						iframe.postMessage( {
							callBack : 'getHeight'
						}, novalnet_creditcard_iframe.target_origin );
					}
				);
				$( document ).live( 'submit', '#' + $( '#novalnet_cc_pan_hash' ).closest( 'form' ).attr( 'id' ), function( event ) {
					if ( $( '#novalnet_cc_iframe' ).is( ":visible" ) && '' == $( '#novalnet_cc_pan_hash' ).val() && 'novalnet_cc' === $( '#_payment_method' ).val() ) {
						event.preventDefault();
						event.stopImmediatePropagation();
						var iframe = document.getElementById( 'novalnet_cc_iframe' ).contentWindow;

						// Call the postMessage event for getting the hash.
						iframe.postMessage( JSON.stringify( {
							callBack : 'getHash'
						} ), novalnet_creditcard_iframe.target_origin );

					}
				});
			} else {

				// Set height when payment selected.
				if ( undefined !== $( '#payment_method_novalnet_cc' ).val() ) {
					$( '#payment_method_novalnet_cc' ).on('click', function() {

						var iframe = document.getElementById( 'novalnet_cc_iframe' ).contentWindow;

						// Initiate post message to get Iframe height.
						$( '#novalnet_cc_iframe' ).css( 'height', 0 );

						iframe.postMessage( {
							callBack : 'getHeight'
						}, novalnet_creditcard_iframe.target_origin );
					});
				}

				// Process hash call.
				$( '#' + novalnet_functions.form_id() ).on( 'click', function( event ) {

					if ( '' == $( '#novalnet_cc_pan_hash' ).val() && novalnet_functions.check_payment( 'novalnet_cc' ) && 'true' !== $( '#novalnet_cc_one_click_shop_process' ).val() ) {
						event.preventDefault();
						event.stopImmediatePropagation();

						var iframe = document.getElementById( 'novalnet_cc_iframe' ).contentWindow;

						// Call the postMessage event for getting the hash.
						iframe.postMessage( JSON.stringify( {
							callBack : 'getHash'
						} ), novalnet_creditcard_iframe.target_origin );
					}
				});
			}
		},

		/* Load Iframe */
		load_iframe : function () {

			var iframe = document.getElementById( 'novalnet_cc_iframe' ).contentWindow;

			var style_object = {
				labelStyle : novalnet_cc_iframe.standard_label,
				inputStyle : novalnet_cc_iframe.standard_input,
				styleText  : novalnet_cc_iframe.standard_css,
				card_holder : {
					labelStyle : novalnet_cc_iframe.holder_label_css,
					inputStyle : novalnet_cc_iframe.holder_input_field_css,
				},
				card_number : {
					labelStyle : novalnet_cc_iframe.number_label_css,
					inputStyle : novalnet_cc_iframe.number_input_field_css,
				},
				expiry_date : {
					labelStyle : novalnet_cc_iframe.expiry_date_label_css,
					inputStyle : novalnet_cc_iframe.expiry_date_input_field_css,
				},
				cvc : {
					labelStyle : novalnet_cc_iframe.cvc_label_css,
					inputStyle : novalnet_cc_iframe.cvc_input_field_css,
				},
			};

			var text_object = {
				card_holder : {
					labelText : novalnet_cc_iframe.holder_label_text,
					inputText : novalnet_cc_iframe.holder_place_holder_text,
				},
				card_number : {
					labelText : novalnet_cc_iframe.number_label_text,
					inputText : novalnet_cc_iframe.number_place_holder_text,
				},
				expiry_date : {
					labelText : novalnet_cc_iframe.expiry_label_text,
					inputText : novalnet_cc_iframe.expiry_place_holder_text,
				},
				cvc : {
					labelText : novalnet_cc_iframe.cvc_label_text,
					inputText : novalnet_cc_iframe.cvc_place_holder_text,
				},
				cvcHintText : novalnet_cc_iframe.cvc_hint_text,
				errorText   : novalnet_cc_iframe.error_text
			};

			// Initiate post message to create Iframe elements.
			iframe.postMessage( {
				callBack : 'createElements',
				customText: text_object,
				customStyle : style_object
			}, novalnet_creditcard_iframe.target_origin );

			// Initiate post message to get Iframe height.
			iframe.postMessage( {
				callBack : 'getHeight'
			}, novalnet_creditcard_iframe.target_origin );
		},

		/* Handle Event Listener */
		add_event : function (event) {

			// Convert message string to object.
			var data = eval( '(' + event.data.replace( /(<([^>]+)>)/gi, "" ) + ')' );

			// To check the message listener origin with the iframe host.
			if ( event.origin === novalnet_creditcard_iframe.target_origin ) {

				// To check the eventListener message from iframe for hash.
				if ( 'getHash' === data['callBack'] ) {

					if ( undefined !== data['error_message'] ) {
						var error_message = $( "<div />" ).html( data['error_message'] ).text();
						alert( error_message );
						return false;
					}
					$( '#novalnet_cc_pan_hash' ).val( data ['hash'] );
					$( '#novalnet_cc_unique_id' ).val( data ['unique_id'] );
					if ( undefined === novalnet_cc_iframe.admin ) {
						$( '#' + novalnet_functions.form_id() ).click();
						return false;
					} else {
						$( '#' + $( '#novalnet_cc_pan_hash' ).closest( 'form' ).attr( 'id' ) ).submit();
						return false;
					}

					// To check the eventListener message from iframe to get the iframe content height.
				} else if ( 'getHeight' === data ['callBack'] ) {

					// Set the content height to the iframe height.
					$( '#novalnet_cc_iframe' ).css( 'height', data ['contentHeight'] );
				}
			}
		}

	};

	$( document ).ready(function () {
		novalnet_creditcard_iframe.process();
		$( document ).ajaxComplete(
			function( event, xhr, settings ) {
				var response = $.parseJSON( xhr.responseText );
				if ( 'failure' === response.result) {
					$( '#novalnet_cc_pan_hash, #novalnet_cc_unique_id' ).val( '' );
				}
				novalnet_creditcard_iframe.process();
			}
		);

		if ( window.addEventListener ) {

			// addEventListener works for all major browsers.
			window.addEventListener('message', function(event) {
				novalnet_creditcard_iframe.add_event( event );
			}, false);
		} else {

			// attachEvent works for IE8.
			window.attachEvent('onmessage', function (event) {
				novalnet_creditcard_iframe.add_event( event );
			});
		}
	});

})(jQuery);
