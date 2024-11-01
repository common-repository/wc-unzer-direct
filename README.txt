=== Unzer Direct for WooCommerce ===
Contributors: PerfectSolution
Tags: gateway, woo commerce, unzer, unzer direct, unzer-direct, unzerdirect, woocommerce unzer, gateway, integration, woocommerce, woocommerce, payment, payment gateway, psp
Requires at least: 4.0.0
Requires PHP: 5.4
Tested up to: 5.9
Stable tag: trunk
License: GPLv2
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Integrates your Unzer Direct payment gateway into your WooCommerce installation.

== Description ==
With this plugin, you are able to integrate your Unzer Direct gateway to your WooCommerce install. With a wide list of API features including secure capturing, refunding and cancelling payments directly from your WooCommerce order overview. This is only a part of the many features found in this plugin.

== Installation ==
1. Upload the 'wc-unzer-direct' folder to /wp-content/plugins/ on your server.
2. Log in to WordPress administration, click on the 'Plugins' tab.
3. Find WC Unzer Direct in the plugin overview and activate it.
4. Go to WooCommerce -> Settings -> Payment Gateways -> Unzer Direct.
5. Fill in all the fields in the "Unzer Direct account" section and save the settings.
6. You are good to go.

== Frequently Asked Questions ==

== Screenshots ==

== Dependencies ==
General:
1. PHP: >= 5.4
2. WooCommerce >= 3.0

== Changelog ==
= 1.4.4 =
* Fix: Disable Unzer Denit Invoice if country is not CH as well.
* Feat: Hide Unzer specific gateways if "Ship to different address" is enabled since the addresses must match.

= 1.4.3 =
* Fix: Remove VISA Electron card logo
* Feat: Hide Unzer Direct Invoice if the currency is not EUR or CHF, and if the cart total is not between 10-3500 EUR/CHF.
* Fix: Remove obsolete card logos
* Feat: Updated payment method logos with new vectors
* Feat: Improved UI in the credit card module, which now only shows VISA, Maestro and Mastercard as possible card logos.
* Fix: Reintroduce 'Unzer Direct' as prefix to default gateway names
* Fix: Upgrade default descriptions to all gateways

= 1.4.2 =
* Fix: Fatal error: Uncaught ValueError: Missing format specifier at end of string

= 1.4.1 =
* Fix: Rename Unzer Direct Invoice to Unzer Invoice

= 1.4.0 =
* Feat: Add Google Pay as payment gateway
* Feat: Add Sofort as payment gateway
* Feat: Add Unzer Direct Invoice (pay later) as payment gateway
* Fix: Adjust SVG icons for PayPal, Apple Pay and Klarna to show properly in Safari
* Feat: Only show Apple Pay in Safari browsers
* Fix: Remove Unzer Direct prefixes from payment gateway titles
* Fix: Payment methods filter not being properly triggered on other instances

= 1.3.0 =
* Feat: Add Apple Pay gateway - works only in Safari.
* Feat: Show a more user-friendly error message when payments fail in the callback handler.
* Dev: Add new filter woocommerce_quickpay_checkout_gateway_icon
* Fix: Gateway icons were attempted fetched with unzer_direct in the file name. This part is now sanitized before passed to woocommerce_quickpay_checkout_gateway_icon.
* Fix: Bump WC + WP tested with versions to latest versions

= 1.2.0 =
* Remove: Payment option Bitcoin
* Remove: Payment option Forbrugsforeningen
* Remove: Payment option iDEAL
* Remove: Payment option Resurs
* Remove: Payment option Sofort
* Remove: Payment option Swish
* Remove: Payment option Trustly
* Remove: Payment option ViaBill
* Remove: Payment option Vipps

= 1.1.0 =
* Feature: New setting 'Cancel payments on order cancellation' allows merchants to automatically cancel payments when an order is cancelled. Disabled by default.
* Fix: Orders with multiple subscriptions didn't get the subscription transaction stored on every subscription.

= 1.0.1 =
* Bump 'tested with' versions

= 1.0.0 =
* First release

== Upgrade Notice ==
