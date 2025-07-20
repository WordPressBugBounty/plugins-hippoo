<?php // phpcs:disable PluginCheck.CodeAnalysis.ImageFunctions.NonEnqueuedImage

class HippooSettings
{
    public $slug = 'hippoo_settings';
    public $hippoo_icon = (hippoo_url . '/images/icon.svg');
    public $settings;

    public function __construct()
    {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'settings_init'));

        $this->settings = get_option($this->slug, []);
    }

    public function add_admin_menu()
    {
        add_menu_page(
            'Hippoo Settings', // Page title
            'Hippoo', // Menu title
            'manage_options', // Capability
            'hippoo_setting_page', // Menu slug
            array($this, 'settings_page_render') // Callback function
        );
    }

    public function settings_init()
    {
        register_setting('hippoo_settings', $this->slug); // phpcs:ignore

        add_settings_section(
            'hippoo_invoice_settings_section',
            null,
            null,
            $this->slug
        );

        $description = '<p>' . esc_html__( 'Provides PDF invoices and shipping labels for easy printing. Also generates barcodes for orders and product SKUs inside the WooCommerce dashboard, allowing you to scan them with the Hippoo WooCommerce app\'s barcode scanner to quickly find orders and products.', 'hippoo' ) . '</p>';
        add_settings_field(
            'invoice_plugin_enabled',
            __('Enable Hippoo invoice and shipping label', 'hippoo') . $description,
            array($this, 'invoice_plugin_enabled_render'),
            $this->slug,
            'hippoo_invoice_settings_section'
        );
    }

    /***** Helper Function *****/
    public function render_checkbox_input($name, $value_key) {
        $value = isset($this->settings[$value_key]) ? $this->settings[$value_key] : 0;
        ?>
        <input type="checkbox" class="switch" name="hippoo_invoice_settings[<?php echo esc_attr($name); ?>]" <?php checked($value, 1); ?> value="1">
        <?php
    }

    public function invoice_plugin_enabled_render()
    {
        $value = isset($this->settings['invoice_plugin_enabled']) ? $this->settings['invoice_plugin_enabled'] : 0;
    ?>
        <input type="checkbox" class="switch" name="hippoo_settings[invoice_plugin_enabled]" <?php checked($value, 1); ?> value="1">
    <?php
    }

    public function settings_page_render()
    {
    ?>
        <form id="hippoo_settings" action="options.php" method="post">
    <h2><?php esc_html_e('Hippoo Settings', 'hippoo'); ?></h2>
    <div class="tabs">
        <h2 class="nav-tab-wrapper">
            <a href="#tab-settings" class="nav-tab nav-tab-active"><?php esc_html_e('Settings', 'hippoo'); ?></a>
            <a href="#tab-app" class="nav-tab"><?php esc_html_e('Hippoo App', 'hippoo'); ?></a>
        </h2>

        <div id="tab-settings" class="tab-content active">
            <?php
            settings_fields('hippoo_settings');
            do_settings_sections('hippoo_settings');
            submit_button();
            ?>
        </div>

        <div id="tab-app" class="tab-content">
            <div class="introduction">
                <div class="details">
                    <h2><?php esc_html_e('Hippoo Woocommerce app', 'hippoo'); ?></h2>
                    <p><?php esc_html_e('Hippoo! is not just a shop management app, it\'s also a platform that enables you to extend its capabilities. With the ability to install extensions, you can customize your experience and add new features to the app. Browse and install other Hippoo plugins from our app to enhance your store\'s functionality.', 'hippoo'); ?></p>
                    <a href="https://play.google.com/store/apps/details?id=io.hippo" target="_blank" class="google-button">
                        <img src="<?php echo esc_url(HIPPOO_INVOICE_PLUGIN_URL . 'assets/images/google-play.svg'); ?>" alt="<?php esc_attr_e('Download Hippoo Android app', 'hippoo'); ?>" />
                        <strong><?php esc_html_e('Download Hippoo Android app', 'hippoo'); ?></strong>
                    </a>
                    <a href="https://apps.apple.com/ee/app/hippoo-woocommerce-admin-app/id1667265325" target="_blank" class="google-button">
                        <img src="<?php echo esc_url(HIPPOO_INVOICE_PLUGIN_URL . 'assets/images/apple.svg'); ?>" alt="<?php esc_attr_e('Download Hippoo iOS app', 'hippoo'); ?>" />
                        <strong><?php esc_html_e('Download Hippoo iOS app', 'hippoo'); ?></strong>
                    </a>
                </div>
                <div class="qrcode">
                    <p><?php esc_html_e('Scan QR code with your Android phone to install the app', 'hippoo'); ?></p>
                    <img src="<?php echo esc_url(HIPPOO_INVOICE_PLUGIN_URL . 'assets/images/qrcode.png'); ?>" alt="<?php esc_attr_e('QR Code', 'hippoo'); ?>" />
                </div>
            </div>
            <div id="image-carousel">
                <div class="carousel-wrapper">
                    <div class="carousel-inner">
                        <img class="carousel-image" src="<?php echo esc_url('https://hippoo.app/static/img/android-app/1.png'); ?>" alt="<?php esc_attr_e('App screenshot 1', 'hippoo'); ?>" />
                        <img class="carousel-image" src="<?php echo esc_url('https://hippoo.app/static/img/android-app/2.png'); ?>" alt="<?php esc_attr_e('App screenshot 2', 'hippoo'); ?>" />
                        <img class="carousel-image" src="<?php echo esc_url('https://hippoo.app/static/img/android-app/3.png'); ?>" alt="<?php esc_attr_e('App screenshot 3', 'hippoo'); ?>" />
                        <img class="carousel-image" src="<?php echo esc_url('https://hippoo.app/static/img/android-app/4.png'); ?>" alt="<?php esc_attr_e('App screenshot 4', 'hippoo'); ?>" />
                        <img class="carousel-image" src="<?php echo esc_url('https://hippoo.app/static/img/android-app/5.png'); ?>" alt="<?php esc_attr_e('App screenshot 5', 'hippoo'); ?>" />
                    </div>
                </div>
                <div class="carousel-nav">
                    <span class="carousel-arrow prev"><i class="carousel-prev"></i></span>
                    <span class="carousel-arrow next"><i class="carousel-next"></i></span>
                </div>
            </div>
        </div>
    </div>
</form>

<?php
    }
}

new HippooSettings();