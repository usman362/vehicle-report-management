<?php
/**
 * Whether customers can actually reach the store.
 *
 * @package VehicleReportOrders
 */

declare( strict_types=1 );

namespace VehicleReportOrders\Admin;

defined( 'ABSPATH' ) || exit;

/**
 * Reports WooCommerce's coming-soon mode on this plugin's own screens.
 *
 * Coming-soon mode hides the shop, cart and checkout from everyone except
 * signed-in administrators. Staff testing an order while signed in see a
 * working checkout and have no way of knowing customers are being turned
 * away, so the queue and settings screens say so plainly.
 */
final class StoreVisibility {

	/**
	 * Hide the whole site from visitors.
	 */
	public const HIDDEN_SITE = 'site';

	/**
	 * Hide only the shop, cart and checkout.
	 */
	public const HIDDEN_STORE = 'store';

	/**
	 * Register the notice.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'admin_notices', array( $this, 'notice' ) );
	}

	/**
	 * Whether the current screen belongs to this plugin.
	 *
	 * @return bool
	 */
	private function on_plugin_screen(): bool {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

		if ( ! is_object( $screen ) ) {
			return false;
		}

		foreach ( array( Menu::SLUG_QUEUE, Menu::SLUG_SETTINGS, Menu::SLUG_AUDIT ) as $slug ) {
			if ( str_contains( (string) $screen->id, $slug ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * What, if anything, is hidden from customers.
	 *
	 * @return string|null One of the HIDDEN_* constants, or null when the store is open.
	 */
	public static function hidden(): ?string {
		if ( 'yes' !== get_option( 'woocommerce_coming_soon' ) ) {
			return null;
		}

		return 'yes' === get_option( 'woocommerce_store_pages_only' )
			? self::HIDDEN_STORE
			: self::HIDDEN_SITE;
	}

	/**
	 * Warn on the plugin's own screens.
	 *
	 * @return void
	 */
	public function notice(): void {
		if ( ! $this->on_plugin_screen() ) {
			return;
		}

		$hidden = self::hidden();

		if ( null === $hidden ) {
			return;
		}

		$message = self::HIDDEN_STORE === $hidden
			? __( 'WooCommerce is in coming-soon mode for store pages. Customers cannot reach the shop, cart or checkout, so no orders can arrive. You can still see them because you are signed in.', 'vehicle-report-orders' )
			: __( 'WooCommerce is in coming-soon mode for the whole site. Visitors see a placeholder instead of the storefront, so no orders can arrive. You can still see it because you are signed in.', 'vehicle-report-orders' );

		printf(
			'<div class="notice notice-warning"><p><strong>%1$s</strong> %2$s %3$s</p></div>',
			esc_html__( 'The store is hidden from customers.', 'vehicle-report-orders' ),
			esc_html( $message ),
			sprintf(
				'<a href="%s">%s</a>',
				esc_url( admin_url( 'admin.php?page=wc-settings&tab=site-visibility' ) ),
				esc_html__( 'Change site visibility', 'vehicle-report-orders' )
			)
		);
	}
}
