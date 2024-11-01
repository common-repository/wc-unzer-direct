<?php

class WC_UnzerDirect_Apple_Pay extends WC_UnzerDirect_Instance {

	public $main_settings = null;

	public function __construct() {
		parent::__construct();

		// Get gateway variables
		$this->id = 'unzer_direct_apple-pay';

		$this->method_title = 'Unzer Direct Apple Pay';

		$this->setup();

		$this->title       = $this->s( 'title' );
		$this->description = $this->s( 'description' );

		add_filter( 'wc_unzer_direct_cardtypelock_' . $this->id, [ $this, 'filter_cardtypelock' ] );
		add_filter( 'wc_unzer_direct_checkout_gateway_icon', [ $this, 'filter_icon' ] );
		add_filter( 'woocommerce_available_payment_gateways', [ $this, 'maybe_disable_gateway' ] );
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
				'title'       => __( 'Enable', 'wc-unzer-direct' ),
				'type'        => 'checkbox',
				'label'       => sprintf( __( 'Enable %s payment', 'wc-unzer-direct' ), $this->method_title ),
				'description' => sprintf( __( 'Works only in %s.', 'wc-unzer-direct' ), 'Safari' ),
				'default'     => 'no'
			],
			'_Shop_setup' => [
				'type'  => 'title',
				'title' => __( 'Shop setup', 'wc-unzer-direct' ),
			],
			'title'       => [
				'title'       => __( 'Title', 'wc-unzer-direct' ),
				'type'        => 'text',
				'description' => __( 'This controls the title which the user sees during checkout.', 'wc-unzer-direct' ),
				'default'     => $this->method_title
			],
			'description' => [
				'title'       => __( 'Customer Message', 'wc-unzer-direct' ),
				'type'        => 'textarea',
				'description' => __( 'This controls the description which the user sees during checkout.', 'wc-unzer-direct' ),
				'default'     => __( 'Pay with your Apple device', 'wc-unzer-direct' )
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
		return 'apple-pay';
	}

	/**
	 * @param $icon
	 *
	 * @return string
	 */
	public function filter_icon( $icon ) {
		if ( 'apple_pay' === $icon ) {
			$icon = 'apple-pay';
		}

		return $icon;
	}

	/**
	 * @param array $gateways
	 */
	public function maybe_disable_gateway( $gateways ) {
		if ( isset( $gateways[ $this->id ] ) && is_checkout() && ! WC_UnzerDirect_Helper::is_browser( 'safari' ) ) {
			unset( $gateways[ $this->id ] );
		}

		return $gateways;
	}
}
