<?php

add_filter( 'manage_woocommerce_page_wc-orders_columns', 'add_wc_order_list_hippoo_columns' );
function add_wc_order_list_hippoo_columns( $columns ) {
    $settings = get_option( 'hippoo_invoice_settings' );
    $new_columns = array();
    foreach ($columns as $key => $value) {
        $new_columns[$key] = $value;

        if ( $key === 'order_status' && $settings['show_barcode_order_list'] ) {
            $new_columns['order_id_barcode'] = 'Order ID Barcode';
        }
    }

    $new_columns['order_print'] = 'Print';
    return $new_columns;
}

add_action( 'manage_woocommerce_page_wc-orders_custom_column', 'populate_order_hippoo_columns', 10, 2);
function populate_order_hippoo_columns( $column, $order ) {
    $order_id = $order->get_id();
    switch ($column) {
        case 'order_id_barcode':
            $barcode = base64_encode( generate_barcode_html( $order_id ) );
            echo '<img src="' . HIPPOO_INVOICE_PLUGIN_URL . 'assets/img/barcode-scanner.svg" data-src="' . $barcode . '" data-text="' . $order_id . '" class="hippoo-tooltip" />';
            break;

        case 'order_print':
            echo '<a href="?post_id=' . $order_id . '&download_type=factor" target="_blank" data-barcode="' .
                $order_id . '" data-type="factor" data-text="Invoice" class="hippoo-tooltip" title="Invoice"><img src="' .
                HIPPOO_INVOICE_PLUGIN_URL . 'assets/img/invoice-factor.svg" /></a>';
            
            echo '<a href="?post_id=' . $order_id . '&download_type=label" target="_blank" data-barcode="' . 
                $order_id . '" data-type="label" data-text="Shipping Label" class="hippoo-tooltip" title="Shipping Label"><img src="' . 
                HIPPOO_INVOICE_PLUGIN_URL . 'assets/img/shipping-label.svg" /></a>';
            break;
    }
}


add_action('woocommerce_admin_order_item_headers', 'add_stock_status_header');
add_action('woocommerce_admin_order_item_values', 'add_barcode_value');


function add_stock_status_header() {
    echo '<th class="quantity sortable" data-sort="string-ins">' . __('Barcode', 'hippoo-invoice') . '</th>';
}

function add_barcode_value($product) {
    if (!is_a($product, 'WC_Product')) {
        return;
    }

    $sku = $product->get_sku();
    $barcode = base64_encode(generate_barcode_html($sku));

    echo '<td class="barcode" width="1%"><img src="' . HIPPOO_INVOICE_PLUGIN_URL . 'assets/img/barcode-scanner.svg" data-src="' . $barcode . '" data-text="' . $sku . '" class="hippoo-tooltip" /></td>';
}



// function adding_custom_meta_boxes( $post_type, $post ) {
//     add_meta_box( 
//         'my-meta-box',
//         __( 'My Meta Box' ),
//         'render_custom_meta_box_content',
//         'post',
//         'normal',
//         'default'
//     );
// }
// add_action( 'add_meta_boxes', 'adding_custom_meta_boxes', 10, 2 );

// function add_custom_meta_box() {
//     add_meta_box(
//         'custom_order_meta_box', // Meta box ID
//         __('Custom Meta Box Title'), // Title displayed in the meta box
//         'render_custom_meta_box_content', // Callback function to render content
//         'post', // Post type to display meta box (in this case, WooCommerce orders)
//         'normal', // Context: 'normal', 'side', or 'advanced'
//         'default' // Priority: 'high', 'core', 'default', or 'low'
//     );
// }
// add_action('add_meta_boxes', 'add_custom_meta_box',  10, 2 );

// function render_custom_meta_box_content($post) {
//     // Output your custom meta box content here
//     echo 'This is a custom meta box content for WooCommerce orders.';
// }

// add_action('add_meta_boxes_shop_order', 'add_meta_boxes'); // Corrected hook for meta boxes


// // Add custom meta box to WooCommerce orders page

// // add_action( 'add_meta_boxes', function( string $post_type, WP_Post $post ): void {} );
// function custom_order_meta_box() {
//     add_meta_box(
//         'custom-order-meta-box',
//         __( 'Custom Meta Box', 'webkul' ),
//         'render_custom_meta_box_content',
//         'shop_order_placehold',
//         'advanced',
//         'core'
//     );
// }
// add_action( 'add_meta_boxes', 'custom_order_meta_box' );

// function add_meta_boxes() {
//     echo "Kian";
//     $settings = get_option('hippoo_invoice_settings');
//     var_dump($settings);
//     // if ($settings['show_barcode_order_details']) {
//         add_meta_box(
//             'order_id_barcode_meta',
//             __('Order ID Barcode', 'hippoo-invoice'),
//             'render_order_barcode_meta_box',
//             'shop_order',
//             'side',
//             'high'
//         );
//     // }

//     add_meta_box(
//         'invoice_and_label_meta',
//         __('Invoice and Label', 'hippoo-invoice'),
//         'render_invoice_and_label_meta_box',
//         'shop_order',
//         'side',
//         'default'
//     );
// }


function render_order_barcode_meta_box($post) {
    $order_id = $post->ID;
    $barcode = generate_barcode_html($order_id);

    echo $barcode . '<br><strong>' . $order_id . '</strong>';
}

function render_invoice_and_label_meta_box($post) {
    $order_id = $post->ID;

    echo '<a href="?post_id=' . $order_id . '&download_type=factor" target="_blank"><img src="' . HIPPOO_INVOICE_PLUGIN_URL . 'assets/img/invoice-factor.svg" /> Print invoice</a>';
    echo '<br>';
    echo '<a href="?post_id=' . $order_id . '&download_type=label" target="_blank"><img src="' . HIPPOO_INVOICE_PLUGIN_URL . 'assets/img/shipping-label.svg" /> Print shipping label</a>';
}


add_action( 'add_meta_boxes', 'custom_order_meta_box' );
/**
 * Add custom meta box.
 *
 * @return void
 */
function custom_order_meta_box() {
	add_meta_box(
		'custom-order-meta-box',
		__( 'Custom Meta Box', 'webkul' ),
		'custom_order_meta_box_callback',
		'post',
		'side',
		'high'
	);
}
function custom_order_meta_box_callback( $post ) {
	echo 'HI KIAN';
}

require_once HIPPOO_INVOICE_PLUGIN_PATH . 'src/woocommerce/order-test.php';
new Metabox();