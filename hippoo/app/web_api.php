<?php
// Add CORS headers to all rest responses
add_action('rest_api_init', function () {
    $origin = get_http_origin();
    
    // Only allow hippoo.app (and optionally localhost for development)
    $allowed_origins = [
        'https://hippoo.app',
       // 'http://localhost', // Uncomment during local development
      //  'http://127.0.0.1', // Optional: if using 127.0.0.1
    ];
    
    // Check if the origin is allowed
    if (in_array($origin, $allowed_origins)) {
        header("Access-Control-Allow-Origin: $origin");
        header("Access-Control-Allow-Credentials: true");
        header("Access-Control-Allow-Methods: OPTIONS, GET, POST, PUT, PATCH, DELETE");
        header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
        header("Access-Control-Max-Age: 86400"); // Cache preflight response for 24 hours
    }
    
    // Handle preflight OPTIONS request
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        status_header(200);
        header("Content-Length: 0"); // Ensure no content is sent with OPTIONS
        exit();
    }
}, 0);


add_action('rest_api_init', function () {
    require_once __DIR__ . '/web_api_auth.php';
    $controller = new HippooControllerWithAuth();
    $controller->register_routes();
    $controller->re_register_external_routes();
}, PHP_INT_MAX);


add_action( 'rest_api_init', function () {
    register_rest_route( 'hippoo/v1', 'wc/token/get', array(
        'methods'  => 'GET',
        'callback' => 'hippoo_get_token_from_wc',
        'permission_callback' => '__return_true'
    ));
    register_rest_route( 'hippoo/v1', 'wc/token/save_callback/(?P<token_id>\w+)', array(
        'methods'  => 'POST',
        'callback' => 'hippoo_save_token_callback',
        'permission_callback' => '__return_true'
    ));
    register_rest_route( 'hippoo/v1', 'wc/token/show/(?P<token_id>\w+)', array(
        'methods'  => 'GET',
        'callback' => 'hippoo_show_token',
        'permission_callback' => '__return_true'
    ));
    register_rest_route('hippoo/v1', 'wc/token/return/(?P<token_id>\w+)', array(
        'methods'  => 'GET',
        'callback' => 'hippoo_returned',
        'permission_callback' => '__return_true'
    ));
    register_rest_route( 'hippoo/v1', 'config', array(
        'methods'  => 'GET',
        'callback' => 'hippoo_config',
        'permission_callback' => '__return_true'
    ));
    register_rest_route('hippoo/v1', 'shop-config', array(
        'methods'  => 'GET',
        'callback' => 'hippoo_shop_config',
        'permission_callback' => '__return_true'
    ));
    register_rest_route('hippoo/v1', 'locations/countries', array(
        'methods'  => 'GET',
        'callback' => 'hippoo_get_countries',
        'permission_callback' => '__return_true'
    ));
    register_rest_route('hippoo/v1', 'locations/countries/(?P<country_code>[A-Z]{2})', array(
        'methods'  => 'GET',
        'callback' => 'hippoo_get_states',
        'permission_callback' => '__return_true'
    ));
    
    # TODO Delete This Soon as Amid is updating the App
    register_rest_route( 'woohouse/v1', 'config', array(
        'methods'  => 'GET',
        'callback' => 'hippoo_config',
        'permission_callback' => '__return_true'
    ));
} );

function hippoo_get_token_from_wc() {
    $key          = md5(microtime().wp_rand());
    $store_url    = get_option('siteurl');
    $store_url    = str_replace("http://", "https://", $store_url);
    $return_url   = $store_url . "/wp-json/hippoo/v1/wc/token/return/" . $key;
    $callback_url = $store_url . "/wp-json/hippoo/v1/wc/token/save_callback/" . $key;
    $endpoint     = '/wc-auth/v1/authorize';
    
    $params = [
        'app_name'     => __( 'Hippoo', 'hippoo' ),
        'scope'        => 'read_write',
        'user_id'      => $key,
        'return_url'   => $return_url,
        'callback_url' => $callback_url
    ];
    
    $query_string = http_build_query($params);
    $url          = $store_url . $endpoint . '?' . $query_string;

    if (headers_sent()) {
        // Fallback: Output JavaScript redirect
        echo '<script>window.location.href="' . $url . '";</script>';
        exit;
    }
    
    wp_redirect($url);
    exit;
}

