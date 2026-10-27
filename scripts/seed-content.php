<?php
/**
 * Create or refresh the storefront pages from content/pages/.
 *
 * Run with:  wp eval-file scripts/seed-content.php
 *
 * The page copy lives in version control, not only in the database, so a fresh
 * install comes up with the same site and a change to the wording is reviewable
 * in a diff. Running it again overwrites the pages it owns and nothing else.
 *
 * @package VehicleReportOrders
 */

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	return;
}

/**
 * The pages this script owns, in menu order.
 *
 * front  — becomes the static front page.
 * header — appears in the theme's main navigation.
 * footer — appears in the footer navigation.
 */
$vr_pages = array(
	'home'                => array( 'Home', array( 'front' => true ) ),
	'order-report'        => array( 'Order a report', array( 'header' => true ) ),
	'pricing'             => array( 'Pricing', array( 'header' => true ) ),
	'how-it-works'        => array( 'How it works', array( 'header' => true ) ),
	'sample-report'       => array( 'What a report looks like', array( 'header' => true ) ),
	'report-data-sources' => array( 'Report data sources', array( 'header' => true ) ),
	'faq'                 => array( 'Frequently asked questions', array( 'footer' => true ) ),
	'about'               => array( 'About us', array( 'footer' => true ) ),
	'contact'             => array( 'Contact', array( 'header' => true, 'footer' => true ) ),
	'terms'               => array( 'Terms and conditions', array( 'footer' => true, 'wc_terms' => true ) ),
	'privacy-policy'      => array( 'Privacy policy', array( 'footer' => true, 'privacy' => true ) ),
	'cookies'             => array( 'Cookies', array( 'footer' => true ) ),
	'refunds'             => array( 'Refunds', array( 'footer' => true ) ),
);

$vr_dir   = dirname( __DIR__ ) . '/content/pages';
$vr_order = 0;
$vr_ids   = array();

foreach ( $vr_pages as $vr_slug => $vr_page ) {
	list( $vr_title, $vr_flags ) = $vr_page;

	$vr_file = "{$vr_dir}/{$vr_slug}.html";

	if ( ! is_readable( $vr_file ) ) {
		WP_CLI::warning( "missing content file: content/pages/{$vr_slug}.html" );
		continue;
	}

	$vr_content  = trim( (string) file_get_contents( $vr_file ) );
	$vr_existing = get_page_by_path( $vr_slug, OBJECT, 'page' );
	++$vr_order;

	$vr_data = array(
		'post_title'     => $vr_title,
		'post_name'      => $vr_slug,
		'post_content'   => $vr_content,
		'post_status'    => 'publish',
		'post_type'      => 'page',
		'menu_order'     => $vr_order,
		'comment_status' => 'closed',
		'ping_status'    => 'closed',
	);

	if ( $vr_existing instanceof WP_Post ) {
		$vr_data['ID'] = $vr_existing->ID;
		$vr_unchanged  = $vr_existing->post_content === $vr_content
			&& $vr_existing->post_title === $vr_title
			&& 'publish' === $vr_existing->post_status
			&& (int) $vr_existing->menu_order === $vr_order;

		$vr_id = wp_update_post( $vr_data, true );
		$vr_verb = $vr_unchanged ? 'unchanged' : 'updated';
	} else {
		$vr_id   = wp_insert_post( $vr_data, true );
		$vr_verb = 'created';
	}

	if ( is_wp_error( $vr_id ) ) {
		WP_CLI::warning( "{$vr_slug}: " . $vr_id->get_error_message() );
		continue;
	}

	$vr_ids[ $vr_slug ] = (int) $vr_id;
	WP_CLI::log( sprintf( '  %-20s %-9s #%d', $vr_slug, $vr_verb, $vr_id ) );

	if ( ! empty( $vr_flags['front'] ) ) {
		update_option( 'show_on_front', 'page' );
		update_option( 'page_on_front', (int) $vr_id );
	}

	if ( ! empty( $vr_flags['privacy'] ) ) {
		update_option( 'wp_page_for_privacy_policy', (int) $vr_id );
	}

	if ( ! empty( $vr_flags['wc_terms'] ) ) {
		update_option( 'woocommerce_terms_page_id', (int) $vr_id );
	}
}

// WooCommerce ships placeholder pages we replace; leave them out of the way
// rather than deleting anything a merchant may have edited.
foreach ( array( 'refund_returns', 'sample-page' ) as $vr_stale ) {
	$vr_post = get_page_by_path( $vr_stale, OBJECT, 'page' );

	if ( $vr_post instanceof WP_Post && 'trash' !== $vr_post->post_status && ! in_array( $vr_post->ID, $vr_ids, true ) ) {
		wp_update_post( array( 'ID' => $vr_post->ID, 'post_status' => 'draft' ) );
		WP_CLI::log( sprintf( '  %-20s %-9s #%d', $vr_stale, 'drafted', $vr_post->ID ) );
	}
}

