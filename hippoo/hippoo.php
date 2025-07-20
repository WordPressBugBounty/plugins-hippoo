<?php
/**
 * Plugin Name: Hippoo Mobile app for WooCommerce
 * Version: 1.5.14
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

define('hippoo_version', '1.5.13');
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


function hippoo_textdomain() {
    load_theme_textdomain( 'hippoo', get_template_directory() . '/languages' );
}
add_action( 'after_setup_theme', 'hippoo_textdomain' );

function hippoo_page_style( $hook ) {
        wp_enqueue_style(  'hippoo-main-page-style', hippoo_url . "css/style.css", null, hippoo_version );
        wp_enqueue_style(  'hippoo-main-admin-style', hippoo_url . "css/admin-style.css", null, hippoo_version );
        wp_enqueue_script( 'hippoo-main-scripts', hippoo_url . "js/admin-script.js", [ 'jquery', 'jquery-ui-core', 'jquery-ui-tooltip' ], hippoo_version, true );
}
add_action( 'admin_enqueue_scripts', 'hippoo_page_style' );

///
///Invoice 
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