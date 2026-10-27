<?php

declare( strict_types=1 );

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use VehicleReportOrders\Domain\FulfillmentStatus;

final class FulfillmentStatusTest extends TestCase {

	public function test_a_fresh_order_is_not_ready(): void {
		$this->assertSame( FulfillmentStatus::NOT_READY, FulfillmentStatus::from_stored( null ) );
		$this->assertSame( FulfillmentStatus::NOT_READY, FulfillmentStatus::from_stored( 'nonsense' ) );
		$this->assertSame( FulfillmentStatus::DELIVERED, FulfillmentStatus::from_stored( 'delivered' ) );
	}

	public function test_work_only_starts_after_payment_puts_it_in_the_queue(): void {
		$this->assertTrue( FulfillmentStatus::NOT_READY->can_move_to( FulfillmentStatus::AWAITING_STAFF ) );
		$this->assertFalse( FulfillmentStatus::NOT_READY->can_move_to( FulfillmentStatus::IN_PROGRESS ) );
		$this->assertFalse( FulfillmentStatus::NOT_READY->can_move_to( FulfillmentStatus::DELIVERED ) );
	}

	public function test_a_report_cannot_be_delivered_before_it_is_uploaded(): void {
		$this->assertFalse( FulfillmentStatus::AWAITING_STAFF->can_move_to( FulfillmentStatus::DELIVERED ) );
		$this->assertFalse( FulfillmentStatus::IN_PROGRESS->can_move_to( FulfillmentStatus::DELIVERED ) );
		$this->assertTrue( FulfillmentStatus::REPORT_UPLOADED->can_move_to( FulfillmentStatus::DELIVERED ) );
	}

	public function test_a_failed_delivery_can_be_retried_or_escalated(): void {
		$this->assertTrue( FulfillmentStatus::DELIVERY_FAILED->can_move_to( FulfillmentStatus::DELIVERED ) );
		$this->assertTrue( FulfillmentStatus::DELIVERY_FAILED->can_move_to( FulfillmentStatus::CANNOT_FULFILL ) );
	}

	public function test_a_delivered_report_can_be_replaced_but_not_cancelled(): void {
		$this->assertTrue( FulfillmentStatus::DELIVERED->can_move_to( FulfillmentStatus::REPORT_UPLOADED ) );
		$this->assertFalse( FulfillmentStatus::DELIVERED->can_move_to( FulfillmentStatus::CANCELLED ) );
	}

	public function test_a_cancelled_order_is_the_end_of_the_line(): void {
		$this->assertTrue( FulfillmentStatus::CANCELLED->is_final() );

		foreach ( FulfillmentStatus::cases() as $status ) {
			$this->assertFalse(
				FulfillmentStatus::CANCELLED->can_move_to( $status ),
				sprintf( 'Cancelled must not move to %s.', $status->value )
			);
		}
	}

	public function test_no_status_can_move_to_itself(): void {
		foreach ( FulfillmentStatus::cases() as $status ) {
			$this->assertFalse( $status->can_move_to( $status ), sprintf( '%s moved to itself.', $status->value ) );
		}
	}

	public function test_every_status_declares_its_transitions_and_a_label(): void {
		$transitions = FulfillmentStatus::transitions();

		foreach ( FulfillmentStatus::cases() as $status ) {
			$this->assertArrayHasKey( $status->value, $transitions );
			$this->assertNotSame( '', $status->label() );
		}
	}

	public function test_only_a_delivered_report_is_readable_by_the_customer(): void {
		foreach ( FulfillmentStatus::cases() as $status ) {
			$this->assertSame(
				FulfillmentStatus::DELIVERED === $status,
				$status->grants_customer_access(),
				sprintf( '%s decided customer access incorrectly.', $status->value )
			);
		}
	}

	public function test_outstanding_work_is_what_staff_still_owe(): void {
		$this->assertTrue( FulfillmentStatus::AWAITING_STAFF->is_outstanding() );
		$this->assertTrue( FulfillmentStatus::REPORT_UPLOADED->is_outstanding() );
		$this->assertFalse( FulfillmentStatus::DELIVERED->is_outstanding() );
		$this->assertFalse( FulfillmentStatus::CANCELLED->is_outstanding() );
		$this->assertFalse( FulfillmentStatus::NOT_READY->is_outstanding() );
	}
}
