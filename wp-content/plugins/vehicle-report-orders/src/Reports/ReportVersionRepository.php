<?php
/**
 * Stored report files for an order.
 *
 * @package VehicleReportOrders
 */

declare( strict_types=1 );

namespace VehicleReportOrders\Reports;

use VehicleReportOrders\Schema;

defined( 'ABSPATH' ) || exit;

/**
 * Every file ever delivered for an order, newest last.
 *
 * Replacing a report keeps the previous version: if a customer asks why their
 * report changed, or a source turns out to have been wrong, the earlier file
 * and its hash are still there.
 */
final class ReportVersionRepository {

	/**
	 * Record a newly stored file.
	 *
	 * @param int                  $order_id Order the report belongs to.
	 * @param array<string, mixed> $data     Storage key, filename, mime, size, hash, notes.
	 *
	 * @return int New row identifier, or 0 on failure.
	 */
	public function add( int $order_id, array $data ): int {
		global $wpdb;

		$version = $this->next_version( $order_id );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Dedicated plugin table.
		$inserted = $wpdb->insert(
			Schema::table( Schema::REPORT_VERSIONS ),
			array(
				'order_id'          => $order_id,
				'version'           => $version,
				'storage_key'       => (string) $data['storage_key'],
				'original_filename' => (string) $data['original_filename'],
				'mime_type'         => (string) $data['mime_type'],
				'size_bytes'        => (int) $data['size_bytes'],
				'sha256'            => (string) $data['sha256'],
				'prepared_by'       => (int) ( $data['prepared_by'] ?? get_current_user_id() ),
				'source_summary'    => (string) ( $data['source_summary'] ?? '' ),
				'coverage_notes'    => (string) ( $data['coverage_notes'] ?? '' ),
				'status'            => 'uploaded',
				'created_at'        => current_time( 'mysql', true ),
			),
			array( '%d', '%d', '%s', '%s', '%s', '%d', '%s', '%d', '%s', '%s', '%s', '%s' )
		);

		if ( ! $inserted ) {
			return 0;
		}

		$this->supersede_earlier( $order_id, (int) $wpdb->insert_id );

		return (int) $wpdb->insert_id;
	}

	/**
	 * The file a customer should currently receive.
	 *
	 * @param int $order_id Order identifier.
	 *
	 * @return object|null
	 */
	public function current_for( int $order_id ): ?object {
		global $wpdb;

		$table = Schema::table( Schema::REPORT_VERSIONS );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Dedicated plugin table.
		$row = $wpdb->get_row(
			$wpdb->prepare( 'SELECT * FROM %i WHERE order_id = %d AND superseded_at IS NULL ORDER BY version DESC LIMIT 1', $table, $order_id )
		);

		return is_object( $row ) ? $row : null;
	}

	/**
	 * One version by identifier, scoped to its order.
	 *
	 * @param int $version_id Report version identifier.
	 * @param int $order_id   Order the caller believes it belongs to.
	 *
	 * @return object|null
	 */
	public function find_for_order( int $version_id, int $order_id ): ?object {
		global $wpdb;

		$table = Schema::table( Schema::REPORT_VERSIONS );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Dedicated plugin table.
		$row = $wpdb->get_row(
			$wpdb->prepare( 'SELECT * FROM %i WHERE id = %d AND order_id = %d LIMIT 1', $table, $version_id, $order_id )
		);

		return is_object( $row ) ? $row : null;
	}

	/**
	 * Every version for an order, oldest first.
	 *
	 * @param int $order_id Order identifier.
	 *
	 * @return list<object>
	 */
	public function all_for( int $order_id ): array {
		global $wpdb;

		$table = Schema::table( Schema::REPORT_VERSIONS );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Dedicated plugin table.
		return (array) $wpdb->get_results(
			$wpdb->prepare( 'SELECT * FROM %i WHERE order_id = %d ORDER BY version ASC', $table, $order_id )
		);
	}

	/**
	 * Next version number for an order.
	 *
	 * @param int $order_id Order identifier.
	 *
	 * @return int
	 */
	private function next_version( int $order_id ): int {
		global $wpdb;

		$table = Schema::table( Schema::REPORT_VERSIONS );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Dedicated plugin table.
		$highest = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT MAX(version) FROM %i WHERE order_id = %d', $table, $order_id ) );

		return $highest + 1;
	}

	/**
	 * Mark previous versions as superseded, keeping their files and hashes.
	 *
	 * @param int $order_id   Order identifier.
	 * @param int $current_id The version that is now current.
	 *
	 * @return void
	 */
	private function supersede_earlier( int $order_id, int $current_id ): void {
		global $wpdb;

		$table = Schema::table( Schema::REPORT_VERSIONS );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Dedicated plugin table.
		$wpdb->query(
			$wpdb->prepare(
				"UPDATE %i SET superseded_at = %s, status = 'superseded' WHERE order_id = %d AND id <> %d AND superseded_at IS NULL",
				$table,
				current_time( 'mysql', true ),
				$order_id,
				$current_id
			)
		);
	}
}
