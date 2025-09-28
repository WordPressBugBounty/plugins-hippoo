<?php
/**
 * Plugin Name: Hippoo Mobile app for WooCommerce
 * Version: 1.6.0
 * Plugin URI: https://Hippoo.app/
 * Description: Best WooCommerce App Alternative – Manage orders and products on the go with real-time notifications, seamless order and product management, and powerful add-ons. Available for Android & iOS. 🚀.
 * Short Description: Best WooCommerce App Alternative – Manage orders and products on the go with real-time notifications, seamless order and product management, and powerful add-ons. Available for Android & iOS. 🚀.
 * Author: Hippoo Team
 * Author URI: https://Hippoo.app/
 * Text Domain: hippoo
 * Domain Path: /languages
 * License: GPL3
 *
 * Hippoo! is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * any later version.
 *
 * Hippoo! is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Hippoo!.
 **/

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define('hippoo_version', '1.6.0');
define('hippoo_path', dirname(__file__).DIRECTORY_SEPARATOR);
define('hippoo_main_file_path', __file__);
define('hippoo_url', plugins_url('hippoo').'/assets/');
define('hippoo_proxy_notifiction_url', 'https://hippoo.app/wp-json/woohouse/v1/fb/proxy_notification');

# This is used by hippoo_pif_get_url_attachment
require_once(ABSPATH."wp-admin/includes/image.php");

include_once(hippoo_path.'app'.DIRECTORY_SEPARATOR.'utils.php');
include_once(hippoo_path.'app'.DIRECTORY_SEPARATOR.'web_api.php');
include_once(hippoo_path.'app'.DIRECTORY_SEPARATOR.'settings.php');
include_once(hippoo_path.'app'.DIRECTORY_SEPARATOR.'dashboard_widget.php');
include_once(hippoo_path.'app'.DIRECTORY_SEPARATOR.'pwa.php');
include_once(hippoo_path.'app'.DIRECTORY_SEPARATOR.'bugsnag.php');


function hippoo_textdomain() {
    load_theme_textdomain( 'hippoo', get_template_directory() . '/languages' );
}
add_action( 'after_setup_theme', 'hippoo_textdomain' );

function hippoo_page_style( $hook ) {
        wp_enqueue_style(  'hippoo-main-page-style', hippoo_url . "css/style.css", null, hippoo_version );
        wp_enqueue_style(  'hippoo-main-admin-style', hippoo_url . "css/admin-style.css", null, hippoo_version );
        wp_enqueue_script( 'hippoo-main-scripts', hippoo_url . "js/admin-script.js", [ 'jquery', 'jquery-ui-core', 'jquery-ui-tooltip' ], hippoo_version, true );
        wp_localize_script( 'hippoo-main-scripts', 'hippoo', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('hippoo_nonce')
        ] );
}
add_action( 'admin_enqueue_scripts', 'hippoo_page_style' );

///
/// Invoice 
///

define( 'HIPPOO_INVOICE_PLUGIN_PATH', plugin_dir_path( __FILE__ ) . 'invoice/' );
define( 'HIPPOO_INVOICE_PLUGIN_URL', plugin_dir_url( __FILE__ )  . 'invoice/');

$options = get_option('hippoo_settings');
if (isset($options['invoice_plugin_enabled']) && $options['invoice_plugin_enabled']) {
    require_once HIPPOO_INVOICE_PLUGIN_PATH . 'main.php';
}
add_action('init', 'init_setting');
function init_setting($request) {
    $settings = get_option('hippoo_settings');

    if (empty($settings)) {
        $settings = [];
    }

    // Define default settings with explicit data types
    $default_settings = [
        'invoice_plugin_enabled' => false,
        'send_notification_wc-processing' => true
    ];

    if (function_exists('wc_get_order_statuses')) {
        $order_statuses = wc_get_order_statuses();
        foreach ($order_statuses as $status_key => $status_label) {
            $key = 'send_notification_' . $status_key;
            if (!array_key_exists($key, $default_settings)) {
                $default_settings[$key] = false;
            }
        }
    }

    $settings = array_merge($default_settings, $settings);

    $settings = array_map(function($value) {
        return ($value === '1') ? true : (($value === '0') ? false : $value);
    }, $settings);

    update_option('hippoo_settings', $settings);
}


// Add custom action links to the Hippoo plugin
function hippoo_add_plugin_action_links($links) {
    $custom_links = array(
        'help_center' => '<a href="https://hippoo.app/docs" target="_blank">' . __('Help Center', 'hippoo') . '</a>',
        'feature_request' => '<a href="https://hippoo.canny.io/feature-request/" target="_blank">' . __('Feature Request', 'hippoo') . '</a>',
        'customer_support' => '<a href="mailto:feedback@hippoo.app">' . __('Customer Support', 'hippoo') . '</a>',
    );

    return array_merge($custom_links, $links);
}
add_filter('plugin_action_links_hippoo/hippoo.php', 'hippoo_add_plugin_action_links');


// Displays a review request banner after two weeks of plugin activation
function hippoo_register_activation_hook() {
    if (!get_option('hippoo_activation_time')) {
        update_option('hippoo_activation_time', time());
    }
}
register_activation_hook(__FILE__, 'hippoo_register_activation_hook');

function hippoo_display_review_banner() {
    if (!current_user_can('manage_options')) {
        return;
    }

    if (get_option('hippoo_review_dismissed')) {
        return;
    }

    $activation_time = get_option('hippoo_activation_time', 0);
    $two_weeks_ago = time() - (2 * WEEK_IN_SECONDS);
    if ($activation_time > $two_weeks_ago) {
        return;
    }
    ?>
    <div class="notice notice-info is-dismissible hippoo-review-banner">
        <p><?php esc_html_e('Enjoying the Hippoo Mobile App for WooCommerce? We would love to hear your feedback! Please take a moment to leave a review.', 'hippoo'); ?></p>
        <p>
            <a href="https://wordpress.org/support/plugin/hippoo/reviews/?rate=5#new-post" target="_blank" class="button button-primary"><?php esc_html_e('Leave a Review', 'hippoo'); ?></a>
            <button class="button hippoo-dismiss-review"><?php esc_html_e('Dismiss', 'hippoo'); ?></button>
        </p>
    </div>
    <?php
}
add_action('admin_notices', 'hippoo_display_review_banner');

function hippoo_dismiss_review_banner() {
    check_ajax_referer('hippoo_nonce', 'nonce');
    update_option('hippoo_review_dismissed', true);
    wp_send_json_success();
}
add_action('wp_ajax_hippoo_dismiss_review', 'hippoo_dismiss_review_banner');
add_action('wp_ajax_nopriv_hippoo_dismiss_review', 'hippoo_dismiss_review_banner');