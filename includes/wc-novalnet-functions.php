<?php
/**
 * Novalnet Functions.
 *
 * General Novalnet functions.
 *
 * @category Core
 * @package  Novalnet-gateway/Functions
 * @version  11.2.0
 * @author   Novalnet
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Redirect to given URL.
 *
 * @since 10.0.0
 * @param string $url The url value.
 */
function wc_novalnet_safe_redirect( $url = '' ) {
	if ( '' === $url ) {
		$url = WC()->cart->get_checkout_url();
	}
	wp_safe_redirect( $url );
	exit();
}

/**
 * Get payment configuration.
 *
 * @since 11.0.0
 * @param string $payment_type The payment type value.
 *
 * @return array
 */
function wc_novalnet_payment_config( $payment_type ) {
	return get_option( 'woocommerce_' . $payment_type . '_settings' );
}

/**
 * Add Novalnet function scripts in front-end.
 *
 * @since 11.0.0
 */
function wc_novalnet_enqueue_script() {

	$get_plugin_url = novalnet_instance()->plugin_url();

	// Enqueue script in front-end.
	wp_enqueue_script( 'wc-novalnet-functions-script', $get_plugin_url . '/assets/js/novalnet-functions.js', array( 'jquery', 'jquery-payment' ), NN_VERSION, true );
	wp_enqueue_style( 'wc-novalnet-functions-script', $get_plugin_url . '/assets/css/novalnet.css',  array(), NN_VERSION );
	wp_localize_script(
		'wc-novalnet-functions-script', 'novalnet_function', array(
			'given_account_details' => __( 'Given account details', 'wc-novalnet' ),
			'given_card_details'    => __( 'Given card details', 'wc-novalnet' ),
			'given_paypal_details'  => __( 'Given PayPal account details', 'wc-novalnet' ),
			'enter_account_details' => __( 'Enter new account details', 'wc-novalnet' ),
			'enter_card_details'    => __( 'Enter new card details', 'wc-novalnet' ),
			'enter_paypal_details'  => __( 'Proceed with new PayPal account details', 'wc-novalnet' ),
		)
	);
}

/**
 * Add Novalnet function scripts in admin.
 *
 * @since 11.0.0
 */
function wc_novalnet_admin_enqueue_script() {

	$get_plugin_url = novalnet_instance()->plugin_url();

	// Enqueue script in front-end.
	wp_enqueue_script( 'wc-novalnet-functions-script', $get_plugin_url . '/assets/js/novalnet-functions.js', array( 'jquery' ), NN_VERSION, true );
	wp_enqueue_style( 'wc-novalnet-functions-script', $get_plugin_url . '/assets/css/novalnet.css',  array(), NN_VERSION );
}

/**
 * Checks for the given string in given text.
 *
 * @since 11.0.0
 * @param string $string The string value.
 * @param string $data   The data value.
 *
 * @return boolean
 */
function wc_novalnet_check_string( $string, $data = 'novalnet' ) {
	return ( false !== strpos( $string, $data ) );
}

/**
 * Check and define constant.
 *
 * @since 11.0.0
 * @param string      $name  The constant name.
 * @param string|bool $value The constant name value.
 */
function wc_novalnet_define( $name, $value ) {

	// Define constants.
	if ( ! defined( $name ) ) {
		define( $name, $value );
	}
}

/**
 * Validates the given input data is numeric or not.
 *
 * @since 10.0.0
 * @param int $input The input value.
 *
 * @return boolean
 */
function wc_novalnet_digits_check( $input ) {
	return ( preg_match( '/^[0-9]+$/', $input ) ) ? $input : false;
}

/**
 * Format the text.
 *
 * @since 11.0.0
 * @param string $text The test value.
 *
 * @return int|boolean
 */
function wc_novalnet_format_text( $text ) {
	return html_entity_decode( $text, ENT_QUOTES, 'UTF-8' );
}

/**
 * Get next subscription cycle date.
 *
 * @since 11.0.0
 * @param array $data The response data.
 *
 * @return string
 */
function wc_novalnet_next_subscription_date( $data ) {

	// Check for next subscription cycle parameter.
	if ( ! empty( $data ['next_subs_cycle'] ) ) {
		return $data ['next_subs_cycle'];
	}
	return $data ['paid_until'];
}

/**
 * Validates the given input data is alpha-numeric or not.
 *
 * @since 10.0.0
 * @param string $input The input value.
 *
 * @return boolean
 */
function wc_novalnet_alphanumeric_check( $input ) {
	return preg_match( '/^[0-9a-zA-Z]+$/', trim( $input ) );
}

/**
 * Formating the amount as per the
 * shop structure.
 *
 * @since 11.0.0
 * @param float $amount The amount value.
 *
 * @return string
 */
function wc_novalnet_shop_amount_format( $amount ) {

	// To get customer note based on version.
	if ( wc_novalnet_compare_version( '3.0.0', WOOCOMMERCE_VERSION, '<' ) ) {
		return strip_tags( woocommerce_price( sprintf( '%0.2f', $amount ) ) );
	} else {
		return strip_tags( wc_price( sprintf( '%0.2f', $amount ) ) );
	}
}

/**
 * Formating the date as per the
 * shop structure.
 *
 * @since 11.0.0
 * @param date $date The date value.
 *
 * @return string
 */
function wc_novalnet_formatted_date( $date = '' ) {
	return date_i18n( get_option( 'date_format' ), strtotime( '' === $date ? date( 'Y-m-d H:i:s' ) : $date ) );
}

/**
 * Subscription cancellation reason form.
 *
 * @since  11.0.0
 * @return string
 */
function wc_novalnet_subscription_cancel_form() {
	$form = '<form method="POST" id="novalnet_subscription_cancel"><select id="novalnet_subscription_cancel_reason" name="novalnet_subscription_cancel_reason">';

	// Append subscription cancel reasons.
	foreach ( wc_novalnet_subscription_cancel_list() as $key => $reason ) {
		$form .= '<option value="' . $key . '">' . $reason . '</option>';
	}
	$form .= '</select><br/><br/><input type="submit" class="button novalnet_cancel" onclick="return novalnet_subscription_functions.process_subscription_cancel(this);" id="novalnet_cancel" value=' . __( 'Cancel', 'wc-novalnet' ) . '></form>';
	return $form;
}

	/**
	 * Retrieves the Novalnet subscription cancel reasons.
	 *
	 * @since  11.0.0
	 * @return array
	 */
function wc_novalnet_subscription_cancel_list() {
	return array(
	   __( '--Select--', 'wc-novalnet' ),
	   __( 'Product is costly', 'wc-novalnet' ),
	   __( 'Cheating', 'wc-novalnet' ),
	   __( 'Partner interfered', 'wc-novalnet' ),
	   __( 'Financial problem', 'wc-novalnet' ),
	   __( 'Content does not match my likes', 'wc-novalnet' ),
	   __( 'Content is not enough', 'wc-novalnet' ),
	   __( 'Interested only for a trial', 'wc-novalnet' ),
	   __( 'Page is very slow', 'wc-novalnet' ),
	   __( 'Satisfied customer', 'wc-novalnet' ),
	   __( 'Logging in problems', 'wc-novalnet' ),
	   __( 'Other', 'wc-novalnet' ),
	);
}

