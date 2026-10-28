<?php
/**
 * Report settings screen.
 *
 * @package VehicleReportOrders
 */

declare( strict_types=1 );

namespace VehicleReportOrders\Admin;

use VehicleReportOrders\Audit\AuditLogger;
use VehicleReportOrders\Content\Seeder;
use VehicleReportOrders\Domain\Settings;
use VehicleReportOrders\Domain\VehicleType;
use VehicleReportOrders\Reports\PrivateStorage;

defined( 'ABSPATH' ) || exit;

/**
 * Lets the merchant supply the facts the product cannot invent.
 *
 * The screen leads with what is still missing before a real payment may be
 * taken, because those are the same launch blockers the project documentation
 * tracks. Live mode cannot be confirmed while anything is outstanding.
 */
final class SettingsPage {

	private const NONCE      = 'vr_save_settings';
	private const SEED_NONCE = 'vr_seed_content';

	/**
	 * Handle a submission, then render.
	 *
	 * @return void
	 */
	public function render(): void {
		if ( ! current_user_can( CapabilityManager::MANAGE_SETTINGS ) ) {
			wp_die( esc_html__( 'You do not have permission to manage report settings.', 'vehicle-report-orders' ), 403 );
		}

		$saved      = false;
		$seed_lines = array();

		if ( isset( $_POST['vr_settings_nonce'] ) ) {
			check_admin_referer( self::NONCE, 'vr_settings_nonce' );
			$this->save();
			$saved = true;
		}

		if ( isset( $_POST['vr_seed_nonce'] ) ) {
			check_admin_referer( self::SEED_NONCE, 'vr_seed_nonce' );
			$seed_lines = $this->seed();
		}

		$settings = Settings::all();
		$missing  = Settings::missing_for_live();
		$seeder   = new Seeder();

		include __DIR__ . '/../../templates/admin/settings.php';
	}

	/**
	 * Rebuild the storefront from the copy shipped with the plugin.
	 *
	 * The same work the WP-CLI script does, for the many hosts that have no
	 * shell. It writes only the pages, packages and settings it owns.
	 *
	 * @return array<int, string> What was done, line by line.
	 */
	private function seed(): array {
		$seeder = new Seeder();

		if ( ! $seeder->has_content() ) {
			return array(
				'! ' . sprintf(
									/* translators: %s: directory the page copy was expected in. */
					__( 'No page copy found. Expected it in %s.', 'vehicle-report-orders' ),
					$seeder->content_dir() . '/pages'
				),
			);
		}

		$lines = $seeder->run();

		AuditLogger::log( 0, 'settings.content_seeded', array( 'lines' => count( $lines ) ) );

		return $lines;
	}

	/**
	 * Sanitise and store the submitted values.
	 *
	 * @return void
	 */
	private function save(): void {
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Verified by check_admin_referer() in render().
		$confirmed = isset( $_POST['live_mode_confirmed'] );

		$values = array(
			'sla_hours'           => isset( $_POST['sla_hours'] ) ? absint( wp_unslash( $_POST['sla_hours'] ) ) : 0,
			'business_days'       => isset( $_POST['business_days'] ) ? array_values( array_filter( array_map( 'absint', (array) wp_unslash( $_POST['business_days'] ) ), static fn( int $day ): bool => $day >= 1 && $day <= 7 ) ) : array(),
			'business_open'       => isset( $_POST['business_open'] ) ? sanitize_text_field( wp_unslash( $_POST['business_open'] ) ) : '09:00',
			'business_close'      => isset( $_POST['business_close'] ) ? sanitize_text_field( wp_unslash( $_POST['business_close'] ) ) : '17:00',
			'support_email'       => isset( $_POST['support_email'] ) ? sanitize_email( wp_unslash( $_POST['support_email'] ) ) : '',
			'trader_name'         => isset( $_POST['trader_name'] ) ? sanitize_text_field( wp_unslash( $_POST['trader_name'] ) ) : '',
			'terms_version'       => isset( $_POST['terms_version'] ) ? sanitize_text_field( wp_unslash( $_POST['terms_version'] ) ) : '',
			'data_sources'        => isset( $_POST['data_sources'] ) ? sanitize_textarea_field( wp_unslash( $_POST['data_sources'] ) ) : '',
			'sample_report_url'   => isset( $_POST['sample_report_url'] ) ? sanitize_url( wp_unslash( $_POST['sample_report_url'] ) ) : '',
			'legal_copy_approved' => isset( $_POST['legal_copy_approved'] ),
			'report_product_id'   => isset( $_POST['report_product_id'] ) ? absint( wp_unslash( $_POST['report_product_id'] ) ) : 0,
			'download_ttl_hours'  => isset( $_POST['download_ttl_hours'] ) ? max( 1, absint( wp_unslash( $_POST['download_ttl_hours'] ) ) ) : 72,
			'max_delivery_tries'  => isset( $_POST['max_delivery_tries'] ) ? max( 1, absint( wp_unslash( $_POST['max_delivery_tries'] ) ) ) : 5,

			'phone'               => isset( $_POST['phone'] ) ? sanitize_text_field( wp_unslash( $_POST['phone'] ) ) : '',
			'postal_address'      => isset( $_POST['postal_address'] ) ? sanitize_textarea_field( wp_unslash( $_POST['postal_address'] ) ) : '',
			'company_number'      => isset( $_POST['company_number'] ) ? sanitize_text_field( wp_unslash( $_POST['company_number'] ) ) : '',
			'vat_number'          => isset( $_POST['vat_number'] ) ? sanitize_text_field( wp_unslash( $_POST['vat_number'] ) ) : '',

			'whatsapp_number'     => isset( $_POST['whatsapp_number'] ) ? sanitize_text_field( wp_unslash( $_POST['whatsapp_number'] ) ) : '',
			'live_chat_src'       => isset( $_POST['live_chat_src'] ) ? sanitize_url( wp_unslash( $_POST['live_chat_src'] ) ) : '',

			'vehicle_types'       => $this->posted_vehicle_types(),
			'accept_vin'          => isset( $_POST['accept_vin'] ),

			'seo_description'     => isset( $_POST['seo_description'] ) ? sanitize_textarea_field( wp_unslash( $_POST['seo_description'] ) ) : '',
			'social_image_id'     => isset( $_POST['social_image_id'] ) ? absint( wp_unslash( $_POST['social_image_id'] ) ) : 0,

			'reviews'             => $this->posted_reviews(),
			'trust_logos'         => $this->posted_trust_logos(),
		);
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		Settings::update( $values );

		// Live mode may only be confirmed once nothing is outstanding, so the
		// check runs against the values that were just saved.
		$still_missing = Settings::missing_for_live();
		Settings::update( array( 'live_mode_confirmed' => $confirmed && array() === $still_missing ) );

		AuditLogger::log(
			0,
			'settings.updated',
			array(
				'changed_keys'        => array_keys( $values ),
				'live_mode_confirmed' => $confirmed && array() === $still_missing,
				'outstanding'         => array_keys( $still_missing ),
			)
		);
	}

