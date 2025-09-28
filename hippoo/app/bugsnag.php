<?php

if (!defined('hippoo_bugsnag_api_key')) {
    define('hippoo_bugsnag_api_key', '76ed4ce2921ad893f4ae5581f3f109a8');
}

require_once hippoo_path . 'libs/bugsnag-php/Autoload.php';

class HippooBugsnag
{
    private $client;
    private $default_notify_severities = 'fatal,error';

    public function __construct()
    {
        $this->init();
        add_action('init', array($this, 'test_bugsnag'));
        add_action('admin_init', array($this, 'settings_init'));
        add_action('update_option_hippoo_settings', array($this, 'update_hippoo_settings'), 10, 2);
    }

    public function init()
    {
        if (!class_exists('Bugsnag_Client')) {
            error_log('Hippoo BugSnag: SDK not found. Please ensure bugsnag-php is in libs/bugsnag-php.');
            return;
        }

        if (!$this->is_enabled()) {
            return;
        }

        try {
            $this->client = new Bugsnag_Client(hippoo_bugsnag_api_key);

            $this->client->setContext(get_bloginfo('name'));
            $this->client->setAppVersion(hippoo_version);

            $this->client->setUser([]);

            $this->client->setErrorReportingLevel($this->error_reporting_level());

            $this->client->setBeforeNotifyFunction(array($this, 'filter_hippoo_errors'));
        } catch (Exception $e) {
            error_log('Hippoo Bugsnag init failed: ' . $e->getMessage());
        }
    }

    public function error_reporting_level()
    {
        $level = 0;

        $severities = explode(',', $this->default_notify_severities);
        foreach ($severities as $severity) {
            $level |= Bugsnag_ErrorTypes::getLevelsForSeverity($severity);
        }

        return $level;
    }

    public function filter_hippoo_errors($error)
    {
        $stacktrace = $error->stacktrace;
        if (!$stacktrace) {
            return false;
        }

        $plugins = get_plugins();
        $hippoo_plugins = array_filter($plugins, function ($plugin_data, $plugin_file) {
            $plugin_folder = dirname($plugin_file);
            return stripos(strtolower($plugin_data['Name']), 'hippoo') !== false 
                || stripos(strtolower($plugin_folder), 'hippoo') !== false;
        }, ARRAY_FILTER_USE_BOTH);

        $hippoo_plugin_paths = array_map(function ($plugin_file) {
            return WP_PLUGIN_DIR . '/' . dirname($plugin_file);
        }, array_keys($hippoo_plugins));

        foreach ($stacktrace->frames as $frame) {
            $file = $frame['file'] ?? '';
            foreach ($hippoo_plugin_paths as $path) {
                if (stripos($file, $path) !== false) {
                    return true;
                }
            }
        }

        return false;
    }

    public function settings_init()
    {
        add_settings_section(
            'hippoo_bugsnag_section',
            null,
            null,
            'hippoo_settings'
        );

        $description = '<p>' . esc_html__( 'Enable this option to send anonymous usage statistics and error reports. This helps us identify issues and improve Hippoo. No personal data will be collected.', 'hippoo' ) . '</p>';
        add_settings_field(
            'bugsnag_enabled',
            __('Help Improve Hippoo', 'hippoo') . $description,
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
        return isset($settings['bugsnag_enabled']) ? $settings['bugsnag_enabled'] : 1;
    }

    public function update_hippoo_settings($old_value, $value)
    {
        if (!isset($value['bugsnag_enabled'])) {
            $value['bugsnag_enabled'] = 0;
            update_option('hippoo_settings', $value);
        }
    }

    public function test_bugsnag() {
        if (!current_user_can('manage_options') || !isset($_GET['hippoo_error_test'])) {
            return;
        }

        $test_type = sanitize_text_field($_GET['hippoo_error_test']);

        if ($test_type === 'error') {
            trigger_error('Hippoo Test Error (E_USER_ERROR)', E_USER_ERROR);
        } elseif ($test_type === 'fatal') {
            non_existent_function();
        }
    }
}

new HippooBugsnag();