/**
 * Handling db insert operation.
 *
 * @since 11.0.0
 * @param array  $insert_value The values to be insert in the given table.
 * @param string $table_name   The table name.
 */
function wc_novalnet_db_insert_query( $insert_value, $table_name ) {
	global $wpdb;

	// Perform query action.
	wc_novalnet_query_process( $wpdb->insert( "{$wpdb->prefix}$table_name", $insert_value ) ); // db call ok.
}

/**
 * Load the template
 *
 * @since 11.0.0
 * @param string $file_name The file name.
 * @param array  $contents The contents for the template.
 * @param string $content_name The name of the contents array.
 */
function wc_novalnet_load_template( $file_name, $contents, $content_name = 'contents' ) {

		$directory_path = dirname( dirname( __FILE__ ) ) . '/templates/checkout/';
	if ( function_exists( 'wc_get_template' ) ) {
		wc_get_template(
			$file_name, array(
			'default_path' => $directory_path,
			$content_name  => $contents,
			)
		);
	} else {
		include_once $directory_path . $file_name;
	}
}

/**
 * Handling db select operation.
 *
 * @since 11.0.0
 * @param string $post_id The post ID.
 *
 * @return array
 */
function wc_novalnet_get_product_item_value( $post_id ) {
	global $wpdb;

	// Perform query action.
	return $wpdb->get_var( $wpdb->prepare( "SELECT order_item_id FROM {$wpdb->prefix}woocommerce_order_items WHERE order_id=%d AND order_item_type='line_item'", $post_id ) ); // db call ok; no-cache ok.
}

/**
 * Handling db update operation.
 *
 * @since 11.0.0
 * @param array  $update_value The update values.
 * @param array  $where_array  The where condition query.
 * @param string $table_name   The table name.
 */
function wc_novalnet_db_update_query( $update_value, $where_array, $table_name = 'novalnet_transaction_detail' ) {
	global $wpdb;

	// Perform query action.
	wc_novalnet_query_process( $wpdb->update( "{$wpdb->prefix}$table_name", $update_value, $where_array ) ); // db call ok; no-cache ok.
}

/**
 * Throw exception error for database handling
 *
 * @since 11.0.0
 * @param string  $query        The handled query.
 * @param boolean $return_query The value of the handled query.
 *
 * @return boolean|string
 *
 * @throws Exception For last error.
 */
function wc_novalnet_query_handling( $query, $return_query = false ) {
	global $wpdb;

	// Checking for query error.
	if ( $wpdb->last_error ) {
		throw new Exception( $wpdb->last_error );
	}
	return $return_query ? $query : true;
}

/**
 * Handles the error while exception occurs.
 *
 * @since 11.0.0
 * @param string  $query        The processed query.
 * @param boolean $return_query The value of the processed query.
 *
 * @return boolean
 */
function wc_novalnet_query_process( $query, $return_query = true ) {

	$query_return = '';
	try {

		// DB error handling.
		$query_return = wc_novalnet_query_handling( $query, $return_query );

	} catch ( Exception $e ) {
		$novalnet_log = wc_novalnet_logger();
		$novalnet_log->add( 'novalneterrorlog', 'Database error occured: ' . $e->getMessage() );
	}
	return ( $return_query ) ? $query_return : true;
}

/**
 * Contents of Novalnet merchant administration portal link.
 *
 * @since 10.0.0
 */
function wc_novalnet_admin_information() {
	echo '<h2>' . esc_attr( __( 'Novalnet Administration Portal', 'wc-novalnet' ) ) . "</h2>
        <div class='novalnet_map_header'>" . esc_attr( __( 'Login here with Novalnet merchant credentials. For the activation of new payment methods please contact', 'wc-novalnet' ) ) . " <a href='mailto:support@novalnet.de'>support@novalnet.de</a></div>
        <iframe frameborder='0' width='100%' height='600px' border='0' src='https://admin.novalnet.de/'></iframe>";
}

/**
 * Built params for API calls.
 *
 * @since 11.0.0
 * @param array $transaction_details The transaction details data.
 *
 * @return array
 */
function wc_novalnet_built_api_params( $transaction_details ) {
	return array(
	'vendor'    => $transaction_details ['vendor_id'],
	'auth_code' => $transaction_details ['auth_code'],
	'product'   => $transaction_details ['product_id'],
	'tariff'    => $transaction_details ['tariff_id'],
	'key'       => $transaction_details ['payment_id'],
	'tid'       => $transaction_details ['tid'],
	'remote_ip' => wc_novalnet_get_ip_address(),
	);
}

/**
 * Built extension operation submit button.
 *
 * @since 11.0.0
 * @param array $data The resourse data.
 */
function wc_novalnet_built_button( $data ) {

	echo '<button id="' . esc_attr( $data ['id'] ) . '" name="' . esc_attr( $data ['id'] ) . '" value="' . esc_attr( $data ['type'] ) . '" class="button button-primary tips" data-tip="' . esc_attr( $data ['tip'] ) . '" onclick="return novalnet_meta_box.process' . esc_attr( strtolower( $data ['type'] ) ) . '(this);">' . esc_attr( $data ['title'] ) . '</button>';
}

/**
 * Show notice if WooCommerce is not active.
 *
 * @since 10.0.0
 */
function wc_novalnet_checks_woocommerce_active() {
	echo '<div id="notice" class="error"><p>' . sprintf( wp_kses( __( 'WooCommerce plugin must be active for the plugin <b>Novalnet Payment Gateway for WooCommerce</b>.Kindly %1$s install & activate it %2$s ', 'wc-novalnet' ), array( 'b' => array() ) ), '<a href="http://www.woothemes.com/woocommerce/" target="_new">', '</a>' ) . '</p></div>';
}

/**
 * Check Subscription version.
 *
 * @since  11.0.0
 * @return boolean
 */
function wc_novalnet_is_subscription_2x() {
	return wc_novalnet_compare_version( '2.0.0', get_option( 'woocommerce_subscriptions_active_version' ) );
}

/**
 * Perform serialize data.
 *
 * @since 11.0.0
 * @param array $data The resourse data.
 *
 * @return string
 */
function wc_novalnet_serialize_data( $data ) {
	return ! empty( $data ) ? wp_json_encode( $data ) : '';
}

/**
 * Perform unserialize data.
 *
 * @since 11.0.0
 * @param array $data The resourse data.
 *
 * @return array
 */
function wc_novalnet_unserialize_data( $data ) {
	if ( is_serialized( $data ) ) {
		return maybe_unserialize( $data );
	}
	return (array) json_decode( $data );
}

