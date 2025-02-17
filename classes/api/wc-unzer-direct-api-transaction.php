<?php
/**
 * WC_UnzerDirect_API_Transaction class
 *
 * Used for common methods shared between payments and subscriptions
 *
 * @class          WC_UnzerDirect_API_Payment
 * @since          4.0.0
 * @category       Class
 * @author         PerfectSolution
 */

class WC_UnzerDirect_API_Transaction extends WC_UnzerDirect_API {

	/**
	 * @var bool
	 */
	protected $loaded_from_cache = false;

	/**
	 * get_current_type function.
	 *
	 * Returns the current payment type
	 *
	 * @access public
	 * @return string
	 * @throws UnzerDirect_API_Exception
	 */
	public function get_current_type() {
		$last_operation = $this->get_last_operation();

		if ( ! is_object( $last_operation ) ) {
			throw new UnzerDirect_API_Exception( "Malformed operation response", 0 );
		}

		return $last_operation->type;
	}

	/**
	 * get_last_operation function.
	 *
	 * Returns the last successful transaction operation
	 *
	 * @access public
	 * @return stdClass
	 * @throws UnzerDirect_API_Exception
	 */
	public function get_last_operation() {
		if ( ! is_object( $this->resource_data ) ) {
			throw new UnzerDirect_API_Exception( 'No API payment resource data available.', 0 );
		}

		// Loop through all the operations and return only the operations that were successful (based on the qp_status_code and pending mode).
		$successful_operations = array_filter( $this->resource_data->operations, function ( $operation ) {
			return $operation->qp_status_code == 20000 || $operation->pending == true;
		} );

		$last_operation = end( $successful_operations );

		if ( ! is_object( $last_operation ) ) {
			throw new UnzerDirect_API_Exception( 'Malformed operation object' );
		}

		if ( $last_operation->pending === true ) {
			$last_operation->type = __( 'Pending - check your Unzer Direct manager', 'wc-unzer-direct' );
		}

		return $last_operation;
	}

	/**
	 * @param $type
	 *
	 * @return mixed|null
	 * @throws UnzerDirect_API_Exception
	 */
	public function get_last_operation_of_type( $type ) {
		if ( ! is_object( $this->resource_data ) ) {
			throw new UnzerDirect_API_Exception( 'No API payment resource data available.', 0 );
		}
		$operations = array_reverse( $this->resource_data->operations );

		foreach ( $operations as $operation ) {
			if ( $operation->type === $type ) {
				return $operation;
			}
		}

		return null;
	}

	/**
	 * is_test function.
	 *
	 * Tests if a payment was made in test mode.
	 *
	 * @access public
	 * @return boolean
	 * @throws UnzerDirect_API_Exception
	 */
	public function is_test() {
		if ( ! is_object( $this->resource_data ) ) {
			throw new UnzerDirect_API_Exception( 'No API payment resource data available.', 0 );
		}

		return $this->resource_data->test_mode;
	}

	/**
	 * create function.
	 *
	 * Creates a new payment via the API
	 *
	 * @access public
	 *
	 * @param WC_UnzerDirect_Order $order
	 *
	 * @return object
	 * @throws UnzerDirect_API_Exception
	 */
	public function create( WC_UnzerDirect_Order $order ) {
		$base_params = [
			'currency'      => $order->get_currency(),
			'order_post_id' => $order->get_id(),
		];

		$text_on_statement = WC_UNZER_DIRECT()->s( 'unzer_direct_text_on_statement' );
		if ( ! empty( $text_on_statement ) ) {
			$base_params['text_on_statement'] = $text_on_statement;
		}

		$order_params = $order->get_transaction_params();

		$params = array_merge( $base_params, $order_params );

		$payment = $this->post( '/', $params );

		return $payment;
	}

