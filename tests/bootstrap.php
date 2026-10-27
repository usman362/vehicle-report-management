<?php
/**
 * Unit-test bootstrap.
 *
 * These tests cover domain logic that must not depend on WordPress being
 * loaded. ABSPATH is defined so plugin files guarded by it can be included,
 * and the handful of WordPress functions that pure domain code touches are
 * stubbed as pass-throughs: translation and escaping are WordPress concerns
 * and are exercised by the integration tests, not here.
 *
 * @package VehicleReportOrders
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/../' );

if ( ! function_exists( '__' ) ) {
	/**
	 * Return the untranslated string.
	 *
	 * @param string $text   Source string.
	 * @param string $domain Text domain (ignored in unit tests).
	 *
	 * @return string
	 */
	function __( string $text, string $domain = 'default' ): string { // phpcs:ignore
		unset( $domain );

		return $text;
	}
}

if ( ! function_exists( 'esc_html__' ) ) {
	/**
	 * Return the untranslated string.
	 *
	 * @param string $text   Source string.
	 * @param string $domain Text domain (ignored in unit tests).
	 *
	 * @return string
	 */
	function esc_html__( string $text, string $domain = 'default' ): string { // phpcs:ignore
		unset( $domain );

		return $text;
	}
}

if ( ! function_exists( 'size_format' ) ) {
	/**
	 * Minimal stand-in for the WordPress helper.
	 *
	 * @param int $bytes Size in bytes.
	 *
	 * @return string
	 */
	function size_format( int $bytes ): string { // phpcs:ignore
		return sprintf( '%d MB', (int) round( $bytes / ( 1024 * 1024 ) ) );
	}
}

if ( ! function_exists( 'sanitize_key' ) ) {
	/**
	 * Lowercase, alphanumerics, dashes and underscores — as WordPress does.
	 *
	 * @param string $key Raw value.
	 *
	 * @return string
	 */
	function sanitize_key( string $key ): string { // phpcs:ignore
		return (string) preg_replace( '/[^a-z0-9_\-]/', '', strtolower( $key ) );
	}
}

if ( ! function_exists( 'sanitize_textarea_field' ) ) {
	/**
	 * Strip tags and trim, which is the part this code relies on.
	 *
	 * @param string $text Raw value.
	 *
	 * @return string
	 */
	function sanitize_textarea_field( string $text ): string { // phpcs:ignore
		return trim( wp_strip_all_tags( $text ) );
	}
}

if ( ! function_exists( 'wp_strip_all_tags' ) ) {
	/**
	 * Remove markup.
	 *
	 * @param string $text Raw value.
	 *
	 * @return string
	 */
	function wp_strip_all_tags( string $text ): string { // phpcs:ignore
		return (string) wp_strip_tags_helper( $text );
	}

	/**
	 * Tag removal used by the stub above.
	 *
	 * @param string $text Raw value.
	 *
	 * @return string
	 */
	function wp_strip_tags_helper( string $text ): string { // phpcs:ignore
		return strip_tags( $text ); // phpcs:ignore WordPress.WP.AlternativeFunctions.strip_tags_strip_tags
	}
}

if ( ! function_exists( 'wp_json_encode' ) ) {
	/**
	 * JSON encode.
	 *
	 * @param mixed $data Value to encode.
	 *
	 * @return string|false
	 */
	function wp_json_encode( $data ) { // phpcs:ignore
		return json_encode( $data ); // phpcs:ignore WordPress.WP.AlternativeFunctions.json_encode_json_encode
	}
}

if ( ! class_exists( 'WP_Error' ) ) {
	/**
	 * Enough of WordPress's error object for the domain code and its tests.
	 */
	class WP_Error { // phpcs:ignore

		/**
		 * Error code.
		 *
		 * @var string
		 */
		private string $code;

		/**
		 * Error message.
		 *
		 * @var string
		 */
		private string $message;

		/**
		 * Constructor.
		 *
		 * @param string $code    Error code.
		 * @param string $message Error message.
		 */
		public function __construct( string $code = '', string $message = '' ) {
			$this->code    = $code;
			$this->message = $message;
		}

		/**
		 * The code.
		 *
		 * @return string
		 */
		public function get_error_code(): string {
			return $this->code;
		}

		/**
		 * The message.
		 *
		 * @return string
		 */
		public function get_error_message(): string {
			return $this->message;
		}
	}
}

require_once __DIR__ . '/../vendor/autoload.php';