/**
 * Restricting the Pay option shop front-end
 * if succesfull transaction has pending status.
 *
 * @since 11.0.0
 *
 * @param array    $actions The actions data.
 * @param WC_Order $order   The order object.
 *
 * @return array
 */
function wc_novalnet_filter_my_account( $actions, $order ) {

	$get_novalnet_payment_method = novalnet_instance()->novalnet_functions()->get_novalnet_payment_method( $order );

	// Check for Novalnet payment.
	$is_novalnet_payment = wc_novalnet_check_string( $get_novalnet_payment_method );

	// Unset pay option.
	if ( ! empty( $actions['pay'] ) && $is_novalnet_payment ) {
		unset( $actions['pay'] );
	}

	// Unset user order cancel option.
	if ( ! empty( $actions['cancel'] ) && $is_novalnet_payment ) {
		unset( $actions['cancel'] );
	}
	return $actions;
}

/**
 * Using all the shop order status for Novalnet payments.
 *
 * @since 11.0.0
 *
 * @param array $status The status data.
 *
 * @return array
 */
function wc_novalnet_append_shop_order_status( $status ) {
	if ( WC()->session->__isset( 'current_novalnet_payment' ) ) {
		foreach ( array_keys( wc_novalnet_get_shop_order_status() ) as $status_value ) {
			$order_status [] = ( 'wc-' === substr( $status_value, 0, 3 ) ) ? substr( $status_value, 3 ) : $status_value;
		}
		return $order_status;
	}
	return $status;
}

/**
 * Get shop order status based on the Woocommerce Version.
 *
 * @since 11.1.0
 *
 * @return array
 */
function wc_novalnet_get_shop_order_status() {

	if ( WOOCOMMERCE_VERSION >= '2.2.0' ) {
		return wc_get_order_statuses();
	}

	$statuses = (array) get_terms( 'shop_order_status', array( 'hide_empty' => 0, 'orderby' => 'id' ) );
	$data = array();
	foreach ( $statuses as $status ) {
		$data[ $status->slug ] = $status->name;
	}
	return $data;

}

/**
 * Retrieve the Novalnet payment type.
 *
 * @since 11.0.0
 * @param string $payment_type The payment type value.
 * @param string $key The key of the array.
 *
 * @return array
 */
function wc_novalnet_get_payment_type( $payment_type = '', $key = '' ) {
	$payment = array(
		'novalnet_cc'          => array(
			'payment_type' => 'CREDITCARD',
			'key'          => '6',
		),
		'novalnet_eps'          => array(
			'payment_type' => 'EPS',
			'key'          => '50',
		),
		'novalnet_ideal'          => array(
			'payment_type' => 'IDEAL',
			'key'          => '49',
		),
		'novalnet_invoice'          => array(
			'payment_type' => 'INVOICE',
			'key'          => '27',
		),
		'novalnet_paypal'          => array(
			'payment_type' => 'PAYPAL',
			'key'          => '34',
		),
		'novalnet_prepayment'          => array(
			'payment_type' => 'PREPAYMENT',
			'key'          => '27',
		),
		'novalnet_sepa'          => array(
			'payment_type' => 'DIRECT_DEBIT_SEPA',
			'key'          => '37',
		),
		'guarantee_novalnet_sepa'    => array(
			'payment_type' => 'GUARANTEED_DIRECT_DEBIT_SEPA',
			'key'          => '40',
		),
		'guarantee_novalnet_invoice' => array(
			'payment_type' => 'GUARANTEED_INVOICE_START',
			'key'          => '41',
		),
		'novalnet_instantbank'          => array(
			'payment_type' => 'ONLINE_TRANSFER',
			'key'          => '33',
		),
		'novalnet_giropay'          => array(
			'payment_type' => 'GIROPAY',
			'key'          => '69',
		),
		'novalnet_przelewy24'          => array(
			'payment_type' => 'PRZELEWY24',
			'key'          => '78',
		),
	);
	if ( '' !== $payment_type ) {
		if ( '' !== $key ) {
			return $payment [ $payment_type ] [ $key ];
		}
		return $payment [ $payment_type ];
	}
	return array_keys( $payment );
}

/**
 * Unset receipt page session.
 *
 * @since 11.0.0
 */
function wc_novalnet_receipt_page_session_unset() {
	WC()->session->__unset( 'novalnet_receipt_page' );
}

/**
 * Unset thankyou page session.
 *
 * @since 10.0.0
 */
function wc_novalnet_thankyou_page_session_unset() {

	// $post_id used in action.
	WC()->session->__unset( 'novalnet_thankyou_page' );
}


/**
 * Including callback API process.
 *
 * @since 11.0.0
 */
function wc_novalnet_process_callback_api_process() {

	// Process Callback API.
	include_once dirname( __FILE__ ) . '/api/class-wc-novalnet-api-callback.php';
}

/**
 * Removing / unset the gateway used sessions.
 *
 * @since 11.0.0
 * @param string $payment_type The payment type value.
 */
function wc_novalnet_unset_payment_session( $payment_type ) {

	WC()->session->__unset( 'novalnet_invoice_guarantee_payment' );
	WC()->session->__unset( 'novalnet_invoice_guarantee_payment_error' );
	WC()->session->__unset( 'novalnet_sepa_guarantee_payment' );
	WC()->session->__unset( 'novalnet_sepa_guarantee_payment_error' );
	WC()->session->__unset( 'novalnet_change_payment_method' );
	WC()->session->__unset( 'current_novalnet_payment' );
	WC()->session->__unset( 'novalnet_receipt_page' );
	WC()->session->__unset( 'novalnet_post_id' );
	WC()->session->__unset( 'novalnet' );
	WC()->session->__unset( 'sepa_hash' );
	WC()->session->__unset( $payment_type );
	WC()->session->__unset( $payment_type . '_tid' );
	WC()->session->__unset( $payment_type . '_order_total' );
	WC()->session->__unset( $payment_type . '_time_limit' );
	WC()->session->__unset( 'novalnet_sepa_fraud_check_validate' );
	WC()->session->__unset( 'novalnet_invoice_fraud_check_validate' );
	WC()->session->__unset( $payment_type . '_reference_tid' );
}

/**
 * Generate hash with the Novalnet config values.
 *
 * @since 10.0.0
 *
 * @param array $data The hash values.
 * @param array $key  The payment access key value.
 *
 * @return string
 */
function wc_novalnet_generate_hash( $data, $key ) {

	// hash generation using md5 and encoded vendor details.
	return md5( $data ['auth_code'] . $data ['product'] . $data ['tariff'] . $data ['amount'] . $data ['test_mode'] . $data ['uniqid'] . strrev( $key ) );
}

/**
 * Generate random string for hash call.
 *
 * @since  11.0.0
 * @return string
 */
