<?php
/**
 * WC_UnzerDirect_API_Subscription class
 *
 * @class       WC_UnzerDirect_API_Subscription
 * @since       4.0.0
 * @category    Class
 * @author      PerfectSolution
 */

class WC_UnzerDirect_API_Subscription extends WC_UnzerDirect_API_Transaction {
	/**
	 * __construct function.
	 *
	 * @access public
	 * @return void
	 */
	public function __construct( $resource_data = null ) {
		// Run the parent construct
		parent::__construct();

		// Set the resource data to an object passed in on object instantiation.
		// Usually done when we want to perform actions on an object returned from
		// the API sent to the plugin callback handler.
		if ( is_object( $resource_data ) ) {
			$this->resource_data = $resource_data;
		}

		// Append the main API url
		$this->api_url .= 'subscriptions/';
	}


	/**
	 * create function.
	 *
	 * Creates a new subscription via the API
	 *
	 * @access public
	 *
	 * @param WC_UnzerDirect_Order $order
	 *
	 * @return object
	 * @throws UnzerDirect_API_Exception
	 */
	public function create( WC_UnzerDirect_Order $order ) {
		return parent::create( $order );
	}


	/**
	 * recurring function.
	 *
	 * Sends a 'recurring' request to the Unzer Direct API
	 *
	 * @access public
	 *
	 * @param int $transaction_id
	 * @param int $amount
	 *
	 * @return $request
	 * @throws UnzerDirect_API_Exception
	 */
	public function recurring( $subscription_id, $order, $amount = null ) {
		// Check if a custom amount ha been set
		if ( $amount === null ) {
			// No custom amount set. Default to the order total
			$amount = WC_Subscriptions_Order::get_recurring_total( $order );
		}

		if ( ! $order instanceof WC_UnzerDirect_Order ) {
			$order_id = $order->get_id();
			$order    = new WC_UnzerDirect_Order( $order_id );
		}

		$order_number = $order->get_order_number_for_api( $is_recurring = true );

		$request_url = sprintf( '%d/%s', $subscription_id, "recurring" );

		$request_data = apply_filters( 'wc_unzer_direct_create_recurring_payment_data', [
			'amount'            => WC_UnzerDirect_Helper::price_multiply( $amount, $order->get_currency() ),
			'order_id'          => $order_number,
			'auto_capture'      => $order->get_autocapture_setting(),
			'autofee'           => WC_UnzerDirect_Helper::option_is_enabled( WC_UNZER_DIRECT()->s( 'unzer_direct_autofee' ) ),
			'text_on_statement' => WC_UNZER_DIRECT()->s( 'unzer_direct_text_on_statement' ),
			'order_post_id'     => $order->get_id(),
		], $order, $subscription_id );

		$request_data = apply_filters( 'wc_unzer_direct_create_recurring_payment_data_' . strtolower( $order->get_payment_method() ), $request_data, $order, $subscription_id );

		return $this->post( $request_url, $request_data, true );
	}


	/**
	 * cancel function.
	 *
	 * Sends a 'cancel' request to the Unzer Direct API
	 *
	 * @access public
	 *
	 * @param int $subscription_id
	 *
	 * @return void
	 * @throws UnzerDirect_API_Exception
	 */
	public function cancel( $subscription_id ) {
		$this->post( sprintf( '%d/%s', $subscription_id, "cancel" ) );
	}


	/**
	 * is_action_allowed function.
	 *
	 * Check if the action we are about to perform is allowed according to the current transaction state.
	 *
	 * @access public
	 *
	 * @param $action
	 *
	 * @return boolean
	 * @throws UnzerDirect_API_Exception
	 */
	public function is_action_allowed( $action ) {
		$state = $this->get_current_type();

		$allowed_states = [
			'cancel'           => [ 'authorize' ],
			'standard_actions' => [ 'authorize' ]
		];

		return array_key_exists( $action, $allowed_states ) and in_array( $state, $allowed_states[ $action ] );
	}
}
