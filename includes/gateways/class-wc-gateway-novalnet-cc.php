<?php
/**
 * Novalnet Credit Card Payment.
 *
 * This gateway is used for real time processing of Credit card data of customers.
 *
 * Copyright (c) Novalnet
 *
 * This script is only free to the use for merchants of Novalnet. If
 * you have found this script useful a small recommendation as well as a
 * comment on merchant form would be greatly appreciated.
 *
 * @class   WC_Gateway_Novalnet_Cc
 * @extends NN_Payment_Gateways
 * @package Novalnet/Classes/Payment
 * @author  Novalnet
 * @located at  /includes/gateways
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * WC_Gateway_Novalnet_Cc Class.
 */
class WC_Gateway_Novalnet_Cc extends NN_Payment_Gateways {


	/**
	 * Id for the gateway.
	 *
	 * @var string
	 */
	public $id                = 'novalnet_cc';

	/**
	 * Global settings of Novalnet.
	 *
	 * @var array
	 */
	public $global_settings   = array();

	/**
	 * Settings of the gateway.
	 *
	 * @var array
	 */
	public $settings          = array();

	/**
	 * Paygate URL.
	 *
	 * @var string
	 */
	private $paygate_url      = 'https://payport.novalnet.de/paygate.jsp';

	/**
	 * Paygate URL.
	 *
	 * @var string
	 */
	private $secure_paygate_url      = 'https://payport.novalnet.de/pci_payport';

	/**
	 * Gateway shows fields on the checkout.
	 *
	 * @var bool
	 */
	public $has_fields        = true;

	/**
	 * Constructor for the gateway.
	 */
	public function __construct() {

		// Assign payment details.
		$this->assign_basic_payment_details();

		// Handle redirection payment response.
		add_action( 'woocommerce_api_response_novalnet_cc', array( $this, 'check_novalnet_payment_response' ) );

		add_action( 'woocommerce_update_options_payment_gateways_novalnet_cc', array( $this, 'save_iframe_configuration' ) );
	}

	/**
	 * Returns the gateway icon.
	 *
	 * @return string
	 */
	public function get_icon() {

		$icon_html = '';

		// Built payment default logo.
		if ( $this->global_settings ['payment_logo'] ) {
			$icon_html  = $this->built_logo( $this->global_settings ['payment_logo'], 'novalnet_cc_visa', $this->title );
			$icon_html .= $this->built_logo( $this->global_settings ['payment_logo'], 'novalnet_cc_master', $this->title );

			// Built amex logo.
			$icon_html .= $this->built_logo( $this->settings ['enable_amex_type'], 'novalnet_cc_amex', $this->title );

			// Built maestro logo.
			$icon_html .= $this->built_logo( $this->settings ['enable_maestro_type'], 'novalnet_cc_maestro', $this->title );

			// Built cartasi logo.
			$icon_html .= $this->built_logo( $this->settings ['enable_cartasi_type'], 'novalnet_cc_cartasi', $this->title );
		}
		return apply_filters( 'woocommerce_gateway_icon', $icon_html, $this->id );
	}

	/**
	 * Displays the payment form, payment description on checkout.
	 */
	public function payment_fields() {

		if ( $this->settings ['cc_secure_enabled'] ) {
			$this->description = wpautop( __( 'After the successful verification, you will be redirected to Novalnet secure order page to proceed with the payment', 'wc-novalnet' ) );
			$this->description .= wpautop( __( 'Please don’t close the browser after successful payment, until you have been redirected back to the Shop', 'wc-novalnet' ) );
		}
		// Display payment details.
		$this->display_payment_details(
			array(
			'description'           => $this->description,
			'test_mode'             => $this->settings ['test_mode'],
			'payment_instruction'   => $this->settings ['payment_instruction'],
			)
		);

		// Check for change payment method.
		$this->check_change_payment_method();

		// Display form fields.
		wc_novalnet_payment_template( $this->id, $this->settings );
	}