function wc_novalnet_random_string() {

	$random_array = array(
	'a',
	'b',
	'c',
	'd',
	'e',
	'f',
	'g',
	'h',
	'i',
	'j',
	'k',
	'l',
	'm',
	'1',
	'2',
	'3',
	'4',
	'5',
	'6',
	'7',
	'8',
	'9',
	'0',
	);
	shuffle( $random_array );
	return substr( implode( $random_array, '' ), 0, 30 );
}

/**
 * Format due_date.
 *
 * @since 11.0.0
 * @param int $days The date value.
 *
 * @return string
 */
function wc_novalnet_format_due_date( $days ) {
	return date( 'Y-m-d', mktime( 0, 0, 0, date( 'm' ), (date( 'd' ) + $days ), date( 'Y' ) ) );
}

/**
 * Calculate subscription period.
 *
 * @since 11.0.0
 * @param int    $interval The subscription interval value.
 * @param string $period   The subscription period value.
 *
 * @return string
 */
function wc_novalnet_calculate_subscription_period( $interval, $period ) {
	if ( $interval > 0 ) {
		$period = substr( $period, 0, 1 );
		if ( 'w' === $period ) {
			$period = 'd';
			$interval = $interval * 7;
		}
		return $interval . $period;
	}
	return '';
}

/**
 * Validate the email address.
 *
 * @since 11.0.0
 * @param string $email_address The Email address.
 *
 * @return boolean
 */
function wc_novalnet_validate_email( $email_address ) {

	if ( ! empty( $email_address ) ) {
		foreach ( explode( ',', $email_address ) as $value ) {
			if ( ! is_email( $value ) ) {
				return false;
			}
		}
	}
	return true;
}

/**
 * Retrieves messages from server response.
 *
 * @since 11.0.0
 * @param array $data The response data.
 *
 * @return string
 */
function wc_novalnet_response_text( $data ) {
	if ( isset( $data ['status_text'] ) ) {
		return $data ['status_text'];
	} elseif ( isset( $data ['status_desc'] ) ) {
		return $data ['status_desc'];
	} elseif ( isset( $data ['status_message'] ) ) {
		return $data ['status_message'];
	} elseif ( isset( $data ['subscription_pause'] ['status_message'] ) ) {
		return $data ['subscription_pause'] ['status_message'];
	} elseif ( isset( $data ['pin_status'] ['status_message'] ) ) {
		return $data ['pin_status'] ['status_message'];
	} elseif ( isset( $data ['subscription_update'] ['status_message'] ) ) {
		return $data ['subscription_update'] ['status_message'];
	} else {
		return __( 'Payment was not successful. An error occurred', 'wc-novalnet' );
	}
}

/**
 * Returns payment name and description.
 *
 * @since 11.0.0
 * @param string $payment_type The payment type value.
 *
 * @return array
 */
function wc_novalnet_payment_details( $payment_type ) {
	$return_array = array(
	'novalnet_cc'        => array(
	'title_en'       => 'Credit Card',
	'admin_title_en' => 'Novalnet Credit Card',
	'title_de'       => 'Kreditkarte',
	'admin_title_de' => 'Novalnet Kreditkarte',
	'description_en' => 'The amount will be debited from your credit card once the order is submitted',
	'description_de' => 'Der Betrag wird von Ihrer Kreditkarte abgebucht, sobald die Bestellung abgeschickt wird.',
	),
	'novalnet_eps'       => array(
	'title_en'       => 'eps',
	'admin_title_en' => 'Novalnet eps',
	'title_de'       => 'eps',
	'admin_title_de' => 'Novalnet eps',
	'description_en' => 'After the successful verification, you will be redirected to Novalnet secure order page to proceed with the payment',
	'description_de' => 'Nach der erfolgreichen Überprüfung werden Sie auf die abgesicherte Novalnet-Bestellseite umgeleitet, um die Zahlung fortzusetzen.',

	),
	'novalnet_ideal'     => array(
	'title_en'       => 'iDEAL',
	'admin_title_en' => 'Novalnet iDEAL',
	'title_de'       => 'iDEAL',
	'admin_title_de' => 'Novalnet iDEAL',
	'description_en' => 'After the successful verification, you will be redirected to Novalnet secure order page to proceed with the payment',
	'description_de' => 'Nach der erfolgreichen Überprüfung werden Sie auf die abgesicherte Novalnet-Bestellseite umgeleitet, um die Zahlung fortzusetzen.',
	),
	'novalnet_invoice'   => array(
	'title_en'       => 'Invoice',
	'admin_title_en' => 'Novalnet Invoice',
	'title_de'       => 'Kauf auf Rechnung',
	'admin_title_de' => 'Novalnet Kauf auf Rechnung',
	'description_en' => 'Once you\'ve submitted the order, you will receive an e-mail with account details to make payment',
	'description_de' => 'Nachdem Sie die Bestellung abgeschickt haben, erhalten Sie eine Email mit den Bankdaten, um die Zahlung durchzuführen.',
	),
	'novalnet_paypal'    => array(
	'title_en'       => 'PayPal',
	'admin_title_en' => 'Novalnet PayPal',
	'title_de'       => 'PayPal',
	'admin_title_de' => 'Novalnet PayPal',
	'description_en' => 'After the successful verification, you will be redirected to Novalnet secure order page to proceed with the payment',
	'description_de' => 'Nach der erfolgreichen Überprüfung werden Sie auf die abgesicherte Novalnet-Bestellseite umgeleitet, um die Zahlung fortzusetzen.',
	),
	'novalnet_prepayment' => array(
	'title_en'       => 'Prepayment',
	'admin_title_en' => 'Novalnet Prepayment',
	'title_de'       => 'Vorauskasse',
	'admin_title_de' => 'Novalnet Vorauskasse',
	'description_en' => 'Once you\'ve submitted the order, you will receive an e-mail with account details to make payment',
	'description_de' => 'Nachdem Sie die Bestellung abgeschickt haben, erhalten Sie eine Email mit den Bankdaten, um die Zahlung durchzuführen.',
	),
	'novalnet_sepa'      => array(
	'title_en'       => 'Direct Debit SEPA',
	'admin_title_en' => 'Novalnet Direct Debit SEPA',
	'title_de'       => 'Lastschrift SEPA',
	'admin_title_de' => 'Novalnet Lastschrift SEPA',
	'description_en' => 'Your account will be debited upon the order submission',
	'description_de' => 'Ihr Konto wird nach Abschicken der Bestellung belastet.',
	),
	'novalnet_instantbank' => array(
	'title_en'       => 'Instant Bank Transfer',
	'admin_title_en' => 'Novalnet Instant Bank Transfer',
	'title_de'       => 'Sofortüberweisung',
	'admin_title_de' => 'Novalnet Sofortüberweisung',
	'description_en' => 'After the successful verification, you will be redirected to Novalnet secure order page to proceed with the payment',
	'description_de' => 'Nach der erfolgreichen Überprüfung werden Sie auf die abgesicherte Novalnet-Bestellseite umgeleitet, um die Zahlung fortzusetzen.',
	),
	'novalnet_giropay'   => array(
	'title_en'       => 'giropay',
	'admin_title_en' => 'Novalnet giropay',
	'title_de'       => 'giropay',
	'admin_title_de' => 'Novalnet giropay',
	'description_en' => 'After the successful verification, you will be redirected to Novalnet secure order page to proceed with the payment',
	'description_de' => 'Nach der erfolgreichen Überprüfung werden Sie auf die abgesicherte Novalnet-Bestellseite umgeleitet, um die Zahlung fortzusetzen.',
	),
	'novalnet_przelewy24'   => array(
	'title_en'       => 'Przelewy24',
	'admin_title_en' => 'Novalnet Przelewy24',
	'title_de'       => 'Przelewy24',
	'admin_title_de' => 'Novalnet Przelewy24',
	'description_en' => 'After the successful verification, you will be redirected to Novalnet secure order page to proceed with the payment',
	'description_de' => 'Nach der erfolgreichen Überprüfung werden Sie auf die abgesicherte Novalnet-Bestellseite umgeleitet, um die Zahlung fortzusetzen.',
	),
	);
	return $return_array [ $payment_type ];
}

