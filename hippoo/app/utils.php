<?php
function hippoo_get_temp_dir() {
    $wp_upload_dir = wp_upload_dir();
    $temp_dir = implode( DIRECTORY_SEPARATOR, [ $wp_upload_dir['basedir'], 'hippoo', 'tmp' ] ) . DIRECTORY_SEPARATOR;
    
    if (!file_exists($temp_dir)) {
        // phpcs:ignore
        mkdir($temp_dir, 0755, true);
    }

    return $temp_dir;
}

function hippoo_get_product_by_slug( $products, $name ) {
    foreach ( $products as $product ) {
        if ( strcasecmp( $product['slug'], $name ) === 0 ) {
            return $product;
        }
    }
    return null;
}