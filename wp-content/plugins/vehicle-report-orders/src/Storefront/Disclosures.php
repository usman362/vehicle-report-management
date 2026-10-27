<?php
/**
 * Shortcodes that publish only what the merchant has actually configured.
 *
 * @package VehicleReportOrders
 */

declare( strict_types=1 );

namespace VehicleReportOrders\Storefront;

use VehicleReportOrders\Domain\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Price, delivery window and data sources, read from settings at render time.
 *
 * Page copy never hard-codes these: if the merchant changes the price or the
 * window, every page follows, and if something is not configured the page says
 * so rather than printing an invented figure.
 */
final class Disclosures {

	/**
	 * Register the shortcodes.
	 *
	 * @return void
	 */
	public function register(): void {
		add_shortcode( 'vehicle_report_price', array( $this, 'price' ) );
		add_shortcode( 'vehicle_report_sla', array( $this, 'sla' ) );
		add_shortcode( 'vehicle_report_sources', array( $this, 'sources' ) );
		add_shortcode( 'vehicle_report_trader', array( $this, 'trader' ) );
		add_shortcode( 'vehicle_report_support_email', array( $this, 'support_email' ) );
		add_shortcode( 'vehicle_report_hours', array( $this, 'hours' ) );
		add_shortcode( 'vehicle_report_sample', array( $this, 'sample' ) );
		add_shortcode( 'vehicle_report_legal_draft', array( $this, 'legal_draft' ) );
	}

	/**
	 * The configured price, or a clear placeholder.
	 *
	 * @return string
	 */
	public function price(): string {
		$product = wc_get_product( (int) Settings::get( 'report_product_id' ) );

		if ( ! $product || '' === (string) $product->get_price() ) {
			return $this->pending( __( 'price to be confirmed', 'vehicle-report-orders' ) );
		}

		$price = wp_kses_post( wc_price( (float) $product->get_price() ) );

		return wc_tax_enabled() && $product->is_taxable()
			? sprintf(
				/* translators: %s: formatted price. */
				esc_html__( '%s including VAT', 'vehicle-report-orders' ),
				$price
			)
			: $price;
	}

	/**
	 * The delivery promise in words.
	 *
	 * @return string
	 */
	public function sla(): string {
		$hours = Settings::sla_hours();
		$label = Settings::business_hours_label();

		if ( null === $hours || '' === $label ) {
			return $this->pending( __( 'delivery time to be confirmed', 'vehicle-report-orders' ) );
		}

		return esc_html(
			sprintf(
				/* translators: 1: business hours promised, 2: opening hours. */
				__( 'within %1$d business hours (%2$s)', 'vehicle-report-orders' ),
				$hours,
				$label
			)
		);
	}

	/**
	 * The approved data sources as a list.
	 *
	 * @return string
	 */
	public function sources(): string {
		$raw = trim( (string) Settings::get( 'data_sources' ) );

		if ( '' === $raw ) {
			return '<p>' . $this->pending( __( 'the data sources for this service have not been published yet', 'vehicle-report-orders' ) ) . '</p>';
		}

		$lines = preg_split( '/\r\n|\r|\n/', $raw );
		$items = array_filter( array_map( 'trim', is_array( $lines ) ? $lines : array() ) );

		if ( array() === $items ) {
			return '';
		}

		$list = '';

		foreach ( $items as $item ) {
			$list .= '<li>' . esc_html( $item ) . '</li>';
		}

		return '<ul class="vr-sources">' . $list . '</ul>';
	}

	/**
	 * The registered trading name.
	 *
	 * @return string
	 */
	public function trader(): string {
		$name = trim( (string) Settings::get( 'trader_name' ) );

		return '' !== $name ? esc_html( $name ) : $this->pending( __( 'trading name to be confirmed', 'vehicle-report-orders' ) );
	}

	/**
	 * The support email address as a link.
	 *
	 * @return string
	 */
	public function support_email(): string {
		$email = (string) Settings::get( 'support_email' );

		if ( ! is_email( $email ) ) {
			return $this->pending( __( 'support address to be confirmed', 'vehicle-report-orders' ) );
		}

		return sprintf( '<a href="mailto:%1$s">%1$s</a>', esc_attr( antispambot( $email ) ) );
	}

	/**
	 * The days and times the team works.
	 *
	 * @return string
	 */
	public function hours(): string {
		$label = Settings::business_hours_label();

		return '' !== $label
			? esc_html( $label )
			: $this->pending( __( 'opening hours to be confirmed', 'vehicle-report-orders' ) );
	}

	/**
	 * A link to the merchant's own example report, or a placeholder.
	 *
	 * No illustrative PDF is shipped with the plugin: a made-up report, even
	 * one labelled as a sample, is a made-up vehicle record.
	 *
	 * @return string
	 */
	public function sample(): string {
		$url = (string) Settings::get( 'sample_report_url' );

		if ( '' === $url || ! wp_http_validate_url( $url ) ) {
			return '<p>' . $this->pending( __( 'an example report has not been published yet', 'vehicle-report-orders' ) ) . '</p>';
		}

		return sprintf(
			'<p><a class="vr-nav-cta" href="%1$s" rel="nofollow noopener">%2$s</a></p>',
			esc_url( $url ),
			esc_html__( 'Open the example report', 'vehicle-report-orders' )
		);
	}

	/**
	 * A banner on legal pages until the merchant has had the copy reviewed.
	 *
	 * The wording shipped here is a drafting aid, not legal advice, and the
	 * reader is told so until the merchant confirms otherwise. Ordering stays
	 * switched off while that confirmation is missing.
	 *
	 * @return string
	 */
	public function legal_draft(): string {
		if ( ! empty( Settings::get( 'legal_copy_approved' ) ) ) {
			return '';
		}

		return '<div class="vr-note vr-draft-notice"><p><strong>'
			. esc_html__( 'Draft wording, not yet in force.', 'vehicle-report-orders' )
			. '</strong> '
			. esc_html__( 'This page is a starting point for the operator and their legal adviser. It has not been reviewed, it does not yet state the terms of any contract, and no orders can be placed while it stands unconfirmed.', 'vehicle-report-orders' )
			. '</p></div>';
	}

	/**
	 * A visible placeholder: never a plausible-looking invented value.
	 *
	 * @param string $text What is missing.
	 *
	 * @return string
	 */
	private function pending( string $text ): string {
		return '<em class="vr-pending">[' . esc_html( $text ) . ']</em>';
	}
}
