<?php
/**
 * Append-only audit trail.
 *
 * @package VehicleReportOrders
 */

declare( strict_types=1 );

namespace VehicleReportOrders\Audit;

defined( 'ABSPATH' ) || exit;

/**
 * Records who did what to an order, and when.
 *
 * Entries are never edited or deleted by the application: a refund, a replaced
 * report or a failed permission check all have to remain visible afterwards.
 * Metadata is redacted before storage so the trail never becomes a second copy
 * of the customer's personal data.
 */
final class AuditLogger {

	public const TABLE = 'vr_audit_events';

	/**
	 * Keys whose values must never be written to the audit trail.
	 *
	 * @var list<string>
	 */
	private const REDACTED_KEYS = array( 'email', 'customer_email', 'password', 'token', 'download_token', 'card', 'phone', 'api_key', 'secret' );

	/**
	 * Fully qualified table name.
	 *
	 * @return string
	 */
	public static function table(): string {
		global $wpdb;

		return $wpdb->prefix . self::TABLE;
	}

	/**
	 * Record one event.
	 *
	 * @param int                  $order_id   WooCommerce order the event belongs to, or 0.
	 * @param string               $action     Short machine name, e.g. "report.uploaded".
	 * @param array<string, mixed> $context    Extra detail; sensitive keys are redacted.
	 * @param string|null          $from_state Previous fulfilment state, when relevant.
	 * @param string|null          $to_state   New fulfilment state, when relevant.
	 *
	 * @return void
	 */
	public static function log( int $order_id, string $action, array $context = array(), ?string $from_state = null, ?string $to_state = null ): void {
		global $wpdb;

		$actor_id = function_exists( 'get_current_user_id' ) ? (int) get_current_user_id() : 0;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Dedicated audit table; no cache applies to an append-only log.
		$wpdb->insert(
			self::table(),
			array(
				'order_id'   => $order_id,
				'actor_id'   => $actor_id > 0 ? $actor_id : null,
				'action'     => substr( $action, 0, 100 ),
				'from_state' => $from_state,
				'to_state'   => $to_state,
				'context'    => (string) wp_json_encode( self::redact( $context ) ),
				'ip_hash'    => self::hashed_ip(),
				'created_at' => current_time( 'mysql', true ),
			),
			array( '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s' )
		);
	}

	/**
	 * Replace sensitive values with a marker, recursively.
	 *
	 * @param array<string, mixed> $context Raw context.
	 *
	 * @return array<string, mixed>
	 */
	public static function redact( array $context ): array {
		$clean = array();

		foreach ( $context as $key => $value ) {
			$lower = strtolower( (string) $key );

			foreach ( self::REDACTED_KEYS as $needle ) {
				if ( str_contains( $lower, $needle ) ) {
					$clean[ $key ] = '[redacted]';

					continue 2;
				}
			}

			if ( is_array( $value ) ) {
				$clean[ $key ] = self::redact( $value );

				continue;
			}

			$clean[ $key ] = is_scalar( $value ) || null === $value ? $value : '[object]';
		}

		return $clean;
	}

	/**
	 * Salted hash of the actor's IP: enough to spot repeated abuse, not enough
	 * to rebuild a browsing history.
	 *
	 * @return string
	 */
	private static function hashed_ip(): string {
		$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( (string) $_SERVER['REMOTE_ADDR'] ) ) : '';

		return '' === $ip ? '' : substr( hash_hmac( 'sha256', $ip, wp_salt( 'auth' ) ), 0, 32 );
	}
}
