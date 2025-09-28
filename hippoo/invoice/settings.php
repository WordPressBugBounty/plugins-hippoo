<?php // phpcs:disable PluginCheck.CodeAnalysis.ImageFunctions.NonEnqueuedImage

class HippooInvoiceSettings {
    public $slug = 'hippoo_invoice_settings';
    public $hippoo_icon = HIPPOO_INVOICE_PLUGIN_URL . 'assets/images/hippoo-mono.svg';
    public $settings;

    public function __construct() {
        add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
        add_action( 'admin_init', array( $this, 'settings_init' ) );
        add_action( 'admin_notices', array( $this, 'admin_notice' ) );
        add_action( 'wp_ajax_dismiss_admin_notice', array( $this, 'handle_dismiss' ) );
        add_action( 'wp_ajax_nopriv_dismiss_admin_notice', array( $this, 'handle_dismiss' ) );
        $this->settings = get_option( $this->slug, [] );
    }

    public function add_admin_menu() {
        add_submenu_page(
            'hippoo_setting_page', // Parent slug
            'Hippoo Invoice', // Page title
            'Hippoo Invoice', // Menu title
            'manage_options', // Capability
            $this->slug, // Menu slug
            array($this, 'settings_page_render') // Callback function
        );
        // Enqueue media scripts on the settings page
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_media_uploader' ) );
    }

    public function enqueue_media_uploader($hook) {
        $this_hook = 'hippoo_page_'. $this->slug;
        if ($hook !== $this_hook ) {
            return;
        }
        wp_enqueue_media();
    }

    public function settings_init() {
        register_setting('hippoo_invoice_settings', $this->slug); // phpcs:ignore

        $this->general_settings_init();
        $this->invoice_settings_init();
        $this->shipping_settings_init();
    }

    public function settings_page_render() {
        ?>
        <form id="hippoo_invoice_settings" action="options.php" method="post">
            <?php wp_nonce_field('hippoo_invoice_settings_save', 'hippoo_invoice_settings_nonce'); ?>
            <h2><?php esc_html_e('Hippoo invoice and shipping label', 'hippoo'); ?></h2>

            <div class="tabs">
                <h2 class="nav-tab-wrapper">
                    <a href="#tab-settings" class="nav-tab nav-tab-active"><?php esc_html_e('Settings', 'hippoo'); ?></a>
                </h2>

                <div id="tab-settings" class="tab-content active">
                    <?php
                    settings_fields('hippoo_invoice_settings');
                    do_settings_sections('hippoo_invoice_settings');
                    submit_button();
                    ?>
                </div>
            </div>
        </form>
        <?php
    }

    /***** Init Settings *****/
    public function general_settings_init() {
        add_settings_section(
            'hippoo_general_settings_section',
            __( 'General settings', 'hippoo' ),
            null,
            $this->slug
        );

        add_settings_field(
            'shop_logo',
            __( 'Shop logo', 'hippoo' ),
            array( $this, 'shop_logo_render' ),
            $this->slug,
            'hippoo_general_settings_section'
        );

        add_settings_field(
            'language_direction',
            __( 'Language direction', 'hippoo' ),
            array( $this, 'language_direction_render' ),
            $this->slug,
            'hippoo_general_settings_section'
        );

        add_settings_field(
            'show_barcode_order_list',
            __( 'Show barcode(Order id) in order list', 'hippoo' ),
            array( $this, 'show_barcode_order_list_render' ),
            $this->slug,
            'hippoo_general_settings_section'
        );

        add_settings_field(
            'show_barcode_order_details',
            __( 'Show barcode(Order id) in order details', 'hippoo' ),
            array( $this, 'show_barcode_order_details_render' ),
            $this->slug,
            'hippoo_general_settings_section'
        );

        add_settings_field(
            'show_barcode_products_list',
            __( 'Show barcode(SKU) in products list', 'hippoo' ),
            array( $this, 'show_barcode_products_list_render' ),
            $this->slug,
            'hippoo_general_settings_section'
        );

        add_settings_field(
            'show_barcode_products_details',
            __( 'Show barcode(SKU) in products details', 'hippoo' ),
            array( $this, 'show_barcode_products_details_render' ),
            $this->slug,
            'hippoo_general_settings_section'
        );
    }