function hippoo_save_token_callback($data) {
    $response = array();
    $msg = "";
    if (isset($data['token_id'])) {
        $token_id = $data['token_id'];
        $file     = hippoo_get_temp_dir() . 'hippoo_' . $token_id . '.json';
        $token    = file_get_contents('php://input');
        file_put_contents($file, $token);

        $msg = 'Token Saved';

    } else {
        $msg = 'No Token';
    }
    $response['Message'] = $msg;
    return new WP_REST_Response($response, 200);
}

function hippoo_show_token($data) {
    $response = array();
    $msg = "";
    # Remove all old files
    $tokens = glob(hippoo_get_temp_dir() . 'hippoo_*.json');
    foreach ($tokens as $t)
    {
        if (time() - filemtime($t) > 20000) {
            wp_delete_file($t);
        }
    }
    
    # Get the token
    if (isset($data['token_id'])){
        $token_id = $data['token_id'];
        $file     = hippoo_get_temp_dir() . 'hippoo_' . $token_id . '.json';

        # Check if the token is not too old
        if (file_exists($file)) {
            $token = file_get_contents($file);
            wp_delete_file($file);
            $token_json = json_decode($token);
            return new WP_REST_Response ($token_json, 200);
        } else {
            $msg = 'Unauthenticated, No Token Reauthenticate';

            $response['Message'] = $msg;
            return new WP_REST_Response ($response, 401);
        }
        
    } else {
        $msg = 'Unauthenticated, No Token Data';
        $response['Message'] = $msg;
        return new WP_REST_Response ($response, 401);
    }

    $response['Message'] = $msg;
    return new WP_REST_Response ($response, 401);
   
}

function hippoo_returned($data) {
    $returned_html_template = "<!DOCTYPE html>
        <html>
        <head>
        <title>Auto Redirect</title>
        <script type=\"text/javascript\">
            window.onload = function() {
            window.location.href = \"LINK\";
            };
        </script>
        </head>
        <body>
        <h1>Auto Redirect</h1>
        <h3>MSG</h3>
        <p>This page will automatically redirect in a few seconds...</p>
        </body>
        </html>";


    if (isset($data['token_id'])) {
        $token_id   = $data['token_id'];

        $msg        = __( 'You can get the data from here', 'hippoo' );
        $token_link = "hippoo://app/login/?token=" . $token_id;

        $returned_html_template = str_replace("LINK", $token_link, $returned_html_template);
        $returned_html_template = str_replace("MSG", $msg, $returned_html_template);
    } else {
        $msg                    = __( 'Unauthenticated, No Token Data', 'hippoo' );
        $returned_html_template = str_replace("MSG", $msg, $returned_html_template);
    }

    header('Content-Type: text/html;charset=utf-8;');
    echo wp_kses( $returned_html_template, array(
        'script' => array(
            'type' => true,
            'src' => true,
        ),
        ) );

}

function hippoo_config($data) {
    if (function_exists('hippoo_config')) {
        $plugin_data = get_plugin_data( hippoo_main_file_path );
        $plugin_version = $plugin_data['Version'];
        $response = array(
            'hippoo' => 'true',
            'hippoo_plugin_version' => $plugin_version,
            'lang' => get_bloginfo('language'),
            'currency' => get_option('woocommerce_currency'),
            'weight_unit' => get_option('woocommerce_weight_unit'),
            'dimension_unit' => get_option('woocommerce_dimension_unit'),
        );
        
        return new WP_REST_Response($response);
    }
}

function hippoo_shop_config($request) {
    $settings = get_option('hippoo_settings', []);
    $pwa_enabled = isset($settings['pwa_plugin_enabled']) && $settings['pwa_plugin_enabled'];
    $route_name = isset($settings['pwa_route_name']) ? $settings['pwa_route_name'] : 'hippooshop';
    $custom_css = isset($settings['pwa_custom_css']) ? $settings['pwa_custom_css'] : null;

    $response = array(
        'nonce' => wp_create_nonce('wc_store_api'),
        'currency' => get_option('woocommerce_currency'),
        'shop_name' => get_bloginfo('name'),
        'direction' => is_rtl() ? 'rtl' : 'ltr',
        'country' => WC()->countries->get_base_country(),
        'language' => strtoupper(substr(get_bloginfo('language'), 0, 2)),
        'custom_css_url' => ($pwa_enabled && !empty($custom_css)) ? home_url($route_name . '/custom.css/') : null,
    );

    return new WP_REST_Response($response, 200);
}

