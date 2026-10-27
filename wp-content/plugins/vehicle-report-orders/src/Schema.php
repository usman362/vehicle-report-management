<?php
/**
 * Database schema owned by this plugin.
 *
 * @package VehicleReportOrders
 */

declare( strict_types=1 );

namespace VehicleReportOrders;

use VehicleReportOrders\Audit\AuditLogger;

defined( 'ABSPATH' ) || exit;

/**
 * Creates the few tables that WooCommerce order meta cannot serve well.
 *
 * Orders themselves stay in WooCommerce (HPOS) and are reached through its CRUD
 * API. These tables hold the append-only histories — audit events, delivery
 * attempts and report versions — which need their own rows, indexes and
 * retention rather than ever-growing serialised meta.
 */
final class Schema {

	public const VERSION = 2;

	public const VERSION_OPTION = 'vr_schema_version';

	public const DELIVERY_ATTEMPTS = 'vr_delivery_attempts';
	public const REPORT_VERSIONS   = 'vr_report_versions';

	/**
	 * Table name with the site prefix.
	 *
	 * @param string $table Unprefixed table name.
	 *
	 * @return string
	 */
	public static function table( string $table ): string {
		global $wpdb;

		return $wpdb->prefix . $table;
	}

	/**
	 * Create or update the tables. Safe to run repeatedly.
	 *
	 * @return void
	 */
	public static function install(): void {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset = $wpdb->get_charset_collate();
		$audit   = AuditLogger::table();
		$deliver = self::table( self::DELIVERY_ATTEMPTS );
		$reports = self::table( self::REPORT_VERSIONS );

		dbDelta(
			"CREATE TABLE {$audit} (
				id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				order_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
				actor_id BIGINT UNSIGNED NULL,
				action VARCHAR(100) NOT NULL,
				from_state VARCHAR(40) NULL,
				to_state VARCHAR(40) NULL,
				context LONGTEXT NULL,
				ip_hash VARCHAR(64) NULL,
				created_at DATETIME NOT NULL,
				PRIMARY KEY  (id),
				KEY order_id (order_id),
				KEY action (action),
				KEY created_at (created_at)
			) {$charset};"
		);

		dbDelta(
			"CREATE TABLE {$deliver} (
				id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				order_id BIGINT UNSIGNED NOT NULL,
				report_version_id BIGINT UNSIGNED NULL,
				channel VARCHAR(20) NOT NULL DEFAULT 'email',
				status VARCHAR(20) NOT NULL,
				recipient_hash VARCHAR(64) NULL,
				provider_message_ref VARCHAR(190) NULL,
				failure_code VARCHAR(60) NULL,
				failure_message TEXT NULL,
				attempt_number SMALLINT UNSIGNED NOT NULL DEFAULT 1,
				created_at DATETIME NOT NULL,
				PRIMARY KEY  (id),
				KEY order_id (order_id),
				KEY status (status),
				KEY created_at (created_at)
			) {$charset};"
		);

		dbDelta(
			"CREATE TABLE {$reports} (
				id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				order_id BIGINT UNSIGNED NOT NULL,
				version SMALLINT UNSIGNED NOT NULL DEFAULT 1,
				storage_key VARCHAR(255) NOT NULL,
				original_filename VARCHAR(255) NOT NULL,
				mime_type VARCHAR(100) NOT NULL,
				size_bytes BIGINT UNSIGNED NOT NULL DEFAULT 0,
				sha256 CHAR(64) NOT NULL,
				prepared_by BIGINT UNSIGNED NULL,
				source_summary TEXT NULL,
				coverage_notes TEXT NULL,
				coverage_json LONGTEXT NULL,
				status VARCHAR(20) NOT NULL DEFAULT 'uploaded',
				superseded_at DATETIME NULL,
				created_at DATETIME NOT NULL,
				PRIMARY KEY  (id),
				UNIQUE KEY order_version (order_id, version),
				KEY sha256 (sha256),
				KEY status (status)
			) {$charset};"
		);

		update_option( self::VERSION_OPTION, self::VERSION );
	}

	/**
	 * Whether the installed schema is current.
	 *
	 * @return bool
	 */
	public static function needs_update(): bool {
		return (int) get_option( self::VERSION_OPTION, 0 ) < self::VERSION;
	}
}
