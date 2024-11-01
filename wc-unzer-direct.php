<?php
/**
 * Plugin Name: Unzer Direct payment gateway for WooCommerce
 * Plugin URI: http://wordpress.org/plugins/wc-unzer-direct/
 * Description: Integrate Unzer Direct payment gateway with WooCommerce
 * Version: 1.4.4
 * Author: Unzer
 * Text Domain: wc-unzer-direct
 * Domain Path: /languages/
 * Author URI: https://www.unzer.com/
 * WC requires at least: 3.0.0
 * WC tested up to: 6.5
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'WC_UNZER_DIRECT_VERSION', '1.4.4' );
define( 'WC_UNZER_DIRECT_URL', plugins_url( __FILE__ ) );
define( 'WC_UNZER_DIRECT_PATH', plugin_dir_path( __FILE__ ) );

add_action( 'plugins_loaded', 'init_unzer_direct_gateway', 0 );

/**
 * Adds notice in case of WooCommerce being inactive
 */
function wc_unzer_direct_woocommerce_inactive_notice() {
	$class    = 'notice notice-error';
	$headline = __( 'WC Unzer Direct requires WooCommerce to be active.', 'wc-unzer-direct' );
	$message  = __( 'Go to the plugins page to activate WooCommerce', 'wc-unzer-direct' );
	printf( '<div class="%1$s"><h2>%2$s</h2><p>%3$s</p></div>', $class, $headline, $message );
}

