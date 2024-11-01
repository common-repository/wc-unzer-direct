<?php

class WC_UnzerDirect_Admin_Orders extends WC_UnzerDirect_Module {

	/**
	 * Perform actions and filters
	 *
	 * @return mixed
	 */
	public function hooks() {
		// Custom order actions
		add_filter( 'woocommerce_order_actions', [ $this, 'admin_order_actions' ], 10, 1 );
		add_action( 'woocommerce_order_action_unzer_direct_create_payment_link', [ $this, 'order_action_unzer_direct_create_payment_link' ], 50, 2 );
		add_filter( 'bulk_actions-edit-shop_order', [ $this, 'list_bulk_actions' ], 10, 1 );
		add_filter( 'bulk_actions-edit-shop_subscription', [ $this, 'list_bulk_actions' ], 10, 1 );
		add_filter( 'handle_bulk_actions-edit-shop_order', [ $this, 'handle_bulk_actions_orders' ], 10, 3 );
		add_filter( 'handle_bulk_actions-edit-shop_subscription', [ $this, 'handle_bulk_actions_subscriptions' ], 10, 3 );
	}

	/**
	 * Handle bulk actions for orders
	 *
	 * @param $redirect_to
	 * @param $action
	 * @param $ids
	 *
	 * @return string
	 */
	public function handle_bulk_actions_orders( $redirect_to, $action, $ids ) {
		$ids     = apply_filters( 'woocommerce_bulk_action_ids', array_reverse( array_map( 'absint', $ids ) ), $action, 'order' );
		$changed = 0;

		if ( 'unzer_direct_create_payment_link' === $action ) {

			foreach ( $ids as $id ) {
				$order = wc_get_order( $id );

				if ( $order ) {
					if ( $this->order_action_unzer_direct_create_payment_link( $order ) ) {
						$changed ++;
					}
				}
			}
		}

		if ( $changed ) {
			wc_unzer_direct_add_admin_notice( sprintf( __( 'Payment links created for %d orders.', 'wc-unzer-direct' ), $changed ) );
		}

		return esc_url_raw( $redirect_to );
	}

	/**
	 * @param \WC_Order $order
	 *
	 * @return bool|void
	 */
	public function order_action_unzer_direct_create_payment_link( $order ) {
		if ( ! $order ) {
			return;
		}

		// The order used to create transaction data with Unzer Direct.
		$is_subscription = WC_UnzerDirect_Subscription::is_subscription( $order );
		$order           = new WC_UnzerDirect_Order( $order->get_id() );
		$resource_order  = $order;
		$subscription    = null;

		// Determine if payment link creation should be skipped.
		// Per default we will skip payment link creation if the order is paid already.
		if ( ! $create_payment_link = apply_filters( 'wc_unzer_direct_order_action_create_payment_link_for_order', ! $order->is_paid(), $order ) ) {
			wc_unzer_direct_add_admin_notice( sprintf( __( 'Payment link creation skipped for order #%s', 'wc-unzer-direct' ), $order->get_id() ), 'error' );

			return;
		}

		try {

			$order->set_payment_method( WC_UNZER_DIRECT()->id );
			$order->set_payment_method_title( WC_UNZER_DIRECT()->get_method_title() );
			$transaction_id = $order->get_transaction_id();

			if ( $is_subscription ) {
				$resource = new WC_UnzerDirect_API_Subscription();

				if ( ! $order_parent_id = $resource_order->get_parent_id() ) {
					throw new UnzerDirect_Exception( __( 'A parent order must be mapped to the subscription.', 'wc-unzer-direct' ) );
				}
				$resource_order = new WC_UnzerDirect_Order( $order_parent_id );

				// Set the appropriate payment method id and title on the parent order as well
				$resource_order->set_payment_method( WC_UNZER_DIRECT()->id );
				$resource_order->set_payment_method_title( WC_UNZER_DIRECT()->get_method_title() );
				$resource_order->save();
			} else {
				$resource = new WC_UnzerDirect_API_Payment();
			}

			if ( ! $transaction_id ) {
				$transaction    = $resource->create( $resource_order );
				$transaction_id = $transaction->id;
				$order->set_transaction_id( $transaction_id );
			}

			$link = $resource->patch_link( $transaction_id, $resource_order );

			if ( ! WC_UnzerDirect_Helper::is_url( $link->url ) ) {
				throw new \Exception( sprintf( __( 'Invalid payment link received from API for order #%s', 'wc-unzer-direct' ), $order->get_id() ) );
			}

			$order->set_payment_link( $link->url );

			// Late save for subscriptions. This is only to make sure that manual renewal is not set to true if an error occurs during the link creation.
			if ( $is_subscription ) {
				$subscription = wcs_get_subscription( $order->get_id() );
				$subscription->set_requires_manual_renewal( false );
				$subscription->save();
			}

			// Make sure to save the changes to the order/subscription object
			$order->save();
			$order->add_order_note( sprintf( __( 'Payment link manually created from backend: %s', 'wc-unzer-direct' ), $link->url ), false, true );

			do_action( 'wc_unzer_direct_order_action_payment_link_created', $link->url, $order );

			return true;
		} catch ( \Exception $e ) {
			wc_unzer_direct_add_admin_notice( sprintf( __( 'Payment link could not be created for order #%s. Error: %s', 'wc-unzer-direct' ), $order->get_id(), $e->getMessage() ), 'error' );

			return false;
		}
	}

	/**
	 * Handle bulk actions for orders
	 *
	 * @param $redirect_to
	 * @param $action
	 * @param $ids
	 *
	 * @return string
	 */
	public function handle_bulk_actions_subscriptions( $redirect_to, $action, $ids ) {
		$ids     = apply_filters( 'woocommerce_bulk_action_ids', array_reverse( array_map( 'absint', $ids ) ), $action, 'order' );
		$changed = 0;

		if ( 'unzer_direct_create_payment_link' === $action ) {

			foreach ( $ids as $id ) {
				$subscription = wcs_get_subscription( $id );

				if ( $subscription ) {
					if ( $this->order_action_unzer_direct_create_payment_link( $subscription ) ) {
						$changed ++;
					}
				}
			}
		}

		if ( $changed ) {
			wc_unzer_direct_add_admin_notice( sprintf( __( 'Payment links created for %d subscriptions.', 'wc-unzer-direct' ), $changed ) );
		}

		return esc_url_raw( $redirect_to );
	}

	/**
	 * @param $actions
	 *
	 * @return mixed
	 */
	public function list_bulk_actions( $actions ) {
		$actions['unzer_direct_create_payment_link'] = __( 'Create payment link', 'wc-unzer-direct' );

		return $actions;
	}

	/**
	 * Adds custom actions
	 *
	 * @param $actions
	 *
	 * @return mixed
	 */
	public function admin_order_actions( $actions ) {
		$actions['unzer_direct_create_payment_link'] = __( 'Create payment link', 'wc-unzer-direct' );

		return $actions;
	}
}
