<?php
/**
 * Plugin Name:       Vehicle Report Orders
 * Description:       Order capture, staff fulfilment queue and secure manual report delivery for the UK vehicle report service. No automatic vehicle-data lookup, CRM or outbound marketing.
 * Version:           0.1.0
 * Requires at least: 6.6
 * Requires PHP:      8.1
 * Requires Plugins:  woocommerce
 * Author:            Vehicle Report UK
 * Text Domain:       vehicle-report-orders
 * Domain Path:       /languages
 * License:           GPL-2.0-or-later
 *
 * @package VehicleReportOrders
 */

declare( strict_types=1 );

namespace VehicleReportOrders;

defined( 'ABSPATH' ) || exit;

const VERSION     = '0.1.0';
const PLUGIN_FILE = __FILE__;

require_once __DIR__ . '/src/Autoloader.php';
Autoloader::register();

/**
 * Declare compatibility with WooCommerce High-Performance Order Storage.
 *
 * Declared because the plugin reads and writes order data exclusively through
 * the WooCommerce CRUD API, never through direct wp_posts/postmeta queries.
 */
add_action(
	'before_woocommerce_init',
	static function (): void {
		if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', PLUGIN_FILE, true );
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', PLUGIN_FILE, true );
		}
	}
);

add_action( 'plugins_loaded', static fn() => Plugin::instance()->boot() );

register_activation_hook( PLUGIN_FILE, array( Activation::class, 'activate' ) );
register_deactivation_hook( PLUGIN_FILE, array( Activation::class, 'deactivate' ) );
