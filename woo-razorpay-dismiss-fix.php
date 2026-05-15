<?php
/**
 * Plugin Name: Razorpay Popup Dismiss Fix
 * Description: Redirects the customer back to the checkout page if they close the Razorpay payment popup without completing the payment.
 * Version: 1.0.3
 * Author: appcoderikbal
 * Author URI: https://github.com/appcoderikbal
 * GitHub Plugin URI: https://github.com/appcoderikbal/woo-razorpay-dismiss-fix
 * GitHub Repo: appcoderikbal/woo-razorpay-dismiss-fix
 * Primary Branch: main
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Razorpay_Dismiss_Fix {
    public function __construct() {
        // Replace the original script
        add_action( 'wp_enqueue_scripts', array( $this, 'replace_razorpay_script' ), 999 );
        
        // Initialize the update checker
        if ( is_admin() ) {
            $this->init_updater();
        }
    }

    public function replace_razorpay_script() {
        if ( ! is_checkout() && ! is_add_payment_method_page() && ! isset( $_GET['pay_for_order'] ) ) {
            return;
        }

        wp_dequeue_script( 'razorpay_wc_script' );
        wp_register_script(
            'razorpay_wc_script_fixed',
            plugin_dir_url( __FILE__ ) . 'script.js',
            array( 'razorpay_checkout', 'jquery' ),
            '1.0.3'
        );
        wp_enqueue_script( 'razorpay_wc_script_fixed' );
    }

    private function init_updater() {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
        
        $username = 'appcoderikbal';
        $repo     = 'woo-razorpay-dismiss-fix';
        $plugin_file = __FILE__;
        $basename = plugin_basename( $plugin_file );

        // Filter to check for updates
        add_filter( 'pre_set_site_transient_update_plugins', function( $transient ) use ( $username, $repo, $basename, $plugin_file ) {
            if ( empty( $transient->checked ) ) {
                return $transient;
            }

            // Cache the GitHub response for 12 hours
            $cache_key = 'rzp_dismiss_fix_update_check';
            $remote_data = get_transient( $cache_key );

            if ( false === $remote_data ) {
                $url = "https://api.github.com/repos/{$username}/{$repo}/releases/latest";
                $response = wp_remote_get( $url, array( 'user-agent' => 'WordPress/' . get_bloginfo( 'version' ) . '; ' . get_bloginfo( 'url' ) ) );

                if ( ! is_wp_error( $response ) ) {
                    $remote_data = json_decode( wp_remote_retrieve_body( $response ), true );
                    set_transient( $cache_key, $remote_data, 12 * HOUR_IN_SECONDS );
                }
            }

            if ( ! empty( $remote_data['tag_name'] ) ) {
                $plugin_data = get_plugin_data( $plugin_file );
                $current_version = $plugin_data['Version'];
                $remote_version = str_replace( 'v', '', $remote_data['tag_name'] );

                if ( version_compare( $remote_version, $current_version, '>' ) ) {
                    $res = new stdClass();
                    $res->slug = $basename;
                    $res->plugin = $basename;
                    $res->new_version = $remote_version;
                    $res->url = "https://github.com/{$username}/{$repo}";
                    $res->package = $remote_data['zipball_url'];
                    
                    $transient->response[ $basename ] = $res;
                }
            }

            return $transient;
        });

        // Filter to show plugin info in the popup
        add_filter( 'plugins_api', function( $res, $action, $args ) use ( $basename, $username, $repo ) {
            if ( 'plugin_information' !== $action || $args->slug !== $basename ) {
                return $res;
            }

            $cache_key = 'rzp_dismiss_fix_update_check';
            $remote_data = get_transient( $cache_key );

            if ( $remote_data ) {
                $res = new stdClass();
                $res->name = 'Razorpay Popup Dismiss Fix';
                $res->slug = $basename;
                $res->version = str_replace( 'v', '', $remote_data['tag_name'] );
                $res->author = '<a href="https://github.com/appcoderikbal">appcoderikbal</a>';
                $res->homepage = "https://github.com/{$username}/{$repo}";
                $res->sections = array(
                    'description' => 'Redirects the customer back to the checkout page if they close the Razorpay payment popup without completing the payment.',
                    'changelog'   => $remote_data['body'] ?? 'No changelog available.',
                );
                $res->download_link = $remote_data['zipball_url'];
                return $res;
            }

            return $res;
        }, 10, 3);
    }
}

new Razorpay_Dismiss_Fix();
