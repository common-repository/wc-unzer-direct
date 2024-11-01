<?php

class WC_UnzerDirect_Invoice extends WC_UnzerDirect_Instance {

	public $main_settings = null;

	public function __construct() {
		parent::__construct();

		// Get gateway variables
		$this->id = 'unzer_direct_invoice';

		$this->method_title = 'Unzer Invoice';

		$this->setup();

		$this->title       = $this->s( 'title' );
		$this->description = $this->s( 'description' );

		add_filter( 'woocommerce_available_payment_gateways', [ $this, 'maybe_disable_gateway' ] );
		add_filter( 'wc_unzer_direct_cardtypelock_' . $this->id, [ $this, 'filter_cardtypelock' ] );
		add_filter( 'wc_unzer_direct_checkout_gateway_icon', [ $this, 'filter_icon' ] );
	}


	/**
	 * init_form_fields function.
	 *
	 * Initiates the plugin settings form fields
	 *
	 * @access public
	 * @return array
	 */
	public function init_form_fields() {
		$this->form_fields = [
			'enabled'     => [
				'title'   => __( 'Enable', 'wc-unzer-direct' ),
				'type'    => 'checkbox',
				'label'   => __( 'Enable Unzer Invoice payment', 'wc-unzer-direct' ),
				'default' => 'no'
			],
			'_Shop_setup' => [
				'type'  => 'title',
				'title' => __( 'Shop setup', 'wc-unzer-direct' ),
			],
			'title'       => [
				'title'       => __( 'Title', 'wc-unzer-direct' ),
				'type'        => 'text',
				'description' => __( 'This controls the title which the user sees during checkout.', 'wc-unzer-direct' ),
				'default'     => $this->method_title,
			],
			'description' => [
				'title'       => __( 'Customer Message', 'wc-unzer-direct' ),
				'type'        => 'textarea',
				'description' => __( 'This controls the description which the user sees during checkout.', 'wc-unzer-direct' ),
				'default'     => __( 'Pay 14 days after delivery', 'wc-unzer-direct' )
			],
		];
	}


	/**
	 * filter_cardtypelock function.
	 *
	 * Sets the cardtypelock
	 *
	 * @access public
	 * @return string
	 */
	public function filter_cardtypelock() {
		return 'unzer-pay-later-invoice';
	}

	/**
	 * @param $icon
	 *
	 * @return string
	 */
	public function filter_icon( $icon ) {
		if ( 'invoice' === $icon ) {
			$icon = 'unzer';
		}

		return $icon;
	}

	/**
	 * @param array $gateways
	 */
	public function maybe_disable_gateway( $gateways ) {
		if ( isset( $gateways[ $this->id ] ) && is_checkout() && ( $cart = WC()->cart ) ) {
			$cart_total  = (float) $cart->get_total( 'edit' );
			$cart_min    = 10;
			$cart_max    = 3500;
			$currency    = strtoupper( get_woocommerce_currency() );
			$country     = strtoupper( WC()->checkout()->get_value( 'billing_country' ) );

			if ( ! ( $cart_total >= $cart_min && $cart_total <= $cart_max ) || ( 'EUR' !== $currency && 'CHF' !== $currency ) || ( 'DE' !== $country && 'AT' !== $country && 'CH' !== $country ) || $this->is_checkout_different_shipping_address_enabled() ) {
				unset( $gateways[ $this->id ] );
			}
		}

		return $gateways;
	}
}
