<?php
/**
 * Structured data for search engines.
 *
 * @package VehicleReportOrders
 */

declare( strict_types=1 );

namespace VehicleReportOrders\Seo;

use VehicleReportOrders\Domain\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Describes the business and its published questions in schema.org terms.
 *
 * Only facts the merchant has actually entered are published: a missing phone
 * number or address is left out rather than filled with something plausible,
 * and no rating, review count or award is emitted at all, because this site
 * has none to report.
 */
final class StructuredData {

	/**
	 * Hook the footer, after the page content exists.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'wp_footer', array( $this, 'render' ), 5 );
	}

	/**
	 * Print whichever graphs apply to this page.
	 *
	 * @return void
	 */
	public function render(): void {
		if ( is_admin() ) {
			return;
		}

		$graph = array();

		if ( is_front_page() ) {
			$graph[] = $this->organization();
		}

		$faq = $this->faq();

		if ( null !== $faq ) {
			$graph[] = $faq;
		}

		if ( array() === $graph ) {
			return;
		}

		printf(
			'<script type="application/ld+json">%s</script>' . "\n",
			wp_json_encode(
				array(
					'@context' => 'https://schema.org',
					'@graph'   => $graph,
				),
				JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
			)
		);
	}

	/**
	 * The business, as far as the merchant has described it.
	 *
	 * @return array<string, mixed>
	 */
	private function organization(): array {
		$name = trim( (string) Settings::get( 'trader_name' ) );

		$organization = array(
			'@type' => 'Organization',
			'name'  => '' !== $name ? $name : (string) get_bloginfo( 'name' ),
			'url'   => home_url( '/' ),
		);

		$email = (string) Settings::get( 'support_email' );
		$phone = Settings::phone();

		if ( is_email( $email ) || '' !== $phone ) {
			$contact = array(
				'@type'       => 'ContactPoint',
				'contactType' => 'customer support',
			);

			if ( is_email( $email ) ) {
				$contact['email'] = $email;
			}

			if ( '' !== $phone ) {
				$contact['telephone'] = $phone;
			}

			$organization['contactPoint'] = array( $contact );
		}

		$address = Settings::postal_address();

		if ( '' !== $address ) {
			$organization['address'] = array(
				'@type'          => 'PostalAddress',
				'streetAddress'  => (string) preg_replace( '/\s*\R\s*/', ', ', $address ),
				'addressCountry' => 'GB',
			);
		}

		$icon = (int) get_option( 'site_icon' );

		if ( $icon > 0 ) {
			$logo = wp_get_attachment_image_url( $icon, 'full' );

			if ( is_string( $logo ) && '' !== $logo ) {
				$organization['logo'] = $logo;
			}
		}

		return $organization;
	}

	/**
	 * The published questions on the FAQ page, as they are actually written.
	 *
	 * @return array<string, mixed>|null
	 */
	private function faq(): ?array {
		if ( ! is_page() ) {
			return null;
		}

		$post = get_post();

		if ( ! $post || 'faq' !== $post->post_name ) {
			return null;
		}

		$rendered = apply_filters( 'the_content', $post->post_content );
		$matched  = preg_match_all( '#<h2[^>]*>(.+?)</h2>\s*<p[^>]*>(.+?)</p>#is', (string) $rendered, $pairs, PREG_SET_ORDER );

		if ( ! $matched ) {
			return null;
		}

		$questions = array();

		foreach ( $pairs as $pair ) {
			$question = trim( wp_strip_all_tags( $pair[1] ) );
			$answer   = trim( wp_strip_all_tags( $pair[2] ) );

			if ( '' === $question || '' === $answer ) {
				continue;
			}

			$questions[] = array(
				'@type'          => 'Question',
				'name'           => $question,
				'acceptedAnswer' => array(
					'@type' => 'Answer',
					'text'  => $answer,
				),
			);
		}

		if ( array() === $questions ) {
			return null;
		}

		return array(
			'@type'      => 'FAQPage',
			'mainEntity' => $questions,
		);
	}
}
