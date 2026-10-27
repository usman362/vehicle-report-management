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

/**
 * Keep WooCommerce's mini cart out of the header.
 *
 * WooCommerce hooks a mini cart into block-theme headers automatically. This
 * store sells one report at a time from an order form, so a basket drawer adds
 * a step rather than removing one — and the drawer it renders is hidden from
 * assistive technology while still holding focusable controls.
 *
 * @param string[] $hooked_blocks Blocks WordPress is about to insert.
 *
 * @return string[]
 */
add_filter(
	'hooked_block_types',
	static function ( array $hooked_blocks ): array {
		return array_values( array_diff( $hooked_blocks, array( 'woocommerce/mini-cart' ) ) );
	}
);

/**
 * A report is a service, not a boxed product, so there is nothing to photograph.
 *
 * WooCommerce fills the gap with a grey placeholder image, which on a cart page
 * reads as a broken picture rather than as "no picture". These pages drop the
 * image entirely and let the name and price carry the row.
 */
add_action(
	'init',
	static function (): void {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return;
		}

		// The empty gallery column on a package page.
		remove_action( 'woocommerce_before_single_product_summary', 'woocommerce_show_product_images', 20 );

		// Cross-sells and "related products", which only showed placeholders.
		remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_output_related_products', 20 );
		remove_action( 'woocommerce_cart_collaterals', 'woocommerce_cross_sell_display' );

		// WooCommerce opens its own <main> around the product. The template
		// already provides one, and two main landmarks is one too many.
		remove_action( 'woocommerce_before_main_content', 'woocommerce_output_content_wrapper', 10 );
		remove_action( 'woocommerce_after_main_content', 'woocommerce_output_content_wrapper_end', 10 );

		add_filter( 'woocommerce_product_get_image', '__return_empty_string' );
		add_filter( 'woocommerce_placeholder_img', '__return_empty_string' );
	}
);