	/**
	 * Validate payment fields on the frontend.
	 */
	public function validate_fields() {

		// Unset other payment session.
		$this->unset_other_payment_session();

		$session_cc = WC()->session->novalnet_cc;

		// Assigning post values in session.
		novalnet_instance()->novalnet_functions()->set_post_value_session( $this->id,
			$session_cc, array(
			'novalnet_cc_pan_hash',
			'novalnet_cc_unique_id',
			'novalnet_cc_one_click_shop_process',
			'novalnet_cc_payment_form',
			'novalnet_cc_reference_tid',
			)
		);

		// Get assigned session values.
		$session_cc = WC()->session->novalnet_cc;

		if ( ! empty( $session_cc['novalnet_cc_one_click_shop_process'] ) && 'true' === $session_cc['novalnet_cc_one_click_shop_process'] && WC()->session->__isset( 'novalnet_cc_reference_tid' ) ) {
			$session_cc ['novalnet_cc_reference_tid'] = WC()->session->novalnet_cc_reference_tid;
		} else {
			$session_cc ['novalnet_cc_payment_form']  = true;
			WC()->session->__unset( 'novalnet_cc_reference_tid' );

			if ( ! novalnet_instance()->novalnet_functions()->validate_payment_input_field( $session_cc, array(
				'novalnet_cc_pan_hash',
				'novalnet_cc_unique_id',
			) ) ) {

				// Display message.
				$this->display_info( __( 'Your credit card details are invalid', 'wc-novalnet' ), 'error' );

				// Redirect to checkout page.
				return $this->novalnet_redirect();
			}
		}

		WC()->session->set( $this->id, $session_cc );
	}

	/**
	 * Process payment flow of the gateway.
	 *
	 * @param int $order_id the order id.
	 *
	 * @return array
	 */
	public function process_payment( $order_id ) {

		$wc_order = new WC_Order( $order_id );

		if ( $this->settings ['cc_secure_enabled'] ) {
			return array(
				'result'   => 'success',
				'redirect' => $wc_order->get_checkout_payment_url( true ),
			);
		}

		$payment_parameters = $this->form_payment_params( $wc_order );

		// Perform Novalnet server call.
		$server_response = $this->perform_payment_call( $payment_parameters, $this->paygate_url );
		// Checks transaction status.
		return $this->check_transaction_status( $server_response, $wc_order, $this->id );
	}


	/**
	 * Check if the gateway is available for use.
	 *
	 * @return boolean
	 */
	public function is_available() {
		if ( empty( $this->settings ['enabled'] ) || 'yes' !== $this->settings ['enabled'] || novalnet_instance()->novalnet_functions()->global_config_validation( $this->global_settings ) || novalnet_instance()->novalnet_functions()->restrict_payment_method( $this->settings ) ) {
			return false;
		}
		return true;
	}

	/**
	 * Forming gateway parameters.
	 *
	 * @param WC_Order $wc_order     the order object.
	 *
	 * @return array
	 */
	public function form_payment_params( $wc_order ) {
		global $current_user;

		// Generate basic parameters.
		$novalnet_settings = array_merge(
			$this->global_settings, array(
			'test_mode'  => $this->settings ['test_mode'],
			'reference1' => $this->settings ['reference1'],
			'reference2' => $this->settings ['reference2'],
			)
		);

		// Generate basic parameters.
		$data = $this->generate_payment_parameters( $wc_order, $novalnet_settings, $this->id );
		$params = $data ['payment_parameters'];

		// Assign card details.
		if ( ! empty( WC()->session->novalnet_cc ['novalnet_cc_reference_tid'] ) ) {
			$params ['payment_ref'] = WC()->session->novalnet_cc ['novalnet_cc_reference_tid'];
		} else {

			// Assign generated pan hash and unique_id.
			$params ['pan_hash']  = WC()->session->novalnet_cc ['novalnet_cc_pan_hash'];
			$params ['unique_id'] = WC()->session->novalnet_cc ['novalnet_cc_unique_id'];

			if ( empty( WC()->session->novalnet_change_payment_method ) ) {

				// Check for reference transaction.
				if ( empty( WC()->session->novalnet_change_payment_method ) && ! $this->settings ['cc_secure_enabled'] && 'one_click_shop' === $this->settings ['payment_process'] && ! empty( $current_user->ID ) ) {
					$params ['create_payment_ref'] = '1';
				}

				// Assign zero amount booking.
				if ( empty( $params['tariff_period'] ) && 'zero_amount_book' === $this->settings['payment_process'] ) {
					$params ['create_payment_ref'] = '1';
					$this->assign_zero_amount( $params );
				}
			}

			// Assign payment integration type.
			$params ['nn_it']     = 'iframe';

			// Assign CC3D parameter if cc3d enabled.
			if ( $this->settings ['cc_secure_enabled'] ) {
				$params ['cc_3d'] = '1';

				// Encoding parameters.
				$this->redirect_payment_params( $params, $wc_order, $data ['payment_access_key'] );
				$params ['implementation'] = 'PHP_PCI';
				return array(
				 'params'      => $params,
				 'paygate_url' => $this->secure_paygate_url,
				);
			}
		}

		// Log to notify payment parameters formed successfully.
		if ( $this->global_settings ['debug_log'] ) {
			$this->novalnet_log->add( 'novalnetpayments', 'Payment parameters formed successfully for the payment ' . $this->id );
		}
		return $params;
	}