    public function invoice_settings_init() {
        add_settings_section(
            'hippoo_invoice_settings_section',
            __( 'Invoice settings', 'hippoo' ),
            null,
            $this->slug
        );

        
        add_settings_field(
            'font_name',
            __( 'Font name', 'hippoo' ),
            array( $this, 'font_name_render' ),
            $this->slug,
            'hippoo_invoice_settings_section'
        );

        add_settings_field(
            'invoice_show_logo',
            __( 'Show logo', 'hippoo' ),
            array( $this, 'invoice_show_logo_render' ),
            $this->slug,
            'hippoo_invoice_settings_section'
        );

        add_settings_field(
            'show_customer_note',
            __( 'Show customer note', 'hippoo' ),
            array( $this, 'show_customer_note_render' ),
            $this->slug,
            'hippoo_invoice_settings_section'
        );

        add_settings_field(
            'show_product_sku_invoice',
            __( 'Show product SKU in invoice', 'hippoo' ),
            array( $this, 'show_product_sku_invoice_render' ),
            $this->slug,
            'hippoo_invoice_settings_section'
        );

        add_settings_field(
            'footer_description',
            __( 'Footer description', 'hippoo' ),
            array( $this, 'footer_description_render' ),
            $this->slug,
            'hippoo_invoice_settings_section'
        );

        add_settings_field(
            'invoice_paper_size',
            __( 'Invoice Paper Size', 'hippoo' ),
            array( $this, 'invoice_paper_size_render' ),
            $this->slug,
            'hippoo_invoice_settings_section'
        );
    }

    public function shipping_settings_init() {
        add_settings_section(
            'hippoo_shipping_settings_section',
            __( 'Shipping label settings', 'hippoo' ),
            null,
            $this->slug
        );


        add_settings_field(
            'shipping_show_logo',
            __( 'Show logo', 'hippoo' ),
            array( $this, 'shipping_show_logo_render' ),
            $this->slug,
            'hippoo_shipping_settings_section'
        );

        add_settings_field(
            'shipping_calculate_weight',
            __( 'Calculate Weight', 'hippoo' ),
            array( $this, 'shipping_calculate_weight_render' ),
            $this->slug,
            'hippoo_shipping_settings_section'
        );

        add_settings_field(
            'shipping_courier_logo',
            __( 'Courier logo', 'hippoo' ),
            array( $this, 'shipping_courier_logo_render' ),
            $this->slug,
            'hippoo_shipping_settings_section'
        );

        add_settings_field(
            'shipping_paper_size',
            __( 'Shipping Label Paper Size', 'hippoo' ),
            array( $this, 'shipping_paper_size_render' ),
            $this->slug,
            'hippoo_shipping_settings_section'
        );
    }

    /***** Helper Function *****/

    public function render_checkbox_input($name, $value_key) {
        $value = isset($this->settings[$value_key]) ? $this->settings[$value_key] : 0;
        ?>
        <input type="checkbox" class="switch" name="hippoo_invoice_settings[<?php echo esc_attr($name); ?>]" <?php checked($value, 1); ?> value="1">
        <?php
    }

    /***** General Render *****/

    public function shop_logo_render() {
        $image_url = isset($this->settings['shop_logo']) ? esc_url($this->settings['shop_logo']) : '';
        ?>
        <div class="shop_logo media_uploader_wrapper">
            <div class="uploader">
                <input type="hidden" id="shop_logo_field" name="hippoo_invoice_settings[shop_logo]" value="<?php echo esc_url($image_url); ?>" />
                <img id="shop_logo" src="<?php echo esc_url($image_url); ?>" width="64" height="64" alt="<?php esc_attr_e('Shop Logo', 'hippoo'); ?>" />
                <div class="upload_buttons">
                    <button id="shop_logo_upload_button" class="button upload"><?php esc_html_e('Upload Logo', 'hippoo'); ?></button>
                    <button id="shop_logo_clear_button" class="button remove"><?php esc_html_e('Remove', 'hippoo'); ?></button>
                    <p class="desc"><?php esc_html_e('512x512 is perfect size for logo', 'hippoo'); ?></p>
                </div>
            </div>
        </div>
        <?php
    }
    

    public function language_direction_render() {
        $options = [
            'LTR' => 'Left-to-Right',
            'RTL' => 'Right-to-Left'
        ];
    
        $selected = isset( $this->settings['language_direction'] ) ? $this->settings['language_direction'] : '';
    
        ?>
        <select name="hippoo_invoice_settings[language_direction]">
            <?php
            foreach ( $options as $value => $label ) {
                $selected_attr = selected( $selected, $value, false );
                echo '<option value="' . esc_attr( $value ) . '" ' . esc_html($selected_attr) . '>' . esc_html( $label ) . '</option>';
            }
            ?>
        </select>
        <?php
    }
    


    
    public function show_barcode_order_list_render() {
        $this->render_checkbox_input('show_barcode_order_list', 'show_barcode_order_list');
    }
    
    public function show_barcode_order_details_render() {
        $this->render_checkbox_input('show_barcode_order_details', 'show_barcode_order_details');
    }
    
    public function show_barcode_products_list_render() {
        $this->render_checkbox_input('show_barcode_products_list', 'show_barcode_products_list');
    }
    
    public function show_barcode_products_details_render() {
        $this->render_checkbox_input('show_barcode_products_details', 'show_barcode_products_details');
    }