function hippoo_get_countries($request) {
    $countries = WC()->countries->get_countries();
    $response = array();

    foreach ($countries as $code => $name) {
        $response[] = array(
            'code' => $code,
            'name' => $name
        );
    }

    return new WP_REST_Response($response, 200);
}

function hippoo_get_states($request) {
    $country_code = $request['country_code'];

    if (!preg_match('/^[A-Z]{2}$/', $country_code)) {
        return new WP_Error(
            'invalid_country_code',
            __('Country code must be a valid two-letter uppercase code.', 'hippoo'),
            array('status' => 400)
        );
    }

    $countries = WC()->countries->get_countries();
    if (!array_key_exists($country_code, $countries)) {
        return new WP_Error('invalid_country', __('Invalid or unauthorized country code.', 'hippoo'), array('status' => 404));
    }

    $states = WC()->countries->get_states($country_code);
    $response = array();

    if (is_array($states) && !empty($states)) {
        foreach ($states as $code => $name) {
            $response[] = array(
                'code' => $code,
                'name' => $name
            );
        }
    }

    return new WP_REST_Response($response, 200);
}


/*
* Enrich order response with total weight and total items
*/
add_filter('woocommerce_rest_prepare_shop_order_object', 'hippoo_enrich_product_order_object', 10, 3);

function hippoo_enrich_product_order_object($response, $order, $request) {
    if (empty($response->data)) {
        return $response;
    }

    // Calculate total weight and total items
    $total_weight = 0;
    $total_items = 0;

    foreach ($order->get_items() as $item) {
        $quantity = $item->get_quantity();
        $total_items += $quantity;

        $product = $item->get_product();
        if ($product && $product->get_weight()) {
            $total_weight += $product->get_weight() * $quantity;
        }
    }

    $total_weight = round($total_weight, 2);

    // Retrieve weight unit from WooCommerce settings
    $weight_unit = get_option('woocommerce_weight_unit');

    // Add total weight, weight unit, and total items to response data
    $response->data['total_weight'] = $total_weight;
    $response->data['weight_unit'] = $weight_unit;
    $response->data['total_items'] = $total_items;

    // Set correct image (variation if exists) for each line item
    if (!empty($response->data['line_items'])) {
        foreach ($response->data['line_items'] as $item_id => $item_values) {
            $product_id   = $item_values['product_id'];
            $variation_id = isset($item_values['variation_id']) ? $item_values['variation_id'] : 0;

            $image_url = '';

            if ($variation_id && $variation_id != $product_id) {
                // Try to get variation image
                $variation = wc_get_product($variation_id);
                if ($variation && $variation->get_image_id()) {
                    $image_url = wp_get_attachment_image_url($variation->get_image_id(), 'thumbnail');
                }
            }

            // If no variation image, fallback to product image
            if (empty($image_url)) {
                $img_id = get_post_thumbnail_id($product_id);
                if ($img_id) {
                    $image_url = wp_get_attachment_image_url($img_id, 'thumbnail');
                }
            }

            $response->data['line_items'][$item_id]['image'] = $image_url;
        }
    }

    return $response;
}


/*
* Set product images from API Functions
*/
function hippoo_pif_get_url_attachment($data){
    $fin       = wp_upload_bits($data['name'],null,base64_decode($data['content']));
    $file_name = basename( $fin['file'] );
    $file_type = wp_check_filetype( $file_name, null );

    $post_info = array(
        'guid'           => $fin['url'],
        'post_mime_type' => $file_type['type'],
        'post_title'     => sanitize_file_name($file_name),
        'post_content'   => '',
        'post_status'    => 'inherit',
    );
    $attach_id   = wp_insert_attachment( $post_info, $fin['file'] );
    $attach_data = wp_generate_attachment_metadata($attach_id,$fin['file']);
    wp_update_attachment_metadata($attach_id,$attach_data);
    return $attach_id;
}

function hippoo_pif_product_images_del($data){
    if($data['cnt']==0)
        delete_post_thumbnail($data['id']);
    else{
        $imgs = explode(",", get_post_meta($data['id'],'_product_image_gallery',true));
        
        $cnt  = $data['cnt'] -1;
        if(isset($imgs[$cnt])){
            unset($imgs[$cnt]);
            update_post_meta($data['id'],'_product_image_gallery',implode(',',$imgs));
            return new WP_REST_Response( __( 'Image deleted successfully.', 'hippoo' ), 200);
        }else
            return new WP_REST_Response( __( 'Image not found!', 'hippoo' ), 404);
    }
}


