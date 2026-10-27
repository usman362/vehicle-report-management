<?php
/**
 * Storefront sections: packages, checks, delivery record and reviews.
 *
 * @package VehicleReportOrders
 */

declare( strict_types=1 );

namespace VehicleReportOrders\Storefront;

use VehicleReportOrders\Domain\OrderMeta;
use VehicleReportOrders\Domain\Package;
use VehicleReportOrders\Domain\PackageRepository;
use VehicleReportOrders\Domain\Settings;
use VehicleReportOrders\Domain\VehicleCheck;

defined( 'ABSPATH' ) || exit;

/**
 * The explanatory and commercial blocks the storefront is built from.
 *
 * Every figure these sections print is either configured by the merchant or
 * counted from this site's own orders. None of them is a marketing number:
 * where there is nothing true to say, the section renders nothing at all
 * rather than filling the space.
 */
final class Sections {

	private const STATS_TRANSIENT = 'vr_delivery_stats';

	/**
	 * Deliveries needed before a published figure means anything.
	 *
	 * Below this, one fast or one slow order moves the number enough to
	 * mislead, so nothing is published at all.
	 *
	 * @var int
	 */
	private const MIN_SAMPLE = 10;

	/**
	 * Register the shortcodes.
	 *
	 * @return void
	 */
	public function register(): void {
		add_shortcode( 'vehicle_report_packages', array( $this, 'packages' ) );
		add_shortcode( 'vehicle_report_checks', array( $this, 'checks' ) );
		add_shortcode( 'vehicle_report_stats', array( $this, 'stats' ) );
		add_shortcode( 'vehicle_report_reviews', array( $this, 'reviews' ) );
		add_shortcode( 'vehicle_report_contact_details', array( $this, 'contact_details' ) );

		add_action( 'woocommerce_single_product_summary', array( $this, 'render_package_checks' ), 21 );
		add_action( 'woocommerce_single_product_summary', array( $this, 'render_package_order_form' ), 29 );
		add_action( 'woocommerce_before_single_product', array( $this, 'replace_add_to_cart' ) );
		add_action( 'save_post_product', array( $this, 'forget_stats' ) );
		add_action( 'woocommerce_order_status_changed', array( $this, 'forget_stats' ) );
	}

	/**
	 * The package comparison.
	 *
	 * @param array<string, mixed>|string $attributes Shortcode attributes.
	 *
	 * @return string
	 */
	public function packages( $attributes = array() ): string {
		$attributes = shortcode_atts(
			array( 'heading' => '' ),
			is_array( $attributes ) ? $attributes : array(),
			'vehicle_report_packages'
		);

		$packages = PackageRepository::all();

		if ( array() === $packages ) {
			return '';
		}

		$heading = (string) $attributes['heading'];

		ob_start();
		include __DIR__ . '/../../templates/storefront/packages.php';

		return (string) ob_get_clean();
	}

	/**
	 * What a report can answer, one block per check.
	 *
	 * @param array<string, mixed>|string $attributes Shortcode attributes.
	 *
	 * @return string
	 */
	public function checks( $attributes = array() ): string {
		$attributes = shortcode_atts(
			array(
				'heading' => '',
				'only'    => '',
			),
			is_array( $attributes ) ? $attributes : array(),
			'vehicle_report_checks'
		);

		$only = array_filter( array_map( 'trim', explode( ',', (string) $attributes['only'] ) ) );
		$keys = array() !== $only ? VehicleCheck::filter( $only ) : VehicleCheck::keys();

		if ( array() === $keys ) {
			return '';
		}

		$heading = (string) $attributes['heading'];

		ob_start();
		include __DIR__ . '/../../templates/storefront/checks.php';

		return (string) ob_get_clean();
	}

	/**
	 * This site's own delivery record.
	 *
	 * Counted from delivered orders. Before anything has been delivered there
	 * is nothing honest to show, so the section disappears.
	 *
	 * @return string
	 */
	public function stats(): string {
		$stats = $this->delivery_stats();

		if ( $stats['delivered'] < self::MIN_SAMPLE ) {
			return '';
		}

		ob_start();
		include __DIR__ . '/../../templates/storefront/stats.php';

		return (string) ob_get_clean();
	}

	/**
	 * Reviews the merchant has entered, or nothing.
	 *
	 * @param array<string, mixed>|string $attributes Shortcode attributes.
	 *
	 * @return string
	 */
	public function reviews( $attributes = array() ): string {
		$attributes = shortcode_atts(
			array( 'heading' => '' ),
			is_array( $attributes ) ? $attributes : array(),
			'vehicle_report_reviews'
		);

		$reviews = Settings::reviews();

		if ( array() === $reviews ) {
			return '';
		}

		$heading = (string) $attributes['heading'];

		ob_start();
		include __DIR__ . '/../../templates/storefront/reviews.php';

		return (string) ob_get_clean();
	}

