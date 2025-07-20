<?php
// namespace Your_Namespace;

use Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController;

/**
 * Class Metabox.
 *
 * Example for how to add a metabox to the order screen that supports both HPOS and legacy order storage.
 * More information: https://github.com/woocommerce/woocommerce/wiki/High-Performance-Order-Storage-Upgrade-Recipe-Book#audit-for-order-administration-screen-functions
 */
class Metabox {

	public static $instance;

	/**
	 * Main Metabox Instance.
	 *
	 * Ensures only one instance of the Metabox is loaded or can be loaded.
	 *
	 * @return Metabox - Main instance.
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'add_meta_boxes', array( $this, 'address_validation_controls' ) );
	}

	/**
	 * Adds a metabox to the order edit screen for address validation.
	 */
	public function address_validation_controls() {
		$screen = wc_get_container()->get( CustomOrdersTableController::class )->custom_orders_table_usage_is_enabled()
		? wc_get_page_screen_id( 'shop-order' )
		: 'shop_order';

		add_meta_box(
			'address_validation_actions',
			__( 'Validate Address', 'hippoo' ),
			array( $this, 'render_address_validation_actions' ),
			$screen,
			'side',
			'high'
		);
	}

	/**
	 * Renders out the HTML shown in the metabox.
	 */
	public function render_address_validation_actions( $post_or_order_object ) {
		$order = ( $post_or_order_object instanceof WP_Post ) ? wc_get_order( $post_or_order_object->ID ) : $post_or_order_object;

		if ( ! $order ) {
			return;
		}

		// We can only validate US addresses.
		if ( $order->get_shipping_country() !== 'US' ) {
			return;
		}
		?>

		<div class="action-address-validate">
			<p>Validate the shipping address.</p>
			<button type="button" class="button format-address">Validate Address</button>
		</div>
		<?php
	}
}