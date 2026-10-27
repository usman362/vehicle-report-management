<?php

declare( strict_types=1 );

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use VehicleReportOrders\Domain\Package;
use VehicleReportOrders\Domain\VehicleCheck;

final class PackageTest extends TestCase {

	public function test_it_keeps_only_checks_that_exist(): void {
		$package = new Package( 'basic', 'Basic', 0, [ 'keepers', 'teleportation', 'valuation' ] );

		$this->assertSame( [ 'keepers', 'valuation' ], $package->checks() );
	}

	public function test_it_orders_checks_as_the_catalogue_publishes_them(): void {
		$package = new Package( 'basic', 'Basic', 0, [ 'valuation', 'vehicle_details' ] );

		$this->assertSame( [ 'vehicle_details', 'valuation' ], $package->checks(), 'The storefront and the package card must list checks in one order.' );
	}

	public function test_a_package_without_a_product_cannot_be_sold(): void {
		$package = new Package( 'basic', 'Basic', 0, [ 'keepers' ] );

		$this->assertNull( $package->price() );
		$this->assertFalse( $package->is_purchasable(), 'Nothing may be sold before the merchant has set a price.' );
		$this->assertSame( '', $package->formatted_price() );
		$this->assertSame( '', $package->url() );
	}

	public function test_it_covers_at_least_one_vehicle(): void {
		$this->assertSame( 1, ( new Package( 'basic', 'Basic', 0, [], 0 ) )->reports() );
		$this->assertSame( 2, ( new Package( 'silver', 'Silver', 0, [], 2 ) )->reports() );
	}

	public function test_the_snapshot_records_what_was_promised(): void {
		$snapshot = ( new Package( 'silver', 'Silver', 0, [ 'keepers', 'finance' ] ) )->snapshot();

		$this->assertSame( 'silver', $snapshot['slug'] );
		$this->assertSame( 'Silver', $snapshot['name'] );
		$this->assertSame( [ 'keepers', 'finance' ], $snapshot['checks'] );
		$this->assertNull( $snapshot['price'], 'An unpriced package records no price rather than a zero.' );
	}
}
