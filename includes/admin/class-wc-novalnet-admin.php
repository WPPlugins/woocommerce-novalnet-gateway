<?php
/**
 * Novalnet Configuration Class
 *
 * @author   Novalnet
 * @category Admin
 * @package  Novalnet-gateway/Admin/Meta Boxes
 * @version  11.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
/**
 * Novalnet_Configuration
 */
class Novalnet_Configuration {


	/**
	 * Constructor
	 */
	public function __construct() {

		// Update Novalnet settings.
		add_action( 'woocommerce_update_options_novalnet_settings', array( $this, 'update_novalnet_settings' ) );

		// Save Novalnet settings.
		add_action( 'woocommerce_settings_save_novalnet_settings', array( $this, 'save' ) );

		// Enqueue admin scripts.
		add_action( 'admin_enqueue_scripts', array( &$this, 'enqueue_scripts' ) );

		// Add Novalnet settings tab.
		add_action( 'woocommerce_settings_tabs_novalnet_settings', array( $this, 'novalnet_settings_page' ) );

		// Add admin menu.
		add_action( 'admin_menu', array( &$this, 'add_novalnet_admin_menus' ) );

		// Add action links.
		add_filter( 'plugin_action_links_' . plugin_basename( NN_PLUGIN_FILE ), array( &$this, 'action_novalnet_links' ) );
	}

	/**
	 * Novalnet plugin action links
	 *
	 * @since 11.0.0
	 * @param array $links Default/ available links.
	 *
	 * @return array
	 */
	public function action_novalnet_links( $links ) {

		return array_merge( array( '<a href="' . admin_url( 'admin.php?page=wc-settings&tab=novalnet_settings' ) . '">' . __( 'Configuration', 'wc-novalnet' ) . '</a>' ), $links );
	}

	/**
	 * Adding admin script
	 *
	 * @since 11.0.0
	 */
	public static function enqueue_scripts() {

		// Enqueue style & script.
		wp_enqueue_script( 'wc-novalnet-admin-script', novalnet_instance()->plugin_url() . '/assets/js/admin/novalnet-admin.js', '', NN_VERSION );

		wp_localize_script(
			'wc-novalnet-admin-script', 'novalnet_admin', array(
			'empty_error'   => __( 'Please fill in all the mandatory fields', 'wc-novalnet' ),
			'shop_lang'     => wc_novalnet_shop_language(),
			'server_ip'     => wc_novalnet_get_ip_address( 'SERVER_ADDR' ),
			'remote_ip'     => wc_novalnet_get_ip_address(),
			'ajax_complete' => 'true',
			)
		);
	}

	/**
	 * Adds separate sub-menu for Novalnet administration portal under WooCommerce
	 *
	 * @since 11.0.0
	 */
	public function add_novalnet_admin_menus() {

		// Add Novalnet Admin portal menu.
		add_submenu_page( 'woocommerce', __( 'Novalnet Administration Portal', 'wc-novalnet' ), __( 'Novalnet Administration Portal', 'wc-novalnet' ), 'manage_options', 'wc-novalnet-admin', 'wc_novalnet_admin_information' );

		// Add Novalnet update information menu.
		add_submenu_page( 'woocommerce', __( 'Novalnet Updates', 'wc-novalnet' ), __( 'Novalnet Updates', 'wc-novalnet' ), 'manage_options', 'wc-novalnet-setup', array( $this, 'setup_info' ) );
	}

	/**
	 * To view Novalnet update information.
	 *
	 * @since 11.2.0
	 */
	public function setup_info() {
		if ( ! current_user_can( 'administrator' ) ) {
			return;
		}
		include_once( 'views/html-novalnet-admin-setup-notice.php' );
	}

	/**
	 * To view Novalnet config page
	 *
	 * @since 10.0.0
	 */
	public static function novalnet_settings_page() {

		woocommerce_admin_fields( self::novalnet_settings_fields() );
	}

