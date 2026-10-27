<?php
/**
 * Merchant-configurable settings.
 *
 * @package VehicleReportOrders
 */

declare( strict_types=1 );

namespace VehicleReportOrders\Domain;

use DateTimeZone;

defined( 'ABSPATH' ) || exit;

/**
 * Reads and writes the settings the merchant controls.
 *
 * Values the merchant has not supplied stay empty on purpose. Nothing here
 * invents a price, a delivery window or a data source: until they are entered,
 * `missing_for_live()` lists what is outstanding and the storefront refuses to
 * promise anything it cannot keep.
 */
final class Settings {

	public const OPTION = 'vr_settings';

	/**
	 * Default values: deliberately empty where only the merchant can decide.
	 *
	 * @return array<string, mixed>
	 */
	public static function defaults(): array {
		return array(
			'sla_hours'           => 0,                        // Business hours promised before delivery.
			'business_days'       => array( 1, 2, 3, 4, 5 ),   // ISO-8601 weekday numbers the team works.
			'business_open'       => '09:00',
			'business_close'      => '17:00',
			'support_email'       => '',
			'trader_name'         => '',
			'terms_version'       => '',
			'data_sources'        => '',     // One source per line, shown on the report-sources page.
			'sample_report_url'   => '',     // Merchant-supplied example report, shown on the sample page.
			'legal_copy_approved' => false,  // Terms, privacy, cookie and refund copy reviewed by the merchant's lawyer.
			'report_product_id'   => 0,      // WooCommerce product behind the entry-level package.
			'packages'            => array(), // Tiers; empty means the shipped defaults, which carry no prices.
			'download_ttl_hours'  => 72,     // Lifetime of a report download link.
			'max_delivery_tries'  => 5,
			'live_mode_confirmed' => false,  // Merchant has signed off price, SLA, sources and policies.

			// How the merchant can be reached. A UK trader has to publish these.
			'phone'               => '',
			'postal_address'      => '',
			'company_number'      => '',
			'vat_number'          => '',

			// Optional support channels; each stays hidden until it is filled in.
			'whatsapp_number'     => '',     // International format, digits only.
			'live_chat_src'       => '',     // Embed script URL for the merchant's own chat provider.

			// What the order form asks for.
			'vehicle_types'       => array( 'car', 'motorcycle', 'van' ),
			'accept_vin'          => true,   // Allow a VIN where there is no current UK plate.

			// Search and sharing.
			'seo_description'     => '',     // Fallback meta description for pages that have none.
			'social_image_id'     => 0,      // Image used when a page is shared.

			// Trust signals. Both stay empty until the merchant supplies the real thing.
			'reviews'             => array(), // Real, attributed customer reviews only.
			'trust_logos'         => array(), // Attachment ids the merchant is entitled to display.
		);
	}

	/**
	 * All settings, defaults merged in.
	 *
	 * @return array<string, mixed>
	 */
	public static function all(): array {
		$stored = get_option( self::OPTION, array() );

		return array_merge( self::defaults(), is_array( $stored ) ? $stored : array() );
	}

	/**
	 * One setting.
	 *
	 * @param string $key Setting name.
	 *
	 * @return mixed
	 */
	public static function get( string $key ) {
		return self::all()[ $key ] ?? null;
	}

	/**
	 * Persist a set of settings, keeping anything not supplied.
	 *
	 * @param array<string, mixed> $values Sanitised values.
	 *
	 * @return void
	 */
	public static function update( array $values ): void {
		update_option( self::OPTION, array_merge( self::all(), $values ) );
	}

	/**
	 * The published data sources, one per line, as a list.
	 *
	 * @return array<int, string>
	 */
	public static function source_list(): array {
		$raw   = (string) self::get( 'data_sources' );
		$lines = preg_split( '/\r\n|\r|\n/', $raw );

		return array_values( array_filter( array_map( 'trim', is_array( $lines ) ? $lines : array() ) ) );
	}

	/**
	 * The vehicle types the order form accepts.
	 *
	 * @return array<int, string>
	 */
	public static function vehicle_types(): array {
		$stored = self::get( 'vehicle_types' );

		return is_array( $stored ) ? array_values( array_filter( array_map( 'strval', $stored ) ) ) : array();
	}

	/**
	 * Whether a VIN may be given instead of a registration.
	 *
	 * @return bool
	 */
	public static function accepts_vin(): bool {
		return (bool) self::get( 'accept_vin' );
	}

	/**
	 * The merchant's published phone number.
	 *
	 * @return string
	 */
	public static function phone(): string {
		return trim( (string) self::get( 'phone' ) );
	}

	/**
	 * The merchant's published postal address.
	 *
	 * @return string
	 */
	public static function postal_address(): string {
		return trim( (string) self::get( 'postal_address' ) );
	}

	/**
	 * A WhatsApp link for the configured number, or an empty string.
	 *
	 * @return string
	 */
	public static function whatsapp_url(): string {
		$digits = preg_replace( '/\D+/', '', (string) self::get( 'whatsapp_number' ) );

		if ( ! is_string( $digits ) || strlen( $digits ) < 8 ) {
			return '';
		}

		return 'https://wa.me/' . $digits;
	}

