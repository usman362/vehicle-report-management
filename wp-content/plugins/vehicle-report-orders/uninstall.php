<?php
/**
 * Removing the plugin.
 *
 * What is deleted here is the plugin's own furniture: its settings, its staff
 * roles, its scheduled retries. What is not deleted is the record of what the
 * business did — orders, the audit trail, report versions and the report files
 * themselves. Those are financial and accountability records, and a customer
 * who paid for a report last month is not expecting it to vanish because an
 * administrator tidied the plugins screen.
 *
 * A site that genuinely wants everything gone can say so, once, in
 * wp-config.php:
 *
 *     define( 'VR_REMOVE_ALL_DATA', true );
 *
 * That is deliberately a decision someone has to write down, not a checkbox
 * anyone can hit by accident.
 *
 * @package VehicleReportOrders
 */

declare( strict_types=1 );

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

require_once __DIR__ . '/src/Autoloader.php';

VehicleReportOrders\Autoloader::register();

use VehicleReportOrders\Admin\CapabilityManager;
use VehicleReportOrders\Audit\AuditLogger;
use VehicleReportOrders\Domain\Settings;
use VehicleReportOrders\Reports\PrivateStorage;
use VehicleReportOrders\Schema;

global $wpdb;

// The staff roles and the capabilities handed to core roles.
CapabilityManager::remove_from_core_roles();

foreach ( array_keys( CapabilityManager::roles() ) as $vr_role ) {
	remove_role( $vr_role );
}

// Anything still waiting to run.
if ( function_exists( 'as_unschedule_all_actions' ) ) {
	as_unschedule_all_actions( 'vr_retry_report_delivery' );
}

$vr_scheduled = wp_next_scheduled( 'vr_retry_report_delivery' );

while ( false !== $vr_scheduled ) {
	wp_unschedule_event( (int) $vr_scheduled, 'vr_retry_report_delivery' );
	$vr_scheduled = wp_next_scheduled( 'vr_retry_report_delivery' );
}

// Settings and bookkeeping.
delete_option( Settings::OPTION );
delete_option( Schema::VERSION_OPTION );
delete_option( 'vr_plugin_version' );

if ( ! defined( 'VR_REMOVE_ALL_DATA' ) || true !== VR_REMOVE_ALL_DATA ) {
	return;
}

// Everything, because the site asked for everything.
$vr_private = PrivateStorage::base_path();

if ( is_dir( $vr_private ) ) {
	$vr_files = glob( trailingslashit( $vr_private ) . '*' );

	foreach ( is_array( $vr_files ) ? $vr_files : array() as $vr_file ) {
		if ( is_file( $vr_file ) ) {
			wp_delete_file( $vr_file );
		}
	}
}

foreach ( array( Schema::REPORT_VERSIONS, Schema::DELIVERY_ATTEMPTS, AuditLogger::TABLE ) as $vr_table ) {
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange -- Uninstalling the plugin's own tables.
	$wpdb->query( $wpdb->prepare( 'DROP TABLE IF EXISTS %i', Schema::table( $vr_table ) ) );
}

// Order metadata belongs to WooCommerce's tables, so it is removed by key.
foreach ( array( 'wc_orders_meta', 'postmeta' ) as $vr_meta_table ) {
	$vr_full = $wpdb->prefix . $vr_meta_table;

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Uninstalling the plugin's own metadata.
	$wpdb->query( $wpdb->prepare( 'DELETE FROM %i WHERE meta_key LIKE %s', $vr_full, $wpdb->esc_like( '_vr_' ) . '%' ) );
}
