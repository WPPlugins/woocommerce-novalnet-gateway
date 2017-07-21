<?php
/**
 * Novalnet Admin Meta Boxes.
 *
 * Sets up the Extension process.
 *
 * @author   Novalnet
 * @category Admin
 * @package  Novalnet-gateway/Admin/Meta Boxes
 * @version  11.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

include_once 'meta-boxes/class-wc-novalnet-meta-box-manage-transaction.php';
include_once 'meta-boxes/class-wc-novalnet-meta-box-amount-refund.php';
include_once 'meta-boxes/class-wc-novalnet-meta-box-amount-update.php';
include_once 'meta-boxes/class-wc-novalnet-meta-box-amount-book.php';

/**
 * NN_Admin_Meta_Boxes
 */
class NN_Admin_Meta_Boxes {


	/**
	 * Stores the transaction details.
	 *
	 * @var array
	 */
	public static $transaction_details = array();

	/**
	 * Gateway supports Manage transaction.
	 *
	 * @var array
	 */
	public static $manage_transaction = array(
	'6',
	'27',
	'37',
	'34',
	'41',
	'40',
	);

	/**
	 * Gateway supports Amount booking.
	 *
	 * @var array
	 */
	public static $supports_amount_book = array(
	'6',
	'37',
	'34',
	);

	/**
	 * Gateway on-hold status.
	 *
	 * @var array
	 */
	public static $on_hold_status = array(
	'99',
	'98',
	'91',
	'85',
	);

	/**
	 * Constructor
	 *
	 * @param WC_Order $wc_order The order object.
	 */
	public function __construct() {

		if ( wc_novalnet_compare_version( '3.0.0', WOOCOMMERCE_VERSION, '<' ) ) {
		    add_action( 'woocommerce_register_post_type', array( $this, 'show_meta_box' ) );
		} else {
		    add_action( 'woocommerce_after_register_post_type', array( $this, 'show_meta_box' ) );
		}
	}
	/**
	 * show meta box.
	 */
	public function show_meta_box() {

		$request = wp_unslash( $_REQUEST ); // Input var okay.
		if ( ! empty( $request ['post'] ) || ! empty( $request ['post_ID'] ) ) {
			$post = ! empty( $request['post'] ) ? $request['post'] : $request['post_ID'];
			$post_details = get_post( $post );
			if ( 'shop_order' === $post_details->post_type ) {
				$wc_order = new WC_Order( $post );

				$novalnet_payment_method = novalnet_instance()->novalnet_functions()->get_novalnet_payment_method( $wc_order );
				// Checks for Novalnet payment & Initiate NN_Admin_Meta_Boxes.
				if ( wc_novalnet_check_string( $novalnet_payment_method ) ) {

					// Novalnet API process.
					if ( ! empty( $request ['novalnet_transaction_process'] ) ) {
						add_action( 'woocommerce_process_shop_order_meta', array( 'NN_Meta_Box' . $request ['novalnet_transaction_process'], 'save' ), 1, 1 );
					}

					$order_id = novalnet_instance()->novalnet_functions()->get_novalnet_id( $wc_order );
					// Get transaction details.
					self::$transaction_details = wc_novalnet_get_transaction_details( $order_id, 'extension' );

					if ( ! empty( self::$transaction_details ) ) {

						// Script action.
						add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_styles_scripts' ) );

						// Extension actions.
						add_action( 'add_meta_boxes', array( &$this, 'add_manage_transaction_boxes' ), 25 );
						add_action( 'add_meta_boxes', array( &$this, 'add_amount_update_boxes' ), 25 );
						add_action( 'add_meta_boxes', array( &$this, 'add_amount_book_boxes' ), 25 );
						add_action( 'add_meta_boxes', array( &$this, 'add_refund_boxes' ), 25 );
					}
				}
			}
		}
	}