/**
 * Retrieve the name of the end user.
 *
 * @since 11.0.0
 * @param string $name The customer name value.
 *
 * @return array
 */
function wc_novalnet_retrieve_name( $name ) {

	// Retrieve first name and last name from order objects.
	if ( empty( $name['0'] ) ) {
		$name['0'] = $name['1'];
	}
	if ( empty( $name['1'] ) ) {
		$name['1'] = $name['0'];
	}
	return $name;
}

/**
 * Return server / remote address.
 *
 * @since 11.0.0
 * @param string $type The host address type.
 *
 * @return float
 */
function wc_novalnet_get_ip_address( $type = 'REMOTE_ADDR' ) {
	$server = $_SERVER; // input var okay.

	// Check for valid IP.
	if ( filter_var( $server [ $type ], FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 ) || '::1' === $server [ $type ] ) {
		return '127.0.0.1';
	}
	return $server [ $type ];
}

/**
 * Validate core PHP functions.
 *
 * @since  11.0.0
 * @return boolean
 */
function wc_novalnet_validate_core_functions() {
	return ! function_exists( 'curl_init' ) || ! function_exists( 'base64_encode' ) || ! function_exists( 'pack' ) || ! function_exists( 'crc32' ) || ! function_exists( 'md5' );
}

/**
 * Align mail order comments.
 *
 * @since 11.0.0
 * @param WC_Order $order The order object.
 */
function wc_novalnet_novalnet_align_email( $order ) {

	$novalnet_customer_note = novalnet_instance()->novalnet_functions()->get_novalnet_customer_note( $order );
	if ( wc_novalnet_compare_version( '3.0.0', WOOCOMMERCE_VERSION, '<' ) ) {
		$order->customer_note = wpautop( $novalnet_customer_note );
	} else {
		$order->set_customer_note( wpautop( $novalnet_customer_note ) );
	}
}

/**
 * Convert week to day
 *
 * @since  11.0.0
 * @param int    $period The subscription period.
 * @param string $interval The subscription interval.
 */
function wc_novalnet_convert_week_to_days( &$period, &$interval ) {
	if ( 'w' === $period ) {
		$period = 'd';
		$interval = $interval * 7;
	}
}

/**
 * Shows the payment template.
 *
 * @since 11.0.0
 * @param string  $payment_type The payment type value.
 * @param boolean $settings     The settings value.
 */
function wc_novalnet_payment_template( $payment_type, $settings ) {

	// Assign form template file.
	$template_file_name = array(
		'novalnet_cc'      => 'render-cc-form.php',
		'novalnet_sepa'    => 'render-sepa-form.php',
		'novalnet_invoice' => 'render-invoice-form.php',
		'novalnet_paypal'  => 'render-paypal-form.php',
	);

	// Displays input fields.
	wc_novalnet_load_template( $template_file_name[ $payment_type ], $settings, 'settings' );
}

/**
 * Returns original post_id based on TID.
 *
 * @since 11.0.0
 * @param int $tid The tid value.
 *
 * @return array
 */
function wc_novalnet_original_post_id( $tid ) {

	global $wpdb;

	// Get post id based on TID.
	$query = wc_novalnet_query_process( $wpdb->get_row( $wpdb->prepare( "SELECT ID FROM {$wpdb->posts} where post_excerpt LIKE %s", "%$tid%" ), ARRAY_A ) ); // db call ok; no-cache ok.
	return $query ['ID'];

}

/**
 * Returns the order post_id.
 *
 * @since 11.0.0
 * @param int $wc_order_id The order id.
 *
 * @return array
 */
function wc_novalnet_order_post_id( $wc_order_id ) {

	global $wpdb;

	// Get order post id.
	$query = wc_novalnet_query_process( $wpdb->get_row( $wpdb->prepare( "SELECT post_id FROM {$wpdb->postmeta} where meta_value='%d' AND (meta_key='_order_number_formatted' OR meta_key='_order_number' )", $wc_order_id ), ARRAY_A ) ); // db call ok; no-cache ok.
	return $query ['post_id'];
}


/**
 * Check for column availablity.
 *
 * @since 11.0.0
 * @param string $table_name  The table name.
 * @param string $column_name The column name.
 *
 * @return boolean
 */
function wc_novalnet_check_valid_column( $table_name, $column_name ) {

	global $wpdb;

	// Check for column exists.
	if ( 'novalnet_callback_history' === $table_name ) {
		return wc_novalnet_query_process( $wpdb->get_var( $wpdb->prepare( "SHOW COLUMNS FROM {$wpdb->prefix}novalnet_callback_history LIKE %s", $column_name ), true ) ); // db call ok; no-cache ok.
	}
	return wc_novalnet_query_process( $wpdb->get_var( $wpdb->prepare( "SHOW COLUMNS FROM {$wpdb->prefix}novalnet_transaction_detail LIKE %s", $column_name ), true ) ); // db call ok; no-cache ok.
}

/**
 * Check for table availablity.
 *
 * @since 11.0.0
 * @param string $table_name The table name.
 *
 * @return booolean
 */