	/**
	 * The vehicle types ticked on the settings screen.
	 *
	 * @return array<int, string>
	 */
	private function posted_vehicle_types(): array {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Nonce checked in render(); every element is passed through sanitize_key() below.
		$posted = isset( $_POST['vehicle_types'] ) ? (array) wp_unslash( $_POST['vehicle_types'] ) : array();
		$valid  = array_keys( VehicleType::catalogue() );

		$chosen = array();

		foreach ( $posted as $slug ) {
			$slug = sanitize_key( (string) $slug );

			if ( in_array( $slug, $valid, true ) ) {
				$chosen[] = $slug;
			}
		}

		return $chosen;
	}

	/**
	 * The media ids entered for the logo strip.
	 *
	 * @return array<int, int>
	 */
	private function posted_trust_logos(): array {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Verified by check_admin_referer() in render().
		$raw = isset( $_POST['trust_logos'] ) ? sanitize_text_field( wp_unslash( $_POST['trust_logos'] ) ) : '';

		$parts = preg_split( '/[^0-9]+/', $raw );
		$ids   = array();

		foreach ( is_array( $parts ) ? $parts : array() as $candidate ) {
			$id = (int) $candidate;

			if ( $id > 0 ) {
				$ids[] = $id;
			}
		}

		return array_values( array_unique( $ids ) );
	}

	/**
	 * The reviews entered on the settings screen.
	 *
	 * Rows without a quote are dropped, so clearing a quote removes the review.
	 *
	 * @return array<int, array{quote: string, name: string, date: string}>
	 */
	private function posted_reviews(): array {
		// phpcs:disable WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Nonce checked in render(); every element is sanitised in the loop below.
		$quotes = isset( $_POST['review_quote'] ) ? (array) wp_unslash( $_POST['review_quote'] ) : array();
		$names  = isset( $_POST['review_name'] ) ? (array) wp_unslash( $_POST['review_name'] ) : array();
		$dates  = isset( $_POST['review_date'] ) ? (array) wp_unslash( $_POST['review_date'] ) : array();
		// phpcs:enable WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

		$reviews = array();

		foreach ( $quotes as $index => $quote ) {
			$text = trim( sanitize_textarea_field( (string) $quote ) );

			if ( '' === $text ) {
				continue;
			}

			$reviews[] = array(
				'quote' => $text,
				'name'  => isset( $names[ $index ] ) ? sanitize_text_field( (string) $names[ $index ] ) : '',
				'date'  => isset( $dates[ $index ] ) ? sanitize_text_field( (string) $dates[ $index ] ) : '',
			);
		}

		return $reviews;
	}

	/**
	 * Weekday names keyed by ISO-8601 number.
	 *
	 * @return array<int, string>
	 */
	public function weekday_names(): array {
		$names = array();

		for ( $day = 1; $day <= 7; $day++ ) {
			$names[ $day ] = wp_date( 'D', (int) strtotime( '2026-10-26 12:00:00 UTC' ) + ( ( $day - 1 ) * DAY_IN_SECONDS ) );
		}

		return $names;
	}

	/**
	 * Products that could represent the report, newest first.
	 *
	 * @return array<int, string>
	 */
	public function product_choices(): array {
		$choices  = array();
		$products = wc_get_products(
			array(
				'limit'   => 50,
				'status'  => array( 'publish', 'private', 'draft' ),
				'orderby' => 'date',
				'order'   => 'DESC',
			)
		);

		foreach ( $products as $product ) {
			$price = $product->get_price();

			$choices[ $product->get_id() ] = sprintf(
				/* translators: 1: product name, 2: formatted price or a note that none is set. */
				__( '%1$s — %2$s', 'vehicle-report-orders' ),
				$product->get_name(),
				'' === (string) $price ? __( 'no price set', 'vehicle-report-orders' ) : wp_strip_all_tags( wc_price( (float) $price ) )
			);
		}

		return $choices;
	}

	/**
	 * Human description of where report files are kept.
	 *
	 * @return array{location: string, path: string, safe: bool}
	 */
	public function storage_status(): array {
		return array(
			'location' => PrivateStorage::location(),
			'path'     => PrivateStorage::base_path(),
			'safe'     => PrivateStorage::is_outside_web_root(),
		);
	}
}