// The packages the report is sold as, deliberately without prices. The
// merchant sets those, and until they do the storefront refuses to start an
// order — inventing a plausible-looking figure here would defeat the point.
if ( class_exists( 'WC_Product_Simple' ) && class_exists( 'VehicleReportOrders\\Domain\\Settings' ) ) {

	// Package pages live under /packages/, which is where a visitor expects
	// to find them and what the navigation links to.
	$vr_permalinks = get_option( 'woocommerce_permalinks', array() );

	if ( ! is_array( $vr_permalinks ) ) {
		$vr_permalinks = array();
	}

	if ( 'packages' !== ( $vr_permalinks['product_base'] ?? '' ) ) {
		$vr_permalinks['product_base'] = 'packages';
		update_option( 'woocommerce_permalinks', $vr_permalinks );
		WP_CLI::log( sprintf( '  %-20s %-9s %s', 'product base', 'set', '/packages/' ) );
	}

	// A named category keeps "Uncategorized" out of the breadcrumb on a
	// package page.
	$vr_term = term_exists( 'vehicle-reports', 'product_cat' );

	if ( ! $vr_term ) {
		$vr_term = wp_insert_term( 'Vehicle reports', 'product_cat', array( 'slug' => 'vehicle-reports' ) );
	}

	$vr_cat_id = is_array( $vr_term ) ? (int) $vr_term['term_id'] : 0;

	$vr_stored   = VehicleReportOrders\Domain\Settings::get( 'packages' );
	$vr_existing = is_array( $vr_stored ) && array() !== $vr_stored
		? $vr_stored
		: VehicleReportOrders\Domain\PackageRepository::defaults();

	$vr_packages = array();

	foreach ( $vr_existing as $vr_index => $vr_row ) {
		$vr_slug    = (string) ( $vr_row['slug'] ?? '' );
		$vr_name    = (string) ( $vr_row['name'] ?? $vr_slug );
		$vr_checks  = is_array( $vr_row['checks'] ?? null ) ? $vr_row['checks'] : array();
		$vr_post_id = (int) ( $vr_row['product_id'] ?? 0 );

		if ( '' === $vr_slug ) {
			continue;
		}

		$vr_product = $vr_post_id > 0 ? wc_get_product( $vr_post_id ) : null;

		if ( ! $vr_product ) {
			$vr_found   = get_page_by_path( $vr_slug, OBJECT, 'product' );
			$vr_product = $vr_found instanceof WP_Post ? wc_get_product( $vr_found->ID ) : null;
		}

		$vr_summary = array();

		foreach ( $vr_checks as $vr_check ) {
			$vr_summary[] = VehicleReportOrders\Domain\VehicleCheck::label( (string) $vr_check );
		}

		if ( ! $vr_product ) {
			$vr_new = new WC_Product_Simple();
			$vr_new->set_name( $vr_name );
			$vr_new->set_slug( $vr_slug );
			$vr_new->set_status( 'publish' );
			$vr_new->set_catalog_visibility( 'visible' );
			$vr_new->set_virtual( true );
			$vr_new->set_sold_individually( true );
			$vr_new->set_menu_order( (int) $vr_index );
			$vr_new->set_short_description(
				'One written vehicle report, researched by a person and emailed as a PDF. Checks included: '
				. implode( ', ', $vr_summary ) . '.'
			);
			if ( $vr_cat_id > 0 ) {
				$vr_new->set_category_ids( array( $vr_cat_id ) );
			}

			$vr_post_id = (int) $vr_new->save();

			WP_CLI::log( sprintf( '  %-20s %-9s #%d', "package {$vr_slug}", 'created', $vr_post_id ) );
		} else {
			$vr_post_id = $vr_product->get_id();
			$vr_product->set_short_description(
				'One written vehicle report, researched by a person and emailed as a PDF. Checks included: '
				. implode( ', ', $vr_summary ) . '.'
			);
			$vr_product->set_catalog_visibility( 'visible' );
			$vr_product->set_menu_order( (int) $vr_index );

			if ( $vr_cat_id > 0 ) {
				$vr_product->set_category_ids( array( $vr_cat_id ) );
			}

			$vr_product->save();

			WP_CLI::log( sprintf( '  %-20s %-9s #%d', "package {$vr_slug}", 'exists', $vr_post_id ) );
		}

		$vr_packages[] = array(
			'slug'       => $vr_slug,
			'name'       => $vr_name,
			'product_id' => $vr_post_id,
			'reports'    => (int) ( $vr_row['reports'] ?? 1 ),
			'featured'   => ! empty( $vr_row['featured'] ),
			'checks'     => $vr_checks,
		);
	}

	VehicleReportOrders\Domain\Settings::update( array( 'packages' => $vr_packages ) );

	// The entry-level package stays the product the rest of the plugin falls
	// back to when a visitor has not chosen a tier.
	if ( array() !== $vr_packages ) {
		VehicleReportOrders\Domain\Settings::update( array( 'report_product_id' => (int) $vr_packages[0]['product_id'] ) );
	}

	flush_rewrite_rules( false );
}

// A site icon, so a tab, a bookmark and a shared link are not blank. The one
// in content/brand is a plain plate shape with no wordmark: a placeholder the
// merchant replaces with their own logo, never a brand invented here.
if ( 0 === (int) get_option( 'site_icon' ) ) {
	$vr_icon = dirname( __DIR__ ) . '/content/brand/site-icon.png';

	if ( is_readable( $vr_icon ) ) {
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';

		$vr_tmp = wp_tempnam( 'vr-site-icon' );
		copy( $vr_icon, $vr_tmp );

		$vr_attachment = media_handle_sideload(
			array(
				'name'     => 'site-icon.png',
				'tmp_name' => $vr_tmp,
			),
			0,
			'Placeholder site icon'
		);

		if ( is_wp_error( $vr_attachment ) ) {
			WP_CLI::warning( 'site icon: ' . $vr_attachment->get_error_message() );
		} else {
			update_option( 'site_icon', (int) $vr_attachment );
			WP_CLI::log( sprintf( '  %-20s %-9s #%d', 'site icon', 'set', (int) $vr_attachment ) );
		}
	}
} else {
	WP_CLI::log( sprintf( '  %-20s %-9s #%d', 'site icon', 'exists', (int) get_option( 'site_icon' ) ) );
}

WP_CLI::success( sprintf( '%d pages seeded from content/pages/.', count( $vr_ids ) ) );
