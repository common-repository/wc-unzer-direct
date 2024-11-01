<?php

/**
 * WC_UnzerDirect_Settings class
 *
 * @class        WC_UnzerDirect_Settings
 * @version        1.0.0
 * @category    Class
 * @author        PerfectSolution
 */
class WC_UnzerDirect_Settings {

	/**
	 * get_fields function.
	 *
	 * Returns an array of available admin settings fields
	 *
	 * @access public static
	 * @return array
	 */
	public static function get_fields() {
		$fields =
			[
				'enabled' => [
					'title'   => __( 'Enable', 'wc-unzer-direct' ),
					'type'    => 'checkbox',
					'label'   => __( 'Enable Unzer Direct Payment', 'wc-unzer-direct' ),
					'default' => 'yes'
				],

				'_Account_setup'                   => [
					'type'  => 'title',
					'title' => __( 'API - Integration', 'wc-unzer-direct' ),
				],
				'unzer_direct_apikey'              => [
					'title'       => __( 'Api User key', 'wc-unzer-direct' ) . self::get_required_symbol(),
					'type'        => 'text',
					'description' => __( 'Your API User\'s key. Create a separate API user in the "Users" tab inside the Unzer Direct manager.', 'wc-unzer-direct' ),
					'desc_tip'    => true,
				],
				'unzer_direct_privatekey'          => [
					'title'       => __( 'Private key', 'wc-unzer-direct' ) . self::get_required_symbol(),
					'type'        => 'text',
					'description' => __( 'Your agreement private key. Found in the "Integration" tab inside the Unzer Direct manager.', 'wc-unzer-direct' ),
					'desc_tip'    => true,
				],
				'_Autocapture'                     => [
					'type'  => 'title',
					'title' => __( 'Autocapture settings', 'wc-unzer-direct' )
				],
				'unzer_direct_autocapture'         => [
					'title'       => __( 'Physical products (default)', 'wc-unzer-direct' ),
					'type'        => 'checkbox',
					'label'       => __( 'Enable', 'wc-unzer-direct' ),
					'description' => __( 'Automatically capture payments on physical products.', 'wc-unzer-direct' ),
					'default'     => 'no',
					'desc_tip'    => false,
				],
				'unzer_direct_autocapture_virtual' => [
					'title'       => __( 'Virtual products', 'wc-unzer-direct' ),
					'type'        => 'checkbox',
					'label'       => __( 'Enable', 'wc-unzer-direct' ),
					'description' => __( 'Automatically capture payments on virtual products. If the order contains both physical and virtual products, this setting will be overwritten by the default setting above.', 'wc-unzer-direct' ),
					'default'     => 'no',
					'desc_tip'    => false,
				],
				'_caching'                         => [
					'type'  => 'title',
					'title' => __( 'Transaction Cache', 'wc-unzer-direct' )
				],
				'unzer_direct_caching_enabled'     => [
					'title'       => __( 'Enable Caching', 'wc-unzer-direct' ),
					'type'        => 'checkbox',
					'description' => __( 'Caches transaction data to improve application and web-server performance. <strong>Recommended.</strong>', 'wc-unzer-direct' ),
					'default'     => 'yes',
					'desc_tip'    => false,
				],
				'unzer_direct_caching_expiration'  => [
					'title'       => __( 'Cache Expiration', 'wc-unzer-direct' ),
					'label'       => __( 'Cache Expiration', 'wc-unzer-direct' ),
					'type'        => 'number',
					'description' => __( '<strong>Time in seconds</strong> for how long a transaction should be cached. <strong>Default: 604800 (7 days).</strong>', 'wc-unzer-direct' ),
					'default'     => 7 * DAY_IN_SECONDS,
					'desc_tip'    => false,
				],

				'_Extra_gateway_settings'   => [
					'type'  => 'title',
					'title' => __( 'Extra gateway settings', 'wc-unzer-direct' )
				],
				'unzer_direct_cardtypelock' => [
					'title'       => __( 'Payment methods', 'wc-unzer-direct' ),
					'type'        => 'text',
					'description' => __( 'Default: creditcard. Type in the cards you wish to accept (comma separated). See the valid payment types here: <b>https://www.unzerdirect.com/documentation//appendixes/payment-methods/</b>', 'wc-unzer-direct' ),
					'default'     => 'creditcard',
				],
				'unzer_direct_branding_id'  => [
					'title'       => __( 'Branding ID', 'wc-unzer-direct' ),
					'type'        => 'text',
					'description' => __( 'Leave empty if you have no custom branding options', 'wc-unzer-direct' ),
					'default'     => '',
					'desc_tip'    => true,
				],

				'unzer_direct_autofee'               => [
					'title'       => __( 'Enable autofee', 'wc-unzer-direct' ),
					'type'        => 'checkbox',
					'label'       => __( 'Enable', 'wc-unzer-direct' ),
					'description' => __( 'If enabled, the fee charged by the acquirer will be calculated and added to the transaction amount.', 'wc-unzer-direct' ),
					'default'     => 'no',
					'desc_tip'    => true,
				],
				'unzer_direct_captureoncomplete'     => [
					'title'       => __( 'Capture on complete', 'wc-unzer-direct' ),
					'type'        => 'checkbox',
					'label'       => __( 'Enable', 'wc-unzer-direct' ),
					'description' => __( 'When enabled Unzer Direct payments will automatically be captured when order state is set to "Complete".', 'wc-unzer-direct' ),
					'default'     => 'no',
					'desc_tip'    => true,
				],
				'unzer_direct_complete_on_capture'   => [
					'title'       => __( 'Complete order on capture callbacks', 'wc-unzer-direct' ),
					'type'        => 'checkbox',
					'label'       => __( 'Enable', 'wc-unzer-direct' ),
					'description' => __( 'When enabled, an order will be automatically completed when capture callbacks are sent to WooCommerce. Callbacks are sent by Unzer Direct when the payment is captured from either the shop or the Unzer Direct manager. Keep disabled to manually complete orders. ', 'wc-unzer-direct' ),
					'default'     => 'no',
				],
				'unzer_cancel_transaction_on_cancel' => [
					'title'       => __( 'Cancel payments on order cancellation', 'wc-unzer-direct' ),
					'type'        => 'checkbox',
					'label'       => __( 'Enable', 'wc-unzer-direct' ),
					'description' => __( 'Automatically cancel payments via the API when an order\'s status changes to cancelled.', 'wc-unzer-direct' ),
					'default'     => 'no',
				],
				'unzer_direct_text_on_statement'     => [
					'title'             => __( 'Text on statement', 'wc-unzer-direct' ),
					'type'              => 'text',
					'description'       => __( 'Text that will be placed on cardholder’s bank statement (MAX 22 ASCII characters. Must match the values defined in your agreement with Clearhaus. Custom values are not allowed).', 'wc-unzer-direct' ),
					'default'           => '',
					'desc_tip'          => false,
					'custom_attributes' => [
						'maxlength' => 22,
					],
				],


				'_Shop_setup'                               => [
					'type'  => 'title',
					'title' => __( 'Shop setup', 'wc-unzer-direct' ),
				],
				'title'                                     => [
					'title'       => __( 'Title', 'wc-unzer-direct' ),
					'type'        => 'text',
					'description' => __( 'This controls the title which the user sees during checkout.', 'wc-unzer-direct' ),
					'default'     => __( 'Unzer Direct', 'wc-unzer-direct' ),
					'desc_tip'    => true,
				],
				'description'                               => [
					'title'       => __( 'Customer Message', 'wc-unzer-direct' ),
					'type'        => 'textarea',
					'description' => __( 'This controls the description which the user sees during checkout.', 'wc-unzer-direct' ),
					'default'     => __( 'Pay with Visa, Mastercard or Maestro card', 'wc-unzer-direct' ),
					'desc_tip'    => true,
				],
				'checkout_button_text'                      => [
					'title'       => __( 'Order button text', 'wc-unzer-direct' ),
					'type'        => 'text',
					'description' => __( 'Text shown on the submit button when choosing payment method.', 'wc-unzer-direct' ),
					'default'     => __( 'Go to payment', 'wc-unzer-direct' ),
					'desc_tip'    => true,
				],
				'instructions'                              => [
					'title'       => __( 'Email instructions', 'wc-unzer-direct' ),
					'type'        => 'textarea',
					'description' => __( 'Instructions that will be added to emails.', 'wc-unzer-direct' ),
					'default'     => '',
					'desc_tip'    => true,
				],
				'unzer_direct_icons'                        => [
					'title'             => __( 'Credit card icons', 'wc-unzer-direct' ),
					'type'              => 'multiselect',
					'description'       => __( 'Choose the card icons you wish to show next to the Unzer Direct payment option in your shop.', 'wc-unzer-direct' ),
					'desc_tip'          => true,
					'class'             => 'wc-enhanced-select',
					'css'               => 'width: 450px;',
					'custom_attributes' => [
						'data-placeholder' => __( 'Select icons', 'wc-unzer-direct' )
					],
					'default'           => '',
					'options'           => self::get_card_icons(),
				],
				'unzer_direct_icons_maxheight'              => [
					'title'       => __( 'Credit card icons maximum height', 'wc-unzer-direct' ),
					'type'        => 'number',
					'description' => __( 'Set the maximum pixel height of the credit card icons shown on the frontend.', 'wc-unzer-direct' ),
					'default'     => 20,
					'desc_tip'    => true,
				],
				'Google Analytics'                          => [
					'type'  => 'title',
					'title' => __( 'Google Analytics', 'wc-unzer-direct' ),
				],
				'unzer_direct_google_analytics_tracking_id' => [
					'title'       => __( 'Tracking ID', 'wc-unzer-direct' ),
					'type'        => 'text',
					'description' => __( 'Your Google Analytics tracking ID. I.E: UA-XXXXXXXXX-X', 'wc-unzer-direct' ),
					'default'     => '',
					'desc_tip'    => true,
				],
				'ShopAdminSetup'                            => [
					'type'  => 'title',
					'title' => __( 'Shop Admin Setup', 'wc-unzer-direct' ),
				],

				'unzer_direct_orders_transaction_info' => [
					'title'       => __( 'Fetch Transaction Info', 'wc-unzer-direct' ),
					'type'        => 'checkbox',
					'label'       => __( 'Enable', 'wc-unzer-direct' ),
					'description' => __( 'Show transaction information in the order overview.', 'wc-unzer-direct' ),
					'default'     => 'yes',
					'desc_tip'    => false,
				],

				'CustomVariables'               => [
					'type'  => 'title',
					'title' => __( 'Custom Variables', 'wc-unzer-direct' ),
				],
				'unzer_direct_custom_variables' => [
					'title'             => __( 'Select Information', 'wc-unzer-direct' ),
					'type'              => 'multiselect',
					'class'             => 'wc-enhanced-select',
					'css'               => 'width: 450px;',
					'default'           => '',
					'description'       => __( 'Selected options will store the specific data on your transaction inside your Unzer Direct Manager.', 'wc-unzer-direct' ),
					'options'           => self::custom_variable_options(),
					'desc_tip'          => true,
					'custom_attributes' => [
						'data-placeholder' => __( 'Select order data', 'wc-unzer-direct' )
					]
				],
			];

		if ( WC_UnzerDirect_Subscription::plugin_is_active() ) {
			$fields['woocommerce-subscriptions'] = [
				'type'  => 'title',
				'title' => 'Subscriptions'
			];

			$fields['subscription_autocomplete_renewal_orders'] = [
				'title'       => __( 'Complete renewal orders', 'wc-unzer-direct' ),
				'type'        => 'checkbox',
				'label'       => __( 'Enable', 'wc-unzer-direct' ),
				'description' => __( 'Automatically mark a renewal order as complete on successful recurring payments.', 'wc-unzer-direct' ),
				'default'     => 'no',
				'desc_tip'    => true,
			];
		}

		return $fields;
	}