	/**
	 * create_link function.
	 *
	 * Creates or updates a payment link via the API
	 *
	 * @param int $transaction_id
	 * @param WC_UnzerDirect_Order $order
	 *
	 * @return object
	 * @throws UnzerDirect_API_Exception
	 * @since  4.5.0
	 * @access public
	 *
	 */
	public function patch_link( $transaction_id, WC_UnzerDirect_Order $order ) {
		$cardtypelock = WC_UNZER_DIRECT()->s( 'unzer_direct_cardtypelock' );

		$payment_method = strtolower( $order->get_payment_method() );

		$base_params = [
			'language'                     => wc_unzer_direct_get_language(),
			'currency'                     => $order->get_currency(),
			'callbackurl'                  => WC_UnzerDirect_Helper::get_callback_url(),
			'autocapture'                  => WC_UnzerDirect_Helper::option_is_enabled( $order->get_autocapture_setting() ),
			'autofee'                      => WC_UnzerDirect_Helper::option_is_enabled( WC_UNZER_DIRECT()->s( 'unzer_direct_autofee' ) ),
			'payment_methods'              => apply_filters( 'wc_unzer_direct_cardtypelock_' . $payment_method, $cardtypelock, $payment_method ),
			'branding_id'                  => WC_UNZER_DIRECT()->s( 'unzer_direct_branding_id' ),
			'google_analytics_tracking_id' => WC_UNZER_DIRECT()->s( 'unzer_direct_google_analytics_tracking_id' ),
			'customer_email'               => $order->get_billing_email(),
		];

		$order_params = $order->get_transaction_link_params();

		$merged_params = array_merge( $base_params, $order_params );

		$params = apply_filters( 'wc_unzer_direct_transaction_link_params', $merged_params, $order, $payment_method );

		$payment_link = $this->put( sprintf( '%d/link', $transaction_id ), $params );

		return $payment_link;
	}

	/**
	 * @param $transaction_id
	 * @param WC_UnzerDirect_Order $order
	 *
	 * @return object
	 * @throws UnzerDirect_API_Exception
	 */
	public function patch_payment( $transaction_id, WC_UnzerDirect_Order $order ) {
		$base_params = [
			'currency'      => $order->get_currency(),
			'order_post_id' => $order->get_id(),
		];

		$text_on_statement = WC_UNZER_DIRECT()->s( 'unzer_direct_text_on_statement' );

		if ( ! empty( $text_on_statement ) ) {
			$base_params['text_on_statement'] = $text_on_statement;
		}

		$order_params = $order->get_transaction_params();

		$params = array_merge( $base_params, $order_params );

		return $this->patch( sprintf( '/%s', $transaction_id ), $params );
	}

	/**
	 * Returns the payment type / card type used on the transaction
	 *
	 * @return mixed
	 * @throws UnzerDirect_API_Exception
	 * @since  4.5.0
	 */
	public function get_brand() {
		if ( ! is_object( $this->resource_data ) ) {
			throw new UnzerDirect_API_Exception( 'No API payment resource data available.', 0 );
		}

		return $this->resource_data->metadata->brand;
	}

	/**
	 * get_formatted_balance function
	 *
	 * Returns a formatted transaction balance
	 *
	 * @return mixed
	 * @throws UnzerDirect_API_Exception
	 * @since  4.5.0
	 */
	public function get_formatted_balance() {
		return WC_UnzerDirect_Helper::price_normalize( $this->get_balance(), $this->get_currency() );
	}

	/**
	 * get_balance function
	 *
	 * Returns the transaction balance
	 *
	 * @return mixed
	 * @throws UnzerDirect_API_Exception
	 * @since  4.5.0
	 */
	public function get_balance() {
		if ( ! is_object( $this->resource_data ) ) {
			throw new UnzerDirect_API_Exception( 'No API payment resource data available.', 0 );
		}

		return ! empty( $this->resource_data->balance ) ? $this->resource_data->balance : null;
	}

	/**
	 * get_currency function
	 *
	 * Returns a transaction currency
	 *
	 * @return mixed
	 * @throws UnzerDirect_API_Exception
	 * @since  4.5.0
	 */
	public function get_currency() {
		if ( ! is_object( $this->resource_data ) ) {
			throw new UnzerDirect_API_Exception( 'No API payment resource data available.', 0 );
		}

		return $this->resource_data->currency;
	}

	/**
	 * get_formatted_remaining_balance function
	 *
	 * Returns a formatted transaction balance
	 *
	 * @return mixed
	 * @throws UnzerDirect_API_Exception
	 * @since  4.5.0
	 */
	public function get_formatted_remaining_balance() {
		return WC_UnzerDirect_Helper::price_normalize( $this->get_remaining_balance(), $this->get_currency() );
	}

	/**
	 * @return float|int|mixed|null
	 * @throws UnzerDirect_API_Exception
	 */
	public function get_remaining_balance_as_float() {
		$remaining_balance = $this->get_remaining_balance();

		if ( $remaining_balance > 0 && WC_UnzerDirect_Helper::is_currency_using_decimals( $this->get_currency() ) ) {
			return $remaining_balance / 100;
		}

		return $remaining_balance;
	}

	/**
	 * get_remaining_balance function
	 *
	 * Returns a remaining balance
	 *
	 * @return mixed
	 * @throws UnzerDirect_API_Exception
	 * @since  4.5.0
	 */
	public function get_remaining_balance() {
		$balance = $this->get_balance();

		$authorized_operations = array_filter( $this->resource_data->operations, function ( $operation ) {
			return 'authorize' === $operation->type || 'recurring' === $operation->type;
		} );

		if ( empty( $authorized_operations ) ) {
			return;
		}

		$operation = reset( $authorized_operations );

		$amount = $operation->amount;

		$remaining = $amount;

		if ( $balance > 0 ) {
			$remaining = $amount - $balance;
		}

		return $remaining;
	}

