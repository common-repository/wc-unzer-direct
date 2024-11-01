<?php

class WC_UnzerDirect_PayPal extends WC_UnzerDirect_Instance {

	public $main_settings = null;

	public function __construct() {
		parent::__construct();

		// Get gateway variables
		$this->id = 'unzer_direct_paypal';

		$this->method_title = 'Unzer Direct PayPal';

		$this->setup();

		$this->title       = $this->s( 'title' );
		$this->description = $this->s( 'description' );

		add_filter( 'wc_unzer_direct_cardtypelock_' . $this->id, [ $this, 'filter_cardtypelock' ] );
		add_filter( 'wc_unzer_direct_transaction_params_basket', [ $this, '_return_empty_array' ], 30, 2 );
		add_filter( 'wc_unzer_direct_transaction_params_shipping_row', [ $this, '_return_empty_array' ], 30, 2 );
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
				'default'     => $this->method_title
			],
			'description' => [
				'title'       => __( 'Customer Message', 'wc-unzer-direct' ),
				'type'        => 'textarea',
				'description' => __( 'This controls the description which the user sees during checkout.', 'wc-unzer-direct' ),
				'default' => __( 'Pay with Paypal', 'wc-unzer-direct' )
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
		return 'paypal';
	}

	/**
	 * @param array $items
	 * @param WC_UnzerDirect_Order $order
	 *
	 * @return array
	 */
	public function _return_empty_array( $items, $order ) {
		if ( $order->get_payment_method() === $this->id ) {
			$items = [];
		}

		return $items;
	}

	/**
	 * FILTER: apply_gateway_icons function.
	 *
	 * Sets gateway icons on frontend
	 *
	 * @access public
	 * @return void
	 */
	public function apply_gateway_icons( $icon, $id ) {
		if ( $id == $this->id ) {
			$icon = $this->gateway_icon_create( 'paypal', $this->gateway_icon_size() );
		}

		return $icon;
	}
}