	/**
	 * Add extension scripts.
	 */
	public static function enqueue_styles_scripts() {

		// Enqueue style & script.
		wp_enqueue_script( 'wc-novalnet-extension-script', novalnet_instance()->plugin_url() . '/assets/js/admin/novalnet-extension.js', array( 'jquery' ), NN_VERSION, true );
		wp_localize_script(
			'wc-novalnet-extension-script', 'novalnet_admin_meta_boxes', array(
			'empty_amount'                   => __( 'The amount is invalid', 'wc-novalnet' ),
			'select_status'                  => __( 'Please select status!', 'wc-novalnet' ),
			'account_details_invalid'        => __( 'Your account details are invalid', 'wc-novalnet' ),
			'due_date_error'                 => __( 'Due date is not valid', 'wc-novalnet' ),
			'due_date_past_error'            => __( 'The date should be in future', 'wc-novalnet' ),
			'amount_update_message'          => ( '27' === self::$transaction_details ['payment_id'] ) ? __( 'Are you sure you want to change the order amount or due date?', 'wc-novalnet' ) : __( 'Are you sure you want to change the order amount?', 'wc-novalnet' ),
			'amount_refund_message'          => __( 'Are you sure you want to refund the amount?', 'wc-novalnet' ),
			'capture_update_message'         => __( 'Are you sure you want to capture the payment?', 'wc-novalnet' ),
			'cancel_update_message'          => __( 'Are you sure you want to cancel the payment?', 'wc-novalnet' ),
			'book_update_message'          => __( 'Are you sure you want to book the order amount?', 'wc-novalnet' ),
			)
		);
	}

	/**
	 * Add Manage transaction Meta boxes.
	 */
	public static function add_manage_transaction_boxes() {

		// To show capture / void box.
		if ( in_array( self::$transaction_details ['payment_id'], self::$manage_transaction, true ) && in_array( self::$transaction_details ['gateway_status'], self::$on_hold_status, true ) ) {
			add_meta_box( 'novalnet-manage-transaction', _x( 'Manage Transaction', 'meta box title', 'wc-novalnet' ), 'NN_Meta_Box_Manage_Transaction::output', 'shop_order', 'side', 'default' );
		}
	}

	/**
	 * Add Amount update Meta boxes.
	 */
	public static function add_amount_update_boxes() {

		// To show Amount / due date update box.
		if ( ( '1' === self::$transaction_details ['booked'] && '37' === self::$transaction_details ['payment_id'] &&  in_array( self::$transaction_details ['gateway_status'], self::$on_hold_status, true ) ) || ( '27' === self::$transaction_details ['payment_id'] && wc_novalnet_status_check( self::$transaction_details, 'gateway_status' ) && self::$transaction_details ['callback_amount'] < self::$transaction_details ['amount'] ) ) {
			add_meta_box( 'novalnet-amount-update', ( '27' === self::$transaction_details ['payment_id'] ) ?  _x( 'Change the amount / due date ', 'meta box title', 'wc-novalnet' ) : _x( 'Amount update', 'meta box title', 'wc-novalnet' ), 'NN_Meta_Box_Amount_Update::output', 'shop_order', 'side', 'default', self::$transaction_details );
		}
	}

	/**
	 * Add Amount book Meta boxes.
	 */
	public static function add_amount_book_boxes() {

		// To show amount book box.
		if ( in_array( self::$transaction_details ['payment_id'], self::$supports_amount_book, true ) && empty( self::$transaction_details ['amount'] ) && '0' === self::$transaction_details ['booked'] ) {
			add_meta_box( 'novalnet-amount-book', _x( 'Book transaction', 'meta box title', 'wc-novalnet' ), 'NN_Meta_Box_Amount_Book::output', 'shop_order', 'side', 'default' );
		}
	}

	/**
	 * Add Amount Refund Meta boxes.
	 */
	public static function add_refund_boxes() {

		// To show refund box.
		if ( wc_novalnet_status_check( self::$transaction_details, 'gateway_status' ) && '1' === self::$transaction_details ['booked'] ) {
			add_meta_box( 'novalnet-amount-refund', _x( 'Transaction Refund', 'meta box title', 'wc-novalnet' ), 'NN_Meta_Box_Amount_Refund::output', 'shop_order', 'side', 'default', self::$transaction_details );
		}
	}

	/**
	 * Redirect process.
	 *
	 * @since 11.0.0
	 * @param int $wc_order_id  The order ID.
	 * @param int $message_type Message type.
	 */
	public static function redirect_process( $wc_order_id, $message_type = '' ) {

		wc_novalnet_safe_redirect(
			add_query_arg(
				array(
				'action'  => 'edit',
				'post'    => $wc_order_id,
				'message' => $message_type,
				)
			)
		);
	}

	/**
	 * Check and maintain debug log if enabled
	 *
	 * @param string $message Message to be logged.
	 *
	 * @since 11.0.0
	 */
	public static function maintain_debug_log( $message ) {

		if ( get_option( 'novalnet_debug_log' ) ) {
			 $novalnet_log = wc_novalnet_logger();
			 $novalnet_log->add( 'novalnetpayments', $message );
		}
	}
}

new NN_Admin_Meta_Boxes();