/*
* Set product images from API Routes
*/
add_action( 'rest_api_init', function () {

    register_rest_route( 'wc/v3', 'productsimg/(?P<id>\d+)/imgs', array(
        'methods'             => 'POST',
        'callback'            => 'hippoo_pif_product_images',
        'permission_callback' => 'hippoo_pif_api_permissions_check',
    ));

    register_rest_route( 'wc/v3', 'productsimg/(?P<id>\d+)/dlmg/(?P<cnt>\d+)', array(
        'methods'             => 'GET',
        'callback'            => 'hippoo_pif_product_images_del',
        'permission_callback' => 'hippoo_pif_api_permissions_check',
    ));

} );

function hippoo_pif_api_permissions_check(){
    return current_user_can( 'edit_others_posts' );
}

function hippoo_pif_product_images($data){

    $arr = $data->get_json_params();

    if(empty($arr['imgs']))
        return ['There is not any images!'];

    $gallery = [];
    foreach($arr['imgs'] as $i=>$img){
        $img_id = hippoo_pif_get_url_attachment($img);
        if( $i == 0 )
            set_post_thumbnail($data['id'], $img_id);
        else
            $gallery[] = $img_id;
    }
    if(!empty($gallery))
        update_post_meta($data['id'], '_product_image_gallery', implode(',',$gallery));

    return new WP_REST_Response( __( 'Image saved successfully.', 'hippoo' ), 200);
}


/**
 * Conditionally add filters for order status notifications.
 */
add_action('init', 'hippoo_add_order_status_filters');

function hippoo_add_order_status_filters() {
    $settings = get_option('hippoo_settings');
    if (empty($settings)) {
        return;
    }
    foreach ($settings as $key => $value) {
        if (strpos($key, 'send_notification_wc-') === 0 && $value === true) {
            $status_key = str_replace('send_notification_wc-', '', $key);
            add_filter("woocommerce_order_status_{$status_key}", "hippoo_send_notification_by_order");
        }
    }
}

# Send notifications to mobile
function hippoo_send_notification_by_order($order_id) {
    $order = wc_get_order($order_id);

    $home_url = home_url();
    $parsed_url = wp_parse_url($home_url);
    $cs_hostname = $parsed_url['host'];
    
    $order_status = $order->get_status();
    $order_id = $order->get_id();
    $order_count = $order->get_item_count();
    $order_total_price = $order->get_total();
    $order_currency = $order->get_currency();

    $title = "Order {$order_id} {$order_status}!";
    $content  = $order_count . " Items";
    $content .= " | " . $order_total_price . $order_currency;
    $content .= " | " . "Status: " . $order_status;
    
    $args = array(
        'body' => array(
            'cs_hostname'=> $cs_hostname,
            'notif_data' => array(
                'title' => $title,
                'content' => $content
                )
            )
    );
    
    $response = wp_remote_post(hippoo_proxy_notifiction_url, $args);

    if (!is_wp_error($response))
        $body = wp_remote_retrieve_body($response);
}

# Send out of stock notifications to mobile
add_action('woocommerce_no_stock_notification', 'hippoo_woocommerce_no_stock_notification', 10, 1);

function hippoo_woocommerce_no_stock_notification($product) {
    update_post_meta($product->get_id(), 'out_stock_time', gmdate('Y-m-d H:i:s'));
    hippoo_out_of_stock_send_notification_by_prodcut($product);
}

function hippoo_out_of_stock_send_notification_by_prodcut($product) {

    $home_url = home_url();
    $parsed_url = wp_parse_url($home_url);
    $cs_hostname = $parsed_url['host'];
    
    $product_name = $product->get_name();
    $product_image_url = get_the_post_thumbnail_url($product->get_id(), 'thumbnail');
    $url = "hippoo://app/outofstock/?product_id=" . $product->get_id();

    $title = "Product is out of stock!";
    $content = "Product " . $product_name;
    
    $args = array(
        'body' => array(
            'cs_hostname'=> $cs_hostname,
            'notif_data' => array(
                'title' => $title,
                'content' => $content,
                'image' => $product_image_url,
                'largeIcon' => $product_image_url,
                'url' => $url
                )
            )
    );
    
    $response = wp_remote_post(hippoo_proxy_notifiction_url, $args);

    if (!is_wp_error($response))
        $body = wp_remote_retrieve_body($response);
}


