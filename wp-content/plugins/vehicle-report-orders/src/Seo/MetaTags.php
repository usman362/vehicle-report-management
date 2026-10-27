<?php
/**
 * Search and sharing metadata.
 *
 * @package VehicleReportOrders
 */

declare( strict_types=1 );

namespace VehicleReportOrders\Seo;

use VehicleReportOrders\Domain\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Description, Open Graph and Twitter tags for every page.
 *
 * WordPress ships none of these, so a link to this site pasted into a message
 * or posted anywhere renders bare. The description is taken from the page's own
 * words — its excerpt, or the opening of its content — so nothing here invents
 * a claim the page does not make.
 *
 * If a dedicated SEO plugin is active it owns these tags, and this class stands
 * aside rather than printing a second, conflicting set.
 */
final class MetaTags {

	private const MAX_DESCRIPTION = 160;

	/**
	 * Hook the head.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'wp_head', array( $this, 'render' ), 2 );
	}

	/**
	 * Print the tags.
	 *
	 * @return void
	 */
	public function render(): void {
		if ( $this->another_plugin_owns_this() ) {
			return;
		}

		$title       = $this->title();
		$description = $this->description();
		$url         = $this->url();
		$image       = $this->image();

		if ( '' !== $description ) {
			printf( '<meta name="description" content="%s">' . "\n", esc_attr( $description ) );
			printf( '<meta property="og:description" content="%s">' . "\n", esc_attr( $description ) );
			printf( '<meta name="twitter:description" content="%s">' . "\n", esc_attr( $description ) );
		}

		printf( '<meta property="og:title" content="%s">' . "\n", esc_attr( $title ) );
		printf( '<meta property="og:type" content="%s">' . "\n", esc_attr( is_front_page() ? 'website' : 'article' ) );
		printf( '<meta property="og:site_name" content="%s">' . "\n", esc_attr( (string) get_bloginfo( 'name' ) ) );
		printf( '<meta name="twitter:title" content="%s">' . "\n", esc_attr( $title ) );

		if ( '' !== $url ) {
			printf( '<meta property="og:url" content="%s">' . "\n", esc_url( $url ) );
		}

		if ( '' !== $image ) {
			printf( '<meta property="og:image" content="%s">' . "\n", esc_url( $image ) );
			printf( '<meta name="twitter:card" content="%s">' . "\n", 'summary_large_image' );
			printf( '<meta name="twitter:image" content="%s">' . "\n", esc_url( $image ) );
		} else {
			printf( '<meta name="twitter:card" content="%s">' . "\n", 'summary' );
		}
	}

	/**
	 * The page title, without the site name appended twice.
	 *
	 * @return string
	 */
	private function title(): string {
		if ( is_front_page() ) {
			return (string) get_bloginfo( 'name' );
		}

		$title = wp_get_document_title();

		return '' !== $title ? $title : (string) get_bloginfo( 'name' );
	}

	/**
	 * A description drawn from the page itself.
	 *
	 * @return string
	 */
	private function description(): string {
		$source = '';

		if ( is_singular() ) {
			$post = get_post();

			if ( $post ) {
				$source = has_excerpt( $post ) ? (string) get_the_excerpt( $post ) : (string) $post->post_content;
			}
		}

		if ( '' === trim( $source ) ) {
			$source = (string) Settings::get( 'seo_description' );
		}

		if ( '' === trim( $source ) ) {
			$source = (string) get_bloginfo( 'description' );
		}

		$text = trim( wp_strip_all_tags( $this->resolve_shortcodes( $source ), true ) );
		$text = (string) preg_replace( '/\s+/', ' ', $text );
		// Removing a block leaves the space in front of the punctuation behind it.
		$text = (string) preg_replace( '/\s+([.,;:])/', '$1', $text );

		if ( '' === $text ) {
			return '';
		}

		return mb_strlen( $text ) > self::MAX_DESCRIPTION
			? rtrim( mb_substr( $text, 0, self::MAX_DESCRIPTION - 1 ), ' ,.;:-' ) . '…'
			: $text;
	}

	/**
	 * Turn the page's own shortcodes into the words they stand for.
	 *
	 * The short ones — price, delivery window, trading name — are part of the
	 * sentence they sit in, so a description that drops them reads as a gap.
	 * The ones that render whole sections are removed instead, because their
	 * markup is not a description of anything.
	 *
	 * @param string $source Raw page content.
	 *
	 * @return string
	 */
	private function resolve_shortcodes( string $source ): string {
		$sections = 'order_form|packages|checks|stats|reviews|contact_form|contact_details|sources|sample|legal_draft|chat_notice|whatsapp';
		$stripped = (string) preg_replace( '/\[vehicle_report_(' . $sections . ')[^\]]*\]/', '', $source );

		return do_shortcode( $stripped );
	}

	/**
	 * The canonical address of what is being viewed.
	 *
	 * @return string
	 */
	private function url(): string {
		if ( is_front_page() ) {
			return (string) home_url( '/' );
		}

		if ( is_singular() ) {
			$permalink = get_permalink();

			return is_string( $permalink ) ? $permalink : '';
		}

		return '';
	}

	/**
	 * The image a shared link should show.
	 *
	 * @return string
	 */
	private function image(): string {
		if ( is_singular() && has_post_thumbnail() ) {
			$thumbnail = get_the_post_thumbnail_url( null, 'large' );

			if ( is_string( $thumbnail ) && '' !== $thumbnail ) {
				return $thumbnail;
			}
		}

		$fallback = (int) Settings::get( 'social_image_id' );

		if ( $fallback > 0 ) {
			$url = wp_get_attachment_image_url( $fallback, 'large' );

			if ( is_string( $url ) && '' !== $url ) {
				return $url;
			}
		}

		$icon = (int) get_option( 'site_icon' );

		if ( $icon > 0 ) {
			$url = wp_get_attachment_image_url( $icon, 'full' );

			if ( is_string( $url ) && '' !== $url ) {
				return $url;
			}
		}

		return '';
	}

	/**
	 * Whether a dedicated SEO plugin is already printing these tags.
	 *
	 * @return bool
	 */
	private function another_plugin_owns_this(): bool {
		return defined( 'WPSEO_VERSION' )
			|| defined( 'RANK_MATH_VERSION' )
			|| defined( 'SEOPRESS_VERSION' )
			|| class_exists( 'All_in_One_SEO_Pack' );
	}
}