function init_unzer_direct_gateway() {
	/**
	 * Required functions
	 */
	if ( ! function_exists( 'is_woocommerce_active' ) ) {
		require_once WC_UNZER_DIRECT_PATH . 'woo-includes/woo-functions.php';
	}

	/**
	 * Check if WooCommerce is active, and if it isn't, disable Subscriptions.
	 *
	 * @since 1.0
	 */
	if ( ! is_woocommerce_active() ) {
		add_action( 'admin_notices', 'wc_unzer_direct_woocommerce_inactive_notice' );

		return;
	}

	// Import helper methods
	require_once WC_UNZER_DIRECT_PATH . 'includes/template.php';

	// Import helper classes
	require_once WC_UNZER_DIRECT_PATH . 'helpers/notices.php';
	require_once WC_UNZER_DIRECT_PATH . 'classes/wc-unzer-direct-install.php';
	require_once WC_UNZER_DIRECT_PATH . 'classes/api/wc-unzer-direct-api.php';
	require_once WC_UNZER_DIRECT_PATH . 'classes/api/wc-unzer-direct-api-transaction.php';
	require_once WC_UNZER_DIRECT_PATH . 'classes/api/wc-unzer-direct-api-payment.php';
	require_once WC_UNZER_DIRECT_PATH . 'classes/api/wc-unzer-direct-api-subscription.php';
	require_once WC_UNZER_DIRECT_PATH . 'classes/modules/wc-unzer-direct-module.php';
	require_once WC_UNZER_DIRECT_PATH . 'classes/modules/wc-unzer-direct-emails.php';
	require_once WC_UNZER_DIRECT_PATH . 'classes/modules/wc-unzer-direct-admin-orders.php';
	require_once WC_UNZER_DIRECT_PATH . 'classes/modules/wc-unzer-direct-orders.php';
	require_once WC_UNZER_DIRECT_PATH . 'classes/modules/wc-unzer-direct-subscriptions.php';
	require_once WC_UNZER_DIRECT_PATH . 'classes/wc-unzer-direct-statekeeper.php';
	require_once WC_UNZER_DIRECT_PATH . 'classes/wc-unzer-direct-exceptions.php';
	require_once WC_UNZER_DIRECT_PATH . 'classes/wc-unzer-direct-log.php';
	require_once WC_UNZER_DIRECT_PATH . 'classes/wc-unzer-direct-helper.php';
	require_once WC_UNZER_DIRECT_PATH . 'classes/wc-unzer-direct-address.php';
	require_once WC_UNZER_DIRECT_PATH . 'classes/wc-unzer-direct-settings.php';
	require_once WC_UNZER_DIRECT_PATH . 'classes/wc-unzer-direct-order.php';
	require_once WC_UNZER_DIRECT_PATH . 'classes/wc-unzer-direct-subscription.php';
	require_once WC_UNZER_DIRECT_PATH . 'classes/wc-unzer-direct-countries.php';
	require_once WC_UNZER_DIRECT_PATH . 'classes/wc-unzer-direct-views.php';
	require_once WC_UNZER_DIRECT_PATH . 'classes/wc-unzer-direct-callbacks.php';
	require_once WC_UNZER_DIRECT_PATH . 'helpers/permissions.php';
	require_once WC_UNZER_DIRECT_PATH . 'helpers/transactions.php';

	require_once WC_UNZER_DIRECT_PATH . 'extensions/wpml.php';
	require_once WC_UNZER_DIRECT_PATH . 'extensions/polylang.php';


	// Main class
	class WC_UnzerDirect extends WC_Payment_Gateway {

		/**
		 * $_instance
		 * @var mixed
		 * @access public
		 * @static
		 */
		public static $_instance = null;

		/**
		 * @var WC_UnzerDirect_Log
		 */
		public $log;

		/**
		 * get_instance
		 *
		 * Returns a new instance of self, if it does not already exist.
		 *
		 * @access public
		 * @static
		 * @return WC_UnzerDirect
		 */
		public static function get_instance() {
			if ( null === self::$_instance ) {
				self::$_instance = new self();
			}

			return self::$_instance;
		}


		/**
		 * __construct function.
		 *
		 * The class construct
		 *
		 * @access public
		 * @return void
		 */
		public function __construct() {
			$this->id           = 'unzer_direct';
			$this->method_title = 'Unzer Direct';
			$this->icon         = '';
			$this->has_fields   = true;

			$this->supports = [
				'subscriptions',
				'products',
				'subscription_cancellation',
				'subscription_reactivation',
				'subscription_suspension',
				'subscription_amount_changes',
				'subscription_date_changes',
				'subscription_payment_method_change_admin',
				'subscription_payment_method_change_customer',
				'refunds',
				'multiple_subscriptions',
				'pre-orders',
			];

			$this->log = new WC_UnzerDirect_Log();

			// Load the form fields and settings
			$this->init_form_fields();
			$this->init_settings();

			// Get gateway variables
			$this->title             = $this->s( 'title' );
			$this->description       = $this->s( 'description' );
			$this->instructions      = $this->s( 'instructions' );
			$this->order_button_text = $this->s( 'checkout_button_text' );

			do_action( 'wc_unzer_direct_loaded' );
		}


		/**
		 * filter_load_instances function.
		 *
		 * Loads in extra instances of as separate gateways
		 *
		 * @access public static
		 * @return array
		 */
		public static function filter_load_instances( $methods ) {
			require_once WC_UNZER_DIRECT_PATH . 'classes/instances/instance.php';

			$instances = self::get_gateway_instances();

			foreach ( $instances as $file_name => $class_name ) {
				$file_path = WC_UNZER_DIRECT_PATH . 'classes/instances/' . $file_name . '.php';

				if ( file_exists( $file_path ) ) {
					require_once $file_path;
					$methods[] = $class_name;
				}
			}

			return $methods;
		}

		/**
		 * @return array
		 */
		public static function get_gateway_instances() {
			return [
				'unzer-direct-invoice' => 'WC_UnzerDirect_Invoice',
				'unzer-direct-debit'   => 'WC_UnzerDirect_Debit',
				'google-pay'           => 'WC_UnzerDirect_Google_Pay',
				'apple-pay'            => 'WC_UnzerDirect_Apple_Pay',
				'sofort'               => 'WC_UnzerDirect_Sofort',
				'paypal'               => 'WC_UnzerDirect_PayPal',
				'klarna'               => 'WC_UnzerDirect_Klarna',
			];
		}


		/**
		 * hooks_and_filters function.
		 *
		 * Applies plugin hooks and filters
		 *
		 * @access public
		 * @return string
		 */
		public function hooks_and_filters() {
			WC_UnzerDirect_Admin_Orders::get_instance();
			WC_UnzerDirect_Emails::get_instance();
			WC_UnzerDirect_Orders::get_instance();
			WC_UnzerDirect_Subscriptions::get_instance();


			add_action( 'woocommerce_api_wc_' . $this->id, [ $this, 'callback_handler' ] );
			add_action( 'woocommerce_order_status_completed', [ $this, 'woocommerce_order_status_completed' ] );
			add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, [ $this, 'process_admin_options' ] );

			// WooCommerce Subscriptions hooks/filters
			if ( $this->supports( 'subscriptions' ) ) {
				add_action( 'woocommerce_scheduled_subscription_payment_' . $this->id, [ $this, 'scheduled_subscription_payment' ], 10, 2 );
				add_action( 'woocommerce_subscription_cancelled_' . $this->id, [ $this, 'subscription_cancellation' ] );
				add_action( 'woocommerce_subscription_payment_method_updated_to_' . $this->id, [ $this, 'on_subscription_payment_method_updated_to_unzer_direct', ], 10, 2 );
				add_filter( 'wcs_renewal_order_meta_query', [ $this, 'remove_failed_unzer_direct_attempts_meta_query' ], 10 );
				add_filter( 'wcs_renewal_order_meta_query', [ $this, 'remove_legacy_transaction_id_meta_query' ], 10 );
				add_filter( 'woocommerce_subscription_payment_meta', [ $this, 'woocommerce_subscription_payment_meta' ], 10, 2 );
				add_action( 'woocommerce_subscription_validate_payment_meta_' . $this->id, [ $this, 'woocommerce_subscription_validate_payment_meta', ], 10, 2 );
			}


			// WooCommerce Pre-Orders
			add_action( 'wc_pre_orders_process_pre_order_completion_payment_' . $this->id, [ $this, 'process_pre_order_payments' ] );

			// Make sure not to add these actions multiple times
			if ( ! has_action( 'init', 'WC_UnzerDirect_Helper::load_i18n' ) ) {
				// Custom bulk actions
				add_action( 'admin_footer-edit.php', [ $this, 'register_bulk_actions' ] );
				add_action( 'load-edit.php', [ $this, 'handle_bulk_actions' ] );
				add_action( 'admin_enqueue_scripts', 'WC_UnzerDirect_Helper::enqueue_stylesheet' );
				add_action( 'admin_enqueue_scripts', 'WC_UnzerDirect_Helper::enqueue_javascript_backend' );
				add_action( 'wp_ajax_unzer_direct_manual_transaction_actions', [ $this, 'ajax_unzer_direct_manual_transaction_actions' ] );
				add_action( 'wp_ajax_unzer_direct_empty_logs', [ $this, 'ajax_empty_logs' ] );
				add_action( 'wp_ajax_unzer_direct_flush_cache', [ $this, 'ajax_flush_cache' ] );
				add_action( 'wp_ajax_unzer_direct_ping_api', [ $this, 'ajax_ping_api' ] );
				add_action( 'wp_ajax_unzer_direct_fetch_private_key', [ $this, 'ajax_fetch_private_key' ] );
				add_action( 'wp_ajax_unzer_direct_run_data_upgrader', 'WC_UnzerDirect_Install::ajax_run_upgrader' );

				add_action( 'woocommerce_email_before_order_table', [ $this, 'email_instructions' ], 10, 2 );
				add_action( 'add_meta_boxes', [ $this, 'add_meta_boxes' ] );

				if ( WC_UnzerDirect_Helper::option_is_enabled( $this->s( 'unzer_direct_orders_transaction_info', 'yes' ) ) ) {
					add_filter( 'manage_edit-shop_order_columns', [ $this, 'filter_shop_order_posts_columns' ], 10, 1 );
					add_filter( 'manage_shop_order_posts_custom_column', [ $this, 'apply_custom_order_data' ] );
					add_filter( 'manage_shop_subscription_posts_custom_column', [ $this, 'apply_custom_order_data' ] );
					add_action( 'wc_unzer_direct_accepted_callback', [ $this, 'callback_update_transaction_cache' ], 10, 2 );
				}

				add_action( 'admin_notices', [ $this, 'admin_notices' ] );
			}

			add_action( 'init', 'WC_UnzerDirect_Helper::load_i18n' );
			add_filter( 'woocommerce_gateway_icon', [ $this, 'apply_gateway_icons' ], 2, 3 );

			// Third party plugins
			add_filter( 'qtranslate_language_detect_redirect', 'WC_UnzerDirect_Helper::qtranslate_prevent_redirect', 10, 3 );
			add_filter( 'wpss_misc_form_spam_check_bypass', 'WC_UnzerDirect_Helper::spamshield_bypass_security_check', - 10, 1 );
		}

		/**
		 * s function.
		 *
		 * Returns a setting if set. Introduced to prevent undefined key when introducing new settings.
		 *
		 * @access public
		 *
		 * @param      $key
		 * @param null $default
		 *
		 * @return mixed
		 */
		public function s( $key, $default = null ) {
			if ( isset( $this->settings[ $key ] ) ) {
				return $this->settings[ $key ];
			}

			return apply_filters( 'wc_unzer_direct_get_setting_' . $key, ! is_null( $default ) ? $default : '', $this );
		}

		/**
		 * Hook used to display admin notices
		 */
		public function admin_notices() {
			WC_UnzerDirect_Settings::show_admin_setup_notices();
			WC_UnzerDirect_Install::show_update_warning();
		}


		/**
		 * add_action_links function.
		 *
		 * Adds action links inside the plugin overview
		 *
		 * @access public static
		 * @return array
		 */
		public static function add_action_links( $links ) {
			$links = array_merge( [
				'<a href="' . WC_UnzerDirect_Settings::get_settings_page_url() . '">' . __( 'Settings', 'wc-unzer-direct' ) . '</a>',
			], $links );

			return $links;
		}


		/**
		 * ajax_unzer_direct_manual_transaction_actions function.
		 *
		 * Ajax method taking manual transaction requests from wp-admin.
		 *
		 * @access public
		 * @return void
		 */
		public function ajax_unzer_direct_manual_transaction_actions() {
			if ( isset( $_REQUEST['unzer_direct_action'] ) and isset( $_REQUEST['post'] ) ) {
				$param_action = sanitize_text_field( $_REQUEST['unzer_direct_action'] );
				$param_post   = (int) $_REQUEST['post'];

				if ( ! wc_unzer_direct_can_user_manage_payments( $param_action ) ) {
					printf( 'Your user is not capable of %s payments.', $param_action );
					exit;
				}

				$order = new WC_UnzerDirect_Order( (int) $param_post );

				try {
					$transaction_id = $order->get_transaction_id();

					// Subscription
					if ( WC_UnzerDirect_Subscription::is_subscription( $order ) ) {
						$payment = new WC_UnzerDirect_API_Subscription();
						$payment->get( $transaction_id );
					} // Payment
					else {
						$payment = new WC_UnzerDirect_API_Payment();
						$payment->get( $transaction_id );
					}

					$payment->get( $transaction_id );

					// Based on the current transaction state, we check if
					// the requested action is allowed
					if ( $payment->is_action_allowed( $param_action ) ) {
						// Check if the action method is available in the payment class
						if ( method_exists( $payment, $param_action ) ) {
							// Fetch amount if sent.
							$amount = isset( $_REQUEST['unzer_direct_amount'] ) ? WC_UnzerDirect_Helper::price_custom_to_multiplied( sanitize_text_field( $_REQUEST['unzer_direct_amount'] ), $payment->get_currency() ) : $payment->get_remaining_balance();

							// Call the action method and parse the transaction id and order object
							$payment->$param_action( $transaction_id, $order, WC_UnzerDirect_Helper::price_multiplied_to_float( $amount, $payment->get_currency() ) );
						} else {
							throw new UnzerDirect_API_Exception( sprintf( "Unsupported action: %s.", $param_action ) );
						}
					} // The action was not allowed. Throw an exception
					else {
						throw new UnzerDirect_API_Exception( sprintf( "Action: \"%s\", is not allowed for order #%d, with type state \"%s\"", $param_action, $order->get_clean_order_number(), $payment->get_current_type() ) );
					}
				} catch ( UnzerDirect_Exception $e ) {
					echo $e->getMessage();
					$e->write_to_logs();
					exit;
				} catch ( UnzerDirect_API_Exception $e ) {
					echo $e->getMessage();
					$e->write_to_logs();
					exit;
				}
			}
		}

		/**
		 * ajax_empty_logs function.
		 *
		 * Ajax method to empty the debug logs
		 *
		 * @access public
		 * @return json
		 */
		public function ajax_empty_logs() {
			if ( wc_unzer_direct_can_user_empty_logs() ) {
				$this->log->clear();
				echo json_encode( [ 'status' => 'success', 'message' => 'Logs successfully emptied' ] );
				exit;
			}
		}

		/**
		 * ajax_empty_logs function.
		 *
		 * Ajax method to empty the debug logs
		 *
		 * @access public
		 * @return json
		 */
		public function ajax_flush_cache() {
			global $wpdb;
			if ( wc_unzer_direct_can_user_flush_cache() ) {
				$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_wc_unzer_direct_transaction_%' OR option_name LIKE '_transient_timeout_wc_unzer_direct_transaction_%'" );
				echo json_encode( [ 'status' => 'success', 'message' => 'The transaction cache has been cleared.' ] );
				exit;
			}
		}

		/**
		 * Returns the private key
		 */
		public function ajax_fetch_private_key() {
			try {
				if ( empty( $_POST['api_key'] ) ) {
					throw new \Exception( __( 'Please type in the API key before requesting a private key', 'wc-unzer-direct' ) );
				}

				if ( ! current_user_can( 'manage_woocommerce' ) ) {
					throw new \Exception( __( 'You are not authorized to perform this action.', 'wc-unzer-direct' ) );
				}

				$api_key = sanitize_text_field( $_POST['api_key'] );

				$api = new WC_UnzerDirect_API( $api_key );

				$response = $api->get( 'account/private-key' );
				echo json_encode( [ 'status' => 'success', 'data' => $response ] );
			} catch ( \Exception $e ) {
				echo json_encode( [ 'status' => 'error', 'message' => $e->getMessage() ] );
			}

			exit;

		}

		/**
		 * Checks if an API key is able to connect to the API
		 */
		public function ajax_ping_api() {
			$status = 'error';
			if ( ! empty( $_POST['api_key'] ) ) {
				try {
					$api = new WC_UnzerDirect_API( sanitize_text_field( $_POST['api_key'] ) );
					$api->get( '/payments?page_size=1' );
					$status = 'success';
				} catch ( UnzerDirect_API_Exception $e ) {
					var_dump( $e->getMessage() );
				}
			}
			echo json_encode( [ 'status' => $status ] );
			exit;
		}

		/**
		 * woocommerce_order_status_completed function.
		 *
		 * Captures one or several transactions when order state changes to complete.
		 *
		 * @access public
		 * @return void
		 */
		public function woocommerce_order_status_completed( $post_id ) {
			// Instantiate new order object
			$order = new WC_UnzerDirect_Order( $post_id );

			// Only run logic on the correct instance to avoid multiple calls, or if all extra instances has not been loaded.
			if ( ( WC_UnzerDirect_Statekeeper::$gateways_added && $this->id !== $order->get_payment_method() ) || ! $order->has_unzer_direct_payment() ) {
				return;
			}

			// Check the gateway settings.
			if ( apply_filters( 'wc_unzer_direct_capture_on_order_completion', WC_UnzerDirect_Helper::option_is_enabled( $this->s( 'unzer_direct_captureoncomplete' ) ), $order ) ) {
				// Capture only orders that are actual payments (regular orders / recurring payments)
				if ( ! WC_UnzerDirect_Subscription::is_subscription( $order ) ) {
					$transaction_id = $order->get_transaction_id();
					$payment        = new WC_UnzerDirect_API_Payment();

					// Check if there is a transaction ID
					if ( $transaction_id ) {
						try {
							// Retrieve resource data about the transaction
							$payment->get( $transaction_id );

							// Check if the transaction can be captured
							if ( $payment->is_action_allowed( 'capture' ) ) {

								// In case a payment has been partially captured, we check the balance and subtracts it from the order
								// total to avoid exceptions.
								$amount_multiplied = WC_UnzerDirect_Helper::price_multiply( $order->get_total(), $payment->get_currency() ) - $payment->get_balance();
								$amount            = WC_UnzerDirect_Helper::price_multiplied_to_float( $amount_multiplied, $payment->get_currency() );

								$payment->capture( $transaction_id, $order, $amount );
							}
						} catch ( UnzerDirect_Capture_Exception $e ) {
							wc_unzer_direct_add_runtime_error_notice( $e->getMessage() );
							$order->add_order_note( $e->getMessage() );
							$this->log->add( $e->getMessage() );
						} catch ( \Exception $e ) {
							$error = sprintf( 'Unable to capture payment on order #%s. Problem: %s', $order->get_id(), $e->getMessage() );
							wc_unzer_direct_add_runtime_error_notice( $error );
							$order->add_order_note( $error );
							$this->log->add( $error );
						}
					}
				}
			}
		}


		/**
		 * payment_fields function.
		 *
		 * Prints out the description of the gateway. Also adds two checkboxes for viaBill/creditcard for customers to choose how to pay.
		 *
		 * @access public
		 * @return void
		 */
		public function payment_fields() {
			if ( $this->description ) {
				echo wpautop( wptexturize( $this->description ) );
			}
		}


		/**
		 * Processing payments on checkout
		 *
		 * @param $order_id
		 *
		 * @return array
		 */
		public function process_payment( $order_id ) {
			$order = new WC_UnzerDirect_Order( $order_id );

			return $this->prepare_external_window_payment( $order );
		}

		/**
		 * Processes a payment
		 *
		 * @param WC_UnzerDirect_Order $order
		 *
		 * @return array
		 */
		private function prepare_external_window_payment( $order ) {
			try {

				// Does the order need a new Unzer Direct payment?
				$needs_payment = true;

				// Default redirect to
				$redirect_to = $this->get_return_url( $order );

				// Instantiate a new transaction
				$api_transaction = wc_unzer_direct_get_transaction_instance_by_order( $order );

				// If the order is a subscripion or an attempt of updating the payment method
				if ( $api_transaction instanceof WC_UnzerDirect_API_Subscription ) {
					// Clean up any legacy data regarding old payment links before creating a new payment.
					$order->delete_payment_id();
					$order->delete_payment_link();
				}
				// If the order contains a product switch and does not need a payment, we will skip the Unzer Direct
				// payment window since we do not need to create a new payment nor modify an existing.
				else if ( $order->order_contains_switch() && ! $order->needs_payment() ) {
					$needs_payment = false;
				}

				if ( $needs_payment ) {
					$redirect_to = wc_unzer_direct_create_payment_link( $order );
				}

				// Perform redirect
				return [
					'result'   => 'success',
					'redirect' => $redirect_to,
				];

			} catch ( UnzerDirect_Exception $e ) {
				$e->write_to_logs();
				wc_add_notice( $e->getMessage(), 'error' );
			}
		}

		/**
		 * HOOK: Handles pre-order payments
		 */
		public function process_pre_order_payments( $order ) {
			// Set order object
			$order = new WC_UnzerDirect_Order( $order );

			// Get transaction ID
			$transaction_id = $order->get_transaction_id();

			// Check if there is a transaction ID
			if ( $transaction_id ) {
				try {
					// Set payment object
					$payment = new WC_UnzerDirect_API_Payment();

					// Retrieve resource data about the transaction
					$payment->get( $transaction_id );

					// Check if the transaction can be captured
					if ( $payment->is_action_allowed( 'capture' ) ) {
						try {
							// Capture the payment
							$payment->capture( $transaction_id, $order );
						} // Payment failed
						catch ( UnzerDirect_API_Exception $e ) {
							$this->log->add( sprintf( "Could not process pre-order payment for order: #%s with transaction id: %s. Payment failed. Exception: %s", $order->get_clean_order_number(), $transaction_id, $e->getMessage() ) );

							$order->update_status( 'failed' );
						}
					}
				} catch ( UnzerDirect_API_Exception $e ) {
					$this->log->add( sprintf( "Could not process pre-order payment for order: #%s with transaction id: %s. Transaction not found. Exception: %s", $order->get_clean_order_number(), $transaction_id, $e->getMessage() ) );
				}

			}
		}

		/**
		 * Process refunds
		 * WooCommerce 2.2 or later
		 *
		 * @param int $order_id
		 * @param float $amount
		 * @param string $reason
		 *
		 * @return bool|WP_Error
		 */
		public function process_refund( $order_id, $amount = null, $reason = '' ) {
			try {
				$order = new WC_UnzerDirect_Order( $order_id );

				$transaction_id = $order->get_transaction_id();

				// Check if there is a transaction ID
				if ( ! $transaction_id ) {
					throw new UnzerDirect_Exception( sprintf( __( "No transaction ID for order: %s", 'wc-unzer-direct' ), $order_id ) );
				}

				// Create a payment instance and retrieve transaction information
				$payment = new WC_UnzerDirect_API_Payment();
				$payment->get( $transaction_id );

				// Check if the transaction can be refunded
				if ( ! $payment->is_action_allowed( 'refund' ) ) {
					if ( in_array( $payment->get_current_type(), [ 'authorize', 'recurring' ], true ) ) {
						throw new UnzerDirect_Exception( __( 'A non-captured payment cannot be refunded.', 'wc-unzer-direct' ) );
					} else {
						throw new UnzerDirect_Exception( __( 'Transaction state does not allow refunds.', 'wc-unzer-direct' ) );
					}
				}

				// Perform a refund API request
				$payment->refund( $transaction_id, $order, $amount );

				return true;
			} catch ( UnzerDirect_Exception $e ) {
				$e->write_to_logs();

				return new WP_Error( 'unzer_direct_refund_error', $e->getMessage() );
			}
		}

		/**
		 * Clear cart in case its not already done.
		 *
		 * @return void
		 */
		public function thankyou_page() {
			global $woocommerce;
			$woocommerce->cart->empty_cart();
		}

		/**
		 * scheduled_subscription_payment function.
		 *
		 * Runs every time a scheduled renewal of a subscription is required
		 *
		 * @access public
		 *
		 * @param $amount_to_charge
		 * @param \WC_Order $renewal_order
		 *
		 * @return stdClass
		 */
		public function scheduled_subscription_payment( $amount_to_charge, $renewal_order ) {
			if ( $renewal_order->get_payment_method() === $this->id ) {
				if ( ! $renewal_order instanceof WC_Order ) {
					$renewal_order = new WC_UnzerDirect_Order( $renewal_order );
				}

				if ( $renewal_order->needs_payment() ) {
					// Create subscription instance
					$transaction = new WC_UnzerDirect_API_Subscription();

					/** @var WC_Subscription $subscription */
					// Get the subscription based on the renewal order
					$subscription = WC_UnzerDirect_Subscription::get_subscriptions_for_renewal_order( $renewal_order, $single = true );

					// Make new instance to properly get the transaction ID with built in fallbacks.
					$subscription_order = new WC_UnzerDirect_Order( $subscription->get_id() );

					// Get the transaction ID from the subscription
					$transaction_id = $subscription_order->get_transaction_id();

					// Capture a recurring payment with fixed amount
					$response = $this->process_recurring_payment( $transaction, $transaction_id, $amount_to_charge, $renewal_order );

					do_action( 'wc_unzer_direct_scheduled_subscription_payment_after', $subscription, $renewal_order, $response, $transaction, $transaction_id, $amount_to_charge );

					return $response;
				}
			}
		}


		/**
		 * Wrapper to process a recurring payment on an order/subscription
		 *
		 * @param WC_UnzerDirect_API_Subscription $transaction
		 * @param                              $subscription_transaction_id
		 * @param                              $amount_to_charge
		 * @param                              $order
		 *
		 * @return mixed
		 */
		public function process_recurring_payment( WC_UnzerDirect_API_Subscription $transaction, $subscription_transaction_id, $amount_to_charge, $order ) {
			if ( ! $order instanceof WC_UnzerDirect_Order ) {
				$order = new WC_UnzerDirect_Order( $order );
			}

			$response = null;

			try {
				// Capture a recurring payment with fixed amount
				list( $response ) = $transaction->recurring( $subscription_transaction_id, $order, $amount_to_charge );
			} catch ( UnzerDirect_Exception $e ) {
				$order->increase_failed_unzer_direct_payment_count();

				// Set the payment as failed
				$order->update_status( 'failed', 'Automatic renewal of ' . $order->get_order_number() . ' failed. Message: ' . $e->getMessage() );

				// Write debug information to the logs
				$e->write_to_logs();
			}

			return $response;
		}

		/**
		 * Prevents the failed attempts count to be copied to renewal orders
		 *
		 * @param $order_meta_query
		 *
		 * @return string
		 */
		public function remove_failed_unzer_direct_attempts_meta_query( $order_meta_query ) {
			$order_meta_query .= " AND `meta_key` NOT IN ('" . WC_UnzerDirect_Order::META_FAILED_PAYMENT_COUNT . "')";
			$order_meta_query .= " AND `meta_key` NOT IN ('_unzer_direct_transaction_id')";
			$order_meta_query .= " AND `meta_key` NOT IN ('_transaction_id')";

			return $order_meta_query;
		}

		/**
		 * Prevents the legacy transaction ID from being copied to renewal orders
		 *
		 * @param $order_meta_query
		 *
		 * @return string
		 */
		public function remove_legacy_transaction_id_meta_query( $order_meta_query ) {
			$order_meta_query .= " AND `meta_key` NOT IN ('TRANSACTION_ID')";

			return $order_meta_query;
		}

		/**
		 * Declare gateway's meta data requirements in case of manual payment gateway changes performed by admins.
		 *
		 * @param array $payment_meta
		 *
		 * @param WC_Subscription $subscription
		 *
		 * @return array
		 */
		public function woocommerce_subscription_payment_meta( $payment_meta, $subscription ) {
			$order                        = new WC_UnzerDirect_Order( $subscription->get_id() );
			$payment_meta['unzer_direct'] = [
				'post_meta' => [
					'_unzer_direct_transaction_id' => [
						'value' => $order->get_transaction_id(),
						'label' => __( 'Unzer Direct Transaction ID', 'wc-unzer-direct' ),
					],
				],
			];

			return $payment_meta;
		}

		/**
		 * Check if the transaction ID actually exists as a subscription transaction in the manager.
		 * If not, an exception will be thrown resulting in a validation error.
		 *
		 * @param array $payment_meta
		 *
		 * @param WC_Subscription $subscription
		 *
		 * @throws UnzerDirect_API_Exception
		 */
		public function woocommerce_subscription_validate_payment_meta( $payment_meta, $subscription ) {
			if ( isset( $payment_meta['post_meta']['_unzer_direct_transaction_id']['value'] ) ) {
				$transaction_id = $payment_meta['post_meta']['_unzer_direct_transaction_id']['value'];
				$order          = new WC_UnzerDirect_Order( $subscription->get_id() );

				// Validate only if the transaction ID has changed
				if ( $transaction_id !== $order->get_transaction_id() ) {
					$transaction = new WC_UnzerDirect_API_Subscription();
					$transaction->get( $transaction_id );

					// If transaction could be found, add a note on the order for history and debugging reasons.
					$subscription->add_order_note( sprintf( __( 'Unzer Direct Transaction ID updated from #%d to #%d', 'wc-unzer-direct' ), $order->get_transaction_id(), $transaction_id ), 0, true );
				}
			}
		}

		/**
		 * Triggered when customers are changing payment method to Unzer Direct.
		 *
		 * @param $new_payment_method
		 * @param $subscription
		 * @param $old_payment_method
		 */
		public function on_subscription_payment_method_updated_to_unzer_direct( $subscription, $old_payment_method ) {
			$order = new WC_UnzerDirect_Order( $subscription->get_id() );
			$order->increase_payment_method_change_count();
		}


		/**
		 * subscription_cancellation function.
		 *
		 * Cancels a transaction when the subscription is cancelled
		 *
		 * @access public
		 *
		 * @param WC_Order $order - WC_Order object
		 *
		 * @return void
		 */
		public function subscription_cancellation( $order ) {
			if ( 'cancelled' !== $order->get_status() ) {
				return;
			}

			try {
				if ( WC_UnzerDirect_Subscription::is_subscription( $order ) && apply_filters( 'wc_unzer_direct_allow_subscription_transaction_cancellation', true, $order, $this ) ) {
					$order          = new WC_UnzerDirect_Order( $order );
					$transaction_id = $order->get_transaction_id();

					$subscription = new WC_UnzerDirect_API_Subscription();
					$subscription->get( $transaction_id );

					if ( $subscription->is_action_allowed( 'cancel' ) ) {
						$subscription->cancel( $transaction_id );
					}
				}
			} catch ( UnzerDirect_Exception $e ) {
				$e->write_to_logs();
			} catch ( UnzerDirect_API_Exception $e ) {
				$e->write_to_logs();
			}
		}

		/**
		 * on_order_cancellation function.
		 *
		 * Is called when a customer cancels the payment process from the Unzer Direct payment window.
		 *
		 * @access public
		 * @return void
		 */
		public function on_order_cancellation( $order_id ) {
			$order = new WC_Order( $order_id );

			// Redirect the customer to account page if the current order is failed
			if ( $order->get_status() === 'failed' ) {
				$payment_failure_text = sprintf( __( '<p><strong>Payment failure</strong> A problem with your payment on order <strong>#%i</strong> occured. Please try again to complete your order.</p>', 'wc-unzer-direct' ), $order_id );

				wc_add_notice( $payment_failure_text, 'error' );

				wp_redirect( get_permalink( get_option( 'woocommerce_myaccount_page_id' ) ) );
			}

			$order->add_order_note( __( 'Unzer Direct Payment', 'wc-unzer-direct' ) . ': ' . __( 'Cancelled during process', 'wc-unzer-direct' ) );

			wc_add_notice( __( '<p><strong>%s</strong>: %s</p>', __( 'Payment cancelled', 'wc-unzer-direct' ), __( 'Due to cancellation of your payment, the order process was not completed. Please fulfill the payment to complete your order.', 'wc-unzer-direct' ) ), 'error' );
		}

		/**
		 * callback_handler function.
		 *
		 * Is called after a payment has been submitted in the Unzer Direct payment window.
		 *
		 * @access public
		 * @return void
		 */
		public function callback_handler() {
			// Get callback body
			$request_body = file_get_contents( "php://input" );

			// Decode the body into JSON
			$json = json_decode( $request_body );

			// Instantiate payment object
			$payment = new WC_UnzerDirect_API_Payment( $json );

			// Fetch order number;
			$order_number = WC_UnzerDirect_Order::get_order_id_from_callback( $json );

			// Fetch subscription post ID if present
			$subscription_id = WC_UnzerDirect_Order::get_subscription_id_from_callback( $json );

			if ( ! empty( $subscription_id ) ) {
				$subscription = new WC_UnzerDirect_Order( $subscription_id );
			}

			if ( $payment->is_authorized_callback( $request_body ) ) {
				// Instantiate order object
				$order = wc_get_order( $order_number );

				// Get last transaction in operation history
				$transaction = end( $json->operations );

				// Is the transaction accepted and approved by QP / Acquirer?
				// Did we find an order?
				if ( $json->accepted && $order ) {
					// Overwrite the order object to inherit specific Unzer Direct logic
					$order = new WC_UnzerDirect_Order( $order->get_id() );

					do_action( 'wc_unzer_direct_accepted_callback_before_processing', $order, $json );
					do_action( 'wc_unzer_direct_accepted_callback_before_processing_status_' . $transaction->type, $order, $json );

					// Perform action depending on the operation status type
					try {
						switch ( $transaction->type ) {
							//
							// Cancel callbacks are currently not supported by the Unzer Direct API
							//
							case 'cancel' :
								// Write a note to the order history
								$order->note( __( 'Payment cancelled.', 'wc-unzer-direct' ) );
								break;

							case 'capture' :
								WC_UnzerDirect_Callbacks::payment_captured( $order, $json );
								break;

							case 'refund' :
								$order->note( sprintf( __( 'Refunded %s %s', 'wc-unzer-direct' ), WC_UnzerDirect_Helper::price_normalize( $transaction->amount, $json->currency ), $json->currency ) );
								break;

							case 'recurring':
								WC_UnzerDirect_Callbacks::payment_authorized( $order, $json );
								break;

							case 'authorize' :
								WC_UnzerDirect_Callbacks::authorized( $order, $json );

								// Subscription authorization
								if ( ! empty( $subscription_id ) && isset( $subscription ) ) {
									// Write log
									WC_UnzerDirect_Callbacks::subscription_authorized( $subscription, $order, $json );

								} // Regular payment authorization
								else {
									WC_UnzerDirect_Callbacks::payment_authorized( $order, $json );
								}
								break;
						}

						do_action( 'wc_unzer_direct_accepted_callback', $order, $json );
						do_action( 'wc_unzer_direct_accepted_callback_status_' . $transaction->type, $order, $json );

					} catch ( UnzerDirect_API_Exception $e ) {
						$e->write_to_logs();
					}
				}

				// The transaction was not accepted.
				// Print debug information to logs
				else {
					// Write debug information
					$this->log->separator();
					$this->log->add( sprintf( __( 'Transaction failed for #%s.', 'wc-unzer-direct' ), $order_number ) );
					$this->log->add( sprintf( __( 'Unzer Direct status code: %s.', 'wc-unzer-direct' ), $transaction->qp_status_code ) );
					$this->log->add( sprintf( __( 'Unzer Direct status message: %s.', 'wc-unzer-direct' ), $transaction->qp_status_msg ) );
					$this->log->add( sprintf( __( 'Acquirer status code: %s', 'wc-unzer-direct' ), $transaction->aq_status_code ) );
					$this->log->add( sprintf( __( 'Acquirer status message: %s', 'wc-unzer-direct' ), $transaction->aq_status_msg ) );
					$this->log->add( sprintf( __( 'Data: %s', 'wc-unzer-direct' ), $request_body ) );
					$this->log->separator();

					if ( $order && ( $transaction->type === 'recurring' || 'rejected' !== $json->state ) ) {
						$order->update_status( 'failed', sprintf( 'Payment failed <br />Unzer Direct Message: %s<br />Acquirer Message: %s', $transaction->qp_status_msg, $transaction->aq_status_msg ) );
					}
				}
			} else {
				$this->log->add( sprintf( __( 'Invalid callback body for order #%s.', 'wc-unzer-direct' ), $order_number ) );
			}
		}

		/**
		 * @param WC_UnzerDirect_Order $order
		 * @param                   $json
		 */
		public function callback_update_transaction_cache( $order, $json ) {
			try {
				// Instantiating a payment transaction.
				// The type of transaction is currently not important for caching - hence no logic for handling subscriptions is added.
				$transaction = new WC_UnzerDirect_API_Payment( $json );
				$transaction->cache_transaction();
			} catch ( UnzerDirect_Exception $e ) {
				$this->log->add( sprintf( 'Could not cache transaction from callback for order: #%s -> %s', $order->get_id(), $e->getMessage() ) );
			}
		}

		/**
		 * @param array $form_fields
		 * @param bool $echo
		 *
		 * @return string|void
		 */
		public function generate_settings_html( $form_fields = array(), $echo = true ) {
			$html = sprintf( "<p><small>Version: %s</small>", WC_UNZER_DIRECT_VERSION );
			$html .= "<p>" . sprintf( __( 'Allows you to receive payments via %s', 'wc-unzer-direct' ), $this->get_method_title() ) . "</p>";
			$html .= WC_UnzerDirect_Settings::clear_logs_section();

			ob_start();
			do_action( 'wc_unzer_direct_settings_table_before' );
			$html .= ob_get_clean();

			$html .= parent::generate_settings_html( $form_fields, $echo );

			ob_start();
			do_action( 'wc_unzer_direct_settings_table_after' );
			$html .= ob_get_clean();

			if ( $echo ) {
				echo $html; // WPCS: XSS ok.
			} else {
				return $html;
			}
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
			$this->form_fields = WC_UnzerDirect_Settings::get_fields();
		}


		/**
		 * add_meta_boxes function.
		 *
		 * Adds the action meta box inside the single order view.
		 *
		 * @access public
		 * @return void
		 */
		public function add_meta_boxes() {
			global $post;

			$screen     = get_current_screen();
			$post_types = [ 'shop_order', 'shop_subscription' ];

			if ( in_array( $screen->id, $post_types, true ) && in_array( $post->post_type, $post_types, true ) ) {
				$order = new WC_UnzerDirect_Order( $post->ID );
				if ( $order->has_unzer_direct_payment() ) {
					add_meta_box( 'unzer-direct-payment-actions', __( 'Unzer Direct Payment', 'wc-unzer-direct' ), [
						&$this,
						'meta_box_payment',
					], 'shop_order', 'side', 'high' );
					add_meta_box( 'unzer-direct-payment-actions', __( 'Unzer Direct Subscription', 'wc-unzer-direct' ), [
						&$this,
						'meta_box_subscription',
					], 'shop_subscription', 'side', 'high' );
				}
			}
		}


		/**
		 * meta_box_payment function.
		 *
		 * Inserts the content of the API actions meta box - Payments
		 *
		 * @access public
		 * @return void
		 */
		public function meta_box_payment() {
			global $post;
			$order = new WC_UnzerDirect_Order( $post->ID );

			$transaction_id = $order->get_transaction_id();

			do_action( 'wc_unzer_direct_meta_box_payment_before_content', $order );
			if ( $transaction_id && $order->has_unzer_direct_payment() ) {
				$state = null;
				try {
					$transaction = new WC_UnzerDirect_API_Payment();
					$transaction->get( $transaction_id );
					$transaction->cache_transaction();

					$state = $transaction->get_state();

					try {
						$status = $transaction->get_current_type();
					} catch ( UnzerDirect_API_Exception $e ) {
						if ( $state !== 'initial' ) {
							throw new UnzerDirect_API_Exception( $e->getMessage() );
						}

						$status = $state;
					}

					echo "<p class=\"wc-unzer-direct-{$status}\"><strong>" . __( 'Current payment state', 'wc-unzer-direct' ) . ": " . $status . "</strong></p>";

					if ( $transaction->is_action_allowed( 'standard_actions' ) ) {
						echo "<h4><strong>" . __( 'Actions', 'wc-unzer-direct' ) . "</strong></h4>";
						echo "<ul class=\"order_action\">";

						if ( $transaction->is_action_allowed( 'capture' ) ) {
							echo "<li class=\"unzer-direct-full-width\"><a class=\"button button-primary\" data-action=\"capture\" data-confirm=\"" . __( 'You are about to capture this payment', 'wc-unzer-direct' ) . "\">" . sprintf( __( 'Capture Full Amount (%s)', 'wc-unzer-direct' ), wc_price( $transaction->get_remaining_balance_as_float(), [ 'currency' => $transaction->get_currency() ] ) ) . "</a></li>";
						}

						printf( "<li class=\"unzer-direct-balance\"><span class=\"unzer-direct-balance__label\">%s:</span><span class=\"unzer-direct-balance__amount\"><span class='unzer-direct-balance__currency'>%s</span>%s</span></li>", __( 'Remaining balance', 'wc-unzer-direct' ), $transaction->get_currency(), $transaction->get_formatted_remaining_balance() );

						if ( $transaction->is_action_allowed( 'capture' ) ) {
							printf( "<li class=\"unzer-direct-balance last\"><span class=\"unzer-direct-balance__label\">%s:</span><span class=\"unzer-direct-balance__amount\"><span class='unzer-direct-balance__currency'>%s</span><input id='unzer-direct-balance__amount-field' type='text' value='%s' /></span></li>", __( 'Capture amount', 'wc-unzer-direct' ), $transaction->get_currency(), $transaction->get_formatted_remaining_balance() );
							echo "<li class=\"unzer-direct-full-width\"><a class=\"button\" data-action=\"captureAmount\" data-confirm=\"" . __( 'You are about to capture this payment', 'wc-unzer-direct' ) . "\">" . __( 'Capture specified amount', 'wc-unzer-direct' ) . "</a></li>";
						}

						if ( $transaction->is_action_allowed( 'cancel' ) ) {
							echo "<li class=\"unzer-direct-full-width\"><a class=\"button\" data-action=\"cancel\" data-confirm=\"" . __( 'You are about to cancel this payment', 'wc-unzer-direct' ) . "\">" . __( 'Cancel', 'wc-unzer-direct' ) . "</a></li>";
						}

						echo "</ul>";
					}

					printf( '<p><small><strong>%s:</strong> %d <span class="unzer-direct-meta-card"><img src="%s" /></span></small>', __( 'Transaction ID', 'wc-unzer-direct' ), $transaction_id, WC_UnzerDirect_Helper::get_payment_type_logo( $transaction->get_brand() ) );

					$transaction_order_id = $order->get_transaction_order_id();
					if ( isset( $transaction_order_id ) && ! empty( $transaction_order_id ) ) {
						printf( '<p><small><strong>%s:</strong> %s</small>', __( 'Transaction Order ID', 'wc-unzer-direct' ), $transaction_order_id );
					}
				} catch ( UnzerDirect_API_Exception $e ) {
					$e->write_to_logs();
					if ( $state !== 'initial' ) {
						$e->write_standard_warning();
					}
				} catch ( UnzerDirect_Exception $e ) {
					$e->write_to_logs();
					if ( $state !== 'initial' ) {
						$e->write_standard_warning();
					}
				}
			}

			// Show payment ID and payment link for orders that have not yet
			// been paid. Show this information even if the transaction ID is missing.
			$payment_id = $order->get_payment_id();
			if ( isset( $payment_id ) && ! empty( $payment_id ) ) {
				printf( '<p><small><strong>%s:</strong> %d</small>', __( 'Payment ID', 'wc-unzer-direct' ), $payment_id );
			}

			$payment_link = $order->get_payment_link();
			if ( isset( $payment_link ) && ! empty( $payment_link ) ) {
				printf( '<p><small><strong>%s:</strong> <br /><input type="text" style="%s"value="%s" readonly /></small></p>', __( 'Payment Link', 'wc-unzer-direct' ), 'width:100%', $payment_link );
			}

			do_action( 'wc_unzer_direct_meta_box_payment_after_content', $order );
		}


		/**
		 * meta_box_payment function.
		 *
		 * Inserts the content of the API actions meta box - Subscriptions
		 *
		 * @access public
		 * @return void
		 */
		public function meta_box_subscription() {
			global $post;
			$order = new WC_UnzerDirect_Order( $post->ID );

			$transaction_id = $order->get_transaction_id();
			$state          = null;

			do_action( 'wc_unzer_direct_meta_box_subscription_before_content', $order );

			if ( $transaction_id && $order->has_unzer_direct_payment() ) {
				try {

					$transaction = new WC_UnzerDirect_API_Subscription();
					$transaction->get( $transaction_id );
					$status = null;
					$state  = $transaction->get_state();
					try {
						$status = $transaction->get_current_type() . ' (' . __( 'subscription', 'wc-unzer-direct' ) . ')';
					} catch ( UnzerDirect_API_Exception $e ) {
						if ( 'initial' !== $state ) {
							throw new UnzerDirect_API_Exception( $e->getMessage() );
						}
						$status = $state;
					}

					echo "<p class=\"wc-unzer-direct-{$status}\"><strong>" . __( 'Current payment state', 'wc-unzer-direct' ) . ": " . $status . "</strong></p>";

					printf( '<p><small><strong>%s:</strong> %d <span class="unzer-direct-meta-card"><img src="%s" /></span></small>', __( 'Transaction ID', 'wc-unzer-direct' ), $transaction_id, WC_UnzerDirect_Helper::get_payment_type_logo( $transaction->get_brand() ) );

					$transaction_order_id = $order->get_transaction_order_id();
					if ( isset( $transaction_order_id ) && ! empty( $transaction_order_id ) ) {
						printf( '<p><small><strong>%s:</strong> %s</small>', __( 'Transaction Order ID', 'wc-unzer-direct' ), $transaction_order_id );
					}
				} catch ( UnzerDirect_API_Exception $e ) {
					$e->write_to_logs();
					if ( 'initial' !== $state ) {
						$e->write_standard_warning();
					}
				}
			}

			do_action( 'wc_unzer_direct_meta_box_subscription_after_content', $order );
		}


		/**
		 * email_instructions function.
		 *
		 * Adds custom text to the order confirmation email.
		 *
		 * @access public
		 *
		 * @param WC_Order $order
		 * @param boolean $sent_to_admin
		 *
		 * @return bool /string/void
		 */
		public function email_instructions( $order, $sent_to_admin ) {
			$payment_method = $order->get_payment_method();

			if ( $sent_to_admin || ( $order->get_status() !== 'processing' && $order->get_status() !== 'completed' ) || $payment_method !== 'unzer_durect' ) {
				return;
			}

			if ( $this->instructions ) {
				echo wpautop( wptexturize( $this->instructions ) );
			}
		}

		/**
		 * Adds a separate column for payment info
		 *
		 * @param array $show_columns
		 *
		 * @return array
		 */
		public function filter_shop_order_posts_columns( $show_columns ) {
			$column_name   = 'unzer_direct_transaction_info';
			$column_header = __( 'Payment', 'wc-unzer-direct' );

			return WC_UnzerDirect_Helper::array_insert_after( 'shipping_address', $show_columns, $column_name, $column_header );
		}

		/**
		 * apply_custom_order_data function.
		 *
		 * Applies transaction ID and state to the order data overview
		 *
		 * @access public
		 * @return void
		 */
		public function apply_custom_order_data( $column ) {
			global $post, $woocommerce;

			$order = new WC_UnzerDirect_Order( $post->ID );

			// Show transaction ID on the overview
			if ( ( $post->post_type == 'shop_order' && $column == 'unzer_direct_transaction_info' ) || ( $post->post_type == 'shop_subscription' && $column == 'order_title' ) ) {
				// Insert transaction id and payment status if any
				$transaction_id = $order->get_transaction_id();

				try {
					if ( $transaction_id && $order->has_unzer_direct_payment() ) {

						if ( WC_UnzerDirect_Subscription::is_subscription( $post->ID ) ) {
							$transaction = new WC_UnzerDirect_API_Subscription();
						} else {
							$transaction = new WC_UnzerDirect_API_Payment();
						}

						// Get transaction data
						$transaction->maybe_load_transaction_from_cache( $transaction_id );

						if ( $order->subscription_is_renewal_failure() ) {
							$status = __( 'Failed renewal', 'wc-unzer-direct' );
						} else {
							$status = $transaction->get_current_type();
						}

						$brand = $transaction->get_brand();

						WC_UnzerDirect_Views::get_view( 'html-order-table-transaction-data.php', [
							'transaction_id'             => $transaction_id,
							'transaction_order_id'       => $order->get_transaction_order_id(),
							'transaction_brand'          => $transaction->get_brand(),
							'transaction_brand_logo_url' => WC_UnzerDirect_Helper::get_payment_type_logo( $brand ? $brand : $transaction->get_acquirer() ),
							'transaction_status'         => $status,
							'transaction_is_test'        => $transaction->is_test(),
							'is_cached'                  => $transaction->is_loaded_from_cached(),
						] );
					}
				} catch ( UnzerDirect_API_Exception $e ) {
					$this->log->add( sprintf( 'Order list: #%s - %s', $order->get_id(), $e->getMessage() ) );
				} catch ( UnzerDirect_Exception $e ) {
					$this->log->add( sprintf( 'Order list: #%s - %s', $order->get_id(), $e->getMessage() ) );
				}

			}
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
				$icon = '';

				$icons = $this->s( 'unzer_direct_icons' );

				if ( ! empty( $icons ) ) {
					$icons_maxheight = $this->gateway_icon_size();

					foreach ( $icons as $key => $item ) {
						$icon .= $this->gateway_icon_create( $item, $icons_maxheight );
					}
				}
			}

			return $icon;
		}


		/**
		 * gateway_icon_create
		 *
		 * Helper to get the a gateway icon image tag
		 *
		 * @access protected
		 * @return string
		 */
		protected function gateway_icon_create( $icon, $max_height ) {
			$icon = apply_filters( 'wc_unzer_direct_checkout_gateway_icon', str_replace( 'unzer_direct_', '', $icon ) );

			if ( file_exists( __DIR__ . '/assets/images/cards/' . $icon . '.svg' ) ) {
				$icon_url = $icon_url = WC_HTTPS::force_https_url( plugin_dir_url( __FILE__ ) . 'assets/images/cards/' . $icon . '.svg' );
			} else {
				$icon_url = WC_HTTPS::force_https_url( plugin_dir_url( __FILE__ ) . 'assets/images/cards/' . $icon . '.png' );
			}

			$icon_url = apply_filters( 'wc_unzer_direct_checkout_gateway_icon_url', $icon_url, $icon );

			return '<img src="' . $icon_url . '" alt="' . esc_attr( $this->get_title() ) . '" style="max-height:' . $max_height . '"/>';
		}


		/**
		 * gateway_icon_size
		 *
		 * Helper to get the a gateway icon image max height
		 *
		 * @access protected
		 * @return void
		 */
		protected function gateway_icon_size() {
			$settings_icons_maxheight = $this->s( 'unzer_direct_icons_maxheight' );

			return ! empty( $settings_icons_maxheight ) ? $settings_icons_maxheight . 'px' : '20px';
		}

		/**
		 * Registers custom bulk actions
		 */
		public function register_bulk_actions() {
			global $post_type;

			if ( $post_type === 'shop_order' && WC_UnzerDirect_Subscription::plugin_is_active() ) {
				WC_UnzerDirect_Views::get_view( 'bulk-actions.php' );
			}
		}

		/**
		 * Handles custom bulk actions
		 */
		public function handle_bulk_actions() {
			$wp_list_table = _get_list_table( 'WP_Posts_List_Table' );

			$action = $wp_list_table->current_action();

			// Check for posts
			if ( ! empty( $_GET['post'] ) ) {
				$order_ids = sanitize_text_field( $_GET['post'] );

				// Make sure the $posts variable is an array
				if ( ! is_array( $order_ids ) ) {
					$order_ids = [ $order_ids ];
				}
			}

			if ( current_user_can( 'manage_woocommerce' ) ) {
				switch ( $action ) {
					// 3. Perform the action
					case 'unzer_direct_capture_recurring':
						// Security check
						$this->bulk_action_unzer_direct_capture_recurring( $order_ids );

						// Redirect client
						wp_redirect( $_SERVER['HTTP_REFERER'] );
						exit;
						break;

					default:
						return;
				}
			}
		}

		/**
		 * @param array $order_ids
		 */
		public function bulk_action_unzer_direct_capture_recurring( $order_ids = [] ) {
			if ( ! empty( $order_ids ) ) {
				foreach ( $order_ids as $order_id ) {
					$order          = new WC_UnzerDirect_Order( $order_id );
					$payment_method = $order->get_payment_method();
					if ( $payment_method === $this->id && WC_UnzerDirect_Subscription::is_renewal( $order ) && $order->needs_payment() ) {
						$this->scheduled_subscription_payment( $order->get_total(), $order );
					}
				}
			}

		}

		public function reset_failed_a( $order_id ) {
			// Instantiate new order object
			$order = new WC_UnzerDirect_Order( $order_id );
		}

		/**
		 * path
		 *
		 * Returns a plugin URL path
		 *
		 * @param $path
		 *
		 * @return mixed
		 */
		public function plugin_url( $path ) {
			return plugins_url( $path, __FILE__ );
		}
	}

	/**
	 * Make the object available for later use
	 *
	 * @return WC_UnzerDirect
	 */
	function WC_UNZER_DIRECT() {
		return WC_UnzerDirect::get_instance();
	}

	// Instantiate
	WC_UNZER_DIRECT();
	WC_UNZER_DIRECT()->hooks_and_filters();

	// Add the gateway to WooCommerce
	function add_unzer_direct_gateway( $methods ) {
		$methods[] = 'WC_UnzerDirect';

		WC_UnzerDirect_Statekeeper::$gateways_added = true;

		return apply_filters( 'wc_unzer_direct_load_instances', $methods );
	}

	add_filter( 'woocommerce_payment_gateways', 'add_unzer_direct_gateway' );
	add_filter( 'wc_unzer_direct_load_instances', 'WC_UnzerDirect::filter_load_instances' );
	add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'WC_UnzerDirect::add_action_links' );
}

/**
 * Run installer
 *
 * @param string __FILE__ - The current file
 * @param function - Do the installer/update logic.
 */
register_activation_hook( __FILE__, function () {
	require_once WC_UNZER_DIRECT_PATH . 'classes/wc-unzer-direct-install.php';

	// Run the installer on the first install.
	if ( WC_UnzerDirect_Install::is_first_install() ) {
		WC_UnzerDirect_Install::install();
	}
} );
