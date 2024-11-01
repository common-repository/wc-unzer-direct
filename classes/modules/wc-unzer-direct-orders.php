<?php

/**
 * Class WC_UnzerDirect_Orders
 */
class WC_UnzerDirect_Orders extends WC_UnzerDirect_Module {

	/**
	 * @return void
	 */
	public function hooks() {
		// Reset failed payment count
		add_action( 'woocommerce_order_status_completed', [ $this, 'reset_failed_payment_count' ] );
		add_action( 'woocommerce_order_status_processing', [ $this, 'reset_failed_payment_count' ] );
		add_action( 'woocommerce_order_status_cancelled', [ $this, 'maybe_cancel_transaction' ], 10, 2 );

		add_action( 'wc_unzer_direct_callback_payment_authorized', [ $this, 'on_payment_authorized' ] );
	}

	/**
	 * @param $order_id
	 * @param $order
	 */
	public function maybe_cancel_transaction( $order_id, $order ) {
		if ( $order && WC_UnzerDirect_Helper::option_is_enabled( WC_UNZER_DIRECT()->s( 'unzer_cancel_transaction_on_cancel' ) ) ) {
			$order = new WC_UnzerDirect_Order( $order_id );
			if ( $transaction_id = $order->get_transaction_id() ) {
				$transaction = wc_unzer_direct_get_transaction_instance_by_order( $order );
				try {
					$transaction->get( $transaction_id );
					if ( $transaction->is_action_allowed( 'cancel' ) ) {
						$transaction->cancel( $transaction_id );
						$order->note( __( 'Payment cancelled due to order cancellation', 'wc-unzer-direct' ) );
					}
				} catch ( Exception $e ) {
					WC_UNZER_DIRECT()->log->add( 'Event: Order cancelled -> Error occured when cancelling transaction: ' . $e->getMessage() );
				}
			}
		}
	}

	/**
	 * When the order status changes to either processing or completed, we will reset the failed payment count (if any).
	 *
	 * @param $order_id
	 */
	public function reset_failed_payment_count( $order_id ) {
		try {
			if ( $order = new WC_UnzerDirect_Order( $order_id ) ) {
				$order->reset_failed_unzer_direct_payment_count();
			}
		} catch ( Exception $e ) {
			// NOOP
		}
	}

	/**
	 * @param WC_UnzerDirect_Order $order
	 */
	public function on_payment_authorized( $order ) {
		$autocomplete_renewal_orders = WC_UnzerDirect_Helper::option_is_enabled( WC_UNZER_DIRECT()->s( 'subscription_autocomplete_renewal_orders' ) );

		if ( $autocomplete_renewal_orders && WC_UnzerDirect_Subscription::is_renewal( $order ) ) {
			$order->update_status( 'completed', __( 'Automatically completing order status due to successful recurring payment', 'wc-unzer-direct' ) );
		}
	}
}