function wc_novalnet_check_valid_table( $table_name ) {

	global $wpdb;

	// Check for table exists.
	return wc_novalnet_query_process( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s',  $wpdb->prefix . $table_name ) ), true ); // db call ok; no-cache ok.
}

/**
 * Returns sepa hash details.
 *
 * @since  11.0.0
 * @return array
 */
function wc_novalnet_get_sepa_hash() {

	global $current_user, $wpdb;

	// Select transaction details.
	if ( ! empty( $current_user->ID ) ) {
		return wc_novalnet_query_process( $wpdb->get_row( $wpdb->prepare( "SELECT bank_details, payment_type FROM {$wpdb->prefix}novalnet_transaction_detail WHERE customer_id=%s ORDER BY ID DESC", $current_user->ID ), ARRAY_A ) );// db call ok; no-cache ok.
	}
	return array();
}

/**
 * Transfer data via wordpress post method.
 *
 * @since 11.0.0
 * @param array  $request_data The request data.
 * @param string $url          The request url.
 *
 * @return string
 */
function wc_novalnet_server_request( $request_data, $url = 'https://payport.novalnet.de/paygate.jsp' ) {

	$time_out = trim( get_option( 'novalnet_gateway_timeout' ) );
	$time_out_value = wc_novalnet_digits_check( $time_out ) ? $time_out : 240;

	// Post the values to the paygate URL.
	$response = wp_remote_post(
		$url, array(
		'method'  => 'POST',
		'timeout' => $time_out_value,
		'body'    => $request_data,
		)
	);

	// Check for error.
	if ( is_wp_error( $response ) ) {
		$novalnet_log = wc_novalnet_logger();
		$novalnet_log->add( 'novalneterrorlog', 'While post the request error occured: ' . $response->get_error_message() );
		return 'tid=&status=' . $response->get_error_code() . '&status_message=' . $response->get_error_message();
	}

	// Return the response.
	return $response['body'];
}

/**
 * Returns Wordpress-blog language.
 *
 * @since  11.0.0
 * @return string
 */
function wc_novalnet_shop_language() {

	// Retrieve language code from blog language.
	return substr( get_bloginfo( 'language' ), 0, 2 );
}

/**
 * Converting the amount into cents
 *
 * @since 11.0.0
 * @param float $amount The amount.
 *
 * @return int
 */
function wc_novalnet_formatted_amount( $amount ) {

	return str_replace( ',', '', sprintf( '%0.2f', $amount ) ) * 100;
}

/**
 * Get subscription details from novalnet_subscription_details table.
 *
 * @since 11.0.0
 * @param int $tid      The Transaction ID.
 * @param int $order_no The Order id.
 *
 * @return array
 */
function wc_novalnet_get_subs_details( $tid, $order_no = '' ) {

	global $wpdb;

	if ( '' === $order_no ) {
		return wc_novalnet_query_process( $wpdb->get_row( $wpdb->prepare( "SELECT order_no, recurring_payment_type, next_payment_date FROM {$wpdb->prefix}novalnet_subscription_details WHERE recurring_tid=%s", $tid ), ARRAY_A ) );// db call ok; no-cache ok.
	}
	return wc_novalnet_query_process( $wpdb->get_row( $wpdb->prepare( "SELECT next_payment_date, recurring_payment_type FROM {$wpdb->prefix}novalnet_subscription_details WHERE order_no=%s", $order_no ), ARRAY_A ) );// db call ok; no-cache ok.
}

/**
 * Returns the details to execute callback.
 *
 * @since 11.0.0
 * @param int $tid     The TID value.
 * @param int $post_id The post id.
 *
 * @return array
 */
function wc_novalnet_get_callback_details( $tid, $post_id ) {

	global $wpdb;

	// Select transaction details based on TID or post_id.
	if ( ! empty( $post_id ) ) {
		return wc_novalnet_query_process( $wpdb->get_row( $wpdb->prepare( "SELECT vendor_id, auth_code, product_id, tariff_id, order_no, payment_type, amount, callback_amount, refunded_amount, tid, payment_id FROM {$wpdb->prefix}novalnet_transaction_detail WHERE tid=%s OR order_no=%s", $tid, $post_id ), ARRAY_A ) );// db call ok; no-cache ok.
	}

	return wc_novalnet_query_process( $wpdb->get_row( $wpdb->prepare( "SELECT vendor_id, auth_code, product_id, tariff_id, order_no, payment_type, amount, callback_amount, refunded_amount, tid, payment_id FROM {$wpdb->prefix}novalnet_transaction_detail WHERE tid=%s", $tid ), ARRAY_A ) );// db call ok; no-cache ok.
}

/**
 * Returns payment details from database.
 *
 * @since 11.0.0
 * @param string $payment_type The payment type.
 *
 * @return array
 */
function wc_novalnet_get_bank_details( $payment_type ) {

	global $current_user, $wpdb;

	// Select transaction details.
	if ( ! empty( $current_user->ID ) ) {
		return wc_novalnet_query_process( $wpdb->get_row( $wpdb->prepare( "SELECT bank_details, tid FROM {$wpdb->prefix}novalnet_transaction_detail WHERE customer_id=%s and payment_type='%s' and booked='1' and payment_ref ='0' and bank_details!='' ORDER BY id DESC", $current_user->ID, $payment_type ), ARRAY_A ) );// db call ok; no-cache ok.
	}
	return array();
}

/**
 * Returns Direct Debit SEPA payment details from database.
 *
 * @since 11.1.0
 * @param string $payment_type The payment type.
 *
 * @return array
 */
function wc_novalnet_get_sepa_bank_details( $payment_type ) {

	global $current_user, $wpdb;

	// Select transaction details.
	if ( ! empty( $current_user->ID ) ) {
		return wc_novalnet_query_process( $wpdb->get_row( $wpdb->prepare( "SELECT bank_details, tid FROM {$wpdb->prefix}novalnet_transaction_detail WHERE customer_id=%s and payment_type='%s' and booked='1' and payment_ref ='0' and bank_details LIKE '%s' ORDER BY id DESC", $current_user->ID, $payment_type, '%iban%' ), ARRAY_A ) );// db call ok; no-cache ok.
	}
	return array();
}

/**
 * Returns order detail.
 *
 * @since 11.0.0
 * @param int    $post_id The post id.
 * @param string $table   The table name.
 *
 * @return array
 */
function wc_novalnet_order_no_details( $post_id, $table = 'novalnet_transaction_detail' ) {
	global $wpdb;

	// Get transaction properties based on order no and table.
	if ( 'novalnet_subscription_details' === $table ) {
		return wc_novalnet_query_process( $wpdb->get_row( $wpdb->prepare( "SELECT recurring_amount, next_payment_date FROM {$wpdb->prefix}novalnet_subscription_details WHERE order_no=%s", $post_id ), ARRAY_A ) );// db call ok; no-cache ok.
	} elseif ( 'novalnet_invoice_details' === $table ) {
		return wc_novalnet_query_process( $wpdb->get_row( $wpdb->prepare( "SELECT invoice_due_date, invoice_bank_details FROM {$wpdb->prefix}novalnet_invoice_details WHERE order_no=%s", $post_id ), ARRAY_A ) );// db call ok; no-cache ok.
	} else {
		return wc_novalnet_query_process( $wpdb->get_row( $wpdb->prepare( "SELECT bank_details FROM {$wpdb->prefix}novalnet_transaction_detail WHERE order_no=%s", $post_id ), ARRAY_A ) );// db call ok; no-cache ok.
	}
}

/**
 * Check for server status
 *
 * @since 11.0.0
 * @param array  $data   The response array.
 * @param string $key    The parameter to be checked.
 * @param string $status The status to be checked.
 *
 * @return array
 */
function wc_novalnet_status_check( $data, $key = 'status', $status = '100' ) {
	return ( ! empty( $data [ $key ] ) && $status === $data [ $key ] );
}

/**
 * Checks affiliate values.
 *
 * @since 11.0.0
 * @param array $params The affiliate params.
 */
function wc_novalnet_process_affiliate_action( &$params ) {

	global $wpdb, $current_user;

	if ( ! empty( WC()->session ) && WC()->session->__isset( 'novalnet_affiliate_id' ) ) {

		// Select affiliate details.
		$affiliate_details = wc_novalnet_query_process( $wpdb->get_row( $wpdb->prepare( "SELECT aff_authcode, aff_accesskey FROM {$wpdb->prefix}novalnet_aff_account_detail WHERE aff_id=%d ORDER BY id DESC", WC()->session->novalnet_affiliate_id ), ARRAY_A ) );// db call ok; no-cache ok.
		if ( ! empty( $affiliate_details ) ) {
			$params['vendor_id']    = WC()->session->novalnet_affiliate_id;
			$params['auth_code']    = $affiliate_details ['aff_authcode'];
			$params['key_password'] = $affiliate_details ['aff_accesskey'];
		}
	} elseif ( ! empty( $current_user->ID ) ) {

		// Check for previous affilliate.
		$query_value = wc_novalnet_query_process( $wpdb->get_row( $wpdb->prepare( "SELECT ad.aff_id, ad.aff_authcode, ad.aff_accesskey FROM {$wpdb->prefix}novalnet_aff_account_detail ad INNER JOIN {$wpdb->prefix}novalnet_aff_user_detail ud ON ad.aff_id = ud.aff_id WHERE ud.customer_id =%s ORDER BY ud.id DESC LIMIT 1", $current_user->ID ), ARRAY_A ) );// db call ok; no-cache ok.
		if ( ! empty( $query_value ) ) {
			$params ['vendor_id']    = $query_value ['aff_id'];
			$params ['auth_code']    = $query_value ['aff_authcode'];
			$params ['key_password'] = $query_value ['aff_accesskey'];
		}
	}
}

/**
 * Fetch the transaction details from database / Novalnet server.
 *
 * @since 11.0.0
 * @param int    $post_id The post value.
 * @param string $type The column type.
 *
 * @return array
 */
function wc_novalnet_get_transaction_details( $post_id, $type = '' ) {

	global $wpdb;

	// Select Transaction details.
	if ( 'subscription' === $type ) {
		$transaction_details = wc_novalnet_query_process( $wpdb->get_row( $wpdb->prepare( "SELECT vendor_id, auth_code, product_id, tariff_id, gateway_status, payment_id, tid, subs_id FROM {$wpdb->prefix}novalnet_transaction_detail WHERE order_no=%s", $post_id ), ARRAY_A ) );// db call ok; no-cache ok.
	} elseif ( 'extension' === $type ) {
		$transaction_details = wc_novalnet_query_process( $wpdb->get_row( $wpdb->prepare( "SELECT vendor_id, auth_code, product_id, tariff_id, gateway_status, payment_id, tid, callback_amount, amount, refunded_amount, test_mode, booked, bank_details, payment_params FROM {$wpdb->prefix}novalnet_transaction_detail WHERE order_no=%s", $post_id ), ARRAY_A ) );// db call ok; no-cache ok.
	} else {
		$transaction_details = wc_novalnet_query_process( $wpdb->get_row( $wpdb->prepare( "SELECT vendor_id, auth_code, product_id, tariff_id, gateway_status, payment_id, tid, callback_amount, amount, refunded_amount, test_mode, booked, bank_details FROM {$wpdb->prefix}novalnet_transaction_detail WHERE order_no=%s ORDER BY ID DESC", $post_id ), ARRAY_A ) );// db call ok; no-cache ok.
	}

	// Get values for lower version.
	if ( empty( $transaction_details ) && get_post_meta( $post_id, '_nn_version', true ) ) {
		$order_comments = wc_novalnet_query_process( $wpdb->get_row( $wpdb->prepare( "SELECT post_excerpt FROM {$wpdb->posts} WHERE ID='%s'", $post_id ), ARRAY_A ) );// db call ok; no-cache ok.

		// Get TID for the post_id from order comments.
		preg_match( '/ID[\s]*:[\s]*([0-9]{17})/', $order_comments ['post_excerpt'], $get_tid );
		$tid = '';
		if ( ! empty( $get_tid ['1'] ) ) {
			$tid = $get_tid ['1'];
		}

		// Assign transaction details based on TID.
		if ( '' !== $tid ) {
			$wc_order = new WC_Order( $post_id );
			$novalnet_payment_method = novalnet_instance()->novalnet_functions()->get_novalnet_payment_method( $wc_order );
			$transaction_details = array(
				'vendor_id'      => get_option( 'novalnet_vendor_id' ),
				'auth_code'      => get_option( 'novalnet_auth_code' ),
				'product_id'     => get_option( 'novalnet_product_id' ),
				'payment_type'   => $novalnet_payment_method,
				'payment_id'     => wc_novalnet_get_payment_type( $novalnet_payment_method, 'key' ),
				'booked'         => 1,
				'tid'            => $tid,
				'gateway_status' => '',
			);

			// Check for subscription tariff.
			$transaction_details ['tariff_id'] = apply_filters( 'novalnet_check_subscription', $wc_order ) ? get_option( 'novalnet_subs_tariff_id' ): get_option( 'novalnet_tariff_id' );
		} else {
			return array();
		}
	}
	return $transaction_details;
}

/**
 * Check for change payment method option.
 *
 * @since 11.1.0
 * @param array  $request      The payment type.
 * @param string $payment_type The payment type.
 *
 * @return boolean
 */
function wc_novalnet_check_payment_method_change( $request, $payment_type ) {
	return ( ! empty( $request ['post_type'] ) && 'shop_subscription' === $request ['post_type'] && wc_novalnet_check_string( $payment_type ) && get_post_meta( $request ['post_ID'], '_payment_method', true ) !== $payment_type && empty( $request ['novalnet_payment_change'] ) && in_array( $payment_type, array( 'novalnet_cc', 'novalnet_sepa', 'novalnet_invoice', 'novalnet_prepayment' ), true ) );
}

/**
 * Initiate WC_Logger
 *
 * @since 11.0.0
 *
 * @return object
 */
function wc_novalnet_logger() {
	$novalnet_log = new WC_Logger();
	return $novalnet_log;
}

/**
 * Get Subscription ID based on Order No.
 *
 * @since 11.0.0
 * @param int $post_id The order no.
 *
 * @return int
 */
function wc_novalnet_get_subs_id( $post_id ) {
	global $wpdb;

	return wc_novalnet_query_process( $wpdb->get_var( $wpdb->prepare( "SELECT subs_id FROM {$wpdb->prefix}novalnet_transaction_detail WHERE order_no=%s", $post_id ) ) ); // db call ok; no-cache ok.
}

/**
 * Get TID status of the particular post ID.
 *
 * @since 11.0.0
 * @param int $post_id The order no.
 *
 * @return int
 */
function wc_novalnet_get_tid_status( $post_id ) {
	global $wpdb;

	return wc_novalnet_query_process( $wpdb->get_var( $wpdb->prepare( "SELECT gateway_status FROM {$wpdb->prefix}novalnet_transaction_detail WHERE order_no=%s", $post_id ) ) ); // db call ok; no-cache ok.
}

/**
 * Send Mail Notification.
 *
 * @since 11.1.0
 * @param int $send_mail         Check for mail option.
 * @param int $email_to_address  E-mail to address.
 * @param int $email_subject     E-mail subject.
 * @param int $comments          E-mail Message content.
 * @param int $email_bcc_address E-mail Bcc address.
 */
function wc_novalnet_send_mail( $send_mail, $email_to_address, $email_subject, $comments, $email_bcc_address = '' ) {
	if ( $send_mail ) {
		if ( '' !== $email_to_address && is_email( $email_to_address ) ) {
			$headers = '';
			if ( is_email( $email_bcc_address ) ) {
				$headers = 'Bcc: ' . $email_bcc_address . PHP_EOL;
			}
			$mailer  = WC()->mailer();
			$message = $mailer->wrap_message( $email_subject, $comments );
			$mailer->send( $email_to_address, $email_subject, $message, $headers );
		}
	}
}


/**
 * Restrict instant order email.
 *
 * @since 11.1.0
 * @param string $value  Return value.
 *
 * @return string
 */
function wc_novalnet_restrict_instant_email( $value ) {
	$request = $_REQUEST; // input var okay.

	if ( ( isset( $request['payment_method'] ) && wc_novalnet_check_string( $request['payment_method'] ) ) || isset( $request['tid'] ) ) {
		$value = false;
	}
	return $value;
}

/**
 * Get order status value.
 *
 * @since 11.1.0
 * @param string $value  The value of the order status.
 *
 * @return string
 */
function wc_novalnet_format_default_order_status( $value ) {
	if ( wc_novalnet_compare_version( '2.2.0', WOOCOMMERCE_VERSION ) ) {
		return 'wc-' . $value;
	}
	return $value;
}

/**
 * Check for admin.
 *
 * @since 11.1.0
 * @return boolean
 */
function wc_novalnet_check_admin() {
	return is_admin();
}

/**
 * Compare version to adopt process.
 *
 * @since 11.1.0
 * @param string $version_to_compare  The version value.
 * @param string $plugin_version      The current plugin version.
 * @param string $operator            The operator.
 *
 * @return boolean
 */
function wc_novalnet_compare_version( $version_to_compare, $plugin_version, $operator = '>=' ) {
	return version_compare( $plugin_version, $version_to_compare, $operator );
}

/**
 * To avoid multiple payment fields while using
 * woocommerce-german-market plugin.
 *
 * @since 11.0.0
 */
function wc_novalnet_hide_multiple_payment() {
	if ( class_exists( 'Woocommerce_German_Market' ) ) {
		wc_enqueue_js(
			'
			if ( $( "div[id=payment]" ).length > 1) {
				' . wc_novalnet_process_multiple_payment_hide() . '
			}
		'
		);
	}
}

/**
 * Process to hide mutiple payment fields.
 *
 * @since 11.0.0
 */
function wc_novalnet_process_multiple_payment_hide() {
	$priority = 20;
	if ( class_exists( 'WooCommerce_Germanized' ) ) {
		$priority = 10;
		if ( 'yes' === get_option( 'woocommerce_gzd_display_checkout_fallback' ) ) {
			add_action( 'woocommerce_checkout_order_review', 'woocommerce_order_review', 10 );
		}
	}
	remove_action( 'woocommerce_checkout_order_review', 'woocommerce_checkout_payment', $priority );
}


/**
 * Handle API request for lower version.
 *
 * @since 11.1.0
 */
function wc_novalnet_handle_api_request() {

	$request = $_REQUEST; // input var okay.

	if ( ! empty( $request['wc-api'] ) ) {

		switch ( $request['wc-api'] ) {
			case 'novalnet_callback':

				wc_novalnet_process_callback_api_process();

			break;
			case 'response_novalnet_cc':

				$payment = new WC_Gateway_Novalnet_Cc();
				$payment->check_novalnet_payment_response();

			break;
			case 'response_novalnet_paypal':

				$payment = new WC_Gateway_Novalnet_Paypal();
				$payment->check_novalnet_payment_response();

			break;
			case 'response_novalnet_eps':

				$payment = new WC_Gateway_Novalnet_Eps();
				$payment->check_novalnet_payment_response();

			break;
			case 'response_novalnet_ideal':

				$payment = new WC_Gateway_Novalnet_Ideal();
				$payment->check_novalnet_payment_response();

			break;
			case 'response_novalnet_instantbank':

				$payment = new WC_Gateway_Novalnet_Instantbank();
				$payment->check_novalnet_payment_response();

			break;
			case 'response_novalnet_giropay':

				$payment = new WC_Gateway_Novalnet_Giropay();
				$payment->check_novalnet_payment_response();

			break;
			case 'response_novalnet_przelewy24':

				$payment = new WC_Gateway_Novalnet_Przelewy24();
				$payment->check_novalnet_payment_response();
			break;
			case 'novalnet_affiliate':
				wc_novalnet_handle_affiliate_process();
			break;
		}
	}
}

/**
 * Handle affiliate value from URL.
 *
 * @since 11.1.0
 */
function wc_novalnet_handle_affiliate_process() {

	$request = $_REQUEST; // input var okay.

	// Check if URL have affiliate id and assigned to SESSION.
	if ( wc_novalnet_digits_check( $request ['nn_aff_id'] ) ) {
		WC()->session->set( 'novalnet_affiliate_id', $request ['nn_aff_id'] );
	}
	wc_novalnet_safe_redirect( site_url() );
}

/**
 * Get the payment title / description / admin title.
 *
 * @since 11.1.0
 *
 * @param array  $settings   The payment settings.
 * @param string $language   Current shop language.
 * @param string $payment_id The payment ID.
 * @param string $title      The text to be returned.
 *
 * @return string
 */
function wc_novalnet_get_payment_text( $settings, $language, $payment_id, $title = 'title' ) {

	$payment_details = wc_novalnet_payment_details( $payment_id );
	if ( isset( $settings [ $title . '_' . $language ] ) ) {
		return $settings [ $title . '_' . $language ];
	} elseif ( isset( $payment_details[ $title . '_' . $language ] ) ) {
		return $payment_details[ $title . '_' . $language ];
	} else {
		return $payment_details[ $title . '_en' ];
	}
}