	/**
	 * Returns the order status.
	 *
	 * @param string $order_status the order status.
	 *
	 * @return string
	 */
	public function get_order_status( $order_status ) {

		if ( WC()->session->__isset( 'current_novalnet_payment' ) && WC()->session->current_novalnet_payment === $this->id ) {
			$order_status = $this->settings ['order_success_status'];
		}
		return $order_status;
	}

	/**
	 * Manage redirect process.
	 */
	public function check_novalnet_payment_response() {

		$server_response = $_REQUEST; // input var okay.

		// Checks redirect response.
		if ( ! empty( $server_response['wc-api'] ) && 'response_' . $this->id === $server_response['wc-api'] ) {

			// Process redirect response.
			$status = $this->process_redirect_payment_response( $server_response );

			// Redirect to checkout / success page.
			wc_novalnet_safe_redirect( $status ['redirect'] );
		}
	}

	/**
	 * Payment configurations in shop backend
	 */
	public function init_form_fields() {

		// Basic payment configurations.
		$this->form_fields = $this->basic_payment_config();

		// CC 3D secure field.
		$this->form_fields ['cc_secure_enabled'] = array(
		 'title'       => __( 'Enable 3D secure', 'wc-novalnet' ),
		 'type'        => 'select',
		 'options'     => array(
		  '0' => __( 'No', 'wc-novalnet' ),
		  '1' => __( 'Yes', 'wc-novalnet' ),
		 ),
		 'description' => __( 'The 3D-Secure will be activated for credit cards. The issuing bank prompts the buyer for a password what, in turn, help to prevent a fraudulent payment. It can be used by the issuing bank as evidence that the buyer is indeed their card holder. This is intended to help decrease a risk of charge-back.', 'wc-novalnet' ),
		 'desc_tip'    => true,
		);

		// Payment Logo display field.
		$this->form_fields ['enable_amex_type'] = array(
		 'title'       => __( 'Display AMEX logo', 'wc-novalnet' ),
		 'type'        => 'select',
		 'options'     => array(
		  '0' => __( 'No', 'wc-novalnet' ),
		  '1' => __( 'Yes', 'wc-novalnet' ),
		 ),
		 'description' => __( 'Display AMEX logo in checkout page', 'wc-novalnet' ),
		 'desc_tip'    => true,
		);
		$this->form_fields ['enable_maestro_type'] = array(
		 'title'       => __( 'Display Maestro logo', 'wc-novalnet' ),
		 'type'        => 'select',
		 'options'     => array(
		  '0' => __( 'No', 'wc-novalnet' ),
		  '1' => __( 'Yes', 'wc-novalnet' ),
		 ),
		 'description' => __( 'Display Maestro logo in checkout page', 'wc-novalnet' ),
		 'desc_tip'    => true,
		);
		$this->form_fields ['enable_cartasi_type'] = array(
		 'title'       => __( 'Display CartaSi logo', 'wc-novalnet' ),
		 'type'        => 'select',
		 'options'     => array(
		  '0' => __( 'No', 'wc-novalnet' ),
		  '1' => __( 'Yes', 'wc-novalnet' ),
		 ),
		 'description' => __( 'Display CartaSi logo in checkout page', 'wc-novalnet' ),
		 'desc_tip'    => true,
		);

		// Shopping type configurations.
		$this->shopping_type_payment_config( $this->form_fields );

		// Other configurations.
		$this->other_payment_config( $this->form_fields );

		$this->form_fields ['standard_style_configuration_heading'] = array(
			'title' => __( 'Custom CSS settings', 'wc-novalnet' ),
			'type'  => 'title',
			'description' => sprintf( '<strong>%s</strong>', __( 'CSS settings for Credit Card iframe', 'wc-novalnet' ) ),
		);

		$this->form_fields ['standard_label'] = array(
			'title'       => __( 'Label', 'wc-novalnet' ),
			'type'        => 'textarea',
		);

		$this->form_fields ['standard_input'] = array(
			'title'       => __( 'Input', 'wc-novalnet' ),
			'type'        => 'textarea',
		);

		$this->form_fields ['standard_css'] = array(
			'title'       => __( 'CSS Text', 'wc-novalnet' ),
			'type'        => 'textarea',
			'default'        => '.input-group{box-sizing: border-box;width: 100%;margin: 0;outline: 0;line-height: 1;padding:0.7em 0;}.label-group{font-size:.92em;}html{font-family:"Source Sans Pro", Helvetica, sans-serif}.form-group{position: relative;box-sizing: border-box;width: 100%;margin: 1em 0;font-size: .92em;border-radius: 2px;line-height: 1.5;background-color: #dfdcde;color: #515151;}',
		);

		// Iframe style configurations.
		$this->form_fields ['iframe_configuration_title'] = array(
			'title' => '',
			'description' => sprintf( '<strong>%s</strong>', __( 'CSS settings for Credit Card fields', 'wc-novalnet' ) ),
			'type'  => 'title',
		);

		$this->form_fields ['iframe_configuration'] = array(
			'type' => 'iframe_configuration',
		);
	}

