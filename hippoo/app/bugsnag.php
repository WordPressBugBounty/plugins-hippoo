<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Bugsnag has been removed for WordPress.org compatibility
// Using WordPress native error logging instead

class HippooBugsnag
{
    private $default_notify_severities = 'fatal,error';

    public function __construct()
    {
        // add_action('admin_init', array($this, 'settings_init'));
        add_action('update_option_hippoo_settings', array($this, 'update_hippoo_settings'), 10, 2);
        
        // Set up WordPress native error handler if enabled
        if ($this->is_enabled()) {
            $this->init_wp_error_logging();
        }
    }

    public function init_wp_error_logging()
    {
        // WordPress native error logging is already enabled via WP_DEBUG_LOG
        // This method is kept for backward compatibility
        if (!defined('WP_DEBUG_LOG')) {
            // Recommend enabling WP_DEBUG_LOG in wp-config.php for error logging
            // define('WP_DEBUG_LOG', true);
        }
    }

    public function error_reporting_level()
    {
        $settings = get_option('hippoo_settings', []);
        $notify_severities = isset($settings['bugsnag_notify_severities'])
            ? $settings['bugsnag_notify_severities']
            : $this->default_notify_severities;

        $severities = array_map('trim', explode(',', $notify_severities));
        
        // Map to PHP error levels
        $level = 0;
        foreach ($severities as $severity) {
            switch ($severity) {
                case 'fatal':
                    $level |= E_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR;
                    break;
                case 'error':
                    $level |= E_ERROR | E_WARNING | E_USER_ERROR;
                    break;
                case 'warning':
                    $level |= E_WARNING | E_USER_WARNING;
                    break;
                case 'info':
                    $level |= E_NOTICE | E_USER_NOTICE;
                    break;
            }
        }

        return $level;
    }

    public function filter_hippoo_errors($error)
    {
        // Filter to only report Hippoo-related errors
        if (isset($error['file'])) {
            $file = $error['file'];
            if (strpos($file, 'hippoo') === false && strpos($file, 'woocommerce') === false) {
                return false;
            }
        }
        return true;
    }

    public function test_bugsnag()
    {
        // Removed Bugsnag test - using WordPress native logging
        // Errors will be logged to debug.log if WP_DEBUG_LOG is enabled
    }

    public function settings_init()
    {
        add_settings_section(
            'hippoo_bugsnag_section',
            __('Error Logging', 'hippoo'),
            '__return_empty_string',
            'hippoo_settings'
        );

        $description = '<p>' . esc_html__('Enable WordPress native error logging for Hippoo plugin errors. Errors will be logged to wp-content/debug.log if WP_DEBUG_LOG is enabled.', 'hippoo') . '</p>';
        add_settings_field(
            'bugsnag_enabled',
            __('Enable Error Logging', 'hippoo') . $description,
            array($this, 'field_bugsnag_enabled_render'),
            'hippoo_settings',
            'hippoo_bugsnag_section'
        );
    }

    public function field_bugsnag_enabled_render()
    {
        echo '<input type="checkbox" class="switch" id="bugsnag_enabled" name="hippoo_settings[bugsnag_enabled]" ' . checked($this->is_enabled(), 1, false) . ' value="1">';
    }

    public function is_enabled()
    {
        $settings = get_option('hippoo_settings', []);
        return isset($settings['bugsnag_enabled']) ? $settings['bugsnag_enabled'] : 0;
    }

    public function update_hippoo_settings($old_value, $new_value)
    {
        // Re-initialize if error logging setting changed
        if (isset($old_value['bugsnag_enabled']) && isset($new_value['bugsnag_enabled'])) {
            if ($old_value['bugsnag_enabled'] !== $new_value['bugsnag_enabled']) {
                if ($new_value['bugsnag_enabled']) {
                    $this->init_wp_error_logging();
                }
            }
        }
    }

    // Helper function to log Hippoo errors
    public static function log_error($message, $context = array())
    {
        if (!defined('WP_DEBUG_LOG') || !WP_DEBUG_LOG) {
            return;
        }

        $log_message = '[Hippoo] ' . $message;
        if (!empty($context)) {
            $log_message .= ' | Context: ' . wp_json_encode($context);
        }

        error_log($log_message);
    }

    // Helper function to log Hippoo notices
    public static function log_notice($message, $context = array())
    {
        if (!defined('WP_DEBUG_LOG') || !WP_DEBUG_LOG) {
            return;
        }

        $log_message = '[Hippoo Notice] ' . $message;
        if (!empty($context)) {
            $log_message .= ' | Context: ' . wp_json_encode($context);
        }

        error_log($log_message);
    }
}

new HippooBugsnag();