	/**
	 * @return string|null
	 */
	public function get_acquirer() {
		$acquirer = null;

		if ( is_object( $this->resource_data ) && isset( $this->resource_data->acquirer ) ) {
			$acquirer = $this->resource_data->acquirer;
		}

		return $acquirer;
	}

	/**
	 * Checks if either a specific operation or the last operation was successful.
	 *
	 * @param null $operation
	 *
	 * @return bool
	 * @throws UnzerDirect_API_Exception
	 * @since 4.5.0
	 */
	public function is_operation_approved( $operation = null ) {
		if ( ! is_object( $this->resource_data ) ) {
			throw new UnzerDirect_API_Exception( 'No API payment resource data available.', 0 );
		}

		if ( $operation === null ) {
			$operation = $this->get_last_operation();
		}

		return $this->resource_data->accepted && $operation->qp_status_code == 20000 && $operation->aq_status_code == 20000;
	}

	/**
	 * get_metadata function
	 *
	 * Returns the metadata of a transaction
	 *
	 * @return mixed
	 * @throws UnzerDirect_API_Exception
	 * @since  4.5.0
	 */
	public function get_metadata() {
		if ( ! is_object( $this->resource_data ) ) {
			throw new UnzerDirect_API_Exception( 'No API payment resource data available.', 0 );
		}

		return $this->resource_data->metadata;
	}

	/**
	 * get_state function
	 *
	 * Returns the current transaction state
	 *
	 * @return mixed
	 * @throws UnzerDirect_API_Exception
	 * @since  4.5.0
	 */
	public function get_state() {
		if ( ! is_object( $this->resource_data ) ) {
			throw new UnzerDirect_API_Exception( 'No API payment resource data available.', 0 );
		}

		return $this->resource_data->state;
	}

	/**
	 * Fetches transaction data based on a transaction ID. This method checks if the transaction is cached in a transient before it asks the
	 * Unzer Direct API. Cached data will always be used if available.
	 *
	 * If no data is cached, we will fetch the transaction from the API and cache it.
	 *
	 * @param        $transaction_id
	 *
	 * @return object|stdClass
	 * @throws UnzerDirect_API_Exception
	 * @throws UnzerDirect_Exception
	 */
	public function maybe_load_transaction_from_cache( $transaction_id ) {

		$is_caching_enabled = self::is_transaction_caching_enabled();

		if ( empty( $transaction_id ) ) {
			throw new UnzerDirect_Exception( __( 'Transaction ID cannot be empty', 'wc-unzer-direct' ) );
		}

		if ( $is_caching_enabled && false !== ( $transient = get_transient( 'wc_unzer_direct_transaction_' . $transaction_id ) ) ) {
			$this->loaded_from_cache = true;

			return $this->resource_data = (object) json_decode( $transient );
		}

		$this->get( $transaction_id );

		if ( $is_caching_enabled ) {
			$this->cache_transaction();
		}

		return $this->resource_data;
	}

	/**
	 * @return boolean
	 */
	public static function is_transaction_caching_enabled() {
		$is_enabled = strtolower( WC_UNZER_DIRECT()->s( 'unzer_direct_caching_enabled' ) ) === 'no' ? false : true;

		return apply_filters( 'wc_unzer_direct_transaction_cache_enabled', $is_enabled );
	}

	/**
	 * Updates cache data for a transaction
	 *
	 * @return boolean
	 * @throws UnzerDirect_Exception
	 */
	public function cache_transaction() {
		if ( ! is_object( $this->resource_data ) ) {
			throw new UnzerDirect_Exception( "Cannot cache empty transaction." );
		}

		if ( ! self::is_transaction_caching_enabled() ) {
			return false;
		}

		$expiration = (int) WC_UNZER_DIRECT()->s( 'unzer_direct_caching_expiration' );

		if ( ! $expiration ) {
			$expiration = 7 * DAY_IN_SECONDS;
		}

		// Cache expiration in seconds
		$expiration = apply_filters( 'wc_unzer_direct_transaction_cache_expiration', $expiration );

		return set_transient( 'wc_unzer_direct_transaction_' . $this->resource_data->id, json_encode( $this->resource_data ), $expiration );
	}

	/**
	 * @return bool
	 */
	public function is_loaded_from_cached() {
		return $this->loaded_from_cache;
	}

	/**
	 * return stdClass
	 */
	public function get_data() {
		return $this->resource_data;
	}
}
