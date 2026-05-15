=== Razorpay Popup Dismiss Fix ===
Contributors: appcoderikbal
Tags: woocommerce, razorpay, payment, checkout
Requires at least: 5.0
Tested up to: 6.4
Stable tag: 1.0.2
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Redirects the customer back to the checkout page if they close the Razorpay payment popup without completing the payment.

== Description ==

This plugin fixes a common confusion in the Razorpay WooCommerce integration where closing the payment modal leaves the customer on the "Order Pay" page. It intercepts the dismissal and redirects the user back to the checkout page with a failure message.

== Installation ==

1. Upload the `woo-razorpay-dismiss-fix` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.

== Changelog ==

= 1.0.2 =
* Fixed dismissal logic by replacing script.js with a more robust implementation.

= 1.0.1 =
* Initial release with GitHub versioning support and popup dismissal interceptor.
