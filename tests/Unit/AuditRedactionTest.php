<?php

declare( strict_types=1 );

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use VehicleReportOrders\Audit\AuditLogger;

final class AuditRedactionTest extends TestCase {

	public function test_it_removes_personal_and_secret_values(): void {
		$clean = AuditLogger::redact(
			array(
				'order_id'       => 42,
				'customer_email' => 'buyer@example.com',
				'phone'          => '+44 7700 900000',
				'download_token' => 'abc123',
				'api_key'        => 'sk_live_xyz',
			)
		);

		$this->assertSame( 42, $clean['order_id'] );
		$this->assertSame( '[redacted]', $clean['customer_email'] );
		$this->assertSame( '[redacted]', $clean['phone'] );
		$this->assertSame( '[redacted]', $clean['download_token'] );
		$this->assertSame( '[redacted]', $clean['api_key'] );
	}

	public function test_it_redacts_nested_values_too(): void {
		$clean = AuditLogger::redact(
			array(
				'delivery' => array(
					'attempt'   => 2,
					'recipient' => array( 'email' => 'buyer@example.com' ),
				),
			)
		);

		$this->assertSame( 2, $clean['delivery']['attempt'] );
		$this->assertSame( '[redacted]', $clean['delivery']['recipient']['email'] );
	}

	public function test_it_keeps_the_facts_an_investigation_needs(): void {
		$clean = AuditLogger::redact(
			array(
				'registration' => 'AB12CDE',
				'sha256'       => str_repeat( 'a', 64 ),
				'from'         => 'report_uploaded',
				'to'           => 'delivered',
			)
		);

		$this->assertSame( 'AB12CDE', $clean['registration'] );
		$this->assertSame( str_repeat( 'a', 64 ), $clean['sha256'] );
		$this->assertSame( 'delivered', $clean['to'] );
	}

	public function test_it_never_stores_an_object(): void {
		$clean = AuditLogger::redact( array( 'thing' => new \stdClass() ) );

		$this->assertSame( '[object]', $clean['thing'] );
	}
}