	/**
	 * @return array
	 */
	public static function get_card_icons() {
		return [
			'maestro'    => 'Maestro',
			'mastercard' => 'Mastercard',
			'visa'       => 'Visa',
		];
	}


	/**
	 * custom_variable_options function.
	 *
	 * Provides a list of custom variable options used in the settings
	 *
	 * @access private
	 * @return array
	 */
	private static function custom_variable_options() {
		$options = [
			'billing_all_data'  => __( 'Billing: Complete Customer Details', 'wc-unzer-direct' ),
			'browser_useragent' => __( 'Browser: User Agent', 'wc-unzer-direct' ),
			'customer_email'    => __( 'Customer: Email Address', 'wc-unzer-direct' ),
			'customer_phone'    => __( 'Customer: Phone Number', 'wc-unzer-direct' ),
			'shipping_all_data' => __( 'Shipping: Complete Customer Details', 'wc-unzer-direct' ),
			'shipping_method'   => __( 'Shipping: Shipping Method', 'wc-unzer-direct' ),
		];

		asort( $options );

		return $options;
	}

	/**
	 * Clears the log file.
	 *
	 * @return string
	 */
	public static function clear_logs_section() {
		$html = sprintf( '<h3 class="wc-settings-sub-title">%s</h3>', __( 'Debug', 'wc-unzer-direct' ) );
		$html .= sprintf( '<a id="wc_unzer_direct_logs" class="unzer-direct-debug-button button" href="%s">%s</a>', WC_UNZER_DIRECT()->log->get_admin_link(), __( 'View debug logs', 'wc-unzer-direct' ) );

		if ( wc_unzer_direct_can_user_empty_logs() ) {
			$html .= sprintf( '<button role="button" id="wc_unzer_direct_logs_clear" class="unzer-direct-debug-button button">%s</button>', __( 'Empty debug logs', 'wc-unzer-direct' ) );
		}

		if ( wc_unzer_direct_can_user_flush_cache() ) {
			$html .= sprintf( '<button role="button" id="wc_unzer_direct_flush_cache" class="unzer-direct-debug-button button">%s</button>', __( 'Empty transaction cache', 'wc-unzer-direct' ) );
		}

		$html .= sprintf( '<br/>' );
		$html .= sprintf( '<h3 class="wc-settings-sub-title">%s</h3>', __( 'Enable', 'wc-unzer-direct' ) );

		return $html;
	}

