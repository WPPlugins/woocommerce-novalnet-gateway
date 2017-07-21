<?php
/**
 * Plugin Name: Novalnet payment plugin - WooCommerce
 * Plugin URI:  http://www.novalnet.com/modul/woocommerce
 * Description: Plug-in to process payments in WooCommerce through Novalnet Gateway
 * Author:      Novalnet
 * Author URI:  https://www.novalnet.de
 * Version:     11.2.0
 * Text Domain: wc-novalnet
 * Domain Path: /i18n/languages/
 * License:     GPLv2
 *
 * @package Novalnet payment plugin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'Novalnet' ) ) :

	ob_start();

	/**
	 * Main Novalnet Class.
	 *
	 * @class   Novalnet
	 * @version 11.2.0
	 */
	final class Novalnet {


		/**
		 * The single instance of the class.
		 *
		 * @var Novalnet The single instance of the class.
		 * @since 11.0.0
		 */
		protected static $_instance = null;

		/**
		 * Main Novalnet Instance.
		 *
		 * Ensures only one instance of Novalnet is loaded.
		 *
		 * @since  11.0.0
		 * @static
		 * @see    novalnet_instance()
		 * @return Novalnet - Main instance.
		 */
		public static function instance() {

			if ( is_null( self::$_instance ) ) {
				self::$_instance = new self();
			}
			return self::$_instance;
		}

		/**
		 * Novalnet Constructor.
		 */
		public function __construct() {

			// Including required files.
			include_once 'includes/wc-novalnet-functions.php';

			if ( ! class_exists( 'WC_Payment_Gateway' ) ) {
				add_action( 'admin_notices', 'wc_novalnet_checks_woocommerce_active' );
				return;
			}

			// Define constants.
			wc_novalnet_define( 'NN_VERSION', '11.2.0' );
			wc_novalnet_define( 'NN_PLUGIN_FILE', __FILE__ );

			include_once 'includes/class-wc-novalnet-install.php';
			include_once 'includes/abstracts/abstract-wc-novalnet-payment.php';
			include_once 'includes/class-wc-novalnet-functions.php';
			include_once 'includes/admin/class-wc-novalnet-admin.php';
			include_once 'includes/admin/class-wc-novalnet-admin-meta-boxes.php';

			// Comparing subscription version & including files.
			if ( wc_novalnet_is_subscription_2x() ) {
				include_once 'includes/subscription/2x/class-wc-novalnet-subscriptions.php';
			} else {
				include_once 'includes/subscription/1x/class-wc-novalnet-subscriptions.php';
			}

			// Including gateway files.
			foreach ( glob( dirname( __FILE__ ) . '/includes/gateways/*.php' ) as $filename ) {
				include_once $filename;
			}

			if ( wc_novalnet_compare_version( '2.4.0', WOOCOMMERCE_VERSION, '<' ) ) {
				add_action( 'wp_loaded', 'wc_novalnet_handle_api_request' );
			}

			// Initiate the text domain.
			load_plugin_textdomain( 'wc-novalnet', false, dirname( plugin_basename( NN_PLUGIN_FILE ) ) . '/i18n/languages/' );

			// Activate plugin.
			register_activation_hook( NN_PLUGIN_FILE, array( 'NN_Install', 'install' ) );

			// Deactivate plugin.
			register_deactivation_hook( NN_PLUGIN_FILE, array( 'NN_Install', 'uninstall' ) );

			// Plugin script actions.
			add_action( 'admin_enqueue_scripts', 'wc_novalnet_admin_enqueue_script' );
			add_action( 'wp_enqueue_scripts', 'wc_novalnet_enqueue_script' );

			// Plugin update action.
			add_action( 'admin_init', array( 'NN_Install', 'update' ) );

			// Plugin gateway actions.
			add_filter( 'woocommerce_payment_gateways', array( &$this, 'add_novalnet_payments' ) );
			add_filter( 'woocommerce_settings_tabs_array', array( &$this, 'add_novalnet_settings_tab' ), 50 );
			add_action( 'woocommerce_order_item_meta_end', array( &$this, 'align_transaction_info' ), 10, 3 );
			add_action( 'woocommerce_api_novalnet_affiliate', 'wc_novalnet_handle_affiliate_process' );

			// Restrict instant mail from Germanized plugin.
			add_filter( 'woocommerce_gzd_instant_order_confirmation', 'wc_novalnet_restrict_instant_email' );
		}

		/**
		 * Get the plugin url.
		 *
		 * @return string
		 */
		public function plugin_url() {

			return untrailingslashit( plugins_url( '/', NN_PLUGIN_FILE ) );
		}

		/**
		 * Adds Novalnet gateway to WooCommerce.
		 *
		 * @since 11.0.0
		 *
		 * @param  array $methods The gateway methods.
		 * @return array
		 */
		public function add_novalnet_payments( $methods ) {

			// Available gateways.
			$methods [] = 'WC_Gateway_Novalnet_Cc';
			$methods [] = 'WC_Gateway_Novalnet_Sepa';
			$methods [] = 'WC_Gateway_Novalnet_Invoice';
			$methods [] = 'WC_Gateway_Novalnet_Prepayment';
			$methods [] = 'WC_Gateway_Novalnet_Paypal';
			$methods [] = 'WC_Gateway_Novalnet_Instantbank';
			$methods [] = 'WC_Gateway_Novalnet_Ideal';
			$methods [] = 'WC_Gateway_Novalnet_Eps';
			$methods [] = 'WC_Gateway_Novalnet_Giropay';
			$methods [] = 'WC_Gateway_Novalnet_Przelewy24';
			return $methods;
		}

		/**
		 * Adds Novalnet global Configuration.
		 *
		 * @since 11.0.0
		 *
		 * @param  array $woocommerce_tab The woocommerce admin settings tab.
		 * @return array
		 */
		public function add_novalnet_settings_tab( $woocommerce_tab ) {

			$woocommerce_tab ['novalnet_settings'] = __( 'Novalnet Global Configuration', 'wc-novalnet' );
			return $woocommerce_tab;
		}

		/**
		 * Align transaction info in "myaccount" page.
		 *
		 * @since 11.0.0
		 * @param int      $item_id The item id.
		 * @param array    $item    The item data.
		 * @param WC_Order $order   The order object.
		 */
		public function align_transaction_info( $item_id, $item, $order ) {

			$get_novalnet_payment_method = novalnet_instance()->novalnet_functions()->get_novalnet_payment_method( $order );
			$novalnet_customer_note = novalnet_instance()->novalnet_functions()->get_novalnet_customer_note( $order );
			// Align transaction details.
			if ( wc_novalnet_check_string( $get_novalnet_payment_method ) && $novalnet_customer_note ) {
				if ( wc_novalnet_compare_version( '3.0.0', WOOCOMMERCE_VERSION, '<' ) ) {
					$order->customer_note = wpautop( $novalnet_customer_note );
				} else {
					$order->set_customer_note( wpautop( $novalnet_customer_note ) );
				}
			}
		}

		/**
		 * Get function class.
		 *
		 * @since 11.0.0
		 *
		 * @return NN_Functions
		 */
		public function novalnet_functions() {

			// Initiate Novalnet Functions.
			return NN_Functions::instance();
		}

		/**
		 * Get Callback API class.
		 *
		 * @since 11.0.0
		 *
		 * @return NN_Callback_Api
		 */
		public function novalnet_callback_api() {

			// Initiate Novalnet Callback API.
			return NN_Callback_Api::instance();
		}

	}
endif;

/**
 * Returns the main instance of NN.
 *
 * @since 11.0.0
 *
 * @return Novalnet
 */
function novalnet_instance() {

	// Initiate Novalnet.
	return Novalnet::instance();
}

/**
 * Initiate the NN function.
 */
add_action( 'plugins_loaded', 'novalnet_instance' );
