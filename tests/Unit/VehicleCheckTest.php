<?php

declare( strict_types=1 );

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use VehicleReportOrders\Domain\VehicleCheck;

final class VehicleCheckTest extends TestCase {

	public function test_every_check_states_what_it_cannot_tell_you(): void {
		foreach ( VehicleCheck::catalogue() as $key => $check ) {
			$this->assertNotSame( '', trim( $check['label'] ), "{$key} has no label." );
			$this->assertNotSame( '', trim( $check['blurb'] ), "{$key} has no description." );
			$this->assertNotSame(
				'',
				trim( $check['limit'] ),
				"{$key} has no stated limit. A check described without its limits reads as a guarantee."
			);
		}
	}

	public function test_it_filters_to_known_checks_in_catalogue_order(): void {
		$this->assertSame(
			[ 'vehicle_details', 'finance' ],
			VehicleCheck::filter( [ 'finance', 'nonsense', 'vehicle_details', 'finance' ] )
		);
	}

	public function test_an_unknown_slug_falls_back_to_itself(): void {
		$this->assertSame( 'nonsense', VehicleCheck::label( 'nonsense' ) );
		$this->assertNull( VehicleCheck::get( 'nonsense' ) );
		$this->assertFalse( VehicleCheck::exists( 'nonsense' ) );
	}
}
