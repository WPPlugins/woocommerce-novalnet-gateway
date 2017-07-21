/**
 * Novalnet Admin action.
 *
 * @category  Novalnet Admin action
 * @package   Novalnet
 * @copyright Novalnet (https://www.novalnet.de)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/* Process product activation key */
(function($){
	novalnet_config = {

		process : function () {
			if ($( '#novalnet_public_key' ).length) {
				$( '#novalnet_vendor_id, #novalnet_auth_code, #novalnet_product_id, #novalnet_key_password, #novalnet_tariff_id, #novalnet_subs_tariff_id' ).prop( 'readonly', true );
				if ( '' !== $.trim( $( '#novalnet_public_key' ).val() ) ) {
					novalnet_config.fill_novalnet_details();
				}
				$( '#novalnet_public_key' ).on(
					'change', function() {
						if ( '' !== $.trim( $( '#novalnet_public_key' ).val() ) ) {
							novalnet_config.fill_novalnet_details();
						} else {
							novalnet_functions.null_basic_params();
						}
					}
				);
				$( '#novalnet_public_key' ).closest( 'form' ).on(
					'submit', function( event ) {
						if ( 'false' === novalnet_admin.ajax_complete ) {
							event.preventDefault();
							$( document ).ajaxComplete(
								function( event, xhr, settings ) {
									$( '#novalnet_public_key' ).closest( 'form' ).submit();
								}
							);
						}
					}
				);
			}
		},

		/* Process to fill the vendor details */
		fill_novalnet_details : function () {
			var form_params = {
				'system_ip': novalnet_admin.server_ip,
				'remote_ip': novalnet_admin.remote_ip,
				'lang': novalnet_admin.shop_lang,
				'api_config_hash': $.trim( $( '#novalnet_public_key' ).val() )
			};
			if ( '' !== novalnet_admin.server_ip ) {
				novalnet_admin.ajax_complete = 'false';
				return novalnet_functions.ajax_call( form_params, 'https://payport.novalnet.de/autoconfig', 'config' );
			} else {
				alert( novalnet_admin.empty_error );
				novalnet_functions.null_basic_params();
				return false;
			}
		}
	};

	$( document ).ready(function () {
		novalnet_config.process();
	});

})(jQuery);
