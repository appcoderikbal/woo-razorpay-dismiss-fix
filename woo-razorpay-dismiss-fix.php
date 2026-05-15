<?php
/**
 * Plugin Name: Razorpay Popup Dismiss Fix
 * Description: Redirects the customer back to the checkout page if they close the Razorpay payment popup without completing the payment.
 * Version: 1.0.6
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

        // Hide confusing order information and "Thank you" messages via CSS
        add_action( 'wp_head', function() {
            ?>
            <style id="rzp-dismiss-fix-css">
                /* Hide ALL order details on the order-pay page */
                ul.order_details,
                ul.woocommerce-order-overview,
                .woocommerce-order-overview,
                .order_details,
                .woocommerce-order-details,
                .woocommerce-customer-details,
                .woocommerce-table--order-details,
                table.order_details,
                table.shop_table.order_details,
                /* Hide the "Thank you" and order text */
                .woocommerce-thankyou-order-received,
                .woocommerce-notice--success,
                /* Target the specific "Thank you for your order" paragraph */
                .woocommerce-order p:not(:has(button)),
                .woocommerce-checkout p:not(:has(button)),
                /* Hide the order pay heading if it shows order info */
                .wc-order-pay-heading {
                    display: none !important;
                }
                /* Ensure Pay Now button is prominent */
                #btn-razorpay {
                    background-color: #3399cc;
                    color: #fff;
                    padding: 12px 30px;
                    border: none;
                    border-radius: 4px;
                    font-size: 16px;
                    cursor: pointer;
                    margin-top: 20px;
                }
                #btn-razorpay:hover {
                    background-color: #2980b9;
                }
                #btn-razorpay-cancel,
                #msg-razorpay-success {
                    display: none !important;
                }
            </style>
            <script>
                // Extra JS-based removal for elements CSS :has() may not cover
                document.addEventListener('DOMContentLoaded', function() {
                    // Hide the order details list
                    var orderDetails = document.querySelectorAll('ul.order_details, ul.woocommerce-order-overview, .order_details');
                    orderDetails.forEach(function(el) { el.style.display = 'none'; });

                    // Hide all paragraphs except the one containing Pay Now button
                    var paySection = document.getElementById('btn-razorpay');
                    if (paySection) {
                        var parent = paySection.closest('.woocommerce') || paySection.closest('.entry-content') || document.body;
                        var allP = parent.querySelectorAll('p');
                        allP.forEach(function(p) {
                            if (!p.querySelector('#btn-razorpay') && !p.querySelector('button')) {
                                p.style.display = 'none';
                            }
                        });
                    }
                });
            </script>
            <?php
        });

        wp_dequeue_script( 'razorpay_wc_script' );
        wp_register_script(
            'razorpay_wc_script_fixed',
            plugin_dir_url( __FILE__ ) . 'script.js',
            array( 'razorpay_checkout', 'jquery' ),
            '1.0.6'
        );
        wp_enqueue_script( 'razorpay_wc_script_fixed' );
    }

    private function init_updater() {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
        
        $username = 'appcoderikbal';
        $repo     = 'woo-razorpay-dismiss-fix';
        $plugin_file = __FILE__;
        $basename = plugin_basename( $plugin_file );

        // Add "Check for Update" link to plugin actions
        add_filter( "plugin_action_links_{$basename}", function( $links ) {
            $update_url = add_query_arg( array( 'rzp_check_update' => 1 ), admin_url( 'plugins.php' ) );
            $links[] = '<a href="' . esc_url( $update_url ) . '" style="color: #3399cc; font-weight: bold;">Check for Update</a>';
            return $links;
        });

        // Handle forced update check
        add_action( 'admin_init', function() {
            if ( isset( $_GET['rzp_check_update'] ) ) {
                delete_transient( 'rzp_dismiss_fix_update_check' );
                delete_site_transient( 'update_plugins' );
                wp_redirect( remove_query_arg( 'rzp_check_update' ) );
                exit;
            }
        });

        // Filter to check for updates
        add_filter( 'pre_set_site_transient_update_plugins', function( $transient ) use ( $username, $repo, $basename, $plugin_file ) {
            if ( empty( $transient->checked ) ) {
                return $transient;
            }

            $cache_key = 'rzp_dismiss_fix_update_check';
            $remote_data = get_transient( $cache_key );

            if ( false === $remote_data ) {
                // Using Tags endpoint instead of Releases as it's more reliable for git tags
                $url = "https://api.github.com/repos/{$username}/{$repo}/tags";
                $response = wp_remote_get( $url, array( 
                    'timeout' => 15,
                    'user-agent' => 'Razorpay-Dismiss-Fix-Updater'
                ) );

                if ( ! is_wp_error( $response ) ) {
                    $tags = json_decode( wp_remote_retrieve_body( $response ), true );
                    if ( is_array( $tags ) && ! empty( $tags ) ) {
                        $remote_data = $tags[0]; // Get the latest tag
                        set_transient( $cache_key, $remote_data, 12 * HOUR_IN_SECONDS );
                    }
                }
            }

            if ( ! empty( $remote_data['name'] ) ) {
                $plugin_data = get_plugin_data( $plugin_file );
                $current_version = $plugin_data['Version'];
                $remote_version = str_replace( 'v', '', $remote_data['name'] );

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
                $res->version = str_replace( 'v', '', $remote_data['name'] );
                $res->author = '<a href="https://github.com/appcoderikbal">appcoderikbal</a>';
                $res->homepage = "https://github.com/{$username}/{$repo}";
                $res->sections = array(
                    'description' => 'Redirects the customer back to the checkout page if they close the Razorpay payment popup without completing the payment.',
                    'changelog'   => 'Check GitHub for full changelog.',
                );
                $res->download_link = $remote_data['zipball_url'];
                return $res;
            }

            return $res;
        }, 10, 3);
    }
}

new Razorpay_Dismiss_Fix();
