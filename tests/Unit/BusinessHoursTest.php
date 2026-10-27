<?php

declare( strict_types=1 );

namespace Tests\Unit;

use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\TestCase;
use VehicleReportOrders\Domain\BusinessHours;

final class BusinessHoursTest extends TestCase {

	private function weekdays_nine_to_five(): BusinessHours {
		return new BusinessHours( array( 1, 2, 3, 4, 5 ), '09:00', '17:00', new DateTimeZone( 'Europe/London' ) );
	}

	private function moment( string $value ): DateTimeImmutable {
		return new DateTimeImmutable( $value, new DateTimeZone( 'Europe/London' ) );
	}

	public function test_hours_inside_the_working_day_are_simply_added(): void {
		$deadline = $this->weekdays_nine_to_five()->deadline_after( $this->moment( '2026-10-27 10:00' ), 4 );

		$this->assertSame( '2026-10-27 14:00', $deadline?->format( 'Y-m-d H:i' ) );
	}

	public function test_the_clock_stops_overnight(): void {
		// 16:00 Tuesday + 4 business hours: one hour today, three from 09:00 Wednesday.
		$deadline = $this->weekdays_nine_to_five()->deadline_after( $this->moment( '2026-10-27 16:00' ), 4 );

		$this->assertSame( '2026-10-28 12:00', $deadline?->format( 'Y-m-d H:i' ) );
	}

	public function test_an_order_placed_at_night_starts_when_the_business_opens(): void {
		$deadline = $this->weekdays_nine_to_five()->deadline_after( $this->moment( '2026-10-27 23:30' ), 2 );

		$this->assertSame( '2026-10-28 11:00', $deadline?->format( 'Y-m-d H:i' ) );
	}

	public function test_the_weekend_does_not_count(): void {
		// Friday 16:00 + 4 business hours lands on Monday, not Saturday.
		$deadline = $this->weekdays_nine_to_five()->deadline_after( $this->moment( '2026-10-30 16:00' ), 4 );

		$this->assertSame( '2026-11-02 12:00', $deadline?->format( 'Y-m-d H:i' ) );
	}

	public function test_a_saturday_order_waits_for_monday(): void {
		$deadline = $this->weekdays_nine_to_five()->deadline_after( $this->moment( '2026-10-31 10:00' ), 1 );

		$this->assertSame( '2026-11-02 10:00', $deadline?->format( 'Y-m-d H:i' ) );
	}

	public function test_a_long_window_spans_several_days(): void {
		// 24 business hours at eight hours a day: three full working days.
		$deadline = $this->weekdays_nine_to_five()->deadline_after( $this->moment( '2026-10-27 09:00' ), 24 );

		$this->assertSame( '2026-10-29 17:00', $deadline?->format( 'Y-m-d H:i' ) );
	}

	public function test_a_business_open_at_the_weekend_uses_its_own_days(): void {
		$hours    = new BusinessHours( array( 6, 7 ), '10:00', '16:00', new DateTimeZone( 'Europe/London' ) );
		$deadline = $hours->deadline_after( $this->moment( '2026-10-30 12:00' ), 3 );

		$this->assertSame( '2026-10-31 13:00', $deadline?->format( 'Y-m-d H:i' ) );
	}

	public function test_it_refuses_to_invent_a_deadline_without_opening_days(): void {
		$hours = new BusinessHours( array(), '09:00', '17:00', new DateTimeZone( 'Europe/London' ) );

		$this->assertFalse( $hours->is_configured() );
		$this->assertNull( $hours->deadline_after( $this->moment( '2026-10-27 10:00' ), 4 ) );
		$this->assertNull( $this->weekdays_nine_to_five()->deadline_after( $this->moment( '2026-10-27 10:00' ), 0 ) );
	}

	public function test_it_describes_its_opening_hours_for_the_storefront(): void {
		$names = array( 1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday', 5 => 'Friday', 6 => 'Saturday', 7 => 'Sunday' );

		$this->assertSame( 'Monday–Friday, 9:00–17:00', $this->weekdays_nine_to_five()->describe( $names ) );

		$split = new BusinessHours( array( 1, 3, 5 ), '09:30', '16:00', new DateTimeZone( 'Europe/London' ) );
		$this->assertSame( 'Monday, Wednesday, Friday, 9:30–16:00', $split->describe( $names ) );
	}

	public function test_a_closing_time_before_opening_is_corrected_rather_than_looping(): void {
		$hours    = new BusinessHours( array( 1, 2, 3, 4, 5 ), '09:00', '08:00', new DateTimeZone( 'Europe/London' ) );
		$deadline = $hours->deadline_after( $this->moment( '2026-10-27 09:00' ), 1 );

		$this->assertSame( '2026-10-27 10:00', $deadline?->format( 'Y-m-d H:i' ) );
	}
}