	/**
	 * To validate the Novalnet configuration
	 *
	 * @since 11.0.0
	 */
	public function save() {

		$request = wp_unslash( $_REQUEST ); // Input var okay.

		// Process backend global configuration validation.
		if ( ! empty( $request['tab'] ) && 'novalnet_settings' === $request['tab'] && ! empty( $request['save'] ) ) {
			$error = wc_novalnet_validate_core_functions() ? esc_attr( __( 'Mentioned PHP Package(s) not available in this Server. Please enable it.', 'wc-novalnet' ) ) : ( (  novalnet_instance()->novalnet_functions()->validate_configuration() ) ? esc_attr( __( 'Please fill in all the mandatory fields', 'wc-novalnet' ) ) : '' );

			// Redirect while error occured.
			if ( '' !== $error ) {
				WC_Admin_Meta_Boxes::add_error( $error );
				wc_novalnet_safe_redirect( admin_url( 'admin.php?page=wc-settings&tab=novalnet_settings' ) );
			}
		}
	}

	/**
	 * Update the Novalnet configuration
	 *
	 * @since 11.0.0
	 */
	public static function update_novalnet_settings() {

		// Update Global configuraion fields.
		woocommerce_update_options( self::novalnet_settings_fields() );
	}

	/**
	 * Adds setting fields for Novalnet global configuration
	 *
	 * @since 10.0.0
	 */
	public static function novalnet_settings_fields() {

		$admin_url       = admin_url( 'admin.php?page=wc-novalnet-admin' );

		// Get woocommerce order status.
		$wc_order_status = wc_novalnet_get_shop_order_status();

		// Global configuration fields.
		return apply_filters(
			'woocommerce_novalnet_settings', array(
			array(
				'title' => 'Novalnet ' . __( 'Global Configuration', 'wc-novalnet' ),
				'id'    => 'novalnet_global_settings',
				'desc'  => sprintf( __( 'For additional configurations login to %1$sNovalnet Merchant Administration portal%2$s. To login to the Portal you need to have an account at Novalnet. If you don\'t have one yet, please contact <a href="mailto:sales@novalnet.de">sales@novalnet.de</a> / tel. +49 (089) 923068320<br/>To use the PayPal payment method please enter your PayPal API details in %3$sNovalnet Merchant Administration portal%4$s', 'wc-novalnet' ), '<a href="' . $admin_url . '" target="_new">', '</a>', '<a href="' . $admin_url . '" target="_new">', '</a>' ),
				'type'  => 'title',
			),
			array(
				'title'     => __( 'Product activation key', 'wc-novalnet' ),
				'desc'      => '<br/>' . sprintf( __( 'To get the Product Activation Key, go to <a href="%s" target="blank">Novalnet admin portal</a> - <strong>PROJECTS</strong>: Project Information - <strong>Shop Parameters</strong>: <strong>API Signature (Product activation key)</strong>.', 'wc-novalnet' ), $admin_url ),
				'id'        => 'novalnet_public_key',
				'css'       => 'width:25em;',
				'desc_tip'  => __( 'Enter Novalnet Product activation key', 'wc-novalnet' ),
				'type'      => 'text',
				'custom_attributes' => array(
					'autocomplete' => 'OFF',
				),
			),
			array(
				'title'     => __( 'Merchant ID', 'wc-novalnet' ),
				'id'        => 'novalnet_vendor_id',
				'css'       => 'width:25em;',
				'type'      => 'text',
			),
			array(
				'title'     => __( 'Authentication code', 'wc-novalnet' ),
				'id'        => 'novalnet_auth_code',
				'css'       => 'width:25em;',
				'type'      => 'text',
			),
			array(
				'title'     => __( 'Project ID', 'wc-novalnet' ),
				'id'        => 'novalnet_product_id',
				'css'       => 'width:25em;',
				'type'      => 'text',
			),
			array(
				'title'     => __( 'Tariff ID', 'wc-novalnet' ),
				'desc'      => __( 'Select tariff ID', 'wc-novalnet' ),
				'id'        => 'novalnet_tariff_id',
				'desc_tip'  => true,
				'css'       => 'width:25em;',
				'type'      => 'text',
			),
			array(
				'title'     => __( 'Payment access key', 'wc-novalnet' ),
				'id'        => 'novalnet_key_password',
				'css'       => 'width:25em;',
				'type'      => 'text',
			),
				array(
				'title'     => __( 'Set a limit for on-hold transaction', 'wc-novalnet' ),
				'desc'      => '<br/>' . __( '(in minimum unit of currency. E.g. enter 100 which is equal to 1.00)', 'wc-novalnet' ),
				'id'        => 'novalnet_manual_limit',
				'css'       => 'width:25em; autocomplete="off"',
				'desc_tip'  => __( 'In case the order amount exceeds mentioned limit, the transaction will be set on hold till your confirmation of transaction', 'wc-novalnet' ),
				'type'      => 'text',
				'custom_attributes' => array(
					'autocomplete' => 'OFF',
				),
			),
			array(
				'title'     => __( 'Display payment method logo', 'wc-novalnet' ),
				'desc'      => __( 'The payment method logo will be displayed on the checkout page', 'wc-novalnet' ),
				'id'        => 'novalnet_payment_logo',
				'type'      => 'select',
				'desc_tip'  => true,
				'default'   => '1',
				'options'   => array(
					'0' => __( 'No', 'wc-novalnet' ),
					'1' => __( 'Yes', 'wc-novalnet' ),
				),
			),
			array(
				'title'     => __( 'Gateway timeout (in seconds)', 'wc-novalnet' ),
				'desc'      => __( 'In case the order processing time exceeds the gateway timeout, the order will not be placed', 'wc-novalnet' ),
				'id'        => 'novalnet_gateway_timeout',
				'css'       => 'width:25em;',
				'type'      => 'text',
				'desc_tip'  => true,
				'default'   => '240',
				'custom_attributes' => array(
					'autocomplete' => 'OFF',
				),
			),
			array(
				'title'     => __( 'Debug log', 'wc-novalnet' ),
				'type'      => 'select',
				'id'       => 'novalnet_debug_log',
				'options'   => array( '0' => __( 'No', 'wc-novalnet' ), '1' => __( 'Yes', 'wc-novalnet' ) ),
				'default'   => '0',
				'desc'      => '<br/>' . sprintf( __( 'Novalnet payment events log in the mentioned path <code>%s.txt</code>', 'wc-novalnet' ), ((WOOCOMMERCE_VERSION > '2.2.0' ) ? wc_get_log_file_path( 'novalnetpayments' ) : 'woocommerce/logs/novalnetpayments-' . sanitize_file_name( wp_hash( 'novalnetpayments' ) ) ) ),
			),
			array(
				'title'     => __( 'Enable E-mail notification for test transaction', 'wc-novalnet' ),
				'type'      => 'select',
				'id'       => 'novalnet_test_order_notification',
				'options'   => array(
					'0' => __( 'No', 'wc-novalnet' ),
					'1' => __( 'Yes', 'wc-novalnet' ),
				),
				'default'   => '0',
				'desc_tip'  => true,
				'desc'      => __( 'You will receive email notifications about every test order in the web shop.', 'wc-novalnet' ),
			),
			array(
				'title'     => __( 'Referrer ID', 'wc-novalnet' ),
				'desc'      => __( 'Enter the referrer ID of the person/company who recommended you Novalnet', 'wc-novalnet' ),
				'id'        => 'novalnet_referrer_id',
				'css'       => 'width:25em;',
				'desc_tip'  => true,
				'type'      => 'text',
				'custom_attributes' => array(
					'autocomplete' => 'OFF',
				),
			),
			array(
				'type'   => 'sectionend',
				'id'     => 'novalnet_global_settings',
			),
			array(
				'title'     => __( 'Order status management for on-hold transaction(-s)', 'wc-novalnet' ),
				'type'      => 'title',
				'desc'      => '',
				'id'        => 'novalnet_status_management',
			),
			array(
				'title'     => __( 'Confirmation order status', 'wc-novalnet' ),
				'id'        => 'novalnet_onhold_success_status',
				'type'      => 'select',
				'default'   => wc_novalnet_format_default_order_status( 'completed' ),
				'options'   => $wc_order_status,
			),
			array(
				'title'     => __( 'Cancellation order status', 'wc-novalnet' ),
				'id'        => 'novalnet_onhold_cancel_status',
				'type'      => 'select',
				'default'   => wc_novalnet_format_default_order_status( 'cancelled' ),
				'options'   => $wc_order_status,
			),
			array(
				'type'      => 'sectionend',
				'id'        => 'novalnet_status_management',
			),
			array(
				'title'     => __( 'Dynamic subscription management', 'wc-novalnet' ),
				'type'      => 'title',
				'desc'      => '',
				'id'        => 'novalnet_subs_management',
			),
			array(
				'title'     => __( 'Enable subscription', 'wc-novalnet' ),
				'id'        => 'novalnet_enable_subs',
				'type'      => 'select',
				'options'   => array(
					'0'     => __( 'No', 'wc-novalnet' ),
					'1'     => __( 'Yes', 'wc-novalnet' ),
				),
				'default'   => '0',
			),
			array(
				'title'     => __( 'Subscription payments', 'wc-novalnet' ),
				'id'        => 'novalnet_subs_payments',
				'type'      => 'multiselect',
				'options'   => array(
					'novalnet_cc'         => __( 'Credit Card', 'wc-novalnet' ),
					'novalnet_sepa'       => __( 'Direct Debit SEPA', 'wc-novalnet' ),
					'novalnet_invoice'    => __( 'Invoice', 'wc-novalnet' ),
					'novalnet_prepayment' => __( 'Prepayment', 'wc-novalnet' ),
					'novalnet_paypal'     => __( 'PayPal', 'wc-novalnet' ),
				),
				'default'   => array(
					'novalnet_cc',
					'novalnet_sepa',
					'novalnet_invoice',
					'novalnet_prepayment',
					'novalnet_paypal',
				),
			),
			array(
				'title'     => __( 'Subscription Tariff ID', 'wc-novalnet' ),
				'desc'      => __( 'Select Novalnet subscription tariff ID', 'wc-novalnet' ),
				'id'        => 'novalnet_subs_tariff_id',
				'desc_tip'  => true,
				'type'      => 'text',
				'custom_attributes' => array(
					'autocomplete' => 'OFF',
				),
			),
			array(
				'type'      => 'sectionend',
				'id'        => 'novalnet_subs_management',
			),
			array(
				'title'     => __( 'Merchant script management', 'wc-novalnet' ),
				'type'      => 'title',
				'desc'      => '',
				'id'        => 'novalnet_vendor_script',
			),
			array(
				'title'     => __( 'Enable debug mode', 'wc-novalnet' ),
				'id'        => 'novalnet_callback_debug_mode',
				'desc'      => __( 'Set the debug mode to execute the merchant script in debug mode', 'wc-novalnet' ),
				'type'      => 'select',
				'desc_tip'  => true,
				'options'   => array(
				'0'     => __( 'No', 'wc-novalnet' ),
				'1'     => __( 'Yes', 'wc-novalnet' ),
			),
			),
			array(
				'title'     => __( 'Enable test mode', 'wc-novalnet' ),
				'id'        => 'novalnet_callback_test_mode',
				'type'      => 'select',
				'options'   => array(
					'0'     => __( 'No', 'wc-novalnet' ),
					'1'     => __( 'Yes', 'wc-novalnet' ),
				),
			),
			array(
				'title'     => __( 'Enable E-mail notification for callback', 'wc-novalnet' ),
				'id'        => 'novalnet_enable_callback',
				'type'      => 'select',
				'options'   => array(
					'0'     => __( 'No', 'wc-novalnet' ),
					'1'     => __( 'Yes', 'wc-novalnet' ),
				),
			'default'   => '0',
			),
			array(
				'title'     => __( 'Notification URL', 'wc-novalnet' ),
				'id'        => 'novalnet_callback_notify_url',
				'css'       => 'width:25em;',
				'default'   => WC()->api_request_url( 'novalnet_callback' ),
				'desc'      => __( 'The notification URL is used to keep your database/system actual and synchronizes with the Novalnet transaction status.', 'wc-novalnet' ),
				'desc_tip'  => true,
				'type'      => 'text',
				'custom_attributes' => array(
					'autocomplete' => 'OFF',
				),
			),
			array(
				'title'     => __( 'E-mail address (To)', 'wc-novalnet' ),
				'id'        => 'novalnet_callback_emailtoaddr',
				'default'   => get_bloginfo( 'admin_email' ),
				'css'       => 'width:25em;',
				'desc'      => __( 'E-mail address of the recipient', 'wc-novalnet' ),
				'desc_tip'  => true,
				'type'      => 'text',
				'custom_attributes' => array(
					'autocomplete' => 'OFF',
				),
			),
			array(
				'title'     => __( 'E-mail address (Bcc)', 'wc-novalnet' ),
				'id'        => 'novalnet_callback_emailbccaddr',
				'desc'      => __( 'E-mail address of the recipient for BCC', 'wc-novalnet' ),
				'desc_tip'  => true,
				'css'       => 'width:25em;',
				'type'      => 'text',
				'custom_attributes' => array(
					'autocomplete' => 'OFF',
				),
			),
			array(
				'type'      => 'sectionend',
				'id'        => 'novalnet_vendor_script',
			),
			)
		);
	}
}

// Initiate Novalnet_Configuration.
new Novalnet_Configuration();
