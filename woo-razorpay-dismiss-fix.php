<?php
/**
 * Plugin Name: Razorpay Popup Dismiss Fix
 * Description: Redirects the customer back to the checkout page if they close the Razorpay payment popup without completing the payment.
 * Version: 1.0.1
 * Author: Deepaman
 * Author URI: https://github.com/deepaman2626
 * GitHub Plugin URI: https://github.com/deepaman2626/woo-razorpay-dismiss-fix
 * Primary Branch: main
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Razorpay_Dismiss_Fix {
    public function __construct() {
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ), 20 );
    }

    public function enqueue_scripts() {
        // Only run on the checkout or order-pay page
        if ( ! is_checkout() && ! is_add_payment_method_page() && ! isset( $_GET['pay_for_order'] ) ) {
            return;
        }

        // Add inline script to intercept Razorpay constructor
        // We use 'razorpay_checkout' as the handle because it's the dependency for the main plugin's script
        wp_add_inline_script( 'razorpay_checkout', "
            (function() {
                var OriginalRazorpay = window.Razorpay;
                window.Razorpay = function(options) {
                    var originalDismiss = options.modal ? options.modal.ondismiss : null;
                    options.modal = options.modal || {};
                    options.modal.ondismiss = function() {
                        if (typeof originalDismiss === 'function') {
                            originalDismiss();
                        }
                        
                        // Check if payment was already successful (payment_id would be set by handler)
                        var paymentIdField = document.getElementById('razorpay_payment_id');
                        if (paymentIdField && paymentIdField.value) {
                            return;
                        }

                        // If the customer closes the popup, we want to treat it as a cancellation
                        // The main Razorpay plugin has a 'Cancel' button that submits a form.
                        // We will try to trigger that form submission to handle backend status and redirect.
                        
                        var cancelBtn = document.getElementById('btn-razorpay-cancel');
                        if (cancelBtn) {
                            cancelBtn.click();
                        } else {
                            // Fallback: manually submit the razorpayform if it exists
                            var form = document.forms['razorpayform'];
                            if (form) {
                                // Add a hidden field to indicate this was a dismissal if needed
                                // but the main plugin already handles empty payment_id as cancellation
                                form.submit();
                            } else if (options.cancel_url) {
                                // Ultimate fallback: redirect to cancel_url
                                window.location.href = options.cancel_url;
                            }
                        }
                    };
                    return new OriginalRazorpay(options);
                };
            })();
        ", 'after' );
    }
}

new Razorpay_Dismiss_Fix();