/*
* WC Store Settings API Route
*/
add_action( 'rest_api_init', function () {

    register_rest_route( 'wc/store/v1', 'settings', array(
        'methods'             => 'GET',
        'callback'            => 'hippoo_wc_store_settings',
        'permission_callback' => '__return_true',
    ));

    register_rest_route( 'wc/store/v1', 'cart/count', array(
        'methods'             => 'GET',
        'callback'            => 'hippoo_cart_count',
        'permission_callback' => '__return_true'
    ));

} );

function hippoo_wc_store_settings() {
    $shop_title = get_bloginfo('name');
    $cart_url = wc_get_cart_url();
    $base_url = get_site_url();

    $response_data = array(
        'shop_title' => $shop_title,
        'cart_url'   => $cart_url,
        'base_url'   => $base_url,
    );

    return new WP_REST_Response($response_data, 200);
}


/*
* Add author information to WC order note API response
*/
add_filter( 'woocommerce_new_order_note_data', 'hippoo_wc_save_order_note_author', 10, 1 );

function hippoo_wc_save_order_note_author( $data ) {
    if ( is_user_logged_in() ) {
        $user = get_user_by( 'id', get_current_user_id() );
        $data['user_id'] = $user->ID;
        $data['comment_author'] = $user->display_name;
        $data['comment_author_email'] = $user->user_email;
    }
    return $data;
}

add_filter( 'woocommerce_rest_prepare_order_note', 'hippoo_wc_prepare_order_note_author', 10, 3 );

function hippoo_wc_prepare_order_note_author( $response, $note, $request ) {
    $data = $response->get_data();
    
    if ( $note->user_id > 0 ) {
        $user = get_user_by( 'id', $note->user_id );
    } else {
        $user = get_user_by( 'email', $note->comment_author_email );
    }
    
    if ( $user ) {
        $data['author_id'] = $user->ID;
        $data['author_email'] = $user->user_email;
    } else {
        $data['author_id'] = 0;
        $data['author_email'] = '';
    }
    
    $response->set_data($data);
    return $response;
}


/*
* Enrich cart response with detailed payment method information
*/
add_filter('rest_request_after_callbacks', function ($response, $handler, $request) {
    $route = $request->get_route();
    if ($route === '/wc/store/v1/cart') {
        if (!class_exists('WooCommerce') || !WC()->payment_gateways()) {
            return array();
        }

        $data = $response->get_data();
        $payment_methods = isset($data['payment_methods']) ? $data['payment_methods'] : array();
        $available_gateways = WC()->payment_gateways()->get_available_payment_gateways();
        $payment_methods_detailed = array();

        foreach ($available_gateways as $gateway) {
            if (!in_array($gateway->id, $payment_methods, true)) {
                continue;
            }

            $icon = null;
            if (!empty($gateway->icon)) {
                $icon_url = $gateway->icon;
                if (strpos($icon_url, 'http') !== 0) {
                    $icon_url = site_url($icon_url);
                }
                $icon = $icon_url;
            }

            $payment_methods_detailed[] = array(
                'id'          => $gateway->id,
                'title'       => $gateway->get_title() ?: $gateway->id,
                'description' => $gateway->get_description() ?: '',
                'icon'        => $icon,
            );
        }
        
        $data['payment_methods_detailed'] = $payment_methods_detailed;
        $response->set_data($data);
    }
    return $response;
}, 10, 3);

function hippoo_cart_count($request) {
    $cart = WC()->cart;
    $count = $cart ? $cart->get_cart_contents_count() : 0;
    $response = array('count' => $count);
    return new WP_REST_Response($response, 200);
}


/*
* Custom Event Notification API Route
*/
add_action('plugins_loaded', function () {
    if (class_exists('WC_REST_Controller')) {
        require_once __DIR__ . '/web_api_notification.php';
        $controller = new HippooEventNotificationController();
        $current_db_version = get_option('hippoo_notification_db_version');
        if ($current_db_version !== HippooEventNotificationController::DB_VERSION) {
            $controller->init_database();
        }
        $controller->register_hooks();
        add_action('rest_api_init', function () use ($controller) {
            $controller->register_routes();
        });
    }
}, 999);