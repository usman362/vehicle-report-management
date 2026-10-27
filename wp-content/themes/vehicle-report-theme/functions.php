<?php
/**
 * Theme setup.
 *
 * Presentation only: every workflow lives in the vehicle-report-orders plugin,
 * so the site keeps working if the theme is replaced.
 *
 * @package VehicleReportTheme
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

add_action(
	'after_setup_theme',
	static function (): void {
		add_theme_support( 'wp-block-styles' );
		add_theme_support( 'responsive-embeds' );
		add_theme_support( 'editor-styles' );
		load_theme_textdomain( 'vehicle-report-theme', get_template_directory() . '/languages' );
	}
);

add_action(
	'wp_enqueue_scripts',
	static function (): void {
		$style = get_template_directory() . '/assets/theme.css';

		wp_enqueue_style(
			'vehicle-report-theme',
			get_template_directory_uri() . '/assets/theme.css',
			array(),
			(string) ( file_exists( $style ) ? filemtime( $style ) : '0.1.0' )
		);
	}
);

/**
 * Keep order, checkout and report pages out of search results and caches.
 */
add_action(
	'wp_head',
	static function (): void {
		$private = function_exists( 'is_checkout' ) && ( is_checkout() || is_account_page() );

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Presence check only.
		$private = $private || isset( $_GET['vr-report'] );

		if ( $private ) {
			echo '<meta name="robots" content="noindex, nofollow">' . "\n";
		}
	},
	1
);