	/**
	 * Returns the link to the gateway settings page.
	 *
	 * @return mixed
	 */
	public static function get_settings_page_url() {
		return admin_url( 'admin.php?page=wc-settings&tab=checkout&section=unzer_direct' );
	}

	/**
	 * Shows an admin notice if the setup is not complete.
	 *
	 * @return void
	 */
	public static function show_admin_setup_notices() {
		$error_fields = [];

		$mandatory_fields = [
			'unzer_direct_privatekey' => __( 'Private key', 'wc-unzer-direct' ),
			'unzer_direct_apikey'     => __( 'Api User key', 'wc-unzer-direct' )
		];

		foreach ( $mandatory_fields as $mandatory_field_setting => $mandatory_field_label ) {
			if ( self::has_empty_mandatory_post_fields( $mandatory_field_setting ) ) {
				$error_fields[] = $mandatory_field_label;
			}
		}

		if ( ! empty( $error_fields ) ) {
			$message = sprintf( '<h2>%s</h2>', __( "WC Unzer Direct", 'wc-unzer-direct' ) );
			$message .= sprintf( '<p>%s</p>', sprintf( __( 'You have missing or incorrect settings. Go to the <a href="%s">settings page</a>.', 'wc-unzer-direct' ), self::get_settings_page_url() ) );
			$message .= '<ul>';
			foreach ( $error_fields as $error_field ) {
				$message .= "<li>" . sprintf( __( '<strong>%s</strong> is mandatory.', 'wc-unzer-direct' ), $error_field ) . "</li>";
			}
			$message .= '</ul>';

			printf( '<div class="%s">%s</div>', 'notice notice-error', $message );
		}

	}

	/**
	 * Logic wrapper to check if some of the mandatory fields are empty on post request.
	 *
	 * @return bool
	 */
	private static function has_empty_mandatory_post_fields( $settings_field ) {
		$post_key    = 'wc_unzer_direct_' . $settings_field;
		$setting_key = WC_UNZER_DIRECT()->s( $settings_field );

		return empty( $_POST[ $post_key ] ) && empty( $setting_key );

	}

	/**
	 * @return string
	 */
	private static function get_required_symbol() {
		return '<span style="color: red;">*</span>';
	}
}


?>