	/**
	 * The real customer reviews the merchant has supplied.
	 *
	 * Nothing is invented here. An empty list renders no reviews section at
	 * all, which is the honest state for a service that has not been used yet.
	 *
	 * @return array<int, array{quote: string, name: string, date: string}>
	 */
	public static function reviews(): array {
		$stored = self::get( 'reviews' );
		$rows   = array();

		foreach ( is_array( $stored ) ? $stored : array() as $row ) {
			if ( ! is_array( $row ) || '' === trim( (string) ( $row['quote'] ?? '' ) ) ) {
				continue;
			}

			$rows[] = array(
				'quote' => trim( (string) $row['quote'] ),
				'name'  => trim( (string) ( $row['name'] ?? '' ) ),
				'date'  => trim( (string) ( $row['date'] ?? '' ) ),
			);
		}

		return $rows;
	}

	/**
	 * Promised delivery window in business hours, or null when unset.
	 *
	 * @return int|null
	 */
	public static function sla_hours(): ?int {
		$hours = (int) self::get( 'sla_hours' );

		return $hours > 0 ? $hours : null;
	}

	/**
	 * The merchant's opening hours as a calculator.
	 *
	 * @return BusinessHours
	 */
	public static function business_hours(): BusinessHours {
		$settings = self::all();

		return new BusinessHours(
			is_array( $settings['business_days'] ) ? $settings['business_days'] : array(),
			(string) $settings['business_open'],
			(string) $settings['business_close'],
			self::timezone()
		);
	}

	/**
	 * The site's timezone, which the opening hours are expressed in.
	 *
	 * @return DateTimeZone
	 */
	public static function timezone(): DateTimeZone {
		if ( function_exists( 'wp_timezone' ) ) {
			return wp_timezone();
		}

		return new DateTimeZone( 'UTC' );
	}

	/**
	 * Opening hours in words, for the storefront and emails.
	 *
	 * @return string
	 */
	public static function business_hours_label(): string {
		$names = array();

		for ( $day = 1; $day <= 7; $day++ ) {
			// 2026-10-26 was a Monday, so adding $day-1 days names each weekday.
			$names[ $day ] = wp_date( 'l', (int) strtotime( '2026-10-26 12:00:00 UTC' ) + ( ( $day - 1 ) * DAY_IN_SECONDS ) );
		}

		return self::business_hours()->describe( $names );
	}

	/**
	 * Settings that must exist before the site may take real money.
	 *
	 * The list mirrors the launch blockers in docs/DECISIONS.md.
	 *
	 * @return array<string, string>
	 */
	public static function missing_for_live(): array {
		$missing  = array();
		$settings = self::all();

		if ( (int) $settings['sla_hours'] < 1 ) {
			$missing['sla_hours'] = __( 'Delivery window in business hours (SLA-01)', 'vehicle-report-orders' );
		}

		if ( ! self::business_hours()->is_configured() ) {
			$missing['business_days'] = __( 'The days of the week the team works (SLA-01)', 'vehicle-report-orders' );
		}

		if ( ! is_email( (string) $settings['support_email'] ) ) {
			$missing['support_email'] = __( 'Customer support email address', 'vehicle-report-orders' );
		}

		if ( '' === trim( (string) $settings['trader_name'] ) ) {
			$missing['trader_name'] = __( 'Registered trading name (LEGAL-01)', 'vehicle-report-orders' );
		}

		if ( '' === trim( (string) $settings['postal_address'] ) ) {
			$missing['postal_address'] = __( 'Published postal address, which a UK trader must give before a sale (LEGAL-01)', 'vehicle-report-orders' );
		}

		if ( '' === trim( (string) $settings['data_sources'] ) ) {
			$missing['data_sources'] = __( 'Approved report data sources (REPORT-01)', 'vehicle-report-orders' );
		}

		if ( '' === trim( (string) $settings['terms_version'] ) ) {
			$missing['terms_version'] = __( 'Terms version recorded against each order (LEGAL-01)', 'vehicle-report-orders' );
		}

		if ( empty( $settings['legal_copy_approved'] ) ) {
			$missing['legal_copy_approved'] = __( 'Confirmation that the terms, privacy, cookie and refund pages have been reviewed (LEGAL-01)', 'vehicle-report-orders' );
		}

		$product_id = (int) $settings['report_product_id'];
		$product    = $product_id > 0 && function_exists( 'wc_get_product' ) ? wc_get_product( $product_id ) : null;

		if ( ! $product ) {
			$missing['report_product_id'] = __( 'The WooCommerce product that represents the report', 'vehicle-report-orders' );
		} elseif ( '' === (string) $product->get_price() || (float) $product->get_price() <= 0 ) {
			$missing['report_price'] = __( 'A real price on the report product (PRICE-01)', 'vehicle-report-orders' );
		}

		$unpriced = array();

		foreach ( PackageRepository::all() as $package ) {
			if ( ! $package->is_purchasable() ) {
				$unpriced[] = $package->name();
			}
		}

		if ( array() !== $unpriced ) {
			$missing['package_prices'] = sprintf(
				/* translators: %s: comma-separated package names. */
				__( 'A price on every published package: %s (PRICE-01)', 'vehicle-report-orders' ),
				implode( ', ', $unpriced )
			);
		}

		return $missing;
	}

	/**
	 * Whether the merchant has supplied everything a paid order needs.
	 *
	 * @return bool
	 */
	public static function ready_for_live(): bool {
		return array() === self::missing_for_live() && (bool) self::get( 'live_mode_confirmed' );
	}
}
