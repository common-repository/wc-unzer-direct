<?php

/**
 * Returns the proper transaction instance type
 *
 * @param mixed $order
 *
 * @return WC_UnzerDirect_API_Payment|WC_UnzerDirect_API_Subscription
 */
function wc_unzer_direct_get_transaction_instance_by_order( $order ) {

	$order = wc_unzer_direct_get_order( $order );

	// Instantiate a new transaction
	$api_transaction = new WC_UnzerDirect_API_Payment();

	// If the order is a subscripion or an attempt of updating the payment method
	if ( ! WC_UnzerDirect_Subscription::cart_contains_switches() && ( $order->contains_subscription() || $order->is_request_to_change_payment() ) ) {
		// Instantiate a subscription transaction instead of a payment transaction
		$api_transaction = new WC_UnzerDirect_API_Subscription();
	}

	return $api_transaction;
}

/**
 * Creates a new transaction based on the order and persists the transaction ID on the object.
 *
 * @param mixed $order
 *
 * @return int
 * @throws UnzerDirect_API_Exception
 */
function wc_unzer_direct_create_order_transaction( $order ) {
	$order = wc_unzer_direct_get_order( $order );

	$transaction = wc_unzer_direct_get_transaction_instance_by_order( $order );
	$result      = $transaction->create( $order );
	$order->set_payment_id( $result->id );

	return (int) $result->id;
}

/**
 * Returns an existing payment link if available or creates a new one.
 *
 * @param $order
 *
 * @param bool $force_update
 *
 * @return string
 * @throws UnzerDirect_API_Exception
 */
function wc_unzer_direct_create_payment_link( $order, $force_update = true ) {

	$order = wc_unzer_direct_get_order( $order );

	if ( ! $order->needs_payment() && ! $order->is_request_to_change_payment() ) {
		throw new \Exception( __( 'Order does not need payment', 'wc-unzer-direct' ) );
	}

	$transaction = wc_unzer_direct_get_transaction_instance_by_order( $order );

	$payment_link = $order->get_payment_link();
	$payment_id   = $order->get_payment_id();

	if ( empty( $payment_id ) && empty( $payment_link ) ) {
		$payment_id = wc_unzer_direct_create_order_transaction( $order );
	} else {
		$transaction->patch_payment( $payment_id, $order );
	}



	if ( empty( $payment_link ) || $force_update ) {
		// Create or update the payment link. This is necessary to do EVERY TIME
		// to avoid fraud with changing amounts.
		$link = $transaction->patch_link( $payment_id, $order );

		if ( WC_UnzerDirect_Helper::is_url( $link->url ) ) {
			$order->set_payment_link( $link->url );
			$payment_link = $link->url;
		}
	}

	return $payment_link;
}

/**
 * Creates a payment transaction.
 *
 * @param mixed $order
 *
 * @return \WC_UnzerDirect_Order
 */
function wc_unzer_direct_get_order( $order ) {
	if ( ! is_object( $order ) ) {
		$order = new WC_UnzerDirect_Order( $order );
	} else if ( $order instanceof WC_Order && ! $order instanceof WC_UnzerDirect_Order ) {
		$order = new WC_UnzerDirect_Order( $order->get_id() );
	}

	return $order;
}

/**
 * Returns the locale used in the payment window
 * @return string
 */
function wc_unzer_direct_get_language() {
	list( $language ) = explode( '_', get_locale() );

	return apply_filters( 'wc_unzer_direct_language', $language );
}
