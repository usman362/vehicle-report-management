<?php

declare( strict_types=1 );

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use VehicleReportOrders\Domain\Vin;

final class VinTest extends TestCase {

	/**
	 * @dataProvider messyInput
	 */
	public function test_it_normalizes_what_the_customer_typed( string $input, string $expected ): void {
		$this->assertSame( $expected, Vin::normalize( $input ) );
	}

	public static function messyInput(): array {
		return [
			'already clean'   => [ 'WVWZZZ1KZAW123456', 'WVWZZZ1KZAW123456' ],
			'lower case'      => [ 'wvwzzz1kzaw123456', 'WVWZZZ1KZAW123456' ],
			'spaced out'      => [ 'WVW ZZZ 1KZ AW1 23456', 'WVWZZZ1KZAW123456' ],
			'hyphenated'      => [ 'WVW-ZZZ-1KZ-AW123456', 'WVWZZZ1KZAW123456' ],
			'padded'          => [ "  WVWZZZ1KZAW123456\n", 'WVWZZZ1KZAW123456' ],
		];
	}

	/**
	 * @dataProvider plausible
	 */
	public function test_it_accepts_a_well_formed_vin( string $input ): void {
		$this->assertTrue( Vin::is_plausible( Vin::normalize( $input ) ) );
	}

	public static function plausible(): array {
		return [
			'volkswagen style' => [ 'WVWZZZ1KZAW123456' ],
			'all digits'       => [ '12345678901234567' ],
			'mixed'            => [ 'SAJAA01N83FM12345' ],
		];
	}

	/**
	 * @dataProvider implausible
	 */
	public function test_it_refuses_anything_that_cannot_be_a_vin( string $input, string $why ): void {
		$this->assertFalse( Vin::is_plausible( Vin::normalize( $input ) ), $why );
	}

	public static function implausible(): array {
		return [
			'too short'       => [ 'WVWZZZ1KZAW12345', 'A VIN is exactly 17 characters.' ],
			'too long'        => [ 'WVWZZZ1KZAW1234567', 'A VIN is exactly 17 characters.' ],
			'contains I'      => [ 'WVWZZI1KZAW123456', 'I is never used: it reads as 1.' ],
			'contains O'      => [ 'WVWZZO1KZAW123456', 'O is never used: it reads as 0.' ],
			'contains Q'      => [ 'WVWZZQ1KZAW123456', 'Q is never used.' ],
			'empty'           => [ '', 'Nothing was entered.' ],
			'punctuation only' => [ '-- --', 'Nothing survives normalisation.' ],
		];
	}

	public function test_a_well_formed_vin_is_not_a_claim_about_a_vehicle(): void {
		// The shape is all this class can check. Whether the vehicle exists is
		// established during manual fulfilment, never here.
		$this->assertTrue( Vin::is_plausible( '11111111111111111' ) );
	}
}
