<?php
/**
 * Plugin bootstrap.
 *
 * @package VehicleReportOrders
 */

declare( strict_types=1 );

namespace VehicleReportOrders;

defined( 'ABSPATH' ) || exit;

/**
 * Wires the plugin's services into WordPress.
 */
final class Plugin {

	/**
	 * Shared instance.
	 *
	 * @var self|null
	 */
	private static ?self $instance = null;

	/**
	 * Guards against booting twice.
	 *
	 * @var bool
	 */
	private bool $booted = false;

	/**
	 * Shared instance accessor.
	 *
	 * @return self
	 */
	public static function instance(): self {
		return self::$instance ??= new self();
	}

	/**
	 * Wire the plugin up once WordPress and WooCommerce are loaded.
	 *
	 * Without WooCommerce there is no order to attach a report to, so the
	 * plugin stays dormant and tells the administrator why.
	 *
	 * @return void
	 */
	public function boot(): void {
		if ( $this->booted ) {
			return;
		}
		$this->booted = true;

		if ( ! class_exists( 'WooCommerce' ) ) {
			add_action( 'admin_notices', array( $this, 'render_missing_woocommerce_notice' ) );

			return;
		}

		// Feature services are registered here as each milestone lands.
	}

	/**
	 * Explain why the plugin is inactive when WooCommerce is missing.
	 *
	 * @return void
	 */
	public function render_missing_woocommerce_notice(): void {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}

		printf(
			'<div class="notice notice-error"><p>%s</p></div>',
			esc_html__( 'Vehicle Report Orders needs WooCommerce to be installed and active.', 'vehicle-report-orders' )
		);
	}
}
