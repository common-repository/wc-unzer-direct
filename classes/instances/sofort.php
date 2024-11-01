<?php

class WC_UnzerDirect_Sofort extends WC_UnzerDirect_Instance {

	public $main_settings = null;

	public function __construct() {
		parent::__construct();

		// Get gateway variables
		$this->id = 'unzer_direct_sofort';

		$this->method_title = 'Unzer Direct Sofort';

		$this->setup();

		$this->title       = $this->s( 'title' );
		$this->description = $this->s( 'description' );

		add_filter( 'wc_unzer_direct_cardtypelock_' . $this->id, [ $this, 'filter_cardtypelock' ] );
		add_action( 'wc_unzer_direct_accepted_callback_status_capture', [ $this, 'additional_callback_handler' ], 10, 2 );
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
				'title'   => __( 'Enable', 'wc-unzer-direct' ),
				'type'    => 'checkbox',
				'label'   => sprintf( __( 'Enable %s payment', 'wc-unzer-direct' ), $this->method_title ),
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
				'default'     => __( 'Pay with SOFORT Banking', 'wc-unzer-direct' )
			],
		];
	}


	/**
	 * Sets the cardtype lock
	 *
	 * @access public
	 * @return string
	 */
	public function filter_cardtypelock() {
		return 'sofort';
	}

	/**
	 * Sofort payments are not sending authorized callbacks. Instead, a capture callback is sent. We will perform
	 * gateway specific logic here to handle the payment properly.
	 *
	 * @param \WC_UnzerDirect_Order $order
	 * @param stdClass $transaction
	 */
	public function additional_callback_handler( $order, $transaction ) {
		if ( $order->get_payment_method() === $this->id ) {
			WC_UnzerDirect_Callbacks::authorized( $order, $transaction );
			WC_UnzerDirect_Callbacks::payment_authorized( $order, $transaction );
		}
	}

	/**
	 * @param $gateways
	 *
	 * @return mixed
	 */
	public function maybe_disable_gateway( $gateways ) {
		$supported_currencies = [ 'EUR', 'GBP', 'PLN', 'CHF' ];

		if ( isset( $gateways[ $this->id ] ) && is_checkout() && ! in_array( strtoupper( get_woocommerce_currency() ), $supported_currencies ) ) {
			unset( $gateways[ $this->id ] );
		}

		return $gateways;
	}
}
