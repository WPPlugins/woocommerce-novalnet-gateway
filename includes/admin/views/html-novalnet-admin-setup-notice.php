<?php
/**
 * Novalnet updates template
 *
 * @author   Novalnet
 * @category Admin
 * @package  Novalnet-gateway/Admin/views
 * @version  11.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$plugin_url = novalnet_instance()->plugin_url();
$configuration_url = admin_url( 'admin.php?page=wc-settings&tab=novalnet_settings' );
$admin_url = admin_url( 'admin.php?page=wc-novalnet-admin' );
$language = wc_novalnet_shop_language();
if ( 'de' === $language ) {
	$projects_tab = $plugin_url . '/assets/images/setup/de/projects_tab.png';
	$product_activation_key = $plugin_url . '/assets/images/setup/de/product_activation_key.png';
	$vendor_script_configuration = $plugin_url . '/assets/images/setup/de/vendor_script_configuration.png';
	$system_ip_configuartion = $plugin_url . '/assets/images/setup/de/system_ip_configuartion.png';
	$paypal_config_home = $plugin_url . '/assets/images/setup/de/paypal_config_home.png';
	$paypal_config = $plugin_url . '/assets/images/setup/de/paypal_config.png';
} else {
	$projects_tab = $plugin_url . '/assets/images/setup/projects_tab.png';
	$product_activation_key = $plugin_url . '/assets/images/setup/product_activation_key.png';
	$vendor_script_configuration = $plugin_url . '/assets/images/setup/vendor_script_configuration.png';
	$system_ip_configuartion = $plugin_url . '/assets/images/setup/system_ip_configuartion.png';
	$paypal_config_home = $plugin_url . '/assets/images/setup/paypal_config_home.png';
	$paypal_config = $plugin_url . '/assets/images/setup/paypal_config.png';
}
$home_page = __( 'http://www.novalnet.com', 'wc-novalnet' );
?>
<div class="wrap about-wrap">
	<a href="<?php echo esc_attr( $home_page ); ?>" target="_blank"><img style="border:0" id="novalnet-logo" alt="Novalnet" src="<?php echo esc_attr( $plugin_url ); ?>/assets/images/novalnet.png" /></a>
	<h1><?php echo esc_html( __( 'Novalnet Payment Plugin V11.2.0', 'wc-novalnet' ) ); ?></h1>
	<div class="about-text">
		</p><?php echo esc_html( __( 'Thank you for updating to the latest version of Novalnet Payment Plugin. This version introduces some great new features and enhancements.', 'wc-novalnet' ) ); ?></p>
		<?php echo esc_html( __( 'We hope you enjoy it!', 'wc-novalnet' ) ); ?>
	</div>

	<div class="return-to-dashboard">
		<a href="<?php echo esc_attr( $configuration_url ); ?>"><?php echo esc_html( __( 'Go to Novalnet Global Configuration &raquo;', 'wc-novalnet' ) ); ?></a>
	</div>

	<div class="changelog">
		<h2><?php echo esc_html( __( "Check Out What's New", 'wc-novalnet' ) ); ?></h2>
		<hr/>
		<div class="feature-section two-col">
			<div class="col feature-copy">
				<img src="<?php echo esc_attr( $projects_tab ); ?>" />
			</div>

			<div class="col feature-copy">
				<h3><?php echo esc_html( __( 'Product Activation Key', 'wc-novalnet' ) ); ?></h3>
				<p><?php echo esc_html( __( 'Novalnet introduces Product Activation Key to fill entire merchant credentials automatically on entering the key into the Novalnet Global Configuration.', 'wc-novalnet' ) ); ?></p>
			</div>
			<div class="feature-image col">
				<img src="<?php echo esc_attr( $product_activation_key ); ?>" />
			</div>
			<div class="col feature-copy">
				<p><?php echo wp_kses( sprintf( __( 'To get the Product Activation Key, please go to <a href="%s" target="_blank">Novalnet admin portal</a> - <strong>PROJECTS</strong>: Project Information - <strong>Shop Parameters</strong>: <strong>API Signature (Product activation key)</strong>.', 'wc-novalnet' ), $admin_url ), array(
					'strong' => true,
					'a' => array(
						'href'   => true,
						'target' => true,
					),
				) ); ?></p>
			</div>
		</div>
		<hr/>
		<div class="feature-section two-col">

			<div class="col feature-copy">
				<h3><?php echo esc_html( __( 'IP Address Configuration', 'wc-novalnet' ) ); ?></h3>
				<p><?php echo esc_attr( __( 'For all API access (Auto configuration with Product Activation Key, loading Credit Card iframe, Transaction API access, Transaction status enquiry, and update), it is required to configure a server IP address in Novalnet administration portal.', 'wc-novalnet' ) ); ?></p>
			</div>
			<div class="feature-image col">
				<img src="<?php echo esc_attr( $projects_tab ); ?>" />
			</div>
			<div class="col feature-copy">
				<p><?php echo wp_kses( sprintf( __( "To configure an IP address, please go to <a href='%s' target='_blank'>Novalnet admin portal</a> - <strong>PROJECTS</strong>: Project Information - <strong>Project Overview</strong>: Payment Request IP's - <strong>Update Payment Request IP</strong>.", 'wc-novalnet' ), $admin_url ), array(
					'strong' => true,
					'a' => array(
						'href'   => true,
						'target' => true,
					),
				) ); ?></p>
			</div>
			<div class="feature-image col">
				<img src="<?php echo esc_attr( $system_ip_configuartion ); ?>" />
			</div>
		</div>
		<hr/>
		<div class="feature-section two-col">
			<div class="col feature-copy">
				<img src="<?php echo esc_attr( $projects_tab ); ?>" />
			</div>

			<div class="col feature-copy">
				<h3><?php echo esc_html( __( 'Update of Vendor Script URL', 'wc-novalnet' ) ); ?></h3>
				<p><?php echo esc_html( __( 'Vendor script URL is required to keep the merchant’s database/system up-to-date and synchronized with Novalnet transaction status. It is mandatory to configure the Vendor Script URL in Novalnet administration portal.', 'wc-novalnet' ) ); ?></p><p><?php echo esc_html( __( 'Novalnet system (via asynchronous) will transmit the information on each transaction and its status to the merchant’s system.', 'wc-novalnet' ) ); ?></p>

			</div>
			<div class="feature-image col">
				<img src="<?php echo esc_attr( $vendor_script_configuration ); ?>" />
			</div>
			<div class="col feature-copy">
				<p><?php echo wp_kses( sprintf( __( "To configure Vendor Script URL, please go to <a href='%s' target='_blank'>Novalnet admin portal</a> - <strong>PROJECTS</strong>: Project Information - <strong>Project Overview</strong> - <strong>Vendor script URL</strong>.", 'wc-novalnet' ), $admin_url ), array(
					'strong' => true,
					'a' => array(
						'href'   => true,
						'target' => true,
					),
				) ); ?></p>
			</div>
		</div>
	</div>
	<hr/>
		<div class="feature-section two-col">

			<div class="col feature-copy">
				<h4><?php echo esc_html( __( 'PAYPAL', 'wc-novalnet' ) ); ?></h4>
				<p><?php echo esc_attr( __( 'To proceed transaction in PayPal payment, it is required to configure PayPal API details in Novalnet administration portal.', 'wc-novalnet' ) ); ?></p>
			</div>
			<div class="feature-image col">
				<img src="<?php echo esc_attr( $paypal_config_home ); ?>" />
			</div>
			<div class="col feature-copy">
				<p><?php echo wp_kses( sprintf( __( "To configure Paypal API details, please go to <a href='%s' target='_blank'>Novalnet admin portal</a> - <strong>PROJECTS</strong>: Project Information - <strong>Payment Methods</strong>: Paypal - <strong>Configure</strong>.", 'wc-novalnet' ), $admin_url ), array(
					'strong' => true,
					'a' => array(
						'href'   => true,
						'target' => true,
					),
				) ); ?></p>
			</div>
			<div class="feature-image col">
				<img src="<?php echo esc_attr( $paypal_config ); ?>" />
			</div>
		</div>
	<div class="changelog still-more">
		<h2><?php echo esc_html( __( "But wait, there's more!", 'wc-novalnet' ) ); ?></h2>
		<hr/>
		<div class="feature-section three-col">
			<div class="col">
				<h3><?php echo esc_html( __( 'One Click Shopping', 'wc-novalnet' ) ); ?></h3>
				<p><?php echo esc_html( __( 'Want your customers to make an order with a single click?', 'wc-novalnet' ) ); ?></p>
				<p><?php echo esc_html( __( 'With Novalnet payment plugin, they can! This feature can make the end customer to make order more conveniently with saved account/card details.', 'wc-novalnet' ) ); ?></p>
			</div>

			<div class="col">
				<h3><?php echo esc_html( __( 'Zero Amount Booking', 'wc-novalnet' ) ); ?></h3>
				<p><?php echo esc_html( __( 'Zero amount booking feature makes it possible for the merchant to sell variable amount product in the shop. Order will be processed with Zero amount initially, then the merchant can book the order amount later to complete the transaction.', 'wc-novalnet' ) ); ?></p>
			</div>
			<div class="col">
				<h3><?php echo esc_html( __( 'Credit Card Responsive Iframe', 'wc-novalnet' ) ); ?></h3>
				<p><?php echo esc_html( __( 'Now, we have updated the Credit Card with the most dynamic features. With the little bit of code, we have made the Credit Card iframe content responsive friendly.', 'wc-novalnet' ) ); ?></p>
				<p><?php echo esc_html( __( 'The merchant can customize the CSS settings of the Credit Card iframe form.', 'wc-novalnet' ) ); ?></p>
			</div>
		</div>
		<hr/>
		<div class="return-to-dashboard">
			<a href="<?php echo esc_attr( $configuration_url ); ?>"><?php echo esc_html( __( 'Go to Novalnet Global Configuration &raquo;', 'wc-novalnet' ) ); ?></a>
		</div>
	</div>
</div>
