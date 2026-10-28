<?php
/**
 * Create or refresh the storefront pages from content/pages/.
 *
 * Run with:  wp eval-file scripts/seed-content.php
 *
 * The work itself lives in the plugin, so a host without shell access can do
 * the same thing from the settings screen.
 *
 * @package VehicleReportOrders
 */

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	return;
}

if ( ! class_exists( \VehicleReportOrders\Content\Seeder::class ) ) {
	WP_CLI::error( 'Activate the Vehicle Report Orders plugin first.' );
}

$vr_seeder = new VehicleReportOrders\Content\Seeder();

if ( ! $vr_seeder->has_content() ) {
	WP_CLI::error( 'No page copy found. Looked in ' . $vr_seeder->content_dir() . '/pages.' );
}

foreach ( $vr_seeder->run() as $vr_line ) {
	if ( str_starts_with( $vr_line, '! ' ) ) {
		WP_CLI::warning( substr( $vr_line, 2 ) );
	} else {
		WP_CLI::log( $vr_line );
	}
}

WP_CLI::success( 'Storefront seeded.' );
