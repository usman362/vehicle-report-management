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

// The product the report is sold as, deliberately without a price. The
// merchant sets that, and until they do the storefront refuses to start an
// order — inventing a plausible-looking figure here would defeat the point.
if ( class_exists( 'WC_Product_Simple' ) && class_exists( 'VehicleReportOrders\\Domain\\Settings' ) ) {
	$vr_product_id = (int) VehicleReportOrders\Domain\Settings::get( 'report_product_id' );
	$vr_product    = $vr_product_id > 0 ? wc_get_product( $vr_product_id ) : null;

	if ( ! $vr_product ) {
		$vr_existing = get_page_by_path( 'vehicle-history-report', OBJECT, 'product' );
		$vr_product  = $vr_existing instanceof WP_Post ? wc_get_product( $vr_existing->ID ) : null;
	}

	if ( ! $vr_product ) {
		$vr_new = new WC_Product_Simple();
		$vr_new->set_name( 'Vehicle history report' );
		$vr_new->set_slug( 'vehicle-history-report' );
		$vr_new->set_status( 'publish' );
		$vr_new->set_catalog_visibility( 'hidden' );
		$vr_new->set_virtual( true );
		$vr_new->set_sold_individually( true );
		$vr_new->set_short_description( 'One written vehicle history report, researched by a person and emailed as a PDF.' );
		$vr_product_id = (int) $vr_new->save();

		WP_CLI::log( sprintf( '  %-20s %-9s #%d', 'report product', 'created', $vr_product_id ) );
	} else {
		$vr_product_id = $vr_product->get_id();
		WP_CLI::log( sprintf( '  %-20s %-9s #%d', 'report product', 'exists', $vr_product_id ) );
	}

	VehicleReportOrders\Domain\Settings::update( array( 'report_product_id' => $vr_product_id ) );
}

WP_CLI::success( sprintf( '%d pages seeded from content/pages/.', count( $vr_ids ) ) );