	/**
	 * Email, phone and postal address, each shown only once supplied.
	 *
	 * @return string
	 */
	public function contact_details(): string {
		$email   = (string) Settings::get( 'support_email' );
		$phone   = Settings::phone();
		$address = Settings::postal_address();
		$company = trim( (string) Settings::get( 'company_number' ) );
		$vat     = trim( (string) Settings::get( 'vat_number' ) );

		ob_start();
		include __DIR__ . '/../../templates/storefront/contact-details.php';

		return (string) ob_get_clean();
	}

	/**
	 * On a package's own product page, list what the tier promises.
	 *
	 * @return void
	 */
	public function render_package_checks(): void {
		$package = $this->current_package();

		if ( null === $package ) {
			return;
		}

		// h2: the product title above it is the page's h1, so h3 would skip a level.
		echo '<div class="vr-package-checks"><h2>' . esc_html__( 'What this package covers', 'vehicle-report-orders' ) . '</h2><ul>';

		foreach ( $package->checks() as $key ) {
			$check = VehicleCheck::get( $key );

			if ( null === $check ) {
				continue;
			}

			printf(
				'<li><strong>%1$s</strong><br><span class="vr-check-limit">%2$s</span></li>',
				esc_html( $check['label'] ),
				esc_html( $check['limit'] )
			);
		}

		echo '</ul><p class="vr-check-limit">'
			. esc_html__( 'Each check is run against the sources we are licensed to use. Where a check cannot be completed for your vehicle, the report says so and explains why.', 'vehicle-report-orders' )
			. '</p></div>';
	}

	/**
	 * On a package's own page, start the order from our own form.
	 *
	 * @return void
	 */
	public function render_package_order_form(): void {
		$package = $this->current_package();

		if ( null === $package ) {
			return;
		}

		echo do_shortcode(
			sprintf(
				'[%1$s package="%2$s" heading="%3$s"]',
				OrderForm::SHORTCODE,
				esc_attr( $package->slug() ),
				esc_attr(
					sprintf(
						/* translators: %s: package name. */
						__( 'Order the %s package', 'vehicle-report-orders' ),
						$package->name()
					)
				)
			)
		);
	}

	/**
	 * Take WooCommerce's own add-to-cart button off a package page.
	 *
	 * Buying straight from it would skip the question of which vehicle the
	 * report is about, so the page offers our own form instead.
	 *
	 * @return void
	 */
	public function replace_add_to_cart(): void {
		if ( null === $this->current_package() ) {
			return;
		}

		remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart', 30 );
	}

	/**
	 * The package whose page is being viewed, if any.
	 *
	 * @return Package|null
	 */
	private function current_package(): ?Package {
		$product = function_exists( 'wc_get_product' ) ? wc_get_product() : null;

		if ( ! $product ) {
			return null;
		}

		return PackageRepository::find_by_product( (int) $product->get_id() );
	}

	/**
	 * Drop the cached figures when an order or a product changes.
	 *
	 * @return void
	 */
	public function forget_stats(): void {
		delete_transient( self::STATS_TRANSIENT );
	}

	/**
	 * Delivered count and median delivery time, counted from real orders.
	 *
	 * @return array{delivered: int, median_minutes: int}
	 */
	private function delivery_stats(): array {
		$cached = get_transient( self::STATS_TRANSIENT );

		if ( is_array( $cached ) && isset( $cached['delivered'], $cached['median_minutes'] ) ) {
			return array(
				'delivered'      => (int) $cached['delivered'],
				'median_minutes' => (int) $cached['median_minutes'],
			);
		}

		$stats = array(
			'delivered'      => 0,
			'median_minutes' => 0,
		);

		if ( ! function_exists( 'wc_get_orders' ) ) {
			return $stats;
		}

		$orders = wc_get_orders(
			array(
				'limit'      => 500,
				'status'     => array( 'wc-completed', 'wc-processing' ),
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Cached for an hour and invalidated when an order changes.
				'meta_query' => array(
					array(
						'key'     => OrderMeta::DELIVERED_AT,
						'compare' => 'EXISTS',
					),
				),
			)
		);

		$durations = array();

		foreach ( is_array( $orders ) ? $orders : array() as $order ) {
			if ( ! method_exists( $order, 'get_meta' ) ) {
				continue;
			}

			$delivered = strtotime( (string) $order->get_meta( OrderMeta::DELIVERED_AT ) );
			$paid      = strtotime( (string) $order->get_meta( OrderMeta::PAYMENT_VERIFIED_AT ) );

			if ( false === $delivered || false === $paid || $delivered <= $paid ) {
				continue;
			}

			$durations[] = (int) round( ( $delivered - $paid ) / MINUTE_IN_SECONDS );
		}

		if ( array() !== $durations ) {
			sort( $durations );
			$middle = (int) floor( ( count( $durations ) - 1 ) / 2 );

			$stats['delivered']      = count( $durations );
			$stats['median_minutes'] = $durations[ $middle ];
		}

		set_transient( self::STATS_TRANSIENT, $stats, HOUR_IN_SECONDS );

		return $stats;
	}
}
