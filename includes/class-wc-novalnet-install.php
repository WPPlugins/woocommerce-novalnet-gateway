<?php
/**
 * Novalnet Plugin installation process.
 *
 * This file is used for creating tables while installing the plugins.
 *
 * @version  11.2.0
 * @package  Novalnet-gateway/Classes
 * @category Class
 * @author   Novalnet
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * NN_Install Class.
 */
class NN_Install {


	/**
	 * The novalnet module version.
	 *
	 * @var Novalnet version
	 */
	private static $update_db_version = '11.2.0';

	/**
	 * The novalnet module previous versions.
	 *
	 * @var Previously released Novalnet version array
	 */
	private static $db_updates = array(
	'1.1.7'  => 'updates/novalnet-update-11.0.0.php',
	'2.0.0'  => 'updates/novalnet-update-11.0.0.php',
	'2.0.1'  => 'updates/novalnet-update-11.0.0.php',
	'2.0.2'  => 'updates/novalnet-update-11.0.0.php',
	'10.0.0' => 'updates/novalnet-update-11.0.0.php',
	'10.1.0' => 'updates/novalnet-update-11.0.0.php',
	'10.1.1' => 'updates/novalnet-update-11.0.0.php',
	'10.2.0' => 'updates/novalnet-update-11.0.0.php',
	'10.2.1' => 'updates/novalnet-update-11.0.0.php',
	'10.3.0' => 'updates/novalnet-update-11.0.0.php',
	'10.3.1' => 'updates/novalnet-update-11.0.0.php',
	'11.0.0' => '',
	'11.1.0' => '',
	'11.1.1' => '',
	);

	/**
	 * Install actions such as creating/ updating the tables while activate link is clicked.
	 *
	 * @since 11.0.0
	 */
	public static function install() {

		// Initialize the DB update.
		self::update();
	}

	/**
	 * Update actions such as updating the tables
	 * when reloading the page after update.
	 *
	 * @since 11.0.0
	 */
	public static function update() {
		$current_db_version = get_option( 'novalnet_db_version' );
		if ( version_compare( $current_db_version, self::$update_db_version, '!=' ) ) {

			// Initialize the DB update.
			foreach ( self::$db_updates as $version => $updater ) {

				// Updating existing Novalnet table.
				if ( '' !== $updater && ( '' === $current_db_version || version_compare( $current_db_version, $version, '!=' ) ) ) {

					include_once $updater;

					// Remove the previous Novalnet values.
					if ( version_compare( $current_db_version, '11.1.0', '<' ) ) {
						self::uninstall();
					}
				}
			}

			// Creating table.
			include_once 'updates/create-table.php';

			update_option( 'novalnet_db_version', NN_VERSION );

			// Redirect to updated information page.
			wc_novalnet_safe_redirect( admin_url( 'admin.php?page=wc-novalnet-setup' ) );
		}
	}

	/**
	 * Deleting actions when plugin deactivated.
	 *
	 * @since 10.0.0
	 */
	public static function uninstall() {

		global $wpdb;

		// Delete the existing Novalnet values from table.
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", '%novalnet%' ) ); // db call ok; no-cache ok.
	}
}
