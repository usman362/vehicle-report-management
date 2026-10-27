<?php
/**
 * Signed, expiring report download tokens.
 *
 * @package VehicleReportOrders
 */

declare( strict_types=1 );

namespace VehicleReportOrders\Reports;

defined( 'ABSPATH' ) || exit;

/**
 * A capability token for one report file.
 *
 * The link in a delivery email is the only thing standing between a forwarded
 * message and someone else's vehicle history, so a token:
 *
 *   - names exactly one order and one report version;
 *   - expires;
 *   - is signed with a secret held per order, so regenerating that secret
 *     revokes every link already sent without touching anything else;
 *   - is compared in constant time, so a mismatch leaks no timing information.
 *
 * The class is free of WordPress calls so the rules can be unit tested.
 */
final class DownloadToken {

	/**
	 * Build a token string.
	 *
	 * @param int    $order_id   Order identifier.
	 * @param int    $version_id Report version identifier.
	 * @param int    $expires_at Unix timestamp the token stops working.
	 * @param string $secret     Per-order secret.
	 *
	 * @return string
	 */
	public static function create( int $order_id, int $version_id, int $expires_at, string $secret ): string {
		$payload = self::payload( $order_id, $version_id, $expires_at );

		return $payload . '.' . self::sign( $payload, $secret );
	}

	/**
	 * Validate a token and return what it grants.
	 *
	 * @param string $token  Token from the request.
	 * @param string $secret Per-order secret, looked up from the claimed order.
	 * @param int    $now    Current Unix timestamp.
	 *
	 * @return array{valid: bool, reason: string, order_id: int, version_id: int, expires_at: int}
	 */
	public static function verify( string $token, string $secret, int $now ): array {
		$claim = self::claim( $token );

		if ( null === $claim ) {
			return self::invalid( 'malformed' );
		}

		$payload   = self::payload( $claim['order_id'], $claim['version_id'], $claim['expires_at'] );
		$expected  = self::sign( $payload, $secret );
		$signature = $claim['signature'];

		if ( ! hash_equals( $expected, $signature ) ) {
			return self::invalid( 'signature' );
		}

		if ( $claim['expires_at'] <= $now ) {
			return array(
				'valid'      => false,
				'reason'     => 'expired',
				'order_id'   => $claim['order_id'],
				'version_id' => $claim['version_id'],
				'expires_at' => $claim['expires_at'],
			);
		}

		return array(
			'valid'      => true,
			'reason'     => 'ok',
			'order_id'   => $claim['order_id'],
			'version_id' => $claim['version_id'],
			'expires_at' => $claim['expires_at'],
		);
	}

	/**
	 * Read the unverified contents of a token.
	 *
	 * Needed before verification to know which order's secret to load. Nothing
	 * may be trusted until verify() has succeeded.
	 *
	 * @param string $token Token from the request.
	 *
	 * @return array{order_id: int, version_id: int, expires_at: int, signature: string}|null
	 */
	public static function claim( string $token ): ?array {
		$parts = explode( '.', trim( $token ) );

		if ( 2 !== count( $parts ) ) {
			return null;
		}

		$fields = explode( '-', $parts[0] );

		if ( 3 !== count( $fields ) ) {
			return null;
		}

		foreach ( $fields as $field ) {
			if ( 1 !== preg_match( '/^\d{1,19}$/', $field ) ) {
				return null;
			}
		}

		if ( 1 !== preg_match( '/^[0-9a-f]{64}$/', $parts[1] ) ) {
			return null;
		}

		return array(
			'order_id'   => (int) $fields[0],
			'version_id' => (int) $fields[1],
			'expires_at' => (int) $fields[2],
			'signature'  => $parts[1],
		);
	}

	/**
	 * Canonical payload string.
	 *
	 * @param int $order_id   Order identifier.
	 * @param int $version_id Report version identifier.
	 * @param int $expires_at Expiry timestamp.
	 *
	 * @return string
	 */
	private static function payload( int $order_id, int $version_id, int $expires_at ): string {
		return sprintf( '%d-%d-%d', $order_id, $version_id, $expires_at );
	}

	/**
	 * Signature for a payload.
	 *
	 * @param string $payload Canonical payload.
	 * @param string $secret  Per-order secret.
	 *
	 * @return string
	 */
	private static function sign( string $payload, string $secret ): string {
		return hash_hmac( 'sha256', $payload, $secret );
	}

	/**
	 * Build an invalid result.
	 *
	 * @param string $reason Why it failed.
	 *
	 * @return array{valid: bool, reason: string, order_id: int, version_id: int, expires_at: int}
	 */
	private static function invalid( string $reason ): array {
		return array(
			'valid'      => false,
			'reason'     => $reason,
			'order_id'   => 0,
			'version_id' => 0,
			'expires_at' => 0,
		);
	}
}