    public function font_name_render() {
        $options = ['Tahoma', 'Arial'];
        $selected = isset($this->settings['font_name']) ? $this->settings['font_name'] : '';
        ?>
        <select name="hippoo_invoice_settings[font_name]">
            <?php
            foreach ($options as $font_name) {
                $selected_attr = selected($selected, $font_name, false);
                ?>
                <option value="<?php echo esc_attr($font_name); ?>" <?php echo esc_html($selected_attr); ?>><?php echo esc_html($font_name); ?></option>
                <?php
            }
            ?>
        </select>
        <?php
    }

    public function invoice_show_logo_render() {
        $this->render_checkbox_input('invoice_show_logo', 'invoice_show_logo');
    }
    
    public function show_customer_note_render() {
        $this->render_checkbox_input('show_customer_note', 'show_customer_note');
    }
    
    public function show_product_sku_invoice_render() {
        $this->render_checkbox_input('show_product_sku_invoice', 'show_product_sku_invoice');
    }

    public function footer_description_render() {
        $value = isset( $this->settings['footer_description'] ) ? esc_textarea( $this->settings['footer_description'] ) : '';
        ?>
        <textarea rows="5" cols="35" id="footer_description" name="hippoo_invoice_settings[footer_description]"><?php echo esc_html($value); ?></textarea>
        <?php
    }

    public function invoice_paper_size_render() {
        $options = [
            'A4' => 'A4',
            'A5' => 'A5'
        ];
        $selected = isset($this->settings['invoice_paper_size']) ? $this->settings['invoice_paper_size'] : 'A4';
        ?>
        <select name="hippoo_invoice_settings[invoice_paper_size]">
            <?php
            foreach ($options as $value => $label) {
                $selected_attr = selected($selected, $value, false);
                echo '<option value="' . esc_attr($value) . '" ' . esc_html($selected_attr) . '>' . esc_html($label) . '</option>';
            }
            ?>
        </select>
        <?php
    }
    

    /***** Shipping Render *****/

    public function shipping_show_logo_render() {
        $this->render_checkbox_input('shipping_show_logo', 'shipping_show_logo');
    }
    
    public function shipping_calculate_weight_render() {
        $this->render_checkbox_input('shipping_calculate_weight', 'shipping_calculate_weight');
    }
    
    public function shipping_courier_logo_render() {
        $image_url = isset( $this->settings['shipping_courier_logo'] ) ? $this->settings['shipping_courier_logo'] : '';
        ?>
        <div class="courier_logo media_uploader_wrapper">
            <div class="uploader">
                <input type="hidden" id="courier_logo_field" name="hippoo_invoice_settings[shipping_courier_logo]" value="<?php echo esc_url( $image_url ); ?>" />
                <img id="courier_logo" src="<?php echo esc_url( $image_url ); ?>" width="64" height="64" />
                <div class="upload_buttons">
                    <button id="courier_logo_upload_button" class="button upload"><?php esc_html_e( 'Upload Logo', 'hippoo' ); ?></button>
                    <button id="courier_logo_clear_button" class="button remove"><?php esc_html_e( 'Remove', 'hippoo' ); ?></button>
                </div>
            </div>
        </div>
        <?php
    }

    public function shipping_paper_size_render() {
        $options = [
            'A4' => 'A4',
            'A5' => 'A5'
        ];
        $selected = isset($this->settings['shipping_paper_size']) ? $this->settings['shipping_paper_size'] : 'A4';
        ?>
        <select name="hippoo_invoice_settings[shipping_paper_size]">
            <?php
            foreach ($options as $value => $label) {
                $selected_attr = selected($selected, $value, false);
                echo '<option value="' . esc_attr($value) . '" ' . esc_html($selected_attr) . '>' . esc_html($label) . '</option>';
            }
            ?>
        </select>
        <?php
    }

    /***** Hippoo Banner *****/

    public function admin_notice() {
        $dismissed = get_option( 'hippoo_dismissed_notice', false );
    
        if ( $dismissed ) {
            return;
        }
    
        wp_nonce_field( 'dismiss_admin_notice_nonce', 'dismiss_admin_notice_nonce' );
        ?>
        <div class="notice notice-info is-dismissible">
            <p><?php esc_html_e( 'Setting saved.', 'hippoo' ); ?></p>
        </div>
        <?php
    }
    
    public function handle_dismiss() {
        $nonce = ( isset( $_REQUEST['dismiss_admin_notice_nonce'] ) ) ? sanitize_key( $_REQUEST['dismiss_admin_notice_nonce'] ) : '';

        if ( ! wp_verify_nonce( $nonce, 'dismiss_admin_notice_nonce' ) ) {
            wp_send_json_error( array( 'message' => 'Nonce verification failed.' ) );
        }
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => 'Permission denied.' ) );
        }
        update_option( 'hippoo_dismissed_notice', true );
        wp_send_json_success();
    }
    
    
}

new HippooInvoiceSettings();