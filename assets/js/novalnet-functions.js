/**
 * Novalnet Functions action.
 *
 * @category  Novalnet Functions action
 * @package   Novalnet
 * @copyright Novalnet (https://www.novalnet.de)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

(function($){

	novalnet_functions = {

		/* Load user details in payment form */
		load_user_details : function () {
			$( document ).ajaxComplete(
				function( event, xhr, settings ) {
					novalnet_functions.update_user_details( 'ajax' );
				}
			);
		},

		/* Assign customer details from billing details */
		update_user_details : function ( type ) {
			var fname = ( $( '#billing_first_name' ).length ) ? $( '#billing_first_name' ).val() : '';
			var lname = ( $( '#billing_last_name' ).length ) ? $( '#billing_last_name' ).val() : '';
			novalnet_functions.check_defined_field( 'novalnet_sepa_account_holder' , $.trim( fname + ' ' + lname ), type );
			novalnet_functions.check_defined_field( 'novalnet_sepa_pin_by_tel' , $( '#billing_phone' ).val(), type );
			novalnet_functions.check_defined_field( 'novalnet_invoice_pin_by_tel' , $( '#billing_phone' ).val(), type );
		},

		/* Check for defined input fields */
		check_defined_field : function ( id, value, type ) {
			if (( $( '#' + id ).length && $( '#' + id ).val() === '' ) || 'change' === type ) {
				$( '#' + id ).val( value );
			}
		},

		/* Get payment form id dynamically */
		form_id : function  () {
			var form_id = ( undefined !== $( '#order_review button[type=submit]' ).attr( 'id' ) && '' !== $( '#order_review button[type=submit]' ).attr( 'id' ) ) ? $( '#order_review button[type=submit]' ).attr( 'id' ) : $( '#order_review input[type=submit]' ).attr( 'id' );
			return ( undefined === form_id || null === form_id ) ? 'place_order' : form_id;
		},

		/* Show payment form */
		show_payment_form : function ( payment ) {

			// Assigning payment form functionality.
			$( '#' + payment + '_payment_form' ).css( 'display','block' );
			$( '#' + payment + '_one_click_shop' ).css( 'display','none' );
			$( '#' + payment + '_one_click_shop_process' ).val( 'false' );
			$( '#' + payment + '_payment_option' ).html( ( 'novalnet_cc' === payment ) ? novalnet_function.given_card_details : ( ( 'novalnet_paypal' === payment ) ? novalnet_function.given_paypal_details  : novalnet_function.given_account_details ) );
		},

		/* Show one click payment form */
		show_one_click_form : function ( payment ) {

			// Assigning one click shop functionality.
			$( '#' + payment + '_payment_form' ).css( 'display','none' );
			$( '#' + payment + '_one_click_shop' ).css( 'display','block' );
			$( '#' + payment + '_one_click_shop_process' ).val( 'true' );
			$( '#' + payment + '_payment_option' ).html( ( 'novalnet_cc' === payment ) ? novalnet_function.enter_card_details : ( ( 'novalnet_paypal' === payment ) ? novalnet_function.enter_paypal_details  : novalnet_function.enter_account_details ) );
		},

		/* Check for payment type */
		check_payment : function ( payment ) {
			return payment === $( 'input[name=payment_method]:checked' ).val();
		},

		/* Initiate ajax call to server */
		ajax_call : function ( url_param, novalnet_server_url, response_type ) {

			// Checking for cross domain request.
			if ('XDomainRequest' in window && null !== window.XDomainRequest ) {
				var request_data = $.param( url_param );
				var xdr = new XDomainRequest();
				xdr.open( 'POST' , novalnet_server_url );
				xdr.onload = function () {
					return ( 'config' === response_type ) ? novalnet_functions.config_hash_response( this.responseText ) : novalnet_direct_debit_sepa.ajax_response( this.responseText, response_type );
				};
				xdr.send( request_data );
			} else {
				$.ajax(
					{
						type: 'POST',
						url: novalnet_server_url,
						data: url_param,
						success: function( response ) {
							return ( 'config' === response_type ) ? novalnet_functions.config_hash_response( response ) : novalnet_direct_debit_sepa.ajax_response( response, response_type );
						}
					}
				);
			}
		},

		load_block: function( id, message ) {
			$( '#' + id ).block({
				message: message,
				overlayCSS: {
					background: '#fff',
					opacity: 0.6
				}
			});
		},

		/* Vendor hash process */
		config_hash_response : function ( data ) {
			var saved_tariff_id = $( '#novalnet_tariff_id' ).val();
			var saved_subs_tariff_id = $( '#novalnet_subs_tariff_id' ).val();
			if (undefined !== data.config_result && '' !== data.config_result ) {

				$( '#novalnet_tariff_id' ).replaceWith( '<input type="text" style="width:25em;" id="novalnet_tariff_id" readonly="true" name= "novalnet_tariff_id" />' );
				$( '#novalnet_subs_tariff_id' ).replaceWith( '<input type="text" style="width:25em;" id="novalnet_subs_tariff_id" readonly="true" name= "novalnet_subs_tariff_id" />' );
				alert( data.config_result );
				novalnet_functions.null_basic_params();
				return false;
			}

			$( '#novalnet_tariff_id' ).replaceWith( '<select id="novalnet_tariff_id" style="width:25em;" name= "novalnet_tariff_id" ></select>' );
			$( '#novalnet_subs_tariff_id' ).replaceWith( '<select id="novalnet_subs_tariff_id" style="width:25em;"  name= "novalnet_subs_tariff_id" ></select>' );
			var hash_tariff_id = data.tariff_id.split( ',' );
			var hash_tariff_name = data.tariff_name.split( ',' );
			var hash_tariff_type = data.tariff_type.split( ',' );
			for (var i = 0; i < hash_tariff_id.length; i++) {
				var tariff_id = hash_tariff_id[i].split( ':' );
				var tariff_name = hash_tariff_name[i].split( ':' );
				var tariff_type = hash_tariff_type[i].split( ':' );
				var tariff_value = ( undefined !== tariff_name[ '2' ] ) ? tariff_name[ '1' ] + ':' + tariff_name[ '2' ] : tariff_name[ '1' ];

				$( '#novalnet_tariff_id' ).append(
					$(
						'<option>', {
							value: $.trim( tariff_id[ '1' ] ),
							text : $.trim( tariff_value )
						}
					)
				);

				// Assign subscription tariff id.
				if ('4' === $.trim( tariff_type[ '1' ] ) ) {
					$( '#novalnet_subs_tariff_id' ).append(
						$(
							'<option>', {
								value: $.trim( tariff_id[ '1' ] ),
								text : $.trim( tariff_value )
							}
						)
					);
					if (saved_subs_tariff_id === $.trim( tariff_id [ '1' ] ) ) {
						$( '#novalnet_subs_tariff_id' ).val( $.trim( tariff_id [ '1' ] ) );
					}
				}

				// Assign tariff id.
				if (saved_tariff_id === $.trim( tariff_id[ '1' ] ) ) {
					$( '#novalnet_tariff_id' ).val( $.trim( tariff_id [ '1' ] ) );
				}
			}

			// Assign vendor details.
			$( '#novalnet_vendor_id' ).val( data.vendor_id );
			$( '#novalnet_auth_code' ).val( data.auth_code );
			$( '#novalnet_product_id' ).val( data.product_id );
			$( '#novalnet_key_password' ).val( data.access_key );
			novalnet_admin.ajax_complete = 'true';
			return true;
		},

		/* Check for alphanumeric keys */
		allow_alphanumeric : function ( event ) {

			var keycode = ( 'which' in event ) ? event.which : event.keyCode,
				reg     = /^(?:[0-9a-zA-Z]+$)/;
			return ( reg.test( String.fromCharCode( keycode ) ) || 0 === keycode || 8 === keycode );
		},

		/* Check for valid date */
		allow_date : function ( event ) {
			var keycode = ( 'which' in event ) ? event.which : event.keyCode,
				reg     = /^(?:[0-9-]+$)/;
			return ( reg.test( String.fromCharCode( keycode ) ) || 0 === keycode || 8 === keycode );
		},

		/* Check for holder keys */
		allow_name_key : function ( event ) {
			var keycode = ( 'which' in event ) ? event.which : event.keyCode,
				reg     = /[^0-9\[\]\/\\#,+@!^()$~%'"=:;<>{}\_\|*?`]/g;
			return ( reg.test( String.fromCharCode( keycode ) ) || 0 === keycode || 8 === keycode );
		},

		/* Allow only numbers */
		allow_numbers : function ( event ) {
			var keycode = ('which' in event) ? event.which : event.keyCode,
				reg     = /^(?:[0-9]+$)/;
			return ( reg.test( String.fromCharCode( keycode ) ) || 0 === keycode || 8 === keycode );
		},

		/* Null config values */
		null_basic_params : function () {

			novalnet_admin.ajax_complete = 'true';
			$( '#novalnet_vendor_id, #novalnet_auth_code, #novalnet_product_id, #novalnet_key_password, #novalnet_public_key' ).val( '' );
			$( '#novalnet_tariff_id' ).find( 'option' ).remove();
			$( '#novalnet_tariff_id' ).append( $( '<option>', {
				value: '',
				text : novalnet_admin.select_text,
			} ) );
			$( '#novalnet_subs_tariff_id' ).find( 'option' ).remove();
			$( '#novalnet_subs_tariff_id' ).append( $( '<option>', {
				value: '',
				text : novalnet_admin.select_text,
			} ) );
		},

		/* Remove special characters, space and tab key */
		format_alphanumeric : function ( value ) {
			return value.replace( /[^a-zA-Z 0-9]+/g, "" ).replace( /\s+/g, "" );
		}
	};

	$( document ).ready(function () {
		novalnet_functions.load_user_details();
	});
})(jQuery);
