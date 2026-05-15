<?php
/**
 * Plugin Name: Razorpay Popup Dismiss Fix
 * Description: Redirects the customer back to the checkout page if they close the Razorpay payment popup without completing the payment.
 * Version: 1.0.2
 * Author: appcoderikbal
 * Author URI: https://github.com/appcoderikbal
 * GitHub Plugin URI: https://github.com/appcoderikbal/woo-razorpay-dismiss-fix
 * Primary Branch: main
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Razorpay_Dismiss_Fix {
    public function __construct() {
        // We use a high priority to ensure our dequeue/enqueue happens after the main plugin
        add_action( 'wp_enqueue_scripts', array( $this, 'replace_razorpay_script' ), 999 );
    }

    public function replace_razorpay_script() {
        // Only run on checkout or order-pay pages
        if ( ! is_checkout() && ! is_add_payment_method_page() && ! isset( $_GET['pay_for_order'] ) ) {
            return;
        }

        // Dequeue the original script from the main Razorpay plugin
        wp_dequeue_script( 'razorpay_wc_script' );

        // Register and enqueue our fixed version
        wp_register_script(
            'razorpay_wc_script_fixed',
            plugin_dir_url( __FILE__ ) . 'script.js',
            array( 'razorpay_checkout', 'jquery' ),
            '1.0.2'
        );

        wp_enqueue_script( 'razorpay_wc_script_fixed' );
    }
}

new Razorpay_Dismiss_Fix();
