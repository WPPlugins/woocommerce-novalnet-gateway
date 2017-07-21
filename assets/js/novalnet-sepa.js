/**
 * Novalnet Direct Debit SEPA action.
 *
 * @category  Novalnet Direct Debit SEPA action
 * @package   Novalnet
 * @copyright Novalnet (https://www.novalnet.de)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/* Initiate Direct Debit SEPA process */
(function($){

	/* Initiate Direct Debit SEPA payment process */
	novalnet_direct_debit_sepa = {

		process : function() {
			novalnet_direct_debit_sepa.initiate_payment_sepa_process();
			$( document ).ajaxComplete(
				function( event, xhr, settings ) {
					var response = $.parseJSON( xhr.responseText );
					if ( undefined === response.hash_result ) {
						novalnet_direct_debit_sepa.initiate_payment_sepa_process();
					}
					if ( 'failure' === response.result) {
					    $( '#novalnet_sepa_hash, #novalnet_sepa_iban, #novalnet_sepa_bic' ).val( '' );
					    novalnet_direct_debit_sepa.mandate_unconfirm_process();
					    novalnet_direct_debit_sepa.process_sepa_refill_call();
					}
				}
			);
			novalnet_functions.update_user_details( 'normal' );
			$( 'div.woocommerce-billing-fields input' ).on(
				'change', function() {
					novalnet_functions.update_user_details( 'change' );
				}
			);
		},

		initiate_payment_sepa_process : function () {

			if (undefined !== novalnet_sepa.admin ) {

				novalnet_direct_debit_sepa.admin_sepa_process();
			} else {

				$( '#novalnet_sepa_iban, #novalnet_sepa_bic' ).removeAttr( 'name' );
				novalnet_direct_debit_sepa.process_sepa_refill_call();
				novalnet_direct_debit_sepa.payment_form_process();
				if ( 'true' === $( '#novalnet_sepa_one_click_shop_process' ).val() ) {
					novalnet_functions.show_one_click_form( 'novalnet_sepa' );
				} else {
					novalnet_functions.show_payment_form( 'novalnet_sepa' );
				}
				$( '#novalnet_sepa_payment_option' ).live(
					'click', function (event) {
						if ( 'none' === $( '#novalnet_sepa_one_click_shop' ).css( 'display' ) ) {
						    novalnet_functions.show_one_click_form( 'novalnet_sepa' );
						} else {
						    novalnet_functions.show_payment_form( 'novalnet_sepa' );
						}
						event.preventDefault();
						event.stopImmediatePropagation();
					}
				);
			}
		},

		/* Initiate Direct Debit SEPA payment form process */
		payment_form_process : function () {
			$( '#novalnet_sepa_iban, #novalnet_sepa_bic, #novalnet_sepa_bank_country, #novalnet_sepa_account_holder' ).on(
				'change', function() {
					novalnet_direct_debit_sepa.mandate_unconfirm_process();
				}
			);

			$( '#novalnet_sepa_mandate_confirm' ).on( 'click', function( event ) {
				if ( ! $( '#novalnet_sepa_mandate_confirm' ).is( ':checked' ) ) {
					novalnet_direct_debit_sepa.mandate_unconfirm_process();
				}
				$( '#novalnet_sepa_hash' ).val( '' );
				if (novalnet_functions.check_payment( 'novalnet_sepa' ) && $( '#novalnet_sepa_mandate_confirm' ).is( ':checked' ) && ( '' === $( '#novalnet_sepa_hash' ).val() && ( ! $( '#novalnet_sepa_one_click_shop' ).length || 'none' === $( '#novalnet_sepa_one_click_shop' ).css( 'display' ) ) ) ) {
					event.stopImmediatePropagation();
					novalnet_direct_debit_sepa.perform_sepa_iban_bic_request();
				}
			} );

			$( '#' + novalnet_functions.form_id() ).on(
				'click', function( event ) {
					if (novalnet_functions.check_payment( 'novalnet_sepa' ) && '' === $( '#novalnet_sepa_hash' ).val() && ( ! $( '#novalnet_sepa_one_click_shop' ).length || 'none' === $( '#novalnet_sepa_one_click_shop' ).css( 'display' ) ) ) {
						alert( novalnet_sepa.mandate_error_message );
						novalnet_direct_debit_sepa.mandate_unconfirm_process();
						event.stopImmediatePropagation();
						return false;
					}
				}
			);
		},

		/* Initiate Direct Debit SEPA admin process */
		admin_sepa_process : function () {
			$( 'input[id="_payment_method_meta[post_meta][novalnet_sepa_iban]"]' ).removeAttr( 'name' );
			$( 'input[id="_payment_method_meta[post_meta][novalnet_sepa_bic]"]' ).removeAttr( 'name' );
			$( 'input[id="_payment_method_meta[post_meta][novalnet_sepa_account_holder]"]' ).attr( 'id', 'novalnet_sepa_account_holder' );
			$( '#novalnet_sepa_account_holder' ).val( $( '#_billing_first_name' ).val() + ' ' + $( '#_billing_last_name' ).val() );
			$( 'input[id="_payment_method_meta[post_meta][novalnet_sepa_iban]"]' ).attr( 'id', 'novalnet_sepa_iban' );

			if ( 'text' === $( 'input[id="_payment_method_meta[post_meta][novalnet_sepa_bank_country]"]' ).attr( 'type' ) ) {
				$( 'input[id="_payment_method_meta[post_meta][novalnet_sepa_bank_country]"]' ).replaceWith( '<select id="novalnet_sepa_bank_country"></select>' );
				$( '#novalnet_sepa_bank_country' ).html( $( '#_billing_country' ).html() );
			}

			$( 'input[id="_payment_method_meta[post_meta][novalnet_sepa_mandate_confirm]"]' ).replaceWith( '<p class="form-field form-field-wide"><input id="novalnet_sepa_mandate_confirm" type="checkbox" value="1" style="width:5%" >' + novalnet_sepa.mandate_text + '</p>' );

			$( 'input[id="_payment_method_meta[post_meta][novalnet_sepa_bic]"]' ).attr( 'id', 'novalnet_sepa_bic' );

			if ( ! $( '#novalnet_sepa_hash' ).length ) {
				$( '#novalnet_sepa_account_holder' ).before( novalnet_sepa.hash + novalnet_sepa.hidden_unique_id );
				$( '#novalnet_sepa_iban' ).after( novalnet_sepa.sepa_iban_span );
				$( '#novalnet_sepa_bic' ).after( novalnet_sepa.sepa_bic_span );
			}
			$( '#novalnet_sepa_iban, #novalnet_sepa_bic' ).keypress(
				function (event) {
					return novalnet_functions.allow_alphanumeric( event );
				}
			);
			$( '#novalnet_sepa_account_holder' ).keypress(
				function (event) {
					return novalnet_functions.allow_name_key( event );
				}
			);
			$( '#novalnet_sepa_account_holder, #novalnet_sepa_iban, #novalnet_sepa_bic' ).attr( 'autocomplete', 'OFF' );
			$( '#novalnet_sepa_iban, #novalnet_sepa_bic, #novalnet_sepa_bank_country, #novalnet_sepa_account_holder' ).on(
				'change', function() {
					novalnet_direct_debit_sepa.mandate_unconfirm_process();
				}
			);
			$( '.edit_address' ).on(
				'click', function() {
					$( '#novalnet_sepa_mandate_confirm' ).on(
						'click', function( event ) {
							if ( ! $( '#novalnet_sepa_mandate_confirm' ).is( ':checked' ) ) {
								return novalnet_direct_debit_sepa.mandate_unconfirm_process();
							}
							if ( 'novalnet_sepa' === $( '#_payment_method' ).val() && '' === $( '#novalnet_sepa_hash' ).val() ) {
								event.stopImmediatePropagation();
								var novalnet_payment_block_id = $( '#novalnet_sepa_mandate_confirm' ).closest( 'div' ).prop( 'id' )
								novalnet_functions.load_block( novalnet_payment_block_id, null );
								novalnet_direct_debit_sepa.perform_sepa_iban_bic_request( event );
							}
						}
					);
				}
			);
		},

		/* Initiate Direct Debit SEPA refill process */
		process_sepa_refill_call : function () {

			if ( ( ( novalnet_functions.check_payment( 'novalnet_sepa' ) ) || ( undefined !== novalnet_sepa.payment_data_refill && 'true' === novalnet_sepa.payment_data_refill ) || ( undefined !== novalnet_sepa.auto_refill && 'true' === novalnet_sepa.auto_refill )  ) && $( '#novalnet_sepa_refill_hash' ).length && '' !== $( '#novalnet_sepa_refill_hash' ).val() && '' === $( '#novalnet_sepa_hash' ).val() ) {
				var novalnet_payment_block_id = $( '#novalnet_sepa_mandate_confirm' ).closest( 'div' ).prop( 'id' )
				novalnet_functions.load_block( novalnet_payment_block_id, null );
				return novalnet_functions.ajax_call( {
					'vendor_id': novalnet_sepa.vendor,
					'vendor_authcode': novalnet_sepa.authcode,
					'remote_ip': novalnet_sepa.remote_ip,
					'sepa_hash':  $( '#novalnet_sepa_refill_hash' ).val(),
					'unique_id': novalnet_sepa.unique_id,
					'sepa_data_approved': 1,
					'mandate_data_req': 1
					}, 'https://payport.novalnet.de/sepa_iban', 'sepa_refill_hash'
				);
			}
		},

		/* Direct Debit SEPA hash call process */
		process_sepa_hash_call : function ( show_loader ) {
			var bank_country = $( '#novalnet_sepa_bank_country' ).val(),
			account_holder   = $.trim( $( '#novalnet_sepa_account_holder' ).val() ),
			account_no       = '',
			bank_code        = '',
			iban             = novalnet_functions.format_alphanumeric( $( '#novalnet_sepa_iban' ).val() ),
			bic              = novalnet_functions.format_alphanumeric( $( '#novalnet_sepa_bic' ).val() ),
			remote_ip        = novalnet_sepa.remote_ip;

			if (( 'DE' !== bank_country && bic === '' ) || ( 'DE' === bank_country && '' === bic && ! isNaN( iban ) ) ) {
				alert( novalnet_sepa.error_message );
				novalnet_direct_debit_sepa.mandate_unconfirm_process();
				return false;
			}
			if ( 'DE' === bank_country && '' === bic && isNaN( iban ) ) {
				bic = '123456';
				$( '#novalnet_sepa_bic, #novalnet_sepa_bic' ).val( '' );
			}
			if ( ! isNaN( iban ) && ! isNaN( bic ) ) {
				account_no = iban;
				bank_code = bic;
				iban = bic = '';
			}
			if ('' === bic &&  '' !== novalnet_sepa.iban && '' !== novalnet_sepa.bic ) {
				iban = novalnet_sepa.iban;
				bic  = novalnet_sepa.bic;
				account_no = bank_code = '';
			}

			// Check for empty hash.
			if ('' === $( '#novalnet_sepa_hash' ).val() ) {
				$( '#novalnet_sepa_mandate_confirm' ).attr( 'disabled', 'disabled' );
				if ( show_loader ) {
					var novalnet_payment_block_id = $( '#novalnet_sepa_mandate_confirm' ).closest( 'div' ).prop( 'id' );
					novalnet_functions.load_block( novalnet_payment_block_id, null );
				}

				return novalnet_functions.ajax_call(
					{
						'account_holder': account_holder,
						'bank_account': account_no,
						'bank_code': bank_code,
						'vendor_id': novalnet_sepa.vendor,
						'vendor_authcode': novalnet_sepa.authcode,
						'bank_country': bank_country,
						'unique_id': novalnet_sepa.unique_id,
						'remote_ip': remote_ip,
						'sepa_data_approved': 1,
						'mandate_data_req': 1,
						'iban': iban,
						'bic': bic
					}, 'https://payport.novalnet.de/sepa_iban', 'sepa_hash'
				);
			}
			return false;
		},

		/* Direct Debit SEPA response process */
		ajax_response : function ( response, code ) {
			if ('success' === response.hash_result ) {

				// Direct Debit SEPA hash process.
				if ('sepa_hash' === code ) {
					$( '#novalnet_sepa_mandate_confirm' ).attr( 'disabled', false );
					$( '.blockUI' ).remove();
					if ( undefined !== novalnet_sepa.auto_refill && 'true' === novalnet_sepa.auto_refill ) {
						$( '#novalnet_sepa_refill_hash' ).val( response.sepa_hash );
					}
					$( '#novalnet_sepa_hash' ).val( response.sepa_hash );
					$( '#novalnet_sepa_unique_id' ).val( novalnet_sepa.unique_id );
					return true;

					// Direct Debit SEPA iban bic process.
				} else if ('sepa_iban_bic' === code ) {
					if ( '' !== response.IBAN && '' !== response.BIC) {
						$( '#novalnet_sepa_iban_span' ).html( ' <b>IBAN: </b>' + response.IBAN );
						$( '#novalnet_sepa_bic_span' ).html( ' <b>BIC: </b>' + response.BIC );
						novalnet_sepa.iban = response.IBAN;
						novalnet_sepa.bic = response.BIC;
						return novalnet_direct_debit_sepa.process_sepa_hash_call( false );
					} else {
						alert( novalnet_sepa.error_message );
						novalnet_direct_debit_sepa.mandate_unconfirm_process();
						return false;
					}

					// Direct Debit SEPA hash refill process.
				} else if ('sepa_refill_hash' === code ) {
					var hash_stringvalue = response.hash_string,
					 hash_string    = hash_stringvalue.split( '&' ),
					 acc_hold       = hash_stringvalue.match( 'account_holder=(.*)&bank_code' ),
					 account_holder = '',
					 holder     = '',
					 array_result   = {},
					 data_length    = hash_string.length;

					account_holder = ( null !== acc_hold && undefined !== acc_hold[1] ) ? acc_hold[1] : '';
					for ( var i = 0; i < data_length; i++ ) {
						var hash_result_val = hash_string[i].split( '=' );
						array_result[ hash_result_val[0] ] = hash_result_val[1];
					}
					try {
						holder = decodeURIComponent( escape( account_holder ) );
					} catch (e) {
						holder = account_holder;
					}
					$( '.blockUI' ).remove();
					$( '#novalnet_sepa_account_holder' ).val( holder );
					$( '#novalnet_sepa_bank_country' ).val( array_result.bank_country );
					$( '#novalnet_sepa_iban' ).val( array_result.iban );
					$( '#novalnet_sepa_hash' ).val( array_result.sepa_hash );
					if ( '123456' !== array_result.bic ) {
						$( '#novalnet_sepa_bic' ).val( array_result.bic );
					}
					return false;
				}
			}

			// Throws error on failure.
			alert( response.hash_result );
			novalnet_direct_debit_sepa.mandate_unconfirm_process();
			return false;
		},

		/* Direct Debit SEPA unconfirm proccess */
		mandate_unconfirm_process : function () {
			$( '#novalnet_sepa_mandate_confirm' ).attr( 'checked',false );
			$( '#novalnet_sepa_mandate_confirm' ).attr( 'disabled',false );
			$( '#novalnet_sepa_hash' ).val( '' );
			$( '#novalnet_sepa_iban_span, #novalnet_sepa_bic_span' ).html( '' );
			$( '.blockUI' ).remove();
		},

		/* Initiate Direct Debit SEPA iban/bic process */
		perform_sepa_iban_bic_request : function () {
			var bank_country       = $( '#novalnet_sepa_bank_country' ).val(),
			account_holder         = $.trim( $( '#novalnet_sepa_account_holder' ).val() ),
			account_no             = novalnet_functions.format_alphanumeric( $( '#novalnet_sepa_iban' ).val() ),
			bank_code              = novalnet_functions.format_alphanumeric( $( '#novalnet_sepa_bic' ).val() ),
			remote_ip              = novalnet_sepa.remote_ip,
			novalnet_vendor        = novalnet_sepa.vendor,
			novalnet_auth_code     = novalnet_sepa.authcode,
			novalnet_sepa_unique_id = novalnet_sepa.unique_id;

			$( '#novalnet_sepa_iban' ).val( account_no );
			$( '#novalnet_sepa_bic' ).val( bank_code );

			if ( '' === account_holder || '' === account_no || ( 'DE' !== bank_country && '' === bank_code ) ) {
				alert( novalnet_sepa.error_message );
				novalnet_direct_debit_sepa.mandate_unconfirm_process();
				return false;
			}
			if ( '' === bank_country ) {
				alert( novalnet_sepa.country_error_message );
				novalnet_direct_debit_sepa.mandate_unconfirm_process();
				return false;
			}
			if ( ( '' === $( '#novalnet_sepa_hash' ).val() ) && ( isNaN( account_no ) && isNaN( bank_code ) || ( 'DE' === bank_country && '' === bank_code ) ) ) {
				$( '#novalnet_sepa_iban_span, #novalnet_sepa_bic_span' ).html( '' );
				return novalnet_direct_debit_sepa.process_sepa_hash_call( true );
			}
			if (( isNaN( account_no ) && ! isNaN( bank_code ) ) || ( ! isNaN( account_no ) && isNaN( bank_code ) ) ) {
				alert( novalnet_sepa.error_message );
				novalnet_direct_debit_sepa.mandate_unconfirm_process();
				return false;
			}
			if ( ! isNaN( account_no ) && ! isNaN( bank_code ) && '' === $( '#novalnet_sepa_hash' ).val() ) {
				$( '#novalnet_sepa_mandate_confirm' ).attr( 'disabled', 'disabled' );
				var novalnet_payment_block_id = $( '#novalnet_sepa_mandate_confirm' ).closest( 'div' ).prop( 'id' )
				novalnet_functions.load_block( novalnet_payment_block_id, null );
				novalnet_functions.ajax_call(
					{
						'account_holder': account_holder,
						'bank_account': account_no,
						'bank_code': bank_code,
						'bank_country': bank_country,
						'get_iban_bic': 1,
						'unique_id': novalnet_sepa_unique_id,
						'vendor_authcode': novalnet_auth_code,
						'vendor_id': novalnet_vendor,
						'remote_ip': remote_ip
					}, 'https://payport.novalnet.de/sepa_iban', 'sepa_iban_bic'
				);
			}
		}
	};

	$( document ).ready(function () {
		novalnet_direct_debit_sepa.process();
	});

})(jQuery);