	/**
	 * Iframe configuration html.
	 *
	 * @return string
	 */
	public function generate_iframe_configuration_html() {

		ob_start();
		$values = get_option( 'woocommerce_novalnet_cc_iframe_configuration' );
		?>
		<tr valign="top">
			<td class="forminp" id="cc_iframe_configuartion_table">
				<table class="wwidefat" cellspacing="0">
					<thead>
						<tr>
							<?php
							foreach ( array(
									__( 'Form fields', 'wc-novalnet' ),
									__( 'Label', 'wc-novalnet' ),
									__( 'Input field', 'wc-novalnet' ),
								) as $column ) {
								echo '<th>' . esc_html( $column ) . '</th>';
							}
							?>
						</tr>
					</thead>
					<tbody>
					<tr>
						<td>
							<?php echo wp_kses( __( 'Card holder name', 'wc-novalnet' ), array() ); ?>
						</td>
						<td>
							<input type="text" name="holder_label_css" autocomplete="OFF" value="<?php echo esc_attr( $values ['holder_label_css'] )?>" />
						</td>
						<td>
							<input type="text" name="holder_input_field_css" autocomplete="OFF" value="<?php echo esc_attr( $values ['holder_input_field_css'] )?>" />
						</td>
					</tr>
					<tr>
						<td>
							<?php echo wp_kses( __( 'Card number', 'wc-novalnet' ), array() ); ?>
						</td>
						<td>
							<input type="text" name="number_label_css" autocomplete="OFF" value="<?php echo esc_attr( $values ['number_label_css'] )?>" />
						</td>
						<td>
							<input type="text" name="number_input_field_css" autocomplete="OFF" value="<?php echo esc_attr( $values ['number_input_field_css'] )?>" />
						</td>
					</tr>
					<tr>
						<td>
							<?php echo wp_kses( __( 'Expiry date', 'wc-novalnet' ), array() ); ?>
						</td>
						<td>
							<input type="text" name="expiry_date_label_css" autocomplete="OFF" value="<?php echo esc_attr( $values ['expiry_date_label_css'] )?>" />
						</td>
						<td>
							<input type="text" name="expiry_date_input_field_css" autocomplete="OFF" value="<?php echo esc_attr( $values ['expiry_date_input_field_css'] )?>" />
						</td>
					</tr>
					<tr>
						<td>
							<?php echo wp_kses( __( 'CVC/CVV/CID', 'wc-novalnet' ), array() ); ?>
						</td>
						<td>
							<input type="text" name="cvc_label_css" autocomplete="OFF" value="<?php echo esc_attr( $values ['cvc_label_css'] )?>" />
						</td>
						<td>
							<input type="text" name="cvc_input_field_css" autocomplete="OFF" value="<?php echo esc_attr( $values ['cvc_input_field_css'] )?>" />
						</td>
					</tr>
					</tbody>
				</table>
			</td>
		</tr>
		<?php
		return ob_get_clean();

	}

	/**
	 * Save account details table.
	 */
	public function save_iframe_configuration() {

		$request = $_REQUEST; // input var okay.
		$values = array();
		foreach ( array(
			'holder_label_css',
			'holder_input_field_css',
			'number_label_css',
			'number_input_field_css',
			'expiry_date_label_css',
			'expiry_date_input_field_css',
			'cvc_label_css',
			'cvc_input_field_css',
		) as $field ) {

			if ( isset( $request [ $field ] ) ) {
				$values [ $field ] = wp_unslash( $request [ $field ] );
			}
		}
		update_option( 'woocommerce_novalnet_cc_iframe_configuration', array_map( 'wc_clean', $values ) );
	}
}
