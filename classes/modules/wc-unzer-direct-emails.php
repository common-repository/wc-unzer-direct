<?php

/**
 * Class WC_UnzerDirect_Emails
 */
class WC_UnzerDirect_Emails extends WC_UnzerDirect_Module {

	/** @var null|static */
	protected static $_instance;

	/**
	 * Perform actions and filters
	 *
	 * @return mixed
	 */
	public function hooks() {
		add_filter( 'woocommerce_email_classes', [ $this, 'emails' ], 10, 1 );
		add_action( 'wc_unzer_direct_order_action_payment_link_created', [ $this, 'send_customer_payment_link' ], 1, 2 );
	}

	/**
	 * Add support for custom emails
	 *
	 * @param $emails
	 *
	 * @return mixed
	 */
	public function emails( $emails ) {
		require_once WC_UNZER_DIRECT_PATH . 'classes/emails/wc-unzer-direct-payment-link-email.php';

		$emails['WC_UnzerDirect_Payment_Link_Email'] = new WC_UnzerDirect_Payment_Link_Email();

		return $emails;
	}

	/**
	 * Make sure the mailer is loaded in order to load e-mails.
	 *
	 * @param $payment_link
	 * @param $order
	 */
	public function send_customer_payment_link( $payment_link, $order ) {
		/** @var WC_UnzerDirect_Payment_Link_Email $mail */
		$mail = wc()->mailer()->emails['WC_UnzerDirect_Payment_Link_Email'];

		if ( $mail ) {
			$mail->trigger( $payment_link, $order );
		}
	}
}
