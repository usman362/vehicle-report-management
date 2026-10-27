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

require_once __DIR__ . '/../vendor/autoload.php';
