<?php

declare( strict_types=1 );

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use VehicleReportOrders\Reports\DownloadToken;

final class DownloadTokenTest extends TestCase {

	private const SECRET = 'order-secret-abcdef0123456789';

	private const NOW = 1_800_000_000;

	public function test_a_fresh_token_grants_exactly_what_it_names(): void {
		$token  = DownloadToken::create( 42, 7, self::NOW + 3600, self::SECRET );
		$result = DownloadToken::verify( $token, self::SECRET, self::NOW );

		$this->assertTrue( $result['valid'] );
		$this->assertSame( 42, $result['order_id'] );
		$this->assertSame( 7, $result['version_id'] );
	}

	public function test_an_expired_token_is_refused(): void {
		$token  = DownloadToken::create( 42, 7, self::NOW - 1, self::SECRET );
		$result = DownloadToken::verify( $token, self::SECRET, self::NOW );

		$this->assertFalse( $result['valid'] );
		$this->assertSame( 'expired', $result['reason'] );
	}

	public function test_another_orders_secret_cannot_open_this_report(): void {
		$token  = DownloadToken::create( 42, 7, self::NOW + 3600, self::SECRET );
		$result = DownloadToken::verify( $token, 'a-different-orders-secret', self::NOW );

		$this->assertFalse( $result['valid'] );
		$this->assertSame( 'signature', $result['reason'] );
	}

	public function test_editing_the_order_or_version_breaks_the_signature(): void {
		$token = DownloadToken::create( 42, 7, self::NOW + 3600, self::SECRET );
		[ $payload, $signature ] = explode( '.', $token );
		[ , $version, $expires ] = explode( '-', $payload );

		$tampered = sprintf( '%d-%s-%s.%s', 43, $version, $expires, $signature );

		$this->assertFalse( DownloadToken::verify( $tampered, self::SECRET, self::NOW )['valid'] );
	}

	public function test_extending_the_expiry_breaks_the_signature(): void {
		$token = DownloadToken::create( 42, 7, self::NOW - 1, self::SECRET );
		[ $payload, $signature ] = explode( '.', $token );
		[ $order, $version ]     = explode( '-', $payload );

		$tampered = sprintf( '%s-%s-%d.%s', $order, $version, self::NOW + 86400, $signature );

		$this->assertFalse( DownloadToken::verify( $tampered, self::SECRET, self::NOW )['valid'] );
	}

	public function test_rotating_the_order_secret_revokes_links_already_sent(): void {
		$token = DownloadToken::create( 42, 7, self::NOW + 3600, self::SECRET );

		$this->assertTrue( DownloadToken::verify( $token, self::SECRET, self::NOW )['valid'] );
		$this->assertFalse( DownloadToken::verify( $token, 'rotated-secret', self::NOW )['valid'] );
	}

	/**
	 * @dataProvider malformedTokens
	 */
	public function test_malformed_tokens_are_refused_without_touching_storage( string $token ): void {
		$this->assertNull( DownloadToken::claim( $token ) );

		$result = DownloadToken::verify( $token, self::SECRET, self::NOW );
		$this->assertFalse( $result['valid'] );
		$this->assertSame( 'malformed', $result['reason'] );
	}

	public static function malformedTokens(): array {
		return array(
			'empty'            => array( '' ),
			'no signature'     => array( '42-7-1800000000' ),
			'short signature'  => array( '42-7-1800000000.abcd' ),
			'not hex'          => array( '42-7-1800000000.' . str_repeat( 'z', 64 ) ),
			'missing field'    => array( '42-7.' . str_repeat( 'a', 64 ) ),
			'negative order'   => array( '-42-7-1800000000.' . str_repeat( 'a', 64 ) ),
			'path traversal'   => array( '../../etc/passwd' ),
			'sql fragment'     => array( "42-7-1800000000' OR '1'='1." . str_repeat( 'a', 64 ) ),
		);
	}

	public function test_a_claim_can_be_read_before_verification_but_proves_nothing(): void {
		$token = DownloadToken::create( 42, 7, self::NOW + 60, self::SECRET );
		$claim = DownloadToken::claim( $token );

		$this->assertSame( 42, $claim['order_id'] );
		// The same claim with a forged signature still fails verification.
		$forged = sprintf( '%d-%d-%d.%s', 42, 7, self::NOW + 60, str_repeat( 'b', 64 ) );
		$this->assertFalse( DownloadToken::verify( $forged, self::SECRET, self::NOW )['valid'] );
	}
}
