<?php
/**
 * Audit log screen.
 *
 * @package VehicleReportOrders
 */

declare( strict_types=1 );

namespace VehicleReportOrders\Admin;

use VehicleReportOrders\Audit\AuditLogger;

defined( 'ABSPATH' ) || exit;

/**
 * Read-only view of the append-only audit trail.
 *
 * Nothing here can edit or delete an entry: that is the point of the table.
 */
final class AuditLogPage {

	private const PER_PAGE = 50;

	/**
	 * Render the log.
	 *
	 * @return void
	 */
	public function render(): void {
		if ( ! current_user_can( CapabilityManager::VIEW_AUDIT ) ) {
			wp_die( esc_html__( 'You do not have permission to view the audit log.', 'vehicle-report-orders' ), 403 );
		}

		global $wpdb;

		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Read-only filters.
		$order_id = isset( $_GET['order_id'] ) ? absint( wp_unslash( $_GET['order_id'] ) ) : 0;
		$paged    = isset( $_GET['paged'] ) ? max( 1, absint( wp_unslash( $_GET['paged'] ) ) ) : 1;
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		$table  = AuditLogger::table();
		$offset = ( $paged - 1 ) * self::PER_PAGE;

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Dedicated plugin table; the name comes from $wpdb->prefix and values are prepared.
		if ( $order_id > 0 ) {
			$events = $wpdb->get_results(
				$wpdb->prepare( "SELECT * FROM {$table} WHERE order_id = %d ORDER BY id DESC LIMIT %d OFFSET %d", $order_id, self::PER_PAGE, $offset )
			);
			$total  = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE order_id = %d", $order_id ) );
		} else {
			$events = $wpdb->get_results(
				$wpdb->prepare( "SELECT * FROM {$table} ORDER BY id DESC LIMIT %d OFFSET %d", self::PER_PAGE, $offset )
			);
			$total  = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" );
		}
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		$pages = (int) ceil( $total / self::PER_PAGE );

		include __DIR__ . '/../../templates/admin/audit-log.php';
	}
}
